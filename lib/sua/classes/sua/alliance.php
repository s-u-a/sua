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
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	  * Repraesentiert eine Allianz im Spiel.
	*/

	class Alliance extends SQLiteSet implements Iterator
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

		protected static $tables = array (
			"alliances" => array (
				"tag TEXT PRIMARY KEY",
				"name TEXT",
				"description TEXT",
				"description_parsed TEXT",
				"inner_description TEXT",
				"inner_description_parsed TEXT",
				"last_rename INTEGER",
				"allow_applications INTEGER DEFAULT 1"),
			"alliances_members" => array (
				"tag TEXT NOT NULL",
				"member TEXT UNIQUE NOT NULL",
				"rank TEXT",
				"time INTEGER",
				"permissions INTEGER DEFAULT 0"
			),
			"alliances_applications" => array (
				"tag TEXT NOT NULL",
				"user TEXT UNIQUE NOT NULL"
			),
			"alliance_highscores_sum" => array ( // virtual
				"tag TEXT",
				"name TEXT",
				"members INTEGER",
				"gebaeude REAL",
				"forschung REAL",
				"roboter REAL",
				"schiffe REAL",
				"verteidigung REAL",
				"flightexp REAL",
				"battleexp REAL"
			),
			"alliance_highscores_average" => array ( // virtual
				"tag TEXT",
				"name TEXT",
				"members INTEGER",
				"gebaeude REAL",
				"forschung REAL",
				"roboter REAL",
				"schiffe REAL",
				"verteidigung REAL",
				"flightexp REAL",
				"battleexp REAL"
			)
		);

		protected static $views = array (
			"alliance_highscores_sum" => "SELECT alliances_members.tag AS tag,alliances.name AS name,COUNT(DISTINCT h_users.user) AS members,SUM(h_users.gebaeude) AS gebaeude,SUM(h_users.forschung) AS forschung,SUM(h_users.roboter) AS roboter,SUM(h_users.schiffe) AS schiffe,SUM(h_users.verteidigung) AS verteidigung,SUM(h_users.flightexp) AS flightexp,SUM(h_users.battleexp) AS battleexp, gebaeude+forschung+roboter+schiffe+verteidigung+flightexp+battleexp AS total FROM (SELECT tag, user FROM alliances_members LEFT OUTER JOIN ( SELECT user, gebaeude, forschung, roboter, schiffe, verteidigung, flighexp, battleexp FROM highscores ) AS h_users ON alliances_members.user = h_users.user LEFT OUTER JOIN ( SELECT tag, name FROM alliances ) as a ON alliances_members.tag = a.tag ) GROUP BY tag;",
			"alliance_highscores_average" => "SELECT alliances_members.tag AS tag,alliances.name AS name,COUNT(DISTINCT h_users.user) AS members,AVG(h_users.gebaeude) AS gebaeude,AVG(h_users.forschung) AS forschung,AVG(h_users.roboter) AS roboter,AVG(h_users.schiffe) AS schiffe,AVG(h_users.verteidigung) AS verteidigung,AVG(h_users.flightexp) AS flightexp,AVG(h_users.battleexp) AS battleexp, gebaeude+forschung+roboter+schiffe+verteidigung+flightexp+battleexp AS total FROM (SELECT tag, user FROM alliances_members LEFT OUTER JOIN ( SELECT user, gebaeude, forschung, roboter, schiffe, verteidigung, flighexp, battleexp FROM highscores ) AS h_users ON alliances_members.user = h_users.user LEFT OUTER JOIN ( SELECT tag, name FROM alliances ) as a ON alliances_members.tag = a.tag ) GROUP BY tag;"
		);

		function __construct()
		{
			call_user_func_array(array("parent", "__construct"), func_get_args());

			$this->user_list = $this->getUsersList();
		}

		static function create($name=null)
		{
			$name = self::datasetName($name);
			self::$sqlite->query("INSERT INTO alliances ( tag ) VALUES ( ".self::$sqlite->quote($name)." );");

			return $name;
		}

		/**
		 * Entfernt die Allianz aus der Datenbank.
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
			return self::$sqlite->singleField("SELECT ".$i." FROM alliance_highscores_".$highscores." WHERE tag = ".self::$sqlite->quote($this->getName())." LIMIT 1;");
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

			return self::singleField("SELECT COUNT(*)+1 FROM alliance_highscores_".$highscores." WHERE ".$i." > ".$me->getScores($i).";");
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
					$query .= implode(",", array_merge(array("tag", "members"), $fields));
				else
					$query .= "tag,members,".$fields;
			}
			else
				$query .= "*";
			$query .= " FROM alliances_highscores_".$highscores." ORDER BY ";
			if(isset($sort))
				$query .= $sort;
			else
				$query .= "total";
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

			return self::$sqlite->arrayQuery($query);
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
		  * Benennt die Allianz um.
		  * @param string $new_name
		  * @return void
		*/

		function rename($new_name)
		{
			$new_name = trim($new_name);

			self::$sqlite->query("UPDATE alliances SET tag = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($this->getName());
			self::$sqlite->query("UPDATE alliances_members SET tag = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($this->getName());
			self::$sqlite->query("UPDATE alliances_applications SET tag = ".self::$sqlite->quote($new_name)." WHERE tag = ".self::$sqlite->quote($this->getName());
			parent::rename($new_name);
		}

		/**
		  * Gibt die Anzahl der Mitglieder zurueck.
		  * @return int
		*/

		function getMembersCount()
		{
			return self::$sqlite->singleField("SELECT COUNT(*) FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName()).";");
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
			return self::$sqlite->singleColumn("SELECT member FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." ORDER BY ".$order.";")
		}

		/**
		  * Wirft einen Benutzer aus der Allianz. Die Allianz wird aus dem Benutzerprofil entfernt und eine Benachrichtigung erfolgt.
		  * @param string $user
		  * @param string|null $by_whom Der Benutzer, der den Kick ausführt. Als Absender der Benachrichtigung.
		  * @return (boolean) Erfolg
		*/

		function kickUser($user, $by_whom=null)
		{
			self::$sqlite->query("DELETE FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");

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
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM alliances_members WHERE tag = ".self::$sqlite->quote($tag)." AND member = ".self::$sqlite->quote($user)." LIMIT 1;") > 0);
		}

		/**
		  * Gibt ein Array aller Mitglieder zurueck, die eine bestimmte Berechtigung haben. Fuer die Bedeutung der Berechtigungen siehe setUserPermission().
		  * @param int $permission Alliance::PERMISSION_*
		  * @return array(string)
		*/

		function getUsersWithPermission($permission)
		{
			return self::$sqlite->singleColumn("SELECT member FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND ( permissions | ".(1 << $permission)." ) == 1;");
		}

		/**
		  * Ueberprueft, ob das Mitglied $user die Berechtigung $key (Alliance::PERMISSION_*) besitzt.
		  * @param string $user
		  * @param int $key
		  * @return bool
		*/

		function checkUserPermissions($user, $key)
		{
			$permissions = self::$sqlite->singleField("SELECT permissions FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
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
			$permissions = self::$sqlite->singleField("SELECT permissions FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
			if($permission)
				$permissions |= 1 << $key;
			elseif($permission & (1 << $key))
				$permissions ^= 1 << $key;
			self::$sqlite->query("UPDATE alliances_members SET permissions = ".self::$sqlite->quote($permissions)." WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		  * Gibt die Beitrittszeit eines Mitglieds zurueck.
		  * @param string $user
		  * @return int
		*/

		function getUserJoiningTime($user)
		{
			return self::$sqlite->singleField("SELECT time FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");
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
			return self::$sqlite->singleField("SELECT rank FROM alliances_members WHERE tag = ".self::$sqlite->quote($tag)." AND member = ".self::$sqlite->quote($user).";");
		}

		/**
		 * Der Benutzer $user kündigt seine Mitgliedschaft in der Allianz.
		 * @param string $user
		 * @return void
		*/

		function quitUser($user)
		{
			self::$sqlite->query("DELETE FROM alliances_members WHERE tag = ".self::$sqlite->quote($this->getName())." AND member = ".self::$sqlite->quote($user).";");

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
			return self::$sqlite->singleColumn("SELECT user FROM alliances_applications WHERE tag = ".self::$sqlite->quote($tag).";");
		}

		/**
		 * Gibt zurück, ob sich der Benutzer gerade bei der Allianz bewirbt.
		 * @param string $user
		 * @return bool
		*/

		function isApplying($user)
		{
			return (self::$sqlite->singleField("SELECT COUNT(*) FROM alliances_applications WHERE tag = ".self::$sqlite->quote($tag)." AND user = ".self::$sqlite->quote($user)." LIMIT 1;") > 0);
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

			self::$sqlite->query("INSERT INTO alliances_applications ( tag, user ) VALUES ( ".self::$sqlite->quote($this->getName).", ".self::$sqlite->quote($user)." );");

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
			self::$sqlite->query("DELETE FROM alliances_applications WHERE tag = ".self::$sqlite->quote($tag)." AND user = ".self::$sqlite->quote($user).";");

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

			self::$sqlite->query("INSERT INTO alliances_members ( tag, member, rank, time, permissions ) VALUES ( ".self::$sqlite->quote($this->getName()).", ".self::$sqlite->quote($user).", ".self::$sqlite->quote($user_obj->_("Neuling")).", ".self::$sqlite->quote(time()).", 0 );");
			self::$sqlite->query("DELETE FROM alliances_applications WHERE tag = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");

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

			self::$sqlite->query("DELETE FROM alliances_applications WHERE tag = ".self::$sqlite->quote($this->getName())." AND user = ".self::$sqlite->quote($user).";");

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
			return self::$sqlite->singleColumn("SELECT tag FROM alliances WHERE tag GLOB ".$this->quote($search_string).";");
		}

		/**
		 * Gibt zurück, in welcher Allianz sich der Benutzer befindet.
		 * @param string $user
		 * @return string|bool
		*/

		static function userAlliance($user)
		{
			return self::$sqlite->singleField("SELECT tag FROM alliances_members WHERE user = ".self::$sqlite->quote($user)." LIMIT 1;");
		}

		/**
		 * Gibt zurück, bei welcher Allianz sich der Benutzer bewirbt.
		 * @param string $user
		 * @return string|bool
		*/

		static function userAllianceApplying($user)
		{
			return self::$sqlite->singleField("SELECT tag FROM alliances_applications WHERE user = ".self::$sqlite->quote($user)." LIMIT 1;");
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
			self::$sqlite->backgroundQuery("UPDATE alliances_members SET member = ".self::$sqlite->quote($new_name)." WHERE member = ".self::$sqlite->quote($old_name).";");
			self::$sqlite->backgroundQuery("UPDATE alliances_applications SET user = ".self::$sqlite->quote($new_name)." WHERE user = ".self::$sqlite->quote($old_name).";");

		function rewind() { reset($this->users_list); return $this->current(); }
		function current() { return current($this->users_list); }
		function key() { return key($this->users_list); }
		function next() { return next($this->users_list); }
		function valid() { return key($this->users_list) !== false; }
	}
