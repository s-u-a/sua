<?php
	require('scripts/include.php');

	$planets = array_keys($user_array['planets']);
	$act = array_search($_SESSION['act_planet'], $planets);

	# Naechsten nicht bauenden Planeten herausfinden
	$i = $act+1;
	$fastbuild_next = false;
	while(true)
	{
		if($i >= count($planets))
			$i = 0;
		if($i == $act)
			break;

		if(!isset($user_array['planets'][$i]['building']['gebaeude']) || trim($user_array['planets'][$i]['building']['gebaeude'][0]) == '')
		{
			$fastbuild_next = $i;
			break;
		}

		$i++;
	}

	# Vorigen herausfinden
	$i = $act-1;
	$fastbuild_prev = false;
	while(true)
	{
		if($i < 0)
			$i = count($planets)-1;
		if($i == $act)
			break;

		if(!isset($user_array['planets'][$i]['building']['gebaeude']) || trim($user_array['planets'][$i]['building']['gebaeude'][0]) == '')
		{
			$fastbuild_prev = $i;
			break;
		}

		$i--;
	}

	if(isset($_GET['ausbau']))
	{
		$a_id = $_GET['ausbau'];
		$rueckbau = false;
	}
	elseif(isset($_GET['abbau']))
	{
		$a_id = $_GET['abbau'];
		$rueckbau = true;
	}

	if(isset($a_id) && !$user_array['umode'] && isset($items['gebaeude'][$a_id]) && $items['gebaeude'][$a_id]['buildable'] && ($a_id != 'B8' || !isset($this_planet['building']['forschung']) || trim($this_planet['building']['forschung'][0]) == '') && ($a_id != 'B9' || !isset($this_planet['building']['roboter']) || count($this_planet['building']['roboter']) == 0) && ($a_id != 'B10' || !isset($this_planet['building']['schiffe']) || count($this_planet['building']['schiffe']) == 0) && ($a_id != 'B10' || !isset($this_planet['building']['verteidigung']) || count($this_planet['building']['verteidigung']) == 0) && ($this_planet['size'][0]+$items['gebaeude'][$a_id]['fields']*(1-2*$rueckbau)) <= floor($this_planet['size'][1]))
	{
		# Ausbau

		# Ausbaulevel bis jetzt
		$level = 0;
		if(isset($this_planet['gebaeude'][$a_id]))
			$level = $this_planet['gebaeude'][$a_id];

		if(!$rueckbau || $level != 0)
		{
			# Rohstoffkosten
			$ress = $items['gebaeude'][$a_id]['ress'];
			# Aktuelle Stufe einberechnen
			$ress[0] *= pow($level+1, 2.4);
			$ress[1] *= pow($level+1, 2.4);
			$ress[2] *= pow($level+1, 2.4);
			$ress[3] *= pow($level+1, 2.4);

			if($rueckbau)
			{
				$ress[0] /= 2;
				$ress[1] /= 2;
				$ress[2] /= 2;
				$ress[3] /= 2;
			}

			$ress[0] = round($ress[0]);
			$ress[1] = round($ress[1]);
			$ress[2] = round($ress[2]);
			$ress[3] = round($ress[3]);

			if($this_planet['ress'][0] >= $ress[0] && $this_planet['ress'][1] >= $ress[1] && $this_planet['ress'][2] >= $ress[2] && $this_planet['ress'][3] >= $ress[3])
			{
				# Genuegend Rohstoffe zum Ausbau

				# Bauzeit berechnen
				$time = calc_btime_gebaeude($items['gebaeude'][$a_id]['time'], $level);

				if($rueckbau)
					$time /= 2;

				$this_planet['building']['gebaeude'] = array($a_id, time()+$time, $rueckbau, $ress);

				# Rohstoffe abziehen
				$this_planet['ress'][0] -= $ress[0];
				$this_planet['ress'][1] -= $ress[1];
				$this_planet['ress'][2] -= $ress[2];
				$this_planet['ress'][3] -= $ress[3];

				if(!write_user_array())
				{
					# Fehlgeschlagen, also alte Einstellungen wiederherstellen
					unset($this_planet['building']['gebaeude']);
					$this_planet['ress'][0] += $ress[0];
					$this_planet['ress'][1] += $ress[1];
					$this_planet['ress'][2] += $ress[2];
					$this_planet['ress'][3] += $ress[3];
				}
				else
				{
					# Jetzt in den Eventhandler aufnehmen
					eventhandler::add_event($this_planet['building']['gebaeude'][1]);

					logfile::action('7', $a_id, $rueckbau);
				}
			}
		}

		if($user_array['fastbuild'] && $fastbuild_next !== false)
		{
			# Fastbuild

			$url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?planet='.urlencode($fastbuild_next).'&'.SESSION_COOKIE.'='.urlencode(session_id());
			header('Location: '.$url, true, 303);
			die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
		}
		else
			delete_request();
	}

	if(isset($_GET['cancel']) && isset($this_planet['building']['gebaeude']) && trim($this_planet['building']['gebaeude'][0]) != '' && $this_planet['building']['gebaeude'][0] == $_GET['cancel'])
	{
		# Aktiven Aus-/Rueckbau abbrechen

		$a_id = $this_planet['building']['gebaeude'][0];

		# Bisherigen Ausbaulevel berechnen
		$level = 0;
		if(isset($this_planet['gebaeude'][$a_id]))
			$level = $this_planet['gebaeude'][$a_id];

		# Rohstoffkosten
		$ress = $items['gebaeude'][$a_id]['ress'];
		# Aktuelle Stufe einberechnen
		$ress[0] *= pow($level+1, 2.4);
		$ress[1] *= pow($level+1, 2.4);
		$ress[2] *= pow($level+1, 2.4);
		$ress[3] *= pow($level+1, 2.4);

		if($this_planet['building']['gebaeude'][2])
		{
			$ress[0] /= 2;
			$ress[1] /= 2;
			$ress[2] /= 2;
			$ress[3] /= 2;
		}

		# Rohstoffe zurueckgeben
		$this_planet['ress'][0] += $ress[0];
		$this_planet['ress'][1] += $ress[1];
		$this_planet['ress'][2] += $ress[2];
		$this_planet['ress'][3] += $ress[3];

		# Bau abbrechen
		unset($this_planet['building']['gebaeude']);

		write_user_array();

		logfile::action('8', $a_id);

		delete_request();
	}

	login_gui::html_head();
?>
<h2>Gebäude</h2>
<?php
	if(($fastbuild_prev !== false || $fastbuild_next !== false) && !$user_array['umode'])
	{
?>
<ul class="unbeschaeftigte-planeten">
<?php
		if($fastbuild_prev !== false)
		{
?>
	<li class="c-voriger"><a href="gebaeude.php?planet=<?=htmlentities(urlencode($fastbuild_prev))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Voriger unbeschäftigter Planet: &bdquo;<?=utf8_htmlentities($user_array['planets'][$fastbuild_prev]['name'])?>&ldquo; (<?=utf8_htmlentities($user_array['planets'][$fastbuild_prev]['pos'])?>) [U]" tabindex="1" accesskey="u" rel="prev">&larr;</a></li>
<?php
		}
		if($fastbuild_next !== false)
		{
?>
	<li class="c-naechster"><a href="gebaeude.php?planet=<?=htmlentities(urlencode($fastbuild_next))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Nächster unbeschäftigter Planet: &bdquo;<?=utf8_htmlentities($user_array['planets'][$fastbuild_next]['name'])?>&ldquo; (<?=utf8_htmlentities($user_array['planets'][$fastbuild_next]['pos'])?>) [Q]" tabindex="2" accesskey="q" rel="next">&rarr;</a></li>
<?php
		}
?>
</ul>
<?php
	}

	$tabindex = 3;
	foreach($items['gebaeude'] as $id=>$geb)
	{
		if(!$geb['buildable']) # Abhaengigkeiten nicht erfuellt
			continue;
		$level = 0;
		if(isset($this_planet['gebaeude'][$id]))
			$level = $this_planet['gebaeude'][$id];

		$ress = $geb['ress'];
		# Rohstoffkosten der aktuellen Ausbaustufe berechnen
		$ress[0] *= pow($level+1, 2.4);
		$ress[1] *= pow($level+1, 2.4);
		$ress[2] *= pow($level+1, 2.4);
		$ress[3] *= pow($level+1, 2.4);

		$buildable = true;
		if($this_planet['ress'][0] < $ress[0] || $this_planet['ress'][1] < $ress[1] || $this_planet['ress'][2] < $ress[2] || $this_planet['ress'][3] < $ress[3])
			$buildable = false; # Zu wenig Rohstoffe zum Bau

		$debuildable = true;
		if($this_planet['ress'][0] < $ress[0]/2 || $this_planet['ress'][1] < $ress[1]/2 || $this_planet['ress'][2] < $ress[2]/2 || $this_planet['ress'][3] < $ress[3]/2)
			$debuildable = false; # Zu wenig Rohstoffe zum Abbau
?>
<div class="item gebaeude" id="item-<?=htmlentities($id)?>">
	<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($geb['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=ths($level)?>)</span></h3>
<?php
		if(($id != 'B8' || !isset($this_planet['building']['forschung']) || trim($this_planet['building']['forschung'][0]) == '') && ($id != 'B9' || !isset($this_planet['building']['roboter']) || count($this_planet['building']['roboter']) == 0) && ($id != 'B10' || !isset($this_planet['building']['schiffe']) || count($this_planet['building']['schiffe']) == 0) && ($id != 'B10' || !isset($this_planet['building']['verteidigung']) || count($this_planet['building']['verteidigung']) == 0))
		{
			if(!$user_array['umode'] && (!isset($this_planet['building']['gebaeude']) || trim($this_planet['building']['gebaeude'][0] == '')))
			{
?>
	<ul>
<?php
				if(($this_planet['size'][0]+$geb['fields']) <= floor($this_planet['size'][1]))
				{
?>
		<li class="item-ausbau<?=$buildable ? '' : ' no-ress'?>"><?=$buildable ? '<a href="gebaeude.php?ausbau='.htmlentities(urlencode($id)).'&amp;'.htmlentities(SESSION_COOKIE.'='.urlencode(session_id())).'" tabindex="'.$tabindex.'">' : ''?>Ausbau auf Stufe&nbsp;<?=ths($level+1)?><?=$buildable ? '</a>' : ''?></li>
<?php
					if($buildable)
						$tabindex++;
				}
				if($level > 0 && ($this_planet['size'][0]-$geb['fields']) <= floor($this_planet['size'][1]))
				{
?>
		<li class="item-rueckbau<?=$buildable ? '' : ' no-ress'?>"><?=$debuildable ? '<a href="gebaeude.php?abbau='.htmlentities(urlencode($id)).'&amp;'.htmlentities(SESSION_COOKIE.'='.urlencode(session_id())).'">' : ''?>Rückbau auf Stufe&nbsp;<?=ths($level-1)?><?=$debuildable ? '</a>' : ''?></li>
<?php
				}
?>
	</ul>
<?php
			}
			elseif(isset($this_planet['building']['gebaeude']) && $this_planet['building']['gebaeude'][0] == $id)
			{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlentities($id)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $this_planet['building']['gebaeude'][1])?> (Serverzeit), <a href="gebaeude.php?cancel=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" class="abbrechen">Abbrechen</a></div>
	<script type="text/javascript">
		init_countdown('<?=$id?>', <?=$this_planet['building']['gebaeude'][1]?>);
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

		<dt class="item-bauzeit">Bauzeit</dt>
		<dd class="item-bauzeit"><?=format_btime(calc_btime_gebaeude($geb['time'], $level))?></dd>
	</dl>
</div>
<?php
	}
?>
<?php
	login_gui::html_foot();
?>