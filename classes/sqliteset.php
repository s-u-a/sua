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
	 * @subpackage storage
	*/

	namespace sua;
	require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	 * Ein Datenset, das SQLite als Speicher verwendet.
	 * Kümmert sich um die Verbindung zur Datenbank und stellt Funktionen zur Verfügung, um diese zu benutzen.
	 * Implementiert die Dataset-Funktionen.
	*/

	class SQLiteSet extends Dataset implements StaticInit
	{
		/**
		 * Eine SQLite-Instanz, die die Datenbank bedient.
		 * @var SQLite
		*/
		protected static $sqlite;

		/**
		 * Muss von der Kindklasse festgelegt werden. Legt fest, welche Tabellen mit welchen Feldern vom
		 * Datenset benötigt werden und kümmert sich darum, dass diese existieren. Siehe SQLite->checkTables()
		 * für das Format der Variable.
		 * @var array
		*/
		protected static $tables;

		static public init()
		{
			self::$sqlite = Classes::SQLite();
			static::checkTables();
		}

		/**
		 * Stellt sicher, dass die in $tables definierten Tabellen existieren.
		 * @return void
		*/

		static protected function checkTables()
		{
			self::$sqlite->checkTables(static::$tables);
		}

		static function datasetName($name=null)
		{
			if(count(func_get_args()) > 1)
				$name = static::idFromParams(func_get_args());

			if(count($name) < 1)
			{
				do { $name = Functions::randomID(); } while(!self::exists($name));
			}
			$existing = self::$sqlite->singleQuery("SELECT ".static::_idField()." FROM ".Functions::first(static::$tables)." WHERE LOWER(".static::_idField().") = LOWER(".self::$sqlite->quote($name).");");
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
			return self::$sqlite->columnQuery("SELECT DISTINCT ".static::_idField()." FROM ".Functions::first(static::$tables).";");
		}

		static function getNumber()
		{
			return self::singleQuery("SELECT COUNT(*) FROM ".Functions::first(static::$tables).";");
		}

		static function exists($name)
		{
			if(count(func_get_args()) > 1)
				$name = static::idFromParams(func_get_args());

			$name = static::datasetName($name);
			return (self::$sqlite->singleQuery("SELECT COUNT(*) FROM ".Functions::first(static::$tables)." WHERE LOWER(".static::_idField().") = LOWER(".self::$sqlite->quote($name).");") >= 1);
		}

		abstract static function create($name);

		abstract static function destroy();

		/**
		 * Gibt den Wert der Spalte $field_name in der ersten in $tables definierten Tabelle zurück, wo
		 * das ID-Feld (_idField()) dem Namen dieses Datensets entspricht.
		 * @param string $field_name
		 * @return mixed
		*/

		protected function getMainField($field_name)
		{
			return self::$sqlite->singleQuery("SELECT ".$field_name." FROM ".Functions::first(static::$tables)." WHERE ".static::_idField()." = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Setzt den Wert der Spalte $field_name in der ersten in $tables definierten Tabelle dort, wo
		 * das ID-Feld (_idField()) dem Namen dieses Datensets entspricht.
		 * @param string $field_name
		 * @param string $value
		 * @return void
		*/

		protected function setMainField($field_name, $value)
		{
			self::$sqlite->query("UPDATE ".Functions::first(static::$tables)." SET ".$field_name." = ".self::$sqlite->quote($value)." WHERE ".static::_idField()." = ".self::$sqlite->quote($this->getName().";");
		}
	}