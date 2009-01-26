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
	 * Imperiumsübersicht, zeigt den Status aller eigenen Planeten an.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('include.php');

	$active_planet = $me->getActivePlanet();

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

	$gui->init();
?>
<h2>Imperium</h2>
<ul class="imperium-modi tabs">
	<li class="c-rohstoffe<?=($action == 'ress') ? ' active' : ''?>"><a href="imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=($action == 'ress') ? '' : ' tabindex="'.htmlspecialchars($tabindex++).'"'?><?=L::accesskeyAttr(_("Rohstoffe&[login/imperium.php|1]"))?>><?=L::h(_("Rohstoffe&[login/imperium.php|1]"))?></a></li>
	<li class="c-roboter<?=($action == 'roboter') ? ' active' : ''?>"><a href="imperium.php?action=roboter&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=($action == 'roboter') ? '' : ' tabindex="'.htmlspecialchars($tabindex++).'"'?><?=L::accesskeyAttr(_("Roboter&[login/imperium.php|1]"))?>><?=L::h(_("Roboter&[login/imperium.php|1]"))?></a></li>
	<li class="c-flotte<?=($action == 'flotte') ? ' active' : ''?>"><a href="imperium.php?action=flotte&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=($action == 'flotten') ? '' : ' tabindex="'.htmlspecialchars($tabindex++).'"'?><?=L::accesskeyAttr(_("Flotten&[login/imperium.php|1]"))?>><?=L::h(_("Flotten&[login/imperium.php|1]"))?></a></li>
</ul>
<?php
	switch($action)
	{
		case 'ress':
?>
<h3 id="rohstoffvorraete" class="strong"><?=L::h(_("Rohstoffvorräte"))?></h3>
<table class="imperium-tabelle imperium-rohstoffvorraete">
	<thead>
		<tr>
			<th class="c-planet separator-right"><?=L::h(_("Planet"))?></th>
			<th class="c-carbon"><?=L::h(_("[ress_0]"))?></th>
			<th class="c-aluminium"><?=L::h(_("[ress_1]"))?></th>
			<th class="c-wolfram"><?=L::h(_("[ress_2]"))?></th>
			<th class="c-radium"><?=L::h(_("[ress_3]"))?></th>
			<th class="c-tritium"><?=L::h(_("[ress_4]"))?></th>
			<th class="c-gesamt"><?=L::h(_("Gesamt"))?></th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array(0, 0, 0, 0, 0, 0);
			$planets = $me->getPlanetsList();
			foreach($planets as $planet)
			{
				$me->setActivePlanet($planet);
				define_url_suffix();
				$ress = $me->getRess();
				$ges[0] += $ress[0];
				$ges[1] += $ress[1];
				$ges[2] += $ress[2];
				$ges[3] += $ress[3];
				$ges[4] += $ress[4];
				$this_ges = $ress[0]+$ress[1]+$ress[2]+$ress[3]+$ress[4];
				$ges[5] += $this_ges;
?>
		<tr<?=($planet == $active_planet) ? ' class="active"' : ''?>>
			<th class="c-planet separator-right" title="<?=htmlspecialchars($me->planetName())?>"><a href="imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=htmlspecialchars($me->getPosFormatted())?></a></th>
			<td class="c-carbon number"><?=F::ths($ress[0])?></td>
			<td class="c-aluminium number"><?=F::ths($ress[1])?></td>
			<td class="c-wolfram number"><?=F::ths($ress[2])?></td>
			<td class="c-radium number"><?=F::ths($ress[3])?></td>
			<td class="c-tritium number"><?=F::ths($ress[4])?></td>
			<td class="c-gesamt number"><?=F::ths($this_ges)?></td>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot class="gesamt">
		<tr>
			<th class="c-planet separator-right"><?=L::h(_("Gesamt"))?></th>
			<td class="c-carbon number"><?=F::ths($ges[0])?></td>
			<td class="c-aluminium number"><?=F::ths($ges[1])?></td>
			<td class="c-wolfram number"><?=F::ths($ges[2])?></td>
			<td class="c-radium number"><?=F::ths($ges[3])?></td>
			<td class="c-tritium number"><?=F::ths($ges[4])?></td>
			<td class="c-gesamt number"><?=F::ths($ges[5])?></td>
		</tr>
	</tfoot>
</table>
<h3 id="rohstoffproduktion" class="strong"><?=L::h(_("Rohstoffproduktion pro Stunde"))?></h3>
<table class="imperium-tabelle imperium-rohstoffproduktion">
	<thead>
		<tr>
			<th class="c-planet separator-right"><?=L::h(_("Planet"))?></th>
			<th class="c-carbon"><?=L::h(_("[ress_0]"))?></th>
			<th class="c-aluminium"><?=L::h(_("[ress_1]"))?></th>
			<th class="c-wolfram"><?=L::h(_("[ress_2]"))?></th>
			<th class="c-radium"><?=L::h(_("[ress_3]"))?></th>
			<th class="c-tritium"><?=L::h(_("[ress_4]"))?></th>
			<th class="c-gesamt"><?=L::h(_("Gesamt"))?></th>
			<th class="c-energie"><?=L::h(_("[ress_5]"))?></th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array(0, 0, 0, 0, 0, 0, 0);
			foreach($planets as $planet)
			{
				$me->setActivePlanet($planet);
				$this_prod = $me->getProduction();

				$ges[0] += $this_prod[0];
				$ges[1] += $this_prod[1];
				$ges[2] += $this_prod[2];
				$ges[3] += $this_prod[3];
				$ges[4] += $this_prod[4];
				$ges[5] += $this_prod[5];
				$this_ges = $this_prod[0]+$this_prod[1]+$this_prod[2]+$this_prod[3]+$this_prod[4];
				$ges[6] += $this_ges;

				define_url_suffix();
?>
		<tr<?=($planet == $active_planet) ? ' class="active"' : ''?>>
			<th class="c-planet separator-right" title="<?=htmlspecialchars($me->planetName())?>"><a href="imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=htmlspecialchars($me->getPosFormatted())?></a></th>
			<td class="c-carbon number <?=get_prod_class($this_prod[0])?>"><?=F::ths($this_prod[0])?></td>
			<td class="c-aluminium number <?=get_prod_class($this_prod[1])?>"><?=F::ths($this_prod[1])?></td>
			<td class="c-wolfram number <?=get_prod_class($this_prod[2])?>"><?=F::ths($this_prod[2])?></td>
			<td class="c-radium number <?=get_prod_class($this_prod[3])?>"><?=F::ths($this_prod[3])?></td>
			<td class="c-tritium number <?=get_prod_class($this_prod[4])?>"><?=F::ths($this_prod[4])?></td>
			<td class="c-gesamt number <?=get_prod_class($this_ges)?>"><?=F::ths($this_ges)?></td>
			<td class="c-energie number <?=get_prod_class($this_prod[5])?>"><?=F::ths($this_prod[5])?></td>
		</tr>
<?php
			}

			$day_prod = array($ges[0]*24, $ges[1]*24, $ges[2]*24, $ges[3]*24, $ges[4]*24);
			$show_day_prod = $day_prod;
			$show_days = $me->checkSetting('prod_show_days');
			$show_day_prod[0] *= $show_days;
			$show_day_prod[1] *= $show_days;
			$show_day_prod[2] *= $show_days;
			$show_day_prod[3] *= $show_days;
			$show_day_prod[4] *= $show_days;
			$show_day_prod[5] = array_sum($show_day_prod);
?>
	</tbody>
	<tfoot class="gesamt">
		<tr class="gesamt-stuendlich">
			<th class="c-planet separator-right"><?=L::h(_("Gesamt"))?></th>
			<td class="c-carbon number <?=get_prod_class($ges[0])?>"><?=F::ths($ges[0])?></td>
			<td class="c-aluminium number <?=get_prod_class($ges[1])?>"><?=F::ths($ges[1])?></td>
			<td class="c-wolfram number <?=get_prod_class($ges[2])?>"><?=F::ths($ges[2])?></td>
			<td class="c-radium number <?=get_prod_class($ges[3])?>"><?=F::ths($ges[3])?></td>
			<td class="c-tritium number <?=get_prod_class($ges[4])?>"><?=F::ths($ges[4])?></td>
			<td class="c-gesamt number <?=get_prod_class($ges[6])?>"><?=F::ths($ges[6])?></td>
			<td class="c-energie number <?=get_prod_class($ges[5])?>"><?=F::ths($ges[5])?></td>
		</tr>
		<tr class="gesamt-taeglich">
			<th class="c-planet separator-right"><?=sprintf(h(_("Pr&o %s Tage[login/imperium.php|2]")), "<input type=\"text\" class=\"prod-show-days\" name=\"show_days\" id=\"show_days\" value=\"".htmlspecialchars($show_days)."\"".L::accesskeyAttr(_("Pr&o %s Tage[login/imperium.php|2]"))." tabindex=\"".htmlspecialchars($tabindex++)."\" onchange=\"recalc_perday();\" onclick=\"recalc_perday();\" onkeyup=\"recalc_perday();\" />")?></th>
			<td class="c-carbon number <?=get_prod_class($show_day_prod[0])?>" id="taeglich-carbon"><?=F::ths($show_day_prod[0])?></td>
			<td class="c-aluminium number <?=get_prod_class($show_day_prod[1])?>" id="taeglich-aluminium"><?=F::ths($show_day_prod[1])?></td>
			<td class="c-wolfram number <?=get_prod_class($show_day_prod[2])?>" id="taeglich-wolfram"><?=F::ths($show_day_prod[2])?></td>
			<td class="c-radium number <?=get_prod_class($show_day_prod[3])?>" id="taeglich-radium"><?=F::ths($show_day_prod[3])?></td>
			<td class="c-tritium number <?=get_prod_class($show_day_prod[4])?>" id="taeglich-tritium"><?=F::ths($show_day_prod[4])?></td>
			<td class="c-gesamt number <?=get_prod_class($show_day_prod[5])?>" id="taeglich-gesamt"><?=F::ths($show_day_prod[5])?></td>
			<td class="c-energie number"></td>
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

		document.getElementById('taeglich-carbon').firstChild.data = F::ths(carbon);
		document.getElementById('taeglich-aluminium').firstChild.data = F::ths(aluminium);
		document.getElementById('taeglich-wolfram').firstChild.data = F::ths(wolfram);
		document.getElementById('taeglich-radium').firstChild.data = F::ths(radium);
		document.getElementById('taeglich-tritium').firstChild.data = F::ths(tritium);
		document.getElementById('taeglich-gesamt').firstChild.data = F::ths(gesamt);

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
<h3 id="ausgegebene-rohstoffe" class="strong">Ausgegebene Rohstoffe</h3>
<dl class="punkte">
	<dt class="c-carbon"><?=L::h(_("[ress_0]"))?></dt>
	<dd class="c-carbon"><?=F::ths($me->getSpentRess(0))?></dd>

	<dt class="c-eisenerz"><?=L::h(_("[ress_1]"))?></dt>
	<dd class="c-eisenerz"><?=F::ths($me->getSpentRess(1))?></dd>

	<dt class="c-wolfram"><?=L::h(_("[ress_2]"))?></dt>
	<dd class="c-wolfram"><?=F::ths($me->getSpentRess(2))?></dd>

	<dt class="c-radium"><?=L::h(_("[ress_3]"))?></dt>
	<dd class="c-radium"><?=F::ths($me->getSpentRess(3))?></dd>

	<dt class="c-tritium"><?=L::h(_("[ress_4]"))?></dt>
	<dd class="c-tritium"><?=F::ths($me->getSpentRess(4))?></dd>

	<dt class="c-gesamt"><?=L::h(_("Gesamt"))?></dt>
	<dd class="c-gesamt"><?=F::ths($me->getSpentRess())?></dd>
</dl>
<?php
			break;
		case 'roboter':
?>
<h3 id="roboterzahlen" class="strong"><?=L::h(_("Roboterzahlen"))?></h3>
<table class="imperium-tabelle imperium-roboterzahlen">
	<thead>
		<tr>
			<th class="c-planet separator-right"><?=L::h(_("Planet"))?></th>
<?php
			$roboter = $me->getItemsList('roboter');
			foreach($roboter as $id)
			{
?>
			<th class="c-<?=htmlspecialchars($id)?>"><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id).'&'.global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Genauere Informationen anzeigen"))?>"><?=L::h(_("[item_".$id."]"))?></a></th>
<?php
			}
?>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array();
			$ges_max = array();
			$use_max_limit = !file_exists(global_setting('DB_NO_STRICT_ROB_LIMITS'));
			$planets = $me->getPlanetsList();
			foreach($planets as $planet)
			{
				$me->setActivePlanet($planet);
				$max_rob_limit = floor($me->getBasicFields()/2);

				define_url_suffix();
?>
		<tr<?=($planet==$active_planet) ? ' class="active"' : ''?>>
			<th class="c-planet separator-right" title="<?=htmlspecialchars($me->planetName())?>"><a href="imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>&amp;action=roboter"><?=htmlspecialchars($me->getPosFormatted())?></a></th>
<?php
				foreach($roboter as $id)
				{
					$count = $me->getItemLevel($id, 'roboter');
					if(!isset($ges[$id])) $ges[$id] = 0;
					$ges[$id] += $count;
					switch($id)
					{
						case 'R01': $max = $max_rob_limit; break;
						case 'R02': $max = ($use_max_limit ? min($max_rob_limit, $me->getItemLevel('B0')) : $me->getItemLevel('B0')); break;
						case 'R03': $max = ($use_max_limit ? min($max_rob_limit, $me->getItemLevel('B1')) : $me->getItemLevel('B1')); break;
						case 'R04': $max = ($use_max_limit ? min($max_rob_limit, $me->getItemLevel('B2')) : $me->getItemLevel('B2')); break;
						case 'R05': $max = ($use_max_limit ? min($max_rob_limit, $me->getItemLevel('B3')) : $me->getItemLevel('B3')); break;
						case 'R06': $max = ($use_max_limit ? min($max_rob_limit, $me->getItemLevel('B4')) : $me->getItemLevel('B4')); break;
					}
					if(!isset($ges_max[$id])) $ges_max[$id] = 0;
					$ges_max[$id] += $max;
?>
			<td class="c-<?=htmlspecialchars($id)?> number"><?=F::ths($count)?> <span class="max"><?=F::ths($max)?></span></td>
<?php
				}
?>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot class="gesamt">
		<tr>
			<th class="c-planet separator-right"><?=L::h(_("Gesamt"))?></th>
<?php
			foreach($roboter as $id)
			{
?>
			<td class="c-<?=htmlspecialchars($id)?> number"><?=F::ths($ges[$id])?> <span class="max"><?=F::ths($ges_max[$id])?></span></td>
<?php
			}
?>
		</tr>
	</tfoot>
</table>
<h3 id="roboter-auswirkungsgrade" class="strong"><?=L::h(_("Roboter-Auswirkungsgrade"))?></h3>
<dl class="imperium-roboter-auswirkungsgrade">
	<dt class="c-bauroboter"><?=L::h(_("Bauroboter"))?></dt>
	<dd class="c-bauroboter"><?=sprintf(h(_("%s %% pro Roboter")), F::ths($me->getItemLevel('F2', 'forschung')*0.125, null, 3))?></dd>

	<dt class="c-minenroboter"><?=L::h(_("Minenroboter"))?></dt>
<?php
			if(file_exists(global_setting("DB_USE_OLD_ROBTECH")))
			{
?>
	<dd class="c-minenroboter"><?=sprintf(h(_("%s %% pro Roboter")), F::ths($me->getItemLevel('F2', 'forschung')*0.03125, null, 3))?></dd>
<?php
			}
			else
			{
?>
	<dd class="c-minenroboter"><?=sprintf(h(_("%s Prozentpunkte pro Roboter")), F::ths(sqrt($me->getItemLevel("F2", "forschung"))/2.5, null, 3))?></dd>
<?php
			}
?>
</dl>
<?php
			break;
		case 'flotte':
?>
<h3 id="stationierte-flotten" class="strong"><?=L::h(_("Stationierte Flotten"))?></h3>
<table class="imperium-tabelle imperium-stationierte-flotten">
	<thead>
		<tr>
			<th class="c-einheit separator-right"><?=L::h(_("Einheit"))?></th>
<?php
			$planets = $me->getPlanetsList();
			foreach($planets as $planet)
			{
				$me->setActivePlanet($planet);
				define_url_suffix();
?>
			<th<?=($planet==$active_planet) ? ' class="active"' : ''?> title="<?=htmlspecialchars($me->planetName())?>"><a href="imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>&amp;action=flotte"><?=htmlspecialchars($me->getPosFormatted())?></a></th>
<?php
			}
?>
			<th class="c-gesamt"><?=L::h(_("Gesamt"))?></th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges_ges = 0;
			$ges = array();
			$einheiten = array_merge($me->getItemsList('schiffe'), $me->getItemsList('verteidigung'));
			foreach($einheiten as $id)
			{
				$this_ges = 0;
?>
		<tr>
			<th class="c-einheit separator-right"><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id).'&'.global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Genauere Informationen anzeigen"))?>"><?=L::h(_("[item_".$id."]"))?></a></th>
<?php
				foreach($planets as $i=>$planet)
				{
					$me->setActivePlanet($planet);
					$anzahl = $me->getItemLevel($id);
					if(!isset($ges[$i])) $ges[$i] = 0;
					$ges[$i] += $anzahl;
					$ges_ges += $anzahl;
					$this_ges += $anzahl;
?>
			<td class="number<?=($planet==$active_planet) ? ' active' : ''?>"><?=F::ths($anzahl)?></td>
<?php
				}
?>
			<td class="c-gesamt number"><?=htmlspecialchars($this_ges)?></td>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot class="gesamt">
		<tr>
			<th class="c-einheit separator-right"><?=L::h(_("Gesamt"))?></th>
<?php
			foreach($planets as $i=>$planet)
			{
?>
			<td class="number<?=($planet==$active_planet) ? ' active' : ''?>"><?=F::ths($ges[$i])?></td>
<?php
			}
?>
			<td class="c-gesamt separator-left number"><?=htmlspecialchars($ges_ges)?></td>
		</tr>
	</tfoot>
</table>
<h3 id="forschungsverbesserungen" class="strong"><?=L::h(_("Forschungsverbesserungen"))?></h3>
<dl class="imperium-schiffe-auswirkungsgrade">
	<dt class="c-antriebe"><?=L::h(_("Antriebe"))?></dt>
	<dd class="c-antriebe"><?=F::ths((pow(1.025, $me->getItemLevel('F6', 'forschung'))*pow(1.05, $me->getItemLevel('F7', 'forschung'))*pow(1.5, $me->getItemLevel('F8', 'forschung'))-1)*100, null, 3)?>&thinsp;<abbr title="Prozent">%</abbr></dd>

	<dt class="c-waffen"><?=L::h(_("Waffen"))?></dt>
	<dd class="c-waffen"><?=F::ths((pow(1.05, $me->getItemLevel('F4', 'forschung'))-1)*100, null, 3)?>&thinsp;<abbr title="Prozent">%</abbr></dd>

	<dt class="c-schilde"><?=L::h(_("Schilde"))?></dt>
	<dd class="c-schilde"><?=F::ths((pow(1.05, $me->getItemLevel('F5', 'forschung'))-1)*100, null, 3)?>&thinsp;<abbr title="Prozent">%</abbr></dd>

	<dt class="c-schadensminderung-durch-schilde"><?=L::h(_("Schadensminderung durch Schilde"))?></dt>
	<dd class="c-schadensminderung-durch-schilde"><?=F::ths((1-pow(.95, $me->getItemLevel('F10', 'forschung')))*100, null, 3)?>&thinsp;<abbr title="Prozent">%</abbr></dd>

	<dt class="c-laderaumvergroesserung"><?=L::h(_("Laderaumvergrößerung"))?></dt>
	<dd class="c-laderaumvergroesserung"><?=F::ths((pow(1.2, $me->getItemLevel('F11', 'forschung'))-1)*100, null, 3)?>&thinsp;<abbr title="Prozent">%</abbr></dd>
</dl>
<?php
	}

	$me->setActivePlanet($active_planet);
	define_url_suffix();

	$gui->end();
?>
