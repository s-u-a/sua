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
		 * Muss von der Kindklasse festgelegt werden. Legt fest, welche Tabellen mit welchen Feldern vom
		 * Datenset benötigt werden und kümmert sich darum, dass diese existieren. Siehe SQLite->checkTables()
		 * für das Format der Variable.
		 * @var array
		*/
		protected static $tables;

		protected static $views;

		private static $check_tables_stack = array();

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

			self::$check_tables_stack[] = array(static::$tables, static::$views);
			self::checkTables();
		}

		/**
		 * Stellt sicher, dass die in $tables definierten Tabellen existieren.
		 * @return void
		*/

		protected static function checkTables()
		{
			if(self::$sqlite)
			{
				while(count(self::$check_tables_stack) > 0)
				{
					list($tables, $views) = array_shift(self::$check_tables_stack);
					self::$sqlite->checkTables($tables, $views);
				}
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
			$existing = self::$sqlite->singleField("SELECT ".static::_idField()." FROM ".Functions::first(static::$tables)." WHERE LOWER(".static::_idField().") = LOWER(".self::$sqlite->quote($name).");");
			if($existing !== false)
				$name = $existing;
			return $name;
		}

		/**
		 * Gibt die erste Spalte der ersten Tabelle zurück. Diese dient als ID-Feld.
		 * @return string
		*/

		private static function _idField()
		{
			$t = static::$tables[Functions::first(static::$tables)];
			list($f) = explode(" ", $t[Functions::first($t)]);
			return $f;
		}

		static function getList()
		{
			return self::$sqlite->singleColumn("SELECT DISTINCT ".static::_idField()." FROM ".Functions::first(static::$tables).";");
		}

		static function getNumber()
		{
			return self::singleField("SELECT COUNT(*) FROM ".Functions::first(static::$tables).";");
		}

		static function exists($name)
		{
			if(count(func_get_args()) > 1)
				$name = static::idFromParams(func_get_args());

			$name = static::datasetName($name);
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM ".Functions::first(static::$tables)." WHERE LOWER(".static::_idField().") = LOWER(".self::$sqlite->quote($name).");") >= 1);
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
				$result = self::$sqlite->singleLine("SELECT ".implode(", ", $field_name)." FROM ".Functions::first(static::$tables)." WHERE ".static::_idField()." = ".self::$sqlite->quote($this->getName()).";");
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
				return self::$sqlite->singleField("SELECT ".$field_name." FROM ".Functions::first(static::$tables)." WHERE ".static::_idField()." = ".self::$sqlite->quote($this->getName()).";");
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
			self::$sqlite->query("UPDATE ".Functions::first(static::$tables)." SET ".implode(", ", $set)." WHERE ".static::_idField()." = ".self::$sqlite->quote($this->getName()).";");
		}
	}