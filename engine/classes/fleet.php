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
		if(count($angreifer) > 1)
			$nachrichten_text .= "<h3>Flotten der Verteidigers</h3>";
		else
			$nachrichten_text .= "<h3>Flotten der Verteidiger</h3>";
	
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
		
		if(count($angreifer) > 1)
		{
			$angreifer_nominativ = 'die Angreifer';
			$angreifer_praedikat = 'sind';
			$angreifer_genitiv = 'der Angreifer';
			$angreifer_dativ = 'ihnen';
		}
		else
		{
			$angreifer_nominativ = 'der Angreifer';
			$angreifer_praedikat = 'ist';
			$angreifer_genitiv = 'des Angreifers';
			$angreifer_dativ = 'ihm';
		}
		if(count($verteidiger) > 1)
		{
			$verteidiger_nominativ = 'die Verteidiger';
			$verteidiger_praedikat = 'sind';
			$verteidiger_nominativ_letzt = 'letztere';
			$verteidiger_genitiv = 'der Verteidiger';
		}
		else
		{
			$verteidiger_nominativ = 'der Verteidiger';
			$verteidiger_praedikat = 'ist';
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

		$truemmerfeld = array(0, 0, 0, 0);

		# Einzelne Runden
		for($runde = 1; $runde <= 20; $runde++)
		{
			$a = & ${$runde_starter};
			$d = & ${$runde_anderer};
			$a_objs = & ${'users_'.$runde_starter};
			$d_objs = & ${'users_'.$runde_anderer};

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
				$av_ids = array_keys($d[$att_user])
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
						.ths($floor_diff)."&nbsp;Einheit";
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
			$nachrichten_text .= "Gewinner ".$verteidiger_praedikat." ".$verteidiger_nominativ.".";
		elseif(count($verteidiger_flotte) == 0)
			$nachrichten_text .= "Gewinner ".$angreifer_praedikat." ".$angreifer_nominativ.".";
		else $nachrichten_text .= "Er endet unentschieden.";
		$nachrichten_text .= "\n";
		$nachrichten_text .= "</p>\n";

		$nachrichten_text .= "<h3>Flotten des Angreifers <span class=\"koords\">".utf8_htmlentities($start_info[1])."</span></h3>\n";
		if(count($angreifer_flotte) > 0)
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
			foreach($angreifer_flotte as $id=>$anzahl)
			{
				$staerke = round($items['ids'][$id]['att']*$anzahl[0]*pow(1.05, $angreifer_waffentechnik));
				$schild = round($anzahl[1]);

				$nachrichten_text .= "\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($items['ids'][$id]['name'])."</a></td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl[0])."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";

				$ges_anzahl += $anzahl[0];
				$ges_staerke += $staerke;
				$ges_schild += $schild;
			}
			$nachrichten_text .= "\t</tbody>\n";
			$nachrichten_text .= "\t<tfoot>\n";
			$nachrichten_text .= "\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			$nachrichten_text .= "\t</tfoot>\n";
			$nachrichten_text .= "</table>\n";
		}
		else
		{
			$nachrichten_text .= "<p class=\"keine\">\n";
			$nachrichten_text .= "\tKeine.\n";
			$nachrichten_text .= "</p>\n";
		}

		$nachrichten_text .= "<h3>Flotten des Verteidigers <span class=\"koords\">".utf8_htmlentities($target_info[1])."</span></h3>\n";
		if(count($verteidiger_flotte) > 0)
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
			foreach($verteidiger_flotte as $id=>$anzahl)
			{
				$staerke = round($items['ids'][$id]['att']*$anzahl[0]*pow(1.05, $verteidiger_waffentechnik));
				$schild = round($anzahl[1]);

				$nachrichten_text .= "\t\t<tr>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($items['ids'][$id]['name'])."</a></td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl[0])."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
				$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
				$nachrichten_text .= "\t\t</tr>\n";

				$ges_anzahl += $anzahl[0];
				$ges_staerke += $staerke;
				$ges_schild += $schild;
			}
			$nachrichten_text .= "\t</tbody>\n";
			$nachrichten_text .= "\t<tfoot>\n";
			$nachrichten_text .= "\t\t<tr>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
			$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
			$nachrichten_text .= "\t\t</tr>\n";
			$nachrichten_text .= "\t</tfoot>\n";
			$nachrichten_text .= "</table>\n";
		}
		else
		{
			$nachrichten_text .= "<p class=\"keine\">\n";
			$nachrichten_text .= "\tKeine.\n";
			$nachrichten_text .= "</p>\n";
		}
		# Flottenbestaende neu eintragen

		# Angreifer
		# Verlorene Punkte
		$angreifer_punkte = 0;
		foreach($flotte[0] as $id=>$anzahl)
		{
			if(!isset($items['schiffe'][$id]))
				continue;
			$a = $anzahl;
			if(isset($angreifer_flotte[$id]))
				$a -= $angreifer_flotte[$id][0];
			$angreifer_punkte += array_sum($items['schiffe'][$id]['ress'])*$a;
		}
		$angreifer_punkte /= 1000;
		$start_user_array['punkte'][3] -= $angreifer_punkte;
		$target_user_array['punkte'][6] += $angreifer_punkte/1000;

		$flotte[0] = array();
		foreach($angreifer_flotte as $id=>$anzahl)
			$flotte[0][$id] = $anzahl[0];

		# Verteidiger
		# Verlorene Punkte
		$verteidiger_punkte_schiffe = 0;
		$verteidiger_punkte_vert = 0;
		$verteidiger_ress = array(0,0,0,0);
		foreach($that_planet['schiffe'] as $id=>$anzahl)
		{
			if(!isset($items['schiffe'][$id]))
				continue;
			$a = $anzahl;
			if(isset($verteidiger_flotte[$id]))
				$a -= $verteidiger_flotte[$id][0];
			$verteidiger_punkte_schiffe += array_sum($items['schiffe'][$id]['ress'])*$a;
		}
		foreach($that_planet['verteidigung'] as $id=>$anzahl)
		{
			if(!isset($items['verteidigung'][$id]))
				continue;
			$a = $anzahl;
			if(isset($verteidiger_flotte[$id]))
				$a -= $verteidiger_flotte[$id][0];
			$verteidiger_punkte_vert += array_sum($items['verteidigung'][$id]['ress'])*$a;
			$verteidiger_ress[0] += $items['verteidigung'][$id]['ress'][0]*0.8;
			$verteidiger_ress[1] += $items['verteidigung'][$id]['ress'][1]*0.8;
			$verteidiger_ress[2] += $items['verteidigung'][$id]['ress'][2]*0.8;
			$verteidiger_ress[3] += $items['verteidigung'][$id]['ress'][3]*0.8;
		}
		$verteidiger_punkte_schiffe /= 1000;
		$verteidiger_punkte_vert /= 1000;
		$verteidiger_punkte = $verteidiger_punkte_schiffe+$verteidiger_punkte_vert;

		$target_user_array['punkte'][3] -= $verteidiger_punkte_schiffe;
		$target_user_array['punkte'][4] -= $verteidiger_punkte_vert;
		$start_user_array['punkte'][6] += $verteidiger_punkte/1000;

		$that_planet['schiffe'] = array();
		$that_planet['verteidigung'] = array();

		foreach($verteidiger_flotte as $id=>$anzahl)
		{
			if(isset($items['verteidigung'][$id]))
				$that_planet['verteidigung'][$id] = $anzahl[0];
			elseif(isset($items['schiffe'][$id]))
				$that_planet['schiffe'][$id] = $anzahl[0];
		}

		# Koordinaten vertauschen
		list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

		if(count($verteidiger_flotte) <= 0)
		{
			# Transportkapazitaet berechnen
			$transport = 0;
			foreach($flotte[0] as $id=>$anzahl)
				$transport += $items['schiffe'][$id]['trans'][0]*$anzahl;

			# Laderaumerweiterung
			$l_level = 0;
			if(isset($user_array['forschung']['F11']))
				$l_level = $user_array['forschung']['F11'];
			$transport = floor($transport*pow(1.2, $l_level));
			$transport -= array_sum($flotte[5][0]);

			# Rohstoffe erbeuten

			# Maximal erbeutbar
			$erbeut = array();
			$erbeut[0] = floor($that_planet['ress'][0]/2);
			$erbeut[1] = floor($that_planet['ress'][1]/2);
			$erbeut[2] = floor($that_planet['ress'][2]/2);
			$erbeut[3] = floor($that_planet['ress'][3]/2);
			$erbeut[4] = floor($that_planet['ress'][4]/2);

			# Im Verhaeltnis mit Ladekapazitaet abgleichen
			$erbeut_sum = array_sum($erbeut);
			if($erbeut_sum > 0)
			{
				$k = $transport/$erbeut_sum;
				if($k < 1)
				{
					$erbeut[0] = floor($erbeut[0]*$k);
					$erbeut[1] = floor($erbeut[1]*$k);
					$erbeut[2] = floor($erbeut[2]*$k);
					$erbeut[3] = floor($erbeut[3]*$k);
					$erbeut[4] = floor($erbeut[4]*$k);

					# Rundungsfehler ausmerzen
					$uebrig = $transport-array_sum($erbeut);
					$jedes = floor($uebrig/5);
					$erbeut[0] += $jedes;
					$erbeut[1] += $jedes;
					$erbeut[2] += $jedes;
					$erbeut[3] += $jedes;
					$erbeut[4] += $jedes;
					$uebrig = $uebrig%5;
					switch($uebrig)
					{
						case 4: $erbeut[3]++;
						case 3: $erbeut[2]++;
						case 2: $erbeut[1]++;
						case 1: $erbeut[0]++;
					}
				}
			}

			# Rohstoffe vom Planeten abziehen und beladen
			$that_planet['ress'][0] -= $erbeut[0];
			$that_planet['ress'][1] -= $erbeut[1];
			$that_planet['ress'][2] -= $erbeut[2];
			$that_planet['ress'][3] -= $erbeut[3];
			$that_planet['ress'][4] -= $erbeut[4];

			$flotte[5][0][0] += $erbeut[0];
			$flotte[5][0][1] += $erbeut[1];
			$flotte[5][0][2] += $erbeut[2];
			$flotte[5][0][3] += $erbeut[3];
			$flotte[5][0][4] += $erbeut[4];
		}

		if(count($angreifer_flotte) > 0)
		{
			# Flotte umkehren

			# Zeit neu berechnen
			$distance = fleet::get_distance($flotte[3][0], $flotte[3][1]);
			# Masse und Geschwindigkeit
			$mass = 0;
			$speed = 0;
			foreach($flotte[0] as $id=>$anzahl)
			{
				if(!isset($items['schiffe'][$id]))
					continue;
				$mass += $items['schiffe'][$id]['mass']*$anzahl;
				$speed += $items['schiffe'][$id]['speed']*$anzahl;
			}
			$mass += fleet::get_tritium($mass, $distance);
			$mass += array_sum($flotte[5][0]);
			foreach($flotte[5][1] as $id=>$anzahl)
			{
				if(!isset($items['roboter'][$id]))
					continue;
				$mass += $items['roboter'][$id]['mass']*$anzahl;
			}
			$time_diff = fleet::get_time($mass, $distance, $speed);
			# Geschwindigkeitsfaktor
			$time_diff /= $flotte[6];
			$flotte[1] = array($flotte[1][1], $flotte[1][1]+$time_diff);

			# Rueckflug?
			$flotte[7] = true;

			if(!isset($start_user_array['flotten'][$i]))
				report_error(1);
			$start_user_array['flotten'][$i] = $flotte;
			uasort($start_user_array['flotten'], 'usort_fleet');
			eventhandler::add_event($flotte[1][1]);
		}
		else
		{
			if(!isset($start_user_array['flotten'][$i]))
				report_error(2);
			unset($start_user_array['flotten'][$i]);
		}

		if(!isset($target_user_array['flotten'][$i]))
			report_error(3);
		unset($target_user_array['flotten'][$i]);

		# Dem Verteidiger Verteidigungsrohstoffe zurueckerstatten
		$verteidiger_ress[0] *= 0.25;
		$verteidiger_ress[1] *= 0.25;
		$verteidiger_ress[2] *= 0.25;
		$verteidiger_ress[3] *= 0.25;
		$that_planet['ress'][0] += $verteidiger_ress[0];
		$that_planet['ress'][1] += $verteidiger_ress[1];
		$that_planet['ress'][2] += $verteidiger_ress[2];
		$that_planet['ress'][3] += $verteidiger_ress[3];

		# Punkte in Nachrichten eintragen
		$nachrichten_text .= "<p>\n";
		$nachrichten_text .= "\tDer Angreifer <span class=\"koords\">".utf8_htmlentities($start_info[1])."</span> hat ".ths(round($angreifer_punkte))."&nbsp;Punkte verloren. Der Verteidiger <span class=\"koords\">".utf8_htmlentities($target_info[1])."</span> hat ".ths(round($verteidiger_punkte_schiffe+$verteidiger_punkte_vert))."&nbsp;Punkte verloren.";
		$nachrichten_text .= "</p>\n";

		if(count($verteidiger_flotte) <= 0)
		{
			# Erbeutete Rohstoffe
			$nachrichten_text .= "<p>\n";
			$nachrichten_text .= "\tDer Angreifer erbeutet ".ths($erbeut[0])."&nbsp;Carbon, ".ths($erbeut[1])."&nbsp;Aluminium, ".ths($erbeut[2])."&nbsp;Wolfram, ".ths($erbeut[3])."&nbsp;Radium und ".ths($erbeut[4])."&nbsp;Tritium.\n";
			$nachrichten_text .= "</p>\n";
		}

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

		$vert_nachrichten_text = $nachrichten_text;

		$nachrichten_text .= "<p>\n";
		$nachrichten_text .= "\tDieser Kampf hat Ihnen ".ths(round(($verteidiger_punkte_schiffe+$verteidiger_punkte_vert)/1000))."&nbsp;Kampferfahrungspunkte eingebracht.\n";
		$nachrichten_text .= "</p>";

		$vert_nachrichten_text .= "<p>\n";
		$vert_nachrichten_text .= "\tDieser Kampf hat Ihnen ".ths(round($angreifer_punkte/1000))."&nbsp;Kampferfahrungspunkte eingebracht.\n";
		$vert_nachrichten_text .= "</p>\n";

		$vert_nachrichten_text .= "<p>\n";
		$vert_nachrichten_text .= "\t".ths($verteidiger_ress[0])."&nbsp;Carbon, ".ths($verteidiger_ress[1])."&nbsp;Aluminium, ".ths($verteidiger_ress[2])."&nbsp;Wolfram und ".ths($verteidiger_ress[3])."&nbsp;Radium konnten aus den Tr�mmern der zerst�rten Verteidigungsanlagen wiederhergestellt werden.\n";
		$vert_nachrichten_text .= "</p>";


		# User-Arrays speichern
		if(!$start_own)
			write_user_array($start_info[1], $start_user_array);
		if(!$target_own)
			write_user_array($target_info[1], $target_user_array);

		# Nachrichten versenden
		messages::new_message(array($start_info[1]=>1), '', 'Angriff Ihrer Flotte auf '.$flotte[3][0], $nachrichten_text, true);
		messages::new_message(array($target_info[1]=>1), $start_info[1], 'Angriff einer fremden Flotte auf '.$flotte[3][0], $vert_nachrichten_text, true);

		unset($start_user_array);
		unset($target_user_array);
		if(isset($that_planet))
			unset($that_planet);

		# Highscores neu berechnen
		if($angreifer_punkte > 0 || $verteidiger_punkte > 0)
		{
			highscores::recalc2($start_info[1]);
			highscores::recalc2($target_info[1]);
		}
		# return $angreifer, $verteidiger, $kampfberichte
	}
	
	function array_sum_r($array)
	{
		$sum = 0;
		foreach($array as $val)
			$sum += array_sum($val);
		return $sum;
	}
?>