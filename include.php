<?php
	setlocale(LC_MESSAGES, array("de_DE.UTF-8", "de_DE.utf8", "de_DE@UTF-8", "de_DE@utf8", "de_DE", "de", "german", "deu"));

	$__FILE__ = str_replace("\\", "/", __FILE__);
	$include_filename = dirname($__FILE__).'/engine/include.php';
	require_once($include_filename);

	class home_gui
	{ # Kuemmert sich ums HTML-Grundgeruest der Hauptseite
		static function html_head($base=false)
		{
			global $SHOW_META_DESCRIPTION; # Sollte nur auf der Startseite der Fall sein
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=htmlspecialchars(LANG)?>">
	<head>
		<title><?=h(_("title_abbr_full"))?></title>
		<link rel="stylesheet" href="<?=h_root?>/style.css" type="text/css" />
<?php
			if(isset($SHOW_META_DESCRIPTION) && $SHOW_META_DESCRIPTION)
			{
?>
		<meta name="description" content="<?=h(sprintf(_("%s ist ein Online-Spiel, für das man nur einen Browser benötigt. Bauen Sie sich im Weltraum ein kleines Imperium auf und kämpfen und handeln Sie mit Hunderten anderer Spielern."), _("title_abbr_full")))?>" />
<?php
			}
			if($base)
			{
?>
		<base href="<?=htmlentities($base)?>" />
<?php
			}
?>
	</head>
	<body><div id="main-container">
		<h1 id="logo"><a href="<?=htmlentities('http://'.$_SERVER['HTTP_HOST'].h_root.'/')?>" title="<?=h(_("Zurück zur Startseite"))?>"<?=accesskey_attr(_("title_full&"))?>><?=h(_("title_full&"))?></a></h1>
		<ul id="links-down" class="cross-navigation">
			<li><a href="#innercontent3"<?=accesskey_attr(_("Zum Inhalt&"))?>><?=h(_("Zum Inhalt&"))?></a></li>
			<li><a href="#navigation"<?=accesskey_attr(_("Zur Navigation&"))?>><?=h(_("Zur Navigation&"))?></a></li>
		</ul>
		<hr class="separator" />
		<div id="content">
		<div id="content2-0"><div id="content2-1"><div id="content2-2"><div id="content2-3">
		<div id="content3-0"><div id="content3-1"><div id="content3-2"><div id="content3-3">
		<div id="content4-1"><div id="content4-2"><div id="content4-3">
			<form action="<?=htmlentities(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php')?>" method="post" id="login-form">
				<fieldset>
					<legend><?=h(_("Anmelden"))?></legend>
					<dl>
						<dt class="c-runde"><label for="login-runde"><?=h(_("Runde&"))?></label></dt>
						<dd class="c-runde"><select name="database" id="login-runde"<?=accesskey_attr(_("Runde&"))?>>
<?php
			$databases = get_databases();
			foreach($databases as $id=>$info)
			{
				if(!$info['enabled'] || $info['dummy']) continue;
?>
							<option value="<?=utf8_htmlentities($id)?>"><?=utf8_htmlentities($info['name'])?></option>
<?php
			}
?>
						</select></dd>

						<dt class="c-name"><label for="login-username"><?=h(_("Name&"))?></label></dt>
						<dd class="c-name"><input type="text" id="login-username" name="username"<?=accesskey_attr(_("Name&"))?> /></dd>

						<dt class="c-passwort"><label for="login-password"><?=h(_("Passwort&"))?></label></dt>
						<dd class="c-passwort"><input type="password" id="login-password" name="password"<?=accesskey_attr(_("Passwort&"))?> /></dd>
					</dl>
					<div class="c-anmelden"><button type="submit"<?=accesskey_attr(_("Anmelden&"))?>><?=h(_("Anmelden&"))?></button></div>
					<ul>
						<li class="c-registrieren"><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/register.php"<?=accesskey_attr(_("Registrieren&"))?>><?=h(_("Registrieren&"))?></a></li>
						<li class="c-passwort-vergessen"><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/passwd.php"<?=accesskey_attr(_("Passwort vergessen?&"))?>><?=h(_("Passwort vergessen?&"))?></a></li>
<?php
			if(global_setting("USE_PROTOCOL") == 'https')
			{
?>
						<li class="c-ssl-abschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=1"<?=accesskey_attr(_("SSL abschalten&"))?>><?=h(_("SSL abschalten&"))?></a></li>
<?php
			}
			else
			{
?>
						<li class="c-ssl-einschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=0"<?=accesskey_attr(_("SSL einschalten&"))?>><?=h(_("SSL einschalten&"))?></a></li>
<?php
			}
?>
						<li class="c-ssl-zertifikat-installieren"><a href="http://www.cacert.org/certs/root.crt"<?=accesskey_attr(_("SSL-Zertifikat installieren&"))?>><?=h(_("SSL-Zertifikat installieren&"))?></a></li>
					</ul>
				</fieldset>
			</form>
			<div id="innercontent1-1"><div id="innercontent1-2"><div id="innercontent1-3">
			<div id="innercontent2-1"><div id="innercontent2-2">
			<div id="innercontent3">
<?php
		}

		static function html_foot()
		{
?>
			</div></div></div>
			</div></div>
			</div>
		</div></div></div></div>
		</div></div></div></div>
		</div></div></div>
		<hr class="separator" />
		<ul id="links-up-1" class="cross-navigation">
			<li><a href="#main-container"<?=accesskey_attr(_("Nach oben&"))?>><?=h(_("Nach oben&"))?></a></li>
			<li><a href="#login-form"<?=accesskey_attr(_("Zur Anmeldung&"))?>><?=h(_("Zur Anmeldung&"))?></a></li>
			<li><a href="#innercontent3"<?=accesskey_attr(_("Zum Inhalt&"))?>><?=h(_("Zum Inhalt&"))?></a></li>
		</ul>
		<hr class="separator" />
		<div id="navigation">
			<h2 id="navigation-heading"><?=h(_("Navigation"))?></h2>
			<div id="navigation2"><ol id="navigation-inner">
				<li class="c-index"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/index.php"<?=accesskey_attr(_("Neuigkeiten&"))?>><?=h(_("Neuigkeiten&"))?></a></li>
				<li class="c-features"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/features.php"<?=accesskey_attr(_("Features&"))?>><?=h(_("Features&"))?></a></li>
				<li class="c-register"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/register.php"<?=accesskey_attr(_("Registrieren&"))?>><?=h(_("Registrieren&"))?></a></li>
				<li class="c-rules"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/rules.php"<?=accesskey_attr(_("Regeln&"))?>><?=h(_("Regeln&"))?></a></li>
				<li class="c-faq"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/faq.php"<?=accesskey_attr(_("FAQ&"))?>><?=h(_("FAQ&"))?></a></li>
				<li class="c-chat"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/chat.php"<?=accesskey_attr(_("Chat&"))?>><?=h(_("Chat&"))?></a></li>
				<li class="c-board"><a href="<?=htmlentities(global_setting("USE_PROTOCOL"))?>://board.s-u-a.net/index.php"<?=accesskey_attr(_("Board&"))?>><?=h(_("Board&"))?></a></li>
				<li class="c-developers"><a href="http://dev.s-u-a.net/"<?=accesskey_attr(_("Entwicklerseite&"))?>><?=h(_("Entwicklerseite&"))?></a></li>
				<li class="c-impressum"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/impressum.php"<?=accesskey_attr(_("Impressum&"))?>><?=h(_("Impressum&"))?></a></li>
<?php
			if(isset($_COOKIE['sua_is_admin']) && $_COOKIE['sua_is_admin'])
			{
?>
				<li class="c-adminbereich"><a href="https://<?=htmlspecialchars($_SERVER['HTTP_HOST'].h_root)?>/admin/index.php"<?=accesskey_attr(_("Adminbereich&"))?>><?=h(_("Adminbereich&"))?></a></li>
<?php
			}
?>
				<li class="c-browsergames24"><a href="http://www.browsergames24.de/modules.php?name=Web_Links&amp;l_op=ratelink&amp;lid=1236">Browsergames24</a></li>
			</ol></div>
		</div>
		<hr class="separator" />
		<ul id="links-up-2" class="cross-navigation">
			<li><a href="#main-container"<?=accesskey_attr(_("Nach oben&"))?>><?=h(_("Nach oben&"))?></a></li>
			<li><a href="#login-form"<?=accesskey_attr(_("Zur Anmeldung&"))?>><?=h(_("Zur Anmeldung&"))?></a></li>
			<li><a href="#innercontent3"<?=accesskey_attr(_("Zum Inhalt&"))?>><?=h(_("Zum Inhalt&"))?></a></li>
		</ul>
	</div>
	<script src="http://www.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
		_uacct = "UA-471643-1";
		urchinTracker();
	</script>
	</body>
</html>
<?php
		}
	}
?>
