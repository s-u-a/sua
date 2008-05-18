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
	*/

	class Planet
	{
		private $galaxy;
		private $system;
		private $planet;
		private $system_obj;

		/**
		 * @param System $system
		 * @param int $planet
		*/

		function __construct($system, $planet)
		{
			if(!$system->planetExists($planet))
				throw new PlanetException("Planet does not exist.");
			$this->system_obj = $system;
			$this->galaxy = $system->getGalaxy();
			$this->system = $system->getName();
			$this->planet = $planet;
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
		 * @todo Truemmerfelder
		*/
	}