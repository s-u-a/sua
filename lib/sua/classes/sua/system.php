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
	 * Repräsentiert ein Sonnensystem im Spiel.
	 * Kann als Iterator durchlaufen werden, Index: Planetennummer, Wert: Planetenobjekt
	*/

	class System extends SQLSet implements \Iterator,StaticInit
	{
		protected static $primary_key = array("t_systems", "c_id");

		private $active_planet;

		static function init()
		{
			__autoload("\sua\Planet");
			parent::init();
		}

		static function idFromParams(array $params)
		{
			if(count($params) < 1 || ($params[0] instanceof Galaxy && count($params) < 2))
				throw new DatasetException("Insufficient parameters.");
			if($params[0] instanceof Galaxy)
				return $params[0]->getGalaxy().":".$params[1];
			else
				return $params[0];
		}

		static function paramsFromId($id)
		{
			$arr = explode(":", $id);
			if(count($arr) < 2)
				throw new SystemException("Invalid ID.");
			return array(Classes::Galaxy($arr[0]), $arr[1]);
		}

		static function create($name=null)
		{
			if(self::exists($name))
				throw new SystemException("This planet does already exist.");
			$name = self::datasetName($name);
			list($galaxy, $system) = self::paramsFromId($name);

			$planets = rand(10, 30);
			for($planet=1; $planet<=$planets; $planet++)
				self::$sql->query("INSERT INTO planets ( galaxy, system, planet, size_original ) VALUES ( ".self::$sql->quote($galaxy->getGalaxy()).", ".self::$sql->quote($system).", ".self::$sql->quote($planet).", ".self::$sql->quote(rand(100, 500))." );");
			return self::idFromParams($galaxy, $system);
		}

		function destroy()
		{
			self::$sql->query("DELETE FROM planets WHERE galaxy = ".self::$sql->quote($this->getGalaxy())." AND system = ".self::$sql->quote($this->getSystem()).";");
		}

		/**
		 * @param string $string Koordinaten im Format Galaxie:System
		 * @return System
		*/

		static function fromString($string)
		{
			$pos = explode(":", $string);
			return Classes::System(Classes::Galaxy($pos[0]), $pos[1]);
		}

		/**
		 * @return int
		*/

		function getGalaxy()
		{
			return $this->params[0]->getGalaxy();
		}

		/**
		 * @return int
		*/

		function getSystem()
		{
			return $this->params[1];
		}

		/**
		 * @return int
		*/

		function getPlanetsCount()
		{
			return $this->getMainField("planets");
		}

		function rewind()
		{
			$this->active_planet = null;
			return $this->current();
		}

		function current()
		{
			if($this->valid())
				return Classes::Planet($this, $this->key());
			else
				return false;
		}

		function key()
		{
			if(!isset($this->active_planet))
				return self::$sql->singleField("SELECT planet FROM planets WHERE galaxy = ".self::$sql->quote($this->getGalaxy())." AND system = ".self::$sql->quote($this->getSystem())." ORDER BY planet ASC LIMIT 1;");
			else
				return $this->active_planet;
		}

		function next()
		{
			$this->active_planet = self::$sql->singleField("SELECT planet FROM planets WHERE galaxy = ".self::$sql->quote($this->getGalaxy())." AND system = ".self::$sql->quote($this->getSystem())." AND planet > ".self::$sql->quote($this->key())." ORDER BY planet ASC LIMIT 1;");
			return $this->current();
		}

		function valid()
		{
			return ($this->active_planet !== false);
		}

		/**
		 * Gibt die Koordinaten des Systems in einem für Benutzer lesbaren Format zurück.
		 * @param System $system
		 * @return string
		*/

		function format()
		{
			return sprintf(_("%d:%d"), $this->getGalaxy(), $this->getSystem());
		}
	}