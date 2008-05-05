<?php
/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/

	/**
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage storage
	*/

	/**
	 * Repräsentiert eine veröffentlichte Nachricht im Spiel.
	*/

	class PublicMessageDatabase extends SQLite
	{
		protected static $tables = array("public_messages" => array("message_id TEXT PRIMARY KEY", "last_view INTEGER", "sender TEXT", "text TEXT", "parsed TEXT", "subject TEXT", "html INTEGER", "receiver TEXT", "time INTEGER", "type INTEGER"));
		protected static $id_field = "message_id";

		function getField($message_id, $field_name)
		{
			if(!$this->messageExists($message_id)) return false;

			$result = $this->singleQuery("SELECT ".$field_name." FROM public_messages WHERE message_id = ".$this->escape($message_id)." LIMIT 1;");
			if($result) $result = $result[$field_name];
			return $result;
		}

		function setField($message_id, $field_name, $field_value)
		{
			if(!$this->messageExists($message_id)) return false;

			return $this->query("UPDATE public_messages SET ".$field_name." = ".$this->escape($field_value)." WHERE message_id = ".$this->escape($message_id).";");
		}

		function getNewName()
		{
			do $name = substr(md5(rand()), 0, 16);
				while($this->messageExists($name));
			return $name;
		}

		function createNewMessage($message_id)
		{
			$this->query("INSERT INTO public_messages ( message_id, last_view ) VALUES ( ".$this->escape($message_id).", ".$this->escape(time())." );");
		}

		function messageExists($message_id)
		{
			return ($this->singleField("SELECT COUNT(*) FROM public_messages WHERE message_id = ".$this->escape($message_id)." LIMIT 1;") > 0);
		}

		function messagesCount()
		{
			return ($this->singleField("SELECT COUNT(*) FROM public_messages;"));
		}

		function cleanUp()
		{
			global $public_messages_time;

			$count = $this->singleField("SELECT COUNT(*) FROM public_messages WHERE last_view < ".(time()-$public_messages_time*86400).";");
			$this->query("DELETE FROM public_messages WHERE last_view < ".(time()-$public_messages_time*86400).";");
			return $count;
		}
	}

	class PublicMessage extends SQLiteSet
	{
		protected $tables = array("public_messages" => array("message_id PRIMARY KEY", "last_view INT", "sender", "text", "parsed", "subject", "html INT", "receiver", "time", "type"));

		static function create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new DatasetException("Dataset already exists.");

			self::$sqlite->query("INSERT INTO public_messages ( message_id, last_view ) VALUES ( ".self::$sqlite->escape($message_id).", ".self::$sqlite->escape(time())." );");
			return $name;
		}

		/**
		 * Veröffentlicht die Nachricht $message.
		 * @param $message Message
		 * @return string Die ID des PublicMessage-Objekts
		*/

		function createFromMessage($message)
		{
			$pm = Classes::PublicMessage(self::create());
			$pm->html($message->html());
			$pm->text($message->text());
			$pm->subject($message->subject());
			$pm->time($message->getTime());
			$pm->from($message->from());
			return $pm->getName();
		}

		/**
		 * Die Nachricht wird vom Benutzer betrachtet, also wird die Zeit der letzten Betrachtung erneuert.
		 * Wichtig für automatische Löschung.
		 * @return null
		*/

		protected function _read()
		{
			$this->setMainField("last_view", time());
		}

		/**
		 * Gibt zurück, wann die Nachricht zuletzt betrachtet wurde, also wann zuletzt _read() ausgeführt wurde.
		 * @return integer
		*/

		function getLastViewTime()
		{
			return $this->getMainField("last_view");
		}

		/**
		 * Setzt oder liest den Text der Nachricht.
		 * @param $text string Der neue Text der Nachricht, oder null, wenn der aktuelle zurückgegeben werden soll
		 * @param $filter boolean Sollen Angreifer- und Verteidigerkoordinaten herausgefiltert werden? (Nur sinnvoll, wenn $text null ist)
		 * @return null Wenn $text gesetzt ist.
		 * @return String Der Text, wenn $text nicht null ist.
		*/

		function text($text=false, $filter=true)
		{
			// last_view erneuern
			$this->_read();

			if(!isset($text))
			{ // Text zurückgeben
				$text = $this->getMainField("parsed");
				if($filter)
				{
					$text = preg_replace('/ ?<span class="koords">.*?<\\/span>/', '', $text);
					$text = preg_replace('/ ?<span class="angreifer-name">.*?<\\/span>/', 'Ein Angreifer', $text);
					$text = preg_replace('/ ?<span class="verteidiger-name">.*?<\\/span>/', 'Ein Verteidiger', $text);
				}
				return $text;
			}

			// Text setzen
			$this->setMainField("text", $text);
			$this->_createParsed();
			if($this->html())
				$this->setMainField("parsed", $text);
			else
				$this->setMainField("parsed", F::parse_html($text));
		}

		/**
		 * Liest oder setzt den Absender der Nachricht.
		 * @param $from string Der neue Absender. Wenn null, wird der aktuelle zurückgegeben.
		 * @return null Wenn $from übergeben wurde.
		 * @return string Wenn $from null ist, wird der Absender zurückgegeben.
		*/

		function from($from=null)
		{
			if(!isset($from))
				return $this->getMainField("sender");
			else
				$this->setMainField("sender", $from);
		}

		/**
		 * Liest oder setzt den Betreff der Nachricht.
		 * @param $subject string Der neue Betreff oder null, wenn der Betreff ausgelesen werden soll.
		 * @return null Wenn $subject gesetzt ist
		 * @return string Der Betreff, wenn $subject null ist.
		*/

		function subject($subject=false)
		{
			if(!isset($subject))
				return $this->getMainField("subject");
			else
				$this->setMainField("subject", $subject);
		}

		/**
		 * Gibt zurück oder stellt ein, ob der Nachrichtentext im HTML-Format gespeichert wurde.
		 * @param $html boolean Ist die Nachricht eine HTML-Nachricht?
		 * @return null Wenn $html gesetzt ist.
		 * @return boolean Wenn $html null ist, ob die Nachricht eine HTML-Nachricht ist.
		*/

		function html($html=null)
		{
			if(!isset($html))
				return (true && $this->getMainField("html"));
			else
				$this->setMainField("html", $html ? 1 : 0);
		}

		/**
		 * Gibt den Nachrichtentyp (Message::$TYPE_*) zurück oder setzt diesen.
		 * @param $type integer Der Nachrichtentyp (Message::$TYPE_*) oder null, wenn der Typ zurückgegeben werden soll.
		 * @return integer Message::$TYPE_*, wenn $type null ist.
		 * @return null Wenn $type gesetzt ist.
		*/

		function type($type=null)
		{
			if(!isset($type))
				return $this->getMainField("type");
			else
				$this->setMainField("type", $type);
		}

		/**
		 * Setzt oder liest die Sendezeit der Nachricht.
		 * @param $time integer Die Sendezeit, oder null, wenn sie zurückgegeben werden soll
		 * @return integer Wenn $time null ist
		 * @return null Wenn $time gesetzt ist
		*/

		function time($time=null)
		{
			if(!isset($type))
				return $this->getMainField("time");
			else
				$this->setMainField("time", $time);
		}

		/**
		 * Setzt oder liest den Empfänger der Nachricht (also den, der sie veröffentlich hat).
		 * @param $to string Der Empfänger, oder null, wenn er zurückgegeben werden soll
		 * @return string Wenn $to null ist
		 * @return null Wenn $to gesetzt ist
		*/

		function to($to=null)
		{
			if(!isset($to))
				return $this->getMainField("receiver");
			else
				$this->setMainField("receiver", $to);
		}
	}
