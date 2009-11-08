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
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	  * Repraesentiert eine Allianz im Spiel.
	*/

	class Alliance extends SQLSet implements \Iterator
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

		/** Gesamtpunktzahl-Highscores */
		const HIGHSCORES_SUM = "sum";

		/** Punkteschnitt-Highscores */
		const HIGHSCORES_AVERAGE = "average";

		/**
		 * Für die Iterator-Implementierung.
		*/
		private $user_list;

		protected static $primary_key = array("t_alliances", "c_tag");

		function __construct()
		{
			call_user_func_array(array("parent", "__construct"), func_get_args());

			$this->user_list = $this->getUsersList();
		}

		static function create($name=null)
		{
			$name = self::datasetName($name);
			self::$sql->query("INSERT INTO t_alliances ( c_tag ) VALUES ( ".self::$sql->quote($name)." );");

			return $name;
		}

		/**
		 * Entfernt die Allianz aus der Datenbank.
		 * Sendet Nachrichten an die Mitglieder, die über die Auflösung informieren.
		 * @param string $by_whom Der Benutzername des Benutzers, der die Auflösung verursacht hat, für die Nachrichten.
		 * @return void
		 * @todo Hier stimmt irgendwas nicht, wo wird die Allianz aus dem Benutzerprofil und aus der Datenbank gelöscht?
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
					$message->subject($user->_("Allianz aufgelöst"));
					$message->text(sprintf($user->_("Die Allianz %s wurde aufgelöst."), $this->getName()));
					if($by_whom) $message->from($by_whom);
					$message->addUser($member, Message::TYPE_VERBUENDETE);
				}

				$applicants = $this->getApplicationsList();
				if(count($applicants) > 0)
				{
					foreach($applicants as $applicant)
					{
						$user = Classes::User($applicant);
						$message = Classes::Message(Message::create());
						$message->subject($user->_("Allianz aufgelöst"));
						$message->text(sprintf($user->_("Die Allianz %s wurde aufgelöst. Ihre Bewerbung wurde deshalb zurückgewiesen."), $this->getName()));
						$message->addUser($applicant, Message::TYPE_VERBUENDETE);
						$user_obj->cancelAllianceApplication(false);
					}
				}
			}
		}

		/**
		 * Gibt die Punkte der Allianz zurück.
		 * @param string $i User::SCORES_* oder nichts für die Gesamtpunktzahl
		 * @param string $highscores Alliance::HIGHSCORES_*
		 * @return float
		*/

		function getScores($i=null, $highscores=Alliance::HIGHSCORES_SUM)
		{
			return self::$sql->singleField("SELECT ".$i." FROM t_alliance_highscores_".$highscores." WHERE c_tag = ".self::$sql->quote($this->getName())." LIMIT 1;");
		}

		/**
		 * Gibt die Platzierung dieser Allianz in den Highscores zurück.
		 * @param string $i User::SCORES_*, wenn die Platzierung bei einer bestimmten Punktart gemeint ist
		 * @param string $highscores Alliance::HIGHSCORES_*
		 * @return int
		*/

		function getRankTotal($i=null, $highscores=Alliance::HIGHSCORES_SUM)
		{
			if(!isset($i))
				$i = User::SCORES_TOTAL;

			return self::singleField("SELECT COUNT(*)+1 FROM t_alliance_highscores_".$highscores." WHERE ".$i." > ".$me->getScores($i).";");
		}

		/**
		 * Gibt die Allianz-Highscores-Liste als Array zurück. Es gilt: Platzierung = Index + $from. Standard-
		 * wert für $from ist 1. Die Werte des Arrays sind wiederrum assoziative Arrays nach dem Format.
		 * Der Wert mit dem Index "tag" gibt das Allianztag an, "members" die Mitgliederzahl. Die Werte mit
		 * den Indexen User::SCORES_* geben die Punktzahl an.
		 * @param int|null $from Die kleinste Platzierung, die zurückgegeben werden soll
		 * @param int|null $to Die größte Platzierung, die zurückgegeben werden soll
		 * @param string|null $sort Nach welcher Punktzahl soll sortiert werden? Standard: User::SCORES_TOTAL
		 * @param array|string|null $fields Welche Punktzahlen/Punktzahl soll zurückgegeben werden? Standard: alle
		 * @param string $highscores Alliance::HIGHSCORES_
		 * @return array
		*/

		static function getHighscores($from=null, $to=null, $sort=null, $fields=null, $highscores=Alliance::HIGHSCORES_SUM)
		{
			if(isset($from) && !is_int($from))
				throw new InvalidArgumentsException("\$from has to be an integer.");
			if(isset($to) && !is_int($to))
				throw new InvalidArgumentsException("\$to has to be an integer.");
			if(isset($sort) && !in_array($sort, $valid_fields))
				throw new InvalidArgumentsException("Invalid sort field.");

			$query = "SELECT ";
			if(isset($fields))
			{
				if(is_array($fields))
					$query .= implode(",", array_merge(array("c_tag", "c_members"), $fields));
				else
					$query .= "c_tag,c_members,".$fields;
			}
			else
				$query .= "*";
			$query .= " FROM t_alliances_highscores_".$highscores." ORDER BY ";
			if(isset($sort))
				$query .= $sort;
			else
				$query .= "c_total";
			$query .= " ASC";

			if(isset($from))
			{
				if(isset($to))
					$query .= " LIMIT ".($to-$from+1)." OFFSET ".$from;
				else
					$query .= " OFFSET ".$from;
			}
			elseif(isset($to))
				$query .= " LIMIT ".$to;

			return self::$sql->arrayQuery($query);
		}

		/**
		  * Setzt oder liest den Allianznamen.
		  * @param null $name Der Name oder null, wenn er zurückgeliefert werden soll.
		  * @return string|void Liefert den aktuellen Namen zurück, wenn $name null ist.
		*/

		function name($name=null)
		{
			if(!isset($name))
				return $this->getMainField("c_name");
			else
				$this->setMainField("c_name", $name);
		}

		/**
		  * Liefert die externe Allianzbeschreibung zurueck.
		  * @param bool $parsed Bestimmt, $parsed ob der HTML-Code gefiltert sein soll (fuer die Ausgabe).
		  * @return string
		*/

		function getExternalDescription($parsed=true)
		{
			return $this->getMainField($parsed ? "c_description_parsed" : "c_description");
		}

		/**
		  * Veraendert die externe Allianzbeschreibung.
		  * @param string $description
		  * @return void
		*/

		function setExternalDescription($description)
		{
			$this->setMainField("c_description", $description);
			$this->setMainField("c_description_parsed", FormattedString::parseHTML($description));
		}

		/**
		  * Setzt die interne Allianzbeschreibung.
		  * @param string $description
		  * @return void
		*/

		function setInternalDescription($description)
		{
			$this->setMainField("c_inner_description", $description);
			$this->setMainField("c_inner_description_parsed", FormattedString::parseHTML($description));
		}

		/**
		  * Gibt die interne Allianzbeschreibung zurück.
		  * @param bool $parsed Bestimmt, $parsed ob der HTML-Code gefiltert sein soll (fuer die Ausgabe)
		  * @return string
		*/

		function getInternalDescription($parsed=true)
		{
			return $this->getMainField($parsed ? "c_inner_description_parsed" : "c_inner_description");
		}

		/**
		  * Setzt oder liest die Eigenschaft der Allianz, ob neue Bewerbungen erlaubt sind.
		  * @param bool $allow Null, wenn die Eigenschaft ausgelesen werden soll
		  * @return void Wenn $allow gesetzt ist
		*/

		function allowApplications($allow=null)
		{
			if(!isset($allow))
				return (true && $this->getMainField("c_allow_applications"));
			else
				$this->setMainField("c_allow_applications", $allow ? 1 : 0);
		}

		/**
		  * Gibt zurueck, ob eine Umbenennung des Allianztags moeglich ist. Es muss mindestens die globale Einstellung ALLIANCE_RENAME_PERIOD in Tagen vergangen sein, damit eine erneute Umbenennung moeglich ist.
		  * @return bool
		*/

		function renameAllowed()
		{
			$last_rename = $this->getMainField("c_last_rename");
			if(!$last_rename) return true;
			return (time()-$last_rename >= Config::getLibConfig()->getConfigValueE("users", "alliance_rename_period")*86400);
		}

		/**
		  * Benennt die Allianz um.
		  * @param string $new_name
		  * @return void
		*/

		function rename($new_name)
		{
			$new_name = trim($new_name);

			self::$sql->query("UPDATE t_alliances SET c_tag = ".self::$sql->quote($new_name)." WHERE tag = ".self::$sql->quote($this->getName()));
			// Other tables are now updated using CASCADE
			//self::$sql->query("UPDATE t_alliances_members SET c_tag = ".self::$sql->quote($new_name)." WHERE tag = ".self::$sql->quote($this->getName()));
			//self::$sql->query("UPDATE t_alliances_applications SET c_tag = ".self::$sql->quote($new_name)." WHERE tag = ".self::$sql->quote($this->getName()));
			parent::rename($new_name);
		}

		/**
		  * Gibt die Anzahl der Mitglieder zurueck.
		  * @return int
		*/

		function getMembersCount()
		{
			return self::$sql->singleField("SELECT COUNT(*) FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName()).";");
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
				case Alliance::SORTBY_PUNKTE: $order = "c_score"; break;
				case Alliance::SORTBY_RANG: $order = "c_rank"; break;
				case Alliance::SORTBY_ZEIT: $order = "c_time"; break;
				default: $order = "c_member"; break;
			}
			$oder .= " ".($invert ? "DESC" : "ASC");
			return self::$sql->singleColumn("SELECT c_member FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." ORDER BY ".$order.";");
		}

		/**
		  * Wirft einen Benutzer aus der Allianz. Die Allianz wird aus dem Benutzerprofil entfernt und eine Benachrichtigung erfolgt.
		  * @param string $user
		  * @param string|null $by_whom Der Benutzer, der den Kick ausführt. Als Absender der Benachrichtigung.
		  * @return (boolean) Erfolg
		  * @todo Check whether this is the last member in the alliance
		*/

		function kickUser($user, $by_whom=null)
		{
			self::$sql->query("DELETE FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_member = ".self::$sql->quote($user).";");

			$message = Classes::Message(Message::create());
			$message->subject($user_obj->_("Allianzmitgliedschaft gekündigt"));
			$message->text(sprintf($user_obj->_("Sie wurden aus der Allianz %s geworfen."), $this->getName()));
			$message->addUser($user, Message::TYPE_VERBUENDETE);

			$members = $this->getUsersWithPermission(self::PERMISSION_KICK);
			foreach($members as $member)
			{
				if($member == $by_whom) continue;

				$user_obj = Classes::User($member);
				$message = Classes::Message(Message::create());
				$message->subject($user_obj->_("Spieler aus Allianz geworfen"));
				$message->text(sprintf($user_obj->_("Der Spieler %s wurde aus Ihrer Allianz geworfen."), $user));
				if($by_whom) $message->from($by_whom);

				$message->addUser($member, Message::TYPE_VERBUENDETE);
			}
		}

		/**
		 * Gibt zurück, ob der Benutzer Mitglied der Allianz ist.
		 * @param string $user
		 * @return bool
		*/

		function isMember($user)
		{
			return (self::$sql->singleField("SELECT COUNT(*) FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($tag)." AND c_member = ".self::$sql->quote($user)." LIMIT 1;") > 0);
		}

		/**
		  * Gibt ein Array aller Mitglieder zurueck, die eine bestimmte Berechtigung haben. Fuer die Bedeutung der Berechtigungen siehe setUserPermission().
		  * @param int $permission Alliance::PERMISSION_*
		  * @return array(string)
		*/

		function getUsersWithPermission($permission)
		{
			return self::$sql->singleColumn("SELECT c_member FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." AND ( c_permissions | ".(1 << $permission)." ) == 1;");
		}

		/**
		  * Ueberprueft, ob das Mitglied $user die Berechtigung $key (Alliance::PERMISSION_*) besitzt.
		  * @param string $user
		  * @param int $key
		  * @return bool
		*/

		function checkUserPermissions($user, $key)
		{
			$permissions = self::$sql->singleField("SELECT c_permissions FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_member = ".self::$sql->quote($user).";");
			return (true && ($permissions & (1 << $key)));
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
			$permissions = self::$sql->singleField("SELECT c_permissions FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_member = ".self::$sql->quote($user).";");
			if($permission)
				$permissions |= 1 << $key;
			elseif($permission & (1 << $key))
				$permissions ^= 1 << $key;
			self::$sql->query("UPDATE t_alliances_members SET c_permissions = ".self::$sql->quote($permissions)." WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_member = ".self::$sql->quote($user).";");
		}

		/**
		  * Gibt die Beitrittszeit eines Mitglieds zurueck.
		  * @param string $user
		  * @return int
		*/

		function getUserJoiningTime($user)
		{
			return self::$sql->singleField("SELECT c_time FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_member = ".self::$sql->quote($user).";");
		}

		/**
		  * Setzt den Rang eines Benutzers.
		  * @param string $user
		  * @param string $rank
		  * @return void
		*/

		function setUserStatus($user, $rank)
		{
			self::$sql->query("UPDATE t_alliances_members SET c_rank = ".self::$sql->quote($rank)." WHERE c_tag = ".self::$sql->quote($tag)." AND c_member = ".self::$sql->quote($user).";");
		}

		/**
		  * Gibt den Rang eines Mitglieds zurueck.
		  * @param string $user
		  * @return bool
		*/

		function getUserStatus($user)
		{
			return self::$sql->singleField("SELECT c_rank FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($tag)." AND c_member = ".self::$sql->quote($user).";");
		}

		/**
		 * Der Benutzer $user kündigt seine Mitgliedschaft in der Allianz.
		 * @param string $user
		 * @return void
		*/

		function quitUser($user)
		{
			self::$sql->query("DELETE FROM t_alliances_members WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_member = ".self::$sql->quote($user).";");

			$members = $this->getUsersList();
			foreach($members as $member)
			{
				$message = Classes::Message(Message::create());
				$user = Classes::User($member);
				$message->from($this->getName());
				$message->subject($user->_("Benutzer aus Allianz ausgetreten"));
				$message->text(sprintf($user->_("Der Benutzer %s hat Ihre Allianz verlassen."), $this->getName()));
				$message->addUser($member, Message::TYPE_VERBUENDETE);
			}

			if(count($this->getUsersWithPermission(self::PERMISSION_PERMISSIONS)) < 1)
				$this->destroy();
		}

		/**
		  * Gibt die Liste der bewerbenden Benutzer zurueck.
		  * @return array(string)
		*/

		function getApplicationsList()
		{
			return self::$sql->singleColumn("SELECT c_user FROM t_alliances_applications WHERE c_tag = ".self::$sql->quote($tag).";");
		}

		/**
		 * Gibt zurück, ob sich der Benutzer gerade bei der Allianz bewirbt.
		 * @param string $user
		 * @return bool
		*/

		function isApplying($user)
		{
			return (self::$sql->singleField("SELECT COUNT(*) FROM t_alliances_applications WHERE c_tag = ".self::$sql->quote($tag)." AND c_user = ".self::$sql->quote($user)." LIMIT 1;") > 0);
		}

		/**
		  * Der Benutzer $user bewirbt sich bei dieser Allianz.
		  * @param string $user
		  * @param string $text Bewerbungstext
		  * @return void
		*/

		function newApplication($user, $text=null)
		{
			if(!$this->allowApplications())
				throw new AllianceException("This alliance does not accept applications.");

			self::$sql->query("INSERT INTO t_alliances_applications ( c_tag, c_user ) VALUES ( ".self::$sql->quote($this->getName).", ".self::$sql->quote($user)." );");

			$users = $this->getUsersWithPermission(self::PERMISSION_APPLICATIONS);
			foreach($users as $user)
			{
				$message = Classes::Message(Message::create());
				$user_obj = Classes::User($user);
				$message_text = sprintf($user_obj->_("Der Benutzer %s hat sich bei Ihrer Allianz beworben. Gehen Sie auf Ihre Allianzseite, um die Bewerbung anzunehmen oder abzulehnen."), $this->getName());
				if(!isset($text) || strlen(trim($text)) < 1)
					$message_text .= "\n\n".$user_obj->_("Der Bewerber hat keinen Bewerbungstext hinterlassen.");
				else $message_text .= "\n\n".$user_obj->_("Der Bewerber hat folgenden Bewerbungstext hinterlassen:")."\n\n".$text;
				$message->text($message_text);
				$message->from($this->getName());
				$message->subject($user_obj->_("Neue Allianzbewerbung"));
				$message->addUser($user, Message::TYPE_VERBUENDETE);
			}
		}

		/**
		  * Der Benutzer $user zieht seine Bewerbung bei dieser Allianz zurück.
		  * @param string $user
		  * @return void
		*/

		function cancelApplication($user)
		{
			self::$sql->query("DELETE FROM t_alliances_applications WHERE c_tag = ".self::$sql->quote($tag)." AND c_user = ".self::$sql->quote($user).";");

			$users = $alliance_obj->getUsersWithPermission(self::PERMISSION_APPLICATIONS);
			foreach($users as $user)
			{
				$message_obj = Classes::Message(Message::create());
				$user_obj = Classes::User($user);
				$message_obj->from($this->getName());
				$message_obj->subject($user_obj->_("Allianzbewerbung zurückgezogen"));
				$message_obj->text(sprintf($user_obj->_("Der Benutzer %s hat seine Bewerbung bei Ihrer Allianz zurückgezogen."),$this->getName()));
				$message_obj->addUser($user, Message::TYPE_VERBUENDETE);
			}
		}

		/**
		  * Die Bewerbung des Benutzers $user wird akzeptiert.
		  * @param string $user
		  * @param string|null $by_whom Der Benutzername des annehmenden Benutzers als Absender für die Benachrichtigungen
		  * @return void
		*/

		function acceptApplication($user, $by_whom=null)
		{
			$user_obj = Classes::User($user);

			self::$sql->query("INSERT INTO t_alliances_members ( c_tag, c_member, c_rank, c_time, c_permissions ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($user).", ".self::$sql->quote($user_obj->_("Neuling")).", ".self::$sql->quote(time()).", 0 );");
			self::$sql->query("DELETE FROM t_alliances_applications WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");

			$message = Classes::Message(Message::create());
			$message->subject($user_obj->_("Allianzbewerbung angenommen"));
			$message->text(sprintf($user_obj->_("Ihre Bewerbung bei der Allianz %s wurde angenommen."), $this->getName()));
			if(isset($by_whom)) $message->from($by_whom);
			$message->addUser($user, Message::TYPE_VERBUENDETE);

			foreach($this->getUsersList() as $member)
			{
				if($member == $by_whom) continue;

				$user_obj = Classes::User($user);
				$message = Classes::Message(Message::create());
				$message->subject($user_obj->_("Neues Allianzmitglied"));
				$message->text(sprintf($user_obj->_("Ein neues Mitglied wurde in Ihre Allianz aufgenommen: %s"), $user));
				if(isset($by_whom)) $message->from($by_whom);
				$message->addUser($member, Message::TYPE_VERBUENDETE);
			}
		}

		/**
		  * Die Allianzbewerbung des Benutzers $user wird zurückgewiesen.
		  * @param string $user
		  * @param string|null $by_whom Der Name des ablehnenden Benutzers als Absender für die Benachrichtigungen.
		  * @return void
		*/

		function rejectApplication($user, $by_whom=null)
		{
			$user_obj = Classes::User($user);

			self::$sql->query("DELETE FROM t_alliances_applications WHERE c_tag = ".self::$sql->quote($this->getName())." AND c_user = ".self::$sql->quote($user).";");

			$message = Classes::Message();
			$message->subject($user_obj->_("Allianzbewerbung abgelehnt"));
			$message->text(sprintf($user_obj->_("Ihre Bewerbung bei der Allianz %s wurde abgelehnt."), $this->getName()));
			$message->addUser($user, Message::TYPE_VERBUENDETE);

			$members = $this->getUsersWithPermission(self::PERMISSION_APPLICATIONS);
			foreach($members as $member)
			{
				if($member == $by_whom) continue;
				$user_obj = Classes::User($member);
				$message = Classes::Message(Message::create());
				$message->subject($user_obj->_("Allianzbewerbung abgelehnt"));
				$message->text(sprintf($user_obj->_("Die Bewerbung von %s an Ihre Allianz wurde abgelehnt."), $user));
				if(isset($by_whom)) $message->from($by_whom);
				$message->addUser($member, Message::TYPE_VERBUENDETE);
			}
		}

		/**
		* Sucht eine Allianz mit dem Tag $search_string. '*' und '?' sind als Wildcards moeglich.
		* @param string $search_string
		* @return array die gefundenen Allianztags
		*/

		static function findAlliance($search_string)
		{
			return self::$sql->singleColumn("SELECT c_tag FROM t_alliances WHERE c_tag GLOB ".$this->quote($search_string).";");
		}

		/**
		 * Gibt zurück, in welcher Allianz sich der Benutzer befindet.
		 * @param string $user
		 * @return string|bool
		*/

		static function userAlliance($user)
		{
			return self::$sql->singleField("SELECT c_tag FROM t_alliances_members WHERE c_member = ".self::$sql->quote($user)." LIMIT 1;");
		}

		/**
		 * Gibt zurück, bei welcher Allianz sich der Benutzer bewirbt.
		 * @param string $user
		 * @return string|bool
		*/

		static function userAllianceApplying($user)
		{
			return self::$sql->singleField("SELECT c_tag FROM t_alliances_applications WHERE c_user = ".self::$sql->quote($user)." LIMIT 1;");
		}

		/**
		 * Zur internen Verwendung, wenn ein Benutzer umbenannt wird.
		 * @param string $old_name
		 * @param string $new_name
		 * @return void
		 * @see User::rename()
		*/

		static function renameUser($old_name, $new_name)
		{
			// Now done using CASCADE
			//self::$sql->backgroundQuery("UPDATE alliances_members SET member = ".self::$sql->quote($new_name)." WHERE member = ".self::$sql->quote($old_name).";");
			//self::$sql->backgroundQuery("UPDATE alliances_applications SET user = ".self::$sql->quote($new_name)." WHERE user = ".self::$sql->quote($old_name).";");
		}

		function rewind() { reset($this->users_list); return $this->current(); }
		function current() { return current($this->users_list); }
		function key() { return key($this->users_list); }
		function next() { return next($this->users_list); }
		function valid() { return key($this->users_list) !== false; }
	}
