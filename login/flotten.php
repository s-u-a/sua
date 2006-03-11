<?php
	if(isset($_GET['action']))
	{
		$requ_uri = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = '';
		if(isset($_SERVER['HTTP_REFERER'])) $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_REFERER'];
	}
	
	require('scripts/include.php');
	
	if(isset($_GET['action']))
		$_SERVER['REQUEST_URI'] = $requ_uri;

	login_gui::html_head();

	$show_versenden = true;

	$max_flotten = $me->getMaxParallelFleets();
	$my_flotten = $me->getCurrentParallelFleets();
	
	__autoload('Fleet');
	__autoload('Galaxy');
?>
<h2>Flotten</h2>
<?php
	$fast_action = false;
	if(isset($_GET['action_galaxy']) && isset($_GET['action_system']) && isset($_GET['action_planet']) && isset($_GET['action']) && ($_GET['action'] == 'spionage' || $_GET['action'] == 'besiedeln' || $_GET['action'] == 'sammeln'))
	{
		$fast_action = true;

		$galaxy = Classes::Galaxy($_GET['action_galaxy']);
		$planet_owner = $galaxy->getPlanetOwner($_GET['action_system'], $_GET['action_planet']);
		if($my_flotten >= $max_flotten)
		{
?>
<p class="error">
	Maximale Flottenzahl erreicht.
</p>
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
?>
<p class="error">
	Keine Spionagesonden vorhanden.
</p>
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
?>
<p class="error">
	Kein Besiedelungsschiff vorhanden.
</p>
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
?>
<p class="error">
	Keine Sammler vorhanden.
</p>
<?php
				}
			}
		}
	}

	if($me->permissionToAct() && $my_flotten < $max_flotten && isset($_POST['flotte']) && is_array($_POST['flotte']) && isset($_POST['galaxie']) && isset($_POST['system']) && isset($_POST['planet']))
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

		if(!$show_versenden)
		{
			$galaxy_obj = Classes::Galaxy($_POST['galaxie']);
			$planet_owner = $galaxy_obj->getPlanetOwner($_POST['system'], $_POST['planet']);
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

				$truemmerfeld = truemmerfeld::get($_POST['galaxie'], $_POST['system'], $_POST['planet']);
				if(($truemmerfeld === false || array_sum($truemmerfeld) <= 0) && isset($types[2]))
					unset($types[2]); # Kein Truemmerfeld, Sammeln nicht moeglich

				if($me->getPosString() == $_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'] || substr($planet_owner, -4) == ' (U)')
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
					if(isset($types[6])) # Stationieren noch nicht moeglich
						unset($types[6]);
					if($me->isVerbuendet($planet_owner) && isset($types[3])) # Verbuendet, Angriff nicht moeglich
						unset($types[3]);
				}

				if(count($types) <= 0)
				{
?>
<p class="error">
	Sie haben nicht die richtigen Schiffe ausgewählt, um diesen Planeten anzufliegen.
</p>
<?php
				}
				else
				{
					$types = array_flip($types);

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
					
					if(isset($_POST['auftrag']))
					{
						$show_form2 = false;

						if(!in_array($_POST['auftrag'], $types))
							$show_form2 = true;
						else
						{
							$that_user = Classes::User($planet_owner);

							$noob = false;
							if($planet_owner && ($_POST['auftrag'] == '3' || $_POST['auftrag'] == '5') && $that_user->userLocked())
							{
								# Anfaengerschutz ueberpruefen
								$that_punkte = $that_user->getScores();
								$this_punkte = $me->getScores();

								if($that_punkte > $this_punkte && $that_punkte*0.05 > $this_punkte)
								{
?>
<p class="error">
	Das Imperium dieses Spielers ist so groß, dass Ihre Sensoren beim Versuch, einen Anflugspunkt auszumachen, durcheinanderkommen. (<abbr title="Also known as" xml:lang="en">Aka</abbr> Anfängerschutz.)
</p>
<?php
									$noob = true;
								}
								elseif($that_punkte < $this_punkte && $that_punkte < $this_punkte*0.05)
								{
?>
<p class="error">
	Dieser Spieler ist noch so klein, dass Ihre Sensoren das Ziel nicht ausmachen und deshalb den Flugkurs nicht berechnen können. (<abbr title="Also known as" xml:lang="en">Aka</abbr> Anfängerschutz.)
</p>
<?php
									$noob = true;
								}
							}

							if(!$noob)
							{
								$fleet_obj = Classes::Fleet();
								if($fleet_obj->create())
								{
									# Geschwindigkeitsfaktor
									if(!isset($_POST['speed']) || $_POST['speed'] < 0.05 || $_POST['speed'] > 1)
										$_POST['speed'] = 1;
									
									$fleet_obj->addTarget($_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'], $_POST['auftrag'], false);
									if($_POST['auftrag'] != 6)
										$fleet_obj->addTarget($me->getPosString(), $_POST['auftrag'], true);
									
									$fleet_obj->addUser($_SESSION['username'], $me->getPosString(), $_POST['speed']);
									
									foreach($_POST['flotte'] as $id=>$anzahl)
										$fleet_obj->addFleet($id, $anzahl, $_SESSION['username']);
									
									$ress = $me->getRess();
									if(($_POST['auftrag'] == 1 || $_POST['auftrag'] == 4 || $_POST['auftrag'] == 6))
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
										$fleet_obj->addTransport($_SESSION['username'], $_POST['transport'], $_POST['rtransport']);
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
?>
<p class="error">
	Nicht genug Tritium vorhanden.
</p>
<?php
									}
									else
									{
										$me->addFleet($fleet_obj->getName());
										if($_POST['auftrag'] != 1 && $_POST['auftrag'] != 2 && $planet_owner != $_SESSION['username'] && $planet_owner)
										{
											# Beim Zielbenutzer die Flottenbewegung eintragen
											$that_user->addFleet($fleet_obj->getName());
										}

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
										
										if($fast_action)
										{
											header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
											ob_end_clean();
											#ob_end_clean();
											die();
										}
										else
										{
?>
<div class="flotte-versandt">
	<p>
		Die Flotte wurde versandt.
	</p>
	<dl>
		<dt class="c-ziel">Ziel</dt>
		<dd class="c-ziel"><?=utf8_htmlentities($fleet_obj->getCurrentTarget())?> &ndash; <?=$planet_owner ? utf8_htmlentities($galaxy_obj->getPlanetName($_POST['system'], $_POST['planet'])).' <span class="playername">('.utf8_htmlentities($planet_owner).')</span>' : 'Unbesiedelt'?></dd>

		<dt class="c-auftragsart">Auftragsart</dt>
		<dd class="c-auftragsart"><?=isset($type_names[$_POST['auftrag']]) ? htmlentities($type_names[$_POST['auftrag']]) : utf8_htmlentities($_POST['auftrag'])?></dt>

		<dt class="c-ankunft">Ankunft</dt>
		<dd class="c-ankunft"><?=date('H:i:s, Y-m-d', $fleet_obj->getNextArrival())?> (Serverzeit)</dd>
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
						if($fast_action)
						{
							header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
							ob_end_clean();
							#ob_end_clean();
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
								$time_string .= floor($time/86400).'&thinsp;<abbr title="Tage">d</abbr>';
								$time2 = $time%86400;
							}
							else
								$time2 = $time;
							$time_string .= add_nulls(floor($time2/3600), 2).':'.add_nulls(floor(($time2%3600)/60), 2).':'.add_nulls(($time2%60), 2);
							
							$this_ress = $me->getRess();
							$transport = $fleet_obj->getTransportCapacity($_SESSION['username']);
?>
<form action="flotten.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="flotte-versenden-2" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'Doppelklickschutz: Sie haben ein zweites Mal auf \u201eAbsenden\u201c geklickt. Dadurch wird Ihre Flotte auch zweimal abgesandt (sofern die nötigen Schiffe verfügbar sind). Sind Sie sicher, dass Sie diese Aktion durchführen wollen?\');');">
	<dl>
		<dt class="c-ziel">Ziel</dt>
		<dd class="c-ziel"><?=utf8_htmlentities($_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'])?> &ndash; <?=$planet_owner ? utf8_htmlentities($galaxy_obj->getPlanetName($_POST['system'], $_POST['planet'])).' <span class="playername">('.utf8_htmlentities($planet_owner).')</span>' : 'Unbesiedelt'?></dd>

		<dt class="c-entfernung">Entfernung</dt>
		<dd class="c-entfernung"><?=ths($distance)?>&thinsp;<abbr title="Orbits">Or</abbr></dd>

		<dt class="c-antrieb">Antrieb</dt>
		<dd class="c-antrieb"><?=ths($speed)?>&thinsp;<abbr title="Milliorbits pro Quadratsekunde">mOr&frasl;s²</abbr></dd>

		<dt class="c-tritiumverbrauch">Tritiumverbrauch</dt>
		<dd class="c-tritiumverbrauch <?=($this_ress[4] >= $tritium) ? 'ja' : 'nein'?>" id="tritium-verbrauch"><?=ths($tritium)?>&thinsp;<abbr title="Tonnen">t</abbr></dd>
		
		<dt class="c-geschwindigkeit"><label for="speed">Gesch<kbd>w</kbd>indigkeit</label></dt>
		<dd class="c-geschwindigkeit">
			<select name="speed" id="speed" accesskey="w" tabindex="1" onchange="recalc_values();" onkeyup="recalc_values();">
<?php
							for($i=1,$pr=100; $i>0; $i-=.05,$pr-=5)
							{
?>
				<option value="<?=htmlentities($i)?>"><?=htmlentities($pr)?>&thinsp;%</option>
<?php
							}
?>
			</select>
		</dd>

		<dt class="c-flugzeit">Flugzeit</dt>
		<dd class="c-flugzeit" id="flugzeit" title="Ankunft: <?=date('H:i:s, Y-m-d', time()+$time)?> (Serverzeit)"><?=$time_string?></dd>

		<dt class="c-transportkapazitaet">Transportkapazität</dt>
		<dd class="c-transportkapazitaet"><?=ths($transport[0])?>&thinsp;<abbr title="Tonnen">t</abbr>, <?=ths($transport[1])?>&nbsp;Roboter</dd>

		<script type="text/javascript">
			// <![CDATA[
			document.write('<dt class="c-verbleibend" id="transport-verbleibend-dt">Verbleibend</dt>');
			document.write('<dd class="c-verbleibend" id="transport-verbleibend-dd"><?=ths($transport[0])?>&thinsp;<abbr title="Tonnen">t</abbr>, <?=ths($transport[1])?>&nbsp;Roboter</dd>');
			// ]]>
		</script>

		<dt class="c-auftrag"><label for="auftrag">A<kbd>u</kbd>ftrag</label></dt>
		<dd class="c-auftrag">
			<select name="auftrag" id="auftrag" accesskey="u" tabindex="2" onchange="recalc_values();" onkeyup="recalc_values();">
<?php
							foreach($types as $type)
							{
?>
				<option value="<?=utf8_htmlentities($type)?>"><?=isset($type_names[$type]) ? htmlentities($type_names[$type]) : $type?></option>
<?php
							}
?>
			</select>
		</dd>
<?php
							if($transport[0] > 0 || $transport[1] > 0)
							{
?>

		<dt class="c-transport" id="transport-dt">Tra<kbd>n</kbd>sport</dt>
		<dd class="c-transport" id="transport-dd">
			<dl>
<?php
								if($transport[0] > 0)
								{
?>
				<dt><label for="transport-carbon">Carbo<kbd>n</kbd></label></dt>
				<dd><input type="text" name="transport[0]" id="transport-carbon" value="0" onchange="recalc_values();" accesskey="n" tabindex="3" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-aluminium">Aluminium</label></dt>
				<dd><input type="text" name="transport[1]" id="transport-aluminium" value="0" onchange="recalc_values();" tabindex="4" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-wolfram">Wolfram</label></dt>
				<dd><input type="text" name="transport[2]" id="transport-wolfram" value="0" onchange="recalc_values();" tabindex="5" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-radium">Radium</label></dt>
				<dd><input type="text" name="transport[3]" id="transport-radium" value="0" onchange="recalc_values();" tabindex="6" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-tritium">Tritium</label></dt>
				<dd><input type="text" name="transport[4]" id="transport-tritium" value="0" onchange="recalc_values();" tabindex="7" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>
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
				<dt><label for="rtransport-<?=utf8_htmlentities($rob)?>"><?=utf8_htmlentities($item_info['name'])?></label></dt>
				<dd><input type="text" name="rtransport[<?=utf8_htmlentities($rob)?>]" id="rtransport-<?=utf8_htmlentities($rob)?>" value="0" onchange="recalc_values();" tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>
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
				document.getElementById('tritium-verbrauch').innerHTML = ths(tritium)+'&thinsp;<abbr title="Tonnen">t</abbr>';
				document.getElementById('tritium-verbrauch').className = 'c-tritiumverbrauch '+((<?=$this_ress[4]?> >= tritium) ? 'ja' : 'nein');

				// Flugzeit
				var time = <?=$time?>;
				if(!isNaN(speed))
					time /= speed;
				time = Math.round(time);
				
				var time_string = '';
				if(time >= 86400)
				{
					time_string += Math.floor(time/86400)+'&thinsp;<abbr title="Tage">d</abbr> ';
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
				document.getElementById('flugzeit').setAttribute(attrName, 'Ankunft: '+mk2(ankunft_server.getHours())+':'+mk2(ankunft_server.getMinutes())+':'+mk2(ankunft_server.getSeconds())+', '+ankunft_server.getFullYear()+'-'+mk2(ankunft_server.getMonth()+1)+'-'+mk2(ankunft_server.getDate())+' (Lokalzeit); '+mk2(ankunft_server.getHours())+':'+mk2(ankunft_server.getMinutes())+':'+mk2(ankunft_server.getSeconds())+', '+ankunft_server.getFullYear()+'-'+mk2(ankunft_server.getMonth()+1)+'-'+mk2(ankunft_server.getDate())+' (Serverzeit)');
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
					document.getElementById('transport-verbleibend-dd').innerHTML = remain_ress+'&thinsp;<abbr title="Tonnen">t</abbr>, '+remain_rob+'&nbsp;Roboter';
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
		<input type="hidden" name="flotte[<?=utf8_htmlentities($id)?>]" value="<?=utf8_htmlentities($anzahl)?>" />
<?php
							}
?>
		<input type="hidden" name="galaxie" value="<?=utf8_htmlentities($_POST['galaxie'])?>" />
		<input type="hidden" name="system" value="<?=utf8_htmlentities($_POST['system'])?>" />
		<input type="hidden" name="planet" value="<?=utf8_htmlentities($_POST['planet'])?>" />
		<button type="submit" accesskey="d">Absen<kbd>d</kbd>en</button>
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
		if($fast_action)
		{
			header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
			ob_end_clean();
			#ob_end_clean();
			die();
		}
?>
<h3>Flotte versenden</h3>
<p class="flotte-anzahl<?=($my_flotten >= $max_flotten) ? ' voll' : ''?>">
	Sie haben derzeit <?=ths($my_flotten)?> von <?=ths($max_flotten)?> <?=($max_flotten == 1) ? 'möglichen Flotte' : 'möglichen Flotten'?> unterwegs.
</p>
<?php
		$this_pos = $me->getPos();
		if(isset($_GET['action_galaxy'])) $this_pos[0] = $_GET['action_galaxy'];
		if(isset($_GET['action_system'])) $this_pos[1] = $_GET['action_system'];
		if(isset($_GET['action_planet'])) $this_pos[2] = $_GET['action_planet'];
?>
<form action="flotten.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="flotte-versenden">
<?php
		if($my_flotten < $max_flotten && $me->permissionToAct())
		{
?>
	<fieldset class="flotte-koords">
		<legend>Ziel</legend>
		<dl>
			<dt class="c-ziel"><label for="ziel-galaxie"><kbd>Z</kbd>iel</label></dt>
			<dd class="c-ziel"><input type="text" id="ziel-galaxie" name="galaxie" value="<?=utf8_htmlentities($this_pos[0])?>" title="Ziel: Galaxie" accesskey="z" tabindex="1" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="<?=strlen(getGalaxiesCount())?>" />:<input type="text" id="ziel-system" name="system" value="<?=utf8_htmlentities($this_pos[1])?>" title="Ziel: System" tabindex="2" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="3" />:<input type="text" id="ziel-planet" name="planet" value="<?=utf8_htmlentities($this_pos[2])?>" title="Ziel: Planet" tabindex="3" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="2" /></dd>
			<script type="text/javascript">
				// <![CDATA[
					document.write('<dt class="c-planet"><label for="ziel-planet-wahl">Pla<kbd>n</kbd>et</label></dt>');
					document.write('<dd class="c-planet">');
					document.write('<select id="ziel-planet-wahl" accesskey="n" tabindex="4" onchange="syncronise(false);" onkeyup="syncronise(false);">');
					document.write('<option value="">Benutzerdefiniert</option>');
<?php
				$planets = $me->getPlanetsList();
				$active_planet = $me->getActivePlanet();
				foreach($planets as $planet)
				{
					$me->setActivePlanet($planet);
?>
					document.write('<option value="<?=utf8_htmlentities($me->getPosString())?>"<?=($planet == $active_planet) ? ' selected="selected"' : ''?>><?=preg_replace('/[\'\\\\]/', '\\\\\\0', utf8_htmlentities($me->planetName()))?> (<?=utf8_htmlentities($me->getPosString())?>)</option>');
<?php
				}
				$me->setActivePlanet($active_planet);
?>
					document.write('</select>');
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
<?php
		}
?>
	<fieldset class="flotte-schiffe">
		<legend>Schiffe</legend>
		<dl>
<?php
		$i = 5;
		foreach($me->getItemsList('schiffe') as $id)
		{
			if($me->getItemLevel($id, 'schiffe') < 1) continue;
			$item_info = $me->getItemInfo($id, 'schiffe');
?>
			<dt><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($item_info['name'])?></a> <span class="vorhanden">(<?=ths($item_info['level'])?>&nbsp;vorhanden)</span></dt>
			<dd><input type="text" name="flotte[<?=utf8_htmlentities($id)?>]" value="0" tabindex="<?=$i?>"<?=($my_flotten >= $max_flotten || !$me->permissionToAct()) ? ' readonly="readonly"' : ''?> /></dd>
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
	<div><button type="submit" accesskey="w" tabindex="<?=$i?>"><kbd>W</kbd>eiter</button></div>
<?php
		}
?>
</form>
<?php
	}

	login_gui::html_foot();
?>