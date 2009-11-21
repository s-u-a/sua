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
	 * Repräsentiert einen Zeitstempel.
	*/

	class Date
	{
		private $time;

		/**
		 * Führt strtotime() mit $time aus.
		 * @param string|int|null $time Wenn null, wird die aktuelle Zeit genommen.
		*/

		function __construct($time=null)
		{
			if(!isset($time))
				$this->time = time();
			elseif(is_numeric($time))
				$this->time = $time;
			else
				$this->time = strtotime($time);
		}

		static function fromPostgres($string)
		{
			return Classes::Date($string."Z");
		}

		/**
		 * Gibt den UNIX-Zeitstempel zurück.
		 * @return int
		*/

		function getTime()
		{
			return $this->time;
		}

		/**
		 * Gibt die Zeit im Standardformat zurück.
		 * @return string
		*/

		function getFormatted()
		{
			$return = date("Y-m-d\\TH:i:s", $this->time);
			if(!date("Z"))
				$return .= "Z";
			else
				$return .= date(" P");
			return $return;
		}

		/**
		 * Gibt die Zeit im Standardformat zurück, als GMT-Zeit.
		 * @return string
		*/

		function getFormattedGMT()
		{
			return gmdate("Y-m-d\\TH:i:s\\Z", $this->time);
		}

		/**
		 * Gibt die Zeit im lokalen Zeitformat zurück.
		 * @return string
		*/

		function getLocalised()
		{
			return date(_("Y-m-d\\TH:i:s"), $this->time);
		}

		/**
		 * To be used by SQL->quote().
		 * @return string
		*/
		function __toString()
		{
			return $this->getFormattedGMT();
		}

		function addSeconds($seconds)
		{
			$this->time += $seconds;
			return $this;
		}
	}