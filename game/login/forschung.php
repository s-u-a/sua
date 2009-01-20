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
	 * Forschungen in Auftrag geben.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\frontend;

	require('include.php');

	$laufende_forschungen = array();
	$planets = $me->getPlanetsList();
	$active_planet = $me->getActivePlanet();
	foreach($planets as $planet)
	{
		$me->setActivePlanet($planet);
		$building = $me->checkBuildingThing('forschung');
		if($building)
			$laufende_forschungen[] = $building[0];
		elseif($building = $me->checkBuildingThing('gebaeude') && $building[0] == 'B8')
			$laufende_forschungen[] = false;
	}
	$me->setActivePlanet($active_planet);

	if(isset($_GET['lokal']))
	{
		$a_id = $_GET['lokal'];
		$global = false;
	}
	elseif(isset($_GET['global']) && count($laufende_forschungen) == 0)
	{
		$a_id = $_GET['global'];
		$global = true;
	}

	if(isset($a_id) && $me->permissionToAct() && $me->buildForschung($a_id, $global))
		delete_request();

	if(isset($_GET['cancel']))
	{
		$building = $me->checkBuildingThing('forschung');
		if($building && $building[0] == $_GET['cancel'] && $me->removeBuildingThing('forschung'))
			delete_request();
	}

	$forschungen = $me->getItemsList('forschung');
	$building = $me->checkBuildingThing('forschung');

	$gui->init();
?>
<h2><?=l::h(_("Forschung"))?></h2>
<?php
	foreach($forschungen as $id)
	{
		$item_info = $me->getItemInfo($id, 'forschung', array("deps-okay", "level", "buildable", "ress", "level", "name", "time_local", "time_global"));

		if(!$item_info['deps-okay'] && $item_info['level'] <= 0 && (!$building || $building[0] != $id))
			continue;

		$buildable_global = $item_info['buildable'];
		if($buildable_global && count($laufende_forschungen) > 0)
			$buildable_global = false; # Es wird schon wo geforscht
?>
<div class="item forschung" id="item-<?=htmlspecialchars($id)?>">
	<h3><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item_info['name'])?></a> <span class="stufe">(<?=sprintf(h(_("Level %s")), F::ths($item_info['level']))?>)</span></h3>
<?php
		if((!($building_geb = $me->checkBuildingThing('gebaeude')) || $building_geb[0] != 'B8') && $item_info['buildable'] && $me->permissionToAct() && !($building = $me->checkBuildingThing('forschung')) && !in_array($id, $laufende_forschungen) && $item_info['deps-okay'])
		{
			$enough_ress = $me->checkRess($item_info['ress']);
			$buildable_global = ($buildable_global && $enough_ress);
?>
	<ul>
		<li class="item-ausbau forschung-lokal <?=$enough_ress ? 'genug' : 'fehlend'?>"><?=$enough_ress ? '<a href="forschung.php?lokal='.htmlspecialchars(urlencode($id)).'&amp;'.htmlspecialchars(global_setting("URL_SUFFIX")).'" tabindex="'.($tabindex++).'">' : ''?><?=l::h(_("Lokal weiterentwickeln"))?><?=$enough_ress ? '</a>' : ''?></li>
<?php
			if(count($laufende_forschungen) <= 0)
			{
?>
		<li class="item-ausbau forschung-global <?=$buildable_global ? 'genug' : 'fehlend'?>"><?=$buildable_global ? '<a href="forschung.php?global='.htmlspecialchars(urlencode($id)).'&amp;'.htmlspecialchars(global_setting("URL_SUFFIX")).'" tabindex="'.($tabindex++).'">' : ''?><?=l::h(_("Global weiterentwickeln"))?><?=$buildable_global ? '</a>' : ''?></li>
<?php
			}
?>
	</ul>
<?php
		}
		elseif($building && $building[0] == $id)
		{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlspecialchars($id)?>"><?=htmlspecialchars(F::format_ftime($building[1], $me))?> <a href="forschung.php?cancel=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="abbrechen"><?=l::h(_("Abbrechen"))?></a></div>
<?php
			if(!$me->umode())
			{
?>
	<script type="text/javascript">
		init_countdown('<?=$id?>', <?=$building[1]?>);
	</script>
<?php
			}
		}
?>
	<dl class="lines">
		<dt class="item-kosten"><?=l::h(_("Kosten"))?></dt>
		<dd class="item-kosten">
<?php
		echo F::format_ress($item_info['ress'], 3, false, false, false, $me);
?>
		</dd>

		<dt class="item-bauzeit forschung-lokal"><?=l::h(_("Bauzeit lokal"))?></dt>
		<dd class="item-bauzeit forschung-lokal"><?=F::format_btime($item_info['time_local'])?></dd>

		<dt class="item-bauzeit forschung-global"><?=l::h(_("Bauzeit global"))?></dt>
		<dd class="item-bauzeit forschung-global"><?=F::format_btime($item_info['time_global'])?></dd>
	</dl>
</div>
<?php
	}
?>
<?php
	$gui->end();
?>
