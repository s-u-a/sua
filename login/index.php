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

	$flotten = $me->getFleetsList();

	login_gui::html_head(array("notify" => true));
?>
<ul id="planeten-umbenennen">
	<li><a href="scripts/rename.php?<?=htmlentities(session_name().'='.urlencode(session_id()))?>" title="Planeten umbenennen/aufgeben" accesskey="u" tabindex="2"><kbd>u</kbd>mbenennen</a></li>
</ul>
<?php
	$active_planet = $me->getActivePlanet();
	if(count($flotten) > 0)
	{
?>
<h2>Flottenbewegungen</h2>
<dl id="flotten">
<?php
		# Flotten sortieren
		$flotten_sorted = array();
		foreach($flotten as $flotte)
		{
			$fl = Classes::Fleet($flotte, false);
			if(!$fl->getStatus() || count($fl->getUsersList()) <= 0) continue;
			$flotten_sorted[$flotte] = $fl->getNextArrival();
		}

		asort($flotten_sorted, SORT_NUMERIC);

		$countdowns = array();
		foreach($flotten_sorted as $flotte=>$next_arrival)
		{
			$fl = Classes::Fleet($flotte);
			if(!$fl->getStatus()) continue;
			$users = $fl->getUsersList();
			if(count($users) <= 0) continue;

			$me_in_users = array_search($_SESSION['username'], $users);
			if($me_in_users !== false)
			{
				$first_user = $_SESSION['username'];
				unset($users[$me_in_users]);
			}
			else $first_user = array_shift($users);

			if($me_in_users !== false) $string = 'Ihre';
			else $string = 'Eine';

			$string .= ' <span class="beschreibung schiffe" title="'.makeFleetString($first_user, $fl->getFleetList($first_user)).'">Flotte</span> kommt ';


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
				$string .= 'zusammen mit ';
				if(count($other_strings) == 1)
					$string .= $other_strings[0];
				else
				{
					$last_string = array_pop($other_strings);
					$string .= implode(', ', $other_strings).' und '.$last_string;
				}
			}

			$string .= ' ';

			$from_pos = $fl->getLastTarget($first_user);
			if($me->isOwnPlanet($from_pos))
			{
				$active_planet2 = $me->getActivePlanet();
				$me->setActivePlanet($me->getPlanetByPos($from_pos));
				$string .= 'von Ihrem Planeten &bdquo;'.utf8_htmlentities($me->planetName()).'&ldquo; ('.$from_pos.')';
				$me->setActivePlanet($active_planet2);
			}
			else
			{
				$from_array = explode(':', $from_pos);
				$from_galaxy = Classes::Galaxy($from_array[0]);
				$planet_owner = $from_galaxy->getPlanetOwner($from_array[1], $from_array[2]);
				if($planet_owner)
				{
					$planet_name = $from_galaxy->getPlanetName($from_array[1], $from_array[2]);
					$string .= 'vom Planeten &bdquo;'.utf8_htmlentities($planet_name).'&ldquo; ('.$from_pos.', Eigentümer: '.utf8_htmlentities($planet_owner).')';
				}
				else $string .= 'vom Planeten '.$from_pos.' (unbesiedelt)';
			}

			$string .= ' und erreicht ';

			$to_pos = $fl->getCurrentTarget();
			if($me->isOwnPlanet($to_pos))
			{
				$active_planet2 = $me->getActivePlanet();
				$me->setActivePlanet($me->getPlanetByPos($to_pos));
				$string .= ' Ihren Planeten &bdquo;'.utf8_htmlentities($me->planetName()).'&ldquo; ('.$to_pos.').';
				$me->setActivePlanet($active_planet2);
			}
			else
			{
				$to_array = explode(':', $to_pos);
				$to_galaxy = Classes::Galaxy($to_array[0]);
				$planet_owner = $to_galaxy->getPlanetOwner($to_array[1], $to_array[2]);
				if($planet_owner)
				{
					$planet_name = $to_galaxy->getPlanetName($to_array[1], $to_array[2]);
					$string .= ' den Planeten &bdquo;'.utf8_htmlentities($planet_name).'&ldquo; ('.$to_pos.', Eigentümer: '.utf8_htmlentities($planet_owner).').';
				}
				else $string .= ' den Planeten '.$to_pos.' (unbesiedelt).';
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
			$string .= '>'.h(_("[fleet_".$fl->getCurrentType()."]"))."</span>.";

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
				$string .= 'Carbon: '.ths($handel[0][0]).', Aluminium: '.ths($handel[0][1]).', Wolfram: '.ths($handel[0][2]).', Radium: '.ths($handel[0][3]).', Tritium: '.ths($handel[0][4]);
				if(array_sum($handel[1]) > 0)
					$string .= '; '.makeItemsString($handel[1]);
				$string .= '">Handel</span> durchgeführt werden.';
			}

			$user_list = $fl->getUsersList();
			if(in_array($me->getName(), $user_list))
			{
				$first_user = Classes::User(array_shift($user_list));
				if($first_user->getStatus())
				{
					$fleet_passwd = $first_user->getFleetPasswd($fl->getName());
					if($fleet_passwd !== null)
						$string .= " Das Verbundflottenpasswort lautet <span class=\"flottenpasswd\">".htmlspecialchars($fleet_passwd)."</span>.";
				}
			}
?>
	<dt class="<?=($me_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=utf8_htmlentities($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug">
		<?=$string."\n"?>
<?php
			if($fl->getCurrentType() == 4 && !$fl->isFlyingBack() && $me->permissionToAct() && $me->isOwnPlanet($fl->getCurrentTarget()))
			{
?>
		<div class="handel"><a href="flotten_actions.php?action=handel&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" title="Geben Sie dieser Flotte Ladung mit auf den Rückweg">Handel</a></div>
<?php
			}
			if($fl->getCurrentType() == 3 && !$fl->isFlyingBack() && array_search($me->getName(), $fl->getUsersList()) === 0)
			{
?>
		<div class="buendnisangriff"><a href="flotten_actions.php?action=buendnisangriff&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" title="Erlauben Sie anderen Spielern, der Flotte eigene Schiffe beizusteuern.">Bündnisangriff</a></div>
<?php
			}
?>
	</dt>
	<dd class="<?=($me_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=utf8_htmlentities($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug" id="restbauzeit-<?=utf8_htmlentities($flotte)?>">Ankunft: <?=date('H:i:s, Y-m-d', $next_arrival)?> (Serverzeit)<?php if(!$fl->isFlyingBack() && ($me_in_users !== false)){?>, <a href="index.php?cancel=<?=htmlentities(urlencode($flotte))?>&amp;<?=htmlentities(urlencode(session_name()).'='.urlencode(session_id()))?>" class="abbrechen">Abbrechen</a><?php }?></dd>
<?php
			$url = '';
			if($fl->isFlyingBack()) $url = h_root.'/login/flotten.php?planet='.urlencode($me->getPlanetByPos($fl->getCurrentTarget())).'&'.urlencode(session_name()).'='.urlencode(session_id());
			$countdowns[] = array($flotte, $next_arrival, ($fl->isFlyingBack() || ($me_in_users === false)), $url);
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
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, <?=$countdown[2] ? 'false' : 'true'?>, false, '<?=$countdown[3]?>');
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
	$me->setActivePlanet($active_planet);
	$show_building = $me->checkSetting('show_building');
	$countdowns = array();
	$tabindex = 3;
	$planets = $me->getPlanetsList();
	foreach($planets as $planet)
	{
		$me->setActivePlanet($planet);
		$class = $me->getPlanetClass();
?>
	<li class="planet-<?=htmlentities($class)?><?=($planet == $active_planet) ? ' active' : ''?>"><?=($planet != $active_planet) ? '<a href="index.php?planet='.htmlentities(urlencode($planet).'&'.urlencode(session_name()).'='.urlencode(session_id())).'" tabindex="'.($tabindex++).'">' : ''?><?=utf8_htmlentities($me->planetName())?><?=($planet != $active_planet) ? '</a>' : ''?> <span class="koords">(<?=utf8_htmlentities($me->getPosString())?>)</span>
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
				$countdowns[] = array('ge-'.$planet, $building_gebaeude[1], h_root."/login/gebaeude.php?planet=".urlencode($planet)."&".urlencode(session_name())."=".urlencode(session_id()));
			}
			elseif($me->getRemainingFields() <= 0)
			{
?>
			<dd class="c-gebaeudebau ausgebaut">Ausgebaut</dd>
<?php
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
				$countdowns[] = array('fo-'.$planet, $building_forschung[1], h_root."/login/forschung.php?planet=".urlencode($planet)."&".urlencode(session_name())."=".urlencode(session_id()));
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
			$building = $me->checkBuildingThing('roboter');
			if($building)
			{
				switch($show_building['roboter'])
				{
					case 3:
						$last_building = array_pop($building);
						$item_info = $me->getItemInfo($last_building[0], 'roboter');
						$finishing_time = $last_building[1]+$last_building[2]*$last_building[3];
?>
			<dd class="c-roboter">(<?=utf8_htmlentities($item_info['name'])?>) <span id="restbauzeit-ro-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'roboter');
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-roboter"><?=utf8_htmlentities($item_info['name'])?> <span class="anzahl">(<?=ths($first_building[2])?>)</span> <span id="restbauzeit-ro-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'roboter');
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-roboter"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-ro-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
				}
				$countdowns[] = array('ro-'.$planet, $finishing_time, h_root."/login/roboter.php?planet=".urlencode($planet)."&".urlencode(session_name())."=".urlencode(session_id()));
			}
			else
			{
?>
			<dd class="c-roboter gelangweilt">Gelangweilt</dd>
<?php
			}
		}

		if($show_building['schiffe'] && $me->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-schiffe">Schiffe</dt>
<?php
			$building = $me->checkBuildingThing('schiffe');
			if($building)
			{
				switch($show_building['schiffe'])
				{
					case 3:
						$last_building = array_pop($building);
						$item_info = $me->getItemInfo($last_building[0], 'schiffe');
						$finishing_time = $last_building[1]+$last_building[2]*$last_building[3];
?>
			<dd class="c-schiffe">(<?=utf8_htmlentities($item_info['name'])?>) <span id="restbauzeit-sc-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'schiffe');
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-schiffe"><?=utf8_htmlentities($item_info['name'])?> <span class="anzahl">(<?=ths($first_building[2])?>)</span> <span id="restbauzeit-sc-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'schiffe');
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-schiffe"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-sc-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
				}
				$countdowns[] = array('sc-'.$planet, $finishing_time, h_root."/login/schiffswerft.php?planet=".urlencode($planet)."&".urlencode(session_name())."=".urlencode(session_id()));
			}
			else
			{
?>
			<dd class="c-schiffe gelangweilt">Gelangweilt</dd>
<?php
			}
		}

		if($show_building['verteidigung'] && $me->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-verteidigung">Verteidigung</dt>
<?php
			$building = $me->checkBuildingThing('verteidigung');
			if($building)
			{
				switch($show_building['verteidigung'])
				{
					case 3:
						$last_building = array_pop($building);
						$item_info = $me->getItemInfo($last_building[0], 'verteidigung');
						$finishing_time = $last_building[1]+$last_building[2]*$last_building[3];
?>
			<dd class="c-verteidigung">(<?=utf8_htmlentities($item_info['name'])?>) <span id="restbauzeit-ve-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'verteidigung');
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-verteidigung"><?=utf8_htmlentities($item_info['name'])?> <span class="anzahl">(<?=ths($first_building[2])?>)</span> <span id="restbauzeit-ve-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'verteidigung');
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-verteidigung"><?=utf8_htmlentities($item_info['name'])?> <span id="restbauzeit-ve-<?=utf8_htmlentities($planet)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $finishing_time)?> (Serverzeit)</span></dd>
<?php
						break;
				}
				$countdowns[] = array('ve-'.$planet, $finishing_time, h_root."/login/verteidigung.php?planet=".urlencode($planet)."&".urlencode(session_name())."=".urlencode(session_id()));
			}
			else
			{
?>
			<dd class="c-verteidigung gelangweilt">Gelangweilt</dd>
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
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, false, false, '<?=$countdown[2]?>');
<?php
		}
?>
</script>
<?php
	}
?>
<h2 id="punkte">Punkte</h2>
<dl class="punkte">
	<dt class="c-gebaeude"><?=h(_("[scores_0]"))?></dt>
	<dd class="c-gebaeude"><?=ths($me->getScores(0))?> <span class="platz">(Platz <strong><?=ths($me->getRank(0))?></strong>)</span></dd>

	<dt class="c-forschung"><?=h(_("[scores_1]"))?></dt>
	<dd class="c-forschung"><?=ths($me->getScores(1))?> <span class="platz">(Platz <strong><?=ths($me->getRank(1))?></strong>)</span></dd>

	<dt class="c-roboter"><?=h(_("[scores_2]"))?></dt>
	<dd class="c-roboter"><?=ths($me->getScores(2))?> <span class="platz">(Platz <strong><?=ths($me->getRank(2))?></strong>)</span></dd>

	<dt class="c-flotte"><?=h(_("[scores_3]"))?></dt>
	<dd class="c-flotte"><?=ths($me->getScores(3))?> <span class="platz">(Platz <strong><?=ths($me->getRank(3))?></strong>)</span></dd>

	<dt class="c-verteidigung"><?=h(_("[scores_4]"))?></dt>
	<dd class="c-verteidigung"><?=ths($me->getScores(4))?> <span class="platz">(Platz <strong><?=ths($me->getRank(4))?></strong>)</span></dd>

	<dt class="c-flugerfahrung"><?=h(_("[scores_5]"))?></dt>
	<dd class="c-flugerfahrung"><?=ths($me->getScores(5))?> <span class="platz">(Platz <strong><?=ths($me->getRank(5))?></strong>)</span></dd>

	<dt class="c-kampferfahrung"><?=h(_("[scores_6]"))?></dt>
	<dd class="c-kampferfahrung"><?=ths($me->getScores(6))?> <span class="platz">(Platz <strong><?=ths($me->getRank(6))?></strong>)</span></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($me->getScores())?> <span class="platz">(Platz <strong><?=ths($me->getRank())?></strong> von <strong class="gesamt-spieler"><?=ths(getUsersCount())?></strong>)</span></dd>
</dl>
<?php
	login_gui::html_foot();
?>
