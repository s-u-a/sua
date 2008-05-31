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

	/**
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage storage
	*/

	namespace sua;
	require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	  * Repraesentiert eine Allianz im Spiel.
	*/

	class Alliance extends SQLiteSet
	{
		/** Rundmail schreiben */
		const PERMISSION_MAIL = 0;
		/** Koordinaten der Mitglieder sehen */
		const PERMISSION_COORDS = 1;
		/** Internen Bereich bearbeiten */
		const PERMISSION_INTERNAL = 2;
		/** Externen Bereich bearbeiten */
		const PERMISSION_EXTERNAL = 3;
		/** Bewerbungen annehmen oder ablehnen */
		const PERMISSION_APPLICATIONS = 4;
		/** Spieler aus der Allianz werfen */
		const PERMISSION_KICK = 5;
		/** Ränge verteilen */
		const PERMISSION_RANK = 6;
		/** Benutzerrechte verteilen */
		const PERMISSION_PERMISSIONS = 7;
		/** Allianz auflösen */
		const PERMISSION_REMOVE = 8;

		/** Nach Punktzahl sortieren */
		const SORTBY_PUNKTE = 1;
		/** Nach Rang sortieren */
		const SORTBY_RANG = 2;
		/** Nach Beitrittszeit sortieren */
		const SORTBY_ZEIT = 3;

		protected static $tables = array (
			"alliances" => array (
				"tag TEXT PRIMARY KEY",
				"name TEXT",
				"description TEXT",
				"description_parsed TEXT",
				"inner_description TEXT",
				"inner_description_parsed TEXT",
				"last_rename INTEGER",
				"allow_applications INTEGER"),
			"alliances_members" => array (
				"tag TEXT",
				"member TEXT UNIQUE",
				"score REAL",
				"rank TEXT",
				"time INTEGER",
				"permissions INTEGER"
			),
			"alliances_applications" => array (
				"tag TEXT",
				"user TEXT UNIQUE"
			)
		);

		static function create($name=null)
		{
			$name = self::datasetName($name);
			self::$sqlite->query("INSERT INTO alliances ( tag ) VALUES ( ".self::$sqlite->quote($name)." );");

			$highscores = Classes::Highscores();
			$highscores->updateAlliance($this->name, 0, 0, 0);

			return $name;
		}

		/**
		 * Entfernt die Allianz aus der Datenbank, aus den Highscores und setzt die Allianztags der Mitglieder auf nichts.
		 * Sendet Nachrichten an die Mitglieder, die über die Auflösung informieren.
		 * @param string $by_whom Der Benutzername des Benutzers, der die Auflösung verursacht hat, für die Nachrichten.
		 * @return void
		*/

		function destroy($by_whom=null)
		{
			if($this->getMembersCount() > 0)
			{
				$members = $this->getUsersList();
				foreach($members as $member)
				{
					$user = Classes::User($member);
					$message = Classes::Message(Message::create());
					if($user->getStatus())
					{
						$message->subject($user->_("Allianz aufgelöst"));
						$message->text(sprintf($user->_("Die Allianz %s wurde aufgelöst."), $this->getName()));
						if($by_whom) $message->from($by_whom);
						$message->addUser($member, Message::$MESSAGE_VERBUENDETE);
					}
					$this_user->allianceTag(false);
				}

				$applicants = $this->getApplicationsList();
				if(count($applicants) > 0)
				{
					foreach($applicants as $applicant)
					{
						$user = Classes::User($applicant);
						$message = Classes::Message(Message::create());
						if($user->getStatus())
						{
							$message->subject($user->_("Allianz aufgelöst"));
							$message->text(sprintf($user->_("Die Allianz %s wurde aufgelöst. Ihre Bewerbung wurde deshalb zurückgewiesen."), $this->getName()));
							$message->addUser($applicant, Message::$MESSAGE_VERBUENDETE);
						}
						$user_obj->cancelAllianceApplication(false);
					}
				}
			}

			# Aus den Allianz-Highscores entfernen
			$highscores = Classes::Highscores();
			$highscores->removeEntry("alliances", $this->getName());
		}

		/**
		  * Gibt den Punkteschnitt der Mitglieder zurueck.
		  * @return int
		*/

		function getAverageScores()
		{
			return floor($this->getTotalScores()/$this->getMembersCount());
		}

		/**
		  * Gibt die Anzahl der Mitglieder zurueck.
		  * @return int
		*/

		function getMembersCount()
		{
			return self::$sqlite->singleQuery("SELECT COUNT(*) FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		  * Gibt die Punktesumme der Mitglieder zurueck.
		  * @return int
		*/

		function getTotalScores()
		{
			return self::$sqlite->singleQuery("SELECT SUM(score) FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		  * Verrechnet die Punktzahlen der Mitglieder neu und aktualisiert den Eintrag in den Allianzhighscores.
		  * @return void
		*/

		function recalcHighscores()
		{
			$highscores = Classes::Highscores();
			$highscores->updateAlliance($this->getName(), $this->getAverageScores(), $this->getTotalScores(), $this->getMembersCount());
		}

		/**
		  * Gibt die Platzierung in den Allianzhighscores hinsichtlich des durchschnittlichen Punktestands zurueck.
		  * @return int
		*/

		function getRankAverage()
		{
			$highscores = Classes::Highscores();
			return $highscores->getPosition("alliances", $this->getName(), "scores_average");
		}

		/**
		  * Gibt die Platzierung in den Allianzhighscores hinsichtlich der Punktesumme der Mitglieder zurueck.
		  * @return int
		*/

		function getRankTotal()
		{
			$highscores = Classes::Highscores();
			return $highscores->getPosition("alliances", $this->getName(), "scores_total");
		}

		/**
		  * Setzt die Erlaubnis (Alliance::PERMISSION_*) fuer das Mitglied $user, die Aktion $key durchzufueren.
		  * @param string $user Benutzername
		  * @param int $key Alliance::PERMISSION_*
		  * @param bool $permission Soll Berechtigung erteilt werden?
		  * @return void
		*/

		function setUserPermissions($user, $key, $permission)
		{
			$permissions = self::$sqlite->singleQuery("SELECT permissions FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
			if($permission)
				$permissions |= 1 << $key;
			elseif($permission & (1 << $key))
				$permissions ^= 1 << $key;
			self::$sqlite->query("UPDATE alliances_members SET permissions = ".self::$sqlite->quote($permissions)." WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Setzt oder liest die Eigenschaft der Allianz, ob neue Bewerbungen erlaubt sind.
		  * @param bool $allow Null, wenn die Eigenschaft ausgelesen werden soll
		  * @return void Wenn $allow gesetzt ist
		*/

		function allowApplications($allow=null)
		{
			if(!isset($allow))
				return (true && $this->getMainField("allow_applications"));
			else
				$this->setMainField("allow_applications", $allow ? 1 : 0);
		}

		/**
		  * Ueberprueft, ob das Mitglied $user die Berechtigung $key (Alliance::PERMISSION_*) besitzt.
		  * @param string $user
		  * @param int $key
		  * @return bool
		*/

		function checkUserPermissions($user, $key)
		{
			$permissions = self::$sqlite->singleQuery("SELECT permissions FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
			return (true && ($permissions & (1 << $key)));
		}

		/**
		  * Aktualisiert den gecachten Punktestand eines Mitglieds.
		  * @param string $user
		  * @param int $scores
		  * @return void
		*/

		function setUserScores($user, $scores)
		{
			self::$sqlite->query("UPDATE alliances_members SET score = ".self::$sqlite->quote($scores)." WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
			$this->recalcHighscores();
		}

		/**
		  * Liefert den gecachten Punktestand eines Mitglieds zurueck.
		  * @param string $User
		  * @return int
		*/

		function getUserScores($user)
		{
			return self::$sqlite->singleQuery("SELECT score FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Gibt die Beitrittszeit eines Mitglieds zurueck.
		  * @param string $user
		  * @return int
		*/

		function getUserJoiningTime($user)
		{
			return self::$sqlite->singleQuery("SELECT time FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Gibt die Mitgliederliste des Arrays zurueck.
		  * ( Benutzername => [ 'time' => Beitrittszeit; 'rang' => Benutzerrang; 'punkte' => Punkte-Cache; 'permissions' => ( Berechtigungsnummer, siehe setUserPermissions() => Berechtigung? ) ] )
		  * @param string $sortby Sortierfeld (Alliance::SORTBY_*)
		  * @param bool $invert Sortierung umkehren?
		  * @return array
		*/

		function getUsersList($sortby=null, $invert=false)
		{
			switch($sortby)
			{
				case Alliance::SORTBY_PUNKTE: $order = "score"; break;
				case Alliance::SORTBY_RANG: $order = "rank"; break;
				case Alliance::SORTBY_ZEIT: $order = "time"; break;
				default: $oder = "member"; break;
			}
			$oder .= " ".($invert ? "DESC" : "ASC");
			return self::$sqlite->columnQuery("SELECT member FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." ORDER BY ".$order.";")
		}

		/**
		  * Gibt ein Array aller Mitglieder zurueck, die eine bestimmte Berechtigung haben. Fuer die Bedeutung der Berechtigungen siehe setUserPermission().
		  * @param int $permission Alliance::PERMISSION_*
		  * @return array(string)
		*/

		function getUsersWithPermission($permission)
		{
			return self::$sqlite->columnQuery("SELECT member FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND ( permissions | ".(1 << $permission)." ) == 1;");
		}

		/**
		  * Setzt den Rang eines Benutzers.
		  * @param string $user
		  * @param string $rank
		  * @return void
		*/

		function setUserStatus($user, $rank)
		{
			self::$sqlite->query("UPDATE alliances_members SET rank = ".self::$sqlite->quote($rank)." WHERE tag = ".self::$sqlite->quote($tag)." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Gibt den Rang eines Mitglieds zurueck.
		  * @param string $user
		  * @return bool
		*/

		function getUserStatus($user)
		{
			return self::$sqlite->singleQuery("SELECT rank FROM alliances_members WHERE tag = ".self::$sqlite->quote($tag)." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Nimmt einen Benutzer in die Allianz auf. Rang ist 'Neuling', keinerlei Rechte.
		  * Stellt die Allianz beim Benutzer <strong>nicht</strong> ein.
		  * @param string $user
		  * @param int $punkte
		  * @return void
		*/

		function addUser($user, $punkte=0)
		{
			$user_obj = Classes::User($user);
			self::$sqlite->query("INSERT INTO alliances_members ( tag, member, punkte, rank, time, permissions ) VALUES ( ".self::$sqlite->quote($tag).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($user_obj->_("Neuling")).", ".self::$sqlite->quote(time()).", 0 );");
			$this->recalcHighscores();
		}

		/**
		  * Entfernt einen Benutzer aus einer Allianz. Entfernt die Allianz <strong>nicht</strong> aus dem Benutzer-Array.
		  * Ist dies der letzte Benutzer, wird die Allianz aufgeloest.
		  * @param String $user
		  * @return void
		*/

		function removeUser($user)
		{
			self::$sqlite->query("DELETE FROM alliances_members WHERE tag = ".self::$sqlite->quote($tag)." AND member = ".self::$sqlite->quote($user).";");
			if($this->singleQuery("SELECT COUNT(*) FROM alliances_members WHERE tag = ".self::$sqlite->quote($tag).";") < 1)
				$this->destroy();
			else
				$this->recalcHighscores();
		}

		/**
		  * Fuegt eine neue Bewerbung des Benutzers $user hinzu. Veraendert das User-Array <strong>nicht</strong>.
		  * @param string $user
		  * @return void
		*/

		function newApplication($user)
		{
			if(!$this->allowApplications())
				throw new AllianceException("This alliance does not accept applications.");

			self::$sqlite->query("INSERT INTO alliances_applications ( tag, user ) VALUES ( ".self::$sqlite->quote($this->getName).", ".self::$sqlite->quote($user)." );");
		}

		/**
		  * Entfernt die Bewerbung des Benutzers $user wieder. Veraendert das User-Array <strong>nicht</strong>.
		  * @param string $user
		  * @return void
		*/

		function deleteApplication($user)
		{
			self::$sqlite->query("DELETE FROM alliances_applications WHERE tag = ".self::$sqlite->quote($tag)." AND user = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Gibt die Liste der bewerbenden Benutzer zurueck.
		  * @return array(string)
		*/

		function getApplicationsList()
		{
			return self::$sqlite->columnQuery("SELECT user FROM alliances_applications WHERE tag = ".self::$sqlite->quote($tag).";");
		}

		/**
		  * Setzt oder liest den Allianznamen.
		  * @param null $name Der Name oder null, wenn er zurückgeliefert werden soll.
		  * @return string|void Liefert den aktuellen Namen zurück, wenn $name null ist.
		*/

		function name($name=null)
		{
			if(!isset($name))
				return $this->getMainField("name");
			else
				$this->setMainField("name", $name);
		}

		/**
		  * Wirft einen Benutzer aus der Allianz. Die Allianz wird aus dem Benutzerprofil entfernt und eine Benachrichtigung erfolgt.
		  * @param string $user
		  * @param string|null $by_whom Der Benutzer, der den Kick ausführt. Als Absender der Benachrichtigung.
		  * @return (boolean) Erfolg
		*/

		function kickUser($user, $by_whom=null)
		{
			$user_obj = Classes::User($user);
			$user_obj->allianceTag(false);

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
		}

		/**
		  * Liefert die externe Allianzbeschreibung zurueck.
		  * @param bool $parsed Bestimmt, $parsed ob der HTML-Code gefiltert sein soll (fuer die Ausgabe).
		  * @return string
		*/

		function getExternalDescription($parsed=true)
		{
			return $this->getMainField($parsed ? "description_parsed" : "description");
		}

		/**
		  * Veraendert die externe Allianzbeschreibung.
		  * @param string $description
		  * @return void
		*/

		function setExternalDescription($description)
		{
			$this->setMainField("description", $description);
			$this->setMainField("description_parsed", F::parse_html($description));
		}

		/**
		  * Setzt die interne Allianzbeschreibung.
		  * @param string $description
		  * @return void
		*/

		function setInternalDescription($description)
		{
			$this->setMainField("inner_description", $description);
			$this->setMainField("inner_description_parsed", F::parse_html($description));
		}

		/**
		  * Gibt die interne Allianzbeschreibung zurück.
		  * @param bool $parsed Bestimmt, $parsed ob der HTML-Code gefiltert sein soll (fuer die Ausgabe)
		  * @return string
		*/

		function getInternalDescription($parsed=true)
		{
			return $this->getMainField($parsed ? "inner_description_parsed" : "inner_description");
		}

		/**
		  * Nimmt eine Bewerbung an und fuegt den Benutzer zur Allianz hinzu. Die Allianz wird ins Benutzerprofil eingetragen und eine Benachrichtigung erfolgt.
		  * @param string $user
		  * @param string|null $by_whom Der Benutzername des annehmenden Benutzers als Absender für die Benachrichtigungen
		  * @return void
		*/

		function acceptApplication($user, $by_whom=null)
		{
			$user_obj = Classes::User($user);
			$user_obj->allianceTag($this->getName()); // Fügt den Benutzer zur Allianz hinzu und löscht die Bewerbung

			$message = Classes::Message(Message::create());
			$message->subject($user_obj->_("Allianzbewerbung angenommen"));
			$message->text(sprintf($user_obj->_("Ihre Bewerbung bei der Allianz %s wurde angenommen."), $this->getName()));
			if(isset($by_whom)) $message->from($by_whom);
			$message->addUser($user, 7);

			foreach($this->getUsersList() as $member)
			{
				if($member == $by_whom) continue;

				$user_obj = Classes::User($user);
				$message = Classes::Message(Message::create());
				if($user_obj->getStatus())
				{
					$message->subject($user_obj->_("Neues Allianzmitglied"));
					$message->text(sprintf($user_obj->_("Ein neues Mitglied wurde in Ihre Allianz aufgenommen: %s"), $user));
					if($by_whom) $message->from($by_whom);
					$message->addUser($member, 7);
				}
			}
		}

		/**
		  * Weist eine Bewerbung zurueck. Das Benutzerprofil wird aktualisiert, eine Benachrichtigung erfolgt.
		  * @param string $user
		  * @param string|null $by_whom Der Name des ablehnenden Benutzers als Absender für die Benachrichtigungen.
		  * @return void
		*/

		function rejectApplication($user, $by_whom=null)
		{
			$user_obj = Classes::User($user);
			$user_obj->cancelAllianceApplication(false); // Löscht auch die Bewerbung aus der Allianz

			$message = Classes::Message();
			if($message->create())
			{
				$message->subject($user_obj->_("Allianzbewerbung abgelehnt"));
				$message->text(sprintf($user_obj->_("Ihre Bewerbung bei der Allianz %s wurde abgelehnt."), $this->getName()));
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
					$message->subject($user_obj->_("Allianzbewerbung abgelehnt"));
					$message->text(sprintf($user_obj->_("Die Bewerbung von %s an Ihre Allianz wurde abgelehnt."), $user));
					if($by_whom) $message->from($by_whom);
					$message->addUser($member, 7);
				}
			}
		}

		/**
		  * Aktualisiert die Mitgliederliste, wenn ein Benutzer umbenannt wird.
		  * @param string $old_name
		  * @param string $new_name
		  * @return void
		*/

		function renameUser($old_name, $new_name)
		{
			self::$sqlite->query("UPDATE alliances_members SET member = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($tag)." AND member = ".self::$sqlite->quote($user).";");
			self::$sqlite->query("UPDATE alliances_applications SET member = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($tag)." AND user = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Gibt zurueck, ob eine Umbenennung des Allianztags moeglich ist. Es muss mindestens die globale Einstellung ALLIANCE_RENAME_PERIOD in Tagen vergangen sein, damit eine erneute Umbenennung moeglich ist.
		  * @return bool
		*/

		function renameAllowed()
		{
			$last_rename = $this->getMainField("last_rename");
			if(!$last_rename) return true;
			return (time()-$last_rename >= global_setting("ALLIANCE_RENAME_PERIOD")*86400);
		}

		/**
		  * Benennt die Allianz um. Aktualisiert die Highscores und Profile der Mitglieder.
		  * @param string $new_name
		  * @return void
		*/

		function rename($new_name)
		{
			$new_name = trim($new_name);
			$really_rename = (self::datasetName($new_name) != $this->getName());

			if($really_rename && self::exists($new_name))
				throw new DatasetException("New name already exists.");

			# Alliancetag bei den Mitgliedern aendern
			foreach($this->getUsersList() as $username)
			{
				$user = Classes::User($username);
				$user->allianceTag($new_name, false);
			}

			# Highscores-Eintrag aendern
			$hs = Classes::Highscores();
			$hs->renameAlliance($this->getName(), $new_name);
			if($really_rename)
				$this->setMainField("last_rename", time());

			self::$sqlite->query("UPDATE alliances SET tag = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($this->getName());
			self::$sqlite->query("UPDATE alliances_members SET tag = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($this->getName());
			self::$sqlite->query("UPDATE alliances_applications SET tag = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($this->getName());
			$this->name = $new_name;
		}

		/**
		* Sucht eine Allianz mit dem Tag $search_string. '*' und '?' sind als Wildcards moeglich.
		* @param string $search_string
		* @return array die gefundenen Allianztags
		*/

		static function findAlliance($search_string)
		{
			return self::$sqlite->columnQuery("SELECT tag FROM alliances WHERE tag GLOB ".$this->quote($search_string).";");
		}
	}
