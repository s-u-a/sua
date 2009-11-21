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
	/**
	 * Produktion einsehen und beeinflussen.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	use \sua\F;
	use \sua\L;

	require('include.php');

	if(isset($_POST['prod']) && is_array($_POST['prod']) && count($_POST['prod']) > 0)
	{
		$changed = false;
		foreach($_POST['prod'] as $id=>$prod)
			$USER->setProductionFactor($id, $prod);

		if(isset($_POST['show_days']))
			$USER->setSetting('prod_show_days', $_POST['show_days']);
	}

	$GUI->init();
?>
<h2><?=L::h(_("Rohstoffproduktion pro Stunde"))?></h2>
<form action="rohstoffe.php?<?=htmlspecialchars($GUI->getOption("url_suffix"))?>" method="post">
	<table class="ress-prod">
		<thead>
			<tr>
				<th class="c-gebaeude"><?=L::h(_("Gebäude"))?></th>
				<th class="c-carbon"><?=L::h(_("[ress_0]"))?></th>
				<th class="c-aluminium"><?=L::h(_("[ress_1]"))?></th>
				<th class="c-wolfram"><?=L::h(_("[ress_2]"))?></th>
				<th class="c-radium"><?=L::h(_("[ress_3]"))?></th>
				<th class="c-tritium"><?=L::h(_("[ress_4]"))?></th>
				<th class="c-energie"><?=L::h(_("[ress_5]"))?></th>
				<th class="c-produktion"><?=L::h(_("Prod&uktion[login/rohstoffe.php|1]"))?></th>
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

	$ges_prod = $PLANET->getProduction();
	$gebaeude = $USER->getItemsList('gebaeude');
	$energie_prod = 0;
	foreach($gebaeude as $id)
	{
		$item_info = $USER->getItemInfo($id, 'gebaeude', array("level", "has_prod", "prod", "name"), $PLANET);

		if($item_info['level'] <= 0 || !$item_info['has_prod'])
			continue; # Es wird nichts produziert, also nicht anzeigen
		$prod = $PLANET->checkProductionFactor($id);

		if($item_info["prod"][5] > 0)
			$energie_prod += $item_info["prod"][5];
?>
			<tr>
				<th class="c-gebaeude"><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars($GUI->getOption("url_suffix"))?>" title="<?=L::h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item_info['name'])?></a> <span class="stufe"><?=h(sprintf(_("(Stufe %s)"), $item_info['level']))?></span></th>
				<td class="c-carbon number <?=get_prod_class($item_info['prod'][0])?>"><?=F::ths($item_info['prod'][0]*$ges_prod[6])?></td>
				<td class="c-aluminium number <?=get_prod_class($item_info['prod'][1])?>"><?=F::ths($item_info['prod'][1]*$ges_prod[6])?></td>
				<td class="c-wolfram number <?=get_prod_class($item_info['prod'][2])?>"><?=F::ths($item_info['prod'][2]*$ges_prod[6])?></td>
				<td class="c-radium number <?=get_prod_class($item_info['prod'][3])?>"><?=F::ths($item_info['prod'][3]*$ges_prod[6])?></td>
				<td class="c-tritium number <?=get_prod_class($item_info['prod'][4])?>"><?=F::ths($item_info['prod'][4]*$ges_prod[6])?></td>
				<td class="c-energie number <?=get_prod_class($item_info['prod'][5])?>"><?=F::ths($item_info['prod'][5])?></td>
				<td class="c-produktion">
					<select name="prod[<?=htmlspecialchars($id)?>]" onchange="this.form.submit();" tabindex="<?=$tabindex?>"<?=($tabindex == 1) ? L::accesskeyAttr(_("Prod&uktion[login/rohstoffe.php|1]")) : ''?>>
<?php
		for($i=1,$h=100; $i>=0; $i-=.05,$h-=5)
		{
			$i = round($i, 4);
?>
						<option value="<?=htmlspecialchars($i)?>"<?=($prod == $i) ? ' selected="selected"' : ''?>><?=htmlspecialchars($h)?>&thinsp;%</option>
<?php
			$diff = $i-$prod;
			if($diff >= 0.0001 && $diff <= 0.0499)
			{
?>
						<option value="<?=htmlspecialchars($prod)?>" selected="selected"><?=L::h(sprintf(_("%s %%"), ths($prod*100)))?></option>
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
		<tfoot class="gesamt">
			<tr class="c-stunde">
				<th><?=L::h(_("Gesamt pro Stunde"))?></th>
				<td class="c-carbon number <?=get_prod_class($ges_prod[0])?>"><?=F::ths($ges_prod[0])?></td>
				<td class="c-aluminium number <?=get_prod_class($ges_prod[1])?>"><?=F::ths($ges_prod[1])?></td>
				<td class="c-wolfram number <?=get_prod_class($ges_prod[2])?>"><?=F::ths($ges_prod[2])?></td>
				<td class="c-radium number <?=get_prod_class($ges_prod[3])?>"><?=F::ths($ges_prod[3])?></td>
				<td class="c-tritium number <?=get_prod_class($ges_prod[4])?>"><?=F::ths($ges_prod[4])?></td>
				<td class="c-energie number <?=get_prod_class($ges_prod[5])?>"><?=F::ths($ges_prod[5])?></td>
				<td class="c-produktion number"></td>
			</tr>
			<tr class="c-tag">
<?php
	$day_prod = array($ges_prod[0]*24, $ges_prod[1]*24, $ges_prod[2]*24, $ges_prod[3]*24, $ges_prod[4]*24);
	$show_day_prod = $day_prod;
	$show_days = $USER->checkSetting('prod_show_days');
	$show_day_prod[0] *= $show_days;
	$show_day_prod[1] *= $show_days;
	$show_day_prod[2] *= $show_days;
	$show_day_prod[3] *= $show_days;
	$show_day_prod[4] *= $show_days;
?>
				<th><?=sprintf(L::h(_("Gesamt pr&o %s Tage[login/rohstoffe.php|1]")), "<input type=\"text\" class=\"prod-show-days\" name=\"show_days\" id=\"show_days\" value=\"".htmlspecialchars($show_days)."\" tabindex=\"".$tabindex."\"".L::accesskeyAttr(_("Gesamt pr&o %s Tage[login/rohstoffe.php|1]"))." onchange=\"recalc_perday();\" onclick=\"recalc_perday();\" onkeyup=\"recalc_perday();\" />")?></th>
				<td class="c-carbon number <?=get_prod_class($show_day_prod[0])?>" id="taeglich-carbon"><?=F::ths($show_day_prod[0])?></td>
				<td class="c-aluminium number <?=get_prod_class($show_day_prod[1])?>" id="taeglich-aluminium"><?=F::ths($show_day_prod[1])?></td>
				<td class="c-wolfram number <?=get_prod_class($show_day_prod[2])?>" id="taeglich-wolfram"><?=F::ths($show_day_prod[2])?></td>
				<td class="c-radium number <?=get_prod_class($show_day_prod[3])?>" id="taeglich-radium"><?=F::ths($show_day_prod[3])?></td>
				<td class="c-tritium number <?=get_prod_class($show_day_prod[4])?>" id="taeglich-tritium"><?=F::ths($show_day_prod[4])?></td>
				<td class="c-speichern" colspan="2" class="button"><button type="submit" tabindex="<?=$tabindex+1?>"<?=L::accesskeyAttr(_("Speicher&n[login/rohstoffe.php|1]"))?>><?=L::h(_("Speicher&n[login/rohstoffe.php|1]"))?></button></td>
			</tr>
<?php
	$limit = $PLANET->getProductionLimit();
	$ress = $PLANET->getRess();
?>
			<tr class="c-speicher">
				<th>Speicher</th>
				<td class="c-carbon number<?=$limit[0] < $ress[0] ? " voll" : ""?>"><?=F::ths($limit[0])?></td>
				<td class="c-aluminium number<?=$limit[1] < $ress[1] ? " voll" : ""?>"><?=F::ths($limit[1])?></td>
				<td class="c-wolfram number<?=$limit[2] < $ress[2] ? " voll" : ""?>"><?=F::ths($limit[2])?></td>
				<td class="c-radium number<?=$limit[3] < $ress[3] ? " voll" : ""?>"><?=F::ths($limit[3])?></td>
				<td class="c-tritium number<?=$limit[4] < $ress[4] ? " voll" : ""?>"><?=F::ths($limit[4])?></td>
				<td class="c-energie number<?=$limit[5] < $energie_prod ? " voll" : ""?>"><?=F::ths($limit[5])?></td>
				<td class="c-produktion empty"></td>
			</tr>
		</tfoot>
	</table>
</form>
<script type="text/javascript">
// <![CDATA[
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

		document.getElementById('taeglich-carbon').firstChild.data = F::ths(carbon);
		document.getElementById('taeglich-aluminium').firstChild.data = F::ths(aluminium);
		document.getElementById('taeglich-wolfram').firstChild.data = F::ths(wolfram);
		document.getElementById('taeglich-radium').firstChild.data = F::ths(radium);
		document.getElementById('taeglich-tritium').firstChild.data = F::ths(tritium);

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
// ]]>
</script>
<?php
	$GUI->end();