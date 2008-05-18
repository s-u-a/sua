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

	namespace sua;
	require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	 * Verwaltet einen Stack, der datenbankunabhängig die Nachrichtenliste verwaltet, die der IM-Server
	 * versenden muss. Darüberhinaus werden IDs zur Verifikation gespeichert, die der Benutzer an den
	 * IM-Server zurücksenden muss, damit sein IM-Account verwendet werden darf.
	*/

	class IMFile extends SQLite
	{
		protected static $tables = array("to_check" => array("uin TEXT", "protocol TEXT", "username TEXT", "database TEXT", "checksum TEXT"), "notifications" => array("time INTEGER", "uin TEXT", "protocol TEXT", "username TEXT", "message TEXT", "database TEXT", "special_id TEXT", "fingerprint TEXT"));

		function __construct()
		{
			parent::__construct(global_setting("DB_NOTIFICATIONS"));
			parent::checkTables(self::$tables);
		}

		/**
		 * Fügt eine Verifikations-ID hinzu. Der IM-Account $uin muss über das Protokoll $protocol die Verifikations-ID
		 * senden, damit $uin und $protocol für $username als IM-Account konfiguriert werden.
		 * @param string $uin
		 * @param string $protocol
		 * @param string $username
		 * @return string Die Verifikations-ID.
		*/

		function addCheck($uin, $protocol, $username)
		{
			$rand_id = substr(md5(rand()), 0, 8);

			$this->query("INSERT INTO to_check ( uin, protocol, username, database, checksum ) VALUES ( ".$this->escape($uin).", ".$this->escape($protocol).", ".$this->escape($username).", ".$this->escape(global_setting("DB")).", ".$this->escape($rand_id)." );");
			return $rand_id;
		}

		/**
		 * Wird ausgeführt, wenn eine IM-Nachricht eingeht. Überprüft, ob der IM-Account $uin mit dem Protokoll
		 * $protocol mit der Verifikations-ID $checksum in der Datenbank eingetragen ist. Wenn ja, wird ein
		 * Array zurückgeliefert: [ Benutzername, Datenbank ]. Wenn nein, wird false zurückgeliefert.
		 * @param string $uin
		 * @param string $protocol
		 * @param string $checksum
		 * @return array|boolean
		*/

		function checkCheckID($uin, $protocol, $checksum)
		{
			$ret = $this->arrayQuery("SELECT * FROM to_check WHERE uin = ".$this->escape($uin)." AND protocol = ".$this->escape($protocol)." AND checksum = ".$this->escape($checksum)." LIMIT 1;");
			if(count($ret) >= 1)
			{
				list($ret) = $ret;
				return array($ret['username'], $ret['database']);
			}
			return false;
		}

		/**
		 * Entfernt alle Verifikations-IDs des Benutzers $username aus der Datenbank.
		 * @param string $username
		 * @return void
		*/

		function removeChecks($username)
		{
			$this->query("DELETE FROM to_check WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB")).";");
		}

		/**
		 * Liest die nächste Nachricht, die versendet werden muss, aus der Datenbank und entfernt sie von
		 * dort. Liefert ein Array mit Informationen über die Nachricht zurück, oder false, wenn keine mehr
		 * im Stack steht.
		 * @return array|boolean
		*/

		function shiftNextMessage()
		{
			$return = $this->arrayQuery("SELECT time,uin,protocol,username,message,database,special_id,fingerprint FROM notifications WHERE time <= '".time()."' LIMIT 1;");
			if($return && $this->query("DELETE FROM notifications WHERE time <= '".time()."';"))
			{
				list($return) = $return;
				return $return;
			}
			else return false;
		}

		/**
		 * Fügt dem Stack eine Nachricht hinzu.
		 * @param string $uin Die UIN des Empfängers.
		 * @param string $protocol Das Protokoll, über das die Nachricht versandt werden soll.
		 * @param string $username Der Benutzeraccount, zu dem die Nachricht gehört.
		 * @param string $message Die Nachricht selbst.
		 * @param string $special_id Eine beliebige Nachrichtengruppe, normalerweise um eine Nachricht einem Planeten zuzuordnen. Man kann später alle Nachrichten mit einer bestimmten $special_id aus dem Stack löschen, zum Beispiel, wenn der Planet aufgelöst wird.
		 * @param int $time Der Zeitpunkt, zu dem die Nachricht versandt werden soll.
		 * @return void
		*/

		function addMessage($uin, $protocol, $username, $message, $special_id="", $time=false)
		{
			if($time === false) $time = time();
			$fingerprint = null;
			$user = Classes::User($username);
			if($user->getStatus())
				$fingerprint = $user->checkSetting("fingerprint");
			$this->query("INSERT INTO notifications ( uin, time, protocol, username, message, database, special_id, fingerprint ) VALUES ( ".$this->escape($uin).", ".$this->escape($time).", ".$this->escape($protocol).", ".$this->escape($username).", ".$this->escape($message).", ".$this->escape(global_setting("DB")).", ".$this->escape($special_id).", ".$this->escape($fingerprint)." );");
		}

		/**
		 * Benennt einen Benutzernamen im Stack um.
		 * @param string $old_username
		 * @param string $new_username
		 * @return void
		*/

		function renameUser($old_username, $new_username)
		{
			$this->query("UPDATE notifications SET username = ".$this->escape($new_username)." WHERE username = ".$this->escape($old_username)." AND database = ".$this->escape(global_setting("DB")).";");
			$this->query("UPDATE to_check SET username = ".$this->escape($new_username)." WHERE username = ".$this->escape($old_username)." AND database = ".$this->escape(global_setting("DB")).";");
		}

		/**
		 * Löscht alle Nachrichten an dem Benutzer $username, optional nur die mit $special_id, aus dem Stack.
		 * @param string $username
		 * @param string $special_id
		 * @return void
		*/

		function removeMessages($username, $special_id=null)
		{
			if(!isset($special_id))
				$this->query("DELETE FROM notifications WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB")).";");
			else
				$this->query("DELETE FROM notifications WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB"))." AND special_id = ".$this->escape($special_id).";");
		}

		/**
		 * Ändert die Empfänger-UIN für den Benutzer $username auf $uin und das Protokoll auf $protocol. Die Empfänger
		 * aller Nachrichten an diesen Benutzer werden aktualisiert.
		 * @param string $username
		 * @param string $uin
		 * @param string $protocol
		 * @return void
		*/

		function changeUIN($username, $uin, $protocol)
		{
			$this->query("UPDATE notifications SET uin = ".$this->escape($uin).", protocol = ".$this->escape($protocol)." WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB")).";");
		}

		/**
		 * Ändert den GPG-Fingerprint des Benutzers $username in allen Nachrichten, die an ihn versendet werden sollen.
		 * @param string $username
		 * @param string $fingerprint
		 * @return void
		*/

		function changeFingerprint($username, $fingerprint)
		{
			$this->query("UPDATE notifications SET fingerprint = ".$this->escape($fingerprint)." WHERE username = ".$this->escape($username).";");
		}
	}
