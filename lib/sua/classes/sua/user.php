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
	 * Repräsentiert einen Benutzer im Spiel.
	 * Der Benutzeraccount stellt Funktionen zur Verfügung, um mit Item-Informationen
	 * umzugehen. Auf diese Weise wird es theoretisch möglich, unterschiedliche „Völker“
	 * zu implementieren, also dass es für unterschiedliche Benutzer unterschiedliche
	 * Items zu bauen gibt, die unterschiedliche Eigenschaften besitzen. Bei allen nicht-
	 * theoretischen Iteminformationen, also bei all denen, die sich auf konkrete gebaute
	 * Gegenstände, die einem Benutzer gehören, handelt, sollten also die Item-Funktionen
	 * der User-Klasse verwendet werden, in Hinblick auf eine etwaige Implementierung von
	 * Völkern.
	 * @todo Überlegen: Eventhandler muss auf allen Planeten ausgeführt werden, da Forschungen auf einem Planeten alle anderen beeinflussen
	 * @todo setActivePlanet scheint noch verwendet zu werden
	*/

	class User extends SQLSet implements \Iterator,StaticInit
	{
		/** Gebäudepunkte */
		const SCORES_GEBAEUDE = "c_gebaeude";

		/** Forschungspunkte */
		const SCORES_FORSCHUNG = "c_forschung";

		/** Roboterpunkte */
		const SCORES_ROBOTER = "c_roboter";

		/** Flottenpunkte */
		const SCORES_SCHIFFE = "c_schiffe";

		/** Verteidigungspunkte */
		const SCORES_VERTEIDIGUNG = "c_verteidigung";

		/** Flugerfahrungspunkte */
		const SCORES_FLUGERFAHRUNG = "c_flightexp";

		/** Kampferfahrungspunkte */
		const SCORES_KAMPFERFAHRUNG = "c_battleexp";

		/** Gesamtpunktzahl */
		const SCORES_TOTAL = "c_total";

		/**
		 * Für die Iterator-Funktion.
		 * @var array(Planet)
		*/
		private $planets = null;

		/**
		 * Gecachte Spracheinstellung für User->setLanguage() und User->restoreLanguage().
		 * @var array(string)
		*/
		protected $language_cache = array();

		protected static $primary_key = array("t_users", "c_user");

		static function init()
		{
			__autoload("\sua\Planet"); // Die View oben hängt von den Tabellen der Planet-Klasse ab
			parent::init();
		}

		/**
		 * Implementiert Dataset::create().
		 * @return string
		*/

		static function create($name=null)
		{
			$name = self::datasetName($name);
			if(self::exists($name))
				throw new UserException("This user does already exist.");

			self::$sql->query("INSERT INTO t_users ( c_user, c_registration ) VALUES ( ".self::$sql->quote($name).", ".self::$sql->quote(Classes::Date())." );");
			self::$sql->query("INSERT INTO t_users_settings ( c_user, c_setting, c_value ) VALUES ( ".self::$sql->quote($name).", ".self::$sql->quote("lang").", ".self::$sql->quote(serialize(L::language()))." );");

			return $name;
		}

		function destroy()
		{
			# Planeten zuruecksetzen
			foreach(Planet::getPlanetsByUser($this->getName()) as $planet)
				$planet->decolonise();

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
			$tag = Alliance::userAlliance($this->getName());
			if($tag)
				Classes::Alliance($tag)->quitUser($this->getName());

			# Flotten zurueckrufen
			$fleets = Fleet::visibleToUser($this->getName());
			foreach($fleets as $fleet)
			{
				$fleet_obj = Classes::Fleet($fleet);
				foreach(array_reverse($fleet_obj->getUsersList()) as $username)
					$fleet_obj->callBack($username);
			}

			# Aus der Datenbank entfernen
			self::$sql->beginTransaction();
			self::$sql->transactionQuery("DELETE FROM t_users WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("DELETE FROM t_users_research WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("DELETE FROM t_users_friends WHERE c_user1 = ".self::$sql->quote($this->getName())." OR c_user2 = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("DELETE FROM t_users_friend_requests WHERE c_user_from = ".self::$sql->quote($this->getName())." OR c_user_to = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("DELETE FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("DELETE FROM t_users_settings WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("DELETE FROM t_users_email WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->endTransaction();
		}

/*********************************************
*** Account ***
*********************************************/

		/**
		 * Benennt den Benutzer um.
		 * @param string $new_name
		 * @return void
		*/

		function rename($new_name)
		{
			/*Planet::renameUser($this->getName(), $new_name);
			Message::renameUser($this->getName(), $new_name);
			Fleet::renameUser($this->getName(), $new_name);
			Alliance::renameUser($this->getName(), $new_name);
			IMServer::renameUser($this->getName(), $new_name);

			self::$sql->beginTransaction();*/
			self::$sql->transactionQuery("UPDATE t_users SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($this->getName()).";");
			/*self::$sql->transactionQuery("UPDATE t_users_research SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_friends SET c_user1 = ".self::$sql->quote($new_name)." WHERE c_user1 = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_friends SET c_user2 = ".self::$sql->quote($new_name)." WHERE c_user2 = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_friend_requests SET c_user_from = ".self::$sql->quote($new_name)." WHERE c_user_from = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_friend_requests SET c_user_to = ".self::$sql->quote($new_name)." WHERE c_user_to = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_shortcuts SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_settings SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->transactionQuery("UPDATE t_users_email SET c_user = ".self::$sql->quote($new_name)." WHERE c_user = ".self::$sql->quote($this->getName()).";");
			self::$sql->endTransaction();*/
		}

		/**
		 * Benachrichtigt oder löscht alle inaktiven Benutzer.
		 * @global $user_inactivity
		 * @return int Die Anzahl der gelöschten Nutzer.
		*/

		static function cleanUp()
		{
			global $user_inactivity;

			$inact = array(array_reverse($user_inactivity[0]), array_reverse($user_inactivity[1]));
			$inact_delete = array(array_shift($inact[0]), array_shift($inact[1]));

			$i = 0;
			self::$sql->query("SELECT c_user,c_last_activity,c_holidays FROM t_users WHERE c_last_inactivity_mail != ".self::$sql->quote(date("Y-m"))." AND ( ( NOT c_holidays AND c_last_activity < ".self::$sql->query(time()-$user_inactivity[0][0]*86400)." ) OR ( c_holidays AND c_last_activity < ".self::$sql->query(time()-$user_inactivity[1][0]*86400)." ) );");
			while(($r = self::$sql->nextResult()) !== false)
			{
				$user_obj = Classes::User($r["c_user"]);
				if($r["c_last_activity"]-time() > $inact_delete[$r["c_holidays"] ? 1 : 0]*86400)
				{ # Benutzer löschen
					$user_obj->destroy();
					$i++;
				}
				elseif(in_array(floor(($r["c_last_activity"]-time())/86400), $inact[$r["c_holidays"] ? 1 : 0]))
				{
					$user_obj->setLanguage();
					$user_obj->_sendMail(sprintf(_("Inaktivität in %s"), _("[title_full]")), sprintf(_("Sie erhalten diese Nachricht, weil Sie sich seit geraumer Zeit nicht mehr in %s in %s angemeldet haben. Sie haben bis zum %s Zeit, sich anzumelden, danach wird Ihr Account einer automatischen Löschung unterzogen.\n\nDas Spiel erreichen Sie unter %s – Ihr Benutzername lautet %s."), _("[title_full]"), self::getDatabase()->getTitle(), date(_("Y-m-d"), $r["c_last_activity"]+$inact_delete[$r["umode"] ? 1 : 0]*86400), "http://".Config::getDefaultHostname()."/", $user_obj->getName()));
					$user_obj->restoreLanguage();
					self::$sql->backgroundQuery("UPDATE t_users SET c_last_inactivity_mail = ".self::$sql->quote(date("Y-m"))." WHERE c_user = ".self::$sql->quote($r["c_user"]).";");
				}
			}
			return $i;
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
			return sha1($password) == $this->getMainField("c_password_sha1");
		}

		/**
		 * Setzt das Passwort dieses Benutzers neu.
		 * @param string $password
		 * @return void
		*/

		function setPassword($password)
		{
			$this->setMainField("c_password_sha1", sha1($password));
		}

		/**
		 * Gibt zurück, ob ein Passwort gesetzt ist. Wenn kein Passwort gesetzt ist, kann nur über
		 * OpenID authentifiziert werden.
		 * @return boolean
		*/

		function passwordIsSet()
		{
			return preg_match("/^[a-z0-9]{32}\$/", $this->getMainField("c_password_sha1"));
		}

		/**
		 * Gibt den Benutzernamen zurück, zu dem die OpenID gehört.
		 * @param string $openid
		 * @return string|null
		*/

		static function getUserByOpenId($openid)
		{
			$openid = self::$sql->singleField("SELECT c_user FROM t_users_openid WHERE c_openid = ".self::$sql->quote($openid)." LIMIT 1;");
			if(!$openid)
				return null;
			return $openid;
		}

		/**
		 * Fügt eine OpenID zum Benutzeraccount hinzu. Bevor dies durchgeführt wird, sollte authentifiziert werden.
		 * @param string $openid
		 * @return void
		 * @throw UserException Die OpenId wird bereits verwendet.
		*/

		function addOpenId($openid)
		{
			if(self::getUserByOpenId($openid))
				throw new UserException("This OpenID is already used by another account.");
			self::$sql->backgroundQuery("INSERT INTO t_users_openid ( c_user, c_openid ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($openid)." );");
		}

		/**
		 * Gibt alle gespeicherten OpenIDs dieses Benutzer zurück.
		 * @return array
		*/

		function getOpenIds()
		{
			return self::$sql->singleColumn("SELECT c_openid FROM t_users_openid WHERE c_user = ".self::$sql->quote($this->getName()).";");
		}

		/**
		 * Entfernt eine OpenID aus dem Benutzeraccount.
		 * @param string $openid
		 * @return void
		 * @throw UserException Die OpenId existiert nicht in diesem Benutzeraccount.
		 * @throw UserException Dies ist die einzige OpenId und es ist kein Passwort gesetzt.
		*/

		function removeOpenId($openid)
		{
			$openids = $this->getOpenIds();
			if(!in_array($openid, $openids))
				throw new UserException("This OpenID is not assigned to this user.");
			if(count($openids) < 2 && !$this->passwordIsSet())
				throw new UserException("This OpenID may not be removed unless a password is set.");
			self::$sql->backgroundQuery("DELETE FROM t_users_openid WHERE c_user = ".self::$sql->quote($this->getName())." AND c_openid = ".self::$sql->quote($openid).";");
		}

		/**
		 * Gibt die Benutzereinstellung mit der ID $setting zurück.
		 * @param string $setting
		 * @return mixed
		*/

		function checkSetting($setting)
		{
			try { return $this->getCacheValue(array("checkSetting", $setting)); }
			catch(DatasetException $e) { }

			$value = self::$sql->singleField("SELECT c_value FROM t_users_settings WHERE c_user = ".self::$sql->quote($this->getName())." AND c_setting = ".self::$sql->quote($setting)." LIMIT 1;");
			if($value === false)
			{
				switch($setting)
				{
					case "skin":
					case "fastbuild":
					case "shortcuts":
					case "tooltips":
					case "noads":
					case "show_extern":
					case "notify":
					case "extended_buildings":
					case "fastbuild_full":
					case "fingerprint":
					case "gpg_im":
						$ret = false;
						break;
					case "schrift":
						$ret = true;
						break;
					case "receive":
						$ret = array(
							1 => array(true, true),
							2 => array(true, false),
							3 => array(true, false),
							4 => array(true, true),
							5 => array(true, false)
						);
						break;
					case "show_building":
						$ret = array(
							"gebaeude" => 1,
							"forschung" => 1,
							"roboter" => 0,
							"schiffe" => 0,
							"verteidigung" => 0
						);
						break;
					case "prod_show_days":
						$ret = 1;
						break;
					case "messenger_receive":
						$ret = array(
							"messages" => array(1=>true, 2=>true, 3=>true, 4=>true, 5=>true, 6=>true, 7=>true),
							"building" => array("gebaeude" => 1, "forschung" => 1, "roboter" => 3, "schiffe" => 3, "verteidigung" => 3)
						);
						break;
					case "lang":
						$ret = L::language();
						break;
					case "timezone":
						$ret = date_default_timezone_get();
						break;
					default:
						$ret = null;
						break;
				}
			}
			else
				$ret = unserialize($value);
			$this->setCacheValue(array("checkSetting", $setting), $ret);
			return $ret;
		}

		/**
		 * Setzt die Benutzereinstellung $setting neu.
		 * @param string $setting
		 * @param mixed $value
		 * @return void
		*/

		function setSetting($setting, $value)
		{
			if(self::$sql->singleField("SELECT COUNT(*) FROM t_users_settings WHERE c_user = ".self::$sql->quote($this->getName())." AND c_setting = ".self::$sql->quote($setting)." LIMIT 1;") > 0)
				self::$sql->backgroundQuery("UPDATE t_users_settings SET c_value = ".self::$sql->quote(serialize($value))." WHERE c_user = ".self::$sql->quote($this->getName())." AND c_setting = ".self::$sql->quote($setting)." LIMIT 1;");
			else
				self::$sql->backgroundQuery("INSERT INTO t_users_settings ( c_user, c_setting, c_value ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($setting).", ".self::$sql->quote(serialize($value))." );");
			$this->setCacheValue(array("checkSetting", $setting), $value);
		}

		/**
		 * Gibt die Benutzerbeschreibung dieses Benutzers zurück.
		 * @param bool $parsed Soll die Beschreibung zur HTML-Ausgabe zurückgegeben werden?
		 * @return string
		*/

		function getUserDescription($parsed=true)
		{
			if(!$parsed)
				return $this->getMainField("c_description");

			$parsed = $this->getMainField("c_description_parsed");
			if(!$parsed)
			{
				$unparsed = $this->getMainField("c_description");
				if($unparsed)
				{
					$parsed = FormattedString::parseHTML($unparsed);
					$this->setMainField("c_description_parsed", $parsed);
				}
			}
			return $parsed;
		}

		/**
		 * Setzt die Benutzerbeschreibung dieses Benutzers neu.
		 * @param string $description
		 * @return void
		*/

		function setUserDescription($description)
		{
			$this->setMainField("c_description", $description);
			$this->setMainField("c_description_parsed", FormattedString::parseHTML($description));
		}

		/**
		 * Liest die letzte aufgerufene URL des Benutzers. ($_SERVER["REQUEST_URI"])
		 * @return string
		*/

		function lastRequest()
		{
			return $this->getMainField("c_last_request_uri");
		}

		/**
		 * Erneuert den Zeitpunkt der letzten Aktivität und setzt die letzte URL
		 * auf die aktuelle.
		 * @param string $url Die URL, relativ zum HROOT des Spiels
		 * @return void
		*/

		function registerAction($url=null)
		{
			if(isset($url))
				$this->setMainField("c_last_request_uri", $url);
			$this->setMainField("c_last_activity", Classes::Date());
		}

		/**
		 * Gibt den Zeitpunkt der letzten Aktivität zurück.
		 * @return int
		*/

		function getLastActivity()
		{
			return $this->getMainField("c_last_activity");
		}

		/**
		 * Gibt den Zeitpunkt der Registrierung zurück.
		 * @return int
		*/

		function getRegistrationTime()
		{
			return $this->getMainField("c_registration");
		}

		/**
		 * Gibt zurück, ob dieser Benutzer gesperrt ist.
		 * @return bool
		*/

		function userLocked()
		{
			$locked = $this->getMainField("c_locked");
			if($locked == -1)
				return true;
			elseif($locked >= time())
				return true;
			return false;
		}

		/**
		 * Gibt bei einer Sperre zurück, bis wann diese noch gilt.
		 * @return int -1, wenn sie unendlich ist.
		*/

		function lockedUntil()
		{
			return $this->getMainField("c_locked");
		}

		/**
		 * Sperrt oder entsperrt den Benutzer.
		 * @param bool $lock_time -1 für unendlich, 0 für entsperren, Zeitstempel für Entsperrdatum
		 * @return void
		*/

		function lockUser($lock_time=-1)
		{
			$this->setMainField("c_locked", $lock_time);
		}

		/**
		 * Gibt zurück, ob sich der Spieler im Urlaubsmodus befindet oder setzt
		 * diesen Zustand. Friert alles, was derzeit läuft, ein oder taut es auf.
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
				{ // Urlaubsmodus wird beendet. Die Fertigstellungszeiten aller Dinge im Bau nach hinten verschieben
					$time_diff = time()-$this->getMainField("c_holidays_changed");
					foreach(Planet::getPlanetsByUser($this->getName()) as $planet)
						$planet->_delayBuildingThings($time_diff);

					foreach(Fleet::visibleToUser($this->getName()) as $fleet)
					{
						$fleet_obj = Classes::Fleet($fleet);
						$fleet_obj->moveTime($time_diff);
					}
				}

				$this->setMainField("c_holidays", $set ? 1 : 0);
				$this->setMainField("c_holidays_changed", time());
			}

			return (true && $this->getMainField("c_holidays"));
		}

		/**
		 * Überprüft, ob der Urlaubsmodus derzeit möglich ist. Das ist nur der Fall,
		 * wenn keine eigenen Flotten zu fremden Planeten unterwegs sind.
		 * @return bool
		*/

		function umodePossible()
		{
			return Fleet::ownFleetsToForeignPlanets($this->getName()) < 1;
		}

		/**
		 * Überprüft, ob der Benutzer die Erlaubnis hat, in/aus den Urlaubsmodus zu
		 * gehen, also ob die Mindestzeit seit dem letzten Statuswechsel abgelaufen
		 * ist.
		 * @return bool
		*/

		function permissionToUmode()
		{
			$holidays_change = $this->getMainField("c_holidays_changed");
			if(!$holidays_change) return true;

			if($this->umode())
				return time()-$holidays_change > Config::getLibConfig()->getConfigValue("users", "holidays_days_before_return")*86400;
			else
				return time()-$holidays_change > Config::getLibConfig()->getConfigValue("users", "holidays_days_before_reenter")*86400;
		}

		/**
		 * Falls sich der Benutzer im Urlaubsmodus befindet, gibt diese Funktion
		 * den Zeitpunkt zurück, zu dem er ihn betreten hat. Ansonsten wird null
		 * zurückgeliefert.
		 * @return int|null
		*/

		function getUmodeEnteringTime()
		{
			if(!$this->umode()) return null;
			$change = $this->getMainField("c_holidays_changed");
			if(!$change) return null;
			return $change;
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
			if($this->umode())
				return $this->getUmodeEnteringTime()+Config::getLibConfig()->getConfigValue("users", "holidays_days_before_return")*86400;
			else
				return time()+Config::getLibConfig()->getConfigValue("users", "holidays_days_before_return")*86400;
		}

		/**
		 * Gibt zurück, ob der Benutzer Dinge bauen und Flotten verschicken darf, was
		 * nicht der Fall ist, wenn er gesperrt ist, die Datenbank gesperrt ist oder
		 * er sich im Urlaubsmodus befindet.
		 * @return bool
		*/

		function permissionToAct()
		{
			return !self::getDatabase()->getConfig()->getConfigValue("c_locked") && !$this->userLocked() && !$this->umode();
		}

		/**
		 * Fügt ein Lesezeichen hinzu.
		 * @param Planet $pos
		 * @return void
		*/

		function addPosShortcut(Planet $pos)
		{
			if(self::$sql->singleField("SELECT COUNT(*) FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." AND c_galaxy = ".self::$sql->quote($pos->getGalaxy())." AND c_system = ".self::$sql->quote($pos->getSystem())." AND c_planet = ".self::$sql->quote($pos->getPlanet())." LIMIT 1;") > 0)
				throw new UserException("This shortcut is already in the list.");

			$i = self::$sql->singleField("SELECT c_i FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." ORDER BY c_i DESC LIMIT 1;");
			self::$sql->backgroundQuery("INSERT INTO t_users_shortcuts ( c_user, c_galaxy, c_system, c_planet, c_i ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($pos->getGalaxy()).", ".self::$sql->quote($pos->getSystem()).", ".self::$sql->quote($pos->getPlanet()).", ".self::$sql->quote($i+1)." );");
		}

		/**
		 * Gibt eine Liste von Lesezeichen in der richtigen Reihenfolge zurück.
		 * @return array(Planet)
		*/

		function getPosShortcutsList()
		{
			$return = array();
			self::$sql->query("SELECT c_galaxy, c_system, c_planet FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." ORDER BY c_i ASC;");
			while(($r = self::$sql->nextResult()) !== false)
				$return[] = Planet::fromKoords($r["c_galaxy"], $r["c_system"], $r["c_planet"]);
			return $return;
		}

		/**
		 * Entfernt ein Lesezeichen.
		 * @param Planet $pos
		 * @return void
		*/

		function removePosShortcut(Planet $pos)
		{
			self::$sql->query("DELETE FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." AND c_galaxy = ".self::$sql->quote($pos->getGalaxy())." AND c_system = ".self::$sql->quote($pos->getSystem())." AND c_planet = ".self::$sql->quote($pos->getPlanet()).";");
		}

		/**
		 * Schiebt ein Lesezeichen in der Liste nach oben.
		 * @param Planet $pos
		 * @return void
		*/

		function movePosShortcutUp(Planet $pos)
		{
			$i = self::$sql->singleField("SELECT i FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." AND c_galaxy = ".self::$sql->quote($pos->getGalaxy())." AND c_system = ".self::$sql->quote($pos->getSystem())." AND c_planet = ".self::$sql->quote($pos->getPlanet())." LIMIT 1;");
			$i2 = self::$sql->singleField("SELECT i FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." AND c_i < ".self::$sql->quote($i)." ORDER BY c_i DESC LIMIT 1;");
			self::$sql->backgroundQuery("UPDATE t_users_shortcuts SET c_i = ".self::$sql->quote($i)." WHERE c_user = ".self::$sql->quote($this->getName())." AND c_i = ".self::$sql->quote($i2).";");
			self::$sql->backgroundQuery("UPDATE t_users_shortcuts SET c_i = ".self::$sql->quote($i2)." WHERE c_user = ".self::$sql->quote($this->getName())." AND c_galaxy = ".self::$sql->quote($pos->getGalaxy())." AND c_system = ".self::$sql->quote($pos->getSystem())." AND c_planet = ".self::$sql->quote($pos->getPlanet()).";");
		}

		/**
		 * Schiebt ein Lesezeichen in der Liste nach unten.
		 * @param Planet $pos
		 * @return void
		*/

		function movePosShortcutDown(Planet $pos)
		{
			$i = self::$sql->singleField("SELECT c_i FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." AND c_galaxy = ".self::$sql->quote($pos->getGalaxy())." AND c_system = ".self::$sql->quote($pos->getSystem())." AND c_planet = ".self::$sql->quote($pos->getPlanet())." LIMIT 1;");
			$i2 = self::$sql->singleField("SELECT c_i FROM t_users_shortcuts WHERE c_user = ".self::$sql->quote($this->getName())." AND c_i > ".self::$sql->quote($i)." ORDER BY c_i ASC LIMIT 1;");
			self::$sql->backgroundQuery("UPDATE t_users_shortcuts SET c_i = ".self::$sql->quote($i)." WHERE c_user = ".self::$sql->quote($this->getName())." AND c_i = ".self::$sql->quote($i2).";");
			self::$sql->backgroundQuery("UPDATE t_users_shortcuts SET c_i = ".self::$sql->quote($i2)." WHERE c_user = ".self::$sql->quote($this->getName())." AND c_galaxy = ".self::$sql->quote($pos->getGalaxy())." AND c_system = ".self::$sql->quote($pos->getSystem())." AND c_planet = ".self::$sql->quote($pos->getPlanet()).";");
		}

		/**
		 * Erzeugt eine neue zufällige Prüfsumme zur Benutzung der Passwort-Vergessen-Funktion und speichert
		 * diese zur Benutzung in der Datenbank.
		 * @return string
		*/

		function getPasswordSendID()
		{
			$send_id = md5(microtime());
			$this->setMainField("c_password_reset_hash", $send_id);
			return $send_id;
		}

		/**
		 * Überprüft, ob die übergebene Prüf-ID für die Passwort-vergessen-Funktion gültig ist.
		 * @param string $id
		 * @return bool
		*/

		function checkPasswordSendID($id)
		{
			return $id == $this->getMainField("c_password_reset_hash");
		}

		/**
		 * Stellt die Sprache dieses Benutzer temporär als globale Spracheinstellung ein. Die vorige Einstellung
		 * kann mittels restoreLanguage() wiederhergestellt werden. Verschachtelte Aufrufe sind möglich.
		 * @return void
		*/

		function setLanguage()
		{
			$next = &$this->language_cache[];
			$next = array();

			$lang = $this->checkSetting("lang");
			if($lang)
			{
				$next[0] = L::language();
				L::language($lang);
			}
			else
				$next[0] = null;

			$timezone = $this->checkSetting("timezone");
			if($timezone)
			{
				$next[1] = L::timezone();
				L::timezone($this->checkSetting("timezone"));
			}
			else
				$next[1] = null;
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
			if(!is_null($language[0]))
				L::language($language[0]);
			if(!is_null($language[1]))
				L::timezone($language[1]);
		}

		/**
		 * _() benutzerlokalisiert.
		 * @param string $message
		 * @return string
		*/

		function _($message)
		{
			return $this->localise("\sua\_", $message);
		}

		/**
		 * L::_I() benutzerlokalisiert.
		 * @param string $message
		 * @return string
		*/

		function _I($message)
		{
			return $this->localise(array("l", "_I"), $message);
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
			if(isset($timestamp))
				return $this->localise("date", $format, $timestamp);
			else
				return $this->localise("date", $format);
		}

		/**
		 * Gibt die aktuell gültige E-Mail-Adresse des Benutzers zurück.
		 * @return string
		*/

		function getEMailAddress()
		{
			return self::$sql->singleField("SELECT c_email FROM t_users_email WHERE c_user = ".self::$sql->quote($this->getName())." AND c_valid_from <= ".self::$sql->quote(time())." ORDER BY c_valid_from DESC LIMIT 1;");
		}

		/**
		 * Gibt die temporäre E-Mail-Adresse des Benutzers zurück. Die Einstellung der E-Mail-Adresse wird erst
		 * nach einer gewissen Zeitspanne übernommen.
		 * @param bool $array Wenn true, wird nur die Adresse zurückgegeben.
		 * @return string|array [ E-Mail-Adresse; Zeitpunkt der Gültigkeit ]
		*/

		function getTemporaryEMailAddress($array=false)
		{
			$res = self::$sql->singleLine("SELECT c_email,c_valid_from FROM t_users_email WHERE c_user = ".self::$sql->quote($this->getName())." ORDER BY c_valid_from DESC LIMIT 1;");
			if($array)
				return array($res["c_email"], $res["c_valid_from"]);
			else
				return $res["c_email"];
		}

		/**
		 * Setzt die E-Mail-Adresse des Benutzers.
		 * @param string $address
		 * @param bool $do_delay Soll die Verzögerung der Gültigkeit angewandt werden?
		 * @return void
		*/

		function setEMailAddress($address, $do_delay=true)
		{
			if($address === $this->getTemporaryEMailAddress() && $do_delay)
				return;
			self::$sql->backgroundQuery("DELETE FROM t_users_email WHERE c_user = ".self::$sql->quote($this->getName())." AND c_valid_from > ".self::$sql->quote(Classes::Date()).";");

			if($address == $this->getEMailAddress())
				return;

			self::$sql->backgroundQuery("INSERT INTO t_users_email ( c_user, c_email, c_valid_from ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($address).", ".self::$sql->quote(Classes::Date(time()+($do_delay ? Config::getLibConfig()->getConfigValue("users", "email_change_delay") : 0)))." );");
		}

		/**
		 * Sendet (und verschlüsselt oder signiert) eine E-Mail an diesen Benutzer.
		 * @param string $subject
		 * @param string $text
		 * @return void
		*/

		protected function _sendMail($subject, $text)
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
			$hdrs = $mime->headers(array("From" => "\"".$this->_("[title_full]")."\" <".Config::getLibConfig()->getConfigValue("email_from").">", "Subject" => $subject));

			$mail = MaiL::factory("mail");
			$return = $mail->send($this->getEMailAddress(), $hdrs, $body);
			error_reporting($er);
		}

		/**
		 * Gibt zurück, ob der Benutzer wieder ein Captcha eingeben muss.
		 * @return bool
		*/

		function challengeNeeded()
		{
			$next_challenge = $this->getMainField("c_next_challenge");
			if(!$next_challenge)
				return true;
			return time() >= $next_challenge;
		}

		/**
		 * Der Benutzer hat sein Captcha erfolgreich eingegeben. Der nächste Zeitpunkt der Eingabe wird
		 * festgelegt.
		 * @return void
		*/

		function _challengePassed()
		{
			$lib_config = Config::getLibConfig();
			$this->setMainField("c_next_challenge", time()+rand($lib_config->getConfigValueE("captcha", "min_time"), $lib_config->getConfigValueE("captcha", "max_time")));
			$this->setMainField("c_challenge_failures", 0);
		}

		/**
		 * Die Eingabe des Captchas ist fehlgeschlagen. Eventuell wird der Benutzer bald gesperrt.
		 * @return void
		*/

		function _challengeFailed()
		{
			$failures = $this->getMainField("c_challenge_failures");
			$failures++;
			$this->setMainField("c_challenge_failures", $failures);

			$lib_config = Config::getLibConfig();
			if($failures > $lib_config->getConfigValueE("captcha", "max_failures") && !$this->userLocked())
				$this->lockUser(time()+$lib_config->getConfigValueE("captcha", "lock_time"));
		}

		/**
		 * Gibt die Anzahl der Rohstoffe zurück, die der Benutzer seit Bestehen
		 * des Accounts ausgegeben hat.
		 * @param int $i 0: Carbon; 1: Aluminium; ... Wenn nicht angegeben, wird die Gesamtzahl zurückgegeben
		 * @return int
		*/

		function getSpentRess($i=null)
		{
			if(!isset($i))
				$field = "c_used_ress0+c_used_ress1+c_used_ress2+c_used_ress3+c_used_ress4";
			else
				$field = "c_used_ress".$i;
			return self::$sql->getMainField($field);
		}

		/**
		 * Registriert neue ausgegebene Rohstoffe.
		 * @param array $ress
		 * @return void
		*/

		function _addSpentRess(array $ress)
		{
			$cur = $this->getMainField(array("c_used_ress0", "c_used_ress1", "c_used_ress2", "c_used_ress3", "c_used_ress4"));
			if(isset($ress[0])) $cur[0] += $ress[0];
			if(isset($ress[1])) $cur[1] += $ress[1];
			if(isset($ress[2])) $cur[2] += $ress[2];
			if(isset($ress[3])) $cur[3] += $ress[3];
			if(isset($ress[4])) $cur[4] += $ress[4];
			$this->setMainField(array("c_used_ress0", "c_used_ress1", "c_used_ress2", "c_used_ress3", "c_used_ress4"), $cur);
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
			try
			{
				return (count(Planet::getPlanetsByUser($this->getName())) < Config::getLibConfig()->getConfigValueE("users", "max_planets"));
			}
			catch(ConfigException $e)
			{
				return true;
			}
		}

		/**
		 * Gibt ein Flag zurück, das in der Karte hinter dem Benutzernamen angezeigt
		 * werden kann. Stellt dar, ob der Benutzer sich im Urlaubsmodus befindet
		 * oder gesperrt ist.
		 * @return string
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

/*****************************************
*** Highscores ***
*****************************************/

		/**
		 * Gibt die Punkte des Spielers zurück.
		 * @param string $i User::SCORES_* oder nichts für die Gesamtpunktzahl
		 * @return float
		*/

		function getScores($i=null)
		{
			return self::$sql->singleField("SELECT ".$i." FROM t_highscores WHERE c_user = ".self::$sql->quote($this->getName())." LIMIT 1;");
		}

		/**
		 * Gibt die Platzierung dieses Spielers in den Highscores zurück.
		 * @param string $i User::SCORES_*, wenn die Platzierung bei einer bestimmten Punktart gemeint ist
		 * @return int
		*/

		function getRank($i=null)
		{
			if(!isset($i))
				$i = self::SCORES_TOTAL;

			return self::singleField("SELECT COUNT(*)+1 FROM t_highscores WHERE ".$i." > ".$me->getScores($i).";");
		}

		/**
		 * Gibt die Benutzer-Highscores-Liste als Array zurück. Es gilt: Platzierung = Index + $from. Standard-
		 * wert für $from ist 1. Die Werte des Arrays sind wiederrum assoziative Arrays nach dem Format.
		 * Der Wert mit dem Index "user" gibt den Benutzernamen an. Die Werte mit den Indexen User::SCORES_*
		 * geben die Punktzahl an.
		 * @param int|null $from Die kleinste Platzierung, die zurückgegeben werden soll
		 * @param int|null $to Die größte Platzierung, die zurückgegeben werden soll
		 * @param string|null $sort Nach welcher Punktzahl soll sortiert werden? Standard: User::SCORES_TOTAL
		 * @param array|string|null $fields Welche Punktzahlen/Punktzahl soll zurückgegeben werden? Standard: alle
		 * @return array
		*/

		static function getHighscores($from=null, $to=null, $sort=null, $fields=null)
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
					$query .= implode(",", array_merge(array("c_user"), $fields));
				else
					$query .= "c_user,".$fields;
			}
			else
				$query .= "*";
			$query .= " FROM t_highscores ORDER BY ";
			if(isset($sort))
				$query .= $sort;
			else
				$query .= self::SCORES_TOTAL;
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

/***************************************
*** Flotten ***
***************************************/

		/**
		 * Gibt das Flottenlimit zurück, also wieviele Flotten gleichzeitig
		 * unterwegs sein dürfen.
		 * @return int
		*/

		function getMaxParallelFleets()
		{
			$werft = 0;
			foreach($this as $planet)
			{
				if($planet->getItemLevel("B10", "gebaeude") > 0)
					$werft++;
			}

			return ceil(pow($werft*$this->getResearchLevel("F0"), .7));
		}

		/**
		 * Gibt zurück, wieviele „Flotten-Slots“ noch frei sind.
		 * @return int
		*/

		function getRemainingParallelFleets()
		{
			return $this->getMaxParallelFleets()-Fleet::userSlots($this->getName());
		}

		/**
		 * Gibt die Flotten-ID zurück, die zu einem Verbundflottenpasswort gehört.
		 * @param string $passwd
		 * @return string
		 * @throw UserException Es wurde keine passende Flotte gefunden.
		*/

		function resolveFleetPasswd($passwd)
		{
			foreach(Fleet::visibleToUser($this->getName()) as $fleet)
			{
				$fleet_obj = Classes::Fleet($fleet);
				if($fleet_obj->password() == $passwd && $fleet_obj->getCurrentType() == Fleet::TYPE_ANGRIFF && !$fleet_obj->isFlyingBack() && $fleet_obj->isFirstUser($this->getName()))
					return $fleet;
			}

			throw new UserException("No fleet with this password exists.");
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

		protected function _itemInfoFields(array $fields)
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
		 * @param Planet $planet Berechnet die Eigenschaften mit den Werten auf dem Planeten
		 * @param int $level Gibt die Informationen auf einer bestimmten Ausbaustufe zurück
		 * @return array
		*/

		function getItemInfo($id, $type=null, $fields=null, $planet=null, $level=null)
		{
			list($calc, $fields) = $this->_itemInfoFields($fields);

			$item = Classes::Item($id);
			if($type === null) $type = $item->getType();
			$info = $item->getInfo($fields);
			if($calc["type"])
				$info["type"] = $type;
			if($calc["buildable"])
				$info["buildable"] = $item->checkDependencies($this, $planet);
			if($calc["deps-okay"])
				$info["deps-okay"] = $item->checkDependencies($this, $planet);
			if($calc["level"])
				$info["level"] = ($level !== null ? $level : $this->getItemLevel($id, $type, $planet));
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

			$lib_config = Config::getLibConfig();
			$global_factors = array(
				"time" => $lib_config->getConfigValue("users", "global_factors", "time"),
				"production" => $lib_config->getConfigValue("users", "global_factors", "production"),
				"costs" => $lib_config->getConfigValue("users", "global_factors", "costs")
			);

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

			$min_building_time = Config::getLibConfig()->getConfigValue("users", "min_building_time");

			switch($type)
			{
				case "gebaeude":
					if(($calc["prod"] || $calc["time"]) && isset($planet))
						$max_rob_limit = floor($planet->getSize(true)/2);

					if($calc["has_prod"])
						$info["has_prod"] = ($info["prod"][0] > 0 || $info["prod"][1] > 0 || $info["prod"][2] > 0 || $info["prod"][3] > 0 || $info["prod"][4] > 0 || $info["prod"][5] > 0);
					if($calc["prod"] && isset($planet))
					{
						$level_f = pow($info["level"], 2);
						$percent_f = $planet->getProductionFactor($id);
						$info["prod"][0] *= $level_f*$percent_f;
						$info["prod"][1] *= $level_f*$percent_f;
						$info["prod"][2] *= $level_f*$percent_f;
						$info["prod"][3] *= $level_f*$percent_f;
						$info["prod"][4] *= $level_f*$percent_f;
						$info["prod"][5] *= $level_f*$percent_f;

						$minen_rob = sqrt($this->getItemLevel("F2", "forschung"))/250;
						if($minen_rob > 0)
						{
							$use_max_limit = !Config::getLibConfig()->getConfigValue("users", "no_max_rob_limit");

							$rob = $this->getItemLevel("R02", "roboter");
							if($rob > $this->getItemLevel("B0", "gebaeude")) $rob = $this->getItemLevel("B0", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							$info["prod"][0] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R03", "roboter");
							if($rob > $this->getItemLevel("B1", "gebaeude")) $rob = $this->getItemLevel("B1", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							$info["prod"][1] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R04", "roboter");
							if($rob > $this->getItemLevel("B2", "gebaeude")) $rob = $this->getItemLevel("B2", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							$info["prod"][2] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R05", "roboter");
							if($rob > $this->getItemLevel("B3", "gebaeude")) $rob = $this->getItemLevel("B3", "gebaeude");
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
							$info["prod"][3] *= 1+$minen_rob*$rob;

							$rob = $this->getItemLevel("R06", "roboter");
							if($rob > $this->getItemLevel("B4", "gebaeude")*2) $rob = $this->getItemLevel("B4", "gebaeude")*2;
							if($use_max_limit && $rob > $max_rob_limit) $rob = $max_rob_limit;
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

					if($calc["buildable"] && $info["buildable"] && isset($planet) && $info["fields"] > $planet->getRemainingFields())
						$info["buildable"] = false;
					if($calc["debuildable"])
						$info["debuildable"] = ($info["level"] >= 1 && isset($planet) && -$info["fields"] <= $planet->getRemainingFields());

					if($calc["limit_factor"])
					{
						if($info["time"] < $min_building_time)
							$info["limit_factor"] = $info["time"]/$min_building_time;
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
						$planets = Planet::getPlanetsByUser($this->getName());
						foreach($planets as $act_planet)
						{
							if($act_planet === $planet) $local_labs += $act_planet->getItemLevel("B8", "gebaeude");
							else $global_labs += $act_planet->getItemLevel("B8", "gebaeude");
						}

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
						if($info["time_local"] < $min_building_time)
							$info["limit_factor_local"] = $info["time_local"]/$min_building_time;
						else
							$info["limit_factor_local"] = 1;
					}
					if($calc["limit_factor_global"])
					{
						if($info["time_global"] < $min_building_time)
							$info["limit_factor_global"] = $info["time_global"]/$min_building_time;
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
						if($info["time"] < $min_building_time)
							$info["limit_factor"] = $info["time"]/$min_building_time;
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
						if($info["time"] < $min_building_time)
							$info["limit_factor"] = $info["time"]/$min_building_time;
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
						if($info["time"] < $min_building_time)
							$info["limit_factor"] = $info["time"]/$min_building_time;
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
				if($calc["time_local"] && $info["time_local"] < $min_building_time) $info["time_local"] = $min_building_time;
				if($calc["time_global"] && $info["time_global"] < $min_building_time) $info["time_global"] = $min_building_time;
			}
			elseif($calc["time"] && $info["time"] < $min_building_time) $info["time"] = $min_building_time;

			return $info;
		}

		/**
		 * Gibt die aktuelle Ausbaustufe eines globalen Items (=> einer Forschung) zurück.
		 * @param string $id
		 * @return int
		*/

		function getItemLevel($id)
		{
			try { return $this->getCacheValue(array("getItemLevel", $id)); }
			catch(DatasetException $e) { }

			$level = self::$sql->singleField("SELECT c_level FROM t_users_research WHERE c_user = ".self::$sql->quote($this->getName())." AND c_id = ".self::$sql->quote($id)." LIMIT 1;");
			if($level === false)
				$level = 0;
			$this->setCacheValue(array("getItemLevel", $id), $level);
			return $level;
		}

		/**
		 * Verändert die Ausbaustufe einer Forschung.
		 * @param string $id
		 * @param int $value Wird zur aktuellen Stufe hinzugezählt
		 * @return void
		*/

		function changeItemLevel($id, $value=1)
		{
			if($value == 0) return;

			$item_info = $this->getItemInfo($id, "forschung", array("ress"));
			$additional_scores = array_sum($item_info["ress"])/1000;
			$entries = self::$sql->singleField("SELECT COUNT(*) FROM t_users_research WHERE c_user = ".self::$sql->quote($this->getName())." AND c_id = ".self::$sql->quote($id)." LIMIT 1;");
			if($entries > 0)
				$this->backgroundQuery("UDPATE t_users_research SET c_level = c_level + ".self::$sql->quote($value).", c_scores = c_scores + ".self::$sql->quote($additional_scores)." WHERE c_user = ".self::$sql->quote($this->getName())." AND c_id = ".self::$sql->quote($id).";");
			else
				$this->backgroundQuery("INSERT INTO t_users_research ( c_user, c_id, c_level, c_scores ) VALUES ( ".self::$sql->quote($this->getName()).", ".self::$sql->quote($id).", ".self::$sql->quote($value).", ".self::$sql->quote($additional_scores)." );");

			switch($id)
			{
				# Ingenieurswissenschaft: Planeten vergroessern
				case "F9":
					Planet::increaseSize($this->getName(), ($this->getItemLevel("F9", false, false)/Item::getIngtechFactor()+1)/(($this->getItemLevel("F9", false, false)-$value)/Item::getIngtechFactor()+1));
					break;

				# Roboterbautechnik: Auswirkungen der Bauroboter aendern
				case "F2":
					Planet::newRobtechFactor($this->getName()); # , ... (todo)
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
			return self::$sql->singleField("SELECT COUNT(*) FROM t_users_friends WHERE ( c_user1 = ".self::$sql->quote($user)." AND c_user2 = ".self::$sql->quote($this->getName())." ) OR ( c_user1 = ".self::$sql->quote($this->getName())." AND c_user2 = ".self::$sql->quote($user)." );") > 0;
		}

		/**
		 * Gibt zurück, ob ein Benutzer diesem Benutzer eine Bündnisanfrage stellt.
		 * @param string $user
		 * @return bool
		*/

		function isApplying($user)
		{
			return self::$sql->singleField("SELECT COUNT(*) FROM t_users_friend_requests WHERE c_user_from = ".self::$sql->quote($user)." AND c_user_to = ".self::$sql->quote($this->getName()).";") > 0;
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

			return self::$sql->singleField("SELECT COUNT(*) FROM t_users_friend_requests WHERE ( c_user_from = ".self::$sql->quote($user)." AND c_user_to = ".self::$sql->quote($this->getName())." ) OR ( c_user_from = ".self::$sql->quote($this->getName())." AND c_user_to = ".self::$sql->quote($user)." );") > 0;
		}

		/**
		 * Gibt eine Liste der verbündeten Spieler zurück.
		 * @return array(string)
		*/

		function getVerbuendetList()
		{
			return self::$sql->singleColumn("SELECT c_user1 FROM t_users_friends WHERE c_user2 = ".self::$sql->quote($this->getName())." UNION SELECT c_user2 FROM t_users_friends WHERE c_user1 = ".self::$sql->quote($this->getName()).";");
		}

		/**
		 * Gibt eine Liste der Spieler, zu denen dieser eine Bündnisanfrage am Laufen hat, zurück.
		 * @return array(string)
		*/

		function getVerbuendetApplicationList()
		{
			return self::$sql->singleColumn("SELECT c_user_to FROM t_users_friend_requests WHERE c_user_from = ".self::$sql->quote($this->getName()).";");
		}

		/**
		 * Gibt eine Liste der Spieler, die an diesem Benutzer eine Bündnisanfrage stellen, zurück.
		 * @return array(string)
		*/

		function getVerbuendetRequestList()
		{
			return self::$sql->singleColumn("SELECT c_user_from FROM t_users_friend_requests WHERE c_user_to = ".self::$sql->quote($this->getName()).";");
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

			self::$sql->query("INSERT INTO t_users_friends ( c_user_from, c_user_to ) VALUES ( ".self::$sql->quote($user).", ".self::$sql->quote($this->getName())." );");

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

			self::$sql->query("DELETE FROM t_users_friend_requests WHERE c_user_from = ".self::$sql->quote($user)." AND c_user_to = ".self::$sql->quote($this->getName()).";");
			self::$sql->query("INSERT INTO t_users_friends ( c_user1, c_user2 ) VALUES ( ".self::$sql->quote($user).", ".self::$sql->quote($this->getName())." );");

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

			self::$sql->query("DELETE FROM t_users_friend_requests WHERE c_user_from = ".self::$sql->quote($user)." AND c_user_to = ".self::$sql->quote($this->getName()).";");

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

			self::$sql->query("DELETE FROM t_users_friends WHERE ( c_user1 = ".self::$sql->quote($this->getName())." AND c_user2 = ".self::$sql->quote($user)." ) OR ( c_user1 = ".self::$sql->quote($user)." AND c_user2 = ".self::$sql->quote($this->getName())." );");

			$user_obj = Classes::User($user);
			$message = Classes::Message(Message::create());
			$message->from($this->getName());
			$message->subject($user_obj->_("Bündnis gekündigt"));
			$message->text(sprintf($user_obj->_("Der Spieler %s hat sein Bündnis mit Ihnen gekündigt."), $this->getName()));
			$message->addUser($user, Message::TYPE_VERBUENDETE);

			# Fremdstationierte Flotten zurueckholen
			$this->cacheActivePlanet();
			$user_obj->cacheActivePlanet();

			foreach(Planet::getPlanetsByUser($user_obj->getName()) as $planet)
			{
				$user_obj->setActivePlanet($planet);
				if(count($user_obj->getForeignFleetsList($this->getName())) > 0)
					$this->callBackForeignFleet($user_obj->getPosObj());
			}

			foreach(Planet::getPlanetsByUser($this->getName()) as $planet)
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

		protected function loadIterator($force = false)
		{
			if($force || is_null($this->planets))
				$this->planets = Planet::getPlanetsByUser($this->getName());
		}

		function rewind()
		{
			$this->loadIterator(true);
			return reset($this->planets);
		}

		function current()
		{
			$this->loadIterator();
			return current($this->planets);
		}

		function key()
		{
			$this->loadIterator();
			return key($this->planets);
		}

		function next()
		{
			$this->loadIterator();
			return next($this->planets);
		}

		function valid()
		{
			$this->loadIterator();
			return !is_null(key($this->planets));
		}
	}
