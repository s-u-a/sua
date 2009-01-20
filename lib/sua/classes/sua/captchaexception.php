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
	 * @subpackage exceptions
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	class CaptchaException extends SuaException
	{
		/**
		 * Beim Überprüfen des Captchas ist ein Verbindungsfehler aufgetreten.
		 * @var integer
		*/
		static $HTTP_ERROR = 1;

		/**
		 * Die Captcha-Konfiguration ist fehlerhaft.
		 * @var integer
		*/
		static $CONFIG_ERROR = 2;

		/**
		 * Der Benutzer hat falsche Daten eingegeben.
		 * @var integer
		*/
		static $USER_ERROR = 3;

		function __construct($message = null, $code = 0)
		{
			parent::__construct($message, $code);
		}
	}