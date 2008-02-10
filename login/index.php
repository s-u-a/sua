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
	require('include.php');

	if(isset($_GET['cancel']))
	{
		# Flotte zurueckrufen

		$flotte = Classes::Fleet($_GET['cancel']);
		if($flotte->callBack($me->getName()))
			delete_request();
	}

	function makeFleetString($user, $fleet)
	{
		$user = Classes::User($user);
		$flotte_string = array();
		foreach($fleet as $id=>$anzahl)
		{
			if($anzahl > 0 && ($item_info = $user->getItemInfo($id, 'schiffe')))
				$flotte_string[] = htmlspecialchars($item_info['name']).': '.ths($anzahl);
		}
		$flotte_string = implode('; ', $flotte_string);
		return $flotte_string;
	}

	$flotten = $me->getFleetsList();

	$gui->setOption("notify", true);
	$gui->init();
?>
<ul id="planeten-umbenennen">
	<li><a href="info/rename.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Planeten umbenennen/aufgeben"), false)?>" tabindex="<?=$tabindex++?>"<?=accesskey_attr(_("&umbenennen[login/index.php|1]"))?>><?=h(_("&umbenennen[login/index.php|1]"))?></a></li>
</ul>
<?php
	$active_planet = $me->getActivePlanet();
	if(count($flotten) > 0)
	{
?>
<h2><?=h(_("Flottenbewegungen"))?></h2>
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

			$me_in_users = array_search($me->getName(), $users);
			if($me_in_users !== false)
			{
				$first_user = $me->getName();
				unset($users[$me_in_users]);
			}
			else $first_user = array_shift($users);

			$part1 = sprintf(h($me_in_users !== false ? _("Ihre %sFlotte%s") : _("Eine %sFlotte%s")), "<span class=\"beschreibung schiffe\" title=\"".makeFleetString($first_user, $fl->getFleetList($first_user))."\">", "</span>");

			$part2 = "";
			if(count($users) > 0)
			{
				foreach($users as $i=>$user)
				{
					$from_pos = $fl->from($user);
					$from_array = explode(':', $from_pos);
					$from_galaxy = Classes::Galaxy($from_array[0]);
					if($i == 0)
						$this_message = _(" zusammen mit einer %sFlotte%s vom Planeten %s");
					elseif($i == count($users)-1)
						$this_message = _(" und einer %sFlotte%s vom Planeten %s");
					else
						$this_message = _(", einer %sFlotte%s vom Planeten %s");
					$part2 .= sprintf(h($this_message), "<span class=\"beschreibung schiffe\" title=\"".makeFleetString($user, $fl->getFleetList($user))."\">", "</span>", htmlspecialchars(format_planet($from_pos, $from_galaxy->getPlanetName($from_array[1], $from_array[2]), $user)));
				}
			}

			$from_pos = $fl->getLastTarget($first_user);
			if($me->isOwnPlanet($from_pos))
			{
				$me->setActivePlanet($me->getPlanetByPos($from_pos));
				$part3 = sprintf(h(_("von Ihrem Planeten %s")), htmlspecialchars(format_planet($from_pos, $me->planetName())));
				$me->setActivePlanet($active_planet);
			}
			else
			{
				$from_array = explode(':', $from_pos);
				$from_galaxy = Classes::Galaxy($from_array[0]);
				$part3 = sprintf(h(_("vom Planeten %s")), htmlspecialchars(format_planet($from_pos, $from_galaxy->getPlanetName($from_array[1], $from_array[2]), $from_galaxy->getPlanetOwner($from_array[1], $from_array[2]))));
			}

			$to_pos = $fl->getCurrentTarget();
			if($me->isOwnPlanet($to_pos))
			{
				$me->setActivePlanet($me->getPlanetByPos($to_pos));
				$part4 = sprintf(h(_("Ihren Planeten %s")), htmlspecialchars(format_planet($to_pos, $me->planetName())));
				$me->setActivePlanet($active_planet);
			}
			else
			{
				$to_array = explode(':', $to_pos);
				$to_galaxy = Classes::Galaxy($to_array[0]);
				$part4 = sprintf(h(_("den Planeten %s")), htmlspecialchars(format_planet($to_pos, $to_galaxy->getPlanetName($to_array[1], $to_array[2]), $to_galaxy->getPlanetOwner($to_array[1], $to_array[2]))));
			}

			$string = sprintf(h(_("%s kommt%s %s und erreicht %s.")), $part1, $part2, $part3, $part4);

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
			foreach($ress[0] as $id=>$anzahl)
			{
				if($anzahl > 0)
					$ress_string[] = sprintf(_("%s: %s"), _("[ress_".$id."]"), ths($anzahl));
			}
			foreach($ress[1] as $id=>$anzahl)
			{
				if($anzahl > 0 && ($item_info = $me->getItemInfo($id, 'roboter')))
					$ress_string[] = sprintf(_("%s: %s"), _("[item_".$id."]"), ths($anzahl));
			}
			$string .= " ".sprintf(h($fl->isFlyingBack() ? _("Ihr Auftrag lautete %s.") : _("Ihr Auftrag lautet %s.")), "<span class=\"beschreibung transport\"".(count($ress_string) > 0 ? " title=\"".h(implode(_(", "), $ress_string))."\"" : "").">".h(_("[fleet_".$fl->getCurrentType()."]"))."</span>");

			$handel = array(array(0, 0, 0, 0, 0), array());
			$give = false;
			$dont_give = false;
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
				if($this_handel[2])
					$give = true;
				else
					$dont_give = true;
			}

			if(!$fl->isFlyingBack())
			{
				if(!$give && $dont_give)
					$string .= " ".h(_("Die transportierten Rohstoffe werden nicht abgeliefert werden."));
				elseif($give && $dont_give)
					$string .= " ".h(_("Nicht alle transportierten Rohstoffe werden abgeliefert werden."));
			}

			if(array_sum($handel[0]) > 0 || array_sum($handel[1]) > 0)
			{
				$ress_string = array();
				foreach($handel[0] as $id=>$anzahl)
				{
					if($anzahl > 0)
						$ress_string[] = sprintf(_("%s: %s"), _("[ress_".$id."]"), ths($anzahl));
				}
				foreach($handel[1] as $id=>$anzahl)
				{
					if($anzahl > 0 && ($item_info = $me->getItemInfo($id, 'roboter')))
						$ress_string[] = sprintf(_("%s: %s"), _("[item_".$id."]"), ths($anzahl));
				}
				$string .= " ".sprintf(h(_("Es wird ein %sHandel%s durchgeführt werden.")), "<span class=\"beschreibung handel\" title=\"".implode(_(", "), $ress_string)."\">", "</span>");
			}

			$user_list = $fl->getUsersList();
			if(in_array($me->getName(), $user_list))
			{
				$first_user = Classes::User(array_shift($user_list));
				if($first_user->getStatus())
				{
					$fleet_passwd = $first_user->getFleetPasswd($fl->getName());
					if($fleet_passwd !== null)
						$string .= " ".sprintf(h(_("Das Verbundflottenpasswort lautet %s.")), "<span class=\"flottenpasswd\">".htmlspecialchars($fleet_passwd)."</span>");
				}
			}

			if($fl->getNeededSlots($me->getName()) > 1)
				$string .= " <a href=\"info/flotten_actions.php?action=route&amp;id=".htmlspecialchars(urlencode($flotte))."&".htmlspecialchars(global_setting("URL_SUFFIX"))."\">".h(_("Route anzeigen"))."</a>";
?>
	<dt class="<?=($me_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=htmlspecialchars($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug">
		<?=$string."\n"?>
<?php
			if($fl->getCurrentType() == 4 && !$fl->isFlyingBack() && $me->permissionToAct() && $me->isOwnPlanet($fl->getCurrentTarget()))
			{
?>
		<div class="handel action"><a href="info/flotten_actions.php?action=handel&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Geben Sie dieser Flotte Ladung mit auf den Rückweg"))?>"><?=h(_("Handel"))?></a></div>
<?php
			}
			if($fl->getCurrentType() == 3 && !$fl->isFlyingBack() && array_search($me->getName(), $fl->getUsersList()) === 0)
			{
?>
		<div class="buendnisangriff action"><a href="info/flotten_actions.php?action=buendnisangriff&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Erlauben Sie anderen Spielern, der Flotte eigene Schiffe beizusteuern."))?>"><?=h(_("Bündnisangriff"))?></a></div>
<?php
			}
?>
	</dt>
	<dd class="<?=($me_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=htmlspecialchars($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug" id="restbauzeit-<?=htmlspecialchars($flotte)?>"><?=htmlspecialchars(format_ftime($next_arrival, $me_in_users !== null ? $me : null))?><?php if(!$fl->isFlyingBack() && ($me_in_users !== false)){?> <a href="index.php?cancel=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="abbrechen"><?=h(_("Abbrechen"))?></a><?php }?></dd>
<?php
			$url = '';
			if($fl->isFlyingBack()) $url = h_root.'/login/flotten.php?'.preg_replace("/((^|&)planet=)\d+/", "\${1}".$me->getPlanetByPos($fl->getCurrentTarget()), global_setting("URL_SUFFIX"));
			if($me_in_users === false || !$me->umode())
				$countdowns[] = array($flotte, $next_arrival, ($fl->isFlyingBack() || ($me_in_users === false)), $url);
		}
?>
</dl>
<?php
		if(count($countdowns) > 0)
		{
?>
<script type="text/javascript">
// <![CDATA[
<?php
			foreach($countdowns as $countdown)
			{
?>
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, <?=$countdown[2] ? 'false' : 'true'?>, false, '<?=$countdown[3]?>');
<?php
			}
?>
// ]]>
</script>
<?php
		}
	}
?>
<h2 id="planeten"><?=h(_("Planeten"))?></h2>
<ol id="planets">
<?php
	$me->setActivePlanet($active_planet);
	$show_building = $me->checkSetting('show_building');
	$countdowns = array();
	$planets = $me->getPlanetsList();
	foreach($planets as $planet)
	{
		$me->setActivePlanet($planet);
		$class = $me->getPlanetClass();

		define_url_suffix();
?>
	<li class="planet-<?=htmlspecialchars($class)?><?=($planet == $active_planet) ? ' active' : ''?>"><?=($planet != $active_planet) ? '<a href="index.php?'.htmlspecialchars(global_setting("URL_SUFFIX")).'" tabindex="'.($tabindex++).'">' : ''?><?=htmlspecialchars($me->planetName())?><?=($planet != $active_planet) ? '</a>' : ''?> <span class="koords">(<?=htmlspecialchars($me->getPosString())?>)</span>
		<dl class="planet-info">
			<dt class="c-felder"><?=h(_("Felder"))?></dt>
			<dd class="c-felder"><?=ths($me->getUsedFields())?> <span class="gesamtgroesse">(<?=ths($me->getTotalFields())?>)</span></dd>
<?php
		if($show_building['gebaeude'])
		{
?>
			<dt class="c-gebaeudebau"><?=h(_("Gebäudebau"))?></dt>
<?php
			$building_gebaeude = $me->checkBuildingThing('gebaeude');
			if($building_gebaeude)
			{
				$item_info = $me->getItemInfo($building_gebaeude[0], 'gebaeude');
?>
			<dd class="c-gebaeudebau"><?=htmlspecialchars($item_info['name'])?> <span class="restbauzeit" id="restbauzeit-ge-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($building_gebaeude[1], $me))?></span></dd>
<?php
				$countdowns[] = array('ge-'.$planet, $building_gebaeude[1], h_root."/login/gebaeude.php?".global_setting("URL_SUFFIX"));
			}
			elseif($me->getRemainingFields() <= 0)
			{
?>
			<dd class="c-gebaeudebau ausgebaut"><?=h(_("Ausgebaut"))?></dd>
<?php
			}
			else
			{
?>
			<dd class="c-gebaeudebau gelangweilt"><?=h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['forschung'] && $me->getItemLevel('B8', 'gebaeude') > 0)
		{
?>

			<dt class="c-forschung"><?=h(_("Forschung"))?></dt>
<?php
			$building_forschung = $me->checkBuildingThing('forschung');
			if($building_forschung)
			{
				$item_info = $me->getItemInfo($building_forschung[0], 'forschung');
?>
			<dd class="c-forschung"><?=htmlspecialchars($item_info['name'])?> <span id="restbauzeit-fo-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($building_forschung[1], $me))?></span></dd>
<?php
				$countdowns[] = array('fo-'.$planet, $building_forschung[1], h_root."/login/forschung.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-forschung gelangweilt"><?=h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['roboter'] && $me->getItemLevel('B9', 'gebaeude') > 0)
		{
?>

			<dt class="c-roboter"><?=h(_("Roboter"))?></dt>
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
			<dd class="c-roboter">(<?=htmlspecialchars($item_info['name'])?>) <span id="restbauzeit-ro-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'roboter');
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-roboter"><?=htmlspecialchars($item_info['name'])?> <span class="anzahl">(<?=ths($first_building[2])?>)</span> <span id="restbauzeit-ro-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'roboter');
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-roboter"><?=htmlspecialchars($item_info['name'])?> <span id="restbauzeit-ro-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
				}
				$countdowns[] = array('ro-'.$planet, $finishing_time, h_root."/login/roboter.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-roboter gelangweilt"><?=h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['schiffe'] && $me->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-schiffe"><?=h(_("Schiffe"))?></dt>
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
			<dd class="c-schiffe">(<?=htmlspecialchars($item_info['name'])?>) <span id="restbauzeit-sc-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'schiffe');
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-schiffe"><?=htmlspecialchars($item_info['name'])?> <span class="anzahl">(<?=ths($first_building[2])?>)</span> <span id="restbauzeit-sc-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'schiffe');
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-schiffe"><?=htmlspecialchars($item_info['name'])?> <span id="restbauzeit-sc-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
				}
				$countdowns[] = array('sc-'.$planet, $finishing_time, h_root."/login/schiffswerft.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-schiffe gelangweilt"><?=h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['verteidigung'] && $me->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-verteidigung"><?=h(_("Verteidigung"))?></dt>
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
			<dd class="c-verteidigung">(<?=htmlspecialchars($item_info['name'])?>) <span id="restbauzeit-ve-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'verteidigung');
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-verteidigung"><?=htmlspecialchars($item_info['name'])?> <span class="anzahl">(<?=ths($first_building[2])?>)</span> <span id="restbauzeit-ve-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$item_info = $me->getItemInfo($first_building[0], 'verteidigung');
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-verteidigung"><?=htmlspecialchars($item_info['name'])?> <span id="restbauzeit-ve-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(format_ftime($finishing_time, $me))?></span></dd>
<?php
						break;
				}
				$countdowns[] = array('ve-'.$planet, $finishing_time, h_root."/login/verteidigung.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-verteidigung gelangweilt"><?=h(_("Gelangweilt"))?></dd>
<?php
			}
		}
?>
		</dl>
	</li>
<?php
	}

	$me->setActivePlanet($active_planet);
	define_url_suffix();
?>
</ol>
<?php
	if(count($countdowns) > 0 && !$me->umode())
	{
?>
<script type="text/javascript">
// <![CDATA[
<?php
		foreach($countdowns as $countdown)
		{
?>
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, false, false, '<?=$countdown[2]?>');
<?php
		}
?>
// ]]>
</script>
<?php
	}
?>
<h2 id="punkte"><?=h(_("Punkte"))?></h2>
<dl class="punkte">
	<dt class="c-gebaeude"><?=h(_("[scores_0]"))?></dt>
	<dd class="c-gebaeude"><?=ths($me->getScores(0))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(0))."</strong>")?></span>)</dd>

	<dt class="c-forschung"><?=h(_("[scores_1]"))?></dt>
	<dd class="c-forschung"><?=ths($me->getScores(1))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(1))."</strong>")?></span>)</dd>

	<dt class="c-roboter"><?=h(_("[scores_2]"))?></dt>
	<dd class="c-roboter"><?=ths($me->getScores(2))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(2))."</strong>")?></span>)</dd>

	<dt class="c-flotte"><?=h(_("[scores_3]"))?></dt>
	<dd class="c-flotte"><?=ths($me->getScores(3))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(3))."</strong>")?></span>)</dd>

	<dt class="c-verteidigung"><?=h(_("[scores_4]"))?></dt>
	<dd class="c-verteidigung"><?=ths($me->getScores(4))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(4))."</strong>")?></strong>)</span></dd>

	<dt class="c-flugerfahrung"><?=h(_("[scores_5]"))?></dt>
	<dd class="c-flugerfahrung"><?=ths($me->getScores(5))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(5))."</strong>")?></span>)</dd>

	<dt class="c-kampferfahrung"><?=h(_("[scores_6]"))?></dt>
	<dd class="c-kampferfahrung"><?=ths($me->getScores(6))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".ths($me->getRank(6))."</strong>")?></span>)</dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($me->getScores())?> <span class="platz">(<?=sprintf(h(_("Platz %s von %s")), "<strong>".ths($me->getRank())."</strong>", "<strong class=\"gesamt-spieler\">".ths(User::getUsersCount())."</strong>")?>)</span></dd>
</dl>
<?php
	$gui->end();
?>
