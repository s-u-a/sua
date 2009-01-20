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
	 * Zeigt die Highscores an.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\frontend;

	require("include.php");

	$gui->init();

	$by = (isset($_GET["by"]) ? $_GET["by"], "0");
	$alliances = false;
	if(isset($_GET["which"]))
	{
		if($_GET["which"] == "alliances_avg")
			$alliances = Alliance::HIGHSCORES_AVERAGE;
		elseif($_GET["which"] = "alliances_sum")
			$alliances = Alliance::HIGHSCORES_SUM;
	}

	if($alliances)
		$class = "Alliance";
	else
		$class = "User";


	$which_param = (isset($_GET["which"]) ? "which=".urlencode($_GET["which"])."&" : "");

	switch($by)
	{
		case "1": $field = User::SCORES_GEBAEUDE; break;
		case "2": $field = User::SCORES_FORSCHUNG; break;
		case "3": $field = User::SCORES_ROBOTER; break;
		case "4": $field = User::SCORES_SCHIFFE; break;
		case "5": $field = User::SCORES_VERTEIDIGUNG; break;
		case "6": $field = User::SCORES_FLIGHTEXP; break;
		case "7": $field = User::SCORES_BATTLEEXP; break;
		default: $field = User::SCORES_TOTAL; break;
	}
?>
<ul class="highscores-modi tabs">
	<li class="c-spieler<?=(!$alliances) ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler&[login/highscores.php|1]"))?>><?=l::h(_("Spieler&[login/highscores.php|1]"))?></a></li>
	<li class="c-allianzen-schnitt<?=($alliances == Alliance::HIGHSCORES_AVG) ? " active" : ""?>"><a href="highscores.php?which=alliances_avg&amp;by=<?=htmlspecialchars(urlencode($by))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Allianzen (Durchschnitt)&[login/highscores.php|1]"))?>><?=l::h(_("Allianzen (Durchschnitt)&[login/highscores.php|1]"))?></a></li>
	<li class="c-allianzen-summe<?=($alliances == Alliance::HIGHSCORES_SUM) ? " active" : ""?>"><a href="highscores.php?which=alliances_sum&amp;by=<?=htmlspecialchars(urlencode($by))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Allianzen (Summe)&[login/highscores.php|1]"))?>><?=l::h(_("Allianzen (Summe)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-gesamt<?=($by=="0") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?><?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Gesamt&[login/highscores.php|1]"))?>><?=l::h(_("Gesamt&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-gebaeude<?=($by=="1") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=1&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Gebäude&[login/highscores.php|1]"))?>><?=l::h(_("Gebäude&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-forschung<?=($by=="2") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=2&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Forschung&[login/highscores.php|1]"))?>><?=l::h(_("Forschung&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-roboter<?=($by=="3") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=3&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Roboter&[login/highscores.php|1]"))?>><?=l::h(_("Roboter&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-schiffe<?=($by=="4") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=4&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Flotte&[login/highscores.php|1]"))?>><?=l::h(_("Flotte&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-verteidigung<?=($by=="5") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=5&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Verteidigung&[login/highscores.php|1]"))?>><?=l::h(_("Verteidigung&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-flugerfahrung<?=($by=="6") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=6&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Flugerfahrung&[login/highscores.php|1]"))?>><?=l::h(_("Flugerfahrung&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-kampferfahrung<?=($by=="7") ? " active" :""?>"><a href="highscores.php?<?=htmlspecialchars($which_param)?>by=7&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Kampferfahrung&[login/highscores.php|1]"))?>><?=l::h(_("Kampferfahrung&[login/highscores.php|1]"))?></a></li>
</ul>
<?php
	$count = $class::getNumber();
	$start = 1;
	if(isset($_GET["start"]) && $_GET["start"] <= $count && $_GET["start"] >= 1)
		$start = floor($_GET["start"]);

	if($alliances)
		$list = $class::getHighscores($start, $start+global_setting("HIGHSCORES_PERPAGE"), $field, $by, $by, $alliances);
	else
		$list = $class::getHighscores($start, $start+global_setting("HIGHSCORES_PERPAGE"), $field, $by, $by);

	if($count > global_setting("HIGHSCORES_PERPAGE"))
	{
?>
<ul class="highscores-seiten fast-seek">
<?php
		if($start > 1)
		{
			$start_prev = $start-global_setting("HIGHSCORES_PERPAGE");
			if($start_prev < 1) $start_prev = 1;
?>
	<li class="c-prev"><a href="highscores.php?<?=htmlspecialchars($mode_prefix)?>start=<?=htmlspecialchars(urlencode($start_prev))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" rel="prev"><?=sprintf(h(_("← %s")), sprintf(h(_("%s–%s")), F::ths($start_prev), F::ths($start_prev+global_setting("HIGHSCORES_PERPAGE")-1)))?></a></li>
<?php
		}
		if($start+global_setting("HIGHSCORES_PERPAGE") <= $count)
		{
			$start_next = $start+global_setting("HIGHSCORES_PERPAGE");
			$end_next = $start_next+global_setting("HIGHSCORES_PERPAGE")-1;
			if($end_next > $count) $end_next = $count;
?>
	<li class="c-next"><a href="highscores.php?<?=htmlspecialchars($mode_prefix)?>start=<?=htmlspecialchars(urlencode($start_next))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" rel="next"><?=sprintf(h(_("%s →")), sprintf(h(_("%s–%s")), $start_next, $end_next))?> &rarr;</a></li>
<?php
		}
?>
</ul>
<?php
	}
?>
<table class="highscores <?=$alliances ? "allianzen" : "spieler"?>">
	<thead>
		<tr>
			<th class="c-platz"><?=l::h(_("Platz"))?></th>
			<th class="c-<?=$alliances ? "allianz" : "spieler"?>"><?=l::h($alliances ? _("Allianz") : _("Spieler"))?></th>
<?php
	if(!$alliances)
	{
?>
			<th class="c-allianz"><?=l::h(_("Allianz"))?></th>
<?php
	}
?>
			<th class="c-punktzahl"><?=l::h(_("Punktzahl"))?> (<?=l::h(_("[scores_".$by."]"))?>)</th>
<?php
	if($alliances)
	{
?>
			<th class="c-mitglieder"><?=l::h(_("Mitglieder"))?></th>
<?php
	}
?>
		</tr>
	</thead>
	<tbody>
<?php
	for($i=0; list(,$info)=each($list); $i++)
	{
		if(!$alliances)
		{
			$class = "fremd";
			if($info["username"] == $me->getName())
				$class = "eigen";
			elseif($me->isVerbuendet($info["username"]))
				$class = "verbuendet";
		}
		else
		{
			$class = "fremd";
			if($info["tag"] == Classes::getUserAlliance($me->getName()))
				$class = "verbuendet";
		}
?>
		<tr class="<?=$class?> allianz-<?=$alliance_class?>">
			<th class="c-platz"><?=F::ths($start+$i)?></th>
			<td class="c-<?=$alliances ? "allianz" : "spieler"?>"><a href="info/<?=$alliances ? "alliance" : "player"?>info.php?<?=$alliances ? "alliance" : "player"?>=<?=htmlspecialchars(urlencode($info[$alliances ? "tag" : "user"]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Informationen zu diesem Spieler anzeigen"))?>" class="<?=$alliances ? "alliance" : "player")?>name"><?=htmlspecialchars($info[$alliances ? "tag" : "user"])?></a></td>
<?php
			if(!$alliances)
			{
				if($info["alliance"])
				{
					$alliance_class = "fremd";
					if($info["alliance"] && Classes::getUserAlliance($me->getName()) == $info["alliance"])
						$alliance_class = "eigen";
?>
			<td class="c-allianz"><a href="info/allianceinfo.php?alliance=<?=htmlspecialchars(urlencode($info["alliance"]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Informationen zu dieser Allianz anzeigen"))?>" class="alliancename"><?=htmlspecialchars($info["alliance"])?></a></td>
<?php
				}
				else
				{
?>
			<td class="c-allianz keine"></td>
<?php
				}
			}
?>
			<td class="c-punktzahl number"><?=F::ths($info[$field])?></td>
<?php
			if($alliances)
			{
?>
			<td class="c-mitglieder number"><?=F::ths($info["members"])?></td>
<?php
			}
?>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
	$gui->end();
