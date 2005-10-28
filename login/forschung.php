<?php
	require('scripts/include.php');

	$jemand_forscht = get_jemand_forscht();

	if(isset($planet))
		unset($planet);

	if(isset($_GET['lokal']))
	{
		$a_id = $_GET['lokal'];
		$global = false;
	}
	elseif(isset($_GET['global']) && !$jemand_forscht)
	{
		$a_id = $_GET['global'];
		$global = true;
	}
	if((!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B8') && isset($a_id) && !$user_array['umode'] && isset($items['forschung'][$a_id]) && $items['forschung'][$a_id]['buildable'] && (!isset($this_planet['building']['forschung']) || trim($this_planet['building']['forschung'][0]) == ''))
	{
		# Weiterforschen


		# Ausbaulevel bis jetzt
		$level = 0;
		if(isset($user_array['forschung'][$a_id]))
			$level = $user_array['forschung'][$a_id];

		# Rohstoffkosten
		$ress = $items['forschung'][$a_id]['ress'];
		# Aktuelle Stufe einberechnen
		$ress[0] *= pow($level+1, 3);
		$ress[1] *= pow($level+1, 3);
		$ress[2] *= pow($level+1, 3);
		$ress[3] *= pow($level+1, 3);

		$ress[0] = round($ress[0]);
		$ress[1] = round($ress[1]);
		$ress[2] = round($ress[2]);
		$ress[3] = round($ress[3]);

		if($this_planet['ress'][0] >= $ress[0] && $this_planet['ress'][1] >= $ress[1] && $this_planet['ress'][2] >= $ress[2] && $this_planet['ress'][3] >= $ress[3])
		{
			# Genuegend Rohstoffe zum Ausbau

			# Bauzeit berechnen
			$time = calc_btime_forschung($items['forschung'][$a_id]['time'], $level, $global+1);

			# Zum Countdown eintragen
			$build_array = array($a_id, time()+$time, $global, $ress);
			if($global)
			{
				$build_array[] = $_SESSION['act_planet'];

				$planets = array_keys($user_array['planets']);
				foreach($planets as $planet)
					$user_array['planets'][$planet]['building']['forschung'] = $build_array;
			}
			else
				$this_planet['building']['forschung'] = $build_array;


			# Rohstoffe abziehen
			$this_planet['ress'][0] -= $ress[0];
			$this_planet['ress'][1] -= $ress[1];
			$this_planet['ress'][2] -= $ress[2];
			$this_planet['ress'][3] -= $ress[3];

			if(!write_user_array())
			{
				# Fehlgeschlagen, also alte Einstellungen wiederherstellen
				unset($this_planet['building']['forschung']);
				$this_planet['ress'][0] += $ress[0];
				$this_planet['ress'][1] += $ress[1];
				$this_planet['ress'][2] += $ress[2];
				$this_planet['ress'][3] += $ress[3];
			}
			else
			{
				# Jetzt in den Eventhandler aufnehmen
				eventhandler::add_event($build_array[1]);
			}
		}

		delete_request();
	}

	if(isset($_GET['cancel']) && isset($this_planet['building']['forschung']) && trim($this_planet['building']['forschung'][0]) != '' && $this_planet['building']['forschung'][0] == $_GET['cancel'])
	{
		# Aktiven Aus-/Rueckbau abbrechen

		$a_id = $this_planet['building']['forschung'][0];

		# Bisherigen Ausbaulevel berechnen
		$level = 0;
		if(isset($user_array['forschung'][$a_id]))
			$level = $user_array['forschung'][$a_id];

		# Rohstoffkosten
		$ress = $items['forschung'][$a_id]['ress'];
		# Aktuelle Stufe einberechnen
		$ress[0] *= pow($level+1, 3);
		$ress[1] *= pow($level+1, 3);
		$ress[2] *= pow($level+1, 3);
		$ress[3] *= pow($level+1, 3);

		$ress_back_pl = $_SESSION['act_planet'];
		if($this_planet['building']['forschung'][2])
			$ress_back_pl = $this_planet['building']['forschung'][4];
		if(!isset($user_array['planets'][$ress_back_pl]))
			$ress_back_pl = $_SESSION['act_planet'];

		# Rohstoffe zurueckgeben
		$user_array['planets'][$ress_back_pl]['ress'][0] += $ress[0];
		$user_array['planets'][$ress_back_pl]['ress'][1] += $ress[1];
		$user_array['planets'][$ress_back_pl]['ress'][2] += $ress[2];
		$user_array['planets'][$ress_back_pl]['ress'][3] += $ress[3];

		# Bau abbrechen
		if($this_planet['building']['forschung'][2])
		{
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
				unset($user_array['planets'][$planet]['building']['forschung']);
		}
		else
			unset($this_planet['building']['forschung']);

		write_user_array();

		# Eintrag aus dem Eventhandler loeschen


		delete_request();
	}

	$jemand_forscht = get_jemand_forscht();

	login_gui::html_head();

	/*echo '<pre>';
	print_r($user_array);
	echo '</pre>';*/
?>
<h2>Forschung</h2>
<?php
	$tabindex = 1;
	foreach($items['forschung'] as $id=>$geb)
	{
		$level = 0;
		if(isset($user_array['forschung'][$id]))
			$level = $user_array['forschung'][$id];

		if(!$geb['buildable'] && $level <= 0 && (!isset($this_planet['building']['forschung']) || trim($this_planet['building']['forschung'][0]) == '' || $this_planet['building']['forschung'][0] != $id)) # Abhaengigkeiten nicht erfuellt bzw. gerade nicht im Bau
			continue;

		$ress = $geb['ress'];
		# Rohstoffkosten der aktuellen Ausbaustufe berechnen
		$ress[0] *= pow($level+1, 3);
		$ress[1] *= pow($level+1, 3);
		$ress[2] *= pow($level+1, 3);
		$ress[3] *= pow($level+1, 3);

		$buildable = true;
		if($this_planet['ress'][0] < $ress[0] || $this_planet['ress'][1] < $ress[1] || $this_planet['ress'][2] < $ress[2] || $this_planet['ress'][3] < $ress[3])
			$buildable = false; # Zu wenig Rohstoffe zum Bau
		$buildable_global = $buildable;
		if($jemand_forscht)
			$buildable_global = false; # Es wird schon wo geforscht
?>
<div class="item forschung" id="item-<?=htmlentities($id)?>">
	<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($geb['name'])?></a> <span class="stufe">(Level&nbsp;<?=ths($level)?>)</span></h3>
<?php
		if((!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B8') && !$user_array['umode'])
		{
			if(!isset($this_planet['building']['forschung']) || trim($this_planet['building']['forschung'][0] == ''))
			{
				if($geb['buildable'])
				{
?>
	<ul>
		<li class="item-ausbau forschung-lokal<?=$buildable ? '' : ' no-ress'?>"><?=$buildable ? '<a href="forschung.php?lokal='.htmlentities(urlencode($id)).'" tabindex="'.$tabindex.'">' : ''?>Lokal weiterentwickeln<?=$buildable ? '</a>' : ''?></li>
<?php
					if($buildable)
						$tabindex++;
					if(!$jemand_forscht)
					{
?>
		<li class="item-ausbau forschung-global<?=$buildable_global ? '' : ' no-ress'?>"><?=$buildable_global ? '<a href="forschung.php?global='.htmlentities(urlencode($id)).'" tabindex="'.$tabindex.'">' : ''?>Global weiterentwickeln<?=$buildable_global ? '</a>' : ''?></li>
<?php
						if($buildable_global)
							$tabindex++;
					}
?>
	</ul>
<?php
				}
			}
			elseif($this_planet['building']['forschung'][0] == $id)
			{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlentities($id)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $this_planet['building']['forschung'][1])?> (Serverzeit), <a href="forschung.php?cancel=<?=htmlentities(urlencode($id))?>" class="abbrechen">Abbrechen</a></div>
	<script type="text/javascript">
		init_countdown('<?=$id?>', <?=$this_planet['building']['forschung'][1]?>);
	</script>
<?php
			}
		}
?>
	<dl>
		<dt class="item-kosten">Kosten</dt>
		<dd class="item-kosten">
			<?=format_ress($ress, 3)?>
		</dd>

		<dt class="item-bauzeit forschung-lokal">Bauzeit lokal</dt>
		<dd class="item-bauzeit forschung-lokal"><?=format_btime(calc_btime_forschung($geb['time'], $level, 1))?></dd>

		<dt class="item-bauzeit forschung-global">Bauzeit global</dt>
		<dd class="item-bauzeit forschung-global"><?=format_btime(calc_btime_forschung($geb['time'], $level, 2))?></dd>
	</dl>
</div>
<?php
	}
?>
<?php
	login_gui::html_foot();
?>