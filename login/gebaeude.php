<?php
	require('scripts/include.php');

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
		if(!$building)
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
		if(!$building)
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

	if(isset($a_id) && $me->permissionToAct() && $me->buildGebaeude($a_id, $rueckbau))
	{
		if($me->checkSetting('fastbuild') && $fastbuild_next !== false)
		{
			# Fastbuild

			$_SESSION['last_click_ignore'] = true;
			$url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?planet='.urlencode($fastbuild_next).'&'.SESSION_COOKIE.'='.urlencode(session_id());
			header('Location: '.$url, true, 303);
			die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
		}
		else
			delete_request();
	}

	if(isset($_GET['cancel']))
	{
		$building = $me->checkBuildingThing('gebaeude');
		if($building && $building[0] == $_GET['cancel'] && $me->removeBuildingThing('gebaeude'))
			delete_request();
	}

	login_gui::html_head();
?>
<h2>Gebäude</h2>
<?php
	if(($fastbuild_prev !== false || $fastbuild_next !== false) && $me->permissionToAct())
	{
?>
<ul class="unbeschaeftigte-planeten">
<?php
		$active_planet = $me->getActivePlanet();
		if($fastbuild_prev !== false)
		{
			$me->setActivePlanet($fastbuild_prev);
?>
	<li class="c-voriger"><a href="gebaeude.php?planet=<?=htmlentities(urlencode($fastbuild_prev))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Voriger unbeschäftigter Planet: &bdquo;<?=utf8_htmlentities($me->planetName())?>&ldquo; (<?=utf8_htmlentities($me->getPosString())?>) [U]" tabindex="1" accesskey="u" rel="prev">&larr;</a></li>
<?php
		}
		if($fastbuild_next !== false)
		{
			$me->setActivePlanet($fastbuild_next);
?>
	<li class="c-naechster"><a href="gebaeude.php?planet=<?=htmlentities(urlencode($fastbuild_next))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Nächster unbeschäftigter Planet: &bdquo;<?=utf8_htmlentities($me->planetName())?>&ldquo; (<?=utf8_htmlentities($me->getPosString())?>) [Q]" tabindex="2" accesskey="q" rel="next">&rarr;</a></li>
<?php
		}
		$me->setActivePlanet($active_planet);
?>
</ul>
<?php
	}

	$tabindex = 3;
	$gebaeude = $me->getItemsList('gebaeude');
	foreach($gebaeude as $id)
	{
		$geb = $me->getItemInfo($id, 'gebaeude');
		
		if(!$geb['deps-okay']) # Abhaengigkeiten nicht erfuellt
			continue;
?>
<div class="item gebaeude" id="item-<?=htmlentities($id)?>">
	<h3><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($geb['name'])?></a> <span class="stufe">(Stufe&nbsp;<?=ths($geb['level'])?>)</span></h3>
<?php
		if($me->permissionToAct() && ($id != 'B8' || !$me->checkBuildingThing('forschung')) && ($id != 'B9' || !$me->checkBuildingThing('roboter')) && ($id != 'B10' || (!$me->checkBuildingThing('schiffe') && !$me->checkBuildingThing('verteidigung'))))
		{
			$building = $me->checkBuildingThing('gebaeude');
			if(!$building)
			{
?>
	<ul>
		<li class="item-ausbau<?=$geb['buildable'] ? '' : ' no-ress'?>"><?=$geb['buildable'] ? '<a href="gebaeude.php?ausbau='.htmlentities(urlencode($id)).'&amp;'.htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id())).'" tabindex="'.($tabindex++).'">' : ''?>Ausbau auf Stufe&nbsp;<?=ths($geb['level']+1)?><?=$geb['buildable'] ? '</a>' : ''?></li>
<?php
				if($geb['level'] >= 1)
				{
?>
		<li class="item-rueckbau<?=$geb['debuildable'] ? '' : ' no-ress'?>"><?=$geb['debuildable'] ? '<a href="gebaeude.php?abbau='.htmlentities(urlencode($id)).'&amp;'.htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id())).'">' : ''?>Rückbau auf Stufe&nbsp;<?=ths($geb['level']-1)?><?=$geb['debuildable'] ? '</a>' : ''?></li>
<?php
				}
?>
	</ul>
<?php
			}
			elseif($building[0] == $id)
			{
?>
	<div class="restbauzeit" id="restbauzeit-<?=htmlentities($building[0])?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $building[1])?> (Serverzeit), <a href="gebaeude.php?cancel=<?=htmlentities(urlencode($building[0]))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" class="abbrechen">Abbrechen</a></div>
	<script type="text/javascript">
		init_countdown('<?=$building[0]?>', <?=$building[1]?>);
	</script>
<?php
			}
		}
?>
	<dl>
		<dt class="item-kosten">Kosten</dt>
		<dd class="item-kosten">
			<?=format_ress($geb['ress'], 3)?>
		</dd>

		<dt class="item-bauzeit">Bauzeit</dt>
		<dd class="item-bauzeit"><?=format_btime($geb['time'])?></dd>
	</dl>
</div>
<?php
	}
?>
<?php
	login_gui::html_foot();
?>