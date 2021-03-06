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
	 * Ein Datenset, das SQL als Speicher verwendet.
	 * Kümmert sich um die Verbindung zur Datenbank und stellt Funktionen zur Verfügung, um diese zu benutzen.
	 * Implementiert die Dataset-Funktionen.
	*/

	abstract class SQLSet extends Dataset implements StaticInit
	{
		/**
		 * Eine SQL-Instanz, die die Datenbank bedient.
		 * @var SQL
		*/
		public static $sql;

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
				Dataset::addDatabaseChangeListener(function(){ SQLSet::init(); });
				$added_to_dataset = true;
			}

			if(!self::$sql)
			{
				try { self::$sql = Classes::SQL(Dataset::getDatabase()); }
				catch(DatasetException $e) {  }
			}
		}

		static function idFromParams($params=null)
		{
			if(!$params || (is_array($params) && !$params[0]))
			{
				do { $name = static::idFromParams(array(Functions::randomID())); } while(!static::exists($name));
			}
			elseif(is_array($params))
				$name = $params[0];
			else
				$name = $params;
			$existing = self::$sql->singleField("SELECT ".static::getMainFieldName()." FROM ".static::getMainTableName()." WHERE LOWER(".static::getMainFieldName().") = LOWER(".self::$sql->quote($name).");");
			if($existing !== false)
				$name = $existing;
			return $name;
		}

		protected static function getMainFieldName()
		{
			return static::$primary_key[1];
		}

		protected static function getMainTableName()
		{
			return static::$primary_key[0];
		}

		static function getList()
		{
			return self::$sql->singleColumn("SELECT DISTINCT ".static::getMainFieldName()." FROM ".static::getMainTableName().";");
		}

		static function getNumber()
		{
			return self::$sql->singleField("SELECT COUNT(*) FROM ".static::getMainTableName().";");
		}

		static function exists($name)
		{
			$name = static::idFromParams(func_get_args());
			return (self::$sql->singleField("SELECT COUNT(*) FROM ".static::getMainTableName()." WHERE LOWER(".static::getMainFieldName().") = LOWER(".self::$sql->quote($name).");") >= 1);
		}

		/**
		 * Gibt den Wert der Spalte $field_name in der ersten in $tables definierten Tabelle zurück, wo
		 * das ID-Feld (_idField()) dem Namen dieses Datensets entspricht.
		 * @param string|array $field_name Kann auch ein Array von Feld-Namen sein, dann wird ein assoziatives Array der Werte zurückgeliefert
		 * @return mixed
		*/

		protected function getMainField($field_name)
		{
			$field_names = (is_array($field_name) ? $field_name : array(0 => $field_name));

			$return = array();
			foreach($field_names as $k=>$v)
			{
				try
				{
					$return[$k] = $this->getCacheValue(array("getMainField", $v));
					unset($field_names[$k]);
				}
				catch(DatasetException $e)
				{
				}
			}

			if(count($field_names) > 0)
			{
				$result = self::$sql->singleLine("SELECT ".implode(", ", $field_names)." FROM ".static::getMainTableName()." WHERE ".static::getMainFieldName()." = ".self::$sql->quote($this->getName()).";");
				foreach($field_names as $k=>$v)
				{
					if(!isset($result[$v]))
					{
						if(is_array($field_name))
							throw new SQLSetException("Field “".$v."” could not be selected.");
						else
							$v = Functions::first($result);
					}
					$return[$k] = $result[$v];
					$this->setCacheValue(array("getMainField", $v), $result[$v]);
				}
			}

			if(is_array($field_name))
				return $return;
			else
				return $return[0];
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
					throw new SQLSetException("setMainField: Could not find a value for field ".$v);
				$set[] = $v." = ".self::$sql->quote($values[$k]);
			}
			if(count($set) < 1)
				throw new SQLSetException("No fields to update.");
			self::$sql->query("UPDATE ".static::getMainTableName()." SET ".implode(", ", $set)." WHERE ".static::getMainFieldName()." = ".self::$sql->quote($this->getName()).";");
			foreach($field_names as $k=>$v)
				$this->setCacheValue(array("getMainField", $v), $values[$k]);
		}
	}