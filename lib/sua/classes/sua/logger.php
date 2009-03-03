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
	 * Stellt eine einfache Möglichkeit zur Ausgabe von Log-Meldungen zur Verfügung. Standardmäßig
	 * wird ein Listener hinzugefügt, der die Meldung mit Zeit nach STDERR schreibt.
	*/

	class Logger implements StaticInit
	{
		private static $listeners = array();

		static function init()
		{
			self::$listeners[] = function($message){ foreach(preg_split("/\r\n|\r|\n/", $message) as $line){ $print = date("Y-m-d\\TH:i:s")."\t".$line; if(defined("STDERR")) fputs(STDERR, $print)."\n"; else echo $print."<br />\n"; } };
		}

		/**
		 * Fügt eine Callback-Funktion hinzu, der als Parameter die Meldung übergeben wird.
		 * @param callback $callback
		 * @return void
		*/

		static function addListener($callback)
		{
			self::$listeners[] = $callback;
		}

		/**
		 * Löscht alle existierenden Callback-Funktionen.
		 * @return void
		*/

		static function clearListeners()
		{
			self::$listeners = array();
		}

		/**
		 * Sendet eine Log-Meldung an alle Listeners.
		 * @param String $message
		 * @return void
		*/

		static function log($message)
		{
			foreach(self::$listeners as $listener)
				$listener($message);
		}
	}