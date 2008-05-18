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
	 * Formular zur Registrierung.
	 * @author Candid Dauth
	 * @package sua-frontend
	 * @subpackage home
	*/

	namespace sua::frontend;

	require('include.php');

	$databases = Config::get_databases();

	$gui->init();
?>
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Registrieren")))?></h2>
<?php
	if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['password2']) && isset($_POST['database']) && isset($databases[$_POST['database']]) && $databases[$_POST['database']]['enabled'])
	{
		define_globals($_POST['database']);

		$error = '';

		if(!isset($_POST['nutzungsbedingungen']) || !$_POST['nutzungsbedingungen'])
			$error = _('Sie müssen die Nutzungsbedingungen lesen und akzeptieren, um am Spiel teilnehmen zu können.');
		elseif(strlen(trim($_POST['username'])) > 24)
			$error = _('Der Benutzername darf maximal 24 Bytes groß sein.');
		elseif(strlen(trim($_POST['hauptplanet'])) > 24)
			$error = _('Der Name des Hauptplanets darf maximal 24 Bytes groß sein.');
		elseif(preg_match('/[\xf8-\xff\x00-\x1f\x7f]/', $_POST['username'])) # Steuerzeichen
			$error = _('Der Benutzername enthält ungültige Zeichen.');
		elseif($_POST['password'] != $_POST['password2'])
			$error = _('Die beiden Passworte stimmen nicht überein.');
		else
		{
			$_POST['username'] = str_replace("\x0a", ' ', trim($_POST['username'])); # nbsp

			if(User::UserExists($_POST['username']))
				$error = _('Dieser Spieler existiert bereits. Bitte wählen Sie einen anderen Namen.');
			elseif(substr($_POST['username'], -4) == ' (U)')
				$error = _('Der Benutzername darf nicht auf (U) enden.');
			elseif(substr($_POST['username'], -4) == ' (g)')
				$error = _('Der Benutzername darf nicht auf (g) enden.');
			else
			{
				$user_obj = Classes::User($_POST['username']);
				if(!$user_obj->create())
					$error = _('Datenbankfehler beim Anlegen des Benutzeraccounts.');

				# Koordinaten des Hauptplaneten bestimmen

				$galaxies_count = Galaxy::getGalaxiesCount();
				$galaxies = array();
				for($i=1; $i<=$galaxies_count; $i++)
					$galaxies[] = $i;
				shuffle($galaxies);

				$koords = false;
				foreach($galaxies as $galaxy)
				{
					$galaxy_obj = Classes::Galaxy($galaxy);
					if(!$galaxy_obj->getStatus()) continue;
					$systems_count = $galaxy_obj->getSystemsCount();
					$systems = array();
					for($i=1; $i<=$systems_count; $i++)
						$systems[] = $i;
					shuffle($systems);

					foreach($systems as $system)
					{
						$planets_count = $galaxy_obj->getPlanetsCount($system);
						$empty_planets = array();
						for($i=0; $i<$planets_count; $i++)
						{
							if($galaxy_obj->getPlanetOwner($system, $i) === '') $empty_planets[] = $i;
						}
						if(count($empty_planets) > 0)
						{
							$koords = $galaxy.':'.$system.':'.$empty_planets[array_rand($empty_planets)];
							break 2;
						}
					}
				}

				if(!$koords)
				{
					$error = _('Es gibt keine freien Planeten mehr.');
					$user_obj->destroy();
				}
				else
				{
					$index = $user_obj->registerPlanet($koords);
					if($index === false)
					{
						$error = _('Der Hauptplanet konnte nicht besiedelt werden.');
						$user_obj->destroy();
					}

					$user_obj->setActivePlanet($index);

					$user_obj->addRess(array(20000, 10000, 7500, 5000, 2000));
					$user_obj->setPassword($_POST['password']);

					if(isset($_POST['email']))
						$user_obj->setEMailAddress($_POST['email']);

					# Planetenname
					if(trim($_POST['hauptplanet']) == '')
						$user_obj->planetName('Hauptplanet');
					else $user_obj->planetName($_POST['hauptplanet']);
?>
<p class="successful"><?=h(sprintf(_("Die Registrierung war erfolgreich. Sie können sich nun anmelden. Die Koordinaten Ihres Hauptplaneten lauten %s."), vsprintf(_("%d:%d:%d"), explode(":", $koords))))?></p>
<ul>
	<li><a href="index.php"<?=l::accesskey_attr(_("Zurück zur Startseite&[register.php|1]"))?>><?=h(_("Zurück zur Startseite&[register.php|1]"))?></a></li>
</ul>
<?php
					$gui->end();
					exit();
				}
			}
		}
		if($error != '')
		{
?>
<p class="error"><?=htmlspecialchars($error)?></p>
<?php
		}
	}

?>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/register.php')?>" method="post" id="register-form">
	<fieldset>
		<legend><?=h(_("Registrieren"))?></legend>
		<dl>
			<dt><label for="runde"><?=h(_("Runde&[register.php|2]"))?></label></dt>
			<dd><select name="database" id="runde"<?=l::accesskey_attr(_("Runde&[register.php|2]"))?>>
<?php
	foreach($databases as $id=>$info)
	{
		if(!$info['enabled'] || $info['dummy']) continue;
?>
				<option value="<?=htmlspecialchars($id)?>"<?=(isset($_POST['database']) && $_POST['database'] == $id) ? ' selected="selected"' : ''?>><?=htmlspecialchars($info['name'])?></option>
<?php
	}
?>
			</select></dd>

			<dt><label for="username"><?=h(_("Benutzername&[register.php|2]"))?></label></dt>
			<dd><input type="text" id="username" name="username"<?=l::accesskey_attr(_("Benutzername&[register.php|2]"))?><?=isset($_POST['username']) ? ' value="'.htmlspecialchars($_POST['username']).'"' : ''?> maxlength="24" /></dd>

			<dt><label for="password"><?=h(_("Passwort&[register.php|2]"))?></label></dt>
			<dd><input type="password" id="password" name="password"<?=l::accesskey_attr(_("Passwort&[register.php|2]"))?> /></dd>

			<dt><label for="password2"><?=h(_("Passwort wiederholen&[register.php|2]"))?></label></dt>
			<dd><input type="password" id="password2" name="password2"<?=l::accesskey_attr(_("Passwort wiederholen&[register.php|2]"))?> /></dd>

			<dt><label for="email"><?=h(_("E-Mail-Adresse&[register.php|2]"))?></label></dt>
			<dd><input type="text" name="email" id="email"<?=l::accesskey_attr(_("E-Mail-Adresse&[register.php|2]"))?><?=isset($_POST['email']) ? ' value="'.htmlspecialchars($_POST['email']).'"' : ''?> /></dd>

			<dt><label for="hauptplanet"><?=h(_("Gewünschter Name des Hauptplaneten&[register.php|2]"))?></label></dt>
			<dd><input type="text" id="hauptplanet" name="hauptplanet"<?=l::accesskey_attr(_("Gewünschter Name des Hauptplaneten&[register.php|2]"))?><?=isset($_POST['hauptplanet']) ? ' value="'.htmlspecialchars($_POST['hauptplanet']).'"' : ''?> maxlength="24" /></dd>
		</dl>
		<div><input type="checkbox" class="checkbox" name="nutzungsbedingungen" id="nutzungsbedingungen"<?=l::accesskey_attr(_("Ich habe die %sNutzungsbedingungen%s gelesen und akzeptiere sie.&[register.php|2]"))?> /> <label for="nutzungsbedingungen"><?=sprintf(h(_("Ich habe die %sNutzungsbedingungen%s gelesen und akzeptiere sie.&[register.php|2]")), "<a href=\"rules.php\">", "</a>")?></label></div>
		<ul>
			<li><button type="submit"<?=l::accesskey_attr(_("Registrieren&[register.php|2]"))?>><?=h(_("Registrieren&[register.php|2]"))?></button></li>
		</ul>
	</fieldset>
</form>
<?php
	$gui->end();
?>
