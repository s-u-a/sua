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
	 * Stellt Funktionen zur Verfügung, um Daten an Sprach- und Lokaleinstellungen
	 * anzupassen.
	*/

	class L implements StaticInit
	{
		static function init()
		{
			bindtextdomain("sua", LIBDIR."/locale");
			bind_textdomain_codeset("sua", "utf-8");
			textdomain("sua");
		}

		/**
		 * Hebt in einem String $text Tastenkuerzel, welche durch ein voranstehendes &amp; gekennzeichnet sind, hervor, und Kodiert HTML-Steuerzeichen mit htmlspecialchars().
		 * @param string $text
		 * @param bool $make_text Wenn true (standardmäßig), wird das Kuerzel durch ein kbd-HTML-Tag hervorgehoben. Wenn false, wird es als ' [' Kuerzel ']' angehaengt.
		 * @return string
		*/

		static function h($text, $make_tags=true)
		{
			if($make_tags)
				return preg_replace("/&amp;([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß])/", "<kbd>$1</kbd>", htmlspecialchars($text));
			elseif(preg_match("/^(.*?)&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)(.*)\$/", $text, $m))
			{
				if(preg_match("/\\[&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)\\]/", $text))
					return htmlspecialchars($m[1].$m[2].$m[3]);
				else
					return htmlspecialchars($m[1].$m[2].$m[3])." [".htmlspecialchars(str_replace(array("ä", "ö", "ü"), array("Ä", "Ö", "Ü"), strtoupper($m[2])))."]";
			}
			else
				return htmlspecialchars($text);
		}

		/**
		 * Liefert den HTML-Code des Attributs fuer das in $message angegebene Tastenkuerzel (durch ein voranstehende & gekennzeichnet) zurueck.
		 * @param string $message
		 * @return string Zum Beispiel ' accesskey="a"'. Wenn kein Tastenkuerzel existiert, ''.
		*/

		static function accesskeyAttr($message)
		{
			if(!preg_match("/&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)/", $message, $m))
				return "";
			return " accesskey=\"".htmlspecialchars(str_replace(array("Ä", "Ö", "Ü"), array("ä", "ö", "ü"), strtolower($m[1])))."\"";
		}

		/**
		* Liefert das Titel-HTML-Attribut zur Darstellung eines Tastenkuerzels (in $message durch ein voranstehendes &amp; markiert) zurueck.
		* @param string $message
		* @return string Zum Beispiel ' title="[A]"'. Wenn kein Tastenkuerzel existiert, ''.
		*/

		static function accesskeyTitle($message)
		{
			if(!preg_match("/&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)/", $message, $m))
				return "";
			return " title=\"[".htmlspecialchars(str_replace(array("ä", "ö", "ü"), array("Ä", "Ö", "Ü"), strtoupper($m[1])))."]\"";
		}

		/**
		 * Setzt die Sprach-Locale fuer die uebergebene Sprache. Dadurch liefert gettext die Nachrichten in der neuen Sprache zurueck.
		 * @param string $lang
		 * @param bool $die Das Script wird beendet, wenn die Sprache nicht ausgewählt werden kann
		 * @return string|bool Konnte die Sprache gesetzt werden?
		*/

		static function language($lang=null, $die=false)
		{
			$languages = array (
				"de_DE" => array("de_DE.utf8", "de_DE@utf8", "de_DE", "de", "german", "ger", "deutsch", "deu")
			);

			if($lang === null)
				return getenv("LANGUAGE");

			if(!isset($languages[$lang]) || !($locale = setlocale(LC_MESSAGES, $languages[$lang])))
			{
				if($die) die("Could not set language to ".$lang."!");
				return false;
			}
			putenv("LANGUAGE=".$lang);
			putenv("LANG=".$lang);
			putenv("LC_MESSAGES=".$locale);
			$_ENV["LANGUAGE"] = $_ENV["LANG"] = $lang;
			$_ENV["LC_MESSAGES"] = $locale;
			return true;
		}

		/**
		 * Setzt oder liest die aktuelle Zeitzone.
		 * @param string $timezone
		 * @return string|void
		*/

		static function timezone($timezone=null)
		{
			if(isset($timezone))
				date_default_timezone_set($timezone);
			else
				return date_default_timezone_get();
		}

		/**
		 * Ersetzt Dinge wie [item_B0] durch den entsprechenden gettext-String.
		 * @param string $string
		 * @param bool $links Sollen die Dinge durch Links auf die Beschreibung ersetzt werden?
		 * @return string
		*/

		static function _I($string, $links=true)
		{
			return preg_replace("/\\[(item|ress)_([a-zA-Z0-9]+)([-a-zA-Z0-9_]*)\\]/e", ($links?"'<a href=\"".global_setting("h_root")."/login/info/description.php?id=\$2&amp;".htmlspecialchars(global_setting("URL_SUFFIX"))."\">'.h(":"")."_('\$0')".($links?").'</a>'" : ""), $string);
		}
	}