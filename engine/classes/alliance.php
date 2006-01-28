<?php
	class Alliance extends Dataset
	{
		protected $save_dir = DB_ALLIANCES;
		private $datatype = 'alliance';
		
		function create()
		{
			if(file_exists($this->filename)) return false;
			$this->raw = array(
				'tag' => $this->name,
				'members' => array(),
				'name' => '',
				'description' => '',
				'description_parsed' => '',
				'inner_description' => '',
				'inner_description_parsed' => ''
			);
			
			$h_string = encodeAllianceHighscoresString($this->name, 0, 0, 0);
			
			$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'a');
			flock($fh, LOCK_EX);
			fwrite($fh, $h_string);
			flock($fh, LOCK_UN);
			fclose($fh);
			
			$fh = fopen(DB_HIGHSCORES_ALLIANCES2, 'a');
			flock($fh, LOCK_EX);
			fwrite($fh, $h_string);
			flock($fh, LOCK_UN);
			fclose($fh);
			
			$this->raw['platz'] = $this->raw['platz2'] = getAlliancesCount();
			
			$this->write(true, false);
			$this->__construct($this->name);
			return true;
		}
		
		function destroy($by_whom=false)
		{
			if($this->status != 1) return false;
			
			if($this->getMembersCount() > 0)
			{
				$members = $this->getUsersList();
				$message = Classes::Message();
				if($message->create())
				{
					$message->subject("Allianz aufgel\xc3\xb6st");
					$message->text('Die Allianz '.$this->getName()." wurde aufgel\xc3\xb6st.");
					if($by_whom) $message->from($by_whom);
					foreach($members as $member)
					{
						if($member == $by_whom) continue;
						$message->addUser($member, 7);
					}
				}
				$i = count($members);
				foreach($members as $member)
				{
					$this_user = Classes::User($member);
					if($i > 1) $this_user->allianceTag(false);
					else return $this_user->allianceTag(false);
					$i--;
				}
			}
			else
			{
				# Aus den Allianz-Highscores entfernen
				$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'r+');
				flock($fh, LOCK_EX);
				fseek($fh, $this->getRankAverage()*26, SEEK_SET);
				$filesize = filesize(DB_HIGHSCORES_ALLIANCES);
				
				while(true)
				{
					if($filesize-ftell($fh) < 26)
						break;
					$line = fread($fh, 26);
					$info = decodeAllianceHighscoresString($line);
					$that_alliance = Classes::Alliance($info[0]);
					$that_alliance->setRankAverage($that_alliance->getRankAverage()-1);
					unset($that_alliance);
					
					fseek($fh, -52, SEEK_CUR);
					fwrite($fh, $line);
					fseek($fh, 26, SEEK_CUR);
				}
				ftruncate($fh, $filesize-26);
				
				flock($fh, LOCK_UN);
				fclose($fh);
				
				$fh = fopen(DB_HIGHSCORES_ALLIANCES2, 'r+');
				flock($fh, LOCK_EX);
				fseek($fh, $this->getRankTotal()*26, SEEK_SET);
				$filesize = filesize(DB_HIGHSCORES_ALLIANCES2);
				
				while(true)
				{
					if($filesize-ftell($fh) < 26)
						break;
					$line = fread($fh, 26);
					$info = decodeAllianceHighscoresString($line);
					$that_alliance = Classes::Alliance($info[0]);
					$that_alliance->setRankTotal($that_alliance->getRankTotal()-1);
					unset($that_alliance);
					
					fseek($fh, -52, SEEK_CUR);
					fwrite($fh, $line);
					fseek($fh, 26, SEEK_CUR);
				}
				ftruncate($fh, $filesize-26);
				
				flock($fh, LOCK_UN);
				fclose($fh);
				
				$status = (unlink($this->filename) || chmod($this->filename, 0));
				if($status)
				{
					$this->status = 0;
					$this->changed = false;
					return true;
				}
				else return false;
			}
		}
		
		function allianceExists($alliance)
		{
			$filename = DB_ALLIANCES.'/'.urlencode($alliance);
			return (is_file($filename) && is_readable($filename));
		}
		
		function getAverageScores()
		{
			if(!$this->status) return false;
			
			return floor($this->getTotalScores()/$this->getMembersCount());
		}
		
		function getMembersCount()
		{
			if(!$this->status) return false;
			
			return count($this->raw['members']);
		}
		
		function getTotalScores()
		{
			if(!$this->status) return false;
			
			$overall = 0;
			foreach($this->raw['members'] as $member)
				$overall += $member['punkte'];
			return $overall;
		}
		
		function recalcHighscores()
		{
			if($this->status != 1) return false;
			
			$overall = 0;
			foreach($this->raw['members'] as $member)
				$overall += $member['punkte'];
			$average = floor($overall/count($this->raw['members']));
			$my_string = encodeAllianceHighscoresString($this->getName(), count($this->raw['members']), $average, $overall);
			
			$old_position = $this->getRankAverage();
			$old_position_f = ($old_position-1)*26;

			$filesize = filesize(DB_HIGHSCORES_ALLIANCES);

			$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'r+');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);

			fseek($fh, $old_position_f, SEEK_SET);

			$up = true;

			# Ueberpruefen, ob man in den Highscores abfaellt
			if($filesize-$old_position_f >= 52)
			{
				fseek($fh, 26, SEEK_CUR);
				list(,,$this_points) = decodeAllianceHighscoresString(fread($fh, 26));
				fseek($fh, -52, SEEK_CUR);

				if($this_points > $average)
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
					fseek($fh, -26, SEEK_CUR);
					$cur = fread($fh, 26);
					list($this_alliance,,$this_points) = decodeAllianceHighscoresString($cur);

					if($this_points < $average)
					{
						# Es muss weiter nach oben verschoben werden

						# Aktuellen Eintrag nach unten verschieben
						fwrite($fh, $cur);
						fseek($fh, -52, SEEK_CUR);
						# In dessen User-Array speichern
						$this_alliance_array = Classes::Alliance($this_alliance);
						$this_alliance_array->setRankAverage($this_alliance_array->getRankAverage()+1);
						unset($this_alliance_array);
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
					if($filesize-ftell($fh) < 52) # Schon auf dem letzten Platz
					{
						fwrite($fh, $my_string);
						break;
					}

					fseek($fh, 26, SEEK_CUR);
					$cur = fread($fh, 26);
					list($this_alliance,,$this_points) = decodeAllianceHighscoresString($cur);
					fseek($fh, -52, SEEK_CUR);

					if($this_points > $average)
					{
						# Es muss weiter nach unten verschoben werden

						# Aktuellen Eintrag nach oben verschieben
						fwrite($fh, $cur);
						# In dessen User-Array speichern
						$this_alliance_array = Classes::Alliance($this_alliance);
						$this_alliance_array->setRankAverage($this_alliance_array->getRankAverage()-1);
						unset($this_alliance_array);
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

			$act_platz = $act_position/26;
			$this->setRankAverage($act_platz);
			
			############# Gesamtpunkte ##############
			
			$old_position = $this->getRankTotal();
			$old_position_f = ($old_position-1)*26;

			$filesize = filesize(DB_HIGHSCORES_ALLIANCES2);

			$fh = fopen(DB_HIGHSCORES_ALLIANCES2, 'r+');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);

			fseek($fh, $old_position_f, SEEK_SET);

			$up = true;

			# Ueberpruefen, ob man in den Highscores abfaellt
			if($filesize-$old_position_f >= 52)
			{
				fseek($fh, 26, SEEK_CUR);
				list(,,,$this_points) = decodeAllianceHighscoresString(fread($fh, 26));
				fseek($fh, -52, SEEK_CUR);

				if($this_points > $overall)
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
					fseek($fh, -26, SEEK_CUR);
					$cur = fread($fh, 26);
					list($this_alliance,,,$this_points) = decodeAllianceHighscoresString($cur);

					if($this_points < $overall)
					{
						# Es muss weiter nach oben verschoben werden

						# Aktuellen Eintrag nach unten verschieben
						fwrite($fh, $cur);
						fseek($fh, -52, SEEK_CUR);
						# In dessen User-Array speichern
						$this_alliance_array = Classes::Alliance($this_alliance);
						$this_alliance_array->setRankTotal($this_alliance_array->getRankTotal()+1);
						unset($this_alliance_array);
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
					if($filesize-ftell($fh) < 52) # Schon auf dem letzten Platz
					{
						fwrite($fh, $my_string);
						break;
					}

					fseek($fh, 26, SEEK_CUR);
					$cur = fread($fh, 26);
					list($this_alliance,,,$this_points) = decodeAllianceHighscoresString($cur);
					fseek($fh, -52, SEEK_CUR);

					if($this_points > $overall)
					{
						# Es muss weiter nach unten verschoben werden

						# Aktuellen Eintrag nach oben verschieben
						fwrite($fh, $cur);
						# In dessen User-Array speichern
						$this_alliance_array = Classes::Alliance($this_alliance);
						$this_alliance_array->setRankTotal($this_alliance_array->getRankTotal()-1);
						unset($this_alliance_array);
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
			
			$act_platz = $act_position/26;
			$this->setRankTotal($act_platz);
			
			$this->changed = true;

			return true;
		}
		
		function getRankAverage()
		{
			if(!$this->status) return false;
			
			return $this->raw['platz'];
		}
		
		function setRankAverage($rank)
		{
			if($this->status != 1) return false;
			
			$this->raw['platz'] = $rank;
			$this->changed = true;
			return true;
		}
		
		function getRankTotal()
		{
			if(!$this->status) return false;
			
			return $this->raw['platz2'];
		}
		
		function setRankTotal($rank)
		{
			if($this->status != 1) return false;
			
			$this->raw['platz2'] = $rank;
			$this->changed = true;
			return true;
		}
		
		function setUserPermissions($user, $key, $permission)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['members'][$user])) return false;
			$this->raw['members'][$user]['permissions'][$key] = (bool) $permission;
			$this->changed = true;
			return true;
		}
		
		function checkUserPermissions($user, $key)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['members'][$user])) return false;
			if(!isset($this->raw['members'][$user]['permissions'][$key])) return false;
			return $this->raw['members'][$user]['permissions'][$key];
		}
		
		function setUserScores($user, $scores)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['members'][$user])) return false;
			$this->raw['members'][$user]['punkte'] = $scores;
			$this->changed = true;
			
			$this->recalcHighscores();
			
			return true;
		}
		
		function getUserScores($user)
		{
			if(!$this->status) return false;
			if(!isset($this->raw['members'][$user])) return false;
			
			return $this->raw['members'][$user]['punkte'];
		}
		
		function getUserJoiningTime($user)
		{
			if(!$this->status) return false;
			if(!isset($this->raw['members'][$user])) return false;
			
			return $this->raw['members'][$user]['time'];
		}
		
		function getUsersList($sortby=false, $invert=false)
		{
			if(!$this->status) return false;
			
			global $sortAllianceMembersBy,$sortAllianceMembersInvert;
			$sortAllianceMembersBy = $sortby;
			$sortAllianceMembersInvert = $invert;
			
			$members = array_keys($this->raw['members']);
			
			if($sortAllianceMembersBy)
				usort($members, 'sortAllianceMembersList');
			
			return $members;
		}
		
		function getUsersWithPermission($permission)
		{
			if(!$this->status) return false;
			
			$users = array();
			
			foreach($this->raw['members'] as $name=>$member)
			{
				if(isset($member['permissions'][$permission]) && $member['permissions'][$permission])
					$users[] = $name;
			}
			return $users;
		}
		
		function setUserStatus($user, $status)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['members'][$user])) return false;
			$this->raw['members'][$user]['rang'] = $status;
			$this->changed = true;
			return true;
		}
		
		function getUserStatus($user)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['members'][$user])) return false;
			return $this->raw['members'][$user]['rang'];
		}
		
		function addUser($user, $punkte=0)
		{
			if($this->status != 1) return false;
			
			if(isset($this->raw['members'][$user])) return false;
			
			$this->raw['members'][$user] = array (
				'punkte' => $punkte,
				'rang' => 'Neuling',
				'time' => time(),
				'permissions' => array(false, false, false, false, false, false, false, false, false)
			);
			$this->changed = true;
			
			$this->recalcHighscores();
			return true;
		}
		
		function removeUser($user)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['members'][$user])) return true;
			
			unset($this->raw['members'][$user]);
			$this->changed = true;
			
			if(count($this->raw['members']) <= 0)
			{
				$this->destroy();
				return true;
			}
			
			$this->recalcHighscores();
			return true;
		}
		
		function newApplication($user)
		{
			if($this->status != 1) return false;
			
			if(!isset($this->raw['bewerbungen'])) $this->raw['bewerbungen'] = array();
			if(in_array($user, $this->raw['bewerbungen'])) return false;
			
			$this->raw['bewerbungen'][] = $user;
			$this->changed = true;
			
			return true;
		}
		
		function deleteApplication($user)
		{
			if($this->status != 1) return false;
			if(!isset($this->raw['bewerbungen'])) return true;
			
			$key = array_search($user, $this->raw['bewerbungen']);
			if($key === false) return true;
			
			unset($this->raw['bewerbungen'][$key]);
			$this->raw['bewerbungen'] = array_values($this->raw['bewerbungen']);
			
			$this->changed = true;
			return true;
		}
		
		function getApplicationsList()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['bewerbungen'])) return array();
			return $this->raw['bewerbungen'];
		}
		
		function name($name=false)
		{
			if(!$this->status) return false;
			
			if(!trim($name))
			{
				if(!isset($this->raw['name'])) return '';
				return $this->raw['name'];
			}
			else
			{
				if($this->status != 1) return false;
				$this->raw['name'] = $name;
				$this->changed = true;
				return true;
			}
		}
		
		function kickUser($user, $by_whom=false)
		{
			if($this->status != 1) return false;
			if(!isset($this->raw['members'][$user])) return false;
			
			$user_obj = Classes::User($user);
			if(!$user_obj->allianceTag(false)) return false;
			
			$this->removeUser($user);
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->subject("Allianzmitgliedschaft gek\xc3\xbcndigt");
				$message->text("Sie wurden aus der Allianz ".$this->getName()." geworfen.");
				$message->addUser($user, 7);
			}
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->subject("Spieler aus Allianz geworfen");
				$message->text("Der Spieler ".$user." wurde aus Ihrer Allianz geworfen.");
				if($by_whom) $message->from($by_whom);
				
				$members = $this->getUsersWithPermission(5);
				foreach($members as $member)
				{
					if($member == $by_whom) continue;
					$message->addUser($member);
				}
			}
			return true;
		}
		
		function getExternalDescription($parsed=true)
		{
			if(!$this->status) return false;
			
			if($parsed)
			{
				if(!isset($this->raw['description_parsed']))
				{
					$this->raw['description_parsed'] = parse_html($this->getExternalDescription(false));
					$this->changed = true;
				}
				return $this->raw['description_parsed'];
			}
			else
			{
				if(!isset($this->raw['description'])) return '';
				return $this->raw['description'];
			}
		}
		
		function setExternalDescription($description)
		{
			if($this->status != 1) return false;
			
			$this->raw['description'] = $description;
			$this->raw['description_parsed'] = parse_html($description);
			$this->changed = true;
			return true;
		}
		
		function setInternalDescription($description)
		{
			if($this->status != 1) return false;
			
			$this->raw['inner_description'] = $description;
			$this->raw['inner_description_parsed'] = parse_html($description);
			$this->changed = true;
			return true;
		}
		
		function getInternalDescription($parsed=true)
		{
			if(!$this->status) return false;
			
			if($parsed)
			{
				if(!isset($this->raw['inner_description_parsed']))
				{
					$this->raw['inner_description_parsed'] = parse_html($this->getInternalDescription(false));
					$this->changed = true;
				}
				return $this->raw['inner_description_parsed'];
			}
			else
			{
				if(!isset($this->raw['inner_description'])) return '';
				return $this->raw['inner_description'];
			}
		}
		
		function acceptApplication($user, $by_whom=false)
		{
			if($this->status != 1) return false;
			
			$key = array_search($user, $this->raw['bewerbungen']);
			if($key === false) return false;
			
			$members = $this->getUsersList();
			
			$user_obj = Classes::User($user);
			if(!$user_obj->allianceTag($this->getName())) return false;
			unset($this->raw['bewerbungen'][$key]);
			$this->changed = true;
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->subject('Neues Allianzmitglied');
				$message->text('Ein neues Mitglied wurde in Ihre Allianz aufgenommen: '.$user);
				if($by_whom) $message->from($by_whom);
				foreach($members as $member)
				{
					if($member == $by_whom) continue;
					$message->addUser($member, 7);
				}
			}
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->subject('Allianzbewerbung angenommen');
				$message->text('Ihre Bewerbung bei der Allianz '.$this->getName().' wurde angenommen.');
				if($by_whom) $message->from($by_whom);
				$message->addUser($user, 7);
			}
			
			return true;
		}
		
		function rejectApplication($user, $by_whom=false)
		{
			if($this->status != 1) return false;
			
			if(!in_array($user, $this->raw['bewerbungen'])) return false;
			
			$user_obj = Classes::User($user);
			if(!$user_obj->cancelAllianceApplication(false)) return false;
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->subject('Allianzbewerbung abgelehnt');
				$message->text('Die Bewerbung von '.$user.' an Ihre Allianz wurde abgelehnt.');
				if($by_whom) $message->from($by_whom);
				$members = $this->getUsersWithPermission(4);
				foreach($members as $member)
				{
					if($member == $by_whom) continue;
					$message->addUser($member, 7);
				}
			}
			
			$message = Classes::Message();
			if($message->create())
			{
				$message->subject('Allianzbewerbung abgelehnt');
				$message->text('Ihre Bewerbung bei der Allianz '.$this->getName().' wurde abgelehnt.');
				$message->addUser($user, 7);
			}
			
			return true;
		}
		
		protected function getDataFromRaw(){}
		protected function getRawFromData(){}
	}
	
	function decodeAllianceHighscoresString($info)
	{
		$alliancename = trim(substr($info, 0, 6));
		
		$members_str = substr($info, 6, 4);
		$members_bin = '';
		for($i=0; $i < strlen($members_str); $i++)
			$members_bin .= add_nulls(decbin(ord($members_str{$i})), 8);
		$members = base_convert($members_bin, 2, 10);
		
		$average_str = substr($info, 10, 8);
		$average_bin = '';
		for($i=0; $i < strlen($average_str); $i++)
			$average_bin .= add_nulls(decbin(ord($average_str{$i})), 8);
		$average = base_convert($average_bin, 2, 10);
		
		$overall_str = substr($info, 18, 8);
		$overall_bin = '';
		for($i=0; $i < strlen($overall_str); $i++)
			$overall_bin .= add_nulls(decbin(ord($overall_str{$i})), 8);
		$overall = base_convert($overall_bin, 2, 10);
		
		return array($alliancename, $members, $average, $overall);
	}

	function encodeAllianceHighscoresString($alliancename, $members, $average, $overall)
	{
		$string = substr($alliancename, 0, 6);
		if(strlen($string) < 6)
			$string .= str_repeat(' ', 6-strlen($string));
		$members_bin = add_nulls(base_convert($members, 10, 2), 32);
		for($i = 0; $i < strlen($members_bin); $i+=8)
			$string .= chr(bindec(substr($members_bin, $i, 8)));
		$average_bin = add_nulls(base_convert($average, 10, 2), 64);
		for($i = 0; $i < strlen($average_bin); $i+=8)
			$string .= chr(bindec(substr($average_bin, $i, 8)));
		$overall_bin = add_nulls(base_convert($overall, 10, 2), 64);
		for($i = 0; $i < strlen($overall_bin); $i+=8)
			$string .= chr(bindec(substr($overall_bin, $i, 8)));
		
		return $string;
	}
	
	function getAlliancesCount()
	{
		$filesize = filesize(DB_HIGHSCORES_ALLIANCES);
		if($filesize === false)
			return false;
		$alliances = floor($filesize/26);
		return $alliances;
	}
	
	function sortAllianceMembersList($a, $b)
	{
		global $sortAllianceMembersInvert;
		global $sortAllianceMembersBy;
		if(isset($sortAllianceMembersInvert) && $sortAllianceMembersInvert) $invert = -1;
		else $invert = 1;
		if(isset($sortAllianceMembersBy) && ($sortAllianceMembersBy == 'punkte' || $sortAllianceMembersBy == 'time'))
		{
			if($a[$sortAllianceMembersBy] > $b[$sortAllianceMembersBy]) return $invert;
			elseif($a[$sortAllianceMembersBy] < $b[$sortAllianceMembersBy]) return -$invert;
			else return 0;
		}
		else
		{
			$cmp = strnatcasecmp($a[$sortAllianceMembersBy], $b[$sortAllianceMembersBy]);
			if($cmp < 0) return -$invert;
			elseif($cmp > 0) return $invert;
			else return 0;
		}
	}
	
	function findAlliance($search_string)
	{
		$preg = '/^'.str_replace(array('\\*', '\\?'), array('.*', '.?'), preg_quote($search_string, '/')).'$/i';
		$alliances = array();
		$dh = opendir(DB_ALLIANCES);
		while(($fname = readdir($dh)) !== false)
		{
			if(!is_file(DB_ALLIANCES.'/'.$fname) || !is_readable(DB_ALLIANCES.'/'.$fname))
				continue;
			$alliance = urldecode($fname);
			if(preg_match($preg, $alliance))
				$alliances[] = $alliance;
		}
		closedir($dh);
		natcasesort($alliances);
		return $alliances;
	}
?>