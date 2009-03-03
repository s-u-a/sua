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
	 * Stellt statische Funktionen zur Verfügung, um die Ausgabe über HTTP zu kontrollieren. Je nachdem, welche Sachen benötigt werden,
	 * können diese Funktionen unabhängig voneinander aufgerufen werden.
	*/

	class HTTPOutput
	{
		/**
		 * Korrigiert $_SERVER["SCRIPT_FILENAME"], da es auf manchen Servern falsch ist.
		 * @todo
		 * @return void
		*/

		/*public static function correctScriptFilename()
		{
			if(!isset($_SERVER["SCRIPT_FILENAME"]) && substr($_SERVER["PHP_SELF"], 0, strlen(h_root)) == $_SERVER["PHP_SELF"])
				$_SERVER["SCRIPT_FILENAME"] = dirname(__FILE__).substr($_SERVER["PHP_SELF"], strlen(h_root));
		}*/

		/**
		 * Berechnet den Pfad, über den das übergebene Verzeichnis auf der Festplatte über den HTTP-Server aufgerufen werden kann.
		 * @param string $directory Ein Verzeichnis auf der lokalen Festplatte
		 * @return string
		*/

		public static function getHPath($directory)
		{
			if(isset($_SERVER["SCRIPT_FILENAME"]) && isset($_SERVER["PHP_SELF"]) && substr($_SERVER["SCRIPT_FILENAME"], -strlen($_SERVER["PHP_SELF"])) == $_SERVER["PHP_SELF"])
				$document_root = substr($_SERVER["SCRIPT_FILENAME"], 0, -strlen($_SERVER["PHP_SELF"]));
			elseif(isset($_SERVER["DOCUMENT_ROOT"]) && substr($directory, strlen($tmp = realpath($_SERVER["DOCUMENT_ROOT"]))) == $tmp)
				$document_root = $_SERVER["DOCUMENT_ROOT"];
			else $document_root = "/"; // TODO: Hier eine Exception werfen?
			if(substr($document_root, -1) == "/")
				$document_root = substr($document_root, 0, -1);
			$document_root = realpath($document_root);
			return substr($directory, strlen($document_root));
		}

		/**
		 * Schaltet magic_quotes_gpc ab, wenn diese aktiviert sind. Bearbeitet dazu $_GET, $_POST und $_COOKIES.
		 * @return void
		*/

		public static function disableMagicQuotes()
		{
			if(get_magic_quotes_gpc())
			{
				$in = array(&$_GET, &$_POST, &$_COOKIE, &$_FILES, &$_REQUEST);
				while(list($k,$v) = each($in))
				{
					foreach($v as $key => $val)
					{
						if(!is_array($val))
						{
							$in[$k][$key] = stripslashes($val);
							continue;
						}
						$in[] = &$in[$k][$key];
					}
				}
				unset($in);
			}
		}

		/**
		 * Sendet den richtigen Content-Type mit Charset.
		 * @param boolean $xhtml Soll, wenn der Browser es unterstützt, ein XHTML-Mime-Type statt HTML gesendet werden?
		 * @return void
		*/

		public static function sendContentType($xhtml=true)
		{
			// TODO: Get rid of document.write and innerHTML
			if($xhtml && isset($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml") !== false)
				header("Content-type: application/xhtml+xml; charset=UTF-8");
			else
				header("Content-type: text/html; charset=UTF-8");
		}

		/**
		 * Aktiviert die GZip-Kompression über HTTP.
		 * @return void
		*/

		public static function enableGZip()
		{
			ob_start("ob_gzhandler");
		}

		/**
		 * Gibt zurück, welches Protokoll gerade verwendet wird, entweder http oder https.
		 * @return string
		*/

		public static function getProtocol()
		{
			if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
				return "https";
			else
				return "http";
		}

		/**
		 * Leitet auf eine URL weiter.
		 * @param string $new_url
		 * @param boolean $keep_post_data POST-Daten erhalten?
		 * @return void
		*/

		static function changeURL($new_url, $keep_post_data=true)
		{
			if(!defined("DEBUG") || !DEBUG)
				header('Location: '.$new_url, true, $keep_post_data ? 307 : 303);

			if(count($_POST) > 0 && $keep_post_data)
			{
				echo '<form action="'.htmlspecialchars($new_url).'" method="post">';
				foreach($_POST as $key=>$val)
					echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'" />';
				echo '<button type="submit">HTTP redirect: '.htmlspecialchars($new_url).'</button>';
				echo '</form>';
			}
			else
				echo 'HTTP redirect: <a href="'.htmlspecialchars($new_url).'">'.htmlspecialchars($new_url).'</a>';
			die();
		}

		/**
		 * Leitet auf die gleiche URL unter einem anderen Hostname weiter, alle GET- und POST-Daten sollen erhalten bleiben.
		 * Ist der Hostname bereits aufgerufen, wird nichts unternommen.
		 * @param string $hostname Der neue Hostname.
		 * @return void
		*/

		static function changeHostname($hostname)
		{
			if(isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"] == $hostname)
				return;

			$url = self::getProtocol()."://".$hostname.$_SERVER["PHP_SELF"];
			if($_SERVER['QUERY_STRING'] != '')
				$url .= '?'.$_SERVER['QUERY_STRING'];
			self::changeURL($url);
		}

		/**
		 * Entfernt alle GET- und POST-Daten aus der URL.
		 * @return void
		*/

		static function removeRequest()
		{
			self::changeURL(self::getProtocol()."://".$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"], false);
		}

		/**
		 * Gibt die vollständige aufgerufene URL zurück.
		 * @param boolean $query_string Sollen die GET-Parameter mitgeliefert werden?
		 * @return string
		*/

		static function getURL($query_string=true)
		{
			return self::getProtocol()."://".$_SERVER["HTTP_HOST"].($query_string ? $_SERVER["REQUEST_URI"] : $_SERVER["PHP_SELF"]);
		}

		/**
		 * Konvertiert einen Query-String (zum Beispiel $_SERVER["QUERY_STRING"]) in ein Array (zum Beispiel
		 * $_GET).
		 * @param string $string
		 * @param boolean $multidimensional Sollen URL-Parameter wie test[0] so bleiben oder auch in ein Array konvertiert werden?
		 * @return array
		*/

		static function queryStringToArray($string, $multidimensional=true)
		{
			$return = array();
			$parts = preg_split("/[&;]/", $string);
			foreach($parts as $part)
			{
				$part = explode("=", $part, 2);
				if(count($part) < 2)
					continue;
				$part[0] = urldecode($part[0]);
				$part[1] = urldecode($part[1]);
				if($multidimensional && preg_match("/(\\[[^\\]]*\\])+\$/", $part[0], $m))
				{
					$value = &$return[substr($part[0], 0, -strlen($m[0]))];
					$array_dimensions = explode("][", substr($m[0], 1, -1));
				}
				else
				{
					$value = &$return[$part[0]];
					$array_dimensions = array();
				}

				foreach($array_dimensions as $array_dimension)
				{
					if(!isset($value)) $value = array();
					$value = &$value[$array_dimension];
				}

				$value = $part[1];
			}
			return $return;
		}

		/**
		 * Konvertiert ein Array (wie $_GET) in einen Query-String (zum Beispiel $_SERVER["QUERY_STRING"]).
		 * @param array $array
		 * @param string $pattern Ein sprintf-Pattern, durch das die Array-Indexe geleitet werden.
		 * @return string
		*/

		static function arrayToQueryString(array $array, $pattern="%s")
		{
			$return = array();
			foreach($array as $k=>$v)
			{
				$index = sprintf($pattern, rawurlencode($k));
				if(is_array($v))
					$return[] = self::arrayToQueryString($v, str_replace("%", "%%", $index)."[%s]");
				else
					$return[] = $index."=".urlencode($v);
			}
			return implode("&", $return);
		}
	}