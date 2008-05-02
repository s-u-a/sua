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

	class Config
	{
		static private $config;
		static function getConfig()
		{
			if(!isset(self::$config))
			{
				if(!is_file(global_setting("DB_CONFIG_CACHE")) || !is_readable(global_setting("DB_CONFIG_CACHE")) || filemtime(global_setting("DB_CONFIG_CACHE")) < filemtime(global_setting("DB_CONFIG")))
				{
					$dom = new DOMDocument();
					$dom->load(global_setting("DB_CONFIG"), LIBXML_DTDVALID | LIBXML_NOCDATA);
					self::$config = array();
					$cur_conf = &self::$config;
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
					file_put_contents(global_setting("DB_CONFIG_CACHE"), serialize(self::$config));
				}
				else
					self::$config = unserialize(file_get_contents(global_setting("DB_CONFIG_CACHE")));
			}
			return self::$config;
		}

		/**
		* Sucht nach installierten Skins und liefert ein Array des folgenden
		* Formats zurueck:
		* ( ID => [ Name, ( Einstellungsname => ( moeglicher Wert ) ) ] )
		*/

		static function get_skins()
		{
			# Vorgegebene Skins-Liste bekommen
			$skins = array();
			if(is_dir(s_root.'/login/res/style') && is_readable(s_root.'/login/res/style'))
			{
				$dh = opendir(s_root.'/login/res/style');
				while(($fname = readdir($dh)) !== false)
				{
					if($fname[0] == '.') continue;
					$path = s_root.'/login/res/style/'.$fname;
					if(!is_dir($path) || !is_readable($path)) continue;
					if(!is_file($path.'/types') || !is_readable($path.'/types')) continue;
					$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents($path.'/types'));
					$new_skin = &$skins[$fname];
					$new_skin = array(array_shift($skins_file), array());
					foreach($skins_file as $skins_line)
					{
						$skins_line = explode("\t", $skins_line);
						if(count($skins_line) < 2)
							continue;
						$new_skin[1][array_shift($skins_line)] = $skins_line;
					}
					unset($new_skin);
				}
				closedir($dh);
			}
			return $skins;
		}

		/**
		* Liefert die Spielversion zurueck.
		*/

		static function get_version()
		{
			$version = '';
			if(is_file(global_setting("DB_VERSION")) && is_readable(global_setting("DB_VERSION")))
				$version = trim(file_get_contents(global_setting("DB_VERSION")));
			return $version;
		}

		/**
		* Liest die Liste der Datenbanken aus und liefert diese in einem Array zurueck:
		* ID => ( 'directory' => Datenbankverzeichnis; 'name' => Anzeigename der Datenbank; 'enabled' => fuer Benutzer sichtbar?; 'hostname' => Hostname, unter dem die Datenbank laeuft; 'dummy' => Ist dieser Eintrag nur ein Alias? )
		*/

		static function get_databases($force_reload=false, &$aliases=null)
		{
			# Liste der Runden/Universen herausfinden

			static $databases;
			static $aliases_cache;

			if(!isset($databases) || $force_reload)
			{
				$config = self::getConfig();
				if(!isset($config["databases"]))
					return false;
				$databases_raw = $config["databases"];

				$aliases_cache = array();
				$databases = array();

				foreach($databases_raw as $i=>$database)
				{
					if(!isset($database['directory'])) continue;

					$databases[$i] = array (
						'directory' => $database['directory'],
						'name' => (isset($database['name']) && strlen($database['name'] = trim($database['name'])) > 0) ? $database['name'] : $i,
						'enabled' => (!isset($database['enabled']) || $database['enabled']),
						'hostname' => (isset($database['hostname']) && strlen($database['hostname'] = trim($database['hostname'])) > 0) ? $database['hostname'] : self::get_default_hostname(),
						'dummy' => false
					);

					if(isset($database['aliases']) && strlen($database['aliases'] = trim($database['aliases'])) > 0)
					{
						foreach(preg_split("/\s+/", $database['aliases']) as $alias)
						{
							if(!isset($aliases_cache[$alias]))
							{
								$aliases_cache[$alias] = $i;
								$databases[$alias] = $databases[$i];
								$databases[$alias]['dummy'] = true;
							}
						}
					}
				}
			}

			$aliases = $aliases_cache;
			return $databases;
		}

		/**
		* Liefert den Hostname zurueck, auf dem die Hauptseite laeuft.
		* @return (string)
		* @return null bei Fehlschlag
		*/

		static function get_default_hostname()
		{
			$config = self::getConfig();
			if(isset($config["hostname"])) return $config["hostname"];
			elseif(isset($_SERVER["HTTP_HOST"])) return $_SERVER["HTTP_HOST"];
			else return null;
		}

		/**
		* Gibt ein Array aller Administratoren zurueck:
		* Benutzername => ( 'password' => md5(Passwort), 'permissions' => ( Nummer => Erlaubnis? ) )
		* @return (array)
		* @return false bei Fehlschlag
		*/

		static function get_admin_list()
		{
			$admins = array();
			if(!is_file(global_setting("DB_ADMINS")) || !is_readable(global_setting("DB_ADMINS")))
				return false;
			$admin_file = preg_split("/\r\n|\r|\n/", file_get_contents(global_setting("DB_ADMINS")));
			foreach($admin_file as $line)
			{
				$line = explode("\t", $line);
				if(count($line) < 2)
					continue;

				$this_admin = &$admins[urldecode(array_shift($line))];
				$this_admin = array();
				$this_admin['password'] = array_shift($line);
				$this_admin['permissions'] = $line;

				unset($this);
			}

			return $admins;
		}

		/**
		* Speichert eine mit get_admin_list() geholte Liste wieder ab.
		* @return (boolean)
		*/

		static function write_admin_list($admins)
		{
			$admin_file = array();
			foreach($admins as $name=>$settings)
			{
				$this_admin = &$admin_file[];
				$this_admin = $name;
				$this_admin .= "\t".$settings['password'];
				if(count($settings['permissions']) > 0)
					$this_admin .= "\t".implode("\t", $settings['permissions']);
				unset($this_admin);
			}

			$fh = fopen(global_setting("DB_ADMINS"), 'w');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);

			fwrite($fh, implode("\n", $admin_file));

			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}

		/**
		* Parst die Messenger-Konfigurationsdatei und schmeisst ungueltige Eintraege hinaus.
		* @param $type Liefert nur die Konfiguration zum gegebenen Protokoll zurueck
		* @param $force_reload Soll die Konfigurationsdatei unbedingt neu eingelesen werden?
		*/

		static function get_messenger_info($type=false, $force_reload=false)
		{
			global $messengers_parsed_file;

			if(!isset($messenger_parsed_file) || $force_reload)
			{
				$config = self::getConfig();
				if(!isset($config["instantmessaging"])) $messenger_parsed_file = false;
				else
				{
					$messenger_parsed_file = &$config["instantmessaging"];
					foreach($messenger_parsed_file as $k=>$v)
					{
						if(!is_array($v) || !isset($v['server']) || !isset($v['username']) || !isset($v['server']))
						{
							unset($messenger_parsed_file[$k]);
							continue;
						}
						$messenger_parsed_file[$k]["uin"] = $v["username"];
						if($k == "jabber") $messenger_parsed_file[$k]["uin"] .= "@".$v["server"];
					}
				}
			}

			if(!$messenger_parsed_file) return false;

			if($type)
			{
				if(!isset($messenger_parsed_file[$type])) return false;
				return $messenger_parsed_file[$type];
			}
			else return $messenger_parsed_file;
		}

		/**
		* Liefert ein Array der eingestellten globalen Faktoren der Datenbank zurueck:
		* ( 'time' => Zeit; 'prod' => Produktion; 'cost' => Kosten )
		* @param $force_reload Sollen die Konfigurationsdateien unbedingt neu eingelesen werden?
		*/

		static function get_global_factors($force_reload=false)
		{
			static $factors;

			if(!isset($factors) || $force_reload)
			{
				$factors = array('time' => 1, 'prod' => 1, 'cost' => 1);
				if(is_file(global_setting('DB_GLOBAL_TIME_FACTOR')) && is_readable(global_setting('DB_GLOBAL_TIME_FACTOR')))
				{
					$content = str_replace(',', '.', trim(file_get_contents(global_setting('DB_GLOBAL_TIME_FACTOR'))));
					if(strlen($content) > 0 && preg_match("/^[0-9]*(\.[0-9]+)?$/", $content))
						$factors['time'] = $content;
				}
				if(is_file(global_setting('DB_GLOBAL_PROD_FACTOR')) && is_readable(global_setting('DB_GLOBAL_PROD_FACTOR')))
				{
					$content = str_replace(',', '.', trim(file_get_contents(global_setting('DB_GLOBAL_PROD_FACTOR'))));
					if(strlen($content) > 0 && preg_match("/^[0-9]*(\.[0-9]+)?$/", $content))
						$factors['prod'] = $content;
				}
				if(is_file(global_setting('DB_GLOBAL_COST_FACTOR')) && is_readable(global_setting('DB_GLOBAL_COST_FACTOR')))
				{
					$content = str_replace(',', '.', trim(file_get_contents(global_setting('DB_GLOBAL_COST_FACTOR'))));
					if(strlen($content) > 0 && preg_match("/^[0-9]*(\.[0-9]+)?$/", $content))
						$factors['cost'] = $content;
				}
			}

			return $factors;
		}

		/**
		* Gibt zurueck, ob eine Handlungssperre in der Datenbank vorliegt.
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
		* Liefert die im Datenbankverzeichnis eingetragene Version zurueck.
		*/

		static function get_database_version()
		{
			if(is_file(global_setting("DB_DIR").'/.version'))
			{
				if(!is_readable(global_setting("DB_DIR").'/.version'))
				{
					fputs(STDERR, "Could not read ".global_setting("DB_DIR")."/.version.\n");
					exit(1);
				}
				$current_version = trim(file_get_contents(global_setting("DB_DIR").'/.version'));
			}
			elseif(file_exists(global_setting("DB_DIR").'/highscores') && !file_exists(global_setting("DB_DIR").'/highscores_alliances') && !file_exists(global_setting("DB_DIR").'/highscores_alliances2')) $current_version = '4';
			elseif(file_exists(global_setting("DB_DIR").'/events') && @sqlite_open(global_setting("DB_DIR").'/events')) $current_version = '3';
			elseif(is_dir(global_setting("DB_DIR").'/fleets')) $current_version = '2';
			else $current_version = '1';

			return $current_version;
		}

		/**
		* Überprüft, ob db_things/imserver gestartet ist.
		* @return boolean
		*/

		static function imserver_running()
		{
			if(!is_file(global_setting("DB_IMSERVER_PIDFILE")) || !is_readable(global_setting("DB_IMSERVER_PIDFILE")))
				return false;
			$fh = fopen(global_setting("DB_IMSERVER_PIDFILE"), "r");
			$running = !flock($fh, LOCK_EX + LOCK_NB);
			if(!$running) flock($fh, LOCK_UN);
			return $running;
		}
	}