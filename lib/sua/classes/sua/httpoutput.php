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
			$stripslashes_r = function&(&$var, $stripslashes_r)
			{ # Macht rekursiv in einem Array addslashes() rueckgaengig
				if(is_array($var))
				{
					foreach($var as $key=>$val)
						$stripslashes_r($var[$key], $stripslashes_r);
				}
				else
					$var = stripslashes($var);
				return $var;
			};

			# magic_quotes_gpc abschalten
			if(get_magic_quotes_gpc())
			{
				$stripslashes_r($_POST, $stripslashes_r);
				$stripslashes_r($_GET, $stripslashes_r);
				$stripslashes_r($_COOKIE, $stripslashes_r);
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
			//if(isset($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml") !== false)
				//header("Content-type: application/xhtml+xml; charset=UTF-8");
			//else
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
	}