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
	require('../include.php');

	login_gui::html_head();

	if(!isset($_GET['player']) || !User::userExists($_GET['player']))
	{
?>
<p class="error"><?=h(_("Diesen Spieler gibt es nicht."))?></p>
<?php
	}
	else
	{
		$user = Classes::User($_GET['player']);
		if(!$user->getStatus())
		{
?>
<p class="error"><?=h(_("Datenbankfehler."))?></p>
<?php
		}
		else
		{
			$at = $user->allianceTag();
			$suf = '%s';
			if($user->userLocked()) $suf = h(_("%s (g)"));
			elseif($user->umode()) $suf = h(_("%s (U)"));
?>
<h2><?=sprintf(h(_("Spielerinfo „%s“")), sprintf($suf, ($at ? sprintf(h(_("[%s] %s")), "<a href=\"allianceinfo.php?alliance=".htmlspecialchars(urlencode($at).'&'.global_setting("URL_SUFFIX"))."\" title=\"".h(_("Informationen zu dieser Allianz anzeigen"))."\">".htmlspecialchars($at)."</a>", htmlspecialchars($user->getName())) : htmlspecialchars($user->getName()))))?></h2>
<h3 id="punkte" class="strong"><?=h(_("Punkte"))?></h3>
<dl class="punkte">
	<dt class="c-gebaeude"><?=h(_("[scores_0]"))?></dt>
	<dd class="c-gebaeude"><?=ths($user->getScores(0))?></dd>

	<dt class="c-forschung"><?=h(_("[scores_1]"))?></dt>
	<dd class="c-forschung"><?=ths($user->getScores(1))?></dd>

	<dt class="c-roboter"><?=h(_("[scores_2]"))?></dt>
	<dd class="c-roboter"><?=ths($user->getScores(2))?></dd>

	<dt class="c-flotte"><?=h(_("[scores_3]"))?></dt>
	<dd class="c-flotte"><?=ths($user->getScores(3))?></dd>

	<dt class="c-verteidigung"><?=h(_("[scores_4]"))?></dt>
	<dd class="c-verteidigung"><?=ths($user->getScores(4))?></dd>

	<dt class="c-flugerfahrung"><?=h(_("[scores_5]"))?></dt>
	<dd class="c-flugerfahrung"><?=ths($user->getScores(5))?></dd>

	<dt class="c-kampferfahrung"><?=h(_("[scores_6]"))?></dt>
	<dd class="c-kampferfahrung"><?=ths($user->getScores(6))?></dd>

	<dt class="c-gesamt"><?=h(_("Gesamt"))?></dt>
	<dd class="c-gesamt"><?=ths($user->getScores())?> <span class="platz"><?=h(sprintf("(Platz %s von %s)", ths($user->getRank()), ths(getUsersCount())))?></span></dd>
</dl>
<?php
			$show_koords = $me->maySeeKoords($user->getName());
			if($show_koords)
			{
?>
<h3 id="ausgegebene-rohstoffe" class="strong"><?=h(_("Ausgegebene Rohstoffe"))?></h3>
<dl class="punkte">
	<dt class="c-carbon"><?=h(_("[ress_0]"))?></dt>
	<dd class="c-carbon"><?=ths($user->getSpentRess(0))?></dd>

	<dt class="c-eisenerz"><?=h(_("[ress_1]"))?></dt>
	<dd class="c-eisenerz"><?=ths($user->getSpentRess(1))?></dd>

	<dt class="c-wolfram"><?=h(_("[ress_2]"))?></dt>
	<dd class="c-wolfram"><?=ths($user->getSpentRess(2))?></dd>

	<dt class="c-radium"><?=h(_("[ress_3]"))?></dt>
	<dd class="c-radium"><?=ths($user->getSpentRess(3))?></dd>

	<dt class="c-tritium"><?=h(_("[ress_4]"))?></dt>
	<dd class="c-tritium"><?=ths($user->getSpentRess(4))?></dd>

	<dt class="c-gesamt"><?=h(_("Gesamt"))?></dt>
	<dd class="c-gesamt"><?=ths($user->getSpentRess())?></dd>
</dl>
<?php
			}
?>
<h3 id="benutzerbeschreibung" class="strong"><?=h(_("Benutzerbeschreibung"))?></h3>
<div class="benutzerbeschreibung">
<?php
			print($user->getUserDescription());
?>
</div>
<h3 id="buendnisse" class="strong"><?=h(_("Bündnisse"))?></h3>
<?php
			$verbuendet = $user->getVerbuendetList();
			if(count($verbuendet) <= 0)
			{
?>
<p class="buendnisse-keine"><?=h(_("Dieser Benutzer ist derzeit in keinem Bündnis."))?></p>
<?php
			}
			else
			{
?>
<ul class="buendnis-informationen paragraph">
<?php
				foreach($verbuendet as $verbuendeter)
				{
?>
	<li><a href="playerinfo.php?player=<?=htmlspecialchars(urlencode($verbuendeter))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($verbuendeter)?></a></li>
<?php
				}
?>
</ul>
<?php
			}
?>
<h3 id="daten" class="strong"><?=h(_("Daten"))?></h3>
<dl class="daten">
	<dt class="c-letzte-aktivitaet"><?=h(_("Letzte Aktivität"))?></dt>
<?php
			$last_activity = $user->getLastActivity();
			if($last_activity !== false)
			{
?>
	<dd class="c-letzte-aktivitaet"><?=h(sprintf(_("%s (Serverzeit)"), date(_('H:i:s, Y-m-d'), $last_activity)))?></dd>
<?php
			}
			else
			{
?>
	<dd class="c-letzte-aktivitaet nie"><?=h(_("Nie"))?></dd>
<?php
			}
?>

	<dt class="c-registrierung"><?=h(_("Registrierung"))?></dt>
<?php
			$registration_time = $user->getRegistrationTime();
			if($registration_time !== false)
			{
?>
	<dd class="c-registrierung"><?=h(sprintf(_("%s (Serverzeit)"), date(_('H:i:s, Y-m-d'), $registration_time)))?></dd>
<?php
			}
			else
			{
?>
	<dd class="c-registriergung unbekannt"><?=h(_("Unbekannt"))?></dd>
<?php
			}
?>
</dl>
<?php
			if($show_koords)
			{
?>
<h3 id="planeten" class="strong"><?=h(_("Planeten"))?></h3>
<ul class="playerinfo-planeten">
<?php
				$planets = $user->getPlanetsList();
				$active_planet = $user->getActivePlanet();
				foreach($planets as $planet)
				{
					$user->setActivePlanet($planet);
					$pos = $user->getPos();
?>
	<li><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($user->planetName()), "<a href=\"../karte.php?galaxy=".htmlspecialchars(urlencode($pos[0]))."&amp;system=".htmlspecialchars(urlencode($pos[1]))."&amp;".htmlspecialchars(global_setting("URL_SUFFIX"))."\" title=\"".h(_("Jenes Sonnensystem in der Karte ansehen"))."\">".h(vsprintf(_("%d:%d:%d"), $pos))."</a>")?></li>
<?php
				}
				if($active_planet !== false) $user->setActivePlanet($active_planet);
?>
</ul>
<?php
			}

			if($user->getName() != $me->getName())
			{
?>
<h3 id="nachricht" class="strong"><?=h(_("Nachricht"))?></h3>
<form action="../nachrichten.php?to=&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="playerinfo-nachricht" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'<?=_("Doppelklickschutz: Sie haben ein zweites Mal auf „Absenden“ geklickt. Dadurch wird die Nachricht auch ein zweites Mal abgeschickt. Sind Sie sicher, dass Sie diese Aktion durchführen wollen?")?>\');');">
	<dl class="form">
		<dt class="c-betreff"><label for="betreff-input"><?=h(_("Betreff&[login/info/playerinfo.php|1]"))?></label></dt>
		<dd class="c-betreff"><input type="text" id="betreff-input" name="betreff" maxlength="30" tabindex="<?=$tabindex++?>"<?=accesskey_attr(_("Betreff&[login/info/playerinfo.php|1]"))?> /></dd>

		<dt class="c-inhalt"><label for="inhalt-input"><?=h(_("Inhalt&[login/info/playerinfo.php|1]"))?></label></dt>
		<dd class="c-inhalt"><textarea id="inhalt-input" name="inhalt" cols="50" rows="10" tabindex="<?=$tabindex++?>"<?=accesskey_attr(_("Inhalt&[login/info/playerinfo.php|1]"))?>></textarea></dd>
	</dl>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=accesskey_attr(_("&Nachricht absenden[login/info/playerinfo.php|1]"))?>><?=h(_("&Nachricht absenden[login/info/playerinfo.php|1]"))?></button><input type="hidden" name="empfaenger" value="<?=htmlspecialchars($user->getName())?>" /></div>
</form>
<?php
			}
		}
	}
	login_gui::html_foot();
?>
