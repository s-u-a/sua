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

	class l
	{
		/**
		* Hebt in einem String $text Tastenkuerzel, welche durch ein voranstehendes &amp; gekennzeichnet sind, hervor, und Kodiert HTML-Steuerzeichen mit htmlspecialchars().
		* @param $make_text Wenn true, wird das Kuerzel durch ein kbd-HTML-Tag hervorgehoben. Wenn false, wird es als ' [' Kuerzel ']' angehaengt.
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
		* Liefert den HTML-Code des Attributs fuer das in $message angegebene Tastenkuerzel (durch ein voranstehende &amp; gekennzeichnet) zurueck.
		* @return Zum Beispiel ' accesskey="a"'. Wenn kein Tastenkuerzel existiert, ''.
		*/

		static function accesskey_attr($message)
		{
			if(!preg_match("/&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)/", $message, $m))
				return "";
			return " accesskey=\"".htmlspecialchars(str_replace(array("Ä", "Ö", "Ü"), array("ä", "ö", "ü"), strtolower($m[1])))."\"";
		}

		/**
		* Liefert das Titel-HTML-Attribut zur Darstellung eines Tastenkuerzels (in $message durch ein voranstehendes &amp; markiert) zurueck.
		* @return Zum Beispiel ' title="[A]"'. Wenn kein Tastenkuerzel existiert, ''.
		*/

		static function accesskey_title($message)
		{
			if(!preg_match("/&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)/", $message, $m))
				return "";
			return " title=\"[".htmlspecialchars(str_replace(array("ä", "ö", "ü"), array("Ä", "Ö", "Ü"), strtoupper($m[1])))."]\"";
		}

		/**
		* Setzt die Sprach-Locale fuer die uebergebene Sprache. Dadurch liefert gettext die Nachrichten in der neuen Sprache zurueck.
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
		* Ersetzt Dinge wie [item_B0] durch den entsprechenden gettext-String.
		* @param $links (boolean) Sollen die Dinge durch Links auf die Beschreibung ersetzt werden?
		*/

		static function _i($string, $links=true)
		{
			return preg_replace("/\\[(item|ress)_([a-zA-Z0-9]+)([-a-zA-Z0-9_]*)\\]/e", ($links?"'<a href=\"".h_root."/login/info/description.php?id=\$2&amp;".htmlspecialchars(global_setting("URL_SUFFIX"))."\">'.h(":"")."_('\$0')".($links?").'</a>'" : ""), $string);
		}
	}