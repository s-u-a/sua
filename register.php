<?php
	require('engine/include.php');

	gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Registrieren</h2>
<?php
	if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['password2']))
	{
		$error = '';

		if(strlen(trim($_POST['username'])) > 24)
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

			if(file_exists(DB_PLAYERS.'/'.urlencode($_POST['username'])))
				$error = 'Dieser Spieler existiert bereits. Bitte wähle einen anderen Namen.';
			elseif(substr($_POST['username'], -4) == ' (U)')
				$error = 'Der Benutzername darf nicht auf (U) enden.';
			else
			{
				touch(DB_PLAYERS.'/'.urlencode($_POST['username']));
				$user_array = array('planets' => array());

				# Koordinaten des Hauptplaneten bestimmen

				$galaxies_count = universe::get_galaxies_count();
				$galaxies = array();
				for($i=1; $i<=$galaxies_count; $i++)
					$galaxies[] = $i;
				shuffle($galaxies);

				$koords = false;
				foreach($galaxies as $galaxy)
				{
					$systems_count = universe::get_systems_count($galaxy);
					$systems = array();
					for($i=1; $i<=$systems_count; $i++)
						$systems[] = $i;
					shuffle($systems);

					foreach($systems as $system)
					{
						$system_info = universe::get_system_info($galaxy, $system);
						$empty_planets = array();
						foreach($system_info as $planet=>$info)
						{
							if(!$info[1])
								$empty_planets[] = $planet;
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
					unlink(DB_PLAYERS.'/'.urlencode($_POST['username']));
				}
				else
				{
					$user_array['planets'][0] = array('pos' => $koords);

					# Startrohstoffe
					$user_array['planets'][0]['ress'] = array(20000, 10000, 7500, 5000, 2000);

					# Passwort setzen
					$user_array['password'] = md5($_POST['password']);

					# Startwerte
					$user_array['planets'][0]['gebaeude'] = array();
					$user_array['planets'][0]['schiffe'] = array();
					$user_array['planets'][0]['verteidigung'] = array();
					$user_array['planets'][0]['roboter'] = array();
					$user_array['planets'][0]['items'] = array();
					$user_array['planets'][0]['size'] = array(0, 375);
					$user_array['planets'][0]['building'] = array();
					$user_array['planets'][0]['last_refresh'] = time();
					$user_array['forschung'] = array();
					$user_array['verbuendete'] = array();
					$user_array['punkte'] = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, floor(filesize(DB_HIGHSCORES)/32)+1);
					$user_array['registration'] = time();
					$user_array['messages'] = array();
					$user_array['description'] = '';
					$user_array['receive'] = array (
						1 => array(true, true),
						2 => array(true, false),
						3 => array(true, false),
						4 => array(true, true),
						5 => array(true, false)
					);
					$user_array['sonden'] = 1;
					$user_array['fastbuild'] = false;
					$user_array['username'] = $_POST['username'];
					$user_array['shortcuts'] = false;
					$user_array['tooltips'] = false;
					$user_array['umode'] = false;
					$user_array['umode_time'] = 0;

					if(isset($_POST['email']))
						$user_array['email'] = $_POST['email'];

					# Planetenname
					$user_array['planets'][0]['name'] = ((trim($_POST['hauptplanet']) == '') ? 'Hauptplanet' : trim($_POST['hauptplanet']));

					$fh = fopen(DB_PLAYERS.'/'.urlencode($_POST['username']), 'w');
					if(!$fh)
						$error = 'Es konnte nicht in die Datenbank geschrieben werden.';
					else
					{
						# Planeten besetzen
						$k = explode(':', $koords);
						$old_info = universe::get_planet_info($k[0], $k[1], $k[2]);
						if(!$old_info || !universe::set_planet_info($k[0], $k[1], $k[2], $old_info[0], $_POST['username'], $_POST['hauptplanet']))
						{
							$error = 'Der Planet konnte nicht besetzt werden.';
							fclose($fh);
							unlink(DB_PLAYERS.'/'.urlencode($_POST['username']));
						}
						else
						{
							fwrite($fh, gzcompress(serialize($user_array)));
							fclose($fh);

							# In die Statistiken eintragen
							$fh = fopen(DB_HIGHSCORES, 'a');
							if($fh)
							{
								flock($fh, LOCK_EX);

								fwrite($fh, $_POST['username']);
								if(strlen($_POST['username']) < 24)
									fwrite($fh, str_repeat(' ', 24-strlen($_POST['username'])));
								fwrite($fh, "\0\0\0\0\0\0\0\0");

								flock($fh, LOCK_UN);
								fclose($fh);
							}
?>
<p class="successful">
	Die Registrierung war erfolgreich. Du kannst dich nun anmelden. Die Koordinaten deines Hauptplaneten lauten <?=htmlentities($koords)?>.
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
			}
		}
		if($error != '')
		{
?>
<p class="error">
	<?=htmlentities($error)."\n"?>
</p>
<?php
		}
	}

?>
<form action="register.php" method="post" id="register-form">
	<fieldset>
		<legend>Registrieren</legend>
		<dl>
			<dt><label for="username">Benutzername</label></dt>
			<dd><input type="text" id="username" name="username"<?=isset($_POST['username']) ? ' value="'.utf8_htmlentities($_POST['username']).'"' : ''?> maxlength="24" /></dd>

			<dt><label for="password">Passwort</label></dt>
			<dd><input type="password" id="password" name="password" /></dd>

			<dt><label for="password2">Passwort wiederholen</label></dt>
			<dd><input type="password" id="password2" name="password2" /></dd>

			<dt><label for="email"><span xml:lang="en">E-Mail</span>-Adresse</label></dt>
			<dd><input type="text" name="email" id="email" /></dd>

			<dt><label for="hauptplanet">Gewünschter Name des Hauptplaneten</label></dt>
			<dd><input type="text" id="hauptplanet" name="hauptplanet"<?=isset($_POST['hauptplanet']) ? ' value="'.utf8_htmlentities($_POST['hauptplanet']).'"' : ''?> maxlength="24" /></dd>
		</dl>
		<ul>
			<li><button type="submit">Registrieren</button></li>
			<li><a href="./">Zurück zur Startseite</a></li>
		</ul>
	</fieldset>
</form>
<?php
	gui::html_foot();
?>
