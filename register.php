<?php
	require('engine/include.php');

	$databases = get_databases();
	
	gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Registrieren</h2>
<?php
	if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['password2']) && isset($_POST['database']) && isset($databases[$_POST['database']]))
	{
		define_globals($databases[$_POST['database']][0]);
		
		$error = '';

		if(!isset($_POST['nutzungsbedingungen']) || !$_POST['nutzungsbedingungen'])
			$error = 'Sie müssen die Nutzungsbedingungen lesen und akzeptieren, um am Spiel teilnehmen zu können.';
		elseif(strlen(trim($_POST['username'])) > 24)
			$error = 'Der Benutzername darf maximal 24 Bytes groß sein.';
		elseif(strlen(trim($_POST['hauptplanet'])) > 24)
			$error = 'Der Name des Hauptplanets darf maximal 24 Bytes groß sein.';
		elseif(preg_match('/[\xf8-\xff\x00-\x1f\x7f]/', $_POST['username'])) # Steuerzeichen
			$error = 'Der Benutzername enthält ungültige Zeichen.';
		elseif($_POST['password'] != $_POST['password2'])
			$error = 'Die beiden Passworte stimmen nicht überein.';
		else
		{
			$_POST['username'] = str_replace("\x0a", ' ', trim($_POST['username'])); # nbsp
			
			__autoload('User');
			if(User::UserExists($_POST['username']))
				$error = 'Dieser Spieler existiert bereits. Bitte wählen Sie einen anderen Namen.';
			elseif(substr($_POST['username'], -4) == ' (U)')
				$error = 'Der Benutzername darf nicht auf (U) enden.';
			elseif(substr($_POST['username'], -4) == ' (g)')
				$error = 'Der Benutzername darf nicht auf (g) enden.';
			else
			{
				$user_obj = Classes::User($_POST['username']);
				if(!$user_obj->create())
					$error = 'Datenbankfehler beim Anlegen des Benutzeraccounts.';
				
				# Koordinaten des Hauptplaneten bestimmen

				__autoload('Galaxy');
				$galaxies_count = getGalaxiesCount();
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
					$error = 'Es gibt keine freien Planeten mehr.';
					$user_obj->destroy();
				}
				else
				{
					$index = $user_obj->registerPlanet($koords);
					if($index === false)
					{
						$error = 'Der Hauptplanet konnte nicht besiedelt werden.';
						$user_obj->destroy();
					}
					
					$user_obj->setActivePlanet($index);
					
					$user_obj->addRess(array(20000, 10000, 7500, 5000, 2000));
					$user_obj->setPassword($_POST['password']);

					if(isset($_POST['email']))
						$user_obj->setSetting('email', $_POST['email']);

					# Planetenname
					if(trim($_POST['hauptplanet']) == '')
						$user_obj->planetName('Hauptplanet');
					else $user_obj->planetName($_POST['hauptplanet']);
?>
<p class="successful">
	Die Registrierung war erfolgreich. Sie können sich nun anmelden. Die Koordinaten Ihres Hauptplaneten lauten <?=htmlentities($koords)?>.
</p>
<ul>
	<li><a href="./">Zurück zur Startseite</a></li>
</ul>
<?php
					gui::html_foot();
					exit();
				}
			}
		}
		if($error != '')
		{
?>
<p class="error">
	<?=utf8_htmlentities($error)."\n"?>
</p>
<?php
		}
	}

?>
<form action="<?=htmlentities(USE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/register.php')?>" method="post" id="register-form">
	<fieldset>
		<legend>Registrieren</legend>
		<dl>
			<dt><label for="runde">Runde</label></dt>
			<dd><select name="database" id="runde">
<?php
	foreach($databases as $id=>$info)
	{
?>
				<option value="<?=utf8_htmlentities($id)?>"<?=(isset($_POST['database']) && $_POST['database'] == $id) ? ' selected="selected"' : ''?>><?=utf8_htmlentities($info[1])?></option>
<?php
	}
?>
			</select></dd>
			
			<dt><label for="username">Benutzername</label></dt>
			<dd><input type="text" id="username" name="username"<?=isset($_POST['username']) ? ' value="'.utf8_htmlentities($_POST['username']).'"' : ''?> maxlength="24" /></dd>

			<dt><label for="password">Passwort</label></dt>
			<dd><input type="password" id="password" name="password" /></dd>

			<dt><label for="password2">Passwort wiederholen</label></dt>
			<dd><input type="password" id="password2" name="password2" /></dd>

			<dt><label for="email"><span xml:lang="en">E-Mail</span>-Adresse</label></dt>
			<dd><input type="text" name="email" id="email"<?=isset($_POST['email']) ? ' value="'.utf8_htmlentities($_POST['email']).'"' : ''?> /></dd>

			<dt><label for="hauptplanet">Gewünschter Name des Hauptplaneten</label></dt>
			<dd><input type="text" id="hauptplanet" name="hauptplanet"<?=isset($_POST['hauptplanet']) ? ' value="'.utf8_htmlentities($_POST['hauptplanet']).'"' : ''?> maxlength="24" /></dd>
		</dl>
		<div><input type="checkbox" name="nutzungsbedingungen" id="nutzungsbedingungen" /> <label for="nutzungsbedingungen">Ich habe die <a href="rules.php">Nutzungsbedingungen</a> gelesen und akzeptiere sie.</label></div>
		<ul>
			<li><button type="submit">Registrieren</button></li>
		</ul>
	</fieldset>
</form>
<?php
	gui::html_foot();
?>