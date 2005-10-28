<?php
	require('scripts/include.php');

	if(!$user_array['umode'] && (!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B10') && isset($_POST['schiffe']) && is_array($_POST['schiffe']))
	{
		$last_time = time();
		if(isset($this_planet['building']['schiffe']))
		{
			foreach($this_planet['building']['schiffe'] as $schiff)
			{
				if(trim($schiff[0]) == '')
					continue;
				$last_time = $schiff[1];
			}
		}

		$event_times = array();

		foreach($_POST['schiffe'] as $id=>$count)
		{
			if(!isset($items['schiffe'][$id]) || !$items['schiffe'][$id]['buildable'])
				continue;

			$ress = $items['schiffe'][$id]['ress'];

			for($i = 1; $i <= $count; $i++)
			{
				# Rohstoffvorhandensein ueberpruefen
				if($this_planet['ress'][0] >= $ress[0] && $this_planet['ress'][1] >= $ress[1] && $this_planet['ress'][2] >= $ress[2] && $this_planet['ress'][3] >= $ress[3])
				{
					$time = calc_btime_schiffe($items['schiffe'][$id]['time']);

					if(!isset($this_planet['building']['schiffe']))
						$this_planet['building']['schiffe'] = array();
					$last_time += $time;
					$this_planet['building']['schiffe'][] = array($id, $last_time);

					$this_planet['ress'][0] -= $ress[0];
					$this_planet['ress'][1] -= $ress[1];
					$this_planet['ress'][2] -= $ress[2];
					$this_planet['ress'][3] -= $ress[3];

					$event_times[] = $last_time;
				}
			}
		}

		write_user_array();

		eventhandler::add_event($event_times);

		delete_request();
	}

	login_gui::html_head();
?>
<h2>Schiffswerft</h2>
<form action="schiffswerft.php" method="post">
<?php
	$tabindex = 1;
	foreach($items['schiffe'] as $id=>$geb)
	{
		$count = 0;
		if(isset($this_planet['schiffe'][$id]))
			$count = $this_planet['schiffe'][$id];

		if(!$geb['buildable'] && $count <= 0) # Abhaengigkeiten nicht erfuellt
			continue;

		$ress = $geb['ress'];
?>
	<div class="item schiffe" id="item-<?=htmlentities($id)?>">
		<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($geb['name'])?></a> <span class="anzahl">(<?=ths($count)?>)</span></h3>
<?php
		if(!$user_array['umode'] && (!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B10') && $geb['buildable'])
		{
?>
		<ul>
			<li class="item-bau"><input type="text" name="schiffe[<?=utf8_htmlentities($id)?>]" value="0" tabindex="<?=$tabindex?>" /></li>
		</ul>
<?php
			$tabindex++;
		}
?>
		<dl>
			<dt class="item-kosten">Kosten</dt>
			<dd class="item-kosten">
				<?=format_ress($ress, 4)?>
			</dd>

			<dt class="item-bauzeit">Bauzeit</dt>
			<dd class="item-bauzeit"><?=format_btime(calc_btime_schiffe($geb['time']))?></dd>
		</dl>
	</div>
<?php
	}

	if($tabindex > 1 && (!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B10'))
	{
?>
	<div><button type="submit" accesskey="u" tabindex="<?=$tabindex?>">In A<kbd>u</kbd>ftrag geben</button></div>
<?php
	}
?>
</form>
<?php
	if(isset($this_planet['building']['schiffe']) && count($this_planet['building']['schiffe']) > 0)
	{
?>
<h3 id="aktive-auftraege">Aktive Aufträge</h3>
<ol class="queue schiffe">
<?php
		$keys = array_keys($this_planet['building']['schiffe']);
		$first = array_shift($keys);
		if(count($keys) == 0)
			$last = $first;
		else
			$last = array_pop($keys);
		unset($keys);

		foreach($this_planet['building']['schiffe'] as $i=>$bau)
		{
			$class = '';
			if($i == $first)
				$class .= ' active';
			if($i == $last)
				$class .= ' last';
?>
	<li class="<?=$bau[0].$class?>" title="Fertigstellung: <?=date('H:i:s, Y-m-d', $bau[1])?> (Serverzeit)"><?=utf8_htmlentities($items['schiffe'][$bau[0]]['name'])?><?php if($i == $first || $i == $last){?> <span class="restbauzeit" id="restbauzeit-<?=utf8_htmlentities($i)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $bau[1])?> (Serverzeit)</span><?php }?></li>
<?php
		}
?>
</ol>
<script type="text/javascript">
	init_countdown('<?=$first?>', <?=$this_planet['building']['schiffe'][$first][1]?>, false);
<?php
		if($first != $last)
		{
?>
	init_countdown('<?=$last?>', <?=$this_planet['building']['schiffe'][$last][1]?>, false);
<?php
		}
?>
</script>
<?php
	}

	login_gui::html_foot();
?>