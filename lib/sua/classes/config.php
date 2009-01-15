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

	namespace sua;
	//require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	 * Stellt statische Funktionen zur Verfügung, um die Konfiguration des Spiels auszulesen
	 * und zu schreiben.
	*/

	class Config
	{
		static private $config;

		/**
		 * Konvertiert eine Konfigurations-XML-Datei in ein Konfigurations-Array.
		 * @param string $filename
		 * @return array
		*/

		static function fileToConfig($filename)
		{
			$dom = new DOMDocument();
			$dom->load($filename, LIBXML_DTDVALID | LIBXML_NOCDATA);
			$config = array();
			$cur_conf = &$config;
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
			return $config;
		}

		/**
		 * Speichert eine Konfiguration als XML ab.
		 * @param string $filename Der Dateiname der XML-Datei.
		 * @param array $config
		 * @return void
		*/

		static function configToFile($filename, array $config)
		{
			$xml = "<?xml version=\"1.0\"?>\n";
			$xml .= "<!DOCTYPE config [\n";
			$xml .= "\t<!ELEMENT config (section | setting)*>\n";
			$xml .= "\t<!ELEMENT section (section | setting)*>\n";
			$xml .= "\t<!ATTLIST section\n";
			$xml .= "\t\tname CDATA #REQUIRED\n";
			$xml .= "\t>\n";
			$xml .= "\t<!ELEMENT setting (#PCDATA)>\n";
			$xml .= "\t<!ATTLIST setting\n";
			$xml .= "\t\tname CDATA #REQUIRED\n";
			$xml .= "\t>\n";
			$xml .= "]>\n";
			$xml .= "<config>\n";

			$cur_config = &$config;
			$parents = array();
			$tabs = "\t";
			while(true)
			{
				$name = key($cur_config);

				if(is_array($cur_config[$name]))
				{
					$xml .= $tabs."<section name=\"".htmlspecialchars($name)."\">\n";
					if(count($cur_config[$name]) > 0)
					{
						$parents[] = &$cur_config;
						$cur_config = &$cur_config[$name];
						$tabs .= "\t";
						continue;
					}
					else
						$xml .= $tabs."</section>\n";
				}
				else
					$xml .= $tabs."<setting name=\"".htmlspecialchars($name)."\">".htmlspecialchars($cur_config[$name])."</setting>\n";

				while(next($cur_config) === false)
				{
					if(count($parents) < 1)
						break 2;
					$cur_config = &$parents[count($parents)-1];
					array_pop($parents);
					$tabs = str_repeat("\t", count($parents)+1);
					$xml .= $tabs."</section>\n";
				}
			}
			$xml .= "</config>\n";
			file_put_contents($filename, $xml);
		}

		/**
		 * Gibt die Spielkonfiguration als Array zurück.
		 * @return array
		*/

		static function getConfig()
		{
			if(!isset(self::$config))
			{
				if(!is_file(global_setting("DB_CONFIG_CACHE")) || !is_readable(global_setting("DB_CONFIG_CACHE")) || filemtime(global_setting("DB_CONFIG_CACHE")) < filemtime(global_setting("DB_CONFIG")))
				{
					self::$config = self::fileToConfig(global_setting("DB_CONFIG"));
					file_put_contents(global_setting("DB_CONFIG_CACHE"), serialize(self::$config));
				}
				else
					self::$config = unserialize(file_get_contents(global_setting("DB_CONFIG_CACHE")));
			}
			return self::$config;
		}

		/**
		 * Speichert eine neue Spielkonfiguration ab.
		 * @param array $config
		 * @return void
		*/

		static function setConfig(array $config)
		{
			self::configToFile(global_setting("DB_CONFIG"), $config);
			file_put_contents(global_setting("DB_CONFIG_CACHE"), serialize(self::$config));
			self::$config = $config;
		}

		/**
		 * Sucht nach installierten Skins und liefert ein Array des folgenden
		 * Formats zurueck:
		 * ( ID => [ Name, ( Einstellungsname => ( moeglicher Wert ) ) ] )
		 * @return array
		*/

		static function getSkins()
		{
			# Vorgegebene Skins-Liste bekommen
			$skins = array();
			if(is_dir(global_setting("s_root")."/login/res/style") && is_readable(global_setting("s_root")."/login/res/style"))
			{
				$dh = opendir(global_setting("s_root")."/login/res/style");
				while(($fname = readdir($dh)) !== false)
				{
					if($fname[0] == ".") continue;
					$path = global_setting("s_root")."/login/res/style/".$fname;
					if(!is_dir($path) || !is_readable($path)) continue;
					if(!is_file($path."/types") || !is_readable($path."/types")) continue;
					$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents($path."/types"));
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
		 * @return string
		*/

		static function getVersion()
		{
			$version = "";
			if(is_file(global_setting("DB_VERSION")) && is_readable(global_setting("DB_VERSION")))
				$version = trim(file_get_contents(global_setting("DB_VERSION")));
			return $version;
		}

		/**
		* Liefert den Hostname zurueck, auf dem die Hauptseite laeuft.
		* @return (string)
		* @return void bei Fehlschlag
		*/

		static function getDefaultHostname()
		{
			$config = self::getConfig();
			if(isset($config["hostname"])) return $config["hostname"];
			elseif(isset($_SERVER["HTTP_HOST"])) return $_SERVER["HTTP_HOST"];
			else return null;
		}

		/**
		 * Überprüft, ob db_things/imserver.phpc gestartet ist.
		 * @return bool
		*/

		static function imserverRunning()
		{
			if(!is_file(global_setting("DB_IMSERVER_PIDFILE")) || !is_readable(global_setting("DB_IMSERVER_PIDFILE")))
				return false;
			$fh = fopen(global_setting("DB_IMSERVER_PIDFILE"), "r");
			$running = !flock($fh, LOCK_EX + LOCK_NB);
			if(!$running) flock($fh, LOCK_UN);
			return $running;
		}
	}