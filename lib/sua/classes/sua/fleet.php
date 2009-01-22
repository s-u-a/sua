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
	 * Repräsentiert eine Flotte im Spiel.
	 * @todo Fremdstationierung einbauen. Flotten, bei denen kein Ziel auf NOT finished steht, sind fremdstationiert. Muss auch bei callBack() noch korrigiert werden.
	*/

	class Fleet extends SQLiteSet
	{
		protected static $tables = array (
			"fleets" => array (
				"fleet_id TEXT PRIMARY KEY",
				"password TEXT"
			),
			"fleets_targets" => array (
				"i INTEGER NOT NULL",
				"fleet_id TEXT NOT NULL",
				"galaxy INTEGER NOT NULL",
				"system INTEGER NOT NULL",
				"planet INTEGER NOT NULL",
				"type INTEGER NOT NULL",
				"flying_back INTEGER DEFAULT 0",
				"arrival INTEGER NOT NULL",
				"finished INTEGER DEFAULT 0"
			),
		    "fleets_users" => array (
				"i INTEGER NOT NULL",
				"fleet_id TEXT NOT NULL",
				"user TEXT NOT NULL",
				"from_galaxy INTEGER NOT NULL",
				"from_system INTEGER NOT NULL",
				"from_planet INTEGER NOT NULL",
				"factor REAL DEFAULT 1",
				"ress0 INTEGER DEFAULT 0",
				"ress1 INTEGER DEFAULT 0",
				"ress2 INTEGER DEFAULT 0",
				"ress3 INTEGER DEFAULT 0",
				"ress4 INTEGER DEFAULT 0",
				"ress_tritium INTEGER DEFAULT 0",
				"hress0 INTEGER DEFAULT 0",
				"hress1 INTEGER DEFAULT 0",
				"hress2 INTEGER DEFAULT 0",
				"hress3 INTEGER DEFAULT 0",
				"hress4 INTEGER DEFAULT 0",
				"used_tritium INTEGER DEFAULT 0",
				"dont_put_ress INTEGER DEFAULT 0",
				"departing INTEGER DEFAULT 0"
			),
		    "fleets_users_rob" => array (
				"fleet_id TEXT NOT NULL",
				"user TEXT NOT NULL",
				"id TEXT NOT NULL",
				"number INTEGER DEFAULT 0",
				"scores INTEGER DEFAULT 0"
			),
		    "fleets_users_hrob" => array (
				"fleet_id TEXT NOT NULL",
				"user TEXT NOT NULL",
				"id TEXT NOT NULL",
				"number INTEGER DEFAULT 0",
				"scores INTEGER DEFAULT 0"
			),
		    "fleets_users_fleet" => array (
				"fleet_id TEXT NOT NULL",
				"user TEXT NOT NULL",
				"id TEXT NOT NULL",
				"number INTEGER DEFAULT 0",
				"scores INTEGER DEFAULT 0"
			)
		);

		/**
		 * Auftrag: steht noch nicht fest
		 * @var integer
		*/
		const TYPE_NULL = 0;

		/**
		 * Auftrag: Besiedeln
		 * @var integer
		*/
		const TYPE_BESIEDELN = 1;

		/**
		 * Auftrag: Sammeln
		 * @var integer
		*/
		const TYPE_SAMMELN = 2;

		/**
		 * Auftrag: Angriff
		 * @var integer
		*/
		const TYPE_ANGRIFF = 3;

		/**
		 * Auftrag: Transport
		 * @var integer
		*/
		const TYPE_TRANSPORT = 4;

		/**
		 * Auftrag: Spionage
		 * @var integer
		*/
		const TYPE_SPIONIEREN = 5;

		/**
		 * Auftrag: Stationieren
		 * @var integer
		*/
		const TYPE_STATIONIEREN = 6;

		/**
		 * Erzeugt ein Flottenobjekt. Implementiert Dataset::create().
		 * @return string ID des erzeugten Objekts
		*/

		static function create($name=null)
		{
			$name = self::datasetName($name);
			self::$sqlite->query("INSERT INTO fleets ( fleet_id ) VALUES ( ".self::$sqlite->quote($name)." );");
			return $name;
		}

		/**
		 * Entfernt ein Flottenobjekt aus der Datenbank. Implementiert Dataset::destroy().
		 * @return void
		*/

		function destroy()
		{
			foreach(array_keys(self::$tables) as $table)
				self::$sqlite->query("DELETE FROM ".$table." WHERE fleet_id = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Gibt eine Liste der Flotten-IDs zurück, die an einem Ziel angekommen sind, dessen Ankunft noch nicht
		 * abgearbeitet wurde.
		 * @return array(string)
		*/

		static function getArrivedFleets()
		{
			return self::$sqlite->singleColumn("SELECT DISTINCT fleet_id FROM fleets_targets WHERE arrival <= ".self::$sqlite->quote(time())." AND NOT finished ORDER BY arrival ASC;");
		}

		/**
		 * Verschiebt die Ankunft und Startzeit der Flotte im $time_diff Sekunden nach hinten.
		 * Dies sollte ausgeführt werden, wenn ein Benutzeraccount aus dem Urlaubsmodus genommmen wird und
		 * die Flotte wieder aufgetaut wird.
		 * @param int $time_diff Zeitdifferenz in Sekunden
		 * @return void
		*/

		function moveTime($time_diff)
		{
			self::$sqlite->query("UPDATE fleets_targets SET arrival = arrival + ".self::$sqlite->quote($time_diff)." WHERE fleet_id = ".self::$sqlite->quote($this->getName()).";");
			self::$sqlite->query("UPDATE fleets_users SET departing = departing + ".self::$sqlite->quote($time_diff)." WHERE fleet_id = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Gibt die Liste der Ziele zurück, die die Flotte anfliegt, in Reihenfolge.
		 * @return array(Planet)
		*/

		function getTargetsList()
		{
			self::$sqlite->query("SELECT galaxy, system, planet, i FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished ORDER BY i ASC;");
			$return = array();
			while(($r = self::$sqlite->nextResult()) !== false)
				$return[$r["i"]] = Planet::fromKoords($r["galaxy"], $r["system"], $r["planet"]);
			return $return;
		}

		/**
		 * Wie getTargetsList(), aber für die Ziele, die schon angeflogen wurden.
		 * @return array(string)
		*/

		function getOldTargetsList()
		{
			self::$sqlite->query("SELECT galaxy, system, planet FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND finished ORDER BY i ASC;");
			$return = array();
			while(($r = self::$sqlite->nextResult()) !== false)
				$return[$r["i"]] = Planet::fromKoords($r["galaxy"], $r["system"], $r["planet"]);
			return $return;
		}

		/**
		 * Liefert Informationen zu einem bestimmten Ziel der Flotte zurück.
		 * @param int $i Der Index des Ziels von getTargetsList() oder getOldTargetsList().
		 * @return array [ Auftragstyp (Fleet::TYPE_*), (boolean) Rückflug? (wird dann behandelt wie Stationieren) ]
		*/

		function getTargetType($i)
		{
			self::$sqlite->query("SELECT type, flying_back FROM fleets_target WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND i = ".self::$sqlite->quote($i).";");
			$r = self::$sqlite->nextResult();
			if($r === false)
				throw new FleetException("This target does not exist.");
			return array($r["type"], true && $r["flying_back"]);
		}

		/**
		 * Liefert ein Array nach dem folgenden Prinzip zurück:
		 * [ (string) Koordinaten => [ Fleet::TYPE_*, (boolean) Rückflug? ] ]
		 * Ist der Typ Sammeln, wird an die Koordinaten ein T angehängt.
		 * @return array
		 * @deprecated
		*/

		function getTargetsInformation()
		{
			$return = array();
			$this->query("SELECT galaxy || ':' || system || ':' || planet, type, flying_back FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished ORDER BY i ASC;");
			while(($r = $this->nextResult()) !== false)
				$return[$r["galaxy || ':' || system || ':' || planet"].($r["type"] == self::TYPE_SAMMELN && !$r["flying_back"] ? "T" : "")] = array($r["type"], true && $r["flying_back"]);
			return $return;
		}

		/**
		 * Wie getTargetsInformation(), aber für die Ziele, die schon angeflogen wurden.
		 * @return array
		 * @deprecated
		*/

		function getOldTargetsInformation()
		{
			$return = array();
			$this->query("SELECT galaxy || ':' || system || ':' || planet, type, flying_back FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND finished ORDER BY i ASC;");
			while(($r = $this->nextResult()) !== false)
				$return[$r["galaxy || ':' || system || ':' || planet"].($r["type"] == self::TYPE_SAMMELN ? "T" : "")] = array($r["type"], true && $r["flying_back"]);
			return $return;
		}

		/**
		 * Liefert zurück, wieviele „Flottenslots“ diese Flotte für den übergebenen Benutzer beansprucht.
		 * Die Zahl der Flottenslots wird durch das Kontrollwesen beeinflusst.
		 * @param string $user Der Benutzername oder null, wenn der Hauptbenutzer der Flotte benutzt werden soll.
		 * @return int
		*/

		function getNeededSlots($user=null)
		{
			if(!$this->status) return false;

			$users = $this->getUsersList();
			if(!isset($user))
				$user = $this->getFirstUser();
			elseif(!$this->isFirstUser($user))
				return 1;

			$slots = self::$sqlite->singleField("SELECT COUNT(*) FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT flying_back AND NOT finished;");
			if($slots < 1) $slots = 1;
			return $slots;
		}

		/**
		 * Fügt der Flotte ein weiteres Ziel hinzu.
		 * @param Planet $pos Die Zielkoordinaten.
		 * @param string $type Fleet::TYPE_*
		 * @param bool $back Ist das nur ein Rückflug? (= Stationieren, $type wird ignoriert)
		 * @return void
		*/

		function addTarget(Planet $pos, $type, $back)
		{
			$i = 1+self::$sqlite->singleField("SELECT i FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." ORDER BY i DESC LIMIT 1;");
			self::$sqlite->query("INSERT INTO fleets_targets ( i, fleet_id, galaxy, system, planet, type, flying_back ) VALUES ( ".self::$sqlite->quote($i).", ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($pos->getGalaxy()).", ".self::$sqlite->quote($pos->getSystem()).", ".self::$sqlite->quote($pos->getPlanet()).", ".self::$sqlite->quote($type).", ".self::$sqlite->quote($flying_back ? 1 : 0)." );");


		}

		/**
		 * Liefert zurück, ob Schiffe eines bestimmten Benutzers in der Flotte mitfliegen.
		 * @param string $user
		 * @return bool
		*/

		function userExists($user)
		{
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND username = ".self::$sqlite->quote($user).";") > 0);
		}

		/**
		 * Gibt die Auftragsart zurück, mit der die Flotte derzeit unterwegs ist.
		 * @return int Fleet::TYPE_*
		*/

		function getCurrentType()
		{
			return self::$sqlite->singleField("SELECT type FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished ORDER BY i ASC LIMIT 1;");
		}

		/**
		 * Gibt das nächste Ziel der Flotte zurück.
		 * @return Planet false, wenn kein weiteres Ziel existiert
		*/

		function getCurrentTarget()
		{
			self::$sqlite->query("SELECT galaxy, system, planet FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished ORDER BY i ASC LIMIT 1;");
			$r = self::$sqlite->nextResult();
			if($r === false)
				return false;
			return Planet::fromKoords($r["galaxy"], $r["system"], $r["planet"]);
		}

		/**
		 * Gibt das letzte Ziel der Flotte zurück.
		 * @param string $user Das letzte Ziel für diesen Benutzer, verschiedene Benutzer haben unterschiedliche Startkoordiaten.
		 * @return Planet
		*/

		function getLastTarget($user=null)
		{
			if(!isset($user)) $user = $this->getFirstUser();
			if($this->isFirstUser($user))
			{
				self::$sqlite->query("SELECT galaxy,system,planet FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND finished ORDER BY i DESC LIMIT 1;");
				$r = self::$sqlite->nextResult();
				if($r !== false)
					return Planet::fromKoords($r["galaxy"], $r["system"], $r["planet"]);
			}
			self::$sqlite->query("SELECT from_galaxy,from_system,from_planet FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user)." LIMIT 1;");
			$r = self::$sqlite->nextResult();
			if($r === false)
				throw new FleetException("This user does not participate on the fleet.");
			return Planet::fromKoords($r["galaxy"], $r["system"], $r["planet"]);
		}

		/**
		 * Gibt die Ankunftszeit beim nächsten Ziel zurück.
		 * @return int
		*/

		function getNextArrival()
		{
			if($this->started())
				return self::$sqlite->singleField("SELECT arrival FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished ORDER BY i DESC LIMIT 1;");
			else
				return time()+$this->calcTime($this->getFirstUser(), $this->getLastTarget(), $this->getCurrentTarget());
		}

		/**
		 * Gibt an, wann die Flotte am Ziel Nummer $i ankommt. $i ist der Index des Ziels aus getTargetsList().
		 * @param int $i
		 * @return int
		*/

		function getArrival($i)
		{
			return self::$sqlite->singleField("SELECT arrival FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND i = ".self::$sqlite->quote($i).";");
		}

		/**
		 * Gibt an, ob das aktuelle Ziel als Rückflug behandelt wird.
		 * @return bool
		*/

		function isFlyingBack()
		{
			return (true && self::$sqlite->singleField("SELECT flying_back FROM fleets_targets WHERE fleet_id = ".self::$sqite->quote($this->getName())." AND NOT finished ORDER BY i ASC LIMIT 1;"));
		}

		/**
		 * Fügt Schiffe zum Bestand des Benutzers $user der Flotte hinzu.
		 * @param string $id Die ID des Schiffs.
		 * @param int $count Die Anzahl der Schiffe des Typs.
		 * @param string $user
		 * @return void
		*/

		function addFleet($id, $count, $user)
		{
			$count = floor($count);
			if($count < 0)
				throw new InvalidArgumentException("Invalid number.");

			$user_obj = Classes::User($user);
			$item_info = $user_obj->getItemInfo($id, "schiffe", array("scores"));
			$scores = $item_info["scores"]*$count;
			$old_number = self::$sqlite->singleField("SELECT number FROM fleets_users_fleet WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user)." AND id = ".self::$sqlite->quote($id)." LIMIT 1;");
			if($old_number === false)
				self::$sqlite->query("INSERT INTO fleets_users_fleet ( fleet_id, user, id, number, scores ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($id).", ".self::$sqlite->quote($count).", ".self::$sqlite->quote($scores)." );");
			else
				self::$sqlite->query("UPDATE fleets_users_fleet SET number = number + ".self::$sqlite->quote($count).", scores = scores + ".self::$sqlite->quote($scores)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user)." AND id = ".self::$sqlite->quote($id).";");

			if(!$this->isFirstUser($user)) // Geschwindigkeitsfaktor anpassen
				self::$sqlite->query("UPDATE fleets_users SET factor = ".self::$sqlite->quote($this->calcTime($user, $this->from($user), $this->getCurrentTarget(), true, true)/($this->getNextArrival()-time()))." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
		}

		/**
		 * Liefert den Hauptbenutzer der Flotte zurück.
		 * @return string
		*/

		function getFirstUser()
		{
			return self::$sqlite->singleField("SELECT user FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." ORDER BY i ASC LIMIT 1;");
		}

		/**
		 * Gibt an, ob der übergebene Benutzer der Hauptbenutzer der Flotte ist.
		 * @return bool
		*/

		function isFirstUser($user)
		{
			return $user == $this->getFirstUser();
		}

		/**
		 * Fügt der Flotte einen Benutzer hinzu.
		 * @param string $user
		 * @param Planet $from
		 * @param float $factor
		 * @return void
		*/

		function addUser($user, Planet $from, $factor=1)
		{
			if($this->userExists($user))
				throw new FleetException("This user is already participating on the fleet.");

			if($factor <= 0) $factor = 0.01;

			if($this->getFirstUser() !== false)
				$factor = null;

			$i = 1+self::$sqlite->singleField("SELECT i FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." ORDER BY i DESC LIMIT 1;");
			self::$sqlite->query("INSERT INTO fleets_users ( i, fleet_id, user, from_galaxy, from_system, from_planet, factor ) VALUES ( ".self::$sqlite->quote($i).", ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($from->getGalaxy()).", ".self::$sqlite->quote($from->getSystem()).", ".self::$sqlite->quote($from->getPlanet()).", ".self::$sqlite->quote($factor)." );");
		}

		/**
		 * Gibt zurück, wieviele Rohstoffe und Roboter der Benutzer mit seinen derzeitigen
		 * Schiffen transportieren kann.
		 * @param string $user
		 * @return array [ 0 => (int) Rohstoffmenge; 1 => (int) Robotermenge ]
		*/

		function getTransportCapacity($user)
		{
			$trans = array(0, 0);
			$user_object = Classes::User($user);
			self::$sqlite->query("SELECT id,number FROM fleets_users_fleet WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			while(($r = self::$sqlite->nextResult()) !== false)
			{
				$item_info = $user_object->getItemInfo($r["id"], "schiffe", array("trans"));
				$trans[0] += $item_info["trans"][0]*$r["number"];
				$trans[1] += $item_info["trans"][1]*$r["number"];
			}
			return $trans;
		}

		/**
		 * Fügt der Flotte des Benutzers $user Transportgüter hinzu.
		 * @param string $user Der Benutzername
		 * @param array $ress Transportierte Rohstoffe
		 * @param array $robs Transportierte Roboter
		 * @return void
		*/

		function addTransport($user, $ress=false, $robs=false)
		{
			if(!$this->userExists($user)) throw new FleetException("This user does not participate on the fleet.");

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);
			$trans = $this->getTransport($user);
			$max_ress -= array_sum($trans[0]);
			$max_robs -= array_sum($trans[1]);
			if($ress)
			{
				$ress = Functions::fitToMax($ress, $max_ress);
				self::$sqlite->query("UPDATE fleets_users SET ress0 = ress0 + ".self::$sqlite->quote($ress[0]).", ress1 = ress1 + ".self::$sqlite->quote($ress[1]).", ress2 = ress2 + ".self::$sqlite->quote($ress[2]).", ress3 = ress3 + ".self::$sqlite->quote($ress[3]).", ress4 = ress4 + ".self::$sqlite->quote($ress[4])." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			}

			if($robs)
			{
				$user_obj = Classes::User($user);
				$robs = Functions::fitToMax($robs, $max_robs);
				foreach($robs as $i=>$rob)
				{
					$item_info = $user_obj->getItemInfo($i, "roboter", array("scores"));
					$scores = $item_info["scores"]*$rob;
					if(isset($trans[1][$i]))
						self::$sqlite->query("UPDATE fleets_users_rob SET number = number + ".self::$sqlite->quote($rob).", scores = scores + ".self::$sqlite->quote($scores)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
					else
						self::$sqlite->query("INSERT INTO fleets_users_rob ( fleet_id, user, id, number, scores ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($i).", ".self::$sqlite->quote($rob).", ".self::$sqlite->quote($scores).");");
				}
			}
		}

		/**
		 * Fügt der Flotte des Benutzers $user Handelsgüter hinzu.
		 * @param string $user Der Benutzername
		 * @param array $ress Transportierte Rohstoffe
		 * @param array $robs Transportierte Roboter
		 * @return void
		*/

		function addHandel($user, $ress=false, $robs=false)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);
			$trans = $this->getHandel($user);
			$max_ress -= array_sum($trans[0]);
			$max_robs -= array_sum($trans[1]);
			if($this->dontPutRes($user))
			{
				$transport = $this->getTransport($user);
				$max_ress -= array_sum($transport[0]);
				$max_robs -= array_sum($transport[1]);
			}

			if($ress)
			{
				$ress = Functions::fitToMax($ress, $max_ress);
				self::$sqlite->query("UPDATE fleets_users SET hress0 = hress0 + ".self::$sqlite->quote($ress[0]).", hress1 = hress1 + ".self::$sqlite->quote($ress[1]).", hress2 = hress2 + ".self::$sqlite->quote($ress[2]).", hress3 = hress3 + ".self::$sqlite->quote($ress[3]).", hress4 = hress4 + ".self::$sqlite->quote($ress[4])." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			}

			if($robs)
			{
				$robs = Functions::fitToMax($robs, $max_robs);
				$user_obj = Classes::User($user);
				foreach($robs as $i=>$rob)
				{
					$item_info = $user_obj->getItemInfo($i, "roboter", array("scores"));
					$scores = $item_info["scores"]*$rob;
					if(isset($trans[1][$i]))
						self::$sqlite->query("UPDATE fleets_users_hrob SET number = number + ".self::$sqlite->quote($rob).", scores = scores + ".self::$sqlite->quote($scores)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
					else
						self::$sqlite->query("INSERT INTO fleets_users_hrob ( fleet_id, user, id, number, scores ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($i).", ".self::$sqlite->quote($rob).", ".self::$sqlite->quote($scores)." );");
				}
			}
		}

		/**
		 * Setzt die Handelsgüter für den Benutzer $user neu.
		 * @param string $user Der Benutzername
		 * @param array $ress Transportierte Rohstoffe
		 * @param array $robs Transportierte Roboter
		 * @return void
		*/

		function setHandel($user, $ress=false, $robs=false)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);
			$trans = $this->getHandel($user);
			if($this->dontPutRes($user))
			{
				$transport = $this->getTransport($user);
				$max_ress -= array_sum($transport[0]);
				$max_robs -= array_sum($transport[1]);
			}

			if($ress)
			{
				$ress = Functions::fitToMax($ress, $max_ress);
				self::$sqlite->query("UPDATE fleets_users SET hress0 = ".self::$sqlite->quote($ress[0]).", hress1 = ".self::$sqlite->quote($ress[1]).", hress2 = ".self::$sqlite->quote($ress[2]).", hress3 = ".self::$sqlite->quote($ress[3]).", hress4 = ".self::$sqlite->quote($ress[4])." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			}

			if($robs)
			{
				$robs = Functions::fitToMax($robs, $max_robs);
				$user_obj = Classes::User($user);
				foreach($robs as $i=>$rob)
				{
					$item_info = $user_obj->getItemInfo($i, "roboter", array("scores"));
					$scores = $item_info["scores"]*$rob;
					if(isset($trans[1][$i]))
						self::$sqlite->query("UPDATE fleets_users_hrob SET number = ".self::$sqlite->quote($rob).", scores = ".self::$sqlite->quote($scores)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
					else
						self::$sqlite->query("INSERT INTO fleets_users_hrob ( fleet_id, user, id, number, scores ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($i).", ".self::$sqlite->quote($rob).", ".self::$sqlite->quote($scores)." );");
				}
			}
		}

		/**
		 * Gibt ein Array zurück, das die aktuelle Transportmenge des Benutzers $user enthält.
		 * @param string $user
		 * @return array [ Rohstoffe; Roboter ]
		*/

		function getTransport($user)
		{
			self::$sqlite->query("SELECT ress0,ress1,ress2,ress3,ress4 FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user)." LIMIT 1;");
			$r = self::$sqlite->nextResult();
			if($r === false)
				throw new FleetException("This user does not participate on the fleet.");
			$return = array(array($r["ress0"], $r["ress1"], $r["ress2"], $r["ress3"], $r["ress4"]), array());
			self::$sqlite->query("SELECT id,number FROM fleets_users_rob WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			while(($r = self::$sqlite->nextResult()) !== false)
			{
				if(!isset($return[1][$r["id"]])) $return[1][$r["id"]] = 0;
				$return[1][$r["id"]] += $r["number"];
			}
			return $return;
		}

		/**
		 * Gibt ein Array zurück, das die aktuelle Handelsmenge des Benutzers $user enthält.
		 * @param string $user
		 * @return array [ Rohstoffe; Roboter ]
		*/

		function getHandel($user)
		{
			self::$sqlite->query("SELECT hress0,hress1,hress2,hress3,hress4 FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user)." LIMIT 1;");
			$r = self::$sqlite->nextResult();
			if($r === false)
				throw new FleetException("This user does not participate on the fleet.");
			$return = array(array($r["hress0"], $r["hress1"], $r["hress2"], $r["hress3"], $r["hress4"]), array());
			self::$sqlite->query("SELECT id,number FROM fleets_users_hrob WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			while(($r = self::$sqlite->nextResult()) !== false)
			{
				if(!isset($return[1][$r["id"]])) $return[1][$r["id"]] = 0;
				$return[1][$r["id"]] += $r["number"];
			}
			return $return;
		}

		/**
		 * Liest oder setzt die Einstellung, ob der Transport die Rohstoffe abliefern soll oder zum nächsten Ziel mitnehmen.
		 * @param string $user
		 * @param bool|null $new_value
		 * @return void|bool Wenn $new_value nicht gestzt ist, wird der aktuelle Zustand zurückgeliefert.
		*/

		function dontPutRes($user, $new_value=null)
		{
			if(isset($new_value))
				self::$sqlite->query("UPDATE fleets_users SET dont_put_ress = ".self::$sqlite->quote($new_value ? 1 : 0)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			else
				return (true && self::$sqlite->singleField("SELECT dont_put_ress FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";"));
		}

		/**
		 * Berechnet, wieviel der Benutzer an Tritium mitnehmen muss.
		 * @param string $user
		 * @return int
		*/

		function calcNeededTritium($user)
		{
			if($this->isFirstUser($user))
			{
				if($this->started())
					throw new FleetException("The fleet has already been started.");
				$tritium = 0;
				$old_target = $this->from($user);
				foreach($this->getTargetsList() as $target)
				{
					$tritium += $this->getTritium($user, $old_target, $target);
					$old_target = $target;
				}
				if($old_target != $this->from($user))
					$tritium += $this->getTritium($user, $old_target, $this->from($user));
				return $tritium;
			}
			else
				return $this->getTritium($user, $this->from($user), $this->getCurrentTarget())*2;
		}

		/**
		 * Berechnet den Tritiumverbrauch für die aktuell mitfliegenden Schiffe von $user vom Planeten
		 * $from zum Planeten $to.
		 * @param string user
		 * @param Planet $from
		 * @param Planet $to
		 * @param bool $factor Soll der Geschwindigkeitsfaktor miteinberechnet werden?
		 * @return float
		*/

		function getTritium($user, Planet $from, Planet $to, $factor=true)
		{
			if(!$this->userExists($user))
				throw new FleetException("This user does not participate on the fleet.");

			$mass = 0;
			$user_obj = Classes::User($user);
			foreach($this->getFleetsList($user) as $id=>$count)
			{
				$item_info = $user_obj->getItemInfo($id, "schiffe", array("mass"));
				$mass += $item_info["mass"]*$count;
			}

			$add_factor = 1;
			if($factor) $add_factor = $this->factor($user);
			try { $add_factor *= Config::getLibConfig()->getConfigValueE("users", "global_factors", "costs"); }
			catch(ConfigException $e) { }

			return $add_factor*self::getDistance($from, $to)*$mass/1000000;
		}

		/**
		 * Gibt die Flugerfahrungspunkte zurück, die der Benutzer mit seinen mitfliegenden Schiffen vom
		 * Planeten $from zum Planeten $to sammelt.
		 * @param string $user
		 * @param Planet $from
		 * @param Planet $to
		 * @return float
		*/

		function getScores($user, Planet $from, Planet $to)
		{
			return $this->getTritium($user, $from, $to)/1000;
		}

		/**
		 * Berechnet die Flugzeit für die Schiffe des Benutzers $user vom Planeten $from zum Planeten $to.
		 * @param string $user
		 * @param Planet $from
		 * @param Planet $to
		 * @param bool $use_min_time Soll die globale Mindestbauzeit für die Flugzeit angewandt werden?
		 * @param bool $ignore_factor Soll der Geschwindigkeitsfaktor, der vom Benutzer eingestellt wurde, ignoriert werden?
		 * @return int
		*/

		function calcTime($user, Planet $from, Planet $to, $use_min_time=true, $ignore_factor=false)
		{
			if(!$this->userExists($user))
				throw new FleetException("This user does not participate on the fleet.");

			$fleets = $this->getFleetsList($user);
			if(array_sum($fleets) <= 0)
				throw new FleetException("This user has not added any fleet yet.");

			$return = self::calcFleetTime($user, $from, $to, $fleets, $use_min_time);
			if(!$ignore_factor)
				$return /= $this->factor($user);
			return $return;
		}

		/**
		 * Berechnet die Flugzeit der Flotten $fleet vom Planeten $from nach $to für den Benutzer $user
		 * @param string $user Der Benutzername muss übergeben werden, damit Dinge wie Antriebsforschungen einberechnet werden können
		 * @param Planet $from
		 * @param Planet $to
		 * @param array $fleet Array mit den Flotten ( ID => Anzahl )
		 * @param bool $use_min_time Die globale Mindestbauzeit einberechnen?
		 * @return int
		*/

		static function calcFleetTime($user, Planet $from, Planet $to, array $fleet, $use_min_time=true)
		{
			$speeds = array();
			$user_obj = Classes::User($user);
			foreach($fleet as $id=>$count)
			{
				$item_info = $user_obj->getItemInfo($id, "schiffe", array("speed"));
				$speeds[] = $item_info["speed"];
			}
			$speed = min($speeds)/1000000;

			$time = sqrt(self::getDistance($from, $to)/$speed)*2;

			try { $time *= Config::getLibConfig()->getConfigValueE("users", "global_factors", "time"); }
			catch(ConfigException $e) { }

			if($use_min_time)
			{
				$min_time = Config::getLibConfig()->getConfigValue("users", "min_building_time");
				if($time < $min_time)
					$time = $min_time;
			}

			return $time;
		}

		/**
		 * Setzt oder liest den Geschwindigkeitsfaktor des angegebenen Benutzers.
		 * @param string $user
		 * @param real|null $factor Der neue Geschwindigkeitsfaktor.
		 * @return void|real Wenn $factor null ist, wird der aktuelle Faktor zurückgegeben.
		*/

		function factor($user, $factor=null)
		{
			if($this->started())
				throw new FleetException("The fleet has already departed.");
			if(!$this->userExists($user))
				throw new FleetException("This user does not participate on the fleet.");

			if(isset($factor))
				self::$sqlite->query("UPDATE fleets_users SET factor = ".self::$sqlite->quote($factor)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			else
				return self::$sqlite->singleField("SELECT factor FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
		}

		/**
		 * Gibt die Schiffe zurück, die vom Benutzer $user in der Flotte mitfliegen.
		 * @param string $user
		 * @return array (Item-ID => Anzahl)
		*/

		function getFleetList($user)
		{
			$return = array();
			self::$sqlite->query("SELECT id,number FROM fleets_users_fleet WHERE fleet_id = ".self::$sqlite->quote($this->getName()).";");
			while(($r = self::$sqlite->nextResult()) !== false)
			{
				if(!isset($return[$r["id"]])) $return[$r["id"]] = 0;
				$return[$r["id"]] += $r["number"];
			}
			return $return;
		}

		/**
		 * Gibt die Liste der Benutzer zurück, die Schiffe in der Flotte mitfliegen lassen.
		 * @return array
		*/

		function getUsersList()
		{
			return self::$sqlite->singleColumn("SELECT DISTINCT user FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Gibt den Startplaneten des angegebenen Benutzers zurück.
		 * @return Planet
		*/

		function from($user)
		{
			self::$sqlite->query("SELECT from_galaxy,from_system,from_planet FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			$r = self::$sqlite->nextResult();
			if($r === false)
				throw new FleetException("This user does not participate on the fleet.");
			return Planet::fromKoords($r["from_galaxy"], $r["from_system"], $r["from_planet"]);
		}

		/**
		 * Gibt zurück, ob die Flotte das Ziel $target anfliegt.
		 * @param Planet $target
		 * @return bool
		*/

		function isATarget(Planet $target)
		{
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND galaxy = ".self::$sqlite->quote($target->getGalaxy())." AND system = ".self::$sqlite->quote($target->getSystem())." AND planet = ".self::$sqlite->quote($target->getPlanet()).";") > 0);
		}

		/**
		 * Benennt den Benutzer in der Flotte um.
		 * @param string $old_name
		 * @param string $new_name
		 * @return void
		*/

		function renameUser($old_name, $new_name)
		{
			if(!$this->userExists($old_name))
				throw new FleetException("This user does not participate on the fleet.");
			self::$sqlite->query("UPDATE fleets_users SET user = ".self::$sqlite->quote($new_name)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($old_name).";");
		}

		/**
		 * Startet die Flotte.
		 * @return void
		*/

		function start()
		{
			if($this->started())
				throw new FleetException("The fleet has already been started.");
			if(count($this->getTargetsList()) == 0)
				throw new FleetException("No targets have been added.");
			if(count($this->getUsersList()) == 0)
				throw new FleetException("No users have joined the fleet.");
			if(array_sum($this->getFleetsList($this->getFirstUser())) <= 0)
				throw new FleetException("The first user has not added any fleet.");

			# Geschwindigkeitsfaktoren der anderen Teilnehmer abstimmen
			$time = $this->getNextArrival()-time();
			$users = $this->getUsersList();
			array_shift($users);
			if(count($users) > 1)
			{
				foreach($users as $user)
					$this->factor($user, $this->calcTime($user, $this->from($user), $koords)/$time);
			}

			self::$sqlite->query("UPDATE fleets_users SET departing = ".self::$sqlite->quote(time())." WHERE fleet_id = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Liefert zurück, ob die Flotte bereits fliegt.
		 * @return bool
		*/

		function started()
		{
			return (true && self::$sqlite->singleField("SELECT departing FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($this->getFirstUser()).";"));
		}

		/**
		 * Liefert zurück, wann die Schiffe des Benutzers $user losgeflogen sind.
		 * @param string|null $user Wenn null, wird der erste Benutzer benutzt.
		 * @return int
		*/

		function getStartTime($user = null)
		{
			if(!isset($user)) $user = $this->getFirstUser();
			return self::$sqlite->singleField("SELECT departing FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
		}

		/**
		 * Berechnet die Distanz zwischen den Planeten $start und $target.
		 * @param Planet $start
		 * @param Planet $target
		 * @return int
		*/

		static function getDistance(Planet $start, Planet $target)
		{
			# Entfernung berechnen
			if($start->getGalaxy() == $target->getGalaxy())
			{
				if($start->getSystem() == $target->getSystem())
				{
					if($start->getPlanet() == $target->getPlanet())
						$distance = 0.001;
					else
						$distance = 0.1*Functions::diff($start->getPlanet(), $target->getPlanet());
				}
				else
				{
					$this_x_value = $start->getSystem()-($start->getSystem()%100);
					$this_y_value = $start->getSystem()-$this_x_value;
					$this_y_value -= $this_y_value%10;
					$this_z_value = $start->getSystem()-$this_x_value-$this_y_value;
					$this_x_value /= 100;
					$this_y_value /= 10;

					$that_x_value = $target->getSystem()-($that_pos[1]%100);
					$that_y_value = $target->getSystem()-$that_x_value;
					$that_y_value -= $that_y_value%10;
					$that_z_value = $target->getSystem()-$that_x_value-$that_y_value;
					$that_x_value /= 100;
					$that_y_value /= 10;

					$x_diff = Functions::diff($this_x_value, $that_x_value);
					$y_diff = Functions::diff($this_y_value, $that_y_value);
					$z_diff = Functions::diff($this_z_value, $that_z_value);

					$distance = sqrt(pow($x_diff, 2)+pow($y_diff, 2)+pow($z_diff, 2));
				}
			}
			else
			{
				$galaxy_count = Galaxy::getNumber();

				$galaxy_diff_1 = Functions::diff($start->getGalaxy(), $target->getGalaxy());
				$galaxy_diff_2 = Functions::diff($start->getGalaxy()+$galaxy_count, $target->getGalaxy());
				$galaxy_diff_3 = Functions::diff($start->getGalaxy(), $target->getGalaxy()+$galaxy_count);
				$galaxy_diff = min($galaxy_diff_1, $galaxy_diff_2, $galaxy_diff_3);

				$radius = (30*$galaxy_count)/(2*pi());
				$distance = sqrt(2*pow($radius, 2)-2*$radius*$radius*cos(($galaxy_diff/$galaxy_count)*2*pi()));
			}

			$distance = round($distance*1000);
			return $distance;
		}

		/**
		  * Liefert ein Array mit den Benutzern zurueck, die die Flotte sehen koennen. Das umfasst die Benutzer,
		  * die selbst Schiffe in der Flotte mitfliegen lassen, und Benutzer, zu deren Planeten die Flotte
		  * fliegt (außer bei Sammeln).
		  * @return array(string)
		*/

		function getVisibleUsers()
		{
			$users = $this->getUsersList();
			foreach($this->getTargetsList() as $i=>$target)
			{
				$type = $this->getTargetType($i);
				if($type[0] == self::TYPE_SAMMELN && !$type[1])
					continue;
				$owner = $target->getOwner();
				if($owner && !in_array($owner, $users))
					$users[] = $owner;
			}
			return $users;
		}

		/**
		 * Gibt ein Flotten-Array mit Flotten-IDs zurück, die der Benutzer $user sehen darf.
		 * @param string $user
		 * @return array
		*/

		static function visibleToUser($user)
		{
			$planets_query = array();
			foreach(Planet::getPlanetsByUser($user) as $planet)
				$planets_query[] = "( galaxy = ".self::$sqlite->quote($planet->getGalaxy())." AND system = ".self::$sqlite->quote($planet->getSystem())." AND planet = ".self::$sqlite->quote($planet->getPlanet())." )";

			return self::$sqlite->singleColumn("SELECT DISTINCT fleet_id FROM ( SELECT fleet_id FROM fleets_users WHERE user = ".self::$sqlite->quote($user)." UNION ALL SELECT fleet_id FROM fleets_targets WHERE type != ".self::$sqlite->quote(self::TYPE_SAMMELN)." AND ( ".implode(" OR ", $planets_query)." ) );");
		}

		/**
		 * Gibt zurück, wieviele Flotten des Benutzers $user noch zu einem fremden Planeten unterwegs ist.
		 * Nützlich, um festzustellen, ob der Urlaubsmodus möglich ist.
		 * @param string $user
		 * @return int
		*/

		static function ownFleetsToForeignPlanet($user)
		{
			$planets_query = array();
			foreach(Planet::getPlanetsByUser($user) as $planet)
				$planets_query[] = "( galaxy = ".self::$sqlite->quote($planet->getGalaxy())." AND system = ".self::$sqlite->quote($planet->getSystem())." AND planet = ".self::$sqlite->quote($planet->getPlanet())." )";

			return self::$sqlite->singleField("SELECT COUNT(DISTINCT fleet_id) FROM ( SELECT fleet_id FROM ( SELECT DISTINCT fleet_id FROM fleets_targets EXCEPT ( SELECT fleet_id FROM fleets_targets WHERE ".implode(" OR ", $planets_query)." ) ) NATURAL JOIN ( SELECT fleet_id, user FROM fleets_users ) ) WHERE user != ".self::$sqlite->query($user).";");
		}

		/**
		 * Gibt zurück, welche Flotten des Benutzers $user oder anderer Benutzer sich auf dem Weg zum
		 * Planeten $planet befinden.
		 * @param string $user
		 * @param Planet $planet
		 * @param bool $not_match Nur Flotten zurückgeben, bei denen $user _nicht_ mitfliegt
		 * @return array
		*/

		static function userFleetsToPlanet($user, Planet $planet, $not_match=false)
		{
			$query = "";
			if($not_match)
				$query .= "SELECT fleet_id FROM ( SELECT DISTINCT fleet_id FROM fleets_users EXCEPT ";
			$query .= "SELECT DISTINCT fleet_id FROM fleets_users WHERE user = ".self::$sqlite->quote($user)." ";
			if($not_match)
				$query .= ") ";
			$query .= "INTERSECT SELECT DISTINCT fleet_id FROM fleets_targets WHERE galaxy = ".self::$sqlite->quote($planet->getGalaxy())." AND system = ".self::$sqlite->quote($planet->getSystem())." AND planet = ".self::$sqlite->quote($planet->getPlanet())." AND NOT finished;";
			return self::$sqlite->singleColumn($query);
		}

		/**
		 * Gibt zurück, wieviele Slots die Flotten des Benutzers $user belegen.
		 * @param string $user
		 * @return int
		*/

		static function userSlots($user)
		{
			$fleets = 0;
			foreach(self::visibleToUser($user) as $flotte)
			{
				$fl = Classes::Fleet($flotte);
				if(!$fl->userExists($user))
					continue;

				$fleets += $fl->getNeededSlots($user);
			}

			return $fleets;
		}

		/**
		 * Gibt alle Flotten zurück, die gerade auf einem bestimmten Planeten fremdstationiert sind.
		 * @param Planet $planet
		 * @return array
		*/

		static function getFleetsPositionedOnPlanet(Planet $planet)
		{
			return self::$sqlite->singleColumn("SELECT fleet_id FROM ( SELECT fleet_id,galaxy,system,planet,finished FROM fleets_targets ORDER BY i DESC GROUP BY fleet_id ) WHERE galaxy = ".self::$sqlite->quote($planet->getGalaxy())." AND system = ".self::$sqlite->quote($planet->getSystem())." AND planet = ".self::$sqlite->quote($planet->getPlanet())." AND finished");
		}

		/**
		 * Ein Planet wurde aufgelöst. Das Ziel wird bei allen Flotten entfernt. Diejenigen, bei denen das
		 * Ziel als Stationieren eingetragen war, erhalten den Rückflug stattdessen als Ziel.
		 * Fremdstationierte Flotten, die diesen Planeten als Startplaneten haben, werden entfernt.
		 * @param Planet $planet
		 * @return void
		*/

		static function planetRemoved(Planet $planet)
		{
			// Stationierungen auf diesen Planeten
			self::$sqlite->query("SELECT DISTINCT fleet_id,arrival FROM fleets_targets WHERE galaxy = ".self::$sqlite->quote($planet->getGalaxy())." AND system = ".self::$sqlite->quote($planet->getSystem())." AND planet = ".self::$sqlite->quote($planet->getPlanet())." AND NOT finished AND ( type = ".self::TYPE_STATIONIEREN." OR flying_back = 1 );");
			while(($r = self::$sqlite->nextResult()) !== false)
			{
				$fleet = Classes::Fleet($r["fleet_id"]);
				$from = $fleet->from($fleet->getFirstUser());
				if($from->equals($planet))
					$fleet->destroy();
			}
		}

		/**
		 * Liest oder setzt das Verbundflotten-Passwort dieser Flotte.
		 * @param string $new_password
		 * @return string|null
		*/

		function password($new_password=null)
		{
			if(!isset($new_password))
				return $this->getMainField("password");
			else
				$this->setMainField("password", $new_password);
		}

		/**
		 * Ruft die Flotten des Benutzers $user aus der Flotte zurück. Ist dies der letzte Benutzer, wird
		 * die Flotte entfernt. Es wird eine neue Flotte erzeugt, mit der die Schiffe zurückfliegen.
		 * @param string $user
		 * @param bool $immediately Wenn true, fliegt die Flotte nicht zurück, sondern landet sofort wieder auf dem Ausgangsplaneten.
		 * @return void
		*/

		function callBack($user, $immediately=false)
		{
			if(!$this->started())
				throw new FleetException("The fleet has not departed yet.");
			if($this->isFlyingBack())
				throw new FleetException("The fleet is already flying back.");
			if(!$this->userExists($user))
				throw new FleetException("This user does not participate on the fleet.");

			$start = $this->from($user);
			$from = $this->getLastTarget($user);
			$to = $this->getCurrentTarget();

			$is_first_user = $this->isFirstUser($user);
			$visible_users = $this->getVisibleUsers();

			if($to->equals($start))
				throw new FleetException("The fleet is already flying back.");

			if($from->equals($start)) $time1 = 0;
			else $time1 = $this->calcTime($user, $from, $start);
			$time2 = $this->calcTime($user, $to, $start);
			$time3 = $this->calcTime($user, $from, $to);
			$progress = (time()-$this->getStartTime())/$time3;
			if($progress > 1) $progress = 1;

			/* Dreieck ABC:
			A: $start
			B: $to
			C: $from
			AB: $time2
			BC: $time3
			AC: $time1
			D teilt CB in die Anteile $progress und 1-$progress
			Gesucht: AD: $back_time */

			$time1_sq = pow($time1, 2);
			$time3_sq = pow($time3, 2);
			$back_time = round(sqrt($time1_sq - $progress*$time1_sq + $progress*pow($time2,2) - $progress*$time3_sq + pow($progress,2)*$time3_sq));

			$tritium3 = $this->getTritium($user, $from, $to);
			$back_tritium = $tritium3*(1-$progress);
			$targets = $this->getTargetsList();
			$prev = array_shift($targets);
			foreach($targets as $target)
			{
				$back_tritium += $this->getTritium($user, $prev, $target);
				$prev = $target;
			}
			if($from->equals($start)) $tritium1 = 0;
			else $tritium1 = $this->getTritium($user, $from, $start);
			$tritium2 = $this->getTritium($user, $to, $start);

			$tritium1_sq = pow($tritium1, 2);
			$tritium3_sq = pow($tritium3, 2);
			$needed_back_tritium = round(sqrt($tritium1_sq - $progress*$tritium1_sq + $progress*pow($tritium2,2) - $progress*$tritium3_sq + pow($progress,2)*$tritium3_sq));
			$back_tritium -= $needed_back_tritium;

			# Mit Erfahrungspunkten herumhantieren
			# Tritium, das für den Rückflug verbraucht wird, wird zum benutzten Tritium hinzugezählt
			# Das Tritium vom aktuellen Ziel zum Ausgangsplaneten wird bei der Ankunft zum benutzten Tritium hinzugezaehlt und deswegen jetzt abgezogen
			self::$sqlite->query("UPDATE fleets_users SET used_tritium = used_tritium + ".self::$sqlite->quote($needed_back_tritium-$tritium2).", ress_tritium = ".self::$sqlite->quote($back_tritium)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");

			# Eventuellen Handel zurueckerstatten
			$handel = $this->getHandel($user);
			if(array_sum($handel[0]) > 0 || array_sum($handel[1]) > 0)
			{
				$target_owner = $to->getOwner();
				$target_user_obj = Classes::User($target_owner);
				$target_user_obj->setActivePlanet($target_user_obj->getPlanetByPos($to));
				$target_user_obj->addRess($handel[0]);
				foreach($handel[1] as $id=>$count)
					$target_user_obj->changeItemLevel($id, $count, "roboter");
				$this->setHandel($user, array(0,0,0,0,0), array());
			}

			// Rückflugflotte erstellen
			$new_fleet = self::create();
			// Benutzer von der einen Flotte in die andere verschieben
			self::$sqlite->query("UPDATE fleets_users SET fleet_id = ".self::$sqlite->quote($new_fleet).", i = 1 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			self::$sqlite->query("UPDATE fleets_users_rob SET fleet_id = ".self::$sqlite->quote($new_fleet).", i = 1 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
			self::$sqlite->query("UPDATE fleets_users_fleet SET fleet_id = ".self::$sqlite->quote($new_fleet).", i = 1 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");

			// Alte Ziele kopieren, wenn der Benutzer sie angeflogen hat
			if($is_first_user)
				self::$sqlite->query("INSERT INTO fleets_targets ( i, fleet_id, galaxy, system, planet, type, flying_back, arrival, finished SELECT i, ".self::$sqlite->quote($new_fleet).", galaxy, system, planet, type, flying_back, arrival, finished FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished;");

			// Neues Ziel hinzufügen
			$i = self::$sqlite->singleField("SELECT i FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($new_fleet)." ORDER BY i DESC LIMIT 1;");
			if($i === false)
				$i = 1;
			self::$sqlite->query("INSERT INTO fleets_targets ( i, fleet_id, galaxy, system, planet, type, flying_back, arrival, finished ) VALUES ( ".self::$sqlite->quote($i).", ".self::$sqlite->quote($new_fleet).", ".self::$sqlite->quote($start->getGalaxy()).", ".self::$sqlite->quote($start->getSystem()).", ".self::$sqlite->quote($start->getPlanet()).", ".self::$sqlite->quote($this->getCurrentType()).", 1, ".self::$sqlite->quote(time()+$back_time).", 0 );");

			if(count($this->getUsersList()) < 1)
			{
				# Aus der Datenbank entfernen
				$this->destroy();
			}
			elseif(!$is_first_user)
			{
				# Weitere Ziele entfernen, da diese zu diesem Benutzer gehoert haben
				$min_i = self::$sqlite->singleField("SELECT i FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." ORDER BY i ASC LIMIT 1;");
				self::$sqlite->query("DELETE FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND i > ".self::$sqlite->quote($min_i).";");

				# Letztes Ziel muss stationieren, Ursprungsplaneten des Benutzers hinzufuegen
				if($this->getCurrentType() != self::TYPE_STATIONIEREN)
					$this->addTarget($this->getLastTarget($this->getFirstUser()), $this->getCurrentType(), true);
			}
		}

		/**
		 * Soll vom Eventhandler ausgeführt werden. Kümmert sich um die Ankunft am nächsten Ziel der Flotte
		 * und führt dort den Auftrag durch. Schiebt das Ziel zu den bearbeiteten Zielen.
		 * @return void
		*/

		function arriveAtNextTarget()
		{
			global $types_message_types;

			$target = $this->getCurrentTarget();
			$first_user = $this->getFirstUser();

			$type = $this->getCurrentType();
			$back = $this->isFlyingBack();

			$besiedeln = false;
			if($type == self::TYPE_BESIEDELN && !$back)
			{
				# Besiedeln
				$target_owner = $target->getOwner();

				if($target_owner)
				{
					# Planet ist bereits besiedelt
					$target_owner_obj = Classes::User($target_owner);
					$message = Classes::Message(Message::create());
					$message->text(sprintf($target_owner_obj->_("Ihre Flotte erreicht den Planeten %s und will mit der Besiedelung anfangen. Jedoch ist der Planet bereits vom Spieler %s besetzt, und Ihre Flotte macht sich auf den Rückweg."), vsprintf($target_owner_obj->_("%d:%d:%d"), explode(":", $next_target_nt)), $target_owner));
					$message->subject(sprintf($target_owner_obj->_("Besiedelung von %s fehlgeschlagen"), vsprintf($target_owner_obj->_("%d:%d:%d"), explode(":", $next_target_nt))));
					$message->addUser($first_user, Message::TYPE_BESIEDELUNG);
				}
				else
				{
					$start_user = Classes::User($first_user);
					if(!$start_user->checkPlanetCount())
					{
						# Planetenlimit erreicht
						$message = Classes::Message(Message::create());
						$message->subject(sprintf($start_user->_("Besiedelung von %s fehlgeschlagen"), vsprintf($start_user->_("%d:%d:%d"), explode(":", $next_target_nt))));
						$message->text(sprintf($start_user->_("Ihre Flotte erreicht den Planeten %s und will mit der Besiedelung anfangen. Als Sie jedoch Ihren Zentralcomputer um Bestätigung für die Besiedelung bittet, kommt dieser durcheinander, da Sie schon so viele Planeten haben und er nicht so viele gleichzeitig kontrollieren kann, und schickt in Panik Ihrer Flotte das Signal zum Rückflug."), vsprintf($start_user->_("%d:%d:%d"), explode(":", $next_target_nt))));
						$message->addUser($first_user, Message::TYPE_BESIEDELUNG);
					}
					else $besiedeln = true;
				}
			}

			if($type != self::TYPE_STATIONIEREN && !$back && !$besiedeln)
			{
				# Nicht stationieren: Flotte fliegt weiter

				$further = true;

				$target_owner = $target->getOwner();
				if($target_owner)
				{
					$target_user = Classes::User($target_owner);
					$target_user->setActivePlanet($target_user->getPlanetByPos($target));
				}
				else $target_user = false;

				if(($type == self::TYPE_ANGRIFF || $type == self::TYPE_TRANSPORT) && !$target_owner)
				{
					# Angriff und Transport nur bei besiedelten Planeten
					# moeglich.

					foreach($this->getUsersList() as $username)
					{
						$username_obj = Classes::User($username);
						$message_obj = Classes::Message(Message::create());
						$message_obj->subject(sprintf($username_obj->_("%s unbesiedelt"), vsprintf($username_obj->_("%d:%d:%d"), explode(":", $next_target_nt))));
						$message_obj->text(sprintf($username_obj->_("Ihre Flotte erreicht den Planeten %s und will ihren Auftrag ausführen. Jedoch wurde der Planet zwischenzeitlich verlassen und Ihre Flotte macht sich auf den weiteren Weg."), vsprintf($username_obj->_("%d:%d:%d"), explode(":", $next_target_nt))));
						$message_obj->addUser($username, $types_message_types[$type]);
					}
				}
				else
				{
					switch($type)
					{
						case self::TYPE_SAMMELN:
						{
							$ress_max = $target->getTruemmerfeld();
							$ress_max_total = array_sum($ress_max);

							# Transportkapazitaeten
							$trans = array();
							$trans_total = 0;
							foreach($this->getUsersList() as $username)
							{
								list($this_trans_used) = $this->getTransport($username);
								$this_trans_tf = 0;
								$this_trans_total = 0;

								$this_user = Classes::User($username);
								foreach($this->getFleetsList() as $id=>$count)
								{
									$item_info = $this_user->getItemInfo($id, "schiffe", array("trans", "types"));
									$this_trans = $item_info["trans"][0]*$count;
									$this_trans_total += $this_trans;
									if(in_array(self::TYPE_SAMMELN, $item_info["types"]))
										$this_trans_tf += $this_trans;
								}

								$this_trans_free = $this_trans_total-$this_trans_used;
								if($this_trans_free < $this_trans_tf)
									$this_trans_tf = $this_trans_free;
								$trans[$username] = $this_trans_tf;
							}
							$trans_total = array_sum($trans);

							if($trans_total < $ress_max_total)
							{
								$f = $trans_total/$ress_max_total;
								$ress_max[0] = floor($ress_max[0]*$f);
								$ress_max[1] = floor($ress_max[1]*$f);
								$ress_max[2] = floor($ress_max[2]*$f);
								$ress_max[3] = floor($ress_max[3]*$f);
								$ress_max_total = array_sum($ress_max);
								$diff = $trans_total-$ress_max_total;
								$diff2 = $diff%4;
								$each = $diff-$diff2;
								$ress_max[0] += $each;
								$ress_max[1] += $each;
								$ress_max[2] += $each;
								$ress_max[3] += $each;
								switch($diff)
								{
									case 3: $ress_max[2]++;
									case 2: $ress_max[1]++;
									case 1: $ress_max[0]++;
								}
							}

							$got_ress = array();

							foreach($trans as $user=>$cap)
							{
								$rtrans = array();
								$p = $cap/$trans_total;
								$rtrans[0] = floor($ress_max[0]*$p);
								$rtrans[1] = floor($ress_max[1]*$p);
								$rtrans[2] = floor($ress_max[2]*$p);
								$rtrans[3] = floor($ress_max[3]*$p);

								self::$sqlite->query("UPDATE fleets_users SET ress0 = ress0 + ".self::$sqlite->quote($rtrans[0]).", ress1 = ress1 + ".self::$sqlite->quote($rtrans[1]).", ress2 = ress2 + ".self::$sqlite->quote($rtrans[2]).", ress3 = ress3 + ".self::$sqlite->quote($rtrans[3])." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");

								$got_ress[$username] = $rtrans;
							}

							# Aus dem Truemmerfeld abziehen
							$target->subTruemmerfeld($ress_max[0], $ress_max[1], $ress_max[2], $ress_max[3]);

							$tr_verbl = $target->getTruemmerfeld();

							# Nachrichten versenden
							foreach($got_ress as $username=>$rtrans)
							{
								$user_obj = Classes::User($username);
								$message = Classes::Message(Message::create());
								$message->subject(sprintf($user_obj->_("Abbau auf %s"), $next_target_nt));
								$message->text(sprintf("<div class=\"nachricht-sammeln\">\n\t<p>".sprintf(h($user_obj->_("Ihre Flotte erreicht das Trümmerfeld auf %s und belädt die %s Tonnen Sammlerkapazität mit folgenden Rohstoffen: %s.")), htmlspecialchars($next_target_nt), F::ths($trans_total), h(sprintf($user_obj->_("%1\$s %5\$s, %2\$s %6\$s, %3\$s %7\$s und %4s %8\$s"), F::ths($rtrans[0]), F::ths($rtrans[1]), F::ths($rtrans[2]), F::ths($rtrans[3]), $user_obj->_("[ress_0]"), $user_obj->_("[ress_1]"), $user_obj->_("[ress_2]"), $user_obj->_("[ress_3]"))))."</p>\n\t<h3 class=\"strong\">".h(_("Verbleibende Rohstoffe im Trümmerfeld"))."</h3>\n".$user_obj->_i(F::formatRess($tr_verbl, 1, false, false, true, false, "ress-block"))."</div>"));
								$message->addUser($username, Message::TYPE_SAMMELN);
								$message->html(true);
							}
							break;
						}
						case self::TYPE_ANGRIFF:
						{
							$angreifer = array();
							$verteidiger = array();
							foreach($this->getUsersList() as $username)
							{
								$trans = $this->getTransport($username);
								$angreifer[$username] = array($this->getFleetsList($username), $trans[0]);
							}

							$angreifer2 = Fleet::battle($target, $angreifer);

							self::$sqlite->beginTransaction();
							foreach($angreifer as $username=>$fl)
							{
								if(!isset($angreifer2[$username]))
								{
									# Flotten des Angreifers wurden zerstoert
									if($username == $first_user) $further = false;
									else
									{
										self::$sqlite->transactionQuery("DELETE FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
									}
								}
								else
								{
									self::$sqlite->transactionQuery("DELETE FROM fleets_users_fleet WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
									foreach($angreifer2[$username][0] as $id=>$number)
										self::$sqlite->transactionQuery("INSERT INTO fleets_users_fleet ( fleet_id, user, id, number ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($username).", ".self::$sqlite->quote($id).", ".self::$sqlite->quote($number).");");
									self::$sqlite->transactionQuery("UPDATE fleets_users SET ress0 = ".self::$sqlite->quote($angreifer2[$username][1][0]).", ress1 = ".self::$sqlite->quote($angreifer2[$username][1][1]).", ress2 = ".self::$sqlite->quote($angreifer2[$username][1][2]).", ress3 = ".self::$sqlite->quote($angreifer2[$username][1][3]).", ress4 = ".self::$sqlite->quote($angreifer2[$username][1][4])." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
								}
							}
							self::$sqlite->endTransaction();

							break;
						}
						case self::TYPE_TRANSPORT:
						{
							$message_text = array(
								$target_owner => sprintf($target_user->_("Ein Transport erreicht Ihren Planeten %s. Folgende Spieler liefern Güter ab:"), sprintf($target_user->_("„%s“ (%s)"), $target_user->planetName(), $next_target_nt))."\n"
							);

							# Rohstoffe abliefern, Handel
							$handel = array();
							$make_handel_message = false;
							foreach($this->getUsersList() as $username)
							{
								$user_obj = Classes::User($username);
								$write_this_username = ($username != $target_owner);
								if($write_this_username) $message_text[$username] = sprintf($user_obj->_("Ihre Flotte erreicht den Planeten %s und liefert folgende Güter ab:"), sprintf($target_user->_("„%s“ (%s, Eigentümer: %s)"), $target_user->planetName(), $next_target_nt, $target_owner))."\n";
								$message_text[$target_owner] .= $username.": ";
								if(!$this->dontPutRes($username))
								{
									$trans = $this->getTransport($username);
									self::$sqlite->query("UPDATE fleets_users SET ress0 = 0, ress1 = 0, ress2 = 0, ress3 = 0, ress4 = 0 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
									self::$sqlite->query("DELETE FROM fleets_users_rob WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
									$target_user->addRess($trans[0]);
									if($write_this_username) $message_text[$username] .= sprintf($user_obj->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $user_obj->_("[ress_0]"), F::ths($data[3][0][0], true), $user_obj->_("[ress_1]"), F::ths($data[3][0][1], true), $user_obj->_("[ress_2]"), F::ths($data[3][0][2], true), $user_obj->_("[ress_3]"), F::ths($data[3][0][3], true), $user_obj->_("[ress_4]"), F::ths($data[3][0][4], true));
									$message_text[$target_owner] .= sprintf($target_user->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $target_user->_("[ress_0]"), F::ths($data[3][0][0], true), $target_user->_("[ress_1]"), F::ths($data[3][0][1], true), $target_user->_("[ress_2]"), F::ths($data[3][0][2], true), $target_user->_("[ress_3]"), F::ths($data[3][0][3], true), $target_user->_("[ress_4]"), F::ths($data[3][0][4], true));

									if($target_owner == $username && array_sum($trans[1]) > 0)
									{
										$target_user->setLanguage();
										$items_string = Item::makeItemsString($trans[1], false);
										$target_user->restoreLanguage();
										if($write_this_username) $message_text[$username] .= "\n".$items_string;
										$message_text[$target_owner] .= $target_user->_("; ").$items_string;
										foreach($trans[1] as $id=>$anzahl)
											$target_user->changeItemLevel($id, $anzahl, "roboter");
									}
								}
								else
								{
									if($write_this_username) $message_text[$username] .= $user_obj->_("Keine.");
									$message_text[$target_owner] .= $target_user->_("Keine.");
								}

								if($write_this_username) $message_text[$username] .= "\n";
								$message_text[$target_owner] .= "\n";
								$transh = $this->getHandel($username);
								if(array_sum($transh[0]) != 0 || array_sum($transh[1]) != 0)
								{
									self::$sqlite->query("UPDATE fleets_users SET hress0 = 0, hress1 = 0, hress2 = 0, hress3 = 0, hress4 = 0 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
									self::$sqlite->query("DELETE FROM fleets_users_hrob WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");

									$handel[$username] = $transh;
									self::$sqlite->query("UPDATE fleets_users SET ress0 = ress0 + ".$transh[0].", ress1 = ress1 + ".$transh[1].", ress2 = ress2 + ".$transh[2].", ress3 = ress3 + ".$transh[3].", ress4 = ress4 + ".$transh[4]." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
									foreach($transh[1] as $id=>$number)
									{
										if(self::$sqlite->singleField("SELECT COUNT(*) FROM fleets_users_rob WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username)." AND id = ".self::$sqlite->quote($id).";") >= 1)
											self::$sqlite->query("UPDATE fleets_users_rob SET number = number + ".self::$sqlite->quote($number)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username)." AND id = ".self::$sqlite->quote($id).";");
										else
											self::$sqlite->query("INSERT INTO fleets_users_rob ( fleet_id, user, id, number ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($username).", ".self::$sqlite->quote($id).", ".self::$sqlite->quote($number).");");
									}
									$make_handel_message = true;
								}
							}

							if($make_handel_message)
							{
								$message_text[$target_owner] .= "\n".$target_user->_("Folgender Handel wird durchgeführt:")."\n";
								foreach($handel as $username=>$h)
								{
									$user_obj = Classes::User($username);
									$write_this_username = ($username != $target_owner);
									if($write_this_username)
									{
										$message_text[$username] .= "\n".$user_obj->_("Folgender Handel wird durchgeführt:")."\n";
										$message_text[$username] .= sprintf($user_obj->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $user_obj->_("[ress_0]"), F::ths($h[0][0], true), $user_obj->_("[ress_1]"), F::ths($h[0][1], true), $user_obj->_("[ress_2]"), F::ths($h[0][2], true), $user_obj->_("[ress_3]"), F::ths($h[0][3], true), $user_obj->_("[ress_4]"), F::ths($h[0][4], true));
									}
									$message_text[$target_owner] .= sprintf($target_user->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $target_user->_("[ress_0]"), F::ths($h[0][0], true), $target_user->_("[ress_1]"), F::ths($h[0][1], true), $target_user->_("[ress_2]"), F::ths($h[0][2], true), $target_user->_("[ress_3]"), F::ths($h[0][3], true), $target_user->_("[ress_4]"), F::ths($h[0][4], true));
									if(array_sum($h[1]) > 0)
									{
										if($write_this_username) $message_text[$username] .= "\n";
										$message_text[$target_owner] .= $target_user->_("; ");
										$items_string = Item::makeItemsString($h[1], false);
										if($write_this_username) $message_text[$username] .= $items_string;
										$message_text[$target_owner] .= $items_string;
									}
									if($write_this_username) $message_text[$username] .= "\n";
									$message_text[$target_owner] .= "\n";
								}
							}
							foreach($message_text as $username=>$text)
							{
								$user_obj = Classes::User($username);

								// Will keine Nachrichten erhalten?
								$receive = $user_obj->checkSetting("receive");
								if($this->userExists($username) && isset($receive[3]) && isset($receive[3][0]) && !$receive[3][0])
									continue;

								$message_obj = Classes::Message(Message::create());
								if($username == $target_owner && !$this->userExists($username))
								{
									$message_obj->subject(sprintf($user_obj->_("Ankunft eines fremden Transportes auf %s"), $user_obj->localise(array("Planet", "format"), $target)));
									$message_obj->from($this->getFirstUser());
								}
								else
								{
									$message_obj->subject(sprintf($user_obj->_("Ankunft Ihres Transportes auf %s"), $user_obj->localise(array("Planet", "format"), $target)));
									$message_obj->from($target_owner);
								}
								$message_obj->text($text);
								$message_obj->addUser($username, $types_message_types[$type]);
							}

							break;
						}
						case self::TYPE_SPIONAGE:
						{
							$destroyed = array();

							if(!$target_owner)
							{
								# Zielplanet ist nicht besiedelt
								$message_text = "<div class=\"nachricht-spionage\">\n";
								$message_text .= "\t<h3 class=\"strong\">%1\$s</h3>\n";
								$message_text .= "\t<div id=\"spionage-planet\">\n";
								$message_text .= "\t\t<h4 class=\"strong\">%2\$s</h4>\n";
								$message_text .= "\t\t<dl class=\"planet_".$target->getPlanetClass()."\">\n";
								$message_text .= "\t\t\t<dt class=\"c-felder\">%3\$s</dt>\n";
								$message_text .= "\t\t\t<dd class=\"c-felder\">".F::ths($target->getSize())."</dd>\n";
								$message_text .= "\t\t</dl>\n";
								$message_text .= "\t</div>\n";

								$message_text .= "\t<p class=\"besiedeln\">\n";
								$message_text .= "\t\t<a href=\"flotten.php?action=besiedeln&amp;action_galaxy=".htmlspecialchars(urlencode($target->getGalaxy()))."&amp;action_system=".htmlspecialchars(urlencode($target->getSystem()))."&amp;action_planet=".htmlspecialchars(urlencode($target->getPlanet()))."\" onclick=\"return fast_action(this, 'besiedeln', ".$target->getGalaxy().", ".$target->getSystem().", ".$target->getPlanet().");\" title=\"%4\$s\">%5\$s</a>\n";
								$message_text .= "\t</p>\n";
								$message_text .= "</div>";
							}
							else
							{
								# Zielplanet ist besiedelt

								$users = $this->getUsersList();
								$verbuendet = true;
								foreach($users as $username)
								{
									if(!$target_user->isVerbuendet($username))
									{
										$verbuendet = false;
										break;
									}
								}
								if(!$verbuendet)
								{
									# Spionageabwehr
									$owner_v7 = $target_user->getItemLevel("V7", "verteidigung"); // Spionageabwehrgeschütze
									foreach($users as $username)
									{
										$fl = $this->getFleetsList($username);
										if(isset($fl["S5"]))
										{
											$destroyed[$username] = $owner_v7;
											if($owner_v7 >= $fl["S5"])
											{
												self::$sqlite->query("DELETE FROM fleets_users_fleet WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username)." AND id = ".self::$sqlite->quote("S5").";");
												$destroyed[$username] = $fl["S5"];
											}
											else
											{
												self::$sqlite->query("UPDATE fleets_users_fleet SET number = number - ".self::$sqlite->quote($owner_v7)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username)." AND id = ".self::$sqlite->quote("S5").";");
												$destroyed[$username] = $owner_v7;
											}
										}
									}

									# Spionagetechnikdifferenz ausrechnen
									$owner_level = $target_user->getItemLevel("F1", "forschung"); // Spionagetechnik
									$others_level = 0;
									foreach($users as $username)
									{
										$fl = $this->getFleetsList($username);
										if(isset($fl["S5"])) // Spionagesonden
											$others_level += $fl["S5"];
									}
									$others_level -= count($users);
									if($others_level < 0) $others_level = 0;

									$max_f1 = 0;
									foreach($users as $username)
									{
										$user = Classes::User($username);
										$this_f1 = $user->getItemLevel("F1", "forschung"); // Spionagetechnik
										if($this_f1 > $max_f1) $max_f1 = $this_f1;
									}
									$others_level += $max_f1;

									if($owner_level == 0) $diff = 5;
									else $diff = floor(pow($others_level/$owner_level, 2));
								}
								else # Spionierter Planet liefert alle Daten aus, wenn alle Spionierenden verbuendet sind
									$diff = 5;

								if($diff > 5)
									$diff = 5;

								$message_text = "<div class=\"nachricht-spionage\">\n";
								$message_text .= "\t<h3 class=\"strong\">%6\$s</h3>\n";
								$message_text .= "\t<div id=\"spionage-planet\">\n";
								$message_text .= "\t\t<h4 class=\"strong\">%2\$s</h4>\n";
								$message_text .= "\t\t<dl class=\"planet_".$target->getPlanetClass()."\">\n";
								$message_text .= "\t\t\t<dt class=\"c-felder\">%3\$s</dt>\n";
								$message_text .= "\t\t\t<dd class=\"c-felder\">".F::ths($target->getSize())."</dd>\n";
								$message_text .= "\t\t</dl>\n";
								$message_text .= "\t</div>\n";

								$message_text2 = array();
								switch($diff)
								{
									case 5: # Roboter zeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-roboter\">\n";
										$next .= "\t\t<h4 class=\"strong\">%7\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList("roboter") as $id)
										{
											if($target_user->getItemLevel($id, "roboter") <= 0) continue;
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"anzahl\">(".F::ths($target_user->getItemLevel($id, "roboter")).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 4: # Forschung zeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-forschung\">\n";
										$next .= "\t\t<h4 class=\"strong\">%8\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList("forschung") as $id)
										{
											if($target_user->getItemLevel($id, "forschung") <= 0) continue;
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"stufe\">(Level&nbsp;".F::ths($target_user->getItemLevel($id, "forschung")).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 3: # Schiffe und Verteidigungsanlagen anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-schiffe\">\n";
										$next .= "\t\t<h4 class=\"strong\">%9\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										$schiffe = array();
										foreach($target_user->getItemsList("schiffe") as $id)
										{
											$count = $target_user->getItemLevel($id, "schiffe");
											if($count <= 0) continue;
											$schiffe[$id] = $count;
										}

										# Fremdstationierte Flotten mit den eigenen zusammen anzeigen
										foreach($target_user->getForeignUsersList() as $foreign_user)
										{
											foreach($target_user->getForeignFleetsList($foreign_user) as $foreign_i=>$foreign_fleet)
											{
												foreach($foreign_fleet[0] as $id=>$count)
												{
													if(isset($schiffe[$id]))
														$schiffe[$id] += $count;
													else
														$schiffe[$id] = $count;
												}
											}
										}

										foreach($schiffe as $id=>$count)
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"anzahl\">(".F::ths($count).")</span></li>\n";
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										$next .= "\t<div id=\"spionage-verteidigung\">\n";
										$next .= "\t\t<h4 class=\"strong\">%10\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList("verteidigung") as $id)
										{
											if($target_user->getItemLevel($id, "verteidigung") <= 0) continue;
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"anzahl\">(".F::ths($target_user->getItemLevel($id, "verteidigung")).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 2: # Gebaeude anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-gebaeude\">\n";
										$next .= "\t\t<h4 class=\"strong\">%11\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList("gebaeude") as $id)
										{
											if($target_user->getItemLevel($id, "gebaeude") <= 0) continue;
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"stufe\">(Stufe&nbsp;".F::ths($target_user->getItemLevel($id, "gebaeude")).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 1: # Rohstoffe anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-rohstoffe\">\n";
										$next .= "\t\t<h4 class=\"strong\">%12\$s</h4>\n";
										$next .= F::formatRess($target_user->getRess(), 2, true, false, true, null, "ress-block");
										$next .= "\t</div>\n";
										unset($next);
								}
								$message_text .= implode("", array_reverse($message_text2));
								$message_text .= "</div>\n";

								# Benachrichtigung an den Planetenbesitzer
								$message = Classes::Message(Message::create());
								$message->subject(sprintf($target_user->_("Fremde Flotte auf dem Planeten %s"), $target_user->localise(array("Planet", "format"), $target)));
								$from = $this->from($this->getFirstUser());
								$first_user_text = sprintf($target_user->_("Eine fremde Flotte vom Planeten %s wurde von Ihrem Planeten %s aus bei der Spionage gesichtet."), sprintf($target_user->_("„%s“ (%s, Eigentümer: %s)"), $from->getName(), $target_user->localise(array("Planet", "format"), $from), $first_user), sprintf($target_user->_("„%s“ (%s)"), $target_user->planetName(), $target_user->localise(array("Planet", "format"), $target)));
								if(array_sum($destroyed) > 0)
									$first_user_text .= "\n\n".sprintf($target_user->ngettext("Durch Spionageabwehr haben Sie eine Spionagesonde zerstört.", "Durch Spionageabwehr haben Sie %s Spionagesonden zerstört.", array_sum($destroyed)), F::ths(array_sum($destroyed)));
								$message->text($first_user_text);
								$message->from($first_user);
								$message->addUser($target_owner, $types_message_types[$type]);
							}

							foreach($this->getUsersList() as $username)
							{
								$u = Classes::User($username);
								$fl = $this->getFleetsList($username);
								$message = Classes::Message(Message::create());
								if(isset($fl["S5"]) && $fl["S5"] > 0)
								{
									$message->subject(sprintf($u->_("Spionage des Planeten %s"), $u->localise(array("Planet", "format"), $target)));
									$text = $u->_i(sprintf($message_text,
										h(sprintf($u->_("Spionagebericht des Planeten %s"), $u->localise(array("Planet", "format"), $target))),
										h($u->_("Planet")),
										h($u->_("Felder")),
										h($u->_("Schicken Sie ein Besiedelungsschiff zu diesem Planeten")),
										h($u->_("Besiedeln")),
										sprintf(h($u->_("Spionagebericht des Planeten %s")), sprintf(h($u->_("„%s“ (%s, Eigentümer: %s)")), htmlspecialchars($target->getName($target[1], $target[2])), htmlspecialchars($u->localise(array("Planet", "format"), $target)), htmlspecialchars($target_owner))),
										h($u->_("Roboter")),
										h($u->_("Forschung")),
										h($u->_("Schiffe")),
										h($u->_("Verteidigung")),
										h($u->_("Gebäude")),
										h($u->_("Rohstoffe"))
									));

									if(isset($destroyed[$username]) && $destroyed[$username] > 0)
										$text .= "<hr />\n<p>".h(sprintf($u->ngettext("Die Spionageabwehr des Planeten hat eine Ihrer Spionagesonden zerstört.", "Die Spionageabwehr des Planeten hat %s Ihrer Spionagesonden zerstört.", $destroyed[$username]), F::ths($destroyed[$username])))."</p>\n";

									$message->text($text);
									$message->html(true);
								}
								elseif(isset($destroyed[$username]) && $destroyed[$username] > 0)
								{
									$message->subject(sprintf($u->_("Spionage des Planeten %s abgewehrt"), $u->localise(array("Planet", "format", $target))));
									$message->text(sprintf($u->ngettext("Ihre Flotte erreichte den Planeten %s, um diesen auszuspionieren.\n\nJedoch zerstörte die Spionageabwehr Ihre Spionagesonde, bevor diese einen Bericht übertragen konnte.", "Ihre Flotte erreichte den Planeten %s, um diesen auszuspionieren.\n\nJedoch zerstörte die Spionageabwehr des Planeten alle Ihrer %s Spionagesonden, bevor diese einen Bericht übertragen konnten.", $destroyed[$username]), sprintf($u->_("„%s“ (%s, Eigentümer: %s)"), $target_user->planetName(), $u->localise(array("Planet", "format"), $target), $target_user->getName()), F::ths($destroyed[$username])));
								}
								else
								{
									$message->subject(sprintf($u->_("Spionage des Planeten %s fehlgeschlagen"), $u->localise(array("Planet", "format", $target))));
									$message->text(sprintf($u->_("Ihre Flotte erreichte den Planeten %s, um diesen auszuspionieren.\n\nLeider waren keine Spionagesonden unter den Schiffen der Flotte, weshalb die Spionage nicht möglich war."), sprintf($u->_("„%s“ (%s, Eigentümer: %s)"), $target_user->planetName(), $u->localise(array("Planet", "format", $target)))));
								}

								if($target_owner)
									$message->from($target_owner);
								$message->addUser($username, $types_message_types[$type]);

								if(array_sum($this->getFleetsList($username)) < 1)
								{
									if($username == $first_user)
										$further = false;
									else
									{
										self::$sqlite->query("DELETE FROM fleets_users WHERE fleet_id = ".self::$sqlite->query($this->getName())." AND user = ".self::$sqlite->query($username).";");
										self::$sqlite->query("DELETE FROM fleets_users_fleet WHERE fleet_id = ".self::$sqlite->query($this->getName())." AND user = ".self::$sqlite->query($username).";");
										self::$sqlite->query("DELETE FROM fleets_users_rob WHERE fleet_id = ".self::$sqlite->query($this->getName())." AND user = ".self::$sqlite->query($username).";");
										self::$sqlite->query("DELETE FROM fleets_users_hrob WHERE fleet_id = ".self::$sqlite->query($this->getName())." AND user = ".self::$sqlite->query($username).";");
									}
								}
							}
						}
					}
				}

				# Weiterfliegen

				$first_user = $this->getFirstUser();
				$visible_users = $this->getVisibleUsers();

				# Flugerfahrung
				foreach($this->getUsersList() as $user)
					self::$sqlite->query("UPDATE fleets_users SET used_tritium = used_tritium + ".self::$sqlite->quote($this->getTritium($user, $this->getLastTarget($user), $target))." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");

				# Rückflugflotten der anderen Benutzer
				$users = $this->getUsersList();
				array_shift($users);
				foreach($users as $user)
				{
					$user_obj = Classes::User($user);
					$new_fleet = self::create();
					$from = $this->from($user);
					self::$sqlite->query("INSERT INTO fleets_targets ( i, fleet_id, galaxy, system, planet, type, flying_back, arrival, finished ) SELECT 1, ".self::$sqlite->quote($new_fleet).", galaxy, system, planet, type, flying_back, arrival, 1 FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT finished ORDER BY i ASC LIMIT 1;");
					self::$sqlite->query("INSERT INTO fleets_targets ( i, fleet_id, galaxy, system, planet, type, flying_back, arrival, finished ) VALUES ( 2, ".self::$sqlite->quote($new_fleet).", ".self::$sqlite->quote($from->getGalaxy()).", ".self::$sqlite->quote($from->getSystem()).", ".self::$sqlite->quote($from->getPlanet()).", ".self::$sqlite->quote($this->getCurrentType()).", 1, ".self::$sqlite->quote(2*$this->getNextArrival()-$this->getStartTime($user)).", 0);");
					self::$sqlite->query("UPDATE fleets_users SET fleet_id = ".self::$sqlite->quote($new_fleet)." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");
				}

				if($further)
				{
					$i = self::$sqlite->singleField("SELECT i FROM fleets_targets WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND NOT FINISHED ORDER BY i ASC LIMIT 1;");
					self::$sqlite->query("UPDATE fleets_targets SET finished = 1 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND i = ".self::$sqlite->quote($i).";");
				}

				if(!$further) $this->destroy();
			}
			else
			{
				# Stationieren

				$owner = $target->getOwner();

				if($besiedeln || $owner == $first_user && !$back)
				{
					# Ueberschuessiges Tritium
					self::$sqlite->query("UPDATE fleets_users SET ress_tritium = ress_tritium + ".self::$sqlite->quote($this->getTritium($first_user, $this->from($first_user), $target))." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($first_user).";");
				}

				if($besiedeln)
				{
					$user_obj = Classes::User($first_user);
					$user_obj->registerPlanet($target);
					$fleet = $this->getFleetsList($first_user);
					if(isset($fleet["S6"]) && $fleet["S6"] > 0) // Besiedelungsschiff
					{
						self::$sqlite->query("UPDATE fleets_users_fleet SET number = number - 1 WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($first_user)." AND id = ".self::$sqlite->quote("S6").";");
						$user_obj->cacheActivePlanet();
						$user_obj->setActivePlanet($user_obj->getPlanetByPos($target));
						$item_info = $user_obj->getItemInfo("S6", "schiffe", array("ress"));
						$besiedelung_ress = $item_info["ress"];
						$besiedelung_ress[0] *= .4;
						$besiedelung_ress[1] *= .4;
						$besiedelung_ress[2] *= .4;
						$besiedelung_ress[3] *= .4;
						$user_obj->addRess($besiedelung_ress);
						$user_obj->restoreActivePlanet();
					}
					$owner = $first_user;
				}

				if(!$owner)
					throw new FleetException("Tried to land on a planet that is not colonised yet.");

				$owner_obj = Classes::User($owner);

				$planet_index = $owner_obj->getPlanetByPos($target);
				if($planet_index === false)
					throw new FleetException("This planet does not belong to that user.");

				$owner_obj->cacheActivePlanet();
				$owner_obj->setActivePlanet($planet_index);

				$ress = array(0, 0, 0, 0, 0);
				$robs = array();
				$schiffe_own = array();
				$schiffe_other = array();

				# Flugerfahrung
				foreach($this->getUsersList() as $username)
				{
					$trans = $this->getTransport($username);
					$ress[0] += $trans[0][0];
					$ress[1] += $trans[0][1];
					$ress[2] += $trans[0][2];
					$ress[3] += $trans[0][3];
					$ress[4] += $trans[0][4];

					foreach($trans[1] as $id=>$count)
					{
						if(isset($robs[$id])) $robs[$id] += $count;
						else $robs[$id] = $count;
					}

					if($username == $owner)
					{
						# Stationieren
						foreach($this->getFleetsList($username) as $id=>$count)
						{
							if(isset($schiffe_own[$id])) $schiffe_own[$id] += $count;
							else $schiffe_own[$id] = $count;
						}

						if($username != $first_user) # Überschüssiges Tritium
							self::$sqlite->query("UPDATE fleets_users SET ress_tritium = ress_tritium + ".self::$sqlite->quote($this->getTritium($username, $this->from($username), $target))." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");
					}
					else
					{
						# Fremdstationieren
						if(!isset($schiffe_other[$username]))
							$schiffe_other[$username] = array();
						foreach($this->getFleetsList($username) as $id=>$count)
						{
							if(isset($schiffe_other[$username][$id])) $schiffe_other[$username][$id] += $count;
							else $schiffe_other[$username][$id] = $count;
						}
					}

					# Flugerfahrung
					self::$sqlite->query("UPDATE fleets_users SET used_tritium = used_tritium + ".self::$sqlite->quote($this->getTritium($username, $this->getLastTarget($username), $target))." WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username).";");

					$user_obj = Classes::User($username);
					$user_obj->addScores(User::SCORES_FLUGERFAHRUNG, self::$sqlite->singleField("SELECT used_tritium FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($username)." LIMIT 1;")/1000);
				}

				foreach($schiffe_own as $id=>$anzahl)
					$owner_obj->changeItemLevel($id, $anzahl);
				foreach($schiffe_other as $user=>$schiffe)
					$owner_obj->addForeignFleet($user, $schiffe, $move_info[1], $move_info[2]);
				foreach($robs as $id=>$anzahl)
					$owner_obj->changeItemLevel($id, $anzahl, "roboter");

				$ress[4] += self::$sqlite->singleField("SELECT ress_tritium FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($first_user)." LIMIT 1;");
				$owner_obj->addRess($ress);

				$message_users = array();
				foreach($this->getUsersList() as $username)
				{
					$message_user_obj = Classes::User($username);
					$receive = $message_user_obj->checkSetting("receive");
					if(!isset($receive[$types_message_types[$this->getCurrentType()]][$this->isFlyingBack()]) || $receive[$types_message_types[$this->getCurrentType()]][$this->isFlyingBack()])
					{
						# Will Nachricht erhalten
						$message_users[] = $username;
					}
				}

				if($owner && !$this->userExists($owner))
					$message_users[] = $owner;

				foreach($message_users as $name)
				{
					$user_obj = Classes::User($name);
					$message_text = "";
					if($besiedeln)
					{
						$message_text .= sprintf($user_obj->_("Ihre Flotte erreicht den Planeten %s und beginnt mit seiner Besiedelung."), $user_obj->localise(array("Planet", "format"), $target));
						if(isset($besiedelung_ress))
							$message_text .= " ".sprintf($user_obj->_(" Durch den Abbau eines Besiedelungsschiffs konnten folgende Rohstoffe wiederhergestellt werden: %s."), sprintf($user_obj->_("%s %s, %s %s, %s %s, %s %s"), F::ths($besiedelung_ress[0], true), $user_obj->_("[ress_0]"), F::ths($besiedelung_ress[1], true), $user_obj->_("[ress_1]"), F::ths($besiedelung_ress[2], true), $user_obj->_("[ress_2]"), F::ths($besiedelung_ress[3], true), $user_obj->_("[ress_3]")));
						$message_text .= "\n";
					}
					else
						$message_text .= sprintf($user_obj->_("Eine Flotte erreicht den Planeten %s."), sprintf($user_obj->_("„%s“ (%s, Eigentümer: %s)"), $owner_obj->planetName(), $owner_obj->getPosFormatted(), $owner_obj->getName()))."\n";

					if(array_sum($schiffe_own) > 0)
					{
						$user_obj->setLanguage();
						$message_text .= sprintf($user_obj->_("Die Flotte besteht aus folgenden Schiffen: %s"), Item::makeItemsString($schiffe_own, false))."\n";
						$user_obj->restoreLanguage();
					}

					if(Functions::array_sum_r($schiffe_other) > 0)
					{
						$message_text .= $user_obj->_("Folgende Schiffe werden fremdstationiert:")."\n";
						foreach($schiffe_other as $user=>$schiffe)
						{
							$user_obj->setLanguage();
							$message_text .= sprintf($user_obj->_("%s: %s"), $user, Item::makeItemsString($schiffe, false))."\n";
							$user_obj->restoreLanguage();
						}
					}

					$message_text .= "\n".$user_obj->_("Folgende Güter werden abgeliefert:")."\n";
					$message_text .= sprintf($user_obj->_("%s %s, %s %s, %s %s, %s %s, %s %s."), F::ths($ress[0], true), $user_obj->_("[ress_0]"), F::ths($ress[1], true), $user_obj->_("[ress_1]"), F::ths($ress[2], true), $user_obj->_("[ress_2]"), F::ths($ress[3], true), $user_obj->_("[ress_3]"), F::ths($ress[4], true), $user_obj->_("[ress_4]"));
					if(array_sum($robs) > 0)
					{
						$user_obj->setLanguage();
						$message_text .= "\n".Item::makeItemsString($robs, false)."\n";
						$user_obj->restoreLanguage();
					}

					$tritium = self::$sqlite->singleField("SELECT ress_tritium FROM fleets_users WHERE fleet_id = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($first_user)." LIMIT 1;");
					if($tritium > 0)
						$message_text .= "\n\n".sprintf($user_obj->_("Folgender überschüssiger Treibstoff wird abgeliefert: %s."), sprintf($user_obj->_("%s %s"), F::ths($tritium, true), $user_obj->_("[ress_4]")));

					$message_obj = Classes::Message(Message::create());
					$message_obj->text($message_text);
					if($besiedeln)
						$message_obj->subject(sprintf($user_obj->_("Besiedelung von %s"), $user_obj->localise(array("Planet", "format"), $target)));
					else
						$message_obj->subject(sprintf($user_obj->_("Stationierung auf %s"), $owner_obj->getPosFormatted()));

					# Bei Fremdstationierung Nachrichtenabsender eintragen
					if($owner && !$this->userExists($owner))
						$message_obj->from($first_user);

					$message_obj->addUser($name, $types_message_types[$this->getCurrentType()]);
				}

				$this->destroy();
			}
		}

		/**
		* Laesst auf dem Planeten $planet die Flotten $angreifer angreifen.
		* @param Planet $planet
		* @param array $angreifer_param ( Benutzername => [ ( Item-ID => Anzahl ), ( Rohstoff-Index => Rohstoff-Anzahl ) ] )
		* @return $angreifer_param hinterher
		*/

		static function battle(Planet $planet, array $angreifer_param)
		{
			$angreifer = array();
			foreach($angreifer_param as $username=>$info)
				$angreifer[$username] = $info[0];

			$target_owner = $planet->getOwner();
			if(!$target_owner)
				throw new FleetException("Planet is not colonised.");
			$target_user = Classes::User($target_owner);
			$target_user->setActivePlanet($target_user->getPlanetByPos($planet));

			$verteidiger = array();
			$verteidiger[$target_owner] = array();
			foreach($target_user->getItemsList("schiffe") as $item)
			{
				$level = $target_user->getItemLevel($item, "schiffe");
				if($level <= 0) continue;
				$verteidiger[$target_owner][$item] = $level;
			}
			foreach($target_user->getItemsList("verteidigung") as $item)
			{
				$level = $target_user->getItemLevel($item, "verteidigung");
				if($level <= 0) continue;
				$verteidiger[$target_owner][$item] = $level;
			}
			$foreign_users = $target_user->getForeignUsersList();
			foreach($foreign_users as $username)
			{
				$foreign_fleet = array();
				foreach($target_user->getForeignFleetsList($username) as $foreign_fi)
					$foreign_fleet = Item::iadd($foreign_fleet, $foreign_fi[0]);
				Item::isort($foreign_fleet);
				$verteidiger[$username] = $foreign_fleet;
			}

			$angreifer_anfang = $angreifer;
			$verteidiger_anfang = $verteidiger;

			$users_angreifer = array();
			$users_verteidiger = array();
			foreach($angreifer as $username=>$i)
				$users_angreifer[$username] = Classes::User($username);
			foreach($verteidiger as $username=>$i)
				$users_verteidiger[$username] = Classes::User($username);
			$users_all = $users_angreifer + $users_verteidiger;

			# Spionagetechnik fuer Erstschlag
			$angreifer_spiotech = 0;
			foreach($users_angreifer as $user)
				$angreifer_spiotech += $user->getItemLevel("F1", "forschung");
			$angreifer_spiotech /= count($users_angreifer);

			$verteidiger_spiotech = 0;
			foreach($users_verteidiger as $user)
				$verteidiger_spiotech += $user->getItemLevel("F1", "forschung");
			$verteidiger_spiotech /= count($users_verteidiger);


			# Kampferfahrung
			$angreifer_erfahrung = 0;
			foreach($users_angreifer as $user)
				$angreifer_erfahrung += $user->getScores(6);
			$verteidiger_erfahrung = 0;
			foreach($users_verteidiger as $user)
				$verteidiger_erfahrung += $user->getScores(6);


			# Nachrichtentext
			$nachrichten_text = "<div class=\"nachricht-kampf\">\n";
			$nachrichten_text .= "\t<h3>%1\$s</h3>\n";

			$nachrichten_text .= "\t<table>\n";
			$nachrichten_text .= "\t\t<thead>\n";
			$nachrichten_text .= "\t\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">%2\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">%3\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">%4\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">%5\$s</th>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</thead>\n";
			$nachrichten_text .= "\t\t<tbody>\n";

			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($angreifer as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"angreifer-name\">".htmlspecialchars($name)."</span></th>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";

				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				foreach($flotten as $id=>$anzahl)
				{
					$item_info = $users_angreifer[$name]->getItemInfo($id, null, array("att", "def"));

					$staerke = $item_info["att"]*$anzahl;
					$schild = $item_info["def"]*$anzahl;

					$nachrichten_text .= "\t\t\t<tr>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($schild)."</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";

					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}

				$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($this_ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($this_ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($this_ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";

				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}

			$nachrichten_text .= "\t\t</tbody>\n";

			if(count($angreifer) > 1)
			{
				$nachrichten_text .= "\t\t<tfoot>\n";
				$nachrichten_text .= "\t\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
				$nachrichten_text .= "\t\t</tfoot>\n";
			}
			$nachrichten_text .= "\t</table>\n";

			$nachrichten_text .= "\t<h3>%8\$s</h3>\n";

			$nachrichten_text .= "\t<table>\n";
			$nachrichten_text .= "\t\t<thead>\n";
			$nachrichten_text .= "\t\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">%2\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">%3\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">%4\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">%5\$s</th>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</thead>\n";
			$nachrichten_text .= "\t\t<tbody>\n";

			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($verteidiger as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"verteidiger-name\">".htmlspecialchars($name)."</span></th>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";

				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				$one = false;
				foreach($flotten as $id=>$anzahl)
				{
					$item_info = $users_verteidiger[$name]->getItemInfo($id, null, array("att", "def"));

					$staerke = $item_info["att"]*$anzahl;
					$schild = $item_info["def"]*$anzahl;

					if($anzahl > 0)
					{
						$nachrichten_text .= "\t\t\t<tr>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($anzahl)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($staerke)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($schild)."</td>\n";
						$nachrichten_text .= "\t\t\t</tr>\n";
						$one = true;
					}

					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}

				if(!$one)
				{
					$nachrichten_text .= "\t\t\t<tr class=\"keine\">\n";
					$nachrichten_text .= "\t\t\t\t<td colspan=\"4\">%9\$s</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";
				}
				else
				{
					$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($this_ges_anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($this_ges_staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($this_ges_schild)."</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";
				}

				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}

			$nachrichten_text .= "\t\t</tbody>\n";

			if(count($verteidiger) > 1)
			{
				$nachrichten_text .= "\t\t<tfoot>\n";
				$nachrichten_text .= "\t\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
				$nachrichten_text .= "\t\t</tfoot>\n";
			}
			$nachrichten_text .= "\t</table>\n";

			# Erstschlag
			if($angreifer_spiotech > $verteidiger_spiotech)
			{
				$runde_starter = "angreifer";
				$runde_anderer = "verteidiger";

				$nachrichten_text .= "\t<p class=\"erstschlag angreifer\">\n";
				$nachrichten_text .= "\t\t%10\$s\n";
				$nachrichten_text .= "\t</p>\n";
			}
			else
			{
				$runde_starter = "verteidiger";
				$runde_anderer = "angreifer";

				$nachrichten_text .= "\t<p class=\"erstschlag verteidiger\">\n";
				$nachrichten_text .= "\t\t%11\$s\n";
				$nachrichten_text .= "\t</p>\n";
			}

			$verteidiger_no_fleet = true;
			foreach($verteidiger as $name=>$ids)
			{
				if(array_sum($ids) > 0)
				{
					$verteidiger_no_fleet = false;
					break;
				}
			}
			if($verteidiger_no_fleet)
			{
				$runde_starter = "angreifer";
				$runde_anderer = "verteidiger";
			}

			foreach($angreifer as $name=>$ids)
			{
				foreach($ids as $id=>$anzahl)
				{
					if($anzahl <= 0) unset($ids[$id]);
				}
				if(count($ids) <= 0) unset($angreifer[$name]);
				else $angreifer[$name] = $ids;
			}

			foreach($verteidiger as $name=>$ids)
			{
				foreach($ids as $id=>$anzahl)
				{
					if($anzahl <= 0) unset($ids[$id]);
				}
				if(count($ids) <= 0) unset($verteidiger[$name]);
				else $verteidiger[$name] = $ids;
			}

			$nachrichten_runden = array();

			foreach($users_all as $name=>$user_obj)
				$nachrichten_runden[$name] = array();

			# Einzelne Runden
			for($runde = 1; $runde <= 20; $runde++)
			{
				if(count($angreifer) <= 0 || count($verteidiger) <= 0) break;

				$a = & ${$runde_starter};
				$d = & ${$runde_anderer};
				$a_objs = & ${"users_".$runde_starter};
				$d_objs = & ${"users_".$runde_anderer};

				# Flottengesamtstaerke
				$staerke = 0;
				foreach($a as $name=>$items)
				{
					foreach($items as $id=>$anzahl)
					{
						$item_info = $a_objs[$name]->getItemInfo($id, null, array("att"));
						if(!$item_info) continue;
						$staerke += $item_info["att"]*$anzahl;
					}
				}

				foreach($users_all as $name=>$user_obj)
				{
					$nachrichten_runden[$name][$runde] = "";
					if($runde%2)
					{
						$nachrichten_runden[$name][$runde] .= "\t<div class=\"runde\">\n";
						$nachrichten_runden[$name][$runde] .= "\t\t<h3>".h(sprintf($user_obj->_("Runde %s"), ($runde+1)/2))."</h3>\n";
					}

					$nachrichten_runden[$name][$runde] .= "\t\t<h4>".h(sprintf($runde_starter == "angreifer" ? $user_obj->ngettext("Der Angreifer ist am Zug (Gesamtstärke %s)", "Die Angreifer sind am Zug (Gesamtstärke %s)", count($angreifer)) : $user_obj->ngettext("Der Verteidiger ist am Zug (Gesamtstärke %s)", "Die Verteidiger sind am Zug (Gesamtstärke %s)", count($verteidiger)), F::ths(round($staerke)))).")</h4>\n";
					$nachrichten_runden[$name][$runde] .= "\t\t<ol>\n";
				}

				while($staerke > 0)
				{
					$att_user = array_rand($d);
					$att_id = array_rand($d[$att_user]);

					$item_info = ${"users_".$runde_anderer}[$att_user]->getItemInfo($att_id, null, array("def"));
					$this_shield = $item_info["def"]*$d[$att_user][$att_id];

					$schild_f = pow(0.95, ${"users_".$runde_anderer}[$att_user]->getItemLevel("F10", "forschung"));
					$aff_staerke = $staerke*$schild_f;

					if($this_shield > $aff_staerke)
					{
						$this_shield -= $aff_staerke;
						$before = $d[$att_user][$att_id];
						$d[$att_user][$att_id] = $this_shield/$item_info["def"];
						$floor_diff = ceil($before)-ceil($d[$att_user][$att_id]);

						foreach($users_all as $name=>$user_obj)
						{
							$nachrichten_runden[$name][$runde] .= "\t\t\t<li>";
							if($floor_diff <= 0)
								$nachrichten_runden[$name][$runde] .= sprintf(h($user_obj->_("Eine Einheit des Typs %s (%s) wird angeschossen.")), h($user_obj->_("[item_".$att_id."]")), "<span class=\"".$runde_anderer."-name\">".htmlspecialchars($att_user)."</span>")."</li>\n";
							else
								$nachrichten_runden[$name][$runde] .= sprintf(h($user_obj->ngettext("%s Einheit des Typs %s (%s) wird zerstört.", "%s Einheiten des Typs %s (%s) werden zerstört.", $floor_diff)), F::ths($floor_diff), htmlspecialchars($item_info["name"]), "<span class=\"".$runde_anderer."-name\">".htmlspecialchars($att_user)."</span>")." ".h(sprintf($user_obj->ngettext("%s verbleibt.", "%s verbleiben.", ceil($d[$att_user][$att_id])), F::ths(ceil($d[$att_user][$att_id]))))."</li>\n";
						}

						$staerke = 0;
					}
					else
					{
						foreach($users_all as $name=>$user_obj)
							$nachrichten_runden[$name][$runde] .= "\t\t\t<li>".sprintf(h($user_obj->_("Alle Einheiten des Typs %s (%s) (%s) werden zerstört.")), $user_obj->_("[item_".$att_id."]"), F::ths(ceil($d[$att_user][$att_id])), "<span class=\"".$runde_anderer."-name\">".htmlspecialchars($att_user)."</span>")."</li>\n";
						$aff_staerke = $this_shield;
						unset($d[$att_user][$att_id]);
						if(count($d[$att_user]) <= 0) unset($d[$att_user]);
						$staerke -= $aff_staerke/$schild_f;
					}

					if(count($angreifer) <= 0 || count($verteidiger) <= 0) break;
				}

				foreach($users_all as $name=>$user_obj)
				{
					$nachrichten_runden[$name][$runde] .= "\t\t</ol>\n";
					if(!$runde%2)
						$nachrichten_runden[$name][$runde] .= "\t</div>\n";
				}

				# Vertauschen
				list($runde_starter, $runde_anderer) = array($runde_anderer, $runde_starter);
				unset($a);
				unset($d);
				unset($a_objs);
				unset($d_objs);
			}

			$nachrichten_text .= "%12\$s";

			$nachrichten_text .= "\t<p>\n";
			if(count($angreifer) == 0)
			{
				$nachrichten_text .= "\t\t%13\$s\n";
				$winner = -1;
			}
			elseif(count($verteidiger) == 0)
			{
				$nachrichten_text .= "\t\t%14\$s\n";
				$winner = 1;
			}
			else
			{
				$nachrichten_text .= "\t\t%15\$s\n";
				$winner = 0;
			}
			$nachrichten_text .= "\t</p>\n";

			# Flottenbestaende aufrunden
			foreach($angreifer as $name=>$ids)
			{
				foreach($ids as $id=>$anzahl)
					$angreifer[$name][$id] = ceil($anzahl);
			}
			foreach($verteidiger as $name=>$ids)
			{
				foreach($ids as $id=>$anzahl)
					$verteidiger[$name][$id] = ceil($anzahl);
			}

			$truemmerfeld = array(0, 0, 0, 0);
			$verteidiger_ress = array();
			$angreifer_punkte = array();
			$verteidiger_punkte = array();

			$nachrichten_text .= "\t<h3>%1\$s</h3>\n";

			$nachrichten_text .= "\t<table>\n";
			$nachrichten_text .= "\t\t<thead>\n";
			$nachrichten_text .= "\t\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">%2\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">%3\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">%4\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">%5\$s</th>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</thead>\n";
			$nachrichten_text .= "\t\t<tbody>\n";

			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($angreifer_anfang as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"angreifer-name\">".htmlspecialchars($name)."</span></th>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";

				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				$angreifer_punkte[$name] = 0;
				$one = false;
				foreach($flotten as $id=>$old_anzahl)
				{
					$item_info = $users_angreifer[$name]->getItemInfo($id, null, array("ress", "simple_scores", "att", "def"));

					if(isset($angreifer[$name]) && isset($angreifer[$name][$id]))
						$anzahl = $angreifer[$name][$id];
					else
						$anzahl = 0;

					$diff = $old_anzahl-$anzahl;
					$truemmerfeld[0] += $item_info["ress"][0]*$diff*.4;
					$truemmerfeld[1] += $item_info["ress"][1]*$diff*.4;
					$truemmerfeld[2] += $item_info["ress"][2]*$diff*.4;
					$truemmerfeld[3] += $item_info["ress"][3]*$diff*.4;
					$angreifer_punkte[$name] += $item_info["simple_scores"]*$diff;

					$staerke = $item_info["att"]*$anzahl;
					$schild = $item_info["def"]*$anzahl;

					if($anzahl > 0)
					{
						$nachrichten_text .= "\t\t\t<tr>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($anzahl)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($staerke)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($schild)."</td>\n";
						$nachrichten_text .= "\t\t\t</tr>\n";
						$one = true;
					}

					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}
				if(!$one)
				{
					$nachrichten_text .= "\t\t\t<tr class=\"keine\">\n";
					$nachrichten_text .= "\t\t\t\t<td colspan=\"4\">%9\$s</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";
				}
				else
				{
					$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($this_ges_anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($this_ges_staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($this_ges_schild)."</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";
				}

				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}

			$nachrichten_text .= "\t\t</tbody>\n";

			if(count($angreifer_anfang) > 1)
			{
				$nachrichten_text .= "\t\t<tfoot>\n";
				$nachrichten_text .= "\t\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
				$nachrichten_text .= "\t\t</tfoot>\n";
			}
			$nachrichten_text .= "\t</table>\n";

			$nachrichten_text .= "\t<h3>%8\$s</h3>\n";

			$nachrichten_text .= "\t<table>\n";
			$nachrichten_text .= "\t\t<thead>\n";
			$nachrichten_text .= "\t\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">%2\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">%3\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">%4\$s</th>\n";
			$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">%5\$s</th>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</thead>\n";
			$nachrichten_text .= "\t\t<tbody>\n";

			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($verteidiger_anfang as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"verteidiger-name\">".htmlspecialchars($name)."</span></th>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";

				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				$verteidiger_punkte[$name] = 0;
				$verteidiger_ress[$name] = array(0, 0, 0, 0);
				$one = false;
				foreach($flotten as $id=>$anzahl_old)
				{
					$item_info = $users_verteidiger[$name]->getItemInfo($id, null, array("type", "ress", "simple_scores", "att", "def"));

					if(isset($verteidiger[$name]) && isset($verteidiger[$name][$id]))
						$anzahl = $verteidiger[$name][$id];
					else $anzahl = 0;

					$diff = $anzahl_old-$anzahl;
					if($item_info["type"] == "schiffe")
					{
						$truemmerfeld[0] += $item_info["ress"][0]*$diff*.4;
						$truemmerfeld[1] += $item_info["ress"][1]*$diff*.4;
						$truemmerfeld[2] += $item_info["ress"][2]*$diff*.4;
						$truemmerfeld[3] += $item_info["ress"][3]*$diff*.4;
					}
					elseif($item_info["type"] == "verteidigung")
					{
						$verteidiger_ress[$name][0] += $item_info["ress"][0]*.2;
						$verteidiger_ress[$name][1] += $item_info["ress"][1]*.2;
						$verteidiger_ress[$name][2] += $item_info["ress"][2]*.2;
						$verteidiger_ress[$name][3] += $item_info["ress"][3]*.2;
					}

					$verteidiger_punkte[$name] += $diff*$item_info["simple_scores"];

					$staerke = $item_info["att"]*$anzahl;
					$schild = $item_info["def"]*$anzahl;

					if($anzahl > 0)
					{
						$nachrichten_text .= "\t\t\t<tr>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($anzahl)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($staerke)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($schild)."</td>\n";
						$nachrichten_text .= "\t\t\t</tr>\n";
						$one = true;
					}

					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}

				if(!$one)
				{
					$nachrichten_text .= "\t\t\t<tr class=\"keine\">\n";
					$nachrichten_text .= "\t\t\t\t<td colspan=\"4\">%9\$s</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";
				}
				else
				{
					$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($this_ges_anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($this_ges_staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($this_ges_schild)."</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";
				}

				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}

			$nachrichten_text .= "\t\t</tbody>\n";

			if(count($verteidiger) > 1)
			{
				$nachrichten_text .= "\t\t<tfoot>\n";
				$nachrichten_text .= "\t\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".F::ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".F::ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".F::ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
				$nachrichten_text .= "\t\t</tfoot>\n";
			}
			$nachrichten_text .= "\t</table>\n";

			if(array_sum($truemmerfeld) > 0)
			{
				# Truemmerfeld

				$truemmerfeld[0] = round($truemmerfeld[0]);
				$truemmerfeld[1] = round($truemmerfeld[1]);
				$truemmerfeld[2] = round($truemmerfeld[2]);
				$truemmerfeld[3] = round($truemmerfeld[3]);
			}

			# Kampferfahrung
			$angreifer_new_erfahrung = array_sum($verteidiger_punkte)/100;
			$verteidiger_new_erfahrung = array_sum($angreifer_punkte)/100;
			$nachrichten_text .= "\t<ul class=\"kampferfahrung\">\n";
			$nachrichten_text .= "\t\t<li class=\"c-angreifer\">%16\$s</li>\n";
			$nachrichten_text .= "\t\t<li class=\"c-verteidiger\">%17\$s</li>\n";
			$nachrichten_text .= "\t</ul>\n";
			foreach($users_angreifer as $user)
				$user->addScores(6, $angreifer_new_erfahrung);
			foreach($users_verteidiger as $user)
				$user->addScores(6, $verteidiger_new_erfahrung);

			$nachrichten = array();
			foreach($users_all as $n=>$u)
			{
				$nachrichten[$n] = $u->_i(sprintf($nachrichten_text,
					h($u->ngettext("Flotten des Angreifers", "Flotten der Angreifer", count($angreifer_anfang))),
					h($u->_("Schiffstyp")),
					h($u->_("Anzahl")),
					h($u->_("Gesamtstärke")),
					h($u->_("Gesamtschild")),
					h($u->_("Genauere Informationen anzeigen")),
					h($u->_("Gesamt")),
					h($u->ngettext("Flotten des Verteidigers", "Flotten der Verteidiger", count($verteidiger_anfang))),
					h($u->_("Keine.")),
					h(sprintf($u->_("%s sind stärker ausgebildet als %s und %s."), $u->ngettext("Die Sensoren des Angreifers", "Die Sensoren der Angreifer", count($angreifer_anfang)), $u->ngettext("die des Verteidigers", "die der Verteidiger", count($verteidiger_anfang)), $u->ngettext("ermöglichen es ihm, den Erstschlag auszuführen", "ermöglichen es ihnen, den Erstschlag auszuführen", count($angreifer_anfang)))),
					h(sprintf($u->_("%s sind %s nicht überlegen, weshalb %s."), $u->ngettext("Die Sensoren des Angreifers", "Die Sensoren der Angreifer", count($angreifer_anfang)), $u->ngettext("denen des Verteidigers", "denen der Verteidiger", count($verteidiger_anfang)), $u->ngettext("letzterer den Erstschlag ausführt", "letztere den Erstschlag ausführen", count($verteidiger_anfang)))),
					implode("", $nachrichten_runden[$n]),
					h($u->ngettext("Der Kampf ist vorüber. Gewinner ist der Verteidiger.", "Der Kampf ist vorüber. Gewinner sind die Verteidiger.", count($verteidiger_anfang))),
					h($u->ngettext("Der Kampf ist vorüber. Gewinner ist der Angreifer.", "Der Kampf ist vorüber. Gewinner sind die Angreifer.", count($angreifer_anfang))),
					h($u->_("Der Kampf ist vorüber. Er endet unentschieden.")),
					h(sprintf($u->ngettext("Der Angreifer hat %s Kampferfahrungspunkte gesammelt.", "Die Angreifer haben %s Kampferfahrungspunkte gesammelt.", count($angreifer_anfang)), F::ths($angreifer_new_erfahrung))),
					h(sprintf($u->ngettext("Der Verteidiger hat %s Kampferfahrungspunkte gesammelt.", "Die Verteidiger haben %s Kampferfahrungspunkte gesammelt.", count($verteidiger_anfang)), F::ths($verteidiger_new_erfahrung)))
				));

				$nachrichten[$n] .= "\t<ul class=\"angreifer-punkte\">\n";
				foreach($angreifer_anfang as $a=>$i)
				{
					$p = 0;
					if(isset($angreifer_punkte[$a])) $p = $angreifer_punkte[$a];
					$nachrichten[$n] .= "\t\t<li>".sprintf(h($u->_("Der Angreifer %s hat %s Punkte verloren.")), "<span class=\"koords\">".htmlspecialchars($a)."</span>", F::ths($p))."</li>\n";
				}
				$nachrichten[$n] .= "\t</ul>\n";
				$nachrichten[$n] .= "\t<ul class=\"verteidiger-punkte\">\n";
				foreach($verteidiger_anfang as $v=>$i)
				{
					$p = 0;
					if(isset($verteidiger_punkte[$v])) $p = $verteidiger_punkte[$v];
					$nachrichten[$n] .= "\t\t<li>".sprintf(h($u->_("Der Verteidiger %s hat %s Punkte verloren.")), "<span class=\"koords\">".htmlspecialchars($v)."</span>", F::ths($p))."</li>\n";
				}
				$nachrichten[$n] .= "\t</ul>\n";

				if(array_sum($truemmerfeld) > 0)
				{
					$nachrichten[$n] .= "\t<p>\n";
					$nachrichten[$n] .= "\t\t".h(sprintf($u->_("Folgende Trümmer zerstörter Schiffe sind durch dem Kampf in die Umlaufbahn des Planeten gelangt: %s."), sprintf($u->_("%s %s, %s %s, %s %s und %s %s"), F::ths($truemmerfeld[0]), $u->_("[ress_0]"), F::ths($truemmerfeld[1]), $u->_("[ress_1]"), F::ths($truemmerfeld[2]), $u->_("[ress_2]"), F::ths($truemmerfeld[3]), $u->_("[ress_3]"))))."\n";
					$nachrichten[$n] .= "\t</p>\n";
				}
			}

			if(array_sum($truemmerfeld) > 0)
				$target->addTruemmerfeld($truemmerfeld);

			$angreifer_return = array();
			foreach($angreifer as $username=>$fleet)
				$angreifer_return[$username] = array($fleet, $angreifer_param[$username][1]);

			# Rohstoffe stehlen
			if($winner == 1)
			{
				# Angreifer haben gewonnen

				# Maximal die Haelfte der vorhandenen Rohstoffe
				$ress_max = $target_user->getRess();
				$ress_max[0] = floor($ress_max[0]*.5);
				$ress_max[1] = floor($ress_max[1]*.5);
				$ress_max[2] = floor($ress_max[2]*.5);
				$ress_max[3] = floor($ress_max[3]*.5);
				$ress_max[4] = floor($ress_max[4]*.5);
				unset($ress_max[5]);
				$ress_max_total = array_sum($ress_max);

				# Transportkapazitaeten der Angreifer
				$trans = array();
				$trans_total = 0;
				foreach($angreifer as $username=>$fleet)
				{
					$trans[$username] = -array_sum($angreifer_return[$username][1]);
					$this_user = Classes::User($username);
					foreach($fleet as $id=>$count)
					{
						$item_info = $this_user->getItemInfo($id, "schiffe", array("trans"));
						$this_trans = $item_info["trans"][0]*$count;
						$trans[$username] += $this_trans;
						$trans_total += $this_trans;
					}
				}

				if($trans_total < $ress_max_total)
				{
					$f = $trans_total/$ress_max_total;
					$ress_max[0] = floor($ress_max[0]*$f);
					$ress_max[1] = floor($ress_max[1]*$f);
					$ress_max[2] = floor($ress_max[2]*$f);
					$ress_max[3] = floor($ress_max[3]*$f);
					$ress_max[4] = floor($ress_max[4]*$f);
					$ress_max_total = array_sum($ress_max);
					$diff = $trans_total-$ress_max_total;
					$diff2 = $diff%5;
					$each = $diff-$diff2;
					$ress_max[0] += $each;
					$ress_max[1] += $each;
					$ress_max[2] += $each;
					$ress_max[3] += $each;
					$ress_max[4] += $each;
					switch($diff)
					{
						case 4: $ress_max[3]++;
						case 3: $ress_max[2]++;
						case 2: $ress_max[1]++;
						case 1: $ress_max[0]++;
					}
				}

				foreach($trans as $username=>$cap)
				{
					$rtrans = array();
					$p = $cap/$trans_total;
					$angreifer_return[$username][1][0] += ($rtrans[0] = floor($ress_max[0]*$p));
					$angreifer_return[$username][1][1] += ($rtrans[1] = floor($ress_max[1]*$p));
					$angreifer_return[$username][1][2] += ($rtrans[2] = floor($ress_max[2]*$p));
					$angreifer_return[$username][1][3] += ($rtrans[3] = floor($ress_max[3]*$p));
					$angreifer_return[$username][1][4] += ($rtrans[4] = floor($ress_max[4]*$p));

					$nachrichten[$username] .= "\n\t<p class=\"rohstoffe-erbeutet selbst\">".h(sprintf($users_all[$username]->_("Sie haben %s %s, %s %s, %s %s, %s %s und %s %s erbeutet."), F::ths($rtrans[0]), $users_all[$username]->_("[ress_0]"), F::ths($rtrans[1]), $users_all[$username]->_("[ress_1]"), F::ths($rtrans[2]), $users_all[$username]->_("[ress_2]"), F::ths($rtrans[3]), $users_all[$username]->_("[ress_3]"), F::ths($rtrans[4]), $users_all[$username]->_("[ress_4]")))."</p>\n";
				}

				$target_user->subtractRess($ress_max, false);

				foreach($users_all as $username=>$u)
				{
					if(isset($angreifer2[$username])) continue;
					$nachrichten[$username] .= "\n\t<p class=\"rohstoffe-erbeutet andere\">".h(sprintf($u->_("Die überlebenden Angreifer haben %s %s, %s %s, %s %s, %s %s und %s %s erbeutet."), F::ths($ress_max[0]), $u->_("[ress_0]"), F::ths($ress_max[1]), $u->_("[ress_1]"), F::ths($ress_max[2]), $u->_("[ress_2]"), F::ths($ress_max[3]), $u->_("[ress_3]"), F::ths($ress_max[4]), $u->_("[ress_4]")))."</p>\n";
				}
			}

			if(isset($verteidiger_ress[$target_owner]))
				$nachrichten[$target_owner] .= "\n\t<p class=\"verteidigung-wiederverwertung\">".h(sprintf($target_user->_("Durch Wiederverwertung konnten folgende Rohstoffe aus den Trümmern der zerstörten Verteidigungsanlagen wiederhergestellt werden: %s"), sprintf($target_user->_("%s %s, %s %s, %s %s und %s %s"), F::ths($verteidiger_ress[$target_owner][0]), $target_user->_("[ress_0]"), F::ths($verteidiger_ress[$target_owner][1]), $target_user->_("[ress_1]"), F::ths($verteidiger_ress[$target_owner][2]), $target_user->_("[ress_2]"), F::ths($verteidiger_ress[$target_owner][3]), $target_user->_("[ress_3]"))))."</p>\n";

			# Nachrichten zustellen
			foreach($nachrichten as $username=>$text)
			{
				$message = Classes::Message(Message::create());
				$message->text($text);
				$message->subject(sprintf($users_all[$username]->_("Kampf auf %s"), $planet));
				$message->html(true);
				$message->addUser($username, 1);
			}

			foreach($users_verteidiger as $username=>$user_obj)
			{
				foreach($verteidiger_anfang[$username] as $id=>$count)
				{
					$count2 = 0;
					if(isset($verteidiger[$username]) && isset($verteidiger[$username][$id]))
						$count2 = $verteidiger[$username][$id];
					if($count2 != $count)
					{
						if($username == $target_owner)
							$target_user->changeItemLevel($id, $count2-$count);
						else $target_user->subForeignShips($username, $id, $count-$count2);
					}
				}
			}

			return $angreifer_return;
		}
	}
