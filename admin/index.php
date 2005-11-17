<?php
	require('include.php');

	if($admin_array['permissions'][1] && isset($_POST['ghost_username']) && is_file(DB_PLAYERS.'/'.urlencode(trim($_POST['ghost_username']))) && is_readable(DB_PLAYERS.'/'.urlencode(trim($_POST['ghost_username']))))
	{
		# Als Geist als ein Benutzer anmelden
		$_SESSION['username'] = trim($_POST['ghost_username']);
		$_SESSION['ghost'] = true;
		$_SESSION['resume'] = true;

		$url = 'https://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php?'.SESSION_COOKIE.'='.urlencode(session_id());
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}

	if($admin_array['permissions'][2] && isset($_POST['passwd_username']) && isset($_POST['passwd_password']) && is_file(DB_PLAYERS.'/'.urlencode(trim($_POST['passwd_username']))) && is_readable(DB_PLAYERS.'/'.urlencode(trim($_POST['passwd_username']))))
	{
		# Passwort aendern

		$_POST['passwd_username'] = trim($_POST['passwd_username']);

		$that_user_array = get_user_array($_POST['passwd_username']);
		$that_user_array['password'] = md5($_POST['passwd_password']);
		write_user_array($_POST['passwd_username'], $that_user_array);
	}

	if($admin_array['permissions'][4] && isset($_POST['delete_username']) && is_file(DB_PLAYERS.'/'.urlencode(trim($_POST['delete_username']))) && is_readable(DB_PLAYERS.'/'.urlencode(trim($_POST['delete_username']))))
	{
		# Benutzer loeschen

		$_POST['delete_username'] = trim($_POST['delete_username']);

		$that_user_array = get_user_array($_POST['delete_username']);
		if($that_user_array)
		{
			# Planeten zuruecksetzen
			$planets = array_keys($that_user_array['planets']);
			$that_poses = array();
			foreach($planets as $planet)
			{
				$pos = explode(':', $that_user_array['planets'][$planet]['pos']);
				universe::set_planet_info($pos[0], $pos[1], $pos[2], rand(100, 500), '', '');
				$that_poses[] = $that_user_array['planets'][$planet]['pos'];
			}

			# Buendnispartner entfernen
			foreach($that_user_array['verbuendete'] as $verbuendeter)
			{
				$verb_user_array = get_user_array($verbuendeter);
				$verb_key = array_search($_POST['delete_username'], $verb_user_array['verbuendete']);
				if($verb_key !== false)
				{
					unset($verb_user_array['verbuendete'][$verb_key]);
					write_user_array($verbuendeter, $verb_user_array);
				}
				unset($verb_user_array);
			}
			if(isset($that_user_array['verbuendete_bewerbungen']))
			{
				foreach($that_user_array['verbuendete_bewerbungen'] as $verbuendeter)
				{
					$verb_user_array = get_user_array($verbuendeter);
					$verb_key = array_search($_POST['delete_username'], $verb_user_array['verbuendete_anfragen']);
					if($verb_key !== false)
					{
						unset($verb_user_array['verbuendete_anfragen'][$verb_key]);
						write_user_array($verbuendeter, $verb_user_array);
					}
					unset($verb_user_array);
				}
			}
			if(isset($that_user_array['verbuendete_anfragen']))
			{
				foreach($that_user_array['verbuendete_anfragen'] as $verbuendeter)
				{
					$verb_user_array = get_user_array($verbuendeter);
					$verb_key = array_search($_POST['delete_username'], $verb_user_array['verbuendete_bewerbungen']);
					if($verb_key !== false)
					{
						unset($verb_user_array['verbuendete_bewerbungen'][$verb_key]);
						write_user_array($verbuendeter, $verb_user_array);
					}
					unset($verb_user_array);
				}
			}

			# Flotten entfernen
			foreach($that_user_array['flotten'] as $id=>$flotte)
			{
				$change = false;
				if(!in_array($flotte[3][0], $poses))
					$change = $flotte[3][0];
				elseif(!in_array($flotte[3][1], $poses))
					$change = $flotte[3][1];

				if($change)
				{
					$pos = explode(':', $change);
					$info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);

					$fleet_user_array = get_user_array($info[1]);
					if(isset($fleet_user_array['flotten'][$id]))
					{
						unset($fleet_user_array['flotten'][$id]);
						write_user_array($info[1], $fleet_user_array);
					}
				}
			}

			# Aus den Highscores entfernen
			$pos = ($that_user_array['punkte'][12]-1)*32;

			$fh = fopen(DB_HIGHSCORES, 'r+');
			flock($fh, LOCK_EX);

			$filesize = filesize(DB_HIGHSCORES)-32;
			fseek($fh, $pos, SEEK_SET);

			while(ftell($fh) <= $filesize-32)
			{
				fseek($fh, 32, SEEK_CUR);
				$bracket = fread($fh, 32);
				fseek($fh, -64, SEEK_CUR);
				fwrite($fh, $bracket);

				list($high_username) = highscores::get_info($bracket);
				$high_user_array = get_user_array($high_username);
				if($high_user_array)
				{
					$high_user_array['punkte'][12]--;
					write_user_array($high_username, $high_user_array);
					unset($high_user_array);
				}
			}

			ftruncate($fh, $filesize);

			flock($fh, LOCK_UN);
			fclose($fh);

			# Nachrichten entfernen
			foreach($that_user_array['messages'] as $type=>$messages)
			{
				foreach($messages as $message)
				{
					if(!is_file(DB_MESSAGES.'/'.$message) || !is_readable(DB_MESSAGES.'/'.$message))
						continue;
					$mess = unserialize(gzuncompress(file_get_contents(DB_MESSAGES.'/'.$message)));
					if(isset($mess['users'][$_POST['delete_username']]))
					{
						unset($mess['users'][$_POST['delete_username']]);
						if(count($mess['users']) == 0)
							unlink(DB_MESSAGES.'/'.$message);
						else
						{
							$fh = fopen(DB_MESSAGES.'/'.$message, 'w');
							flock($fh, LOCK_EX);
							fwrite($fh, gzcompress(serialize($mess)));
							flock($fh, LOCK_UN);
							fclose($fh);
						}
					}
					unset($mess);
				}
			}

			if(!unlink(DB_PLAYERS.'/'.urlencode($_POST['delete_username'])))
				chmod(DB_PLAYERS.'/'.urlencode($_POST['delete_username']), 0);
		}
	}

	if($admin_array['permissions'][5] && isset($_POST['lock_username']) && is_file(DB_PLAYERS.'/'.urlencode(trim($_POST['lock_username']))) && is_readable(DB_PLAYERS.'/'.urlencode(trim($_POST['lock_username']))))
	{
		# Benutzer sperren / entsperren

		$_POST['lock_username'] = trim($_POST['lock_username']);

		$that_user_array = get_user_array($_POST['lock_username']);
		$unlock = (isset($that_user_array['locked']) && $that_user_array['locked']);

		if($unlock)
		{
			$that_user_array['umode'] = $that_user_array['umode.sav'];
			unset($that_user_array['umode.sav']);
			$that_user_array['locked'] = false;
		}
		else
		{
			$that_user_array['umode.sav'] = $that_user_array['umode'];
			$that_user_array['umode'] = true;
			$that_user_array['locked'] = true;
		}

		# Planeten umbenennen
		$planets = array_keys($that_user_array['planets']);
		foreach($planets as $planet)
		{
			$pos = explode(':', $that_user_array['planets'][$planet]['pos']);
			$old_info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
			if($unlock)
			{
				if($that_user_array['umode'])
					universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], substr($_POST['lock_username'], 0, 20).' (U)', $old_info[2]);
				else
					universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $_POST['lock_username'], $old_info[2]);
			}
			else
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], substr($_POST['lock_username'], 0, 20).' (g)', $old_info[2]);
		}

		write_user_array($_POST['lock_username'], $that_user_array);
	}

	if($admin_array['permissions'][6] && isset($_POST['rename_old']) && isset($_POST['rename_new']) && is_file(DB_PLAYERS.'/'.urlencode(trim($_POST['rename_old']))) && is_readable(DB_PLAYERS.'/'.urlencode(trim($_POST['rename_old']))) && !file_exists(DB_PLAYERS.'/'.urlencode(substr(trim($_POST['rename_new']), 0, 20))))
	{
		# Benutzer umbenennen

		$_POST['rename_old'] = trim($_POST['rename_old']);
		$_POST['rename_new'] = substr(trim($_POST['rename_new']), 0, 20);

		$that_user_array = get_user_array($_POST['rename_old']);

		$that_user_array['username'] = $_POST['rename_new'];

		# Planeten neu benenennen
		$planets = array_keys($that_user_array['planets']);
		foreach($planets as $planet)
		{
			$pos = explode(':', $that_user_array['planets'][$planet]['pos']);
			$old_info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
			if(isset($that_user_array['locked']) && $that_user_array['locked'])
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $_POST['rename_new'].' (g)', $old_info[2]);
			elseif($that_user_array['umode'])
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $_POST['rename_new'].' (U)', $old_info[2]);
			else
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $_POST['rename_new'], $old_info[2]);
		}

		# Nachrichten durchforsten
		$dh = opendir(DB_MESSAGES);
		while(($message = readdir($dh)) !== false)
		{
			if(!is_file(DB_MESSAGES.'/'.$message) || !is_readable(DB_MESSAGES.'/'.$message) || !is_writeable(DB_MESSAGES.'/'.$message))
				continue;
			$changed = false;
			$msg = unserialize(gzuncompress(file_get_contents(DB_MESSAGES.'/'.$message)));
			if($msg['from'] == $_POST['rename_old'])
			{
				$msg['from'] = $_POST['rename_new'];
				$changed = true;
			}

			if(isset($msg['from'][$_POST['rename_old']]))
			{
				$msg['from'][$_POST['rename_new']] = $msg['from'][$_POST['rename_old']];
				unset($msg['from'][$_POST['rename_old']]);
				$changed = true;
			}

			if($changed)
			{
				$fh = fopen(DB_MESSAGES.'/'.$message, 'w');
				flock($fh, LOCK_EX);
				fwrite($fh, gzcompress(serialize($msg)));
				flock($fh, LOCK_UN);
				fclose($fh);
			}
		}
		closedir($dh);

		# Eintrag in Highscores aendern
		$fh = fopen(DB_HIGHSCORES, 'r+');
		flock($fh, LOCK_EX);

		fseek($fh, ($that_user_array['punkte'][12]-1)*32, SEEK_SET);
		fwrite($fh, highscores::make_info($_POST['rename_new'], $that_user_array['punkte'][1]+$that_user_array['punkte'][2]+$that_user_array['punkte'][3]+$that_user_array['punkte'][4]+$that_user_array['punkte'][5]+$that_user_array['punkte'][6]));

		flock($fh, LOCK_UN);
		fclose($fh);

		# Buendnisparter auswechseln
		foreach($that_user_array['verbuendete'] as $verbuendeter)
		{
			$verb_user_array = get_user_array($verbuendeter);
			$verb_key = array_search($_POST['delete_username'], $verb_user_array['verbuendete']);
			if($verb_key !== false)
			{
				$verb_user_array['verbuendete'][$verb_key] = $_POST['rename_new'];
				write_user_array($verbuendeter, $verb_user_array);
			}
			unset($verb_user_array);
		}
		if(isset($that_user_array['verbuendete_bewerbungen']))
		{
			foreach($that_user_array['verbuendete_bewerbungen'] as $verbuendeter)
			{
				$verb_user_array = get_user_array($verbuendeter);
				$verb_key = array_search($_POST['delete_username'], $verb_user_array['verbuendete_anfragen']);
				if($verb_key !== false)
				{
					$verb_user_array['verbuendete_anfragen'][$verb_key] = $_POST['rename_new'];
					write_user_array($verbuendeter, $verb_user_array);
				}
				unset($verb_user_array);
			}
		}
		if(isset($that_user_array['verbuendete_anfragen']))
		{
			foreach($that_user_array['verbuendete_anfragen'] as $verbuendeter)
			{
				$verb_user_array = get_user_array($verbuendeter);
				$verb_key = array_search($_POST['delete_username'], $verb_user_array['verbuendete_bewerbungen']);
				if($verb_key !== false)
				{
					$verb_user_array['verbuendete_bewerbungen'][$verb_key] = $_POST['rename_new'];
					write_user_array($verbuendeter, $verb_user_array);
				}
				unset($verb_user_array);
			}
		}

		# Datei umbenennen und schreiben
		rename(DB_PLAYERS.'/'.urlencode($_POST['rename_old']), DB_PLAYERS.'/'.urlencode($_POST['rename_new']));
		write_user_array($_POST['rename_new'], $that_user_array);
	}

	if($admin_array['permissions'][9] && isset($_POST['message_text']) && trim($_POST['message_text']) != '')
	{
		$from = $to = $subject = '';
		$html = false;
		if(isset($_POST['message_from']))
			$from = $_POST['message_from'];
		if(isset($_POST['message_to']))
			$to = $_POST['message_to'];
		if(isset($_POST['message_subject']))
			$subject = $_POST['message_subject'];
		if(isset($_POST['message_html']) && $_POST['message_html'])
			$html = true;

		if(trim($to) == '')
		{
			# An alle Benutzer versenden

			$to = array();
			$dh = opendir(DB_PLAYERS);
			while(($uname = readdir($dh)) !== false)
			{
				if(!is_file(DB_PLAYERS.'/'.$uname) || !is_readable(DB_PLAYERS.'/'.$uname))
					continue;
				$to[urldecode($uname)] = 6;
			}
			closedir($dh);
		}
		else
		{
			$to2 = explode("\r\n", $to);
			$to = array();
			foreach($to2 as $to_v)
				$to[$to_v] = 6;
		}

		messages::new_message($to, $from, $subject, $_POST['message_text'], $html);
	}

	if($admin_array['permissions'][12] && isset($_POST['wartungsarbeiten']))
	{
		if($_POST['wartungsarbeiten'] && !is_file('../.htaccess.wartungsarbeiten.sav'))
		{
			if(!file_exists('../.htaccess'))
				touch('../.htaccess');
			if(rename('../.htaccess', '../.htaccess.wartungsarbeiten.sav'))
			{
				$fh = fopen('../.htaccess', 'w');
				flock($fh, LOCK_EX);

				fwrite($fh, "Order Deny,Allow\n");
				fwrite($fh, "Deny from All\n");
				fwrite($fh, "ErrorDocument 403 /wartungsarbeiten.html.gz\n");
				fwrite($fh, "<Files \"wartungsarbeiten.html.gz\">\n");
				fwrite($fh, "\tDeny from None\n");
				fwrite($fh, "</Files>\n");

				flock($fh, LOCK_UN);
				fclose($fh);
			}
		}
		elseif(!$_POST['wartungsarbeiten'] && is_file('../.htaccess.wartungsarbeiten.sav'))
		{
			if(is_file('../.htaccess'))
				unlink('../.htaccess');
			rename('../.htaccess.wartungsarbeiten.sav', '../.htaccess');
		}
	}

	if($admin_array['permissions'][13] && isset($_POST['lock']))
	{
		include_once('../login/scripts/eventhandler.php');

		if($_POST['lock'] && !file_exists(LOCK_FILE))
		{
			# Bei allen Benutzern den Eventhandler ausfuehren

			$dh = opendir(DB_PLAYERS);
			while(($player = readdir($dh)) !== false)
			{
				if(!is_file(DB_PLAYERS.'/'.$player) || !is_readable(DB_PLAYERS.'/'.$player))
					continue;
				eventhandler::run_eventhandler(urldecode($player));
			}
			closedir($dh);

			touch(LOCK_FILE);
		}
		elseif(!$_POST['lock'] && file_exists(LOCK_FILE))
		{
			# Bei allen Benutzern den Eventhandler ausfuehren

			$dh = opendir(DB_PLAYERS);
			while(($player = readdir($dh)) !== false)
			{
				if(!is_file(DB_PLAYERS.'/'.$player) || !is_readable(DB_PLAYERS.'/'.$player))
					continue;
				eventhandler::run_eventhandler(urldecode($player));
			}
			closedir($dh);

			unlink(LOCK_FILE);
		}
	}

	admin_gui::html_head();
?>
<p>Willkommen im Adminbereich. Wählen Sie aus der Liste eine der Funktionen, die Ihnen zur Verfügung stehen.</p>
<p>Denken Sie immer daran: <strong>Benutzen Sie niemals Dinge aus dem Adminbereich zu Ihrem eigenen Vorteil im Spiel und geben Sie keine Informationen an Personen weiter, die sich diese Informationen nicht selbst beschaffen könnten.</strong></p>
<hr />
<ol>
	<li><a href="#passwort-aendern">Adminpasswort ändern</a></li>
<?php if($admin_array['permissions'][0]){?>	<li><a href="#action-0">Benutzerliste einsehen</a></li>
<?php }if($admin_array['permissions'][1]){?>	<li><a href="#action-1">Als Geist als ein Benutzer anmelden</a></li>
<?php }if($admin_array['permissions'][2]){?>	<li><a href="#action-2">Das Passwort eines Benutzers ändern</a></li>
<?php }if($admin_array['permissions'][3]){?>	<li><a href="#action-3">Die Passwörter zweier Benutzer vergleichen</a></li>
<?php }if($admin_array['permissions'][4]){?>	<li><a href="#action-4">Einen Benutzer löschen</a></li>
<?php }if($admin_array['permissions'][5]){?>	<li><a href="#action-5">Einen Benutzer sperren / entsperren</a></li>
<?php }if($admin_array['permissions'][6]){?>	<li><a href="#action-6">Einen Benutzer umbenennen</a></li>
<?php }if($admin_array['permissions'][7]){?>	<li><a href="#action-7"><span xml:lang="en">Todo</span>-Liste bearbeiten</a></li>
<?php }if($admin_array['permissions'][8]){?>	<li><a href="#action-8"><span xml:lang="en">Changelog</span> bearbeiten</a></li>
<?php }if($admin_array['permissions'][9]){?>	<li><a href="#action-9">Nachricht versenden</a></li>
<?php }if($admin_array['permissions'][10]){?>	<li><a href="#action-10"><span xml:lang="en">Log</span>dateien einsehen</a></li>
<?php }if($admin_array['permissions'][11]){?>	<li><a href="#action-11">Benutzerverwaltung</a></li>
<?php }if($admin_array['permissions'][12]){?>	<li><a href="#action-12">Wartungsarbeiten</a></li>
<?php }if($admin_array['permissions'][13]){?>	<li><a href="#action-13">Spiel sperren</a></li>
<?php }if($admin_array['permissions'][14]){?>	<li><a href="#action-14"><span xml:lang="en">News</span> bearbeiten</a></li>
<?php }?></ol>
<hr />
<h2 id="passwort-aendern">Adminpasswort ändern</h2>
<?php
	if(isset($_POST['old_password']) && isset($_POST['new_password']) && isset($_POST['new_password2']))
	{
		if(md5($_POST['old_password']) != $admin_array['password'])
		{
?>
<p class="error"><strong>Sie haben das falsche alte Passwort eingegeben.</strong></p>
<?php
		}
		elseif($_POST['new_password'] != $_POST['new_password2'])
		{
?>
<p class="error"><strong>Die beiden neuen Passwörter stimmen nicht überein.</strong></p>
<?php
		}
		else
		{
			$admin_array['password'] = md5($_POST['new_password']);
			write_admin_list($admins);
?>
<p class="successful"><strong>Das Passwort wurde erfolgreich geändert.</strong></p>
<?php
		}
	}
?>
<form action="index.php" method="post">
	<dl>
		<dt><label for="old-password-input">Altes Passwort</label></dt>
		<dd><input type="password" name="old_password" id="old-password-input" /></dd>

		<dt><label for="new-password-input">Neues Passwort</label></dt>
		<dd><input type="password" name="new_password" id="new-password-input" /></dd>

		<dt><label for="new-password2-input">Neues Passwort wiederholen</label></dt>
		<dd><input type="password" name="new_password2" id="new-password2-input" /></dd>
	</dl>
	<div><button type="submit">Passwort ändern</button></div>
</form>
<?php
	if($admin_array['permissions'][0])
	{
?>
<hr />
<h2 id="action-0">Benutzerliste einsehen</h2>
<form action="userlist.php" method="get">
	<ul>
		<li><button type="submit">Unsortiert</button></li>
		<li><button type="submit" name="sort" value="1">Sortiert</button></li>
	</ul>
</form>
<?php
	}

	if($admin_array['permissions'][1])
	{
?>
<hr />
<h2 id="action-1">Als Geist als ein Benutzer anmelden</h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="ghost-input">Benutzername</label></dt>
		<dd><input type="text" name="ghost_username" id="ghost-input" /></dd>
	</dl>
	<div><button type="submit">Anmelden</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][2])
	{
?>
<hr />
<h2 id="action-2">Das Passwort eines Benutzers ändern</h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="passwd-name-input">Benutzername</label></dt>
		<dd><input type="text" name="passwd_username" id="passwd-name-input" /></dd>

		<dt><label for="passwd-passwd-input">Passwort</label></dt>
		<dd><input type="text" name="passwd_password" id="passwd-passwd-input" /></dd>
	</dl>
	<div><button type="submit">Passwort ändern</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][3])
	{
?>
<hr />
<h2 id="action-3">Die Passwörter zweier Benutzer vergleichen</h2>
<?php
		if(isset($_POST['compare_1']) && isset($_POST['compare_2']) && is_file(DB_PLAYERS.'/'.urlencode($_POST['compare_1'])) && is_readable(DB_PLAYERS.'/'.urlencode($_POST['compare_1'])) && is_file(DB_PLAYERS.'/'.urlencode($_POST['compare_2'])) && is_readable(DB_PLAYERS.'/'.urlencode($_POST['compare_2'])))
		{
			$user_array_1 = get_user_array($_POST['compare_1']);
			$user_array_2 = get_user_array($_POST['compare_2']);
			if($user_array_1['password'] == $user_array_2['password'])
			{
?>
<p><strong>Die Passwörter der Benutzer &bdquo;<?=utf8_htmlentities($_POST['compare_1'])?>&ldquo; und &bdquo;<?=utf8_htmlentities($_POST['compare_2'])?>&ldquo; stimmen überein.</strong></p>
<?php
			}
			else
			{
?>
<p><strong>Die Passwörter der Benutzer &bdquo;<?=utf8_htmlentities($_POST['compare_1'])?>&ldquo; und &bdquo;<?=utf8_htmlentities($_POST['compare_2'])?>&ldquo; unterscheiden sich.</strong></p>
<?php
			}
		}
?>
<form action="index.php#action-3" method="post">
	<ul>
		<li><input type="text" name="compare_1" /></li>
		<li><input type="text" name="compare_2" /></li>
	</ul>
	<div><button type="submit">Vergleichen</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][4])
	{
?>
<hr />
<h2 id="action-4">Einen Benutzer löschen</h2>
<p><strong>Bitte nicht wegen Regelverstoßes durchführen (dann Benutzer sperren), nur bei fehlerhaften Registrierungen oder Ähnlichem.</strong></p>
<form action="index.php" method="post">
	<dl>
		<dt><label for="delete-input">Benutzername</label></dt>
		<dd><input type="text" name="delete_username" id="delete-input" /></dd>
	</dl>
	<div><button type="submit">Löschen</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][5])
	{
?>
<hr />
<h2 id="action-5">Einen Benutzer sperren / entsperren</h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="lock-input">Benutzername</label></dt>
		<dd><input type="text" name="lock_username" id="lock-input" /></dd>
	</dl>
	<div><button type="submit">Sperren / Entsperren</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][6])
	{
?>
<hr />
<h2 id="action-6">Einen Benutzer umbenennen</h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="rename-from">Alter Name</label></dt>
		<dd><input type="text" name="rename_old" id="rename-from" /></dd>

		<dt><label for="rename-to">Neuer Name</label></dt>
		<dd><input type="text" name="rename_new" id="rename-to" /></dd>
	</dl>
	<div><button type="submit">Umbenennen</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][7])
	{
?>
<hr />
<h2 id="action-7"><a href="edit_todo.php"><span xml:lang="en">Todo</span>-Liste bearbeiten</a></h2>
<?php
	}

	if($admin_array['permissions'][8])
	{
?>
<hr />
<h2 id="action-8"><a href="edit_changelog.php"><span xml:lang="en">Changelog</span> bearbeiten</a></h2>
<?php
	}

	if($admin_array['permissions'][9])
	{
?>
<hr />
<h2 id="action-9">Nachricht versenden</h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="message-absender-input">Absender</label></dt>
		<dd><input type="text" name="message_from" id="message-absender-input" /></dd>

		<dt><label for="message-empfaenger-textarea">Empfänger</label></dt>
		<dd><textarea cols="20" rows="4" name="message_to" id="message-empfaenger-textarea"></textarea> Bleibt dieses Feld leer, wird an alle Benutzer verschickt.</dd>

		<dt><label for="message-betreff-input">Betreff</label></dt>
		<dd><input type="text" name="message_subject" id="message-betreff-input" /></dd>

		<dt><label for="message-html-checkbox"><abbr title="Hypertext Markup Language" xml:lang="en"><span xml:lang="de">HTML</span></abbr>?</label></dt>
		<dd><input type="checkbox" name="message_html" id="message-html-checkbox" /></dd>

		<dt><label for="message-text-textarea">Text</label></dt>
		<dd><textarea cols="50" rows="10" name="message_text" id="message-text-textarea"></textarea></dd>
	</dl>
	<div><button type="submit">Absenden</button></div>
</form>
<?php
	}

	if($admin_array['permissions'][10])
	{
?>
<hr />
<h2 id="action-10"><span xml:lang="en">Log</span>dateien einsehen</h2>
<ul>
	<li><a href="logfiles.php?action=select_username">Nach Benutzernamen filtern</a></li>
	<li><a href="logfiles.php?action=select_ip">Nach <abbr title="Internet Protocol" xml:lang="en"><span xml:lang="de">IP</span></abbr>-Adressen filtern</a></li>
	<li><a href="logfiles.php?action=select_session">Nach <span xml:lang="en">Sessions</span> filtern</a></li>
	<li><a href="logfiles.php?action=scan_multis">Nach Multi-<span xml:lang="en">Accounts</span> suchen</a></li>
	<li><a href="logfiles.php?action=whole">Gesamte Logdatei einsehen</a></li>
</ul>
<?php
	}

	if($admin_array['permissions'][11])
	{
?>
<hr />
<h2 id="action-11">Benutzerverwaltung</h2>
<ul>
	<li><a href="usermanagement.php?action=edit">Bestehende Benutzer bearbeiten</a></li>
	<li><a href="usermanagement.php?action=add">Neuen Benutzer anlegen</a></li>
</ul>
<?php
	}

	if($admin_array['permissions'][12])
	{
?>
<hr />
<h2 id="action-12">Wartungsarbeiten</h2>
<?php
		if(is_file('../.htaccess.wartungsarbeiten.sav'))
		{
?>
<form action="index.php" method="post">
	<div><input type="hidden" name="wartungsarbeiten" value="0" /><button type="submit">Wartungsarbeiten deaktivieren</button></div>
</form>
<?php
		}
		else
		{
?>
<form action="index.php" method="post">
	<div><input type="hidden" name="wartungsarbeiten" value="1" /><button type="submit">Wartungsarbeiten aktivieren</button></div>
</form>
<?php
		}
	}

	if($admin_array['permissions'][13])
	{
		if(file_exists(LOCK_FILE))
		{
?>
<hr />
<h2 id="action-13">Spiel entsperren</h2>
<form action="index.php" method="post">
	<div><input type="hidden" name="lock" value="0" /><button type="submit">Entsperren</button></div>
</form>
<?php
		}
		else
		{
?>
<hr />
<h2 id="action-13">Spiel sperren</h2>
<form action="index.php" method="post">
	<div><input type="hidden" name="lock" value="1" /><button type="submit">Sperren</button></div>
</form>
<?php
		}
	}

	if($admin_array['permissions'][14])
	{
?>
<hr />
<h2 id="action-14"><a href="news.php"><span xml:lang="en">News</span> bearbeiten</a></h2>
<?php
	}

	admin_gui::html_foot();
?>