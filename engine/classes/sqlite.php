<?php
	class SQLite
	{
		protected $filename = false;
		protected $tables = array();
		protected $connection = false;
		protected $status = 0;
		protected $custom_filename = false;
		private $last_result = false;

		function __construct()
		{
			$this->filename = global_setting("DB_SQLITE");
			$this->connect();
		}

		function connect()
		{
			if(!($this->connection = sqlite_popen($this->filename)))
				throw new SQLiteException("Could not open database ".$filename, sqlite_last_error($this->connection));

			if($this->tables)
			{
				foreach($this->tables as $table=>$cols)
				{
					$table_check = $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name='".$this->escape($table)."';");
					if($this->lastResultCount($table_check) < 1)
					{
						if(!sqlite_query($this->connection, "CREATE TABLE ".$table." ( ".implode(", ", $cols)." );"))
							throw new SQLiteException("Could not create table ".$table." in database ".$filename, sqlite_last_error($this->connection));
					}
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
			if(sqlite_query($this->connection, $query))
				return true;
			else
				throw new SQLiteException("Could not perform query.", sqlite_last_error($this->connection));
		}

		function query($query)
		{
			$this->checkConnection();
			if(!($this->last_result = sqlite_query($this->connection, $query)))
				throw new SQLiteException("Could not perform query.", sqlite_last_error($this->connection));
			return ($this->last_result == true);
		}

		function nextResult()
		{
			if(!$this->last_result) return false;

			return sqlite_fetch_array($this->last_result, SQLITE_ASSOC);
		}

		function lastResultCount()
		{
			if($this->last_result) return sqlite_num_rows($this->last_result);
			else return false;
		}

		function arrayQuery($query)
		{
			$this->checkConnection();
			if(($result = sqlite_array_query($this->connection, $query, SQLITE_ASSOC)) === false)
				throw new SQLiteException("Could not run query.", sqlite_last_error($this->connection));
			return $result;
		}

		function singleQuery($query)
		{
			$this->checkConnection();
			if(($q = sqlite_query($this->connection, $query)) === false)
				throw new SQLiteException("Could not run query.", sqlite_last_error($this->connection));
			return sqlite_fetch_array($q, SQLITE_ASSOC);
		}

		function singleField($query)
		{
			$this->checkConnection();
			if(($result = sqlite_single_query($this->connection, $query, true)) === false)
				throw new SQLiteException("Could not run query.", sqlite_last_error($this->connection));
			return $result;
		}

		function escape($string)
		{
			return sqlite_escape_string($string);
		}
	}
?>
