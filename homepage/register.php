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
	 * Formular zur Registrierung.
	 * @author Candid Dauth
	 * @package sua-homepage
	*/

	namespace sua\homepage;

	use \sua\Config;
	use \sua\L;
	use \sua\HTTPOutput;
	use \sua\JS;

	require('include.php');

	$GUI->init();
?>
<h2><?=L::h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Registrieren")))?></h2>
<?php
	if(isset($_REQUEST["error"]))
	{
		switch($_REQUEST["error"])
		{
			case "1":
				$error = _('Sie müssen die Nutzungsbedingungen lesen und akzeptieren, um am Spiel teilnehmen zu können.');
				break;
			case "2":
				$error = _('Der Benutzername enthält ungültige Zeichen.');
				break;
			case "3":
				$error = _('Die beiden Passworte stimmen nicht überein.');
				break;
			case "4":
				$error = _('Dieser Spieler existiert bereits. Bitte wählen Sie einen anderen Namen.');
				break;
			case "5":
				$error = _('Der Benutzername darf nicht auf (U) enden.');
				break;
			case "6":
				$error = _('Der Benutzername darf nicht auf (g) enden.');
				break;
			case "7":
				$error = _("Dieser Benutzername ist nicht zulässig.");
				break;
			case "8":
				$error = _('Es gibt keine freien Planeten mehr.');
				break;
			default:
				$error = _("Registrierung fehlgeschlagen.");
		}
?>
<p class="error"><?=htmlspecialchars($error)?></p>
<?php
	}
?>
<form action="<?=htmlspecialchars($GUI->getOption("protocol").'://'.$_SERVER['HTTP_HOST'].HROOT.'/login_redirect.php?action=register')?>" method="post" id="register-form">
	<fieldset>
		<legend><?=L::h(_("Registrieren"))?></legend>
		<dl>
			<dt><label for="runde"><?=L::h(_("Runde&[register.php|2]"))?></label></dt>
			<dd><select name="database" id="runde"<?=L::accesskeyAttr(_("Runde&[register.php|2]"))?> onchange="updateSSL()" onkeyup="onchange()">
<?php
	$databases = $CONFIG->getConfigValue("databases");
	$databases_js = array();
	if($databases)
	{
		$databases_js = array();
		if($databases)
		{
			foreach($databases as $id=>$info)
			{
				if(!isset($info["urls"]) || !isset($info["urls"]["register"]))
					continue;
				$databases_js[$id] = $info["urls"]["register"];
?>
				<option value="<?=htmlspecialchars($id)?>"<?=(isset($_REQUEST['database']) && $_REQUEST['database'] == $id) ? ' selected="selected"' : ''?>><?=htmlspecialchars($info['name'])?></option>
<?php
			}
		}
	}
?>
			</select></dd>

			<dt><label for="i-username"><?=L::h(_("Benutzername&[register.php|2]"))?></label></dt>
			<dd><input type="text" id="i-username" name="username"<?=L::accesskeyAttr(_("Benutzername&[register.php|2]"))?><?=isset($_REQUEST['username']) ? ' value="'.htmlspecialchars($_REQUEST['username']).'"' : ''?> maxlength="24" /></dd>

			<dt><label for="i-password"><?=L::h(_("Passwort&[register.php|2]"))?></label></dt>
			<dd><input type="password" id="i-password" name="password"<?=L::accesskeyAttr(_("Passwort&[register.php|2]"))?> /></dd>

			<dt><label for="password2"><?=L::h(_("Passwort wiederholen&[register.php|2]"))?></label></dt>
			<dd><input type="password" id="password2" name="password2"<?=L::accesskeyAttr(_("Passwort wiederholen&[register.php|2]"))?> /></dd>

			<dt><label for="email"><?=L::h(_("E-Mail-Adresse&[register.php|2]"))?></label></dt>
			<dd><input type="text" name="email" id="email"<?=L::accesskeyAttr(_("E-Mail-Adresse&[register.php|2]"))?><?=isset($_REQUEST['email']) ? ' value="'.htmlspecialchars($_REQUEST['email']).'"' : ''?> /></dd>

			<dt><label for="hauptplanet"><?=L::h(_("Gewünschter Name des Hauptplaneten&[register.php|2]"))?></label></dt>
			<dd><input type="text" id="hauptplanet" name="hauptplanet"<?=L::accesskeyAttr(_("Gewünschter Name des Hauptplaneten&[register.php|2]"))?><?=isset($_REQUEST['hauptplanet']) ? ' value="'.htmlspecialchars($_REQUEST['hauptplanet']).'"' : ''?> maxlength="24" /></dd>
		</dl>
		<div>
			<input type="checkbox" class="checkbox" name="nutzungsbedingungen" id="nutzungsbedingungen"<?=isset($_REQUEST["nutzungsbedingungen"]) && $_REQUEST["nutzungsbedingungen"] ? " checked=\"checked\"" : ""?><?=L::accesskeyAttr(_("Ich habe die %sNutzungsbedingungen%s gelesen und akzeptiere sie.&[register.php|2]"))?> /> <label for="nutzungsbedingungen"><?=sprintf(L::h(_("Ich habe die %sNutzungsbedingungen%s gelesen und akzeptiere sie.&[register.php|2]")), "<a href=\"rules.php\">", "</a>")?></label>
			<input type="hidden" name="referrer" value="<?=htmlspecialchars(HTTPOutput::getURL(false))?>" />
		</div>
		<ul>
			<li><button type="submit"<?=L::accesskeyAttr(_("Registrieren&[register.php|2]"))?>><?=L::h(_("Registrieren&[register.php|2]"))?></button></li>
		</ul>
	</fieldset>
</form>
<script type="text/javascript">
	var databases_register = <?=JS::aimplodeJS($databases_js)?>;
	ssl_callbacks.push(
		function(enable_ssl)
		{
			document.getElementById("register-form").action = (enable_ssl ? "https" : "http")+"://"+databases_register[document.getElementById("runde").value];
		}
	);
	updateSSL();
</script>
<?php
	$GUI->end();
?>
