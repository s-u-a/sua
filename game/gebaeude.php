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
	 * Gebäude in Auftrag geben.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('include.php');

	$planets = $me->getPlanetsList();
	$active_planet = $me->getActivePlanet();
	$act = array_search($active_planet, $planets);

	# Naechsten nicht bauenden Planeten herausfinden
	$i = $act+1;
	$fastbuild_next = false;
	while(true)
	{
		if($i >= count($planets))
			$i = 0;
		if($planets[$i] == $active_planet)
			break;

		$me->setActivePlanet($planets[$i]);
		$building = $me->checkBuildingThing('gebaeude');
		if(!$building && ($PLANET->getRemainingFields() > 0 || $me->checkSetting('fastbuild_full')))
		{
			$fastbuild_next = $planets[$i];
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

		$me->setActivePlanet($planets[$i]);
		$building = $me->checkBuildingThing('gebaeude');
		if(!$building && ($PLANET->getRemainingFields() > 0 || $me->checkSetting('fastbuild_full')))
		{
			$fastbuild_prev = $planets[$i];
			break;
		}

		$i--;
	}

	$me->setActivePlanet($active_planet);

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

	if(isset($a_id) && $me->permissionToAct())
	{
		$me->buildGebaeude($a_id, $rueckbau);
		if($me->checkBuildingThing("gebaeude"))
		{
			if($me->checkSetting('fastbuild') && $fastbuild_next !== false)
			{
				# Fastbuild

				$_SESSION['last_click_ignore'] = true;
				$url = global_setting("PROTOCOL")."://".$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]."?".preg_replace("/((^|&)planet=)\d+/", "\${1}".$fastbuild_next, global_setting("URL_SUFFIX"));
				header('Location: '.$url, true, 303);
				die('HTTP redirect: <a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a>');
			}
			else
				delete_request();
		}
	}

	if(isset($_GET['cancel']))
	{
		$building = $me->checkBuildingThing('gebaeude');
		if($building && $building[0] == $_GET['cancel'] && $me->removeBuildingThing('gebaeude'))
			delete_request();
	}

	$gui->init();
?>
<h2><?=L::h(_("Gebäude"))?></h2>
<?php
	if(($fastbuild_prev !== false || $fastbuild_next !== false) && $me->permissionToAct())
	{
?>
<ul class="unbeschaeftigte-planeten fast-seek">
<?php
		$active_planet = $me->getActivePlanet();
		if($fastbuild_prev !== false)
		{
			$me->setActivePlanet($fastbuild_prev);
			define_url_suffix();
?>
	<li class="c-prev"><a href="gebaeude.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=sprintf(h(_("Voriger &unbeschäftigter Planet: %s[login/gebaeude.php|1]"), false), htmlspecialchars($me->getActivePlanetFormatted()))?>" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Voriger &unbeschäftigter Planet: %s[login/gebaeude.php|1]"))?> rel="prev"><?=L::h(_("←"))?></a></li>
<?php
		}
		if($fastbuild_next !== false)
		{
			$me->setActivePlanet($fastbuild_next);
			define_url_suffix();
?>
	<li class="c-next"><a href="gebaeude.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=sprintf(h(_("Nächster unbeschäftigter Planet: %s [&Q][login/gebaeude.php|1]"), false), htmlspecialchars($me->getActivePlanetFormatted()))?>" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Nächster unbeschäftigter Planet: %s [&Q][login/gebaeude.php|1]"))?> rel="next"><?=L::h(_("→"))?></a></li>
<?php
		}
		$me->setActivePlanet($active_planet);
		define_url_suffix();
?>
</ul>
<?php
	}

	$gebaeude = $me->getItemsList('gebaeude');
	foreach($gebaeude as $id)
	{
		$geb = $me->getItemInfo($id, 'gebaeude', array("deps-okay", "level", "buildable", "debuildable", "name", "ress", "time", "prod"));
		$building = false;

		if(!$geb['deps-okay'] && $geb['level'] <= 0) # Abhaengigkeiten nicht erfuellt
			continue;
?>
<div class="item gebaeude" id="item-<?=htmlspecialchars($id)?>">
	<h3><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($geb['name'])?></a> <span class="stufe">(<?=sprintf(h(_("Stufe %s")), F::ths($geb['level']))?>)</span></h3>
<?php
		if($me->permissionToAct() && ($geb['buildable'] || $geb['debuildable']) && !($building = $me->checkBuildingThing('gebaeude')) && ($id != 'B8' || !$me->checkBuildingThing('forschung')) && ($id != 'B9' || !$me->checkBuildingThing('roboter')) && ($id != 'B10' || (!$me->checkBuildingThing('schiffe') && !$me->checkBuildingThing('verteidigung'))))
		{
?>
	<ul>
<?php
			if($geb['buildable'])
			{
				$enough_ress = $me->checkRess($geb['ress']);
?>
		<li class="item-ausbau <?=$enough_ress ? 'genug' : 'fehlend'?>"><?=$enough_ress ? '<a href="gebaeude.php?ausbau='.htmlspecialchars(urlencode($id)).'&amp;'.htmlspecialchars(global_setting("URL_SUFFIX")).'" tabindex="'.($tabindex++).'">' : ''?><?=sprintf(h(_("Ausbau auf Stufe %s")), F::ths($geb['level']+1))?><?=$enough_ress ? '</a>' : ''?></li>
<?php
			}
			if($geb['debuildable'])
			{
				$ress = $geb['ress'];
				$ress[0] /= 2;
				$ress[1] /= 2;
				$ress[2] /= 2;
				$ress[3] /= 2;
				$enough_ress = $me->checkRess($ress);
?>
		<li class="item-rueckbau <?=$enough_ress ? 'genug' : 'fehlend'?>"><?=$enough_ress ? '<a href="gebaeude.php?abbau='.htmlspecialchars(urlencode($id)).'&amp;'.htmlspecialchars(global_setting("URL_SUFFIX")).'">' : ''?><?=sprintf(h(_("Rückbau auf Stufe %s")), F::ths($geb['level']-1))?><?=$enough_ress ? '</a>' : ''?></li>
<?php
			}
?>
	</ul>
<?php
		}
		elseif($building && $building[0] == $id)
		{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlspecialchars($building[0])?>"><?=F::formatFTime($building[1], $me)?> <a href="gebaeude.php?cancel=<?=htmlspecialchars(urlencode($building[0]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="abbrechen"><?=L::h(_("Abbrechen"))?></a></div>
<?php
			if(!$me->umode())
			{
?>
	<script type="text/javascript">
		init_countdown('<?=$building[0]?>', <?=$building[1]?>);
	</script>
<?php
			}
		}
?>
	<dl class="lines">
		<dt class="item-kosten"><?=L::h(_("Kosten"))?></dt>
		<dd class="item-kosten">
<?php
		echo F::formatRess($geb['ress'], 3, false, false, false, $me);
?>
		</dd>

		<dt class="item-bauzeit"><?=L::h(_("Bauzeit"))?></dt>
		<dd class="item-bauzeit"><?=F::formatBTime($geb['time'])?></dd>
<?php
		if($me->checkSetting("extended_buildings"))
		{
			$geb_next = $me->getItemInfo($id, "gebaeude", array("prod"), null, $geb["level"]+1);
?>

		<dt class="item-produktion-aktuell"><?=L::h(_("Produktion aktuell"))?></dt>
		<dd class="item-produktion-aktuell">
<?php
			echo F::formatRess($geb["prod"], 3, true, true);
?>
		</dd>

		<dt class="item-produktion-naechste-stufe"><?=L::h(_("Nächste Stufe"))?></dt>
		<dd class="item-produktion-naechste-stufe">
<?php
			echo F::formatRess($geb_next["prod"], 3, true, true);
?>
		</dd>
<?php
		}
?>
	</dl>
</div>
<?php
	}
?>
<?php
	$gui->end();
?>