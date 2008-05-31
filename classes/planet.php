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
	 * Repräsentiert einen Planeten im Universum.
	 * Konstruktor: Classes::Planet(System $system, int $planet)
	*/

	class Planet extends SQLiteSet
	{
		protected static $tables = array (
			"planets" => array (
				"pid INTEGER PRIMARY KEY AUTOINCREMENT",
				"galaxy INTEGER",
				"system INTEGER",
				"planet INTEGER",
				"size_original INTEGER",
				"user TEXT",
				"name TEXT",
				"ress0 REAL",
				"ress1 REAL",
				"ress2 REAL",
				"ress3 REAL",
				"ress4 REAL",
				"last_refresh INTEGER",
				"tf0 REAL",
				"tf1 REAL",
				"tf2 REAL",
				"tf3 REAL",
				"size INTEGER",
				"size_used INTEGER"
			),
			"planets_items" => array (
				"pid INTEGER",
				"id TEXT",
				"level INTEGER"
			),
			"planets_building" => array (
				"pid INTEGER",
				"id TEXT",
				"number INTEGER",
				"start INTEGER",
				"duration REAL",
				"cost0 INTEGER",
				"cost1 INTEGER",
				"cost2 INTEGER",
				"cost3 INTEGER"
			)
		);

		static function idFromParams(array $params)
		{
			if(count($params) < 2)
				throw new DatasetException("Insufficient parameters.");
			return self::$sqlite->singleField("SELECT pid FROM planets WHERE galaxy = ".self::$sqlite->quote($params[0])." AND system = ".self::$sqlite->quote($params[1]).";");
		}

		/**
		 * Besiedelt den Planeten.
		 * @param System $system
		 * @param int $planet
		*/

		static function create(System $system, $planet)
		{
			if(self::$sqlite->singleField("SELECT COUNT(*) FROM planets WHERE galaxy = ".self::$sqlite->quote($system->getGalaxy())." AND system = ".self::$sqlite->quote($system->getSystem())." AND planet = ".self::$sqlite->quote($planet)." LIMIT 1;") > 0)
				throw new PlanetException("This planet does already exist.");
			self::$sqlite->query("INSERT INTO planets ( galaxy, system, planet ) VALUES ( ".self::$sqlite->quote($system->getGalaxy()).", ".self::$sqlite->quote($system->getSystem()).", ".self::$sqlite->quote($planet)." );");
			return self::idFromParams(array($system, $planet));
		}

		/**
		 * Entfernt den Planeten.
		 * @todo
		*/

		function destroy()
		{
		}

		/**
		 * @return int
		*/

		function getGalaxy()
		{
			return $this->galaxy;
		}

		/**
		 * @return int
		*/

		function getSystem()
		{
			return $this->system;
		}

		/**
		 * @return int
		*/

		function getPlanet()
		{
			return $this->planet;
		}

		function __toString()
		{
			return $this->galaxy.":".$this->system.":".$this->planet;
		}

		/**
		 * @param string $string Koordinaten im Format Galaxie:System:Planet
		 * @return Planet
		*/

		static function fromString($string)
		{
			$pos = explode(":", $string);
			return Classes::Planet(Classes::System(Classes::Galaxy($pos[0]), $pos[1]), $pos[2]);
		}

		/**
		 * @param int $galaxy
		 * @param int $system
		 * @param int $planet
		 * @return Planet
		*/

		static function fromKoords($galaxy, $system, $planet)
		{
			return Classes::Planet(Classes::System(Classes::Galaxy($galaxy), $system), $planet);
		}

		/**
		 * Gibt den Eigentümer des Planeten zurück.
		 * @return string
		*/

		function getOwner()
		{
			return Classes::Galaxy($this->galaxy)->getPlanetOwner($this->system, $this->planet);
		}

		/**
		 * Gibt den Namen des Planeten zurück.
		 * @return string
		*/

		function getName()
		{
			return Classes::Galaxy($this->galaxy)->getPlanetName($this->system, $this->planet);
		}

		/**
		 * Gibt die Größe des Planeten zurück.
		 * @return int
		*/

		function getSize()
		{
			return Classes::Galaxy($this->galaxy)->getPlanetSize($this->system, $this->planet);
		}

		/**
		 * Gibt die CSS-Klasse für den Planeten zurück. Wird verwendet, um verschiedene
		 * Planetenbilder darzustellen.
		 * @return int
		*/

		function getPlanetClass()
		{
			return Galaxy::calcPlanetClass($this->galaxy, $this->system, $this->planet);
		}

		/**
		 * Liefert zurück, ob $other diesem Planeten entspricht.
		 * @param Planet $other
		 * @return bool
		*/

		function equals($other)
		{
			return ($this->getGalaxy() == $other->getGalaxy() && $this->getSystem() == $other->getSystem() && $this->getPlanet() == $other->getPlanet());
		}

		/**
		 * Gibt die Koordinaten eines Planeten in einem für Benutzer lesbaren Format zurück.
		 * @param Planet $planet
		 * @return string
		*/

		static function format(Planet $planet)
		{
			return sprintf(_("%d:%d:%d"), $planet->getGalaxy(), $planet->getSystem(), $planet->getPlanet());
		}

		/**
		 * @todo Truemmerfelder
		 * getTruemmerfeld()
		 * setTruemmerfeld()
		 * subTruemmerfeld()
		*/
	}