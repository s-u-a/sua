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
	/**
	 * @author Candid Dauth
	 * @package sua
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Erstellt die GUI der Hauptseite.
	 * @todo Auslagern ins homepage-Projekt
	*/

	class HomeGui extends Gui
	{
		/**
		 * Implementiert Gui::htmlHead(). Gibt HTML-Head, Überschrift und Login-Formular aus.
		*/

		protected function htmlHead()
		{
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=L::h(_("[LANG]"))?>">
	<head>
<!--
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
-->
		<title><?=L::h(_("[title_abbr_full]"))?></title>
		<link rel="stylesheet" href="<?=$this->getOption("h_root")?>/style.css" type="text/css" />
<?php
			if($this->getOption("meta"))
			{
?>
		<meta name="description" content="<?=L::h(sprintf(_("%s ist ein Online-Spiel, für das man nur einen Browser benötigt. Bauen Sie sich im Weltraum ein kleines Imperium auf und kämpfen und handeln Sie mit Hunderten anderer Spieler."), _("[title_abbr_full]")))?>" />
<?php
			}
			if($this->getOption("base"))
			{
?>
		<base href="<?=htmlspecialchars($this->getOption("base"))?>" />
<?php
			}
?>
	</head>
	<body><div id="main-container">
		<h1 id="logo"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root").'/')?>" title="<?=L::h(_("Zurück zur Startseite"))?>"<?=L::accesskeyAttr(_("[title_full]&[include.php|1]"))?>><?=L::h(_("[title_full]&[include.php|1]"))?></a></h1>
		<ul id="links-down" class="cross-navigation">
			<li><a href="#innercontent3"<?=L::accesskeyAttr(_("Zum Inhalt&[include.php|1]"))?>><?=L::h(_("Zum Inhalt&[include.php|1]"))?></a></li>
			<li><a href="#navigation"<?=L::accesskeyAttr(_("Zur Navigation&[include.php|1]"))?>><?=L::h(_("Zur Navigation&[include.php|1]"))?></a></li>
		</ul>
		<hr class="separator" />
		<div id="content">
		<div id="content2-0"><div id="content2-1"><div id="content2-2"><div id="content2-3">
		<div id="content3-0"><div id="content3-1"><div id="content3-2"><div id="content3-3">
		<div id="content4-1"><div id="content4-2"><div id="content4-3">
			<form action="<?=htmlspecialchars($this->getOption("protocol").'://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root").'/login/index.php')?>" method="post" id="login-form">
				<fieldset id="login-form-data">
					<legend><?=L::h(_("Anmelden"))?></legend>
					<dl>
						<dt class="c-runde"><label for="login-runde"><?=L::h(_("Runde&[include.php|2]"))?></label></dt>
						<dd class="c-runde"><select name="database" id="login-runde"<?=L::accesskeyAttr(_("Runde&[include.php|2]"))?>>
<?php
			$databases = $this->getOption("databases");
			if($databases)
			{
				foreach($databases as $id=>$info)
				{
?>
							<option value="<?=htmlspecialchars($id)?>"><?=htmlspecialchars($info['name'])?></option>
<?php
				}
			}
?>
						</select></dd>

						<dt class="c-name"><label for="login-username"><?=L::h(_("Name&[include.php|2]"))?></label></dt>
						<dd class="c-name"><input type="text" id="login-username" name="username"<?=L::accesskeyAttr(_("Name&[include.php|2]"))?> /></dd>

						<dt class="c-passwort"><label for="login-password"><?=L::h(_("Passwort&[include.php|2]"))?></label></dt>
						<dd class="c-passwort"><input type="password" id="login-password" name="password"<?=L::accesskeyAttr(_("Passwort&[include.php|2]"))?> /></dd>
					</dl>
					<div class="c-anmelden"><button type="submit"<?=L::accesskeyAttr(_("Anmelden&[include.php|2]"))?>><?=L::h(_("Anmelden&[include.php|2]"))?></button></div>
					<ul>
						<li class="c-registrieren"><a href="http://<?=$_SERVER['HTTP_HOST'].$this->getOption("h_root")?>/register.php"<?=L::accesskeyAttr(_("Registrieren&[include.php|2]"))?>><?=L::h(_("Registrieren&[include.php|2]"))?></a></li>
						<li class="c-passwort-vergessen"><a href="http://<?=$_SERVER['HTTP_HOST'].$this->getOption("h_root")?>/passwd.php"<?=L::accesskeyAttr(_("Passwort vergessen?&[include.php|2]"))?>><?=L::h(_("Passwort vergessen?&[include.php|2]"))?></a></li>
<?php
			if($this->getOption("protocol") == "https")
			{
?>
						<li class="c-ssl-abschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=1"<?=L::accesskeyAttr(_("SSL abschalten&[include.php|3]"))?>><?=L::h(_("SSL abschalten&[include.php|3]"))?></a></li>
<?php
			}
			else
			{
?>
						<li class="c-ssl-einschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=0"<?=L::accesskeyAttr(_("SSL einschalten&[include.php|3]"))?>><?=L::h(_("SSL einschalten&[include.php|3]"))?></a></li>
<?php
			}
?>
						<li class="c-ssl-zertifikat-installieren"><a href="http://www.cacert.org/certs/root.crt"<?=L::accesskeyAttr(_("SSL-Zertifikat installieren&[include.php|3]"))?>><?=L::h(_("SSL-Zertifikat installieren&[include.php|3]"))?></a></li>
					</ul>
				</fieldset>
				<fieldset id="login-form-options">
					<legend><?=L::h(_("Optionen"))?></legend>
					<dl>
						<dt class="c-javascript"><label for="i-javascript"><?=L::h(_("JavaScript deaktivieren"))?></label></dt>
						<dd class="c-javascript"><input class="checkbox" type="checkbox" name="options[javascript]" id="i-javascript" title="<?=L::h(_("Mit dieser Option können Sie alle fortwährenden JavaScript-Änderungen (zum Beispiel die Uhr) deaktivieren. Nützlich an langsamen Terminals."))?>" /></dd>

						<dt class="c-ipschutz"><label for="i-ipcheck"><?=L::h(_("IP-Schutz abschalten"))?></label></dt>
						<dd class="c-ipschutz"><input class="checkbox" type="checkbox" name="options[ipcheck]" id="i-ipcheck" title="<?=L::h(_("Wenn diese Option deaktiviert ist, kann Ihre Session von mehreren IP-Adressen gleichzeitig genutzt werden. (Unsicher!)"))?>" /></dd>
					</dl>
				</fieldset>
			</form>
			<script type="text/javascript">
			// <![CDATA[
				function toggleOptions()
				{
					if(document.getElementById("login-form-options").style.display == "block")
						document.getElementById("login-form-options").style.display = "none";
					else
						document.getElementById("login-form-options").style.display = "block";
				}

				document.getElementById("login-form-options").style.display = "none";
				var l = document.createElement("a");
				l.href = "javascript:toggleOptions()";
				l.appendChild(document.createTextNode('<?=JS::jsentities(_("Optionen"))?>'));
				document.getElementById("login-form-data").getElementsByTagName("ul")[0].appendChild(l);
			// ]]>
			</script>
			<div id="innercontent1-1"><div id="innercontent1-2"><div id="innercontent1-3">
			<div id="innercontent2-1"><div id="innercontent2-2">
			<div id="innercontent3">
<?php
			return true;
		}

		/**
		 * Implementiert Gui::htmlFoot(). Gibt Navigation und Google-Analytics-JavaScript aus.
		*/

		protected function htmlFoot()
		{
?>
			</div></div></div>
			</div></div>
			</div>
		</div></div></div></div>
		</div></div></div></div>
		</div></div></div>
		</div>
		<hr class="separator" />
		<ul id="links-up-1" class="cross-navigation">
			<li><a href="#main-container"<?=L::accesskeyAttr(_("Nach oben&[include.php|4]"))?>><?=L::h(_("Nach oben&[include.php|4]"))?></a></li>
			<li><a href="#login-form"<?=L::accesskeyAttr(_("Zur Anmeldung&[include.php|4]"))?>><?=L::h(_("Zur Anmeldung&[include.php|4]"))?></a></li>
			<li><a href="#innercontent3"<?=L::accesskeyAttr(_("Zum Inhalt&[include.php|4]"))?>><?=L::h(_("Zum Inhalt&[include.php|4]"))?></a></li>
		</ul>
		<hr class="separator" />
		<div id="navigation">
			<h2 id="navigation-heading"><?=L::h(_("Navigation"))?></h2>
			<div id="navigation2"><ol id="navigation-inner">
				<li class="c-index"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/index.php"<?=L::accesskeyAttr(_("Neuigkeiten&[include.php|6]"))?>><?=L::h(_("Neuigkeiten&[include.php|6]"))?></a></li>
				<li class="c-features"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/features.php"<?=L::accesskeyAttr(_("Features&[include.php|6]"))?>><?=L::h(_("Features&[include.php|6]"))?></a></li>
				<li class="c-register"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/register.php"<?=L::accesskeyAttr(_("Registrieren&[include.php|6]"))?>><?=L::h(_("Registrieren&[include.php|6]"))?></a></li>
				<li class="c-rules"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/rules.php"<?=L::accesskeyAttr(_("Regeln&[include.php|6]"))?>><?=L::h(_("Regeln&[include.php|6]"))?></a></li>
				<li class="c-faq"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/faq.php"<?=L::accesskeyAttr(_("FAQ&[include.php|6]"))?>><?=L::h(_("FAQ&[include.php|6]"))?></a></li>
				<li class="c-chat"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/chat.php"<?=L::accesskeyAttr(_("Chat&[include.php|6]"))?>><?=L::h(_("Chat&[include.php|6]"))?></a></li>
				<li class="c-board"><a href="<?=htmlspecialchars($this->getOption("protocol"))?>://board.s-u-a.net/index.php"<?=L::accesskeyAttr(_("Board&[include.php|6]"))?>><?=L::h(_("Board&[include.php|6]"))?></a></li>
				<li class="c-developers"><a href="http://dev.s-u-a.net/"<?=L::accesskeyAttr(_("Entwicklerseite&"))?>><?=L::h(_("Entwicklerseite&[include.php|6]"))?></a></li>
				<li class="c-impressum"><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/impressum.php"<?=L::accesskeyAttr(_("Impressum&[include.php|6]"))?>><?=L::h(_("Impressum&[include.php|6]"))?></a></li>
<?php
			if(isset($_COOKIE['sua_is_admin']) && $_COOKIE['sua_is_admin'])
			{
?>
				<li class="c-adminbereich"><a href="https://<?=htmlspecialchars($_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/admin/index.php"<?=L::accesskeyAttr(_("Adminbereich&[include.php|6]"))?>><?=L::h(_("Adminbereich&[include.php|6]"))?></a></li>
<?php
			}
?>
				<li class="c-browsergames24"><a href="http://www.browsergames24.de/modules.php?name=Web_Links&amp;l_op=ratelink&amp;lid=1236">Browsergames24</a></li>
			</ol></div>
		</div>
		<hr class="separator" />
		<ul id="links-up-2" class="cross-navigation">
			<li><a href="#main-container"<?=L::accesskeyAttr(_("Nach oben&[include.php|1]"))?>><?=L::h(_("Nach oben&[include.php|1]"))?></a></li>
			<li><a href="#login-form"<?=L::accesskeyAttr(_("Zur Anmeldung&[include.php|1]"))?>><?=L::h(_("Zur Anmeldung&[include.php|1]"))?></a></li>
			<li><a href="#innercontent3"><?=L::h(_("Zum Inhalt&[include.php|1]"))?></a></li>
		</ul>
	</div></body>
</html>
<?php
			return true;
		}
	}