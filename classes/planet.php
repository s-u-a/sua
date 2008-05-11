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
		 * @param integer $planet
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
		 * @return integer
		*/

		function getGalaxy()
		{
			return $this->galaxy;
		}

		/**
		 * @return integer
		*/

		function getSystem()
		{
			return $this->system;
		}

		/**
		 * @return integer
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
		 * @param integer $galaxy
		 * @param integer $system
		 * @param integer $planet
		 * @return Planet
		*/

		static function fromKoords($galaxy, $system, $planet)
		{
			return Classes::Planet(Classes::System(Classes::Galaxy($galaxy), $system), $planet);
		}
	}