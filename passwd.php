<?php
	require('engine/include.php');
	
	$databases = get_databases();

	if(isset($_POST['benutzername']) && isset($_POST['email']) && isset($_POST['database']) && isset($databases[$_POST['database']]))
	{
		define_globals($databases[$_POST['database']][0]);
		
		$_POST['benutzername'] = trim($_POST['benutzername']);
		$_POST['email'] = trim($_POST['email']);
		if(!is_file(DB_PLAYERS.'/'.urlencode($_POST['benutzername'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_POST['benutzername'])))
			$error = 'Sie haben einen falschen Benutzernamen eingegeben.';
		elseif(!($that_user_array = get_user_array($_POST['benutzername'])))
			$error = 'Datenbankfehler.';
		elseif(!preg_match('/^[-._=a-z0-9]+@([-_=a-z0-9ßáàâäéèêíìîóòôöúùûü]+\.)*[-_=a-z0-9ßáàâäéèêíìîóòôöúùûü]+$/i', trim($that_user_array['email'])))
			$error = 'In diesem Account wurde keine gültige E-Mail-Adresse gespeichert.';
		else
		{
			if($_POST['email'] == trim($that_user_array['email']))
			{
				$send_id = md5(microtime());

				# ID schreiben
				$that_user_array['email_passwd'] = $send_id;
				if(!write_user_array($_POST['benutzername'], $that_user_array))
					$error = 'Datenbankfehler 2.';
				elseif(!mail($that_user_array['email'], 'Passwortänderung in S-U-A', "Jemand (vermutlich Sie) hat in S-U-A die \xe2\x80\x9ePasswort vergessen\xe2\x80\x9c-Funktion mit Ihrem Account benutzt. Diese Nachricht ist deshalb an jene E-Mail-Adresse adressiert, die Sie in Ihren Einstellungen in S-U-A eingetragen haben.\nSollten Sie eine Ãnderung Ihres Passworts nicht erwÃ¼nschen, ignorieren â oder besser lÃ¶schen â Sie diese Nachricht einfach.\n\nUm Ihr Passwort zu Ã¤ndern, rufen Sie bitte die folgende Adresse in Ihrem Browser auf und folgen Sie den Anweisungen:\nhttps://".$_SERVER['HTTP_HOST'].h_root."/passwd.php?name=".urlencode($_POST['benutzername'])."&id=".urlencode($send_id)."&database=".urlencode($_POST['database'])."\n(Ohne SSL: http://".$_SERVER['HTTP_HOST'].h_root."/passwd.php?name=".urlencode($_POST['benutzername'])."&id=".urlencode($send_id)."&database=".urlencode($_POST['database'])." )", "Content-Type: text/plain;\r\n  charset=\"utf-8\"\r\nFrom: ".EMAIL_FROM."\r\nReply-To: ".EMAIL_FROM))
					$error = 'Fehler beim Versand der E-Mail-Nachricht.';
				else
				{
					$_SESSION['username'] = $_POST['benutzername'];
					unset($_SESSION['username']);
				}
			}
			else
			{
				$_SESSION['username'] = $_POST['benutzername'];
				unset($_SESSION['username']);
			}
		}
	}

	gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Passwort vergessen</h2>
<?php
	if(isset($_GET['name']) && isset($_GET['id']) && isset($_GET['database']) && isset($databases[$_GET['database']]))
	{
		define_globals($databases[$_GET['database']][0]);
		
		if(!is_file(DB_PLAYERS.'/'.urlencode($_GET['name'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_GET['name'])))
		{
?>
<p class="error">Sie haben einen falschen Benutzernamen angegeben.</p>
<?php
		}
		elseif(!($that_user_array = get_user_array($_GET['name'])))
		{
?>
<p class="error">Datenbankfehler.</p>
<?php
		}
		elseif(!isset($that_user_array['email_passwd']) || $_GET['id'] != $that_user_array['email_passwd'])
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
					$that_user_array['password'] = md5($_POST['new_password']);
					unset($that_user_array['email_passwd']);
					if(!write_user_array($_GET['name'], $that_user_array))
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
						$_SESSION['username'] = $_GET['name'];
						unset($_SESSION['username']);

						$continue = false;
					}
				}
			}

			if($continue)
			{
?>
<form action="passwd.php?name=<?=htmlentities(urlencode($_GET['name']).'&id='.urlencode($_GET['id']).'&database='.urlencode($_GET['database']))?>" method="post">
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
	else
	{
		if(isset($_POST['benutzername']) && isset($_POST['email']))
		{
			if(isset($error) && $error != '')
			{
?>
<p class="error"><?=htmlentities($error)?></p>
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
<form action="<?=htmlentities(USE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/passwd.php')?>" method="post">
	<dl>
		<dt><label for="runde-select">Runde</label></dt>
		<dd><select name="database" id="runde-select">
<?php
		foreach($databases as $id=>$info)
		{
?>
			<option value="<?=htmlentities($id)?>"><?=utf8_htmlentities($info[1])?></option>
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