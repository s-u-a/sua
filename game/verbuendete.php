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
	 * Verbündete verwalten.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('include.php');

	$gui->init();

	if(isset($_POST['rundschreiben']))
	{
		$betreff = "B\xc3\xbcndnisrundschreiben";
		if(isset($_POST['betreff']) && strlen(trim($_POST['betreff'])) > 0)
			$betreff = $_POST['betreff'];
		$message = Classes::Message(Message::create());
		$message->from($me->getName());
		$message->subject($betreff);
		$message->text($_POST["rundschreiben"]);
		foreach($me->getVerbuendetList() as $verbuendeter)
			$message->addUser($verbuendeter, Message::TYPE_VERBUENDETE);
?>
<p class="successful"><?=L::h(_("Das Rundschreiben wurde erfolgreich verschickt."))?></p>
<?php
	}

	if(isset($_POST['empfaenger']) && strlen(trim($_POST['empfaenger'])) > 0)
	{
		$_POST['empfaenger'] = User::datasetName($_POST['empfaenger']);
		if(!User::exists($_POST['empfaenger']))
			$buendnis_error = _('Dieser Spieler existiert nicht.');
		elseif($me->existsVerbuendet($_POST['empfaenger']))
			$buendnis_error = _('Mit diesem Spieler läuft bereits eine Bewerbung oder ein Bündnis.');
		else
		{
			$text = '';
			if(isset($_POST['mitteilung'])) $text = $_POST['mitteilung'];
			Classes::User($_POST["empfaenger"])->applyVerbuendet($me->getName(), $_POST['mitteilung']);
		}
	}

	if(isset($_GET['anfrage']) && isset($_GET['annehmen']))
	{
		$_GET['anfrage'] = User::datasetName($_GET['anfrage']);
		if($_GET['annehmen']) $me->acceptVerbuendetApplication($_GET['anfrage']);
		else $me->rejectVerbuendetApplication($_GET['anfrage']);
	}

	if(isset($_GET['bewerbung']))
	{
		Classes::User($_GET["bewerbung"])->cancelVerbuendetApplication($me->getName());
	}

	if(isset($_GET['kuendigen']))
	{
		$_GET['kuendigen'] = User::datasetName($_GET['kuendigen']);
		$me->quitVerbuendet($_GET['kuendigen']);
	}

	$anfragen = $me->getVerbuendetRequestList();
	if(count($anfragen) > 0)
	{
?>
<h3 class="strong"><?=L::h(_("Anfragen von anderen Spielern"))?></h3>
<dl class="buendnisse-anfragen buendnisse-liste player-list-actions">
<?php
		foreach($anfragen as $anfrage)
		{
?>
	<dt><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($anfrage))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($anfrage)?></a></dt>
	<dd><ul>
		<li><a href="verbuendete.php?anfrage=<?=htmlspecialchars(urlencode($anfrage))?>&amp;annehmen=1&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=L::h(_("Annehmen"))?></a></li>
		<li><a href="verbuendete.php?anfrage=<?=htmlspecialchars(urlencode($anfrage))?>&amp;annehmen=0&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=L::h(_("Ablehnen"))?></a></li>
	</ul></dd>
<?php
		}
?>
</dl>
<?php
	}

	$bewerbungen = $me->getVerbuendetApplicationList();
	if(count($bewerbungen) > 0)
	{
?>
<h3 class="strong"><?=L::h(_("Bewerbungen bei anderen Spielern"))?></h3>
<dl class="buendnisse-bewerbungen buendnisse-liste player-list-actions">
<?php
		foreach($bewerbungen as $bewerbung)
		{
?>
	<dt><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($bewerbung))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($bewerbung)?></a></dt>
	<dd><ul>
		<li><a href="verbuendete.php?bewerbung=<?=htmlspecialchars(urlencode($bewerbung))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=L::h(_("Zurückziehen"))?></a></li>
	</ul></dd>
<?php
		}
?>
</dl>
<?php
	}
?>
<h2><?=L::h(_("Bündnisse"))?></h2>
<?php
	$verbuendete = $me->getVerbuendetList();
	if(count($verbuendete) <= 0)
	{
?>
<p class="buendnisse-keine"><?=L::h(_("Sie sind derzeit mit keinen Spielern verbündet."))?></p>
<?php
	}
	else
	{
?>
<dl class="buendnisse buendnisse-liste player-list-actions">
<?php
		foreach($verbuendete as $name)
		{
?>
	<dt><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($name))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($name)?></a></dt>
	<dd><ul>
		<li><a href="verbuendete.php?kuendigen=<?=htmlspecialchars(urlencode($name))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" onclick="return confirm('<?=JS::jsentities(sprintf(_("Wollen Sie das Bündnis mit dem Spieler %s wirklich kündigen?"), $name))?>');"><?=L::h(_("Kündigen"))?></a></li>
	</ul></dd>
<?php
		}
?>
</dl>
<form action="verbuendete.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="buendnisse-rundschreiben" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'<?=JS::jsentities(_("Doppelklickschutz: Sie haben ein zweites Mal auf „Absenden“ geklickt. Dadurch wird die Nachricht auch ein zweites Mal abgeschickt. Sind Sie sicher, dass Sie diese Aktion durchführen wollen?"))?>\');');">
	<fieldset>
		<legend><?=L::h(_("Bündnisrundschreiben"))?></legend>
		<dl class="form">
			<dt class="c-betreff"><label for="betreff-input"><?=L::h(_("Betreff [&J][login/verbuendete.php|1]"))?></label></dt>
			<dd class="c-betreff"><input type="text" name="betreff" id="betreff-input" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Betreff [&J][login/verbuendete.php|1]"))?> /></dd>

			<dt class="c-text"><label for="text-textarea"><?=L::h(_("Te&xt[login/verbuendete.php|1]"))?></label></dt>
			<dd class="c-text"><textarea name="rundschreiben" id="text-textarea" rows="6" cols="35"<?=L::accesskeyAttr(_("Te&xt[login/verbuendete.php|1]"))?> tabindex="<?=$tabindex++?>"></textarea></dd>
		</dl>
		<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("R&undschreiben verschicken[login/verbuendete.php|1]"))?>><?=L::h(_("R&undschreiben verschicken[login/verbuendete.php|1]"))?></button></div>
	</fieldset>
</form>
<?php
	}
?>
<h3 class="strong"><?=L::h(_("Neues Bündnis eingehen"))?></h3>
<?php
	if(isset($buendnis_error) && strlen(trim($buendnis_error)) > 0)
	{
?>
<p class="error">
	<?=htmlspecialchars($buendnis_error)."\n"?>
</p>
<?php
	}
?>
<form action="verbuendete.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="buendnisse-eingehen">
	<dl class="form">
		<dt class="c-spieler"><label for="spieler-input"><?=L::h(_("Spieler [&Z][login/verbuendete.php|1]"))?></label></dt>
		<dd class="c-spieler"><input type="text" name="empfaenger" id="spieler-input" value="<?=(isset($_POST['empfaenger']) ? htmlspecialchars($_POST['empfaenger']) : '')?>" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Spieler [&Z][login/verbuendete.php|1]"))?> /></dd>

		<dt class="c-mitteilung"><label for="mitteilung-textarea"><?=L::h(_("Mitteilung [&O][login/verbuendete.php|1]"))?></label></dt>
		<dd class="c-mitteilung"><textarea rows="5" cols="30" name="mitteilung" id="mitteilung-textarea" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Mitteilung [&O][login/verbuendete.php|1]"))?>><?=(isset($_POST['mitteilung']) ? preg_replace("/[\t\r\n]/e", '\'&#\'.ord(\'$0\').\';\'', htmlspecialchars($_POST['mitteilung'])) : '')?></textarea></dd>
	</dl>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("A&nfrage absenden[login/verbuendete.php|1]"))?>><?=L::h(_("A&nfrage absenden[login/verbuendete.php|1]"))?></button></div>
</form>
<script type="text/javascript">
	activate_users_list(document.getElementById('spieler-input'));
</script>
<?php
	$gui->end();
?>
