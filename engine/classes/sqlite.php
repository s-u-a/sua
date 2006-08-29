<?php
	class SQLite
	{
		protected $filename = false;
		protected $tables = array();
		protected $connection = false;
		protected $status = 0;
		protected $custom_filename = false;
		private $last_result = false;
		static protected $connections = array();

		function __construct()
		{
			$this->filename = global_setting("DB_SQLITE");
			$this->connect();
		}

		function connect()
		{
			if(!isset(self::$connections[$this->filename]))
			{
				self::$connections[$this->filename] = new PDO("sqlite:".$this->filename);
				self::$connections[$this->filename]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}

			$this->connection = &self::$connections[$this->filename];

			if($this->tables)
			{
				foreach($this->tables as $table=>$cols)
				{
					if($this->singleField("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=".$this->escape($table).";") < 1)
						$this->query("CREATE TABLE ".$table." ( ".implode(", ", $cols)." );");
				}
			}

			$this->last_result = false;
			$this->status = 1;
		}

		function __destruct()
		{
		}

		function checkConnection()
		{
			if($this->custom_filename) return 2;
			$new_fname = global_setting("DB_SQLITE");
			if($this->filename != $new_fname)
			{
				$this->filename = $new_fname;
				$this->connect();
			}
		}

		function backgroundQuery($query)
		{
			$this->checkConnection();
			$result = $this->connection->query($query);
			if($result == false) return false;
			$result->closeCursor();
			return true;
		}

		function query($query)
		{
			$this->checkConnection();
			$this->last_result = $this->connection->query($query);
			return true;
		}

		function nextResult()
		{
			if(!$this->last_result) return false;

			return $this->last_result->fetch(PDO::FETCH_ASSOC);
		}

		function lastRowsAffected()
		{
			if(!$this->last_result) return false;

			return $this->last_result->rowCount();
		}

		function arrayQuery($query)
		{
			$this->checkConnection();
			$result = $this->connection->query($query);
			if($result == false) return false;
			$data = $result->fetchAll(PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
		}

		function singleQuery($query)
		{
			$this->checkConnection();
			$result = $this->connection->query($query);
			if($result == false) return false;
			$data = $result->fetch(PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
		}

		function singleField($query)
		{
			$this->checkConnection();
			$result = $this->connection->query($query);
			if($result == false) return false;
			$data = $result->fetchColumn();
			$result->closeCursor();
			return $data;
		}

		function escape($string)
		{
			return $this->connection->quote($string);
		}
	}
?>
