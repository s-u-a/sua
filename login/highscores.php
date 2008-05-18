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
	 * @package sua-frontend
	 * @subpackage login
	*/
	namespace sua::frontend;

	require('include.php');

	$gui->init();

	if(isset($_GET['alliances']) && $_GET['alliances'])
	{
		$mode = 'alliances';
		$mode_prefix = 'alliances='.urlencode($_GET['alliances']).'&';
		$sort = $_GET["alliances"];
	}
	else
	{
		$mode = 'users';
		if(isset($_GET["users"]) && in_array($_GET["users"], array("0", "1", "2", "3", "4", "5", "6")))
		{
			$mode_prefix = "users=".urlencode($_GET["users"]);
			$sort = $_GET["users"];
		}
		else
		{
			$mode_prefix = '';
			$sort = false;
		}
	}
?>
<ul class="highscores-modi tabs">
	<li class="c-spieler<?=($mode=='users' && $sort === false) ? ' active' :''?>"><a href="highscores.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler&[login/highscores.php|1]"))?>><?=h(_("Spieler&[login/highscores.php|1]"))?></a></li>
	<li class="c-allianzen<?=($mode=='alliances') ? ' active' : ''?>"><a href="highscores.php?alliances=1&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Allianzen&[login/highscores.php|1]"))?>><?=h(_("Allianzen&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-gebaeude<?=($mode=='users' && $sort==="0") ? ' active' :''?>"><a href="highscores.php?users=0&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Gebäude)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Gebäude)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-forschung<?=($mode=='users' && $sort==="1") ? ' active' :''?>"><a href="highscores.php?users=1&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Forschung)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Forschung)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-roboter<?=($mode=='users' && $sort==="2") ? ' active' :''?>"><a href="highscores.php?users=2&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Roboter)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Roboter)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-schiffe<?=($mode=='users' && $sort==="3") ? ' active' :''?>"><a href="highscores.php?users=3&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Flotte)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Flotte)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-verteidigung<?=($mode=='users' && $sort==="4") ? ' active' :''?>"><a href="highscores.php?users=4&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Verteidigung)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Verteidigung)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-flugerfahrung<?=($mode=='users' && $sort==="5") ? ' active' :''?>"><a href="highscores.php?users=5&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Flugerfahrung)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Flugerfahrung)&[login/highscores.php|1]"))?></a></li>
	<li class="c-spieler-kampferfahrung<?=($mode=='users' && $sort==="6") ? ' active' :''?>"><a href="highscores.php?users=6&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Spieler (Kampferfahrung)&[login/highscores.php|1]"))?>><?=h(_("Spieler (Kampferfahrung)&[login/highscores.php|1]"))?></a></li>
</ul>
<?php
	$highscores = Classes::Highscores();
	$count = $highscores->getCount($mode);
	$start = 1;
	if(isset($_GET['start']) && $_GET['start'] <= $count && $_GET['start'] >= 1)
		$start = floor($_GET['start']);

	$sort_field = null;
	if($mode == "alliances" && $sort == '2') $sort_field = 'scores_total';
	elseif($mode == "users" && $sort !== false) $sort_field = "scores_".$sort;
	$score_field = null;
	if($mode == "users" && $sort !== false) $score_field = "scores_".$sort;
	$list = $highscores->getList($mode, $start, $start+global_setting("HIGHSCORES_PERPAGE"), $sort_field, $score_field);

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

	if($mode == 'users')
	{
?>
<table class="highscores spieler">
	<thead>
		<tr>
			<th class="c-platz"><?=h(_("Platz"))?></th>
			<th class="c-spieler"><?=h(_("Spieler"))?></th>
			<th class="c-allianz"><?=h(_("Allianz"))?></th>
			<th class="c-punktzahl"><?=h(_("Punktzahl"))?><?php if($sort!==false){?> (<?=h(_("[scores_".$sort."]"))?>)<?php }?></th>
		</tr>
	</thead>
<?php
	}
	else
	{
?>
<table class="highscores allianzen">
	<thead>
		<tr>
			<th class="c-platz"><?=h(_("Platz"))?></th>
			<th class="c-allianz"><?=h(_("Allianz"))?></th>
			<th class="c-punkteschnitt"><?=($_GET['alliances']=='2') ? '<a href="highscores.php?alliances=1&amp;'.htmlspecialchars(global_setting("URL_SUFFIX")).'">'.h(_("Punkteschnitt")).'</a>' : h(_('Punkteschnitt'))?></th>
			<th class="c-gesamtpunkte"><?=($_GET['alliances']=='2') ? h(_('Gesamtpunkte')) : '<a href="highscores.php?alliances=2&amp;'.htmlspecialchars(global_setting("URL_SUFFIX")).'">'.h(_("Gesamtpunkte")).'</a>'?></th>
			<th class="c-mitglieder"><?=h(_("Mitglieder"))?></th>
		</tr>
	</thead>
<?php
	}
?>
	<tbody>
<?php
	for($i=0; list(,$info)=each($list); $i++)
	{
		if($mode == 'users')
		{
			$class = 'fremd';
			if($info['username'] == $me->getName())
				$class = 'eigen';
			elseif($me->isVerbuendet($info['username']))
				$class = 'verbuendet';

			$alliance_class = 'fremd';
			if($info['alliance'] && $me->allianceTag() == $info['alliance'])
				$alliance_class = 'eigen';

			$info2 = $info;
			unset($info2["username"]);
			unset($info2["alliance"]);
			$scores = array_shift($info2);
?>
		<tr class="<?=$class?> allianz-<?=$alliance_class?>">
			<th class="c-platz"><?=F::ths($start+$i)?></th>
			<td class="c-spieler"><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($info['username']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Informationen zu diesem Spieler anzeigen"))?>" class="playername"><?=htmlspecialchars($info['username'])?></a></td>
<?php
			if($info['alliance'])
			{
?>
			<td class="c-allianz"><a href="info/allianceinfo.php?alliance=<?=htmlspecialchars(urlencode($info['alliance']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Informationen zu dieser Allianz anzeigen"))?>" class="alliancename"><?=htmlspecialchars($info['alliance'])?></a></td>
<?php
			}
			else
			{
?>
			<td class="c-allianz keine"></td>
<?php
			}
?>
			<td class="c-punktzahl number"><?=F::ths($scores)?></td>
		</tr>
<?php
		}
		else
		{
			$class = 'fremd';
			if($info['tag'] == $me->allianceTag())
				$class = 'verbuendet';

?>
		<tr class="<?=$class?>">
			<th class="c-platz"><?=F::ths($start+$i)?></th>
			<td class="c-allianz"><a href="info/allianceinfo.php?alliance=<?=htmlspecialchars(urlencode($info['tag']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Informationen zu dieser Allianz anzeigen"))?>"><?=htmlspecialchars($info['tag'])?></a></td>
			<td class="c-punkteschnitt number"><?=F::ths($info['scores_average'])?></td>
			<td class="c-gesamtpunkte number"><?=F::ths($info['scores_total'])?></td>
			<td class="c-mitglieder number"><?=F::ths($info['members_count'])?></td>
		</tr>
<?php
		}
	}
?>
	</tbody>
</table>
<?php
	$gui->end();
?>
