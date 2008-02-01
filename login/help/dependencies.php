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
	require('../scripts/include.php');

	login_gui::html_head();

	$check_deps = array(
		'gebaeude' => ngettext("Gebäude", "Gebäude", 1),
		'forschung' => ngettext("Forschung", "Forschungen", 1),
		'roboter' => ngettext("Roboter", "Roboter", 1),
		'schiffe' => ngettext("Schiff", "Schiffe", 1),
		'verteidigung' => ngettext("Verteidigungsanlage", "Verteidigungsanlagen", 1)
	);

	foreach($check_deps as $type=>$heading)
	{
?>
<table class="deps table-2" id="deps-<?=htmlspecialchars($type)?>">
	<thead>
		<tr>
			<th class="c-item"><?=h($heading)?></th>
			<th class="c-deps"><?=h(_("Abhängigkeiten"))?></th>
		</tr>
	</thead>
	<tbody>
<?php
		$items = $me->getItemsList($type);
		foreach($items as $item)
		{
			$item_info = $me->getItemInfo($item, $type);
?>
		<tr id="deps-<?=htmlspecialchars($item)?>">
			<td class="c-item"><a href="description.php?id=<?=htmlspecialchars(urlencode($item))?>&amp;<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" title="<?=h(_("Genauere Informationen anzeigen"))?>"><?=h(_("[item_".$item."]"))?></a></td>
<?php
			if(!isset($item_info['deps']) || count($item_info['deps']) <= 0)
			{
?>
			<td class="c-deps"></td>
<?php
			}
			else
			{
?>
			<td class="c-deps">
				<ul class="deps">
<?php
				$needed_deps = array();
				foreach($item_info['deps'] as $dep)
				{
					$dep = explode('-', $dep, 2);
?>
					<li class="deps-<?=($me->getItemLevel($dep[0]) >= $dep[1]) ? 'ja' : 'nein'?>"><?=sprintf(h(_("%s (Stufe %s)")), "<a href=\"#deps-".htmlspecialchars($dep[0])."\" title=\"".h(_("Zu diesem Gegenstand scrollen"))."\">".h(_("[item_".$dep[0]."]"))."</a>", ths($dep[1]))?></li>
<?php
				}
?>
				</ul>
			</td>
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
	}

	login_gui::html_foot();
?>
