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
	require('include.php');

	__autoload('User');

	if($admin_array['permissions'][1] && isset($_POST['ghost_username']) && User::userExists(trim($_POST['ghost_username'])))
	{
		# Als Geist als ein Benutzer anmelden
		$_SESSION['username'] = trim($_POST['ghost_username']);
		$_SESSION['ghost'] = true;
		$_SESSION['resume'] = true;

		protocol("1", $_SESSION['username']);

		$url = 'https://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php?'.urlencode(session_name())."=".urlencode(session_id());
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a>');
	}

	if($admin_array['permissions'][2] && isset($_POST['passwd_username']) && isset($_POST['passwd_password']) && User::userExists(trim($_POST['passwd_username'])))
	{
		# Passwort aendern

		$_POST['passwd_username'] = trim($_POST['passwd_username']);

		$that_user = Classes::User($_POST['passwd_username']);
		$that_user->setPassword($_POST['passwd_password']) && protocol("2", $_POST['passwd_username']);
		unset($that_user);
	}

	if($admin_array['permissions'][4] && isset($_POST['delete_username']) && User::userExists(trim($_POST['delete_username'])))
	{
		# Benutzer loeschen

		$_POST['delete_username'] = trim($_POST['delete_username']);

		$that_user = Classes::User($_POST['delete_username']);
		$that_user->destroy() && protocol("4", $_POST['delete_username']);
	}

	if($admin_array['permissions'][5] && isset($_POST['lock_username']) && User::userExists(trim($_POST['lock_username'])))
	{
		# Benutzer sperren / entsperren

		$_POST['lock_username'] = trim($_POST['lock_username']);

		$lock_time = false;
		if(isset($_POST['user_lock_period']) && isset($_POST['user_lock_period_unit']))
		{
			$_POST['user_lock_period'] = trim($_POST['user_lock_period']);
			$_POST['user_lock_period_unit'] = trim($_POST['user_lock_period_unit']);
			if($_POST['user_lock_period'])
			{
				switch($_POST['user_lock_period_unit'])
				{
					case 'min': $lock_time = time()+$_POST['user_lock_period']*60; break;
					case 'h': $lock_time = time()+$_POST['user_lock_period']*3600; break;
					case 'd': $lock_time = time()+$_POST['user_lock_period']*86400; break;
				}
			}
		}

		$that_user = Classes::User($_POST['lock_username']);
		$that_user->lockUser($lock_time) && protocol(($that_user->userLocked() ? '5.1' : '5.2'), $_POST['lock_username']);
		unset($that_user);
	}

	if($admin_array['permissions'][6] && isset($_POST['rename_old']) && isset($_POST['rename_new']) && User::userExists(trim($_POST['rename_old'])))
	{
		# Benutzer umbenennen

		$_POST['rename_old'] = trim($_POST['rename_old']);
		$_POST['rename_new'] = substr(trim($_POST['rename_new']), 0, 20);

		$that_user = Classes::User($_POST['rename_old']);
		$that_user->rename($_POST['rename_new']) && protocol("6", $_POST['rename_old'], $_POST['rename_new']);
	}

	if($admin_array['permissions'][7] && isset($_POST['noob']))
	{
		if($_POST['noob'] && !file_exists(global_setting("DB_NONOOBS")))
			touch(global_setting("DB_NONOOBS")) && protocol("7.1");
		elseif(!$_POST['noob'] && file_exists(global_setting("DB_NONOOBS")))
			unlink(global_setting("DB_NONOOBS")) && protocol("7.2");
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

				$dh = opendir(global_setting("DB_PLAYERS"));
				while(($uname = readdir($dh)) !== false)
					$message->addUser(urldecode($uname), 6);
				closedir($dh);
			}
			else
			{
				$to = explode("\r\n", $to);
				foreach($to as $t)
					$message->addUser(urldecode($t), 6);
			}
			protocol("9", $_POST['message_subject'], str_replace("\r\n", ", ", $_POST['message_to']));
			unset($message);
		}
	}

	if($admin_array['permissions'][12] && isset($_POST['wartungsarbeiten']))
	{
		if($_POST['wartungsarbeiten'] && !is_file('../.htaccess.wartungsarbeiten.sav'))
		{
			if(!file_exists('../.htaccess'))
				touch('../.htaccess');
			if(copy('../.htaccess', '../.htaccess.wartungsarbeiten.sav'))
			{
				($fh = fopen('../.htaccess', 'a')) && (protocol("12.1"));
				flock($fh, LOCK_EX);
				fwrite($fh, "\nRedirectMatch 503 ^/(?!(admin/)|(503.html))\n");
				flock($fh, LOCK_UN);
				fclose($fh);
			}
		}
		elseif(!$_POST['wartungsarbeiten'] && is_file('../.htaccess.wartungsarbeiten.sav'))
		{
			if(is_file('../.htaccess'))
				unlink('../.htaccess');
			rename('../.htaccess.wartungsarbeiten.sav', '../.htaccess') && protocol("12.2");
		}
	}

	if($admin_array['permissions'][13] && isset($_POST['lock']))
	{
		if($_POST['lock'] && !database_locked())
		{
			# Bei allen Benutzern den Eventhandler ausfuehren

			$dh = opendir(global_setting("DB_PLAYERS"));
			while(($player = readdir($dh)) !== false)
			{
				if(!is_file(global_setting("DB_PLAYERS").'/'.$player) || !is_readable(global_setting("DB_PLAYERS").'/'.$player))
					continue;
				$this_user = Classes::User(urldecode($player));
				$this_user->eventhandler(0, 1,1,1,1,1);
				unset($this_user);
			}
			closedir($dh);

			($fh = fopen(global_setting("DB_LOCKED"), "w")) and protocol("13.1");
			if($fh)
			{
				if(isset($_POST['lock_period']) && isset($_POST['lock_period_unit']))
				{
					$lock_time = false;
					$_POST['lock_period'] = trim($_POST['lock_period']);
					$_POST['lock_period_unit'] = trim($_POST['lock_period_unit']);
					if($_POST['lock_period'])
					{
						switch($_POST['lock_period_unit'])
						{
							case 'min': $lock_time = time()+$_POST['lock_period']*60; break;
							case 'h': $lock_time = time()+$_POST['lock_period']*3600; break;
							case 'd': $lock_time = time()+$_POST['lock_period']*86400; break;
						}
					}
					if($lock_time)
						fwrite($fh, $lock_time);
				}
				fclose($fh);
			}
		}
		elseif(!$_POST['lock'] && database_locked())
		{
			# Bei allen Benutzern den Eventhandler ausfuehren

			$dh = opendir(global_setting("DB_PLAYERS"));
			while(($player = readdir($dh)) !== false)
			{
				if(!is_file(global_setting("DB_PLAYERS").'/'.$player) || !is_readable(global_setting("DB_PLAYERS").'/'.$player))
					continue;
				$this_user = Classes::User(urldecode($player));
				$this_user->eventhandler(0, 1,1,1,1,1);
				unset($this_user);
			}
			closedir($dh);

			unlink(global_setting("DB_LOCKED")) && protocol("13.2");
		}
	}

	if($admin_array['permissions'][15] && isset($_POST['flock']))
	{
		if($_POST['flock'] && !fleets_locked())
		{
			($fh = fopen(global_setting("DB_NO_ATTS"), "w")) and protocol("15.1");
			if($fh)
			{
				if(isset($_POST['flock_period']) && isset($_POST['flock_period_unit']))
				{
					$lock_time = false;
					$_POST['flock_period'] = trim($_POST['flock_period']);
					$_POST['flock_period_unit'] = trim($_POST['flock_period_unit']);
					if($_POST['flock_period'])
					{
						switch($_POST['flock_period_unit'])
						{
							case 'min': $lock_time = time()+$_POST['flock_period']*60; break;
							case 'h': $lock_time = time()+$_POST['flock_period']*3600; break;
							case 'd': $lock_time = time()+$_POST['flock_period']*86400; break;
						}
					}
					if($lock_time)
						fwrite($fh, $lock_time);
				}
				fclose($fh);
			}
		}
		elseif(!$_POST['flock'] && fleets_locked())
		{
			unlink(global_setting("DB_NO_ATTS")) && protocol("15.2");
		}
	}

	admin_gui::html_head();
?>
<p><?=h(_("Willkommen im Adminbereich. Wählen Sie aus der Liste eine der Funktionen, die Ihnen zur Verfügung stehen."))?></p>
<p><?=sprintf(h(_("Denken Sie immer daran: %sBenutzen Sie niemals Dinge aus dem Adminbereich zu Ihrem eigenen Vorteil im Spiel und geben Sie keine Informationen an Personen weiter, die sich diese Informationen nicht selbst beschaffen könnten.%s")), "<strong>", "</strong>")?></p>
<hr />
<ol>
	<li><a href="#passwort-aendern"<?=accesskey_attr(_("Adminpasswort ändern&[admin/index.php|1]"))?>><?=h(_("Adminpasswort ändern&[admin/index.php|1]"))?></a></li>
<?php if($admin_array['permissions'][0]){?>	<li><a href="#action-0"<?=accesskey_attr(_("Benutzerliste einsehen&[admin/index.php|1]"))?>><?=h(_("Benutzerliste einsehen&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][1]){?>	<li><a href="#action-1"<?=accesskey_attr(_("Als Geist als ein Benutzer anmelden&[admin/index.php|1]"))?>><?=h(_("Als Geist als ein Benutzer anmelden&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][2]){?>	<li><a href="#action-2"<?=accesskey_attr(_("Das Passwort eines Benutzers ändern&[admin/index.php|1]"))?>><?=h(_("Das Passwort eines Benutzers ändern&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][3]){?>	<li><a href="#action-3"<?=accesskey_attr(_("Die Passwörter zweier Benutzer vergleichen&[admin/index.php|1]"))?>><?=h(_("Die Passwörter zweier Benutzer vergleichen&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][4]){?>	<li><a href="#action-4"<?=accesskey_attr(_("Einen Benutzer löschen&[admin/index.php|1]"))?>><?=h(_("Einen Benutzer löschen&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][5]){?>	<li><a href="#action-5"<?=accesskey_attr(_("Einen Benutzer sperren / entsperren&[admin/index.php|1]"))?>><?=h(_("Einen Benutzer sperren / entsperren&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][6]){?>	<li><a href="#action-6"<?=accesskey_attr(_("Einen Benutzer umbenennen&[admin/index.php|1]"))?>><?=h(_("Einen Benutzer umbenennen&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][7]){?>	<li><a href="#action-7"<?=accesskey_attr(_("Anfängerschutz ein-/ausschalten&[admin/index.php|1]"))?>><?=h(_("Anfängerschutz ein-/ausschalten&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][8]){?>	<li><a href="#action-8"><?=h(_("Changelog bearbeiten&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][9]){?>	<li><a href="#action-9"<?=accesskey_attr(_("Nachricht versenden&[admin/index.php|1]"))?>><?=h(_("Nachricht versenden&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][10]){?>	<li><a href="#action-10"><?=h(_("Admin-Logdateien einsehen&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][11]){?>	<li><a href="#action-11"<?=accesskey_attr(_("Benutzerverwaltung&[admin/index.php|1]"))?>><?=h(_("Benutzerverwaltung&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][12]){?>	<li><a href="#action-12"<?=accesskey_attr(_("Wartungsarbeiten&[admin/index.php|1]"))?>><?=h(_("Wartungsarbeiten&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][13]){?>	<li><a href="#action-13"<?=accesskey_attr(_("Spiel sperren&[admin/index.php|1]"))?>><?=h(_("Spiel sperren&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][15]){?>	<li><a href="#action-15"<?=accesskey_attr(_("Flottensperre&[admin/index.php|1]"))?>><?=h(_("Flottensperre&[admin/index.php|1]"))?></a></li>
<?php }if($admin_array['permissions'][14]){?>	<li><a href="#action-14"<?=accesskey_attr(_("News bearbeiten&[admin/index.php|1]"))?>><?=h(_("News bearbeiten&[admin/index.php|1]"))?></a></li>
<?php }?></ol>
<hr />
<h2 id="passwort-aendern"><?=h(_("Adminpasswort ändern"))?></h2>
<?php
	if(isset($_POST['old_password']) && isset($_POST['new_password']) && isset($_POST['new_password2']))
	{
		if(md5($_POST['old_password']) != $admin_array['password'])
		{
?>
<p class="error"><strong><?=h(_("Sie haben das falsche alte Passwort eingegeben."))?></strong></p>
<?php
		}
		elseif($_POST['new_password'] != $_POST['new_password2'])
		{
?>
<p class="error"><strong><?=h(_("Die beiden neuen Passworte stimmen nicht überein."))?></strong></p>
<?php
		}
		else
		{
			$admin_array['password'] = md5($_POST['new_password']);
			write_admin_list($admins);
?>
<p class="successful"><strong><?=h(_("Das Passwort wurde erfolgreich geändert."))?></strong></p>
<?php
		}
	}
?>
<form action="index.php" method="post">
	<dl>
		<dt><label for="old-password-input"><?=h(_("Altes Passwort&[admin/index.php|2]"))?></label></dt>
		<dd><input type="password" name="old_password" id="old-password-input"<?=accesskey_attr(_("Altes Passwort&[admin/index.php|2]"))?> /></dd>

		<dt><label for="new-password-input"><?=h(_("Neues Passwort&[admin/index.php|2]"))?></label></dt>
		<dd><input type="password" name="new_password" id="new-password-input"<?=accesskey_attr(_("Neues Passwort&[admin/index.php|2]"))?> /></dd>

		<dt><label for="new-password2-input"><?=h(_("Neues Passwort wiederholen&[admin/index.php|2]"))?></label></dt>
		<dd><input type="password" name="new_password2" id="new-password2-input"<?=accesskey_attr(_("Neues Passwort wiederholen&[admin/index.php|2]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Passwort ändern&[admin/index.php|2]"))?>><?=h(_("Passwort ändern&[admin/index.php|2]"))?></button></div>
</form>
<?php
	if($admin_array['permissions'][0])
	{
?>
<hr />
<h2 id="action-0"><?=h(_("Benutzerliste einsehen"))?></h2>
<form action="userlist.php" method="get">
	<ul>
		<li><button type="submit"<?=accesskey_attr(_("Unsortiert&[admin/index.php|3]"))?>><?=h(_("Unsortiert&[admin/index.php|3]"))?></button></li>
		<li><button type="submit" name="sort" value="1"<?=accesskey_attr(_("Sortiert&[admin/index.php|3]"))?>><?=h(_("Sortiert&[admin/index.php|3]"))?></button></li>
	</ul>
</form>
<?php
	}

	if($admin_array['permissions'][1])
	{
?>
<hr />
<h2 id="action-1"><?=h(_("Als Geist als ein Benutzer anmelden"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="ghost-input"><?=h(_("Benutzername&[admin/index.php|4]"))?></label></dt>
		<dd><input type="text" name="ghost_username" id="ghost-input"<?=accesskey_attr(_("Benutzername&[admin/index.php|4]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Anmelden&[admin/index.php|4]"))?>><?=h(_("Anmelden&[admin/index.php|4]"))?></button></div>
</form>
<script type="text/javascript">
	// Autocompletion
	activate_users_list(document.getElementById('ghost-input'));
</script>
<?php
	}

	if($admin_array['permissions'][2])
	{
?>
<hr />
<h2 id="action-2"><?=h(_("Das Passwort eines Benutzers ändern"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="passwd-name-input"><?=h(_("Benutzername&[admin/index.php|5]"))?></label></dt>
		<dd><input type="text" name="passwd_username" id="passwd-name-input"<?=accesskey_attr(_("Benutzername&[admin/index.php|5]"))?> /></dd>

		<dt><label for="passwd-passwd-input"><?=h(_("Passwort&[admin/index.php|5]"))?></label></dt>
		<dd><input type="text" name="passwd_password" id="passwd-passwd-input"<?=accesskey_attr(_("Passwort&[admin/index.php|5]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Passwort ändern&[admin/index.php|5]"))?>><?=h(_("Passwort ändern&[admin/index.php|5]"))?></button></div>
</form>
<script type="text/javascript">
	// Autocompletion
	activate_users_list(document.getElementById('passwd-name-input'));
</script>
<?php
	}

	if($admin_array['permissions'][3])
	{
?>
<hr />
<h2 id="action-3"><?=h(_("Die Passwörter zweier Benutzer vergleichen&[admin/index.php|6]"))?></h2>
<?php
		if(isset($_POST['compare_1']) && isset($_POST['compare_2']) && User::userExists($_POST['compare_1']) && User::userExists($_POST['compare_2']))
		{
			$user_1 = Classes::User($_POST['compare_1']);
			$user_2 = Classes::User($_POST['compare_2']);
			$pwd_1 = $user_1->getPasswordSum();
			$pwd_2 = $user_2->getPasswordSum();
			if($pwd_1 && $pwd_2 && $pwd_1 == $pwd_2)
			{
				protocol("3", $_POST['compare_1'], $_POST['compare_2'], "1");
?>
<p><strong><?=sprintf(h(_("Die Passwörter der Benutzer „%s“ und „%s“ stimmen überein.")), htmlspecialchars($_POST["compare_1"]), htmlspecialchars($_POST["compare_2"]))?></strong></p>
<?php
			}
			else
			{
				protocol("3", $_POST['compare_1'], $_POST['compare_2'], "0");
?>
<p><strong><?=sprintf(h(_("Die Passwörter der Benutzer „%s“ und „%s“ unterscheiden sich.")), htmlspecialchars($_POST["compare_1"]), htmlspecialchars($_POST["compare_2"]))?></strong></p>
<?php
			}
		}
?>
<form action="index.php#action-3" method="post">
	<ul>
		<li><input type="text" name="compare_1" id="i-compare-1"<?=accesskey_attr(_("Die Passwörter zweier Benutzer vergleichen&[admin/index.php|6]"))?> /></li>
		<li><input type="text" name="compare_2" id="i-compare-2" /></li>
	</ul>
	<div><button type="submit"<?=accesskey_attr(_("Vergleichen&[admin/index.php|6]"))?>><?=h(_("Vergleichen&[admin/index.php|6]"))?></button></div>
</form>
<script type="text/javascript">
	// Autocompletion
	activate_users_list(document.getElementById('i-compare-1'));
	activate_users_list(document.getElementById('i-compare-2'));
</script>
<?php
	}

	if($admin_array['permissions'][4])
	{
?>
<hr />
<h2 id="action-4"><?=h(_("Einen Benutzer löschen"))?></h2>
<p><strong><?=h(_("Bitte nicht wegen Regelverstoßes durchführen (dann Benutzer sperren), nur bei fehlerhaften Registrierungen oder Ähnlichem."))?></strong></p>
<form action="index.php" method="post">
	<dl>
		<dt><label for="delete-input"><?=h(_("Benutzername&[admin/index.php|7]"))?></label></dt>
		<dd><input type="text" name="delete_username" id="delete-input"<?=accesskey_attr(_("Benutzername&[admin/index.php|7]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Löschen&[admin/index.php|7]"))?>><?=h(_("Löschen&[admin/index.php|7]"))?></button></div>
</form>
<script type="text/javascript">
	// Autocompletion
	activate_users_list(document.getElementById('delete-input'));
</script>
<?php
	}

	if($admin_array['permissions'][5])
	{
?>
<hr />
<h2 id="action-5"><?=h(_("Einen Benutzer sperren / entsperren"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="user-lock-input"><?=h(_("Benutzername&[admin/index.php|8]"))?></label></dt>
		<dd><input type="text" name="lock_username" id="user-lock-input"<?=accesskey_attr(_("Benutzername&[admin/index.php|8]"))?> /></dd>

		<dt><label for="user-lock-period-input"><?=h(_("Dauer der Sperre&[admin/index.php|8]"))?></label></dt>
		<dd><input type="text" name="user_lock_period" id="user-lock-period-input"<?=accesskey_attr(_("Dauer der Sperre&[admin/index.php|8]"))?>> <select name="user_lock_period_unit"><option value="min"><?=h(_("Minuten"))?></option><option value="h"><?=h(_("Stunden"))?></option><option value="d"><?=h(_("Tage"))?></option></select></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Sperren / Entsperren&[admin/index.php|8]"))?>><?=h(_("Sperren / Entsperren&[admin/index.php|8]"))?></button></div>
</form>
<script type="text/javascript">
	// Autocompletion
	activate_users_list(document.getElementById('user-lock-input'));
</script>
<?php
	}

	if($admin_array['permissions'][6])
	{
?>
<hr />
<h2 id="action-6"><?=h(_("Einen Benutzer umbenennen"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="rename-from"><?=h(_("Alter Name&[admin/index.php|9]"))?></label></dt>
		<dd><input type="text" name="rename_old" id="rename-from"<?=accesskey_attr(_("Alter Name&[admin/index.php|9]"))?> /></dd>

		<dt><label for="rename-to"><?=h(_("Neuer Name&[admin/index.php|9]"))?></label></dt>
		<dd><input type="text" name="rename_new" id="rename-to"<?=accesskey_attr(_("Neuer Name&[admin/index.php|9]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Umbenennen&[admin/index.php|9]"))?>><?=h(_("Umbenennen&[admin/index.php|9]"))?></button></div>
</form>
<script type="text/javascript">
	// Autocompletion
	activate_users_list(document.getElementById('rename-from'));
</script>
<?php
	}

	if($admin_array['permissions'][7])
	{
?>
<hr />
<h2 id="action-7"><?=h(_("Anfängerschutz ein-/ausschalten"))?></h2>
<?php
		if(file_exists(global_setting("DB_NONOOBS")))
		{
?>
<form action="index.php" method="post">
        <div><input type="hidden" name="noob" value="0" /><button type="submit"<?=accesskey_attr(_("Anfängerschutz einschalten&[admin/index.php|10]"))?>><?=h(_("Anfängerschutz einschalten&[admin/index.php|10]"))?></button></div>
</form>
<?php
		}
		else
		{
?>
<form action="index.php" method="post">
        <div><input type="hidden" name="noob" value="1" /><button type="submit"<?=accesskey_attr(_("Anfängerschutz ausschalten&[admin/index.php|10]"))?>><?=h(_("Anfängerschutz ausschalten&[admin/index.php|10]"))?></button></div>
</form>
<?php
		}
	}

	if($admin_array['permissions'][8])
	{
?>
<hr />
<h2 id="action-8"><a href="edit_changelog.php"<?=accesskey_attr(_("Changelog bearbeiten&[admin/index.php|1]"))?>><?=h(_("Changelog bearbeiten&[admin/index.php|1]"))?></a></h2>
<?php
	}

	if($admin_array['permissions'][9])
	{
?>
<hr />
<h2 id="action-9"><?=h(_("Nachricht versenden"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="message-absender-input"><?=h(_("Absender&[admin/index.php|11]"))?></label></dt>
		<dd><input type="text" name="message_from" id="message-absender-input"<?=accesskey_attr(_("Absender&[admin/index.php|11]"))?> /></dd>

		<dt><label for="message-empfaenger-textarea"><?=h(_("Empfänger&[admin/index.php|11]"))?></label></dt>
		<dd><textarea cols="20" rows="4" name="message_to" id="message-empfaenger-textarea"<?=accesskey_attr(_("Empfänger&[admin/index.php|11]"))?>></textarea> Bleibt dieses Feld leer, wird an alle Benutzer verschickt.</dd>

		<dt><label for="message-betreff-input"><?=h(_("Betreff&[admin/index.php|11]"))?></label></dt>
		<dd><input type="text" name="message_subject" id="message-betreff-input"<?=h(_("Betreff&[admin/index.php|11]"))?> /></dd>

		<dt><label for="message-html-checkbox"><?=h(_("HTML?&[admin/index.php|11]"))?></label></dt>
		<dd><input type="checkbox" name="message_html" id="message-html-checkbox"<?=accesskey_attr(_("HTML?&[admin/index.php|11]"))?> /></dd>

		<dt><label for="message-text-textarea"><?=h(_("Text&[admin/index.php|11]"))?></label></dt>
		<dd><textarea cols="50" rows="10" name="message_text" id="message-text-textarea"<?=accesskey_attr(_("Text&[admin/index.php|11]"))?>></textarea></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Absenden&[admin/index.php|11]"))?>><?=h(_("Absenden&[admin/index.php|11]"))?></button></div>
</form>
<?php
	}

	if($admin_array['permissions'][10])
	{
?>
<hr />
<h2 id="action-10"><a href="logs.php"<?=accesskey_attr(_("Admin-Logdateien einsehen&[admin/index.php|1]"))?>><?=h(_("Admin-Logdateien einsehen&[admin/index.php|1]"))?></a></h2>
<?php
	}

	if($admin_array['permissions'][11])
	{
?>
<hr />
<h2 id="action-11"><?=h(_("Benutzerverwaltung"))?></h2>
<ul>
	<li><a href="usermanagement.php?action=edit"<?=accesskey_attr(_("Bestehende Benutzer bearbeiten&[admin/index.php|12]"))?>><?=h(_("Bestehende Benutzer bearbeiten&[admin/index.php|12]"))?></a></li>
	<li><a href="usermanagement.php?action=add"<?=accesskey_attr(_("Neuen Benutzer anlegen&[admin/index.php|12]"))?>><?=h(_("Neuen Benutzer anlegen&[admin/index.php|12]"))?></a></li>
</ul>
<?php
	}

	if($admin_array['permissions'][12])
	{
?>
<hr />
<h2 id="action-12"><?=h(_("Wartungsarbeiten"))?></h2>
<?php
		if(is_file('../.htaccess.wartungsarbeiten.sav'))
		{
?>
<form action="index.php" method="post">
	<div><input type="hidden" name="wartungsarbeiten" value="0" /><button type="submit"<?=accesskey_attr(_("Wartungsarbeiten deaktivieren&[admin/index.php|13]"))?>><?=h(_("Wartungsarbeiten deaktivieren&[admin/index.php|13]"))?></button></div>
</form>
<?php
		}
		else
		{
?>
<form action="index.php" method="post">
	<div><input type="hidden" name="wartungsarbeiten" value="1" /><button type="submit"<?=accesskey_attr(_("Wartungsarbeiten aktivieren&[admin/index.php|13]"))?>><?=h(_("Wartungsarbeiten aktivieren&[admin/index.php|13]"))?></button></div>
</form>
<?php
		}
	}

	if($admin_array['permissions'][13])
	{
		if(database_locked())
		{
?>
<hr />
<h2 id="action-13"><?=h(_("Spiel entsperren"))?></h2>
<form action="index.php" method="post">
	<div><input type="hidden" name="lock" value="0" /><button type="submit"<?=accesskey_attr(_("Entsperren&[admin/index.php|14]"))?>><?=h(_("Entsperren&[admin/index.php|14]"))?></button></div>
</form>
<?php
		}
		else
		{
?>
<hr />
<h2 id="action-13"><?=h(_("Spiel sperren"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="lock-period-input"><?=h(_("Dauer der Sperre&[admin/index.php|14]"))?></label></dt>
		<dd><input type="text" name="lock_period" id="lock-period-input"<?=accesskey_attr(_("Dauer der Sperre&[admin/index.php|14]"))?> /> <select name="lock_period_unit"><option value="min"><?=h(_("Minuten"))?></option><option value="h"><?=h(_("Stunden"))?></option><option value="d"><?=h(_("Tage"))?></option></select></dd>
	</dl>
	<div><input type="hidden" name="lock" value="1" /><button type="submit"<?=accesskey_attr(_("Sperren&[admin/index.php|14]"))?>><?=h(_("Sperren&[admin/index.php|14]"))?></button></div>
</form>


<?php
                }
        }

	if($admin_array['permissions'][15])
	{
		if(fleets_locked())
		{
?>
<hr />
<h2 id="action-15"><?=h(_("Flottensperre"))?></h2>
<form action="index.php" method="post">
	<div><input type="hidden" name="flock" value="0" /><button type="submit"<?=accesskey_attr(_("Aufheben&[admin/index.php|15]"))?>><?=h(_("Aufheben&[admin/index.php|15]"))?></button></div>
</form>
<?php
		}
		else
		{
?>
<hr />
<h2 id="action-15"><?=h(_("Flottensperre"))?></h2>
<form action="index.php" method="post">
	<dl>
		<dt><label for="flock-period-input"><?=h(_("Dauer der Flottensperre&[admin/index.php|15]"))?></label></dt>
		<dd><input type="text" name="flock_period" id="lock-period-input"<?=accesskey_attr(_("Dauer der Flottensperre&[admin/index.php|15]"))?> /> <select name="flock_period_unit"><option value="min"><?=h(_("Minuten"))?></option><option value="h"><?=h(_("Stunden"))?></option><option value="d"><?=h(_("Tage"))?></option></select></dd>
	</dl>
	<div><input type="hidden" name="flock" value="1" /><button type="submit"<?=accesskey_attr(_("Setzen&[admin/index.php|15]"))?>><?=h(_("Setzen&[admin/index.php|15]"))?></button></div>
</form>


<?php
		}
	}

	if($admin_array['permissions'][14])
	{
?>
<hr />
<h2 id="action-14"><a href="news.php"<?=accesskey_attr(_("News bearbeiten&[admin/index.php|16]"))?>><?=h(_("News bearbeiten&[admin/index.php|16]"))?></a></h2>
<?php
	}

	admin_gui::html_foot();
?>
