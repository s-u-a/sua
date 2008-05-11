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

	class User extends Serialized
	{
		protected $active_planet = null;
		protected $active_planet_cache = array();
		protected $recalc_highscores = array(false,false,false,false,false,false,false);
		protected $last_eventhandler_run = array();
		protected $language_cache = null;

		function __construct($name=false, $write=true)
		{
			$this->save_dir = Classes::Database()->getDirectory()."/players";
			parent::__construct($name, $write);
		}

		function create()
		{
			if(file_exists($this->filename)) return false;
			$this->raw = array(
				'username' => $this->name,
				'planets' => array(),
				'forschung' => array(),
				'password' => 'x',
				'punkte' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
				'registration' => time(),
				'messages' => array(),
				'description' => '',
				'description_parsed' => '',
				'flotten' => array(),
				'flotten_passwds' => array(),
				'foreign_fleets' => array(),
				'foreign_coords' => array(),
				'alliance' => false,
				'lang' => l::language()
			);

			$highscores = Classes::Highscores();
			$highscores->updateUser($this->name, '');

			$this->write(true, false);
			$this->__construct($this->name);
			return true;
		}

		static function userExists($user)
		{
			$filename = Classes::Database()->getDirectory()."/players/".strtolower(urlencode($user));
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

			$this->active_planet = $planet;
			$this->planet_info = &$this->raw['planets'][$planet];

			$this->items['gebaeude'] = &$this->planet_info['gebaeude'];
			$this->items['roboter'] = &$this->planet_info['roboter'];
			$this->items['schiffe'] = &$this->planet_info['schiffe'];
			$this->items['verteidigung'] = &$this->planet_info['verteidigung'];

			return true;
		}

		function cacheActivePlanet()
		{
			if($this->active_planet === null) return null;
			return $this->active_planet_cache[] = $this->getActivePlanet();
		}

		function restoreActivePlanet()
		{
			if(count($this->active_planet_cache) < 1) return null;
			$this->setActivePlanet($p = array_pop($this->active_planet_cache));
			return $p;
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
			if(!$this->status || $this->active_planet === null) return false;

			return $this->active_planet;
		}

		function getPlanetsList()
		{
			if(!$this->status) return false;

			return array_keys($this->raw['planets']);
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

			return ceil($this->planet_info['size'][1]/($this->getItemLevel('F9', 'forschung')/self::getIngtechFactor()+1));
		}

		function setFields($size)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			$this->planet_info['size'][1] = $size;
			$this->changed = true;
			return true;
		}

		function getPlanet()
		{
			$pos = $this->getPos();
			return Classes::Planet(Classes::System(Classes::Galaxy($pos[0]), $pos[1]), $pos[2]);
		}

		function getPos()
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			$pos = explode(':', $this->planet_info['pos'], 3);
			if(count($pos) < 3) return false;
			return $pos;
		}

		function getPosString()
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			return $this->planet_info['pos'];
		}

		function getPosFormatted()
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			return vsprintf(_("%d:%d:%d"), $this->getPos());
		}

		function getPlanetClass()
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			$pos = $this->getPos();
			return Galaxy::calcPlanetClass($pos[0], $pos[1], $pos[2]);
		}

		function removePlanet()
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			global $types_message_types;

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
						$user_obj = Classes::User($user);
						$message->addUser($user, $types_message_types[$type]);
						$message->subject($user_obj->_("Flotte zurückgerufen"));
						$message->from($this->getName());
						$message->text($user_obj->_("Ihre Flotte befand sich auf dem Weg zum Planeten %s. Soeben wurde jener Planet verlassen, weshalb Ihre Flotte sich auf den Rückweg zu Ihrem Planeten %s macht."), $user_obj->localise(array("f", "format_planet"), $this->getPosString(), $this->planetName(), $this->getName()), $this->localise(array("f", "format_planet"), $pos_string, $this_galaxy->getPlanetName($pos[1], $pos[2])));
					}
				}
			}

			# Fremdstationierte Flotten auf diesem Planeten zurueckschicken
			foreach($this->getForeignUsersList() as $user)
			{
				$user_obj = Classes::User($user);
				foreach(array_keys($this->getForeignFleetsList($user)) as $i)
					$user_obj->callBackForeignFleet($this->getPosString(), $i);
			}

			# Fremdstationierte Flotten, die hier ihren Heimatplaneten haben, aufloesen
			foreach($this->getMyForeignFleets() as $koords)
			{
				$koords_a = explode(":", $koords);
				$galaxy_obj = Classes::Galaxy($koords_a[0]);
				$user_obj = Classes::User($galaxy_obj->getPlanetOwner($koords_a[1], $koords_a[2]));
				if(!$user_obj->getStatus()) continue;
				$user_obj->cacheActivePlanet();
				$user_obj->setActivePlanet($user_obj->getPlanetByPos($koords));
				foreach($user_obj->getForeignFleetsList($this->getName()) as $i=>$fleet)
				{
					if($fleet[1] == $this->getPosString())
						$user_obj->subForeignFleet($this->getName(), $i);
				}
				$user_obj->restoreActivePlanet();
			}

			# Planeten aus der Karte loeschen
			$this_pos = $this->getPos();
			if(!$this_pos) return false;

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
			elseif(isset($planets[$active_key-1]))
				$new_active_planet = array_search($planets[$active_key-1], $keys);
			else $new_active_planet = false;

			$new_planets = $this->getPlanetsList();
			foreach($new_planets as $planet)
			{
				$this->setActivePlanet($planet);
				$active_forschung = $this->checkBuildingThing('forschung');
				if(!$active_forschung) continue;
				if($active_forschung[2])
					$this->planet_info['building']['forschung'][4] = array_search($active_forschung[4], $keys);
			}

			if($new_active_planet !== false)
				$this->setActivePlanet($new_active_planet);

			# Highscores neu berechnen
			$this->recalcHighscores(true, true, true, true, true);

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
			$size = floor($size*($this->getItemLevel('F9', 'forschung')/self::getIngtechFactor()+1));

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
				'name' => $planet_name,
				"foreign_fleets" => array() # Enthaelt ein Array aus Arrays, von wem welche Flotten stationiert sind
			);

			$this->changed = true;

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
			$this->refreshMessengerBuildingNotifications();

			$this->setActivePlanet($planet2);
			$active_forschung = $this->checkBuildingThing('forschung');
			if($active_forschung && $active_forschung[2])
				$this->planet_info['building']['forschung'][4] = $planet;
			$this->refreshMessengerBuildingNotifications();

			if($new_active_planet != $planet2) $this->setActivePlanet($new_active_planet);

			return true;
		}

		function getScores($i=false)
		{
			if(!$this->status) return false;

			if($i === false)
				return $this->raw['punkte'][0]+$this->raw['punkte'][1]+$this->raw['punkte'][2]+$this->raw['punkte'][3]+$this->raw['punkte'][4]+$this->raw['punkte'][5]+$this->raw['punkte'][6];
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

			$this->recalc_highscores[$i] = true;
			$this->changed = true;
			return true;
		}

		function getSpentRess($i=false)
		{
			if(!$this->status) return false;

			if($i === false)
			{
				return $this->getScores(7)+$this->getScores(8)+$this->getScores(9)+$this->getScores(10)+$this->getScores(11);
			}
			else return $this->getScores($i+7);
		}

		function getRank($i=null)
		{
			if(!$this->status) return false;

			$highscores = Classes::Highscores();
			if($i === null)
				return $highscores->getPosition('users', $this->getName());
			else
				return $highscores->getPosition("users", $this->getName(), "scores_".$i);
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

			$ress = $this->planet_info["ress"];

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

			if(isset($ress[0])) $this->planet_info["ress"][0] += $ress[0];
			if(isset($ress[1])) $this->planet_info["ress"][1] += $ress[1];
			if(isset($ress[2])) $this->planet_info["ress"][2] += $ress[2];
			if(isset($ress[3])) $this->planet_info["ress"][3] += $ress[3];
			if(isset($ress[4])) $this->planet_info["ress"][4] += $ress[4];

			$this->changed = true;

			return true;
		}

		function subtractRess($ress, $make_scores=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!is_array($ress)) return false;

			if(isset($ress[0])){ $this->planet_info["ress"][0] -= $ress[0]; if($make_scores) $this->raw['punkte'][7] += $ress[0]; }
			if(isset($ress[1])){ $this->planet_info["ress"][1] -= $ress[1]; if($make_scores) $this->raw['punkte'][8] += $ress[1]; }
			if(isset($ress[2])){ $this->planet_info["ress"][2] -= $ress[2]; if($make_scores) $this->raw['punkte'][9] += $ress[2]; }
			if(isset($ress[3])){ $this->planet_info["ress"][3] -= $ress[3]; if($make_scores) $this->raw['punkte'][10] += $ress[3]; }
			if(isset($ress[4])){ $this->planet_info["ress"][4] -= $ress[4]; if($make_scores) $this->raw['punkte'][11] += $ress[4]; }

			$this->changed = true;

			return true;
		}

		function checkRess($ress)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!is_array($ress)) return false;

			if(isset($ress[0]) && $ress[0] > $this->planet_info["ress"][0]) return false;
			if(isset($ress[1]) && $ress[1] > $this->planet_info["ress"][1]) return false;
			if(isset($ress[2]) && $ress[2] > $this->planet_info["ress"][2]) return false;
			if(isset($ress[3]) && $ress[3] > $this->planet_info["ress"][3]) return false;
			if(isset($ress[4]) && $ress[4] > $this->planet_info["ress"][4]) return false;

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

			if(isset($this->raw['flotten']) && count($this->raw['flotten']) > 0)
			{
				$eventfile = Classes::EventFile();
				foreach($this->raw['flotten'] as $i=>$flotte)
				{
					if(!Fleet::exists($flotte))
					{
						unset($this->raw['flotten'][$i]);
						$this->changed = true;
						continue;
					}
					/*$fl = Classes::Fleet($flotte);
					if($fl->getStatus() == 1)
					{
						$arrival = $fl->getNextArrival();
						if($arrival <= time() && $fl->arriveAtNextTarget())
							$eventfile->removeCanceledFleet($flotte, $arrival);
					}*/
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

			return ceil(pow($werft*$this->getItemLevel('F0', 'forschung'), .7));
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
					else $fleets += $fl->getNeededSlots();
				}
			}

			# Fremdstationierte Flotten benoetigen einen Slot
			foreach($this->getMyForeignFleets() as $coords)
			{
				$coords_a = explode(":", $coords);
				$galaxy_obj = Classes::Galaxy($coords_a[0]);
				$user_obj = Classes::User($galaxy_obj->getPlanetOwner($coords_a[1], $coords_a[2]));
				if(!$user_obj->getStatus()) continue;
				$user_obj->cacheActivePlanet();
				$user_obj->setActivePlanet($user_obj->getPlanetByPos($coords));
				$fleets += count($user_obj->getForeignFleetsList($this->getName()));
				$user_obj->restoreActivePlanet();
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

		function findMessageType($message_id)
		{
			if(!$this->status) return false;

			foreach($this->raw['messages'] as $type=>$messages)
			{
				if(isset($messages[$message_id])) return $type;
			}
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

			if(!isset($this->raw['messages']) || !isset($this->raw['messages'][$type]))
				return array();
			else return array_reverse(array_keys($this->raw['messages'][$type]));
		}

		function getMessageCategoriesList()
		{
			if(!$this->status) return false;

			if(!isset($this->raw['messages'])) $return = array();
			elseif(!isset($this->raw['messages'])) $return = array();
			else $return = array_keys($this->raw['messages']);
			sort($return, SORT_NUMERIC);
			return $return;
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
		}

		function removeMessage($message_id, $type=null, $edit_message=true)
		{
			if(!$this->status) return false;

			if(!isset($type) && isset($this->raw["messages"]))
			{
				foreach($this->raw["messages"] as $type=>$messages)
				{
					if(isset($messages[$message_id]))
					{
						unset($this->raw["messages"][$type][$message_id]);
						$this->changed = true;
					}
				}
				return true;
			}

			if(!isset($this->raw['messages']) || !isset($this->raw['messages'][$type]) || !isset($this->raw['messages'][$type][$message_id]))
				return 2;
			unset($this->raw['messages'][$type][$message_id]);
			$this->changed = true;

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
				if(isset($this->raw['email_passwd']) && $this->raw['email_passwd'])
				{
					$this->raw['email_passwd'] = false;
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

			if(isset($this->raw['email_passwd']) && $this->raw['email_passwd'])
				$this->raw['email_passwd'] = false;

			$this->changed = true;
			return true;
		}

		function getPasswordSum()
		{
			if(!$this->status) return false;
			return $this->raw['password'];
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
					$this->raw['description_parsed'] = F::parse_html($this->raw['description']);
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
				$this->raw['description_parsed'] = F::parse_html($this->raw['description']);
				$this->changed = true;

				return true;
			}
			else
				return 2;
		}

		function lastRequest($last_request=null)
		{
			if(!$this->status) return false;

			if($last_request === null)
			{
				if(!isset($this->raw['last_request'])) return null;
				else return $this->raw['last_request'];
			}

			$this->raw['last_request'] = $last_request;
			$this->changed = true;
			return true;
		}

		function registerAction()
		{
			if(!$this->status) return false;

			$this->raw['last_request'] = $_SERVER['REQUEST_URI'];
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

		/**
		  * Liefert die Abhaengigkeiten des Gegenstandes mit der ID $id zurueck. Die Abhaengigkeiten
		  * werden rekursiv aufgeloest, also die Abhaengigkeiten der Abhaengigkeiten mitbeachtet.
		  * @param $deps Zur Rekursion, wird als Referenz uebergeben, die Abhaengigkeiten werden dann dem Array hinzugefuegt
		  * @return ( Item-ID => Stufe )
		*/

		function getItemDeps($id, $deps=null)
		{
			if(!$this->status) return false;

			if(!isset($deps)) $deps = array();

			$item_info = $this->getItemInfo($id, null, array("deps", "level"));
			if(!$item_info) return false;

			foreach($item_info["deps"] as $dep)
			{
				$dep = explode("-", $dep, 2);
				$dep_info = $this->getItemInfo($dep[0], null, array("level"));
				if(!$dep_info) continue;
				if(!isset($deps[$dep[0]]) || $deps[$dep[0]] < $dep[1])
					$deps[$dep[0]] = $dep[1];
				if($dep_info["level"] < $dep[1])
					$this->getItemDeps($dep[0], &$deps);
			}

			return $deps;
		}

		function _itemInfoFields($fields)
		{
			static $calc_items,$calc_deps;
			if(!isset($calc_items))
				$calc_items = array("type", "buildable", "debuildable", "deps-okay", "level", "prod", "has_prod", "time", "scores", "ress", "fields", "limit_factor", "time_local", "time_global", "limit_factor_local", "limit_factor_global", "simple_scores", "att", "def", "trans", "speed", "name");
			if(!isset($calc_deps))
				$calc_deps = array("has_prod" => array("prod", "level"),
				                   "prod" => array("level"),
				                   "scores" => array("level", "ress", "simple_scores"),
				                   "simple_scores" => array("ress"),
				                   "ress" => array("level"),
				                   "buildable" => array("fields"),
				                   "debuildable" => array("fields"),
				                   "limit_factor" => array("time", "level"),
				                   "time" => array("level"),
				                   "time_local" => array("time", "level"),
				                   "time_global" => array("time_local", "time", "level"),
				                   "limit_factor_local" => array("time_local", "time", "level"),
				                   "limit_factor_global" => array("time_global", "time_local", "time", "level")
				);
			$calc = array();
			if(!$fields)
			{
				foreach($calc_items as $c)
					$calc[$c] = true;
			}
			else
			{
				foreach($calc_items as $c)
					$calc[$c] = false;
				foreach($fields as $f)
					$calc[$f] = true;
			}
			foreach($calc_deps as $f=>$deps)
			{
				if($calc[$f])
				{
					foreach($deps as $dep)
					{
						if(!$calc[$dep])
						{
							$calc[$dep] = true;
							$fields[] = $dep;
						}
					}
				}
			}
			return array($calc, $fields);
		}

		function getItemInfo($id, $type=null, $fields=null, $run_eventhandler=null, $level=null)
		{
			if(!$this->status) return false;

			if($run_eventhandler === null) $run_eventhandler = true;

			list($calc, $fields) = $this->_itemInfoFields($fields);

			$this_planet = $this->getActivePlanet();
			$item = Classes::Item($id);
			if($type === null) $type = $item->getType();
			$info = $item->getInfo($fields);
			if($info === false) return false;
			if($calc["type"])
				$info['type'] = $type;
			if($calc["buildable"])
				$info['buildable'] = $item->checkDependencies($this, $run_eventhandler);
			if($calc["deps-okay"])
				$info['deps-okay'] = $item->checkDependencies($this, $run_eventhandler);
			if($calc["level"])
				$info['level'] = ($level !== null ? $level : $this->getItemLevel($id, $type, $run_eventhandler));
			if($calc["name"])
				$info["name"] = $this->_("[item_".$id."]");

			# Bauzeit als Anteil der Punkte des ersten Platzes
			/*if(isset($info['time']))
			{
				$highscores = Classes::Highscores();
				if($highscores->getStatus() && ($first = $highscores->getList('users', 1, 1)))
				{
					list($best_rank) = $first;
					if($best_rank['scores'] == 0) $f = 1;
					else $f = $this->getScores()/$best_rank['scores'];
					if($f < .5) $f = .5;
					$info['time'] *= $f;
				}
			}*/

			$database_config = Classes::Database(global_setting("DB"))->getConfig();
			$global_factors = isset($database_config["global_factors"]) ? $database_config["global_factors"] : array();

			if(isset($info['time']) && isset($global_factors["time"]))
				$info['time'] *= $global_factors['time'];
			if(isset($info['prod']) && isset($global_factors["production"]))
			{
				$info['prod'][0] *= $global_factors['production'];
				$info['prod'][1] *= $global_factors['production'];
				$info['prod'][2] *= $global_factors['production'];
				$info['prod'][3] *= $global_factors['production'];
				$info['prod'][4] *= $global_factors['production'];
				$info['prod'][5] *= $global_factors['production'];
			}
			if(isset($info['ress']) && isset($global_factors["costs"]))
			{
				$info['ress'][0] *= $global_factors['costs'];
				$info['ress'][1] *= $global_factors['costs'];
				$info['ress'][2] *= $global_factors['costs'];
				$info['ress'][3] *= $global_factors['costs'];
			}

			switch($type)
			{
				case 'gebaeude':
					if($calc["prod"] || $calc["time"])
						$max_rob_limit = floor($this->getBasicFields()/2);

					if($calc["has_prod"])
						$info['has_prod'] = ($info['prod'][0] > 0 || $info['prod'][1] > 0 || $info['prod'][2] > 0 || $info['prod'][3] > 0 || $info['prod'][4] > 0 || $info['prod'][5] > 0);
					if($calc["prod"])
					{
						$level_f = pow($info['level'], 2);
						$percent_f = $this->checkProductionFactor($id);
						$info['prod'][0] *= $level_f*$percent_f;
						$info['prod'][1] *= $level_f*$percent_f;
						$info['prod'][2] *= $level_f*$percent_f;
						$info['prod'][3] *= $level_f*$percent_f;
						$info['prod'][4] *= $level_f*$percent_f;
						$info['prod'][5] *= $level_f*$percent_f;

						$use_old_robtech = file_exists(global_setting("DB_USE_OLD_ROBTECH"));
						if($use_old_robtech)
							$minen_rob = 1+0.0003125*$this->getItemLevel('F2', 'forschung', $run_eventhandler);
						else
							$minen_rob = sqrt($this->getItemLevel("F2", "forschung", $run_eventhandler))/250;
						if($use_old_robtech && $minen_rob > 1 || !$use_old_robtech && $minen_rob > 0)
						{
							$use_max_limit = !file_exists(global_setting('DB_NO_STRICT_ROB_LIMITS'));

							$rob = $this->getItemLevel('R02', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B0', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B0', 'gebaeude', $run_eventhandler);
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info['prod'][0] *= pow($minen_rob, $rob);
							else
								$info['prod'][0] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel('R03', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B1', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B1', 'gebaeude', $run_eventhandler);
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info['prod'][1] *= pow($minen_rob, $rob);
							else
								$info['prod'][1] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel('R04', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B2', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B2', 'gebaeude', $run_eventhandler);
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info['prod'][2] *= pow($minen_rob, $rob);
							else
								$info['prod'][2] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel('R05', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B3', 'gebaeude', $run_eventhandler)) $rob = $this->getItemLevel('B3', 'gebaeude', $run_eventhandler);
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info['prod'][3] *= pow($minen_rob, $rob);
							else
								$info['prod'][3] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel('R06', 'roboter', $run_eventhandler);
							if($rob > $this->getItemLevel('B4', 'gebaeude', $run_eventhandler)*2) $rob = $this->getItemLevel('B4', 'gebaeude', $run_eventhandler)*2;
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info['prod'][4] *= pow($minen_rob, $rob);
							else
								$info['prod'][4] *= 1+$minen_rob*$rob;
						}
					}

					if($calc["time"])
					{
						$info['time'] *= pow($info['level']+1, 1.5);
						$baurob = 1-0.00125*$this->getItemLevel('F2', 'forschung', $run_eventhandler);
						$rob = $this->getItemLevel('R01', 'roboter', $run_eventhandler);
						if($rob > $max_rob_limit) $rob = $max_rob_limit;
						$info['time'] *= pow($baurob, $rob);
					}

					if($calc["scores"])
					{
						$ress = array_sum($info['ress']);
						$scores = 0;
						for($i=1; $i<=$info['level']; $i++)
							$scores += $ress*pow($i, 2.4);
						$info['scores'] = $scores/1000;
					}

					if($calc["ress"])
					{
						$ress_f = pow($info['level']+1, 2.4);
						$info['ress'][0] *= $ress_f;
						$info['ress'][1] *= $ress_f;
						$info['ress'][2] *= $ress_f;
						$info['ress'][3] *= $ress_f;
					}

					if($calc["buildable"] && $info['buildable'] && $info['fields'] > $this->getRemainingFields())
						$info['buildable'] = false;
					if($calc["debuildable"])
						$info['debuildable'] = ($info['level'] >= 1 && -$info['fields'] <= $this->getRemainingFields());

					if($calc["limit_factor"])
					{
						if($info['time'] < global_setting("MIN_BUILDING_TIME"))
							$info['limit_factor'] = $info['time']/global_setting("MIN_BUILDING_TIME");
						else
							$info['limit_factor'] = 1;
					}

					# Runden
					if($calc["prod"])
					{
						Functions::stdround($info['prod'][0]);
						Functions::stdround($info['prod'][1]);
						Functions::stdround($info['prod'][2]);
						Functions::stdround($info['prod'][3]);
						Functions::stdround($info['prod'][4]);
						Functions::stdround($info['prod'][5]);
					}
					if($calc["time"])
						Functions::stdround($info['time']);
					if($calc["ress"])
					{
						Functions::stdround($info['ress'][0]);
						Functions::stdround($info['ress'][1]);
						Functions::stdround($info['ress'][2]);
						Functions::stdround($info['ress'][3]);
						Functions::stdround($info['ress'][4]);
					}
					break;
				case 'forschung':
					if($calc["time"])
					{
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

						if($calc["time_local"])
							$info['time_local'] = $info['time']*pow(0.95, $local_labs);
						unset($info["time"]);
						if($calc["time_global"])
							$info['time_global'] = $info['time_local']*pow(0.975, $global_labs);
					}

					if($calc["scores"])
					{
						$ress = array_sum($info['ress']);
						$scores = 0;
						for($i=1; $i<=$info['level']; $i++)
							$scores += $ress*pow($i, 3);
						$info['scores'] = $scores/1000;
					}

					if($calc["ress"])
					{
						$ress_f = pow($info['level']+1, 3);
						$info['ress'][0] *= $ress_f;
						$info['ress'][1] *= $ress_f;
						$info['ress'][2] *= $ress_f;
						$info['ress'][3] *= $ress_f;
					}

					if($calc["limit_factor_local"])
					{
						if($info['time_local'] < global_setting("MIN_BUILDING_TIME"))
							$info['limit_factor_local'] = $info['time_local']/global_setting("MIN_BUILDING_TIME");
						else
							$info['limit_factor_local'] = 1;
					}
					if($calc["limit_factor_global"])
					{
						if($info['time_global'] < global_setting("MIN_BUILDING_TIME"))
							$info['limit_factor_global'] = $info['time_global']/global_setting("MIN_BUILDING_TIME");
						else
							$info['limit_factor_global'] = 1;
					}

					# Runden
					if($calc["time_local"])
						Functions::stdround($info['time_local']);
					if($calc["time_global"])
						Functions::stdround($info['time_global']);
					if($calc["ress"])
					{
						Functions::stdround($info['ress'][0]);
						Functions::stdround($info['ress'][1]);
						Functions::stdround($info['ress'][2]);
						Functions::stdround($info['ress'][3]);
						Functions::stdround($info['ress'][4]);
					}
					break;
				case 'roboter':
					if($calc["time"])
						$info['time'] *= pow(0.95, $this->getItemLevel('B9', 'gebaeude', $run_eventhandler));

					if($calc["simple_scores"])
						$info['simple_scores'] = array_sum($info['ress'])/1000;
					if($calc["scores"])
						$info['scores'] = $info['simple_scores']*$info['level'];

					if($calc["limit_factor"])
					{
						if($info['time'] < global_setting("MIN_BUILDING_TIME"))
							$info['limit_factor'] = $info['time']/global_setting("MIN_BUILDING_TIME");
						else
							$info['limit_factor'] = 1;
					}

					if($calc["time"])
						Functions::stdround($info['time']);
					break;
				case 'schiffe':
					if($calc["att"])
						$info['att'] *= pow(1.05, $this->getItemLevel('F4', 'forschung', $run_eventhandler));
					if($calc["def"])
						$info['def'] *= pow(1.05, $this->getItemLevel('F5', 'forschung', $run_eventhandler));
					if($calc["trans"])
					{
						$lad_f = pow(1.2, $this->getItemLevel('F11', 'forschung', $run_eventhandler));
						$info['trans'][0] *= $lad_f;
						$info['trans'][1] *= $lad_f;
					}
					if($calc["time"])
						$info['time'] *= pow(0.95, $this->getItemLevel('B10', 'gebaeude', $run_eventhandler));
					if($calc["speed"])
					{
						$info['speed'] *= pow(1.025, $this->getItemLevel('F6', 'forschung', $run_eventhandler));
						$info['speed'] *= pow(1.05, $this->getItemLevel('F7', 'forschung', $run_eventhandler));
						$info['speed'] *= pow(1.5, $this->getItemLevel('F8', 'forschung', $run_eventhandler));
					}

					if($calc["simple_scores"])
						$info['simple_scores'] = array_sum($info['ress'])/1000;
					if($calc["scores"])
						$info['scores'] = $info['simple_scores']*$info['level'];

					if($calc["limit_factor"])
					{
						if($info['time'] < global_setting("MIN_BUILDING_TIME"))
							$info['limit_factor'] = $info['time']/global_setting("MIN_BUILDING_TIME");
						else
							$info['limit_factor'] = 1;
					}

					# Runden
					if($calc["att"])
						Functions::stdround($info['att']);
					if($calc["def"])
						Functions::stdround($info['def']);
					if($calc["trans"])
					{
						Functions::stdround($info['trans'][0]);
						Functions::stdround($info['trans'][1]);
					}
					if($calc["time"])
						Functions::stdround($info['time']);
					if($calc["speed"])
						Functions::stdround($info['speed']);
					break;
				case 'verteidigung':
					if($calc["att"])
						$info['att'] *= pow(1.05, $this->getItemLevel('F4', 'forschung', $run_eventhandler));
					if($calc["def"])
						$info['def'] *= pow(1.05, $this->getItemLevel('F5', 'forschung', $run_eventhandler));
					if($calc["time"])
						$info['time'] *= pow(0.95, $this->getItemLevel('B10', 'gebaeude', $run_eventhandler));

					if($calc["simple_scores"])
						$info['simple_scores'] = array_sum($info['ress'])/1000;
					if($calc["scores"])
						$info['scores'] = $info['simple_scores']*$info['level'];

					if($calc["limit_factor"])
					{
						if($info['time'] < global_setting("MIN_BUILDING_TIME"))
							$info['limit_factor'] = $info['time']/global_setting("MIN_BUILDING_TIME");
						else
							$info['limit_factor'] = 1;
					}

					if($calc["att"])
						Functions::stdround($info['att']);
					if($calc["def"])
						Functions::stdround($info['def']);
					if($calc["time"])
						Functions::stdround($info['time']);
					break;
			}

			# Mindestbauzeit zwoelf Sekunden aufgrund von Serverbelastung
			if($type == 'forschung')
			{
				if($calc["time_local"] && $info['time_local'] < global_setting("MIN_BUILDING_TIME")) $info['time_local'] = global_setting("MIN_BUILDING_TIME");
				if($calc["time_global"] && $info['time_global'] < global_setting("MIN_BUILDING_TIME")) $info['time_global'] = global_setting("MIN_BUILDING_TIME");
			}
			elseif($calc["time"] && $info['time'] < global_setting("MIN_BUILDING_TIME")) $info['time'] = global_setting("MIN_BUILDING_TIME");

			return $info;
		}

		function getItemLevel($id, $type=null, $run_eventhandler=true)
		{
			if(!$this->status) return false;

			if($run_eventhandler) $this->eventhandler($id,0,0,0,0,0);

			if($type === false || $type === null)
				$type = Item::getItemType($id);
			if(!isset($this->items[$type]) || !isset($this->items[$type][$id]))
				return 0;
			return $this->items[$type][$id];
		}

		function changeItemLevel($id, $value=1, $type=null, $time=null)
		{
			if(!$this->status) return false;

			if($value == 0) return true;

			if($time === false || $time === null) $time = time();

			$recalc = array(
				'gebaeude' => 0,
				'forschung' => 1,
				'roboter' => 2,
				'schiffe' => 3,
				'verteidigung' => 4
			);

			if($type === false || $type === null)
				$type = Item::getItemType($id);

			if(!isset($this->items[$type])) $this->items[$type] = array();
			if(isset($this->items[$type][$id])) $this->items[$type][$id] += $value;
			else $this->items[$type][$id] = $value;

			$this->recalc_highscores[$recalc[$type]] = true;

			# Felder belegen
			if($type == 'gebaeude')
			{
				$item_info = $this->getItemInfo($id, 'gebaeude', array("fields"));
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
						$size = ceil($this->getTotalFields()/(($this->getItemLevel('F9', false, false)-$value)/self::getIngtechFactor()+1));
						$this->setFields(floor($size*($this->getItemLevel('F9', false, false)/self::getIngtechFactor()+1)));
					}
					$this->setActivePlanet($active_planet);
					break;

				# Bauroboter: Laufende Bauzeit verkuerzen (TODO?)
				/*case 'R01':
					$max_rob_limit = floor($this->getBasicFields()/2);
					$counting_after = $this->items[$type][$id];
					$counting_before = $counting_after-$value;
					if($counting_after > $max_rob_limit) $counting_after = $max_rob_limit;
					if($counting_before > $max_rob_limit) $counting_before = $max_rob_limit;
					$counting_value = $counting_after-$counting_before;

					$building = $this->checkBuildingThing('gebaeude');
					if($building && $building[1] > $time)
					{
						$f = pow(1-0.00125*$this->getItemLevel('F2', 'forschung', false), $counting_value);
						$old_finished = $building[4][0]-$building[1];
						$old_remaining = ($building[1]-$time)*$building[4][1];
						$new_remaining = $old_remaining*$f;
						if(($old_finished*$f)+$new_remaining < global_setting("MIN_BUILDING_TIME"))
						{
							$this->planet_info['building']['gebaeude'][4][1] = $new_remaining/(global_setting("MIN_BUILDING_TIME")-($old_finished*$f));
							$new_remaining = global_setting("MIN_BUILDING_TIME")-($old_finished*$f);
						}
						else
							$this->planet_info['building']['gebaeude'][4][1] = 1;
						$this->planet_info['building']['gebaeude'][1] = $time+$new_remaining;
					}

					break;*/

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

			$prod = $this->getProduction($time !== false);
			$limit = $this->getProductionLimit($time !== false);

			$f = ($time-$this->planet_info['last_refresh'])/3600;

			for($i=0; $i<=4; $i++)
			{
				if($this->planet_info["ress"][$i] >= $limit[$i])
					continue;
				$this->planet_info["ress"][$i] += $prod[$i]*$f;
				if($this->planet_info["ress"][$i] > $limit[$i])
					$this->planet_info["ress"][$i] = $limit[$i];
			}

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

			if(!$this->getItemInfo($gebaeude, 'gebaeude', array(false))) return false;

			$factor = (float) $factor;

			if($factor < 0) $factor = 0;
			if($factor > 1) $factor = 1;

			$this->planet_info['prod'][$gebaeude] = $factor;
			$this->changed = true;

			return true;
		}

		function getProduction($run_eventhandler=true)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			$planet = $this->getActivePlanet();
			$prod = array(0,0,0,0,0,0,0,false);
			if($this->permissionToAct())
			{
				$gebaeude = $this->getItemsList('gebaeude');

				$energie_prod = 0;
				$energie_need = 0;
				foreach($gebaeude as $id)
				{
					$item = $this->getItemInfo($id, 'gebaeude', null, false);
					if($item['prod'][5] < 0) $energie_need -= $item['prod'][5];
					elseif($item['prod'][5] > 0) $energie_prod += $item['prod'][5];

					$prod[0] += $item['prod'][0];
					$prod[1] += $item['prod'][1];
					$prod[2] += $item['prod'][2];
					$prod[3] += $item['prod'][3];
					$prod[4] += $item['prod'][4];
				}

				$limit = $this->getProductionLimit($run_eventhandler);
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

				foreach(global_setting("MIN_PRODUCTION") as $k=>$v)
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
			return $prod;
		}

		function getProductionLimit($run_eventhandler=true)
		{
			if(!$this->status || !$this->planet_info) return false;

			$limit = global_setting("PRODUCTION_LIMIT_INITIAL");
			$steps = global_setting("PRODUCTION_LIMIT_STEPS");
			$limit[0] += $this->getItemLevel("R01", "roboter", $run_eventhandler)*$steps[0];
			$limit[1] += $this->getItemLevel("R01", "roboter", $run_eventhandler)*$steps[1];
			$limit[2] += $this->getItemLevel("R01", "roboter", $run_eventhandler)*$steps[2];
			$limit[3] += $this->getItemLevel("R01", "roboter", $run_eventhandler)*$steps[3];
			$limit[4] += $this->getItemLevel("R01", "roboter", $run_eventhandler)*$steps[4];
			$limit[5] += $this->getItemLevel("F3", "forschung", $run_eventhandler)*$steps[5];

			return $limit;
		}

		function userLocked($check_unlocked=true)
		{
			if(!$this->status) return false;

			if($check_unlocked && isset($this->raw['lock_time']) && $this->raw['lock_time'] && time() > $this->raw['lock_time'])
				$this->lockUser(false, false);
			return (isset($this->raw['locked']) && $this->raw['locked']);
		}

		function lockedUntil()
		{
			if(!$this->status) return false;

			if(!$this->userLocked()) return false;
			if(!isset($this->raw['lock_time'])) return false;
			return $this->raw['lock_time'];
		}

		function lockUser($lock_time=false, $check_unlocked=true)
		{
			if(!$this->status) return false;

			$this->eventhandler(0, 1,1,1,1,1);
			$this->raw['locked'] = !$this->userLocked($check_unlocked);
			$this->raw['lock_time'] = ($this->raw['locked'] ? $lock_time : false);
			$this->changed = true;

			# Planeteneigentuemer umbenennen
			$flag = '';
			if($this->userLocked(false)) $flag = 'g';
			$active_planet = $this->getActivePlanet();
			$planets = $this->getPlanetsList();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				$pos = $this->getPos();
				$galaxy = Classes::Galaxy($pos[0]);
				$galaxy->setPlanetOwnerFlag($pos[1], $pos[2], $flag);
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
				if($set && !$this->umodePossible()) return false;

				if(!$set)
				{
					$time_diff = time()-$this->raw["umode_time"];
					$active_planet = $this->getActivePlanet();
					foreach($this->getPlanetsList() as $planet)
					{
						$this->setActivePlanet($planet);
						if(isset($this->planet_info["building"]) && isset($this->planet_info["building"]["gebaeude"]) && $this->planet_info["building"]["gebaeude"])
							$this->planet_info["building"]["gebaeude"][1] += $time_diff;
						if(isset($this->planet_info["building"]) && isset($this->planet_info["building"]["forschung"]) && $this->planet_info["building"]["forschung"])
							$this->planet_info["building"]["forschung"][1] += $time_diff;
						if(isset($this->planet_info["building"]) && isset($this->planet_info["building"]["roboter"]) && $this->planet_info["building"]["roboter"])
						{
							foreach($this->planet_info["building"]["roboter"] as $k=>$v)
								$this->planet_info["building"]["roboter"][$k][1] += $time_diff;
						}
						if(isset($this->planet_info["building"]) && isset($this->planet_info["building"]["schiffe"]) && $this->planet_info["building"]["schiffe"])
						{
							foreach($this->planet_info["building"]["schiffe"] as $k=>$v)
								$this->planet_info["building"]["schiffe"][$k][1] += $time_diff;
						}
						if(isset($this->planet_info["building"]) && isset($this->planet_info["building"]["verteidigung"]) && $this->planet_info["building"]["verteidigung"])
						{
							foreach($this->planet_info["building"]["verteidigung"] as $k=>$v)
								$this->planet_info["building"]["verteidigung"][$k][1] += $time_diff;
						}
					}
					foreach($this->getFleetsList() as $fleet)
					{
						$fleet_obj = Classes::Fleet($fleet);
						$fleet_obj->moveTime($time_diff);
					}
				}
				else
				{
					$eventfile = Classes::EventFile();
					foreach($this->getFleetsList() as $fleet)
						$eventfile->removeCanceledFleet($fleet);
				}

				$this->raw['umode'] = $set;
				$this->raw['umode_time'] = time();
				$this->changed = true;

				$flag = ($this->raw['umode'] ? 'U' : '');
				$active_planet = $this->getActivePlanet();
				$planets = $this->getPlanetsList();
				foreach($planets as $planet)
				{
					$this->setActivePlanet($planet);
					$pos = $this->getPos();
					$galaxy_obj = Classes::Galaxy($pos[0]);
					$galaxy_obj->setPlanetOwnerFlag($pos[1], $pos[2], $flag);
				}
				$this->setActivePlanet($planet);

				return true;
			}

			return (isset($this->raw['umode']) && $this->raw['umode']);
		}

		function umodePossible()
		{
			if(!$this->status) return false;

			foreach($this->getFleetsList() as $fleet)
			{
				$fleet_obj = Classes::Fleet($fleet);
				if(!$fleet_obj->userExists($this->getName()))
					continue;
				foreach($fleet_obj->getTargetsList() as $target)
				{
					$target_spl = explode(":", $target);
					$galaxy_obj = Classes::Galaxy($target_spl[0]);
					$owner = $galaxy_obj->getPlanetOwner($target_spl[1], $target_spl[2]);
					if($owner && $owner != $this->getName())
						return false;
				}
			}
			return true;
		}

		function permissionToUmode()
		{
			if(!$this->status) return false;

			if(!isset($this->raw['umode_time'])) return true;

			if($this->umode()) $min_days = 3; # Ist gerade im Urlaubsmodus
			else $min_days = 3;

			return ((time()-$this->raw['umode_time']) > $min_days*86400);
		}

		function getUmodeEnteringTime()
		{
			if(!$this->status) return false;

			if(!$this->umode() || !isset($this->raw["umode_time"])) return null;

			return $this->raw["umode_time"];
		}

		function getUmodeReturnTime()
		{
			if(!$this->status) return false;

			if($this->umode()) return $this->raw['umode_time']+3*86400;
			else return time()+3*86400;
		}

		function permissionToAct()
		{
			return !Config::database_locked() && !$this->userLocked() && !$this->umode();
		}

		protected function getDataFromRaw()
		{
			$settings = array('skin' => false, 'schrift' => true,
				'sonden' => 1, 'ress_refresh' => 0,
				'fastbuild' => false, 'shortcuts' => false,
				'tooltips' => false,
				'noads' => false, 'show_extern' => false,
				'notify' => false,
				"extended_buildings" => false,
				'fastbuild_full' => false,
				'receive' => array(
					1 => array(true, true),
					2 => array(true, false),
					3 => array(true, false),
					4 => array(true, true),
					5 => array(true, false)
				),
				'show_building' => array(
					'gebaeude' => 1,
					'forschung' => 1,
					'roboter' => 0,
					'schiffe' => 0,
					'verteidigung' => 0
				),
				'prod_show_days' => 1,
				'messenger_receive' => array(
					'messages' => array(1=>true, 2=>true, 3=>true, 4=>true, 5=>true, 6=>true, 7=>true),
					'building' => array('gebaeude' => 1, 'forschung' => 1, 'roboter' => 3, 'schiffe' => 3, 'verteidigung' => 3)
				),
				'lang' => l::language(),
				'fingerprint' => false,
				'gpg_im' => false,
				'timezone' => date_default_timezone_get()
			);

			$this->settings = array();
			foreach($settings as $setting=>$default)
			{
				if(isset($this->raw[$setting])) $this->settings[$setting] = $this->raw[$setting];
				else $this->settings[$setting] = $default;
			}
			if(!isset($this->settings['messenger_receive']['building']))
				$this->settings['messenger_receive']['building'] = array('gebaeude' => 1, 'forschung' => 1, 'roboter' => 3, 'schiffe' => 3, 'verteidigung' => 3);

			$this->items = array();
			$this->items['forschung'] = &$this->raw['forschung'];

			$this->name = $this->raw['username'];

			$this->realEventhandler();
		}

		protected function getRawFromData()
		{
			if($this->recalc_highscores[0] || $this->recalc_highscores[1] || $this->recalc_highscores[2] || $this->recalc_highscores[3] || $this->recalc_highscores[4] || $this->recalc_highscores[5] || $this->recalc_highscores[6])
				$this->doRecalcHighscores($this->recalc_highscores[0], $this->recalc_highscores[1], $this->recalc_highscores[2], $this->recalc_highscores[3], $this->recalc_highscores[4]);

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
						//if(!isset($this->raw['planets'][$source_planet]['building'][$type]) || trim($this->raw['planets'][$source_planet]['building'][$type][0]) == '')
						//	return false;
						$active_planet = $this->getActivePlanet();
						$planets = $this->getPlanetsList();
						foreach($planets as $planet)
						{
							$this->setActivePlanet($planet);
							if($planet == $source_planet && $cancel)
								$this->addRess($this->planet_info['building'][$type][3]);
							if(isset($this->planet_info['building'][$type]))
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
					}

					unset($this->planet_info['building'][$type]);
					$this->changed = true;

					if($cancel)
						$this->refreshMessengerBuildingNotifications($type);

					return true;
				case 'roboter': case 'schiffe': case 'verteidigung':
					if(!isset($this->planet_info['building']) || !isset($this->planet_info['building'][$type]) || count($this->planet_info['building'][$type]) <= 0)
						return false;
					unset($this->planet_info['building'][$type]);
					$this->changed = true;

					if($cancel)
						$this->refreshMessengerBuildingNotifications($type);

					return true;
			}
		}

		function eventhandler($check_id=false, $check_gebaeude=true, $check_forschung=true, $check_roboter=true, $check_schiffe=true, $check_verteidigung=true)
		{ /* Dummy function */ }

		/**
		  * Event handler helper function: returns the earliest item of type $type that has been built but not yet dealt with. Removes this item.
		  * @param $type String One of gebaeude, forschung, roboter, schiffe, verteidigung
		*/

		function getNextBuiltThing($type)
		{
			if(!$this->status) return false;

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

		function realEventhandler()
		{
			/* Array
			(
				[0] => Zeit
				[1] => ID
				[2] => Stufen hinzuzaehlen
				[3] => Rohstoffe neu berechnen?
			)*/

			if(!$this->raw) return false;

			if($this->umode())
				return 2;

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
			return true;
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

		function renameVerbuendet($old_name, $new_name)
		{
			if(!$this->status) return false;

			if($old_name == $new_name) return 2;

			$k1 = (isset($this->raw['verbuendete']) ? array_search($old_name, $this->raw['verbuendete']) : false);
			$k2 = (isset($this->raw['verbuendete_bewerbungen']) ? array_search($old_name, $this->raw['verbuendete_bewerbungen']) : false);
			$k3 = (isset($this->raw['verbuendete_anfragen']) ? array_search($old_name, $this->raw['verbuendete_anfragen']) : false);

			if($k1 !== false) $this->raw['verbuendete'][$k1] = $new_name;
			if($k2 !== false) $this->raw['verbuendete_bewerbungen'][$k2] = $new_name;
			if($k3 !== false) $this->raw['verbuendete_anfragen'][$k3] = $new_name;

			$this->changed = ($k1 !== false || $k2 !== false || $k3 !== false);

			return true;
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
					$message->subject($that_user->_("Anfrage auf ein Bündnis"));
					$message->from($this->getName());
					if(trim($text) == '')
						$message->text(sprintf($that_user->_("Der Spieler %s hat Ihnen eine mitteilungslose Bündnisanfrage gestellt."), $this->getName()));
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
				$message->subject($user_obj->_("Bündnisanfrage angenommen"));
				$message->text(sprintf($user_obj->_("Der Spieler %s hat Ihre Bündnisanfrage angenommen."), $this->getName()));
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
				$message->subject($user_obj->_("Bündnisanfrage abgelehnt"));
				$message->text(sprintf($user_obj->_("Der Spieler %s hat Ihre Bündnisanfrage abgelehnt."), $this->getName()));
				$message->addUser($user, 7);
			}

			return true;
		}

		function quitVerbuendet($user)
		{
			if(!$this->status) return false;

			if(!$this->isVerbuendet($user)) return false;

			$user_obj = Classes::User($user);
			if($user_obj->_removeVerbuendet($this->getName()))
			{
				$this->_removeVerbuendet($user);

				$message = Classes::Message();
				if($message->create())
				{
					$message->from($this->getName());
					$message->subject($user_obj->_("Bündnis gekündigt"));
					$message->text(sprintf($user_obj->_("Der Spieler %s hat sein Bündnis mit Ihnen gekündigt."), $this->getName()));
					$message->addUser($user, 7);
				}

				# Fremdstationierte Flotten zurueckholen
				$this->cacheActivePlanet();
				$user_obj->cacheActivePlanet();

				foreach($user_obj->getPlanetsList() as $planet)
				{
					$user_obj->setActivePlanet($planet);
					if(count($user_obj->getForeignFleetsList($this->getName())) > 0)
						$this->callBackForeignFleet($user_obj->getPosString());
				}

				foreach($this->getPlanetsList() as $planet)
				{
					$this->setActivePlanet($planet);
					if(count($this->getForeignFleetsList($user)) > 0)
						$user_obj->callBackForeignFleet($this->getPosString());
				}

				$this->restoreActivePlanet();
				$user_obj->restoreActivePlanet();

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
					$message->subject($user_obj->_("Bündnisanfrage zurückgezogen"));
					$message->text(sprintf($user_obj->_("Der Spieler %s hat seine Bündnisanfrage an Sie zurückgezogen."), $this->getName()));
					$message->addUser($user, 7);
				}
				$this->changed = true;
				return true;
			}
			else return false;
		}

		function allianceTag($tag='', $check=true)
		{
			if(!$this->status) return false;

			if($tag === '')
			{
				if(!isset($this->raw['alliance']) || trim($this->raw['alliance']) == '' || !Alliance::allianceExists($this->raw['alliance']))
					return false;
				else return trim($this->raw['alliance']);
			}
			else
			{
				if($tag && $check)
				{
					$that_alliance = Classes::Alliance($tag);
					if(!$that_alliance->getStatus()) return false;
				}
				if((isset($this->raw['alliance']) && trim($this->raw['alliance']) != '') && (!$tag || $tag != $this->raw['alliance']))
				{
					# Aus der aktuellen Allianz austreten
					if($check)
					{
						$my_alliance = Classes::Alliance(trim($this->raw['alliance']));
						if(!$my_alliance->getStatus()) return false;
						if(!$my_alliance->removeUser($this->getName())) return false;
					}
					$this->raw['alliance'] = '';
					$this->changed = true;
				}

				if($check)
				{
					if($tag)
					{
						$that_alliance->addUser($this->getName(), $this->getScores());
						$tag = $that_alliance->getName();
					}
					else $tag = '';
				}

				$this->raw['alliance'] = $tag;

				if($check) $this->cancelAllianceApplication(false);
				$this->changed = true;

				$highscores = Classes::Highscores();
				$highscores->updateUser($this->getName(), $tag);

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
			if($alliance_obj->getStatus())
			{
				if(!$alliance_obj->deleteApplication($this->getName()))
					return false;
				if($message)
				{
					foreach($users as $user)
					{
						$message_obj = Classes::Message();
						if($message_obj->create())
						{
							$user_obj = Classes::User($user);
							$message_obj->from($this->getName());
							$message_obj->subject($user_obj->_("Allianzbewerbung zurückgezogen"));
							$message_obj->text(sprintf($user_obj->_("Der Benutzer %s hat seine Bewerbung bei Ihrer Allianz zurückgezogen."),$this->getName()));
							$users = $alliance_obj->getUsersWithPermission(4);
							foreach($users as $user)
								$message_obj->addUser($user, 7);
						}
					}
				}
				unset($alliance_obj);
			}
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
				$alliance = $alliance_obj->getName();
				if(!$alliance_obj->getStatus()) return false;
				if(!$alliance_obj->newApplication($this->getName())) return false;

				$users = $alliance_obj->getUsersWithPermission(Alliance::$PERMISSION_APPLICATIONS);
				foreach($users as $user)
				{
					$message = Classes::Message();
					if($message->create())
					{
						$user_obj = Classes::User($user);
						$message_text = sprintf($user_obj->_("Der Benutzer %s hat sich bei Ihrer Allianz beworben. Gehen Sie auf Ihre Allianzseite, um die Bewerbung anzunehmen oder abzulehnen."), $this->getName());
						if(!trim($text))
							$message_text .= "\n\n".$user_obj->_("Der Bewerber hat keinen Bewerbungstext hinterlassen.");
						else $message_text .= "\n\n".$user_obj->_("Der Bewerber hat folgenden Bewerbungstext hinterlassen:")."\n\n".$text;
						$message->text($message_text);
						$message->from($this->getName());
						$message->subject($user_obj->_('Neue Allianzbewerbung'));

						$users = $alliance_obj->getUsersWithPermission(4);
						foreach($users as $user)
							$message->addUser($user, 7);
					}
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
			if($alliance->getStatus())
			{
				if(!$alliance->removeUser($this->getName())) return false;

				$members = $alliance->getUsersList();
				if($members)
				{
					foreach($members as $member)
					{
						$message = Classes::Message();
						if($message->create())
						{
							$user = Classes::User($member);
							$message->from($this->getName());
							$message->subject($user->_('Benutzer aus Allianz ausgetreten'));
							$message->text(sprintf($user->_('Der Benutzer %s hat Ihre Allianz verlassen.'), $this->getName()));
							$message->addUser($member, 7);
						}
					}
				}
			}

			$this->allianceTag(false);

			return true;
		}

		function checkPlanetCount()
		{
			if(!$this->status) return false;

			if(global_setting("MAX_PLANETS") > 0 && count($this->raw['planets']) < global_setting("MAX_PLANETS")) return true;
			else return false;
		}

		function buildGebaeude($id, $rueckbau=false)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if($this->checkBuildingThing('gebaeude')) return false;
			if($id == 'B8' && $this->checkBuildingThing('forschung')) return false;
			if($id == 'B9' && $this->checkBuildingThing('roboter')) return false;
			if($id == 'B10' && ($this->checkBuildingThing('schiffe') || $this->checkBuildingThing('verteidigung'))) return false;

			$item_info = $this->getItemInfo($id, 'gebaeude', array("buildable", "debuildable", "ress", "time", "limit_factor"));
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
				$this->planet_info['building']['gebaeude'] = array($id, $time, $rueckbau, $ress, array(time(), $item_info['limit_factor']));

				# Rohstoffe abziehen
				$this->subtractRess($ress);

				$this->refreshMessengerBuildingNotifications('gebaeude');

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

			$item_info = $this->getItemInfo($id, 'forschung', array("buildable", "ress", "time_global", "time_local"));
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

				$this->refreshMessengerBuildingNotifications('forschung');

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

			$item_info = $this->getItemInfo($id, 'roboter', array("buildable", "ress", "time"));
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

			$this->refreshMessengerBuildingNotifications('roboter');

			$this->changed = true;

			return true;
		}

		function buildSchiffe($id, $anzahl)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing('gebaeude')) && $gebaeude[0] == 'B10') return false;

			$item_info = $this->getItemInfo($id, 'schiffe', array("buildable", "ress", "time"));
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

			$this->refreshMessengerBuildingNotifications('schiffe');

			$this->changed = true;

			return true;
		}

		function buildVerteidigung($id, $anzahl)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing('gebaeude')) && $gebaeude[0] == 'B10') return false;

			$item_info = $this->getItemInfo($id, 'verteidigung', array("buildable", "ress", "time"));
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

			$this->refreshMessengerBuildingNotifications('verteidigung');

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
				$this->setActivePlanet($planet);
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
				$this->rejectVerbuendetApplication($verb);

			# Nachrichten entfernen
			$categories = $this->getMessageCategoriesList();
			foreach($categories as $category)
			{
				$messages = $this->getMessagesList($category);
				foreach($messages as $message)
					$this->removeMessage($message, $category);
			}

			# Aus der Allianz austreten
			if($this->allianceTag())
			{
				$alliance_obj = Classes::Alliance($this->allianceTag());
				if(count($alliance_obj->getUsersList()) < 2)
					$alliance_obj->destroy();
				elseif($alliance_obj->checkUserPermissions($this->getName(), Alliance::$PERMISSION_PERMISSIONS))
				{
					$bosses = 0;
					foreach($alliance_obj->getUsersList() as $member)
					{
						if($alliance_obj->checkUserPermissions($member, Alliance::$PERMISSION_PERMISSIONS))
							$bosses++;
					}
					if($bosses < 2) $alliance_obj->destroy();
				}
			}
			$this->allianceTag(false);

			# Flotten zurueckrufen
			$fleets = $this->getFleetsList();
			foreach($fleets as $fleet)
			{
				$fleet_obj = Classes::Fleet($fleet);
				foreach(array_reverse($fleet_obj->getUsersList()) as $username)
					$fleet_obj->callBack($username);
			}

			# IM-Benachrichtigungen entfernen
			$imfile = Classes::IMFile();
			$imfile->removeMessages($this->getName());

			# Aus den Highscores entfernen
			$highscores = Classes::Highscores();
			$highscores->removeEntry('users', $this->getName());

			$status = (unlink($this->filename) || chmod($this->filename, 0));
			if($status)
			{
				$this->status = 0;
				$this->changed = false;
				return true;
			}
			else return false;
		}

		function recalcHighscores($recalc_gebaeude=false, $recalc_forschung=false, $recalc_roboter=false, $recalc_schiffe=false, $recalc_verteidigung=false)
		{
			if(!$this->status) return false;

			$this->recalc_highscores[0] = ($this->recalc_highscores[0] || $recalc_gebaeude);
			$this->recalc_highscores[1] = ($this->recalc_highscores[1] || $recalc_forschung);
			$this->recalc_highscores[2] = ($this->recalc_highscores[2] || $recalc_roboter);
			$this->recalc_highscores[3] = ($this->recalc_highscores[3] || $recalc_schiffe);
			$this->recalc_highscores[4] = ($this->recalc_highscores[4] || $recalc_verteidigung);

			$this->changed = true;
			return 2;
		}

		function doRecalcHighscores($recalc_gebaeude=false, $recalc_forschung=false, $recalc_roboter=false, $recalc_schiffe=false, $recalc_verteidigung=false)
		{
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
							$item_info = $this->getItemInfo($item, 'gebaeude', array("scores"));
							$this->raw['punkte'][0] += $item_info['scores'];
						}
					}

					if($recalc_roboter)
					{
						$items = $this->getItemsList('roboter');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'roboter', array("scores"));
							$this->raw['punkte'][2] += $item_info['scores'];
						}
					}

					if($recalc_schiffe)
					{
						$items = $this->getItemsList('schiffe');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'schiffe', array("scores"));
							$this->raw['punkte'][3] += $item_info['scores'];
						}
					}

					if($recalc_verteidigung)
					{
						$items = $this->getItemsList('verteidigung');
						foreach($items as $item)
						{
							$item_info = $this->getItemInfo($item, 'verteidigung', array("scores"));
							$this->raw['punkte'][4] += $item_info['scores'];
						}
					}
				}
				$this->setActivePlanet($active_planet);

				if($recalc_forschung)
				{
					$items = $this->getItemsList('forschung');
					foreach($items as $item)
					{
						$item_info = $this->getItemInfo($item, 'forschung', array("scores"));
						$this->raw['punkte'][1] += $item_info['scores'];
					}
				}

				if($recalc_schiffe)
				{
					// Fremdstationierte Flotten einbeziehen
					$koords = $this->getMyForeignFleets();
					foreach($koords as $koord)
					{
						$koord = explode(":", $koord);
						$galaxy_obj = Classes::Galaxy($koord[0]);
						$user_obj = Classes::User($galaxy_obj->getPlanetOwner($koord[1], $koord[2]));
						if(!$user_obj->getStatus()) continue;
						foreach($user_obj->getForeignFleetsList($this->getName()) as $i=>$fleets)
						{
							foreach($fleets[0] as $id=>$count)
							{
								$item_info = $this->getItemInfo($id, "schiffe", array("simple_scores"));
								$this->raw["punkte"][3] += $count*$item_info["simple_scores"];
							}
						}
					}
				}

				if($recalc_schiffe || $recalc_roboter)
				{
					foreach($this->getFleetsList() as $flotte)
					{
						$fl = Classes::Fleet($flotte, false);
						if(!$fl->getStatus()) continue;
						if($fl->userExists($this->getName()))
						{
							if($recalc_schiffe)
							{
								$schiffe = $fl->getFleetList($this->getName());
								if($schiffe)
								{
									foreach($schiffe as $id=>$count)
									{
										$item_info = $this->getItemInfo($id, 'schiffe', array("simple_scores"));
										$this->raw['punkte'][3] += $count*$item_info['simple_scores'];
									}
								}
							}
							if($recalc_roboter)
							{
								$transport = $fl->getTransport($this->getName());
								if($transport)
								{
									foreach($transport[1] as $id=>$count)
									{
										$item_info = $this->getItemInfo($id, 'roboter', array("simple_scores"));
										$this->raw['punkte'][2] += $count*$item_info['simple_scores'];
									}
								}
							}
						}

						if($recalc_roboter)
						{
							# Handel miteinbeziehen
							$users = $fl->getUsersList();
							foreach($users as $user)
							{
								$handel = $fl->getHandel($user);
								if($handel)
								{
									foreach($handel[1] as $id=>$count)
									{
										$item_info = $this->getItemInfo($id, 'roboter', array("simple_scores"));
										$this->raw['punkte'][2] += $count*$item_info['simple_scores'];
									}
								}
							}
						}
					}
				}
			}

			$highscores = Classes::Highscores();
			$highscores->updateUser($this->getName(), false, $this->raw["punkte"]);

			$my_alliance = $this->allianceTag();
			if($my_alliance)
			{
				$alliance = Classes::Alliance($my_alliance);
				$alliance->setUserScores($this->getName(), $this->getScores());
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
			# Ueberpruefen
			$really_rename = (strtolower($new_name) != strtolower($this->name));

			if($really_rename)
			{
				$new_fname = $this->save_dir.'/'.strtolower(urlencode($new_name));
				if(file_exists($new_fname)) return false;
			}

			# Planeteneigentuemer aendern
			$active_planet = $this->getActivePlanet();
			foreach($this->getPlanetsList() as $planet)
			{
				$this->setActivePlanet($planet);
				$pos = $this->getPos();
				$galaxy_obj = Classes::Galaxy($pos[0]);
				$galaxy_obj->setPlanetOwner($pos[1], $pos[2], $new_name);
			}
			$this->setActivePlanet($active_planet);

			# Nachrichtenabsender aendern
			$message_db = new MessageDatabase();
			$message_db->renameUser($this->name, $new_name);
			unset($message_db);

			# Bei Buendnispartnern abaendern
			Classes::resetInstances('Users');
			foreach(array_merge($this->getVerbuendetList(), $this->getVerbuendetRequestList(), $this->getVerbuendetApplicationList()) as $username)
			{
				$user = new User($username);
				$user->renameVerbuendet($this->name, $new_name);
				unset($user);
			}

			# In Flottenbewegungen umbenennen
			Classes::resetInstances('Fleet');
			foreach($this->getFleetsList() as $fleet)
			{
				$fleet = new Fleet($fleet);
				$fleet->renameUser($this->name, $new_name);
			}

			# In der Allianz umbenennen
			if($this->allianceTag())
			{
				$alliance = Classes::Alliance($this->allianceTag());
				$alliance->renameUser($this->name, $new_name);
			}

			# Highscores-Eintrag neu schreiben
			$highscores = Classes::Highscores();
			$highscores->renameUser($this->name, $new_name);

			# IM-Benachrichtigungen aendern
			$imfile = Classes::IMFile();
			$imfile->renameUser($this->name, $new_name);

			$this->raw['username'] = $new_name;
			$this->changed = true;

			if($really_rename)
			{
				# Datei umbenennen
				$this->__destruct();
				rename($this->filename, $new_fname);
				$this->__construct($new_name);
				$this->setActivePlanet($active_planet);
			}
			else $this->name = $new_name;

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

		/**
		  * Stationiert eine Flotte $fleet vom Planeten $from, die dem Benutzer $user gehört, auf dem aktiven Planeten.
		  * @param $user string Der Benutzername des Eigentümers der Flotten.
		  * @param $fleet array Das Item-Array der Flotten. ( Item-ID => Anzahl )
		  * @param $from string Die Herkunfskoordinaten der Flotte. Hierhin werden sie beim Abbruch zurückgesandt.
		  * @param $speed_factor float Mit diesem Geschwindigkeitsfaktor wurden die Flotten losgeschickt, sie werden beim Abbruch genausolangsam zurückfliegen.
		  * @return boolean Erfolg
		*/

		function addForeignFleet($user, $fleet, $from, $speed_factor)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!isset($this->planet_info["foreign_fleets"]))
				$this->planet_info["foreign_fleets"] = array();

			if(!isset($this->planet_info["foreign_fleets"][$user]))
			{
				$this->planet_info["foreign_fleets"][$user] = array();
				$user_obj = Classes::User($user);
				if(!$user_obj->getStatus()) return false;
				$user_obj->_addForeignCoordinates($this->getPosString());
			}

			if(count($this->planet_info["foreign_fleets"][$user]) > 0)
				$next_i = max(array_keys($this->planet_info["foreign_fleets"][$user]))+1;
			else
				$next_i = 0;
			$this->planet_info["foreign_fleets"][$user][$next_i] = array($fleet, $from, $speed_factor);

			$this->changed = true;

			return $next_i;
		}

		/**
		  * Entfernt fremdstationierte Schiffe des Benutzers $username. Hat der Benutzer mehrere Flotten stationiert, wird zunächst von der ältesten abgezogen.
		  * @param $username Der Benutzername des Eigentümers der Schiffe.
		  * @param $id Die Item-ID des abzuziehenden Schiffstyps.
		  * @param $count Wieviele Schiffe abgezogen werden sollen.
		  * @return 2 Wenn nicht so viele Schiffe vorhanden waren.
		  * @return boolean Erfolg.
		*/

		function subForeignShips($username, $id, $count)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!isset($this->planet_info["foreign_fleets"]) || !isset($this->planet_info["foreign_fleets"][$username]))
				return false;
			foreach($this->planet_info["foreign_fleets"][$username] as $i=>$fleet)
			{
				if(!isset($fleet[0][$id])) continue;
				$fleet[0][$id] -= $count;
				$count = -$fleet[0][$id];
				if($fleet[0][$id] <= 0)
					unset($fleet[0][$id]);
				if(count($fleet[0]) > 0)
					$this->planet_info["foreign_fleets"][$username][$i] = $fleet;
				else
					unset($this->planet_info["foreign_fleets"][$username][$i]);
				if($count <= 0) break;
			}
			if(count($this->planet_info["foreign_fleets"][$username]) <= 0)
				unset($this->planet_info["foreign_fleets"][$username]);
			$this->changed = true;
			if($count > 0) return 2;
			return true;
		}

		/**
		  * Entfernt die fremdstationierte Flotte Nummer $i des Benutzers $user. $i kann mit getForeignFleetsList() herausgefunden werden.
		  * @param $user string Der Benutzername.
		  * @param $i integer Die Nummer der Flotte.
		  * @return boolean Erfolg.
		*/

		function subForeignFleet($user, $i)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!isset($this->planet_info["foreign_fleets"]) || !isset($this->planet_info["foreign_fleets"][$user]) || !isset($this->planet_info["foreign_fleets"][$user][$i]))
				return false;

			$message_obj = Classes::Message();
			$message_obj->create();

			if($message_obj->getStatus())
			{
				$message_obj->text(sprintf($this->_("Der Benutzer %s hat eine fremdstationierte Flotte von Ihrem Planeten „%s“ (%s) zurückgezogen.\nDie Flotte bestand aus folgenden Schiffen: %s"), $user, $this->planetName(), vsprintf($this->_("%d:%d:%d"), $this->getPos()), $this->_i(Items::makeItemsString($this->planet_info["foreign_fleets"][$user][$i][0], true, true))));
				$message_obj->subject(sprintf($this->_("Fremdstationierung zurückgezogen auf %s"), vsprintf($this->_("%d:%d:%d"), $this->getPos())));
				$message_obj->from($user);
				$message_obj->addUser($this->getName(), 3);
			}

			unset($this->planet_info["foreign_fleets"][$user][$i]);

			if(count($this->planet_info["foreign_fleets"][$user]) == 0)
			{
				unset($this->planet_info["foreign_fleets"][$user]);
				$user_obj = Classes::User($user);
				if(!$user_obj->getStatus()) return false;
				$user_obj->_subForeignCoordinates($this->getPosString());
			}

			$this->changed = true;

			return true;
		}

		/**
		  * Gibt die Liste der Benutzer zurück, die auf diesem Planeten Flotte stationiert haben.
		  * @return array ( Benutzername )
		  * @return false Bei Fehlschlag.
		*/

		function getForeignUsersList()
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!isset($this->planet_info["foreign_fleets"]))
				$this->planet_info["foreign_fleets"] = array();

			return array_keys($this->planet_info["foreign_fleets"]);
		}

		/**
		  * Gibt die Liste der fremdstationierten Flotten des Benutzers $user auf diesem Planeten zurück. Jede ankommende Fremdstationierung erhält einen eigenen Index. Um nur die Flotten einer bestimmten Fremdstationierung zu erhalten, kann $i gesetzt werden.
		  * @param $user string Der Benutzername.
		  * @param $i integer Der Index der Fremdstationierung.
		  * @return array ( Index => [ ( Item-ID => Anzahl ), Herkunftsplanet, Geschwindigkeitsfaktor ] ), wenn $i null ist
		  * @return array [ ( Item-ID => Anzahl ), Herkunftsplanet, Geschwindigkeitsfaktor ], wenn $i gesetzt ist
		  * @return false Bei Fehler, oder wenn $i gesetzt ist, die Flotte aber nicht existiert
		*/

		function getForeignFleetsList($user, $i=null)
		{
			if(!$this->status || !isset($this->planet_info)) return false;

			if(!isset($this->planet_info["foreign_fleets"]))
				$this->planet_info["foreign_fleets"] = array();

			if($i === null)
			{
				if(!isset($this->planet_info["foreign_fleets"][$user]))
					return array();
				else
					return $this->planet_info["foreign_fleets"][$user];
			}
			elseif(!isset($this->planet_info["foreign_fleets"][$user]) || !isset($this->planet_info["foreign_fleets"][$user][$i]))
				return false;
			else
				return $this->planet_info["foreign_fleets"][$user][$i];
		}

		/**
		 * Speichert Koordinaten ab, unter denen der Benutzer Flotte
		 * fremdstationiert hat. Wird von addForeignFleet auf den
		 * stationierenden Benutzer aufgerufen.
		*/

		function _addForeignCoordinates($coords)
		{
			if(!$this->status) return false;

			if(!isset($this->raw["foreign_coords"]))
				$this->raw["foreign_coords"] = array();

			if(in_array($coords, $this->raw["foreign_coords"]))
				return 2;

			$this->raw["foreign_coords"][] = $coords;

			$this->changed = true;

			return true;
		}

		/**
		 * Loescht die Koordinaten von User::_addForeignCoordinates
		 * wieder. Wird von subForeignFleet auf den zurueckziehenden
		 * Benutzer ausgefuehrt.
		*/

		function _subForeignCoordinates($coords)
		{
			if(!$this->status) return false;

			if(!isset($this->raw["foreign_coords"]))
				$this->raw["foreign_coords"] = array();

			$key = array_search($coords, $this->raw["foreign_coords"]);
			if($key === false) return 2;

			unset($this->raw["foreign_coords"][$key]);
			$this->changed = true;

			return true;
		}

		/**
		  * Liefert die Koordinaten zurück, bei denen dieser Benutzer Flotten stationiert hat.
		*/

		function getMyForeignFleets()
		{
			if(!$this->status) return false;

			if(!isset($this->raw["foreign_coords"]))
				$this->raw["foreign_coords"] = array();

			return array_unique($this->raw["foreign_coords"]);
		}

		function callBackForeignFleet($koords, $i=null)
		{
			if(!$this->status) return false;

			$koords_a = explode(":", $koords);
			$galaxy = Classes::Galaxy($koords_a[0]);
			$owner = $galaxy->getPlanetOwner($koords_a[1], $koords_a[2]);
			if(!$owner) return false;

			$user_obj = Classes::User($owner);
			if(!$user_obj->getStatus()) return false;
			$user_obj->cacheActivePlanet();
			$user_obj->setActivePlanet($user_obj->getPlanetByPos($koords));

			$fleets = $user_obj->getForeignFleetsList($this->getName());
			if($i !== null && !isset($fleets[$i])) return false;
			foreach($fleets as $i2=>$fleet)
			{
				if($i !== null && $i != $i2) continue;

				$fleet_obj = Classes::Fleet();
				$fleet_obj->create();

				if(!$fleet_obj->getStatus()) return false;

				$fleet_obj->addTarget(Planet::fromString($fleet[1]), 6, true);
				$fleet_obj->addUser($this->getName(), $koords, $fleet[2]);
				foreach($fleet[0] as $id=>$c)
					$fleet_obj->addFleet($id, $c, $this->getName());

				if(!$user_obj->subForeignFleet($this->getName(), $i))
					return false;

				$fleet_obj->start();
			}

			$user_obj->restoreActivePlanet();

			return true;
		}

		function _printRaw()
		{
			echo "<pre>";
			print_r($this->raw);
			echo "</pre>";
		}

		static function resolveName($name)
		{
			$instance = Classes::User($name);
			return $instance->getName();
		}

		function getNotificationType()
		{
			if(!$this->status) return false;

			if(!isset($this->raw['im_notification'])) return false;
			return $this->raw['im_notification'];
		}

		function checkNewNotificationType($uin, $protocol)
		{
			if(!$this->status) return false;

			$this->raw['im_notification_check'] = array($uin, $protocol, time());
			$this->changed = true;
			return true;
		}

		function doSetNotificationType($uin, $protocol)
		{
			if(!$this->status) return false;

			if(!isset($this->raw['im_notification_check'])) return false;
			if($this->raw['im_notification_check'][0] != $uin || $this->raw['im_notification_check'][1] != $protocol)
				return false;
			if(time()-$this->raw['im_notification_check'][2] > 86400) return false;

			$this->raw['im_notification'] = array($this->raw['im_notification_check'][0], $this->raw['im_notification_check'][1]);
			$this->changed = true;
			return true;
		}

		function disableNotification()
		{
			if(!$this->status) return false;

			$this->raw['im_notification_check'] = false;
			$this->raw['im_notification'] = false;
			$this->changed = true;
			return true;
		}

		function addPosShortcut($pos)
		{ # Fuegt ein Koordinatenlesezeichen hinzu
			if(!$this->status) return false;

			if(!is_array($this->raw['pos_shortcuts'])) $this->raw['pos_shortcuts'] = array();
			if(in_array($pos, $this->raw['pos_shortcuts'])) return 2;

			$this->raw['pos_shortcuts'][] = $pos;
			$this->changed = true;
			return true;
		}

		function getPosShortcutsList()
		{ # Gibt die Liste der Koordinatenlesezeichen zurueck
			if(!$this->status) return false;

			if(!isset($this->raw['pos_shortcuts'])) return array();
			return $this->raw['pos_shortcuts'];
		}

		function removePosShortcut($pos)
		{ # Entfernt ein Koordinatenlesezeichen wieder
			if(!$this->status) return false;

			if(!isset($this->raw['pos_shortcuts'])) return 2;
			$idx = array_search($pos, $this->raw['pos_shortcuts']);
			if($idx === false) return 2;
			unset($this->raw['pos_shortcuts'][$idx]);
			$this->changed = true;
			return true;
		}

		function movePosShortcutUp($pos)
		{ # Veraendert die Reihenfolge der Lesezeichen
			if(!$this->status) return false;

			if(!isset($this->raw['pos_shortcuts'])) return false;

			$idx = array_search($pos, $this->raw['pos_shortcuts']);
			if($idx === false) return false;

			$keys = array_keys($this->raw['pos_shortcuts']);
			$keys_idx = array_search($idx, $keys);

			if(!isset($keys[$keys_idx-1])) return false;

			list($this->raw['pos_shortcuts'][$idx], $this->raw['pos_shortcuts'][$keys[$keys_idx-1]]) = array($this->raw['pos_shortcuts'][$keys[$keys_idx-1]], $this->raw['pos_shortcuts'][$idx]); # Confusing, ain't it? ;-)
			$this->changed = true;
			return true;
		}

		function movePosShortcutDown($pos)
		{ # Veraendert die Reihenfolge der Lesezeichen
			if(!$this->status) return false;

			if(!isset($this->raw['pos_shortcuts'])) return false;

			$idx = array_search($pos, $this->raw['pos_shortcuts']);
			if($idx === false) return false;

			$keys = array_keys($this->raw['pos_shortcuts']);
			$keys_idx = array_search($idx, $keys);

			if(!isset($keys[$keys_idx+1])) return false;

			list($this->raw['pos_shortcuts'][$idx], $this->raw['pos_shortcuts'][$keys[$keys_idx+1]]) = array($this->raw['pos_shortcuts'][$keys[$keys_idx+1]], $this->raw['pos_shortcuts'][$idx]); # The same another time...
			$this->changed = true;
			return true;
		}

		function getPasswordSendID()
		{ # Liefert eine ID zurueck, die zum Senden des Passworts benutzt werden kann
			if($this->status != 1) return false;

			$send_id = md5(microtime());
			$this->raw['email_passwd'] = $send_id;
			$this->changed = true;
			return $send_id;
		}

		function checkPasswordSendID($id)
		{ # Ueberprueft, ob eine vom Benutzer eingegebene ID der letzten durch getPasswordSendID zurueckgelieferten ID entspricht
			if(!$this->status) return false;

			return (isset($this->raw['email_passwd']) && $this->raw['email_passwd'] && $this->raw['email_passwd'] == $id);
		}

		function refreshMessengerBuildingNotifications($type=false)
		{
			if(!$this->status || !$this->planet_info) return false;

			if($type == false)
			{
				return ($this->refreshMessengerBuildingNotifications('gebaeude')
				&& $this->refreshMessengerBuildingNotifications('forschung')
				&& $this->refreshMessengerBuildingNotifications('roboter')
				&& $this->refreshMessengerBuildingNotifications('schiffe')
				&& $this->refreshMessengerBuildingNotifications('verteidigung'));
			}

			if(!in_array($type, array('gebaeude', 'forschung', 'roboter', 'schiffe', 'verteidigung')))
				return false;

			$special_id = $this->getActivePlanet().'-'.$type;
			$imfile = Classes::IMFile();
			$imfile->removeMessages($this->getName(), $special_id);

			$reload_stack = Classes::ReloadStack();
			$reload_stack->reset($this->getName(), $special_id);

			$building = $this->checkBuildingThing($type);
			if(!$building) return 2;

			$messenger_receive = $this->checkSetting('messenger_receive');
			$messenger_settings = $this->getNotificationType();
			$add_message = ($messenger_settings && $messenger_receive['building'][$type]);

			$planet_prefix = "(".$this->planetName().", ".$this->getPosString().") ";

			switch($type)
			{
				case 'gebaeude': case 'forschung':
					if(!$building || ($type == 'forschung' && $building[2] && $this->getActivePlanet() != $building[4]))
						break;

					if($add_message)
					{
						$item_info = $this->getItemInfo($building[0], $type, array("name", "level"));

						if($type == 'gebaeude')
							$message = $planet_prefix."Gebäudebau abgeschlossen: ".$item_info['name']." (".($item_info['level']+($building[2] ? -1 : 1)).")";
						else
							$message = $planet_prefix."Forschung fertiggestellt: ".$item_info['name']." (".($item_info['level']+1).")";
						$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $message, $special_id, $building[1]);
					}
					break;
				case 'roboter': case 'schiffe': case 'verteidigung':
					$building_number = 0;
					$finish_time = time();
					foreach($building as $b)
					{
						$building_number += $b[2];
						while($building_number > global_setting("RELOAD_LIMIT"))
						{
							$building_number -= global_setting("RELOAD_LIMIT");
							$b[2] -= global_setting("RELOAD_LIMIT");
							if($b[2] >= 0) $finish_time += global_setting("RELOAD_LIMIT")*$b[3];
							else $finish_time -= $b[2]*$b[3];
							$reload_stack->addReload($this->getName(), $finish_time, $special_id);
						}
						if($b[2] > 0)
							$finish_time += $b[2]*$b[3];
					}

					if($add_message)
					{
						switch($type)
						{
							case 'roboter': $singular = 'Roboter'; $plural = 'Roboter'; $art = 'ein'; break;
							case 'schiffe': $singular = 'Schiff'; $plural = 'Schiffe'; $art = 'ein'; break;
							case 'verteidigung': $singular = 'Verteidigungsanlage'; $plural = 'Verteidigungsanlagen'; $art = 'eine'; break;
						}

						switch($messenger_receive['building'][$type])
						{
							case 1:
								foreach($building as $b)
								{
									$item_info = $this->getItemInfo($b[0], $type, array("name"));
									$time = $b[1];
									for($i=0; $i<$b[2]; $i++)
									{
										$time += $b[3];
										$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $planet_prefix.ucfirst($art)." ".$singular." der Sorte ".$item_info['name']." wurde fertiggestellt.", $special_id, $time);
									}
								}
								break;
							case 2:
								foreach($building as $b)
								{
									$item_info = $this->getItemInfo($b[0], $type, array("name"));
									$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $planet_prefix.$b[2]." ".($b[2]==1 ? $singular : $plural)." der Sorte ".$item_info['name']." ".($b[2]==1 ? 'wurde' : 'wurden')." fertiggestellt.", $special_id, $b[1]+$b[2]*$b[3]);
								}
								break;
							case 3:
								$keys = array_keys($building);
								$b = $building[array_pop($keys)];
								$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $planet_prefix."Alle ".$plural." wurden fertiggestellt.", $special_id, $b[1]+$b[2]*$b[3]);
								break;
						}
					}
					break;
			}
			return true;
		}

		function resolveFleetPasswd($passwd)
		{
			if(!$this->status) return false;

			if(!isset($this->raw["flotten_passwds"]) || !isset($this->raw["flotten_passwds"][$passwd])) return null;
			$fleet_id = $this->raw["flotten_passwds"][$passwd];

			# Ueberpruefen, ob die Flotte noch die Kriterien erfuellt, ansonsten aus der Liste loeschen
			$fleet = Classes::Fleet($fleet_id);
			if($fleet->getCurrentType() != 3 || $fleet->isFlyingBack() || array_search($this->getName(), $fleet->getUsersList()) !== 0)
			{
				unset($this->raw["flotten_passwds"][$passwd]);
				$this->changed = true;
				return null;
			}

			return $fleet_id;
		}

		function getFleetPasswd($fleet_id)
		{
			if(!$this->status) return false;

			if(!isset($this->raw["flotten_passwds"]) || ($idx = array_search($fleet_id, $this->raw["flotten_passwds"])) === false)
				return null;

			# Ueberpruefen, ob die Flotte noch die Kriterien erfuellt, ansonsten aus der Liste loeschen
			$fleet = Classes::Fleet($fleet_id);
			if($fleet->getCurrentType() != 3 || $fleet->isFlyingBack() || array_search($this->getName(), $fleet->getUsersList()) !== 0)
			{
				unset($this->raw["flotten_passwds"][$idx]);
				$this->changed = true;
				return null;
			}

			return $idx;
		}

		function changeFleetPasswd($fleet_id, $passwd)
		{
			if(!$this->status) return false;

			if(!isset($this->raw["flotten_passwds"]))
				$this->raw["flotten_passwds"] = array();

			$old_passwd = $this->getFleetPasswd($fleet_id);
			if(($old_passwd === null || $old_passwd != $passwd) && $this->resolveFleetPasswd($passwd) !== null)
				return false;

			if($old_passwd !== null)
				unset($this->raw["flotten_passwds"][$old_passwd]);

			if($passwd)
				$this->raw["flotten_passwds"][$passwd] = $fleet_id;

			$this->changed = true;
			return true;
		}

		function setLanguage()
		{
			if(!$this->status) return false;

			$lang = $this->checkSetting("lang");
			if($lang && $lang != -1)
			{
				$this->language_cache = l::language();
				l::language($lang);
				return 1;
			}
			return 2;
		}

		function restoreLanguage()
		{
			if($this->language_cache)
			{
				l::language($this->language_cache);
				$this->language_cache = null;
				return 1;
			}
			return 2;
		}

		function _($message)
		{
			$this->setLanguage();
			$ret = _($message);
			$this->restoreLanguage();
			return $ret;
		}

		function _i($message)
		{
			return $this->localise(array("l", "_i"), $message);
		}

		function localise($function)
		{
			$args = func_get_args();
			$this->setLanguage();
			$ret = call_user_func_array(array_shift($args), $args);
			$this->restoreLanguage();
			return $ret;
		}

		function ngettext($msgid1, $msgid2, $n)
		{
			$this->setLanguage();
			$ret = ngettext($msgid1, $msgid2, $n);
			$this->restoreLanguage();
			return $ret;
		}

		function date($format, $timestamp=null)
		{
			$timezone = date_default_timezone_get();
			date_default_timezone_set($this->checkSetting("timezone"));
			if($timestamp !== null) $r = date($format, $timestamp);
			else $r = date($format);
			date_default_timezone_set($timezone);
			return $r;
		}

		function getEMailAddress()
		{
			if(!$this->status) return false;

			if(isset($this->raw["email_new"]) && $this->raw["email_new"][1] <= time())
			{
				$this->raw["email"] = $this->raw["email_new"][0];
				unset($this->raw["email_new"]);
			}
			if(!isset($this->raw["email"]) || !$this->raw["email"])
				return null;
			return $this->raw["email"];
		}

		function getTemporaryEMailAddress($array=false)
		{
			if(!$this->status) return false;

			if(!isset($this->raw["email_new"]))
				return null;
			if($array)
				return $this->raw["email_new"];
			else
				return $this->raw["email_new"][0];
		}

		function setEMailAddress($address, $do_delay=true)
		{
			if($this->status != 1) return false;

			if($address === $this->getTemporaryEMailAddress())
				return true;
			elseif($do_delay && $this->getEMailAddress() != $this->getTemporaryEMailAddress() && $this->getEMailAddress() != $address)
				$this->raw["email_new"] = array($address, time()+global_setting("EMAIL_CHANGE_DELAY"));
			else
			{
				$this->raw["email"] = $address;
				if(isset($this->raw["email_new"]))
					unset($this->raw["email_new"]);
			}
			$this->changed = true;
			return true;
		}

		function sendMail($subject, $text, $last_mail_sent=null)
		{
			if(!$this->getEMailAddress()) return 2;
			$er = error_reporting();
			error_reporting(3);
			if(!(include_once("Mail.php")) || !(include_once("Mail/mime.php")))
			{
				error_reporting($er);
				return false;
			}

			$text = sprintf($this->_("Automatisch generierte Nachricht vom %s"), $this->date(_("Y-m-d H:i:s")))."\n\n".$text;

			$mime = new Mail_mime("\n");
			if($this->checkSetting("fingerprint"))
				$mime->setTXTBody(gpg_encrypt($text, $this->checkSetting("fingerprint")));
			else
				$mime->setTXTBody(GPG::sign($text));

			$body = $mime->get(array("text_charset" => "utf-8", "html_charset" => "utf-8", "head_charset" => "utf-8"));
			$hdrs = $mime->headers(array("From" => "\"".$this->_("[title_full]")."\" <".global_setting("EMAIL_FROM").">", "Subject" => $subject));

			$mail = Mail::factory('mail');
			$return = $mail->send($this->getEMailAddress(), $hdrs, $body);
			if($return && $last_mail_sent !== null)
				$this->lastMailSent($last_mail_sent);
			error_reporting($er);
			return $return;
		}

		function challengeNeeded()
		{
			if(!$this->status) return false;

			if(isset($_SESSION["admin_username"]))
				return false;

			if(!$this->permissionToAct()) return false;

			if(!isset($this->raw["next_challenge"]))
				return true;
			else return (time() >= $this->raw["next_challenge"]);
		}

		function challengePassed()
		{
			if($this->status != 1) return false;

			$this->raw["next_challenge"] = time()+rand(global_setting("CHALLENGE_MIN_TIME"), global_setting("CHALLENGE_MAX_TIME"));
			$this->raw["challenge_failures"] = 0;
			$this->changed = true;
			return true;
		}

		function challengeFailed()
		{
			if($this->status != 1) return false;

			if(!isset($this->raw["challenge_failures"]))
				$this->raw["challenge_failures"] = 0;
			$this->raw["challenge_failures"]++;
			$this->changed = true;

			if($this->raw["challenge_failures"] > global_setting("CHALLENGE_MAX_FAILURES") && !$this->userLocked())
				$this->lockUser(time()+global_setting("CHALLENGE_LOCK_TIME"));
			return true;
		}

		static function getUsersCount()
		{
			$highscores = Classes::Highscores();
			return $highscores->getCount('users');
		}

		static function getIngtechFactor()
		{
			if(file_exists(global_setting('DB_USE_OLD_INGTECH')))
				return 1;
			elseif(file_exists(global_setting("DB_USE_OLD_ROBTECH")))
				return 2;
			else
				return 10;
		}
	}
