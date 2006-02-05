<?php
	require('scripts/include.php');

	if(isset($_GET['cancel']))
	{
		# Flotte zurueckrufen

		$flotte = Classes::Fleet($_GET['cancel']);
		if($flotte->callBack($_SESSION['username']))
			delete_request();
	}
	
	function makeFleetString($user, $fleet)
	{
		$user = Classes::User($user);
		$flotte_string = array();
		foreach($fleet as $id=>$anzahl)
		{
			if($anzahl > 0 && ($item_info = $user->getItemInfo($id, 'schiffe')))
				$flotte_string[] = utf8_htmlentities($item_info['name']).': '.ths($anzahl);
		}
		$flotte_string = implode('; ', $flotte_string);
		return $flotte_string;
	}
	
	login_gui::html_head();
?>
<ul id="planeten-umbenennen">
	<li><a href="scripts/rename.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Planeten umbenennen/aufgeben" accesskey="u" tabindex="2"><kbd>u</kbd>mbenennen</a></li>
</ul>
<?php
	if(!$me->checkSetting('notify'))
	{
		global $message_type_names;
		
		$ncount = array(
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0,
			5 => 0,
			6 => 0,
			7 => 0
		);
		$ges_ncount = 0;
		
		$cats = $me->getMessageCategoriesList();
		foreach($cats as $cat)
		{
			$message_ids = $me->getMessagesList($cat);
			foreach($message_ids as $message)
			{
				$status = $me->checkMessageStatus($message, $cat);
				if($status == 1 && $cat != 8)
				{
					$ncount[$cat]++;
					$ges_ncount++;
				}
			}
		}

		if($ges_ncount > 0)
		{
			$title = array();
			$link = 'nachrichten.php';
			foreach($ncount as $type=>$count)
			{
				if($count > 0)
					$title[] = htmlentities($message_type_names[$type]).':&nbsp;'.htmlentities($count);
				if($count == $ges_ncount)
					$link .= '?type='.urlencode($type);
			}
			$title = implode('; ', $title);
			if(strpos($link, '?') === false)
				$link .= '?';
			else
				$link .= '&';
			$link .= urlencode(SESSION_COOKIE).'='.urlencode(session_id());
?>
<p class="neue-nachrichten">
	<a href="<?=htmlentities('http://'.$_SERVER['HTTP_HOST'].h_root.'/login/'.$link)?>" title="<?=$title?>">Sie haben <?=htmlentities($ges_ncount)?> neue <kbd>N</kbd>achricht<?=($ges_ncount != 1) ? 'en' : ''?>.</a>
</p>
<?php
		}
	}

	$flotten = $me->getFleetsList();
	if(count($flotten) > 0)
	{
?>
<h2>Flottenbewegungen</h2>
<dl id="flotten">
<?php
		$countdowns = array();
		foreach($flotten as $flotte)
		{
			$fl = Classes::Fleet($flotte);
			$users = $fl->getUsersList();
			if(count($users) <= 0) continue;
			
			$me_in_users = array_search($_SESSION['username'], $users);
			if($me_in_users !== false)
			{
				$first_user = $_SESSION['username'];
				unset($users[$me_in_users]);
			}
			else $first_user = array_shift($users);
			
			if($me_in_users !== false)
			{
				$active_planet = $me->getActivePlanet();
				$me->setActivePlanet($me->getPlanetByPos($fl->from($first_user)));
				$string = 'Ihre <span class="beschreibung schiffe" title="'.makeFleetString($first_user, $fl->getFleetList($first_user)).'">Flotte</span> vom Planeten &bdquo;'.utf8_htmlentities($me->planetName()).'&ldquo; ('.$me->getPosString().') erreicht';
				$me->setActivePlanet($active_planet);
			}
			else
			{
				$from_pos = $fl->from($first_user);
				$from_array = explode(':', $from_pos);
				$from_galaxy = Classes::Galaxy($from_array[0]);
				$planet_name = $from_galaxy->getPlanetName($from_array[1], $from_array[2]);
				$string = 'Eine <span class="beschreibung schiffe" title="'.makeFleetString($first_user, $fl->getFleetList($first_user)).'">Flotte</span> vom Planeten &bdquo;'.utf8_htmlentities($planet_name).'&ldquo; ('.$from_pos.', Eigentümer: '.utf8_htmlentities($from_user).') erreicht';
			}
			
			if(count($users) > 0)
			{
				$other_strings = array();
				foreach($users as $user)
				{
					$from_pos = $fl->from($user);
					$from_array = explode(':', $from_pos);
					$from_galaxy = Classes::Galaxy($from_array[0]);
					$planet_name = $from_galaxy->getPlanetName($from_array[1], $from_array[2]);
					$other_strings[] = 'einer <span class="beschreibung schiffe" title="'.makeFleetString($user, $fl->getFleetList($user)).'">Flotte</span> vom Planeten &bdquo;'.utf8_htmlentities($planet_name).'&ldquo; ('.$from_pos.', Eigentümer: '.utf8_htmlentities($user).')';
				}
				if(count($other_strings) == 1)
					$string .= $other_strings[0];
				else
				{
					$last_string = array_pop($other_strings);
					$string .= 'zusammen mit '.implode(', ', $other_strings).' und '.$last_string;
				}
			}
			
			$to_pos = $fl->getCurrentTarget();
			if($me->isOwnPlanet($to_pos))
			{
				$active_planet = $me->getActivePlanet();
				$me->setActivePlanet($me->getPlanetByPos($to_pos));
				$string .= ' Ihren Planeten &bdquo;'.utf8_htmlentities($me->planetName()).'&ldquo; ('.$to_pos.').';
				$me->setActivePlanet($active_planet);
			}
			else
			{
				$to_array = explode(':', $to_pos);
				$to_galaxy = Classes::Galaxy($to_array[0]);
				$planet_name = $to_galaxy->getPlanetName($to_array[1], $to_array[2]);
				$planet_owner = $to_galaxy->getPlanetOwner($to_array[1], $to_array[2]);
				$string .= ' den Planeten &bdquo;'.utf8_htmlentities($planet_name).'&ldquo; ('.$to_pos.', Eigentümer: '.utf8_htmlentities($planet_owner).').';
			}
			
			if($fl->isFlyingBack())
				$string .= ' Ihr Auftrag lautete ';
			else $string .= ' Ihr Auftrag lautet ';

			$ress = array(array(0, 0, 0, 0, 0), array());
			$users = $fl->getUsersList();
			foreach($users as $user)
			{
				$this_ress = $fl->getTransport($user);
				if(isset($this_ress[0][0])) $ress[0][0] += $this_ress[0][0];
				if(isset($this_ress[0][1])) $ress[0][1] += $this_ress[0][1];
				if(isset($this_ress[0][2])) $ress[0][2] += $this_ress[0][2];
				if(isset($this_ress[0][3])) $ress[0][3] += $this_ress[0][3];
				if(isset($this_ress[0][4])) $ress[0][4] += $this_ress[0][4];
				foreach($this_ress[1] as $id=>$count)
				{
					if(isset($ress[1][$id])) $ress[1][$id] += $count;
					else $ress[1][$id] = $count;
				}
			}
			$ress_string = array();
			if(array_sum($ress[0]) > 0)
			{
				if(isset($ress[0][0])) $ress_string[] = 'Carbon: '.ths($ress[0][0]);
				if(isset($ress[0][1])) $ress_string[] = 'Aluminium: '.ths($ress[0][1]);
				if(isset($ress[0][2])) $ress_string[] = 'Wolfram: '.ths($ress[0][2]);
				if(isset($ress[0][3])) $ress_string[] = 'Radium: '.ths($ress[0][3]);
				if(isset($ress[0][4])) $ress_string[] = 'Tritium: '.ths($ress[0][4]);
			}

			foreach($ress[1] as $id=>$anzahl)
			{
				if($anzahl > 0 && ($item_info = $me->getItemInfo($id, 'roboter')))
					$ress_string[] = utf8_htmlentities($item_info['name']).': '.ths($anzahl);
			}

			$ress_string = implode(', ', $ress_string);

			$string .= '<span class="beschreibung transport"';
			if(strlen($ress_string) > 0) $string .= ' title="'.$ress_string.'"';
			$string .= '>';
			
			$type = $fl->getCurrentType();
			if(isset($type_names[$type]))
				$string .= htmlentities($type_names[$type]);
			else
				$string .= utf8_htmlentities($type);
			$string .= '</span>.';
			
			$handel = array(array(0, 0, 0, 0, 0), array());
			foreach($users as $user)
			{
				$this_handel = $fl->getHandel($user);
				if(isset($this_handel[0][0])) $handel[0][0] += $this_handel[0][0];
				if(isset($this_handel[0][1])) $handel[0][1] += $this_handel[0][1];
				if(isset($this_handel[0][2])) $handel[0][2] += $this_handel[0][2];
				if(isset($this_handel[0][3])) $handel[0][3] += $this_handel[0][3];
				if(isset($this_handel[0][4])) $handel[0][4] += $this_handel[0][4];
				foreach($this_handel[1] as $id=>$count)
				{
					if(isset($handel[1][$id])) $handel[1][$id] += $count;
					else $handel[1][$id] = $count;
				}
			}
			
			if(array_sum($handel[0]) > 0 || array_sum($handel[1]) > 0)
			{
				$string .= ' Es wird ein <span class="beschreibung handel" title="';
				$string .= 'Carbon: '.ths($flotte[8][0][0]).', Aluminium: '.ths($flotte[8][0][1]).', Wolfram: '.ths($flotte[8][0][2]).', Radium: '.ths($flotte[8][0][3]).', Tritium: '.ths($flotte[8][0][4]);
				if(array_sum($flotte[8][1]) > 0)
				{
					$string .= '; ';
					$rob = array();
					foreach($flotte[8][1] as $id=>$anzahl)
					{
						if(!isset($items['roboter'][$id]) || $anzahl <= 0)
							$rob[] = utf8_htmlentities($items['roboter'][$id]['name']).': '.ths($anzahl);
					}
					$string .= implode(', ', $rob);
				}
				$string .= '">Handel</span> durchgeführt werden.';
			}
?>
	<dt class="<?=($me_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=utf8_htmlentities($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug">
		<?=$string."\n"?>
<?php
			if($fl->getCurrentType() == 4 && !$fl->isFlyingBack() && $me->isOwnPlanet($fl->getCurrentTarget()))
			{
?>
		<div class="handel"><a href="flotten_actions.php?action=handel&amp;id=<?=htmlentities(urlencode($flotte))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Geben Sie dieser Flotte Ladung mit auf den Rückweg">Handel</a></div>
<?php
			}
?>
	</dt>
	<dd class="<?=($me_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=utf8_htmlentities($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug" id="restbauzeit-<?=utf8_htmlentities($flotte)?>">Ankunft: <?=date('H:i:s, Y-m-d', $fl->getNextArrival())?> (Serverzeit)<?php if(!$fl->isFlyingBack() && ($me_in_users !== false)){?>, <a href="index.php?cancel=<?=htmlentities(urlencode($flotte))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" class="abbrechen">Abbrechen</a><?php }?></dd>
<?php
			$countdowns[] = array($flotte, $fl->getNextArrival(), ($fl->isFlyingBack() || ($me_in_users === false)));
		}
?>
</dl>
<?php
		if(count($countdowns) > 0)
		{
?>
<script type="text/javascript">
<?php
			foreach($countdowns as $countdown)
			{
?>
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, <?=$countdown[2] ? 'false' : 'true'?>, <?=EVENTHANDLER_INTERVAL?>);
<?php
			}
?>
</script>
<?php
		}
	}
?>
<h2 id="planeten">Planeten</h2>
<ol id="planets">
<?php
	$show_building = $me->checkSetting('show_building');
	$countdowns = array();
	$tabindex = 3;
	$planets = $me->getPlanetsList();
	$active_planet = $me->getActivePlanet();
	foreach($planets as $planet)
	{
		$me->setActivePlanet($planet);
		$class = $me->getPlanetClass();
?>
	<li class="planet-<?=htmlentities($class)?><?=($planet == $active_planet) ? ' active' : ''?>"><?=($planet != $active_planet) ? '<a href="index.php?planet='.htmlentities(urlencode($planet).'&'.urlencode(SESSION_COOKIE).'='.urlencode(session_id())).'" tabindex="'.($tabindex++).'">' : ''?><?=utf8_htmlentities($me->planetName())?><?=($planet != $active_planet) ? '</a>' : ''?> <span class="koords">(<?=utf8_htmlentities($me->getPosString())?>)</span>
		<dl class="planet-info">
			<dt class="c-felder">Felder</dt>
			<dd class="c-felder"><?=ths($me->getUsedFields())?> <span class="gesamtgroesse">(<?=ths($me->getTotalFields())?>)</span></dd>
<?php
		if($show_building['gebaeude'])
		{
?>
			<dt class="c-gebaeudebau">Gebäudebau</dt>
<?php
			$building_gebaeude = $me->checkBuildingThing('gebaeude');
			if($building_gebaeude)
			{
				$item_info = $me->getItemInfo($building_gebaeude[0], 'gebaeude');
?>
			<dd class="c-gebaeudebau"><?=utf8_htmlentities($item_info['name'])?> <span class="restbauzeit" id="restbauzeit-ge-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $building_gebaeude[1])?> (Serverzeit)</span></dd>
<?php
				$countdowns[] = array('ge-'.$planet, $building_gebaeude[1]);
			}
			else
			{
?>
			<dd class="c-gebaeudebau gelangweilt">Gelangweilt</dd>
<?php
			}
		}

		if($show_building['forschung'] && $me->getItemLevel('B8', 'gebaeude') > 0)
		{
?>

			<dt class="c-forschung">Forschung</dt>
<?php
			$building_forschung = $me->checkBuildingThing('forschung');
			if($building_forschung)
			{
				$item_info = $me->getItemInfo($building_forschung[0], 'forschung');
?>
			<dd class="c-forschung"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-fo-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $building_forschung[1])?> (Serverzeit)</span></dd>
<?php
				$countdowns[] = array('fo-'.$planet, $building_forschung[1]);
			}
			else
			{
?>
			<dd class="c-forschung gelangweilt">Gelangweilt</dd>
<?php
			}
		}
		
		if($show_building['roboter'] && $me->getItemLevel('B9', 'gebaeude') > 0)
		{
?>

			<dt class="c-roboter">Roboter</dt>
<?php
			$building_roboter = $me->checkBuildingThing('roboter');
			if($building_roboter)
			{
				$building_roboter = array_shift($building_roboter);
				$item_info = $me->getItemInfo($building_roboter[0], 'roboter');
				$finishing_time = $building_roboter[1]+$building_roboter[3];
?>
			<dd class="c-roboter"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-ro-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
				$countdowns[] = array('ro-'.$planet, $finishing_time);
			}
			else
			{
?>
			<dd class="c-roboter">Gelangweilt</dd>
<?php
			}
		}
		
		if($show_building['schiffe'] && $me->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-schiffe">Schiffe</dt>
<?php
			$building_schiffe = $me->checkBuildingThing('schiffe');
			if($building_schiffe)
			{
				$building_schiffe = array_shift($building_schiffe);
				$item_info = $me->getItemInfo($building_schiffe[0], 'schiffe');
				$finishing_time = $building_schiffe[1]+$building_schiffe[3];
?>
			<dd class="c-roboter"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-sc-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
				$countdowns[] = array('sc-'.$planet, $finishing_time);
			}
			else
			{
?>
			<dd class="c-schiffe">Gelangweilt</dd>
<?php
			}
		}
		
		if($show_building['verteidigung'] && $me->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-verteidigung">Verteidigung</dt>
<?php
			$building_verteidigung = $me->checkBuildingThing('verteidigung');
			if($building_verteidigung)
			{
				$building_verteidigung = array_shift($building_verteidigung);
				$item_info = $me->getItemInfo($building_verteidigung[0], 'verteidigung');
				$finishing_time = $building_verteidigung[1]+$building_verteidigung[3];
?>
			<dd class="c-verteidigung"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-ve-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
				$countdowns[] = array('ve-'.$planet, $finishing_time);
			}
			else
			{
?>
			<dd class="c-verteidigung">Gelangweilt</dd>
<?php
			}
		}
?>
		</dl>
	</li>
<?php
	}
	$me->setActivePlanet($active_planet);
?>
</ol>
<?php
	if(count($countdowns) > 0)
	{
?>
<script type="text/javascript">
<?php
		foreach($countdowns as $countdown)
		{
?>
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, false);
<?php
		}
?>
</script>
<?php
	}
?>
<h2 id="punkte">Punkte</h2>
<dl class="punkte">
	<dt class="c-gebaeude">Gebäude</dt>
	<dd class="c-gebaeude"><?=ths($me->getScores(0))?></dd>

	<dt class="c-forschung">Forschung</dt>
	<dd class="c-forschung"><?=ths($me->getScores(1))?></dd>

	<dt class="c-roboter">Roboter</dt>
	<dd class="c-roboter"><?=ths($me->getScores(2))?></dd>

	<dt class="c-flotte">Flotte</dt>
	<dd class="c-flotte"><?=ths($me->getScores(3))?></dd>

	<dt class="c-verteidigung">Verteidigung</dt>
	<dd class="c-verteidigung"><?=ths($me->getScores(4))?></dd>

	<dt class="c-flugerfahrung">Flugerfahrung</dt>
	<dd class="c-flugerfahrung"><?=ths($me->getScores(5))?></dd>

	<dt class="c-kampferfahrung">Kampferfahrung</dt>
	<dd class="c-kampferfahrung"><?=ths($me->getScores(6))?></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($me->getScores())?> <span class="gesamt-spieler">(Platz <?=ths($me->getRank())?> von <?=ths(getUsersCount())?>)</span></dd>
</dl>
<?php
	login_gui::html_foot();
?>