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
	 * Leitet die Login-Anfrage an das entsprechende Script der Datenbankinstallation weiter. Wird benötigt, falls JavaScript nicht
	 * verfügbar ist und das action-Attribut des Login-Formulars nicht dynamisch verändert werden kann.
	 * @author Candid Dauth
	 * @package sua-homepage
	*/

	namespace sua\homepage;

	use sua\HTTPOutput;
	use sua\ConfigException;

	require('include.php');

	if(isset($_POST["database"]) && isset($_GET["action"]))
	{
		try
		{
			HTTPOutput::changeURL(HTTPOutput::getProtocol()."://".$CONFIG->getConfigValueE("databases", $_POST["database"], "urls", $_GET["action"]));
		}
		catch(ConfigException $e)
		{
		}
	}

	// Alles fehlgeschlagen
	HTTPOutput::changeURL(HTTPOutput::getProtocol()."://".$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"])."/");