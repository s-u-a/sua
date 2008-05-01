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

	class IMFile extends SQLite
	{
		protected $tables = array("to_check" => array("uin", "protocol", "username", "database", "checksum"), "notifications" => array("time INT", "uin", "protocol", "username", "message", "database", "special_id", "fingerprint"));
		protected $custom_filename = true;

		function __construct()
		{
			$this->filename = global_setting("DB_NOTIFICATIONS");
			parent::connect();
		}

		function addCheck($uin, $protocol, $username)
		{
			if(!$this->status) return false;

			$rand_id = substr(md5(rand()), 0, 8);

			$this->query("INSERT INTO to_check ( uin, protocol, username, database, checksum ) VALUES ( ".$this->escape($uin).", ".$this->escape($protocol).", ".$this->escape($username).", ".$this->escape(global_setting("DB")).", ".$this->escape($rand_id)." );");
			return $rand_id;
		}

		function checkCheckID($uin, $protocol, $checksum)
		{
			if(!$this->status) return false;

			$ret = $this->arrayQuery("SELECT * FROM to_check WHERE uin = ".$this->escape($uin)." AND protocol = ".$this->escape($protocol)." AND checksum = ".$this->escape($checksum)." LIMIT 1;");
			if(count($ret) >= 1)
			{
				list($ret) = $ret;
				return array($ret['username'], $ret['database']);
			}
			return false;
		}

		function removeChecks($username)
		{
			if(!$this->status) return false;

			return $this->query("DELETE FROM to_check WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB")).";");
		}

		function shiftNextMessage()
		{
			if(!$this->status) return false;

			$return = $this->arrayQuery("SELECT * FROM notifications WHERE time <= '".time()."' LIMIT 1;");
			if($return && $this->query("DELETE FROM notifications WHERE time <= '".time()."';"))
			{
				list($return) = $return;
				return $return;
			}
			else return false;
		}

		function addMessage($uin, $protocol, $username, $message, $special_id="", $time=false)
		{
			if(!$this->status) return false;

			if($time === false) $time = time();
			$fingerprint = null;
			$user = Classes::User($username);
			if($user->getStatus())
				$fingerprint = $user->setSetting("fingerprint", false);
			return $this->query("INSERT INTO notifications ( uin, time, protocol, username, message, database, special_id, fingerprint ) VALUES ( ".$this->escape($uin).", ".$this->escape($time).", ".$this->escape($protocol).", ".$this->escape($username).", ".$this->escape($message).", ".$this->escape(global_setting("DB")).", ".$this->escape($special_id).", ".$this->escape($fingerprint)." );");
		}

		function renameUser($old_username, $new_username)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE notifications SET username = ".$this->escape($new_username)." WHERE username = ".$this->escape($old_username)." AND database = ".$this->escape(global_setting("DB")).";") && $this->query("UPDATE to_check SET username = ".$this->escape($new_username)." WHERE username = ".$this->escape($old_username)." AND database = ".$this->escape(global_setting("DB")).";");
		}

		function removeMessages($username, $special_id=false)
		{
			if(!$this->status) return false;

			if($special_id === false)
				return $this->query("DELETE FROM notifications WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB")).";");
			else
				return $this->query("DELETE FROM notifications WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB"))." AND special_id = ".$this->escape($special_id).";");
		}

		function changeUIN($username, $uin, $protocol)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE notifications SET uin = ".$this->escape($uin).", protocol = ".$this->escape($protocol)." WHERE username = ".$this->escape($username)." AND database = ".$this->escape(global_setting("DB")).";");
		}

		function changeFingerprint($username, $fingerprint)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE notifications SET fingerprint = ".$this->escape($fingerprint)." WHERE username = ".$this->escape($username).";");
		}
	}
