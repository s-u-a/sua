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

	class Planet extends SQLiteSet
	{
		protected static $tables = array (
			"planets" => array (
				"galaxy INTEGER NOT NULL",
				"system INTEGER NOT NULL",
				"planet INTEGER NOT NULL",
				"size_original INTEGER DEFAULT 0",
				"user TEXT",
				"name TEXT",
				"ress0 REAL DEFAULT 0",
				"ress1 REAL DEFAULT 0",
				"ress2 REAL DEFAULT 0",
				"ress3 REAL DEFAULT 0",
				"ress4 REAL DEFAULT 0",
				"last_refresh INTEGER",
				"tf0 REAL DEFAULT 0",
				"tf1 REAL DEFAULT 0",
				"tf2 REAL DEFAULT 0",
				"tf3 REAL DEFAULT 0",
				"size INTEGER DEFAULT 0",
				"user_index INTEGER NOT NULL" // Bestimmt die Reihenfolge der Planeten eines Benutzers
			),
			"planets_items" => array (
				"galaxy INTEGER NOT NULL",
				"system INTEGER NOT NULL",
				"planet INTEGER NOT NULL",
				"id TEXT NOT NULL",
				"type TEXT NOT NULL",
				"level INTEGER DEFAULT 0",
				"scores INTEGER DEFAULT 0",
				"fields INTEGER DEFAULT 0",
				"prod_factor INTEGER DEFAULT 1 NOT NULL"
			),
			"planets_building" => array ( // Gerade bauende Gegenstände
				"galaxy INTEGER NOT NULL",
				"system INTEGER NOT NULL",
				"planet INTEGER NOT NULL",
				"id TEXT NOT NULL", // Item-ID
				"type TEXT NOT NULL", // gebaeude, forschung, roboter, schiffe, verteidigung
				"number INTEGER NOT NULL", // Anzahl der zu bauenden Roboter, Schiffe oder Verteidigungsanlagen dieses Typs
				"start INTEGER NOT NULL", // Zeitstempel, wann der Bau gestartet wurde
				"duration REAL NOT NULL", // Bauzeit eines einzelnen Gegenstandes
				"cost0 INTEGER DEFAULT 0", // Ausgegebene Kosten, Carbon (wissenswert bei Abbruch von Gebäude oder Forschung
				"cost1 INTEGER DEFAULT 0", // Kosten, Aluminium
				"cost2 INTEGER DEFAULT 0", // Kosten, Wolfram
				"cost3 INTEGER DEFAULT 0", // Kosten, Radium
				"global INTEGER DEFAULT 0" // Bei Forschung: 1: Es wird global geforscht.
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

		static function _idField()
		{
			return "galaxy || ':' || system || ':' || planet";
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
			self::$sqlite->query("INSERT INTO planets ( galaxy, system, planet, size_original ) VALUES ( ".self::$sqlite->quote($system->getGalaxy()).", ".self::$sqlite->quote($system->getSystem()).", ".self::$sqlite->quote($planet).", ".self::$sqlite->quote(rand(100, 500))." );");
			return self::idFromParams(array($system, $planet));
		}

		/**
		 * Entfernt den Planeten.
		*/

		function destroy()
		{
			if($this->getOwner())
				$this->decolonise();
			self::$sqlite->query("DELETE FROM planets WHERE ".$this->sqlCond().";");
			self::$sqlite->query("DELETE FROM planets_items WHERE ".$this->sqlCond().";");
			self::$sqlite->query("DELETE FROM planets_building WHERE ".$this->sqlCond().";");
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
			return "galaxy = ".$this->quote($this->getGalaxy())." AND system = ".$this->quote($this->getSystem())." AND planet = ".$this->quote($this->getPlanet());
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
		 * @param bool $original Die ursprüngliche Größe? (Ohne Ingenieurswissenschaft)
		 * @return int
		*/

		function getSize($original=false)
		{
			return $this->getMainField($original ? "size_original" : "size");
		}

		/**
		 * Gibt zurück, wieviele Felder auf diesem Planeten bereits bebaut sind.
		 * @return int
		*/

		function getUsedFields()
		{
			return self::$sqlite->singleField("SELECT SUM(fields) FROM planets_items WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem())." AND planet = ".self::$sqlite->quote($this->getPlanet()).";");
		}

		/**
		 * Gibt zurück, wieviele Felder auf diesem Planeten noch zur Verfügung stehen.
		 * @return int
		*/

		function getRemainingFields()
		{
			$this->_forceActivePlanet();

			return ($this->planet_info["size"][1]-$this->planet_info["size"][0]);
		}

		/**
		 * Setzt die Planetengröße neu.
		 * @param int $size
		 * @return void
		*/

		function _setFields($size)
		{
			$this->setMainField("size", $size);
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
			$result = self::$sqlite->singleField("SELECT pid FROM planets WHERE NOT user ORDER BY RANDOM() LIMIT 1;");
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
			self::$sqlite->query("SELECT galaxy,system,planet FROM planets WHERE user = ".self::$sqlite->quote($user)." ORDER BY i ASC;");
			while(($r = self::$sqlite->nextResult()) !== false)
				$return[] = Planet::fromKoords($r["galaxy"], $r["system"], $r["planet"]);
			return $return;
		}

		/**
		 * Liest das Trümmerfeld des Planeten aus.
		 * @return array 0 => Carbon, 1 => Aluminium ...
		*/

		function getTruemmerfeld()
		{
			return array($this->getMainField("tf0"), $this->getMainField("tf1"), $this->getMainField("tf2"), $this->getMainField("tf3"));
		}

		/**
		 * Setzt das Trümmerfeld des Planeten neu.
		 * @param array $ress 0 => Carbon, 1 => Aluminium, 2 => Wolfram, 3 => Radium
		 * @return void
		*/

		function setTruemmerfeld($ress)
		{
			$this->setMainField("tf0", $ress[0]);
			$this->setMainField("tf1", $ress[1]);
			$this->setMainField("tf2", $ress[2]);
			$this->setMainField("tf3", $ress[3]);
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
			$this_pos = $this->getPos();

			self::$sqlite->backgroundQuery("UPDATE planets SET user = NULL, name = NULL, size = size_original, ress0 = 0, ress1 = 0, ress2 = 0, ress3 = 0, ress4 = 0 WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem())." AND planet = ".self::$sqlite->quote($this->getPlanet()));
			self::$sqlite->backgroundQuery("DELETE FROM planets_items WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem())." AND planet = ".self::$sqlite->quote($this->getPlanet()));
			self::$sqlite->backgroundQuery("DELETE FROM planets_building WHERE galaxy = ".self::$sqlite->quote($this->getGalaxy())." AND system = ".self::$sqlite->quote($this->getSystem())." AND planet = ".self::$sqlite->quote($this->getPlanet()));
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

			$this->setMainField("user", $user_obj->getName());
			$this->setMainField("name", $user_obj->_("Kolonie"));

			if(count($this->getPlanetsByUser($user)) <= 0) $size = 375;
			else $size = $this->getSize(true);
			$size = floor($size*($user_obj->getItemLevel("F9", "forschung")/Item::getIngtechFactor()+1));
			$this->setMainField("size", $size);

			$index = $this->singleField("SELECT MAX(user_index) FROM planets WHERE user = ".$this->quote($user));
			if($index === false)
				$index = 0;
			else
				$index++;
			$this->setMainField("user_index", $index);

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
			$owner = $this->getMainField("user");
			if(!$owner)
				throw PlanetException("This planet is not colonised.");
			$current_index = $this->getMainField("user_index");
			$next_index = $this->singleLine("SELECT galaxy,system,planet,user_index FROM planets WHERE user_index < ".$this->quote($current_index)." ORDER BY user_index DESC LIMIT 1;");
			if(!$next_index)
				throw new UserException("This planet is on the top.");
			$this->backgroundQuery("UPDATE planets SET user_index = ".$this->quote($current_index)." WHERE galaxy = ".$this->quote($next_index["galaxy"])." AND system = ".$this->quote($next_index["system"])." AND planet = ".$this->quote($next_index["planet"]).";");
			$this->backgroundQuery("UPDATE planets SET user_index = ".$this->quote($next_index["index"])." WHERE ".$this->sqlCond().";");
		}

		/**
		 * Verändert die Reihenfolge der Planetenliste des Benutzers. Schiebt
		 * den Planeten mit dem Index $planet um eins nach unten.
		 * @return void
		 * @throw UserException Wenn der Planet bereits unten in der Liste steht.
		*/

		function moveDown()
		{
			$owner = $this->getMainField("user");
			if(!$owner)
				throw PlanetException("This planet is not colonised.");
			$current_index = $this->getMainField("user_index");
			$next_index = $this->singleLine("SELECT galaxy,system,planet,user_index FROM planets WHERE user_index > ".$this->quote($current_index)." ORDER BY user_index ASC LIMIT 1;");
			if(!$next_index)
				throw new UserException("This planet is on the bottom.");
			$this->backgroundQuery("UPDATE planets SET user_index = ".$this->quote($current_index)." WHERE galaxy = ".$this->quote($next_index["galaxy"])." AND system = ".$this->quote($next_index["system"])." AND planet = ".$this->quote($next_index["planet"]).";");
			$this->backgroundQuery("UPDATE planets SET user_index = ".$this->quote($next_index["index"])." WHERE ".$this->sqlCond().";");
		}

		/**
		 * Setzt oder liest den Namen des Planeten.
		 * @param string $name Der neue Name.
		 * @return void
		*/

		function setName($name)
		{
			$this->setMainField("name", $name);
		}

		/**
		 * Gibt die Rohstoffbestände auf dem aktiven Planeten zurück.
		 * @param bool $refresh Soll Planet->refreshRess() ausgeführt werden?
		 * @return array(int)
		*/

		function getRess($refresh=true)
		{
			if($refresh)
				$this->refreshRess();

			$ress = $this->getMainField(array("ress0", "ress1", "ress2", "ress3", "ress4"));

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
			$cur = $this->getMainField(array("ress0", "ress1", "ress2", "ress3", "ress4"));
			if(isset($ress[0])) $cur[0] += $ress[0];
			if(isset($ress[1])) $cur[1] += $ress[1];
			if(isset($ress[2])) $cur[2] += $ress[2];
			if(isset($ress[3])) $cur[3] += $ress[3];
			if(isset($ress[4])) $cur[4] += $ress[4];
			$this->setMainField(array("ress0", "ress1", "ress2", "ress3", "ress4"), $cur);
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
		 * @param int $time Bestand zu diesem Zeitpunkt statt zum aktuellen verwenden
		 * @throw UserException Die letzte Aktualisierung ist neuer als $time
		 * @return void
		*/

		protected function refreshRess($time=null)
		{
			$last_refresh = $this->getMainField("last_refresh");
			if($last_refresh >= $time)
				throw new PlanetException("Last refresh is in the future.");

			$prod = $this->getProduction($time !== false);
			$limit = $this->getProductionLimit($time !== false);
			$cur = $this->getRess($time !== false);

			$f = ($time-$last_refresh)/3600;

			for($i=0; $i<=4; $i++)
			{
				if($cur[$i] >= $limit[$i])
					continue;
				$cur[$i] += $prod[$i]*$f;
				if($cur[$i] > $limit[$i])
					$cur[$i] = $limit[$i];
			}

			$this->setMainField(array("ress0", "ress1", "ress2", "ress3", "ress4"), $cur);
			$this->setMainField("last_refresh", $time);
		}

		/**
		 * Gibt den eingestellten Produktionsfaktor eines Gebäudes zurück.
		 * @param string $gebaeude Die Item-ID.
		 * @return float
		*/

		function getProductionFactor($gebaeude)
		{
			$factor = $this->singleField("SELECT prod_factor FROM planets_items WHERE ".$this->sqlCond()." AND id = ".$this->quote($gebaeude)." LIMIT 1;");
			if(!$factor)
				$factor = 1;
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

			$this->backgroundQuery("UPDATE planets_items SET prod_factor = ".$this->quote($factor)." WHERE ".$this->sqlCond()." AND id = ".$this->quote($gebaeude).";");
		}

		/**
		 * Gibt zurück, wieviel pro Stunde produziert wird.
		 * @return array [ Carbon, Aluminium, Wolfram, Radium, Tritium, Energie, Unterproduktionsfaktor Energie, Energiemaximum erreicht? ]
		*/

		function getProduction()
		{
			$prod = array(0,0,0,0,0,0,0,false);
			$owner = $this->getOwner();
			if($owner)
			{
				$owner_obj = Classes::User($owner);
				if($owner_obj->permissionToAct())
				{
					$gebaeude = $user_obj->getItemsList("gebaeude");

					$energie_prod = 0;
					$energie_need = 0;
					foreach($gebaeude as $id)
					{
						$item = $user_obj->getItemInfo($id, "gebaeude", array("prod"), false, null, $this);
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
			return $prod;
		}

		/**
		 * Gibt die maximalen Produktionsmengen für Rohstoffe und Energie zurück.
		 * @return array(float)
		*/

		function getProductionLimit()
		{
			$this->_forceActivePlanet();

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

			$level = $this->singleField("SELECT level FROM planets_items WHERE = ".$this->sqlCond()." AND id = ".$this->quote($id)." AND type = ".$this->quote($type)." LIMIT 1;");
			if($level === false)
				$level = 0;
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
			$this->backgroundQuery("UPDATE planets_building SET start = start + ".$this->quote($seconds)." WHERE ".$this->sqlCond().";");
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
			self::$sqlite->backgroundQuery("UPDATE planets SET user = ".self::$sqlite->quote($new_name)." WHERE user = ".self::$sqlite->quote($old_name).";");
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
			$this->backgroundQuery("UPDATE planets SET size = CEIL(size*".$this->quote($factor).") WHERE user = ".$this->quote($user).";");
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
			if($this->singleField("SELECT COUNT(*) FROM planets_items WHERE ".$this->sqlCond()." AND id = ".$this->quote($id).";") > 0)
				$this->backgroundQuery("UPDATE planets_items SET level = level+".$this->quote($value)." WHERE ".$this->sqlCond()." AND id = ".$this->quote($id).";");
			else
				$this->backgroundQuery("INSERT INTO planets_items ( galaxy, system, planet, id, level ) VALUES ( ".$this->quote($this->getGalaxy()).", ".$this->quote($this->getSystem()).", ".$this->quote($this->getPlanet()).", ".$this->quote($id).", ".$this->quote($value)." );");

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
		}

		/**
		 * Gibt Informationen über die im Bau befindlichen Dinge auf diesem Planeten zurück.
		 * Das Rückgabe-Array hat bei Gebäuden und Forschung das folgende Format:
		 * [ Item-ID; Fertigstellungszeitpunkt; Globale Forschung?/Gebäuderückbau?; Verbrauchte Rohstoffe; Planetenindex der globalen Forschung ]
		 * Bei Robotern, Schiffen und Verteidigungsanlagen ist das Format wie folgt:
		 * ( [ Item-ID; Startzeit; Anzahl; Bauzeit pro Stück ] )
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return array
		 * @todo
		*/

		function checkBuildingThing($type)
		{
			$this->_forceActivePlanet();

			switch($type)
			{
				case "gebaeude": case "forschung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || trim($this->planet_info["building"][$type][0]) == "")
						return false;
					return $this->planet_info["building"][$type];
				case "roboter": case "schiffe": case "verteidigung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || count($this->planet_info["building"][$type]) <= 0)
						return array();
					return $this->planet_info["building"][$type];
				default: return false;
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
			$this->_forceActivePlanet();

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
			$this->_forceActivePlanet();

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
			$this->_forceActivePlanet();

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
			$this->_forceActivePlanet();

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
			$this->_forceActivePlanet();

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
			$this->_forceActivePlanet();

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