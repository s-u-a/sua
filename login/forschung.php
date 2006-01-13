<?php
	require('scripts/include.php');

	$laufende_forschungen = get_laufende_forschungen();

	if(isset($planet))
		unset($planet);

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

		logfile::action('10', $a_id);

		delete_request();
	}

	$laufende_forschungen = get_laufende_forschungen();

	login_gui::html_head();
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
		if(count($laufende_forschungen) > 0)
			$buildable_global = false; # Es wird schon wo geforscht
?>
<div class="item forschung" id="item-<?=htmlentities($id)?>">
	<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($geb['name'])?></a> <span class="stufe">(Level&nbsp;<?=ths($level)?>)</span></h3>
<?php
		if(!isset($this_planet['building']['gebaeude']) || $this_planet['building']['gebaeude'][0] != 'B8')
		{
			if(!$user_array['umode'] && (!isset($this_planet['building']['forschung']) || trim($this_planet['building']['forschung'][0]) == '') && !in_array($id, $laufende_forschungen))
			{
				if($geb['buildable'])
				{
?>
	<ul>
		<li class="item-ausbau forschung-lokal<?=$buildable ? '' : ' no-ress'?>"><?=$buildable ? '<a href="forschung.php?lokal='.htmlentities(urlencode($id)).'&amp;'.htmlentities(SESSION_COOKIE.'='.urlencode(session_id())).'" tabindex="'.$tabindex.'">' : ''?>Lokal weiterentwickeln<?=$buildable ? '</a>' : ''?></li>
<?php
					if($buildable)
						$tabindex++;
					if(count($laufende_forschungen) == 0)
					{
?>
		<li class="item-ausbau forschung-global<?=$buildable_global ? '' : ' no-ress'?>"><?=$buildable_global ? '<a href="forschung.php?global='.htmlentities(urlencode($id)).'&amp;'.htmlentities(SESSION_COOKIE.'='.urlencode(session_id())).'" tabindex="'.$tabindex.'">' : ''?>Global weiterentwickeln<?=$buildable_global ? '</a>' : ''?></li>
<?php
						if($buildable_global)
							$tabindex++;
					}
?>
	</ul>
<?php
				}
			}
			elseif(isset($this_planet['building']['forschung']) && $this_planet['building']['forschung'][0] == $id)
			{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlentities($id)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $this_planet['building']['forschung'][1])?> (Serverzeit), <a href="forschung.php?cancel=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" class="abbrechen">Abbrechen</a></div>
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