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

	import("Dataset/Dataset");
	import("Dataset/Classes");
	import("Dataset/Galaxy");
	import("Dataset/Item");

	class FleetDatabase extends SQLite
	{
		protected $tables = array("fleets" => array("fleet_id PRIMARY KEY", "targets", "users", "start INT", "finished" ));

		function getField($fleet_id, $field_name)
		{
			if(!$this->fleetExists($fleet_id)) return false;

			$result = $this->singleQuery("SELECT ".$field_name." FROM fleets WHERE fleet_id = ".$this->escape($fleet_id)." LIMIT 1;");
			if($result) $result = $result[$field_name];
			return $result;
		}

		function setField($fleet_id, $field_name, $field_value)
		{
			if(!$this->fleetExists($fleet_id)) return false;

			return $this->query("UPDATE fleets SET ".$field_name." = ".$this->escape($field_value)." WHERE fleet_id = ".$this->escape($fleet_id).";");
		}

		function getNewName()
		{
			do $name = str_replace('.', '-', microtime(true));
				while($this->fleetExists($name));
			return $name;
		}

		function createNewFleet($fleet_id)
		{
			$this->query("INSERT INTO fleets ( fleet_id ) VALUES ( ".$this->escape($fleet_id)." );");
		}

		function deleteFleet($fleet_id)
		{
			$this->query("DELETE FROM fleets WHERE fleet_id = ".$this->escape($fleet_id).";");
		}

		function fleetExists($fleet_id)
		{
			return ($this->singleField("SELECT COUNT(*) FROM fleets WHERE fleet_id = ".$this->escape($fleet_id)." LIMIT 1;") > 0);
		}

		function fleetsCount()
		{
			return ($this->singleField("SELECT COUNT(*) FROM fleets;"));
		}
	}

/*
  * Format von $raw:
  * [ Ziele, Benutzer, Startzeit, Vergangene Ziele ]
  * Ziele: ( (string) Koordinaten => [ Fleet::$TYPE_*, (boolean) Rückflug? ] )
  * Benutzer: ( (string) Benutzername => [ ( Schiffs-ID => Anzahl ), (string) Start-Koordinaten, (float) Geschwindigkeitsfaktor, Mitgenommene Rohstoffe, Handel, (float) Verbrauchtes Tritium (für die Flugerfahrungspunkte) ] )
  * Vergangene Ziele: Ziele
  * Mitgenommene Rohstoffe: [ ( Rohstoffnummer => Menge ), ( Roboter-ID => Anzahl ), Überschüssiges Tritium ]
  * Handel: [ ( Rohstoffnummer => Menge ), ( Roboter-ID => Anzahl ), Rohstoffe abliefern? ]
*/

	class Fleet
	{
		protected static $database = false;
		protected $status = false;
		protected $raw = false;
		protected $changed = false;
		protected $name = false;

		static $TYPE_BESIEDELN = 1;
		static $TYPE_SAMMELN = 2;
		static $TYPE_ANGRIFF = 3;
		static $TYPE_TRANSPORT = 4;
		static $TYPE_SPIONIEREN = 5;
		static $TYPE_STATIONIEREN = 6;

		static protected function databaseInstance()
		{
			if(!self::$database)
				self::$database = new FleetDatabase();
		}

		function __construct($name=false)
		{
			self::databaseInstance();

			if($name === false) $name = self::$database->getNewName();

			$this->name = $name;
			if(self::$database->fleetExists($this->name))
			{
				$this->status = 1;
				$this->read();
			}
		}

		function getStatus()
		{
			return $this->status;
		}

		function getName()
		{
			return $this->name;
		}

		function readonly()
		{
			return false;
		}

		function __destruct()
		{
			$this->write();
		}

		function create()
		{
			self::$database->createNewFleet($this->name);
			$this->raw = array(array(), array(), false, array());
			$this->changed = true;
			$this->status = 1;
			return true;
		}

		function read($force=false)
		{
			if(!$this->status) return false;
			if($this->changed && !$force) $this->write();

			$this->raw = array(array(), array(), false, array());

			$targets = self::$database->getField($this->name, "targets");
			$targets = (strlen($targets) > 0 ? explode("\n", $targets) : array());
			foreach($targets as $t)
			{
				$t = explode("\t", $t);
				if(count($t) < 3) continue;
				$this->raw[0][$t[0]] = array($t[1], ($t[2] == true));
			}

			$users = self::$database->getField($this->name, "users");
			$users = (strlen($users) > 0 ? explode("\n", $users) : array());
			foreach($users as $u)
			{
				$u = explode("\t", $u);
				if(count($u) < 10) continue;
				$this->raw[1][$u[0]] = array(
					decode_item_list($u[1]),
					$u[2],
					(float)$u[3],
					array(
						decode_ress_list($u[4]),
						decode_item_list($u[5]),
						(float)$u[6]
					),
					array(
						decode_ress_list($u[7]),
						decode_item_list($u[8]),
						(float)$u[10]
					),
					(float)$u[9]
				);
			}

			$start = self::$database->getField($this->name, "start");
			if($start)
				$this->raw[2] = $start;

			$finished = self::$database->getField($this->name, "finished");
			$finished = (strlen($finished) > 0 ? explode("\n", $finished) : array());
			foreach($finished as $f)
			{
				$f = explode("\t", $f);
				if(count($f) < 3) continue;
				$this->raw[3][$f[0]] = array($f[1], ($f[2] == true));
			}
		}

		function write($force=false)
		{
			if(!$this->status) return false;

			self::databaseInstance();

			if(!$this->started() && !$force)
			{
				self::$database->deleteFleet($this->name);
				return true;
			}

			if(!$this->changed && !$force)
				return true;

			$targets = array();
			foreach($this->raw[0] as $k=>$v)
				$targets[] = $k."\t".$v[0]."\t".$v[1];
			self::$database->setField($this->name, "targets", implode("\n", $targets));

			$users = array();
			foreach($this->raw[1] as $k=>$v)
				$users[] = $k."\t".encode_item_list($v[0])."\t".$v[1]."\t".$v[2]."\t".encode_ress_list($v[3][0])."\t".encode_item_list($v[3][1])."\t".$v[3][2]."\t".encode_ress_list($v[4][0])."\t".encode_item_list($v[4][1])."\t".$v[5]."\t".$v[4][2];
			self::$database->setField($this->name, "users", implode("\n", $users));

			self::$database->setField($this->name, "start", $this->raw[2]);

			$finished = array();
			foreach($this->raw[3] as $k=>$v)
				$finished[] = $k."\t".$v[0]."\t".$v[1];
			self::$database->setField($this->name, "finished", implode("\n", $finished));

			return true;
		}

		function destroy()
		{
			if(!$this->status) return false;
			self::databaseInstance();

			foreach($this->getVisibleUsers() as $user)
			{
				$user_obj = Classes::User($user);
				$user_obj->unsetFleet($this->getName());
			}

			self::$database->deleteFleet($this->name);
			$this->status = 0;
			$this->changed = false;
			return true;
		}

		static function fleetExists($fleet)
		{
			self::databaseInstance();
			return self::$database->fleetExists($fleet);
		}

		function moveTime($time_diff)
		{
			if(!$this->status) return false;

			$this->raw[2] += $time_diff;
			$this->changed = true;

			$this->createNextEvent();

			return true;
		}

		function getTargetsList()
		{
			if(!$this->status) return false;

			$targets = array_keys($this->raw[0]);
			foreach($targets as $i=>$target)
			{
				if(substr($target, -1) == 'T') $targets[$i] = substr($target, 0, -1);
			}
			return $targets;
		}

		function getTargetsInformation()
		{
			if(!$this->status) return false;

			return $this->raw[0];
		}

		function getOldTargetsInformation()
		{
			if(!$this->status) return false;

			return $this->raw[3];
		}

		function getOldTargetsList()
		{
			if(!$this->status) return false;

			$targets = array_keys($this->raw[3]);
			foreach($targets as $i=>$target)
			{
				if(substr($target, -1) == 'T') $targets[$i] = substr($target, 0, -1);
			}
			return $targets;
		}

		function getNeededSlots($user=null)
		{
			if(!$this->status) return false;

			$users = array_keys($this->raw[1]);
			$first_user = array_shift($users);
			if($user === null) $user = $first_user;
			if(!isset($this->raw[1][$user])) return false;

			if($user != $first_user) return 1;

			$slots = 0;
			foreach($this->raw[0] as $k=>$v)
			{
				if(!$v[1]) $slots++;
			}
			foreach($this->raw[3] as $k=>$v)
			{
				if(!$v[1]) $slots++;
			}
			if($slots < 1) $slots = 1;
			return $slots;
		}

		function addTarget($pos, $type, $back)
		{
			if(!$this->status) return false;

			if($type == 2 && !$back) $pos .= 'T';

			if(isset($this->raw[0][$pos])) return false;

			$this->raw[0][$pos] = array($type, $back);

			# Eintragen in die Flottenliste des Benutzers
			if($this->started() && $pos[strlen($pos)-1] != 'T')
			{
				$pos_a = explode(":", $pos2);
				$galaxy_obj = Classes::Galaxy($pos_a[0]);
				$owner = $galaxy_obj->getPlanetOwner($pos_a[1], $pos_a[2]);
				if($owner)
				{
					$user = Classes::User($owner);
					if($user->getStatus())
						$user->addFleet($this->getName());
				}
			}

			$this->changed = true;
			return true;
		}

		function userExists($user)
		{
			if(!$this->status) return false;

			return isset($this->raw[1][$user]);
		}

		function getCurrentType()
		{
			if(!$this->status) return false;

			$keys = array_keys($this->raw[0]);
			return $this->raw[0][array_shift($keys)][0];
		}

		function getCurrentTarget()
		{
			if(!$this->status) return false;

			$keys = array_keys($this->raw[0]);
			$t = array_shift($keys);
			if(substr($t, -1) == 'T') $t = substr($t, 0, -1);
			return $t;
		}

		function getLastTarget($user=false)
		{
			if(!$this->status) return false;

			$keys = array_keys($this->raw[1]);
			$first_user = array_shift($keys);
			if($user === false) $user = $first_user;
			if($user == $first_user && count($this->raw[3]) > 0)
			{
				$keys = array_keys($this->raw[3]);
				$l = array_pop($keys);
				if(substr($l, -1) == 'T') $l = substr($l, 0, -1);
				return $l;
			}
			else
			{
				if(!isset($this->raw[1][$user])) return false;

				return $this->raw[1][$user][1];
			}
		}

		function getNextArrival()
		{
			if(!$this->status) return false;

			if($this->started()) $start_time = $this->raw[2];
			else $start_time = time();
			$users = array_keys($this->raw[1]);
			$duration = $this->calcTime(array_shift($users), $this->getLastTarget(), $this->getCurrentTarget());
			return $start_time+$duration;
		}

		function getDepartingTime()
		{
			if(!$this->status) return false;
			return $this->raw[2];
		}

		function isFlyingBack()
		{
			if(!$this->status) return false;

			$keys = array_keys($this->raw[0]);
			return (bool) $this->raw[0][array_shift($keys)][1];
		}

		function addFleet($id, $count, $user)
		{
			if(!$this->status) return false;

			$count = (int) $count;
			if($count < 0) return false;

			if(!isset($this->raw[1][$user])) return false;

			$keys = array_keys($this->raw[1]);
			$first = !array_search($user, $keys);
			if(isset($this->raw[1][$user][0][$id])) $this->raw[1][$user][0][$id] += $count;
			else $this->raw[1][$user][0][$id] = $count;

			if(!$first)
				$this->raw[1][$user][2] = $this->calcTime($user, $this->raw[1][$user][1], $this->getCurrentTarget())/($this->getNextArrival()-time());

			$this->changed = true;
			return true;
		}

		function addUser($user, $from, $factor=1)
		{
			if(!$this->status) return false;

			if(isset($this->raw[1][$user])) return false;

			if(count($this->raw[1]) > 0)
				$factor = null;

			if($factor <= 0) $factor = 0.01;

			$this->raw[1][$user] = array(
				array(), # Flotten
				$from, # Startkoordinaten
				$factor, # Geschwindigkeitsfaktor
				array(array(0, 0, 0, 0, 0), array(), 0), # Mitgenommene Rohstoffe
				array(array(0, 0, 0, 0, 0), array(), true), # Handel
				0 # Verbrauchtes Tritium
			);

			# Eintragen in die Flottenliste des Benutzers
			if($this->started())
			{
				$user = Classes::User($user);
				if($user->getStatus())
					$user->addFleet($this->getName());
			}

			$this->changed = true;
			return true;
		}

		function getTransportCapacity($user)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			$trans = array(0, 0);
			$user_object = Classes::User($user);
			foreach($this->raw[1][$user][0] as $id=>$count)
			{
				$item_info = $user_object->getItemInfo($id, 'schiffe');
				$trans[0] += $item_info['trans'][0]*$count;
				$trans[1] += $item_info['trans'][1]*$count;
			}
			return $trans;
		}

		function addTransport($user, $ress=false, $robs=false)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);
			$max_ress -= array_sum($this->raw[1][$user][3][0]);
			$max_robs -= array_sum($this->raw[1][$user][3][1]);
			if($ress)
			{
				$ress = fit_to_max($ress, $max_ress);
				$this->raw[1][$user][3][0][0] += $ress[0];
				$this->raw[1][$user][3][0][1] += $ress[1];
				$this->raw[1][$user][3][0][2] += $ress[2];
				$this->raw[1][$user][3][0][3] += $ress[3];
				$this->raw[1][$user][3][0][4] += $ress[4];
			}

			if($robs)
			{
				$robs = fit_to_max($robs, $max_robs);
				foreach($robs as $i=>$rob)
				{
					if(!isset($this->raw[1][$user][3][1][$i]))
						$this->raw[1][$user][3][1][$i] = $rob;
					else
						$this->raw[1][$user][3][1][$i] += $rob;
				}
			}

			$this->changed = true;
			return true;
		}

		function addHandel($user, $ress=false, $robs=false)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);
			$max_ress -= array_sum($this->raw[1][$user][4][0]);
			$max_robs -= array_sum($this->raw[1][$user][4][1]);
			if(!$this->raw[1][$user][4][2])
			{
				$transport = $this->getTransport($user);
				$max_ress -= array_sum($transport[0]);
				$max_robs -= array_sum($transport[1]);
			}
			if($ress)
			{
				$ress = fit_to_max($ress, $max_ress);
				$this->raw[1][$user][4][0][0] += $ress[0];
				$this->raw[1][$user][4][0][1] += $ress[1];
				$this->raw[1][$user][4][0][2] += $ress[2];
				$this->raw[1][$user][4][0][3] += $ress[3];
				$this->raw[1][$user][4][0][4] += $ress[4];
			}

			if($robs)
			{
				$robs = fit_to_max($robs, $max_robs);
				foreach($robs as $i=>$rob)
				{
					if(!isset($this->raw[1][$user][4][1][$i]))
						$this->raw[1][$user][4][1][$i] = $rob;
					else
						$this->raw[1][$user][4][1][$i] += $rob;
				}
			}

			$this->changed = true;
			return true;
		}

		function setHandel($user, $ress=false, $robs=false, $give=null)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			if($give !== null)
				$this->raw[1][$user][4][2] = $give;

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);

			if(!$this->raw[1][$user][4][2])
			{
				$transport = $this->getTransport($user);
				$max_ress -= array_sum($transport[0]);
				$max_robs -= array_sum($transport[1]);
			}

			if($give !== null && !$give)
			{
				$this->raw[1][$user][4][0] = fit_to_max($this->raw[1][$user][4][0], $max_ress);
				$this->raw[1][$user][4][1] = fit_to_max($this->raw[1][$user][4][1], $max_robs);
			}

			if($ress !== false && is_array($ress))
			{
				if(!isset($ress[0])) $ress[0] = 0;
				if(!isset($ress[1])) $ress[1] = 0;
				if(!isset($ress[2])) $ress[2] = 0;
				if(!isset($ress[3])) $ress[3] = 0;
				if(!isset($ress[4])) $ress[4] = 0;

				$ress = fit_to_max($ress, $max_ress);

				$this->raw[1][$user][4][0] = $ress;
			}
			if($robs !== false && is_array($robs))
			{
				$robs = fit_to_max($robs, $max_robs);
				$this->raw[1][$user][4][1] = $robs;
			}

			$this->changed = true;
			return true;
		}

		function getTransport($user)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			return $this->raw[1][$user][3];
		}

		function getHandel($user)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			return $this->raw[1][$user][4];
		}

		function calcNeededTritium($user)
		{
			if(!$this->status || $this->started()) return false;

			$users = array_keys($this->raw[1]);
			$user_key = array_search($user, $users);

			if($user_key === false) return false;

			if($user_key)
				return $this->getTritium($user, $this->raw[1][$user][1], $this->getCurrentTarget())*2;
			else
			{
				$tritium = 0;
				$old_target = $this->raw[1][$user][1];
				foreach($this->raw[0] as $target=>$info)
				{
					if(substr($target, -1) == 'T') $target = substr($target, 0, -1);
					$tritium += $this->getTritium($user, $old_target, $target);
				}
				if($old_target != $this->raw[1][$user][1])
					$tritium += $this->getTritium($user, $old_target, $this->raw[1][$user][1]);
				return $tritium;
			}
		}

		function getTritium($user, $from, $to, $factor=true)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			$mass = 0;
			$user_obj = Classes::User($user);
			foreach($this->raw[1][$user][0] as $id=>$count)
			{
				$item_info = $user_obj->getItemInfo($id, 'schiffe');
				$mass += $item_info['mass']*$count;
			}

			$global_factors = get_global_factors();
			$add_factor = 1;
			if($factor) $add_factor = $this->raw[1][$user][2];

			return $add_factor*$global_factors['cost']*self::getDistance($from, $to)*$mass/1000000;
		}

		function getScores($user, $from, $to)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			return $this->getTritium($user, $from, $to)/1000;
		}

		function getTime($target)
		{
			if(!$this->status) return false;
			if(count($this->raw[1]) <= 0) return false;

			$keys = array_keys($this->raw[1]);
			$user = array_shift($keys);

			$targets = array_keys($this->raw[0]);
			$max = array_search($target, $targets);
			if($max === false) $max = array_search($target."T", $targets);
			if($max === false) return false;

			$time = 0;
			$last = $this->getLastTarget($user);
			for($i=0; $i<=$max; $i++)
			{
				if(substr($targets[$i], -1) == "T") $targets[$i] = substr($targets[$i], 0, -1);
				$time += $this->calcTime($user, $last, $targets[$i]);
				$last = $targets[$i];
			}

			return $time;
		}

		function calcTime($user, $from, $to, $use_min_time=true)
		{
			if(!$this->status || !isset($this->raw[1][$user]) || count($this->raw[1][$user]) <= 0) return false;

			return self::calcFleetTime($user, $from, $to, $this->raw[1][$user][0], $use_min_time)/$this->raw[1][$user][2];
		}

		static function calcFleetTime($user, $from, $to, $fleet, $use_min_time=true)
		{
			$speeds = array();
			$user_obj = Classes::User($user);
			foreach($fleet as $id=>$count)
			{
				$item_info = $user_obj->getItemInfo($id, 'schiffe');
				$speeds[] = $item_info['speed'];
			}
			$speed = min($speeds)/1000000;

			$time = sqrt(self::getDistance($from, $to)/$speed)*2;

			$global_factors = get_global_factors();
			$time *= $global_factors['time'];

			if($use_min_time && $time < global_setting("MIN_BUILDING_TIME"))
				$time = global_setting("MIN_BUILDING_TIME");

			return $time;
		}

		function callBack($user, $immediately=false)
		{
			if(!$this->status || !$this->started() || !isset($this->raw[1][$user]) || $this->isFlyingBack()) return false;

			$is_first_user = (array_search($user, array_keys($this->raw[1])) == 0);
			$start = $this->raw[1][$user][1];
			$keys = array_keys($this->raw[0]);
			$to = $to_t = array_shift($keys);
			if(substr($to, -1) == 'T') $to = substr($to, 0, -1);
			if(count($this->raw[3]) > 0)
			{
				$keys = array_keys($this->raw[3]);
				$from = $from_t = array_pop($keys);
				if(substr($from, -1) == 'T') $from = substr($from, 0, -1);
			}
			else $from = $from_t = $start;

			if($to_t == $start) return false;

			if($from_t == $start) $time1 = 0;
			else $time1 = $this->calcTime($user, $from, $start);
			$time2 = $this->calcTime($user, $to, $start);
			$time3 = $this->calcTime($user, $from, $to);
			if($immediately) $progress = 0;
			else
			{
				$progress = (time()-$this->raw[2])/$time3;
				if($progress > 1) $progress = 1;
			}

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
			$prev_t = $to;
			foreach($keys as $next_t)
			{
				if(substr($next_t, -1) == 'T') $next_t = substr($next_t, 0, -1);
				$back_tritium += $this->getTritium($user, $prev_t, $next_t);
			}
			if($from_t == $start) $tritium1 = 0;
			else $tritium1 = $this->getTritium($user, $from, $start);
			$tritium2 = $this->getTritium($user, $to, $start);

			$tritium1_sq = pow($tritium1, 2);
			$tritium3_sq = pow($tritium3, 2);
			$needed_back_tritium = round(sqrt($tritium1_sq - $progress*$tritium1_sq + $progress*pow($tritium2,2) - $progress*$tritium3_sq + pow($progress,2)*$tritium3_sq));
			$back_tritium -= $needed_back_tritium;

			# Mit Erfahrungspunkten herumhantieren
			$this->raw[1][$user][5] += $needed_back_tritium;
			$this->raw[1][$user][5] -= $tritium2; # Wird bei der Ankunft sowieso wieder hinzugezaehlt

			# Eventuellen Handel zurueckerstatten
			if(array_sum($this->raw[1][$user][4][0]) > 0 || array_sum($this->raw[1][$user][4][1]) > 0)
			{
				$target_split = explode(':', $to);
				$galaxy_obj = Classes::Galaxy($target_split[0]);
				$target_owner = $galaxy_obj->getPlanetOwner($target_split[1], $target_split[2]);
				$target_user_obj = Classes::User($target_owner);
				$target_user_obj->setActivePlanet($target_user_obj->getPlanetByPos($to));
				$target_user_obj->addRess($this->raw[1][$user][4][0]);
				foreach($this->raw[1][$user][4][1] as $id=>$count)
					$target_user_obj->changeItemLevel($id, $count, 'roboter');
				$this->raw[1][$user][4] = array(array(0,0,0,0,0), array(), true);
			}

			$new_raw = array(
				array($start => array($this->raw[0][$to_t][0], 1)),
				array($user => $this->raw[1][$user]),
				(time()+$back_time)-$time2,
				array($to_t => $this->raw[0][$to_t])
			);
			if(array_search($user, array_keys($this->raw[1])) == 0)
				$new_raw[3] = array_merge($this->raw[3], $new_raw[3]);
			$new_raw[1][$user][3][2] += $back_tritium;

			unset($this->raw[1]);
			$this->changed = true;

			if(count($this->raw[1]) <= 0)
			{
				unset($this->raw[1][$user]);

				# Aus der Eventdatei entfernen
				$event_obj = Classes::EventFile();
				$event_obj->removeCanceledFleet($this->getName());

				$this->destroy();
			}
			elseif(!$is_first_user)
			{
				# Weitere Ziele entfernen
				$remaining_users = array_keys($this->raw[1]);
				$first_user = array_shift($remaining_users);
				$targets = array_keys($this->raw[0]);
				array_shift($targets);
				$check_users = array();
				foreach($targets as $target)
				{
					if(substr($target, -1) != "T")
					{
						$target_arr = explode(":", $target);
						$galaxy_obj = Classes::Galaxy($target_arr[0]);
						$check_users[] = $galaxy_obj->getPlanetOwner($target_arr[1], $target_arr[2]);
					}
					unset($this->raw[0][$target]);
				}
				if($this->getCurrentType() != 6)
					$fleet_obj->addTarget($this->getLastTarget($first_user), $this->getCurrentType(), true);
				unset($this->raw[1][$user]);
				$visible_users = $this->getVisibleUsers();
				foreach($check_users as $u)
				{
					if($u && !in_array($u, $visible_users))
					{
						$u_obj = Classes::User($u);
						$u_obj->unsetFleet($this->getName());
					}
				}
			}

			$new = Classes::Fleet();
			$new->create();
			$new->setRaw($new_raw);
			$new->createNextEvent();

			$user_obj = Classes::User($user);
			if(!in_array($user, $this->getVisibleUsers()))
				$user_obj->unsetFleet($this->getName());
			$user_obj->addFleet($new->getName());

			if(count($this->raw[1]) <= 0)
				return true;

			$this->changed = true;
			return true;
		}

		function setRaw($raw)
		{
			if(!$this->status) return false;

			$this->raw = $raw;
			$this->changed = true;
			return true;
		}

		function factor($user, $factor=false)
		{
			if(!$this->status || $this->started() || !isset($this->raw[1][$user])) return false;

			if(!$factor) return $this->raw[1][$user][2];

			$this->raw[1][$user][2] = $factor;
			$this->changed = true;
			return true;
		}

		function getFleetList($user)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			return $this->raw[1][$user][0];
		}

		function getUsersList()
		{
			if(!$this->status) return false;

			return array_keys($this->raw[1]);
		}

		function getFleetOwner()
		{
			if(!$this->status) return false;

			$users = array_keys($this->raw[1]);
			return array_shift($users);
		}

		function from($user)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			return $this->raw[1][$user][1];
		}

		function isATarget($target)
		{
			if(!$this->status) return false;

			return (isset($this->raw[0][$target]) || isset($this->raw[0][$target.'T']));
		}

		function renameUser($old_name, $new_name)
		{
			if(!$this->status) return false;

			if(!isset($this->raw[1][$old_name])) return true;
			if($old_name == $new_name) return 2;

			$this->raw[1][$new_name] = $this->raw[1][$old_name];
			unset($this->raw[1][$old_name]);
			$this->changed = true;
			return true;
		}

		function start()
		{
			if(!$this->status || $this->started()) return false;

			if(count($this->raw[1]) <= 0 || count($this->raw[0]) <= 0) return false;

			$keys = array_keys($this->raw[1]);
			$user = array_shift($keys);
			if(array_sum($this->raw[1][$user][0]) <= 0) return false;

			# Geschwindigkeitsfaktoren der anderen Teilnehmer abstimmen
			$koords = array_keys($this->raw[0]);
			$koords = $koords_t = array_shift($koords);
			if(substr($koords, -1) == 'T') $koords = substr($koords, 0, -1);
			$time = $this->calcTime($user, $this->raw[1][$user][1], $koords);
			if(count($keys) > 1)
			{
				foreach($keys as $key)
				{
					$this_time = calcTime($key, $this->raw[1][$key][1], $koords);
					$this->raw[1][$key][2] = $this_time/$time;
				}
			}

			$this->raw[2] = time();

			# In Eventdatei eintragen
			$this->createNextEvent();

			# Bei den Benutzern eintragen
			$users = array_keys($this->raw[1]);
			foreach(array_keys($this->raw[0]) as $koords)
			{
				if($koords[strlen($koords)-1] == 'T') continue;
				$koords_a = explode(":", $koords);
				$galaxy_obj = Classes::Galaxy($koords_a[0]);
				$owner = $galaxy_obj->getPlanetOwner($koords_a[1], $koords_a[2]);
				if($owner)
					$users[] = $owner;
			}
			foreach(array_unique($users) as $user)
			{
				$user_obj = Classes::User($user);
				if(!$user_obj->getStatus()) continue;
				$user_obj->addFleet($this->getName());
			}

			$this->changed = true;
			return true;
		}

		function started()
		{
			if(!$this->status) return false;
			return ($this->raw[2] !== false);
		}

		static function getDistance($start, $target)
		{
			import("Dataset/Galaxy");

			if(substr($start, -1) == "T") $start = substr($start, 0, -1);
			if(substr($target, -1) == "T") $target = substr($target, 0, -1);
			$this_pos = explode(':', $start);
			$that_pos = explode(':', $target);

			# Entfernung berechnen
			if($this_pos[0] == $that_pos[0]) # Selbe Galaxie
			{
				if($this_pos[1] == $that_pos[1]) # Selbes System
				{
					if($this_pos[2] == $that_pos[2]) # Selber Planet
						$distance = 0.001;
					else # Anderer Planet
						$distance = 0.1*diff($this_pos[2], $that_pos[2]);
				}
				else
				{
					# Anderes System

					$this_x_value = $this_pos[1]-($this_pos[1]%100);
					$this_y_value = $this_pos[1]-$this_x_value;
					$this_y_value -= $this_y_value%10;
					$this_z_value = $this_pos[1]-$this_x_value-$this_y_value;
					$this_x_value /= 100;
					$this_y_value /= 10;

					$that_x_value = $that_pos[1]-($that_pos[1]%100);
					$that_y_value = $that_pos[1]-$that_x_value;
					$that_y_value -= $that_y_value%10;
					$that_z_value = $that_pos[1]-$that_x_value-$that_y_value;
					$that_x_value /= 100;
					$that_y_value /= 10;

					$x_diff = diff($this_x_value, $that_x_value);
					$y_diff = diff($this_y_value, $that_y_value);
					$z_diff = diff($this_z_value, $that_z_value);

					$distance = sqrt(pow($x_diff, 2)+pow($y_diff, 2)+pow($z_diff, 2));
				}
			}
			else # Andere Galaxie
			{
				$galaxy_count = Galaxy::getGalaxiesCount();

				$galaxy_diff_1 = diff($this_pos[0], $that_pos[0]);
				$galaxy_diff_2 = diff($this_pos[0]+$galaxy_count, $that_pos[0]);
				$galaxy_diff_3 = diff($this_pos[0], $that_pos[0]+$galaxy_count);
				$galaxy_diff = min($galaxy_diff_1, $galaxy_diff_2, $galaxy_diff_3);

				$radius = (30*$galaxy_count)/(2*pi());
				$distance = sqrt(2*pow($radius, 2)-2*$radius*$radius*cos(($galaxy_diff/$galaxy_count)*2*pi()));
			}

			$distance = round($distance*1000);

			return $distance;
		}

		/**
		  * Liefert ein Array mit den Benutzern zurueck, die die Flotte sehen koennen.
		*/

		function getVisibleUsers()
		{
			if(!$this->status) return array();

			$users = array_keys($this->raw[1]);
			foreach(array_keys($this->raw[0]) as $target)
			{
				if(substr($target, -1) == "T") continue;
				$target = explode(":", $target);
				$galaxy_obj = Classes::Galaxy($target[0]);
				$owner = $galaxy_obj->getPlanetOwner($target[1], $target[2]);
				if($owner && !in_array($owner, $users))
					$users[] = $owner;
			}
			return $users;
		}

		function arriveAtNextTarget()
		{
			if($this->status != 1) return false;

			global $types_message_types;

			$keys = array_keys($this->raw[0]);
			$next_target = $next_target_nt = array_shift($keys);
			if(substr($next_target_nt, -1) == 'T') $next_target_nt = substr($next_target_nt, 0, -1);
			$keys2 = array_keys($this->raw[1]);
			$first_user = array_shift($keys2);

			$type = $this->raw[0][$next_target][0];
			$back = $this->raw[0][$next_target][1];

			$besiedeln = false;
			if($type == 1 && !$back)
			{
				# Besiedeln
				$target = explode(':', $next_target_nt);
				$target_galaxy = Classes::Galaxy($target[0]);
				$target_owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);

				if($target_owner)
				{
					# Planet ist bereits besiedelt
					$target_owner_obj = Classes::User($target_owner);
					$message = Classes::Message();
					if($target_owner_obj->getStatus() && $message->create())
					{
						$message->text(sprintf($target_owner_obj->_("Ihre Flotte erreicht den Planeten %s und will mit der Besiedelung anfangen. Jedoch ist der Planet bereits vom Spieler %s besetzt, und Ihre Flotte macht sich auf den Rückweg."), vsprintf($target_owner_obj->_("%d:%d:%d"), explode(":", $next_target_nt)), $target_owner));
						$message->subject(sprintf($target_owner_obj->_("Besiedelung von %s fehlgeschlagen"), vsprintf($target_owner_obj->_("%d:%d:%d"), explode(":", $next_target_nt))));
						$message->addUser($first_user, 5);
					}
				}
				else
				{
					$start_user = Classes::User($first_user);
					if(!$start_user->checkPlanetCount())
					{
						# Planetenlimit erreicht
						$message = Classes::Message();
						if($message->create())
						{
							$message->subject(sprintf($start_user->_('Besiedelung von %s fehlgeschlagen'), vsprintf($start_user->_("%d:%d:%d"), explode(":", $next_target_nt))));
							$message->text(sprintf($start_user->_("Ihre Flotte erreicht den Planeten %s und will mit der Besiedelung anfangen. Als Sie jedoch Ihren Zentralcomputer um Bestätigung für die Besiedelung bittet, kommt dieser durcheinander, da Sie schon so viele Planeten haben und er nicht so viele gleichzeitig kontrollieren kann, und schickt in Panik Ihrer Flotte das Signal zum Rückflug."), vsprintf($start_user->_("%d:%d:%d"), explode(":", $next_target_nt))));
							$message->addUser($first_user, 5);
						}
					}
					else $besiedeln = true;
				}
			}

			if($type != 6 && !$back && !$besiedeln)
			{
				# Nicht stationieren: Flotte fliegt weiter

				$further = true;

				$target = explode(':', $next_target_nt);
				$target_galaxy = Classes::Galaxy($target[0], false);
				if(!$target_galaxy->getStatus()) return false;
				$target_owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);
				if($target_owner)
				{
					$target_user = Classes::User($target_owner);
					if(!$target_user->getStatus()) return false;
					$target_user->setActivePlanet($target_user->getPlanetByPos($next_target_nt));
				}
				else $target_user = false;

				if(($type == 3 || $type == 4) && !$target_owner)
				{
					# Angriff und Transport nur bei besiedelten Planeten
					# moeglich.

					foreach(array_keys($this->raw[1]) as $username)
					{
						$username_obj = Classes::User($username);
						$message_obj = Classes::Message();
						if($username_obj->getStatus() && $message_obj->create())
						{
							$message_obj->subject(sprintf($username_obj->_("%s unbesiedelt"), vsprintf($username_obj->_("%d:%d:%d"), explode(":", $next_target_nt))));
							$message_obj->text(sprintf($username_obj->_("Ihre Flotte erreicht den Planeten %s und will ihren Auftrag ausführen. Jedoch wurde der Planet zwischenzeitlich verlassen und Ihre Flotte macht sich auf den weiteren Weg."), vsprintf($username_obj->_("%d:%d:%d"), explode(":", $next_target_nt))));
							$message_obj->addUser($username, $types_message_types[$type]);
						}
					}
				}
				else
				{
					switch($type)
					{
						case 2: # Sammeln
						{
							$ress_max = truemmerfeld::get($target[0], $target[1], $target[2]);
							$ress_max_total = array_sum($ress_max);

							# Transportkapazitaeten
							$trans = array();
							$trans_total = 0;
							foreach($this->raw[1] as $username=>$info)
							{
								$this_trans_used = array_sum($info[3][0]);
								$this_trans_tf = 0;
								$this_trans_total = 0;

								$this_user = Classes::User($username);
								foreach($info[0] as $id=>$count)
								{
									$item_info = $this_user->getItemInfo($id, 'schiffe');
									$this_trans = $item_info['trans'][0]*$count;
									$this_trans_total += $this_trans;
									if(in_array(2, $item_info['types']))
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

								$this->raw[1][$user][3][0][0] += $rtrans[0];
								$this->raw[1][$user][3][0][1] += $rtrans[1];
								$this->raw[1][$user][3][0][2] += $rtrans[2];
								$this->raw[1][$user][3][0][3] += $rtrans[3];

								$got_ress[$username] = $rtrans;
							}

							# Aus dem Truemmerfeld abziehen
							truemmerfeld::sub($target[0], $target[1], $target[2], $ress_max[0], $ress_max[1], $ress_max[2], $ress_max[3]);

							$tr_verbl = truemmerfeld::get($target[0], $target[1], $target[2]);

							# Nachrichten versenden
							foreach($got_ress as $username=>$rtrans)
							{
								$user_obj = Classes::User($username);
								$message = Classes::Message();
								if(!$message->create()) continue;
								$message->subject(sprintf($user_obj->_("Abbau auf %s"), $next_target_nt));
								$message->text(sprintf("<div class=\"nachricht-sammeln\">\n\t<p>".sprintf(h($user_obj->_("Ihre Flotte erreicht das Trümmerfeld auf %s und belädt die %s Tonnen Sammlerkapazität mit folgenden Rohstoffen: %s.")), htmlspecialchars($next_target_nt), ths($trans_total), h(sprintf($user_obj->_("%1\$s %5\$s, %2\$s %6\$s, %3\$s %7\$s und %4s %8\$s"), ths($rtrans[0]), ths($rtrans[1]), ths($rtrans[2]), ths($rtrans[3]), $user_obj->_("[ress_0]"), $user_obj->_("[ress_1]"), $user_obj->_("[ress_2]"), $user_obj->_("[ress_3]"))))."</p>\n\t<h3 class=\"strong\">".h(_("Verbleibende Rohstoffe im Trümmerfeld"))."</h3>\n".$user_obj->_i(format_ress($tr_verbl, 1, false, false, true, false, "ress-block"))."</div>"));
								$message->addUser($username, 4);
								$message->html(true);
							}
							break;
						}
						case 3: # Angriff
						{
							$angreifer = array();
							$verteidiger = array();
							foreach($this->raw[1] as $username=>$info)
								$angreifer[$username] = array($info[0], $info[3][0]);

							$angreifer2 = Fleet::battle($next_target_nt, $angreifer);

							foreach($angreifer as $username=>$fl)
							{
								if(!isset($angreifer2[$username]))
								{
									# Flotten des Angreifers wurden zerstoert
									if($username == $first_user) $further = false;
									else unset($this->raw[1][$username]);
								}
								else
								{
									$this->raw[1][$username][0] = $angreifer2[$username][0];
									$this->raw[1][$username][3][0] = $angreifer2[$username][1];
								}
								$user_obj = Classes::User($username);
								$user_obj->recalcHighscores(false, false, false, true, false);
							}

							break;
						}
						case 4: # Transport
						{
							$message_text = array(
								$target_owner => sprintf($target_user->_("Ein Transport erreicht Ihren Planeten %s. Folgende Spieler liefern Güter ab:"), sprintf($target_user->_("„%s“ (%s)"), $target_user->planetName(), $next_target_nt))."\n"
							);

							# Rohstoffe abliefern, Handel
							$handel = array();
							$make_handel_message = false;
							foreach($this->raw[1] as $username=>$data)
							{
								$user_obj = Classes::User($username);
								$write_this_username = ($username != $target_owner);
								if($write_this_username) $message_text[$username] = sprintf($user_obj->_("Ihre Flotte erreicht den Planeten %s und liefert folgende Güter ab:"), sprintf($target_user->_("„%s“ (%s, Eigentümer: %s)"), $target_user->planetName(), $next_target_nt, $target_owner))."\n";
								$message_text[$target_owner] .= $username.": ";
								if($data[4][2])
								{
									$target_user->addRess($data[3][0]);
									$this->raw[1][$username][3][0] = array(0,0,0,0,0);
									if($write_this_username) $message_text[$username] .= sprintf($user_obj->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $user_obj->_("[ress_0]"), ths($data[3][0][0], true), $user_obj->_("[ress_1]"), ths($data[3][0][1], true), $user_obj->_("[ress_2]"), ths($data[3][0][2], true), $user_obj->_("[ress_3]"), ths($data[3][0][3], true), $user_obj->_("[ress_4]"), ths($data[3][0][4], true));
									$message_text[$target_owner] .= sprintf($target_user->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $target_user->_("[ress_0]"), ths($data[3][0][0], true), $target_user->_("[ress_1]"), ths($data[3][0][1], true), $target_user->_("[ress_2]"), ths($data[3][0][2], true), $target_user->_("[ress_3]"), ths($data[3][0][3], true), $target_user->_("[ress_4]"), ths($data[3][0][4], true));

									if($target_owner == $username && array_sum($data[3][1]) > 0)
									{
										$target_user->setLanguage();
										$items_string = Item::makeItemsString($data[3][1], false);
										$target_user->restoreLanguage();
										if($write_this_username) $message_text[$username] .= "\n".$items_string;
										$message_text[$target_owner] .= $target_user->_("; ").$items_string;
										foreach($data[3][1] as $id=>$anzahl)
											$target_user->changeItemLevel($id, $anzahl, 'roboter');
										$this->raw[1][$username][3][1] = array();
									}
								}
								else
								{
									if($write_this_username) $message_text[$username] .= $user_obj->_("Keine.");
									$message_text[$target_owner] .= $target_user->_("Keine.");
								}

								if($write_this_username) $message_text[$username] .= "\n";
								$message_text[$target_owner] .= "\n";
								if(array_sum($data[4][0])+array_sum($data[4][1]) > 0)
								{
									$handel[$username] = $data[4];
									foreach($data[4][0] as $k=>$v)
									{
										if(!isset($this->raw[1][$username][3][0][$k]))
											$this->raw[1][$username][3][0][$k] = 0;
										$this->raw[1][$username][3][0][$k] += $v;
									}
									foreach($data[4][1] as $k=>$v)
									{
										if(!isset($this->raw[1][$username][3][1][$k]))
											$this->raw[1][$username][3][1][$k] = 0;
										$this->raw[1][$username][3][1][$k] += $v;
									}
									$this->raw[1][$username][4] = array(array(0,0,0,0,0),array(),true);
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
										$message_text[$username] .= sprintf($user_obj->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $user_obj->_("[ress_0]"), ths($h[0][0], true), $user_obj->_("[ress_1]"), ths($h[0][1], true), $user_obj->_("[ress_2]"), ths($h[0][2], true), $user_obj->_("[ress_3]"), ths($h[0][3], true), $user_obj->_("[ress_4]"), ths($h[0][4], true));
									}
									$message_text[$target_owner] .= sprintf($target_user->_("%s: %s, %s: %s, %s: %s, %s: %s, %s: %s"), $target_user->_("[ress_0]"), ths($h[0][0], true), $target_user->_("[ress_1]"), ths($h[0][1], true), $target_user->_("[ress_2]"), ths($h[0][2], true), $target_user->_("[ress_3]"), ths($h[0][3], true), $target_user->_("[ress_4]"), ths($h[0][4], true));
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
								$receive = $user_obj->checkSetting('receive');
								if(isset($this->raw[1][$username]) && isset($receive[3]) && isset($receive[3][0]) && !$receive[3][0])
									continue;

								$message_obj = Classes::Message();
								if($message_obj->create())
								{
									if($username == $target_owner && !isset($this->raw[1][$username]))
									{
										$message_obj->subject(sprintf($user_obj->_("Ankunft eines fremden Transportes auf %s"), $next_target_nt));
										$users = array_keys($this->raw[1]);
										$message_obj->from(array_shift($users));
									}
									else
									{
										$message_obj->subject(sprintf($user_obj->_("Ankunft Ihres Transportes auf %s"), $next_target_nt));
										$message_obj->from($target_owner);
									}
									$message_obj->text($text);
									$message_obj->addUser($username, $types_message_types[$type]);
								}
							}

							break;
						}
						case 5: # Spionage
						{
							# Spionieren

							$destroyed = array();

							if(!$target_owner)
							{
								# Zielplanet ist nicht besiedelt
								$message_text = "<div class=\"nachricht-spionage\">\n";
								$message_text .= "\t<h3 class=\"strong\">%1\$s</h3>\n";
								$message_text .= "\t<div id=\"spionage-planet\">\n";
								$message_text .= "\t\t<h4 class=\"strong\">%2\$s</h4>\n";
								$message_text .= "\t\t<dl class=\"planet_".$target_galaxy->getPlanetClass($target[1], $target[2])."\">\n";
								$message_text .= "\t\t\t<dt class=\"c-felder\">%3\$s</dt>\n";
								$message_text .= "\t\t\t<dd class=\"c-felder\">".ths($target_galaxy->getPlanetSize($target[1], $target[2]))."</dd>\n";
								$message_text .= "\t\t</dl>\n";
								$message_text .= "\t</div>\n";

								$message_text .= "\t<p class=\"besiedeln\">\n";
								$message_text .= "\t\t<a href=\"flotten.php?action=besiedeln&amp;action_galaxy=".htmlspecialchars(urlencode($target[0]))."&amp;action_system=".htmlspecialchars(urlencode($target[1]))."&amp;action_planet=".htmlspecialchars(urlencode($target[2]))."\" onclick=\"return fast_action(this, 'besiedeln', ".$target[0].", ".$target[1].", ".$target[2].");\" title=\"%4\$s\">%5\$s</a>\n";
								$message_text .= "\t</p>\n";
								$message_text .= "</div>";
							}
							else
							{
								# Zielplanet ist besiedelt

								$users = array_keys($this->raw[1]);
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
									$owner_v7 = $target_user->getItemLevel("V7", "verteidigung");
									foreach($this->raw[1] as $username=>$info)
									{
										if(isset($info[0]["S5"]))
										{
											$destroyed[$username] = $owner_v7;
											$this->raw[1][$username][0]["S5"] -= $owner_v7;
											if($this->raw[1][$username][0]["S5"] <= 0)
											{
												$destroyed[$username] += $this->raw[1][$username][0]["S5"];
												unset($this->raw[1][$username][0]["S5"]);
											}
										}
									}

									# Spionagetechnikdifferenz ausrechnen
									$owner_level = $target_user->getItemLevel('F1', 'forschung');
									$others_level = 0;
									foreach($users as $username)
									{
										if(isset($this->raw[1][$username][0]['S5']))
											$others_level += $this->raw[1][$username][0]['S5'];
									}
									$others_level -= count($users);
									if($others_level < 0) $others_level = 0;

									$max_f1 = 0;
									foreach($users as $username)
									{
										$user = Classes::User($username);
										$this_f1 = $user->getItemLevel('F1', 'forschung');
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
								$message_text .= "\t\t<dl class=\"planet_".$target_galaxy->getPlanetClass($target[1], $target[2])."\">\n";
								$message_text .= "\t\t\t<dt class=\"c-felder\">%3\$s</dt>\n";
								$message_text .= "\t\t\t<dd class=\"c-felder\">".ths($target_user->getTotalFields())."</dd>\n";
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
										foreach($target_user->getItemsList('roboter') as $id)
										{
											if($target_user->getItemLevel($id, 'roboter') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'roboter');
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"anzahl\">(".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 4: # Forschung zeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-forschung\">\n";
										$next .= "\t\t<h4 class=\"strong\">%8\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('forschung') as $id)
										{
											if($target_user->getItemLevel($id, 'forschung') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'forschung');
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"stufe\">(Level&nbsp;".ths($item_info['level']).")</span></li>\n";
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
										foreach($target_user->getItemsList('schiffe') as $id)
										{
											$count = $target_user->getItemLevel($id, 'schiffe');
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
										{
											$item_info = $target_user->getItemInfo($id, 'schiffe');
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"anzahl\">(".ths($count).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										$next .= "\t<div id=\"spionage-verteidigung\">\n";
										$next .= "\t\t<h4 class=\"strong\">%10\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('verteidigung') as $id)
										{
											if($target_user->getItemLevel($id, 'verteidigung') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'verteidigung');
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"anzahl\">(".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 2: # Gebaeude anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-gebaeude\">\n";
										$next .= "\t\t<h4 class=\"strong\">%11\$s</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('gebaeude') as $id)
										{
											if($target_user->getItemLevel($id, 'gebaeude') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'gebaeude');
											$next .= "\t\t\t<li>[item_".$id."] <span class=\"stufe\">(Stufe&nbsp;".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 1: # Rohstoffe anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-rohstoffe\">\n";
										$next .= "\t\t<h4 class=\"strong\">%12\$s</h4>\n";
										$next .= format_ress($target_user->getRess(), 2, true, false, true, null, "ress-block");
										$next .= "\t</div>\n";
										unset($next);
								}
								$message_text .= implode('', array_reverse($message_text2));
								$message_text .= "</div>\n";

								# Benachrichtigung an den Planetenbesitzer
								$message = Classes::Message();
								if($message->create())
								{
									$message->subject(sprintf($target_user->_('Fremde Flotte auf dem Planeten %s'), $next_target_nt));
									$first_user = array_shift($users);
									$from_pos_str = $this->raw[1][$first_user][1];
									$from_pos = explode(':', $from_pos_str);
									$from_galaxy = Classes::Galaxy($from_pos[0]);
									$first_user_text = sprintf($target_user->_("Eine fremde Flotte vom Planeten %s wurde von Ihrem Planeten %s aus bei der Spionage gesichtet."), sprintf($target_user->_("„%s“ (%s, Eigentümer: %s)"), $from_galaxy->getPlanetName($from_pos[1], $from_pos[2]), $from_pos_str, $first_user), sprintf($target_user->_("„%s“ (%s)"), $target_user->planetName(), $next_target_nt));
									if(array_sum($destroyed) > 0)
										$first_user_text .= "\n\n".sprintf($target_user->ngettext("Durch Spionageabwehr haben Sie eine Spionagesonde zerstört.", "Durch Spionageabwehr haben Sie %s Spionagesonden zerstört.", array_sum($destroyed)), ths(array_sum($destroyed)));
									$message->text($first_user_text);
									$message->from($first_user);
									$message->addUser($target_owner, $types_message_types[$type]);
								}
							}

							foreach($this->raw[1] as $username=>$data)
							{
								$u = Classes::User($username);
								$message = Classes::Message();
								if($message->create())
								{
									if(isset($data[0]["S5"]) && $data[0]["S5"] > 0)
									{
										$message->subject(sprintf($u->_("Spionage des Planeten %s"), $next_target_nt));
										$text = $u->_i(sprintf($message_text,
											h(sprintf($u->_("Spionagebericht des Planeten %s"), $next_target_nt)),
											h($u->_("Planet")),
											h($u->_("Felder")),
											h($u->_("Schicken Sie ein Besiedelungsschiff zu diesem Planeten")),
											h($u->_("Besiedeln")),
											sprintf(h($u->_("Spionagebericht des Planeten %s")), sprintf(h($u->_("„%s“ (%s, Eigentümer: %s)")), htmlspecialchars($target_galaxy->getPlanetName($target[1], $target[2])), htmlspecialchars($next_target_nt), htmlspecialchars($target_owner))),
											h($u->_("Roboter")),
											h($u->_("Forschung")),
											h($u->_("Schiffe")),
											h($u->_("Verteidigung")),
											h($u->_("Gebäude")),
											h($u->_("Rohstoffe"))
										));

										if(isset($destroyed[$username]) && $destroyed[$username] > 0)
											$text .= "<hr />\n<p>".h(sprintf($u->ngettext("Die Spionageabwehr des Planeten hat eine Ihrer Spionagesonden zerstört.", "Die Spionageabwehr des Planeten hat %s Ihrer Spionagesonden zerstört.", $destroyed[$username]), ths($destroyed[$username])))."</p>\n";

										$message->text($text);
										$message->html(true);
									}
									elseif(isset($destroyed[$username]) && $destroyed[$username] > 0)
									{
										$message->subject(sprintf($u->_("Spionage des Planeten %s abgewehrt"), $next_target_nt));
										$message->text(sprintf($u->ngettext("Ihre Flotte erreichte den Planeten %s, um diesen auszuspionieren.\n\nJedoch zerstörte die Spionageabwehr Ihre Spionagesonde, bevor diese einen Bericht übertragen konnte.", "Ihre Flotte erreichte den Planeten %s, um diesen auszuspionieren.\n\nJedoch zerstörte die Spionageabwehr des Planeten alle Ihrer %s Spionagesonden, bevor diese einen Bericht übertragen konnten.", $destroyed[$username]), sprintf($u->_("„%s“ (%s, Eigentümer: %s)"), $target_user->planetName(), vsprintf($u->_("%d:%d:%d"), $target_user->getPos()), $target_user->getName()), ths($destroyed[$username])));
									}
									else
									{
										$message->subject(sprintf($u->_("Spionage des Planeten %s fehlgeschlagen"), $next_target_nt));
										$message->text(sprintf($u->_("Ihre Flotte erreichte den Planeten %s, um diesen auszuspionieren.\n\nLeider waren keine Spionagesonden unter den Schiffen der Flotte, weshalb die Spionage nicht möglich war."), sprintf($u->_("„%s“ (%s, Eigentümer: %s)"), $target_user->planetName(), vsprintf($u->_("%d:%d:%d"), $target_user->getPos()))));
									}

									if($target_owner)
										$message->from($target_owner);
									$message->addUser($username, $types_message_types[$type]);
								}

								if(array_sum($data[0]) < 1)
								{
									if($username == $first_user)
										$further = false;
									else
										unset($this->raw[1][$username]);
								}

								if(isset($destroyed[$username]) && $destroyed[$username] > 0)
									$u->recalcHighscores(false, false, false, true, false);
							}
						}
					}
				}

				# Weiterfliegen

				$users = array_keys($this->raw[1]);
				$first_user = array_shift($users);

				if($further)
				{
					$this->raw[3][$next_target] = array_shift($this->raw[0]);
					$this->raw[2] = time();
					$this->createNextEvent();
				}

				# Vom Empfaenger entfernen
				if($target_owner && !in_array($target_owner, $this->getVisibleUsers()))
					$target_user->unsetFleet($this->getName());

				$this->changed = true;

				# Flugerfahrung
				$last_targets = array_keys($this->raw[3]);
				if(count($last_targets) <= 0) $last_target = false;
				else
				{
					$last_target = array_pop($last_targets);
					if(substr($last_target, -1) == 'T') $last_target = substr($last_target, 0, -1);
				}

				# Flugerfahrung fuer den ersten Benutzer
				$this_last_target = (($last_target === false) ? $this->raw[1][$first_user][1] : $last_target);
				$this->raw[1][$first_user][5] += $this->getTritium($first_user, $this_last_target, $next_target);

				foreach($users as $user)
				{
					$user_obj = Classes::User($user);
					$new_fleet = Classes::Fleet();

					# Flugerfahrung
					$this_last_target = (($last_target === false) ? $this->raw[1][$user][1] : $last_target);
					$this->raw[1][$user][5] += $this->getTritium($user, $this_last_target, $next_target);

					if($new_fleet->create())
					{
						$new_fleet->setRaw(array(
							array($this->raw[1][$user][1] => array($type, true)),
							array($user => $this->raw[1][$user]),
							false,
							array($next_target => array($type, false))
						));
						$new_fleet->start();
						$user_obj = Classes::User($user);
						$user_obj->addFleet($new_fleet->getName());
					}
					unset($this->raw[1][$user]);

					if(!in_array($user, $this->getVisibleUsers()))
						$user_obj->unsetFleet($this->getName());
				}

				if(!$further) $this->destroy();
			}
			else
			{
				# Stationieren

				$target = explode(':', $next_target_nt);
				$target_galaxy = Classes::Galaxy($target[0], $besiedeln);
				if(($besiedeln && $target_galaxy->getStatus() != 1) || !$target_galaxy->getStatus())
					return false;

				$owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);

				if($besiedeln || $owner == $first_user && !$back)
				{
					# Ueberschuessiges Tritium
					$this->raw[1][$first_user][3][2] += $this->getTritium($first_user, $this->raw[1][$first_user][1], $next_target_nt);
				}

				if($besiedeln)
				{
					$user_obj = Classes::User($first_user);
					if($user_obj->registerPlanet($next_target_nt) === false)
						return false;
					if(isset($this->raw[1][$first_user][0]['S6']))
					{
						$this->raw[1][$first_user][0]['S6']--;
						$active_planet = $user_obj->getActivePlanet();
						$user_obj->setActivePlanet($user_obj->getPlanetByPos($next_target_nt));
						$item_info = $user_obj->getItemInfo('S6', 'schiffe');
						$besiedelung_ress = $item_info['ress'];
						$besiedelung_ress[0] *= .4;
						$besiedelung_ress[1] *= .4;
						$besiedelung_ress[2] *= .4;
						$besiedelung_ress[3] *= .4;
						$user_obj->addRess($besiedelung_ress);
					}
					$owner = $first_user;
				}


				if(!$owner)
				{
					$this->destroy();
					return false;
				}

				$owner_obj = Classes::User($owner);
				if(!$owner_obj->getStatus()) return false;

				$planet_index = $owner_obj->getPlanetByPos($next_target_nt);
				if($planet_index === false)
					return false;

				$owner_obj->setActivePlanet($planet_index);

				$ress = array(0, 0, 0, 0, 0);
				$robs = array();
				$schiffe_own = array();
				$schiffe_other = array();

				# Flugerfahrung
				$last_targets = array_keys($this->raw[3]);
				if(count($last_targets) <= 0) $last_target = false;
				else
				{
					$last_target = array_pop($last_targets);
					if(substr($last_target, -1) == 'T') $last_target = substr($last_target, 0, -1);
				}

				foreach($this->raw[1] as $username=>$move_info)
				{
					$ress[0] += $move_info[3][0][0];
					$ress[1] += $move_info[3][0][1];
					$ress[2] += $move_info[3][0][2];
					$ress[3] += $move_info[3][0][3];
					$ress[4] += $move_info[3][0][4];

					foreach($move_info[3][1] as $id=>$count)
					{
						if(isset($robs[$id])) $robs[$id] += $count;
						else $robs[$id] = $count;
					}

					if($username == $owner)
					{
						# Stationieren
						foreach($move_info[0] as $id=>$count)
						{
							if(isset($schiffe_own[$id])) $schiffe_own[$id] += $count;
							else $schiffe_own[$id] = $count;
						}

						if($username != $first_user)
							$this->raw[1][$username][3][2] += $this->getTritium($username, $this->raw[1][$username][1], $next_target_nt);
					}
					else
					{
						# Fremdstationieren
						if(!isset($schiffe_other[$username]))
							$schiffe_other[$username] = array();
						foreach($move_info[0] as $id=>$count)
						{
							if(isset($schiffe_other[$username][$id])) $schiffe_other[$username][$id] += $count;
							else $schiffe_other[$username][$id] = $count;
						}
					}

					# Flugerfahrung
					$this_last_target = (($last_target === false) ? $this->raw[1][$username][1] : $last_target);
					$this->raw[1][$username][5] += $this->getTritium($username, $this_last_target, $next_target);

					$user_obj = Classes::User($username);
					$user_obj->addScores(5, $this->raw[1][$username][5]/1000);
				}

				foreach($schiffe_own as $id=>$anzahl)
					$owner_obj->changeItemLevel($id, $anzahl);
				foreach($schiffe_other as $user=>$schiffe)
					$owner_obj->addForeignFleet($user, $schiffe, $move_info[1], $move_info[2]);
				foreach($robs as $id=>$anzahl)
					$owner_obj->changeItemLevel($id, $anzahl, 'roboter');

				$ress[4] += $this->raw[1][$first_user][3][2];
				$owner_obj->addRess($ress);

				$message_users = array();
				foreach($this->raw[1] as $username=>$move_info)
				{
					$message_user_obj = Classes::User($username);
					$receive = $message_user_obj->checkSetting('receive');
					if(!isset($receive[$types_message_types[$this->raw[0][$next_target][0]]][$this->raw[0][$next_target][1]]) || $receive[$types_message_types[$this->raw[0][$next_target][0]]][$this->raw[0][$next_target][1]])
					{
						# Will Nachricht erhalten
						$message_users[] = $username;
					}
				}

				if($owner && !isset($this->raw[1][$owner]))
					$message_users[] = $owner;

				foreach($message_users as $name)
				{
					$user_obj = Classes::User($name);
					$message_text = "";
					if($besiedeln)
					{
						$message_text .= sprintf($user_obj->_("Ihre Flotte erreicht den Planeten %s und beginnt mit seiner Besiedelung."), $next_target_nt);
						if(isset($besiedelung_ress))
							$message_text .= " ".sprintf($user_obj->_(" Durch den Abbau eines Besiedelungsschiffs konnten folgende Rohstoffe wiederhergestellt werden: %s."), sprintf($user_obj->_("%s %s, %s %s, %s %s, %s %s"), ths($besiedelung_ress[0], true), $user_obj->_("[ress_0]"), ths($besiedelung_ress[1], true), $user_obj->_("[ress_1]"), ths($besiedelung_ress[2], true), $user_obj->_("[ress_2]"), ths($besiedelung_ress[3], true), $user_obj->_("[ress_3]")));
						$message_text .= "\n";
					}
					else
						$message_text .= sprintf($user_obj->_("Eine Flotte erreicht den Planeten %s."), sprintf($user_obj->_("„%s“ (%s, Eigentümer: %s)"), $owner_obj->planetName(), $owner_obj->getPosString(), $owner_obj->getName()))."\n";

					if(array_sum($schiffe_own) > 0)
					{
						$user_obj->setLanguage();
						$message_text .= sprintf($user_obj->_("Die Flotte besteht aus folgenden Schiffen: %s"), Item::makeItemsString($schiffe_own, false))."\n";
						$user_obj->restoreLanguage();
					}

					if(array_sum_r($schiffe_other) > 0)
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
					$message_text .= sprintf($user_obj->_("%s %s, %s %s, %s %s, %s %s, %s %s."), ths($ress[0], true), $user_obj->_("[ress_0]"), ths($ress[1], true), $user_obj->_("[ress_1]"), ths($ress[2], true), $user_obj->_("[ress_2]"), ths($ress[3], true), $user_obj->_("[ress_3]"), ths($ress[4], true), $user_obj->_("[ress_4]"));
					if(array_sum($robs) > 0)
					{
						$user_obj->setLanguage();
						$message_text .= "\n".Item::makeItemsString($robs, false)."\n";
						$user_obj->restoreLanguage();
					}

					if($this->raw[1][$first_user][3][2] > 0)
						$message_text .= "\n\n".sprintf($user_obj->_("Folgender überschüssiger Treibstoff wird abgeliefert: %s."), sprintf($user_obj->_("%s %s"), ths($this->raw[1][$first_user][3][2], true), $user_obj->_("[ress_4]")));

					$message_obj = Classes::Message();
					if($message_obj->create())
					{
						$message_obj->text($message_text);
						if($besiedeln)
							$message_obj->subject(sprintf($user_obj->_("Besiedelung von %s"), $next_target_nt));
						else
							$message_obj->subject(sprintf($user_obj->_("Stationierung auf %s"), $owner_obj->getPosString()));

						# Bei Fremdstationierung Nachrichtenabsender eintragen
						if($owner && !isset($this->raw[1][$owner]))
							$message_obj->from($first_user);

						$message_obj->addUser($name, $types_message_types[$this->raw[0][$next_target][0]]);
					}
				}

				$this->destroy();
			}

			return true;
		}

		function createNextEvent()
		{
			if(!$this->status) return false;

			$event_obj = Classes::EventFile();
			return $event_obj->addNewFleet($this->getNextArrival(), $this->getName());
		}

		/**
		* Laesst auf dem Planeten $planet die Flotten $angreifer angreifen.
		* @param $planet (string) Galaxie ':' System ':' Planet
		* @param $angreifer_param ( Benutzername => [ ( Item-ID => Anzahl ), ( Rohstoff-Index => Rohstoff-Anzahl ) ] )
		* @return $angreifer_param hinterher
		* @return false bei Fehlschlag
		*/

		static function battle($planet, $angreifer_param)
		{
			$angreifer = array();
			foreach($angreifer_param as $username=>$info)
				$angreifer[$username] = $info[0];

			$target = explode(":", $planet);
			$target_galaxy = Classes::Galaxy($target[0]);
			$target_owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);
			if(!$target_owner) return false;
			$target_user = Classes::User($target_owner);
			if(!$target_user->getStatus()) return false;
			$target_user->setActivePlanet($target_user->getPlanetByPos($planet));

			$verteidiger = array();
			$verteidiger[$target_owner] = array();
			foreach($target_user->getItemsList('schiffe') as $item)
			{
				$level = $target_user->getItemLevel($item, 'schiffe');
				if($level <= 0) continue;
				$verteidiger[$target_owner][$item] = $level;
			}
			foreach($target_user->getItemsList('verteidigung') as $item)
			{
				$level = $target_user->getItemLevel($item, 'verteidigung');
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
				$angreifer_spiotech += $user->getItemLevel('F1', 'forschung');
			$angreifer_spiotech /= count($users_angreifer);

			$verteidiger_spiotech = 0;
			foreach($users_verteidiger as $user)
				$verteidiger_spiotech += $user->getItemLevel('F1', 'forschung');
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
					$item_info = $users_angreifer[$name]->getItemInfo($id);

					$staerke = $item_info['att']*$anzahl;
					$schild = $item_info['def']*$anzahl;

					$nachrichten_text .= "\t\t\t<tr>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
					$nachrichten_text .= "\t\t\t</tr>\n";

					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}

				$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">%7\$s</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
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
					$item_info = $users_verteidiger[$name]->getItemInfo($id);

					$staerke = $item_info['att']*$anzahl;
					$schild = $item_info['def']*$anzahl;

					if($anzahl > 0)
					{
						$nachrichten_text .= "\t\t\t<tr>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
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
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
				$nachrichten_text .= "\t\t</tfoot>\n";
			}
			$nachrichten_text .= "\t</table>\n";

			# Erstschlag
			if($angreifer_spiotech > $verteidiger_spiotech)
			{
				$runde_starter = 'angreifer';
				$runde_anderer = 'verteidiger';

				$nachrichten_text .= "\t<p class=\"erstschlag angreifer\">\n";
				$nachrichten_text .= "\t\t%10\$s\n";
				$nachrichten_text .= "\t</p>\n";
			}
			else
			{
				$runde_starter = 'verteidiger';
				$runde_anderer = 'angreifer';

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
				$runde_starter = 'angreifer';
				$runde_anderer = 'verteidiger';
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
				$a_objs = & ${'users_'.$runde_starter};
				$d_objs = & ${'users_'.$runde_anderer};

				# Flottengesamtstaerke
				$staerke = 0;
				foreach($a as $name=>$items)
				{
					foreach($items as $id=>$anzahl)
					{
						$item_info = $a_objs[$name]->getItemInfo($id);
						if(!$item_info) continue;
						$staerke += $item_info['att']*$anzahl;
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

					$nachrichten_runden[$name][$runde] .= "\t\t<h4>".h(sprintf($runde_starter == "angreifer" ? $user_obj->ngettext("Der Angreifer ist am Zug (Gesamtstärke %s)", "Die Angreifer sind am Zug (Gesamtstärke %s)", count($angreifer)) : $user_obj->ngettext("Der Verteidiger ist am Zug (Gesamtstärke %s)", "Die Verteidiger sind am Zug (Gesamtstärke %s)", count($verteidiger)), ths(round($staerke)))).")</h4>\n";
					$nachrichten_runden[$name][$runde] .= "\t\t<ol>\n";
				}

				while($staerke > 0)
				{
					$att_user = array_rand($d);
					$att_id = array_rand($d[$att_user]);

					$item_info = ${'users_'.$runde_anderer}[$att_user]->getItemInfo($att_id);
					$this_shield = $item_info['def']*$d[$att_user][$att_id];

					$schild_f = pow(0.95, ${'users_'.$runde_anderer}[$att_user]->getItemLevel('F10', 'forschung'));
					$aff_staerke = $staerke*$schild_f;

					if($this_shield > $aff_staerke)
					{
						$this_shield -= $aff_staerke;
						$before = $d[$att_user][$att_id];
						$d[$att_user][$att_id] = $this_shield/$item_info['def'];
						$floor_diff = ceil($before)-ceil($d[$att_user][$att_id]);

						foreach($users_all as $name=>$user_obj)
						{
							$nachrichten_runden[$name][$runde] .= "\t\t\t<li>";
							if($floor_diff <= 0)
								$nachrichten_runden[$name][$runde] .= sprintf(h($user_obj->_("Eine Einheit des Typs %s (%s) wird angeschossen.")), h($user_obj->_("[item_".$att_id."]")), "<span class=\"".$runde_anderer."-name\">".htmlspecialchars($att_user)."</span>")."</li>\n";
							else
								$nachrichten_runden[$name][$runde] .= sprintf(h($user_obj->ngettext("%s Einheit des Typs %s (%s) wird zerstört.", "%s Einheiten des Typs %s (%s) werden zerstört.", $floor_diff)), ths($floor_diff), htmlspecialchars($item_info["name"]), "<span class=\"".$runde_anderer."-name\">".htmlspecialchars($att_user)."</span>")." ".h(sprintf($user_obj->ngettext("%s verbleibt.", "%s verbleiben.", ceil($d[$att_user][$att_id])), ths(ceil($d[$att_user][$att_id]))))."</li>\n";
						}

						$staerke = 0;
					}
					else
					{
						foreach($users_all as $name=>$user_obj)
							$nachrichten_runden[$name][$runde] .= "\t\t\t<li>".sprintf(h($user_obj->_("Alle Einheiten des Typs %s (%s) (%s) werden zerstört.")), $user_obj->_("[item_".$att_id."]"), ths(ceil($d[$att_user][$att_id])), "<span class=\"".$runde_anderer."-name\">".htmlspecialchars($att_user)."</span>")."</li>\n";
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
					$item_info = $users_angreifer[$name]->getItemInfo($id, false, true, true);

					if(isset($angreifer[$name]) && isset($angreifer[$name][$id]))
						$anzahl = $angreifer[$name][$id];
					else
						$anzahl = 0;

					$diff = $old_anzahl-$anzahl;
					$truemmerfeld[0] += $item_info['ress'][0]*$diff*.4;
					$truemmerfeld[1] += $item_info['ress'][1]*$diff*.4;
					$truemmerfeld[2] += $item_info['ress'][2]*$diff*.4;
					$truemmerfeld[3] += $item_info['ress'][3]*$diff*.4;
					$angreifer_punkte[$name] += $item_info['simple_scores']*$diff;

					$staerke = $item_info['att']*$anzahl;
					$schild = $item_info['def']*$anzahl;

					if($anzahl > 0)
					{
						$nachrichten_text .= "\t\t\t<tr>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
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
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
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
					$item_info = $users_verteidiger[$name]->getItemInfo($id, false, true, true);

					if(isset($verteidiger[$name]) && isset($verteidiger[$name][$id]))
						$anzahl = $verteidiger[$name][$id];
					else $anzahl = 0;

					$diff = $anzahl_old-$anzahl;
					if($item_info['type'] == 'schiffe')
					{
						$truemmerfeld[0] += $item_info['ress'][0]*$diff*.4;
						$truemmerfeld[1] += $item_info['ress'][1]*$diff*.4;
						$truemmerfeld[2] += $item_info['ress'][2]*$diff*.4;
						$truemmerfeld[3] += $item_info['ress'][3]*$diff*.4;
					}
					elseif($item_info['type'] == 'verteidigung')
					{
						$verteidiger_ress[$name][0] += $item_info['ress'][0]*.2;
						$verteidiger_ress[$name][1] += $item_info['ress'][1]*.2;
						$verteidiger_ress[$name][2] += $item_info['ress'][2]*.2;
						$verteidiger_ress[$name][3] += $item_info['ress'][3]*.2;
					}

					$verteidiger_punkte[$name] += $diff*$item_info['simple_scores'];

					$staerke = $item_info['att']*$anzahl;
					$schild = $item_info['def']*$anzahl;

					if($anzahl > 0)
					{
						$nachrichten_text .= "\t\t\t<tr>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"info/description.php?id=".htmlspecialchars(urlencode($id))."\" title=\"%6\$s\">[item_".$id."]</a></td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
						$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
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
					$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
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
					h(sprintf($u->ngettext("Der Angreifer hat %s Kampferfahrungspunkte gesammelt.", "Die Angreifer haben %s Kampferfahrungspunkte gesammelt.", count($angreifer_anfang)), ths($angreifer_new_erfahrung))),
					h(sprintf($u->ngettext("Der Verteidiger hat %s Kampferfahrungspunkte gesammelt.", "Die Verteidiger haben %s Kampferfahrungspunkte gesammelt.", count($verteidiger_anfang)), ths($verteidiger_new_erfahrung)))
				));

				$nachrichten[$n] .= "\t<ul class=\"angreifer-punkte\">\n";
				foreach($angreifer_anfang as $a=>$i)
				{
					$p = 0;
					if(isset($angreifer_punkte[$a])) $p = $angreifer_punkte[$a];
					$nachrichten[$n] .= "\t\t<li>".sprintf(h($u->_("Der Angreifer %s hat %s Punkte verloren.")), "<span class=\"koords\">".htmlspecialchars($a)."</span>", ths($p))."</li>\n";
				}
				$nachrichten[$n] .= "\t</ul>\n";
				$nachrichten[$n] .= "\t<ul class=\"verteidiger-punkte\">\n";
				foreach($verteidiger_anfang as $v=>$i)
				{
					$p = 0;
					if(isset($verteidiger_punkte[$v])) $p = $verteidiger_punkte[$v];
					$nachrichten[$n] .= "\t\t<li>".sprintf(h($u->_("Der Verteidiger %s hat %s Punkte verloren.")), "<span class=\"koords\">".htmlspecialchars($v)."</span>", ths($p))."</li>\n";
				}
				$nachrichten[$n] .= "\t</ul>\n";

				if(array_sum($truemmerfeld) > 0)
				{
					$nachrichten[$n] .= "\t<p>\n";
					$nachrichten[$n] .= "\t\t".h(sprintf($u->_("Folgende Trümmer zerstörter Schiffe sind durch dem Kampf in die Umlaufbahn des Planeten gelangt: %s."), sprintf($u->_("%s %s, %s %s, %s %s und %s %s"), ths($truemmerfeld[0]), $u->_("[ress_0]"), ths($truemmerfeld[1]), $u->_("[ress_1]"), ths($truemmerfeld[2]), $u->_("[ress_2]"), ths($truemmerfeld[3]), $u->_("[ress_3]"))))."\n";
					$nachrichten[$n] .= "\t</p>\n";
				}
			}

			if(array_sum($truemmerfeld) > 0)
				truemmerfeld::add($target[0], $target[1], $target[2], $truemmerfeld[0], $truemmerfeld[1], $truemmerfeld[2], $truemmerfeld[3]);

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
						$item_info = $this_user->getItemInfo($id, 'schiffe');
						$this_trans = $item_info['trans'][0]*$count;
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

					$nachrichten[$username] .= "\n\t<p class=\"rohstoffe-erbeutet selbst\">".h(sprintf($users_all[$username]->_("Sie haben %s %s, %s %s, %s %s, %s %s und %s %s erbeutet."), ths($rtrans[0]), $users_all[$username]->_("[ress_0]"), ths($rtrans[1]), $users_all[$username]->_("[ress_1]"), ths($rtrans[2]), $users_all[$username]->_("[ress_2]"), ths($rtrans[3]), $users_all[$username]->_("[ress_3]"), ths($rtrans[4]), $users_all[$username]->_("[ress_4]")))."</p>\n";
				}

				$target_user->subtractRess($ress_max, false);

				foreach($users_all as $username=>$u)
				{
					if(isset($angreifer2[$username])) continue;
					$nachrichten[$username] .= "\n\t<p class=\"rohstoffe-erbeutet andere\">".h(sprintf($u->_("Die überlebenden Angreifer haben %s %s, %s %s, %s %s, %s %s und %s %s erbeutet."), ths($ress_max[0]), $u->_("[ress_0]"), ths($ress_max[1]), $u->_("[ress_1]"), ths($ress_max[2]), $u->_("[ress_2]"), ths($ress_max[3]), $u->_("[ress_3]"), ths($ress_max[4]), $u->_("[ress_4]")))."</p>\n";
				}
			}

			if(isset($verteidiger_ress[$target_owner]))
				$nachrichten[$target_owner] .= "\n\t<p class=\"verteidigung-wiederverwertung\">".h(sprintf($target_user->_("Durch Wiederverwertung konnten folgende Rohstoffe aus den Trümmern der zerstörten Verteidigungsanlagen wiederhergestellt werden: %s"), sprintf($target_user->_("%s %s, %s %s, %s %s und %s %s"), ths($verteidiger_ress[$target_owner][0]), $target_user->_("[ress_0]"), ths($verteidiger_ress[$target_owner][1]), $target_user->_("[ress_1]"), ths($verteidiger_ress[$target_owner][2]), $target_user->_("[ress_2]"), ths($verteidiger_ress[$target_owner][3]), $target_user->_("[ress_3]"))))."</p>\n";

			# Nachrichten zustellen
			foreach($nachrichten as $username=>$text)
			{
				$message = Classes::Message();
				if(!$message->create()) continue;
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
				$user_obj->recalcHighscores(false, false, false, true, true);
			}

			return $angreifer_return;
		}
	}

	function array_sum_r($array)
	{
		$sum = 0;
		foreach($array as $val)
			$sum += array_sum($val);
		return $sum;
	}
