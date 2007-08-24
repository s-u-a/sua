<?php
	require('include.php');

	$databases = get_databases();

	if(isset($_POST['benutzername']) && isset($_POST['email']) && isset($_POST['database']) && isset($databases[$_POST['database']]) && $databases[$_POST['database']]['enabled'])
	{
		define_globals($_POST['database']);

		$_POST['benutzername'] = trim($_POST['benutzername']);
		$_POST['email'] = trim($_POST['email']);
		if(!User::userExists($_POST['benutzername']))
			$error = 'Sie haben einen falschen Benutzernamen eingegeben.';
		else
		{
			$that_user = Classes::User($_POST['benutzername']);
			if(!$that_user->getStatus())
				$error = 'Datenbankfehler.';
			elseif(!preg_match('/^[-._=a-z0-9]+@([-_=a-z0-9ßáàâäéèêíìîóòôöúùûü]+\.)*[-_=a-z0-9ßáàâäéèêíìîóòôöúùûü]+$/i', trim($that_user->checkSetting('email'))))
				$error = 'In diesem Account wurde keine gültige E-Mail-Adresse gespeichert.';
			elseif($_POST['email'] == trim($that_user->checkSetting('email')))
			{
				$send_id = $that_user->getPasswordSendID();

				# ID schreiben
				if(!mail(trim($that_user->checkSetting('email')), '=?utf-8?q?Passwort=C3=A4nderung_in?= S-U-A', "Jemand (vermutlich Sie) hat in S-U-A die „Passwort vergessen“-Funktion mit Ihrem Account benutzt. Diese Nachricht ist deshalb an jene E-Mail-Adresse adressiert, die Sie in Ihren Einstellungen in S-U-A eingetragen haben.\nSollten Sie eine Änderung Ihres Passworts nicht erwünschen, ignorieren – oder besser löschen – Sie diese Nachricht einfach.\n\nUm Ihr Passwort zu ändern, rufen Sie bitte die folgende Adresse in Ihrem Browser auf und folgen Sie den Anweisungen:\nhttps://".$_SERVER['HTTP_HOST'].h_root."/passwd.php?name=".urlencode($_POST['benutzername'])."&id=".urlencode($send_id)."&database=".urlencode($_POST['database'])."\n(Ohne SSL: http://".$_SERVER['HTTP_HOST'].h_root."/passwd.php?name=".urlencode($_POST['benutzername'])."&id=".urlencode($send_id)."&database=".urlencode($_POST['database'])." )", "Content-Type: text/plain;\r\n  charset=\"utf-8\"\r\nFrom: ".global_setting("EMAIL_FROM")."\r\nReply-To: ".global_setting("EMAIL_FROM")))
					$error = 'Fehler beim Versand der E-Mail-Nachricht.';
			}
		}
	}

	home_gui::html_head();
?>
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Passwort vergessen?")))?></h2>
<?php
	if(isset($_GET['name']) && isset($_GET['id']) && isset($_GET['database']) && isset($databases[$_GET['database']]) && $databases[$_GET['database']]['enabled'])
	{
		define_globals($_GET['database']);

		if(!User::userExists($_GET['name']))
		{
?>
<p class="error"><?=h(_("Sie haben einen falschen Benutzernamen angegeben."))?></p>
<?php
		}
		else
		{
			$that_user = Classes::User($_GET['name']);
			if(!$that_user->getStatus())
			{
?>
<p class="error"><?=h(_("Datenbankfehler."))?></p>
<?php
			}
			elseif(!$that_user->checkPasswordSendID($_GET['id']))
			{
?>
<p class="error"><?=h(_("Falsche ID."))?></p>
<?php
			}
			else
			{
				$continue = true;
				if(isset($_POST['new_password']) && isset($_POST['new_password2']))
				{
					if($_POST['new_password'] != $_POST['new_password2'])
					{
?>
<p class="error"><?=h(_("Die beiden Passwörter stimmen nicht überein."))?></p>
<?php
					}
					else
					{
						if(!$that_user->setPassword($_POST['new_password']))
						{
?>
<p class="error"><?=h(_("Datenbankfehler."))?></p>
<?php
						}
						else
						{
?>
<p class="successful"><?=h(_("Das Passwort wurde erfolgreich geändert. Sie können sich nun mit Ihrem neuen Passwort anmelden."))?></p>
<?php
							$continue = false;
						}
					}
				}

				if($continue)
				{
?>
<form action="passwd.php?name=<?=htmlspecialchars(urlencode($_GET['name']).'&id='.urlencode($_GET['id']).'&database='.urlencode($_GET['database']))?>" method="post">
	<dl>
		<dt><label for="neues-passwort-input"><?=h(_("Neues Passwort&[passwd.php|1]"))?></label></dt>
		<dd><input type="password" name="new_password" id="neues-passwort-input"<?=accesskey_attr(_("Neues Passwort&[passwd.php|1]"))?> /></dd>

		<dt><label for="neues-passwort-wiederholen-input"><?=h(_("Neues Passwort wiederholen&[passwd.php|1]"))?></label></dt>
		<dd><input type="password" name="new_password2" id="neues-passwort-wiederholen-input"<?=accesskey_attr(_("Neues Passwort wiederholen&[passwd.php|1]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Passwort ändern&[passwd.php|1]"))?>><?=h(_("Passwort ändern&[passwd.php|1]"))?></button></div>
</form>
<?php
				}
			}
		}
	}
	else
	{
		if(isset($_POST['benutzername']) && isset($_POST['email']))
		{
			if(isset($error) && $error != '')
			{
?>
<p class="error"><?=htmlspecialchars($error)?></p>
<?php
			}
			else
			{
?>
<p class="successful"><?=h(_("Falls Sie die richtige E-Mail-Adresse eingegeben haben, wurde die E-Mail-Nachricht erfolgreich versandt. Überprüfen Sie nun bitte Ihr Postfach."))?></p>
<?php
			}
		}
?>
<p><?=h(_("Hier haben Sie die Möglichkeit, Ihr Passwort zu ändern, falls Sie es vergessen haben."))?></p>
<p><?=h(_("Ihnen wird eine Bestätigungs-E-Mail-Nachricht zu der E-Mail-Adresse geschickt werden, die Sie im Spiel in den Einstellungen angegeben haben."))?></p>
<p><?=sprintf(h(_("Sollten Sie im Spiel keine gültige E-Mail-Adresse angegeben haben, %swenden Sie sich bitte an einen der Administratoren%s.")), "<a href=\"faq.php#administrators\" title=\"".h(sprintf(_("FAQ: %s"), _("Wie kann ich die Administratoren erreichen?")))."\">", "</a>")?></p>
<hr />
<p><?=h(_("Um Ihr Passwort ändern zu können, füllen Sie bitte in das folgende Formular Ihren Benutzernamen und diejenige E-Mail-Adresse an, die Sie im Spiel in Ihren Einstellungen gespeichert haben."))?></p>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/passwd.php')?>" method="post">
	<dl>
		<dt><label for="runde-select"><?=h(_("Runde&[passwd.php|2]"))?></label></dt>
		<dd><select name="database" id="runde-select"<?=accesskey_attr(_("Runde&[passwd.php|2]"))?>>
<?php
		foreach($databases as $id=>$info)
		{
			if(!$info['enabled'] || $info['dummy']) continue;
?>
			<option value="<?=htmlspecialchars($id)?>"><?=htmlspecialchars($info['name'])?></option>
<?php
		}
?>
		</select></dd>

		<dt><label for="benutzername-input"><?=h(_("Benutzername&[passwd.php|2]"))?></label></dt>
		<dd><input type="text" name="benutzername" id="benutzername-input"<?=accesskey_attr(_("Benutzername&[passwd.php|2]"))?> /></dd>

		<dt><label for="email-input"><?=h(_("E-Mail-Adresse&[passwd.php|2]"))?></label></dt>
		<dd><input type="text" name="email" id="email-input"<?=accesskey_attr(_("E-Mail-Adresse&[passwd.php|2]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Absenden&[passwd.php|2]"))?>><?=h(_("Absenden&[passwd.php|2]"))?></button></div>
</form>
<?php
	}

	home_gui::html_foot();
?>
