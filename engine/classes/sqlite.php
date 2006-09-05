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
		protected $transaction = false;
		protected $transaction_calls = array();

		function __construct()
		{
			$this->filename = global_setting("DB_SQLITE");
			$this->connect();
		}

		function connect()
		{
			$fname_index = null;
			if(file_exists($this->filename))
				$fname_index = realpath($this->filename);
			if($fname_index === null || !isset(self::$connections[$fname_index]))
			{
				$conn = new PDO("sqlite:".$this->filename);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if($fname_index === null)
					$fname_index = realpath($this->filename);
				self::$connections[$fname_index] = $conn;
			}

			$this->connection = &self::$connections[$fname_index];

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

		function transactionQuery($query)
		{
			$this->checkConnection();
			if(!$this->transaction)
				$this->transaction = true;

			$this->transaction_calls[] = $query;
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

		function endTransaction()
		{
			if(!$this->transaction) return false;
			$this->transaction = false;

			$this->connection->beginTransaction();
			foreach($this->transaction_calls as $q)
				$this->connection->query($q);
			return $this->connection->commit();
		}
	}
?>
