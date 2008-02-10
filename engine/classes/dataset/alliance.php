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

	/**
	  * Repraesentiert eine Allianz im Spiel.
	*/

	class Alliance extends Dataset
	{
		protected $datatype = 'alliance';

		function __construct($name=false, $write=true)
		{
			$this->save_dir = global_setting("DB_ALLIANCES");
			parent::__construct($name, $write);
		}

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

			$highscores = Classes::Highscores();
			$highscores->updateAlliance($this->name, 0, 0, 0);

			$this->write(true, false);
			$this->__construct($this->name, !$this->readonly);
			return true;
		}

		function destroy($by_whom=false)
		{
			if($this->status != 1) return false;

			if($this->getMembersCount() > 0)
			{
				$members = $this->getUsersList();
				foreach($members as $member)
				{
					$user = Classes::User($member);
					$message = Classes::Message();
					if($user->getStatus() && $message->create())
					{
						$message->subject($user->_("Allianz aufgelöst"));
						$message->text(sprintf($user->_("Die Allianz %s wurde aufgelöst."), $this->getName()));
						if($by_whom) $message->from($by_whom);
						$message->addUser($member, 7);
					}
				}

				$applicants = $this->getApplicationsList();
				if(count($applicants) > 0)
				{
					foreach($applicants as $applicant)
					{
						$user = Classes::User($applicant);
						$message = Classes::Message();
						if($user->getStatus() && $message->create())
						{
							$message->subject($user->_("Allianz aufgelöst"));
							$message->text(sprintf($user->_("Die Allianz %s wurde aufgelöst. Ihre Bewerbung wurde deshalb zurückgewiesen."), $this->getName()));
							$message->addUser($applicant, 7);
						}
					}
				}

				foreach($applicants as $applicant)
				{
					$user = Classes::User($applicant);
					$user_obj->cancelAllianceApplication(false);
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

			# Aus den Allianz-Highscores entfernen
			$highscores = Classes::Highscores();
			$highscores->removeEntry('alliances', $this->getName());

			$status = (unlink($this->filename) || chmod($this->filename, 0));
			if($status)
			{
				$this->status = 0;
				$this->changed = false;
				return true;
			}
			else return false;
		}

		/**
		  * Prueft, ob die Allianz mit dem Tag $alliance existiert.
		*/

		static function allianceExists($alliance)
		{
			$filename = global_setting("DB_ALLIANCES").'/'.strtolower(urlencode($alliance));
			return (is_file($filename) && is_readable($filename));
		}

		/**
		  * Gibt den Punkteschnitt der Mitglieder zurueck.
		  * @return false bei Fehlschlag
		*/

		function getAverageScores()
		{
			if(!$this->status) return false;

			return floor($this->getTotalScores()/$this->getMembersCount());
		}

		/**
		  * Gibt die Anzahl der Mitglieder zurueck.
		  * @return false bei Fehlschlag
		*/

		function getMembersCount()
		{
			if(!$this->status) return false;

			return count($this->raw['members']);
		}

		/**
		  * Gibt die Punktesumme der Mitglieder zurueck.
		  * @return false bei Fehlschlag
		*/

		function getTotalScores()
		{
			if(!$this->status) return false;

			$overall = 0;
			foreach($this->raw['members'] as $member)
				$overall += $member['punkte'];
			return $overall;
		}

		/**
		  * Verrechnet die Punktzahlen der Mitglieder neu und aktualisiert den Eintrag in den Allianzhighscores.
		  * @return (boolean) Erfolg
		*/

		function recalcHighscores()
		{
			if($this->status != 1) return false;

			$overall = 0;
			foreach($this->raw['members'] as $member)
				$overall += $member['punkte'];
			$members = count($this->raw['members']);
			$average = floor($overall/$members);
			$highscores = Classes::Highscores();
			$highscores->updateAlliance($this->getName(), $average, $overall, $members);

			return true;
		}

		/**
		  * Gibt die Platzierung in den Allianzhighscores hinsichtlich des durchschnittlichen Punktestands zurueck.
		  * @return false bei Fehlschlag
		*/

		function getRankAverage()
		{
			if(!$this->status) return false;

			$highscores = Classes::Highscores();
			return $highscores->getPosition('alliances', $this->getName(), 'scores_average');
		}

		/**
		  * Gibt die Platzierung in den Allianzhighscores hinsichtlich der Punktesumme der Mitglieder zurueck.
		  * @return false bei Fehlschlag
		*/

		function getRankTotal()
		{
			if(!$this->status) return false;

			$highscores = Classes::Highscores();
			return $highscores->getPosition('alliances', $this->getName(), 'scores_total');
		}

		/**
		  * Setzt die Erlaubnis fuer das Mitglied $user, die Aktion $key durchzufueren.
		  * Folgende Aktionen sind moeglich:
		  * 0: Rundschreiben verfassen
		  * 1: Koordinaten der Mitglieder einsehen
		  * 2: Internen Bereich bearbeiten
		  * 3: Externen Bereich bearbeiten
		  * 4: Bewerbungen annehmen/ablehnen
		  * 5: Mitglieder hinauswerfen
		  * 6: Raenge verteilen
		  * 7: Benutzerrechte verteilen
		  * 8: Bündnis aufloesen
		  * @return (boolean) Erfolg
		*/

		function setUserPermissions($user, $key, $permission)
		{
			if($this->status != 1) return false;

			if(!isset($this->raw['members'][$user])) return false;
			$this->raw['members'][$user]['permissions'][$key] = (bool) $permission;
			$this->changed = true;
			return true;
		}

		/**
		  * Setzt oder liest die Eigenschaft der Allianz, ob neue Bewerbungen erlaubt sind.
		  * @return (boolean) Den Erfolg des Setzens
		  * @return (boolean) Den aktuellen Status, wenn $allow nicht oder auf -1 gesetzt ist
		*/

		function allowApplications($allow=-1)
		{
			if(!$this->status) return false;

			if($allow === -1)
				return (!isset($this->raw['allow_applications']) || $this->raw['allow_applications']);

			$this->raw['allow_applications'] = ($allow == true);
			$this->changed = true;
			return true;
		}

		/**
		  * Ueberprueft, ob das Mitglied $user die Berechtigung $key besitzt. Siehe setUserPermissions() fuer die Liste der Berechtigungen.
		*/

		function checkUserPermissions($user, $key)
		{
			if(!$this->status) return false;

			if(!isset($this->raw['members'][$user])) return false;
			if(!isset($this->raw['members'][$user]['permissions'][$key])) return false;
			return $this->raw['members'][$user]['permissions'][$key];
		}

		/**
		  * Aktualisiert den gecachten Punktestand eines Mitglieds.
		  * @return (boolean) Erfolg
		*/

		function setUserScores($user, $scores)
		{
			if($this->status != 1) return false;

			if(!isset($this->raw['members'][$user])) return false;
			$this->raw['members'][$user]['punkte'] = $scores;
			$this->changed = true;

			$this->recalcHighscores();

			return true;
		}

		/**
		  * Liefert den gecachten Punktestand eines Mitglieds zurueck.
		  * @return false bei Fehlschlag
		*/

		function getUserScores($user)
		{
			if(!$this->status) return false;
			if(!isset($this->raw['members'][$user])) return false;

			return $this->raw['members'][$user]['punkte'];
		}

		/**
		  * Gibt die Beitrittszeit eines Mitglieds zurueck.
		  * @return false bei Fehlschlag
		*/

		function getUserJoiningTime($user)
		{
			if(!$this->status) return false;
			if(!isset($this->raw['members'][$user])) return false;

			return $this->raw['members'][$user]['time'];
		}

		/**
		  * Gibt die Mitgliederliste des Arrays zurueck.
		  * ( Benutzername => [ 'time' => Beitrittszeit; 'rang' => Benutzerrang; 'punkte' => Punkte-Cache; 'permissions' => ( Berechtigungsnummer, siehe setUserPermissions() => Berechtigung? ) ] )
		  * @return false bei Fehlschlag
		*/

		function getUsersList($sortby=false, $invert=false)
		{
			if(!$this->status) return false;

			if($sortby) $sortby = ''.$sortby;

			if($sortby && ('punkte'==$sortby || 'rang'==$sortby || 'time'==$sortby))
			{
				global $sortAllianceMembersBy;
				global $sortAllianceMembersInvert;
				$sortAllianceMembersBy = $sortby;
				$sortAllianceMembersInvert = $invert;

				$members_raw = $this->raw['members'];
				uasort($members_raw, array("Alliance", 'sortAllianceMembersList'));
				$members = array_keys($members_raw);
			}
			else
			{
				$members = array_keys($this->raw['members']);
				if($sortby)
				{
					natcasesort($members);
					if($invert) $members = array_reverse($members);
				}
			}

			return $members;
		}

		/**
		  * Gibt ein Array aller Mitglieder zurueck, die eine bestimmte Berechtigung haben. Fuer die Bedeutung der Berechtigungen siehe setUserPermission().
		  * @return false bei Fehlschlag
		*/

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

		/**
		  * Setzt den Rang eines Benutzers.
		  * @return (boolean) Erfolg
		*/

		function setUserStatus($user, $status)
		{
			if($this->status != 1) return false;

			if(!isset($this->raw['members'][$user])) return false;
			$this->raw['members'][$user]['rang'] = $status;
			$this->changed = true;
			return true;
		}

		/**
		  * Gibt den Rang eines Mitglieds zurueck.
		  * @return false bei Fehlschlag
		*/

		function getUserStatus($user)
		{
			if(!$this->status) return false;

			if(!isset($this->raw['members'][$user])) return false;
			return $this->raw['members'][$user]['rang'];
		}

		/**
		  * Nimmt einen Benutzer in die Allianz auf. Rang ist 'Neuling', keinerlei Rechte.
		  * Stellt die Allianz beim Benutzer <strong>nicht</strong> ein.
		  * @return (boolean) Erfolg
		*/

		function addUser($user, $punkte=0)
		{
			if($this->status != 1) return false;

			if(isset($this->raw['members'][$user])) return false;

			$user_obj = Classes::User($user);

			$this->raw['members'][$user] = array (
				'punkte' => $punkte,
				'rang' => $user_obj->_("Neuling"),
				'time' => time(),
				'permissions' => array(false, false, false, false, false, false, false, false, false)
			);
			$this->changed = true;

			$this->recalcHighscores();
			return true;
		}

		/**
		  * Entfernt einen Benutzer aus einer Allianz. Entfernt die Allianz <strong>nicht</strong> aus dem Benutzer-Array.
		  * Ist dies der letzte Benutzer, wird die Allianz aufgeloest.
		  * @return (boolean) Erfolg
		*/

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

		/**
		  * Fuegt eine neue Bewerbung des Benutzers $user hinzu. Veraendert das User-Array <strong>nicht</strong>.
		  * @return (boolean) Erfolg
		*/

		function newApplication($user)
		{
			if($this->status != 1) return false;
			if(!$this->allowApplications()) return false;

			if(!isset($this->raw['bewerbungen'])) $this->raw['bewerbungen'] = array();
			if(in_array($user, $this->raw['bewerbungen'])) return false;

			$this->raw['bewerbungen'][] = $user;
			$this->changed = true;

			return true;
		}

		/**
		  * Entfernt die Bewerbung des Benutzers $user wieder. Veraendert das User-Array <strong>nicht</strong>.
		  * @return (boolean) Erfolg
		*/

		function deleteApplication($user)
		{
			if($this->status != 1) return false;
			if(!isset($this->raw['bewerbungen'])) return true;

			$key = array_search($user, $this->raw['bewerbungen']);
			if($key === false) return true;

			unset($this->raw['bewerbungen'][$key]);

			$this->changed = true;
			return true;
		}

		/**
		  * Gibt die Liste der bewerbenden Benutzer zurueck.
		  * @return false bei Fehlschlag
		*/

		function getApplicationsList()
		{
			if(!$this->status) return false;

			if(!isset($this->raw['bewerbungen'])) return array();
			return $this->raw['bewerbungen'];
		}

		/**
		  * Setzt oder liest den Allianznamen.
		  * @return false bei Fehlschlag
		  * @return Allianzname, wenn $name nicht oder auf false gesetzt
		  * @return (boolean) Erfolg
		*/

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

		/**
		  * Wirft einen Benutzer aus der Allianz. Die Allianz wird aus dem Benutzerprofil entfernt und eine Benachrichtigung erfolgt.
		  * @return (boolean) Erfolg
		*/

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
				$message->subject($user_obj->_("Allianzmitgliedschaft gekündigt"));
				$message->text(sprintf($user_obj->_("Sie wurden aus der Allianz %s geworfen."), $this->getName()));
				$message->addUser($user, 7);
			}

			$members = $this->getUsersWithPermission(5);
			foreach($members as $member)
			{
				if($member == $by_whom) continue;

				$user_obj = Classes::User($member);
				$message = Classes::Message();
				if($user_obj->getStatus() && $message->create())
				{
					$message->subject($user_obj->_("Spieler aus Allianz geworfen"));
					$message->text(sprintf($user_obj->_("Der Spieler %s wurde aus Ihrer Allianz geworfen."), $user));
					if($by_whom) $message->from($by_whom);

					$message->addUser($member, 7);
				}
			}
			return true;
		}

		/**
		  * Liefert die externe Allianzbeschreibung zurueck.
		  * @param $parsed Bestimmt, ob der HTML-Code gefiltert sein soll (fuer die Ausgabe).
		  * @return false bei Fehlschlag
		*/

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

		/**
		  * Veraendert die externe Allianzbeschreibung.
		  * @return (boolean) Erfolg
		*/

		function setExternalDescription($description)
		{
			if($this->status != 1) return false;

			$this->raw['description'] = $description;
			$this->raw['description_parsed'] = parse_html($description);
			$this->changed = true;
			return true;
		}

		/**
		  * Setzt die interne Allianzbeschreibung.
		  * @return (boolean) Erfolg
		*/

		function setInternalDescription($description)
		{
			if($this->status != 1) return false;

			$this->raw['inner_description'] = $description;
			$this->raw['inner_description_parsed'] = parse_html($description);
			$this->changed = true;
			return true;
		}

		/**
		  * Setzt die interne Allianzbeschreibung.
		  * @param $parsed Bestimmt, ob der HTML-Code gefiltert sein soll (fuer die Ausgabe)
		  * @return false bei Fehlschlag
		*/

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

		/**
		  * Nimmt eine Bewerbung an und fuegt den Benutzer zur Allianz hinzu. Die Allianz wird ins Benutzerprofil eingetragen und eine Benachrichtigung erfolgt.
		  * @return (boolean) Erfolg
		*/

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
				$message->subject($user_obj->_('Allianzbewerbung angenommen'));
				$message->text(sprintf($user_obj->_('Ihre Bewerbung bei der Allianz %s wurde angenommen.'), $this->getName()));
				if($by_whom) $message->from($by_whom);
				$message->addUser($user, 7);
			}

			foreach($members as $member)
			{
				if($member == $by_whom) continue;

				$user_obj = Classes::User($user);
				$message = Classes::Message();
				if($user_obj->getStatus() && $message->create())
				{
					$message->subject($user_obj->_('Neues Allianzmitglied'));
					$message->text(sprintf($user_obj->_("Ein neues Mitglied wurde in Ihre Allianz aufgenommen: %s"), $user));
					if($by_whom) $message->from($by_whom);
					$message->addUser($member, 7);
				}
			}

			return true;
		}

		/**
		  * Weist eine Bewerbung zurueck. Das Benutzerprofil wird aktualisiert, eine Benachrichtigung erfolgt.
		  * @return (boolean) Erfolg
		*/

		function rejectApplication($user, $by_whom=false)
		{
			if($this->status != 1) return false;

			if(!in_array($user, $this->raw['bewerbungen'])) return false;

			$user_obj = Classes::User($user);
			if(!$user_obj->cancelAllianceApplication(false)) return false;

			$message = Classes::Message();
			if($message->create())
			{
				$message->subject($user_obj->_('Allianzbewerbung abgelehnt'));
				$message->text(sprintf($user_obj->_('Ihre Bewerbung bei der Allianz %s wurde abgelehnt.'), $this->getName()));
				$message->addUser($user, 7);
			}

			$members = $this->getUsersWithPermission(4);
			foreach($members as $member)
			{
				if($member == $by_whom) continue;
				$user_obj = Classes::User($member);
				$message = Classes::Message();
				if($user_obj->getStatus() && $message->create())
				{
					$message->subject($user_obj->_('Allianzbewerbung abgelehnt'));
					$message->text(sprintf($user_obj->_('Die Bewerbung von %s an Ihre Allianz wurde abgelehnt.'), $user));
					if($by_whom) $message->from($by_whom);
					$message->addUser($member, 7);
				}
			}

			return true;
		}

		protected function getDataFromRaw()
		{
			$this->name = $this->raw['tag'];
		}
		protected function getRawFromData(){}

		static function resolveName($name)
		{
			$instance = Classes::Alliance($name);
			return $instance->getName();
		}

		/**
		  * Aktualisiert die Mitgliederliste, wenn ein Benutzer umbenannt wird.
		  * @return (boolean) Erfolg
		*/

		function renameUser($old_name, $new_name)
		{
			if(!$this->status) return false;
			if($old_name == $new_name) return 2;
			if(!isset($this->raw['members'][$old_name])) return true;

			$this->raw['members'][$new_name] = $this->raw['members'][$old_name];
			unset($this->raw['members'][$old_name]);
			$this->changed = true;
			return true;
		}

		/**
		  * Gibt zurueck, ob eine Umbenennung des Allianztags moeglich ist. Es muss mindestens die globale Einstellung ALLIANCE_RENAME_PERIOD in Tagen vergangen sein, damit eine erneute Umbenennung moeglich ist.
		*/

		function renameAllowed()
		{
			if(!$this->status) return false;

			if(!isset($this->raw['last_rename'])) return true;
			return (time()-$this->raw['last_rename'] >= global_setting("ALLIANCE_RENAME_PERIOD")*86400);
		}

		/**
		  * Benennt die Allianz um. Aktualisiert die Highscores und Profile der Mitglieder.
		*/

		function rename($new_name)
		{
			if(!$this->status) return false;

			$new_name = trim($new_name);

			$really_rename = (strtolower($new_name) != strtolower($this->getName()));

			if($really_rename)
			{
				$new_fname = $this->save_dir.'/'.urlencode(strtolower($new_name));
				if(file_exists($new_fname)) return false;
			}

			# Alliancetag bei den Mitgliedern aendern
			foreach($this->raw['members'] as $username=>$info)
			{
				$user = Classes::User($username);
				$user->allianceTag($new_name, false);
			}

			# Highscores-Eintrag aendern
			$hs = Classes::Highscores();
			$hs->renameAlliance($this->getName(), $new_name);

			$this->raw['tag'] = $new_name;
			if($really_rename) $this->raw['last_rename'] = time();
			$this->changed = true;

			if($really_rename)
			{
				# Datei umbenennen
				$this->__destruct();
				rename($this->filename, $new_fname);
				$this->__construct($new_name, !$this->readonly);
			}
			else $this->name = $new_name;

			return true;
		}

		/**
		* Liefert die Zahl der existierenden Allianzen zurueck. Diese wird aus den Highscores ermittelt.
		*/

		static function getAlliancesCount()
		{
			$highscores = Classes::Highscores();
			return $highscores->getCount('alliances');
		}

		/**
		* uasort-Callbackfunktion zum Sortieren von Allianzmitgliedern.
		* Die globale Variable $sortAllianceMembersBy bestimmt, ob nach Beitrittszeit ('time') oder Punkten ('punkte') sortiert werden soll.
		* Ist die globale Variable $sortAllianceMembersInvert gesetzt, wird die Sortierung umgekehrt.
		*/

		static function sortAllianceMembersList($a, $b)
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

		/**
		* Sucht eine Allianz mit dem Tag $search_string. '*' und '?' sind als Wildcards moeglich.
		* @return (array) die gefundenen Allianztags
		*/

		static function findAlliance($search_string)
		{
			$preg = '/^'.str_replace(array('\\*', '\\?'), array('.*', '.?'), preg_quote($search_string, '/')).'$/i';
			$alliances = array();
			$dh = opendir(global_setting("DB_ALLIANCES"));
			while(($fname = readdir($dh)) !== false)
			{
				if(!is_file(global_setting("DB_ALLIANCES").'/'.$fname) || !is_readable(global_setting("DB_ALLIANCES").'/'.$fname))
					continue;
				$alliance = urldecode($fname);
				if(preg_match($preg, $alliance))
					$alliances[] = $alliance;
			}
			closedir($dh);
			natcasesort($alliances);
			return $alliances;
		}

	}
