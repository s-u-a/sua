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

	class SQLiteSet implements Dataset
	{
		protected static $sqlite = Classes::SQLite();
		protected static $tables;
		protected static $id_field;
		protected $name;

		static
		{
			static::checkTables();
		}

		static protected function checkTables()
		{
			self::$sqlite->checkTables(static::$tables);
		}

		function __construct($name=null)
		{
			$name = static::datasetName($name);
			if(!$tables) throw new SQLiteSetException("No tables are used.");
			if(!self::exists($name))
				throw new DatasetException("Dataset does not exist.");
			$this->name = $name;
		}

		function getName()
		{
			return $this->name;
		}

		static function datasetName($name=null)
		{
			if(!isset($name))
			{
				do { $name = Functions::randomID(); } while(!self::exists($name));
			}
			$existing = self::$sqlite->singleQuery("SELECT ".static::$id_field." FROM ".Functions::first(static::$tables)." WHERE LOWER(".static::$id_field.") = LOWER(".self::$sqlite->quote($name).");");
			if($existing !== false)
				$name = $existing;
			return $name;
		}

		static function getList()
		{
			return self::$sqlite->columnQuery("SELECT DISTINCT ".static::$id_field." FROM ".Functions::first(static::$tables).";");
		}

		static function getNumber()
		{
			return self::singleQuery("SELECT COUNT(*) FROM ".Functions::first(static::$tables).";");
		}

		static function exists($name)
		{
			$name = static::datasetName($name);
			return (self::$sqlite->singleQuery("SELECT COUNT(*) FROM ".Functions::first(static::$tables)." WHERE LOWER(".static::$id_field.") = LOWER(".self::$sqlite->quote($name).");") >= 1);
		}

		abstract static function create($name);

		abstract static function destroy();

		protected function getMainField($field_name)
		{
			return self::$sqlite->singleQuery("SELECT ".$field_name." FROM ".Functions::first(static::$tables)." WHERE ".static::$id_field." = ".self::$sqlite->quote($this->name).";");
		}

		protected function setMainField($field_name, $value)
		{
			self::$sqlite->query("UPDATE ".Functions::first(static::$tables)." SET ".$field_name." = ".self::$sqlite->quote($value)." WHERE ".static::$id_field." = ".self::$sqlite->quote($this->name).";");
		}
	}