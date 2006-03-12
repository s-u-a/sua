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
				'username' => $this->name,
				'planets' => array(),
				'forschung' => array(),
				'password' => 'x',
				'punkte' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
				'registration' => time(),
				'messages' => array(),
				'description' => '',
				'description_parsed' => '',
				'flotten' => array(),
				'alliance' => false
			);
			
			$fh = fopen(DB_HIGHSCORES, 'a');
			if(!fancy_flock($fh, LOCK_EX)) return false;
			fwrite($fh, encodeUserHighscoresString($this->name, 0, ''));
			flock($fh, LOCK_UN);
			fclose($fh);
			$this->raw['punkte'][12] = getUsersCount();
			
			$this->write(true, false);
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
				if(isset($this->ress)) $this->planet_info['ress'] = $this->ress;
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
		
		function changeUsedFields($value)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$this->planet_info['size'][0] += $value;
			$this->changed = true;
			return true;
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
			__autoload('Galaxy');
			return getPlanetClass($pos[0], $pos[1], $pos[2]);
		}
		
		function removePlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			# Alle feindlichen Flotten, die auf diesen Planeten, zurueckrufen
			$fleets = $this->getFleetsWithPlanet();
			foreach($fleets as $fleet)
			{
				$fl = Classes::Fleet($fleet);
				$users = $fl->getUsersList();
				foreach($users as $user)
				{
					$pos_string = $fl->from($user);
					$pos = explode(':', $pos_string);
					$type = $fl->getCurrentType();
					$fl->callBack($user);
					
					$this_galaxy = Classes::Galaxy($pos[0]);
					
					$message = Classes::Message();
					if($message->create())
					{
						$message->addUser($user, $types_message_types[$type]);
						$message->subject("Flotte zur\xc3\xbcckgerufen");
						$message->from($this->getName());
						$message->text("Ihre Flotte befand sich auf dem Weg zum Planeten \xe2\x80\x9e".$this->planetName()."\xe2\x80\x9c (".$this->getPosString().", Eigent\xc3\xbcmer: ".utf8_htmlentities($this->getName())."). Soeben wurde jener Planet verlassen, weshalb Ihre Flotte sich auf den R\xc3\xbcckweg zu Ihrem Planeten \xe2\x80\x9e".$this_galaxy->getPlanetName($pos[1], $pos[2])."\xe2\x80\x9c (".$pos_string.") macht.");
					}
				}
			}
			
			# Planeten aus der Karte loeschen
			$this_pos = $this->getPos();
			$galaxy = Classes::galaxy($this_pos[0]);
			$galaxy->resetPlanet($this_pos[1], $this_pos[2]);

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
			$this->recalcHighscores(true, true, true, true, true);
			
			if(isset($this->cache['getPlanetsList'])) unset($this->cache['getPlanetsList']);
			
			return true;
		}
		
		function registerPlanet($pos_string)
		{
			if(!$this->status) return false;
			
			$pos = explode(':', $pos_string);
			if(count($pos) != 3) return false;
			
			if(!$this->checkPlanetCount()) return false;
			
			$galaxy = Classes::Galaxy($pos[0]);
			if($galaxy->getStatus() != 1) return false;
			
			$owner = $galaxy->getPlanetOwner($pos[1], $pos[2]);
			if($owner === false || $owner) return false;
			
			$planet_name = 'Kolonie';
			if(!$galaxy->setPlanetOwner($pos[1], $pos[2], $this->getName())) return false;
			$galaxy->setPlanetName($pos[1], $pos[2], $planet_name);
			if($this->allianceTag())
				$galaxy->setPlanetOwnerAlliance($pos[1], $pos[2], $this->allianceTag());
			
			if(count($this->raw['planets']) <= 0) $size = 375;
			else $size = $galaxy->getPlanetSize($pos[1], $pos[2]);
			$size *= $this->getItemLevel('F9', 'forschung')+1;
			
			$planets = $this->getPlanetsList();
			if(count($planets) == 0) $planet_index = 0;
			else $planet_index = max($planets)+1;
			while(isset($this->raw['planets'][$planet_index])) $planet_index++;
			
			$this->raw['planets'][$planet_index] = array (
				'pos' => $pos_string,
				'ress' => array(0, 0, 0, 0, 0),
				'gebaeude' => array(),
				'roboter' => array(),
				'schiffe' => array(),
				'verteidigung' => array(),
				'size' => array(0, $size),
				'last_refresh' => time(),
				'time' => $planet_name,
				'prod' => array(),
				'name' => $planet_name
			);
			
			if(isset($this->cache['getPlanetsList'])) unset($this->cache['getPlanetsList']);
			
			return $planet_index;
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
		
		function addScores($i, $scores)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['punkte'][$i]))
				$this->raw['punkte'][$i] = $scores;
			else $this->raw['punkte'][$i] += $scores;
			
			if(isset($this->cache['getScores'])) $this->cache['getScores'] += $scores;
			return true;
		}
		
		function getSpentRess($i=false)
		{
			if(!$this->status) return false;
			
			if($i === false)
			{
				if(!isset($this->cache['getSpentRess']))
					$this->cache['getSpentRess'] = $this->getScores(7)+$this->getScores(8)+$this->getScores(9)+$this->getScores(10)+$this->getScores(11);
				return $this->cache['getSpentRess'];
			}
			else return $this->getScores($i+7);
		}
		
		function getRank()
		{
			if(!$this->status) return false;
			
			return $this->raw['punkte'][12];
		}
		
		function setRank($rank)
		{
			if(!$this->status) return false;
			
			$this->raw['punkte'][12] = (int) $rank;
			$this->changed = true;
			return true;
		}
		
		function planetName($name=false)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($name !== false && trim($name) != '')
			{
				$name = substr($name, 0, 24);
				if(isset($this->planet_info['name']))
					$old_name = $this->planet_info['name'];
				else $old_name = '';
				$this->planet_info['name'] = $name;
				
				$pos = $this->getPos();
				$galaxy = Classes::Galaxy($pos[0]);
				if(!$galaxy->setPlanetName($pos[1], $pos[2], $name))
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
		
		function subtractRess($ress, $make_scores=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!is_array($ress)) return false;
			
			if(isset($ress[0])){ $this->ress[0] -= $ress[0]; if($make_scores) $this->raw['punkte'][7] += $ress[0]; }
			if(isset($ress[1])){ $this->ress[1] -= $ress[1]; if($make_scores) $this->raw['punkte'][8] += $ress[1]; }
			if(isset($ress[2])){ $this->ress[2] -= $ress[2]; if($make_scores) $this->raw['punkte'][9] += $ress[2]; }
			if(isset($ress[3])){ $this->ress[3] -= $ress[3]; if($make_scores) $this->raw['punkte'][10] += $ress[3]; }
			if(isset($ress[4])){ $this->ress[4] -= $ress[4]; if($make_scores) $this->raw['punkte'][11] += $ress[4]; }
			
			if($make_scores && isset($this->cache['getSpentRess'])) unset($this->cache['getSpentRess']);
			
			$this->changed = true;
			
			return true;
		}
		
		function checkRess($ress)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!is_array($ress)) return false;
			
			if(isset($ress[0]) && $ress[0] > $this->ress[0]) return false;
			if(isset($ress[1]) && $ress[1] > $this->ress[1]) return false;
			if(isset($ress[2]) && $ress[2] > $this->ress[2]) return false;
			if(isset($ress[3]) && $ress[3] > $this->ress[3]) return false;
			if(isset($ress[4]) && $ress[4] > $this->ress[4]) return false;
			
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
		
		function getFleetsList()
		{
			if(!$this->status) return false;
			
			if(isset($this->raw['flotten']))
			{
				foreach($this->raw['flotten'] as $i=>$flotte)
				{
					__autoload('Fleet');
					if(!Fleet::fleetExists($flotte))
					{
						unset($this->raw['flotten'][$i]);
						$this->changed = true;
					}
				}
				return $this->raw['flotten'];
			}
			else return array();
		}
		
		function addFleet($fleet)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['flotten'])) $this->raw['flotten'] = array();
			elseif(in_array($fleet, $this->raw['flotten'])) return 2;
			$this->raw['flotten'][] = $fleet;
			natcasesort($this->raw['flotten']);
			$this->changed = true;
			return true;
		}
		
		function unsetFleet($fleet)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['flotten'])) return true;
			$key = array_search($fleet, $this->raw['flotten']);
			if($key === false) return true;
			unset($this->raw['flotten'][$key]);
			$this->changed = true;
			return true;
		}
		
		function checkOwnFleetWithPlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			foreach($this->getFleetsList() as $flotte)
			{
				$fl = Classes::Fleet($flotte);
				if(in_array($this->getName(), $fl->getUsersList()) && ($fl->from($this->getName()) == $this->getPosString() || $fl->isATarget($this->getPosString())))
					return true;
			}
			return false;
		}
		
		function getFleetsWithPlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$fleets = array();
			foreach($this->getFleetsList() as $flotte)
			{
				$fl = Classes::Fleet($flotte);
				if(in_array($this->getName(), $fl->getUsersList()) && ($fl->from($this->getName()) == $this->getPosString() || $fl->isATarget($this->getPosString())))
					$fleets[] = $flotte;
			}
			return $fleets;
		}
		
		function getMaxParallelFleets()
		{
			if(!$this->status) return false;
			
			$werft = 0;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if($this->getItemLevel('B10', 'gebaeude') > 0)
					$werft++;
			}
			$this->setActivePlanet($active_planet);
			
			return floor(pow($werft*$this->getItemLevel('F0', 'forschung'), .7));
		}
		
		function getCurrentParallelFleets()
		{
			if(!$this->status) return false;
			
			$fleets = 0;
			foreach($this->getFleetsList() as $flotte)
			{
				$fl = Classes::Fleet($flotte);
				$key = array_search($this->getName(), $fl->getUsersList());
				if($key !== false)
				{
					if($key) $fleets++;
					else $fleets += count($fl->getTargetsList())+count($fl->getOldTargetsList())-1;
				}
			}
			return $fleets;
		}
		
		function getRemainingParallelFleets()
		{
			if(!$this->status) return false;
			
			return $this->getMaxParallelFleets()-$this->getCurrentParallelFleets();
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
		
		function getPasswordSum()
		{
			if(!$this->status) return false;
			return $this->raw['planet'];
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
		
		function getLastActivity()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['last_active'])) return false;
			return $this->raw['last_active'];
		}
		
		function getRegistrationTime()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['registration'])) return false;
			return $this->raw['registration'];
		}
		
		function getItemsList($type=false)
		{
			if(!$this->status) return false;
			
			$items_instance = Classes::Items();
			return $items_instance->getItemsList($type);
		}
		
		function getItemInfo($id, $type=false, $run_eventhandler=true, $calc_scores=false)
		{
			if(!$this->status) return false;
			
			$this_planet = $this->getActivePlanet();
			if(!isset($this->cache['getItemInfo'])) $this->cache['getItemInfo'] = array();
			if(!isset($this->cache['getItemInfo'][$this_planet])) $this->cache['getItemInfo'][$this_planet] = array();
			if(!isset($this->cache['getItemInfo'][$this_planet][$id]) || ($calc_scores && !isset($this->cache['getItemInfo'][$this_planet][$id]['scores'])))
			{
				$item = Classes::Item($id);
				if($type === false) $type = $item->getType();
				$info['type'] = $type;
				$info = $item->getInfo();
				if(!$info) return false;
				$info['buildable'] = $info['deps-okay'] = $item->checkDependencies($this, $run_eventhandler);
				$info['level'] = $this->getItemLevel($id, $type, $run_eventhandler);
				
				# Bauzeit als Anteil der Punkte des ersten Platzes
				if(isset($info['time']))
				{
					$highscores_fh = fopen(DB_HIGHSCORES, 'r');
					if(fancy_flock($highscores_fh, LOCK_SH))
					{
						$my_scores = $this->getScores();
						list(,$best_scores) = decodeUserHighscoresString(fread($highscores_fh, 38));
						$f = $my_scores/$best_scores;
						if($f < .5) $f = .5;
						$info['time'] *= $f;
					}
					if($highscores_fh) fclose($highscores_fh);
				}
				
				switch($type)
				{
					case 'gebaeude':
						$info['has_prod'] = ($info['prod'][0] > 0 || $info['prod'][1] > 0 || $info['prod'][2] > 0 || $info['prod'][3] > 0 || $info['prod'][4] > 0 || $info['prod'][5] > 0);
						$level_f = pow($info['level'], 2);
						$percent_f = $this->checkProductionFactor($id);
						$info['prod'][0] *= $level_f*$percent_f;
						$info['prod'][1] *= $level_f*$percent_f;
						$info['prod'][2] *= $level_f*$percent_f;
						$info['prod'][3] *= $level_f*$percent_f;
						$info['prod'][4] *= $level_f*$percent_f;
						$info['prod'][5] *= $level_f*$percent_f;
						
						$minen_rob = 1+0.0003125*$this->getItemLevel('F2', 'forschung', $run_eventhandler);
						if($minen_rob > 1)
						{
							$rob = $this->getItemLevel('R02', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B0', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B0', 'gebaeude', $run_eventhandler);
							$info['prod'][0] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R03', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B1', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B1', 'gebaeude', $run_eventhandler);
							$info['prod'][1] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R04', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B2', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B2', 'gebaeude', $run_eventhandler);
							$info['prod'][2] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R05', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B3', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B3', 'gebaeude', $run_eventhandler);
							$info['prod'][3] *= pow($minen_rob, $rob);
							
							$rob = $this->getItemLevel('R06', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B4', 'gebaeude', $run_eventhandler)*2) $rob = $this->getItemLevel('B4', 'gebaeude', $run_eventhandler)*2;
							$info['prod'][4] *= pow($minen_rob, $rob);
						}
						if($info['prod'][5] > 0)
							$info['prod'][5] *= pow(1.05, $this->getItemLevel('F3', 'forschung', $run_eventhandler));
						
						$info['time'] *= pow($info['level']+1, 1.5);
						$baurob = 1-0.00125*$this->getItemLevel('F2', 'forschung', $run_eventhandler);
						$rob = $this->getItemLevel('R01', 'roboter', $run_eventhandler);
						if($rob > $this->getBasicFields()/2) $rob = floor($this->getBasicFields()/2);
						$info['time'] *= pow($baurob, $rob);
						
						if($calc_scores)
						{
							$ress = array_sum($info['ress']);
							$scores = 0;
							for($i=1; $i<=$info['level']; $i++)
								$scores += $ress*pow($i, 2.4);
							$info['scores'] = $scores/1000;
						}
						
						$ress_f = pow($info['level']+1, 2.4);
						$info['ress'][0] *= $ress_f;
						$info['ress'][1] *= $ress_f;
						$info['ress'][2] *= $ress_f;
						$info['ress'][3] *= $ress_f;
						
						if($info['buildable'] && $info['fields'] > $this->getRemainingFields())
							$info['buildable'] = false;
						$info['debuildable'] = ($info['level'] >= 1 && -$info['fields'] <= $this->getRemainingFields());
						
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
							if($planet == $active_planet) $local_labs += $this->getItemLevel('B8', 'gebaeude', $run_eventhandler);
							else $global_labs += $this->getItemLevel('B8', 'gebaeude', $run_eventhandler);
						}
						$this->setActivePlanet($active_planet);
						
						$info['time_local'] = $info['time']*pow(0.95, $local_labs);
						unset($info['time']);
						$info['time_global'] = $info['time_local']*pow(0.975, $global_labs);
						
						if($calc_scores)
						{
							$ress = array_sum($info['ress']);
							$scores = 0;
							for($i=1; $i<=$info['level']; $i++)
								$scores += $ress*pow($i, 3);
							$info['scores'] = $scores/1000;
						}
						
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
						$info['time'] *= pow(0.95, $this->getItemLevel('B9', 'gebaeude', $run_eventhandler));
						
						if($calc_scores)
						{
							$info['simple_scores'] = array_sum($info['ress'])/1000;
							$info['scores'] = $info['simple_scores']*$info['level'];
						}
						
						stdround(&$info['time']);
						break;
					case 'schiffe':
						$info['att'] *= pow(1.05, $this->getItemLevel('F4', 'forschung', $run_eventhandler));
						$info['def'] *= pow(1.05, $this->getItemLevel('F5', 'forschung', $run_eventhandler));
						$lad_f = pow(1.2, $this->getItemLevel('F11', 'forschung', $run_eventhandler));
						$info['trans'][0] *= $lad_f;
						$info['trans'][1] *= $lad_f;
						$info['time'] *= pow(0.95, $this->getItemLevel('B10', 'gebaeude', $run_eventhandler));
						$info['speed'] *= pow(1.025, $this->getItemLevel('F6', 'forschung', $run_eventhandler));
						$info['speed'] *= pow(1.05, $this->getItemLevel('F7', 'forschung', $run_eventhandler));
						$info['speed'] *= pow(1.5, $this->getItemLevel('F8', 'forschung', $run_eventhandler));
						
						if($calc_scores)
						{
							$info['simple_scores'] = array_sum($info['ress'])/1000;
							$info['scores'] = $info['simple_scores']*$info['level'];
						}
						
						# Runden
						stdround(&$info['att']);
						stdround(&$info['def']);
						stdround(&$info['trans'][0]);
						stdround(&$info['trans'][1]);
						stdround(&$info['time']);
						stdround(&$info['speed']);
						break;
					case 'verteidigung':
						$info['att'] *= pow(1.05, $this->getItemLevel('F4', 'forschung', $run_eventhandler));
						$info['def'] *= pow(1.05, $this->getItemLevel('F5', 'forschung', $run_eventhandler));
						$info['time'] *= pow(0.95, $this->getItemLevel('B10', 'gebaeude', $run_eventhandler));
						
						if($calc_scores)
						{
							$info['simple_scores'] = array_sum($info['ress'])/1000;
							$info['scores'] = $info['simple_scores']*$info['level'];
						}
						
						stdround(&$info['att']);
						stdround(&$info['def']);
						stdround(&$info['time']);
						break;
				}
				
				# Mindestbauzeit zwoelf Sekunden aufgrund von Serverbelastung
				if($type == 'forschung')
				{
					if($info['time_local'] < 12) $info['time_local'] = 12;
					if($info['time_global'] < 12) $info['time_global'] = 12;
				}
				elseif($info['time'] < 12) $info['time'] = 12;
				
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
		
		function changeItemLevel($id, $value=1, $type=false, $time=false, &$actions=false)
		{
			if(!$this->status) return false;
			
			if($value == 0) return true;
			
			if($time === false) $time = time();
			
			if($actions === false) $actions = array();
			
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
				$item = Classes::Item($id);
				$type = $item->getType();
				if(isset($this->items['ids'][$id])) $this->items['ids'][$id] += $value;
				else
				{
					if(!isset($this->items[$type])) $this->items[$type] = array();
					$this->items[$type][$id] = $value;
					$this->items['ids'][$id] = &$this->items[$type][$id];
				}
			}
			
			# Felder belegen
			if($type == 'gebaeude')
			{
				$item_info = $this->getItemInfo($id, 'gebaeude');
				if($item_info['fields'] > 0)
					$this->changeUsedFields($item_info['fields']*$value);
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
						$size = $this->getTotalFields()/($this->getItemLevel('F9', false, false)-$value+1);
						$this->setFields($size*($this->getItemLevel('F9', false, false)+1));
					}
					$this->setActivePlanet($active_planet);
					break;
				
				# Bauroboter: Laufende Bauzeit verkuerzen
				case 'R01':
					$building = $this->checkBuildingThing('gebaeude');
					if($building && $building[1] > $time)
					{
						$remaining = ($building[1]-$time)*pow(1-0.00125*$this->getItemLevel('F2', 'forschung', false), $value);
						$this->raw['building']['gebaeude'][1] = $time+$remaining;
					}
					
					# Auch in $actions schauen
					$one = false;
					foreach($actions as $i=>$action2)
					{
						$this_item = Classes::Item($action2[1]);
						if($this_item->getType() == 'gebaeude')
						{
							$remaining = ($action2[0]-$time)*pow(1-0.00125*$this->getItemLevel('F2', 'forschung', false), $value);
							$actions[$i][0] = $time+$remaining;
							$one = true;
						}
					}
					if($one) usort($actions, 'sortEventhandlerActions');
					
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
							$f_1 = pow(1-0.00125*($this->getItemLevel('F2', false, false)-$value), $robs);
							$f_2 = pow(1-0.00125*$this->getItemLevel('F2', false, false), $robs);
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
									$f_1 = pow(1-0.00125*($this->getItemLevel('F2', false, false)-$value), $robs);
									$f_2 = pow(1-0.00125*$this->getItemLevel('F2', false, false), $robs);
									$remaining = ($action2[0]-$time)*$f_2/$f_1;
									$actions[$i][0] = $action2[0]+$remaining;
									$one = true;
								}
							}
							if($one) usort($actions, 'sortEventhandlerActions');
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
			
			$this->planet_info['last_refresh'] = $time;
			
			$this->changed = true;
			return true;
		}
		
		function checkProductionFactor($gebaeude)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(isset($this->planet_info['prod'][$gebaeude]))
				return $this->planet_info['prod'][$gebaeude];
			else return 1;
		}
		
		function setProductionFactor($gebaeude, $factor)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!$this->getItemInfo($gebaeude, 'gebaeude')) return false;
			
			$factor = (float) $factor;
			
			if($factor < 0) $factor = 0;
			if($factor > 1) $factor = 1;
			
			$this->planet_info['prod'][$gebaeude] = $factor;
			$this->changed = true;
			
			if(isset($this->cache['getProduction']) && isset($this->cache['getProduction'][$this->getActivePlanet()]))
				unset($this->cache['getProduction'][$this->getActivePlanet()]);
			if(isset($this->cache['getItemInfo']) && isset($this->cache['getItemInfo'][$this->getActivePlanet()]) && isset($this->cache['getItemInfo'][$this->getActivePlanet()][$gebaeude]))
				unset($this->cache['getItemInfo'][$this->getActivePlanet()][$gebaeude]);
			
			return true;
		}
		
		function getProduction()
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if(!isset($this->cache['getProduction'])) $this->cache['getProduction'] = array();
			$planet = $this->getActivePlanet();
			if(!isset($this->cache['getProduction'][$planet]))
			{
				$prod = array(0,0,0,0,0,0,0);
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
					
					stdround(&$prod[0]);
					stdround(&$prod[1]);
					stdround(&$prod[2]);
					stdround(&$prod[3]);
					stdround(&$prod[4]);
					stdround(&$prod[5]);
					
					$prod[6] = $f;
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
		
		function lockUser()
		{
			if(!$this->status) return false;
			
			$this->eventhandler(0, 1,1,1,1,1);
			$this->raw['locked'] = !$this->userLocked();
			$this->changed = true;
			
			# Planeteneigentuemer umbenennen
			$suffix = '';
			if($this->userLocked()) $suffix = ' (g)';
			$name = $this->getName().$suffix;
			$active_planet = $this->getActivePlanet();
			$planets = $this->getPlanetsList();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				$pos = $this->getPos();
				$galaxy = Classes::Galaxy($pos[0]);
				$galaxy->setPlanetOwner($pos[1], $pos[2], $name);
			}
			if($active_planet !== false) $this->setActivePlanet($active_planet);
			
			return true;
		}
		
		function umode($set=-1)
		{
			if(!$this->status) return false;
			
			if($set !== -1)
			{
				$set = (bool)$set;
				if($set == $this->umode()) return true;
				$this->raw['umode'] = $set;
				$this->raw['umode_time'] = time();
				$this->changed = true;
				
				$planet_owner = $this->getName();
				if($this->raw['umode']) $planet_owner = substr($planet_owner, 0, 20).' (U)';
				$active_planet = $this->getActivePlanet();
				$planets = $this->getPlanetsList();
				foreach($planets as $planet)
				{
					$this->setActivePlanet($planet);
					$pos = $this->getPos();
					$galaxy_obj = Classes::Galaxy($pos[0]);
					$galaxy_obj->setPlanetOwner($pos[1], $pos[2], $planet_owner);
				}
				$this->setActivePlanet($planet);
				
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
		
		function getUmodeReturnTime()
		{
			if(!$this->status) return false;
			
			if($this->umode()) return $this->raw['umode_time']+3*86400;
			else return time()+3*86400;
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
				),
				'show_building' => array(
					'gebaeude' => true,
					'forschung' => true,
					'roboter' => false,
					'schiffe' => false,
					'verteidigung' => false
				),
				'prod_show_days' => 1
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
		
		function checkBuildingThing($type, $run_eventhandler=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			if($run_eventhandler)
			{
				switch($type)
				{
					case 'gebaeude': $this->eventhandler(false, 1, 0, 0, 0, 0); break;
					case 'forschung': $this->eventhandler(false, 0, 1, 0, 0, 0); break;
					case 'roboter': $this->eventhandler(false, 0, 0, 1, 0, 0); break;
					case 'schiffe': $this->eventhandler(false, 0, 0, 0, 1, 0); break;
					case 'verteidigung': $this->eventhandler(false, 0, 0, 0, 0, 1); break;
					default: return false;
				}
			}
			
			switch($type)
			{
				case 'gebaeude': case 'forschung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || trim($this->planet_info['building'][$type][0]) == '')
						return false;
					return $this->planet_info['building'][$type];
				case 'roboter': case 'schiffe': case 'verteidigung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || count($this->planet_info['building'][$type]) <= 0)
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
							$this->setActivePlanet($planet);
							if($planet == $source_planet && $cancel)
								$this->addRess($this->planet_info['building'][$type][3]);
							unset($this->planet_info['building'][$type]);
						}
						$this->setActivePlanet($active_planet);
					}
					elseif($cancel)
						$this->addRess($this->planet_info['building'][$type][3]);
					
					if($cancel)
					{
						$this->raw['punkte'][7] -= $this->planet_info['building'][$type][3][0];
						$this->raw['punkte'][8] -= $this->planet_info['building'][$type][3][1];
						$this->raw['punkte'][9] -= $this->planet_info['building'][$type][3][2];
						$this->raw['punkte'][10] -= $this->planet_info['building'][$type][3][3];
						$this->raw['punkte'][11] -= $this->planet_info['building'][$type][3][4];
						if(isset($this->cache['getSpentRess'])) unset($this->cache['getSpentRess']);
					}
					
					unset($this->planet_info['building'][$type]);
					$this->changed = true;
					
					return true;
				case 'roboter': case 'schiffe': case 'verteidigung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || count($this->planet_info['building'][$type]) <= 0)
						return false;
					unset($this->planet_info['building'][$type]);
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
			$beginning_building = array();
			if(isset($this->planet_info['building'])) $beginning_building = $this->planet_info['building'];
			$beginning_changed = $this->changed;
			
			$recalc_gebaeude = false;
			$recalc_forschung = false;
			$recalc_roboter = false;
			$recalc_schiffe = false;
			$recalc_verteidigung = false;
			
			$building = $this->checkBuildingThing('gebaeude', false);
			if($building !== false && $building[1] <= time() && $this->removeBuildingThing('gebaeude', false))
			{
				$stufen = 1;
				if($building[2]) $stufen = -1;
				$actions[] = array($building[1], $building[0], $stufen, true);
				$recalc_gebaeude = true;
				
				if($check_gebaeude || $building[0]==$check_id) $run = true;
			}
			
			
			$building = $this->checkBuildingThing('forschung', false);
			if($building !== false && $building[1] <= time() && $this->removeBuildingThing('forschung', false))
			{
				$actions[] = array($building[1], $building[0], 1, true);
				$recalc_forschung = true;
				if($check_forschung || $building[0]==$check_id) $run = true;
			}
			
			
			$building = $this->checkBuildingThing('roboter', false);
			foreach($building as $j=>$items)
			{
				$info = $this->getItemInfo($items[0], 'roboter', false);
				if(!$info) continue;
				$time = $items[1];
				for($i=0; $i<$items[2]; $i++)
				{
					$time += $items[3];
					if($time <= time())
					{
						$actions[] = array($time, $items[0], 1, true);
						$recalc_roboter = true;
						if($check_roboter || $items[0]==$check_id) $run = true;
						
						# Roboter entfernen
						$this->planet_info['building']['roboter'][$j][2]--;
						if($this->planet_info['building']['roboter'][$j][2] <= 0)
						{
							unset($this->planet_info['building']['roboter'][$j]);
							break;
						}
						else $this->planet_info['building']['roboter'][$j][1] = $time;
					}
					else
						break 2;
				}
			}
			
			$building = $this->checkBuildingThing('schiffe', false);
			foreach($building as $j=>$items)
			{
				$info = $this->getItemInfo($items[0], 'schiffe', false);
				if(!$info) continue;
				$time = $items[1];
				for($i=0; $i<$items[2]; $i++)
				{
					$time += $items[3];
					if($time <= time())
					{
						$actions[] = array($time, $items[0], 1, true);
						$recalc_schiffe = true;
						if($check_schiffe || $items[0]==$check_id) $run = true;
						
						# Schiff entfernen
						$this->planet_info['building']['schiffe'][$j][2]--;
						if($this->planet_info['building']['schiffe'][$j][2] <= 0)
						{
							unset($this->planet_info['building']['schiffe'][$j]);
							break;
						}
						else $this->planet_info['building']['schiffe'][$j][1] = $time;
					}
					else
						break 2;
				}
			}
			
			
			$building = $this->checkBuildingThing('verteidigung', false);
			foreach($building as $j=>$items)
			{
				$info = $this->getItemInfo($items[0], 'verteidigung', false);
				if(!$info) continue;
				$time = $items[1];
				for($i=0; $i<$items[2]; $i++)
				{
					$time += $items[3];
					if($time <= time())
					{
						$actions[] = array($time, $items[0], 1, true);
						$recalc_verteidigung = true;
						if($check_verteidigung || $items[0]==$check_id) $run = true;
						
						# Schiff entfernen
						$this->planet_info['building']['verteidigung'][$j][2]--;
						if($this->planet_info['building']['verteidigung'][$j][2] <= 0)
						{
							unset($this->planet_info['building']['verteidigung'][$j]);
							break;
						}
						else $this->planet_info['building']['verteidigung'][$j][1] = $time;
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
					
					if(isset($this->cache['getProduction']))
						unset($this->cache['getProduction']);
					if(isset($this->cache['getItemInfo']))
						unset($this->cache['getItemInfo']);
					
					array_shift($actions);
				}
				
				$this->changed = true;
				$this->recalcHighscores($recalc_gebaeude, $recalc_forschung, $recalc_roboter, $recalc_schiffe, $recalc_verteidigung);
			}
			elseif(!$run)
			{
				$this->planet_info['building'] = $beginning_building;
				$this->changed = $beginning_changed;
			}
		}
		
		function isVerbuendet($user)
		{
			if(!$this->status) return false;
			
			if($user == $this->getName()) return true;
			
			if(!isset($this->raw['verbuendete'])) return false;
			return in_array($user, $this->raw['verbuendete']);
		}
		
		function existsVerbuendet($user)
		{
			if(!$this->status) return false;
			
			return (
				$user == $this->getName()
				|| (isset($this->raw['verbuendete']) && in_array($user, $this->raw['verbuendete']))
				|| (isset($this->raw['verbuendete_bewerbungen']) && in_array($user, $this->raw['verbuendete_bewerbungen']))
				|| (isset($this->raw['verbuendete_anfragen']) && in_array($user, $this->raw['verbuendete_anfragen']))
			);
		}
		
		function getVerbuendetList()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete'])) return array();
			else return $this->raw['verbuendete'];
		}
		
		function getVerbuendetApplicationList()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_bewerbungen'])) return array();
			else return $this->raw['verbuendete_bewerbungen'];
		}
		
		function getVerbuendetRequestList()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_anfragen'])) return array();
			else return $this->raw['verbuendete_anfragen'];
		}
		
		function _addVerbuendetRequest($user)
		{
			if(!$this->status) return false;
			if($this->existsVerbuendet($user)) return false;
			
			if(!isset($this->raw['verbuendete_anfragen'])) $this->raw['verbuendete_anfragen'] = array();
			$this->raw['verbuendete_anfragen'][] = $user;
			
			$this->changed = true;
			return true;
		}
		
		function _removeVerbuendetRequest($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_anfragen']) || !in_array($user, $this->raw['verbuendete_anfragen']))
				return false;
			unset($this->raw['verbuendete_anfragen'][array_search($user, $this->raw['verbuendete_anfragen'])]);
			$this->changed = true;
			return true;
		}
		
		function _removeVerbuendetApplication($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_bewerbungen']) || !in_array($user, $this->raw['verbuendete_bewerbungen']))
				return false;
			
			unset($this->raw['verbuendete_bewerbungen'][array_search($user, $this->raw['verbuendete_bewerbungen'])]);
			$this->changed = true;
			
			return true;
		}
		
		function _addVerbuendet($user)
		{
			if(!$this->status) return false;
			
			if($this->isVerbuendet($user)) return false;
			
			if(!isset($this->raw['verbuendete'])) $this->raw['verbuendete'] = array();
			$this->raw['verbuendete'][] = $user;
			$this->changed = true;
			return true;
		}
		
		function _removeVerbuendet($user)
		{
			if(!$this->status) return false;
			
			if(!$this->isVerbuendet($user)) return false;
			unset($this->raw['verbuendete'][array_search($user, $this->raw['verbuendete'])]);
			$this->changed = true;
			return true;
		}
		
		function applyVerbuendet($user, $text='')
		{
			if(!$this->status) return false;
			if($this->existsVerbuendet($user)) return false;
			
			$that_user = Classes::User($user);
			if($that_user->_addVerbuendetRequest($this->getName()))
			{
				if(!isset($this->raw['verbuendete_bewerbungen'])) $this->raw['verbuendete_bewerbungen'] = array();
				$this->raw['verbuendete_bewerbungen'][] = $user;
				$this->changed = true;
				
				$message = Classes::Message();
				if($message->create())
				{
					$message->addUser($user, 7);
					$message->subject("Anfrage auf ein B\xc3\xbcndnis");
					$message->from($this->getName());
					if(trim($text) == '')
						$message->text("Der Spieler ".$this->getName()." hat Ihnen eine mitteilungslose B\xc3\xbcndnisanfrage gestellt.");
					else
						$message->text($text);
				}
				
				return true;
			}
			else return false;
		}
		
		function acceptVerbuendetApplication($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_anfragen']) || !in_array($user, $this->raw['verbuendete_anfragen']))
				return false;
			
			$user_obj = Classes::User($user);
			if(!$user_obj->_removeVerbuendetApplication($this->getName())) return false;
			
			unset($this->raw['verbuendete_anfragen'][array_search($user, $this->raw['verbuendete_anfragen'])]);
			
			$user_obj->_addVerbuendet($this->getName());
			$this->_addVerbuendet($user);
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->from($this->getName());
				$message->subject("B\xc3\xbcndnisanfrage angenommen");
				$message->text("Der Spieler ".$this->getName()." hat Ihre B\xc3\xbcndnisanfrage angenommen.");
				$message->addUser($user, 7);
			}
			
			return true;
		}
		
		function rejectVerbuendetApplication($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_anfragen']) || !in_array($user, $this->raw['verbuendete_anfragen']))
				return false;
			
			$user_obj = Classes::User($user);
			if(!$user_obj->_removeVerbuendetApplication($this->getName())) return false;
			
			unset($this->raw['verbuendete_anfragen'][array_search($user, $this->raw['verbuendete_anfragen'])]);
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->from($this->getName());
				$message->subject("B\xc3\xbcndnisanfrage abgelehnt");
				$message->text("Der Spieler ".$this->getName()." hat Ihre B\xc3\xbcndnisanfrage abgelehnt.");
				$message->addUser($user, 7);
			}
			
			return true;
		}
		
		function quitVerbuendet($user)
		{
			if(!$this->status) return false;
			
			if(!$this->isVerbuendet($user)) return false;
			
			$user_obj = Classes::User($user);
			if($user_obj->_removeVerbuendet($user))
			{
				$this->_removeVerbuendet($user);
				
				$message = Classes::Message();
				if($message->create())
				{
					$message->from($this->getName());
					$message->subject("B\xc3\xbcndnis gek\xc3\xbcndigt");
					$message->text("Der Spieler ".$this->getName()." hat sein B\xc3\xbcndnis mit Ihnen gek\xc3\xbcndigt.");
					$message->addUser($user, 7);
				}
				
				$this->changed = true;
				
				return true;
			}
			else return false;
		}
		
		function verbuendetNewsletter($subject, $text)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete']) || count($this->raw['verbuendete']) <= 0) return false;
			if(trim($text) == '') return false;
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->from($this->getName());
				$message->subject($subject);
				$message->text($text);
				foreach($this->raw['verbuendete'] as $verbuendeter)
					$message->addUser($verbuendeter, 7);
			}
			return true;
		}
		
		function cancelVerbuendetApplication($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['verbuendete_bewerbungen']) || !in_array($user, $this->raw['verbuendete_bewerbungen']))
				return false;
			
			$user_obj = Classes::User($user);
			if($user_obj->_removeVerbuendetRequest($this->getName()))
			{
				unset($this->raw['verbuendete_bewerbungen'][array_search($user, $this->raw['verbuendete_bewerbungen'])]);
				
				$message = Classes::Message();
				if($message->create())
				{
					$message->from($this->getName());
					$message->subject("B\xc3\xbcndnisanfrage zur\xc3\xbcckgezogen");
					$message->text("Der Spieler ".$this->getName()." hat seine B\xc3\xbcndnisanfrage an Sie zur\xc3\xbcckgezogen.");
					$message->addUser($user, 7);
				}
				$this->changed = true;
				return true;
			}
			else return false;
		}
		
		function allianceTag($tag='')
		{
			if(!$this->status) return false;
			
			if($tag === '')
			{
				__autoload('Alliance');
				if(!isset($this->raw['alliance']) || trim($this->raw['alliance']) == '' || !Alliance::allianceExists($this->raw['alliance']))
					return false;
				else return trim($this->raw['alliance']);
			}
			else
			{
				if($tag)
				{
					$that_alliance = Classes::Alliance($tag);
					if(!$that_alliance->getStatus()) return false;
				}
				if((isset($this->raw['alliance']) && trim($this->raw['alliance']) != '') && (!$tag || $tag != $this->raw['alliance']))
				{
					# Aus der aktuellen Allianz austreten
					$my_alliance = Classes::Alliance(trim($this->raw['alliance']));
					if(!$my_alliance->getStatus()) return false;
					if(!$my_alliance->removeUser($this->getName())) return false;
					$this->raw['alliance'] = '';
					$this->changed = true;
				}
				
				if($tag)
					$that_alliance->addUser($this->getName(), $this->getScores());
				else $tag = '';
				$this->raw['alliance'] = $tag;
				
				$this->cancelAllianceApplication(false);
				$this->changed = true;
				
				$this->recalcHighscores();
				$active_planet = $this->getActivePlanet();
				$planets = $this->getPlanetsList();
				foreach($planets as $planet)
				{
					$this->setActivePlanet($planet);
					$pos = $this->getPos();
					$galaxy = Classes::Galaxy($pos[0]);
					$galaxy->setPlanetOwnerAlliance($pos[1], $pos[2], $tag);
				}
				$this->setActivePlanet($active_planet);
				
				return true;
			}
		}
		
		function cancelAllianceApplication($message=true)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['alliance_bewerbung']) || !$this->raw['alliance_bewerbung'])
				return false;
			
			$alliance_obj = Classes::Alliance($this->raw['alliance_bewerbung']);
			if(!$alliance_obj->deleteApplication($this->getName()))
				return false;
			if($message)
			{
				$message_obj = Classes::Message();
				if($message_obj->create())
				{
					$message_obj->from($this->getName());
					$message_obj->subject("Allianzbewerbung zur\xc3\xbcckgezogen");
					$message_obj->text('Der Benutzer '.$this->getName()." hat seine Bewerbung bei Ihrer Allianz zur\xc3\xbcckgezogen.");
					$users = $alliance_obj->getUsersWithPermission(4);
					foreach($users as $user)
						$message_obj->addUser($user, 7);
				}
			}
			unset($alliance_obj);
			$this->raw['alliance_bewerbung'] = false;
			$this->changed = true;
			return true;
		}
		
		function allianceApplication($alliance=false, $text=false)
		{
			if(!$this->status) return false;
			if($this->allianceTag()) return false;
			
			if(!$alliance)
			{
				if(!isset($this->raw['alliance_bewerbung'])) return false;
				return $this->raw['alliance_bewerbung'];
			}
			else
			{
				if($this->status != 1) return false;
				if(isset($this->raw['alliance_bewerbung']) && $this->raw['alliance_bewerbung'])
					return false;
				
				$alliance_obj = Classes::Alliance($alliance);
				if(!$alliance_obj->getStatus()) return false;
				if(!$alliance_obj->newApplication($this->getName())) return false;
				
				$message = Classes::Message();
				if($message->create())
				{
					$message_text = "Der Benutzer ".$this->getName()." hat sich bei Ihrer Allianz beworben. Gehen Sie auf Ihre Allianzseite, um die Bewerbung anzunehmen oder abzulehnen.";
					if(!trim($text))
						$message_text .= "\n\nDer Bewerber hat keinen Bewerbungstext hinterlassen.";
					else $message_text .= "\n\nDer Bewerber hat folgenden Bewerbungstext hinterlassen:\n\n".$text;
					$message->text($message_text);
					$message->from($this->getName());
					$message->subject('Neue Allianzbewerbung');
					
					$users = $alliance_obj->getUsersWithPermission(4);
					foreach($users as $user)
						$message->addUser($user, 7);
				}
				
				$this->raw['alliance_bewerbung'] = $alliance;
				$this->changed = true;
				return true;
			}
		}
		
		function quitAlliance()
		{
			if($this->status != 1) return false;
			if(!$this->allianceTag()) return false;
			
			$alliance = Classes::Alliance($this->allianceTag());
			if(!$alliance->removeUser($this->getName())) return false;
			
			$members = $alliance->getUsersList();
			if($members)
			{
				$message = Classes::Message();
				if($message->create())
				{
					$message->from($this->getName());
					$message->subject('Benutzer aus Allianz ausgetreten');
					$message->text('Der Benutzer '.$this->getName().' hat Ihre Allianz verlassen.');
					foreach($members as $member)
						$message->addUser($member, 7);
				}
			
			}
			
			$this->allianceTag(false);
			
			return true;
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
				if(!$this->checkRess($ress)) return false;

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
			if(($gebaeude = $this->checkBuildingThing('gebaeude')) && $gebaeude[0] == 'B8') return false;
			
			$buildable = true;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if(($global && $this->checkBuildingThing('forschung')) || (!$global && ($building = $this->checkBuildingThing('forschung')) && $building[0] == $id))
				{
					$buildable = false;
					break;
				}
			}
			$this->setActivePlanet($active_planet);
			
			$item_info = $this->getItemInfo($id, 'forschung');
			if($item_info && $item_info['buildable'] && $this->checkRess($item_info['ress']))
			{
				$build_array = array($id, time()+$item_info['time_'.($global ? 'global' : 'local')], $global, $item_info['ress']);
				if($global)
				{
					$build_array[] = $this->getActivePlanet();
					
					$planets = $this->getPlanetsList();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$this->planet_info['building']['forschung'] = $build_array;
					}
					$this->setActivePlanet($active_planet);
				}
				else $this->planet_info['building']['forschung'] = $build_array;
				
				$this->subtractRess($item_info['ress']);
				
				$this->changed = true;
				
				return true;
			}
			return false;
		}
		
		function buildRoboter($id, $anzahl)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;
			
			if(($gebaeude = $this->checkBuildingThing('gebaeude')) && $gebaeude[0] == 'B9') return false;
			
			$item_info = $this->getItemInfo($id, 'roboter');
			if(!$item_info || !$item_info['buildable']) return false;
			
			$ress = $item_info['ress'];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;
			
			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info['ress'];
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
			
			$roboter = $this->checkBuildingThing('roboter');
			$make_new = true;
			$last_time = time();
			if($roboter && count($roboter) > 0)
			{
				$roboter_keys = array_keys($this->planet_info['building']['roboter']);
				$last = &$this->planet_info['building']['roboter'][array_pop($roboter_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info['time'])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info['building'])) $this->planet_info['building'] = array();
				if(!isset($this->planet_info['building']['roboter'])) $this->planet_info['building']['roboter'] = array();
				$build_array = &$this->planet_info['building']['roboter'][];
				$build_array = array($id, $last_time, 0, $item_info['time']);
			}
			
			$build_array[2] += $anzahl;
			
			$this->subtractRess($ress);
			
			$this->changed = true;
			
			return true;
		}
		
		function buildSchiffe($id, $anzahl)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;
			
			if(($gebaeude = $this->checkBuildingThing('gebaeude')) && $gebaeude[0] == 'B10') return false;
			
			$item_info = $this->getItemInfo($id, 'schiffe');
			if(!$item_info || !$item_info['buildable']) return false;
			
			$ress = $item_info['ress'];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;
			
			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info['ress'];
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
			
			$schiffe = $this->checkBuildingThing('schiffe');
			$make_new = true;
			$last_time = time();
			if($schiffe && count($schiffe) > 0)
			{
				$schiffe_keys = array_keys($this->planet_info['building']['schiffe']);
				$last = &$this->planet_info['building']['schiffe'][array_pop($schiffe_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info['time'])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info['building'])) $this->planet_info['building'] = array();
				if(!isset($this->planet_info['building']['schiffe'])) $this->planet_info['building']['schiffe'] = array();
				$build_array = &$this->planet_info['building']['schiffe'][];
				$build_array = array($id, $last_time, 0, $item_info['time']);
			}
			
			$build_array[2] += $anzahl;
			
			$this->subtractRess($ress);
			
			$this->changed = true;
			
			return true;
		}
		
		function buildVerteidigung($id, $anzahl)
		{
			if(!$this->status || !isset($this->planet_info)) return false;
			
			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;
			
			if(($gebaeude = $this->checkBuildingThing('gebaeude')) && $gebaeude[0] == 'B10') return false;
			
			$item_info = $this->getItemInfo($id, 'verteidigung');
			if(!$item_info || !$item_info['buildable']) return false;
			
			$ress = $item_info['ress'];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;
			
			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info['ress'];
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
			
			$verteidigung = $this->checkBuildingThing('verteidigung');
			$make_new = true;
			$last_time = time();
			if($verteidigung && count($verteidigung) > 0)
			{
				$verteidigung_keys = array_keys($this->planet_info['building']['verteidigung']);
				$last = &$this->planet_info['building']['verteidigung'][array_pop($verteidigung_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info['time'])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info['building'])) $this->planet_info['building'] = array();
				if(!isset($this->planet_info['building']['verteidigung'])) $this->planet_info['building']['verteidigung'] = array();
				$build_array = &$this->planet_info['building']['verteidigung'][];
				$build_array = array($id, $last_time, 0, $item_info['time']);
			}
			
			$build_array[2] += $anzahl;
			
			$this->subtractRess($ress);
			
			$this->changed = true;
			
			return true;
		}
		
		function destroy()
		{
			if(!$this->status) return false;
			
			# Planeten zuruecksetzen
			$planets = $this->getPlanetsList();
			foreach($planets as $planet)
			{
				if(!$this->removePlanet()) return false;
			}
	
			# Buendnispartner entfernen
			$verb_list = $this->getVerbuendetList();
			foreach($verb_list as $verb)
				$this->quitVerbuendet($verb);
			$verb_list = $this->getVerbuendetApplicationList();
			foreach($verb_list as $verb)
				$this->cancelVerbuendetApplication($verb);
			$verb_list = $this->getVerbuendetRequestList();
			foreach($verb_list as $verb)
				$this->rejectVerbuendetRequest($verb);

			# Aus den Highscores entfernen
			$pos = ($this->getRank()-1)*38;

			$fh = fopen(DB_HIGHSCORES, 'r+');
			if(!fancy_flock($fh, LOCK_EX)) return false;
	
			$filesize = filesize(DB_HIGHSCORES)-38;
			fseek($fh, $pos, SEEK_SET);
	
			while(ftell($fh) <= $filesize-38)
			{
				fseek($fh, 38, SEEK_CUR);
				$bracket = fread($fh, 38);
				fseek($fh, -76, SEEK_CUR);
				fwrite($fh, $bracket);
	
				list($high_username) = decodeUserHighscoresString($bracket);
				$that_user = Classes::User($high_username);
				$that_user->setRank($that_user->getRank()-1);
				unset($that_user);
			}
	
			ftruncate($fh, $filesize);
	
			flock($fh, LOCK_UN);
			fclose($fh);
	
			# Nachrichten entfernen
			$categories = $this->getMessageCategoriesList();
			foreach($categories as $category)
			{
				$messages = $this->getMessagesList($category);
				foreach($messages as $message)
					$this->removeMessage($message, $category);
			}
			
			# Aus der Allianz austreten
			$this->allianceTag(false);
	
			$status = (unlink($this->filename) || chmod($this->filename, 0));
			if($status)
			{
				$this->status = 0;
				$this->changed = false;
				return true;
			}
			else return false;
		}
		
		function makeHighscoresString()
		{
			if(!$this->status) return false;
		
			return encodeUserHighscoresString($this->getName(), $this->getScores(), $this->allianceTag());
		}
		
		function recalcHighscores($recalc_gebaeude=false, $recalc_forschung=false, $recalc_roboter=false, $recalc_schiffe=false, $recalc_verteidigung=false)
		{
			$old_position = $this->getRank();
			$old_position_f = ($old_position-1)*38;

			if($recalc_gebaeude || $recalc_forschung || $recalc_roboter || $recalc_schiffe || $recalc_verteidigung)
			{
				if($recalc_gebaeude) $this->raw['punkte'][0] = 0;
				if($recalc_forschung) $this->raw['punkte'][1] = 0;
				if($recalc_roboter) $this->raw['punkte'][2] = 0;
				if($recalc_schiffe) $this->raw['punkte'][3] = 0;
				if($recalc_verteidigung) $this->raw['punkte'][4] = 0;
				
				$planets = $this->getPlanetsList();
				$active_planet = $this->getActivePlanet();
				foreach($planets as $planet)
				{
					$this->setActivePlanet($planet);
					
					if($recalc_gebaeude)
					{
						$items = $this->getItemsList('gebaeude');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'gebaeude', true, true);
							$this->raw['punkte'][0] += $item_info['scores'];
						}
					}
					
					if($recalc_forschung)
					{
						$items = $this->getItemsList('forschung');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'forschung', true, true);
							$this->raw['punkte'][1] += $item_info['scores'];
						}
					}
					
					if($recalc_roboter)
					{
						$items = $this->getItemsList('roboter');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'roboter', true, true);
							$this->raw['punkte'][2] += $item_info['scores'];
						}
					}
					
					if($recalc_schiffe)
					{
						$items = $this->getItemsList('schiffe');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'schiffe', true, true);
							$this->raw['punkte'][3] += $item_info['scores'];
						}
					}
					
					if($recalc_verteidigung)
					{
						$items = $this->getItemsList('verteidigung');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'verteidigung', true, true);
							$this->raw['punkte'][4] += $item_info['scores'];
						}
					}
				}
				$this->setActivePlanet($active_planet);

				foreach($this->getFleetsList() as $flotte)
				{
					$fl = Classes::Fleet($flotte);
					if($fl->userExists($this->getName()))
					{
						$schiffe = $fl->getFleetList($this->getName());
						foreach($schiffe as $id=>$count)
						{
							$item_info = $this->getItemInfo($id, 'schiffe', true, true);
							$this->raw['punkte'][3] += $count*$item_info['simple_scores'];
						}
						$transport = $fl->getTransport($this->getName());
						foreach($transport[1] as $id=>$count)
						{
							$item_info = $this->getItemInfo($id, 'roboter', true, true);
							$this->raw['punkte'][2] += $count*$item_info['simple_scores'];
						}
					}
					
					# Handel miteinbeziehen
					$users = $fl->getUsersList();
					foreach($users as $user)
					{
						$handel = $fl->getHandel($user);
						foreach($handel[1] as $id=>$count)
						{
							$item_info = $this->getItemInfo($id, 'roboter', true, true);
							$this->raw['punkte'][2] += $count*$item_info['simple_scores'];
						}
					}
				}
				
				if(isset($this->cache['getScores'])) unset($this->cache['getScores']);
			}

			$new_points = floor($this->getScores());
			$my_string = encodeUserHighscoresString($this->getName(), $new_points, $this->allianceTag());

			$filesize = filesize(DB_HIGHSCORES);

			$fh = fopen(DB_HIGHSCORES, 'r+');
			if(!$fh)
				return false;
			if(!fancy_flock($fh, LOCK_EX)) return false;

			fseek($fh, $old_position_f, SEEK_SET);

			$up = true;

			# Ueberpruefen, ob man in den Highscores abfaellt
			if($filesize-$old_position_f >= 76)
			{
				fseek($fh, 38, SEEK_CUR);
				list(,$this_points) = decodeUserHighscoresString(fread($fh, 38));
				fseek($fh, -76, SEEK_CUR);

				if($this_points > $new_points)
					$up = false;
			}

			if($up)
			{
				# In den Highscores nach oben rutschen
				while(true)
				{
					if(ftell($fh) == 0) # Schon auf Platz 1
					{
						fwrite($fh, $my_string);
						break;
					}
					fseek($fh, -38, SEEK_CUR);
					$cur = fread($fh, 38);
					list($this_user,$this_points) = decodeUserHighscoresString($cur);

					if($this_points < $new_points)
					{
						# Es muss weiter nach oben verschoben werden

						# Aktuellen Eintrag nach unten verschieben
						fwrite($fh, $cur);
						fseek($fh, -76, SEEK_CUR);
						# In dessen User-Array speichern
						$this_user = Classes::User($this_user);
						$this_user->setRank($this_user->getRank()+1);
						unset($this_user);
					}
					else
					{
						fwrite($fh, $my_string);
						break;
					}
				}
			}
			else
			{
				# In den Highscores nach unten rutschen

				while(true)
				{
					if($filesize-ftell($fh) < 76) # Schon auf dem letzten Platz
					{
						fwrite($fh, $my_string);
						break;
					}

					fseek($fh, 38, SEEK_CUR);
					$cur = fread($fh, 38);
					list($this_user, $this_points) = decodeUserHighscoresString($cur);
					fseek($fh, -76, SEEK_CUR);

					if($this_points > $new_points)
					{
						# Es muss weiter nach unten verschoben werden

						# Aktuellen Eintrag nach oben verschieben
						fwrite($fh, $cur);
						# In dessen User-Array speichern
						$this_user = Classes::User($this_user);
						$this_user->setRank($this_user->getRank()-1);
						unset($this_user);
					}
					else
					{
						fwrite($fh, $my_string);
						break;
					}
				}
			}

			$act_position = ftell($fh);

			flock($fh, LOCK_UN);
			fclose($fh);

			$act_platz = $act_position/38;
			if($act_platz != $old_position)
				$this->setRank($act_platz);

			$my_alliance = $this->allianceTag();
			if($my_alliance)
			{
				$alliance = Classes::Alliance($my_alliance);
				$alliance->setUserScores($this->getName(), $new_points);
			}
			
			$this->changed = true;

			return true;
		}
		
		function maySeeKoords($user)
		{
			if(!$this->status) return false;
			
			if($user == $this->getName()) return true;
			if($this->isVerbuendet($user)) return true;
			
			if($this->allianceTag())
			{
				$alliance = Classes::Alliance($this->allianceTag());
				if(!$alliance->getStatus()) return false;
				if(!$alliance->checkUserPermissions($this->getName(), 1)) return false;
				if(!in_array($user, $alliance->getUsersList())) return false;
				return true;
			}
		}
		
		function rename($new_name)
		{
			# Fehlt noch.
			
			# Planeteneigentuemer aendern
			# Nachrichtenabsender aendern
			# Bei Buendnispartnern abaendern
			# In Flottenbewegungen umbenennen
			# In der Allianz umbenennen
			# Datei umbenennen
			# Raw-interne Werte aendern
			# Highscores-Eintrag neu schreiben
			
			return true;
		}
		
		function lastMailSent($time=false)
		{
			if(!$this->status) return false;
			
			if($time !== false)
			{
				$this->raw['last_mail'] = $time;
				$this->changed = true;
				return true;
			}
			
			if(!isset($this->raw['last_mail'])) return false;
			return $this->raw['last_mail'];
		}
		
		function addForeignFleet($user, $fleet)
		{
			return true;
		}
		
		function getForeignUsersList()
		{
			return array();
		}
		
		function getForeignFleetsList($user)
		{
			return array();
		}
		
		function _printRaw()
		{
			echo "<pre>";
			print_r($this->raw);
			echo "</pre>";
		}
	}
	
	function encodeUserHighscoresString($username, $points, $alliance)
	{
		$points = floor($points);
		
		$string = substr($username, 0, 24);
		if(strlen($string) < 24)
			$string .= str_repeat(' ', 24-strlen($string));
		$string .= $alliance;
		if(strlen($string) < 30)
			$string .= str_repeat(' ', 30-strlen($string));
		$points_bin = add_nulls(base_convert($points, 10, 2), 64);
		for($i = 0; $i < strlen($points_bin); $i+=8)
			$string .= chr(bindec(substr($points_bin, $i, 8)));
		return $string;
	}
	
	function decodeUserHighscoresString($string)
	{
		$username = trim(substr($string, 0, 24));
		$alliance = trim(substr($string, 24, 6));
		$points_str = substr($string, 30);

		$points_bin = '';
		for($i = 0; $i < strlen($points_str); $i++)
			$points_bin .= add_nulls(decbin(ord($points_str[$i])), 8);

		$points = base_convert($points_bin, 2, 10);

		return array($username, $points, $alliance);
	}
	
	function getUsersCount()
	{
		clearstatcache();
		$filesize = filesize(DB_HIGHSCORES);
		if($filesize === false)
			return false;
		$players = floor($filesize/38);
		return $players;
	}
	
	function sortEventhandlerActions($a, $b)
	{
		if($a[0] < $b[0]) return -1;
		elseif($a[0] > $b[0]) return 1;
		else return 0;
	}
?>