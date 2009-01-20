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
	 * Zeigt Informationen zu einer Allianz an.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\frontend;

	require('../include.php');

	$gui->init();

	if(!isset($_GET['alliance']) || !Alliance::exists($_GET['alliance']))
	{
?>
<p class="error"><?=l::h(_("Diese Allianz gibt es nicht."))?></p>
<?php
	}
	else
	{
		$alliance = Classes::Alliance($_GET['alliance']);
?>
<h2><?=l::h(sprintf(_("Allianzinfo „%s“"), $alliance->getName()))?></h2>
<dl class="allianceinfo">
	<dt class="c-allianztag"><?=l::h(_("Allianztag"))?></dt>
	<dd class="c-allianztag"><?=htmlspecialchars($alliance->getName())?></dd>

	<dt class="c-name"><?=l::h(_("Name"))?></dt>
	<dd class="c-name"><?=htmlspecialchars($alliance->name())?></dd>

	<dt class="c-mitglieder"><?=l::h(_("Mitglieder"))?></dt>
	<dd class="c-mitglieder"><?=ths($alliance->getMembersCount())?></dd>

	<dt class="c-punkteschnitt"><?=l::h(_("Punkteschnitt"))?></dt>
	<dd class="c-punkteschnitt"><?=F::ths($alliance->getScores(Alliance::HIGHSCORES_AVERAGE))?> <span class="platz"><?=l::h(sprintf(_("(Platz %s von %s)"), F::ths($alliance->getRank(Alliance::HIGHSCORES_AVERAGE)), F::ths(Alliance::getNumber())))?>)</span></dd>

	<dt class="c-gesamtpunkte"><?=l::h(_("Gesamtpunkte"))?></dt>
	<dd class="c-gesamtpunkte"><?=F::ths($alliance->getScores(Alliance::HIGHSCORES_SUM))?> <span class="platz"><?=l::h(sprintf(_("(Platz %s von %s)"), F::ths($alliance->getRank(Alliance::HIGHSCORES_SUM)), F::ths(Alliance::getNumber())))?></span></dd>
</dl>
<h3 id="allianzbeschreibung" class="strong"><?=l::h(_("Allianzbeschreibung"))?></h3>
<div class="allianz-externes">
<?php
		print($alliance->getExternalDescription());
?>
</div>
<?php
		if(!Alliance::getUserAlliance($me->getName()) && !Alliance::getUserAllianceApplication($me->getName()))
		{
			if($alliance->allowApplications())
			{
?>
<ul class="allianz-bewerben possibilities">
	<li><a href="../allianz.php?action=apply&amp;for=<?=htmlspecialchars(urlencode($alliance->getName()))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Bei dieser Allianz bewerben&[login/info/allianceinfo.php|1]"))?>><?=l::h(_("Bei dieser Allianz bewerben&[login/info/allianceinfo.php|1]"))?></a></li>
</ul>
<?php
			}
			else
			{
?>
<p class="allianz-bewerben error"><?=l::h(_("Diese Allianz akzeptiert keine neuen Bewerbungen."))?></p>
<?php
			}
		}
	}

	$gui->end();