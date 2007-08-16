<?php
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
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
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
		<h1 id="logo"><a href="<?=htmlentities('http://'.$_SERVER['HTTP_HOST'].h_root.'/')?>" title="Zurück zur Startseite" xml:lang="en">Stars Under Attack</a></h1>
		<ul id="links-down" class="cross-navigation">
			<li><a href="#innercontent3">Zum Inhalt</a></li>
			<li><a href="#navigation">Zur Navigation</a></li>
		</ul>
		<hr class="separator" />
		<div id="content">
		<div id="content2-0"><div id="content2-1"><div id="content2-2"><div id="content2-3">
		<div id="content3-0"><div id="content3-1"><div id="content3-2"><div id="content3-3">
		<div id="content4-1"><div id="content4-2"><div id="content4-3">
			<form action="<?=htmlentities(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php')?>" method="post" id="login-form">
				<fieldset>
					<legend>Anmelden</legend>
					<dl>
						<dt class="c-runde"><label for="login-runde">Runde</label></dt>
						<dd class="c-runde"><select name="database" id="login-runde">
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

						<dt class="c-name"><label for="login-username">Name</label></dt>
						<dd class="c-name"><input type="text" id="login-username" name="username" /></dd>

						<dt class="c-passwort"><label for="login-password">Passwort</label></dt>
						<dd class="c-passwort"><input type="password" id="login-password" name="password" /></dd>
					</dl>
					<div class="c-anmelden"><button type="submit">Anmelden</button></div>
					<ul>
						<li class="c-registrieren"><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/register.php">Registrieren</a></li>
						<li class="c-passwort-vergessen"><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/passwd.php">Passwort vergessen?</a></li>
<?php
			if(global_setting("USE_PROTOCOL") == 'https')
			{
?>
						<li class="c-ssl-abschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=1"><abbr title="Secure Sockets Layer" xml:lang="en"><span xml:lang="de">SSL</span></abbr> abschalten</a></li>
<?php
			}
			else
			{
?>
						<li class="c-ssl-einschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=0"><abbr title="Secure Sockets Layer" xml:lang="en"><span xml:lang="de">SSL</span></abbr> einschalten</a></li>
<?php
			}
?>
						<li class="c-ssl-zertifikat-installieren"><a href="http://www.cacert.org/certs/root.crt"><abbr title="Secure Sockets Layer" xml:lang="en"><span xml:lang="de">SSL</span></abbr>-Zertifikat installieren</a></li>
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
			<li><a href="#main-container">Nach oben</a></li>
			<li><a href="#login-form">Zur Anmeldung</a></li>
			<li><a href="#innercontent3">Zum Inhalt</a></li>
		</ul>
		<hr class="separator" />
		<div id="navigation">
			<h2 id="navigation-heading">Navigation</h2>
			<div id="navigation2"><ol id="navigation-inner">
				<li class="c-index"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/index.php">Neuigkeiten</a></li>
				<li class="c-features"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/features.php" xml:lang="en">Features</a></li>
				<li class="c-register"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/register.php">Registrieren</a></li>
				<li class="c-rules"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/rules.php">Regeln</a></li>
				<li class="c-faq"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/faq.php"><abbr title="Frequently Asked Questions" xml:lang="en">FAQ</abbr></a></li>
				<li class="c-chat"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/chat.php" xml:lang="en">Chat</a></li>
				<li class="c-board"><a href="<?=htmlentities(global_setting("USE_PROTOCOL"))?>://board.s-u-a.net/index.php" xml:lang="en">Board</a></li>
				<li class="c-developers"><a href="http://dev.s-u-a.net/">Entwicklerseite</a></li>
				<li class="c-impressum"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/impressum.php">Impressum</a></li>
<?php
			if(isset($_COOKIE['sua_is_admin']) && $_COOKIE['sua_is_admin'])
			{
?>
				<li class="c-adminbereich"><a href="https://<?=htmlspecialchars($_SERVER['HTTP_HOST'].h_root)?>/admin/index.php"><span xml:lang="en">Admin</span>bereich</a></li>
<?php
			}
?>
				<li class="c-browsergames24"><a href="http://www.browsergames24.de/modules.php?name=Web_Links&amp;l_op=ratelink&amp;lid=1236" xml:lang="en">Browsergames24</a></li>
			</ol></div>
		</div>
		<hr class="separator" />
		<ul id="links-up-2" class="cross-navigation">
			<li><a href="#main-container">Nach oben</a></li>
			<li><a href="#login-form">Zur Anmeldung</a></li>
			<li><a href="#innercontent3">Zum Inhalt</a></li>
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
