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
	 * Stellt eine Verbindung zur SQL-Datenbank her und stellt Funktionen zur Verfügung, um damit zu arbeiten.
	 * @todo Die ganzen Kindklassen aktualisieren
	*/

	class SQL
	{
		/**
		 * Die Verbindung zur Datenbank.
		 * @var PDO
		*/
		private $connection = false;

		/**
		 * Die Rückgabe des letzten SQL->query()-Aufrufs für SQL->nextResult() und SQL->lastRowsAffected().
		 * @var PDOStatement
		*/
		private $last_result = false;

		/**
		 * Zweidimensionales Array, enthält die Queries der einzelnen Transaktionen, die gerade geöffnet sind.
		 * @var array(array(string))
		*/
		private $transactions = array();

		/**
		 * Enthält den DSN des PDO-Objekts. Siehe zum Beispiel http://www.php.net/manual/en/ref.pdo-pgsql.connection.php.
		 * @var string
		*/
		private $dsn;

		/**
		 * Öffnet die Datenbank unter dem DSN $dsn. Alternativ kann auch ein Datenbankobjekt übergeben werden, dann
		 * wird die dort konfigurierte SQL-Datenbank verwendet.
		 * @param string|Database $dsn See http://www.php.net/manual/en/ref.pdo-pgsql.connection.php for example
		*/

		function __construct($dsn = null)
		{
			if($dsn instanceof Database)
				$this->dsn = $dsn->getConfig()->getConfigValueE("database");
			else
				$this->dsn = $dsn;
			$this->connection = new \PDO($this->dsn);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(\PDO::ATTR_TIMEOUT, 86400);
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
					throw SQLException("No columns were queried.");
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
		 * Formatiert einen String so, dass dieser als Wert in einem SQL-Query verwendet werden kann. Ein String wird zum
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
		 * Alias für SQL->escape().
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
		 * @throw SQLException Wenn noch keine Transaktion initialisiert wurde
		*/

		function transactionQuery($query)
		{
			$c = count($this->transactions);
			if($c < 1)
				throw new SQLException("No transaction has been started.");
			$this->transactions[$c-1][] = $query;
		}

		/**
		 * Beendet die letzte Transaktion, die durch beginTransaction() initialisiert wurde und führt deren Queries aus.
		 * @return null
		 * @throw SQLException Wenn noch keine Transaktion initialisiert wurde
		*/

		function endTransaction()
		{
			if(count($this->transactions) < 1)
				throw new SQLException("No transaction has been started.");
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
			echo "<pre>";
			throw $exception;
		}
	}
