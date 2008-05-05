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
	 * Repräsentiert eine Nachricht des internen Benutzer-Nachrichten-Systems.
	 * Eine Nachricht kann mehrere Empfänger haben, die unabhängig voneinander die Nachricht in ihrem Postfach
	 * erhalten. Löscht ein Benutzer die Nachricht aus seinem Postfach, so muss Message::removeUser()
	 * ausgeführt werden. Damit wird die Leseberechtigung des Benutzers auf die Nachricht entfernt, versucht
	 * er also, sie abzurufen, soll er eine Fehlermeldung erhalten. Erst wenn kein Benutzer mehr Leseberechtigung
	 * auf die Nachricht hat, wird sie aus der Datenbank entfernt.
	 * @author Candid Dauth
	*/

	class Message extends SQLiteSet
	{
		protected static $tables = array("messages" => array("message_id TEXT PRIMARY KEY", "time INTEGER", "text TEXT", "parsed_text TEXT", "sender TEXT", "subject TEXT", "html INTEGER"),
		                                 "messages_recipients" => array("message_id TEXT", "recipient TEXT"),
		                                 "messages_users" => array("message_id TEXT", "user TEXT"));
		protected static $id_field = "message_id";

		/** Array nach dem Schema ( Benutzername => Nachrichtentyp (Message::$TYPE_*) ). Beim Zerstören des
		Nachrichtenobjekts werden an die Benutzer IM-Nachrichten verschickt, wenn sie es wünschen. Der Grund,
		warum dies nicht sofort gemacht wird, ist, dass möglicherweise addUser() ausgeführt wird, bevor Dinge
		wie der Betreff oder der Absender gesetzt sind. */
		protected $im_check_notify = array();

		/** Nachricht des Typs Kampfbericht */
		static $TYPE_KAEMPFE = 1;
		/** Nachricht des Typs Spionagebericht */
		static $TYPE_SPIONAGE = 2;
		/** Nachricht des Typs Transportbenachrichtigung */
		static $TYPE_TRANSPORT = 3;
		/** Nachricht des Typs Sammelbenachrichtigung */
		static $TYPE_SAMMELN = 4;
		/** Nachricht des Typs Besiedelungsbenachrichtigung */
		static $TYPE_BESIEDELUNG = 5;
		/** Typ Benutzernachricht */
		static $TYPE_BENUTZERNACHRICHTEN = 6;
		/** Typ Bündnis- oder Allianznachricht */
		static $TYPE_VERBUENDETE = 7;
		/** Nachricht im Postausgang */
		static $TYPE_POSTAUSGANG = 8;

		/**
		 * Gibt die Zahl der gespeicherten Nachrichten zurück.
		 * @return integer
		*/

		static function getMessagesCount()
		{
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM messages;"));
		}

		/**
		 * Erzeugt die Nachricht mit der ID $name. Implementiert Dataset::create().
		 * @param $name string Die ID der Nachricht oder null für eine zufällige.
		 * @return null
		*/

		static function create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new DatasetException("Dataset already exists.");

			self::$sqlite->query("INSERT INTO messages ( message_id, time ) VALUES ( ".$this->escape($name).", ".$this->escape(time())." );");
			return $name;
		}

		/**
		 * Entfernt die Nachricht aus der Datenbank. Implementiert Dataset::destroy().
		 * @return null
		*/

		function destroy()
		{
			foreach($this->getUsersList() as $l)
			{
				$user = Classes::User($l);
				$user->removeMessage($this->name, null, false);
			}

			self::$sqlite->query("DELETE FROM messages WHERE message_id = ".$this->escape($message_id).";");
		}

		/**
		 * Wird aufgerufen, wenn ein Benutzer seinen Namen ändert. Ersetzt den Benutzernamen in allen Feldern, wo dieser steht (Absender, Empfänger, berechtigte Benutzer).
		 * @param $old_name string Der bisherige Name des Benutzers.
		 * @param $new_name string Der neue Benutzername.
		 * @return null
		*/

		function renameUser($old_name, $new_name)
		{
			self::$sqlite->query("UPDATE messages_users SET user = ".self::$sqlite->quote($new_name)." WHERE user = ".self::$sqlite->quote($old_name).";");
			self::$sqlite->query("UPDATE message_recipients SET recipient = ".self::$sqlite->quote($new_name)." WHERE recipient = ".self::$sqlite->quote($old_name).";");
			self::$sqlite->query("UPDATE messages SET sender = ".self::$sqlite->quote($new_name)." WHERE sender = ".self::$sqlite->quote($old_name).";");
		}

		/**
		 * Liest oder schreibt den Nachrichtentext.
		 * @param $text Wird als neuer Nachrichtentext gesetzt. Null für Rückgabe des aktuellen Textes. Sollte nur HTML-Code enthalten, wenn die Nachricht als HTML-Nachricht definiert wurde (Message::html()).
		 * @return null Wenn $text definiert wird
		 * @return string Wenn $text null ist: der aktuelle Nachrichtentext als HTML-Code
		*/

		function text($text=null)
		{
			if(!isset($text))
				return $this->getMainField($this->html() ? "text" : "parsed_text");
			else
			{
				$this->setMainField("text", $text);
				if(!$this->html())
					$this->setMainField("parsed_text", F::parse_html($text));
			}
		}

		/**
		 * Gibt den eingegebenen Nachrichtentext zurück, ohne diesen mit HTML zu formatieren.
		 * @return string
		*/

		function rawText()
		{
			return $this->getMainField("text");
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
		 * Fügt einen Benutzer zur Liste leseberechtigter Benutzer der Nachricht hinzu. Benachrichtigt ihn wenn gewünscht per Instant Messaging über den Eingang.
		 * @param $user string Der Benutzername
		 * @param $type integer Der Nachrichtentyp (Message::$TYPE_*). Benötigt für die Benachrichtigung.
		*/

		function addUser($user, $type=6)
		{
			self::$sqlite->query("INSERT INTO messages_users ( message_id, user ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user)." );");

			$user_obj = Classes::User($user);
			$user_obj->addMessage($this->name, $type);
			unset($user_obj);

			if($type != self::$TYPE_POSTAUSGANG)
			{
				self::$sqlite->query("INSERT INTO messages_recipients ( message_id, recipient ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user)." );");

				// IM-Benachrichtung, siehe __destroy()
				$this->im_check_notify[$user] = $type;
			}

			return true;
		}

		/**
		 * Löscht die Leseberechtigung eines Nutzers, normalerweise, weil dieser die Nachricht aus seinem Postfach löscht.
		 * Wenn keine Benutzer mehr eine Leseberechtigung haben, wird die Nachricht gelöscht.
		 * @param $user string Der Benutzername, der entfernt werden soll.
		 * @param $edit_user boolean Soll der Benutzeraccount bearbeitet werden und dort die Nachricht aus dem Postfach entfernt werden? (Standard: true)
		 * @return null
		*/

		function removeUser($user, $edit_user=true)
		{
			self::$sqlite->query("DELETE FROM messages_users WHERE message_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			if(self::$sqlite->singleQuery("SELECT COUNT(*) FROM message_users WHERE message_id = ".self::$sqlite->quote($this->getName()).";") < 1)
				$this->destroy();

			if($edit_user)
			{
				$user = Classes::User($user);
				$user->removeMessage($this->name, null, false);
			}
		}

		/**
		 * Gibt die Zeit zurück, zu der die Nachricht versandt wurde.
		 * @return integer
		*/

		function getTime()
		{
			return $this->getMainField("time");
		}

		/**
		 * Gibt ein Array der Benutzer zurück, die Leseberechtigung auf die Nachricht haben.
		 * @return array(string)
		*/

		function getUsersList()
		{
			return self::$sqlite->columnQuery("SELECT user FROM messages_users WHERE message_id = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Gibt ein Array der Benutzer zurück, an die die Nachricht gesandt wurde.
		 * @return array(string)
		*/

		function getRecipients()
		{
			return self::$sqlite->columnQuery("SELECT recipient FROM messages_recipients WHERE message_id = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Benachrichtigt die per addUser() hinzugefügten Benutzer auf Wunsch per Instant Messaging über den Eingang der Nachricht.
		 * @return null
		*/

		private function IMNotify()
		{
			if(count($this->im_check_notify) > 0)
			{
				$imfile = Classes::IMFile();
				foreach($this->im_check_notify as $user=>$type)
				{
					$user_obj = Classes::User($user);
					$im_settings = $user_obj->getNotificationType();
					if($im_settings)
					{
						$im_receive = $user_obj->checkSetting('messenger_receive');
						if($im_receive['messages'][$type])
							$imfile->addMessage($im_settings[0], $im_settings[1], $user, sprintf($this->from() ? $user_obj->_("Sie haben eine neue Nachricht der Sorte %s von %s erhalten. Der Betreff lautet: %s") : $user_obj->_("Sie haben eine neue Nachricht der Sorte %s erhalten. Der Betreff lautet: %3\$s"), $user_obj->_("[message_".$type."]"), $this->from(), $this->subject()));
					}
					unset($this->im_check_notify[$user]);
				}
			}
		}

		/**
		 * Die IM-Benachrichtung soll erst „zum Schluss“ erfolgen, wenn sicher ist, dass alle Werte wie from() und subject() gesetzt wurden.
		*/

		function __destruct()
		{
			$this->IMNotify();
		}

	}
