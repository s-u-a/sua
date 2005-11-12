<?php
	require('scripts/include.php');

	login_gui::html_head();

	$show_versenden = true;

	$kontrollwesen = 0;
	if(isset($user_array['forschung']['F0']))
		$kontrollwesen = $user_array['forschung']['F0'];

	$werften = 0;
	$planets = array_keys($user_array['planets']);
	$koords = array();
	foreach($planets as $planet)
	{
		if(isset($user_array['planets'][$planet]['gebaeude']['B10']) && $user_array['planets'][$planet]['gebaeude']['B10'] > 0)
			$werften++;
		$koords[] = $user_array['planets'][$planet]['pos'];
	}
	$max_flotten = floor(pow($kontrollwesen*$werften, .7));

	$my_flotten = 0;
	foreach($user_array['flotten'] as $flotte)
	{
		if((!$flotte[7] && in_array($flotte[3][0], $koords)) || ($flotte[7] && in_array($flotte[3][1], $koords)))
			$my_flotten++;
	}
?>
<h2>Flotten</h2>
<?php
	$fast_action = false;
	if(isset($_GET['action_galaxy']) && isset($_GET['action_system']) && isset($_GET['action_planet']) && isset($_GET['action']) && ($_GET['action'] == 'spionage' || $_GET['action'] == 'besiedeln' || $_GET['action'] == 'sammeln'))
	{
		$fast_action = true;

		$this_pos = explode(':', $this_planet['pos']);
		$action_back_url = 'http://'.$_SERVER['HTTP_HOST'].h_root.'/login/karte.php?';
		if($_GET['action_galaxy'] != $this_pos[0] || $_GET['action_system'] != $this_pos[1])
			$action_back_url .= 'galaxy='.urlencode($_GET['action_galaxy']).'&system='.urlencode($_GET['action_system']).'&';
		$action_back_url .= SESSION_COOKIE.'='.urlencode(session_id());

		$target_info = universe::get_planet_info($_GET['action_galaxy'], $_GET['action_system'], $_GET['action_planet']);
		if(!$target_info || $target_info[1] == $_SESSION['username'])
		{
			header('Location: '.$action_back_url, true, 307);
			die('HTTP redirect: <a href="'.htmlentities($action_back_url).'">'.htmlentities($action_back_url).'</a>');
		}
		if($my_flotten >= $max_flotten)
		{
?>
<p class="error">
	Maximale Flottenzahl erreicht.
</p>
<?php
			login_gui::html_foot();
			die();
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
				if($target_info[1])
					$_POST['flotte']['S5'] = $user_array['sonden'];
				if(!isset($this_planet['schiffe']['S5']) || $this_planet['schiffe']['S5'] < 1)
				{
?>
<p class="error">
	Keine Spionagesonden vorhanden.
</p>
<?php
					login_gui::html_foot();
					die();
				}
			}
			elseif($_GET['action'] == 'besiedeln')
			{
				$_POST['auftrag'] = 1;
				$_POST['flotte'] = array('S6' => 1);
				if(!isset($this_planet['schiffe']['S6']) || $this_planet['schiffe']['S6'] < 1)
				{
?>
<p class="error">
	Kein Besiedelungsschiff vorhanden.
</p>
<?php
					login_gui::html_foot();
					die();
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
					$transport = $items['schiffe']['S3']['trans'][0];

					# Laderaumerweiterung
					$l_level = 0;
					if(isset($user_array['forschung']['F11']))
						$l_level = $user_array['forschung']['F11'];
					$transport = floor($transport*pow(1.2, $l_level));

					$anzahl = round(array_sum($truemmerfeld)/$transport);
				}
				if($anzahl <= 0)
					$anzahl = 1;

				$_POST['flotte'] = array('S3' => $anzahl);

				if(!isset($this_planet['schiffe']['S3']) || $this_planet['schiffe']['S3'] < 1)
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

	if(!$user_array['umode'] && $my_flotten < $max_flotten && isset($_POST['flotte']) && is_array($_POST['flotte']) && isset($_POST['galaxie']) && isset($_POST['system']) && isset($_POST['planet']))
	{
		foreach($_POST['flotte'] as $id=>$anzahl)
		{
			$_POST['flotte'][$id] = $anzahl = (int) $anzahl;
			if(!isset($items['schiffe']) || !isset($this_planet['schiffe'][$id]))
			{
				unset($_POST['flotte'][$id]);
				continue;
			}
			if($anzahl > $this_planet['schiffe'][$id])
				$_POST['flotte'][$id] = $anzahl = $this_planet['schiffe'][$id];
			if($anzahl < 1)
			{
				unset($_POST['flotte'][$id]);
				continue;
			}
			$show_versenden = false;
		}

		if(!$show_versenden)
		{
			$info = universe::get_planet_info($_POST['galaxie'], $_POST['system'], $_POST['planet']);
			if($info === false)
				$show_versenden = true;

			if(!$show_versenden)
			{
				# Auftragsarten ermitteln
				$types = array();
				foreach($_POST['flotte'] as $id=>$anzahl)
				{
					foreach($items['schiffe'][$id]['types'] as $type)
					{
						if(array_search($type, $types) === false)
							$types[] = $type;
					}
				}
				sort($types, SORT_NUMERIC);

				$types = array_flip($types);
				if($info[1] && isset($types[1])) # Besiedeln, Planet besetzt
					unset($types[1]);
				if($info[1] && isset($types[1]) && in_array($info[1], $user_array['verbuendete'])) # Verbuendete angreifen
					unset($types[3]);
				if(!$info[1]) # Planet nicht besetzt
				{
					if(isset($types[3])) # Angriff
						unset($types[3]);
					if(isset($types[4])) # Transport
						unset($types[4]);
					if(isset($types[6])) # Stationieren
						unset($types[6]);

					if(count($user_array['planets']) >= MAX_PLANETS) # Planetenlimit erreicht
						unset($types[1]);
				}

				$truemmerfeld = truemmerfeld::get($_POST['galaxie'], $_POST['system'], $_POST['planet']);
				if(($truemmerfeld === false || array_sum($truemmerfeld) <= 0) && isset($types[2])) # Sammeln, kein Truemmerfeld
					unset($types[2]);

				if($this_planet['pos'] == $_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'] || substr($info[1], -4) == ' (U)')
				{ # Selber Planet / Urlaubsmodus, nur Sammeln
					if($truemmerfeld && isset($types[2]))
						$types = array(2 => 0);
					else
						$types = array();
				}
				elseif($info[1] == $_SESSION['username'])
				{ # Eigener Planet
					if(isset($types[3])) # Angriff
						unset($types[3]);
					if(isset($types[5])) # Spionieren
						unset($types[5]);
				}
				else
				{ # Fremder Planet
					if(isset($types[6])) # Stationieren
						unset($types[6]);
					if(in_array($info[1], $user_array['verbuendete']) && isset($types[3])) # Verbuendet, Angriff
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

					$distance = fleet::get_distance($this_planet['pos'], $_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet']);

					# Gesamtleermasse, Transportkapazitaet und Antriebsstaerke berechnen, Auftragstypen
					$mass = 0;
					$speed = 0;
					$transport = array(0, 0);
					foreach($_POST['flotte'] as $id=>$anzahl)
					{
						$mass += $items['schiffe'][$id]['mass']*$anzahl;
						$speed += $items['schiffe'][$id]['speed']*$anzahl;

						$transport[0] += $items['schiffe'][$id]['trans'][0]*$anzahl;
						$transport[1] += $items['schiffe'][$id]['trans'][1]*$anzahl;
					}
					$mass = round($mass*0.8);
					$leermasse = $mass;
					sort($types, SORT_NUMERIC);

					# Laderaumerweiterung
					$l_level = 0;
					if(isset($user_array['forschung']['F11']))
						$l_level = $user_array['forschung']['F11'];
					$transport[0] = floor($transport[0]*pow(1.2, $l_level));
					$transport[1] = floor($transport[1]*pow(1.2, $l_level));

					# Triebwerke
					# Rueckstossantrieb
					$rueckstoss_level = 0;
					if(isset($user_array['forschung']['F6']))
						$rueckstoss_level = $user_array['forschung']['F6'];
					if($rueckstoss_level > 0)
						$speed *= pow(1.025, $rueckstoss_level);

					# Ionenantrieb
					$ionen_level = 0;
					if(isset($user_array['forschung']['F7']))
						$ionen_level = $user_array['forschung']['F7'];
					if($ionen_level > 0)
						$speed *= pow(1.05, $ionen_level);

					# Kernantrieb
					$kern_level = 0;
					if(isset($user_array['forschung']['F8']))
						$kern_level = $user_array['forschung']['F8'];
					if($kern_level > 0)
						$speed *= pow(1.5, $kern_level);
					$speed = round($speed);

					$show_form2 = true;
					if(isset($_POST['auftrag']))
					{
						$show_form2 = false;

						if(!in_array($_POST['auftrag'], $types))
							$show_form2 = true;
						elseif(isset($_SESSION['doppelklick']) && isset($_POST['doppelklick']) && $_POST['doppelklick'] == $_SESSION['doppelklick'])
						{
?>
<p class="error">
	Doppelklickschutz &ndash; Sie wollten die Flotte zweimal abschicken.
</p>
<?php
						}
						else
						{
							$that_user_array = get_user_array($info[1]);

							$noob = false;
							if($_POST['auftrag'] == '3' && substr($info[1], -4) != ' (g)')
							{
								# Anfaengerschutz ueberpruefen
								$that_punkte = $that_user_array['punkte'][0]+$that_user_array['punkte'][1]+$that_user_array['punkte'][2]+$that_user_array['punkte'][3]+$that_user_array['punkte'][4]+$that_user_array['punkte'][5]+$that_user_array['punkte'][6];
								$this_punkte = $user_array['punkte'][0]+$user_array['punkte'][1]+$user_array['punkte'][2]+$user_array['punkte'][3]+$user_array['punkte'][4]+$user_array['punkte'][5]+$user_array['punkte'][6];

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
								$auftrag_array = array();
								$auftrag_array[0] = $_POST['flotte']; # Schiffe
								$auftrag_array[1] = array(time(), 0); # Start-, Ankunftszeit
								$auftrag_array[2] = $_POST['auftrag']; # Auftragsart
								$auftrag_array[3] = array($this_planet['pos'], $_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet']); # Koordinaten

								# Geschwindigkeitsfaktor
								if(isset($_POST['speed']) && $_POST['speed'] >= 0.05 && $_POST['speed'] <= 1)
									$auftrag_array[6] = $_POST['speed'];
								else
									$auftrag_array[6] = 1;

								$auftrag_array[4] = array(fleet::get_tritium($leermasse, $distance)*$auftrag_array[6]*2, 0); # Tritium (Verbrauch, Ueberschuessig)

								$auftrag_array[5] = array(array(0,0,0,0,0), array()); # Mitnahme: Rohstoffe, Roboter
								if(($auftrag_array[2] == 1 || $auftrag_array[2] == 4 || $auftrag_array[2] == 6))
								{
									if($transport[0] > 0)
									{
										# Rohstoffmitnahme
										if(isset($_POST['transport-carbon']) && $_POST['transport-carbon'] >= 0)
											$auftrag_array[5][0][0] = (int) $_POST['transport-carbon'];
										if(isset($_POST['transport-aluminium']) && $_POST['transport-aluminium'] >= 0)
											$auftrag_array[5][0][1] = (int) $_POST['transport-aluminium'];
										if(isset($_POST['transport-wolfram']) && $_POST['transport-wolfram'] >= 0)
											$auftrag_array[5][0][2] = (int) $_POST['transport-wolfram'];
										if(isset($_POST['transport-radium']) && $_POST['transport-radium'] >= 0)
											$auftrag_array[5][0][3] = (int) $_POST['transport-radium'];
										if(isset($_POST['transport-tritium']) && $_POST['transport-tritium'] >= 0)
											$auftrag_array[5][0][4] = (int) $_POST['transport-tritium'];

										# Mehr mitgenommen als vorhanden?
										if($auftrag_array[5][0][0] > $this_planet['ress'][0])
											$auftrag_array[5][0][0] = $this_planet['ress'][0];
										if($auftrag_array[5][0][1] > $this_planet['ress'][1])
											$auftrag_array[5][0][1] = $this_planet['ress'][1];
										if($auftrag_array[5][0][2] > $this_planet['ress'][2])
											$auftrag_array[5][0][2] = $this_planet['ress'][2];
										if($auftrag_array[5][0][3] > $this_planet['ress'][3])
											$auftrag_array[5][0][3] = $this_planet['ress'][3];
										if($auftrag_array[5][0][4] > $this_planet['ress'][4]-$auftrag_array[4][0])
											$auftrag_array[5][0][4] = $this_planet['ress'][4]-$auftrag_array[4][0];
									}

									if($transport[1] > 0)
									{
										# Robotermitnahme
										if(isset($_POST['rtransport-bau']) && $_POST['rtransport-bau'] >= 0 && isset($this_planet['roboter']['R01']))
										{
											$auftrag_array[5][1]['R01'] = (int) $_POST['rtransport-bau'];
											if($auftrag_array[5][1]['R01'] > $this_planet['roboter']['R01'])
												$auftrag_array[5][1]['R01'] = $this_planet['roboter']['R01'];
										}
										if(isset($_POST['rtransport-carbon']) && $_POST['rtransport-carbon'] >= 0 && isset($this_planet['roboter']['R02']))
										{
											$auftrag_array[5][1]['R02'] = (int) $_POST['rtransport-carbon'];
											if($auftrag_array[5][1]['R02'] > $this_planet['roboter']['R02'])
												$auftrag_array[5][1]['R02'] = $this_planet['roboter']['R02'];
										}
										if(isset($_POST['rtransport-aluminium']) && $_POST['rtransport-aluminium'] >= 0 && isset($this_planet['roboter']['R03']))
										{
											$auftrag_array[5][1]['R03'] = (int) $_POST['rtransport-aluminium'];
											if($auftrag_array[5][1]['R03'] > $this_planet['roboter']['R03'])
												$auftrag_array[5][1]['R03'] = $this_planet['roboter']['R03'];
										}
										if(isset($_POST['rtransport-wolfram']) && $_POST['rtransport-wolfram'] >= 0 && isset($this_planet['roboter']['R04']))
										{
											$auftrag_array[5][1]['R04'] = (int) $_POST['rtransport-wolfram'];
											if($auftrag_array[5][1]['R04'] > $this_planet['roboter']['R04'])
												$auftrag_array[5][1]['R04'] = $this_planet['roboter']['R04'];
										}
										if(isset($_POST['rtransport-radium']) && $_POST['rtransport-radium'] >= 0 && isset($this_planet['roboter']['R05']))
										{
											$auftrag_array[5][1]['R05'] = (int) $_POST['rtransport-radium'];
											if($auftrag_array[5][1]['R05'] > $this_planet['roboter']['R05'])
												$auftrag_array[5][1]['R05'] = $this_planet['roboter']['R05'];
										}
										if(isset($_POST['rtransport-tritium']) && $_POST['rtransport-tritium'] >= 0 && isset($this_planet['roboter']['R06']))
										{
											$auftrag_array[5][1]['R06'] = (int) $_POST['rtransport-tritium'];
											if($auftrag_array[5][1]['R06'] > $this_planet['roboter']['R06'])
												$auftrag_array[5][1]['R06'] = $this_planet['roboter']['R06'];
										}
									}

									# Wenn zu viel mitgenommen wurde, kuerzen

									# Rohstoffe
									$rohstoff_sum = array_sum($auftrag_array[5][0]);
									if($rohstoff_sum > $transport[0])
									{
										$f = $transport[0]/$rohstoff_sum;
										$auftrag_array[5][0][0] = floor($auftrag_array[5][0][0]*$f);
										$auftrag_array[5][0][1] = floor($auftrag_array[5][0][1]*$f);
										$auftrag_array[5][0][2] = floor($auftrag_array[5][0][2]*$f);
										$auftrag_array[5][0][3] = floor($auftrag_array[5][0][3]*$f);
										$auftrag_array[5][0][4] = floor($auftrag_array[5][0][4]*$f);

										# Rundungsdifferenzen ausgleichen
										$rohstoff_sum = array_sum($auftrag_array[5][0]);
										$d = $transport[0]-$rohstoff_sum;
										$ed = floor($d/5);
										$auftrag_array[5][0][0] += $ed;
										$auftrag_array[5][0][1] += $ed;
										$auftrag_array[5][0][2] += $ed;
										$auftrag_array[5][0][3] += $ed;
										$auftrag_array[5][0][4] += $ed;

										$d = $d%5;
										switch($d)
										{
											case 4: $auftrag_array[5][0][3]++;
											case 3: $auftrag_array[5][0][2]++;
											case 2: $auftrag_array[5][0][1]++;
											case 1: $auftrag_array[5][0][0]++;
										}
										$rohstoff_sum = array_sum($auftrag_array[5][0]);
									}

									# Roboter
									$roboter_sum = array_sum($auftrag_array[5][1]);
									if($roboter_sum > $transport[1])
									{
										$f = $transport[1]/$roboter_sum;
										$auftrag_array[5][1]['R01'] = floor($auftrag_array[5][1]['R01']*$f);
										$auftrag_array[5][1]['R02'] = floor($auftrag_array[5][1]['R02']*$f);
										$auftrag_array[5][1]['R03'] = floor($auftrag_array[5][1]['R03']*$f);
										$auftrag_array[5][1]['R04'] = floor($auftrag_array[5][1]['R04']*$f);
										$auftrag_array[5][1]['R05'] = floor($auftrag_array[5][1]['R05']*$f);
										$auftrag_array[5][1]['R06'] = floor($auftrag_array[5][1]['R06']*$f);

										# Rundungsdifferenzen ausgleichen
										$roboter_sum = array_sum($auftrag_array[5][1]);
										$d = $transport[1]-$roboter_sum;
										$ed = floor($d/6);
										$auftrag_array[5][1]['R01'] += $ed;
										$auftrag_array[5][1]['R02'] += $ed;
										$auftrag_array[5][1]['R03'] += $ed;
										$auftrag_array[5][1]['R04'] += $ed;
										$auftrag_array[5][1]['R05'] += $ed;
										$auftrag_array[5][1]['R06'] += $ed;

										$d = $d%6;
										switch($d)
										{
											case 5: $auftrag_array[5][1]['R05']++;
											case 4: $auftrag_array[5][1]['R04']++;
											case 3: $auftrag_array[5][1]['R03']++;
											case 2: $auftrag_array[5][1]['R02']++;
											case 1: $auftrag_array[5][1]['R01']++;
										}
									}

									$mass += $rohstoff_sum;
									foreach($auftrag_array[5][1] as $id=>$anzahl)
										$mass += $items['roboter'][$id]['mass']*$anzahl;
								}

								# Geschwindigkeit und Tritiumverbrauch nun berechnen
								$auftrag_array[1][1] = time()+round(fleet::get_time($mass, $distance, $speed)/$auftrag_array[6]); # Ankunftszeit

								$auftrag_array[7] = false; # Rueckflug?

								if($this_planet['ress'][4] < $auftrag_array[4][0])
								{
?>
<p class="error">
	Nicht genug Tritium vorhanden.
</p>
<?php
								}
								else
								{
									if(isset($_POST['doppelklick']))
										$_SESSION['doppelklick'] = $_POST['doppelklick'];

									if(!isset($user_array['flotten']))
										$user_array['flotten'] = array();

									do $key = str_replace('.', '-', array_sum(explode(' ', microtime()))); while(isset($user_array['flotten'][$key]) && false);

									$cont = true;
									if($auftrag_array[2] != 1 && $auftrag_array[2] != 2 && $info[1] != $_SESSION['username'] && $info[1])
									{
										# Beim Zielbenutzer die Flottenbewegung eintragen
										if(!isset($that_user_array['flotten']))
											$that_user_array['flotten'] = array();
										while(isset($user_array['flotten'][$key]) || isset($that_user_array['flotten'][$key]))
											$key = str_replace('.', '-', array_sum(explode(' ', microtime())));
										$that_user_array['flotten'][$key] = $auftrag_array;

										uasort($that_user_array['flotten'], 'usort_fleet');

										if(!write_user_array($info[1], $that_user_array))
										{
?>
<p class="error">
	Datenbankfehler.
</p>
<?php
											$cont = false;
										}
									}

									if($cont)
									{
										$user_array['flotten'][$key] = $auftrag_array;
										$this_planet['ress'][4] -= $auftrag_array[4][0];
										$user_array['punkte'][11] += $auftrag_array[4][0]; # Verbrauchtes Tritium

										# Flotten abziehen
										$logfile_schiffe = array();
										foreach($_POST['flotte'] as $id=>$anzahl)
										{
											$this_planet['schiffe'][$id] -= $anzahl;
											if($anzahl > 0)
												$logfile_schiffe[] = $id.' '.$anzahl;
										}
										$logfile_schiffe = implode(' ', $logfile_schiffe);

										# Rohstoffe abziehen
										$this_planet['ress'][0] -= $auftrag_array[5][0][0];
										$this_planet['ress'][1] -= $auftrag_array[5][0][1];
										$this_planet['ress'][2] -= $auftrag_array[5][0][2];
										$this_planet['ress'][3] -= $auftrag_array[5][0][3];
										$this_planet['ress'][4] -= $auftrag_array[5][0][4];

										# Roboter abziehen
										$logfile_roboter = array();
										foreach($auftrag_array[5][1] as $id=>$anzahl)
										{
											if($anzahl == 0)
												continue;
											$this_planet['roboter'][$id] -= $anzahl;
											$logfile_roboter[] = $id.' '.$anzahl;
										}
										$logfile_roboter = implode(' ', $logfile_roboter);

										uasort($user_array['flotten'], 'usort_fleet');
										write_user_array();

										eventhandler::add_event($auftrag_array[1][1]);

										if($fast_action)
										{
											header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
											ob_end_clean();
											ob_end_clean();
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
		<dd class="c-ziel"><?=utf8_htmlentities($auftrag_array[3][1])?> &ndash; <?=$info[1] ? utf8_htmlentities($info[2]).' <span class="playername">('.utf8_htmlentities($info[1]).')</span>' : 'Unbesiedelt'?></dd>

		<dt class="c-auftragsart">Auftragsart</dt>
		<dd class="c-auftragsart"><?=isset($type_names[$auftrag_array[2]]) ? htmlentities($type_names[$auftrag_array[2]]) : $auftrag_array[2]?></dt>

		<dt class="c-ankunft">Ankunft</dt>
		<dd class="c-ankunft"><?=date('H:i:s, Y-m-d', $auftrag_array[1][1])?> (Serverzeit)</dd>
	</dl>
</div>
<?php
											logfile::action('12', $auftrag_array[3][1], $logfile_schiffe, $auftrag_array[2], $auftrag_array[6], implode('.', $auftrag_array[5][0]), $logfile_roboter, $key);
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
							header('Location: '.$action_back_url, true, 307);
							die('HTTP redirect: <a href="'.htmlentities($action_back_url).'">'.htmlentities($action_back_url).'</a>');
						}

						$time = fleet::get_time($mass, $distance, $speed);
						$tritium = fleet::get_tritium($mass, $distance);
						$tritium *= 2;
						$time_string = '';
						if($time >= 86400)
						{
							$time_string .= floor($time/86400).'&thinsp;<abbr title="Tage">d</abbr>';
							$time2 = $time%86400;
						}
						else
							$time2 = $time;
						$time_string .= add_nulls(floor($time2/3600), 2).':'.add_nulls(floor(($time2%3600)/60), 2).':'.add_nulls(($time2%60), 2);
?>
<form action="flotten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post" class="flotte-versenden-2">
	<dl>
		<dt class="c-ziel">Ziel</dt>
		<dd class="c-ziel"><?=utf8_htmlentities($_POST['galaxie'].':'.$_POST['system'].':'.$_POST['planet'])?> &ndash; <?=$info[1] ? utf8_htmlentities($info[2]).' <span class="playername">('.utf8_htmlentities($info[1]).')</span>' : 'Unbesiedelt'?></dd>

		<dt class="c-entfernung">Entfernung</dt>
		<dd class="c-entfernung"><?=ths($distance)?>&thinsp;<abbr title="Orbits">Or</abbr></dd>

		<dt class="c-masse">Masse</dt>
		<dd class="c-masse" id="masse"><?=ths($mass)?>&thinsp;<abbr title="Tonnen">t</abbr></dd>

		<dt class="c-antrieb">Antrieb</dt>
		<dd class="c-antrieb"><?=ths($speed)?>&thinsp;<abbr title="Megawatt">MW</abbr></dd>

		<script type="text/javascript">
			// <![CDATA[
			document.write('<dt class="c-tritiumverbrauch">Tritiumverbrauch</dt>');
			document.write('<dd class="c-tritiumverbrauch <?=($this_planet['ress'][4] >= $tritium) ? 'ja' : 'nein'?>" id="tritium-verbrauch"><?=ths($tritium)?>&thinsp;<abbr title="Tonnen">t</abbr></dd>');
			// ]]>
		</script>

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
				<dd><input type="text" name="transport-carbon" id="transport-carbon" value="0" onchange="recalc_values();" accesskey="n" tabindex="3" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-aluminium">Aluminium</label></dt>
				<dd><input type="text" name="transport-aluminium" id="transport-aluminium" value="0" onchange="recalc_values();" tabindex="4" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-wolfram">Wolfram</label></dt>
				<dd><input type="text" name="transport-wolfram" id="transport-wolfram" value="0" onchange="recalc_values();" tabindex="5" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-radium">Radium</label></dt>
				<dd><input type="text" name="transport-radium" id="transport-radium" value="0" onchange="recalc_values();" tabindex="6" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="transport-tritium">Tritium</label></dt>
				<dd><input type="text" name="transport-tritium" id="transport-tritium" value="0" onchange="recalc_values();" tabindex="7" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>
<?php
							}
							if($transport[1] > 0)
							{
								if($transport[0] > 0)
									echo "\n";
?>
				<dt><label for="rtransport-bau">Bauroboter</label></dt>
				<dd><input type="text" name="rtransport-bau" id="rtransport-bau" value="0" onchange="recalc_values();" tabindex="8" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="rtransport-carbon">Carbonroboter</label></dt>
				<dd><input type="text" name="rtransport-carbon" id="rtransport-carbon" value="0" onchange="recalc_values();" tabindex="9" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="rtransport-aluminium">Aluminiumroboter</label></dt>
				<dd><input type="text" name="rtransport-aluminium" id="rtransport-aluminium" value="0" onchange="recalc_values();" tabindex="10" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="rtransport-wolfram">Wolframroboter</label></dt>
				<dd><input type="text" name="rtransport-wolfram" id="rtransport-wolfram" value="0" onchange="recalc_values();" tabindex="11" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="rtransport-radium">Radiumroboter</label></dt>
				<dd><input type="text" name="rtransport-radium" id="rtransport-radium" value="0" onchange="recalc_values();" tabindex="12" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>

				<dt><label for="rtransport-tritium">Tritiumroboter</label></dt>
				<dd><input type="text" name="rtransport-tritium" id="rtransport-tritium" value="0" onchange="recalc_values();" tabindex="13" onkeyup="recalc_values();" onclick="recalc_values();" /></dd>
<?php
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
				// Transport
				var auftraege = new Array();
				auftraege[1] = true;
				auftraege[2] = false;
				auftraege[3] = false;
				auftraege[4] = true;
				auftraege[5] = false;
				auftraege[6] = true;

				var auftrag = document.getElementById('auftrag');


				// Masse
				var masse = <?=$mass?>;
<?php
						if($transport[0] > 0 || $transport[1] > 0)
						{
?>
				var use_transport = auftraege[auftrag.options[auftrag.selectedIndex].value];
				document.getElementById('transport-dt').style.display = (use_transport ? 'block' : 'none');
				document.getElementById('transport-dd').style.display = (use_transport ? 'block' : 'none');
				document.getElementById('transport-verbleibend-dt').style.display = (use_transport ? 'block' : 'none');
				document.getElementById('transport-verbleibend-dd').style.display = (use_transport ? 'block' : 'none');

				if(use_transport)
				{
<?php
							if($transport[0] > 0)
							{
?>
					var masse_ress = 0;
					masse_ress += myParseInt(document.getElementById('transport-carbon').value);
					masse_ress += myParseInt(document.getElementById('transport-aluminium').value);
					masse_ress += myParseInt(document.getElementById('transport-wolfram').value);
					masse_ress += myParseInt(document.getElementById('transport-radium').value);
					masse_ress += myParseInt(document.getElementById('transport-tritium').value);
					if(masse_ress > <?=$transport[0]?>)
						masse_ress = <?=$transport[0]?>;
					masse += masse_ress;
<?php
							}
							if($transport[1] > 0)
							{
?>
					var masse_rob = new Array(6);
					var masse_rob_ges = 0;

					masse_rob[0] = myParseInt(document.getElementById('rtransport-bau').value);
					if(masse_rob_ges+masse_rob[0] > <?=$transport[1]?>)
						masse += (<?=$transport[1]?>-masse_rob_ges)*<?=$items['roboter']['R01']['mass']?>;
					else
						masse += masse_rob[0]*<?=$items['roboter']['R01']['mass']?>;
					masse_rob_ges += masse_rob[0];

					masse_rob[1] = myParseInt(document.getElementById('rtransport-carbon').value);
					if(masse_rob_ges+masse_rob[1] > <?=$transport[1]?>)
						masse += (<?=$transport[1]?>-masse_rob_ges)*<?=$items['roboter']['R02']['mass']?>;
					else
						masse += masse_rob[1]*<?=$items['roboter']['R02']['mass']?>;
					masse_rob_ges += masse_rob[1];

					masse_rob[2] = myParseInt(document.getElementById('rtransport-aluminium').value);
					if(masse_rob_ges+masse_rob[2] > <?=$transport[1]?>)
						masse += (<?=$transport[1]?>-masse_rob_ges)*<?=$items['roboter']['R03']['mass']?>;
					else
						masse += masse_rob[2]*<?=$items['roboter']['R03']['mass']?>;
					masse_rob_ges += masse_rob[2];

					masse_rob[3] = myParseInt(document.getElementById('rtransport-wolfram').value);
					if(masse_rob_ges+masse_rob[3] > <?=$transport[1]?>)
						masse += (<?=$transport[1]?>-masse_rob_ges)*<?=$items['roboter']['R04']['mass']?>;
					else
						masse += masse_rob[3]*<?=$items['roboter']['R04']['mass']?>;
					masse_rob_ges += masse_rob[3];

					masse_rob[4] = myParseInt(document.getElementById('rtransport-radium').value);
					if(masse_rob_ges+masse_rob[4] > <?=$transport[1]?>)
						masse += (<?=$transport[1]?>-masse_rob_ges)*<?=$items['roboter']['R05']['mass']?>;
					else
						masse += masse_rob[4]*<?=$items['roboter']['R05']['mass']?>;
					masse_rob_ges += masse_rob[4];

					masse_rob[5] = myParseInt(document.getElementById('rtransport-tritium').value);
					if(masse_rob_ges+masse_rob[5] > <?=$transport[1]?>)
						masse += (<?=$transport[1]?>-masse_rob_ges)*<?=$items['roboter']['R06']['mass']?>;
					else
						masse += masse_rob[5]*<?=$items['roboter']['R06']['mass']?>;
					masse_rob_ges += masse_rob[5];
<?php
							}
?>
				}
<?php
						}
?>
				document.getElementById('masse').innerHTML = ths(masse)+'&thinsp;<abbr title="Tonnen">t</abbr>';

				// Tritiumverbrauch
				var speed_obj = document.getElementById('speed');
				var speed = parseFloat(speed_obj.options[speed_obj.selectedIndex].value);
				var tritium = <?=$tritium?>;
				if(!isNaN(speed))
					tritium = Math.floor(tritium*speed);
				document.getElementById('tritium-verbrauch').innerHTML = ths(tritium)+'&thinsp;<abbr title="Tonnen">t</abbr>';
				document.getElementById('tritium-verbrauch').setAttribute('class', 'c-tritiumverbrauch '+((<?=$this_planet['ress'][4]?> >= tritium) ? 'ja' : 'nein'));

				// Flugzeit
<?php
						if($speed <= 0)
						{
?>
				var time = 0;
<?php
						}
						else
						{
?>
				//var time = Math.pow(1.125*masse*Math.pow(<?=$distance?>, 2)/<?=$speed?>, 0.33333)*10;
				var time = Math.pow((masse*<?=$distance?>)/<?=$speed?>, 0.3)*300;
				if(!isNaN(speed))
					time /= speed;
				time = Math.round(time);

<?php
						}
?>
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
?>
					var ges_rob = myParseInt(document.getElementById('rtransport-bau').value)+myParseInt(document.getElementById('rtransport-carbon').value)+myParseInt(document.getElementById('rtransport-aluminium').value)+myParseInt(document.getElementById('rtransport-wolfram').value)+myParseInt(document.getElementById('rtransport-radium').value)+myParseInt(document.getElementById('rtransport-tritium').value);
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
		<input type="hidden" name="doppelklick" value="<?=htmlentities(md5(microtime()))?>" />
		<button type="submit" accesskey="d">Absen<kbd>d</kbd>en</button>
	</div>
</form>
<?php
					}
				}
			}
		}
	}

	if($show_versenden)
	{
		if($fast_action)
		{
			header('Location: '.$action_back_url, true, 307);
			die('HTTP redirect: <a href="'.htmlentities($action_back_url).'">'.htmlentities($action_back_url).'</a>');
		}
?>
<h3>Flotte versenden</h3>
<p class="flotte-anzahl<?=($my_flotten >= $max_flotten) ? ' voll' : ''?>">
	Sie haben derzeit <?=ths($my_flotten)?> von <?=ths($max_flotten)?> <?=($max_flotten == 1) ? 'möglichen Flotte' : 'möglichen Flotten'?> unterwegs.
</p>
<?php
		if(isset($this_planet['schiffe']) && array_sum($this_planet['schiffe']) > 0)
		{
?>
<?php
			$this_pos = explode(':', $this_planet['pos']);
?>
<form action="flotten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post" class="flotte-versenden">
<?php
			if($my_flotten < $max_flotten && !$user_array['umode'])
			{
?>
	<fieldset class="flotte-koords">
		<legend>Ziel</legend>
		<dl>
			<dt class="c-ziel"><label for="ziel-galaxie"><kbd>Z</kbd>iel</label></dt>
			<dd class="c-ziel"><input type="text" id="ziel-galaxie" name="galaxie" value="<?=utf8_htmlentities($this_pos[0])?>" title="Ziel: Galaxie" accesskey="z" tabindex="1" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" />:<input type="text" id="ziel-system" name="system" value="<?=utf8_htmlentities($this_pos[1])?>" title="Ziel: System" tabindex="2" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" />:<input type="text" id="ziel-planet" name="planet" value="<?=utf8_htmlentities($this_pos[2])?>" title="Ziel: Planet" tabindex="3" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" /></dd>
			<script type="text/javascript">
				// <![CDATA[
					document.write('<dt class="c-planet"><label for="ziel-planet-wahl">Pla<kbd>n</kbd>et</label></dt>');
					document.write('<dd class="c-planet">');
					document.write('<select id="ziel-planet-wahl" accesskey="n" tabindex="4" onchange="syncronise(false);" onkeyup="syncronise(false);">');
					document.write('<option value="">Benutzerdefiniert</option>');
<?php
				$planets = array_keys($user_array['planets']);
				foreach($planets as $planet)
				{
?>
					document.write('<option value="<?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?>"<?=($planet == $_SESSION['act_planet']) ? ' selected="selected"' : ''?>><?=utf8_htmlentities($user_array['planets'][$planet]['name'])?> (<?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?>)</option>');
<?php
				}
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
			foreach($this_planet['schiffe'] as $id=>$anzahl)
			{
				if(!isset($items['schiffe'][$id]) || $anzahl < 1)
					continue;
?>
			<dt><a href="help/description.php?id=<?=htmlentities(urlencode($id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($items['schiffe'][$id]['name'])?></a> <span class="vorhanden">(<?=ths($anzahl)?>&nbsp;vorhanden)</span></dt>
			<dd><input type="text" name="flotte[<?=utf8_htmlentities($id)?>]" value="0" tabindex="<?=$i?>"<?=($my_flotten >= $max_flotten || $user_array['umode']) ? ' readonly="readonly"' : ''?> /></dd>
<?php
				$i++;
			}
?>
		</dl>
	</fieldset>
<?php
			if($my_flotten < $max_flotten && !$user_array['umode'])
			{
?>
	<div><button type="submit" accesskey="w" tabindex="<?=$i?>"><kbd>W</kbd>eiter</button></div>
<?php
			}
		}
?>
</form>
<?php
	}

	login_gui::html_foot();
?>