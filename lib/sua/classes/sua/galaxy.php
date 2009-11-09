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

	class Galaxy extends SQLSet implements \Iterator,StaticInit
	{
		protected static $primary_key = array("t_galaxies", "c_galaxy");

		static function init()
		{
			__autoload("\sua\Planet");
			parent::init();
		}

		private $active_system;

		static function create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new GalaxyException("This galaxy already exists.");

			self::$sql->beginTransaction();
			$galaxy_quote = self::$sql->quote($name);
			for($system=1; $system<=999; $system++)
			{
				$system_quote = self::$sql->quote($system);
				$planets = rand(10, 30);
				for($planet=1; $planet<=$planets; $planet++)
					self::$sql->transactionQuery("INSERT INTO t_planets ( c_galaxy, c_system, c_planet, c_size_original ) VALUES ( ".$galaxy_quote.", ".$system_quote.", ".self::$sql->quote($planet).", ROUND(RANDOM()*400)+100 );");
			}
			self::$sql->endTransaction();
			return $name;
		}

		public function destroy()
		{
			self::$sql->query("DELETE FROM t_planets WHERE c_galaxy = ".self::$sql->quote($this->getName()).";");
		}

		static function datasetName($name=null)
		{
			if(!isset($name))
				$name = self::$sql->singleField("SELECT MAX(c_galaxy)+1 FROM t_galaxies;");
			return parent::datasetName($name);
		}

		function getGalaxy()
		{
			return $this->getName();
		}

		function getSystemsCount()
		{
			return $this->getMainField("c_systems");
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
				return self::$sql->singleField("SELECT c_system FROM t_planets WHERE c_galaxy = ".self::$sql->quote($this->getGalaxy())." ORDER BY c_system ASC LIMIT 1;");
			else
				return $this->active_system;
		}

		function next()
		{
			$this->active_system = self::$sql->singleField("SELECT c_system FROM t_planets WHERE c_galaxy = ".self::$sql->quote($this->getGalaxy())." AND c_system > ".self::$sql->quote($this->key())." ORDER BY c_system ASC LIMIT 1;");
			return $this->current();
		}

		function valid()
		{
			return ($this->active_system !== false);
		}
	}
