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
	 * Ein Datenset, das SQLite als Speicher verwendet.
	 * Kümmert sich um die Verbindung zur Datenbank und stellt Funktionen zur Verfügung, um diese zu benutzen.
	 * Implementiert die Dataset-Funktionen.
	*/

	abstract class SQLiteSet extends Dataset implements StaticInit
	{
		/**
		 * Eine SQLite-Instanz, die die Datenbank bedient.
		 * @var SQLite
		*/
		public static $sqlite;

		/**
		 * Ein Array von zwei Werten: [0]: Tabellenname, [1]: Spaltenname. Für getMainField() und Ähnliche.
		 * @var Array
		*/
		protected static $primary_key;

		static function init()
		{
			static $added_to_dataset;

			if(!isset($added_to_dataset) || !$added_to_dataset)
			{
				Dataset::addDatabaseChangeListener(function(){ SQLiteSet::init(); });
				$added_to_dataset = true;
			}

			if(!self::$sqlite)
			{
				try { self::$sqlite = Classes::SQLite(Dataset::getDatabase()); }
				catch(DatasetException $e) {  }
			}
		}

		static function datasetName($name=null)
		{
			if(count(func_get_args()) > 1)
				$name = static::idFromParams(func_get_args());

			if(count($name) < 1)
			{
				do { $name = Functions::randomID(); } while(!self::exists($name));
			}
			$name = parent::datasetName($name);
			$existing = self::$sqlite->singleField("SELECT ".static::getMainFieldName()." FROM ".static::getMainTableName()." WHERE LOWER(".static::getMainFieldName().") = LOWER(".self::$sqlite->quote($name).");");
			if($existing !== false)
				$name = $existing;
			return $name;
		}

		protected static function getMainFieldName()
		{
			return self::$primary_key[1];
		}

		protected static function getMainTableName()
		{
			return self::$primary_key[0];
		}

		static function getList()
		{
			return self::$sqlite->singleColumn("SELECT DISTINCT ".static::getMainFieldName()." FROM ".static::getMainTableName().";");
		}

		static function getNumber()
		{
			return self::$sqlite->singleField("SELECT COUNT(*) FROM ".static::getMainTableName().";");
		}

		static function exists($name)
		{
			if(count(func_get_args()) > 1)
				$name = static::idFromParams(func_get_args());

			$name = static::datasetName($name);
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM ".static::getMainTableName()." WHERE LOWER(".static::getMainFieldName().") = LOWER(".self::$sqlite->quote($name).");") >= 1);
		}

		/**
		 * Gibt den Wert der Spalte $field_name in der ersten in $tables definierten Tabelle zurück, wo
		 * das ID-Feld (_idField()) dem Namen dieses Datensets entspricht.
		 * @param string|array $field_name Kann auch ein Array von Feld-Namen sein, dann wird ein assoziatives Array der Werte zurückgeliefert
		 * @return mixed
		*/

		protected function getMainField($field_name)
		{
			if(is_array($field_name))
			{
				$result = self::$sqlite->singleLine("SELECT ".implode(", ", $field_name)." FROM ".static::getMainTableName()." WHERE ".static::getMainFieldName()." = ".self::$sqlite->quote($this->getName()).";");
				$return = array();
				foreach($field_name as $k=>$v)
				{
					if(!isset($result[$v]))
						throw new SQLiteSetException("Field “".$v."” could not be selected.");
					$return[$k] = $result[$v];
				}
				return $return;
			}
			else
				return self::$sqlite->singleField("SELECT ".$field_name." FROM ".static::getMainTableName()." WHERE ".static::getMainFieldName()." = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Setzt den Wert der Spalte $field_name in der ersten in $tables definierten Tabelle dort, wo
		 * das ID-Feld (_idField()) dem Namen dieses Datensets entspricht.
		 * @param string|array $field_name
		 * @param string|array $value
		 * @return void
		*/

		protected function setMainField($field_name, $value)
		{
			$field_names = is_array($field_name) ? $field_name : array($field_name);
			$values = is_array($value) ? $value : array($value);
			$set = array();
			foreach($field_names as $k=>$v)
			{
				if(!isset($values[$k]))
					throw new SQLiteSetException("setMainField: Could not find a value for field ".$v);
				$set[] = $v." = ".self::$sqlite->quote($values[$k]);
			}
			if(count($set) < 1)
				throw new SQLiteSetException("No fields to update.");
			self::$sqlite->query("UPDATE ".static::getMainTableName()." SET ".implode(", ", $set)." WHERE ".static::getMainFieldName()." = ".self::$sqlite->quote($this->getName()).";");
		}
	}