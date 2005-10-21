<?php
	require('scripts/include.php');

	if(isset($_POST['prod']) && is_array($_POST['prod']) && count($_POST['prod']) > 0)
	{
		$changed = false;
		foreach($_POST['prod'] as $id=>$prod)
		{
			if(!isset($items['ids'][$id]))
				continue;
			if($prod < 0)
				$prod = 0;
			elseif($prod > 1)
				$prod = 1;

			$this_planet['prod'][$id] = $prod;
			$changed = true;
		}

		if(isset($_POST['show_days']))
		{
			$user_array['prod_show_days'] = $_POST['show_days'];
			$changed = true;
		}

		if($changed && write_user_array()) # Produktion neu berechnen
			$ges_prod = get_ges_prod();
	}

	login_gui::html_head();
?>
<h2>Rohstoffproduktion pro Stunde</h2>
<form action="rohstoffe.php" method="post">
	<table class="ress-prod">
		<thead>
			<tr>
				<th class="c-gebaeude">Gebäude</th>
				<th class="c-carbon">Carbon</th>
				<th class="c-aluminium">Aluminium</th>
				<th class="c-wolfram">Wolfram</th>
				<th class="c-radium">Radium</th>
				<th class="c-tritium">Tritium</th>
				<th class="c-energie">Energie</th>
				<th class="c-produktion">Prod<kbd>u</kbd>ktion</th>
			</tr>

		</thead>
		<tbody>
<?php
	function get_prod_class($prod)
	{
		if($prod > 0)
			return 'positiv';
		elseif($prod < 0)
			return 'negativ';
		else
			return 'null';
	}

	$tabindex = 1;
	$ges_prod = array(0, 0, 0, 0, 0, 0);
	foreach($items['gebaeude'] as $id=>$gebaeude)
	{
		if($gebaeude['prod'][0] == 0 && $gebaeude['prod'][1] == 0 && $gebaeude['prod'][2] == 0 && $gebaeude['prod'][3] == 0 && $gebaeude['prod'][4] == 0 && $gebaeude['prod'][5] == 0)
			continue;

		$level = 0;
		if(isset($this_planet['gebaeude'][$id]))
			$level = $this_planet['gebaeude'][$id];

		if($level == 0)
			continue;

		$prod = 1;
		if(isset($this_planet['prod'][$id]) && $this_planet['prod'][$id] <= 1 && $this_planet['prod'][$id] >= 0)
			$prod = $this_planet['prod'][$id];
		$prod = round($prod, 4);

		# Ausbaustufe des Gebaeudes einberechnen
		$gebaeude['prod'][0] *= pow($level, 2)*$prod;
		$gebaeude['prod'][1] *= pow($level, 2)*$prod;
		$gebaeude['prod'][2] *= pow($level, 2)*$prod;
		$gebaeude['prod'][3] *= pow($level, 2)*$prod;
		$gebaeude['prod'][4] *= pow($level, 2)*$prod;
		$gebaeude['prod'][5] *= pow($level, 2)*$prod;

		# Roboter miteinbrechnen, Faktoren siehe scripts/include.php
		if($gebaeude['prod'][0] > 0)
			$gebaeude['prod'][0] *= $carbon_f;
		if($gebaeude['prod'][1] > 0)
			$gebaeude['prod'][1] *= $aluminium_f;
		if($gebaeude['prod'][2] > 0)
			$gebaeude['prod'][2] *= $wolfram_f;
		if($gebaeude['prod'][3] > 0)
			$gebaeude['prod'][3] *= $radium_f;
		if($gebaeude['prod'][4] > 0)
			$gebaeude['prod'][4] *= $tritium_f;

		# Energietechnik, Faktor siehe scripts/include.php
		if($gebaeude['prod'][5] > 0)
			$gebaeude['prod'][5] *= $energie_f;

		# Energiemangel, siehe scripts/include.php
		if($gebaeude['prod'][5] < 0)
		{
			$gebaeude['prod'][0] *= $energie_mangel;
			$gebaeude['prod'][1] *= $energie_mangel;
			$gebaeude['prod'][2] *= $energie_mangel;
			$gebaeude['prod'][3] *= $energie_mangel;
			$gebaeude['prod'][4] *= $energie_mangel;
		}

		$gebaeude['prod'][0] = round($gebaeude['prod'][0]);
		$gebaeude['prod'][1] = round($gebaeude['prod'][1]);
		$gebaeude['prod'][2] = round($gebaeude['prod'][2]);
		$gebaeude['prod'][3] = round($gebaeude['prod'][3]);
		$gebaeude['prod'][4] = round($gebaeude['prod'][4]);
		$gebaeude['prod'][5] = round($gebaeude['prod'][5]);

		$ges_prod[0] += $gebaeude['prod'][0];
		$ges_prod[1] += $gebaeude['prod'][1];
		$ges_prod[2] += $gebaeude['prod'][2];
		$ges_prod[3] += $gebaeude['prod'][3];
		$ges_prod[4] += $gebaeude['prod'][4];
		$ges_prod[5] += $gebaeude['prod'][5];
?>
			<tr>
				<td class="c-gebaeude"><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($gebaeude['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($level)?>)</span></td>
				<td class="c-carbon <?=get_prod_class($gebaeude['prod'][0])?>"><?=ths($gebaeude['prod'][0])?></td>
				<td class="c-aluminium <?=get_prod_class($gebaeude['prod'][1])?>"><?=ths($gebaeude['prod'][1])?></td>
				<td class="c-wolfram <?=get_prod_class($gebaeude['prod'][2])?>"><?=ths($gebaeude['prod'][2])?></td>
				<td class="c-radium <?=get_prod_class($gebaeude['prod'][3])?>"><?=ths($gebaeude['prod'][3])?></td>
				<td class="c-tritium <?=get_prod_class($gebaeude['prod'][4])?>"><?=ths($gebaeude['prod'][4])?></td>
				<td class="c-energie <?=get_prod_class($gebaeude['prod'][5])?>"><?=ths($gebaeude['prod'][5])?></td>
				<td class="c-produktion">
					<select name="prod[<?=utf8_htmlentities($id)?>]" onchange="this.form.submit();" tabindex="<?=$tabindex?>"<?=($tabindex == 1) ? ' accesskey="u"' : ''?>>
<?php
		for($i=1,$h=100; $i>=0; $i-=.05,$h-=5)
		{
			$i = round($i, 4);
?>
						<option value="<?=htmlentities($i)?>"<?=($prod == $i) ? ' selected="selected"' : ''?>><?=htmlentities($h)?>&thinsp;%</option>
<?php
			$diff = $i-$prod;
			if($diff >= 0.0001 && $diff <= 0.0499)
			{
?>
						<option value="<?=htmlentities($prod)?>" selected="selected"><?=htmlentities(str_replace('.', ',', $prod*100))?>&thinsp;%</option>
<?php
			}
		}
?>
					</select>
				</td>
			</tr>
<?php
		$tabindex++;
	}
?>
		</tbody>
		<tfoot>
			<tr class="c-stunde">
				<th>Gesamt pro Stunde</th>
				<td class="c-carbon <?=get_prod_class($ges_prod[0])?>"><?=ths($ges_prod[0])?></td>
				<td class="c-aluminium <?=get_prod_class($ges_prod[1])?>"><?=ths($ges_prod[1])?></td>
				<td class="c-wolfram <?=get_prod_class($ges_prod[2])?>"><?=ths($ges_prod[2])?></td>
				<td class="c-radium <?=get_prod_class($ges_prod[3])?>"><?=ths($ges_prod[3])?></td>
				<td class="c-tritium <?=get_prod_class($ges_prod[4])?>"><?=ths($ges_prod[4])?></td>
				<td class="c-energie <?=get_prod_class($ges_prod[5])?>"><?=ths($ges_prod[5])?></td>
				<td class="c-produktion"></td>
			</tr>
<?php
	$day_prod = array($ges_prod[0]*24, $ges_prod[1]*24, $ges_prod[2]*24, $ges_prod[3]*24, $ges_prod[4]*24);
?>
			<script type="text/javascript">
				function ths(old_count)
				{
					var minus = false;
					if(old_count < 0)
					{
						old_count *= -1;
						minus = true;
					}
					var count = new String(Math.floor(old_count));
					var new_count = new Array();
					var first_letters = count.length%3;
					if(first_letters == 0)
						first_letters = 3;
					new_count.push(count.substr(0, first_letters));
					var max_i = (count.length-first_letters)/3;
					for(var i=0; i<max_i; i++)
						new_count.push(count.substr(i*3+first_letters, 3));
					new_count = new_count.join("<?=utf8_jsentities(THS_UTF8)?>");
					if(minus)
						new_count = "\u2212"+new_count;
					return new_count;
				}

				function recalc_perday()
				{
					var show_days = parseFloat(document.getElementById('show_days').value);

					var carbon,aluminium,wolfram,radium,tritium;
					if(isNaN(show_days))
					{
						carbon = 0;
						aluminium = 0;
						wolfram = 0;
						radium = 0;
						tritium = 0;
					}
					else
					{
						carbon = <?=floor($day_prod[0])?>*show_days;
						aluminium = <?=floor($day_prod[1])?>*show_days;
						wolfram = <?=floor($day_prod[2])?>*show_days;
						radium = <?=floor($day_prod[3])?>*show_days;
						tritium = <?=floor($day_prod[4])?>*show_days;
					}

					document.getElementById('taeglich-carbon').innerHTML = ths(carbon);
					document.getElementById('taeglich-aluminium').innerHTML = ths(aluminium);
					document.getElementById('taeglich-wolfram').innerHTML = ths(wolfram);
					document.getElementById('taeglich-radium').innerHTML = ths(radium);
					document.getElementById('taeglich-tritium').innerHTML = ths(tritium);

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

					document.getElementById('taeglich-carbon').className = 'c-carbon '+carbon_class;
					document.getElementById('taeglich-aluminium').className = 'c-aluminium '+aluminium_class;
					document.getElementById('taeglich-wolfram').className = 'c-wolfram '+wolfram_class;
					document.getElementById('taeglich-radium').className = 'c-radium '+radium_class;
					document.getElementById('taeglich-tritium').className = 'c-tritium '+tritium_class;
				}
			</script>
			<tr class="c-tag">
<?php
	$show_days = 1;
	if(isset($user_array['prod_show_days']))
		$show_days = $user_array['prod_show_days'];
	$day_prod[0] *= $show_days;
	$day_prod[1] *= $show_days;
	$day_prod[2] *= $show_days;
	$day_prod[3] *= $show_days;
	$day_prod[4] *= $show_days;
?>
				<th>Gesamt pr<kbd>o</kbd> <input type="text" class="prod-show-days" name="show_days" id="show_days" value="<?=utf8_htmlentities($show_days)?>" tabindex="<?=$tabindex?>" accesskey="o" onchange="recalc_perday();" onclick="recalc_perday();" onkeyup="recalc_perday();" />&nbsp;Tage</th>
				<td class="c-carbon <?=get_prod_class($day_prod[0])?>" id="taeglich-carbon"><?=ths($day_prod[0])?></td>
				<td class="c-aluminium <?=get_prod_class($day_prod[1])?>" id="taeglich-aluminium"><?=ths($day_prod[1])?></td>
				<td class="c-wolfram <?=get_prod_class($day_prod[2])?>" id="taeglich-wolfram"><?=ths($day_prod[2])?></td>
				<td class="c-radium <?=get_prod_class($day_prod[3])?>" id="taeglich-radium"><?=ths($day_prod[3])?></td>
				<td class="c-tritium <?=get_prod_class($day_prod[4])?>" id="taeglich-tritium"><?=ths($day_prod[4])?></td>
				<td class="c-speichern" colspan="2"><button type="submit" tabindex="<?=$tabindex+1?>" accesskey="n">Speicher<kbd>n</kbd></button></td>
			</tr>
		</foot>
	</table>
</form>
<?php
	login_gui::html_foot();
?>