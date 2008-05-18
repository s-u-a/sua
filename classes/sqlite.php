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
		private static $connection = false;
		private static $last_result = false;
		private static $transaction = false;
		private static $transaction_calls = array();

		function __construct($filename = null)
		{
			if(!isset($filename))
				$filename = Classes::Database()->getDirectory()."/sqlite";
			$this->connection = new PDO("sqlite:".$this->filename);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(PDO::ATTR_TIMEOUT, 86400);
		}

		function checkTables($tables)
		{
			foreach($tables as $table=>$cols)
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
			if($this->last_result)
				$this->last_result->closeCursor();
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

		function columnQuery($query)
		{
			$this->checkConnection();
			try { $result = $this->connection->query($query); }
			catch(PDOException $e) { $this->printException($e, $query); }
			$result = array();
			while(($row = $result->fetch(PDO::FETCH_NUM)) !== false)
			{
				if(!isset($row[0]))
					throw SQLiteException("No columns were queried.");
				$result[] = $row[0];
			}
			return $result;
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
			fputs(global_setting("LOG"), "\nPDO error, query: ".$query);
			throw $exception;
		}
	}
