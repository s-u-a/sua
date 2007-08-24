<?php
	if(isset($_GET['action'])) define('ignore_action', true);
	require_once('scripts/include.php');
	if(!defined('ajax')) login_gui::html_head();

	$show_versenden = true;

	$max_flotten = $me->getMaxParallelFleets();
	$my_flotten = $me->getCurrentParallelFleets();

	__autoload('Fleet');
	__autoload('Galaxy');

	if(!defined('ajax'))
	{
?>
<h2><?=h(_("Flotten"))?></h2>
<?php
	}

	$fast_action = false;
	if(isset($_GET['action_galaxy']) && isset($_GET['action_system']) && isset($_GET['action_planet']) && isset($_GET['action']) && ($_GET['action'] == 'spionage' || $_GET['action'] == 'besiedeln' || $_GET['action'] == 'sammeln' || $_GET['action'] == 'shortcut'))
	{
		if($_GET['action'] == 'shortcut')
		{
			$result = $me->addPosShortcut($_GET['action_galaxy'].':'.$_GET['action_system'].':'.$_GET['action_planet']);
			if($result === 2) $return = array('nothingtodo', _('Dieser Planet ist schon in Ihren Lesezeichen.'));
			elseif($result) $return = array('successful', _('Der Planet wurde zu den Lesezeichen hinzugefügt.'));
			else $return = array('error', _('Datenbankfehler.'));

			if(defined('ajax')) return $return;
			elseif($return[0] == 'error')
			{
?>
<p class="<?=htmlspecialchars($return[0])?>"><?=htmlspecialchars($return[1])?></p>
<?php
				login_gui::html_foot();
				exit();
			}
			else
			{
				header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
				ob_end_clean();
				die();
			}
		}

		$fast_action = true;

		$galaxy = Classes::Galaxy($_GET['action_galaxy']);
		$planet_owner = $galaxy->getPlanetOwner($_GET['action_system'], $_GET['action_planet']);
		if($my_flotten >= $max_flotten)
		{
			if(defined('ajax'))
				return array('error', _('Maximale Flottenzahl erreicht.'));
?>
<p class="error"><?=h(_("Maximale Flottenzahl erreicht."))?></p>
<?php
			login_gui::html_foot();
			exit();
		}
		else
		{
			$_POST['galaxie'] = $_GET['action_galaxy'];
			$_POST['system'] = $_GET['action_system'];
			$_POST['planet'] = $_GET['action_planet'];

			$_POST['speed'] = 1;

			if($_GET['action'] == 'spionage')
			{
				$_POST['auftrag'] = 5;
				$_POST['flotte'] = array('S5' => 1);
				if($planet_owner && !$me->isVerbuendet($planet_owner))
					$_POST['flotte']['S5'] = $me->checkSetting('sonden');
				if($me->getItemLevel('S5', 'schiffe') < 1)
				{
					if(defined('ajax')) return array('error', _('Keine Spionagesonden vorhanden.'));
?>
<p class="error"><?=h(_("Keine Spionagesonden vorhanden."))?></p>
<?php
					login_gui::html_foot();
					exit();
				}
			}
			elseif($_GET['action'] == 'besiedeln')
			{
				$_POST['auftrag'] = 1;
				$_POST['flotte'] = array('S6' => 1);
				if($me->getItemLevel('S6', 'schiffe') < 1)
				{
					if(defined('ajax')) return array('error', _('Kein Besiedelungsschiff vorhanden.'));
?>
<p class="error"><?=h(_("Kein Besiedelungsschiff vorhanden."))?></p>
<?php
					login_gui::html_foot();
					exit();
				}
			}
			elseif($_GET['action'] == 'sammeln')
			{
				$_POST['auftrag'] = 2;

				$truemmerfeld = truemmerfeld::get($_GET['action_galaxy'], $_GET['action_system'], $_GET['action_planet']);

				$anzahl = 0;
				if($truemmerfeld !== false)
				{
					# Transportkapazitaet eines Sammlers
					$sammler_info = $me->getItemInfo('S3', 'schiffe');
					$transport = $sammler_info['trans'][0];

					$anzahl = ceil(array_sum($truemmerfeld)/$transport);
				}
				if($anzahl <= 0)
					$anzahl = 1;

				$_POST['flotte'] = array('S3' => $anzahl);

				if($me->getItemLevel('S3', 'schiffe') < 1)
				{
					if(defined('ajax')) return array('error', _('Keine Sammler vorhanden.'));
?>
<p class="error"><?=h(_("Keine Sammler vorhanden."))?></p>
<?php
				}
			}
		}
	}

	$buendnisflug = (isset($_POST["buendnisflug"]) && $_POST["buendnisflug"]);

	if($me->permissionToAct() && $my_flotten < $max_flotten && isset($_POST['flotte']) && is_array($_POST['flotte']) && ((!$buendnisflug && isset($_POST['galaxie']) && isset($_POST['system']) && isset($_POST['planet'])) || ($buendnisflug && isset($_POST["buendnis_benutzername"]) && isset($_POST["buendnis_flottenpasswort"]))))
	{
		$types = array();
		foreach($_POST['flotte'] as $id=>$anzahl)
		{
			$_POST['flotte'][$id] = $anzahl = (int) $anzahl;
			$item_info = $me->getItemInfo($id, 'schiffe');
			if(!$item_info)
			{
				unset($_POST['flotte'][$id]);
				continue;
			}
			if($anzahl > $item_info['level'])
				$_POST['flotte'][$id] = $anzahl = $item_info['level'];
			if($anzahl < 1)
			{
				unset($_POST['flotte'][$id]);
				continue;
			}
			$show_versenden = false;

			foreach($item_info['types'] as $type)
			{
				if(!in_array($type, $types)) $types[] = $type;
			}
		}

		if($buendnisflug)
		{
			$buendnisflug_user = Classes::User($_POST["buendnis_benutzername"]);
			if(!$buendnisflug_user->getStatus() || $buendnisflug_user->getName() == $me->getName())
				$show_versenden = true;
			else
			{
				$buendnisflug_id = $buendnisflug_user->resolveFleetPasswd($_POST["buendnis_flottenpasswort"]);
				if($buendnisflug_id === null)
					$show_versenden = true;
				else
				{
					$buendnisflug_fleet = Classes::Fleet($buendnisflug_id);
					if(!$buendnisflug_fleet->getStatus() || in_array($me->getName(), $buendnisflug_fleet->getUsersList()))
						$show_versenden = true;
				}
			}
		}
		elseif(!preg_match("/^[1-9]([0-9]*)$/", $_POST['galaxie']) || !preg_match("/^[1-9]([0-9]*)$/", $_POST['system']) || !preg_match("/^[1-9]([0-9]*)$/", $_POST['planet']))
			$show_versenden = true;

		if(!$show_versenden)
		{
			if($buendnisflug)
				$target_koords = explode(":", $buendnisflug_fleet->getCurrentTarget());
			else
				$target_koords = array($_POST["galaxie"], $_POST["system"], $_POST["planet"]);

			$galaxy_obj = Classes::Galaxy($target_koords[0]);
			$planet_owner = $galaxy_obj->getPlanetOwner($target_koords[1], $target_koords[2]);
			$planet_owner_flag = $galaxy_obj->getPlanetOwnerFlag($target_koords[1], $target_koords[2]);

			if($planet_owner === false) $show_versenden = true;

			if(!$show_versenden)
			{
				sort($types, SORT_NUMERIC);

				$types = array_flip($types);
				if($planet_owner && isset($types[1])) # Planet besetzt, Besiedeln nicht moeglich
					unset($types[1]);
				if(!$planet_owner) # Planet nicht besetzt
				{
					if(isset($types[3])) # Angriff nicht moeglich
						unset($types[3]);
					if(isset($types[4])) # Transport nicht moeglich
						unset($types[4]);
					if(isset($types[6])) # Stationieren nicht moeglich
						unset($types[6]);

					if(!$me->checkPlanetCount()) # Planetenlimit erreicht, Besiedeln nicht moeglich
						unset($types[1]);
				}

				$truemmerfeld = truemmerfeld::get($target_koords[0], $target_koords[1], $target_koords[2]);
				if(($truemmerfeld === false || array_sum($truemmerfeld) <= 0) && isset($types[2]))
					unset($types[2]); # Kein Truemmerfeld, Sammeln nicht moeglich

				if($me->getPosString() == implode(":", $target_koords) || $planet_owner_flag == 'U')
				{ # Selber Planet / Urlaubsmodus, nur Sammeln
					if($truemmerfeld && isset($types[2]))
						$types = array(2 => 0);
					else
						$types = array();
				}
				elseif($planet_owner == $_SESSION['username'])
				{ # Eigener Planet
					if(isset($types[3])) # Angriff nicht moeglich
						unset($types[3]);
					if(isset($types[5])) # Spionage nicht moeglich
						unset($types[5]);
				}
				else
				{ # Fremder Planet
					if(!$me->isVerbuendet($planet_owner) && isset($types[6])) # Fremdstationierung nur bei Verbuendeten
						unset($types[6]);
					if($me->isVerbuendet($planet_owner) && isset($types[3])) # Verbuendet, Angriff nicht moeglich
						unset($types[3]);
				}

				if(fleets_locked()) # Flottensperre
				{
					if($planet_owner && !$me->isVerbuendet($planet_owner) && isset($types[5])) # Feindliche Spionage nicht moeglich
						unset($types[5]);
					if(isset($types[3])) # Angriff nicht erlaubt
						unset($types[3]);
				}

				if(count($types) <= 0)
				{
					if(defined('ajax')) return array('error', _('Diese Aktion ist auf diesen Planeten nicht möglich.'));
?>
<p class="error"><?=h(_("Sie haben nicht die richtigen Schiffe ausgewählt, um diesen Planeten anzufliegen."))?></p>
<?php
				}
				elseif($buendnisflug && time()+Fleet::calcFleetTime($me->getName(), $me->getPosString(), $buendnisflug_fleet->getCurrentTarget(), $_POST["flotte"]) > $buendnisflug_fleet->getNextArrival())
				{
?>
<p class="error"><?=h(_("Ihre Flotte kann nicht schnell genug fliegen um gleichzeitig mit dem Flottenverbund am Ziel anzukommen."))?></p>
<?php
				}
				else
				{
					$types = array_flip($types);

					if(!$buendnisflug)
					{
						# Transportkapazitaet und Antriebsstaerke berechnen
						$speed = 0;
						$transport = array(0, 0);
						$ges_count = 0;
						foreach($_POST['flotte'] as $id=>$anzahl)
						{
							$item_info = $me->getItemInfo($id);
							if($speed == 0 || ($item_info['speed'] != 0 && $item_info['speed'] < $speed))
								$speed = $item_info['speed'];

							$transport[0] += $item_info['trans'][0]*$anzahl;
							$transport[1] += $item_info['trans'][1]*$anzahl;
							$ges_count += $anzahl;
						}
						$show_form2 = true;
					}

					if(isset($_POST['auftrag']) || $buendnisflug)
					{
						$show_form2 = false;

						if($buendnisflug)
							$auftrag = $buendnisflug_fleet->getCurrentType();
						else
							$auftrag = $_POST["auftrag"];

						if(!$buendnisflug && !in_array($_POST['auftrag'], $types))
							$show_form2 = true;
						else
						{
							$that_user = Classes::User($planet_owner);

							$noob = false;
							if($planet_owner && (($auftrag == 3 || $auftrag == 5) && !$that_user->userLocked() || $auftrag == 6 && $planet_owner != $me->getName()) && !file_exists(global_setting("DB_NONOOBS")))
							{
								# Anfaengerschutz ueberpruefen
								$that_punkte = $that_user->getScores();
								$this_punkte = $me->getScores();

								if($that_punkte > $this_punkte && $that_punkte*0.05 > $this_punkte)
								{
									if(defined('ajax')) return array('error', _('Dieser Spieler ist zu stark für Sie.'));
?>
<p class="error"><?=h(_("Das Imperium dieses Spielers ist so groß, dass Ihre Sensoren beim Versuch, einen Anflugspunkt auszumachen, durcheinanderkommen. (Aka Anfängerschutz.)"))?></p>
<?php
									$noob = true;
								}
								elseif($that_punkte < $this_punkte && $that_punkte < $this_punkte*0.05)
								{
									if(defined('ajax')) return array('error', _('Dieser Spieler ist zu schwach für Sie.'));
?>
<p class="error"><?=h(_("Dieser Spieler ist noch so klein, dass Ihre Sensoren das Ziel nicht ausmachen und deshalb den Flugkurs nicht berechnen können. (Aka Anfängerschutz.)"))?></p>
<?php
									$noob = true;
								}
							}

							if(!$noob)
							{
								if(!$buendnisflug)
								{
									$fleet_obj = Classes::Fleet();
									$fleet_obj->create();
								}
								else
									$fleet_obj = $buendnisflug_fleet;

								if($fleet_obj->getStatus())
								{
									if(!$buendnisflug)
									{
										# Geschwindigkeitsfaktor
										if(!isset($_POST['speed']) || $_POST['speed'] < 0.05 || $_POST['speed'] > 1)
											$_POST['speed'] = 1;

										$fleet_obj->addTarget($_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'], $_POST['auftrag'], false);
										if($_POST['auftrag'] != 6)
											$fleet_obj->addTarget($me->getPosString(), $_POST['auftrag'], true);
									}

									if($buendnisflug)
										$fleet_obj->addUser($_SESSION['username'], $me->getPosString());
									else
										$fleet_obj->addUser($_SESSION['username'], $me->getPosString(), $_POST['speed']);

									foreach($_POST['flotte'] as $id=>$anzahl)
										$fleet_obj->addFleet($id, $anzahl, $_SESSION['username']);

									$ress = $me->getRess();
									if(($auftrag == 1 || $auftrag == 4 || $auftrag == 6))
									{
										if(!isset($_POST['transport'])) $_POST['transport'] = array(0,0,0,0,0);
										if(!isset($_POST['rtransport'])) $_POST['rtransport'] = array();
										if($_POST['transport'][0] > $ress[0]) $_POST['transport'][0] = $ress[0];
										if($_POST['transport'][1] > $ress[1]) $_POST['transport'][1] = $ress[1];
										if($_POST['transport'][2] > $ress[2]) $_POST['transport'][2] = $ress[2];
										if($_POST['transport'][3] > $ress[3]) $_POST['transport'][3] = $ress[3];
										if($_POST['transport'][4] > $ress[4]) $_POST['transport'][4] = $ress[4];

										foreach($_POST['rtransport'] as $id=>$anzahl)
										{
											if($anzahl > $me->getItemLevel($id, 'roboter'))
												$_POST['rtransport'][$id] = $me->getItemLevel($id, 'roboter');
										}
										if($planet_owner == $_SESSION['username'])
											$fleet_obj->addTransport($_SESSION['username'], $_POST['transport'], $_POST['rtransport']);
										else $fleet_obj->addTransport($_SESSION['username'], $_POST['transport']);
										list($_POST['transport'], $_POST['rtransport']) = $fleet_obj->getTransport($_SESSION['username']);
									}
									else
									{
										$_POST['transport'] = array(0,0,0,0,0);
										$_POST['rtransport'] = array();
									}

									$tritium = $fleet_obj->calcNeededTritium($_SESSION['username']);

									if($ress[4]-$_POST['transport'][4] < $tritium)
									{
										if(defined('ajax')) return array('error', _('Nicht genug Tritium vorhanden.'));
?>
<p class="error"><?=h("Nicht genug Tritium vorhanden.")?></p>
<?php
									}
									else
									{
										$me->subtractRess(array(0, 0, 0, 0, $tritium));

										# Flotten abziehen
										foreach($_POST['flotte'] as $id=>$anzahl)
											$me->changeItemLevel($id, -$anzahl, 'schiffe');

										# Rohstoffe abziehen
										$me->subtractRess($_POST['transport'], false);

										# Roboter abziehen
										foreach($_POST['rtransport'] as $id=>$anzahl)
											$me->changeItemLevel($id, -$anzahl, 'roboter');

										$fleet_obj->start();

										if(defined('ajax')) return array('successful', _('Die Flotte wurde versandt.'));
										elseif($fast_action)
										{
											header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
											ob_end_clean();
											die();
										}
										else
										{
?>
<div class="flotte-versandt">
	<p><?=h(_("Die Flotte wurde versandt."))?></p>
	<dl>
		<dt class="c-ziel"><?=h(_("Ziel"))?></dt>
		<dd class="c-ziel"><?=h($planet_owner ? sprintf(_("„%s“ (%s, Eigentümer: %s)"), htmlspecialchars($galaxy_obj->getPlanetName($_POST['system'], $_POST['planet'])), vsprintf(_("%d:%d:%d"), explode(":", $fleet_obj->getCurrentTarget())), htmlspecialchars($planet_owner)) : sprintf(_("Unbesiedelt (%s)"), vsprintf(_("%d:%d:%d"), explode(":", $fleet_obj->getCurrentTarget()))))?></dd>

		<dt class="c-auftragsart"><?=h(_("Auftragsart"))?></dt>
		<dd class="c-auftragsart"><?=isset($type_names[$auftrag]) ? htmlspecialchars($type_names[$auftrag]) : htmlspecialchars($auftrag)?></dt>

		<dt class="c-ankunft"><?=h(_("Ankunft"))?></dt>
		<dd class="c-ankunft"><?=h(sprintf("%s (Serverzeit)", date(_("H:i:s, Y-m-d"), $fleet_obj->getNextArrival())))?></dd>
	</dl>
</div>
<?php
										}
									}
								}
							}
						}
					}

					if($show_form2)
					{
						if(defined('ajax')) return array('error', _('Ungültige Aktion.'));
						elseif($fast_action)
						{
							header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
							ob_end_clean();
							die();
						}

						$distance = Fleet::getDistance($me->getPosString(), $_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet']);
						$fleet_obj = Classes::Fleet();
						if($fleet_obj->create())
						{
							$fleet_obj->addUser($_SESSION['username'], $me->getPosString());
							$fleet_obj->addTarget($_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'], 0, false);
							foreach($_POST['flotte'] as $id=>$anzahl)
								$fleet_obj->addFleet($id, $anzahl, $_SESSION['username']);
							$time = $fleet_obj->getNextArrival()-time();
							$tritium = $fleet_obj->calcNeededTritium($_SESSION['username']);
							$time_string = '';
							if($time >= 86400)
							{
								$time_string .= floor($time/86400).h(_("[unit_separator]")).'<abbr title="'.h(_("Tage")).'">d</abbr>';
								$time2 = $time%86400;
							}
							else
								$time2 = $time;
							$time_string .= add_nulls(floor($time2/3600), 2).':'.add_nulls(floor(($time2%3600)/60), 2).':'.add_nulls(($time2%60), 2);

							$this_ress = $me->getRess();
							$transport = $fleet_obj->getTransportCapacity($_SESSION['username']);

							# Kein Robotertransport zu fremden Planeten
							if($planet_owner != $_SESSION['username']) $transport[1] = 0;
?>
<form action="flotten.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="flotte-versenden-2" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'Doppelklickschutz: Sie haben ein zweites Mal auf \u201eAbsenden\u201c geklickt. Dadurch wird Ihre Flotte auch zweimal abgesandt (sofern die nötigen Schiffe verfügbar sind). Sind Sie sicher, dass Sie diese Aktion durchführen wollen?\');');">
	<dl>
		<dt class="c-ziel"><?=h(_("Ziel"))?></dt>
		<dd class="c-ziel"><?=sprintf(h(_("%d:%d:%d")), $_POST['galaxie'], $_POST['system'], $_POST['planet'])?> &ndash; <?=$planet_owner ? htmlspecialchars($galaxy_obj->getPlanetName($_POST['system'], $_POST['planet'])).' <span class="playername">('.htmlspecialchars($planet_owner).')</span>' : 'Unbesiedelt'?></dd>

		<dt class="c-entfernung"><?=h(_("Entfernung"))?></dt>
		<dd class="c-entfernung"><?=ths($distance)?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Orbit", "Orbits", $distance))?>"><?=h(_("Or"))?></abbr></dd>

		<dt class="c-antrieb"><?=h(_("Antrieb"))?></dt>
		<dd class="c-antrieb"><?=ths($speed)?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Mikroorbit pro Quadratsekunde", "Mikroorbits pro Quadratsekunde", $speed))?>"><?=h(_("µOr⁄s²"))?></abbr></dd>

		<dt class="c-tritiumverbrauch"><?=h(_("Tritiumverbrauch"))?></dt>
		<dd class="c-tritiumverbrauch <?=($this_ress[4] >= $tritium) ? 'ja' : 'nein'?>" id="tritium-verbrauch"><?=ths($tritium)?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Tonne", "Tonnen", $tritium))?>">t</abbr></dd>

		<dt class="c-geschwindigkeit"><label for="speed"><?=h(_("Gesch&windigkeit[login/flotten.php|1]"))?></label></dt>
		<dd class="c-geschwindigkeit">
			<select name="speed" id="speed"<?=accesskey_attr(_("Gesch&windigkeit[login/flotten.php|1]"))?> tabindex="1" onchange="recalc_values();" onkeyup="recalc_values();">
<?php
							for($i=1,$pr=100; $i>0; $i-=.05,$pr-=5)
							{
?>
				<option value="<?=htmlspecialchars($i)?>"><?=htmlspecialchars($pr)?><?=h(_("[unit_separator]"))?>%</option>
<?php
							}
?>
			</select>
		</dd>

		<dt class="c-flugzeit"><?=h(_("Flugzeit"))?></dt>
		<dd class="c-flugzeit" id="flugzeit" title="<?=h(sprintf(_("Ankunft: %s"), sprintf(_("%s (Serverzeit)"), date(_("H:i:s, Y-m-d"), time()+$time))))?>"><?=$time_string?></dd>

		<dt class="c-transportkapazitaet"><?=h(_("Transportkapazität"))?></dt>
		<dd class="c-transportkapazitaet"><?=ths($transport[0])?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Tonne", "Tonnen", $transport[0]))?>">t</abbr>, <?=ths($transport[1])?>&nbsp;<?=h(ngettext("Roboter", "Roboter", $transport[1]))?></dd>

		<script type="text/javascript">
			// <![CDATA[
			document.write('<dt class="c-verbleibend" id="transport-verbleibend-dt"><?=h(_("Verbleibend"))?></dt>');
			document.write('<dd class="c-verbleibend" id="transport-verbleibend-dd"><?=ths($transport[0])?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Tonne", "Tonnen", $transport[0]))?>">t</abbr>, <?=ths($transport[1])?>&nbsp;<?=h(ngettext("Roboter", "Roboter", $transport[1]))?></dd>');
			// ]]>
		</script>

		<dt class="c-auftrag"><label for="auftrag"><?=h(_("A&uftrag[login/flotten.php|1]"))?></label></dt>
		<dd class="c-auftrag">
			<select name="auftrag" id="auftrag"<?=accesskey_attr(_("A&uftrag[login/flotten.php|1]"))?> tabindex="2" onchange="recalc_values();" onkeyup="recalc_values();">
<?php
							foreach($types as $type)
							{
?>
				<option value="<?=htmlspecialchars($type)?>"><?=isset($type_names[$type]) ? htmlspecialchars($type_names[$type]) : $type?></option>
<?php
							}
?>
			</select>
		</dd>
<?php
							if($transport[0] > 0 || $transport[1] > 0)
							{
?>

		<dt class="c-transport" id="transport-dt"><?=h(_("Transport"))?></dt>
		<dd class="c-transport" id="transport-dd">
			<dl>
<?php
								if($transport[0] > 0)
								{
?>
				<dt><label for="transport-carbon"><?=h(_("Carbo&n[login/flotten.php|1]"))?></label></dt>
				<dd><input type="text" name="transport[0]" id="transport-carbon" value="0" onchange="recalc_values();"<?=accesskey_attr(_("Carbo&n[login/flotten.php|1]"))?> tabindex="3" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-aluminium"><?=h(_("Aluminium&[login/flotten.php|1]"))?></label></dt>
				<dd><input type="text" name="transport[1]" id="transport-aluminium" value="0" onchange="recalc_values();"<?=accesskey_attr(_("Aluminium&[login/flotten.php|1]"))?> tabindex="4" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-wolfram"><?=h(_("Wolfram&[login/flotten.php|1]"))?></label></dt>
				<dd><input type="text" name="transport[2]" id="transport-wolfram" value="0" onchange="recalc_values();"<?=accesskey_attr(_("Wolfram&[login/flotten.php|1]"))?> tabindex="5" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-radium"><?=h(_("Radium&[login/flotten.php|1]"))?></label></dt>
				<dd><input type="text" name="transport[3]" id="transport-radium" value="0" onchange="recalc_values();"<?=accesskey_attr(_("Radium&[login/flotten.php|1]"))?> tabindex="6" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-tritium"><?=h(_("Tritium&[login/flotten.php|1]"))?></label></dt>
				<dd><input type="text" name="transport[4]" id="transport-tritium" value="0" onchange="recalc_values();"<?=accesskey_attr(_("Tritium&[login/flotten.php|1]"))?> tabindex="7" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>
<?php
								}
								if($transport[1] > 0)
								{
									if($transport[0] > 0)
										echo "\n";

									$tabindex = 8;
									foreach($me->getItemsList('roboter') as $rob)
									{
										$item_info = $me->getItemInfo($rob, 'roboter');
?>
				<dt><label for="rtransport-<?=htmlspecialchars($rob)?>"><?=htmlspecialchars($item_info['name'])?></label></dt>
				<dd><input type="text" name="rtransport[<?=htmlspecialchars($rob)?>]" id="rtransport-<?=htmlspecialchars($rob)?>" value="0" onchange="recalc_values();" tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>
<?php
									}
								}
?>
			</dl>
		</dd>
<?php
							}
?>
	</dl>
	<script type="text/javascript">
		// <![CDATA[
			function recalc_values()
			{
<?php
							if($transport[0] > 0 || $transport[1] > 0)
							{
?>
				// Transport
				var auftraege = new Array();
				auftraege[1] = true;
				auftraege[2] = false;
				auftraege[3] = false;
				auftraege[4] = true;
				auftraege[5] = false;
				auftraege[6] = true;

				var auftrag = document.getElementById('auftrag');
				var use_transport = auftraege[auftrag.options[auftrag.selectedIndex].value];
				document.getElementById('transport-dt').style.display = (use_transport ? 'block' : 'none');
				document.getElementById('transport-dd').style.display = (use_transport ? 'block' : 'none');
				document.getElementById('transport-verbleibend-dt').style.display = (use_transport ? 'block' : 'none');
				document.getElementById('transport-verbleibend-dd').style.display = (use_transport ? 'block' : 'none');
<?php
							}
?>

				// Tritiumverbrauch
				var speed_obj = document.getElementById('speed');
				var speed = parseFloat(speed_obj.options[speed_obj.selectedIndex].value);
				var tritium = <?=$tritium?>;
				if(!isNaN(speed))
					tritium = Math.floor(tritium*speed);
				document.getElementById('tritium-verbrauch').innerHTML = ths(tritium)+'<?=h(_("[unit_separator]"))?><abbr title="<?=h(_("Tonnen"))?>">t</abbr>';
				document.getElementById('tritium-verbrauch').className = 'c-tritiumverbrauch '+((<?=$this_ress[4]?> >= tritium) ? 'ja' : 'nein');

				// Flugzeit
				var time = <?=$time?>;
				if(!isNaN(speed))
					time /= speed;
				time = Math.round(time);
				if(time < <?=global_setting("MIN_BUILDING_TIME")?>)
					time = <?=global_setting("MIN_BUILDING_TIME")?>;

				var time_string = '';
				if(time >= 86400)
				{
					time_string += Math.floor(time/86400)+'<?=h(_("[unit_separator]"))?><abbr title="<?=h(_("Tage"))?>">d</abbr> ';
					var time2 = time%86400;
				}
				else
					var time2 = time;
				time_string += mk2(Math.floor(time2/3600))+':'+mk2(Math.floor((time2%3600)/60))+':'+mk2(Math.floor(time2%60));
				document.getElementById('flugzeit').innerHTML = time_string;

				var jetzt = new Date();
				var ankunft_server = new Date(jetzt.getTime()+(time*1000));
				var ankunft_server_server = new Date(ankunft_server.getTime()-time_diff);

				var attrName;
				if(document.getElementById('flugzeit').getAttribute('titleAttribute'))
					attrName = 'titleAttribute';
				else
					attrName = 'title';
				document.getElementById('flugzeit').setAttribute(attrName, '<?=sprintf(str_replace("'", "\\'", _("Ankunft: %s")), sprintf(str_replace("'", "\\'", _("%s (Lokalzeit)")), "'+mk2(ankunft_server.getHours())+':'+mk2(ankunft_server.getMinutes())+':'+mk2(ankunft_server.getSeconds())+', '+ankunft_server.getFullYear()+'-'+mk2(ankunft_server.getMonth()+1)+'-'+mk2(ankunft_server.getDate())+' (Lokalzeit); '+mk2(ankunft_server.getHours())+':'+mk2(ankunft_server.getMinutes())+':'+mk2(ankunft_server.getSeconds())+', '+ankunft_server.getFullYear()+'-'+mk2(ankunft_server.getMonth()+1)+'-'+mk2(ankunft_server.getDate())+'"))?>');
<?php
							if($transport[0] > 0 || $transport[1] > 0)
							{
?>

				// Verbleibendes Ladevermoegen
				if(use_transport)
				{
<?php
								if($transport[0] > 0)
								{
?>
					var ges_ress = myParseInt(document.getElementById('transport-carbon').value)+myParseInt(document.getElementById('transport-aluminium').value)+myParseInt(document.getElementById('transport-wolfram').value)+myParseInt(document.getElementById('transport-radium').value)+myParseInt(document.getElementById('transport-tritium').value);
<?php
								}
								else
								{
?>
					var ges_ress = 0;
<?php
								}
								if($transport[1] > 0)
								{
									$robs_arr = array();
									foreach($me->getItemsList('roboter') as $rob)
										$robs_arr[] = "myParseInt(document.getElementById('rtransport-".$rob."').value)";
?>
					var ges_rob = <?=implode('+', $robs_arr)?>;
<?php
								}
								else
								{
?>
					var ges_rob = 0;
<?php
								}
?>
					var remain_ress = <?=$transport[0]?>;
					if(!isNaN(ges_ress))
						remain_ress -= ges_ress;
					var remain_rob = <?=$transport[1]?>;
					if(!isNaN(ges_rob))
						remain_rob -= ges_rob;
					if(remain_ress < 0)
						remain_ress = "\u22120";
					else
						remain_ress = ths(remain_ress);
					if(remain_rob < 0)
						remain_rob = "\u22120";
					else
						remain_rob = ths(remain_rob);
					document.getElementById('transport-verbleibend-dd').innerHTML = remain_ress+'<?=h(_("[unit_separator]"))?><abbr title="<?=h(_("Tonnen"))?>">t</abbr>, '+remain_rob+'&nbsp;<?=h(_("Roboter"))?>';
				}
<?php
							}
?>
			}

			recalc_values();
		// ]]>
	</script>
	<div>
<?php
							foreach($_POST['flotte'] as $id=>$anzahl)
							{
?>
		<input type="hidden" name="flotte[<?=htmlspecialchars($id)?>]" value="<?=htmlspecialchars($anzahl)?>" />
<?php
							}
?>
		<input type="hidden" name="galaxie" value="<?=htmlspecialchars($_POST['galaxie'])?>" />
		<input type="hidden" name="system" value="<?=htmlspecialchars($_POST['system'])?>" />
		<input type="hidden" name="planet" value="<?=htmlspecialchars($_POST['planet'])?>" />
		<button type="submit"<?=accesskey_attr(_("Absen&den[login/flotten.php|1]"))?>><?=h("Absen&den[login/flotten.php|1]")?></button>
	</div>
</form>
<?php
						}
					}
				}
			}
		}
	}

	if($show_versenden)
	{
		if(defined('ajax')) return array('error', _('Ungültige Aktion.'));
		elseif($fast_action)
		{
			header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
			ob_end_clean();
			die();
		}
?>
<h3><?=h(_("Flotte versenden"))?></h3>
<p class="flotte-anzahl<?=($my_flotten >= $max_flotten) ? ' voll' : ''?>">
	<?=h(sprintf(ngettext("Sie haben derzeit %s von %s möglichen Flotte unterwegs.", "Sie haben derzeit %s von %s möglichen Flotten unterwegs.", $max_flotten), ths($my_flotten), ths($max_flotten)))?><br />
	<?=h(sprintf(_("Bauen Sie %s aus, um die maximale Anzahl zu erhöhen."), _("[item_F0_def_acc]")))."\n"?>
</p>
<?php
		$this_pos = $me->getPos();
		if(isset($_GET['action_galaxy'])) $this_pos[0] = $_GET['action_galaxy'];
		if(isset($_GET['action_system'])) $this_pos[1] = $_GET['action_system'];
		if(isset($_GET['action_planet'])) $this_pos[2] = $_GET['action_planet'];
?>
<form action="flotten.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="flotte-versenden">
<?php
		if($my_flotten < $max_flotten && $me->permissionToAct())
		{
?>
	<fieldset class="flotte-koords">
		<legend><input type="radio" name="buendnisflug" value="0"<?php if(!$buendnisflug){?> checked="checked"<?php }?> id="i-eigenes-ziel" /> <label for="i-eigenes-ziel">Eigenes Ziel</label></legend>
		<dl>
			<dt class="c-ziel"><label for="ziel-galaxie"><?=h(_("&Ziel[login/flotten.php|2]"))?></label></dt>
			<dd class="c-ziel"><input type="text" id="ziel-galaxie" name="galaxie" value="<?=htmlspecialchars($this_pos[0])?>" title="<?=h("Ziel: Galaxie")?>"<?=accesskey_attr(_("&Ziel[login/flotten.php|2]"))?> tabindex="1" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="<?=strlen(getGalaxiesCount())?>" />:<input type="text" id="ziel-system" name="system" value="<?=htmlspecialchars($this_pos[1])?>" title="<?=h(_("Ziel: System&[login/flotten.php|2]"), false)?>"<?=accesskey_attr(_("Ziel: System&[login/flotten.php|2]"))?> tabindex="2" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="3" />:<input type="text" id="ziel-planet" name="planet" value="<?=htmlspecialchars($this_pos[2])?>" title="<?=h(_("Ziel: Planet&[login/flotten.php|2]"), false)?>"<?=accesskey_attr(_("Ziel: Planet&[login/flotten.php|2]"))?> tabindex="3" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="2" /></dd>
			<script type="text/javascript">
				// <![CDATA[
					document.write('<dt class="c-planet"><label for="ziel-planet-wahl"><?=h(_("Pla&net[login/flotten.php|2]"))?></label></dt>');
					document.write('<dd class="c-planet">');
					document.write('<select id="ziel-planet-wahl"<?=accesskey_attr(_("Pla&net[login/flotten.php|2]"))?> tabindex="4" onchange="syncronise(false);" onkeyup="syncronise(false);">');
					document.write('<option value=""><?=h(_("Benutzerdefiniert"))?></option>');
<?php
				$shortcuts = $me->getPosShortcutsList();
				if(count($shortcuts) > 0)
				{
?>
					document.write('<optgroup label="<?=h(_("Lesezeichen"))?>">');
<?php
					foreach($shortcuts as $shortcut)
					{
						$s_pos = explode(':', $shortcut);
						$galaxy_obj = Classes::Galaxy($s_pos[0]);
						$owner = $galaxy_obj->getPlanetOwner($s_pos[1], $s_pos[2]);
						$s = $shortcut.': ';
						if($owner)
						{
							$s .= $galaxy_obj->getPlanetName($s_pos[1], $s_pos[2]).' (';
							$alliance = $galaxy_obj->getPlanetOwnerAlliance($s_pos[1], $s_pos[2]);
							if($alliance) $s .= '['.$alliance.'] ';
							$s .= $owner.')';
						}
						else $s .= "["._('unbesiedelt')."]";
?>
					document.write('<option value="<?=htmlspecialchars($shortcut)?>"><?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($s))?></option>');
<?php
					}
?>
					document.write('</optgroup>');
<?php
				}
?>
					document.write('<optgroup label="<?=h(_("Eigene Planeten"))?>">');
<?php
				$planets = $me->getPlanetsList();
				$active_planet = $me->getActivePlanet();
				foreach($planets as $planet)
				{
					$me->setActivePlanet($planet);
?>
					document.write('<option value="<?=htmlspecialchars($me->getPosString())?>"<?=($planet == $active_planet) ? ' selected="selected"' : ''?>><?=h(vsprintf(_("%d:%d:%d"), $me->getPos()))?>: <?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($me->planetName()))?></option>');
<?php
				}
				$me->setActivePlanet($active_planet);
?>
					document.write('</select>');
					document.write('</optgroup>');
<?php
				if(count($shortcuts) > 0)
				{
?>
					document.write('<a href="flotten_actions.php?action=shortcuts&amp;<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" class="lesezeichen-verwalten-link"<?=accesskey_attr(_("Lesezeichen verwalten&[login/flotten.php|2]"))?>>[<?=h(_("Lesezeichen verwalten&[login/flotten.php|2]"))?>]</a>');
<?php
				}
?>
					document.write('</dd>');

					function syncronise(input_select)
					{
						var select_obj = document.getElementById('ziel-planet-wahl');
						if(!input_select)
						{
							var pos = select_obj.options[select_obj.selectedIndex].value;
							if(pos != '')
							{
								pos = pos.split(/:/);
								document.getElementById('ziel-galaxie').value = pos[0];
								document.getElementById('ziel-system').value = pos[1];
								document.getElementById('ziel-planet').value = pos[2];
							}
						}
						else
						{
							var pos = new Array(3);
							pos[0] = document.getElementById('ziel-galaxie').value;
							pos[1] = document.getElementById('ziel-system').value;
							pos[2] = document.getElementById('ziel-planet').value;
							pos = pos.join(':');

							var one = false;
							for(var sindex=0; sindex<select_obj.options.length; sindex++)
							{
								if(pos == select_obj.options[sindex].value)
								{
									select_obj.selectedIndex = sindex;
									one = true;
									break;
								}
							}

							if(!one)
								select_obj.selectedIndex = 0;
						}
					}

					syncronise(true);
				// ]]>
			</script>
		</dl>
	</fieldset>
	<fieldset class="buendnisflug">
		<legend><input type="radio" name="buendnisflug" value="1"<?php if($buendnisflug){?> checked="checked"<?php }?> id="i-buendnisflug" /> <label for="i-buendnisflug"><?=h(_("Bündnisflug"))?></label></legend>
		<dl>
			<dt class="c-benutzername"><label for="i-buendnis-benutzername"><?=h(_("Benutzername&[login/flotten.php|2]"))?></label></dt>
			<dd class="c-benutzername"><input type="text" id="i-buendnis-benutzername"<?=accesskey_attr(_("Benutzername&[login/flotten.php|2]"))?> name="buendnis_benutzername"<?php if(isset($_POST["buendnis_benutzername"])){?> value="<?=htmlspecialchars($_POST["buendnis_benutzername"])?>"<?php }?> /></dd>

			<dt class="c-passwort"><label for="i-buendnis-flottenpasswort"><?=h(_("Flottenpasswort&[login/flotten.php|2]"))?></label></dt>
			<dd class="c-passwort"><input type="text" id="i-buendnis-flottenpasswort"<?=accesskey_attr(_("Flottenpasswort&[login/flotten.php|2]"))?> name="buendnis_flottenpasswort"<?php if(isset($_POST["buendnis_flottenpasswort"])){?> value="<?=htmlspecialchars($_POST["buendnis_flottenpasswort"])?>"<?php }?> /></dd>
		</dl>
	</fieldset>
	<script type="text/javascript">
		activate_users_list(document.getElementById("i-buendnis-benutzername"));
	</script>
<?php
		}
?>
	<fieldset class="flotte-schiffe">
		<legend><?=h(_("Schiffe"))?></legend>
		<dl>
<?php
		$i = 5;
		foreach($me->getItemsList('schiffe') as $id)
		{
			if($me->getItemLevel($id, 'schiffe') < 1) continue;
			$item_info = $me->getItemInfo($id, 'schiffe');
?>
			<dt><a href="help/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" title="<?=h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item_info['name'])?></a> <span class="vorhanden">(<?=h(sprintf(_("%s vorhanden"), ths($item_info['level'])))?>)</span></dt>
			<dd><input type="text" name="flotte[<?=htmlspecialchars($id)?>]" value="0" tabindex="<?=$i?>"<?=($my_flotten >= $max_flotten || !$me->permissionToAct()) ? ' readonly="readonly"' : ''?> /></dd>
<?php
			$i++;
		}
?>
		</dl>
	</fieldset>
<?php
		if($i>5 && $my_flotten < $max_flotten && $me->permissionToAct())
		{
?>
	<div><button type="submit"<?=accesskey_attr(_("&Weiter[login/flotten.php|2]"))?> tabindex="<?=$i?>"><?=h(_("&Weiter[login/flotten.php|2]"))?></button></div>
<?php
		}
?>
</form>
<?php
		if(isset($_POST["callback_foreign"]) && is_array($_POST["callback_foreign"]))
		{
			foreach($_POST["callback_foreign"] as $coords=>$is)
			{
				if(is_array($is))
				{
					foreach(array_keys($is) as $i)
						$me->callBackForeignFleet($coords, $i);
				}
			}
		}

		$foreign_users = $me->getForeignUsersList();
		if(count($foreign_users) > 0)
		{
?>
<h3 id="fremdstationierungen-auf-diesem-planeten"><?=h(_("Fremdstationierungen auf diesem Planeten"))?></h3>
<?php
			foreach($foreign_users as $user)
			{
				$fleets = array();
				foreach($me->getForeignFleetsList($user) as $fi)
					$fleets = iadd($fleets, $fi[0]);
				isort($fleets);
?>
<fieldset>
	<legend><?=htmlspecialchars($user)?></legend>
<?php
				makeItemList($fleets, 1);
?>
</fieldset>
<?php
			}
		}

		$foreign_coords = $me->getMyForeignFleets();
		if(count($foreign_coords) > 0)
		{
?>
<h3 id="ihre-fremdstationierungen"><?=h(_("Ihre Fremdstationierungen"))?></h3>
<fieldset>
<?php
			$me->cacheActivePlanet();
			foreach($foreign_coords as $coords)
			{
				$coords_a = explode(":", $coords);
				$galaxy_obj = Classes::Galaxy($coords_a[0]);
				$user_obj = Classes::User($galaxy_obj->getPlanetOwner($coords_a[1], $coords_a[2]));
				if(!$user_obj->getStatus()) continue;
				$user_obj->cacheActivePlanet();
				$user_obj->setActivePlanet($user_obj->getPlanetByPos($coords));

				$fleet = $user_obj->getForeignFleetsList($me->getName());
				foreach($fleet as $i=>$fi)
				{
					$me->setActivePlanet($me->getPlanetByPos($fi[1]));
?>
	<legend><?=h(sprintf(_("„%s“ (%s, Eigentümer: %s)"), $user_obj->planetName(), vsprintf(_("%d:%d:%d"), $coords_a), $user_obj->getName()))?></legend>
<?php
					makeItemList($fi[0], 1);
?>
	<form action="flotten.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>#fremdstationierungen" method="post" class="ihre-fremdstationierungen"><div><button type="submit" name="callback_foreign[<?=htmlspecialchars($coords)?>][<?=htmlspecialchars($i)?>]"><?=h(sprintf(_("Zurückrufen zum Planeten %s"), sprintf(_("„%s“ (%s)"), $me->planetName(), vsprintf(_("%d:%d:%d"), $me->getPos()))))?></button></div></form>
<?php
				}
				$user_obj->restoreActivePlanet();
			}
?>
</fieldset>
<?php
		}
	}

	login_gui::html_foot();
?>
