<?php
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
<table class="deps" id="deps-<?=htmlspecialchars($type)?>">
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
					<li class="deps-<?=($this->getItemLevel($dep[0]) >= $dep[1]) ? 'ja' : 'nein'?>"><?=sprintf(h(_("%s (Stufe %s)")), "<a href=\"#deps-".htmlspecialchars($dep[0])."\" title=\"".h(_("Zu diesem Gegenstand scrollen"))."\">".h(_("[item_".$dep[0]."]"))."</a>", ths($dep[1]))?></li>
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
