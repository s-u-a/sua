<?php
	require('scripts/include.php');

	if((!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B9') && isset($_POST['roboter']) && is_array($_POST['roboter']))
	{
		# Roboter in Auftrag geben

		$last_time = time();
		if(isset($this_planet['building']['roboter']))
		{
			foreach($this_planet['building']['roboter'] as $roboter)
			{
				if(trim($roboter[0]) == '')
					continue;
				$last_time = $roboter[1];
			}
		}

		foreach($_POST['roboter'] as $id=>$count)
		{
			if(!isset($items['roboter'][$id]) || !$items['roboter'][$id]['buildable'])
				continue;

			$ress = $items['roboter'][$id]['ress'];

			for($i = 1; $i <= $count; $i++)
			{
				# Rohstoffvorhandensein ueberpruefen
				if($this_planet['ress'][0] >= $ress[0] && $this_planet['ress'][1] >= $ress[1] && $this_planet['ress'][2] >= $ress[2] && $this_planet['ress'][3] >= $ress[3])
				{
					$time = calc_btime_roboter($items['roboter'][$id]['time']);

					if(!isset($this_planet['building']['roboter']))
						$this_planet['building']['roboter'] = array();
					$this_planet['building']['roboter'][] = array($id, $last_time+$time);
					$last_time += $time;

					$this_planet['ress'][0] -= $ress[0];
					$this_planet['ress'][1] -= $ress[1];
					$this_planet['ress'][2] -= $ress[2];
					$this_planet['ress'][3] -= $ress[3];
				}
			}
		}

		write_user_array();

		delete_request();
	}

	login_gui::html_head();
?>
<h2>Roboter</h2>
<form action="roboter.php" method="post">
<?php
	$tabindex = 1;
	foreach($items['roboter'] as $id=>$geb)
	{
		$count = 0;
		if(isset($this_planet['roboter'][$id]))
			$count = $this_planet['roboter'][$id];

		if(!$geb['buildable'] && $count <= 0)
			continue;

		$ress = $geb['ress'];
?>
	<div class="item roboter" id="item-<?=htmlentities($id)?>">
		<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($geb['name'])?></a> <span class="anzahl">(<?=utf8_htmlentities($count)?>)</span></h3>
<?php
		if((!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B9') && $geb['buildable'])
		{
?>
		<ul>
			<li class="item-bau"><input type="text" name="roboter[<?=utf8_htmlentities($id)?>]" value="0" tabindex="<?=$tabindex?>" /></li>
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
			<dd class="item-bauzeit"><?=format_btime(calc_btime_roboter($geb['time']))?></dd>
		</dl>
	</div>
<?php
	}

	if($tabindex > 1 && (!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B9'))
	{
?>
	<div><button type="submit" tabindex="<?=$tabindex?>" accesskey="u">In A<kbd>u</kbd>ftrag geben</button></div>
<?php
	}
?>
</form>
<?php
	if(isset($this_planet['building']['roboter']) && count($this_planet['building']['roboter']) > 0)
	{
?>
<h3 id="aktive-auftraege">Aktive Aufträge</h3>
<ol class="queue roboter">
<?php
		$keys = array_keys($this_planet['building']['roboter']);
		$first = array_shift($keys);
		if(count($keys) == 0)
			$last = $first;
		else
			$last = array_pop($keys);
		unset($keys);

		foreach($this_planet['building']['roboter'] as $i=>$bau)
		{
			$class = '';
			if($i == $first)
				$class .= ' active';
			if($i == $last)
				$class .= ' last';
?>
	<li class="<?=$bau[0].$class?>" title="Fertigstellung: <?=date('H:i:s, Y-m-d', $bau[1])?> (Serverzeit)"><?=utf8_htmlentities($items['roboter'][$bau[0]]['name'])?><?php if($i == $first || $i == $last){?> <span class="restbauzeit" id="restbauzeit-<?=utf8_htmlentities($i)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $bau[1])?> (Serverzeit)</span><?php }?></li>
<?php
		}
?>
</ol>
<script type="text/javascript">
	init_countdown('<?=$first?>', <?=$this_planet['building']['roboter'][$first][1]?>, false);
<?php
		if($first != $last)
		{
?>
	init_countdown('<?=$last?>', <?=$this_planet['building']['roboter'][$last][1]?>, false);
<?php
		}
?>
</script>
<?php
	}

	login_gui::html_foot();
?>