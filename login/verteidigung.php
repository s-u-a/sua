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
	require('scripts/include.php');

	if(isset($_POST['cancel-all-verteidigung']))
	{
		if($me->checkPassword($_POST['cancel-all-verteidigung']) && $me->removeBuildingThing('verteidigung', true))
			delete_request();
	}

	if($me->permissionToAct() && isset($_POST['verteidigung']) && is_array($_POST['verteidigung']))
	{
		# Verteidigungsanlagen in Auftrag geben
		$built = 0;
		foreach($_POST['verteidigung'] as $id=>$count)
		{
			if($me->buildVerteidigung($id, $count)) $built++;
		}
		if($built > 0)
			delete_request();
	}

	login_gui::html_head();
?>
<h2>Verteidigung</h2>
<form action="verteidigung.php?<?=htmlentities(urlencode(session_name()).'='.urlencode(session_id()))?>" method="post">
<?php
	$tabindex = 1;
	$verteidigung = $me->getItemsList('verteidigung');
	$building_possible = (!($building_gebaeude = $me->checkBuildingThing('gebaeude')) || $building_gebaeude[0] != 'B10');
	foreach($verteidigung as $id)
	{
		$item_info = $me->getItemInfo($id);

		if(!$item_info['buildable'] && $item_info['level'] <= 0)
			continue;
?>
	<div class="item verteidigung" id="item-<?=htmlentities($id)?>">
		<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(urlencode(session_name()).'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($item_info['name'])?></a> <span class="anzahl">(<?=utf8_htmlentities($item_info['level'])?>)</span></h3>
<?php
		if($me->permissionToAct() && $building_possible && $item_info['buildable'])
		{
?>
		<ul>
			<li class="item-bau"><input type="text" name="verteidigung[<?=utf8_htmlentities($id)?>]" value="0" tabindex="<?=$tabindex++?>" /></li>
		</ul>
<?php
		}
?>
		<dl>
			<dt class="item-kosten">Kosten</dt>
			<dd class="item-kosten">
				<?=format_ress($item_info['ress'], 4)?>
			</dd>

			<dt class="item-bauzeit">Bauzeit</dt>
			<dd class="item-bauzeit"><?=format_btime($item_info['time'])?></dd>
		</dl>
	</div>
<?php
	}

	if($tabindex > 1)
	{
?>
	<div><button type="submit" tabindex="<?=$tabindex++?>" accesskey="u">In A<kbd>u</kbd>ftrag geben</button></div>
<?php
	}
?>
</form>
<?php
	$building_verteidigung = $me->checkBuildingThing('verteidigung');
	if(count($building_verteidigung) > 0)
	{
?>
<h3 id="aktive-auftraege">Aktive Aufträge</h3>
<ol class="queue verteidigung">
<?php
		$i = 0;

		$keys = array_keys($building_verteidigung);
		$first_building = &$building_verteidigung[array_shift($keys)];
		$first = array($first_building[0], $first_building[1]+$first_building[3]);
		$first_building[1] += $first_building[3];
		$first_building[2]--;
		if($first_building[2] <= 0) array_shift($building_verteidigung);
		$first_info = $me->getItemInfo($first[0]);
?>
	<li class="<?=utf8_htmlentities($first[0])?> active<?=(count($building_verteidigung) <= 0) ? ' last' : ''?>" title="Fertigstellung: <?=date('H:i:s, Y-m-d', $first[1])?> (Serverzeit)"><strong><?=utf8_htmlentities($first_info['name'])?> <span class="restbauzeit" id="restbauzeit-<?=$i++?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $first[0])?> (Serverzeit)</span></strong></li>
<?php
		if(count($building_verteidigung) > 0)
		{
			$keys = array_keys($building_verteidigung);
			$last = array_pop($keys);
			foreach($building_verteidigung as $key=>$bau)
			{
				$finishing_time = $bau[1]+$bau[2]*$bau[3];
				$item_info = $me->getItemInfo($bau[0]);
?>
	<li class="<?=utf8_htmlentities($bau[0])?><?=($key == $last) ? ' last' : ''?>" title="Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)"><?=utf8_htmlentities($item_info['name'])?> &times; <?=$bau[2]?><?php if($key == $last){?> <span class="restbauzeit" id="restbauzeit-<?=$i++?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span><?php }?></li>
<?php
			}
		}
?>
</ol>
<script type="text/javascript">
	init_countdown('0', <?=$first[1]?>, false);
<?php
		if(count($building_verteidigung) > 0)
		{
?>
	init_countdown('<?=$i-1?>', <?=$finishing_time?>, false);
<?php
		}
?>
</script>
<form action="<?=htmlentities(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/login/verteidigung.php?'.urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="alle-abbrechen">
	<p>Geben Sie hier Ihr Passwort ein, um alle im Bau befindlichen Verteidigungsanlagen <strong>ohne Kostenrückerstattung</strong> abzubrechen.</p>
	<div><input type="password" name="cancel-all-verteidigung" /><input type="submit" value="Alle abbrechen" /></div>
</form>
<?php
	}

	login_gui::html_foot();
?>
