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
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Repräsentiert ein Sonnensystem im Spiel.
	 * Kann als Iterator durchlaufen werden, Index: Planetennummer, Wert: Planetenobjekt
	*/

	class System implements Iterator
	{
		protected static $tables = array (
			"systems" => array (
				"id TEXT",
				"planets INTEGER"
			),
			"planets" => Planet::$tables["planets"]
		);

		protected static $views = array (
			"systems" => "SELECT galaxy || ':' || system AS id, COUNT(DISTINCT planet) AS planets FROM planets GROUP BY id"
		);

		private $active_planet;

		static function idFromParams(array $params)
		{
			if(count($params) < 2)
				throw new DatasetException("Insufficient parameters.");
			return $params[0]->getGalaxy().":".$params[1];
		}

		static function paramsFromId($id)
		{
			$arr = explode(":", $id);
			if(count($arr) < 2)
				throw new SystemException("Invalid ID.");
			return array(Classes::Galaxy($arr[0]), $arr[1]);
		}

		static function create(Galaxy $galaxy, $system, $transaction=false)
		{
			if(self::$sqlite->singleField("SELECT COUNT(*) FROM planets WHERE galaxy = ".self::$sqlite->quote($galaxy->getGalaxy())." AND system = ".self::$sqlite->quote($system)." LIMIT 1;") > 0)
				throw new PlanetException("This system does already exist.");

			$planets = rand(10, 30);
			for($planet=1; $planet<=$planets; $planet++)
				self::$sqlite->query("INSERT INTO planets ( galaxy, system, planet, size_original ) VALUES ( ".self::$sqlite->quote($galaxy->getGalaxy()).", ".self::$sqlite->quote($system).", ".self::$sqlite->quote($planet).", ".self::$sqlite->quote(rand(100, 500))." );");
			return self::idFromParams($galaxy, $system);
		}

		function destroy()
		{
			self::$sqlite->query("DELETE FROM planets WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem()).";");
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
				return self::$sqlite->singleField("SELECT planet FROM planets WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem())." ORDER BY planet ASC LIMIT 1;");
			else
				return $this->active_planet;
		}

		function next()
		{
			$this->active_planet = self::$sqlite->singleField("SELECT planet FROM planets WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem())." AND planet > ".self::$sqlite->quote($this->key())." ORDER BY planet ASC LIMIT 1;");
			return $this->current();
		}

		function valid()
		{
			return ($this->active_planet !== false);
		}

		/**
		 * Gibt die Koordinaten eines Systems in einem für Benutzer lesbaren Format zurück.
		 * @param System $system
		 * @return string
		*/

		static function format(System $planet)
		{
			return sprintf(_("%d:%d"), $system->getGalaxy(), $system->getSystem());
		}
	}