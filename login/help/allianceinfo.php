<?php
	require('../scripts/include.php');

	login_gui::html_head();

	if(!isset($_GET['alliance']) || !Alliance::allianceExists($_GET['alliance']))
	{
?>
<p class="error"><?=h(_("Diese Allianz gibt es nicht."))?></p>
<?php
	}
	else
	{
		$alliance = Classes::Alliance($_GET['alliance']);

		if(!$alliance->getStatus())
		{
?>
<p class="error"><?=h(_("Datenbankfehler."))?></p>
<?php
		}
		else
		{
			$overall = $alliance->getTotalScores();
			$members = $alliance->getMembersCount();
			$average = floor($overall/$members);
?>
<h2><?=h(sprintf(_("Allianzinfo „%s“"), $alliance->getName()))?></h2>
<dl class="allianceinfo">
	<dt class="c-allianztag"><?=h(_("Allianztag"))?></dt>
	<dd class="c-allianztag"><?=htmlspecialchars($alliance->getName())?></dd>

	<dt class="c-name"><?=h(_("Name"))?></dt>
	<dd class="c-name"><?=htmlspecialchars($alliance->name())?></dd>

	<dt class="c-mitglieder"><?=h(_("Mitglieder"))?></dt>
	<dd class="c-mitglieder"><?=htmlspecialchars($members)?></dd>

	<dt class="c-punkteschnitt"><?=h(_("Punkteschnitt"))?></dt>
	<dd class="c-punkteschnitt"><?=ths($average)?> <span class="platz"><?=h(sprintf(_("(Platz %s von %s)"), ths($alliance->getRankAverage()), ths(getAlliancesCount())))?>)</span></dd>

	<dt class="c-gesamtpunkte"><?=h(_("Gesamtpunkte"))?></dt>
	<dd class="c-gesamtpunkte"><?=ths($overall)?> <span class="platz"><?=h(sprintf(_("(Platz %s von %s)"), ths($alliance->getRankTotal()), ths(getAlliancesCount())))?></span></dd>
</dl>
<h3 id="allianzbeschreibung"><?=h(_("Allianzbeschreibung"))?></h3>
<div class="allianz-externes">
<?php
			print($alliance->getExternalDescription());
?>
</div>
<?php
			if(!$me->allianceTag())
			{
				if($alliance->allowApplications())
				{
?>
<ul class="allianz-bewerben">
	<li><a href="../allianz.php?action=apply&amp;for=<?=htmlspecialchars(urlencode($alliance->getName()))?>&amp;<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Bei dieser Allianz bewerben&[login/help/allianceinfo.php|1]"))?>><?=h(_("Bei dieser Allianz bewerben&[login/help/allianceinfo.php|1]"))?></a></li>
</ul>
<?php
				}
				else
				{
?>
<p class="allianz-bewerben error"><?=h(_("Diese Allianz akzeptiert keine neuen Bewerbungen."))?></p>
<?php
				}
			}
		}
	}

	login_gui::html_foot();
?>
