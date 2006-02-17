<?php
	class Fleet extends Dataset
	{
		protected $save_dir = DB_FLEETS;
		private $datatype = 'fleet';
		
		function create()
		{
			if(file_exists($this->filename)) return false;
			$this->raw = array(array(), array(), false, array());
			$this->write(true);
			$this->__construct($this->name);
			return true;
		}
		
		function write($force=false)
		{
			if($this->started() || $force) return Dataset::write($force);
			else return $this->destroy();
		}
		
		function destroy()
		{
			if(!$this->status) return false;
			
			foreach($this->raw[1] as $user=>$info)
			{
				$user_obj = Classes::User($user);
				$user_obj->unsetFleet($this->getName());
			}
			
			$status = (unlink($this->filename) || chmod($this->filename, 0));
			if($status)
			{
				$this->status = 0;
				$this->changed = false;
				return true;
			}
			else return false;
		}
		
		function fleetExists($fleet)
		{
			$filename = DB_FLEETS.'/'.urlencode($fleet);
			return (is_file($filename) && is_readable($filename));
		}
		
		function getTargetsList()
		{
			if(!$this->status) return false;
			
			return array_keys($this->raw[0]);
		}
		
		function getOldTargetsList()
		{
			if(!$this->status) return false;
			
			return array_keys($this->raw[3]);
		}
		
		function addTarget($pos, $type, $back)
		{
			if(!$this->status) return false;
			
			if(isset($this->raw[0][$pos])) return false;
			
			$this->raw[0][$pos] = array($type, false, $back);
			
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
			return array_shift($keys);
		}
		
		function getLastTarget($user=false)
		{
			if(!$this->status) return false;
			
			if(count($this->raw[3]) > 0)
			{
				$keys = array_keys($this->raw[3]);
				return array_pop($keys);
			}
			else
			{
				if($user === false)
				{
					$keys = array_keys($this->raw[1]);
					$user = array_shift($keys);
				}
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
			
			$this->changed = true;
			return true;
		}
		
		function addUser($user, $from, $factor=1)
		{
			if(!$this->status) return false;
			
			if(isset($this->raw[1][$user])) return false;
			
			if($this->started())
			{
				if(count($this->raw[1]) <= 0) return false;
				$keys = array_keys($this->raw[1]);
				$user2 = array_shift($keys);
				$koords = array_keys($this->raw[0]);
				$koords = array_shift($koords);
				$time = $this->calcTime($user2, $this->raw[1][$user2][1], $koords);
				$time2 = $this->calcTime($user2, $from, $koords);
				if($time2 > $time) return false;
				$factor = $time2/$time;
			}
			elseif(count($this->raw[1]) > 0) $factor = 1;
			
			$this->raw[1][$user] = array(
				array(), # Flotten
				$from, # Startkoordinaten
				$factor, # Geschwindigkeitsfaktor
				array(array(0, 0, 0, 0, 0), array()), # Mitgenommene Rohstoffe
				array(array(0, 0, 0, 0, 0), array()) # Handel
			);
			
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
				$ress_sum = array_sum($ress);
				if($ress_sum > $max_ress)
				{
					# Kuerzen
					$f = $max_ress/$ress_sum;
					$ress[0] = floor($ress[0]*$f);
					$ress[1] = floor($ress[1]*$f);
					$ress[2] = floor($ress[2]*$f);
					$ress[3] = floor($ress[3]*$f);
					$ress[4] = floor($ress[4]*$f);
					$ress_sum = array_sum($ress);
					switch($max_ress-$ress_sum)
					{
						case 4: $ress[3]++;
						case 3: $ress[2]++;
						case 2: $ress[1]++;
						case 1: $ress[0]++;
					}
				}
				$this->raw[1][$user][3][0][0] += $ress[0];
				$this->raw[1][$user][3][0][1] += $ress[1];
				$this->raw[1][$user][3][0][2] += $ress[2];
				$this->raw[1][$user][3][0][3] += $ress[3];
				$this->raw[1][$user][3][0][4] += $ress[4];
			}
			
			if($robs)
			{
				$rob_sum = array_sum($robs);
				if($rob_sum > $max_robs)
				{
					$f = $max_robs/$robs_sum;
					foreach($robs as $i=>$rob)
						$robs[$i] = floor($rob/$f);
					$rob_sum = array_sum($robs);
					$diff = $max_robs-$robs_sum;
					foreach($robs as $i=>$rob)
					{
						if($diff <= 0) break;
						$robs[$i]++;
						$diff--;
					}
				}
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
				$ress_sum = array_sum($ress);
				if($ress_sum > $max_ress)
				{
					# Kuerzen
					$f = $max_ress/$ress_sum;
					$ress[0] = floor($ress[0]*$f);
					$ress[1] = floor($ress[1]*$f);
					$ress[2] = floor($ress[2]*$f);
					$ress[3] = floor($ress[3]*$f);
					$ress[4] = floor($ress[4]*$f);
					$ress_sum = array_sum($ress);
					switch($max_ress-$ress_sum)
					{
						case 4: $ress[3]++;
						case 3: $ress[2]++;
						case 2: $ress[1]++;
						case 1: $ress[0]++;
					}
				}
				$this->raw[1][$user][4][0][0] += $ress[0];
				$this->raw[1][$user][4][0][1] += $ress[1];
				$this->raw[1][$user][4][0][2] += $ress[2];
				$this->raw[1][$user][4][0][3] += $ress[3];
				$this->raw[1][$user][4][0][4] += $ress[4];
			}
			
			if($robs)
			{
				$rob_sum = array_sum($robs);
				if($rob_sum > $max_robs)
				{
					$f = $max_robs/$robs_sum;
					foreach($robs as $i=>$rob)
						$robs[$i] = floor($rob/$f);
					$rob_sum = array_sum($robs);
					$diff = $max_robs-$robs_sum;
					foreach($robs as $i=>$rob)
					{
						if($diff <= 0) break;
						$robs[$i]++;
						$diff--;
					}
				}
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
				return $this->getTritium($user, $this->raw[1][$user][1], $this->getCurrentTarget())*$this->raw[1][$user][2]*2;
			else
			{
				$tritium = 0;
				$old_target = $this->raw[1][$user][1];
				foreach($this->raw[0] as $target=>$info)
				{
					$tritium += $this->getTritium($user, $old_target, $target);
					$old_target = $target;
				}
				if($old_target != $this->raw[1][$user][1])
					$tritium += $this->getTritium($user, $old_target, $this->raw[1][$user][1]);
				return $tritium*$this->raw[1][$user][2];
			}
		}
		
		function getTritium($user, $from, $to)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;
			
			$mass = 0;
			$user_obj = Classes::User($user);
			foreach($this->raw[1][$user][0] as $id=>$count)
			{
				$item_info = $user_obj->getItemInfo($id, 'schiffe');
				$mass += $item_info['mass']*$count;
			}
			
			return $this->getDistance($from, $to)*$mass/1000000;
		}
		
		function getScores($user, $from, $to)
		{
			if(!$this->status || !isset($this->raw[1][$user])) return false;
			
			return $this->getTritium($user, $from, $to)/1000;
		}
		
		function getTime($i)
		{
			if(!$this->status || !isset($this->raw[0][$i])) return false;
			if(count($this->raw[1]) <= 0) return false;
			
			$keys = array_keys($this->raw[1]);
			$user = array_shift($keys);
			$from = $this->raw[1][$user][1];
			$to = $i;
			
			return $this->calcTime($user, $from, $to);
		}
		
		function calcTime($user, $from, $to)
		{
			if(!$this->status || !isset($this->raw[1][$user]) || count($this->raw[1][$user]) <= 0) return false;
			
			$speeds = array();
			$user_obj = Classes::User($user);
			foreach($this->raw[1][$user][0] as $id=>$count)
			{
				$item_info = $user_obj->getItemInfo($id, 'schiffe');
				$speeds[] = $item_info['speed'];
			}
			$speed = min($speeds)/1000;
			
			$time = sqrt($this->getDistance($from, $to)/$speed)*2;
			$time /= $this->raw[1][$user][2];
			return $time;
		}
		
		function callBack($user)
		{
			if(!$this->status || !$this->started() || !isset($this->raw[1][$user]) || $this->isFlyingBack()) return false;
			
			$start = $this->raw[1][$user][1];
			$keys = array_keys($this->raw[0]);
			$to = array_shift($keys);
			if(count($this->raw[3]) > 0)
			{
				$keys = array_keys($this->raw[3]);
				$from = array_pop($keys);
			}
			else $from = $start;
			
			$time1 = $this->calcTime($user, $from, $start);
			$time2 = $this->calcTime($user, $to, $start);
			
			$progress = (time()-$this->raw[2])/$this->calcTime($user, $from, $to);
			$back_time = $time1+($time2-$time1)*$progress;
			
			$new_raw = array(
				array($start => array($this->raw[0][$from][0], 1)),
				array($user => $this->raw[1][$user]),
				time(),
				array_merge($this->raw[3], $this->raw[0])
			);
			unset($this->raw[1][$user]);
			if(count($this->raw[1]) <= 0)
			{
				unlink($this->filename) or chmod($this->filename, 0);
				$this->status = false;
				$this->changed = false;
			}
			
			$new = Classes::Fleet();
			$new->create();
			$new->setRaw($new_raw);
			
			if(count($this->raw[1]) <= 0)
				return true;
			
			$user_obj = Classes::User($user);
			$user_obj->unsetFleet($this->getName());
			$user_obj->addFleet($new->getName());
			
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
			
			return isset($this->raw[0][$target]);
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
			$koords = array_shift($koords);
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
			$fh = fopen(EVENT_FILE, 'a');
			flock($fh, LOCK_EX);
			fwrite($fh, round(time()+$time)."\t".$this->getName()."\n");
			flock($fh, LOCK_UN);
			fclose($fh);
			
			$this->changed = true;
			return true;
		}
		
		function started()
		{
			if(!$this->status) return false;
			return ($this->raw[2] !== false);
		}
		
		function getDistance($start, $target)
		{
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
			
			$keys = array_keys($this->raw[0]);
			$next_target = array_shift($keys);
			$keys2 = array_keys($this->raw[1]);
			$first_user = array_shift($keys2);
			
			$type = $this->raw[0][$next_target][0];
			$back = $this->raw[0][$next_target][1];
			
			$besiedeln = false;
			if($type == 1 && !$back)
			{
				# Besiedeln
				$target = explode(':', $next_target);
				$target_galaxy = Classes::Galaxy($target[0]);
				$target_owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);
				
				if($target_owner)
				{
					# Planet ist bereits besiedelt
					$message = Classes::Message();
					if($message->create())
					{
						$message->text('Ihre Flotte erreicht den Planeten '.$next_target.' und will mit der Besiedelung anfangen. Jedoch ist der Planet bereits vom Spieler '.$target_owner." besetzt, und Ihre Flotte macht sich auf den R\xc3\xbcckweg.");
						$message->subject('Besiedelung von '.$next_target.' fehlgeschlagen');
						$message->addUser($first_user, 5);
					}
				}
				else
					$besiedeln = true;
			}
			
			if($type != 6 && !$back && !$besiedeln)
			{
				# Nicht stationieren: Flotte fliegt weiter
				
				$target = explode(':', $next_target);
				$target_galaxy = Classes::Galaxy($target[0]);
				$target_owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);
				
				
			}
			else
			{
				# Stationieren
				
				$target = explode(':', $next_target);
				$target_galaxy = Classes::Galaxy($target[0]);
				
				if($besiedeln)
				{
					$user_obj = Classes::User($first_user);
					if(!$user_obj->registerPlanet($next_target))
						return false;
					if(isset($this->raw[1][$first_user][0]['S6']))
					{
						$this->raw[1][$first_user][0]['S6']--;
						$active_planet = $user_obj->getActivePlanet();
						$user_obj->setActivePlanet($user_obj->getPlanetByPos($next_target));
						$item_info = $user_obj->getItemInfo('S6', 'schiffe');
						$besiedelung_ress = $item_info['ress'];
						$besiedelung_ress[0] *= .4;
						$besiedelung_ress[1] *= .4;
						$besiedelung_ress[2] *= .4;
						$besiedelung_ress[3] *= .4;
						$besiedelung_ress[4] *= .4;
						$user_obj->addRess($besiedelung_ress);
					}
				}
				
				$owner = $target_galaxy->getPlanetOwner($target[1], $target[2]);
				if(!$owner)
				{
					$this->destroy();
					return false;
				}
				
				$owner_obj = Classes::User($owner);
				if(!$owner_obj->getStatus()) return false;
				
				$planet_index = $owner_obj->getPlanetByPos($next_target);
				if($planet_index === false)
				{
					$this->destroy();
					return false;
				}
				
				$owner_obj->setActivePlanet($planet_index);
				
				$ress = array(0, 0, 0, 0, 0);
				$robs = array();
				$schiffe_own = array();
				$schiffe_other = array();
				
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
				}
				
				if($besiedeln)
				{
					$message_text = "Ihre Flotte erreicht den Planeten ".$next_target." und beginnt mit seiner Besiedelung.";
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
					$message_text .= "Folgende Schiffe anderer Spieler werden fremdstationiert:\n";
					foreach($schiffe_other as $user=>$schiffe)
					{
						$message_text .= $user.": ".makeItemsString($schiffe, false)."\n";
						$owner_obj->addForeignFleet($user, $schiffe);
					}
				}
				
				$owner_obj->addRess($ress);
				$message_text .= "\nFolgende G�ter werden abgeliefert:\n";
				$message_text .= ths($ress[0], true).' Carbon, '.ths($ress[1], true).' Aluminium, '.ths($ress[2], true).' Wolfram, '.ths($ress[3], true).' Radium, '.ths($ress[4], true)." Tritium.\n";
				if(array_sum($robs) > 0)
					$message_text .= makeItemsString($robs, false)."\n";
				foreach($robs as $id=>$anzahl)
					$owner_obj->changeItemLevel($id, $anzahl, 'roboter');
				
				global $types_message_types;
				
				$message_users = array();
				foreach($this->raw[1] as $username=>$move_info)
				{
					$message_user_obj = Classes::User($username);
					$receive = $message_user_obj->checkSetting('receive');
					if(!isset($receive[$types_message_types[$this->raw[0][$next_target][0]]][$this->raw[0][$next_target][1]]) || $receive[$types_message_types[$this->raw[0][$next_target][0]]][$this->raw[0][$next_target][1]])
						$message_users[] = $username;
				}
				
				if(count($message_users) > 0)
				{
					$message_obj = Classes::Message();
					if($message_obj->create())
					{
						$message_obj->text($message_text);
						if($besiedeln)
							$message_obj->subject("Besiedelung von ".$next_target);
						else
							$message_obj->subject("Stationierung auf ".$owner_obj->getPosString());
						$message_obj->from($owner_obj->getName());
						foreach($message_users as $username)
							$message_obj->addUser($username, $types_message_types[$this->raw[0][$next_target][0]]);
					}
				}
				
				$this->destroy();
			}
		}
		
		protected function getDataFromRaw(){}
		protected function getRawFromData(){}
	}
	
	function battle($angreifer, $verteidiger)
	{
		if(count($angreifer) < 0 || count($verteidiger) < 0) return false;
		
		$angreifer_anfang = $angreifer;
		$verteidiger_anfang = $verteidiger;
		
		$users_angreifer = array();
		$users_verteidiger = array();
		
		foreach($angreifer as $name=>$flotte)
		{
			$users_angreifer[$name] = Classes::User($name);
			if(!$users_angreifer[$name]->getStatus()) return false;
		}
		
		foreach($verteidiger as $name=>$flotte)
		{
			$users_verteidiger[$name] = Classes::User($name);
			if(!$users_verteidiger[$name]->getStatus()) return false;
		}
		
		
		# Spionagetechnik fuer Erstschlag
		$angreifer_spiotech = 0;
		foreach($users_angreifer as $user)
			$angreifer_spiotech += $user->getItemLevel('F1', 'forschung');
		$angreifer_spiotech /= count($users_angreifer);
		
		$verteidiger_spiotech = 0;
		foreach($users_verteidiger as $user)
			$verteidiger_spiotech += $user->getItemLevel('F1', 'forschung');
		$verteidiger_spiotech /= count($user_verteidiger);
		
		
		# Kampferfahrung
		$angreifer_erfahrung = 0;
		foreach($users_angreifer as $user)
			$angreifer_erfahrung += $user->getScores(6);
		$verteidiger_erfahrung = 0;
		foreach($users_verteidiger as $user)
			$verteidiger_erfahrung += $user->getScores(6);


		# Nachrichtentext
		$nachrichten_text = '';
		if(count($angreifer) > 1)
			$nachrichten_text .= "<h3>Flotten der Angreifer</h3>";
		else
			$nachrichten_text .= "<h3>Flotten des Angreifers</h3>";
	
		$nachrichten_text .= "<table>\n";
		$nachrichten_text .= "\t<thead>\n";
		$nachrichten_text .= "\t\t<tr>\n";
		$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
		$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
		$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
		$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
		$nachrichten_text .= "\t\t</tr>\n";
		$nachrichten_text .= "\t</thead>\n";
		$nachrichten_text .= "\t<tbody>\n";

		$ges_anzahl = $ges_staerke = $ges_schild = 0;
		foreach($angreifer as $name=>$flotten)
		{
			$nachrichten_text .= "\t\t<tr class=\"benutzername\">\n";
			$nachrichten_text .= "\t\t\t<th colspan=\"4\">".utf8_htmlentities($name)."</th>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			
			$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
			foreach($flotten as $id=>$anzahl)
			{
				$item_info = $users_angreifer[$name]->getItemInfo($id);
				
				$staerke = $item_info['att']*$anzahl;
				$schild = $item_info['def']*$anzahl;
	
				$nachrichten_text .= "\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
	
				$this_ges_anzahl += $anzahl;
				$this_ges_staerke += $staerke;
				$this_ges_schild += $schild;
			}
			
			$nachrichten_text .= "\t\t<tr class=\"gesamt\">\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			
			$ges_anzahl += $this_ges_anzahl;
			$ges_staerke += $this_ges_staerke;
			$ges_schild += $this_ges_schild;
		}
		
		$nachrichten_text .= "\t</tbody>\n";
		
		if(count($angreifer) > 1)
		{
			$nachrichten_text .= "\t<tfoot>\n";
			$nachrichten_text .= "\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			$nachrichten_text .= "\t</tfoot>\n";
		}
		$nachrichten_text .= "</table>\n";
		
		$nachrichten_text = '';
		if(count($verteidiger) > 1)
			$nachrichten_text .= "<h3>Flotten der Verteidigers</h3>";
		else
			$nachrichten_text .= "<h3>Flotten der Verteidiger</h3>";
		if(array_sum_r($verteidiger) <= 0)
		{
			$nachrichten_text .= "<p class=\"keine\">\n";
			$nachrichten_text .= "\tKeine.\n";
			$nachrichten_text .= "</p>\n";
		}
		else
		{
			$nachrichten_text .= "<table>\n";
			$nachrichten_text .= "\t<thead>\n";
			$nachrichten_text .= "\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			$nachrichten_text .= "\t</thead>\n";
			$nachrichten_text .= "\t<tbody>\n";
	
			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($verteidiger as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t<th colspan=\"4\">".utf8_htmlentities($name)."</th>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				
				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				foreach($flotten as $id=>$anzahl)
				{
					$item_info = $users_verteidiger[$name]->getItemInfo($id);
					
					$staerke = $item_info['att']*$anzahl;
					$schild = $item_info['def']*$anzahl;
		
					$nachrichten_text .= "\t\t<tr>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
					$nachrichten_text .= "\t\t</tr>\n";
		
					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}
				
				$nachrichten_text .= "\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				
				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}
			
			$nachrichten_text .= "\t</tbody>\n";
			
			if(count($verteidiger) > 1)
			{
				$nachrichten_text .= "\t<tfoot>\n";
				$nachrichten_text .= "\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				$nachrichten_text .= "\t</tfoot>\n";
			}
			$nachrichten_text .= "</table>\n";
		}
		
		if(count($angreifer) > 1)
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
		if(count($verteidiger) > 1)
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
			$veteidiger_praedikat2 = 'hat';
			$verteidiger_nominativ_letzt = 'letzterer';
			$verteidiger_genitiv = 'des Verteidigers';
		}
		

		# Erstschlag
		if($angreifer_spiotech > $verteidiger_spiotech)
		{
			$runde_starter = 'angreifer';
			$runde_anderer = 'verteidiger';

			$nachrichten_text .= "<p class=\"erstschlag angreifer\">\n";
			$nachrichten_text .= "\tDie Sensoren ".$angreifer_genitiv." sind st\xc3\xa4rker ausgebildet als die ".$verteidiger_genitiv." und erm\xc3\xb6glichen es ".$angreifer_dativ.", den Erstschlag auszuf\xc3\xbchren.\n";
			$nachrichten_text .= "</p>\n";
		}
		else
		{
			$runde_starter = 'verteidiger';
			$runde_anderer = 'angreifer';

			$nachrichten_text .= "<p class=\"erstschlag verteidiger\">\n";
			$nachrichten_text .= "\tDie Sensoren ".$angreifer_genitiv." sind denen ".$verteidiger_genitiv." nicht \xc3\xbcberlegen, weshalb ".$verteidiger_nominativ_letzt." den Erstschlag ausf\xc3\xbchrt.\n";
			$nachrichten_text .= "</p>\n";
		}

		if(count($verteidiger_flotte) <= 0)
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

		# Einzelne Runden
		for($runde = 1; $runde <= 20; $runde++)
		{
			$a = & ${$runde_starter};
			$d = & ${$runde_anderer};
			$a_objs = & ${'users_'.$runde_starter};
			$d_objs = & ${'users_'.$runde_anderer};

			if(count($angreifer) <= 0 || count($verteidiger) <= 0) break;
			
			if($runde%2)
			{
				$nachrichten_text .= "<div class=\"runde\">\n";
				$nachrichten_text .= "\t<h3>Runde ".(($runde+1)/2)."</h3>\n";
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
			
			$nachrichten_text .= "\t<h4>".${$runde_starter.'_nominativ'}." ".${$runde_starter.'_praedikat'}." am Zug (Gesamtst\xc3\xa4rke ".round($staerke).")</h4>\n";
			$nachrichten_text .= "\t<ol>\n";

			while($staerke > 0)
			{
				$av_users = array_keys($d);
				$att_user = $av_users[array_rand($av_users)];
				$av_ids = array_keys($d[$att_user]);
				$att_id = $av_ids[array_rand($av_ids)];
				
				$item_info = ${'users_'.$runde_anderer}[$att_user]->getItemInfo($att_id);
				$this_shield = $item_info['def']*$d[$att_user][$att_id];
				
				$schild_f = pow(0.95, ${'users_'.$runde_anderer}[$att_user]);
				$aff_staerke = $staerke*$schild_f;
				
				if($this_shield > $aff_staerke)
				{
					$this_shield -= $aff_staerke;
					$before = $d[$att_user][$add_id];
					$d[$att_user][$att_id] = $this_shield/$item_info['def'];
					$diff = $before-$d[$att_user][$att_id];
					$floor_diff = floor($diff);
					$nachrichten_text .= "\t\t<li>";
					if($floor_diff <= 0)
						$nachrichten_text .= "Eine Einheit des Typs ".utf8_htmlentities($item_info['name'])." (".utf8_htmlentities($att_user).") wird angeschossen.</li>\n";
					else
					{
						$nachrichten_text .= ths($floor_diff)."&nbsp;Einheit";
						if($floor_diff != 1) $nachrichten_text .= "en";
						$nachrichten_text .= " des Typs ".utf8_htmlentities($item_info['name'])." (".utf8_htmlentities($att_user).") werden zerst\xc3\xb6rt. ".$d[$att_user][$att_id]." verbleiben.</li>\n";
					}
					$staerke = 0;
				}
				else
				{
					$nachrichten_text .= "\t\t<li>Alle Einheiten des Typs ".utf8_htmlentities($item_info['name'])." (".ths(ceil($d[$att_user][$att_id])).") (".utf8_htmlentities($att_user).") werden zerst\xc3\xb6rt.</li>\n";
					$aff_staerke = $this_shield;
					unset($d[$att_user][$att_id]);
					if(count($d[$att_user]) <= 0) unset($d[$att_user]);
					$staerke -= $aff_staerke/$schild_f;
				}
			}

			$nachrichten_text .= "\t</ol>\n";
			if(!$runde%2)
				$nachrichten_text .= "</div>\n";

			# Vertauschen
			list($runde_starter, $runde_anderer) = array($runde_anderer, $runde_starter);
			unset($a);
			unset($d);
			unset($a_objs);
			unset($d_objs);
		}

		$nachrichten_text .= "<p>\n";
		$nachrichten_text .= "\tDer Kampf ist vor\xc3\xbcber. ";
		if(count($angreifer) == 0)
		{
			$nachrichten_text .= "Gewinner ".$verteidiger_praedikat." ".$verteidiger_nominativ.".";
			$winner = -1;
		}
		elseif(count($verteidiger_flotte) == 0)
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
		$nachrichten_text .= "</p>\n";

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
			$nachrichten_text .= "<h3>Flotten der Angreifer</h3>";
		else
			$nachrichten_text .= "<h3>Flotten des Angreifers</h3>";
		if(array_sum_r($angreifer) <= 0)
		{
			$nachrichten_text .= "<p class=\"keine\">\n";
			$nachrichten_text .= "\tKeine.\n";
			$nachrichten_text .= "</p>\n";
		}
		else
		{
			$nachrichten_text .= "<table>\n";
			$nachrichten_text .= "\t<thead>\n";
			$nachrichten_text .= "\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			$nachrichten_text .= "\t</thead>\n";
			$nachrichten_text .= "\t<tbody>\n";
	
			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($angreifer_anfang as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t<th colspan=\"4\">".utf8_htmlentities($name)."</th>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				
				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				$angreifer_punkte[$name] = 0;
				if(!isset($angreifer[$name])) $angreifer[$name] = array();
				foreach($flotten as $id=>$old_anzahl)
				{
					$item_info = $users_angreifer[$name]->getItemInfo($id, false, true, true);
					
					if(isset($angreifer[$name]) && isset($angreifer[$name][$id]))
						$anzahl = $angreifer[$name][$id];
					else
					{
						$anzahl = 0;
						$angreifer[$name][$id] = 0;
					}
					
					$diff = $anzahl_old-$anzahl;
					$truemmerfeld[0] += $item_info['ress'][0]*$diff*.4;
					$truemmerfeld[1] += $item_info['ress'][1]*$diff*.4;
					$truemmerfeld[2] += $item_info['ress'][2]*$diff*.4;
					$truemmerfeld[3] += $item_info['ress'][3]*$diff*.4;
					$angreifer_punkte[$name] += $item_info['simple_scores']*$diff;
					
					$staerke = $item_info['att']*$anzahl;
					$schild = $item_info['def']*$anzahl;
		
					$nachrichten_text .= "\t\t<tr>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
					$nachrichten_text .= "\t\t</tr>\n";
		
					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}
				
				$nachrichten_text .= "\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				
				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}
			
			$nachrichten_text .= "\t</tbody>\n";
			
			if(count($angreifer_anfang) > 1)
			{
				$nachrichten_text .= "\t<tfoot>\n";
				$nachrichten_text .= "\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				$nachrichten_text .= "\t</tfoot>\n";
			}
			$nachrichten_text .= "</table>\n";
		}
		
		$nachrichten_text = '';
		if(count($verteidiger_anfang) > 1)
			$nachrichten_text .= "<h3>Flotten der Verteidigers</h3>";
		else
			$nachrichten_text .= "<h3>Flotten der Verteidiger</h3>";
		if(array_sum_r($verteidiger) <= 0)
		{
			$nachrichten_text .= "<p class=\"keine\">\n";
			$nachrichten_text .= "\tKeine.\n";
			$nachrichten_text .= "</p>\n";
		}
		else
		{
			$nachrichten_text .= "<table>\n";
			$nachrichten_text .= "\t<thead>\n";
			$nachrichten_text .= "\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
			$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			$nachrichten_text .= "\t</thead>\n";
			$nachrichten_text .= "\t<tbody>\n";
	
			$ges_anzahl = $ges_staerke = $ges_schild = 0;
			foreach($verteidiger_anfang as $name=>$flotten)
			{
				$nachrichten_text .= "\t\t<tr class=\"benutzername\">\n";
				$nachrichten_text .= "\t\t\t<th colspan=\"4\">".utf8_htmlentities($name)."</th>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				
				$this_ges_anzahl = $this_ges_staerke = $this_ges_schild = 0;
				$verteidiger_punkte[$name] = 0;
				$verteidiger_ress[$name] = array(0, 0, 0, 0);
				foreach($flotten as $id=>$anzahl_old)
				{
					$item_info = $users_verteidiger[$name]->getItemInfo($id, false, true, true);
					
					if(isset($verteidiger[$name]) && isset($verteidiger[$id]))
						$anzahl = $angreifer[$name][$id];
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
		
					$nachrichten_text .= "\t\t<tr>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($item_info['name'])."</a></td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl)."</td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
					$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
					$nachrichten_text .= "\t\t</tr>\n";
		
					$this_ges_anzahl += $anzahl;
					$this_ges_staerke += $staerke;
					$this_ges_schild += $schild;
				}
				
				$nachrichten_text .= "\t\t<tr class=\"gesamt\">\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($this_ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($this_ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($this_ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				
				$ges_anzahl += $this_ges_anzahl;
				$ges_staerke += $this_ges_staerke;
				$ges_schild += $this_ges_schild;
			}
			
			$nachrichten_text .= "\t</tbody>\n";
			
			if(count($verteidiger) > 1)
			{
				$nachrichten_text .= "\t<tfoot>\n";
				$nachrichten_text .= "\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";
				$nachrichten_text .= "\t</tfoot>\n";
			}
			$nachrichten_text .= "</table>\n";
		}
		
		$nachrichten_text .= "<ul class=\"angreifer-punkte\">\n";
		foreach($angreifer_punkte as $a=>$p)
			$nachrichten_text .= "\t<li>Der Angreifer <span class=\"koords\">".utf8_htmlentities($a)."</span> hat ".ths($p)."&nbsp;Punkte verloren.</li>\n";
		$nachrichten_text .= "</ul>\n";
		$nachrichten_text .= "<ul class=\"verteidiger-punkte\">\n";
		foreach($verteidiger_punkte as $v=>$p)
			$nachrichten_text .= "\t<li>Der Verteidiger <span class=\"koords\">".utf8_htmlentities($v)."</span> hat ".ths($p)."&nbsp;Punkte verloren.</li>\n";
		$nachrichten_text .= "</ul>\n";

		if(array_sum($truemmerfeld) > 0)
		{
			# Truemmerfeld

			$truemmerfeld[0] = round($truemmerfeld[0]);
			$truemmerfeld[1] = round($truemmerfeld[1]);
			$truemmerfeld[2] = round($truemmerfeld[2]);
			$truemmerfeld[3] = round($truemmerfeld[3]);

			truemmerfeld::add($target_pos[0], $target_pos[1], $target_pos[2], $truemmerfeld[0], $truemmerfeld[1], $truemmerfeld[2], $truemmerfeld[3]);

			$nachrichten_text .= "<p>\n";
			$nachrichten_text .= "\tFolgende Tr\xc3\xbcmmer zerst\xc3\xb6rter Schiffe sind durch dem Kampf in die Umlaufbahn des Planeten gelangt: ".ths($truemmerfeld[0])."&nbsp;Carbon, ".ths($truemmerfeld[1])."&nbsp;Aluminium, ".ths($truemmerfeld[2])."&nbsp;Wolfram und ".ths($truemmerfeld[3])."&nbsp;Radium.\n";
			$nachrichten_text .= "</p>\n";
		}

		# Kampferfahrung
		$angreifer_new_erfahrung = array_sum($verteidiger_punkte)/1000;
		$verteidiger_new_erfahrung = array_sum($angreifer_punkte)/1000;
		$nachrichten_text .= "<ul class=\"kampferfahrung\">\n";
		foreach($users_angreifer as $user)
		{
			$user->addScores(6, $angreifer_new_erfahrung);
			$nachrichten_text .= "\t<li class=\"c-angreifer\">".$angreifer_nominativ." ".$angreifer_praedikat2." ".ths($angreifer_new_erfahrung)."&nbsp;Kampferfahrungspunkte gesammelt.</li>\n";
		}
		foreach($users_verteidiger as $user)
		{
			$user->addScores(6, $verteidiger_new_erfahrung);
			$nachrichten_text .= "\t<li class=\"c-angreifer\">".$verteidiger_nominativ." ".$verteidiger_praedikat2." ".ths($verteidiger_new_erfahrung)."&nbsp;Kampferfahrungspunkte gesammelt.</li>\n";
		}

		return array($winner, $angreifer, $verteidiger, $nachrichten_text, $verteidiger_ress);
	}
	
	function array_sum_r($array)
	{
		$sum = 0;
		foreach($array as $val)
			$sum += array_sum($val);
		return $sum;
	}
?>