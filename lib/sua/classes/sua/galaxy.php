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
	 * Repräsentiert eine Galaxie im Spiel.
	 * Kann als Iterator durchlaufen werden, Index: Systemnummer, Wert: Systemobjekt
	*/

	class Galaxy extends SQLiteSet implements Iterator
	{
		protected static $views = array (
			"galaxies" => "SELECT galaxy, COUNT(DISTINCT system) AS systems FROM planets GROUP BY galaxy"
		);
		protected static $tables = array (
			"galaxies" => array (
				"galaxy INTEGER",
				"systems INTEGER"
			),
			"planets" => Planet::$tables["planets"]
		);

		private $active_system;

		protected static create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new GalaxyException("This galaxy already exists.");

			self::$sqlite->beginTransaction();
			$galaxy_quote = self::$sqlite->quote($name);
			for($system=1; $system<=999; $system++)
			{
				$system_quote = self::$sqlite->quote($system);
				$planets = rand(10, 30);
				for($planet=1; $planet<=$planets; $planet++)
					self::$sqlite->transactionQuery("INSERT INTO planets ( galaxy, system, planet, size_original ) VALUES ( ".$galaxy_quote.", ".$system_quote.", ".self::$sqlite->quote($planet).", ".self::$sqlite->quote(rand(100, 500))." );");
			}
			self::$sqlite->endTransaction();
			return $name;
		}

		public destroy()
		{
			self::$sqlite->query("DELETE FROM planets WHERE galaxy = ".self::$sqlite->quote($this->getName()).";");
		}

		protected static datasetName($name=null)
		{
			if(!isset($name))
				$name = self::$sqlite->singleField("SELECT MAX(galaxy)+1 FROM galaxies;");
			return parent::datasetName($name);
		}

		function getSystemsCount()
		{
			return $this->getMainField("systems");
		}

		function rewind()
		{
			$this->active_system = null;
		}

		function current()
		{
			if($this->valid())
				return Classes::System($this, $this->key());
			else
				return false;
		}

		function key()
		{
			if(!isset($this->active_system))
				return self::$sqlite->singleField("SELECT system FROM planets WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." ORDER BY system ASC LIMIT 1;");
			else
				return $this->active_system;
		}

		function next()
		{
			$this->active_system = self::$sqlite->singleField("SELECT system FROM planets WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system > ".self::$sqlite->quote($this->key())." ORDER BY system ASC LIMIT 1;");
			return $this->current();
		}

		function valid()
		{
			return ($this->active_system !== false);
		}

		function getPlanetOwner($system, $planet)

		function getPlanetOwnerFlag($system, $planet)

		function setPlanetOwner($system, $planet, $owner)

		function setPlanetOwnerFlag($system, $planet, $flag)

		function getPlanetName($system, $planet)

		function setPlanetName($system, $planet, $name)

		function getPlanetOwnerAlliance($system, $planet)

		function setPlanetOwnerAlliance($system, $planet, $alliance)

		function getPlanetSize($system, $planet)

		function setPlanetSize($system, $planet, $size)

		function resetPlanet($system, $planet)
		{
			return ($this->setPlanetName($system, $planet, '') && $this->_setPlanetOwner($system, $planet, '')
			&& $this->setPlanetOwnerAlliance($system, $planet, '') && $this->setPlanetSize($system, $planet, rand(100, 500)));
		}
	}