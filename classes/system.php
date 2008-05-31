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
	 * Repräsentiert ein Sonnensystem im Spiel.
	*/

	class System
	{
		private $galaxy;
		private $system;
		private $galaxy_obj;

		/**
		 * @param Galaxy $galaxy
		 * @param int $system
		*/

		function __construct(Galaxy $galaxy, $system)
		{
			if(!$galaxy->systemExists($system))
				throw new SystemException("System does not exist.");
			$this->galaxy_obj = $galaxy;
			$this->galaxy = $galaxy->getName();
			$this->system = $system;
		}

		function __toString()
		{
			return $this->galaxy.":".$this->system;
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
		 * @param int $planet
		 * @return bool
		*/

		function planetExists($planet)
		{
			if(floor($planet) != $planet) return false;
			if($planet < 1) return false;
			if($planet > $this->getPlanetsCount()) return false;
			return true;
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

		function getPlanetsCount()
		{
			return $this->galaxy_obj->getPlanetsCount($this->system);
		}
	}