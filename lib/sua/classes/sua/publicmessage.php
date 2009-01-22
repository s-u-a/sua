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
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Repräsentiert eine veröffentlichte Nachricht im Spiel.
	*/

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
		 * @param Message $message
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
		 * @return void
		*/

		protected function _read()
		{
			$this->setMainField("last_view", time());
		}

		/**
		 * Gibt zurück, wann die Nachricht zuletzt betrachtet wurde, also wann zuletzt _read() ausgeführt wurde.
		 * @return int
		*/

		function getLastViewTime()
		{
			return $this->getMainField("last_view");
		}

		/**
		 * Setzt oder liest den Text der Nachricht.
		 * @param string $text Der neue Text der Nachricht, oder null, wenn der aktuelle zurückgegeben werden soll
		 * @param bool $filter Sollen Angreifer- und Verteidigerkoordinaten herausgefiltert werden? (Nur sinnvoll, wenn $text null ist)
		 * @return void Wenn $text gesetzt ist.
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
				$this->setMainField("parsed", F::parseHTML($text));
		}

		/**
		 * Liest oder setzt den Absender der Nachricht.
		 * @param string $from Der neue Absender. Wenn null, wird der aktuelle zurückgegeben.
		 * @return void Wenn $from übergeben wurde.
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
		 * @param string $subject Der neue Betreff oder null, wenn der Betreff ausgelesen werden soll.
		 * @return void Wenn $subject gesetzt ist
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
		 * @param bool $html Ist die Nachricht eine HTML-Nachricht?
		 * @return void Wenn $html gesetzt ist.
		 * @return bool Wenn $html null ist, ob die Nachricht eine HTML-Nachricht ist.
		*/

		function html($html=null)
		{
			if(!isset($html))
				return (true && $this->getMainField("html"));
			else
				$this->setMainField("html", $html ? 1 : 0);
		}

		/**
		 * Gibt den Nachrichtentyp (Message::TYPE_*) zurück oder setzt diesen.
		 * @param int $type Der Nachrichtentyp (Message::TYPE_*) oder null, wenn der Typ zurückgegeben werden soll.
		 * @return int Message::TYPE_*, wenn $type null ist.
		 * @return void Wenn $type gesetzt ist.
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
		 * @param int $time Die Sendezeit, oder null, wenn sie zurückgegeben werden soll
		 * @return int Wenn $time null ist
		 * @return void Wenn $time gesetzt ist
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
		 * @param string $to Der Empfänger, oder null, wenn er zurückgegeben werden soll
		 * @return string Wenn $to null ist
		 * @return void Wenn $to gesetzt ist
		*/

		function to($to=null)
		{
			if(!isset($to))
				return $this->getMainField("receiver");
			else
				$this->setMainField("receiver", $to);
		}

		/**
		 * Löscht alle öffentlichen Nachrichten, die seit langer Zeit (public_messages_time in der Konfiguration)
		 * nicht mehr gelesen wurden.
		 * @return int Die Anzahl der gelöschten Nachrichten.
		*/

		static function cleanUp()
		{
			$public_messages_time = Config::getLibConfig()->getConfigValue("public_messages_time");
			if(!$public_messages_time)
				return 0;
			$count = self::$sqlite->singleField("SELECT COUNT(*) FROM public_messages WHERE last_view < ".(time()-$public_messages_time*86400).";");
			self::$sqlite->query("DELETE FROM public_messages WHERE last_view < ".(time()-$public_messages_time*86400).";");
			return $count;
		}
	}
