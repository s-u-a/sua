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
	 * Stellt eine Verbindung zur SQLite-Datenbank her und stellt Funktionen zur Verfügung, um damit zu arbeiten.
	 * @todo Die ganzen Kindklassen aktualisieren
	*/

	class SQLite
	{
		/**
		 * Die Verbindung zur Datenbank.
		 * @var PDO
		*/
		private $connection = false;

		/**
		 * Die Rückgabe des letzten SQLite->query()-Aufrufs für SQLite->nextResult() und SQLite->lastRowsAffected().
		 * @var PDOStatement
		*/
		private $last_result = false;

		/**
		 * Zweidimensionales Array, enthält die Queries der einzelnen Transaktionen, die gerade geöffnet sind.
		 * @var array(array(string))
		*/
		private $transactions = array();

		/**
		 * Öffnet die SQLite-Datenbank mit dem Dateinamen $filename. Alternativ kann auch ein Datenbankobjekt übergeben werden, dann
		 * wird die SQLite-Datei dortheraus verwendet.
		 * @param string|Database $filename
		*/

		function __construct($filename = null)
		{
			if($filename instanceof Database)
				$this->filename = $filename->getDirectory()."/sqlite";
			else
				$this->filename = $filename;
			$this->connection = new \PDO("sqlite:".$this->filename);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(\PDO::ATTR_TIMEOUT, 86400);
		}

		/**
		 * Überprüft, ob die in $tables definierten Tabellen mit ihren Spalten definiert sind. Ansonsten wird die Tabelle angelegt
		 * oder die Spalten eingefügt. $tables hat das Format ( Tabellenname => ( Feld ) ). Feld kann auch den Typ und weitere
		 * Informationen enthalten, es entspricht einem Eintrag aus der Spaltenliste bei CREATE TABLE. Feld kann also zum Beispiel
		 * "i INTEGER PRIMARY KEY" sein.
		 * @param array $tables
		 * @return void
		*/

		function checkTables($tables, $views=null)
		{
			if(isset($tables))
			{
				foreach($tables as $table=>$cols)
				{
					if(isset($views) && isset($views[$table]))
						continue;

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
					catch(\PDOException $e)
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
							catch(\PDOException $e)
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

			if(isset($views))
			{
				foreach($views as $key=>$create)
				{
					$view_statement = "CREATE VIEW ".$key." AS ".$create.";";
					if($this->singleField("SELECT COUNT(*) FROM sqlite_master WHERE type='view' AND sql=".$this->quote($view_statement).";") < 1)
					{
						$this->query("DROP VIEW IF EXISTS ".$key.";");
						$this->query($view_statement);
					}
				}
			}
		}

		/**
		 * Wrapper für die Funktion $this->connection->query(). Stellt vielleicht Sachen mit dem Query-String an und fängt Exceptions ab.
		 * @param string $query
		 * @return PDOStatement
		 * @see PDO::query
		*/

		private function queryWrapper($query)
		{
			try
			{
				if(defined("DEBUG") && DEBUG)
					$time = microtime(true);
				$result = $this->connection->query($query);
				if(defined("DEBUG") && DEBUG)
				{
					$time = microtime(true)-$time;
					Logger::log("Query “".$query."” took ".round($time, 6)." seconds.");
				}
				return $result;
			}
			catch(\PDOException $e) { $this->printException($e, $query); }
		}

		/**
		 * Führt einen Query aus, ohne dass sein Ergebnis für nextResult() und lastRowsAffected() gespeichert wird.
		 * @param string $query
		 * @return void
		*/

		function backgroundQuery($query)
		{
			$result = $this->queryWrapper($query);
			$result->closeCursor();
		}

		/**
		 * Führt einen Query aus. Die Ergebnisse können per nextResult() und lastRowsAffected() ausgelesen werden.
		 * @param string $query
		 * @return void
		*/

		function query($query)
		{
			if($this->last_result)
				$this->last_result->closeCursor();
			$this->last_result = $this->queryWrapper($query);
		}

		/**
		 * Gibt das nächste Ergebnis der letzten query()-Operation aus. Ist kein Ergebnis vorhanden, wird false zurückgeliefert.
		 * Siehe PDOStatement->fetch(). Die Zeile wird als assoziatives Array zurückgegeben, die Indexe sind die Namen der Felder.
		 * @return mixed
		*/

		function nextResult()
		{
			if(!$this->last_result) return false;

			return $this->last_result->fetch(\PDO::FETCH_ASSOC);
		}

		/**
		 * Wenn die letzte query()-Operation ein UPDATE oder DELETE war, gibt diese Funktion zurück, wieviele Zeilen davon
		 * betroffen waren.
		 * @return int|bool Wenn kein Ergebnis eines Querys zur Verfügung steht, wird false zurückgegeben.
		*/

		function lastRowsAffected()
		{
			if(!$this->last_result) return false;

			return $this->last_result->rowCount();
		}

		/**
		 * Führt einen Query aus und gibt dessen Ergebnis in Form eines Arrays aus, wobei jeder Eintrag des Arrays einer Zeile
		 * entspricht. Die einzelnen Zeilen werden in einem assoziativen Array gespeichert, dessen Indexe den Feldernamen des
		 * Querys entsprechen.
		 * @param string $query
		 * @return array
		*/

		function arrayQuery($query)
		{
			$result = $this->queryWrapper($query);
			$data = $result->fetchAll(\PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
		}

		/**
		 * Führt einen Query aus und gibt ein Array zurück, das die Werte der ersten Spalte des Querys enthält.
		 * @param string $query
		 * @return array
		*/

		function singleColumn($query)
		{
			$result = $this->queryWrapper($query);
			$return = array();
			while(($row = $result->fetch(\PDO::FETCH_NUM)) !== false)
			{
				if(!isset($row[0]))
					throw SQLiteException("No columns were queried.");
				$return[] = $row[0];
			}
			return $return;
		}

		/**
		 * Führt einen Query aus und gibt die erste Zeile als assoziatives Array zurück, die damit selektiert wurde.
		 * @param string $query
		 * @return mixed
		*/

		function singleLine($query)
		{
			$result = $this->queryWrapper($query);
			$data = $result->fetch(\PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
		}

		/**
		 * Führt einen Query aus und gibt das erste Feld zurück, das damit selektiert wurde.
		 * @param string $query
		 * @return mixed
		*/

		function singleField($query)
		{
			$result = $this->queryWrapper($query);
			$data = $result->fetchColumn();
			$result->closeCursor();
			return $data;
		}

		/**
		 * Formatiert einen String so, dass dieser als Wert in einem SQLite-Query verwendet werden kann. Ein String wird zum
		 * Beispiel in Anführungszeichen gesetzt.
		 * @param mixed $value
		 * @return string
		*/

		function escape($value)
		{
			if(is_numeric($value))
				return "".$value;
			else
				return $this->connection->quote($value);
		}

		/**
		 * Alias für SQLite->escape().
		 * @param mixed $value
		 * @return string
		*/

		function quote($value)
		{
			return $this->escape($value);
		}

		/**
		 * Startet eine Transaktion. Nach dem Start können immernoch normale Queries ausgeführt werden, die Transaktion wird
		 * erst bei endTransaction() an die Datenbank übermittelt. beginTransaction() kann mehrmals hintereinander ausgeführt
		 * werden, dies öffnet mehrere Transaktionsebenen, die nach mehrmaligem endTransaction() von der Datenbank ausgeführt
		 * werden.
		 * @return void
		*/

		function beginTransaction()
		{
			$this->transactions[] = array();
		}

		/**
		 * Fügt einen Query zur letzten per beginTransaction() initialisierten Transaktion hinzu.
		 * @param string $query
		 * @return void
		 * @throw SQLiteException Wenn noch keine Transaktion initialisiert wurde
		*/

		function transactionQuery($query)
		{
			$c = count($this->transactions);
			if($c < 1)
				throw new SQLiteException("No transaction has been started.");
			$this->transactions[$c-1][] = $query;
		}

		/**
		 * Beendet die letzte Transaktion, die durch beginTransaction() initialisiert wurde und führt deren Queries aus.
		 * @return null
		 * @throw SQLiteException Wenn noch keine Transaktion initialisiert wurde
		*/

		function endTransaction()
		{
			if(count($this->transactions) < 1)
				throw new SQLiteException("No transaction has been started.");
			$calls = array_pop($this->transactions);
			$this->connection->beginTransaction();
			foreach($calls as $q)
				$this->queryWrapper($q);
			return $this->connection->commit();
		}

		/**
		 * Gibt eine Exception an das globale Logfile (@see Logger) aus und leitet sie dann weiter. Diese Funktion
		 * wird in allen Query-Funktionen ausgeführt, wenn eine PDOException auftaucht, damit der Fehler dokumentiert wird.
		 * @param Exception $exception
		 * @param string $query Der Query, der die Exception verursacht hat.
		 * @throw Exception $exception
		*/

		function printException(\Exception $exception, $query)
		{
			Logger::log("PDO error, query: ".$query);
			throw $exception;
		}
	}
