<?php
	class User extends Dataset
	{
		protected $save_dir = DB_PLAYERS;
		protected $active_planet = false;
		protected $planet_info = false;
		private $datatype = 'user';
		
		function create()
		{
			if(file_exists($this->filename)) return false;
			$this->raw = array(
				'username' => $user,
				'planets' => array()
				);
			$this->write(true);
			$this->__construct($this->name);
			return true;
		}
		
		function userExists($user)
		{
			$filename = DB_PLAYERS.'/'.urlencode($user);
			return (is_file($filename) && is_readable($filename));
		}
		
		function planetExists($planet)
		{
			if(!$this->status) return false;
			
			return isset($this->raw['planets'][$planet]);
		}
		
		function setActivePlanet($planet)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['planets'][$planet]))
				return false;
			
			if(isset($this->planet_info))
			{
				if(isset($this->items['gebaeude'])) $this->planet_info['gebaeude'] = $this->items['gebaeude'];
				if(isset($this->items['roboter'])) $this->planet_info['roboter'] = $this->items['roboter'];
				if(isset($this->items['schiffe'])) $this->planet_info['schiffe'] = $this->items['schiffe'];
				if(isset($this->items['verteidigung'])) $this->planet_info['verteidigung'] = $this->items['verteidigung'];
			}
			
			$this->active_planet = $planet;
			$this->planet_info = &$this->raw['planets'][$planet];
			
			if(isset($this->cache['getPos'])) unset($this->cache['getPos']);
			
			$this->items['gebaeude'] = $this->planet_info['gebaeude'];
			$this->items['roboter'] = $this->planet_info['roboter'];
			$this->items['schiffe'] = $this->planet_info['schiffe'];
			$this->items['verteidigung'] = $this->planet_info['verteidigung'];
			
			$this->items['ids'] = array();
			foreach($this->items['gebaeude'] as $id=>$level)
				$this->items['ids'][$id] = & $this->items['gebaeude'][$id];
			foreach($this->items['forschung'] as $id=>$level)
				$this->items['ids'][$id] = & $this->items['forschung'][$id];
			foreach($this->items['roboter'] as $id=>$level)
				$this->items['ids'][$id] = & $this->items['roboter'][$id];
			foreach($this->items['schiffe'] as $id=>$level)
				$this->items['ids'][$id] = & $this->items['schiffe'][$id];
			foreach($this->items['verteidigung'] as $id=>$level)
				$this->items['ids'][$id] = & $this->items['verteidigung'][$id];
			
			$this->ress = $this->planet_info['ress'];
			
			return true;
		}
		
		function getPlanetByPos($pos)
		{
			if(!$this->status) return false;
			
			$return = false;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $i=>$planet)
			{
				$this->setActivePlanet($i);
				if($this->getPosString() == $pos)
				{
					$return = $i;
					break;
				}
			}
			$this->setActivePlanet($active_planet);
			return $return;
		}
		
		function getActivePlanet()
		{
			if(!$this->status) return false;
			
			return $this->active_planet;
		}
		
		function getPlanetsList()
		{
			if(!$this->status) return false;
			
			if(!isset($this->cache['getPlanetsList']))
				$this->cache['getPlanetsList'] = array_keys($this->raw['planets']);
			return $this->cache['getPlanetsList'];
		}
		
		function getTotalFields()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			return $this->planet_info['size'][1];
		}
		
		function getUsedFields()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			return $this->planet_info['size'][0];
		}
		
		function getRemainingFields()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			return ($this->planet_info['size'][1]-$this->planet_info['size'][0]);
		}
		
		function getBasicFields()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			return ($this->planet_info['size'][1]/($this->getItemLevel('F9', 'forschung')+1));
		}
		
		function setFields($size)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$this->planet_info['size'][1] = $size;
			$this->changed = true;
			return true;
		}
		
		function getPos()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!isset($this->cache['getPos'])) $this->cache['getPos'] = explode(':', $this->planet_info['pos']);
			return $this->cache['getPos'];
		}
		
		function getPosString()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			return $this->planet_info['pos'];
		}
		
		function getPlanetClass()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$pos = $this->getPos();
			return universe::get_planet_class($pos[0], $pos[1], $pos[2]);
		}
		
		function removePlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			# Alle feindlichen Flotten, die auf diesen Planeten, zurueckrufen
			$fleets = $this->getFleetsWithPlanet();
			foreach($fleets as $fleet)
			{
				$owner = $this->getFleetSender($fleet);
				$this->cancelFleet($fleet);
				
				$message = Classes::Message();
				if($message->create())
				{
					$message->addUser($owner[1], $types_message_types[$flotte[2]]);
					$message->subject("Flotte zur\xc3\xbcckgerufen");
					$message->from($_SESSION['username']);
					$message->text("Ihre Flotte befand sich auf dem Weg zum Planeten \xe2\x80\x9e".$this->planetName()."\xe2\x80\x9c (".$this->getPosString().", Eigent\xc3\xbcmer: ".$_SESSION['username']."). Soeben wurde jener Planet verlassen, weshalb Ihre Flotte sich auf den R\xc3\xbcckweg zu Ihrem Planeten \xe2\x80\x9e".$owner[2]."\xe2\x80\x9c (".$owner[0].") macht.");
				}
			}
			
			# Planeten aus der Karte loeschen
			$this_pos = $this->getPos();
			universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], rand(100, 500), '', '', '');

			$planets = $this->getPlanetsList();
			$active_key = array_search($this->getActivePlanet(), $planets);
			unset($this->planet_info);
			unset($this->raw['planets'][$active_key]);
			$keys = array_keys($this->raw['planets']);
			$this->raw['planets'] = array_values($this->raw['planets']);
			if(isset($planets[$active_key+1]))
				$new_active_planet = array_search($planets[$active_key+1], $keys);
			else
				$new_active_planet = array_search($planets[$active_key-1], $keys);
			
			$new_planets = $this->getPlanetsList();
			foreach($new_planets as $planet)
			{
				$this->setActivePlanet($planet);
				$active_forschung = $this->checkBuildingThing('forschung');
				if(!$active_forschung) continue;
				if($active_forschung[2])
					$this->planet_info['building']['forschung'][4] = array_search($active_forschung[4], $keys);
			}
			
			$this->setActivePlanet($new_active_planet);
			
			# Highscores neu berechnen
			highscores::recalc2(); # DEPRECATED
			
			if(isset($this->cache['getPlanetsList'])) unset($this->cache['getPlanetsList']);
			
			return true;
		}
		
		function movePlanetUp($planet=false)
		{
			if(!$this->status) return false;
			if($planet === false)
			{
				if(!isset($this->planet_info)) return false;
				$planet = $this->getActivePlanet();
			}
			
			$planets = $this->getPlanetsList();
			$planet_key = array_search($planet, $planets);
			if($planet_key === false || !isset($planets[$planet_key-1])) return false;
			return $this->movePlanetDown($planets[$planet_key-1]);
		}
		
		function movePlanetDown($planet=false)
		{
			if(!$this->status) return false;
			if($planet === false)
			{
				if(!isset($this->planet_info)) return false;
				$planet = $this->getActivePlanet();
			}
			
			$planets = $this->getPlanetsList();
			$planet_key = array_search($planet, $planets);
			if($planet_key === false || !isset($planets[$planet_key+1])) return false;
			
			$planet2 = $planets[$planet_key+1];
			
			$new_active_planet = $this->getActivePlanet();
			if($new_active_planet == $planet) $new_active_planet = $planet2;
			elseif($new_active_planet == $planet2) $new_active_planet = $planet;
			
			unset($this->planet_info);
			
			# Planeten vertauschen
			list($this->raw['planets'][$planet], $this->raw['planets'][$planet2]) = array($this->raw['planets'][$planet2], $this->raw['planets'][$planet]);
			
			# Aktive Forschungen aendern
			$this->setActivePlanet($planet);
			$active_forschung = $this->checkBuildingThing('forschung');
			if($active_forschung && $active_forschung[2])
				$this->planet_info['building']['forschung'][4] = $planet2;
			
			$this->setActivePlanet($planet2);
			$active_forschung = $this->checkBuildingThing('forschung');
			if($active_forschung && $active_forschung[2])
				$this->planet_info['building']['forschung'][4] = $planet;
			
			if($new_active_planet != $planet2) $this->setActivePlanet($new_active_planet);
			
			if(isset($this->cache['getPlanetsList'])) unset($this->cache['getPlanetsList']);
			
			return true;
		}
		
		function getScores($i=false)
		{
			if(!$this->status) return false;
			
			if($i === false)
			{
				if(!isset($this->cache['getScores']))
					$this->cache['getScores'] = $this->raw['punkte'][0]+$this->raw['punkte'][1]+$this->raw['punkte'][2]+$this->raw['punkte'][3]+$this->raw['punkte'][4]+$this->raw['punkte'][5]+$this->raw['punkte'][6];
				return $this->cache['getScores'];
			}
			elseif(!isset($this->raw['punkte'][$i]))
				return 0;
			else
				return $this->raw['punkte'][$i];
		}
		
		function getRank()
		{
			if(!$this->status) return false;
			
			return $this->raw['punkte'][12];
		}
		
		function planetName($name=false)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($name !== false && trim($name) != '')
			{
				$name = substr($name, 0, 24);
				$old_name = $this->planet_info['name'];
				$this->planet_info['name'] = $name;
				
				$pos = $this->getPos();
				$old_info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				if(!$old_info || !universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $old_info[1], $_POST['planet_name'], $old_info[3]))
				{
					$this->planet_info['name'] = $old_name;
					return false;
				}
				else
				{
					$this->changed = true;
					return true;
				}
			}
			
			return $this->planet_info['name'];
		}
		
		function getRess($refresh=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($refresh)
				$this->refreshRess();
			
			$ress = $this->ress;
			
			if($refresh)
			{
				$prod = $this->getProduction();
				$ress[5] = $prod[5];
			}
			
			return $ress;
		}
		
		function addRess($ress)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!is_array($ress)) return false;
			
			if(isset($ress[0])) $this->ress[0] += $ress[0];
			if(isset($ress[1])) $this->ress[1] += $ress[1];
			if(isset($ress[2])) $this->ress[2] += $ress[2];
			if(isset($ress[3])) $this->ress[3] += $ress[3];
			if(isset($ress[4])) $this->ress[4] += $ress[4];
			
			if(isset($this->cache['getItemInfo']) && isset($this->cache['getItemInfo'][$this->getActivePlanet()])) unset($this->cache['getItemInfo'][$this->getActivePlanet()]);
			
			$this->changed = true;
			
			return true;
		}
		
		function subtractRess($ress)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!is_array($ress)) return false;
			
			if(isset($ress[0])) $this->ress[0] -= $ress[0];
			if(isset($ress[1])) $this->ress[1] -= $ress[1];
			if(isset($ress[2])) $this->ress[2] -= $ress[2];
			if(isset($ress[3])) $this->ress[3] -= $ress[3];
			if(isset($ress[4])) $this->ress[4] -= $ress[4];
			
			if(isset($this->cache['getItemInfo']) && isset($this->cache['getItemInfo'][$this->getActivePlanet()])) unset($this->cache['getItemInfo'][$this->getActivePlanet()]);
			
			$this->changed = true;
			
			return true;
		}
		
		function isOwnPlanet($pos)
		{
			if(!$this->status) return false;
			
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			$return = false;
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if((is_array($pos) && $pos == $this->getPos()) || (!is_array($pos) && $pos == $this->getPosString()))
				{
					$return = true;
					break;
				}
			}
			$this->setActivePlanet($active_planet);
			return $return;
		}
		
		function checkOwnFleetWithPlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			foreach($this->raw['flotten'] as $i=>$flotte)
			{
				if($flotte[3][0] == $this->getPosString() || ($flotte[3][1] == $this->getPosString() && $this->isOwnPlanet($flotte[3][0])))
					return true;
			}
			return false;
		}
		
		function getFleetsWithPlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$fleets = array();
			foreach($this->raw['flotten'] as $i=>$flotte)
			{
				if($flotte[3][0] == $this->getPosString() || $flotte[3][1] == $this->getPosString())
					$fleets[] = $i;
			}
			return $fleets;
		}
		
		function getFleetSender($fleet)
		{
			if(!$this->status || !isset($this->raw['flotten'][$fleet])) return false;
			
			if(!isset($this->cache['getFleetSender'])) $this->cache['getFleetSender'] = array();
			if(!isset($this->cache['getFleetSender'][$fleet]))
			{
				$flotte = & $this->raw['flotten'][$fleet];
				if($flotte[7]) $pos = $flotte[3][1];
				else $pos = $flotte[3][0];
				$pos_array = explode(':', $pos);
				$info = universe::get_planet_info($pos_array[0], $pos_array[1], $pos_array[2]);
				$info[0] = $pos;
				$this->cache['getFleetSender'][$fleet] = $info;
			}
			return $this->cache['getFleetSender'][$fleet];
		}
		
		function getFleetReceiver($fleet)
		{
			if(!$this->status || !isset($this->raw['flotten'][$fleet])) return false;
			
			if(!isset($this->cache['getFleetReceiver'])) $this->cache['getFleetReceiver'] = array();
			if(!isset($this->cache['getFleetReceiver'][$fleet]))
			{
				$flotte = & $this->raw['flotten'][$fleet];
				if($flotte[7]) $pos = $flotte[3][0];
				else $pos = $flotte[3][1];
				$pos_array = explode(':', $pos);
				$info = universe::get_planet_info($pos_array[0], $pos_array[1], $pos_array[2]);
				$info[0] = $pos;
				$this->cache['getFleetReceiver'][$fleet] = $info;
			}
			return $this->cache['getFleetReceiver'][$fleet];
		}
		
		function cancelFleet($fleet, $punkte=false)
		{
			if(!$this->status || !isset($this->raw['flotten'][$fleet])) return false;
			
			list(,$sender_name) = $this->getFleetSender($fleet);
			if(!$sender_name) return false;
			
			if($sender_name != $this->getName())
			{
				$sender = Classes::User($sender_name);
				return $sender->cancelFleet($fleet, $punkte);
			}
			
			list(,$receiver_name) = $this->getFleetReceiver($fleet);
			if(!$receiver_name) return false;
			
			$flotte = &$this->raw['flotten'][$fleet];
			
			if($flotte[7]) return false;
			
			$distance_to_here1 = fleet::get_distance($flotte[3][0], $this->getPosString());
			$distance_to_here2 = fleet::get_distance($flotte[3][1], $this->getPosString());
			
			$time_done = (time()-$flotte[1][0])/($flotte[1][1]-$flotte[1][0]);
			$distance = abs($distance_to_here1-$distance_to_here2)*$time_done;
			
			# Masse und Antrieb berechnen
			$mass = 0;
			$speed = 0;
			foreach($flotte[3][0] as $id=>$anzahl)
			{
				$info = $this->getItemInfo($id, 'schiffe');
				if(!$info) continue;
				$mass += $info['mass']*$anzahl;
				$speed += $Info['speed']*$anzahl;
			}
			$time = fleet::get_time($mass, $distance, $speed);
			
			$flotte[3][1] = array(time(), $time);
			
			# Koordinaten vertauschen
			list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

			# Ueberschuessiges Tritium
			$flotte[4][1] = round($flotte[4][0]*($time_left/$time_diff));
			if($punkte) $flotte[4][0] -= $flotte[4][1];
			else $flotte[4][0] = 0;

			# Rueckflug?
			$flotte[7] = true;

			uasort($this->raw['flotten'], 'usort_fleet');
			
			
			# Beim Zielplaneten entfernen
			$target_user = Classes::User($receiver_name);
			$target_user->unsetFleet($fleet);
			unset($target_user);
		}
		
		function unsetFleet($fleet)
		{
			if(!$this->status || !isset($this->raw['flotten'][$fleet])) return false;
			
			unset($this->raw['flotten'][$fleet]);
			$this->changed = true;
		}
		
		function checkMessage($message_id, $type)
		{
			if(!$this->status) return false;
			
			return (isset($this->raw['messages']) && isset($this->raw['messages'][$type]) && isset($this->raw['messages'][$type][$message_id]));
		}
		
		function checkMessageStatus($message_id, $type)
		{
			if(!$this->status) return false;
			
			if(isset($this->raw['messages']) && isset($this->raw['messages'][$type]) && isset($this->raw['messages'][$type][$message_id]))
				return (int) $this->raw['messages'][$type][$message_id];
			else
				return false;
		}
		
		function setMessageStatus($message_id, $type, $status)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['messages']) || !isset($this->raw['messages'][$type]) || !isset($this->raw['messages'][$type][$message_id]))
				return false;
			
			$this->raw['messages'][$type][$message_id] = $status;
			$this->changed = true;
			
			return true;
		}
		
		function getMessagesList($type)
		{
			if(!$this->status) return false;
			
			if(!isset($this->cache['getMessagesList'])) $this->cache['getMessagesList'] = array();
			if(!isset($this->cache['getMessagesList'][$type]))
			{
				if(!isset($this->raw['messages']) || !isset($this->raw['messages'][$type])) $this->cache['getMessagesList'] = array();
				else $this->cache['getMessagesList'][$type] = array_reverse(array_keys($this->raw['messages'][$type]));
			}
			return $this->cache['getMessagesList'][$type];
		}
		
		function getMessageCategoriesList()
		{
			if(!$this->status) return false;
			
			if(!isset($this->cache['getMessageCategoriesList']))
			{
				if(!isset($this->raw['messages'])) $this->cache['getMessageCategoriesList'] = array();
				elseif(!isset($this->raw['messages'])) $this->cache['getMessageCategoriesList'] = array();
				else $this->cache['getMessageCategoriesList'] = array_keys($this->raw['messages']);
				sort($this->cache['getMessageCategoriesList'], SORT_NUMERIC);
			}
			return $this->cache['getMessageCategoriesList'];
		}
		
		function addMessage($message_id, $type)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['messages']))
				$this->raw['messages'] = array();
			if(!isset($this->raw['messages'][$type]))
				$this->raw['messages'][$type] = array();
			$this->raw['messages'][$type][$message_id] = 1;
			$this->changed = true;
			
			if(isset($this->cache['getMessagesList']) && isset($this->cache['getMessagesList'][$type])) unset($this->cache['getMessagesList'][$type]);
		}
		
		function removeMessage($message_id, $type, $edit_message=true)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['messages']) || !isset($this->raw['messages'][$type]) || !isset($this->raw['messages'][$type][$message_id]))
				return 2;
			unset($this->raw['messages'][$type][$message_id]);
			$this->changed = true;
			
			if(isset($this->cache['getMessagesList'])) unset($this->cache['getMessagesList']);
			
			if($edit_message)
			{
				$message = Classes::Message($message_id);
				return $message->removeUser($this->name, false);
			}
			
			return true;
		}
		
		function checkPassword($password)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['password'])) return false;
			if(md5($password) == $this->raw['password'])
			{
				# Passwort stimmt, Passwort-vergessen-Funktion deaktivieren
				if(isset($this->raw['email_password']) && $this->raw['email_password'])
				{
					$this->raw['email_password'] = false;
					$this->changed = true;
				}
				
				return true;
			}
			else return false;
		}
		
		function setPassword($password)
		{
			if(!$this->status) return false;
			
			$this->raw['password'] = md5($password);
			$this->changed = true;
			return true;
		}
		
		function checkSetting($setting)
		{
			if(!$this->status) return false;
			
			if(!isset($this->settings[$setting])) return -1;
			else return $this->settings[$setting];
		}
		
		function setSetting($setting, $value)
		{
			if(!$this->status) return false;
			
			if(!isset($this->settings[$setting]))
				return false;
			else
			{
				$this->settings[$setting] = $value;
				$this->changed = true;
			}
		}
		
		function getUserDescription($parsed=true)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['description'])) $this->raw['description'] = '';
			
			if($parsed)
			{
				if(!isset($this->raw['description_parsed']))
				{
					$this->raw['description_parsed'] = parse_html($this->raw['description']);
					$this->changed = true;
				}
				return $this->raw['description_parsed'];
			}
			else
				return $this->raw['description'];
		}
		
		function setUserDescription($description)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['description'])) $this->raw['description'] = '';
			
			if($description != $this->raw['description'])
			{
				$this->raw['description'] = $description;
				$this->raw['description_parsed'] = parse_html($this->raw['description']);
				$this->changed = true;
				
				return true;
			}
			else
				return 2;
		}
		
		function lastRequest($last_request=false, $last_planet=false)
		{
			if(!$this->status) return false;
			
			if($last_request === false && $last_planet === false)
			{
				$return = array();
				if(!isset($this->raw['last_request']) && !isset($this->raw['last_planet']))
					return false;
				
				if(!isset($this->raw['last_request'])) $return[0] = false;
				else $return[0] = $this->raw['last_request'];
				
				if(!isset($this->raw['last_planet']) || !$this->planetExists($this->raw['last_planet']))
				{
					$planets = $this->getPlanetsList();
					$return[1] = array_shift($planets);
				}
				else $return[1] = $this->raw['last_planet'];
				
				return $return;
			}
			
			if($last_request !== false)
				$this->raw['last_request'] = $last_request;
			if($last_planet !== false)
				$this->raw['last_planet'] = $last_planet;
			$this->changed = true;
			return true;
		}
		
		function registerAction()
		{
			if(!$this->status) return false;
			
			$this->raw['last_request'] = $_SERVER['REQUEST_URI'];
			$this->raw['last_planet'] = $this->getActivePlanet();
			$this->raw['last_active'] = time();
		}
		
		function getItemsList($type=false)
		{
			if(!$this->status) return false;
			
			$items_instance = Classes::Items();
			return $items_instance->getItemsList($type);
		}
		
		function getItemInfo($id, $type=false)
		{
			if(!$this->status) return false;
			
			$this_planet = $this->getActivePlanet();
			if(!isset($this->cache['getItemInfo'])) $this->cache['getItemInfo'] = array();
			if(!isset($this->cache['getItemInfo'][$this_planet])) $this->cache['getItemInfo'][$this_planet] = array();
			if(!isset($this->cache['getItemInfo'][$this_planet][$id]))
			{
				$item = Classes::Item($id);
				if($type === false) $type = $item->getType();
				$info = $item->getInfo();
				if(!$info) return false;
				$info['buildable'] = $info['deps-okay'] = $item->checkDependencies($this);
				$info['level'] = $this->getItemLevel($id, $type);
				
				switch($type)
				{
					case 'gebaeude':
						$minen_rob = 1+0.000625*$this->getItemLevel('F2', 'forschung');
						if($minen_rob > 1)
						{
							$rob = $this->getItemLevel('R02', 'roboter');
							if($rob > $this->getItemLevel('B0', 'gebaeude')*10) $rob = $this->getItemLevel('B0', 'gebaeude')*10;
							$info['prod'][0] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R03', 'roboter');
							if($rob > $this->getItemLevel('B1', 'gebaeude')*10) $rob = $this->getItemLevel('B1', 'gebaeude')*10;
							$info['prod'][1] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R04', 'roboter');
							if($rob > $this->getItemLevel('B2', 'gebaeude')*10) $rob = $this->getItemLevel('B2', 'gebaeude')*10;
							$info['prod'][2] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R05', 'roboter');
							if($rob > $this->getItemLevel('B3', 'gebaeude')*10) $rob = $this->getItemLevel('B3', 'gebaeude')*10;
							$info['prod'][3] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R06', 'roboter');
							if($rob > $this->getItemLevel('B4', 'gebaeude')*15) $rob = $this->getItemLevel('B4', 'gebaeude')*15;
							$info['prod'][4] *= pow($minen_rob, $rob);
						}
						$info['prod'][5] *= pow(1.05, $this->getItemLevel('F3', 'forschung'));
						
						$info['time'] *= pow($info['level']+1, 1.5);
						$baurob = 1-0.0025*$this->getItemLevel('F2', 'forschung');
						$rob = $this->getItemLevel('R01', 'roboter');
						if($rob > $this->getBasicFields()/2) $rob = floor($this->getBasicFields()/2);
						$info['time'] *= pow($baurob, $rob);
						
						$ress_f = pow($info['level']+1, 2.4);
						$info['ress'][0] *= $ress_f;
						$info['ress'][1] *= $ress_f;
						$info['ress'][2] *= $ress_f;
						$info['ress'][3] *= $ress_f;
						
						$ress = $this->getRess(false);
						if($info['buildable'])
						{
							if($ress[0] < $info['ress'][0] || $ress[1] < $info['ress'][1]
							|| $ress[2] < $info['ress'][2] || $ress[3] < $info['ress'][3])
								$info['buildable'] = false;
							if($info['fields'] > $this->getRemainingFields())
								$info['buildable'] = false;
						}
						$info['debuildable'] = ($info['level'] >= 1 && $ress[0]/2 >= $info['ress'][0] && $ress[1]/2 >= $info['ress'][1] && $ress[2]/2 >= $info['ress'][2] && $ress[3]/2 >= $info['ress'][3] && -$info['fields'] <= $this->getRemainingFields());
						
						# Runden
						stdround(&$info['prod'][0]);
						stdround(&$info['prod'][1]);
						stdround(&$info['prod'][2]);
						stdround(&$info['prod'][3]);
						stdround(&$info['prod'][4]);
						stdround(&$info['prod'][5]);
						stdround(&$info['time']);
						stdround(&$info['ress'][0]);
						stdround(&$info['ress'][1]);
						stdround(&$info['ress'][2]);
						stdround(&$info['ress'][3]);
						stdround(&$info['ress'][4]);
						break;
					case 'forschung':
						$info['time'] *= pow($info['level']+1, 2);
						
						$local_labs = 0;
						$global_labs = 0;
						$planets = $this->getPlanetsList();
						$active_planet = $this->getActivePlanet();
						foreach($planets as $planet)
						{
							$this->setActivePlanet($planet);
							if($planet == $active_planet) $local_labs += $this->getItemLevel('B8', 'gebaeude');
							else $global_labs += $this->getItemLevel('B8', 'gebaeude');
						}
						$this->setActivePlanet($active_planet);
						
						$info['time_local'] = $info['time']*pow(0.95, $local_labs);
						unset($info['time']);
						$info['time_global'] = $info['time_local']*pow(0.975, $global_labs);
						
						$ress_f = pow($info['level']+1, 3);
						$info['ress'][0] *= $ress_f;
						$info['ress'][1] *= $ress_f;
						$info['ress'][2] *= $ress_f;
						$info['ress'][3] *= $ress_f;
						
						# Runden
						stdround(&$info['time_local']);
						stdround(&$info['time_global']);
						stdround(&$info['ress'][0]);
						stdround(&$info['ress'][1]);
						stdround(&$info['ress'][2]);
						stdround(&$info['ress'][3]);
						stdround(&$info['ress'][4]);
						break;
					case 'roboter':
						$info['time'] *= pow(0.95, $this->getItemLevel('B9', 'gebaeude'));
						
						stdround(&$info['time']);
						break;
					case 'schiffe':
						$info['att'] *= pow(1.05, $this->getItemLevel('F4', 'forschung'));
						$info['def'] *= pow(1.05, $this->getItemLevel('F5', 'forschung'));
						$lad_f = pow(1.2, $this->getItemLevel('F11', 'forschung'));
						$info['trans'][0] *= $lad_f;
						$info['trans'][1] *= $lad_f;
						$info['time'] *= pow(0.95, $this->getItemLevel('B10', 'gebaeude'));
						$info['speed'] *= pow(1.025, $this->getItemLevel('F6', 'forschung'));
						$info['speed'] *= pow(1.05, $this->getItemLevel('F7', 'forschung'));
						$info['speed'] *= pow(1.5, $this->getItemLevel('F8', 'forschung'));
						
						# Runden
						stdround(&$info['att']);
						stdround(&$info['def']);
						stdround(&$info['trans'][0]);
						stdround(&$info['trans'][1]);
						stdround(&$info['time']);
						stdround(&$info['speed']);
						break;
					case 'verteidigung':
						$info['att'] *= pow(1.05, $this->getItemLevel('F4', 'forschung'));
						$info['def'] *= pow(1.05, $this->getItemLevel('F5', 'forschung'));
						$info['time'] *= pow(0.95, $this->getItemLevel('B10', 'gebaeude'));
						
						stdround(&$info['att']);
						stdround(&$info['def']);
						stdround(&$info['time']);
						break;
				}
				$this->cache['getItemInfo'][$this_planet][$id] = $info;
			}
			
			return $this->cache['getItemInfo'][$this_planet][$id];
		}
		
		function getItemLevel($id, $type=false, $run_eventhandler=true)
		{
			if(!$this->status) return false;
			
			if($run_eventhandler) $this->eventhandler($id,0,0,0,0,0);
			
			if($type === false)
				$type = 'ids';
			if(!isset($this->items[$type]) || !isset($this->items[$type][$id]))
				return 0;
			return $this->items[$type][$id];
		}
		
		function changeItemLevel($id, $value=1, $type=false, $time=false, &$action=false)
		{
			if(!$this->status) return false;
			
			if($time === false) $time = time();
			
			if($type !== false && $type != 'ids')
			{
				if(!isset($this->items[$type])) $this->items[$type] = array();
				if(isset($this->items[$type][$id])) $this->items[$type][$id] += $value;
				else
				{
					$this->items[$type][$id] = $value;
					$this->items['ids'][$id] = &$this->items[$type][$id];
				}
			}
			else
			{
				if(isset($this->items['ids'][$id])) $this->items['ids'][$id] += $value;
				else
				{
					$item = Classes::Item($id);
					$type = $item->getType();
					if(!isset($this->items[$type])) $this->items[$type] = array();
					$this->items[$type][$id] = $value;
					$this->items['ids'][$id] = &$this->items[$type][$id];
				}
			}
			
			switch($id)
			{
				# Ingeneurswissenschaft: Planeten vergroessern
				case 'F9':
					$planets = $this->getPlanetsList();
					$active_planet = $this->getActivePlanet();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$size = $this->getTotalFields()/($this->getItemLevel('F9', false, false)-$value);
						$this->setFields($size*$this->getItemLevel('F9', false, false));
					}
					$this->setActivePlanet($active_planet);
					break;
				
				# Bauroboter: Laufende Bauzeit verkuerzen
				case 'R01':
					$building = $this->checkBuildingThing('gebaeude');
					if($building && $building[1] > $time)
					{
						$remaining = ($building[1]-$time)*pow(1-0.0025*$this->getItemLevel('F2', 'forschung', false), $value);
						$this->raw['building']['gebaeude'][1] = $time+$remaining;
					}
					
					# Auch in $actions schauen
					$one = false;
					foreach($actions as $i=>$action2)
					{
						$this_item = Classes::Item($action2[1]);
						if($this_item->getType() == 'gebaeude')
						{
							$remaining = ($action2[0]-$time)*pow(1-0.0025*$this->getItemLevel('F2', 'forschung', false), $value);
							$actions[$i][0] = $time+$remaining;
							$one = true;
						}
					}
					if($one) usort($action, 'sortEventhandlerActions');
					
					break;
				
				# Roboterbautechnik: Auswirkungen der Bauroboter aendern
				case 'F2':
					$planets = $this->getPlanetsList();
					$active_planet = $this->getActivePlanet();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						
						$building = $this->checkBuildingThing('gebaeude');
						$robs = $this->getItemLevel('R01', 'roboter', false);
						if($robs > 0 && $building && $building[1] > $time)
						{
							$f_1 = pow(1-0.0025*($this->getItemLevel('F2', false, false)-$value), $rob);
							$f_2 = pow(1-0.0025*$this->getItemLevel('F2', false, false), $rob);
							$remaining = ($building[1]-$time)*$f_2/$f_1;
							$this->raw['building']['gebaeude'][1] = $time+$remaining;
						}
						
						# Auch in $actions schauen
						if($actions !== false && $planet == $active_planet)
						{
							$one = false;
							foreach($actions as $i=>$action2)
							{
								$this_item = Classes::Item($action2[1]);
								if($this_item->getType() == 'gebaeude')
								{
									$f_1 = pow(1-0.0025*($this->getItemLevel('F2', false, false)-$value), $rob);
									$f_2 = pow(1-0.0025*$this->getItemLevel('F2', false, false), $rob);
									$remaining = ($action2[0]-$time)*$f_2/$f_1;
									$actions[$i][0] = $action[0]+$remaining;
									$one = true;
								}
							}
							if($one) usort($action, 'sortEventhandlerActions');
						}
					}
					$this->setActivePlanet($active_planet);
					
					break;
			}
			
			$this->changed = true;
			
			return true;
		}
		
		protected function refreshRess($time=false)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($time === false)
			{
				$this->eventhandler(0, 1,1,1,0,0);
				$time = time();
			}
			
			if($this->planet_info['last_refresh'] >= $time) return false;
			
			$prod = $this->getProduction();
			
			$f = ($time-$this->planet_info['last_refresh'])/3600;
			
			$this->ress[0] += $prod[0]*$f;
			$this->ress[1] += $prod[1]*$f;
			$this->ress[2] += $prod[2]*$f;
			$this->ress[3] += $prod[3]*$f;
			$this->ress[4] += $prod[4]*$f;
			
			$this->changed = true;
			return true;
		}
		
		function getProduction()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!isset($this->cache['getProduction'])) $this->cache['getProduction'] = array();
			$planet = $this->getActivePlanet();
			if(!isset($this->cache['getProduction'][$planet]))
			{
				$prod = array(0,0,0,0,0);
				if($this->permissionToAct())
				{
					$gebaeude = $this->getItemsList('gebaeude');
					
					$energie_prod = 0;
					$energie_need = 0;
					foreach($gebaeude as $id)
					{
						$item = $this->getItemInfo($id, 'gebaeude');
						if($item['prod'][5] < 0) $energie_need -= $item['prod'][5];
						elseif($item['prod'][5] > 0) $energie_prod += $item['prod'][5];
						
						$prod[0] += $item['prod'][0];
						$prod[1] += $item['prod'][1];
						$prod[2] += $item['prod'][2];
						$prod[3] += $item['prod'][3];
						$prod[4] += $item['prod'][4];
					}
	
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
					
					stdround(&$prod[0]);
					stdround(&$prod[1]);
					stdround(&$prod[2]);
					stdround(&$prod[3]);
					stdround(&$prod[4]);
					stdround(&$prod[5]);
				}
				$this->cache['getProduction'][$planet] = $prod;
			}
			
			return $this->cache['getProduction'][$planet];
		}
		
		function gameLocked()
		{
			return file_exists(LOCK_FILE);
		}
		
		function userLocked()
		{
			if(!$this->status) return false;
			
			return (isset($this->raw['locked']) && $this->raw['locked']);
		}
		
		function umode($set=false)
		{
			if(!$this->status) return false;
			
			if($set !== false)
			{
				$this->raw['umode'] = (bool)$set;
				$this->raw['umode_time'] = time();
				$this->changed = true;
				
				if(isset($this->cache['getProduction'])) # Produktion wird auf 0 gefahren
					unset($this->cache['getProduction']);
				
				return true;
			}
			
			return (isset($this->raw['umode']) && $this->raw['umode']);
		}
		
		function permissionToUmode()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['umode_time'])) return true;
			
			if($this->umode()) $min_days = 3; # Ist gerade im Urlaubsmodus
			else $min_days = 3;
			
			return ((time()-$this->raw['umode_time']) > $min_days*86400);
		}
		
		function permissionToAct()
		{
			return !($this->gameLocked() || $this->userLocked() || $this->umode());
		}
		
		protected function getDataFromRaw()
		{
			$settings = array('skin' => false, 'schrift' => true,
				'sonden' => 1, 'ress_refresh' => 0,
				'fastbuild' => false, 'shortcuts' => false,
				'tooltips' => false, 'ipcheck' => true,
				'noads' => false, 'show_extern' => false,
				'notify' => false, 'email' => false,
				'receive' => array(
					1 => array(true, true),
					2 => array(true, false),
					3 => array(true, false),
					4 => array(true, true),
					5 => array(true, false)
				)
			);
			
			$this->settings = array();
			foreach($settings as $setting=>$default)
			{
				if(isset($this->raw[$setting])) $this->settings[$setting] = $this->raw[$setting];
				else $this->settings[$setting] = $default;
			}
			
			$this->items = array();
			$this->items['forschung'] = $this->raw['forschung'];
			$this->items['ids'] = array();
			foreach($this->items['forschung'] as $id=>$level)
				$this->items['ids'][$id] = &$this->items['forschung'][$id];
		}
		
		protected function getRawFromData()
		{
			foreach($this->settings as $setting=>$value)
				$this->raw[$setting] = $value;
			$this->raw['forschung'] = $this->items['forschung'];
			
			$active_planet = $this->getActivePlanet();
			if($active_planet !== false)
			{
				$this->planet_info['gebaeude'] = $this->items['gebaeude'];
				$this->planet_info['roboter'] = $this->items['roboter'];
				$this->planet_info['schiffe'] = $this->items['schiffe'];
				$this->planet_info['verteidigung'] = $this->items['verteidigung'];
				$this->planet_info['ress'] = $this->ress;
			}
		}
		
		function checkBuildingThing($type)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			switch($type)
			{
				case 'gebaeude': case 'forschung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || trim($this->planet_info['building'][$type][0]) == '')
						return false;
					return $this->planet_info['building'][$type];
				case 'roboter': case 'schiffe': case 'verteidigung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || count($this->planet_info['building'][$type]) > 0)
						return array();
					return $this->planet_info['building'][$type];
				default: return false;
			}
		}
		
		function removeBuildingThing($type, $cancel=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			switch($type)
			{
				case 'gebaeude': case 'forschung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || trim($this->planet_info['building'][$type][0]) == '')
						return false;
					
					if($type == 'forschung' && $this->planet_info['building'][$type][2])
					{
						$source_planet = $this->planet_info['building'][$type][4];
						if(!isset($this->raw['planets'][$source_planet]['building'][$type]) || trim($this->raw['planets'][$source_planet]['building'][$type][0]) == '')
							return false;
						$active_planet = $this->getActivePlanet();
						$planets = $this->getPlanetsList();
						foreach($planets as $planet)
						{
							if($planet == $active_planet) continue;
							$this->setActivePlanet($planet);
							if($planet == $source_planet && $cancel)
								$this->addRess($this->planet_info['building'][$type][3]);
							unset($this->planet_info['building'][$type]);
						}
						$this->setActivePlanet($active_planet);
					}
					elseif($cancel)
						$this->addRess($this->planet_info['building'][$type][3]);
					
					unset($this->planet_info['building'][$type]);
					$this->changed = true;
					
					return true;
				case 'roboter': case 'schiffe': case 'verteidigung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || trim($this->planet_info['building'][$type][0]) == '')
						return false;
					$this->planet_info['building'][$type][0][2]--;
					if($this->planet_info['building'][$type][0][2] <= 0)
						array_shift($this->planet_info['building'][$type]);
					$this->changed = true;
					return true;
			}
		}
		
		protected function eventhandler($check_id=false, $check_gebaeude=true, $check_forschung=true, $check_roboter=true, $check_schiffe=true, $check_verteidigung=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$actions = array();
			/* Array
			   (
				[0] => Zeit
				[1] => ID
				[2] => Stufen hinzuzaehlen
				[3] => Rohstoffe neu berechnen?
			  )*/
			
			$run = false;
			
			$building = $this->checkBuildingThing('gebaeude');
			if($building !== false && $building[1] <= time() && $this->removeBuildingThing('gebaeude', false))
			{
				$stufen = 1;
				if($building[2]) $stufen = -1;
				$actions[] = array($building[1], $building[0], $stufen, true);
				
				if($check_gebaeude || $building[0]==$check_id) $run = true;
			}
			
			
			$building = $this->checkBuildingThing('forschung');
			if($building !== false && $building[1] <= time() && $this->removeBuildingThing('forschung', false))
			{
				$actions[] = array($building[1], $building[0], 1, true);
				if($check_forschung || $building[0]==$check_id) $run = true;
			}
			
			
			$building = $this->checkBuildingThing('roboter');
			foreach($building as $items)
			{
				$info = $this->getItemInfo($items[0], 'roboter');
				if(!$info) continue;
				$punkte = array_sum($info['ress'])/1000;
				$time = $items[1];
				for($i=0; $i<$items[2]; $i++)
				{
					$time += $items[3];
					if($time <= time() && $this->removeBuildingThing('roboter', false))
					{
						$actions[] = array($time, $items[0], 1, true);
						if($check_roboter || $items[0]==$check_id) $run = true;
					}
					else
						break 2;
				}
			}
			
			$building = $this->checkBuildingThing('schiffe');
			foreach($building as $items)
			{
				$info = $this->getItemInfo($items[0], 'schiffe');
				if(!$info) continue;
				$punkte = array_sum($info['ress'])/1000;
				$time = $items[1];
				for($i=0; $i<$items[2]; $i++)
				{
					$time += $items[3];
					if($time <= time() && $this->removeBuildingThing('schiffe', false))
					{
						$actions[] = array($time, $items[0], 1, true);
						if($check_schiffe || $items[0]==$check_id) $run = true;
					}
					else
						break 2;
				}
			}
			
			
			$building = $this->checkBuildingThing('verteidigung');
			foreach($building as $items)
			{
				$info = $this->getItemInfo($items[0], 'verteidigung');
				if(!$info) continue;
				$punkte = array_sum($info['ress'])/1000;
				$time = $items[1];
				for($i=0; $i<$items[2]; $i++)
				{
					$time += $items[3];
					if($time <= time() && $this->removeBuildingThing('verteidigung', false))
					{
						$actions[] = array($time, $items[0], 1, true);
						if($check_verteidigung || $items[0]==$check_id) $run = true;
					}
					else
						break 2;
				}
			}
			
			if($run && count($actions) > 0)
			{
				usort($actions, 'sortEventhandlerActions');
				
				foreach($actions as $action)
				{
					if($action[3])
						$this->refreshRess($action[0]);
					
					$this->changeItemLevel($action[1], $action[2], false, $action[0], &$actions);
					
					if(isset($this->cache['getProduction']) && isset($this->cache['getProduction'][$this->getActivePlanet()]))
						unset($this->cache['getProduction'][$this->getActivePlanet()]);
					if(isset($this->cache['getItemInfo']) && isset($this->cache['getItemInfo'][$this->getActivePlanet()]) && isset($this->cache['getItemInfo'][$this->getActivePlanet()][$action[1]]))
						unset($this->cache['getItemInfo'][$this->getActivePlanet()][$action[1]]);
					
					array_shift($actions);
				}
				
				$this->changed = true;
				highscores::recalc();
			}
		}
		
		function isVerbuendet($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete'])) return false;
			return in_array($user, $this->raw['verbuendete']);
		}
		
		function checkPlanetCount()
		{
			if(!$this->status) return false;
			
			if(count($this->raw['planets']) < 15) return true;
			else return true;
		}
		
		function buildGebaeude($id, $rueckbau=false)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($this->checkBuildingThing('gebaeude')) return false;
			if($id == 'B8' && $this->checkBuildingThing('forschung')) return false;
			if($id == 'B9' && $this->checkBuildingThing('roboter')) return false;
			if($id == 'B10' && ($this->checkBuildingThing('schiffe') || $this->checkBuildingThing('verteidigung'))) return false;
			
			$item_info = $this->getItemInfo($id, 'gebaeude');
			if($item_info && ((!$rueckbau && $item_info['buildable']) || ($rueckbau && $item_info['debuildable'])))
			{
				# Rohstoffkosten
				$ress = $item_info['ress'];
				
				if($rueckbau)
				{
					$ress[0] = $ress[0]>>1;
					$ress[1] = $ress[1]>>1;
					$ress[2] = $ress[2]>>1;
					$ress[3] = $ress[3]>>1;
				}
	
				# Genuegend Rohstoffe zum Ausbau

				$time = $item_info['time'];
				if($rueckbau)
					$time = $time>>1;
				$time += time();

				if(!isset($this->planet_info['building'])) $this->planet_info['building'] = array();
				$this->planet_info['building']['gebaeude'] = array($id, $time, $rueckbau, $ress);

				# Rohstoffe abziehen
				$this->subtractRess($ress);
				
				return true;
			}
			return false;
		}
		
		function buildForschung($id, $global)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($this->checkBuildingThing('forschung')) return false;
			if($gebaeude = $this->checkBuildingThing('gebaeude') && $gebaeude[0] == 'B8') return false;
			
			$buildable = true;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if(($global && $this->checkBuildingThing('forschung')) || (!$global && ($building = $this->getBuildingThing('forschung')) && $building[0] == $id))
				{
					$buildable = false;
					break;
				}
			}
			$this->setActivePlanet($active_planet);
			
			$item_info = $this->getItemInfo($id, 'forschung');
			if($item_info && $item_info['buildable'])
			{
				$build_array = array($id, time()+$item_info['time'], $global, $item_info['ress']);
				if($global)
				{
					$build_array[] = $_SESSION['act_planet'];
					
					$planets = $this->getPlanetsList();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$this->planet_info['building']['forschung'] = $build_array;
					}
					$this->setActivePlanet($active_planet);
				}
				else $this_planet['building']['forschung'] = $build_array;
				
				$this->subtractRess($item_info['ress']);
				
				return true;
			}
			return false;
		}
	}
	
	function sortEventhandlerActions($a, $b)
	{
		if($a[0] < $b[0]) return -1;
		elseif($a[0] > $b[0]) return 1;
		else return 0;
	}
?>