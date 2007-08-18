<?php
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

	class Fleet
	{
		protected static $database = false;
		protected $status = false;
		protected $raw = false;
		protected $changed = false;
		protected $name = false;

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
						decode_item_list($u[8])
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
				$users[] = $k."\t".encode_item_list($v[0])."\t".$v[1]."\t".$v[2]."\t".encode_ress_list($v[3][0])."\t".encode_item_list($v[3][1])."\t".$v[3][2]."\t".encode_ress_list($v[4][0])."\t".encode_item_list($v[4][1])."\t".$v[5];
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

			foreach($this->raw[1] as $user=>$info)
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

		function getNeededSlots()
		{
			if(!$this->status) return false;

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
				array(array(0, 0, 0, 0, 0), array()), # Handel
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

		function setHandel($user, $ress=false, $robs=false)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;

			list($max_ress, $max_robs) = $this->getTransportCapacity($user);
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
					$old_target = $target;
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

		function getTime($i)
		{
			if(!$this->status || (!isset($this->raw[0][$i]) && !isset($this->raw[0][$i.'T']))) return false;
			if(count($this->raw[1]) <= 0) return false;

			$keys = array_keys($this->raw[1]);
			$user = array_shift($keys);
			$from = $this->raw[1][$user][1];
			$to = $i;

			return $this->calcTime($user, $from, $to);
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
				$this->raw[1][$user][4] = array(array(0,0,0,0,0), array());
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

			unset($this->raw[1][$user]);

			if(count($this->raw[1]) <= 0)
			{
				# Aus der Eventdatei entfernen
				$event_obj = Classes::EventFile();
				$event_obj->removeCanceledFleet($this->getName());

				$this->destroy();
			}

			$new = Classes::Fleet();
			$new->create();
			$new->setRaw($new_raw);
			$new->createNextEvent();

			$user_obj = Classes::User($user);
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
			__autoload('Galaxy');

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
				$galaxy_count = getGalaxiesCount();

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
					$message = Classes::Message();
					if($message->create())
					{
						$message->text('Ihre Flotte erreicht den Planeten '.$next_target_nt.' und will mit der Besiedelung anfangen. Jedoch ist der Planet bereits vom Spieler '.$target_owner." besetzt, und Ihre Flotte macht sich auf den R\xc3\xbcckweg.");
						$message->subject('Besiedelung von '.$next_target_nt.' fehlgeschlagen');
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
							$message->subject('Besiedelung von '.$next_target_nt.' fehlgeschlagen');
							$message->text("Ihre Flotte erreicht den Planeten ".$next_target_nt." und will mit der Besiedelung anfangen. Als Sie jedoch Ihren Zentralcomputer um Bestätigung für die Besiedelung bittet, kommt dieser durcheinander, da Sie schon so viele Planeten haben und er nicht so viele gleichzeitig kontrollieren kann, und schickt in Panik Ihrer Flotte das Signal zum Rückflug.");
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

					$message_obj = Classes::Message();
					if($message_obj->create())
					{
						$message_obj->subject($next_target_nt.' unbesiedelt');
						$message_obj->text("Ihre Flotte erreicht den Planeten ".$next_target_nt." und will ihren Auftrag ausf\xc3\xbchren. Jedoch wurde der Planet zwischenzeitlich verlassen und Ihre Flotte macht sich auf den weiteren Weg.");
						foreach(array_keys($this->raw[1]) as $username)
							$message_obj->addUser($username, $types_message_types[$type]);
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
								$message = Classes::Message();
								if(!$message->create()) continue;
								$message->subject('Abbau auf '.$next_target_nt);
								$message->text(sprintf(<<<EOF
<div class="nachricht-sammeln">
	<p>Ihre Flotte erreicht das Trümmerfeld auf {$next_target_nt} und belädt die %s Tonnen Sammlerkapazität mit folgenden Rohstoffen: %s Carbon, %s Aluminium, %s Wolfram und %s Radium.</p>
	<h3>Verbleibende Rohstoffe im Trümmerfeld</h3>
	<dl class="ress truemmerfeld-verbleibend">
		<dt class="c-carbon">Carbon</dt>
		<dd class="c-carbon">%s</dd>

		<dt class="c-aluminium">Aluminium</dt>
		<dd class="c-aluminium">%s</dd>

		<dt class="c-wolfram">Wolfram</dt>
		<dd class="c-wolfram">%s</dd>

		<dt class="c-radium">Radium</dt>
		<dd class="c-radium">%s</dd>
	</dl>
</div>
EOF
									, ths($trans_total), ths($rtrans[0]), ths($rtrans[1]), ths($rtrans[2]), ths($rtrans[3]), ths($tr_verbl[0]), ths($tr_verbl[1]), ths($tr_verbl[2]), ths($tr_verbl[3])));
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
								$angreifer[$username] = $info[0];
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
									$foreign_fleet = iadd($foreign_fleet, $foreign_fi[0]);
								isort($foreign_fleet);
								$verteidiger[$username] = $foreign_fleet;
							}

							list($winner, $angreifer2, $verteidiger2, $nachrichten_text, $verteidiger_ress, $truemmerfeld) = battle($angreifer, $verteidiger);

							if(array_sum($truemmerfeld) > 0)
							{
								truemmerfeld::add($target[0], $target[1], $target[2], $truemmerfeld[0], $truemmerfeld[1], $truemmerfeld[2], $truemmerfeld[3]);

								$nachrichten_text .= "<p>\n";
								$nachrichten_text .= "\tFolgende Tr\xc3\xbcmmer zerst\xc3\xb6rter Schiffe sind durch dem Kampf in die Umlaufbahn des Planeten gelangt: ".ths($truemmerfeld[0])."&nbsp;Carbon, ".ths($truemmerfeld[1])."&nbsp;Aluminium, ".ths($truemmerfeld[2])."&nbsp;Wolfram und ".ths($truemmerfeld[3])."&nbsp;Radium.\n";
								$nachrichten_text .= "</p>\n";
							}

							# Nachrichten aufteilen
							$angreifer_keys = array_keys($angreifer);
							$verteidiger_keys = array_keys($verteidiger);
							$users_keys = array_merge($angreifer_keys, $verteidiger_keys);
							$messages = array();
							foreach($users_keys as $username)
								$messages[$username] = $nachrichten_text;


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
								foreach($angreifer2 as $username=>$fleet)
								{
									$trans[$username] = -array_sum($this->raw[1][$username][3][0]);
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
									$rtrans[0] = floor($ress_max[0]*$p);
									$rtrans[1] = floor($ress_max[1]*$p);
									$rtrans[2] = floor($ress_max[2]*$p);
									$rtrans[3] = floor($ress_max[3]*$p);
									$rtrans[4] = floor($ress_max[4]*$p);

									$this->raw[1][$username][3][0][0] += $rtrans[0];
									$this->raw[1][$username][3][0][1] += $rtrans[1];
									$this->raw[1][$username][3][0][2] += $rtrans[2];
									$this->raw[1][$username][3][0][3] += $rtrans[3];
									$this->raw[1][$username][3][0][4] += $rtrans[4];

									$messages[$username] .= "\n<p class=\"rohstoffe-erbeutet selbst\">Sie haben ".ths($rtrans[0])." Carbon, ".ths($rtrans[1])." Aluminium, ".ths($rtrans[2])." Wolfram, ".ths($rtrans[3])." Radium und ".ths($rtrans[4])." Tritium erbeutet.</p>\n";
								}

								$target_user->subtractRess($ress_max, false);

								foreach($users_keys as $username)
								{
									if(isset($angreifer2[$username])) continue;
									$messages[$username] .= "\n<p class=\"rohstoffe-erbeutet andere\">Die überlebenden Angreifer haben ".ths($ress_max[0])." Carbon, ".ths($ress_max[1])." Aluminium, ".ths($ress_max[2])." Wolfram, ".ths($ress_max[3])." Radium und ".ths($ress_max[4])." Tritium erbeutet.</p>\n";
								}
							}

							if(isset($verteidiger_ress[$target_owner]))
								$messages[$target_owner] .= "\n<p class=\"verteidigung-wiederverwertung\">Durch Wiederverwertung konnten folgende Rohstoffe aus den Trümmern der zerstörten Verteidigungsanlagen wiederhergestellt werden: ".ths($verteidiger_ress[$target_owner][0])." Carbon, ".ths($verteidiger_ress[$target_owner][1])." Aluminium, ".ths($verteidiger_ress[$target_owner][2])." Wolfram und ".ths($verteidiger_ress[$target_owner][3])." Radium.</p>\n";

							# Nachrichten zustellen
							foreach($messages as $username=>$text)
							{
								$message = Classes::Message();
								if(!$message->create()) continue;
								$message->text($text);
								$message->subject("Kampf auf ".$next_target_nt);
								$message->html(true);
								$message->addUser($username, 1);
							}

							foreach($angreifer_keys as $username)
							{
								if(!isset($angreifer2[$username]))
								{
									# Flotten des Angreifers wurden zerstoert
									if($username == $first_user) $further = false;
									else unset($this->raw[1][$username]);
								}
								else $this->raw[1][$username][0] = $angreifer2[$username];
								$user_obj = Classes::User($username);
								$user_obj->recalcHighscores(false, false, false, true, false);
							}

							foreach($verteidiger_keys as $username)
							{
								foreach($verteidiger[$username] as $id=>$count)
								{
									$count2 = 0;
									if(isset($verteidiger2[$username]) && isset($verteidiger2[$username][$id]))
										$count2 = $verteidiger2[$username][$id];
									if($count2 != $count)
									{
										if($username == $target_owner)
											$target_user->changeItemLevel($id, $count2-$count);
										else $target_user->subForeignFleet($username, $id, $count-$count2);
									}
								}
								$user_obj = Classes::User($username);
								$user_obj->recalcHighscores(false, false, false, true, true);
							}

							break;
						}
						case 4: # Transport
						{
							$message_text = array(
								$target_owner => "Ein Transport erreicht Ihren Planeten \xe2\x80\x9e".$target_user->planetName()."\xe2\x80\x9c (".$next_target_nt."). Folgende Spieler liefern Güter ab:\n"
							);

							# Rohstoffe abliefern, Handel
							$handel = array();
							$make_handel_message = false;
							foreach($this->raw[1] as $username=>$data)
							{
								$write_this_username = ($username != $target_owner);
								if($write_this_username) $message_text[$username] = "Ihre Flotte erreicht den Planeten \xe2\x80\x9e".$target_user->planetName()."\xe2\x80\x9c (".$next_target_nt.", Eigent\xc3\xbcmer: ".$target_owner.") und liefert folgende Güter ab:\n";
								$message_text[$target_owner] .= $username.": ";
								if($write_this_username) $message_text[$username] .= "Carbon: ".ths($data[3][0][0], true).", Aluminium: ".ths($data[3][0][1], true).", Wolfram: ".ths($data[3][0][2], true).", Radium: ".ths($data[3][0][3], true).", Tritium: ".ths($data[3][0][4], true);
								$message_text[$target_owner] .= "Carbon: ".ths($data[3][0][0], true).", Aluminium: ".ths($data[3][0][1], true).", Wolfram: ".ths($data[3][0][2], true).", Radium: ".ths($data[3][0][3], true).", Tritium: ".ths($data[3][0][4], true);
								$target_user->addRess($data[3][0]);
								$this->raw[1][$username][3][0] = array(0,0,0,0,0);
								if($target_owner == $username && array_sum($data[3][1]) > 0)
								{
									$items_string = makeItemsString($data[3][1], false);
									if($write_this_username) $message_text[$username] .= "\n".$items_string;
									$message_text[$target_owner] .= "; ".$items_string;
									foreach($data[3][1] as $id=>$anzahl)
										$target_user->changeItemLevel($id, $anzahl, 'roboter');
									$this->raw[1][$username][3][1] = array();
								}
								if($write_this_username) $message_text[$username] .= "\n";
								$message_text[$target_owner] .= "\n";
								if(array_sum_r($data[4]) > 0)
								{
									$handel[$username] = $data[4];
									$this->raw[1][$username][3][0] = $data[4][0];
									$this->raw[1][$username][3][1] = $data[4][1];
									$this->raw[1][$username][4] = array(array(0,0,0,0,0),array());
									$make_handel_message = true;
								}
							}
							if($make_handel_message)
							{
								$message_text[$target_owner] .= "\nFolgender Handel wird durchgef\xc3\xbchrt:\n";
								foreach($handel as $username=>$h)
								{
									$write_this_username = ($username != $target_owner);
									if($write_this_username)
									{
										$message_text[$username] .= "\nFolgender Handel wird durchgef\xc3\xbchrt:\n";
										$message_text[$username] .= "Carbon: ".ths($h[0][0], true).", Aluminium: ".ths($h[0][1], true).", Wolfram: ".ths($h[0][2], true).", Radium: ".ths($h[0][3], true).", Tritium: ".ths($h[0][4], true);
									}
									$message_text[$target_owner] .= $username.": Carbon: ".ths($h[0][0], true).", Aluminium: ".ths($h[0][1], true).", Wolfram: ".ths($h[0][2], true).", Radium: ".ths($h[0][3], true).", Tritium: ".ths($h[0][4], true);
									if(array_sum($h[1]) > 0)
									{
										if($write_this_username) $message_text[$username] .= "\n";
										$message_text[$target_owner] .= "; ";
										$items_string = makeItemsString($h[1], false);
										if($write_this_username) $message_text[$username] .= $items_string;
										$message_text[$target_owner] .= $items_string;
									}
									if($write_this_username) $message_text[$username] .= "\n";
									$message_text[$target_owner] .= "\n";
								}
							}
							foreach($message_text as $username=>$text)
							{
								$message_obj = Classes::Message();
								if($message_obj->create())
								{
									if($username == $target_owner && !isset($this->raw[1][$username]))
									{
										$message_obj->subject('Ankunft eines fremden Transportes auf '.$next_target_nt);
										$users = array_keys($this->raw[1]);
										$message_obj->from(array_shift($users));
									}
									else
									{
										$message_obj->subject('Ankunft Ihres Transportes auf '.$next_target_nt);
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

							if(!$target_owner)
							{
								# Zielplanet ist nicht besiedelt
								$message_text = "<div class=\"nachricht-spionage\">\n";
								$message_text .= "\t<h3>Spionagebericht des Planeten ".utf8_htmlentities($next_target_nt)."</h3>\n";
								$message_text .= "\t<div id=\"spionage-planet\">\n";
								$message_text .= "\t\t<h4>Planet</h4>\n";
								$message_text .= "\t\t<dl class=\"planet_".$target_galaxy->getPlanetClass($target[1], $target[2])."\">\n";
								$message_text .= "\t\t\t<dt class=\"c-felder\">Felder</dt>\n";
								$message_text .= "\t\t\t<dd class=\"c-felder\">".ths($target_galaxy->getPlanetSize($target[1], $target[2]))."</dd>\n";
								$message_text .= "\t\t</dl>\n";
								$message_text .= "\t</div>\n";

								$message_text .= "\t<p class=\"besiedeln\">\n";
								$message_text .= "\t\t<a href=\"flotten.php?action=besiedeln&amp;action_galaxy=".htmlentities(urlencode($target[0]))."&amp;action_system=".htmlentities(urlencode($target[1]))."&amp;action_planet=".htmlentities(urlencode($target[2]))."\" title=\"Schicken Sie ein Besiedelungsschiff zu diesem Planeten\">Besiedeln</a>\n";
								$message_text .= "\t</p>\n";
								$message_text .= "</div>";

								$message = Classes::Message();

								if($message->create())
								{
									$message->text($message_text);
									$message->subject('Spionage des Planeten '.$next_target_nt);
									foreach(array_keys($this->raw[1]) as $username)
										$message->addUser($username, $types_message_types[$type]);
									$message->html(true);
								}
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
								$message_text .= "\t<h3>Spionagebericht des Planeten \xe2\x80\x9e".utf8_htmlentities($target_galaxy->getPlanetName($target[1], $target[2]))."\xe2\x80\x9c (".utf8_htmlentities($next_target_nt).", Eigent\xc3\xbcmer: ".utf8_htmlentities($target_owner).")</h3>\n";
								$message_text .= "\t<div id=\"spionage-planet\">\n";
								$message_text .= "\t\t<h4>Planet</h4>\n";
								$message_text .= "\t\t<dl class=\"planet_".$target_galaxy->getPlanetClass($target[1], $target[2])."\">\n";
								$message_text .= "\t\t\t<dt class=\"c-felder\">Felder</dt>\n";
								$message_text .= "\t\t\t<dd class=\"c-felder\">".$target_user->getTotalFields()."</dd>\n";
								$message_text .= "\t\t</dl>\n";
								$message_text .= "\t</div>\n";

								$message_text2 = array();
								switch($diff)
								{
									case 5: # Roboter zeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-roboter\">\n";
										$next .= "\t\t<h4>Roboter</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('roboter') as $id)
										{
											if($target_user->getItemLevel($id, 'roboter') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'roboter');
											$next .= "\t\t\t<li>".$item_info['name']." <span class=\"anzahl\">(".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 4: # Forschung zeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-forschung\">\n";
										$next .= "\t\t<h4>Forschung</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('forschung') as $id)
										{
											if($target_user->getItemLevel($id, 'forschung') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'forschung');
											$next .= "\t\t\t<li>".$item_info['name']." <span class=\"stufe\">(Level&nbsp;".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 3: # Schiffe und Verteidigungsanlagen anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-schiffe\">\n";
										$next .= "\t\t<h4>Schiffe</h4>\n";
										$next .= "\t\t<ul>\n";
										$schiffe = array();
										foreach($target_user->getItemsList('schiffe') as $id)
										{
											$count = $target_user->getItemLevel($id, 'schiffe');
											if($count <= 0) continue;
											$schiffe[$i] = $count;
										}

										# Fremdstationierte Flotten mit den eigenen zusammen anzeigen
										foreach($target_user->getForeignUsersList() as $foreign_user)
										{
											foreach($target_user->getForeignFleetsList($foreign_user) as $foreign_i->$foreign_fleet)
											{
												foreach($foreign_fleet as $id=>$count)
												{
													if(isset($schiffe[$i]))
														$schiffe[$i] += $count;
													else
														$schiffe[$i] = $count;
												}
											}
										}

										foreach($schiffe as $id=>$count)
										{
											$item_info = $target_user->getItemInfo($id, 'schiffe');
											$next .= "\t\t\t<li>".$item_info['name']." <span class=\"anzahl\">(".ths($count).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										$next .= "\t<div id=\"spionage-verteidigung\">\n";
										$next .= "\t\t<h4>Verteidigung</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('verteidigung') as $id)
										{
											if($target_user->getItemLevel($id, 'verteidigung') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'verteidigung');
											$next .= "\t\t\t<li>".$item_info['name']." <span class=\"anzahl\">(".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 2: # Gebaeude anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-gebaeude\">\n";
										$next .= "\t\t<h4>Geb\xc3\xa4ude</h4>\n";
										$next .= "\t\t<ul>\n";
										foreach($target_user->getItemsList('gebaeude') as $id)
										{
											if($target_user->getItemLevel($id, 'gebaeude') <= 0) continue;
											$item_info = $target_user->getItemInfo($id, 'gebaeude');
											$next .= "\t\t\t<li>".$item_info['name']." <span class=\"stufe\">(Stufe&nbsp;".ths($item_info['level']).")</span></li>\n";
										}
										$next .= "\t\t</ul>\n";
										$next .= "\t</div>\n";
										unset($next);
									case 1: # Rohstoffe anzeigen
										$next = &$message_text2[];
										$next = "\t<div id=\"spionage-rohstoffe\">\n";
										$next .= "\t\t<h4>Rohstoffe</h4>\n";
										$next .= "\t\t".format_ress($target_user->getRess(), 2, true);
										$next .= "\t</div>\n";
										unset($next);
								}
								$message_text .= implode('', array_reverse($message_text2));
								$message_text .= "</div>\n";

								$message = Classes::Message();
								if($message->create())
								{
									$message->subject('Spionage des Planeten '.$next_target_nt);
									$message->text($message_text);
									$message->html(true);
									$message->from($target_owner);
									foreach($users as $username)
										$message->addUser($username, $types_message_types[$type]);
								}

								$message = Classes::Message();
								if($message->create())
								{
									$message->subject('Fremde Flotte auf dem Planeten '.$next_target_nt);
									$first_user = array_shift($users);
									$from_pos_str = $this->raw[1][$first_user][1];
									$from_pos = explode(':', $from_pos_str);
									$from_galaxy = Classes::Galaxy($from_pos[0]);
									$message->text("Eine fremde Flotte vom Planeten \xe2\x80\x9e".$from_galaxy->getPlanetName($from_pos[1], $from_pos[2])."\xe2\x80\x9c (".$from_pos_str.", Eigent\xc3\xbcmer: ".$first_user.") wurde von Ihrem Planeten \xe2\x80\x9e".$target_user->planetName()."\xe2\x80\x9c (".$next_target_nt.") aus bei der Spionage gesichtet.");
									$message->from($first_user);
									$message->addUser($target_owner, $types_message_types[$type]);
								}
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
				if($target_user && $target_owner != $first_user)
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
					$user_obj->unsetFleet($this->getName());
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

				if($besiedeln)
				{
					$message_text = "Ihre Flotte erreicht den Planeten ".$next_target_nt." und beginnt mit seiner Besiedelung.";
					if(isset($besiedelung_ress))
						$message_text .= " Durch den Abbau eines Besiedelungsschiffs konnten folgende Rohstoffe wiederhergestellt werden: ".ths($besiedelung_ress[0], true)." Carbon, ".ths($besiedelung_ress[1], true)." Aluminium, ".ths($besiedelung_ress[2], true)." Wolfram, ".ths($besiedelung_ress[3], true)." Radium.";
					$message_text .= "\n";
				}
				else
					$message_text = "Eine Flotte erreicht den Planeten \xe2\x80\x9e".$owner_obj->planetName()."\xe2\x80\x9c (".$owner_obj->getPosString().", Eigent\xc3\xbcmer: ".$owner_obj->getName().").\n";
				if(array_sum($schiffe_own) > 0)
				{
					$message_text .= "Die Flotte besteht aus folgenden Schiffen: ".makeItemsString($schiffe_own, false)."\n";
					foreach($schiffe_own as $id=>$anzahl)
						$owner_obj->changeItemLevel($id, $anzahl);
				}
				if(array_sum_r($schiffe_other) > 0)
				{
					$message_text .= "Folgende Schiffe werden fremdstationiert:\n";
					foreach($schiffe_other as $user=>$schiffe)
					{
						$message_text .= $user.": ".makeItemsString($schiffe, false)."\n";
						$owner_obj->addForeignFleet($user, $schiffe, $move_info[1], $move_info[2]);
					}
				}

				$message_text .= "\nFolgende G\xc3\xbcter werden abgeliefert:\n";
				$message_text .= ths($ress[0], true).' Carbon, '.ths($ress[1], true).' Aluminium, '.ths($ress[2], true).' Wolfram, '.ths($ress[3], true).' Radium, '.ths($ress[4], true)." Tritium.";
				if(array_sum($robs) > 0)
					$message_text .= "\n".makeItemsString($robs, false)."\n";
				foreach($robs as $id=>$anzahl)
					$owner_obj->changeItemLevel($id, $anzahl, 'roboter');

				if($this->raw[1][$first_user][3][2] > 0)
				{
					$message_text .= "\n\nFolgender \xc3\xbcbersch\xc3\xbcssiger Treibstoff wird abgeliefert: ".ths($this->raw[1][$first_user][3][2], true)." Tritium.";
					$ress[4] += $this->raw[1][$first_user][3][2];
				}
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

				if(count($message_users) > 0)
				{
					$message_obj = Classes::Message();
					if($message_obj->create())
					{
						$message_obj->text($message_text);
						if($besiedeln)
							$message_obj->subject("Besiedelung von ".$next_target_nt);
						else
							$message_obj->subject("Stationierung auf ".$owner_obj->getPosString());

						# Bei Fremdstationierung Nachrichtenabsender eintragen
						if($owner && !isset($this->raw[1][$owner]))
							$message_obj->from($first_user);

						foreach($message_users as $username)
							$message_obj->addUser($username, $types_message_types[$this->raw[0][$next_target][0]]);
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
	}

	function battle($angreifer, $verteidiger)
	{
		if(count($angreifer) < 0 || count($verteidiger) < 0) return false;

		$angreifer_anfang = $angreifer;
		$verteidiger_anfang = $verteidiger;

		$users_angreifer = array();
		$users_verteidiger = array();
		foreach($angreifer as $username=>$i)
			$users_angreifer[$username] = Classes::User($username);
		foreach($verteidiger as $username=>$i)
			$users_verteidiger[$username] = Classes::User($username);

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
		if(count($angreifer) > 1)
			$nachrichten_text .= "\t<h3>Flotten der Angreifer</h3>\n";
		else
			$nachrichten_text .= "\t<h3>Flotten des Angreifers</h3>\n";

		$nachrichten_text .= "\t<table>\n";
		$nachrichten_text .= "\t\t<thead>\n";
		$nachrichten_text .= "\t\t\t<tr>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
		$nachrichten_text .= "\t\t\t</tr>\n";
		$nachrichten_text .= "\t\t</thead>\n";
		$nachrichten_text .= "\t\t<tbody>\n";

		$ges_anzahl = $ges_staerke = $ges_schild = 0;
		foreach($angreifer as $name=>$flotten)
		{
			$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
			$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"angreifer-name\">".utf8_htmlentities($name)."</span></th>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";

			$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
			foreach($flotten as $id=>$anzahl)
			{
				$item_info = $users_angreifer[$name]->getItemInfo($id);

				$staerke = $item_info['att']*$anzahl;
				$schild = $item_info['def']*$anzahl;

				$nachrichten_text .= "\t\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";

				$this_ges_anzahl += $anzahl;
				$this_ges_staerke += $staerke;
				$this_ges_schild += $schild;
			}

			$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
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
			$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</tfoot>\n";
		}
		$nachrichten_text .= "\t</table>\n";

		if(count($verteidiger) > 1)
			$nachrichten_text .= "\t<h3>Flotten der Verteidigers</h3>\n";
		else
			$nachrichten_text .= "\t<h3>Flotten des Verteidigers</h3>\n";

		$nachrichten_text .= "\t<table>\n";
		$nachrichten_text .= "\t\t<thead>\n";
		$nachrichten_text .= "\t\t\t<tr>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
		$nachrichten_text .= "\t\t\t</tr>\n";
		$nachrichten_text .= "\t\t</thead>\n";
		$nachrichten_text .= "\t\t<tbody>\n";

		$ges_anzahl = $ges_staerke = $ges_schild = 0;
		foreach($verteidiger as $name=>$flotten)
		{
			$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
			$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"verteidiger-name\">".utf8_htmlentities($name)."</span></th>\n";
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
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td colspan=\"4\">Keine.</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
			}
			else
			{
				$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
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
			$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</tfoot>\n";
		}
		$nachrichten_text .= "\t</table>\n";

		if(count($angreifer_anfang) > 1)
		{
			$angreifer_nominativ = 'die Angreifer';
			$angreifer_praedikat = 'sind';
			$angreifer_praedikat2 = 'haben';
			$angreifer_genitiv = 'der Angreifer';
			$angreifer_dativ = 'ihnen';
		}
		else
		{
			$angreifer_nominativ = 'der Angreifer';
			$angreifer_praedikat = 'ist';
			$angreifer_praedikat2 = 'hat';
			$angreifer_genitiv = 'des Angreifers';
			$angreifer_dativ = 'ihm';
		}
		if(count($verteidiger_anfang) > 1)
		{
			$verteidiger_nominativ = 'die Verteidiger';
			$verteidiger_praedikat = 'sind';
			$verteidiger_praedikat2 = 'haben';
			$verteidiger_nominativ_letzt = 'letztere';
			$verteidiger_genitiv = 'der Verteidiger';
		}
		else
		{
			$verteidiger_nominativ = 'der Verteidiger';
			$verteidiger_praedikat = 'ist';
			$verteidiger_praedikat2 = 'hat';
			$verteidiger_nominativ_letzt = 'letzterer';
			$verteidiger_genitiv = 'des Verteidigers';
		}


		# Erstschlag
		if($angreifer_spiotech > $verteidiger_spiotech)
		{
			$runde_starter = 'angreifer';
			$runde_anderer = 'verteidiger';

			$nachrichten_text .= "\t<p class=\"erstschlag angreifer\">\n";
			$nachrichten_text .= "\t\tDie Sensoren ".$angreifer_genitiv." sind st\xc3\xa4rker ausgebildet als die ".$verteidiger_genitiv." und erm\xc3\xb6glichen es ".$angreifer_dativ.", den Erstschlag auszuf\xc3\xbchren.\n";
			$nachrichten_text .= "\t</p>\n";
		}
		else
		{
			$runde_starter = 'verteidiger';
			$runde_anderer = 'angreifer';

			$nachrichten_text .= "\t<p class=\"erstschlag verteidiger\">\n";
			$nachrichten_text .= "\t\tDie Sensoren ".$angreifer_genitiv." sind denen ".$verteidiger_genitiv." nicht \xc3\xbcberlegen, weshalb ".$verteidiger_nominativ_letzt." den Erstschlag ausf\xc3\xbchrt.\n";
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

		# Einzelne Runden
		for($runde = 1; $runde <= 20; $runde++)
		{
			if(count($angreifer) <= 0 || count($verteidiger) <= 0) break;

			$a = & ${$runde_starter};
			$d = & ${$runde_anderer};
			$a_objs = & ${'users_'.$runde_starter};
			$d_objs = & ${'users_'.$runde_anderer};

			if($runde%2)
			{
				$nachrichten_text .= "\t<div class=\"runde\">\n";
				$nachrichten_text .= "\t\t<h3>Runde ".(($runde+1)/2)."</h3>\n";
			}

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

			$nachrichten_text .= "\t\t<h4>".ucfirst(${$runde_starter.'_nominativ'})." ".${$runde_starter.'_praedikat'}." am Zug (Gesamtst\xc3\xa4rke ".round($staerke).")</h4>\n";
			$nachrichten_text .= "\t\t<ol>\n";

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
					$nachrichten_text .= "\t\t\t<li>";
					if($floor_diff <= 0)
						$nachrichten_text .= "Eine Einheit des Typs ".utf8_htmlentities($item_info['name'])." (<span class=\"".$runde_anderer."-name\">".utf8_htmlentities($att_user)."</span>) wird angeschossen.</li>\n";
					else
					{
						$nachrichten_text .= ths($floor_diff)."&nbsp;Einheit";
						if($floor_diff != 1) $nachrichten_text .= "en";
						$nachrichten_text .= " des Typs ".utf8_htmlentities($item_info['name'])." (<span class=\"".$runde_anderer."-name\">".utf8_htmlentities($att_user)."</span>) werden zerst\xc3\xb6rt. ".ths(ceil($d[$att_user][$att_id]))." verbleiben.</li>\n";
					}
					$staerke = 0;
				}
				else
				{
					$nachrichten_text .= "\t\t\t<li>Alle Einheiten des Typs ".utf8_htmlentities($item_info['name'])." (".ths(ceil($d[$att_user][$att_id])).") (<span class=\"".$runde_anderer."-name\">".utf8_htmlentities($att_user)."</span>) werden zerst\xc3\xb6rt.</li>\n";
					$aff_staerke = $this_shield;
					unset($d[$att_user][$att_id]);
					if(count($d[$att_user]) <= 0) unset($d[$att_user]);
					$staerke -= $aff_staerke/$schild_f;
				}

				if(count($angreifer) <= 0 || count($verteidiger) <= 0) break;
			}

			$nachrichten_text .= "\t\t</ol>\n";
			if(!$runde%2)
				$nachrichten_text .= "\t</div>\n";

			# Vertauschen
			list($runde_starter, $runde_anderer) = array($runde_anderer, $runde_starter);
			unset($a);
			unset($d);
			unset($a_objs);
			unset($d_objs);
		}

		$nachrichten_text .= "\t<p>\n";
		$nachrichten_text .= "\t\tDer Kampf ist vor\xc3\xbcber. ";
		if(count($angreifer) == 0)
		{
			$nachrichten_text .= "Gewinner ".$verteidiger_praedikat." ".$verteidiger_nominativ.".";
			$winner = -1;
		}
		elseif(count($verteidiger) == 0)
		{
			$nachrichten_text .= "Gewinner ".$angreifer_praedikat." ".$angreifer_nominativ.".";
			$winner = 1;
		}
		else
		{
			$nachrichten_text .= "Er endet unentschieden.";
			$winner = 0;
		}
		$nachrichten_text .= "\n";
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

		if(count($angreifer_anfang) > 1)
			$nachrichten_text .= "\t<h3>Flotten der Angreifer</h3>\n";
		else
			$nachrichten_text .= "\t<h3>Flotten des Angreifers</h3>\n";

		$nachrichten_text .= "\t<table>\n";
		$nachrichten_text .= "\t\t<thead>\n";
		$nachrichten_text .= "\t\t\t<tr>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
		$nachrichten_text .= "\t\t\t</tr>\n";
		$nachrichten_text .= "\t\t</thead>\n";
		$nachrichten_text .= "\t\t<tbody>\n";

		$ges_anzahl = $ges_staerke = $ges_schild = 0;
		foreach($angreifer_anfang as $name=>$flotten)
		{
			$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
			$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"angreifer-name\">".utf8_htmlentities($name)."</span></th>\n";
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
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td colspan=\"4\">Keine.</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
			}
			else
			{
				$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
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
			$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</tfoot>\n";
		}
		$nachrichten_text .= "\t</table>\n";


		if(count($verteidiger_anfang) > 1)
			$nachrichten_text .= "\t<h3>Flotten der Verteidigers</h3>\n";
		else
			$nachrichten_text .= "\t<h3>Flotten des Verteidigers</h3>\n";

		$nachrichten_text .= "\t<table>\n";
		$nachrichten_text .= "\t\t<thead>\n";
		$nachrichten_text .= "\t\t\t<tr>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
		$nachrichten_text .= "\t\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
		$nachrichten_text .= "\t\t\t</tr>\n";
		$nachrichten_text .= "\t\t</thead>\n";
		$nachrichten_text .= "\t\t<tbody>\n";

		$ges_anzahl = $ges_staerke = $ges_schild = 0;
		foreach($verteidiger_anfang as $name=>$flotten)
		{
			$nachrichten_text .= "\t\t\t<tr class=\"benutzername\">\n";
			$nachrichten_text .= "\t\t\t\t<th colspan=\"4\"><span class=\"verteidiger-name\">".utf8_htmlentities($name)."</span></th>\n";
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
					$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
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
				$nachrichten_text .= "\t\t\t\t<td colspan=\"4\">Keine.</td>\n";
				$nachrichten_text .= "\t\t\t</tr>\n";
			}
			else
			{
				$nachrichten_text .= "\t\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
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
			$nachrichten_text .= "\t\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t\t</tr>\n";
			$nachrichten_text .= "\t\t</tfoot>\n";
		}
		$nachrichten_text .= "\t</table>\n";

		$nachrichten_text .= "\t<ul class=\"angreifer-punkte\">\n";
		foreach($angreifer_anfang as $a=>$i)
		{
			$p = 0;
			if(isset($angreifer_punkte[$a])) $p = $angreifer_punkte[$a];
			$nachrichten_text .= "\t\t<li>Der Angreifer <span class=\"koords\">".utf8_htmlentities($a)."</span> hat ".ths($p)."&nbsp;Punkte verloren.</li>\n";
		}
		$nachrichten_text .= "\t</ul>\n";
		$nachrichten_text .= "\t<ul class=\"verteidiger-punkte\">\n";
		foreach($verteidiger_anfang as $v=>$i)
		{
			$p = 0;
			if(isset($verteidiger_punkte[$v])) $p = $verteidiger_punkte[$v];
			$nachrichten_text .= "\t\t<li>Der Verteidiger <span class=\"koords\">".utf8_htmlentities($v)."</span> hat ".ths($p)."&nbsp;Punkte verloren.</li>\n";
		}
		$nachrichten_text .= "\t</ul>\n";

		if(array_sum($truemmerfeld) > 0)
		{
			# Truemmerfeld

			$truemmerfeld[0] = round($truemmerfeld[0]);
			$truemmerfeld[1] = round($truemmerfeld[1]);
			$truemmerfeld[2] = round($truemmerfeld[2]);
			$truemmerfeld[3] = round($truemmerfeld[3]);
		}

		# Kampferfahrung
		$angreifer_new_erfahrung = array_sum($verteidiger_punkte)/1000;
		$verteidiger_new_erfahrung = array_sum($angreifer_punkte)/1000;
		$nachrichten_text .= "\t<ul class=\"kampferfahrung\">\n";
		$nachrichten_text .= "\t\t<li class=\"c-angreifer\">".ucfirst($angreifer_nominativ)." ".$angreifer_praedikat2." ".ths($angreifer_new_erfahrung)."&nbsp;Kampferfahrungspunkte gesammelt.</li>\n";
		$nachrichten_text .= "\t\t<li class=\"c-verteidiger\">".ucfirst($verteidiger_nominativ)." ".$verteidiger_praedikat2." ".ths($verteidiger_new_erfahrung)."&nbsp;Kampferfahrungspunkte gesammelt.</li>\n";
		$nachrichten_text .= "\t</ul>\n";
		foreach($users_angreifer as $user)
			$user->addScores(6, $angreifer_new_erfahrung);
		foreach($users_verteidiger as $user)
			$user->addScores(6, $verteidiger_new_erfahrung);

		$nachrichten_text .= "</div>\n";


		# $winner:  1: Angreifer gewinnt
		#           0: Unentschieden
		#          -1: Verteidiger gewinnt
		#
		# $angreifer: Wie uebergeben, Flotten nach der Schlacht
		# $verteidiger: Wie uebergeben, Flotten nach der Schlacht
		#
		# $nachrichten_text: Kampfbericht, es muessen noch fuer jeden Benutzer die regenerierten
		#                    Verteidigungsrohstoffe aus $verteidiger_ress angehaengt werden.
		#
		# $truemmerfeld: Das Truemmerfeld, das entstehen wird
		#
		# Rohstoffe muessen noch gestohlen werden

		return array($winner, $angreifer, $verteidiger, $nachrichten_text, $verteidiger_ress, $truemmerfeld);
	}

	function array_sum_r($array)
	{
		$sum = 0;
		foreach($array as $val)
			$sum += array_sum($val);
		return $sum;
	}
?>
