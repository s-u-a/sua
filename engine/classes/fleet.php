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
		
		function fleetExists($fleet)
		{
			$filename = DB_FLEETS.'/'.urlencode($fleet);
			return (is_file($filename) && is_readable($filename));
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
			if(!$this->status || !$this->started()) return false;
			
			$users = array_keys($this->raw[1]);
			$duration = $this->calcTime(array_shift($users), $this->getLastTarget(), $this->getCurrentTarget());
			return $this->raw[2]+$duration;
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
				$trans[1] += $Item_info['trans'][1]*$count;
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
			return $this->getDistance($from, $to)*$mass;
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
			$speed = min($speed)*array_sum($this->raw[1][$user][0]);
			
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
				
				$new = Classes::Fleet();
				$new->create();
				$new->setRaw($new_raw);
				return true;
			}
			
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
			if(array_sum($this->raw[1][array_shift($keys)][0]) <= 0) return false;
			
			# Geschwindigkeitsfaktoren der anderen Teilnehmer abstimmen
			if(count($keys) > 1)
			{
				$koords = array_keys($this->raw[0]);
				$koords = array_shift($koords);
				$time = $this->calcTime($user, $this->raw[1][$user][1], $koords);
				foreach($keys as $key)
				{
					$this_time = calcTime($key, $this->raw[1][$key][1], $koords);
					$this->raw[1][$key][2] = $this_time/$time;
				}
			}
			
			$this->raw[2] = time();
			
			$this->changed = true;
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
		
		protected function getDataFromRaw(){}
		protected function getRawFromData(){}
	}
?>