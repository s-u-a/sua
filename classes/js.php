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

	class JS
	{
		/**
		* Escapt alle ' und \ mit einem Backslash, sodass der String in JavaScript innerhalb von einfachen Anfuehrungszeichen verwendet werden kann.
		*/

		static function jsentities($string)
		{
			return preg_replace("/['\\\\]/", "\\\\\$1", $string);
		}

		/**
		* Implodiert ein assoziatives Array und gibt den Code für ein JavaScript-Object seiner Entsprechung zurück.
		*/

		static function aimplode_js($array)
		{
			$string = array();
			foreach($array as $k=>$v)
				$string[] = "'".JS::jsentities($k)."' : ".(is_array($v) ? js_assoc_implode($v) : "'".JS::jsentities($v)."'");
			return $string = "{ ".implode(", ", $string)." }";
		}

		/**
		* Implodiert ein assoziatives Array und gibt den Code für einen Query-String zurück.
		*/

		static function aimplode_url($array, $prefix="%s")
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