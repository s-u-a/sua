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
	 * Repräsentiert eine XML-Konfigurationsdatei.
	*/

	class Config
	{
		private $config;
		private static $defaultConfig;

		/**
		 * Konvertiert eine Konfigurations-XML-Datei in ein Konfigurations-Array.
		 * @param string $filename
		 * @return array
		*/

		static function fileToConfig($filename)
		{
			$dom = new \DOMDocument();
			$dom->load($filename, LIBXML_DTDVALID | LIBXML_NOCDATA);
			$config = array();
			$cur_conf = &$config;
			$p = array();
			if(!$dom->firstChild || !$dom->firstChild->nextSibling || !$dom->firstChild->nextSibling->firstChild)
				return $config;

			$el = $dom->firstChild->nextSibling->firstChild;
			while($el->nodeType != 1) $el = $el->nextSibling;
			while(true)
			{
				$name = $el->getAttribute("name");
				if(isset($cur_conf[$name]))
					throw new ConfigException("Double definition of setting/section “".$name."”.");
				if($el->nodeName == "setting")
				{
					$value = $el->firstChild ? $el->firstChild->data : "";
					switch($el->getAttribute("type"))
					{
						case "date":
							$cur_conf[$name] = Classes::Date($value);
							break;
						case "number":
							$cur_conf[$name] = 0+$value;
							break;
						case "boolean":
							$cur_conf[$name] = Functions::string2boolean($value);
							break;
						case "formatted":
							$cur_conf[$name] = Classes::FormattedString($value);
							break;
						default:
							$cur_conf[$name] = $value;
							break;
					}
				}
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
				{
					$xml .= $tabs."<setting name=\"".htmlspecialchars($name)."\"";
					if($cur_config[$name] instanceof Date)
						$xml .= " type=\"date\">".htmlspecialchars($cur_config[$name]->getFormattedGMT());
					elseif($cur_config[$name] instanceof FormattedString)
						$xml .= " type=\"formatted\">".htmlspecialchars($cur_config[$name]->getRawString());
					elseif(is_bool($cur_config[$name]))
						$xml .= " type=\"boolean\">".($cur_config[$name] ? "yes" : "no");
					elseif(is_numeric($cur_config[$name]))
						$xml .= " type=\"number\">".htmlspecialchars($cur_config[$name]);
					else
						$xml .= htmlspecialchars($cur_config[$name]);
					$xml .= "</setting>\n";
				}

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
		 * @param string $filename Der Dateiname der XML-Datei
		*/

		function __construct($filename)
		{
			$this->filename = $filename;
			$cache_file = $this->filename.".cache";
			if(!is_file($cache_file) || !is_readable($cache_file) || filemtime($this->filename) > filemtime($cache_file))
			{
				$this->config = self::fileToConfig($this->filename);
				file_put_contents($cache_file, serialize($this->config));
			}
			else
				$this->config = unserialize(file_get_contents($cache_file));
		}

		/**
		 * Gibt einen Wert aus der Konfiguration zurück. Es werden beliebig viele Parameter übergeben, die den Pfad im Config-Array
		 * angeben.
		 * @return string|null Null, wenn der Wert nicht existiert
		*/

		function getConfigValue()
		{
			try
			{
				return call_user_func_array(array($this, "getConfigValueE"), func_get_args());
			}
			catch(ConfigException $e)
			{
				if($e->getCode() == ConfigException::VALUE_NOT_FOUND)
					return null;
				throw $e;
			}
		}

		/**
		 * Wie Config::getConfigValue(), wirft aber eine Exception, wenn der Konfigurationswert nicht gefunden wurde.
		 * @throw ConfigException
		 * @return string
		*/

		function getConfigValueE()
		{
			$ref = &$this->config;
			foreach(func_get_args() as $arg)
			{
				if(!isset($ref[$arg]))
					throw new ConfigException("Config value not found.", ConfigException::VALUE_NOT_FOUND);
				$ref = &$ref[$arg];
			}
			return $ref;
		}

		/**
		 * Setzt einen Wert in der Konfiguration. Wie Config::getConfigValue(), der letzte Wert ist allerdings
		 * der neue Wert für die Einstellung. Ist der neue Wert null, wird der Eintrag gelöscht, falls er existiert.
		 * @return void
		*/

		function setConfigValue()
		{
			$path = func_get_args();
			if(count($path) < 2)
				throw new InvalidArgumentException("Expecting at least 2 arguments.");

			$new_value = array_pop($path);
			$last_arg = array_pop($path);
			$ref = &$this->config;
			foreach($path as $arg)
			{
				if(!isset($ref[$arg]))
					$ref[$arg] = array();
				$ref = &$ref[$arg];
			}
			if(!isset($new_value))
			{
				if(isset($ref[$last_arg]))
					unset($ref[$last_arg]);
			}
			else
				$ref = $new_value;
			$this->setConfig(null);
		}

		/**
		 * Gibt die Konfiguration zurück.
		 * @return array
		*/

		function getConfig()
		{
			return $this->config;
		}

		/**
		 * Speichert eine neue Spielkonfiguration ab.
		 * @param array|null $config Wenn null, wird die aktuelle Konfiguration neu geschrieben (hauptsächlich zur internen Verwendung)
		 * @return void
		*/

		function setConfig($config)
		{
			$write_config = (isset($config) ? $config : $this->config);
			self::configToFile($this->filename, $write_config);
			file_put_contents($this->filename.".cache", serialize($write_config));
			if(isset($config))
				$this->config = $config;
		}

		/**
		 * Gibt ein Config-Objekt der config.xml im Lib-Verzeichnis zurück.
		 * @return Config
		*/

		static function getLibConfig()
		{
			if(!self::$defaultConfig)
				self::$defaultConfig = Classes::Config(LIBDIR."/config.xml");
			return self::$defaultConfig;
		}
	}