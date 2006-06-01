<?php
	require('engine/include.php');

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
				if(!mail(trim($that_user->checkSetting('email')), 'Passwortänderung in S-U-A', "Jemand (vermutlich Sie) hat in S-U-A die „Passwort vergessen“-Funktion mit Ihrem Account benutzt. Diese Nachricht ist deshalb an jene E-Mail-Adresse adressiert, die Sie in Ihren Einstellungen in S-U-A eingetragen haben.\nSollten Sie eine Änderung Ihres Passworts nicht erwünschen, ignorieren – oder besser löschen – Sie diese Nachricht einfach.\n\nUm Ihr Passwort zu ändern, rufen Sie bitte die folgende Adresse in Ihrem Browser auf und folgen Sie den Anweisungen:\nhttps://".$_SERVER['HTTP_HOST'].h_root."/passwd.php?name=".urlencode($_POST['benutzername'])."&id=".urlencode($send_id)."&database=".urlencode($_POST['database'])."\n(Ohne SSL: http://".$_SERVER['HTTP_HOST'].h_root."/passwd.php?name=".urlencode($_POST['benutzername'])."&id=".urlencode($send_id)."&database=".urlencode($_POST['database'])." )", "Content-Type: text/plain;\r\n  charset=\"utf-8\"\r\nFrom: ".global_setting("EMAIL_FROM")."\r\nReply-To: ".global_setting("EMAIL_FROM")))
					$error = 'Fehler beim Versand der E-Mail-Nachricht.';
			}
		}
	}

	gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Passwort vergessen</h2>
<?php
	if(isset($_GET['name']) && isset($_GET['id']) && isset($_GET['database']) && isset($databases[$_GET['database']]) && $databases[$_GET['database']]['enabled'])
	{
		define_globals($_GET['database']);

		if(!User::userExists($_GET['name']))
		{
?>
<p class="error">Sie haben einen falschen Benutzernamen angegeben.</p>
<?php
		}
		else
		{
			$that_user = Classes::User($_GET['name']);
			if(!$that_user->getStatus())
			{
?>
<p class="error">Datenbankfehler.</p>
<?php
			}
			elseif(!$that_user->checkPasswordSendID($_GET['id']))
			{
?>
<p class="error">Falsche <abbr title="Identificator" xml:lang="en">ID</abbr>.</p>
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
<p class="error">Die beiden Passwörter stimmen nicht überein.</p>
<?php
					}
					else
					{
						if(!$that_user->setPassword($_POST['new_password']))
						{
?>
<p class="error">Datenbankfehler.</p>
<?php
						}
						else
						{
?>
<p class="successful">Das Passwort wurde erfolgreich geändert. Sie können sich nun mit Ihrem neuen Passwort anmelden.</p>
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
		<dt><label for="neues-passwort-input">Neues Passwort</label></dt>
		<dd><input type="password" name="new_password" id="neues-passwort-input" /></dd>

		<dt><label for="neues-passwort-wiederholen-input">Neues Passwort wiederholen</label></dt>
		<dd><input type="password" name="new_password2" id="neues-passwort-wiederholen-input" /></dd>
	</dl>
	<div><button type="submit">Passwort ändern</button></div>
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
<p class="successful">Falls Sie die richtige <span xml:lang="en">E-Mail</span>-Adresse eingegeben haben, wurde die <span xml:lang="en">E-Mail</span>-Nachricht erfolgreich versandt. Überprüfen Sie nun bitte Ihr Postfach.</p>
<?php
			}
		}
?>
<p>Hier haben Sie die Möglichkeit, Ihr Passwort zu ändern, falls Sie es vergessen haben.</p>
<p>Ihnen wird eine Bestätigungs-<span xml:lang="en">E-Mail</span>-Nachricht zu der <span xml:lang="en">E-Mail</span>-Adresse geschickt werden, die Sie im Spiel in den Einstellungen angegeben haben.</p>
<p>Sollten Sie im Spiel keine gültige <span xml:lang="en">E-Mail</span>-Adresse angegeben haben, <a href="faq.php#administrators" title="FAQ: Wie kann ich die Administratoren erreichen?">wenden Sie sich bitte an einen der Administratoren</a>.</p>
<hr />
<p>Um Ihr Passwort ändern zu können, füllen Sie bitte in das folgende Formular Ihren Benutzernamen und diejenige <span xml:lang="en">E-Mail</span>-Adresse an, die Sie im Spiel in Ihren Einstellungen gespeichert haben.</p>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/passwd.php')?>" method="post">
	<dl>
		<dt><label for="runde-select">Runde</label></dt>
		<dd><select name="database" id="runde-select">
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

		<dt><label for="benutzername-input">Benutzername</label></dt>
		<dd><input type="text" name="benutzername" id="benutzername-input" /></dd>

		<dt><label for="email-input"><span xml:lang="en">E-Mail</span>-Adresse</label></dt>
		<dd><input type="text" name="email" id="email-input" /></dd>
	</dl>
	<div><button type="submit">Absenden</button></div>
</form>
<?php
	}

	gui::html_foot();
?>
