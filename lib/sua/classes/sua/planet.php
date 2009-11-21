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
	 * Repräsentiert einen Planeten im Universum.
	 * Konstruktor: Classes::Planet(System $system, int $planet)
	*/

	class Planet extends SQLSet
	{
		protected static $primary_key = array("t_planets", "c_galaxy || ':' || c_system || ':' || c_planet");

		protected $cache = array();

		static function idFromParams($params = null)
		{
			if(!$params || count($params) < 1 || ($params[0] instanceof System && count($params) < 2))
				throw new DatasetException("Insufficient parameters.");
			if($params[0] instanceof System)
				return $params[0]->getGalaxy().":".$params[0]->getSystem().":".$params[1];
			else
				return $params[0];
		}

		static function paramsFromId($id)
		{
			$params = explode(":", $id);
			if(count($params) < 2)
				throw new PlanetException("Invalid ID.");
			return array(Classes::System(Classes::Galaxy($params[0]), $params[1]), $params[2]);
		}

		/**
		 * Erzeugt einen Planeten.
		 * @param string $name Galaxie ":" System ":" Planet
		 * @return void
		*/

		static function create($name=null)
		{
			if(self::exists($name))
				throw new PlanetException("This planet does already exist.");
			$name = self::datasetName($name);
			list($system, $planet) = self::paramsFromId($name);
			self::$sql->query("INSERT INTO t_planets ( c_galaxy, c_system, c_planet, c_size_original ) VALUES ( ".self::$sql->quote($system->getGalaxy()).", ".self::$sql->quote($system->getSystem()).", ".self::$sql->quote($planet).", ROUND(RANDOM()*400)+100) );");
			return self::idFromParams(array($system, $planet));
		}

		/**
		 * Entfernt den Planeten.
		*/

		function destroy()
		{
			if($this->getOwner())
				$this->decolonise();
			self::$sql->query("DELETE FROM t_planets_items WHERE ".$this->sqlCond().";");
			self::$sql->query("DELETE FROM t_planets_building WHERE ".$this->sqlCond().";");
			self::$sql->query("DELETE FROM t_planets WHERE ".$this->sqlCond().";");
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
		 * Baut die Koordinaten in eine SQL-Abfrage ein.
		 * @return string
		*/

		private function sqlCond()
		{
			return "c_galaxy = ".self::$sql->quote($this->getGalaxy())." AND c_system = ".self::$sql->quote($this->getSystem())." AND c_planet = ".self::$sql->quote($this->getPlanet());
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
			return $this->getMainField("c_user");
		}

		/**
		 * Gibt den Namen des Planeten zurück.
		 * @return string
		*/

		function getGivenName()
		{
			return $this->getMainField("c_name");
		}

		/**
		 * Gibt die Größe des Planeten zurück.
		 * @param bool $original Die ursprüngliche Größe? (Ohne Ingenieurswissenschaft)
		 * @return int
		*/

		function getSize($original=false)
		{
			return $this->getMainField($original ? "c_size_original" : "c_size");
		}

		/**
		 * Gibt zurück, wieviele Felder auf diesem Planeten bereits bebaut sind.
		 * @return int
		*/

		function getUsedFields()
		{
			return self::$sql->singleField("SELECT SUM(c_fields) FROM t_planets_items WHERE c_galaxy = ".self::$sql->quote($this->getGalaxy())." AND c_system = ".self::$sql->quote($this->getSystem())." AND c_planet = ".self::$sql->quote($this->getPlanet()).";");
		}

		/**
		 * Gibt zurück, wieviele Felder auf diesem Planeten noch zur Verfügung stehen.
		 * @return int
		*/

		function getRemainingFields()
		{
			return $this->getSize()-$this->getUsedFields();
		}

		/**
		 * Setzt die Planetengröße neu.
		 * @param int $size
		 * @return void
		*/

		function _setFields($size)
		{
			$this->setMainField("c_size", $size);
		}

		/**
		 * Gibt die CSS-Klasse für den Planeten zurück. Wird verwendet, um verschiedene
		 * Planetenbilder darzustellen.
		 * @return int
		*/

		function getPlanetClass()
		{
			return self::calcPlanetClass($this);
		}

		/**
		 * Berechnet die CSS-Klasse eines Planeten.
		 * @param Planet $planet
		*/

		static function calcPlanetClass(Planet $planet)
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
		 * Gibt die Koordinaten des Planeten in einem für Benutzer lesbaren Format zurück.
		 * @return string
		*/

		function format()
		{
			return sprintf(_("%d:%d:%d"), $this->getGalaxy(), $this->getSystem(), $this->getPlanet());
		}

		/**
		 * Gibt einen zufälligen Planeten zurück, der noch nicht besiedelt ist.
		 * @return Planet
		*/

		static function randomFreePlanet()
		{
			$result = self::$sql->singleField("SELECT c_galaxy || ':' || c_system || ':' || c_planet FROM t_planets WHERE c_user IS NULL ORDER BY RANDOM() LIMIT 1;");
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
		 * Gibt ein Array mit Planeten zurück, die diesem Benutzer gehören. Dieses Array ist so sortiert,
		 * wie der Benutzer es eingestellt hat.
		 * @param string $user
		 * @return array(Planet)
		*/

		static function getPlanetsByUser($user)
		{
			$return = array();
			self::$sql->query("SELECT c_galaxy,c_system,c_planet FROM t_planets WHERE c_user = ".self::$sql->quote($user)." ORDER BY c_user_index ASC;");
			while(($r = self::$sql->nextResult()) !== false)
				$return[] = Planet::fromKoords($r["c_galaxy"], $r["c_system"], $r["c_planet"]);
			return $return;
		}

		/**
		 * Liest das Trümmerfeld des Planeten aus.
		 * @return array 0 => Carbon, 1 => Aluminium ...
		*/

		function getTruemmerfeld()
		{
			return array($this->getMainField("c_tf0"), $this->getMainField("c_tf1"), $this->getMainField("c_tf2"), $this->getMainField("c_tf3"));
		}

		/**
		 * Setzt das Trümmerfeld des Planeten neu.
		 * @param array $ress 0 => Carbon, 1 => Aluminium, 2 => Wolfram, 3 => Radium
		 * @return void
		*/

		function setTruemmerfeld($ress)
		{
			$this->setMainField("c_tf0", $ress[0]);
			$this->setMainField("c_tf1", $ress[1]);
			$this->setMainField("c_tf2", $ress[2]);
			$this->setMainField("c_tf3", $ress[3]);
		}

		/**
		 * Fügt dem Trümmerfeld Rohstoffe hinzu.
		 * @param array $ress 0 => Carbon, 1 => Aluminium, 2 => Wolfram, 3 => Radium
		 * @return void
		*/

		function addTruemmerfeld($ress)
		{
			$current = $this->getTruemmerfeld();
			$this->setTruemmerfeld(array($current[0]+$ress[0], $current[1]+$ress[1], $current[2]+$ress[2], $current[3]+$ress[3]));
		}

		/**
		 * Zieht Rohstoffe vom Trümmerfeld ab.
		 * @param array $ress 0 => Carbon, 1 => Aluminium, 2 => Wolfram, 3 => Radium
		 * @return void
		*/

		function subTruemmerfeld($ress)
		{
			$current = $this->getTruemmerfeld();
			$this->setTruemmerfeld(array($current[0]-$ress[0], $current[1]-$ress[1], $current[2]-$ress[2], $current[3]-$ress[3]));
		}


////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * Löscht den Planeten aus der Planetenliste des Benutzers. Hierzu werden folgende Aktionen durch-
		 * geführt:
		 * • Fremde Flotten zu diesem Planeten zurückschicken
		 * • Auf diesem Planeten fremdstationierte Flotten zurückschicken
		 * • Planeten auflösen inklusive fremdstationierter Flotten von diesem Planeten
		 * @todo Was ist mit Flotten, die von diesem Planeten kommen?
		 * @return void
		 * @todo Alter Name removePlanet
		*/

		function decolonise()
		{
			global $types_message_types;

			# Fremdstationierte Flotten auf diesem Planeten zurueckschicken
			foreach(Fleet::getFleetsPositionedOnPlanet($this) as $fleet)
			{
				$fleet_obj = Classes::Fleet($fleet);
				foreach($fleets_obj->getUsersList() as $user)
					$fleet_obj->callBack($user);
			}

			Fleet::planetRemoved($this);

			# Planeten aus der Karte loeschen
			self::$sql->backgroundQuery("UPDATE t_planets SET c_user = NULL, c_name = NULL, c_size = c_size_original, c_ress0 = 0, c_ress1 = 0, c_ress2 = 0, c_ress3 = 0, c_ress4 = 0 WHERE c_galaxy = ".self::$sql->quote($this->getGalaxy())." AND c_system = ".self::$sql->quote($this->getSystem())." AND c_planet = ".self::$sql->quote($this->getPlanet()));
			self::$sql->backgroundQuery("DELETE FROM t_planets_items WHERE c_galaxy = ".self::$sql->quote($this->getGalaxy())." AND c_system = ".self::$sql->quote($this->getSystem())." AND c_planet = ".self::$sql->quote($this->getPlanet()));
			self::$sql->backgroundQuery("DELETE FROM t_planets_building WHERE c_galaxy = ".self::$sql->quote($this->getGalaxy())." AND c_system = ".self::$sql->quote($this->getSystem())." AND c_planet = ".self::$sql->quote($this->getPlanet()));
		}

		/**
		 * Fügt einen Planeten in die Liste der Planeten des Benutzers ein.
		 * @param string $user Der Benutzername.
		 * @return int Der Index des neuen Planeten.
		*/

		function colonise($user)
		{
			$user_obj = Classes::User($user);
			if(!$user_obj->checkPlanetCount())
				throw new PlanetException("Planet limit reached.", PlanetException::ERROR_PLANETCOUNT);

			if($this->getOwner())
				throw new PlanetException("This planet is already colonised.");

			$this->setMainField("c_user", $user_obj->getName());
			$this->setMainField("c_name", $user_obj->_("Kolonie"));

			if(count($this->getPlanetsByUser($user)) <= 0) $size = 375;
			else $size = $this->getSize(true);
			$size = floor($size*($user_obj->getItemLevel("F9", "forschung")/Item::getIngtechFactor()+1));
			$this->setMainField("c_size", $size);

			$index = self::$sql->singleField("SELECT MAX(c_user_index) FROM t_planets WHERE c_user = ".self::$sql->quote($user));
			if($index === false)
				$index = 0;
			else
				$index++;
			$this->setMainField("c_user_index", $index);

			return $index;
		}

		/**
		 * Verändert die Reihenfolge der Planetenliste des Benutzers. Schiebt
		 * den Planeten mit dem Index $planet um eins nach oben.
		 * @return void
		 * @throw PlanetException Wenn der Planet bereits oben in der Liste steht.
		*/

		function moveUp()
		{
			$owner = $this->getMainField("c_user");
			if(!$owner)
				throw PlanetException("This planet is not colonised.");
			$current_index = $this->getMainField("c_user_index");
			$next_index = self::$sql->singleLine("SELECT c_galaxy,c_system,c_planet,c_user_index FROM t_planets WHERE c_user_index < ".self::$sql->quote($current_index)." ORDER BY c_user_index DESC LIMIT 1;");
			if(!$next_index)
				throw new UserException("This planet is on the top.");
			self::$sql->backgroundQuery("UPDATE t_planets SET c_user_index = ".self::$sql->quote($current_index)." WHERE c_galaxy = ".self::$sql->quote($next_index["c_galaxy"])." AND c_system = ".self::$sql->quote($next_index["c_system"])." AND c_planet = ".self::$sql->quote($next_index["c_planet"]).";");
			self::$sql->backgroundQuery("UPDATE t_planets SET c_user_index = ".self::$sql->quote($next_index["index"])." WHERE ".$this->sqlCond().";");
		}

		/**
		 * Verändert die Reihenfolge der Planetenliste des Benutzers. Schiebt
		 * den Planeten mit dem Index $planet um eins nach unten.
		 * @return void
		 * @throw UserException Wenn der Planet bereits unten in der Liste steht.
		*/

		function moveDown()
		{
			$owner = $this->getMainField("c_user");
			if(!$owner)
				throw PlanetException("This planet is not colonised.");
			$current_index = $this->getMainField("c_user_index");
			$next_index = self::$sql->singleLine("SELECT c_galaxy,c_system,c_planet,c_user_index FROM t_planets WHERE c_user_index > ".self::$sql->quote($current_index)." ORDER BY c_user_index ASC LIMIT 1;");
			if(!$next_index)
				throw new UserException("This planet is on the bottom.");
			self::$sql->backgroundQuery("UPDATE t_planets SET c_user_index = ".self::$sql->quote($current_index)." WHERE c_galaxy = ".self::$sql->quote($next_index["c_galaxy"])." AND c_system = ".self::$sql->quote($next_index["c_system"])." AND c_planet = ".self::$sql->quote($next_index["c_planet"]).";");
			self::$sql->backgroundQuery("UPDATE t_planets SET c_user_index = ".self::$sql->quote($next_index["index"])." WHERE ".$this->sqlCond().";");
		}

		/**
		 * Setzt oder liest den Namen des Planeten.
		 * @param string $name Der neue Name.
		 * @return void
		*/

		function setGivenName($name)
		{
			$this->setMainField("c_name", $name);
		}

		/**
		 * Gibt die Rohstoffbestände auf dem aktiven Planeten zurück.
		 * @param bool $refresh Soll Planet->refreshRess() ausgeführt werden?
		 * @return array(int)
		*/

		function getRess($refresh=true)
		{
			if($refresh)
			{
				try
				{
					$this->refreshRess();
				}
				catch(PlanetException $e)
				{
				}
			}

			$ress = $this->getMainField(array("c_ress0", "c_ress1", "c_ress2", "c_ress3", "c_ress4"));

			if($refresh)
			{
				$prod = $this->getProduction();
				$ress[5] = $prod[5];
			}

			return $ress;
		}

		/**
		 * Fügt den Rohstoffbeständen des Planeten Rohstoffe hinzu.
		 * @param array $ress Array mit Rohstoffen
		 * @return void
		*/

		function addRess(array $ress)
		{
			$cur = $this->getMainField(array("c_ress0", "c_ress1", "c_ress2", "c_ress3", "c_ress4"));
			if(isset($ress[0])) $cur[0] += $ress[0];
			if(isset($ress[1])) $cur[1] += $ress[1];
			if(isset($ress[2])) $cur[2] += $ress[2];
			if(isset($ress[3])) $cur[3] += $ress[3];
			if(isset($ress[4])) $cur[4] += $ress[4];
			$this->setMainField(array("c_ress0", "c_ress1", "c_ress2", "c_ress3", "c_ress4"), $cur);
		}

		/**
		 * Zieht Rohstoffe vom Bestand auf dem aktiven Planeten ab.
		 * @param array $ress Das Rohstoff-Array
		 * @param bool $make_scores Sollen die Rohstoffe zu den ausgegebenen Punkten gezählt werden?
		 * @return void
		*/

		function subtractRess(array $ress, $make_scores=true)
		{
			$ress_m = array();
			foreach($ress as $k=>$v)
				$ress_m[$k] = -$v;
			$this->addRess($ress_m);

			if($make_scores)
			{
				$user_obj = Classes::User($this->getOwner());
				$user_obj->_addSpentRess($ress);
			}
		}

		/**
		 * Überprüft, ob die angegebenen Rohstoffe auf dem Planeten vorhanden sind.
		 * @param array $ress Rohstoff-Array
		 * @param bool $refresh Siehe Planet->getRess()
		 * @return bool
		*/

		function checkRess(array $ress, $refresh=true)
		{
			$cur = $this->getRess($refresh);
			if(isset($ress[0]) && $ress[0] > $cur[0]) return false;
			if(isset($ress[1]) && $ress[1] > $cur[1]) return false;
			if(isset($ress[2]) && $ress[2] > $cur[2]) return false;
			if(isset($ress[3]) && $ress[3] > $cur[3]) return false;
			if(isset($ress[4]) && $ress[4] > $cur[4]) return false;

			return true;
		}

		/**
		 * Erneuert die Rohstoffbestände auf diesem Planeten. Der Zeitpunkt der
		 * letzten Erneuerung wurde zwischengespeichert, die Produktion seither
		 * wird nun hinzugefügt.
		 * @param int $a_time Bestand zu diesem Zeitpunkt statt zum aktuellen verwenden
		 * @throw UserException Die letzte Aktualisierung ist neuer als $time
		 * @return void
		*/

		protected function refreshRess($a_time=null)
		{
			$time = isset($a_time) ? $a_time : time();
			$last_refresh = Date::fromPostgres($this->getMainField("c_last_refresh"))->getTime();
			if($last_refresh == $time)
				return;
			elseif($last_refresh >= $time)
				throw new PlanetException("Last refresh is in the future (".$last_refresh." >= ".$time.", current time ".time().").");

			$prod = $this->getProduction(isset($a_time));
			$limit = $this->getProductionLimit(isset($a_time));
			$cur = $this->getRess(isset($a_time));

			$f = ($time-$last_refresh)/3600;

			for($i=0; $i<=4; $i++)
			{
				if($cur[$i] >= $limit[$i])
					continue;
				$cur[$i] += $prod[$i]*$f;
				if($cur[$i] > $limit[$i])
					$cur[$i] = $limit[$i];
			}

			$this->setMainField(array("c_ress0", "c_ress1", "c_ress2", "c_ress3", "c_ress4"), $cur);
			$this->setMainField("c_last_refresh", Classes::Date($time));
		}

		/**
		 * Gibt den eingestellten Produktionsfaktor eines Gebäudes zurück.
		 * @param string $gebaeude Die Item-ID.
		 * @return float
		*/

		function getProductionFactor($gebaeude)
		{
			try { return $this->getCacheValue(array("getProductionFactor", $gebaeude)); }
			catch(DatasetException $e) {}

			$factor = self::$sql->singleField("SELECT c_prod_factor FROM t_planets_items WHERE ".$this->sqlCond()." AND c_id = ".self::$sql->quote($gebaeude)." LIMIT 1;");
			if(!$factor)
				$factor = 1;
			$this->setCacheValue(array("getProductionFactor", $gebaeude), $factor);
			return $factor;
		}

		/**
		 * Setzt den Produktionsfaktor eines Gebäudes.
		 * @param string $gebaeude Die Item-ID
		 * @param float $factor
		 * @return void
		*/

		function setProductionFactor($gebaeude, $factor)
		{
			$factor = (float) $factor;

			if($factor < 0) $factor = 0;
			if($factor > 1) $factor = 1;

			self::$sql->backgroundQuery("UPDATE t_planets_items SET c_prod_factor = ".self::$sql->quote($factor)." WHERE ".$this->sqlCond()." AND c_id = ".self::$sql->quote($gebaeude).";");

			$this->setCacheValue(array("getProductionFactor", $gebaeude), $factor);
		}

		/**
		 * Gibt zurück, wieviel pro Stunde produziert wird.
		 * @return array [ Carbon, Aluminium, Wolfram, Radium, Tritium, Energie, Unterproduktionsfaktor Energie, Energiemaximum erreicht? ]
		*/

		function getProduction()
		{
			try { return $this->getCacheValue(array("getProduction")); }
			catch(DatasetException $e) { }

			$prod = array(0,0,0,0,0,0,0,false);
			$owner = $this->getOwner();
			if($owner)
			{
				$owner_obj = Classes::User($owner);
				if($owner_obj->permissionToAct())
				{
					$gebaeude = $owner_obj->getItemsList("gebaeude");

					$energie_prod = 0;
					$energie_need = 0;
					foreach($gebaeude as $id)
					{
						$item = $owner_obj->getItemInfo($id, "gebaeude", array("prod"), $this);
						if($item["prod"][5] < 0) $energie_need -= $item["prod"][5];
						elseif($item["prod"][5] > 0) $energie_prod += $item["prod"][5];

						$prod[0] += $item["prod"][0];
						$prod[1] += $item["prod"][1];
						$prod[2] += $item["prod"][2];
						$prod[3] += $item["prod"][3];
						$prod[4] += $item["prod"][4];
					}

					$limit = $this->getProductionLimit();
					if($energie_prod > $limit[5])
					{
						$energie_prod = $limit[5];
						$prod[7] = true;
					}

					$f = 1;
					if($energie_need > $energie_prod) # Nicht genug Energie
					{
						$f = $energie_prod/$energie_need;
						$prod[0] *= $f;
						$prod[1] *= $f;
						$prod[2] *= $f;
						$prod[3] *= $f;
						$prod[4] *= $f;
					}

					$prod[5] = $energie_prod-$energie_need;

					$lib_config = Config::getLibConfig();
					$min_production = array(
						$lib_config->getConfigValue("users", "min_production", "ress0"),
						$lib_config->getConfigValue("users", "min_production", "ress1"),
						$lib_config->getConfigValue("users", "min_production", "ress2"),
						$lib_config->getConfigValue("users", "min_production", "ress3"),
						$lib_config->getConfigValue("users", "min_production", "ress4")
					);

					foreach($min_production as $k=>$v)
					{
						if(!isset($prod[$k])) $prod[$k] = 0;
						if($prod[$k] < $v) $prod[$k] = $v;
					}

					Functions::stdround($prod[0]);
					Functions::stdround($prod[1]);
					Functions::stdround($prod[2]);
					Functions::stdround($prod[3]);
					Functions::stdround($prod[4]);
					Functions::stdround($prod[5]);

					$prod[6] = $f;
				}
			}
			$this->setCacheValue(array("getProduction"), $prod);
			return $prod;
		}

		/**
		 * Gibt die maximalen Produktionsmengen für Rohstoffe und Energie zurück.
		 * @return array(float)
		*/

		function getProductionLimit()
		{
			$lib_config = Config::getLibConfig();
			$limit = array(
				$lib_config->getConfigValue("users", "production_limit", "initial", "ress0"),
				$lib_config->getConfigValue("users", "production_limit", "initial", "ress1"),
				$lib_config->getConfigValue("users", "production_limit", "initial", "ress2"),
				$lib_config->getConfigValue("users", "production_limit", "initial", "ress3"),
				$lib_config->getConfigValue("users", "production_limit", "initial", "ress4"),
				$lib_config->getConfigValue("users", "production_limit", "initial", "energy")
			);
			$steps = array(
				$lib_config->getConfigValue("users", "production_limit", "steps", "ress0"),
				$lib_config->getConfigValue("users", "production_limit", "steps", "ress1"),
				$lib_config->getConfigValue("users", "production_limit", "steps", "ress2"),
				$lib_config->getConfigValue("users", "production_limit", "steps", "ress3"),
				$lib_config->getConfigValue("users", "production_limit", "steps", "ress4"),
				$lib_config->getConfigValue("users", "production_limit", "steps", "energy")
			);
			$limit[0] += $this->getItemLevel("R02", "roboter")*$steps[0];
			$limit[1] += $this->getItemLevel("R03", "roboter")*$steps[1];
			$limit[2] += $this->getItemLevel("R04", "roboter")*$steps[2];
			$limit[3] += $this->getItemLevel("R05", "roboter")*$steps[3];
			$limit[4] += $this->getItemLevel("R06", "roboter")*$steps[4];
			$limit[5] += $this->getItemLevel("F3", "forschung")*$steps[5];

			return $limit;
		}

		/**
		 * Gibt die aktuelle Ausbaustufe (Gebäude, Forschung) bzw. die Anzahl
		 * (Roboter, Schiffe, Verteidigung) des Items auf diesem Planeten zurück.
		 * @param string $id
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return int
		*/

		function getItemLevel($id, $type=null)
		{
			if($type === false || $type === null)
				$type = Item::getItemType($id);

			if($type == "forschung") // Forschungen sind global, deswegen im Benutzerobjekt gespeichert
			{
				$user_obj = Classes::User($this->getOwner());
				return $user_obj->getItemLevel($id);
			}

			try { return $this->getCacheValue(array("getItemLevel", $type, $id)); }
			catch(DatasetException $e) { }

			$level = self::$sql->singleField("SELECT c_level FROM t_planets_items WHERE ".$this->sqlCond()." AND c_id = ".self::$sql->quote($id)." AND c_type = ".self::$sql->quote($type)." LIMIT 1;");
			if($level === false)
				$level = 0;
			$this->setCacheValue(array("getItemLevel", $type, $id), $level);
			return $level;
		}

		/**
		 * Verschiebt alle Fertigstellungszeiten auf diesem Planeten um $seconds Sekunden nach hinten. Zur internen Verwendung, wenn
		 * der Urlaubsmodus beendet wurde.
		 * @param int $seconds
		 * @return void
		 * @see User::umode()
		*/

		function _delayBuildingThings($seconds)
		{
			self::$sql->backgroundQuery("UPDATE t_planets_building SET c_start = c_start + ".self::$sql->quote($seconds)." WHERE ".$this->sqlCond().";");
		}

		/**
		 * Zur internen Verwendung, wenn ein Benutzer umbenannt wird.
		 * @param string $old_name
		 * @param string $new_name
		 * @return void
		 * @see User::rename()
		*/

		static function renameUser($old_name, $new_name)
		{
			self::$sql->backgroundQuery("UPDATE t_planets SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($old_name).";");
		}

		/**
		 * Zur internen Verwendung, wenn eine neue Ausbaustufe der Ingenieurswissenschaft fertiggestellt wird. Die Planeten eines Benutzers
		 * werden um einen Faktor vergrößert.
		 * @param string $user
		 * @param float $factor
		 * @return void
		*/

		static function increaseSize($user, $factor)
		{
			self::$sql->backgroundQuery("UPDATE t_planets SET c_size = CEIL(c_size*".self::$sql->quote($factor).") WHERE c_user = ".self::$sql->quote($user).";");
		}

		/**
		 * Zur internen Verwendung bei Fertigstellung einer neuen Ausbaustufe der Roboterbautechnik. Verkürzt alle Bauzeiten entsprechend
		 * den neuen Auswirkungen der Roboter.
		 * @param string $user
		 * @return void
		 * @todo
		*/

		static function newRobtechFactor($user)
		{
			/*
			foreach(Planet::getPlanetsByUser($this->getName()) as $planet)
			{
				$building = $this->checkBuildingThing("gebaeude");
				$robs = $this->getItemLevel("R01", "roboter", false);
				if($robs > 0 && $building && $building[1] > $time)
				{
					$f_1 = pow(1-0.00125*($this->getItemLevel("F2", false, false)-$value), $robs);
					$f_2 = pow(1-0.00125*$this->getItemLevel("F2", false, false), $robs);
					$remaining = ($building[1]-$time)*$f_2/$f_1;
					$this->raw["building"]["gebaeude"][1] = $time+$remaining;
				}
			}
			*/
		}

		/**
		 * Verändert die Ausbaustufe/Anzahl des Items.
		 * @param string $id
		 * @param int $value Wird zur aktuellen Stufe hinzugezählt
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @param int $time Ausbau geschah zu diesem Zeitpunkt, wichtig für den Eventhandler zum Beispiel bei der Verkürzung der laufenden Bauzeit
		 * @return void
		*/

		function changeItemLevel($id, $value=1, $type=null, $time=null)
		{
			if($value == 0) return;

			if(!isset($time)) $time = time();

			if(!isset($type))
				$type = Item::getItemType($id);

			// TODO: Punkte und belegte Felder aktualisieren
			if(self::$sql->singleField("SELECT COUNT(*) FROM t_planets_items WHERE ".$this->sqlCond()." AND c_id = ".self::$sql->quote($id).";") > 0)
			{
				self::$sql->backgroundQuery("UPDATE t_planets_items SET c_level = c_level+".self::$sql->quote($value)." WHERE ".$this->sqlCond()." AND c_id = ".self::$sql->quote($id).";");
				try
				{
					$cache = $this->getCacheValue(array("getItemLevel", $type, $id));
					$this->setCacheValue(array("getItemLevel", $type, $id), $cache+$value);
				}
				catch(DatasetException $e)
				{
				}
			}
			else
			{
				self::$sql->backgroundQuery("INSERT INTO t_planets_items ( c_galaxy, c_system, c_planet, c_id, c_level ) VALUES ( ".self::$sql->quote($this->getGalaxy()).", ".self::$sql->quote($this->getSystem()).", ".self::$sql->quote($this->getPlanet()).", ".self::$sql->quote($id).", ".self::$sql->quote($value)." );");
				$this->setCacheValue(array("getItemInfo", $type, $id), $value);
			}

			# Felder belegen
			if($type == "gebaeude")
			{
				$item_info = $this->getItemInfo($id, "gebaeude", array("fields"));
				if($item_info["fields"] > 0)
					$this->changeUsedFields($item_info["fields"]*$value);
			}

			switch($id)
			{
				# Ingeneurswissenschaft: Planeten vergroessern TODO
				/*case "F9":
					$planets = $this->getPlanetsList();
					$active_planet = $this->getActivePlanet();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$size = ceil($this->getSize()/(($this->getItemLevel("F9", false, false)-$value)/Item::getIngtechFactor()+1));
						$this->setFields(floor($size*($this->getItemLevel("F9", false, false)/Item::getIngtechFactor()+1)));
					}
					$this->setActivePlanet($active_planet);
					break;*/

				# Bauroboter: Laufende Bauzeit verkuerzen (TODO: Algorithmus scheint nicht zu stimmen)
				/*case "R01":
					$max_rob_limit = floor($this->getBasicFields()/2);
					$counting_after = $this->items[$type][$id];
					$counting_before = $counting_after-$value;
					if($counting_after > $max_rob_limit) $counting_after = $max_rob_limit;
					if($counting_before > $max_rob_limit) $counting_before = $max_rob_limit;
					$counting_value = $counting_after-$counting_before;

					$building = $this->checkBuildingThing("gebaeude");
					if($building && $building[1] > $time)
					{
						$f = pow(1-0.00125*$this->getItemLevel("F2", "forschung", false), $counting_value);
						$old_finished = $building[4][0]-$building[1];
						$old_remaining = ($building[1]-$time)*$building[4][1];
						$new_remaining = $old_remaining*$f;
						$min_building_time = Config::getLibConfig()->getConfigValue("users", "min_building_time");
						if(($old_finished*$f)+$new_remaining < $min_building_time)
						{
							$this->planet_info["building"]["gebaeude"][4][1] = $new_remaining/($min_building_time-($old_finished*$f));
							$new_remaining = $min_building_time-($old_finished*$f);
						}
						else
							$this->planet_info["building"]["gebaeude"][4][1] = 1;
						$this->planet_info["building"]["gebaeude"][1] = $time+$new_remaining;
					}

					break;*/

				# Roboterbautechnik: Auswirkungen der Bauroboter aendern TODO
				/*case "F2":
					$planets = $this->getPlanetsList();
					$active_planet = $this->getActivePlanet();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);

						$building = $this->checkBuildingThing("gebaeude");
						$robs = $this->getItemLevel("R01", "roboter", false);
						if($robs > 0 && $building && $building[1] > $time)
						{
							$f_1 = pow(1-0.00125*($this->getItemLevel("F2", false, false)-$value), $robs);
							$f_2 = pow(1-0.00125*$this->getItemLevel("F2", false, false), $robs);
							$remaining = ($building[1]-$time)*$f_2/$f_1;
							$this->raw["building"]["gebaeude"][1] = $time+$remaining;
						}
					}
					$this->setActivePlanet($active_planet);

					break;*/
			}

			$this->setCacheValue(array("getProduction"), null);
		}

		/**
		 * Gibt Informationen über die im Bau befindlichen Dinge auf diesem Planeten zurück.
		 * Das Rückgabe-Array hat bei Gebäuden und Forschung das folgende Format:
		 * [ Item-ID; Fertigstellungszeitpunkt; Globale Forschung?/Gebäuderückbau?; Verbrauchte Rohstoffe; Planet der globalen Forschung ]
		 * Bei Robotern, Schiffen und Verteidigungsanlagen ist das Format wie folgt:
		 * ( [ Item-ID; Startzeit; Anzahl; Bauzeit pro Stück; Verbrauchte Rohstoffe ] )
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return array|null
		*/

		function checkBuildingThing($type)
		{
			switch($type)
			{
				case "gebaeude":
					$query = self::$sql->singleLine("SELECT c_id,c_start,c_duration,c_cost0,c_cost1,c_cost2,c_cost3,c_number FROM t_planets_building WHERE ".$this->sqlCond()." AND c_type = ".self::$sql->quote($type).";");
					if(!$query)
						return null;
					return array($query["c_id"], $query["c_start"]+$query["c_duration"], $query["c_number"] < 0, array($query["c_cost0"], $query["c_cost1"], $query["c_cost2"], $query["c_cost3"]));
				case "forschung":
					$query = self::$sql->singleLine("SELECT c_id,c_start,c_duration,c_cost0,c_cost1,c_cost2,c_cost3,".($type == "forschung" ? "c_global" : "c_number")." FROM t_planets_building WHERE ".$this->sqlCond()." AND c_type = ".self::$sql->quote($type).";");
					if(!$query)
					{
						foreach(self::getPlanetsByUser($this->getOwner()) as $planet)
						{
							if($planet == $this) continue;
							if(self::$sql->singleField("SELECT c_id FROM t_planets_building WHERE ".$planet->sqlCond()." AND c_type = 'forschung' AND c_global;"))
								return $planet->checkBuildingThing("forschung");
						}
						return null;
					}
					return array($query["c_id"], $query["c_start"]+$query["c_duration"], ($type == "forschung" ? $query["c_global"] && true : $query["c_number"] < 0), array($query["c_cost0"], $query["c_cost1"], $query["c_cost2"], $query["c_cost3"]), $this);
				case "roboter": case "schiffe": case "verteidigung":
					self::$sql->query("SELECT c_id,c_start,c_duration,c_cost0,c_cost1,c_cost2,c_cost3,c_number FROM t_planets_building WHERE ".$this->sqlCond()." AND c_type = ".self::$sql->quote($type).";");
					$return = array();
					while(($res = self::$sql->nextResult()) !== false)
						$return[] = array($res["c_id"], $res["c_start"], $res["c_number"], $res["c_duration"], array($query["c_cost0"], $query["c_cost1"], $query["c_cost2"], $query["c_cost3"]));
					return $return;
				default:
					throw new \InvalidArgumentException("Type ".$type." is unknown.");
			}
		}

		/**
		 * Bricht die aktuell im Bau befindlichen Gegenstände des angegebenen Typs ab.
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @param bool $cancel Wenn true, werden die Rohstoffe bei Gebäude und Forschung rückerstattet.
		 * @return void
		 * @todo
		*/

		function removeBuildingThing($type, $cancel=true)
		{
			switch($type)
			{
				case "gebaeude": case "forschung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || trim($this->planet_info["building"][$type][0]) == "")
						return;

					if($type == "forschung" && $this->planet_info["building"][$type][2])
					{
						$source_planet = $this->planet_info["building"][$type][4];
						//if(!isset($this->raw["planets"][$source_planet]["building"][$type]) || trim($this->raw["planets"][$source_planet]["building"][$type][0]) == "")
						//	return false;
						$active_planet = $this->getActivePlanet();
						$planets = $this->getPlanetsList();
						foreach($planets as $planet)
						{
							$this->setActivePlanet($planet);
							if($planet == $source_planet && $cancel)
								$this->addRess($this->planet_info["building"][$type][3]);
							if(isset($this->planet_info["building"][$type]))
								unset($this->planet_info["building"][$type]);
						}
						$this->setActivePlanet($active_planet);
					}
					elseif($cancel)
						$this->addRess($this->planet_info["building"][$type][3]);

					if($cancel)
					{
						$this->raw["punkte"][7] -= $this->planet_info["building"][$type][3][0];
						$this->raw["punkte"][8] -= $this->planet_info["building"][$type][3][1];
						$this->raw["punkte"][9] -= $this->planet_info["building"][$type][3][2];
						$this->raw["punkte"][10] -= $this->planet_info["building"][$type][3][3];
						$this->raw["punkte"][11] -= $this->planet_info["building"][$type][3][4];
					}

					unset($this->planet_info["building"][$type]);
					$this->changed = true;

					if($cancel)
						$this->refreshMessengerBuildingNotifications($type);

					break;
				case "roboter": case "schiffe": case "verteidigung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || count($this->planet_info["building"][$type]) <= 0)
						return;
					unset($this->planet_info["building"][$type]);
					$this->changed = true;

					if($cancel)
						$this->refreshMessengerBuildingNotifications($type);

					break;
			}
		}

		/**
		 * Eventhandler-Hilfsfunktion: entfernt den nächsten fertigzustellenden Gegenstand und entfernt ihn
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return array|null [ Zeitpunkt; Item-ID; Ausbaustufen; Rohstoffe nach Fertigstellung aktualisieren? ]
		 * @todo
		*/

		function getNextBuiltThing($type)
		{
			$building = $this->checkBuildingThing($type, false);

			switch($type)
			{
				case "gebaeude":
				case "forschung":
				{
					if($building !== false && $building[1] <= time() && $this->removeBuildingThing($type, false))
					{
						$stufen = 1;
						if($type == "gebaeude" && $building[2]) $stufen = -1;
						$this->changed = true;
						return array($building[1], $building[0], $stufen, true);
					}
					break;
				}
				case "roboter":
				case "schiffe":
				case "verteidigung":
				{
					if($building && count($building) > 0)
					{
						$keys = array_keys($building);
						$first_key = array_shift($keys);
						$time = $building[$first_key][1]+$building[$first_key][3];
						if($time <= time())
						{
							$this->planet_info["building"][$type][$first_key][2]--;
							if($this->planet_info["building"][$type][$first_key][2] <= 0)
								unset($this->planet_info["building"][$type][$first_key]);
							else
								$this->planet_info["building"][$type][$first_key][1] = $time;
							$this->changed = true;
							return array($time, $building[$first_key][0], 1, $type == "roboter");
						}
					}
					break;
				}
			}
			return null;
		}

		/**
		 * Stellt alle Gegenstände, die seit der letzten Ausführung fertig geworden sind, fertig.
		 * @return void
		 * @todo
		*/

		function eventhandler()
		{
			return;

			if($this->umode())
				return;

			$active_planet = $this->getActivePlanet();

			$min = null;
			$planets = $this->getPlanetsList();
			$next = array();

			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				$next[$planet] = array(
					"gebaeude" => $this->getNextBuiltThing("gebaeude"),
					"forschung" => $this->getNextBuiltThing("forschung"),
					"roboter" => $this->getNextBuiltThing("roboter"),
					"schiffe" => $this->getNextBuiltThing("schiffe"),
					"verteidigung" => $this->getNextBuiltThing("verteidigung")
				);
			}

			while(true)
			{
				foreach($planets as $planet)
				{
					foreach($next[$planet] as $i=>$arr)
					{
						if($arr !== null && ($min === null || $arr[0] < $next[$min[0]][$min[1]][0]))
							$min = array($planet, $i);
					}
				}

				if($min === null)
					break;

				$action = &$next[$min[0]][$min[1]];

				$this->setActivePlanet($min[0]);

				if($action[3])
					$this->refreshRess($action[0]);

				$this->changeItemLevel($action[1], $action[2], $min[1], $action[0]);

				$next[$min[0]][$min[1]] = $this->getNextBuiltThing($min[1]);

				$min = null;

				$this->changed = true;
			}

			$this->setActivePlanet($active_planet);
		}

		// TODO: Die ganzen build*-Funktionen zusammen fassen

		function buildGebaeude($id, $rueckbau=false)
		{
			if($this->checkBuildingThing("gebaeude")) return false;
			if($id == "B8" && $this->checkBuildingThing("forschung")) return false;
			if($id == "B9" && $this->checkBuildingThing("roboter")) return false;
			if($id == "B10" && ($this->checkBuildingThing("schiffe") || $this->checkBuildingThing("verteidigung"))) return false;

			$item_info = $this->getItemInfo($id, "gebaeude", array("buildable", "debuildable", "ress", "time", "limit_factor"));
			if($item_info && ((!$rueckbau && $item_info["buildable"]) || ($rueckbau && $item_info["debuildable"])))
			{
				# Rohstoffkosten
				$ress = $item_info["ress"];

				if($rueckbau)
				{
					$ress[0] = $ress[0]>>1;
					$ress[1] = $ress[1]>>1;
					$ress[2] = $ress[2]>>1;
					$ress[3] = $ress[3]>>1;
				}

				# Genuegend Rohstoffe zum Ausbau
				if(!$this->checkRess($ress)) return false;

				$time = $item_info["time"];
				if($rueckbau)
					$time = $time>>1;
				$time += time();

				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				$this->planet_info["building"]["gebaeude"] = array($id, $time, $rueckbau, $ress, array(time(), $item_info["limit_factor"]));

				# Rohstoffe abziehen
				$this->subtractRess($ress);

				$this->refreshMessengerBuildingNotifications("gebaeude");

				return true;
			}
			return false;
		}

		function buildForschung($id, $global)
		{
			if($this->checkBuildingThing("forschung")) return false;
			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B8") return false;

			$buildable = true;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if(($global && $this->checkBuildingThing("forschung")) || (!$global && ($building = $this->checkBuildingThing("forschung")) && $building[0] == $id))
				{
					$buildable = false;
					break;
				}
			}
			$this->setActivePlanet($active_planet);

			$item_info = $this->getItemInfo($id, "forschung", array("buildable", "ress", "time_global", "time_local"));
			if($item_info && $item_info["buildable"] && $this->checkRess($item_info["ress"]))
			{
				$build_array = array($id, time()+$item_info["time_".($global ? "global" : "local")], $global, $item_info["ress"]);
				if($global)
				{
					$build_array[] = $this->getActivePlanet();

					$planets = $this->getPlanetsList();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$this->planet_info["building"]["forschung"] = $build_array;
					}
					$this->setActivePlanet($active_planet);
				}
				else $this->planet_info["building"]["forschung"] = $build_array;

				$this->subtractRess($item_info["ress"]);

				$this->refreshMessengerBuildingNotifications("forschung");

				$this->changed = true;

				return true;
			}
			return false;
		}

		function buildRoboter($id, $anzahl)
		{
			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B9") return false;

			$item_info = $this->getItemInfo($id, "roboter", array("buildable", "ress", "time"));
			if(!$item_info || !$item_info["buildable"]) return false;

			$ress = $item_info["ress"];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;

			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info["ress"];
				$anzahlen = array();
				if($ress[0] > 0) $anzahlen[] = floor($planet_ress[0]/$ress[0]);
				if($ress[1] > 0) $anzahlen[] = floor($planet_ress[1]/$ress[1]);
				if($ress[2] > 0) $anzahlen[] = floor($planet_ress[2]/$ress[2]);
				if($ress[3] > 0) $anzahlen[] = floor($planet_ress[3]/$ress[3]);
				$anzahl = min($anzahlen);
				$ress[0] *= $anzahl;
				$ress[1] *= $anzahl;
				$ress[2] *= $anzahl;
				$ress[3] *= $anzahl;
			}

			if($anzahl <= 0) return false;

			$roboter = $this->checkBuildingThing("roboter");
			$make_new = true;
			$last_time = time();
			if($roboter && count($roboter) > 0)
			{
				$roboter_keys = array_keys($this->planet_info["building"]["roboter"]);
				$last = &$this->planet_info["building"]["roboter"][array_pop($roboter_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info["time"])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				if(!isset($this->planet_info["building"]["roboter"])) $this->planet_info["building"]["roboter"] = array();
				$build_array = &$this->planet_info["building"]["roboter"][];
				$build_array = array($id, $last_time, 0, $item_info["time"]);
			}

			$build_array[2] += $anzahl;

			$this->subtractRess($ress);

			$this->refreshMessengerBuildingNotifications("roboter");

			$this->changed = true;

			return true;
		}

		function buildSchiffe($id, $anzahl)
		{
			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B10") return false;

			$item_info = $this->getItemInfo($id, "schiffe", array("buildable", "ress", "time"));
			if(!$item_info || !$item_info["buildable"]) return false;

			$ress = $item_info["ress"];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;

			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info["ress"];
				$anzahlen = array();
				if($ress[0] > 0) $anzahlen[] = floor($planet_ress[0]/$ress[0]);
				if($ress[1] > 0) $anzahlen[] = floor($planet_ress[1]/$ress[1]);
				if($ress[2] > 0) $anzahlen[] = floor($planet_ress[2]/$ress[2]);
				if($ress[3] > 0) $anzahlen[] = floor($planet_ress[3]/$ress[3]);
				$anzahl = min($anzahlen);
				$ress[0] *= $anzahl;
				$ress[1] *= $anzahl;
				$ress[2] *= $anzahl;
				$ress[3] *= $anzahl;
			}

			if($anzahl <= 0) return false;

			$schiffe = $this->checkBuildingThing("schiffe");
			$make_new = true;
			$last_time = time();
			if($schiffe && count($schiffe) > 0)
			{
				$schiffe_keys = array_keys($this->planet_info["building"]["schiffe"]);
				$last = &$this->planet_info["building"]["schiffe"][array_pop($schiffe_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info["time"])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				if(!isset($this->planet_info["building"]["schiffe"])) $this->planet_info["building"]["schiffe"] = array();
				$build_array = &$this->planet_info["building"]["schiffe"][];
				$build_array = array($id, $last_time, 0, $item_info["time"]);
			}

			$build_array[2] += $anzahl;

			$this->subtractRess($ress);

			$this->refreshMessengerBuildingNotifications("schiffe");

			$this->changed = true;

			return true;
		}

		function buildVerteidigung($id, $anzahl)
		{
			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B10") return false;

			$item_info = $this->getItemInfo($id, "verteidigung", array("buildable", "ress", "time"));
			if(!$item_info || !$item_info["buildable"]) return false;

			$ress = $item_info["ress"];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;

			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info["ress"];
				$anzahlen = array();
				if($ress[0] > 0) $anzahlen[] = floor($planet_ress[0]/$ress[0]);
				if($ress[1] > 0) $anzahlen[] = floor($planet_ress[1]/$ress[1]);
				if($ress[2] > 0) $anzahlen[] = floor($planet_ress[2]/$ress[2]);
				if($ress[3] > 0) $anzahlen[] = floor($planet_ress[3]/$ress[3]);
				$anzahl = min($anzahlen);
				$ress[0] *= $anzahl;
				$ress[1] *= $anzahl;
				$ress[2] *= $anzahl;
				$ress[3] *= $anzahl;
			}

			if($anzahl <= 0) return false;

			$verteidigung = $this->checkBuildingThing("verteidigung");
			$make_new = true;
			$last_time = time();
			if($verteidigung && count($verteidigung) > 0)
			{
				$verteidigung_keys = array_keys($this->planet_info["building"]["verteidigung"]);
				$last = &$this->planet_info["building"]["verteidigung"][array_pop($verteidigung_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info["time"])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				if(!isset($this->planet_info["building"]["verteidigung"])) $this->planet_info["building"]["verteidigung"] = array();
				$build_array = &$this->planet_info["building"]["verteidigung"][];
				$build_array = array($id, $last_time, 0, $item_info["time"]);
			}

			$build_array[2] += $anzahl;

			$this->subtractRess($ress);

			$this->refreshMessengerBuildingNotifications("verteidigung");

			$this->changed = true;

			return true;
		}
	}