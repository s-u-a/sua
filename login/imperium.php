<?php
	require('scripts/include.php');
	
	switch(isset($_GET['action']) ? $_GET['action'] : false)
	{
		case 'roboter':
		case 'flotte':
			$action = $_GET['action'];
			break;
		default:
			$action = 'ress';
			break;
	}
	
	function get_prod_class($prod)
	{
		if($prod > 0)
			return 'positiv';
		elseif($prod < 0)
			return 'negativ';
		else
			return 'null';
	}
	
	login_gui::html_head();
	
	$tabindex = 1;
?>
<h2>Imperium</h2>
<ul class="imperium-modi">
	<li class="c-rohstoffe<?=($action == 'ress') ? ' active' : ''?>"><a href="imperium.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"<?=($action == 'ress') ? '' : ' tabindex="'.htmlentities($tabindex++).'"'?>>Rohstoffe</a></li>
	<li class="c-roboter<?=($action == 'roboter') ? ' active' : ''?>"><a href="imperium.php?action=roboter&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"<?=($action == 'roboter') ? '' : ' tabindex="'.htmlentities($tabindex++).'"'?>>Roboter</a></li>
	<li class="c-flotte<?=($action == 'flotte') ? ' active' : ''?>"><a href="imperium.php?action=flotte&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"<?=($action == 'flotten') ? '' : ' tabindex="'.htmlentities($tabindex++).'"'?>>Flotten</a></li>
</ul>
<?php
	switch($action)
	{
		case 'ress':
?>
<h3 id="rohstoffvorraete">Rohstoffvorräte</h3>
<table class="imperium-tabelle imperium-rohstoffvorraete">
	<thead>
		<tr>
			<th class="c-planet">Planet</th>
			<th class="c-carbon">Carbon</th>
			<th class="c-aluminium">Aluminium</th>
			<th class="c-wolfram">Wolfram</th>
			<th class="c-radium">Radium</th>
			<th class="c-tritium">Tritium</th>
			<th class="c-gesamt">Gesamt</th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array(0, 0, 0, 0, 0, 0);
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				$ges[0] += $user_array['planets'][$planet]['ress'][0];
				$ges[1] += $user_array['planets'][$planet]['ress'][1];
				$ges[2] += $user_array['planets'][$planet]['ress'][2];
				$ges[3] += $user_array['planets'][$planet]['ress'][3];
				$ges[4] += $user_array['planets'][$planet]['ress'][4];
				$this_ges = $user_array['planets'][$planet]['ress'][0]+$user_array['planets'][$planet]['ress'][1]+$user_array['planets'][$planet]['ress'][2]+$user_array['planets'][$planet]['ress'][3]+$user_array['planets'][$planet]['ress'][4];
				$ges[5] += $this_ges;
?>
		<tr>
			<th class="c-planet" title="<?=utf8_htmlentities($user_array['planets'][$planet]['name'])?>"><?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?></th>
			<td class="c-carbon"><?=ths($user_array['planets'][$planet]['ress'][0])?></td>
			<td class="c-aluminium"><?=ths($user_array['planets'][$planet]['ress'][1])?></td>
			<td class="c-wolfram"><?=ths($user_array['planets'][$planet]['ress'][2])?></td>
			<td class="c-radium"><?=ths($user_array['planets'][$planet]['ress'][3])?></td>
			<td class="c-tritium"><?=ths($user_array['planets'][$planet]['ress'][4])?></td>
			<td class="c-gesamt"><?=ths($this_ges)?></td>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot>
		<tr>
			<th class="c-planet">Gesamt</th>
			<td class="c-carbon"><?=ths($ges[0])?></td>
			<td class="c-aluminium"><?=ths($ges[1])?></td>
			<td class="c-wolfram"><?=ths($ges[2])?></td>
			<td class="c-radium"><?=ths($ges[3])?></td>
			<td class="c-tritium"><?=ths($ges[4])?></td>
			<td class="c-gesamt"><?=ths($ges[5])?></td>
		</tr>
	</tfoot>
</table>
<h3 id="rohstoffproduktion">Rohstoffproduktion pro Stunde</h3>
<table class="imperium-tabelle imperium-rohstoffproduktion">
	<thead>
		<tr>
			<th class="c-planet">Planet</th>
			<th class="c-carbon">Carbon</th>
			<th class="c-aluminium">Aluminium</th>
			<th class="c-wolfram">Wolfram</th>
			<th class="c-radium">Radium</th>
			<th class="c-tritium">Tritium</th>
			<th class="c-gesamt">Gesamt</th>
			<th class="c-energie">Energie</th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array(0, 0, 0, 0, 0, 0, 0);
			foreach($planets as $planet)
			{
				$this_planet = &$user_array['planets'][$planet];
				$this_prod = get_ges_prod();
				
				$ges[0] += $this_prod[0];
				$ges[1] += $this_prod[1];
				$ges[2] += $this_prod[2];
				$ges[3] += $this_prod[3];
				$ges[4] += $this_prod[4];
				$ges[5] += $this_prod[5];
				$this_ges = $this_prod[0]+$this_prod[1]+$this_prod[2]+$this_prod[3]+$this_prod[4];
				$ges[6] += $this_ges;
?>
		<tr>
			<th class="c-planet" title="<?=utf8_htmlentities($this_planet['name'])?>"><?=utf8_htmlentities($this_planet['pos'])?></a></th>
			<td class="c-carbon <?=get_prod_class($this_prod[0])?>"><?=ths($this_prod[0])?></td>
			<td class="c-aluminium <?=get_prod_class($this_prod[1])?>"><?=ths($this_prod[1])?></td>
			<td class="c-wolfram <?=get_prod_class($this_prod[2])?>"><?=ths($this_prod[2])?></td>
			<td class="c-radium <?=get_prod_class($this_prod[3])?>"><?=ths($this_prod[3])?></td>
			<td class="c-tritium <?=get_prod_class($this_prod[4])?>"><?=ths($this_prod[4])?></td>
			<td class="c-gesamt <?=get_prod_class($this_ges)?>"><?=ths($this_ges)?></td>
			<td class="c-energie <?=get_prod_class($this_prod[5])?>"><?=ths($this_prod[5])?></td>
		</tr>
<?php
			}
			
			$day_prod = array($ges[0]*24, $ges[1]*24, $ges[2]*24, $ges[3]*24, $ges[4]*24);
			$show_day_prod = $day_prod;
			$show_days = 1;
			if(isset($user_array['prod_show_days']))
				$show_days = $user_array['prod_show_days'];
			$show_day_prod[0] *= $show_days;
			$show_day_prod[1] *= $show_days;
			$show_day_prod[2] *= $show_days;
			$show_day_prod[3] *= $show_days;
			$show_day_prod[4] *= $show_days;
			$show_day_prod[5] = array_sum($show_day_prod);
?>
	</tbody>
	<tfoot>
		<tr class="gesamt-stuendlich">
			<th class="c-planet">Gesamt</th>
			<td class="c-carbon <?=get_prod_class($ges[0])?>"><?=ths($ges[0])?></td>
			<td class="c-aluminium <?=get_prod_class($ges[1])?>"><?=ths($ges[1])?></td>
			<td class="c-wolfram <?=get_prod_class($ges[2])?>"><?=ths($ges[2])?></td>
			<td class="c-radium <?=get_prod_class($ges[3])?>"><?=ths($ges[3])?></td>
			<td class="c-tritium <?=get_prod_class($ges[4])?>"><?=ths($ges[4])?></td>
			<td class="c-gesamt <?=get_prod_class($ges[6])?>"><?=ths($ges[6])?></td>
			<td class="c-energie <?=get_prod_class($ges[5])?>"><?=ths($ges[5])?></td>
		</tr>
		<tr class="gesamt-taeglich">
			<th class="c-planet">Pr<kbd>o</kbd> <input type="text" class="prod-show-days" name="show_days" id="show_days" value="<?=utf8_htmlentities($show_days)?>" tabindex="<?=htmlentities($tabindex++)?>" accesskey="o" onchange="recalc_perday();" onclick="recalc_perday();" onkeyup="recalc_perday();" />&nbsp;Tage</th>
			<td class="c-carbon <?=get_prod_class($show_day_prod[0])?>" id="taeglich-carbon"><?=ths($show_day_prod[0])?></td>
			<td class="c-aluminium <?=get_prod_class($show_day_prod[1])?>" id="taeglich-aluminium"><?=ths($show_day_prod[1])?></td>
			<td class="c-wolfram <?=get_prod_class($show_day_prod[2])?>" id="taeglich-wolfram"><?=ths($show_day_prod[2])?></td>
			<td class="c-radium <?=get_prod_class($show_day_prod[3])?>" id="taeglich-radium"><?=ths($show_day_prod[3])?></td>
			<td class="c-tritium <?=get_prod_class($show_day_prod[4])?>" id="taeglich-tritium"><?=ths($show_day_prod[4])?></td>
			<td class="c-gesamt <?=get_prod_class($show_day_prod[5])?>" id="taeglich-gesamt"><?=ths($show_day_prod[5])?></td>
			<td class="c-energie"></td>
		</tr>
	</tfoot>
</table>
<script type="text/javascript">
// <![CDATA[
	function recalc_perday()
	{
		var show_days = parseFloat(document.getElementById('show_days').value);

		var carbon,aluminium,wolfram,radium,tritium,gesamt;
		if(isNaN(show_days))
		{
			carbon = 0;
			aluminium = 0;
			wolfram = 0;
			radium = 0;
			tritium = 0;
			gesamt = 0;
		}
		else
		{
			carbon = <?=floor($day_prod[0])?>*show_days;
			aluminium = <?=floor($day_prod[1])?>*show_days;
			wolfram = <?=floor($day_prod[2])?>*show_days;
			radium = <?=floor($day_prod[3])?>*show_days;
			tritium = <?=floor($day_prod[4])?>*show_days;
			gesamt = carbon+aluminium+wolfram+radium+tritium;
		}

		document.getElementById('taeglich-carbon').firstChild.data = ths(carbon);
		document.getElementById('taeglich-aluminium').firstChild.data = ths(aluminium);
		document.getElementById('taeglich-wolfram').firstChild.data = ths(wolfram);
		document.getElementById('taeglich-radium').firstChild.data = ths(radium);
		document.getElementById('taeglich-tritium').firstChild.data = ths(tritium);
		document.getElementById('taeglich-gesamt').firstChild.data = ths(gesamt);

		var carbon_class,aluminium_class,wolfram_class,radium_class,tritium_class;

		if(carbon > 0) carbon_class = 'positiv';
		else if(carbon < 0) carbon_class = 'negativ';
		else carbon_class = 'null';

		if(aluminium > 0) aluminium_class = 'positiv';
		else if(aluminium < 0) aluminium_class = 'negativ';
		else aluminium_class = 'null';

		if(wolfram > 0) wolfram_class = 'positiv';
		else if(wolfram < 0) wolfram_class = 'negativ';
		else wolfram_class = 'null';

		if(radium > 0) radium_class = 'positiv';
		else if(radium < 0) radium_class = 'negativ';
		else radium_class = 'null';

		if(tritium > 0) tritium_class = 'positiv';
		else if(tritium < 0) tritium_class = 'negativ';
		else tritium_class = 'null';
		
		if(gesamt > 0) gesamt_class = 'positiv';
		else if(gesamt < 0) gesamt_class = 'negativ';
		else gesamt_class = 'null';

		document.getElementById('taeglich-carbon').className = 'c-carbon '+carbon_class;
		document.getElementById('taeglich-aluminium').className = 'c-aluminium '+aluminium_class;
		document.getElementById('taeglich-wolfram').className = 'c-wolfram '+wolfram_class;
		document.getElementById('taeglich-radium').className = 'c-radium '+radium_class;
		document.getElementById('taeglich-tritium').className = 'c-tritium '+tritium_class;
		document.getElementById('taeglich-gesamt').className = 'c-gesamt '+gesamt_class;
	}
// ]]>
</script>
<h3 id="ausgegebene-rohstoffe">Ausgegebene Rohstoffe</h3>
<dl class="punkte">
	<dt class="c-carbon">Carbon</dt>
	<dd class="c-carbon"><?=ths($user_array['punkte'][7])?></dd>

	<dt class="c-eisenerz">Aluminium</dt>
	<dd class="c-eisenerz"><?=ths($user_array['punkte'][8])?></dd>

	<dt class="c-wolfram">Wolfram</dt>
	<dd class="c-wolfram"><?=ths($user_array['punkte'][9])?></dd>

	<dt class="c-radium">Radium</dt>
	<dd class="c-radium"><?=ths($user_array['punkte'][10])?></dd>

	<dt class="c-tritium">Tritium</dt>
	<dd class="c-tritium"><?=ths($user_array['punkte'][11])?></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($user_array['punkte'][7]+$user_array['punkte'][8]+$user_array['punkte'][9]+$user_array['punkte'][10]+$user_array['punkte'][11])?></dd>
</dl>
<?php
			break;
		case 'roboter':
?>
<h3 id="roboterzahlen">Roboterzahlen</h3>
<table class="imperium-tabelle imperium-roboterzahlen">
	<thead>
		<tr>
			<th class="c-planet">Planet</th>
<?php
			foreach($items['roboter'] as $id=>$roboter)
			{
?>
			<td class="c-<?=utf8_htmlentities($id)?>"><?=utf8_htmlentities($roboter['name'])?></td>
<?php
			}
?>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array();
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
?>
		<tr>
			<th class="c-planet" title="<?=utf8_htmlentities($user_array['planets'][$planet]['name'])?>"><?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?></th>
<?php
				foreach($items['roboter'] as $id=>$roboter)
				{
					$anzahl = 0;
					if(isset($user_array['planets'][$planet]['roboter'][$id]))
						$anzahl = $user_array['planets'][$planet]['roboter'][$id];
					if(!isset($ges[$id]))
						$ges[$id] = 0;
					$ges[$id] += $anzahl;
?>
			<td class="c-<?=utf8_htmlentities($id)?>"><?=utf8_htmlentities($anzahl)?></td>
<?php
				}
?>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot>
		<tr>
			<th>Gesamt</th>
<?php
			foreach($items['roboter'] as $id=>$roboter)
			{
?>
			<td class="c-<?=utf8_htmlentities($id)?>"><?=utf8_htmlentities($ges[$id])?></td>
<?php
			}
?>
		</tr>
	</tfoot>
</table>
<?php
			$robotech = 0;
			if(isset($user_array['forschung']['F2']))
				$robotech = $user_array['forschung']['F2'];
			
?>
<h3 id="roboter-auswirkungsgrade">Roboter-Auswirkungsgrade</h3>
<dl class="imperium-roboter-auswirkungsgrade">
	<dt class="c-bauroboter">Bauroboter</dt>
	<dd class="c-bauroboter"><?=str_replace('.', ',', $robotech*0.25)?>&thinsp;<abbr title="Prozent">%</abbr></dd>
	
	<dt class="c-minenroboter">Minenroboter</dt>
	<dd class="c-minenroboter"><?=str_replace('.', ',', $robotech*0.0625)?>&thinsp;<abbr title="Prozent">%</abbr></dd>
</dl>
<?php
			break;
		case 'flotte':
?>
<h3 id="stationierte-flotten">Stationierte Flotten</h3>
<table class="imperium-tabelle imperium-stationierte-flotten">
	<thead>
		<tr>
			<th class="c-einheit">Einheit</th>
<?php
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
?>
			<th title="<?=utf8_htmlentities($user_array['planets'][$planet]['name'])?>"><?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?></th>
<?php
			}
?>
			<th class="c-gesamt">Gesamt</th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges_ges = 0;
			$ges = array();
			$einheiten = array_merge($items['schiffe'], $items['verteidigung']);
			foreach($einheiten as $id=>$einheit)
			{
				$this_ges = 0;
?>
		<tr>
			<th class="c-einheit"><?=utf8_htmlentities($einheit['name'])?></th>
<?php
				foreach($planets as $i=>$planet)
				{
					$anzahl = 0;
					if(isset($user_array['planets'][$planet]['ids'][$id]))
						$anzahl = $user_array['planets'][$planet]['ids'][$id];
					if(!isset($ges[$i]))
						$ges[$i] = 0;
					$ges_ges += $anzahl;
					$this_ges += $anzahl;
?>
			<td><?=utf8_htmlentities($anzahl)?></td>
<?php
				}
?>
			<td class="c-gesamt"><?=utf8_htmlentities($this_ges)?></td>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot>
		<tr>
			<th class="c-einheit">Gesamt</th>
<?php
			foreach($planets as $i=>$planet)
			{
?>
			<td><?=utf8_htmlentities($ges[$i])?></td>
<?php
			}
?>
			<td class="c-gesamt"><?=utf8_htmlentities($ges_ges)?></td>
		</tr>
	</tfoot>
</table>
<?php
	}
	
	login_gui::html_foot();
?>