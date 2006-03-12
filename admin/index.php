<?php
	require('include.php');
	
	__autoload('User');
	
	if($admin_array['permissions'][1] && isset($_POST['ghost_username']) && User::userExists(trim($_POST['ghost_username'])))
	{
		# Als Geist als ein Benutzer anmelden
		$_SESSION['username'] = trim($_POST['ghost_username']);
		$_SESSION['ghost'] = true;
		$_SESSION['resume'] = true;

		$url = 'https://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php?'.urlencode(SESSION_COOKIE).'='.urlencode(session_id());
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}

	if($admin_array['permissions'][2] && isset($_POST['passwd_username']) && isset($_POST['passwd_password']) && User::userExists(trim($_POST['passwd_username'])))
	{
		# Passwort aendern

		$_POST['passwd_username'] = trim($_POST['passwd_username']);

		$that_user = Classes::User($_POST['passwd_username']);
		$that_user->setPassword($_POST['passwd_password']);
		unset($that_user);
	}

	if($admin_array['permissions'][4] && isset($_POST['delete_username']) && User::userExists(trim($_POST['delete_username'])))
	{
		# Benutzer loeschen

		$_POST['delete_username'] = trim($_POST['delete_username']);

		$that_user = Classes::User($_POST['delete_username']);
		$that_user->destroy();
	}

	if($admin_array['permissions'][5] && isset($_POST['lock_username']) && User::userExists(trim($_POST['lock_username'])))
	{
		# Benutzer sperren / entsperren

		$_POST['lock_username'] = trim($_POST['lock_username']);

		$that_user = Classes::User($_POST['lock_username']);
		$that_user->lockUser();
		unset($that_user);
	}

	if($admin_array['permissions'][6] && isset($_POST['rename_old']) && isset($_POST['rename_new']) && User::userExists(trim($_POST['rename_old'])))
	{
		# Benutzer umbenennen

		$_POST['rename_old'] = trim($_POST['rename_old']);
		$_POST['rename_new'] = substr(trim($_POST['rename_new']), 0, 20);

		$that_user = Classes::User($_POST['rename_old']);
		$that_user->rename($_POST['rename_new']);
	}

	if($admin_array['permissions'][9] && isset($_POST['message_text']) && trim($_POST['message_text']) != '')
	{
		$message = new Message();
		if($message->create())
		{
			if(isset($_POST['message_from']))
				$message->from($_POST['message_from']);
			if(isset($_POST['message_subject']))
				$message->subject($_POST['message_subject']);
			$message->text($_POST['message_text']);
			if(isset($_POST['message_html']) && $_POST['message_html'])
				$message->html(true);
			$to = '';
			if(isset($_POST['message_to'])) $to = trim($_POST['message_to']);
			if(!$to)
			{
				# An alle Benutzer versenden
	
				$dh = opendir(DB_PLAYERS);
				while(($uname = readdir($dh)) !== false)
					$message->addUser(urldecode($uname), 6);
				closedir($dh);
			}
			else
			{
				$to = explode("\r\n", $to);
				foreach($to as $t)
					$message->addUser(urldecode($uname), 6);
			}
			unset($message);
		}
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
				fwrite($fh, "ErrorDocument 403 /wartungsarbeiten.html\n");
				fwrite($fh, "<Files \"wartungsarbeiten.html\">\n");
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
		if($_POST['lock'] && !file_exists(LOCK_FILE))
		{
			# Bei allen Benutzern den Eventhandler ausfuehren

			$dh = opendir(DB_PLAYERS);
			while(($player = readdir($dh)) !== false)
			{
				if(!is_file(DB_PLAYERS.'/'.$player) || !is_readable(DB_PLAYERS.'/'.$player))
					continue;
				$this_user = Classes::User(urldecode($player));
				$this_user->eventhandler(0, 1,1,1,1,1);
				unset($this_user);
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
				$this_user = Classes::User(urldecode($player));
				$this_user->eventhandler(0, 1,1,1,1,1);
				unset($this_user);
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
<?php }if($admin_array['permissions'][8]){?>	<li><a href="#action-8"><span xml:lang="en">Changelog</span> bearbeiten</a></li>
<?php }if($admin_array['permissions'][9]){?>	<li><a href="#action-9">Nachricht versenden</a></li>
<?php }/*if($admin_array['permissions'][10]){?>	<li><a href="#action-10"><span xml:lang="en">Log</span>dateien einsehen</a></li>
<?php }*/if($admin_array['permissions'][11]){?>	<li><a href="#action-11">Benutzerverwaltung</a></li>
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
			$user_1 = Classes::User($_POST['compare_1']);
			$user_2 = Classes::User($_POST['compare_2']);
			$pwd_1 = $user_1->getPasswordSum();
			$pwd_2 = $user_2->getPasswordSum();
			if($pwd_1 && $pwd_2 && $pwd_1 == $pwd_2)
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

	/*if($admin_array['permissions'][10])
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
	}*/

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
