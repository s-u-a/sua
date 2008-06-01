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
		protected static $views = array (
			"planet_ids" => "SELECT DISTINCT galaxy || ':' || system || ':' || planet AS pid FROM planets"
		);

		protected static $tables = array (
			"planet_ids" => array(
				"pid"
			),
			"planets" => array (
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
				"galaxy INTEGER",
				"system INTEGER",
				"planet INTEGER",
				"id TEXT",
				"type TEXT",
				"level INTEGER",
				"scores INTEGER"
			),
			"planets_building" => array (
				"galaxy INTEGER",
				"system INTEGER",
				"planet INTEGER",
				"id TEXT",
				"type TEXT",
				"number INTEGER",
				"start INTEGER",
				"duration REAL",
				"cost0 INTEGER",
				"cost1 INTEGER",
				"cost2 INTEGER",
				"cost3 INTEGER",
				"global INTEGER"
			),
			"planets_remote_fleet" => array (
				"i INTEGER",
				"galaxy INTEGER",
				"system INTEGER",
				"planet INTEGER",
				"user TEXT",
				"id TEXT",
				"number INTEGER",
				"scores INTEGER",
				"from_pid INTEGER"
			)
		);

		static function idFromParams(array $params)
		{
			if(count($params) < 2)
				throw new DatasetException("Insufficient parameters.");
			return $params[0]->getGalaxy().":".$params[0]->getSystem().":".$params[1];
		}

		static function paramsFromId($id)
		{
			$params = explode(":", $id);
			if(count($params) < 2)
				throw new PlanetException("Invalid ID.");
			return array(Classes::System(Classes::Galaxy($params[0]), $params[1]), $params[2]);
		}

		/**
		 * Besiedelt den Planeten.
		 * @param System $system
		 * @param int $planet
		*/

		static function create(System $system, $planet)
		{
			if(self::exists(self::idFromParams(array($sytem, $planet))))
				throw new PlanetException("This planet does already exist.");
			self::$sqlite->query("INSERT INTO planets ( galaxy, system, planet, size_original ) VALUES ( ".self::$sqlite->quote($system->getGalaxy()).", ".self::$sqlite->quote($system->getSystem()).", ".self::$sqlite->quote($planet).", ".self::$sqlite->quote(rand(100, 500))." );");
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
			return $this->params[0]->getGalaxy();
		}

		/**
		 * @return int
		*/

		function getSystem()
		{
			return $this->params[0]->getSystem();
		}

		/**
		 * @return int
		*/

		function getPlanet()
		{
			return $this->params[1];
		}

		function __toString()
		{
			return $this->getGalaxy().":".$this->getSystem().":".$this->getPlanet();
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
			return $this->getMainField("user");
		}

		/**
		 * Gibt den Namen des Planeten zurück.
		 * @return string
		*/

		function getName()
		{
			return $this->getMainField("name");
		}

		/**
		 * Gibt die Größe des Planeten zurück.
		 * @return int
		*/

		function getSize()
		{
			return $this->getMainField("size");
		}

		/**
		 * Gibt die CSS-Klasse für den Planeten zurück. Wird verwendet, um verschiedene
		 * Planetenbilder darzustellen.
		 * @return int
		*/

		function getPlanetClass()
		{
			return Galaxy::calcPlanetClass($this);
		}

		/**
		 * Berechnet die CSS-Klasse eines Planeten.
		 * @param Planet $planet
		*/

		static function calcPlanetClass(Galaxy $planet)
		{
			$type = (((floor($planet->getSystem()/100)+1)*(floor(($planet->getSystem()%100)/10)+1)*(($planet->getSystem()%10)+1))%$planet->getPlanet())*$planet->getPlanet()+($planet->getSystem()%(($planet->getGalaxy()+1)*$planet->getPlanet()));
			return $type%20+1;
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
		 * Gibt einen zufälligen Planeten zurück, der noch nicht besiedelt ist.
		 * @return Planet
		*/

		static function randomFreePlanet()
		{
			$result = self::$sqlite->singleField("SELECT pid FROM planets WHERE NOT owner ORDER BY RANDOM() LIMIT 1;");
			if($result === false)
				throw new PlanetException("No free planets available.");
			return Classes::Planet($result);
		}

		/**
		* Callback-Funktion fuer usort() zum Sortieren von Koordinaten nach Galaxie, System und Planet.
		* @param Planet $a
		* @param Planet $b
		* @return int
		*/

		static function usort(Planet $a, Planet $b)
		{
			$a_expl = array($a->getGalaxy(), $a->getSystem(), $a->getPlanet());
			$b_expl = array($b->getGalaxy(), $b->getSystem(), $b->getPlanet());

			if($a_expl[0] > $b_expl[0])
				return 1;
			elseif($a_expl[0] < $b_expl[0])
				return -1;
			else
			{
				if($a_expl[1] > $b_expl[1])
					return 1;
				elseif($a_expl[1] < $b_expl[1])
					return -1;
				else
				{
					if($a_expl[2] > $b_expl[2])
						return 1;
					elseif($a_expl[2] < $b_expl[2])
						return -1;
					else
						return 0;
				}
			}
		}

		/**
		 * @todo Truemmerfelder
		 * getTruemmerfeld()
		 * setTruemmerfeld()
		 * addTruemmerfeld()
		 * subTruemmerfeld()
		*/
	}