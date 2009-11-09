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
	 * Repräsentiert eine Nachricht des internen Benutzer-Nachrichten-Systems.
	 * Eine Nachricht kann mehrere Empfänger haben, die unabhängig voneinander die Nachricht in ihrem Postfach
	 * erhalten. Löscht ein Benutzer die Nachricht aus seinem Postfach, so muss Message::removeUser()
	 * ausgeführt werden. Damit wird die Leseberechtigung des Benutzers auf die Nachricht entfernt, versucht
	 * er also, sie abzurufen, soll er eine Fehlermeldung erhalten. Erst wenn kein Benutzer mehr Leseberechtigung
	 * auf die Nachricht hat, wird sie aus der Datenbank entfernt.
	*/

	class Message extends SQLSet
	{
		protected static $primary_key = array("t_messages", "c_message_id");

		/** Array nach dem Schema ( Benutzername => Nachrichtentyp (Message::TYPE_*) ). Beim Zerstören des
		 * Nachrichtenobjekts werden an die Benutzer IM-Nachrichten verschickt, wenn sie es wünschen. Der Grund,
		 * warum dies nicht sofort gemacht wird, ist, dass möglicherweise addUser() ausgeführt wird, bevor Dinge
		 * wie der Betreff oder der Absender gesetzt sind. */
		protected $im_check_notify = array();

		/**
		 * Nachricht des Typs Kampfbericht
		 * @var integer
		*/
		const TYPE_KAEMPFE = 1;

		/**
		 * Nachricht des Typs Spionagebericht
		 * @var integer
		*/
		const TYPE_SPIONAGE = 2;

		/**
		 * Nachricht des Typs Transportbenachrichtigung
		 * @var integer
		*/
		const TYPE_TRANSPORT = 3;

		/**
		 * Nachricht des Typs Sammelbenachrichtigung
		 * @var integer
		*/
		const TYPE_SAMMELN = 4;

		/**
		 * Nachricht des Typs Besiedelungsbenachrichtigung
		 * @var integer
		*/
		const TYPE_BESIEDELUNG = 5;

		/**
		 * Typ Benutzernachricht
		 * @var integer
		*/
		const TYPE_BENUTZERNACHRICHTEN = 6;

		/**
		 * Typ Bündnis- oder Allianznachricht
		 * @var integer
		*/
		const TYPE_VERBUENDETE = 7;

		/**
		 * Nachricht im Postausgang
		 * @var integer
		*/
		const TYPE_POSTAUSGANG = 8;

		/**
		 * Ungelesene Nachricht
		 * @var integer
		*/
		const STATUS_NEU = 1;

		/**
		 * Gelesene Nachricht
		 * @var integer
		*/
		const STATUS_ALT = 2;

		/**
		 * Archivierte Nachricht
		 * @var integer
		*/
		const STATUS_ARCHIV = 3;

		/**
		 * Gibt die Zahl der gespeicherten Nachrichten zurück.
		 * @return int
		*/

		static function getMessagesCount()
		{
			return (self::$sql->singleField("SELECT COUNT(*) FROM t_messages;"));
		}

		/**
		 * Erzeugt die Nachricht mit der ID $name. Implementiert Dataset::create().
		 * @param string $name Die ID der Nachricht oder null für eine zufällige.
		 * @return void
		*/

		static function create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new DatasetException("Dataset already exists.");

			self::$sql->query("INSERT INTO t_messages ( c_message_id, c_time ) VALUES ( ".$this->escape($name).", ".$this->escape(time())." );");
			return $name;
		}

		/**
		 * Entfernt die Nachricht aus der Datenbank. Implementiert Dataset::destroy().
		 * @return void
		*/

		function destroy()
		{
			self::$sql->query("DELETE FROM t_messages WHERE c_message_id = ".$this->escape($message_id).";");
		}

		/**
		 * Wird aufgerufen, wenn ein Benutzer seinen Namen ändert. Ersetzt den Benutzernamen in allen Feldern, wo dieser steht (Absender, Empfänger, berechtigte Benutzer).
		 * @param string $old_name Der bisherige Name des Benutzers.
		 * @param string $new_name Der neue Benutzername.
		 * @return void
		*/

		static function renameUser($old_name, $new_name)
		{
			self::$sql->query("UPDATE t_messages_users SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($old_name).";");
			self::$sql->query("UPDATE t_messages_recipients SET c_recipient = ".self::$sql->quote($new_name)." WHERE c_recipient = ".self::$sql->quote($old_name).";");
			self::$sql->query("UPDATE t_messages SET c_sender = ".self::$sql->quote($new_name)." WHERE c_sender = ".self::$sql->quote($old_name).";");
		}

		/**
		 * Liest oder schreibt den Nachrichtentext.
		 * @param Wird $text als neuer Nachrichtentext gesetzt. Null für Rückgabe des aktuellen Textes. Sollte nur HTML-Code enthalten, wenn die Nachricht als HTML-Nachricht definiert wurde (Message::html()).
		 * @return void Wenn $text definiert wird
		 * @return string Wenn $text null ist: der aktuelle Nachrichtentext als HTML-Code
		*/

		function text($text=null)
		{
			if(!isset($text))
				return $this->getMainField($this->html() ? "c_text" : "c_parsed_text");
			else
			{
				$this->setMainField("c_text", $text);
				if(!$this->html())
					$this->setMainField("c_parsed_text", FormattedString::parseHTML($text));
			}
		}

		/**
		 * Gibt den eingegebenen Nachrichtentext zurück, ohne diesen mit HTML zu formatieren.
		 * @return string
		*/

		function rawText()
		{
			return $this->getMainField("c_text");
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
				return $this->getMainField("c_sender");
			else
				$this->setMainField("c_sender", $from);
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
				return $this->getMainField("c_subject");
			else
				$this->setMainField("c_subject", $subject);
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
				return (true && $this->getMainField("c_html"));
			else
				$this->setMainField("c_html", true && $html);
		}

		/**
		 * Fügt einen Benutzer zur Liste leseberechtigter Benutzer der Nachricht hinzu. Benachrichtigt ihn wenn gewünscht per Instant Messaging über den Eingang.
		 * @param string $user Der Benutzername
		 * @param int $type Der Nachrichtentyp (Message::TYPE_*). Benötigt für die Benachrichtigung.
		 * @return void
		*/

		function addUser($user, $type=null)
		{
			if(!isset($type)) $type = self::TYPE_BENUTZERNACHRICHTEN;

			self::$sql->query("INSERT INTO t_messages_users ( c_message_id, c_user, c_type, c_status ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($user).", ".self::$sql->quote($type).", ".self::$sql->quote(self::STATUS_NEU)." );");

			if($type != self::TYPE_POSTAUSGANG)
			{
				self::$sql->query("INSERT INTO t_messages_recipients ( c_message_id, c_recipient ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($user)." );");

				// IM-Benachrichtung, siehe __destroy()
				$this->im_check_notify[$user] = $type;
			}
		}

		/**
		 * Findet heraus oder setzt, welchen Status die Nachricht im Postfach des Benutzers $user hat.
		 * @param string $user Der Benutzername
		 * @param int|null $status Der neue Status (Message::STATUS_*) oder null, wenn der aktuelle Status zurückgeliefert werden soll
		 * @return int|null Message::STATUS_*, wenn $status null ist
		*/

		function messageStatus($user, $status=null)
		{
			if(!isset($status))
				return self::$sql->singleField("SELECT c_status FROM t_messages_users WHERE c_message_id = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");
			else
				self::$sql->query("UPDATE t_messages_users SET c_status = ".self::$sql->quote($status)." WHERE c_message_id = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");
		}

		/**
		 * Findet heraus oder setzt, welchen Nachrichtentyp die Nachricht im Postfach des Benutzers $user besitzt.
		 * @param string $user Der Benutzername
		 * @param int|null $type Der neue Status (Message::TYPE_*) oder null, wenn der aktuelle Typ zurückgeliefert werden soll
		 * @return int|null Message::TYPE_*, wenn $type null ist
		*/

		function messageType($user, $type=null)
		{
			if(!isset($type))
				return self::$sql->singleField("SELECT c_type FROM t_messages_users WHERE c_message_id = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");
			else
				self::$sql->query("UPDATE t_messages_users SET c_type = ".self::$sql->quote($status)." WHERE c_message_id = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");
		}

		/**
		 * Löscht die Leseberechtigung eines Nutzers, normalerweise, weil dieser die Nachricht aus seinem Postfach löscht.
		 * Wenn keine Benutzer mehr eine Leseberechtigung haben, wird die Nachricht gelöscht.
		 * @param string $user Der Benutzername, der entfernt werden soll.
		 * @return void
		*/

		function removeUser($user)
		{
			self::$sql->query("DELETE FROM t_messages_users WHERE c_message_id = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");
			if(self::$sql->singleField("SELECT COUNT(*) FROM t_messages_users WHERE c_message_id = ".self::$sql->quote($this->getName()).";") < 1)
				$this->destroy();
		}

		/**
		 * Gibt die Zeit zurück, zu der die Nachricht versandt wurde.
		 * @return int
		*/

		function getTime()
		{
			return $this->getMainField("c_time");
		}

		/**
		 * Gibt ein Array der Benutzer zurück, die Leseberechtigung auf die Nachricht haben.
		 * @return array(string)
		*/

		function getUsersList()
		{
			return self::$sql->singleColumn("SELECT c_user FROM t_messages_users WHERE c_message_id = ".self::$sql->quote($this->getName()).";");
		}

		/**
		 * Gibt zurück, ob der angegebene Benutzer berechtigt ist, die Nachricht zu
		 * lesen.
		 * @param string $user
		 * @return bool
		*/

		function mayRead($user)
		{
			return (self::$sql->singleField("SELECT COUNT(*) FROM t_messages_users WHERE c_message_id = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user)." LIMIT 1;") > 0);
		}

		/**
		 * Gibt ein Array der Benutzer zurück, an die die Nachricht gesandt wurde.
		 * @return array(string)
		*/

		function getRecipients()
		{
			return self::$sql->singleColumn("SELECT c_recipient FROM t_messages_recipients WHERE c_message_id = ".self::$sql->quote($this->getName()).";");
		}

		/**
		 * Benachrichtigt die per addUser() hinzugefügten Benutzer auf Wunsch per Instant Messaging über den Eingang der Nachricht.
		 * @return void
		 * @todo
		*/

		private function IMNotify()
		{
			if(count($this->im_check_notify) > 0)
			{
				foreach($this->im_check_notify as $user=>$type)
				{
					$user_obj = Classes::User($user);
					$im_receive = $user_obj->checkSetting('messenger_receive');
					if($im_receive['messages'][$type])
						IMServer::sendMessage($user, sprintf($this->from() ? $user_obj->_("Sie haben eine neue Nachricht der Sorte %s von %s erhalten. Der Betreff lautet: %s") : $user_obj->_("Sie haben eine neue Nachricht der Sorte %s erhalten. Der Betreff lautet: %3\$s"), $user_obj->_("[message_".$type."]"), $this->from(), $this->subject()));
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

		/**
		 * Gibt eine Liste der Nachrichten zurück, die dieser Benutzer in seinem
		 * Posteingang hat.
		 * @param string $user
		 * @param int $type Message::TYPE_* Wenn angegeben, werden nur Nachrichten dieses Typs zurückgegeben
		 * @param int $status Message::STATUS_* Wenn angegeben, werden nur Nachrichten mit diesem Status zurückgegeben
		 * @return array(string)
		*/

		static function getUserMessages($user, $type=null, $status=null)
		{
			return self::$sql->singleColumn("SELECT DISTINCT c_message_id FROM t_messages_users WHERE c_user = ".self::$sql->quote($user).(isset($type) ? " AND c_type = ".self::$sql->quote($type) : "").(isset($status) ? " AND c_status = ".self::$sql->quote($status) : "").";");
		}

		/**
		 * Gibt die Anzahl der Nachrichten zurück, die dieser Benutzer in seinem
		 * Posteingang hat.
		 * @param string $user
		 * @param int $type Message::TYPE_* Wenn angegeben, werden nur Nachrichten dieses Typs zurückgegeben
		 * @param int $status Message::STATUS_* Wenn angegeben, werden nur Nachrichten mit diesem Status zurückgegeben
		 * @return array(string)
		*/

		static function getUserMessagesCount($user, $type=null, $status=null)
		{
			return self::$sql->singleField("SELECT COUNT(DISTINCT c_message_id) FROM t_messages_users WHERE c_user = ".self::$sql->quote($user).(isset($type) ? " AND c_type = ".self::$sql->quote($type) : "").(isset($status) ? " AND c_status = ".self::$sql->quote($status) : "").";");
		}

		/**
		 * Sortiert eine Liste von Nachrichten-IDs nach Eingangdatum.
		 * @param array $message_ids
		 * @return array
		*/

		static function sort(array $message_ids)
		{
			$times = array();
			foreach($message_ids as $id)
				$times[$id] = self::$sql->singleField("SELECT c_time FROM t_messages WHERE c_message_id = ".self::$sql->quote($id)." LIMIT 1;");
			asort($times, SORT_NUMERIC);
			return array_keys($times);
		}

		/**
		 * Löscht alle gelesenen (nicht archivierten) Nachrichten, die das maximale Nachrichtenalter überschreiten.
		 * @global $message_type_times
		 * @return int Die Anzahl der gelöschten Nachrichten. (In mehreren Postfächern gelöschte Nachrichten werden auch mehrfach gelöscht.)
		*/

		static function cleanUp()
		{
			global $message_type_times;

			self::$sql->query("SELECT c_message_id,c_user,c_type FROM t_messages_users WHERE c_status = ".self::$sql->quote(self::STATUS_ALT)." NATURAL JOIN ( SELECT c_message_id, c_time AS c_time FROM t_messages );");

			$i = 0;
			while(($r = self::$sql->nextResult()) !== false)
			{
				if(!isset($message_type_times[$r["c_type"]]))
					continue;

				if(time()-$time > $message_type_times[$r["c_type"]]*86400)
				{
					$message = Classes::Message($r["c_message_id"]);
					$message->removeUser($r["c_user"]);
					$i++;
				}
			}

			return $i;
		}
	}
