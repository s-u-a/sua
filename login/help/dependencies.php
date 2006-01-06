<?php
	require('../scripts/include.php');

	login_gui::html_head();
?>
<table class="deps" id="deps-gebaeude">
	<thead>
		<tr>
			<th class="c-item">Gebäude</th>
			<th class="c-deps">Abhängigkeiten</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($items['gebaeude'] as $id=>$gebaeude)
	{
?>
		<tr id="deps-<?=htmlentities($id)?>">
			<td class="c-item"><a href="description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($gebaeude['name'])?></a></td>
<?php
		if(!isset($gebaeude['deps']) || count($gebaeude['deps']) <= 0)
		{
?>
			<td class="c-deps"></td>
<?php
		}
		else
		{
?>
			<td class="c-deps">
				<ul>
<?php
			/*echo '<pre>';
			print_r($this_planet['ids']);
			echo '</pre>';*/
			foreach($gebaeude['deps'] as $dep)
			{
				$dep = explode('-', $dep);
				$this_item = &$items['ids'][$dep[0]];
?>
					<li class="deps-<?=(isset($this_planet['ids'][$dep[0]]) && $this_planet['ids'][$dep[0]] >= $dep[1]) ? 'ja' : 'nein'?>"><a href="#deps-<?=htmlentities($dep[0])?>" title="Zu diesem Gegenstand scrollen."><?=utf8_htmlentities($this_item['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($dep[1])?>)</span></li>
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
<table class="deps" id="deps-forschung">
	<thead>
		<tr>
			<th class="c-item">Forschung</th>
			<th class="c-deps">Abhängigkeiten</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($items['forschung'] as $id=>$forschung)
	{
?>
		<tr id="deps-<?=htmlentities($id)?>">
			<td class="c-item"><a href="description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($forschung['name'])?></a></td>
<?php
		if(!isset($forschung['deps']) || count($forschung['deps']) <= 0)
		{
?>
			<td class="c-deps"></td>
<?php
		}
		else
		{
?>
			<td class="c-deps">
				<ul>
<?php
			foreach($forschung['deps'] as $dep)
			{
				$dep = explode('-', $dep);
				$this_item = &$items['ids'][$dep[0]];
?>
					<li class="deps-<?=(isset($this_planet['ids'][$dep[0]]) && $this_planet['ids'][$dep[0]] >= $dep[1]) ? 'ja' : 'nein'?>"><a href="#deps-<?=htmlentities($dep[0])?>" title="Zu diesem Gegenstand scrollen."><?=utf8_htmlentities($this_item['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($dep[1])?>)</span></li>
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
<table class="deps" id="deps-roboter">
	<thead>
		<tr>
			<th class="c-item">Roboter</th>
			<th class="c-deps">Abhängigkeiten</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($items['roboter'] as $id=>$roboter)
	{
?>
		<tr id="deps-<?=htmlentities($id)?>">
			<td class="c-item"><a href="description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($roboter['name'])?></a></td>
<?php
		if(!isset($roboter['deps']) || count($roboter['deps']) <= 0)
		{
?>
			<td class="c-deps"></td>
<?php
		}
		else
		{
?>
			<td class="c-deps">
				<ul>
<?php
			foreach($roboter['deps'] as $dep)
			{
				$dep = explode('-', $dep);
				$this_item = &$items['ids'][$dep[0]];
?>
					<li class="deps-<?=(isset($this_planet['ids'][$dep[0]]) && $this_planet['ids'][$dep[0]] >= $dep[1]) ? 'ja' : 'nein'?>"><a href="#deps-<?=htmlentities($dep[0])?>" title="Zu diesem Gegenstand scrollen."><?=utf8_htmlentities($this_item['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($dep[1])?>)</span></li>
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
<table class="deps" id="deps-schiffe">
	<thead>
		<tr>
			<th class="c-item">Schiff</th>
			<th class="c-deps">Abhängigkeiten</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($items['schiffe'] as $id=>$schiff)
	{
?>
		<tr id="deps-<?=htmlentities($id)?>">
			<td class="c-item"><a href="description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($schiff['name'])?></a></td>
<?php
		if(!isset($schiff['deps']) || count($schiff['deps']) <= 0)
		{
?>
			<td class="c-deps"></td>
<?php
		}
		else
		{
?>
			<td class="c-deps">
				<ul>
<?php
			foreach($schiff['deps'] as $dep)
			{
				$dep = explode('-', $dep);
				$this_item = &$items['ids'][$dep[0]];
?>
					<li class="deps-<?=(isset($this_planet['ids'][$dep[0]]) && $this_planet['ids'][$dep[0]] >= $dep[1]) ? 'ja' : 'nein'?>"><a href="#deps-<?=htmlentities($dep[0])?>" title="Zu diesem Gegenstand scrollen."><?=utf8_htmlentities($this_item['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($dep[1])?>)</span></li>
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
<table class="deps" id="deps-verteidigung">
	<thead>
		<tr>
			<th class="c-item">Verteidigungsanlage</th>
			<th class="c-deps">Abhängigkeiten</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($items['verteidigung'] as $id=>$verteidigung)
	{
?>
		<tr id="deps-<?=htmlentities($id)?>">
			<td class="c-item"><a href="description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($verteidigung['name'])?></a></td>
<?php
		if(!isset($verteidigung['deps']) || count($verteidigung['deps']) <= 0)
		{
?>
			<td class="c-deps"></td>
<?php
		}
		else
		{
?>
			<td class="c-deps">
				<ul>
<?php
			foreach($verteidigung['deps'] as $dep)
			{
				$dep = explode('-', $dep);
				$this_item = &$items['ids'][$dep[0]];
?>
					<li class="deps-<?=(isset($this_planet['ids'][$dep[0]]) && $this_planet['ids'][$dep[0]] >= $dep[1]) ? 'ja' : 'nein'?>"><a href="#deps-<?=htmlentities($dep[0])?>" title="Zu diesem Gegenstand scrollen."><?=utf8_htmlentities($this_item['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($dep[1])?>)</span></li>
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
	login_gui::html_foot();
?>
