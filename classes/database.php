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
	 * @subpackage config
	*/

	/**
	 * Repräsentiert eine Datenbank in der Konfiguration und kümmert sich um
	 * deren Konfiguration.
	 * Eine Liste der auswählbaren Datenbanken samt IDs wird in der Konfiguration
	 * des Spiels konfiguriert. Zusätzlich besitzt jede Datenbank eine eigene
	 * config.xml, in der interne Parameter konfiguriert werden. Diese Datenbank-
	 * konfiguration wird in dieser Klasse verwaltet.
	*/

	class Database
	{
		private $id; /// Datenbank-ID
		private $directory; /// Datenbankverzeichnis
		private $alias; /// Datenbank-ID, für den diese Datenbank ein Alias ist
		private $config; /// Konfiguration der Datenbank in der Spielkonfiguration (Verzeichnis, Aliase und Aktivierung)
		private $settings; /// Konfiguration der Datenbank selbst

		/**
		 * @param string|null $id Die ID der Datenbank oder null, wenn global_setting("DB") verwendet werden soll.
		 * @param string|null $directory Setzt das Datenbankverzeichnis manuell, falls die Datenbank nicht in der Konfiguration steht
		*/

		function __construct($id=null, $directory=null)
		{
			if(!isset($id)) $id = global_setting("DB");
			$this->id = $id;

			if(isset($directory))
				$this->directory = $directory;
			else
			{
				$config = Config::getConfig();

				if(!isset($config["databases"]))
					throw new DatabaseException("Database does not exist.");

				$this->alias = false;
				if(isset($config["databases"][$id]))
					$this->config = $config["databases"][$id];
				else
				{
					foreach($config["databases"] as $i=>$db)
					{
						if(!isset($db["aliases"])) continue;
						$aliases = preg_split("/\s+/", $db["aliases"]);
						if(in_array($id, preg_split("/\s+/", $db["aliases"])))
						{
							$this->alias = $i;
							$this->config = $db;
						}
					}
					if($this->alias === false)
						throw new DatabaseException("Database does not exist.");
				}
				if(!isset($this->config["directory"]))
					throw new DatabaseException("Directory is not configured for database ".$this->id);
				$this->directory = $this->config["directory"];
			}

			if(!is_file($this->directory."/config.xml") || !is_readable($this->directory."/config.xml"))
				throw new DatabaseException("The directory ".$this->directory." does not contain any readable configuration file.");

			if(!is_file($this->directory."/config.db") || filemtime($this->directory."/config.db") < filemtime($this->directory."/config.xml"))
			{
				$dom = new DOMDocument();
				$dom->load($this->directory."/config.xml", LIBXML_DTDVALID | LIBXML_NOCDATA);
				self::$config = array();
				$cur_conf = &$this->settings;
				$p = array();
				$el = $dom->firstChild->nextSibling->firstChild;
				while($el->nodeType != 1) $el = $el->nextSibling;
				while(true)
				{
					$name = $el->getAttribute("name");
					if($el->nodeName == "setting")
						$cur_conf[$name] = $el->firstChild ? $el->firstChild->data : "";
					elseif($el->nodeName == "section")
						$cur_conf[$name] = array();

					if($el->nodeName == "section" && $el->firstChild)
					{
						$p[] = &$cur_conf;
						$cur_conf = &$cur_conf[$name];
						$el = $el->firstChild;
					}

					if($el->nodeName != "section" || $el->nodeType != 1)
					{
						do
						{
							while(!$el->nextSibling)
							{
								if(count($p) == 0) break 3;
								$cur_conf = &$p[count($p)-1];
								array_pop($p);
								$el = $el->parentNode;
							}
							$el = $el->nextSibling;
						} while($el->nodeType != 1);
					}
				}
				file_put_contents($this->directory."/config.db", serialize($this->settings));
			}
			else
				$this->settings = unserialize(file_get_contents($this->directory."/config.db"));
		}

		/**
		 * Liefert die Konfiguration der Datenbank (config.xml im Datenbankverzeichnis)
		 * als Array zurück.
		 * @return array
		*/

		function getConfig()
		{
			return $this->settings;
		}

		/**
		 * Gibt eine Liste der konfigurierten Datenbank-IDs zurück.
		 * @return array
		*/

		static function getList()
		{
			$config = Config::getConfig();
			if(!isset($config["databases"])) return array();
			return array_keys($config["databases"]);
		}

		/**
		 * Gibt das Verzeichnis zurück, wo die Datenbank liegt.
		 * @return string
		*/

		function getDirectory()
		{
			return $this->directory;
		}

		/**
		 * Gibt zurück, ob die Datenbank in der Spielkonfiguration aktiviert ist.
		 * Deaktivierte Datenbanken sollen nicht in einer Auswahlliste erscheinen,
		 * also für den Spieler unsichtbar sein. Sie dienen nur dem Zweck, dass
		 * öffentliche Nachrichten weiterhin aufrufbar sind.
		 * @return boolean
		*/

		function enabled()
		{
			if(!isset($this->config["enabled"]))
				return true;
			else
				return $this->config["enabled"];
		}

		/**
		 * Gibt die Datenbank-ID zurück, für die diese Datenbank ein Alias ist.
		 * Wird eine Datenbank umbenannt, können in der Spielkonfiguration Aliase
		 * für die neue Datenbank-ID definiert werden, damit öffentliche Nachrichten
		 * und andere Dinge weiterhin aufrufbar sind.
		 * Ist diese Datenbank kein Alias, wird die ID dieser Datenbank zurückgeliefert.
		 * @return string
		*/

		function alias()
		{
			if($this->alias === false)
				return $this->id;
			else
				return $this->alias;
		}

		/**
		 * Gibt eine Liste der Aliase zurück, die für diese Datenbank konfiguriert sind.
		 * @return array(string)
		*/

		function getAliases()
		{
			if(!isset($this->config["aliases"]) || strlen(trim($this->config["aliases"])) == 0)
				return array();
			else
				return preg_split("/\s+/", $this->config["aliases"]);
		}

		/**
		 * Gibt den Titel der Datenbank an, also die Bezeichnung, die für den
		 * Benutzer sichtbar sein soll (der Name des Universums/der Runde).
		 * Ist kein Titel konfiguriert, wird die ID benutzt.
		 * @return string
		*/

		function getTitle()
		{
			if(isset($this->settings["name"]))
				return $this->settings["name"];
			else return $this->id;
		}

		/**
		* Initialisiert die Standardwerte fuer die globalen Einstellungen.
		* Kann mehrmals aufgerufen werden, zum Beispiel, um auf eine andere
		* Datenbank umzustellen.
		* In Zukunft soll nur global_setting("DB") verändert werden und die Funktion
		* wird überflüssig.
		* @deprecated
		*/

		function defineGlobals()
		{ # Setzt diverse Spielkonstanten zu einer bestimmten Datenbank
			static $instances_cache;

			if(!isset($instances_cache)) $instances_cache = array();

			// Instanzen-Cache auslagern, damit keine Konflikte entstehen
			// TODO: Das soll wenn überhaupt in Classes geschehen
			/*$old_db = global_setting('DB');
			if($old_db && isset($GLOBALS['objectInstances']) && $GLOBALS['objectInstances'])
			{
				$db_obj = Classes::Database($old_db);
				$instances[$db_obj->alias()] = &$GLOBALS['objectInstances'];
				unset($GLOBALS['objectInstances']);
			}

			if(isset($instances[$this->alias()]))
				$GLOBALS['objectInstances'] = &$instances[$this->alias()];
			else
				$GLOBALS['objectInstances'] = array();*/

			global_setting('DB', $this->id);

			$DB_DIR = $this->config['directory'];
			if(substr($DB_DIR, 0, 1) != '/')
				$DB_DIR = s_root.'/'.$DB_DIR;

			global_setting('DB_DIR', $DB_DIR);

			global_setting('DB_LOCKED', $DB_DIR.'/locked');
			global_setting('DB_ALLIANCES', $DB_DIR.'/alliances');
			global_setting('DB_PLAYERS', $DB_DIR.'/players');
			global_setting('DB_UNIVERSE', $DB_DIR.'/universe');
			global_setting('DB_ITEMS', $DB_DIR.'/items');
			global_setting('DB_ITEM_DB', $DB_DIR.'/items.db');
			global_setting('DB_TRUEMMERFELDER', $DB_DIR.'/truemmerfelder');
			global_setting('DB_HANDEL', $DB_DIR.'/handel');
			global_setting('DB_HANDELSKURS', $DB_DIR.'/handelskurs');
			global_setting('DB_ADMINS', $DB_DIR.'/admins');
			global_setting('DB_NONOOBS', $DB_DIR.'/nonoobs');
			global_setting('DB_ADMIN_LOGFILE', $DB_DIR.'/admin_logfile');
			global_setting('DB_NO_STRICT_ROB_LIMITS', $DB_DIR.'/no_strict_rob_limits');
			global_setting('DB_GLOBAL_TIME_FACTOR', $DB_DIR.'/global_time_factor');
			global_setting('DB_GLOBAL_PROD_FACTOR', $DB_DIR.'/global_prod_factor');
			global_setting('DB_GLOBAL_COST_FACTOR', $DB_DIR.'/global_cost_factor');
			global_setting('DB_USE_OLD_INGTECH', $DB_DIR.'/use_old_ingtech');
			global_setting('DB_USE_OLD_ROBTECH', $DB_DIR.'/use_old_robtech');
			global_setting('DB_NO_ATTS', $DB_DIR.'/no_atts');
			global_setting("DB_SQLITE", $DB_DIR."/sqlite");
			return true;
		}

		/**
		 * Gibt zurueck, ob eine Handlungssperre in der Datenbank vorliegt.
		 * @return integer|boolean Wird ein Integer zurückgegeben, so gibt dieser an, bis wann die Sperre gilt.
		 * @todo Muss die neue Konfigurationsdatei und die neue Namenskonvention verwenden.
		*/

		static function database_locked()
		{
			if(!file_exists(global_setting("DB_LOCKED"))) return false;

			if(!is_readable(global_setting("DB_LOCKED"))) return true;

			$until = trim(file_get_contents(global_setting("DB_LOCKED")));
			if($until && time() > $until)
			{
				unlink(global_setting("DB_LOCKED"));
				return false;
			}
			return ($until ? $until : true);
		}

		/**
		 * Gibt zurueck, ob eine Flottensperre in der Datenbank vorliegt.
		 * @return integer|boolean Wird ein Integer zurückgegeben, so gibt dieser an, bis wann die Sperre gilt.
		 * @todo Muss die neue Konfigurationsdatei und die neue Namenskonvention verwenden.
		*/

		static function fleets_locked()
		{
			if(!file_exists(global_setting("DB_NO_ATTS"))) return false;

			if(!is_readable(global_setting("DB_NO_ATTS"))) return true;

			$until = trim(file_get_contents(global_setting("DB_NO_ATTS")));
			if($until && time() > $until)
			{
				unlink(global_setting("DB_NO_ATTS"));
				return false;
			}
			return ($until ? $until : true);
		}

		/**
		 * Liefert die im Datenbankverzeichnis eingetragene Version zurück.
		 * In der Datenbank wird eine Versionsnummer gespeichert, die global_setting("DATABASE_VERSION")
		 * entsprechen muss, um Konflikte zu vermeiden, wenn das Datenbankschema geändert wird.
		 * @return integer
		*/

		static function getDatabaseVersion()
		{
			if(is_file($this->getDirectory().'/.version'))
			{
				if(!is_readable($this->getDirectory().'/.version'))
					throw new DatabaseException("Could not read ".$this->getDirectory()."/.version.");
				$current_version = trim(file_get_contents($this->getDirectory().'/.version'));
			}
			elseif(file_exists($this->getDirectory().'/highscores') && !file_exists($this->getDirectory().'/highscores_alliances') && !file_exists($this->getDirectory().'/highscores_alliances2')) $current_version = '4';
			elseif(file_exists($this->getDirectory().'/events') && @sqlite_open($this->getDirectory().'/events')) $current_version = '3';
			elseif(is_dir(global_setting("DB_DIR").'/fleets')) $current_version = '2';
			else $current_version = '1';

			return $current_version;
		}
	}