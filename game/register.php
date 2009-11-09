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
	 * Versucht, einen Benutzeraccount zu registrieren und leitet dann mit einer Meldung auf die Registrierseite
	 * zurück.
	 * @author Candid Dauth
	 * @package sua
	*/
	namespace sua\psua;

	use \sua\User;
	use \sua\SuaException;
	use \sua\PlanetException;
	use \sua\HTTPOutput;
	use \sua\Classes;
	use \sua\Planet;
	use \sua\L;

	require("include.php");

	try
	{
		if(!isset($_REQUEST["username"]) || strlen(trim($_REQUEST["username"])) < 1 || !isset($_REQUEST["password"]) || !isset($_REQUEST["password2"]))
			throw new SuaException(0);
		if(!isset($_REQUEST["nutzungsbedingungen"]) || !$_REQUEST["nutzungsbedingungen"])
			throw new SuaException(1);
		elseif(preg_match('/[\xf8-\xff\x00-\x1f\x7f]/', $_REQUEST['username'])) # Steuerzeichen
			throw new SuaException(2);
		elseif($_REQUEST['password'] != $_REQUEST['password2'])
			throw new SuaException(3);

		$_REQUEST['username'] = str_replace("\x0a", ' ', trim($_REQUEST['username'])); # nbsp

		if(User::exists($_REQUEST['username']))
			throw new SuaException(4);
		elseif(substr($_REQUEST['username'], -4) == ' (U)')
			throw new SuaException(5);
		elseif(substr($_REQUEST['username'], -4) == ' (g)')
			throw new SuaException(6);
		elseif($_REQUEST["username"] == "0")
			throw new SuaException(7);
		else
		{
			$user_obj = Classes::User(User::create($_REQUEST['username']));

			try
			{
				$koords = Planet::randomFreePlanet();
				$koords->colonise($user_obj->getName());

				$koords->addRess(array(20000, 10000, 7500, 5000, 2000));

				$user_obj->setPassword($_REQUEST['password']);

				if(isset($_REQUEST['email']))
					$user_obj->setEMailAddress($_REQUEST['email']);

				# Planetenname
				if(!isset($_REQUEST["hauptplanet"]) || trim($_REQUEST['hauptplanet']) == '')
					$koords->setGivenName(_('Hauptplanet'));
				else $koords->setGivenName($_REQUEST['hauptplanet']);

				$GUI->setOption("login_username", $_REQUEST["username"]);
				$GUI->init();
?>
<p class="successful"><?=L::h(sprintf(_("Die Registrierung war erfolgreich. Sie können sich nun anmelden. Die Koordinaten Ihres Hauptplaneten lauten %s."), $koords->format()))?></p>
<?php
				$GUI->loginFormular();
			}
			catch(PlanetException $e)
			{
				$user_obj->destroy();
				throw new SuaException(8);
			}
		}
	}
	catch(SuaException $e)
	{
		if(isset($_REQUEST["referrer"]))
		{
			$url1 = explode("#", $_REQUEST["referrer"], 2);
			$url2 = explode("?", $url1[0], 2);
			if(count($url2) < 2)
				$url2[1] = "";
			$query = $_REQUEST;
			$query += HTTPOutput::queryStringToArray($url2[1]);

			if(isset($query["password"]))
				unset($query["password"]);
			if(isset($query["password2"]))
				unset($query["password2"]);
			unset($query["referrer"]);

			$query["error"] = $e->getMessage();

			$url2[1] = HTTPOutput::arrayToQueryString($query);
			$url1[0] = implode("?", $url2);
			HTTPOutput::changeURL(implode("#", $url1), false);
		}
		else
			$GUI->fatal(_("Registrierung fehlgeschlagen"));
	}