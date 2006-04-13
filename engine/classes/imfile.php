<?php
	class IMFile
	{
		protected $connection=false;
		protected $status=false;

		function __construct()
		{
			if(!$this->status)
			{
				# Datenbankverbindung herstellen
				$this->connection = sqlite_open(DB_NOTIFICATIONS, 0666);
				if($this->connection)
				{
					$table_check = sqlite_query($this->connection, "SELECT name FROM sqlite_master WHERE type='table' AND name='to_check' OR name='notifications';");
					if(sqlite_num_rows($table_check)!=0 || sqlite_query($this->connection, "CREATE TABLE to_check ( uin, protocol, username, checksum );\nCREATE TABLE notifications ( no INT PRIMARY KEY, time INT, uin, protocol, username, message, database );"))
						$this->status = true;
				}
			}
		}

		function __destruct()
		{
			if($this->status)
			{
				# Datenbankerbindung schliessen
				sqlite_close($this->connection);
				$this->status = false;
			}
		}

		function getLastErrorMessage()
		{
			$number = sqlite_last_error($this->connection);
			if($number === false) return false;
			return $number.': '.sqlite_error_string($this->connection);
		}

		function addCheck($uin, $protocol, $username)
		{
			if(!$this->status) return false;

			$rand_id = substr(md5(rand()), 0, 8);

			if(sqlite_query($this->connection, "INSERT INTO to_check ( uin, protocol, username, checksum ) VALUES ( '".sqlite_escape_string($uin)."', '".sqlite_escape_string($protocol)."', '".sqlite_escape_string($username)."', '".sqlite_escape_string($rand_id)."' );"))
				return $rand_id;
			else return false;
		}

		function checkCheckID($uin, $protocol, $checksum)
		{
			if(!$this->status) return false;

			$ret = sqlite_array_query($this->connection, "SELECT * FROM to_check WHERE uin = '".sqlite_escape_string($uin)."' AND protocol = '".sqlite_escape_string($protocol)."' AND checksum = '".sqlite_escape_string($checksum)."' LIMIT 1;", SQLITE_ASSOC);
			if(count($ret) >= 1)
			{
				list($ret) = $ret;
				return $ret['username'];
			}
			return false;
		}

		function removeChecks($username)
		{
			if(!$this->status) return false;

			return sqlite_query($this->connection, "DELETE FROM to_check WHERE username='".sqlite_escape_string($username)."';");
		}

		function shiftNextMessage()
		{
			if(!$this->status) return false;

			$return = sqlite_array_query($this->connection, "SELECT * FROM notifications WHERE time <= '".time()."' LIMIT 1;", SQLITE_ASSOC);
			if($return && sqlite_query($this->connection, "DELETE FROM notifications WHERE time <= '".time()."';"))
			{
				list($return) = $return;
				return $return;
			}
			else return false;
		}

		function addMessage($uin, $protocol, $username, $message, $database)
		{
			return sqlite_query($this->connection, "INSERT INTO notifications ( uin, time, protocol, username, message, database ) VALUES ( '".sqlite_escape_string($uin)."', '".sqlite_escape_string(time())."', '".sqlite_escape_string($protocol)."', '".sqlite_escape_string($username)."', '".sqlite_escape_string($message)."', '".sqlite_escape_string($database)."' );");
		}
	}
?>