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
	 * @subpackage gui
	*/

	namespace sua;
	require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	 * Stellt Hilfsfuntionen für die Ausgabe von JavaScript-Code zur Verfügung.
	 * Ergänzt daher die Funktionen, die PHP zur HTML-Ausgabe bereitstellt.
	*/

	class JS
	{
		/**
		 * Escapt alle ' und \ mit einem Backslash, sodass der String in JavaScript innerhalb von einfachen Anfuehrungszeichen verwendet werden kann.
		 * @param string $string
		 * @return string
		*/

		static function jsentities($string)
		{
			return preg_replace("/['\\\\]/", "\\\\\$1", $string);
		}

		/**
		 * Implodiert ein assoziatives Array und gibt den Code für ein JavaScript-Object seiner Entsprechung zurück.
		 * @param array $array
		 * @return string
		*/

		static function aimplode_js(array $array)
		{
			$string = array();
			foreach($array as $k=>$v)
				$string[] = "'".JS::jsentities($k)."' : ".(is_array($v) ? js_assoc_implode($v) : "'".JS::jsentities($v)."'");
			return $string = "{ ".implode(", ", $string)." }";
		}

		/**
		 * Implodiert ein assoziatives Array und gibt den Code für einen Query-String zurück.
		 * @param array $array
		 * @param string $prefix Eventueller Prefix für den Index der URL-Parameter
		 * @return string
		*/

		static function aimplode_url(array $array, $prefix="%s")
		{
			$string = array();
			foreach($array as $k=>$v)
			{
				if(is_array($v))
					$string = array_merge($string, aimplode_url($v, sprintf($prefix, urlencode($k))."[%s]"));
				else
					$string[] = sprintf($prefix, urlencode($k))."=".urlencode($v);
			}
			return $string = implode("&", $string);
		}
	}