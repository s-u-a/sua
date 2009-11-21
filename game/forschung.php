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
	namespace sua\psua;

	use \sua\L;
	use \sua\F;

	require('include.php');

	$laufende_forschungen = array();
	foreach($USER as $planet)
	{
		$building = $planet->checkBuildingThing('forschung');
		if($building)
			$laufende_forschungen[] = $building[0];
		elseif($building = $planet->checkBuildingThing('gebaeude') && $building[0] == 'B8')
			$laufende_forschungen[] = false;
	}

	$a_id = null;
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

	if(isset($a_id) && $USER->permissionToAct() && $PLANET->buildForschung($a_id, $global))
		delete_request();

	if(isset($_GET['cancel']))
	{
		$building = $PLANET->checkBuildingThing('forschung');
		if($building && $building[0] == $_GET['cancel'] && $PLANET->removeBuildingThing('forschung'))
			delete_request();
	}

	$forschungen = $USER->getItemsList('forschung');
	$building = $PLANET->checkBuildingThing('forschung');

	$GUI->init();
?>
<h2><?=L::h(_("Forschung"))?></h2>
<?php
	foreach($forschungen as $id)
	{
		$item_info = $USER->getItemInfo($id, 'forschung', array("deps-okay", "level", "buildable", "ress", "level", "name", "time_local", "time_global"), $PLANET);

		if(!$item_info['deps-okay'] && $item_info['level'] <= 0 && (!$building || $building[0] != $id))
			continue;

		$buildable_global = $item_info['buildable'];
		if($buildable_global && count($laufende_forschungen) > 0)
			$buildable_global = false; # Es wird schon wo geforscht
?>
<div class="item forschung" id="item-<?=htmlspecialchars($id)?>">
	<h3><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars($GUI->getOption("url_suffix"))?>" title="<?=L::h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item_info['name'])?></a> <span class="stufe">(<?=sprintf(h(_("Level %s")), F::ths($item_info['level']))?>)</span></h3>
<?php
		if((!($building_geb = $USER->checkBuildingThing('gebaeude')) || $building_geb[0] != 'B8') && $item_info['buildable'] && $USER->permissionToAct() && !($building = $USER->checkBuildingThing('forschung')) && !in_array($id, $laufende_forschungen) && $item_info['deps-okay'])
		{
			$enough_ress = $USER->checkRess($item_info['ress']);
			$buildable_global = ($buildable_global && $enough_ress);
?>
	<ul>
		<li class="item-ausbau forschung-lokal <?=$enough_ress ? 'genug' : 'fehlend'?>"><?=$enough_ress ? '<a href="forschung.php?lokal='.htmlspecialchars(urlencode($id)).'&amp;'.htmlspecialchars($GUI->getOption("url_suffix")).'" tabindex="'.($tabindex++).'">' : ''?><?=L::h(_("Lokal weiterentwickeln"))?><?=$enough_ress ? '</a>' : ''?></li>
<?php
			if(count($laufende_forschungen) <= 0)
			{
?>
		<li class="item-ausbau forschung-global <?=$buildable_global ? 'genug' : 'fehlend'?>"><?=$buildable_global ? '<a href="forschung.php?global='.htmlspecialchars(urlencode($id)).'&amp;'.htmlspecialchars($GUI->getOption("url_suffix")).'" tabindex="'.($tabindex++).'">' : ''?><?=L::h(_("Global weiterentwickeln"))?><?=$buildable_global ? '</a>' : ''?></li>
<?php
			}
?>
	</ul>
<?php
		}
		elseif($building && $building[0] == $id)
		{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlspecialchars($id)?>"><?=htmlspecialchars(F::formatFTime($building[1], $USER))?> <a href="forschung.php?cancel=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars($GUI->getOption("url_suffix"))?>" class="abbrechen"><?=L::h(_("Abbrechen"))?></a></div>
<?php
			if(!$USER->umode())
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
		<dt class="item-kosten"><?=L::h(_("Kosten"))?></dt>
		<dd class="item-kosten">
<?php
		echo F::formatRess($item_info['ress'], 3, false, false, false, $PLANET);
?>
		</dd>

		<dt class="item-bauzeit forschung-lokal"><?=L::h(_("Bauzeit lokal"))?></dt>
		<dd class="item-bauzeit forschung-lokal"><?=F::formatBTime($item_info['time_local'])?></dd>

		<dt class="item-bauzeit forschung-global"><?=L::h(_("Bauzeit global"))?></dt>
		<dd class="item-bauzeit forschung-global"><?=F::formatBTime($item_info['time_global'])?></dd>
	</dl>
</div>
<?php
	}
?>
<?php
	$GUI->end();
