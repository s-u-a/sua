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
	 * @todo dokumentieren
	*/

	namespace sua;
	require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	 * Repräsentiert einen Benutzer im Spiel.
	 * Viele Funktionen des Benutzers werden nur auf einem bestimmten Planeten ausgeführt. Anstatt dass dieser
	 * per Parameter übergeben wird, wird per setActivePlanet() der Planet ausgewählt, auf dem solche Aktionen
	 * durchgeführt werden.
	 * Der Benutzeraccount stellt Funktionen zur Verfügung, um mit Item-Informationen
	 * umzugehen. Auf diese Weise wird es theoretisch möglich, unterschiedliche „Völker“
	 * zu implementieren, also dass es für unterschiedliche Benutzer unterschiedliche
	 * Items zu bauen gibt, die unterschiedliche Eigenschaften besitzen.
	 * @todo IteratorAggregate für den Planetendurchlauf implementieren
	 * @todo Alle Funktionen/Methoden, die sich auf einen Planeten beziehen, nach Planet auslagern.
	*/

	class User extends Serialized
	{
		/** Gebäudepunkte */
		const SCORES_GEBAEUDE = 0;

		/** Forschungspunkte */
		const SCORES_FORSCHUNG = 1;

		/** Roboterpunkte */
		const SCORES_ROBOTER = 2;

		/** Flottenpunkte */
		const SCORES_SCHIFFE = 3;

		/** Verteidigungspunkte */
		const SCORES_VERTEIDIGUNG = 4;

		/** Flugerfahrungspunkte */
		const SCORES_FLUGERFAHRUNG = 5;

		/** Kampferfahrungspunkte */
		const SCORES_KAMPFERFAHRUNG = 6;

		/**
		 * Der Index des ausgewählten Planeten.
		 * @var int
		*/
		protected $active_planet = null;

		/**
		 * Cache für den Index des aktuellen Planeten (cacheActivePlanet(), restoreActivePlanet()).
		 * @var int
		*/
		protected $active_planet_cache = array();

		/**
		 * Der aktuell ausgewählte Planet.
		 * @var Planet
		*/
		protected $active_planet_obj = null;

		/**
		 * Die einzelnen Werte geben an, ob beim Zerstören des Objekts die Highscores für Gebäude, Forschung,
		 * Schiffe, Roboter, Verteidigung, Flugerfahrung und Kampferfahrung neu berechnet werden müssen.
		 * Wird von recalcHighscores() beeinflusst.
		 * @var array(bool)
		*/
		protected $recalc_highscores = array(false,false,false,false,false,false,false);

		/**
		 * Gecachte Spracheinstellung für User->setLanguage() und User->restoreLanguage().
		 * @var array(string)
		*/
		protected $language_cache = array();

		protected static $save_dir = Classes::Database()->getDirectory()."/players";

		protected static $tables = array (
			"users" => array (
				"user TEXT PRIMARY KEY",
				"password TEXT",
				"registration INTEGER",
				"last_activity INTEGER",
				"flightexp INTEGER",
				"battleexp INTEGER",
				"used_ress_0 INTEGER",
				"used_ress_1 INTEGER",
				"used_ress_2 INTEGER",
				"used_ress_3 INTEGER",
				"used_ress_4 INTEGER",
				"description TEXT",
				"description_parsed TEXT",
				"locked INTEGER",
				"holidays INTEGER",
				"messenger_type TEXT",
				"messenger_uname TEXT"
			),
			"users_research" => array (
				"user TEXT",
				"id TEXT",
				"level INTEGER",
				"scores REAL"
			),
			"highscores" => array (
				"user TEXT",
				"gebaeude REAL",
				"forschung REAL",
				"roboter REAL",
				"schiffe REAL",
				"verteidigung REAL",
				"flightexp REAL",
				"battleexp REAL",
				"total REAL"
			),
			"users_friends" => array (
				"user1 TEXT",
				"user2 TEXT"
			),
			"users_friend_requests" => array (
				"user_from TEXT",
				"user_to TEXT"
			),
			"users_shortcuts" => array (
				"user TEXT",
				"galaxy INTEGER",
				"system INTEGER",
				"planet INTEGER"
			),
			"users_settings" => array (
				"setting TEXT",
				"value TEXT"
			)
		);

		protected static $views = array (
			"highscores" => "SELECT h_users.user AS user, h_gebaeude.scores AS gebaeude, h_forschung.scores AS forschung, h_roboter.scores AS roboter, h_schiffe.scores AS schiffe, h_verteidigung.scores AS verteidigung, h_users.flightexp AS flightexp, h_users.battleexp AS battleexp, gebaeude + forschung + roboter + schiffe + verteidigung + flightexp + battleexp AS total FROM ( SELECT user, flightexp, battleexp FROM users ) AS h_users LEFT OUTER JOIN ( SELECT user,scores FROM planets_items WHERE type = 'gebaeude' GROUP BY user ) AS h_gebaeude ON h_gebaeude.user = h_users.user LEFT OUTER JOIN ( SELECT user,scores FROM users_research GROUP BY user ) AS h_forschung ON h_forschung.user = h_users.user LEFT OUTER JOIN ( SELECT user,scores FROM ( SELECT user,scores FROM planets_items WHERE type = 'roboter' UNION ALL SELECT user,scores FROM fleets_users_rob UNION ALL SELECT user,scores FROM fleets_users_hrob ) GROUP BY user ) AS h_roboter ON h_roboter.user = h_users.user LEFT OUTER JOIN ( SELECT user,scores FROM ( SELECT user,scores FROM planets_items WHERE type = 'schiffe' UNION ALL SELECT user,scores FROM fleets_users_fleet UNION ALL SELECT user,scores FROM planet_remote_fleet ) GROUP BY user ) AS h_schiffe ON h_schiffe.user = h_users.user LEFT OUTER JOIN ( SELECT user,scores FROM planets_items WHERE type = 'verteidigung' GROUP BY user ) AS h_verteidigung ON h_verteidigung.user = h_users.user"
		);

		/**
		 * Implementiert Dataset::create().
		 * @return string
		*/

		static function create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new UserException("This user does already exist.");

			$raw = array(
				"username" => $name,
				"planets" => array(),
				"forschung" => array(),
				"password" => "x",
				"punkte" => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
				"registration" => time(),
				"messages" => array(),
				"description" => "",
				"description_parsed" => "",
				"flotten" => array(),
				"flotten_passwds" => array(),
				"foreign_fleets" => array(),
				"foreign_coords" => array(),
				"alliance" => false,
				"lang" => l::language()
			);

			$highscores = Classes::Highscores();
			$highscores->updateUser($name, "");

			self::store($name, $raw);
			return $name;
		}

		function destroy()
		{
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
			foreach(Message::getUserMessages($this->getName()) as $message_id)
				Classes::Message($message_id)->removeUser($me->getName());

			# Aus der Allianz austreten
			$tag = Alliance::getUserAlliance($this->getName());
			if($tag)
				Classes::Alliance($tag)->quitUser($this->getName());

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
			$highscores->removeEntry("users", $this->getName());

			$status = (unlink($this->filename) || chmod($this->filename, 0));
			if($status)
			{
				$this->changed = false;
				return true;
			}
			else return false;
		}

		protected function getDataFromRaw()
		{
			$settings = array("skin" => false, "schrift" => true,
				"sonden" => 1, "ress_refresh" => 0,
				"fastbuild" => false, "shortcuts" => false,
				"tooltips" => false,
				"noads" => false, "show_extern" => false,
				"notify" => false,
				"extended_buildings" => false,
				"fastbuild_full" => false,
				"receive" => array(
					1 => array(true, true),
					2 => array(true, false),
					3 => array(true, false),
					4 => array(true, true),
					5 => array(true, false)
				),
				"show_building" => array(
					"gebaeude" => 1,
					"forschung" => 1,
					"roboter" => 0,
					"schiffe" => 0,
					"verteidigung" => 0
				),
				"prod_show_days" => 1,
				"messenger_receive" => array(
					"messages" => array(1=>true, 2=>true, 3=>true, 4=>true, 5=>true, 6=>true, 7=>true),
					"building" => array("gebaeude" => 1, "forschung" => 1, "roboter" => 3, "schiffe" => 3, "verteidigung" => 3)
				),
				"lang" => l::language(),
				"fingerprint" => false,
				"gpg_im" => false,
				"timezone" => date_default_timezone_get()
			);

			$this->settings = array();
			foreach($settings as $setting=>$default)
			{
				if(isset($this->raw[$setting])) $this->settings[$setting] = $this->raw[$setting];
				else $this->settings[$setting] = $default;
			}
			if(!isset($this->settings["messenger_receive"]["building"]))
				$this->settings["messenger_receive"]["building"] = array("gebaeude" => 1, "forschung" => 1, "roboter" => 3, "schiffe" => 3, "verteidigung" => 3);

			$this->items = array();
			$this->items["forschung"] = &$this->raw["forschung"];

			$this->name = $this->raw["username"];

			$this->eventhandler();
		}

		protected function getRawFromData()
		{
			if($this->recalc_highscores[0] || $this->recalc_highscores[1] || $this->recalc_highscores[2] || $this->recalc_highscores[3] || $this->recalc_highscores[4] || $this->recalc_highscores[5] || $this->recalc_highscores[6])
				$this->doRecalcHighscores($this->recalc_highscores[0], $this->recalc_highscores[1], $this->recalc_highscores[2], $this->recalc_highscores[3], $this->recalc_highscores[4]);

			foreach($this->settings as $setting=>$value)
				$this->raw[$setting] = $value;
			$this->raw["forschung"] = $this->items["forschung"];

			$active_planet = $this->getActivePlanet();
			if($active_planet !== false)
			{
				$this->planet_info["gebaeude"] = $this->items["gebaeude"];
				$this->planet_info["roboter"] = $this->items["roboter"];
				$this->planet_info["schiffe"] = $this->items["schiffe"];
				$this->planet_info["verteidigung"] = $this->items["verteidigung"];
			}
		}

/*********************************************
*** Account ***
*********************************************/

		/**
		 * Benennt den Benutzer um.
		 * @param string $new_name
		 * @return void
		 * @todo
		*/

		function rename($new_name)
		{
			# Planeteneigentuemer aendern
			# Nachrichtenabsender aendern
			# Bei Buendnispartnern abaendern
			# In Flottenbewegungen umbenennen
			# In der Allianz umbenennen
			# Highscores-Eintrag neu schreiben
			# IM-Benachrichtigungen aendern
		}

		/**
		 * Liest oder setzt die Zeit, wann eine letzte Inaktivitätsbenachrichtigung versandt wurde.
		 * @param int $time
		 * @return void|int
		*/

		function lastMailSent($time=null)
		{
			if(isset($time))
			{
				$this->raw["last_mail"] = $time;
				$this->changed = true;
			}

			if(!isset($this->raw["last_mail"])) return null;
			return $this->raw["last_mail"];
		}

		/**
		 * Überprüft, ob das übergebene Passwort dem dieses Benutzers entspricht.
		 * Wenn das Passwort stimmt, wird eine eventuelle Prüfsumme, die beim letzten
		 * Aufruf der Passwort-vergessen-Funktion angelegt wurde, wieder deaktiviert.
		 * @param string $password
		 * @return bool
		*/

		function checkPassword($password)
		{
			if(!isset($this->raw["password"])) return false;
			if(md5($password) == $this->raw["password"])
			{
				# Passwort stimmt, Passwort-vergessen-Funktion deaktivieren
				if(isset($this->raw["email_passwd"]) && $this->raw["email_passwd"])
				{
					$this->raw["email_passwd"] = false;
					$this->changed = true;
				}

				return true;
			}
			else return false;
		}

		/**
		 * Setzt das Passwort dieses Benutzers neu.
		 * @param string $password
		 * @return void
		*/

		function setPassword($password)
		{
			$this->raw["password"] = md5($password);

			if(isset($this->raw["email_passwd"]) && $this->raw["email_passwd"])
				$this->raw["email_passwd"] = false;

			$this->changed = true;
		}

		/**
		 * Gibt die Passwort-Prüfsumme dieses Benutzers zurück. Nutzbar zum Vergleich
		 * mit anderen Benutzern.
		 * @return string
		*/

		function getPasswordSum()
		{
			return $this->raw["password"];
		}

		/**
		 * Gibt die Benutzereinstellung mit der ID $setting zurück.
		 * @param string $setting
		 * @return mixed
		*/

		function checkSetting($setting)
		{
			if(!isset($this->settings[$setting]))
				throw new UserException("This is not a valid setting.");
			return $this->settings[$setting];
		}

		/**
		 * Setzt die Benutzereinstellung $setting neu.
		 * @param string $setting
		 * @param mixed $value
		 * @return void
		*/

		function setSetting($setting, $value)
		{
			if(!isset($this->settings[$setting]))
				throw new UserException("This is not a valid setting.");
			else
			{
				$this->settings[$setting] = $value;
				$this->changed = true;
			}
		}

		/**
		 * Gibt die Benutzerbeschreibung dieses Benutzers zurück.
		 * @param bool $parsed Soll die Beschreibung zur HTML-Ausgabe zurückgegeben werden?
		 * @return string
		*/

		function getUserDescription($parsed=true)
		{
			if(!isset($this->raw["description"])) $this->raw["description"] = "";

			if($parsed)
			{
				if(!isset($this->raw["description_parsed"]))
				{
					$this->raw["description_parsed"] = F::parse_html($this->raw["description"]);
					$this->changed = true;
				}
				return $this->raw["description_parsed"];
			}
			else
				return $this->raw["description"];
		}

		/**
		 * Setzt die Benutzerbeschreibung dieses Benutzers neu.
		 * @param string $description
		 * @return void
		*/

		function setUserDescription($description)
		{
			if(!isset($this->raw["description"])) $this->raw["description"] = "";

			if($description != $this->raw["description"])
			{
				$this->raw["description"] = $description;
				$this->raw["description_parsed"] = F::parse_html($this->raw["description"]);
				$this->changed = true;
			}
		}

		/**
		 * Liest oder setzt die letzte aufgerufene URL des Benutzers. ($_SERVER["REQUEST_URI"])
		 * @param string $last_request
		 * @return void|int Wenn $last_request null ist, die letzte URL
		*/

		function lastRequest($last_request=null)
		{
			if(!isset($last_request))
			{
				if(!isset($this->raw["last_request"])) return null;
				else return $this->raw["last_request"];
			}

			$this->raw["last_request"] = $last_request;
			$this->changed = true;
		}

		/**
		 * Erneuert den Zeitpunkt der letzten Aktivität und setzt die letzte URL
		 * auf die aktuelle.
		 * @return void
		*/

		function registerAction()
		{
			$this->raw["last_request"] = $_SERVER["REQUEST_URI"];
			$this->raw["last_active"] = time();
		}

		/**
		 * Gibt den Zeitpunkt der letzten Aktivität zurück.
		 * @return int
		*/

		function getLastActivity()
		{
			if(!isset($this->raw["last_active"])) return null;
			return $this->raw["last_active"];
		}

		/**
		 * Gibt den Zeitpunkt der Registrierung zurück.
		 * @return int
		*/

		function getRegistrationTime()
		{
			if(!isset($this->raw["registration"])) return null;
			return $this->raw["registration"];
		}

		/**
		 * Gibt zurück, ob dieser Benutzer gesperrt ist.
		 * @param bool $check_unlocked Soll überprüft werden, ob eine Zeitsperre abgelaufen ist?
		 * @return bool
		*/

		function userLocked($check_unlocked=true)
		{
			if($check_unlocked && isset($this->raw["lock_time"]) && time() > $this->raw["lock_time"])
				$this->lockUser(false, false);
			return (isset($this->raw["locked"]) && $this->raw["locked"]);
		}

		/**
		 * Gibt bei einer Sperre zurück, bis wann diese noch gilt.
		 * @return int|null Null, wenn sie unendlich ist.
		*/

		function lockedUntil()
		{
			if(!$this->userLocked()) return null;
			if(!isset($this->raw["lock_time"])) return null;
			return $this->raw["lock_time"];
		}

		/**
		 * Schaltet den Sperrzustand des Benutzers um. Wenn er gesperrt ist, wird
		 * er entsperrt, wenn er entsperrt ist, wird er gesperrt.
		 * @param bool $lock_time Wenn der Benutzer gesperrt werden soll, die Zeit, wie lange die Sperre gilt
		 * @param bool $check_unlocked Soll überprüft werden, ob eine Zeitsperre mittlerweile abgelaufen ist?
		 * @return void
		*/

		function lockUser($lock_time=null, $check_unlocked=true)
		{
			$this->raw["locked"] = !$this->userLocked($check_unlocked);
			$this->raw["lock_time"] = ($this->raw["locked"] ? $lock_time : false);
			$this->changed = true;
		}

		/**
		 * Gibt zurück, ob sich der Spieler im Urlaubsmodus befindet oder setzt
		 * diesen Zustand.
		 * @param bool $set
		 * @return bool|void Wenn $set null ist, der aktuelle Zustand.
		 * @throw UserException Der Urlaubsmodus ist nicht möglich. (Siehe User->umodePossible())
		*/

		function umode($set=null)
		{
			if(isset($set))
			{
				if($set == $this->umode()) return;
				if($set && !$this->umodePossible())
					throw new UserException("Vacation mode is not possible.");

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

				$this->raw["umode"] = $set;
				$this->raw["umode_time"] = time();
				$this->changed = true;

				$flag = ($this->raw["umode"] ? "U" : "");
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
			}

			return (isset($this->raw["umode"]) && $this->raw["umode"]);
		}

		/**
		 * Überprüft, ob der Urlaubsmodus derzeit möglich ist. Das ist nur der Fall,
		 * wenn keine eigenen Flotten zu fremden Planeten unterwegs sind.
		 * @return bool
		*/

		function umodePossible()
		{
			foreach($this->getFleetsList() as $fleet)
			{
				$fleet_obj = Classes::Fleet($fleet);
				if(!$fleet_obj->userExists($this->getName()))
					continue;
				foreach($fleet_obj->getTargetsList() as $target)
				{
					if($target->getOwner() != $this->getName())
						return false;
				}
			}
			return true;
		}

		/**
		 * Überprüft, ob der Benutzer die Erlaubnis hat, in/aus den Urlaubsmodus zu
		 * gehen, also ob die Mindestzeit seit dem letzten Statuswechsel abgelaufen
		 * ist.
		 * @return bool
		*/

		function permissionToUmode()
		{
			if(!isset($this->raw["umode_time"])) return true;

			if($this->umode()) $min_days = 3; # Ist gerade im Urlaubsmodus
			else $min_days = 3;

			return ((time()-$this->raw["umode_time"]) > $min_days*86400);
		}

		/**
		 * Falls sich der Benutzer im Urlaubsmodus befindet, gibt diese Funktion
		 * den Zeitpunkt zurück, zu dem er ihn betreten hat. Ansonsten wird null
		 * zurückgeliefert.
		 * @return int|null
		*/

		function getUmodeEnteringTime()
		{
			if(!$this->umode() || !isset($this->raw["umode_time"])) return null;

			return $this->raw["umode_time"];
		}

		/**
		 * Falls sich der Benutzer im Urlaubsmodus befindet, gibt diese Funktion
		 * den Zeitpunkt zurück, zu dem er ihn verlassen darf. Ansonsten gibt sie
		 * den Zeitpunkt zurück, zu dem er ihn verlassen dürfte, wenn er ihn jetzt
		 * beträte.
		 * @return int
		*/

		function getUmodeReturnTime()
		{
			if($this->umode()) return $this->raw["umode_time"]+3*86400;
			else return time()+3*86400;
		}

		/**
		 * Gibt zurück, ob der Benutzer Dinge bauen und Flotten verschicken darf, was
		 * nicht der Fall ist, wenn er gesperrt ist, die Datenbank gesperrt ist oder
		 * er sich im Urlaubsmodus befindet.
		 * @return bool
		*/

		function permissionToAct()
		{
			return !Config::database_locked() && !$this->userLocked() && !$this->umode();
		}

		/**
		 * Gibt die ID des IM-Protokolls zurück, unter dem der Benutzer benachrichtigt werden will. Ansonsten
		 * false.
		 * @return string|bool
		*/

		function getNotificationType()
		{
			if(!isset($this->raw["im_notification"])) return false;
			return $this->raw["im_notification"];
		}

		/**
		 * Stellt einen neuen IM-Account temporär für den Benutzer ein. Der Account wird erst übernommen,
		 * wenn von diesem Account eine Nachricht mit der Prüfsumme eingeht.
		 * @param string $uin
		 * @param string $protocol
		 * @return void
		*/

		function checkNewNotificationType($uin, $protocol)
		{
			$this->raw["im_notification_check"] = array($uin, $protocol, time());
			$this->changed = true;
			return true;
		}

		/**
		 * Setzt einen neuen IM-Account für diesen Benutzer ein.
		 * @param string $uin
		 * @param string $protocol
		 * @return void
		*/

		function doSetNotificationType($uin, $protocol)
		{
			if(!isset($this->raw["im_notification_check"])) return false;
			if($this->raw["im_notification_check"][0] != $uin || $this->raw["im_notification_check"][1] != $protocol)
				return false;
			if(time()-$this->raw["im_notification_check"][2] > 86400) return false;

			$this->raw["im_notification"] = array($this->raw["im_notification_check"][0], $this->raw["im_notification_check"][1]);
			$this->changed = true;
			return true;
		}

		/**
		 * Entfernt die IM-Benachrichtigung für diesen Benutzer.
		 * @return void
		*/

		function disableNotification()
		{
			$this->raw["im_notification_check"] = false;
			$this->raw["im_notification"] = false;
			$this->changed = true;
			return true;
		}

		/**
		 * Fügt ein Lesezeichen hinzu.
		 * @param Planet $pos
		 * @return void
		*/

		function addPosShortcut(Planet $pos)
		{ # Fuegt ein Koordinatenlesezeichen hinzu
			if(!is_array($this->raw["pos_shortcuts"])) $this->raw["pos_shortcuts"] = array();
			if(in_array($pos, $this->raw["pos_shortcuts"])) return 2;

			$this->raw["pos_shortcuts"][] = $pos;
			$this->changed = true;
			return true;
		}

		/**
		 * Gibt eine Liste von Lesezeichen zurück.
		 * @return array(Planet)
		*/

		function getPosShortcutsList()
		{ # Gibt die Liste der Koordinatenlesezeichen zurueck
			if(!isset($this->raw["pos_shortcuts"])) return array();
			return $this->raw["pos_shortcuts"];
		}

		/**
		 * Entfernt ein Lesezeichen.
		 * @param Planet $pos
		 * @return void
		*/

		function removePosShortcut(Planet $pos)
		{ # Entfernt ein Koordinatenlesezeichen wieder
			if(!isset($this->raw["pos_shortcuts"])) return 2;
			$idx = array_search($pos, $this->raw["pos_shortcuts"]);
			if($idx === false) return 2;
			unset($this->raw["pos_shortcuts"][$idx]);
			$this->changed = true;
			return true;
		}

		/**
		 * Schiebt ein Lesezeichen in der Liste nach oben.
		 * @param Planet $pos
		 * @return void
		*/

		function movePosShortcutUp(Planet $pos)
		{ # Veraendert die Reihenfolge der Lesezeichen
			if(!isset($this->raw["pos_shortcuts"])) return false;

			$idx = array_search($pos, $this->raw["pos_shortcuts"]);
			if($idx === false) return false;

			$keys = array_keys($this->raw["pos_shortcuts"]);
			$keys_idx = array_search($idx, $keys);

			if(!isset($keys[$keys_idx-1])) return false;

			list($this->raw["pos_shortcuts"][$idx], $this->raw["pos_shortcuts"][$keys[$keys_idx-1]]) = array($this->raw["pos_shortcuts"][$keys[$keys_idx-1]], $this->raw["pos_shortcuts"][$idx]); # Confusing, ain"t it? ;-)
			$this->changed = true;
			return true;
		}

		/**
		 * Schiebt ein Lesezeichen in der Liste nach unten.
		 * @param Planet $pos
		 * @return void
		*/

		function movePosShortcutDown(Planet $pos)
		{ # Veraendert die Reihenfolge der Lesezeichen
			if(!isset($this->raw["pos_shortcuts"])) return false;

			$idx = array_search($pos, $this->raw["pos_shortcuts"]);
			if($idx === false) return false;

			$keys = array_keys($this->raw["pos_shortcuts"]);
			$keys_idx = array_search($idx, $keys);

			if(!isset($keys[$keys_idx+1])) return false;

			list($this->raw["pos_shortcuts"][$idx], $this->raw["pos_shortcuts"][$keys[$keys_idx+1]]) = array($this->raw["pos_shortcuts"][$keys[$keys_idx+1]], $this->raw["pos_shortcuts"][$idx]); # The same another time...
			$this->changed = true;
			return true;
		}

		/**
		 * Gibt die Prüfsumme zurück, die der Benutzer beim letzten Benutzen der „Passwort vergessen“-Funktion
		 * zugesandt bekommen hat.
		 * @return string
		*/

		function getPasswordSendID()
		{ # Liefert eine ID zurueck, die zum Senden des Passworts benutzt werden kann
			$send_id = md5(microtime());
			$this->raw["email_passwd"] = $send_id;
			$this->changed = true;
			return $send_id;
		}

		/**
		 * Überprüft, ob die übergebene Prüf-ID für die Passwort-vergessen-Funktion gültig ist.
		 * @param string $id
		 * @return bool
		*/

		function checkPasswordSendID($id)
		{ # Ueberprueft, ob eine vom Benutzer eingegebene ID der letzten durch getPasswordSendID zurueckgelieferten ID entspricht
			return (isset($this->raw["email_passwd"]) && $this->raw["email_passwd"] && $this->raw["email_passwd"] == $id);
		}

		/**
		 * Stellt die Sprache dieses Benutzer temporär als globale Spracheinstellung ein. Die vorige Einstellung
		 * kann mittels restoreLanguage() wiederhergestellt werden. Verschachtelte Aufrufe sind möglich.
		 * @return void
		*/

		function setLanguage()
		{
			$lang = $this->checkSetting("lang");
			if($lang && $lang != -1)
			{
				$this->language_cache[] = l::language();
				l::language($lang);
			}
			else
				$this->language_cache[] = null;
		}

		/**
		 * Stellt die Sprache, die zuletzt bei setLanguage() gecacht wurde, wieder ein.
		 * @return void
		*/

		function restoreLanguage()
		{
			if(count($this->language_cache) < 1)
				throw new UserException("No language has been cached.");
			$language = array_pop($this->language_cache);
			if(!is_null($language))
				l::language($language);
		}

		/**
		 * _() benutzerlokalisiert.
		 * @param string $message
		 * @return string
		*/

		function _($message)
		{
			return $this->localise("_", $message);
		}

		/**
		 * l::_i() benutzerlokalisiert.
		 * @param string $message
		 * @return string
		*/

		function _i($message)
		{
			return $this->localise(array("l", "_i"), $message);
		}

		/**
		 * Führt eine Funktion mit der Spracheinstellung dieses Benutzers aus. Der erste Parameter ist der Name
		 * der Funktion, alle anderen werden an die Funktion übergeben.
		 * @param callback $function
		 * @return mixed
		*/

		function localise($function)
		{
			$args = func_get_args();
			$this->setLanguage();
			$ret = call_user_func_array(array_shift($args), $args);
			$this->restoreLanguage();
			return $ret;
		}

		/**
		 * ngettext() benutzerlokalisiert.
		 * @param string $msgid1
		 * @param string $msgid2
		 * @param int $n
		 * @return string
		*/

		function ngettext($msgid1, $msgid2, $n)
		{
			return $this->localise("ngettext", $msgid1, $msgid2, $n);
		}

		/**
		 * date() benutzerlokalisiert.
		 * @param string $format
		 * @param int $timestamp
		 * @return string
		*/

		function date($format, $timestamp=null)
		{
			$timezone = date_default_timezone_get();
			date_default_timezone_set($this->checkSetting("timezone"));
			if($timestamp !== null) $r = date($format, $timestamp);
			else $r = date($format);
			date_default_timezone_set($timezone);
			return $r;
		}

		/**
		 * Gibt die aktuell gültige E-Mail-Adresse des Benutzers zurück.
		 * @return string
		*/

		function getEMailAddress()
		{
			if(isset($this->raw["email_new"]) && $this->raw["email_new"][1] <= time())
			{
				$this->raw["email"] = $this->raw["email_new"][0];
				unset($this->raw["email_new"]);
			}
			if(!isset($this->raw["email"]) || !$this->raw["email"])
				return null;
			return $this->raw["email"];
		}

		/**
		 * Gibt die temporäre E-Mail-Adresse des Benutzers zurück. Die Einstellung der E-Mail-Adresse wird erst
		 * nach einer gewissen Zeitspanne übernommen.
		 * @param bool $array Wenn true, wird nur die Adresse zurückgegeben.
		 * @return bool|array [ E-Mail-Adresse; Zeitpunkt der Gültigkeit ]
		*/

		function getTemporaryEMailAddress($array=false)
		{
			if(!isset($this->raw["email_new"]))
				return null;
			if($array)
				return $this->raw["email_new"];
			else
				return $this->raw["email_new"][0];
		}

		/**
		 * Setzt die E-Mail-Adresse des Benutzers.
		 * @param string $address
		 * @param bool $do_delay Soll die Verzögerung der Gültigkeit angewandt werden?
		 * @return void
		*/

		function setEMailAddress($address, $do_delay=true)
		{
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

		/**
		 * Sendet (und verschlüsselt oder signiert) eine E-Mail an diesen Benutzer.
		 * @param string $subject
		 * @param string $text
		 * @param bool $last_mail_sent Soll User->lastMailSent() aktualisiert werden?
		 * @return void
		*/

		function sendMail($subject, $text, $last_mail_sent=null)
		{
			if(!$this->getEMailAddress()) return;
			$er = error_reporting();
			error_reporting(3);
			if(!(include_once("Mail.php")) || !(include_once("Mail/mime.php")))
			{
				error_reporting($er);
				return;
			}

			$text = sprintf($this->_("Automatisch generierte Nachricht vom %s"), $this->date(_("Y-m-d H:i:s")))."\n\n".$text;

			$mime = new Mail_mime("\n");
			if($this->checkSetting("fingerprint"))
				$mime->setTXTBody(gpg_encrypt($text, $this->checkSetting("fingerprint")));
			else
				$mime->setTXTBody(GPG::sign($text));

			$body = $mime->get(array("text_charset" => "utf-8", "html_charset" => "utf-8", "head_charset" => "utf-8"));
			$hdrs = $mime->headers(array("From" => "\"".$this->_("[title_full]")."\" <".global_setting("EMAIL_FROM").">", "Subject" => $subject));

			$mail = Mail::factory("mail");
			$return = $mail->send($this->getEMailAddress(), $hdrs, $body);
			if($return && $last_mail_sent !== null)
				$this->lastMailSent($last_mail_sent);
			error_reporting($er);
		}

		/**
		 * Gibt zurück, ob der Benutzer wieder ein Captcha eingeben muss.
		 * @return bool
		*/

		function challengeNeeded()
		{
			if(isset($_SESSION["admin_username"]))
				return false;

			if(!$this->permissionToAct()) return false;

			if(!isset($this->raw["next_challenge"]))
				return true;
			else return (time() >= $this->raw["next_challenge"]);
		}

		/**
		 * Der Benutzer hat sein Captcha erfolgreich eingegeben. Der nächste Zeitpunkt der Eingabe wird
		 * festgelegt.
		 * @return void
		*/

		function challengePassed()
		{
			$this->raw["next_challenge"] = time()+rand(global_setting("CHALLENGE_MIN_TIME"), global_setting("CHALLENGE_MAX_TIME"));
			$this->raw["challenge_failures"] = 0;
			$this->changed = true;
		}

		/**
		 * Die Eingabe des Captchas ist fehlgeschlagen. Eventuell wird der Benutzer bald gesperrt.
		 * @return void
		*/

		function challengeFailed()
		{
			if(!isset($this->raw["challenge_failures"]))
				$this->raw["challenge_failures"] = 0;
			$this->raw["challenge_failures"]++;
			$this->changed = true;

			if($this->raw["challenge_failures"] > global_setting("CHALLENGE_MAX_FAILURES") && !$this->userLocked())
				$this->lockUser(time()+global_setting("CHALLENGE_LOCK_TIME"));
		}

/***************************************************
*** Planeten ***
***************************************************/

		/**
		 * Überprüft, ob noch weitere Planeten besiedelt werden dürfen.
		 * @return bool
		*/

		function checkPlanetCount()
		{
			if(global_setting("MAX_PLANETS") > 0 && count($this->raw["planets"]) < global_setting("MAX_PLANETS")) return true;
			else return false;
		}

		/**
		 * Gibt ein Flag zurück, das in der Karte hinter dem Benutzernamen angezeigt
		 * werden kann. Stellt dar, ob der Benutzer sich im Urlaubsmodus befindet
		 * oder gesperrt ist.
		*/

		function getFlag()
		{
			if($this->userLocked())
				return "g";
			elseif($this->umode())
				return "U";
			else
				return null;
		}

		/**
		 * Überprüft, ob der Benutzer einen Planeten mit dem Index $planet besitzt.
		 * @param int $planet
		 * @return bool
		*/

		function planetExists($planet)
		{
			return isset($this->raw["planets"][$planet]);
		}

		/**
		 * Setzt den aktiven Planeten des Benutzerobjekts.
		 * @param int $planet Entspricht dem Index aus getPlanetsList().
		 * @return void
		*/

		function setActivePlanet($planet)
		{
			$this->active_planet = $planet;
			$this->planet_info = &$this->raw["planets"][$planet];
			$this->active_planet_obj = Planet::fromString($this->planet_info["pos"]);

			$this->items["gebaeude"] = &$this->planet_info["gebaeude"];
			$this->items["roboter"] = &$this->planet_info["roboter"];
			$this->items["schiffe"] = &$this->planet_info["schiffe"];
			$this->items["verteidigung"] = &$this->planet_info["verteidigung"];
		}

		/**
		 * Speichert den aktuellen aktiven Planeten zwischen, sodass kurzzeitig andere Planeten ausgewählt
		 * werden können und der Ausgangszustand durch restoreActivePlanet() wiederhergestellt wird.
		 * Kann mehrmals aufgerufen werden, speichert dann mehrere Zustände zwischen, die nacheinander
		 * mit restoreActivePlanet() zurückgesetzt werden können.
		 * Falls kein Planet ausgewählt ist, ist der Aufruf von restoreActivePlanet() wirkungslos, dieser
		 * Fall muss also nicht extra behandelt werden.
		 * @return void
		*/

		function cacheActivePlanet()
		{
			try
			{
				$this->active_planet_cache[] = $this->getActivePlanet();
			}
			catch(UserException $e)
			{
				$this->active_planet_cache[] = null;
			}
		}

		/**
		 * Stellt den aktiven Planeten zum Zeitpunkt des letzten Aufrufs von cacheActivePlanet() wieder
		 * her.
		 * @return void
		 * @throw UserException cacheActivePlanet() wurde nicht ausgeführt
		*/

		function restoreActivePlanet()
		{
			if(count($this->active_planet_cache) < 1)
				throw new UserException("No planet is cached.");
			$p = array_pop($this->active_planet_cache);
			if(!is_null($p))
				$this->setActivePlanet($p);
		}

		/**
		 * Gibt den Index des Planeten $pos zurück, der für setActivePlanet() verwendet werden kann.
		 * @param Planet $pos
		 * @return int
		 * @throw UserException Der Planet gehört dem Benutzer nicht.
		 * @todo Auf Planet umstellen.
		*/

		function getPlanetByPos(Planet $pos)
		{
			$return = null;
			$this->cacheActivePlanet();
			$planets = $this->getPlanetsList();
			foreach($planets as $planet)
			{
				$this->setActivePlanet();
				if($this->getPosObj()->equals($pos))
				{
					$return = $planet;
					break;
				}
			}
			$this->restoreActivePlanet();

			if(!isset($return))
				throw new UserException("This planet is not owned by this user.");
			return $return;
		}

		/**
		 * Stellt sicher, dass ein Planet ausgewählt ist.
		 * @throw UserException Es ist kein Planet ausgewählt.
		 * @return void
		*/

		protected function _forceActivePlanet()
		{
			if($this->active_planet === null)
				throw new UserException("No planet is selected.");
		}

		/**
		 * Gibt den Index des aktuellen Planeten zurück.
		 * @return int
		*/

		function getActivePlanet()
		{
			$this->_forceActivePlanet();

			return $this->active_planet;
		}

		/**
		 * Gibt eine Liste der Indexe der Planeten dieses Benutzers zurück.
		 * @return array(int)
		*/

		function getPlanetsList()
		{
			return array_keys($this->raw["planets"]);
		}

		/**
		 * Gibt zurück, wieviele Felder dieser Planet insgesamt besitzt.
		 * @return int
		*/

		function getTotalFields()
		{
			$this->_forceActivePlanet();

			return $this->planet_info["size"][1];
		}

		/**
		 * Gibt zurück, wieviele Felder auf diesem Planeten bereits bebaut sind.
		 * @return int
		*/

		function getUsedFields()
		{
			$this->_forceActivePlanet();

			return $this->planet_info["size"][0];
		}

		/**
		 * Setzt die Anzahl der benutzten Felder auf diesem Planeten neu.
		 * @param int $value
		 * @return null
		*/

		function changeUsedFields($value)
		{
			$this->_forceActivePlanet();

			$this->planet_info["size"][0] += $value;
			$this->changed = true;
		}

		/**
		 * Gibt zurück, wieviele Felder auf diesem Planeten noch zur Verfügung stehen.
		 * @return int
		*/

		function getRemainingFields()
		{
			$this->_forceActivePlanet();

			return ($this->planet_info["size"][1]-$this->planet_info["size"][0]);
		}

		/**
		 * Gibt zurück, wieviele Felder dieser Planet ursprünglich hatte, also ohne Ingenieurswissenschaft.
		 * @return int
		*/

		function getBasicFields()
		{
			$this->_forceActivePlanet();

			return ceil($this->planet_info["size"][1]/($this->getItemLevel("F9", "forschung")/Item::getIngtechFactor()+1));
		}

		/**
		 * Setzt die Planetengröße neu.
		 * @param int $size
		 * @return void
		*/

		function setFields($size)
		{
			$this->_forceActivePlanet();

			$this->planet_info["size"][1] = $size;
			$this->changed = true;
		}

		/**
		 * Gibt den aktiven Planeten zurück.
		 * @return Planet
		*/

		function getPosObj()
		{
			return $this->active_planet_obj;
		}

		/**
		 * Gibt die Koordinaten des aktuellen Planeten als Array zurück.
		 * @return array(int)
		*/

		function getPos()
		{
			$this->_forceActivePlanet();

			$pos = explode(":", $this->planet_info["pos"], 3);
			if(count($pos) < 3) return false;
			return $pos;
		}

		/**
		 * Gibt die Koordinaten des aktuellen Planeten als String zurück.
		 * @return string implode(":", User->getPos())
		*/

		function getPosString()
		{
			$this->_forceActivePlanet();

			return $this->planet_info["pos"];
		}

		/**
		 * Gibt die lokalisierten Koordinaten des aktuellen Planeten zurück. Es wird die aktuelle Sprachein-
		 * stellung benutzt.
		 * @return string
		*/

		function getPosFormatted()
		{
			$this->_forceActivePlanet();

			return vsprintf(_("%d:%d:%d"), $this->getPos());
		}

		/**
		 * Gibt die CSS-Klasse des ausgewählten Planeten zurück.
		 * @return string
		*/

		function getPlanetClass()
		{
			$this->_forceActivePlanet();

			$pos = $this->getPosObj();
			return $pos->getPlanetClass();
		}

		/**
		 * Löscht den Planeten aus der Planetenliste des Benutzers. Hierzu werden folgende Aktionen durch-
		 * geführt:
		 * • Fremde Flotten zu diesem Planeten zurückschicken
		 * • Auf diesem Planeten fremdstationierte Flotten zurückschicken
		 * • Planeten auflösen inklusive fremdstationierter Flotten von diesem Planeten
		 * @todo Was ist mit Flotten, die von diesem Planeten kommen?
		 * @return void
		*/

		function removePlanet()
		{
			$this->_forceActivePlanet();

			global $types_message_types;

			# Alle feindlichen Flotten, die auf diesen Planeten, zurueckrufen
			$fleets = $this->getFleetsWithPlanet();
			foreach($fleets as $fleet)
			{
				$fl = Classes::Fleet($fleet);
				$users = $fl->getUsersList();
				foreach($users as $user)
				{
					$pos = $fl->from($user);
					$type = $fl->getCurrentType();
					$fl->callBack($user);

					$message = Classes::Message(Message::create());
					$user_obj = Classes::User($user);
					$message->addUser($user, $types_message_types[$type]);
					$message->subject($user_obj->_("Flotte zurückgerufen"));
					$message->from($this->getName());
					$message->text(sprintf($user_obj->_("Ihre Flotte befand sich auf dem Weg zum Planeten %s. Soeben wurde jener Planet verlassen, weshalb Ihre Flotte sich auf den Rückweg zu Ihrem Planeten %s macht."), $user_obj->localise(array("f", "formatPlanet"), $this->getPosObj(), true, false), $this->localise(array("f", "formatPlanet"), $pos, true, false)));
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
				$user_obj = Classes::User($koords->getOwner());
				$user_obj->cacheActivePlanet();
				$user_obj->setActivePlanet($user_obj->getPlanetByPos($koords));
				foreach($user_obj->getForeignFleetsList($this->getName()) as $i=>$fleet)
				{
					if($fleet[1]->equals($this->getPosObj()))
						$user_obj->subForeignFleet($this->getName(), $i);
				}
				$user_obj->restoreActivePlanet();
			}

			# Planeten aus der Karte loeschen
			$this_pos = $this->getPos();

			$galaxy = Classes::galaxy($this_pos[0]);
			$galaxy->resetPlanet($this_pos[1], $this_pos[2]);

			$planets = $this->getPlanetsList();
			$active_key = array_search($this->getActivePlanet(), $planets);
			unset($this->planet_info);
			unset($this->raw["planets"][$active_key]);
			$keys = array_keys($this->raw["planets"]);
			$this->raw["planets"] = array_values($this->raw["planets"]);
			if(isset($planets[$active_key+1]))
				$new_active_planet = array_search($planets[$active_key+1], $keys);
			elseif(isset($planets[$active_key-1]))
				$new_active_planet = array_search($planets[$active_key-1], $keys);
			else $new_active_planet = false;

			$new_planets = $this->getPlanetsList();
			foreach($new_planets as $planet)
			{
				$this->setActivePlanet($planet);
				$active_forschung = $this->checkBuildingThing("forschung");
				if(!$active_forschung) continue;
				if($active_forschung[2])
					$this->planet_info["building"]["forschung"][4] = array_search($active_forschung[4], $keys);
			}

			if($new_active_planet !== false)
				$this->setActivePlanet($new_active_planet);

			# Highscores neu berechnen
			$this->recalcHighscores(true, true, true, true, true);
		}

		/**
		 * Fügt einen Planeten in die Liste der Planeten des Benutzers ein. Bearbeitet dabei auch die Karte.
		 * @param Planet $pos
		 * @return int Der Index des neuen Planeten.
		*/

		function registerPlanet(Planet $pos)
		{
			if(!$this->checkPlanetCount())
				throw new UserException("Planet limit reached.", UserException::ERROR_PLANETCOUNT);

			$owner = $pos->getOwner();
			if($owner)
				throw new UserException("This planet is already colonised.");

			$planet_name = $this->_("Kolonie");
			$pos->setOwner($this->getName());
			$pos->setName($planet_name);

			if(count($this->raw["planets"]) <= 0) $size = 375;
			else $size = $pos->getSize();
			$size = floor($size*($this->getItemLevel("F9", "forschung")/Item::getIngtechFactor()+1));

			$planets = $this->getPlanetsList();
			if(count($planets) == 0) $planet_index = 0;
			else $planet_index = max($planets)+1;
			while(isset($this->raw["planets"][$planet_index])) $planet_index++;

			$this->raw["planets"][$planet_index] = array (
				"pos" => $pos_string,
				"ress" => array(0, 0, 0, 0, 0),
				"gebaeude" => array(),
				"roboter" => array(),
				"schiffe" => array(),
				"verteidigung" => array(),
				"size" => array(0, $size),
				"last_refresh" => time(),
				"time" => $planet_name,
				"prod" => array(),
				"name" => $planet_name,
				"foreign_fleets" => array() # Enthaelt ein Array aus Arrays, von wem welche Flotten stationiert sind
			);

			$this->changed = true;

			return $planet_index;
		}

		/**
		 * Verändert die Reihenfolge der Planetenliste des Benutzers. Schiebt
		 * den Planeten mit dem Index $planet um eins nach oben.
		 * @param int $planet Wenn nicht angegeben, wird der aktive Planet benutzt.
		 * @return void
		 * @throw UserException Wenn der Planet bereits oben in der Liste steht.
		*/

		function movePlanetUp($planet=null)
		{
			if(!isset($planet))
			{
				$this->_forceActivePlanet();
				$planet = $this->getActivePlanet();
			}

			$planets = $this->getPlanetsList();
			$planet_key = array_search($planet, $planets);
			if($planet_key === false)
				throw new UserException("This planet does not exist.");
			elseif(!isset($planets[$planet_key-1]))
				throw new UserException("This planet is on the top.");
			$this->movePlanetDown($planets[$planet_key-1]);
		}

		/**
		 * Verändert die Reihenfolge der Planetenliste des Benutzers. Schiebt
		 * den Planeten mit dem Index $planet um eins nach unten.
		 * @param int $planet Wenn nicht angegeben, wird der aktive Planet benutzt.
		 * @return void
		 * @throw UserException Wenn der Planet bereits unten in der Liste steht.
		*/

		function movePlanetDown($planet=null)
		{
			if(!isset($planet)
			{
				$this->_forceActivePlanet();
				$planet = $this->getActivePlanet();
			}

			$planets = $this->getPlanetsList();
			$planet_key = array_search($planet, $planets);
			if($planet_key === false)
				throw new UserException("This planet does not exist.");
			elseif(!isset($planets[$planet_key+1]))
				throw new UserException("This planet is on the bottom.");

			$planet2 = $planets[$planet_key+1];

			$new_active_planet = $this->getActivePlanet();
			if($new_active_planet == $planet) $new_active_planet = $planet2;
			elseif($new_active_planet == $planet2) $new_active_planet = $planet;

			unset($this->planet_info);

			# Planeten vertauschen
			list($this->raw["planets"][$planet], $this->raw["planets"][$planet2]) = array($this->raw["planets"][$planet2], $this->raw["planets"][$planet]);

			# Aktive Forschungen aendern
			$this->setActivePlanet($planet);
			$active_forschung = $this->checkBuildingThing("forschung");
			if($active_forschung && $active_forschung[2])
				$this->planet_info["building"]["forschung"][4] = $planet2;
			$this->refreshMessengerBuildingNotifications();

			$this->setActivePlanet($planet2);
			$active_forschung = $this->checkBuildingThing("forschung");
			if($active_forschung && $active_forschung[2])
				$this->planet_info["building"]["forschung"][4] = $planet;
			$this->refreshMessengerBuildingNotifications();

			# Index in der Börse aktualisieren
			$market = Classes::Market();
			$tmp = round(microtime()*1000000);
			$market->renamePlanet($this->getName(), $planet, $tmp);
			$market->renamePlanet($this->getName(), $planet2, $planet);
			$market->renamePlanet($this->getName(), $tmp, $planet2);

			if($new_active_planet != $planet2) $this->setActivePlanet($new_active_planet);
		}

				/**
		 * Setzt oder liest den Namen des aktiven Planeten.
		 * @param string $name Wenn angegeben, wird der Name hierauf gesetzt.
		 * @return string|void Wenn $name null ist, der Name des aktiven Planeten.
		*/

		function planetName($name=null)
		{
			$this->_forceActivePlanet();

			if(isset($name))
			{
				if(trim($name) == "")
					throw new UserException("The planet name cannot be empty.");
				$name = substr($name, 0, 24);
				if(isset($this->planet_info["name"]))
					$old_name = $this->planet_info["name"];
				else $old_name = "";
				$this->planet_info["name"] = $name;

				$pos = $this->getPos();
				$galaxy = Classes::Galaxy($pos[0]);
				if(!$galaxy->setPlanetName($pos[1], $pos[2], $name))
					$this->planet_info["name"] = $old_name;
				else
					$this->changed = true;
			}

			return $this->planet_info["name"];
		}

		/**
		 * Gibt die Rohstoffbestände auf dem aktiven Planeten zurück.
		 * @param bool $refresh Soll User->refreshRess() ausgeführt werden?
		 * @return array(int)
		*/

		function getRess($refresh=true)
		{
			$this->_forceActivePlanet();

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

		/**
		 * Fügt den Rohstoffbeständen des Planeten Rohstoffe hinzu.
		 * @param array $ress Array mit Rohstoffen
		 * @return void
		*/

		function addRess(array $ress)
		{
			$this->_forceActivePlanet();

			if(isset($ress[0])) $this->planet_info["ress"][0] += $ress[0];
			if(isset($ress[1])) $this->planet_info["ress"][1] += $ress[1];
			if(isset($ress[2])) $this->planet_info["ress"][2] += $ress[2];
			if(isset($ress[3])) $this->planet_info["ress"][3] += $ress[3];
			if(isset($ress[4])) $this->planet_info["ress"][4] += $ress[4];

			$this->changed = true;
		}

		/**
		 * Zieht Rohstoffe vom Bestand auf dem aktiven Planeten ab.
		 * @param array $ress Das Rohstoff-Array
		 * @param bool $make_scores Sollen die Rohstoffe zu den ausgegebenen Punkten gezählt werden?
		 * @return void
		*/

		function subtractRess(array $ress, $make_scores=true)
		{
			$this->_forceActivePlanet();

			if(isset($ress[0])){ $this->planet_info["ress"][0] -= $ress[0]; if($make_scores) $this->raw["punkte"][7] += $ress[0]; }
			if(isset($ress[1])){ $this->planet_info["ress"][1] -= $ress[1]; if($make_scores) $this->raw["punkte"][8] += $ress[1]; }
			if(isset($ress[2])){ $this->planet_info["ress"][2] -= $ress[2]; if($make_scores) $this->raw["punkte"][9] += $ress[2]; }
			if(isset($ress[3])){ $this->planet_info["ress"][3] -= $ress[3]; if($make_scores) $this->raw["punkte"][10] += $ress[3]; }
			if(isset($ress[4])){ $this->planet_info["ress"][4] -= $ress[4]; if($make_scores) $this->raw["punkte"][11] += $ress[4]; }

			$this->changed = true;
		}

		/**
		 * Überprüft, ob die angegebenen Rohstoffe auf dem Planeten vorhanden sind.
		 * @param array $ress Rohstoff-Array
		 * @return bool
		*/

		function checkRess($ress)
		{
			$this->_forceActivePlanet();

			if(isset($ress[0]) && $ress[0] > $this->planet_info["ress"][0]) return false;
			if(isset($ress[1]) && $ress[1] > $this->planet_info["ress"][1]) return false;
			if(isset($ress[2]) && $ress[2] > $this->planet_info["ress"][2]) return false;
			if(isset($ress[3]) && $ress[3] > $this->planet_info["ress"][3]) return false;
			if(isset($ress[4]) && $ress[4] > $this->planet_info["ress"][4]) return false;

			return true;
		}

		/**
		 * Erneuert die Rohstoffbestände auf diesem Planeten. Der Zeitpunkt der
		 * letzten Erneuerung wurde zwischengespeichert, die Produktion seither
		 * wird nun hinzugefügt.
		 * @param int $time Bestand zu diesem Zeitpunkt statt zum aktuellen verwenden
		 * @throw UserException Die letzte Aktualisierung ist neuer als $time
		 * @return void
		*/

		protected function refreshRess($time=null)
		{
			$this->_forceActivePlanet();

			if($this->planet_info["last_refresh"] >= $time)
				throw new UserException("Last refresh is in the future.");

			$prod = $this->getProduction($time !== false);
			$limit = $this->getProductionLimit($time !== false);

			$f = ($time-$this->planet_info["last_refresh"])/3600;

			for($i=0; $i<=4; $i++)
			{
				if($this->planet_info["ress"][$i] >= $limit[$i])
					continue;
				$this->planet_info["ress"][$i] += $prod[$i]*$f;
				if($this->planet_info["ress"][$i] > $limit[$i])
					$this->planet_info["ress"][$i] = $limit[$i];
			}

			$this->planet_info["last_refresh"] = $time;

			$this->changed = true;
		}

		/**
		 * Gibt den eingestellten Produktionsfaktor eines Gebäudes zurück.
		 * @param string $gebaeude Die Item-ID.
		 * @return float
		*/

		function checkProductionFactor($gebaeude)
		{
			$this->_forceActivePlanet();

			if(isset($this->planet_info["prod"][$gebaeude]))
				return $this->planet_info["prod"][$gebaeude];
			else return 1;
		}

		/**
		 * Setzt den Produktionsfaktor eines Gebäudes.
		 * @param string $gebaeude Die Item-ID
		 * @param float $factor
		 * @return void
		*/

		function setProductionFactor($gebaeude, $factor)
		{
			$this->_forceActivePlanet();

			if(!$this->getItemInfo($gebaeude, "gebaeude", array(false))) return false;

			$factor = (float) $factor;

			if($factor < 0) $factor = 0;
			if($factor > 1) $factor = 1;

			$this->planet_info["prod"][$gebaeude] = $factor;
			$this->changed = true;
		}

		/**
		 * Gibt zurück, wieviel pro Stunde produziert wird.
		 * @return array [ Carbon, Aluminium, Wolfram, Radium, Tritium, Energie, Unterproduktionsfaktor Energie, Energiemaximum erreicht? ]
		*/

		function getProduction()
		{
			$this->_forceActivePlanet();

			$planet = $this->getActivePlanet();
			$prod = array(0,0,0,0,0,0,0,false);
			if($this->permissionToAct())
			{
				$gebaeude = $this->getItemsList("gebaeude");

				$energie_prod = 0;
				$energie_need = 0;
				foreach($gebaeude as $id)
				{
					$item = $this->getItemInfo($id, "gebaeude", null, false);
					if($item["prod"][5] < 0) $energie_need -= $item["prod"][5];
					elseif($item["prod"][5] > 0) $energie_prod += $item["prod"][5];

					$prod[0] += $item["prod"][0];
					$prod[1] += $item["prod"][1];
					$prod[2] += $item["prod"][2];
					$prod[3] += $item["prod"][3];
					$prod[4] += $item["prod"][4];
				}

				$limit = $this->getProductionLimit();
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

		/**
		 * Gibt die maximalen Produktionsmengen für Rohstoffe und Energie zurück.
		 * @return array(float)
		*/

		function getProductionLimit()
		{
			$this->_forceActivePlanet();

			$limit = global_setting("PRODUCTION_LIMIT_INITIAL");
			$steps = global_setting("PRODUCTION_LIMIT_STEPS");
			$limit[0] += $this->getItemLevel("R02", "roboter")*$steps[0];
			$limit[1] += $this->getItemLevel("R03", "roboter")*$steps[1];
			$limit[2] += $this->getItemLevel("R04", "roboter")*$steps[2];
			$limit[3] += $this->getItemLevel("R05", "roboter")*$steps[3];
			$limit[4] += $this->getItemLevel("R06", "roboter")*$steps[4];
			$limit[5] += $this->getItemLevel("F3", "forschung")*$steps[5];

			return $limit;
		}

/*****************************************
*** Highscores ***
*****************************************/

		/**
		 * Gibt die Punkte des Spielers zurück.
		 * @param int $i User::SCORES_* oder nichts für die Gesamtpunktzahl
		 * @return float
		*/

		function getScores($i=null)
		{
			if(!isset($i))
				return $this->raw["punkte"][0]+$this->raw["punkte"][1]+$this->raw["punkte"][2]+$this->raw["punkte"][3]+$this->raw["punkte"][4]+$this->raw["punkte"][5]+$this->raw["punkte"][6];
			elseif(!isset($this->raw["punkte"][$i]))
				return 0;
			else
				return $this->raw["punkte"][$i];
		}

		/**
		 * Addiert dem Benutzeraccount Punkte hinzu.
		 * @param int $i User::SCORES_*
		 * @param float $scores
		 * @return void
		*/

		function addScores($i, $scores)
		{
			if(!isset($this->raw["punkte"][$i]))
				$this->raw["punkte"][$i] = $scores;
			else $this->raw["punkte"][$i] += $scores;

			$this->recalc_highscores[$i] = true;
			$this->changed = true;
		}

		/**
		 * Gibt die Anzahl der Rohstoffe zurück, die der Benutzer seit Bestehen
		 * des Accounts ausgegeben hat.
		 * @param int $i 0: Carbon; 1: Aluminium; ... Wenn nicht angegeben, wird die Gesamtzahl zurückgegeben
		 * @return int
		*/

		function getSpentRess($i=false)
		{
			if($i === false)
			{
				return $this->getScores(7)+$this->getScores(8)+$this->getScores(9)+$this->getScores(10)+$this->getScores(11);
			}
			else return $this->getScores($i+7);
		}

		/**
		 * Gibt die Platzierung dieses Spielers in den Highscores zurück.
		 * @param int $i User::SCORES_*, wenn die Platzierung bei einer bestimmten Punktart gemeint ist
		 * @return int
		*/

		function getRank($i=null)
		{
			$highscores = Classes::Highscores();
			if($i === null)
				return $highscores->getPosition("users", $this->getName());
			else
				return $highscores->getPosition("users", $this->getName(), "scores_".$i);
		}

/***************************************
*** Flotten ***
***************************************/

		/**
		 * Gibt eine Liste mit den Flotten zurück, die dieser Benutzer sehen darf.
		 * @return array Array mit Flotten-IDs.
		*/

		function getFleetsList()
		{
			if(isset($this->raw["flotten"]) && count($this->raw["flotten"]) > 0)
			{
				foreach($this->raw["flotten"] as $i=>$flotte)
				{
					if(!Fleet::exists($flotte))
					{
						unset($this->raw["flotten"][$i]);
						$this->changed = true;
						continue;
					}
					/*$fl = Classes::Fleet($flotte);
					$arrival = $fl->getNextArrival();
					if($arrival <= time())
						$fl->arriveAtNextTarget();*/
				}
				return $this->raw["flotten"];
			}
			else return array();
		}

		/**
		 * Fügt der Liste der Flotten, die der Benutzer sehen darf, eine Flotte
		 * hinzu.
		 * @return void
		*/

		function addFleet($fleet)
		{
			if(!isset($this->raw["flotten"])) $this->raw["flotten"] = array();
			elseif(in_array($fleet, $this->raw["flotten"])) return;
			$this->raw["flotten"][] = $fleet;
			natcasesort($this->raw["flotten"]);
			$this->changed = true;
		}

		/**
		 * Entfernt eine Flotte aus der Liste der Flotten, die der Benutzer sehen darf.
		 * @return void
		*/

		function unsetFleet($fleet)
		{
			if(!isset($this->raw["flotten"])) return;
			$key = array_search($fleet, $this->raw["flotten"]);
			if($key === false) return;
			unset($this->raw["flotten"][$key]);
			$this->changed = true;
		}

		/**
		 * Gibt zurück, ob eigenen Flotte vom oder zum aktiven Planeten unterwegs sind.
		 * @return bool
		*/

		function checkOwnFleetWithPlanet()
		{
			$this->_forceActivePlanet();

			foreach($this->getFleetsList() as $flotte)
			{
				$fl = Classes::Fleet($flotte);
				if(in_array($this->getName(), $fl->getUsersList()) && ($fl->from($this->getName())->equals($this->getPosObj()) || $fl->isATarget($this->getPosObj())))
					return true;
			}
			return false;
		}

		/**
		 * Gibt eine Liste aller eigenen Flotten zurück, die vom oder zum aktiven
		 * Planeten unterwegs sind.
		 * @return array
		*/

		function getFleetsWithPlanet()
		{
			$this->_forceActivePlanet();

			$fleets = array();
			foreach($this->getFleetsList() as $flotte)
			{
				$fl = Classes::Fleet($flotte);
				if(in_array($this->getName(), $fl->getUsersList()) && ($fl->from($this->getName())->equals($this->getPosObj()) || $fl->isATarget($this->getPosObj())))
					$fleets[] = $flotte;
			}
			return $fleets;
		}

		/**
		 * Gibt das Flottenlimit zurück, also wieviele Flotten gleichzeitig
		 * unterwegs sein dürfen.
		 * @return int
		*/

		function getMaxParallelFleets()
		{
			$werft = 0;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if($this->getItemLevel("B10", "gebaeude") > 0)
					$werft++;
			}
			$this->setActivePlanet($active_planet);

			return ceil(pow($werft*$this->getItemLevel("F0", "forschung"), .7));
		}

		/**
		 * Gibt zurück, wieviele „Flotten-Slots“ gerade belegt sind.
		 * @return int
		*/

		function getCurrentParallelFleets()
		{
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
				$user_obj = Classes::User($coords->getOwner());
				$user_obj->cacheActivePlanet();
				$user_obj->setActivePlanet($user_obj->getPlanetByPos($coords));
				$fleets += count($user_obj->getForeignFleetsList($this->getName()));
				$user_obj->restoreActivePlanet();
			}
			return $fleets;
		}

		/**
		 * Gibt zurück, wieviele „Flotten-Slots“ noch frei sind.
		 * @return int
		*/

		function getRemainingParallelFleets()
		{
			return $this->getMaxParallelFleets()-$this->getCurrentParallelFleets();
		}

		/**
		 * Stationiert eine Flotte $fleet vom Planeten $from, die dem Benutzer $user gehört, auf dem aktiven Planeten.
		 * @param string $user Der Benutzername des Eigentümers der Flotten.
		 * @param array $fleet Das Item-Array der Flotten. ( Item-ID => Anzahl )
		 * @param string $from Die Herkunfskoordinaten der Flotte. Hierhin werden sie beim Abbruch zurückgesandt.
		 * @param float $speed_factor Mit diesem Geschwindigkeitsfaktor wurden die Flotten losgeschickt, sie werden beim Abbruch genausolangsam zurückfliegen.
		 * @return void
		*/

		function addForeignFleet($user, array $fleet, $from, $speed_factor)
		{
			$this->_forceActivePlanet();

			if(!isset($this->planet_info["foreign_fleets"]))
				$this->planet_info["foreign_fleets"] = array();

			if(!isset($this->planet_info["foreign_fleets"][$user]))
			{
				$this->planet_info["foreign_fleets"][$user] = array();
				$user_obj = Classes::User($user);
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
		 * @return void
		*/

		function subForeignShips($username, $id, $count)
		{
			$this->_forceActivePlanet();

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
		 * @param string $user Der Benutzername.
		 * @param int $i Die Nummer der Flotte.
		 * @return void
		*/

		function subForeignFleet($user, $i)
		{
			$this->_forceActivePlanet();

			if(!isset($this->planet_info["foreign_fleets"]) || !isset($this->planet_info["foreign_fleets"][$user]) || !isset($this->planet_info["foreign_fleets"][$user][$i]))
				return false;

			$message_obj = Classes::Message(Message::create());
			$message_obj->text(sprintf($this->_("Der Benutzer %s hat eine fremdstationierte Flotte von Ihrem Planeten „%s“ (%s) zurückgezogen.\nDie Flotte bestand aus folgenden Schiffen: %s"), $user, $this->planetName(), vsprintf($this->_("%d:%d:%d"), $this->getPos()), $this->_i(Item::makeItemsString($this->planet_info["foreign_fleets"][$user][$i][0], true, true))));
			$message_obj->subject(sprintf($this->_("Fremdstationierung zurückgezogen auf %s"), vsprintf($this->_("%d:%d:%d"), $this->getPos())));
			$message_obj->from($user);
			$message_obj->addUser($this->getName(), Message::TYPE_TRANSPORT);

			unset($this->planet_info["foreign_fleets"][$user][$i]);

			if(count($this->planet_info["foreign_fleets"][$user]) == 0)
			{
				unset($this->planet_info["foreign_fleets"][$user]);
				$user_obj = Classes::User($user);
				$user_obj->_subForeignCoordinates($this->getPosString());
			}

			$this->changed = true;

			return true;
		}

		/**
		 * Gibt die Liste der Benutzer zurück, die auf diesem Planeten Flotte stationiert haben.
		 * @return array ( Benutzername )
		*/

		function getForeignUsersList()
		{
			$this->_forceActivePlanet();

			if(!isset($this->planet_info["foreign_fleets"]))
				$this->planet_info["foreign_fleets"] = array();

			return array_keys($this->planet_info["foreign_fleets"]);
		}

		/**
		 * Gibt die Liste der fremdstationierten Flotten des Benutzers $user auf diesem Planeten zurück. Jede ankommende Fremdstationierung erhält einen eigenen Index. Um nur die Flotten einer bestimmten Fremdstationierung zu erhalten, kann $i gesetzt werden.
		 * @param string $user Der Benutzername.
		 * @param int $i Der Index der Fremdstationierung.
		 * @return array ( Index => [ ( Item-ID => Anzahl ), Herkunftsplanet, Geschwindigkeitsfaktor ] ), wenn $i null ist, ansonsten den Inhalt des Index $i.
		*/

		function getForeignFleetsList($user, $i=null)
		{
			$this->_forceActivePlanet();

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
		 * @param Planet $coords
		 * @return void
		*/

		function _addForeignCoordinates($coords)
		{
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
		 * @param Planet $coords
		 * @return void
		*/

		function _subForeignCoordinates($coords)
		{
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
		 * @return array(Planet)
		*/

		function getMyForeignFleets()
		{
			if(!isset($this->raw["foreign_coords"]))
				$this->raw["foreign_coords"] = array();

			return array_unique($this->raw["foreign_coords"]);
		}

		/**
		 * Holt eine fremdstationierte Flotte zurück.
		 * @param Planet $koords
		 * @param int $i Der Index der Flotte, ansonsten werden alle zurückgeholt.
		 * @return void
		 * @todo $koords als Planet übergeben lassen
		*/

		function callBackForeignFleet($koords, $i=null)
		{
			$owner = $koords->getOwner();
			if(!$owner) return false;

			$user_obj = Classes::User($owner);
			$user_obj->cacheActivePlanet();
			$user_obj->setActivePlanet($user_obj->getPlanetByPos($koords));

			$fleets = $user_obj->getForeignFleetsList($this->getName());
			if($i !== null && !isset($fleets[$i])) return false;
			foreach($fleets as $i2=>$fleet)
			{
				if($i !== null && $i != $i2) continue;

				$fleet_obj = Classes::Fleet(Fleet::create());

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

		/**
		 * Gibt die Flotten-ID zurück, die zu einem Verbundflottenpasswort gehört.
		 * @param string $passwd
		 * @return string
		*/

		function resolveFleetPasswd($passwd)
		{
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

		/**
		 * Gibt das Verbundflottenpasswort einer Flotte zurück.
		 * @param string $fleet_id
		 * @return string
		*/

		function getFleetPasswd($fleet_id)
		{
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

		/**
		 * Ändert das Verbundflottenpasswort einer Flotte.
		 * @param string $fleet_id
		 * @param string $passwd
		 * @return void
		*/

		function changeFleetPasswd($fleet_id, $passwd)
		{
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
		}

/*********************************************
*** Items ***
*********************************************/

		/**
		 * Gibt eine Liste der Item-IDs zurück, die dieser Benutzer theoretisch
		 * bauen kann.
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @return array(string)
		*/

		function getItemsList($type=null)
		{
			return Item::getList($type);
		}

		/**
		  * Liefert die Abhaengigkeiten des Gegenstandes mit der ID $id zurueck. Die Abhaengigkeiten
		  * werden rekursiv aufgeloest, also die Abhaengigkeiten der Abhaengigkeiten mitbeachtet.
		  * @param string $id
		  * @param array|null $deps Zur Rekursion, wird als Referenz uebergeben, die Abhaengigkeiten werden dann dem Array hinzugefuegt
		  * @return array ( Item-ID => Stufe )
		*/

		function getItemDeps($id, &$deps=null)
		{
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

		/**
		 * Der Parameter $fields von User->getItemInfo() kann Felder enthalten, die
		 * andere Felder benötigen, um berechnet werden zu können. Dieser Funktion
		 * wird der $fields-Parameter übergeben, sie liefert dann die vervollständigte
		 * Liste zurück.
		 * @param array $fields
		 * @return array
		*/

		function _itemInfoFields(array $fields)
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

		/**
		 * Gibt Informationen zu einem Item zurück.
		 * @param string $id
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @param array $fields Wenn angegeben, werden nur diese Eigenschaften (also nur diese Array-Keys) zurückgegeben.
		 * @param bool $run_eventhandler Wirkungslos
		 * @param int $level Gibt die Informationen auf einer bestimmten Ausbaustufe zurück
		 * @return array
		*/

		function getItemInfo($id, $type=null, $fields=null, $run_eventhandler=null, $level=null)
		{
			list($calc, $fields) = $this->_itemInfoFields($fields);

			$this_planet = $this->getActivePlanet();
			$item = Classes::Item($id);
			if($type === null) $type = $item->getType();
			$info = $item->getInfo($fields);
			if($info === false) return false;
			if($calc["type"])
				$info["type"] = $type;
			if($calc["buildable"])
				$info["buildable"] = $item->checkDependencies($this);
			if($calc["deps-okay"])
				$info["deps-okay"] = $item->checkDependencies($this);
			if($calc["level"])
				$info["level"] = ($level !== null ? $level : $this->getItemLevel($id, $type));
			if($calc["name"])
				$info["name"] = $this->_("[item_".$id."]");

			# Bauzeit als Anteil der Punkte des ersten Platzes
			/*if(isset($info["time"]))
			{
				$highscores = Classes::Highscores();
				if($first = $highscores->getList("users", 1, 1))
				{
					list($best_rank) = $first;
					if($best_rank["scores"] == 0) $f = 1;
					else $f = $this->getScores()/$best_rank["scores"];
					if($f < .5) $f = .5;
					$info["time"] *= $f;
				}
			}*/

			$database_config = Classes::Database(global_setting("DB"))->getConfig();
			$global_factors = isset($database_config["global_factors"]) ? $database_config["global_factors"] : array();

			if(isset($info["time"]) && isset($global_factors["time"]))
				$info["time"] *= $global_factors["time"];
			if(isset($info["prod"]) && isset($global_factors["production"]))
			{
				$info["prod"][0] *= $global_factors["production"];
				$info["prod"][1] *= $global_factors["production"];
				$info["prod"][2] *= $global_factors["production"];
				$info["prod"][3] *= $global_factors["production"];
				$info["prod"][4] *= $global_factors["production"];
				$info["prod"][5] *= $global_factors["production"];
			}
			if(isset($info["ress"]) && isset($global_factors["costs"]))
			{
				$info["ress"][0] *= $global_factors["costs"];
				$info["ress"][1] *= $global_factors["costs"];
				$info["ress"][2] *= $global_factors["costs"];
				$info["ress"][3] *= $global_factors["costs"];
			}

			switch($type)
			{
				case "gebaeude":
					if($calc["prod"] || $calc["time"])
						$max_rob_limit = floor($this->getBasicFields()/2);

					if($calc["has_prod"])
						$info["has_prod"] = ($info["prod"][0] > 0 || $info["prod"][1] > 0 || $info["prod"][2] > 0 || $info["prod"][3] > 0 || $info["prod"][4] > 0 || $info["prod"][5] > 0);
					if($calc["prod"])
					{
						$level_f = pow($info["level"], 2);
						$percent_f = $this->checkProductionFactor($id);
						$info["prod"][0] *= $level_f*$percent_f;
						$info["prod"][1] *= $level_f*$percent_f;
						$info["prod"][2] *= $level_f*$percent_f;
						$info["prod"][3] *= $level_f*$percent_f;
						$info["prod"][4] *= $level_f*$percent_f;
						$info["prod"][5] *= $level_f*$percent_f;

						$use_old_robtech = file_exists(global_setting("DB_USE_OLD_ROBTECH"));
						if($use_old_robtech)
							$minen_rob = 1+0.0003125*$this->getItemLevel("F2", "forschung");
						else
							$minen_rob = sqrt($this->getItemLevel("F2", "forschung"))/250;
						if($use_old_robtech && $minen_rob > 1 || !$use_old_robtech && $minen_rob > 0)
						{
							$use_max_limit = !file_exists(global_setting("DB_NO_STRICT_ROB_LIMITS"));

							$rob = $this->getItemLevel("R02", "roboter");
							if($rob > $this->getItemLevel("B0", "gebaeude")) $rob = $this->getItemLevel("B0", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info["prod"][0] *= pow($minen_rob, $rob);
							else
								$info["prod"][0] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R03", "roboter");
							if($rob > $this->getItemLevel("B1", "gebaeude")) $rob = $this->getItemLevel("B1", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info["prod"][1] *= pow($minen_rob, $rob);
							else
								$info["prod"][1] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R04", "roboter");
							if($rob > $this->getItemLevel("B2", "gebaeude")) $rob = $this->getItemLevel("B2", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info["prod"][2] *= pow($minen_rob, $rob);
							else
								$info["prod"][2] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R05", "roboter");
							if($rob > $this->getItemLevel("B3", "gebaeude")) $rob = $this->getItemLevel("B3", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info["prod"][3] *= pow($minen_rob, $rob);
							else
								$info["prod"][3] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R06", "roboter");
							if($rob > $this->getItemLevel("B4", "gebaeude")*2) $rob = $this->getItemLevel("B4", "gebaeude")*2;
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							if($use_old_robtech)
								$info["prod"][4] *= pow($minen_rob, $rob);
							else
								$info["prod"][4] *= 1+$minen_rob*$rob;
						}
					}

					if($calc["time"])
					{
						$info["time"] *= pow($info["level"]+1, 1.5);
						$baurob = 1-0.00125*$this->getItemLevel("F2", "forschung");
						$rob = $this->getItemLevel("R01", "roboter");
						if($rob > $max_rob_limit) $rob = $max_rob_limit;
						$info["time"] *= pow($baurob, $rob);
					}

					if($calc["scores"])
					{
						$ress = array_sum($info["ress"]);
						$scores = 0;
						for($i=1; $i<=$info["level"]; $i++)
							$scores += $ress*pow($i, 2.4);
						$info["scores"] = $scores/1000;
					}

					if($calc["ress"])
					{
						$ress_f = pow($info["level"]+1, 2.4);
						$info["ress"][0] *= $ress_f;
						$info["ress"][1] *= $ress_f;
						$info["ress"][2] *= $ress_f;
						$info["ress"][3] *= $ress_f;
					}

					if($calc["buildable"] && $info["buildable"] && $info["fields"] > $this->getRemainingFields())
						$info["buildable"] = false;
					if($calc["debuildable"])
						$info["debuildable"] = ($info["level"] >= 1 && -$info["fields"] <= $this->getRemainingFields());

					if($calc["limit_factor"])
					{
						if($info["time"] < global_setting("MIN_BUILDING_TIME"))
							$info["limit_factor"] = $info["time"]/global_setting("MIN_BUILDING_TIME");
						else
							$info["limit_factor"] = 1;
					}

					# Runden
					if($calc["prod"])
					{
						Functions::stdround($info["prod"][0]);
						Functions::stdround($info["prod"][1]);
						Functions::stdround($info["prod"][2]);
						Functions::stdround($info["prod"][3]);
						Functions::stdround($info["prod"][4]);
						Functions::stdround($info["prod"][5]);
					}
					if($calc["time"])
						Functions::stdround($info["time"]);
					if($calc["ress"])
					{
						Functions::stdround($info["ress"][0]);
						Functions::stdround($info["ress"][1]);
						Functions::stdround($info["ress"][2]);
						Functions::stdround($info["ress"][3]);
						Functions::stdround($info["ress"][4]);
					}
					break;
				case "forschung":
					if($calc["time"])
					{
						$info["time"] *= pow($info["level"]+1, 2);

						$local_labs = 0;
						$global_labs = 0;
						$planets = $this->getPlanetsList();
						$active_planet = $this->getActivePlanet();
						foreach($planets as $planet)
						{
							$this->setActivePlanet($planet);
							if($planet == $active_planet) $local_labs += $this->getItemLevel("B8", "gebaeude");
							else $global_labs += $this->getItemLevel("B8", "gebaeude");
						}
						$this->setActivePlanet($active_planet);

						if($calc["time_local"])
							$info["time_local"] = $info["time"]*pow(0.95, $local_labs);
						unset($info["time"]);
						if($calc["time_global"])
							$info["time_global"] = $info["time_local"]*pow(0.975, $global_labs);
					}

					if($calc["scores"])
					{
						$ress = array_sum($info["ress"]);
						$scores = 0;
						for($i=1; $i<=$info["level"]; $i++)
							$scores += $ress*pow($i, 3);
						$info["scores"] = $scores/1000;
					}

					if($calc["ress"])
					{
						$ress_f = pow($info["level"]+1, 3);
						$info["ress"][0] *= $ress_f;
						$info["ress"][1] *= $ress_f;
						$info["ress"][2] *= $ress_f;
						$info["ress"][3] *= $ress_f;
					}

					if($calc["limit_factor_local"])
					{
						if($info["time_local"] < global_setting("MIN_BUILDING_TIME"))
							$info["limit_factor_local"] = $info["time_local"]/global_setting("MIN_BUILDING_TIME");
						else
							$info["limit_factor_local"] = 1;
					}
					if($calc["limit_factor_global"])
					{
						if($info["time_global"] < global_setting("MIN_BUILDING_TIME"))
							$info["limit_factor_global"] = $info["time_global"]/global_setting("MIN_BUILDING_TIME");
						else
							$info["limit_factor_global"] = 1;
					}

					# Runden
					if($calc["time_local"])
						Functions::stdround($info["time_local"]);
					if($calc["time_global"])
						Functions::stdround($info["time_global"]);
					if($calc["ress"])
					{
						Functions::stdround($info["ress"][0]);
						Functions::stdround($info["ress"][1]);
						Functions::stdround($info["ress"][2]);
						Functions::stdround($info["ress"][3]);
						Functions::stdround($info["ress"][4]);
					}
					break;
				case "roboter":
					if($calc["time"])
						$info["time"] *= pow(0.95, $this->getItemLevel("B9", "gebaeude"));

					if($calc["simple_scores"])
						$info["simple_scores"] = array_sum($info["ress"])/1000;
					if($calc["scores"])
						$info["scores"] = $info["simple_scores"]*$info["level"];

					if($calc["limit_factor"])
					{
						if($info["time"] < global_setting("MIN_BUILDING_TIME"))
							$info["limit_factor"] = $info["time"]/global_setting("MIN_BUILDING_TIME");
						else
							$info["limit_factor"] = 1;
					}

					if($calc["time"])
						Functions::stdround($info["time"]);
					break;
				case "schiffe":
					if($calc["att"])
						$info["att"] *= pow(1.05, $this->getItemLevel("F4", "forschung"));
					if($calc["def"])
						$info["def"] *= pow(1.05, $this->getItemLevel("F5", "forschung"));
					if($calc["trans"])
					{
						$lad_f = pow(1.2, $this->getItemLevel("F11", "forschung"));
						$info["trans"][0] *= $lad_f;
						$info["trans"][1] *= $lad_f;
					}
					if($calc["time"])
						$info["time"] *= pow(0.95, $this->getItemLevel("B10", "gebaeude"));
					if($calc["speed"])
					{
						$info["speed"] *= pow(1.025, $this->getItemLevel("F6", "forschung"));
						$info["speed"] *= pow(1.05, $this->getItemLevel("F7", "forschung"));
						$info["speed"] *= pow(1.5, $this->getItemLevel("F8", "forschung"));
					}

					if($calc["simple_scores"])
						$info["simple_scores"] = array_sum($info["ress"])/1000;
					if($calc["scores"])
						$info["scores"] = $info["simple_scores"]*$info["level"];

					if($calc["limit_factor"])
					{
						if($info["time"] < global_setting("MIN_BUILDING_TIME"))
							$info["limit_factor"] = $info["time"]/global_setting("MIN_BUILDING_TIME");
						else
							$info["limit_factor"] = 1;
					}

					# Runden
					if($calc["att"])
						Functions::stdround($info["att"]);
					if($calc["def"])
						Functions::stdround($info["def"]);
					if($calc["trans"])
					{
						Functions::stdround($info["trans"][0]);
						Functions::stdround($info["trans"][1]);
					}
					if($calc["time"])
						Functions::stdround($info["time"]);
					if($calc["speed"])
						Functions::stdround($info["speed"]);
					break;
				case "verteidigung":
					if($calc["att"])
						$info["att"] *= pow(1.05, $this->getItemLevel("F4", "forschung"));
					if($calc["def"])
						$info["def"] *= pow(1.05, $this->getItemLevel("F5", "forschung"));
					if($calc["time"])
						$info["time"] *= pow(0.95, $this->getItemLevel("B10", "gebaeude"));

					if($calc["simple_scores"])
						$info["simple_scores"] = array_sum($info["ress"])/1000;
					if($calc["scores"])
						$info["scores"] = $info["simple_scores"]*$info["level"];

					if($calc["limit_factor"])
					{
						if($info["time"] < global_setting("MIN_BUILDING_TIME"))
							$info["limit_factor"] = $info["time"]/global_setting("MIN_BUILDING_TIME");
						else
							$info["limit_factor"] = 1;
					}

					if($calc["att"])
						Functions::stdround($info["att"]);
					if($calc["def"])
						Functions::stdround($info["def"]);
					if($calc["time"])
						Functions::stdround($info["time"]);
					break;
			}

			# Mindestbauzeit zwoelf Sekunden aufgrund von Serverbelastung
			if($type == "forschung")
			{
				if($calc["time_local"] && $info["time_local"] < global_setting("MIN_BUILDING_TIME")) $info["time_local"] = global_setting("MIN_BUILDING_TIME");
				if($calc["time_global"] && $info["time_global"] < global_setting("MIN_BUILDING_TIME")) $info["time_global"] = global_setting("MIN_BUILDING_TIME");
			}
			elseif($calc["time"] && $info["time"] < global_setting("MIN_BUILDING_TIME")) $info["time"] = global_setting("MIN_BUILDING_TIME");

			return $info;
		}

		/**
		 * Gibt die aktuelle Ausbaustufe (Gebäude, Forschung) bzw. die Anzahl
		 * (Roboter, Schiffe, Verteidigung) des Items zurück.
		 * @param string $id
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return int
		*/

		function getItemLevel($id, $type=null)
		{
			if($type === false || $type === null)
				$type = Item::getItemType($id);
			if(!isset($this->items[$type]) || !isset($this->items[$type][$id]))
				return 0;
			return $this->items[$type][$id];
		}

		/**
		 * Verändert die Ausbaustufe/Anzahl des Items.
		 * @param string $id
		 * @param int $value Wird zur aktuellen Stufe hinzugezählt
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @param int $time Ausbau geschah zu diesem Zeitpunkt, wichtig für den Eventhandler zum Beispiel bei der Verkürzung der laufenden Bauzeit
		 * @return void
		*/

		function changeItemLevel($id, $value=1, $type=null, $time=null)
		{
			if($value == 0) return;

			if(!isset($time)) $time = time();

			$recalc = array(
				"gebaeude" => 0,
				"forschung" => 1,
				"roboter" => 2,
				"schiffe" => 3,
				"verteidigung" => 4
			);

			if(!isset($type))
				$type = Item::getItemType($id);

			if(!isset($this->items[$type])) $this->items[$type] = array();
			if(isset($this->items[$type][$id])) $this->items[$type][$id] += $value;
			else $this->items[$type][$id] = $value;

			$this->recalc_highscores[$recalc[$type]] = true;

			# Felder belegen
			if($type == "gebaeude")
			{
				$item_info = $this->getItemInfo($id, "gebaeude", array("fields"));
				if($item_info["fields"] > 0)
					$this->changeUsedFields($item_info["fields"]*$value);
			}

			switch($id)
			{
				# Ingeneurswissenschaft: Planeten vergroessern
				case "F9":
					$planets = $this->getPlanetsList();
					$active_planet = $this->getActivePlanet();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$size = ceil($this->getTotalFields()/(($this->getItemLevel("F9", false, false)-$value)/Item::getIngtechFactor()+1));
						$this->setFields(floor($size*($this->getItemLevel("F9", false, false)/Item::getIngtechFactor()+1)));
					}
					$this->setActivePlanet($active_planet);
					break;

				# Bauroboter: Laufende Bauzeit verkuerzen (TODO?)
				/*case "R01":
					$max_rob_limit = floor($this->getBasicFields()/2);
					$counting_after = $this->items[$type][$id];
					$counting_before = $counting_after-$value;
					if($counting_after > $max_rob_limit) $counting_after = $max_rob_limit;
					if($counting_before > $max_rob_limit) $counting_before = $max_rob_limit;
					$counting_value = $counting_after-$counting_before;

					$building = $this->checkBuildingThing("gebaeude");
					if($building && $building[1] > $time)
					{
						$f = pow(1-0.00125*$this->getItemLevel("F2", "forschung", false), $counting_value);
						$old_finished = $building[4][0]-$building[1];
						$old_remaining = ($building[1]-$time)*$building[4][1];
						$new_remaining = $old_remaining*$f;
						if(($old_finished*$f)+$new_remaining < global_setting("MIN_BUILDING_TIME"))
						{
							$this->planet_info["building"]["gebaeude"][4][1] = $new_remaining/(global_setting("MIN_BUILDING_TIME")-($old_finished*$f));
							$new_remaining = global_setting("MIN_BUILDING_TIME")-($old_finished*$f);
						}
						else
							$this->planet_info["building"]["gebaeude"][4][1] = 1;
						$this->planet_info["building"]["gebaeude"][1] = $time+$new_remaining;
					}

					break;*/

				# Roboterbautechnik: Auswirkungen der Bauroboter aendern
				case "F2":
					$planets = $this->getPlanetsList();
					$active_planet = $this->getActivePlanet();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);

						$building = $this->checkBuildingThing("gebaeude");
						$robs = $this->getItemLevel("R01", "roboter", false);
						if($robs > 0 && $building && $building[1] > $time)
						{
							$f_1 = pow(1-0.00125*($this->getItemLevel("F2", false, false)-$value), $robs);
							$f_2 = pow(1-0.00125*$this->getItemLevel("F2", false, false), $robs);
							$remaining = ($building[1]-$time)*$f_2/$f_1;
							$this->raw["building"]["gebaeude"][1] = $time+$remaining;
						}
					}
					$this->setActivePlanet($active_planet);

					break;
			}

			$this->changed = true;
		}

		/**
		 * Gibt Informationen über die im Bau befindlichen Dinge auf diesem Planeten zurück.
		 * Das Rückgabe-Array hat bei Gebäuden und Forschung das folgende Format:
		 * [ Item-ID; Fertigstellungszeitpunkt; Globale Forschung?/Gebäuderückbau?; Verbrauchte Rohstoffe; Planetenindex der globalen Forschung ]
		 * Bei Robotern, Schiffen und Verteidigungsanlagen ist das Format wie folgt:
		 * ( [ Item-ID; Startzeit; Anzahl; Bauzeit pro Stück ] )
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return array
		*/

		function checkBuildingThing($type)
		{
			$this->_forceActivePlanet();

			switch($type)
			{
				case "gebaeude": case "forschung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || trim($this->planet_info["building"][$type][0]) == "")
						return false;
					return $this->planet_info["building"][$type];
				case "roboter": case "schiffe": case "verteidigung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || count($this->planet_info["building"][$type]) <= 0)
						return array();
					return $this->planet_info["building"][$type];
				default: return false;
			}
		}

		/**
		 * Bricht die aktuell im Bau befindlichen Gegenstände des angegebenen Typs ab.
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @param bool $cancel Wenn true, werden die Rohstoffe bei Gebäude und Forschung rückerstattet.
		 * @return void
		*/

		function removeBuildingThing($type, $cancel=true)
		{
			$this->_forceActivePlanet();

			switch($type)
			{
				case "gebaeude": case "forschung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || trim($this->planet_info["building"][$type][0]) == "")
						return;

					if($type == "forschung" && $this->planet_info["building"][$type][2])
					{
						$source_planet = $this->planet_info["building"][$type][4];
						//if(!isset($this->raw["planets"][$source_planet]["building"][$type]) || trim($this->raw["planets"][$source_planet]["building"][$type][0]) == "")
						//	return false;
						$active_planet = $this->getActivePlanet();
						$planets = $this->getPlanetsList();
						foreach($planets as $planet)
						{
							$this->setActivePlanet($planet);
							if($planet == $source_planet && $cancel)
								$this->addRess($this->planet_info["building"][$type][3]);
							if(isset($this->planet_info["building"][$type]))
								unset($this->planet_info["building"][$type]);
						}
						$this->setActivePlanet($active_planet);
					}
					elseif($cancel)
						$this->addRess($this->planet_info["building"][$type][3]);

					if($cancel)
					{
						$this->raw["punkte"][7] -= $this->planet_info["building"][$type][3][0];
						$this->raw["punkte"][8] -= $this->planet_info["building"][$type][3][1];
						$this->raw["punkte"][9] -= $this->planet_info["building"][$type][3][2];
						$this->raw["punkte"][10] -= $this->planet_info["building"][$type][3][3];
						$this->raw["punkte"][11] -= $this->planet_info["building"][$type][3][4];
					}

					unset($this->planet_info["building"][$type]);
					$this->changed = true;

					if($cancel)
						$this->refreshMessengerBuildingNotifications($type);

					break;
				case "roboter": case "schiffe": case "verteidigung":
					if(!isset($this->planet_info["building"]) || !isset($this->planet_info["building"][$type]) || count($this->planet_info["building"][$type]) <= 0)
						return;
					unset($this->planet_info["building"][$type]);
					$this->changed = true;

					if($cancel)
						$this->refreshMessengerBuildingNotifications($type);

					break;
			}
		}

		/**
		 * Eventhandler-Hilfsfunktion: entfernt den nächsten fertigzustellenden Gegenstand und entfernt ihn
		 * @param string $type gebaeude, forschung, roboter, schiffe oder verteidigung
		 * @return array|null [ Zeitpunkt; Item-ID; Ausbaustufen; Rohstoffe nach Fertigstellung aktualisieren? ]
		*/

		function getNextBuiltThing($type)
		{
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

		/**
		 * Stellt alle Gegenstände, die seit der letzten Ausführung fertig geworden sind, fertig.
		 * @return void
		*/

		function eventhandler()
		{
			if($this->umode())
				return;

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
		}

		function buildGebaeude($id, $rueckbau=false)
		{
			$this->_forceActivePlanet();

			if($this->checkBuildingThing("gebaeude")) return false;
			if($id == "B8" && $this->checkBuildingThing("forschung")) return false;
			if($id == "B9" && $this->checkBuildingThing("roboter")) return false;
			if($id == "B10" && ($this->checkBuildingThing("schiffe") || $this->checkBuildingThing("verteidigung"))) return false;

			$item_info = $this->getItemInfo($id, "gebaeude", array("buildable", "debuildable", "ress", "time", "limit_factor"));
			if($item_info && ((!$rueckbau && $item_info["buildable"]) || ($rueckbau && $item_info["debuildable"])))
			{
				# Rohstoffkosten
				$ress = $item_info["ress"];

				if($rueckbau)
				{
					$ress[0] = $ress[0]>>1;
					$ress[1] = $ress[1]>>1;
					$ress[2] = $ress[2]>>1;
					$ress[3] = $ress[3]>>1;
				}

				# Genuegend Rohstoffe zum Ausbau
				if(!$this->checkRess($ress)) return false;

				$time = $item_info["time"];
				if($rueckbau)
					$time = $time>>1;
				$time += time();

				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				$this->planet_info["building"]["gebaeude"] = array($id, $time, $rueckbau, $ress, array(time(), $item_info["limit_factor"]));

				# Rohstoffe abziehen
				$this->subtractRess($ress);

				$this->refreshMessengerBuildingNotifications("gebaeude");

				return true;
			}
			return false;
		}

		function buildForschung($id, $global)
		{
			$this->_forceActivePlanet();

			if($this->checkBuildingThing("forschung")) return false;
			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B8") return false;

			$buildable = true;
			$planets = $this->getPlanetsList();
			$active_planet = $this->getActivePlanet();
			foreach($planets as $planet)
			{
				$this->setActivePlanet($planet);
				if(($global && $this->checkBuildingThing("forschung")) || (!$global && ($building = $this->checkBuildingThing("forschung")) && $building[0] == $id))
				{
					$buildable = false;
					break;
				}
			}
			$this->setActivePlanet($active_planet);

			$item_info = $this->getItemInfo($id, "forschung", array("buildable", "ress", "time_global", "time_local"));
			if($item_info && $item_info["buildable"] && $this->checkRess($item_info["ress"]))
			{
				$build_array = array($id, time()+$item_info["time_".($global ? "global" : "local")], $global, $item_info["ress"]);
				if($global)
				{
					$build_array[] = $this->getActivePlanet();

					$planets = $this->getPlanetsList();
					foreach($planets as $planet)
					{
						$this->setActivePlanet($planet);
						$this->planet_info["building"]["forschung"] = $build_array;
					}
					$this->setActivePlanet($active_planet);
				}
				else $this->planet_info["building"]["forschung"] = $build_array;

				$this->subtractRess($item_info["ress"]);

				$this->refreshMessengerBuildingNotifications("forschung");

				$this->changed = true;

				return true;
			}
			return false;
		}

		function buildRoboter($id, $anzahl)
		{
			$this->_forceActivePlanet();

			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B9") return false;

			$item_info = $this->getItemInfo($id, "roboter", array("buildable", "ress", "time"));
			if(!$item_info || !$item_info["buildable"]) return false;

			$ress = $item_info["ress"];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;

			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info["ress"];
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

			$roboter = $this->checkBuildingThing("roboter");
			$make_new = true;
			$last_time = time();
			if($roboter && count($roboter) > 0)
			{
				$roboter_keys = array_keys($this->planet_info["building"]["roboter"]);
				$last = &$this->planet_info["building"]["roboter"][array_pop($roboter_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info["time"])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				if(!isset($this->planet_info["building"]["roboter"])) $this->planet_info["building"]["roboter"] = array();
				$build_array = &$this->planet_info["building"]["roboter"][];
				$build_array = array($id, $last_time, 0, $item_info["time"]);
			}

			$build_array[2] += $anzahl;

			$this->subtractRess($ress);

			$this->refreshMessengerBuildingNotifications("roboter");

			$this->changed = true;

			return true;
		}

		function buildSchiffe($id, $anzahl)
		{
			$this->_forceActivePlanet();

			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B10") return false;

			$item_info = $this->getItemInfo($id, "schiffe", array("buildable", "ress", "time"));
			if(!$item_info || !$item_info["buildable"]) return false;

			$ress = $item_info["ress"];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;

			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info["ress"];
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

			$schiffe = $this->checkBuildingThing("schiffe");
			$make_new = true;
			$last_time = time();
			if($schiffe && count($schiffe) > 0)
			{
				$schiffe_keys = array_keys($this->planet_info["building"]["schiffe"]);
				$last = &$this->planet_info["building"]["schiffe"][array_pop($schiffe_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info["time"])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				if(!isset($this->planet_info["building"]["schiffe"])) $this->planet_info["building"]["schiffe"] = array();
				$build_array = &$this->planet_info["building"]["schiffe"][];
				$build_array = array($id, $last_time, 0, $item_info["time"]);
			}

			$build_array[2] += $anzahl;

			$this->subtractRess($ress);

			$this->refreshMessengerBuildingNotifications("schiffe");

			$this->changed = true;

			return true;
		}

		function buildVerteidigung($id, $anzahl)
		{
			$this->_forceActivePlanet();

			$anzahl = floor($anzahl);
			if($anzahl < 0) return false;

			if(($gebaeude = $this->checkBuildingThing("gebaeude")) && $gebaeude[0] == "B10") return false;

			$item_info = $this->getItemInfo($id, "verteidigung", array("buildable", "ress", "time"));
			if(!$item_info || !$item_info["buildable"]) return false;

			$ress = $item_info["ress"];
			$ress[0] *= $anzahl;
			$ress[1] *= $anzahl;
			$ress[2] *= $anzahl;
			$ress[3] *= $anzahl;

			if(!$this->checkRess($ress))
			{
				$planet_ress = $this->getRess();
				$ress = $item_info["ress"];
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

			$verteidigung = $this->checkBuildingThing("verteidigung");
			$make_new = true;
			$last_time = time();
			if($verteidigung && count($verteidigung) > 0)
			{
				$verteidigung_keys = array_keys($this->planet_info["building"]["verteidigung"]);
				$last = &$this->planet_info["building"]["verteidigung"][array_pop($verteidigung_keys)];
				$last_time = $last[1]+$last[2]*$last[3];
				if($last[0] == $id && $last[3] == $item_info["time"])
				{
					$build_array = &$last;
					$make_new = false;
				}
			}
			if($make_new)
			{
				if(!isset($this->planet_info["building"])) $this->planet_info["building"] = array();
				if(!isset($this->planet_info["building"]["verteidigung"])) $this->planet_info["building"]["verteidigung"] = array();
				$build_array = &$this->planet_info["building"]["verteidigung"][];
				$build_array = array($id, $last_time, 0, $item_info["time"]);
			}

			$build_array[2] += $anzahl;

			$this->subtractRess($ress);

			$this->refreshMessengerBuildingNotifications("verteidigung");

			$this->changed = true;

			return true;
		}

		/**
		 * Erneuert die Benachrichtungen fertiggestellter Gegenstände.
		 * @param string $type gebaeude, forschung, roboter, schiffe, verteidigung
		 * @return void
		*/

		function refreshMessengerBuildingNotifications($type=false)
		{
			$this->_forceActivePlanet();

			if($type == false)
			{
				return ($this->refreshMessengerBuildingNotifications("gebaeude")
				&& $this->refreshMessengerBuildingNotifications("forschung")
				&& $this->refreshMessengerBuildingNotifications("roboter")
				&& $this->refreshMessengerBuildingNotifications("schiffe")
				&& $this->refreshMessengerBuildingNotifications("verteidigung"));
			}

			if(!in_array($type, array("gebaeude", "forschung", "roboter", "schiffe", "verteidigung")))
				return;

			$special_id = $this->getActivePlanet()."-".$type;
			$imfile = Classes::IMFile();
			$imfile->removeMessages($this->getName(), $special_id);

			$reload_stack = Classes::ReloadStack();
			$reload_stack->reset($this->getName(), $special_id);

			$building = $this->checkBuildingThing($type);
			if(!$building) return 2;

			$messenger_receive = $this->checkSetting("messenger_receive");
			$messenger_settings = $this->getNotificationType();
			$add_message = ($messenger_settings && $messenger_receive["building"][$type]);

			$planet_prefix = "(".$this->planetName().", ".$this->getPosString().") ";

			switch($type)
			{
				case "gebaeude": case "forschung":
					if(!$building || ($type == "forschung" && $building[2] && $this->getActivePlanet() != $building[4]))
						break;

					if($add_message)
					{
						$item_info = $this->getItemInfo($building[0], $type, array("name", "level"));

						if($type == "gebaeude")
							$message = $planet_prefix."Gebäudebau abgeschlossen: ".$item_info["name"]." (".($item_info["level"]+($building[2] ? -1 : 1)).")";
						else
							$message = $planet_prefix."Forschung fertiggestellt: ".$item_info["name"]." (".($item_info["level"]+1).")";
						$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $message, $special_id, $building[1]);
					}
					break;
				case "roboter": case "schiffe": case "verteidigung":
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
							case "roboter": $singular = "Roboter"; $plural = "Roboter"; $art = "ein"; break;
							case "schiffe": $singular = "Schiff"; $plural = "Schiffe"; $art = "ein"; break;
							case "verteidigung": $singular = "Verteidigungsanlage"; $plural = "Verteidigungsanlagen"; $art = "eine"; break;
						}

						switch($messenger_receive["building"][$type])
						{
							case 1:
								foreach($building as $b)
								{
									$item_info = $this->getItemInfo($b[0], $type, array("name"));
									$time = $b[1];
									for($i=0; $i<$b[2]; $i++)
									{
										$time += $b[3];
										$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $planet_prefix.ucfirst($art)." ".$singular." der Sorte ".$item_info["name"]." wurde fertiggestellt.", $special_id, $time);
									}
								}
								break;
							case 2:
								foreach($building as $b)
								{
									$item_info = $this->getItemInfo($b[0], $type, array("name"));
									$imfile->addMessage($messenger_settings[0], $messenger_settings[1], $this->getName(), $planet_prefix.$b[2]." ".($b[2]==1 ? $singular : $plural)." der Sorte ".$item_info["name"]." ".($b[2]==1 ? "wurde" : "wurden")." fertiggestellt.", $special_id, $b[1]+$b[2]*$b[3]);
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
		}

/*************************************
*** Verbündete ***
*************************************/

		/**
		 * Gibt zurück, ob dieser Benutzer mit dem übergebenen verbündet ist.
		 * @param string $user
		 * @return bool
		*/

		function isVerbuendet($user)
		{
			if($user == $this->getName()) return true;
			return self::$sqlite->singleField("SELECT COUNT(*) FROM users_friends WHERE ( user1 = ".self::$sqlite->quote($user)." AND user2 = ".self::$sqlite->quote($this->getName())." ) OR ( user1 = ".self::$sqlite->quote($this->getName())." AND user2 = ".self::$sqlite->quote($user)." );") > 0;
		}

		/**
		 * Gibt zurück, ob ein Benutzer diesem Benutzer eine Bündnisanfrage stellt.
		 * @param string $user
		 * @return bool
		*/

		function isApplying($user)
		{
			return self::$sqlite->singleField("SELECT COUNT(*) FROM users_friend_requests WHERE user_from = ".self::$sqlite->quote($user)." AND user_to = ".self::$sqlite->quote($this->getName()).";") > 0;
		}

		/**
		 * Gibt zurück, ob eine Beziehung zu dem Benutzer vorliegt, entweder dadurch, dass er verbündet ist,
		 * oder dadurch, dass eine Bündnisanfrage von einem Benutzer zum anderen vorliegt.
		 * @param string $user
		 * @return bool
		*/

		function existsVerbuendet($user)
		{
			if($this->isVerbuendet($user))
				return true;

			return self::$sqlite->singleField("SELECT COUNT(*) FROM users_friend_requests WHERE ( user_from = ".self::$sqlite->quote($user)." AND user_to = ".self::$sqlite->quote($this->getName())." ) OR ( user_from = ".self::$sqlite->quote($this->getName())." AND user_to = ".self::$sqlite->quote($user)." );") > 0;
		}

		/**
		 * Gibt eine Liste der verbündeten Spieler zurück.
		 * @return array(string)
		*/

		function getVerbuendetList()
		{
			return self::$sqlite->columnQuery("SELECT user1 FROM users_friends WHERE user2 = ".self::$sqlite->quote($this->getName())." UNION SELECT user2 FROM users_friends WHERE user1 = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Gibt eine Liste der Spieler, zu denen dieser eine Bündnisanfrage am Laufen hat, zurück.
		 * @return array(string)
		*/

		function getVerbuendetApplicationList()
		{
			return self::$sqlite->columnQuery("SELECT user_to FROM users_friend_requests WHERE user_from = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Gibt eine Liste der Spieler, die an diesem Benutzer eine Bündnisanfrage stellen, zurück.
		 * @return array(string)
		*/

		function getVerbuendetRequestList()
		{
			return self::$sqlite->columnQuery("SELECT user_from FROM users_friend_requests WHERE user_to = ".self::$sqlite->quote($this->getName()).";");
		}

		/**
		 * Ein Spieler bewirbt sich um ein Bündnis.
		 * @param string $user Der bewerbende Benutzer.
		 * @param string $text Eventueller Bewerbungstext
		 * @return void
		*/

		function applyVerbuendet($user, $text="")
		{
			if($this->isApplying($user))
			{
				$this->acceptVerbuendetApplication($user);
				return;
			}
			elseif($this->existsVerbuendet($user))
				throw new UserException("These users are already friends.");

			self::$sqlite->query("INSERT INTO users_friends ( user_from, user_to ) VALUES ( ".self::$sqlite->quote($user).", ".self::$sqlite->quote($this->getName())." );");

			$message = Classes::Message(Message::create());
			$message->addUser($this->getName(), Message::TYPE_VERBUENDETE);
			$message->subject($this->_("Anfrage auf ein Bündnis"));
			$message->from($user);
			if(trim($text) == "")
				$message->text(sprintf($this->_("Der Spieler %s hat Ihnen eine mitteilungslose Bündnisanfrage gestellt."), $user));
			else
				$message->text($text);
		}

		/**
		 * Nimmt eine Bündnisanfrage an.
		 * @param string $user Der stellende Benutzer.
		 * @return void
		*/

		function acceptVerbuendetApplication($user)
		{
			if(!$this->isApplying($user))
				throw new UserException("This user is not applying for a friendship.");

			self::$sqlite->query("DELETE FROM users_friend_requests WHERE user_from = ".self::$sqlite->quote($user)." AND user_to = ".self::$sqlite->quote($this->getName()).";");
			self::$sqlite->query("INSERT INTO users_friends ( user1, user2 ) VALUES ( ".self::$sqlite->quote($user).", ".self::$sqlite->quote($this->getName())." );");

			$user_obj = Classes::User($user);
			$message = Classes::Message(Message::create());
			$message->from($this->getName());
			$message->subject($user_obj->_("Bündnisanfrage angenommen"));
			$message->text(sprintf($user_obj->_("Der Spieler %s hat Ihre Bündnisanfrage angenommen."), $this->getName()));
			$message->addUser($user, Message::TYPE_VERBUENDETE);
		}

		/**
		 * Weist eine Bündisanfrage zurück.
		 * @param string $user Der stellende Benutzer.
		 * @return void
		*/

		function rejectVerbuendetApplication($user)
		{
			if(!$this->isApplying($user))
				throw new UserException("This user is not applying for a friendship.");

			self::$sqlite->query("DELETE FROM users_friend_requests WHERE user_from = ".self::$sqlite->quote($user)." AND user_to = ".self::$sqlite->quote($this->getName()).";");

			$user_obj = Classes::User($user);
			$message = Classes::Message(Message::create());
			$message->from($this->getName());
			$message->subject($user_obj->_("Bündnisanfrage abgelehnt"));
			$message->text(sprintf($user_obj->_("Der Spieler %s hat Ihre Bündnisanfrage abgelehnt."), $this->getName()));
			$message->addUser($user, Message::TYPE_VERBUENDETE);
		}

		/**
		 * Kündigt das Bündnis zum Benutzer $user.
		 * @param string $user
		 * @return void
		*/

		function quitVerbuendet($user)
		{
			if(!$this->isVerbuendet($user))
				throw new UserException("These users are not friends.");

			self::$sqlite->query("DELETE FROM users_friends WHERE ( user1 = ".self::$sqlite->quote($this->getName())." AND user2 = ".self::$sqlite->quote($user)." ) OR ( user1 = ".self::$sqlite->quote($user)." AND user2 = ".self::$sqlite->quote($this->getName())." );");

			$user_obj = Classes::User($user);
			$message = Classes::Message(Message::create());
			$message->from($this->getName());
			$message->subject($user_obj->_("Bündnis gekündigt"));
			$message->text(sprintf($user_obj->_("Der Spieler %s hat sein Bündnis mit Ihnen gekündigt."), $this->getName()));
			$message->addUser($user, Message::TYPE_VERBUENDETE);

			# Fremdstationierte Flotten zurueckholen
			$this->cacheActivePlanet();
			$user_obj->cacheActivePlanet();

			foreach($user_obj->getPlanetsList() as $planet)
			{
				$user_obj->setActivePlanet($planet);
				if(count($user_obj->getForeignFleetsList($this->getName())) > 0)
					$this->callBackForeignFleet($user_obj->getPosObj());
			}

			foreach($this->getPlanetsList() as $planet)
			{
				$this->setActivePlanet($planet);
				if(count($this->getForeignFleetsList($user)) > 0)
					$user_obj->callBackForeignFleet($this->getPosObj());
			}

			$this->restoreActivePlanet();
			$user_obj->restoreActivePlanet();
		}

		/**
		 * Der Benutzer $user zieht seine Bündnisanfrage zurück.
		 * @param string $user Der stellende Benutzer.
		 * @return void
		*/

		function cancelVerbuendetApplication($user)
		{
			if(!$this->isApplying($user))
				throw new UserException("This user is not applying.");

			$message = Classes::Message(Message::create());
			$message->from($user);
			$message->subject($this->_("Bündnisanfrage zurückgezogen"));
			$message->text(sprintf($this->_("Der Spieler %s hat seine Bündnisanfrage an Sie zurückgezogen."), $user));
			$message->addUser($this->getName(), Message::TYPE_VERBUENDETE);
		}

		/**
		 * Liefert zurück, ob dieser Benutzer die Koordinaten der Planeten des Benutzers $user sehen darf.
		 * Dies ist der Fall, wenn er mit dem Benutzer verbündet ist oder wenn beide in derselben Allianz
		 * sind, die ihm dies erlaubt.
		 * @param string $user
		 * @return bool
		*/

		function maySeeKoords($user)
		{
			if($user == $this->getName()) return true;
			if($this->isVerbuendet($user)) return true;

			$tag = Alliance::userAlliance($this->getName());
			if($tag !== false)
			{
				$alliance = Classes::Alliance($tag);
				if(!$alliance->checkUserPermissions($this->getName(), 1)) return false;
				if(!in_array($user, $alliance->getUsersList())) return false;
				return true;
			}
		}
	}
