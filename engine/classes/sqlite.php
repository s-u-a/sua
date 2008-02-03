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
				$conn->setAttribute(PDO::ATTR_TIMEOUT, 86400);
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
					try
					{
						$q = "SELECT ";
						foreach(array_values($cols) as $i=>$c)
						{
							list($c) = explode(" ", $c, 2);
							if($i != 0) $q .= ",";
							$q .= $c;
						}
						$q .= " FROM ".$table." LIMIT 1;";
						$this->query($q);
					}
					catch(PDOException $e)
					{
						if(strpos($e, "no such column") === false) throw $e;

						$add_cols = array();
						foreach($cols as $c)
						{
							list($cf) = explode(" ", $c, 2);
							try
							{
								$this->query("SELECT ".$cf." FROM ".$table." LIMIT 1;");
							}
							catch(PDOException $e)
							{
								if(strpos($e, "no such column") === false) throw $e;
								$add_cols[] = $c;
							}
						}
						foreach($add_cols as $c)
						{
							$this->query("ALTER TABLE ".$table." ADD COLUMN ".$c.";");
							$this->query("VACUUM");
						}
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
			try { $result = $this->connection->query($query); }
			catch(PDOException $e) { $this->printException($e, $query); }
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
			try { $this->last_result = $this->connection->query($query); }
			catch(PDOException $e) { $this->printException($e, $query); }
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
			try { $result = $this->connection->query($query); }
			catch(PDOException $e) { $this->printException($e, $query); }
			if($result == false) return false;
			$data = $result->fetchAll(PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
		}

		function singleQuery($query)
		{
			$this->checkConnection();
			try { $result = $this->connection->query($query); }
			catch(PDOException $e) { $this->printException($e, $query); }
			if($result == false) return false;
			$data = $result->fetch(PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
		}

		function singleField($query)
		{
			$this->checkConnection();
			try { $result = $this->connection->query($query); }
			catch(PDOException $e) { $this->printException($e, $query); }
			if($result == false) return false;
			$data = $result->fetchColumn();
			$result->closeCursor();
			return $data;
		}

		function escape($string)
		{
			if(is_numeric($string))
				return $string;
			else
				return $this->connection->quote($string);
		}

		function endTransaction()
		{
			if(!$this->transaction) return false;
			$this->transaction = false;

			$this->connection->beginTransaction();
			foreach($this->transaction_calls as $q)
			{
				try { $this->connection->query($q); }
				catch(PDOException $e) { $this->printException($e, $q); }
			}
			return $this->connection->commit();
		}
		
		function printException($exception, $query)
		{
			fputs(global_setting("LOG"), "PDO error, query: ".$query."\n");
			throw $exception;
		}
	}
