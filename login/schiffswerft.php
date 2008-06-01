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
	 * Schiffe in Auftrag geben.
	 * @author Candid Dauth
	 * @package sua-frontend
	 * @subpackage login
	*/
	namespace sua::frontend;

	require('include.php');

	if(isset($_POST['cancel-all-schiffe']))
	{
		if($me->checkPassword($_POST['cancel-all-schiffe']) && $me->removeBuildingThing('schiffe', true))
			delete_request();
	}

	if($me->permissionToAct() && isset($_POST['schiffe']) && is_array($_POST['schiffe']))
	{
		# Schiffe in Auftrag geben
		$built = 0;
		foreach($_POST['schiffe'] as $id=>$count)
		{
			if($me->buildSchiffe($id, $count)) $built++;
		}
		if($built > 0)
			delete_request();
	}

	$gui->init();
?>
<h2><?=h(_("Schiffswerft"))?></h2>
<form action="schiffswerft.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post">
<?php
	$schiffe = $me->getItemsList('schiffe');
	$building_possible = (!($building_gebaeude = $me->checkBuildingThing('gebaeude')) || $building_gebaeude[0] != 'B10');
	foreach($schiffe as $id)
	{
		$item_info = $me->getItemInfo($id, "schiffe", array("buildable", "level", "name", "ress", "time"));

		if(!$item_info['buildable'] && $item_info['level'] <= 0)
			continue;
?>
	<div class="item schiffe" id="item-<?=htmlspecialchars($id)?>">
		<h3><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item_info['name'])?></a> <span class="anzahl">(<?=htmlspecialchars($item_info['level'])?>)</span></h3>
<?php
		if($me->permissionToAct() && $building_possible && $item_info['buildable'])
		{
?>
		<ul>
			<li class="item-bau"><input type="text" class="number number-items" name="schiffe[<?=htmlspecialchars($id)?>]" value="0" tabindex="<?=$tabindex++?>" /></li>
		</ul>
<?php
		}
?>
		<dl class="lines">
			<dt class="item-kosten"><?=h(_("Kosten"))?></dt>
			<dd class="item-kosten">
<?php
		echo F::format_ress($item_info['ress'], 4, false, false, false, $me);
?>
			</dd>

			<dt class="item-bauzeit"><?=h(_("Bauzeit"))?></dt>
			<dd class="item-bauzeit"><?=F::format_btime($item_info['time'])?></dd>
		</dl>
	</div>
<?php
	}

	if($tabindex > 1)
	{
?>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("In A&uftrag geben[login/schiffswerft.php|1]"))?>><?=h(_("In A&uftrag geben[login/schiffswerft.php|1]"))?></button></div>
<?php
	}
?>
</form>
<?php
	$building_schiffe = $me->checkBuildingThing('schiffe');
	if(count($building_schiffe) > 0)
	{
?>
<h3 id="aktive-auftraege" class="strong">Aktive Aufträge</h3>
<ol class="queue schiffe">
<?php
		$i = 0;

		$keys = array_keys($building_schiffe);
		$first_building = &$building_schiffe[array_shift($keys)];
		$first = array($first_building[0], $first_building[1]+$first_building[3]);
		$first_building[1] += $first_building[3];
		$first_building[2]--;
		if($first_building[2] <= 0) array_shift($building_schiffe);
?>
	<li class="<?=htmlspecialchars($first[0])?> active<?=(count($building_schiffe) <= 0) ? ' last' : ''?>"><strong><?=h(_("[item_".$first[0]."]"))?> <span class="restbauzeit" id="restbauzeit-<?=$i++?>"><?=htmlspecialchars(F::format_ftime($first[1], $me))?></span></strong></li>
<?php
		if(count($building_schiffe) > 0)
		{
			$keys = array_keys($building_schiffe);
			$last = array_pop($keys);
			foreach($building_schiffe as $key=>$bau)
			{
				$finishing_time = $bau[1]+$bau[2]*$bau[3];
?>
	<li class="<?=htmlspecialchars($bau[0])?><?=($key == $last) ? ' last' : ''?>"><?=h(_("[item_".$bau[0]."]"))?> &times; <?=$bau[2]?><?php if($key == $last){?> <span class="restbauzeit" id="restbauzeit-<?=$i++?>"><?=htmlspecialchars(F::format_ftime($finishing_time, $me))?></span><?php }?></li>
<?php
			}
		}
?>
</ol>
<?php
		if(!$me->umode())
		{
?>
<script type="text/javascript">
	init_countdown('0', <?=$first[1]?>, false);
<?php
			if(count($building_schiffe) > 0)
			{
?>
	init_countdown('<?=$i-1?>', <?=$finishing_time?>, false);
<?php
			}
?>
</script>
<?php
		}
?>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].global_setting("h_root").'/login/schiffswerft.php?'.global_setting("URL_SUFFIX"))?>" method="post" class="alle-abbrechen">
	<p><?=sprintf(h(_("Geben Sie hier Ihr Passwort ein, um alle im Bau befindlichen Schiffe %sohne Kostenrückerstattung%s abzubrechen.")), "<strong>", "</strong>")?></p>
	<div><input type="password" name="cancel-all-schiffe" /><input type="submit" value="<?=h(_("Alle abbrechen&[login/schiffe.php|1]"), false)?>"<?=l::accesskey_attr(_("Alle abbrechen&[login/schiffe.php|1]"))?> /></div>
</form>
<?php
	}

	$gui->end();
?>
