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
	 * Übersicht über Planeten, Flotten und Punkte
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	use \sua\Fleet;

	require('include.php');

	/*if(isset($_GET['cancel']))
	{
		# Flotte zurueckrufen

		$flotte = Classes::Fleet($_GET['cancel']);
		if($flotte->callBack($USER->getName()))
			delete_request();
	}

	$flotten = Fleet::visibleToUser($USER->getName());

	$GUI->setOption("notify", true);*/
	$GUI->init(); /*
?>
<ul id="planeten-umbenennen">
	<li><a href="info/rename.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Planeten umbenennen/aufgeben"), false)?>" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("&umbenennen[login/index.php|1]"))?>><?=L::h(_("&umbenennen[login/index.php|1]"))?></a></li>
</ul>
<?php
	$active_planet = $USER->getActivePlanet();
	if(count($flotten) > 0)
	{
?>
<h2><?=L::h(_("Flottenbewegungen"))?></h2>
<dl id="flotten">
<?php
		# Flotten sortieren
		$flotten_sorted = array();
		foreach($flotten as $flotte)
		{
			$fl = Classes::Fleet($flotte, false);
			$flotten_sorted[$flotte] = $fl->getNextArrival();
		}

		asort($flotten_sorted, SORT_NUMERIC);

		$countdowns = array();
		foreach($flotten_sorted as $flotte=>$next_arrival)
		{
			$fl = Classes::Fleet($flotte);
			$users = $fl->getUsersList();

			$USER_in_users = array_search($USER->getName(), $users);
			if($USER_in_users !== false)
			{
				$first_user = $USER->getName();
				unset($users[$USER_in_users]);
			}
			else $first_user = array_shift($users);

			$part1 = sprintf(h($USER_in_users !== false ? _("Ihre %sFlotte%s") : _("Eine %sFlotte%s")), "<span class=\"beschreibung schiffe\" title=\"".Item::makeItemsString($fl->getFleetList($first_user))."\">", "</span>");

			$part2 = "";
			if(count($users) > 0)
			{
				foreach($users as $i=>$user)
				{
					$from = $fl->from($user);
					if($i == 0)
						$this_message = _(" zusammen mit einer %sFlotte%s vom Planeten %s");
					elseif($i == count($users)-1)
						$this_message = _(" und einer %sFlotte%s vom Planeten %s");
					else
						$this_message = _(", einer %sFlotte%s vom Planeten %s");
					$part2 .= sprintf(h($this_message), "<span class=\"beschreibung schiffe\" title=\"".Item::makeItemsString($fl->getFleetList($user))."\">", "</span>", htmlspecialchars(F::formatPlanet($from, true)));
				}
			}

			$from = $fl->getLastTarget($first_user);
			$from_own = ($from->getOwner() == $USER->getName());
			$part3 = sprintf(h($from_own ? _("von Ihrem Planeten %s") : _("vom Planeten %s")), htmlspecialchars(F::formatPlanet($from, !$from_own, false)));

			$to = $fl->getCurrentTarget();
			$to_own = ($to->getOwner() == $USER->getName());
			$part4 = sprintf(h($to_own ? _("Ihren Planeten %s") : _("den Planeten %s")), htmlspecialchars(F::formatPlanet($to_pos, !$to_own, false)));

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
					$ress_string[] = sprintf(_("%s: %s"), _("[ress_".$id."]"), F::ths($anzahl));
			}
			foreach($ress[1] as $id=>$anzahl)
			{
				if($anzahl > 0)
					$ress_string[] = sprintf(_("%s: %s"), _("[item_".$id."]"), F::ths($anzahl));
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
						$ress_string[] = sprintf(_("%s: %s"), _("[ress_".$id."]"), F::ths($anzahl));
				}
				foreach($handel[1] as $id=>$anzahl)
				{
					if($anzahl > 0)
						$ress_string[] = sprintf(_("%s: %s"), _("[item_".$id."]"), F::ths($anzahl));
				}
				$string .= " ".sprintf(h(_("Es wird ein %sHandel%s durchgeführt werden.")), "<span class=\"beschreibung handel\" title=\"".implode(_(", "), $ress_string)."\">", "</span>");
			}

			$user_list = $fl->getUsersList();
			if(in_array($USER->getName(), $user_list))
			{
				$first_user = Classes::User(array_shift($user_list));
				$fleet_passwd = $first_user->getFleetPasswd($fl->getName());
				if($fleet_passwd !== null)
					$string .= " ".sprintf(h(_("Das Verbundflottenpasswort lautet %s.")), "<span class=\"flottenpasswd\">".htmlspecialchars($fleet_passwd)."</span>");
			}

			if($fl->getNeededSlots($USER->getName()) > 1)
				$string .= " <a href=\"info/flotten_actions.php?action=route&amp;id=".htmlspecialchars(urlencode($flotte))."&".htmlspecialchars(global_setting("URL_SUFFIX"))."\">".h(_("Route anzeigen"))."</a>";
?>
	<dt class="<?=($USER_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=htmlspecialchars($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug">
		<?=$string."\n"?>
<?php
			if($fl->getCurrentType() == 4 && !$fl->isFlyingBack() && $USER->permissionToAct() && $fl->getCurrentTarget()->getOwner() == $USER->getName())
			{
?>
		<div class="handel action"><a href="info/flotten_actions.php?action=handel&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Geben Sie dieser Flotte Ladung mit auf den Rückweg"))?>"><?=L::h(_("Handel"))?></a></div>
<?php
			}
			if($fl->getCurrentType() == 3 && !$fl->isFlyingBack() && array_search($USER->getName(), $fl->getUsersList()) === 0)
			{
?>
		<div class="buendnisangriff action"><a href="info/flotten_actions.php?action=buendnisangriff&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Erlauben Sie anderen Spielern, der Flotte eigene Schiffe beizusteuern."))?>"><?=L::h(_("Bündnisangriff"))?></a></div>
<?php
			}
?>
	</dt>
	<dd class="<?=($USER_in_users !== false) ? 'eigen' : 'fremd'?> type-<?=htmlspecialchars($fl->getCurrentType())?> <?=$fl->isFlyingBack() ? 'rueck' : 'hin'?>flug" id="restbauzeit-<?=htmlspecialchars($flotte)?>"><?=htmlspecialchars(F::formatFTime($next_arrival, $USER_in_users !== null ? $USER : null))?><?php if(!$fl->isFlyingBack() && ($USER_in_users !== false)){?> <a href="index.php?cancel=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="abbrechen"><?=L::h(_("Abbrechen"))?></a><?php }?></dd>
<?php
			$url = '';
			if($fl->isFlyingBack()) $url = global_setting("h_root").'/login/flotten.php?'.preg_replace("/((^|&)planet=)\d+/", "\${1}".$USER->getPlanetByPos($fl->getCurrentTarget()), global_setting("URL_SUFFIX"));
			if($USER_in_users === false || !$USER->umode())
				$countdowns[] = array($flotte, $next_arrival, ($fl->isFlyingBack() || ($USER_in_users === false)), $url);
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
<h2 id="planeten"><?=L::h(_("Planeten"))?></h2>
<ol id="planets">
<?php
	$USER->setActivePlanet($active_planet);
	$show_building = $USER->checkSetting('show_building');
	$countdowns = array();
	$planets = $USER->getPlanetsList();
	foreach($planets as $planet)
	{
		$USER->setActivePlanet($planet);
		$class = $USER->getPlanetClass();

		define_url_suffix();
?>
	<li class="planet-<?=htmlspecialchars($class)?><?=($planet == $active_planet) ? ' active' : ''?>"><?=($planet != $active_planet) ? '<a href="index.php?'.htmlspecialchars(global_setting("URL_SUFFIX")).'" tabindex="'.($tabindex++).'">' : ''?><?=htmlspecialchars($USER->planetName())?><?=($planet != $active_planet) ? '</a>' : ''?> <span class="koords">(<?=htmlspecialchars($USER->getPosString())?>)</span>
		<dl class="planet-info">
			<dt class="c-felder"><?=L::h(_("Felder"))?></dt>
			<dd class="c-felder"><?=F::ths($PLANET->getUsedFields())?> <span class="gesamtgroesse">(<?=F::ths($PLANET->getSize())?>)</span></dd>
<?php
		if($show_building['gebaeude'])
		{
?>
			<dt class="c-gebaeudebau"><?=L::h(_("Gebäudebau"))?></dt>
<?php
			$building_gebaeude = $USER->checkBuildingThing('gebaeude');
			if($building_gebaeude)
			{
?>
			<dd class="c-gebaeudebau"><?=L::h(_("[item_".$building_gebaeude[0]."]"))?> <span class="restbauzeit" id="restbauzeit-ge-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($building_gebaeude[1], $USER))?></span></dd>
<?php
				$countdowns[] = array('ge-'.$planet, $building_gebaeude[1], global_setting("h_root")."/login/gebaeude.php?".global_setting("URL_SUFFIX"));
			}
			elseif($PLANET->getRemainingFields() <= 0)
			{
?>
			<dd class="c-gebaeudebau ausgebaut"><?=L::h(_("Ausgebaut"))?></dd>
<?php
			}
			else
			{
?>
			<dd class="c-gebaeudebau gelangweilt"><?=L::h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['forschung'] && $USER->getItemLevel('B8', 'gebaeude') > 0)
		{
?>

			<dt class="c-forschung"><?=L::h(_("Forschung"))?></dt>
<?php
			$building_forschung = $USER->checkBuildingThing('forschung');
			if($building_forschung)
			{
?>
			<dd class="c-forschung"><?=L::h(_("[item_".$building_forschung[0]."]"))?> <span id="restbauzeit-fo-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($building_forschung[1], $USER))?></span></dd>
<?php
				$countdowns[] = array('fo-'.$planet, $building_forschung[1], global_setting("h_root")."/login/forschung.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-forschung gelangweilt"><?=L::h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['roboter'] && $USER->getItemLevel('B9', 'gebaeude') > 0)
		{
?>

			<dt class="c-roboter"><?=L::h(_("Roboter"))?></dt>
<?php
			$building = $USER->checkBuildingThing('roboter');
			if($building)
			{
				switch($show_building['roboter'])
				{
					case 3:
						$last_building = array_pop($building);
						$finishing_time = $last_building[1]+$last_building[2]*$last_building[3];
?>
			<dd class="c-roboter">(<?=L::h(_("[item_".$last_building[0]."]"))?>) <span id="restbauzeit-ro-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-roboter"><?=L::h(_("[item_".$first_building[0]."]"))?> <span class="anzahl">(<?=F::ths($first_building[2])?>)</span> <span id="restbauzeit-ro-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-roboter"><?=L::h(_("[item_".$first_building[0]."]"))?> <span id="restbauzeit-ro-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
				}
				$countdowns[] = array('ro-'.$planet, $finishing_time, global_setting("h_root")."/login/roboter.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-roboter gelangweilt"><?=L::h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['schiffe'] && $USER->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-schiffe"><?=L::h(_("Schiffe"))?></dt>
<?php
			$building = $USER->checkBuildingThing('schiffe');
			if($building)
			{
				switch($show_building['schiffe'])
				{
					case 3:
						$last_building = array_pop($building);
						$finishing_time = $last_building[1]+$last_building[2]*$last_building[3];
?>
			<dd class="c-schiffe">(<?=L::h(_("[item_".$last_building[0]."]"))?>) <span id="restbauzeit-sc-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-schiffe"><?=L::h(_("[item_".$first_building[0]."]"))?> <span class="anzahl">(<?=F::ths($first_building[2])?>)</span> <span id="restbauzeit-sc-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-schiffe"><?=L::h(_("[item_".$first_building[0]."]"))?> <span id="restbauzeit-sc-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
				}
				$countdowns[] = array('sc-'.$planet, $finishing_time, global_setting("h_root")."/login/schiffswerft.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-schiffe gelangweilt"><?=L::h(_("Gelangweilt"))?></dd>
<?php
			}
		}

		if($show_building['verteidigung'] && $USER->getItemLevel('B10', 'gebaeude') > 0)
		{
?>

			<dt class="c-verteidigung"><?=L::h(_("Verteidigung"))?></dt>
<?php
			$building = $USER->checkBuildingThing('verteidigung');
			if($building)
			{
				switch($show_building['verteidigung'])
				{
					case 3:
						$last_building = array_pop($building);
						$finishing_time = $last_building[1]+$last_building[2]*$last_building[3];
?>
			<dd class="c-verteidigung">(<?=L::h(_("[item_".$last_building[0]."]"))?>) <span id="restbauzeit-ve-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
					case 2:
						$first_building = array_shift($building);
						$finishing_time = $first_building[1]+$first_building[2]*$first_building[3];
?>
			<dd class="c-verteidigung"><?=L::h(_("[item_".$first_building[0]."]"))?> <span class="anzahl">(<?=F::ths($first_building[2])?>)</span> <span id="restbauzeit-ve-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
					case 1:
						$first_building = array_shift($building);
						$finishing_time = $first_building[1]+$first_building[3];
?>
			<dd class="c-verteidigung"><?=L::h(_("[item_".$first_building[0]."]"))?> <span id="restbauzeit-ve-<?=htmlspecialchars($planet)?>"><?=htmlspecialchars(F::formatFTime($finishing_time, $USER))?></span></dd>
<?php
						break;
				}
				$countdowns[] = array('ve-'.$planet, $finishing_time, global_setting("h_root")."/login/verteidigung.php?".global_setting("URL_SUFFIX"));
			}
			else
			{
?>
			<dd class="c-verteidigung gelangweilt"><?=L::h(_("Gelangweilt"))?></dd>
<?php
			}
		}
?>
		</dl>
	</li>
<?php
	}

	$USER->setActivePlanet($active_planet);
	define_url_suffix();
?>
</ol>
<?php
	if(count($countdowns) > 0 && !$USER->umode())
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
<h2 id="punkte"><?=L::h(_("Punkte"))?></h2>
<dl class="punkte">
	<dt class="c-gebaeude"><?=L::h(_("[scores_0]"))?></dt>
	<dd class="c-gebaeude"><?=F::ths($USER->getScores(0))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(0))."</strong>")?></span>)</dd>

	<dt class="c-forschung"><?=L::h(_("[scores_1]"))?></dt>
	<dd class="c-forschung"><?=F::ths($USER->getScores(1))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(1))."</strong>")?></span>)</dd>

	<dt class="c-roboter"><?=L::h(_("[scores_2]"))?></dt>
	<dd class="c-roboter"><?=F::ths($USER->getScores(2))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(2))."</strong>")?></span>)</dd>

	<dt class="c-flotte"><?=L::h(_("[scores_3]"))?></dt>
	<dd class="c-flotte"><?=F::ths($USER->getScores(3))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(3))."</strong>")?></span>)</dd>

	<dt class="c-verteidigung"><?=L::h(_("[scores_4]"))?></dt>
	<dd class="c-verteidigung"><?=F::ths($USER->getScores(4))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(4))."</strong>")?></strong>)</span></dd>

	<dt class="c-flugerfahrung"><?=L::h(_("[scores_5]"))?></dt>
	<dd class="c-flugerfahrung"><?=F::ths($USER->getScores(5))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(5))."</strong>")?></span>)</dd>

	<dt class="c-kampferfahrung"><?=L::h(_("[scores_6]"))?></dt>
	<dd class="c-kampferfahrung"><?=F::ths($USER->getScores(6))?> <span class="platz">(<?=sprintf(h(_("Platz %s")), "<strong>".F::ths($USER->getRank(6))."</strong>")?></span>)</dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=F::ths($USER->getScores())?> <span class="platz">(<?=sprintf(h(_("Platz %s von %s")), "<strong>".F::ths($USER->getRank())."</strong>", "<strong class=\"gesamt-spieler\">".F::ths(User::getNumber())."</strong>")?>)</span></dd>
</dl>
<?php */
	$GUI->end();