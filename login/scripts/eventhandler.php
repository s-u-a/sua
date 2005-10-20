<?php
	$path = substr(__FILE__, 0, strrpos(__FILE__, '/'));
	$path = substr($path, 0, strrpos($path, '/'));
	$path = substr($path, 0, strrpos($path, '/')).'/engine/include.php';
	require_once($path);

	class eventhandler
	{
		function add_event($time, $user=false) # Fuegt einen Event hinzu, der vom Eventhandler ausgefuehrt wird
		{
			if($user === false)
			{
				if(!isset($_SESSION['username']))
					return false;
				$user = $_SESSION['username'];
			}

			if(!is_array($time))
				$time = array($time);

			$user_string = $user;
			if(strlen($user) < 24)
				$user_string .= str_repeat(' ', 24-strlen($user));

			sort($time, SORT_NUMERIC);

			$fh = fopen(EVENT_FILE, 'a');

			if(!$fh)
			{
				logfile::error('Ein Event konnte nicht geschrieben werden.');
				return false;
			}

			flock($fh, LOCK_EX);

			foreach($time as $i=>$time)
			{
				$time_bin = add_nulls(base_convert($time, 10, 2), 64);
				$string = '';
				for($i = 0; $i < strlen($time_bin); $i+=8)
					$string .= chr(bindec(substr($time_bin, $i, 8)));
				unset($time_bin);
				$string .= $user_string;
				fwrite($fh, $string);
			}

			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}

		function run_eventhandler($ev_username=false, $check_fleet=true)
		{
			if($ev_username === false && !isset($_SESSION['username']))
				return false;

			global $user_array;
			global $items;
			global $types_message_types;
			global $this_planet;

			if($ev_username !== false && (!isset($user_array) || !isset($_SESSION['username']) || $ev_username != $_SESSION['username']))
			{
				if(isset($user_array))
				{
					$user_array_save = $user_array;
					unset($user_array);
				}
				$user_array = get_user_array($ev_username);
			}

			if($ev_username === false)
				$ev_username = $_SESSION['username'];

			if(isset($_SESSION['username']))
				$username_save = $_SESSION['username'];
			$GLOBALS['_SESSION']['username'] = $ev_username;

			if(!isset($items))
				$items = get_items(false);

			# Events ausfuehren

			$ges_planets = array_keys($user_array['planets']);
			foreach($ges_planets as $ges_planet)
			{
				$GLOBALS['this_planet'] = & $user_array['planets'][$ges_planet];
				$this_planet = &$GLOBALS['this_planet'];

				# Rohstoffe aktualisieren
				refresh_ress();

				if(isset($this_planet['building']['gebaeude']) && trim($this_planet['building']['gebaeude'][0]) != '' && $this_planet['building']['gebaeude'][1] <= time())
				{
					# Gebaeude fertigstellen
					$a_id = $this_planet['building']['gebaeude'][0];
					if($this_planet['building']['gebaeude'][2])
						$this_planet['gebaeude'][$a_id]--;
					else
					{
						$level = 0;
						if(isset($this_planet['gebaeude'][$a_id]))
							$level = $this_planet['gebaeude'][$a_id];
						$this_planet['gebaeude'][$a_id] = $level+1;
						$this_planet['ids'][$a_id] = & $this_planet['gebaeude'][$a_id];
					}

					# Abhaengigkeiten neu berechnen
					$items = get_items();
					$ges_prod = get_ges_prod();

					# Punkte verteilen und Felderzahl
					$needed_fields = 0;
					if(isset($items['gebaeude'][$a_id]['fields']))
						$needed_fields = $items['gebaeude'][$a_id]['fields'];

					$ress = $this_planet['building']['gebaeude'][3];
					$user_array['punkte'][7] += $ress[0]; # Verbautes Carbon
					$user_array['punkte'][8] += $ress[1]; # Verbautes Aluminium
					$user_array['punkte'][9] += $ress[2]; # Verbautes Wolfram
					$user_array['punkte'][10] += $ress[3]; # Verbautes Radium

					$geb_punkte = ($ress[0]+$ress[1]+$ress[2]+$ress[3])/1000; # Gebaeudepunkte
					if($this_planet['building']['gebaeude'][2]) # Abriss
					{
						$user_array['punkte'][0] -= $geb_punkte*2/2.4;
						$this_planet['size'][0] -= $needed_fields;
					}
					else
					{
						$user_array['punkte'][0] += $geb_punkte;
						$this_planet['size'][0] += $needed_fields;
					}

					# Bau aus der Warteschleife entfernen
					unset($this_planet['building']['gebaeude']);

					write_user_array();

					# Punkte haben sich veraendert, Highscores neu berechnen
					highscores::recalc();

					# Aus dem Eventhandler streichen

					# Loggen nicht vergessen!

					# FEHLT NOCH!
					# Rohstoffe neu berechnen
				}

				if(isset($this_planet['building']['forschung']) && trim($this_planet['building']['forschung'][0]) != '' && $this_planet['building']['forschung'][1] <= time())
				{
					# Forschung fertigstellen
					$a_id = $this_planet['building']['forschung'][0];

					$level = 0;
					if(isset($user_array['forschung'][$a_id]))
						$level = $user_array['forschung'][$a_id];
					$user_array['forschung'][$a_id] = $level+1;
					$planets = array_keys($user_array['planets']);
					foreach($planets as $planet)
						$user_array['planets'][$planet]['ids'][$a_id] = & $user_array['forschung'][$a_id];
					$this_planet['ids'][$a_id] = & $user_array['forschung'][$a_id];

					# Abhaengigkeiten neu berechnen
					$items = get_items();
					$ges_prod = get_ges_prod();

					# Punkte verteilen
					$ress = $this_planet['building']['forschung'][3];
					$user_array['punkte'][7] += $ress[0]; # Verbautes Carbon
					$user_array['punkte'][8] += $ress[1]; # Verbautes Aluminium
					$user_array['punkte'][9] += $ress[2]; # Verbautes Wolfram
					$user_array['punkte'][10] += $ress[3]; # Verbautes Radium

					$for_punkte = ($ress[0]+$ress[1]+$ress[2]+$ress[3])/1000; # Forschungspunkte
					$user_array['punkte'][1] += $for_punkte;

					# Bau aus der Warteschleife entfernen
					if($this_planet['building']['forschung'][2])
					{ # Bei globaler Forschung auf allen Planeten entfernen
						foreach($planets as $planet)
							unset($user_array['planets'][$planet]['building']['forschung']);
					}
					else
						unset($this_planet['building']['forschung']);

					# Bei Ingeneurstechnik Planeten vergroessern
					if($a_id == 'F9')
					{
						$planets = array_keys($user_array['planets']);
						$old_level = $user_array['forschung']['F9'];
						foreach($planets as $planet)
							$user_array['planets'][$planet]['size'][1] *= ($old_level+1)/$old_level;
					}

					# Bei Robotertechnik Bauzeit des aktuellen Gebaeudes verkuerzen
					# KOMMT NOCH!
					# Ebenfalls: Rohstoffe neu berechnen

					write_user_array();

					# Punkte haben sich veraendert, Highscores neu berechnen
					highscores::recalc();

					# Aus dem Eventhandler streichen

					# Loggen nicht vergessen!
				}

				if(isset($this_planet['building']['roboter']))
				{
					$changed = false;
					$arob = 0;
					foreach($this_planet['building']['roboter'] as $i=>$rob)
					{
						if(!isset($items['roboter'][$rob[0]]))
							continue;
						if($rob[1] <= time())
						{
							# Fertigstellen

							$ress = $items['roboter'][$rob[0]]['ress'];

							# Punkte hinzufuegen
							$user_array['punkte'][7] += $ress[0]; # Verbautes Carbon
							$user_array['punkte'][8] += $ress[1]; # Verbautes Aluminium
							$user_array['punkte'][9] += $ress[2]; # Verbautes Wolfram
							$user_array['punkte'][10] += $ress[3]; # Verbautes Radium

							$rob_punkte = ($ress[0]+$ress[1]+$ress[2]+$ress[3])/1000; # Roboterpunkte
							$user_array['punkte'][2] += $rob_punkte;

							# Roboter bauen
							$anzahl = 0;
							if(isset($this_planet['roboter'][$rob[0]]))
								$anzahl = $this_planet['roboter'][$rob[0]];
							$this_planet['roboter'][$rob[0]] = $anzahl+1;
							$this_planet['ids'][$rob[0]] = & $this_planet['roboter'][$rob[0]];

							# Aus Warteschlange entfernen
							unset($this_planet['building']['roboter'][$i]);

							$changed = true;

							if($rob[0] == 'R01')
								$arob++;
						}
					}

					if($arob > 0 && isset($this_planet['building']['gebaeude']) && trim($this_planet['building']['gebaeude'][0]) != '')
					{
						# Gebaeude im Bau, Restbauzeit verkuerzen
						$f = 1;
						if(isset($user_array['forschung']['F2']))
							$f = 1-$user_array['forschung']['F2']*0.0025;

						$rest_bauzeit = $this_planet['building']['gebaeude'][1]-time();
						$rest_bauzeit *= pow($f, $arob);

						$this_planet['building']['gebaeude'][1] = time()+round($rest_bauzeit);

						$changed = true;
					}

					if($changed)
					{
						write_user_array();

						# Punkte haben sich veraendert, Highscores neu berechnen
						highscores::recalc();
					}

					# Aus dem Eventhandler streichen

					# Loggen nicht vergessen!

					# FEHLT NOCH!
					# Rohstoffe neu berechnen
				}

				if(isset($this_planet['building']['schiffe']))
				{
					$changed = false;
					foreach($this_planet['building']['schiffe'] as $i=>$sch)
					{
						if(!isset($items['schiffe'][$sch[0]]))
							continue;
						if($sch[1] <= time())
						{
							# Fertigstellen

							$ress = $items['schiffe'][$sch[0]]['ress'];

							# Punkte hinzufuegen
							$user_array['punkte'][7] += $ress[0]; # Verbautes Carbon
							$user_array['punkte'][8] += $ress[1]; # Verbautes Aluminium
							$user_array['punkte'][9] += $ress[2]; # Verbautes Wolfram
							$user_array['punkte'][10] += $ress[3]; # Verbautes Radium

							$sch_punkte = ($ress[0]+$ress[1]+$ress[2]+$ress[3])/1000; # Flottenpunkte
							$user_array['punkte'][3] += $sch_punkte;

							# Schiffe bauen
							$anzahl = 0;
							if(isset($this_planet['schiffe'][$sch[0]]))
								$anzahl = $this_planet['schiffe'][$sch[0]];
							$this_planet['schiffe'][$sch[0]] = $anzahl+1;
							$this_planet['ids'][$sch[0]] = & $this_planet['schiffe'][$sch[0]];

							# Aus Warteschlange entfernen
							unset($this_planet['building']['schiffe'][$i]);

							$changed = true;
						}
					}

					if($changed)
					{
						write_user_array();

						# Punkte haben sich veraendert, Highscores neu berechnen
						highscores::recalc();
					}

					# Handelsmoeglichkeit neu ueberpruefen?

					# Aus dem Eventhandler streichen

					# Loggen nicht vergessen!
				}

				if(isset($this_planet['building']['verteidigung']))
				{
					$changed = false;
					foreach($this_planet['building']['verteidigung'] as $i=>$def)
					{
						if(!isset($items['verteidigung'][$def[0]]))
							continue;
						if($def[1] <= time())
						{
							# Fertigstellen

							$ress = $items['verteidigung'][$def[0]]['ress'];

							# Punkte hinzufuegen
							$user_array['punkte'][7] += $ress[0]; # Verbautes Carbon
							$user_array['punkte'][8] += $ress[1]; # Verbautes Aluminium
							$user_array['punkte'][9] += $ress[2]; # Verbautes Wolfram
							$user_array['punkte'][10] += $ress[3]; # Verbautes Radium

							$def_punkte = ($ress[0]+$ress[1]+$ress[2]+$ress[3])/1000; # Verteidigungspunkte
							$user_array['punkte'][4] += $def_punkte;

							# Schiffe bauen
							$anzahl = 0;
							if(isset($this_planet['verteidigung'][$def[0]]))
								$anzahl = $this_planet['verteidigung'][$def[0]];
							$this_planet['verteidigung'][$def[0]] = $anzahl+1;
							$this_planet['ids'][$def[0]] = & $this_planet['verteidigung'][$def[0]];

							# Aus Warteschlange entfernen
							unset($this_planet['building']['verteidigung'][$i]);

							$changed = true;
						}
					}

					if($changed)
					{
						write_user_array();

						# Punkte haben sich veraendert, Highscores neu berechnen
						highscores::recalc();
					}

					# Handelsmoeglichkeit neu ueberpruefen?

					# Aus dem Eventhandler streichen

					# Loggen nicht vergessen!
				}
			}

			unset($this_planet);

			global $this_planet;

			if(isset($user_array['flotten']) && $check_fleet)
			{
				# Flottenbewegungen abarbeiten

				$nbsp = "\xc2\xa0";
				$changed = false;
				foreach($user_array['flotten'] as $i=>$flotte)
				{
					if($flotte[1][1] <= time())
					{
						# Flotte ist angekommen, Event abarbeiten

						if(!$flotte[7])
						{
							# Hinflug
							if($flotte[2] == 1)
							{
								# Kolonisieren

								$koords = explode(':', $flotte[3][1]);
								$planet_info = universe::get_planet_info($koords[0], $koords[1], $koords[2]);
								if($planet_info[1])
								{
									# Planet ist bereits besiedelt

									# Flotte zurueckrufen

									# Zeit
									$time_diff = $flotte[1][1]-$flotte[1][0];
									$flotte[1] = array($flotte[1][1], $flotte[1][1]+$time_diff);

									# Koordinaten vertauschen
									list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

									# Rueckflug?
									$flotte[7] = true;

									$user_array['flotten'][$i] = $flotte;

									messages::new_message(array($ev_username=>5), '', 'Planet '.$flotte[3][0].' bereits besetzt', 'Ihre Flotte erreicht den Planeten '.$flotte[3][0].' und will mit der Besiedelung anfangen. Jedoch ist der Planet nicht mehr frei und Ihre Flotte tritt den Rückweg an.');
								}
								elseif(count($user_array['planets']) >= 15)
								{
									# 15-Planeten-Limit erreicht

									# Flotte zurueckrufen

									# Zeit
									$time_diff = $flotte[1][1]-$flotte[1][0];
									$flotte[1] = array($flotte[1][1], $flotte[1][1]+$time_diff);

									# Koordinaten vertauschen
									list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

									# Rueckflug?
									$flotte[7] = true;

									$user_array['flotten'][$i] = $flotte;

									messages::new_message(array($ev_username=>5), '', 'Besiedelung von '.$flotte[3][0].' fehlgeschlagen', 'Ihre Flotte erreicht den Planeten '.$flotte[3][0]." und will mit der Besiedelung anfangen. Als Sie jedoch Ihren Zentralcomputer um Best\xc3\xa4tigung für die Besiedelung bittet, kommt dieser durcheinander, da Sie schon so viele Planeten haben und er nicht so viele gleichzeitig kontrollieren kann, und schickt in Panik Ihrer Flotte das Signal zum R\xc3\xbcckflug.");
								}
								else
								{
									$new_planet_array = array();

									# Neue Rohstoffe errechnen
									$new_planet_array['ress'] = array();
									$new_planet_array['ress'][0] = round($items['schiffe']['S6']['ress'][0]*0.8);
									$new_planet_array['ress'][1] = round($items['schiffe']['S6']['ress'][1]*0.8);
									$new_planet_array['ress'][2] = round($items['schiffe']['S6']['ress'][2]*0.8);
									$new_planet_array['ress'][3] = round($items['schiffe']['S6']['ress'][3]*0.8);
									$new_planet_array['ress'][4] = 0;

									# Mitgenommene Rohstoffe hinzuzaehlen
									if(isset($flotte[5][0][0]))
										$new_planet_array['ress'][0] += $flotte[5][0][0];
									if(isset($flotte[5][0][1]))
										$new_planet_array['ress'][1] += $flotte[5][0][1];
									if(isset($flotte[5][0][2]))
										$new_planet_array['ress'][2] += $flotte[5][0][2];
									if(isset($flotte[5][0][3]))
										$new_planet_array['ress'][3] += $flotte[5][0][3];
									if(isset($flotte[5][0][4]))
										$new_planet_array['ress'][4] += $flotte[5][0][4];

									# Tritiumverbauch eines Besiedelungsschiffes ausrechnen, um ueberfluessigen Treibstoff abzuliefern
									$distance = fleet::get_distance($flotte[3][0], $flotte[3][1]);
									$mass = $items['schiffe']['S6']['mass'];
									$speed = $items['schiffe']['S6']['speed'];
									list(,$tritium) = fleet::calc($mass, $distance, $speed);
									$tritium *= $flotte[6];
									$new_planet_array['ress'][4] += round($tritium);

									$new_planet_array['last_refresh'] = time();

									$new_planet_array['name'] = 'Kolonie';

									$new_planet_array['gebaeude'] = array();
									$new_planet_array['schiffe'] = array();
									$new_planet_array['verteidigung'] = array();
									$new_planet_array['roboter'] = array();
									$new_planet_array['items'] = array();

									# Bei globaler Forschung Planeten miteinbeziehen
									if(isset($user_array['planets'][0]['building']['forschung']) && trim($user_array['planets'][0]['building']['forschung'][0]) != '' && $user_array['planets'][0]['building']['forschung'][2])
										$new_planet_array['building']['forschung'] = $user_array['planets'][0]['building']['forschung'];

									# Mitgenommene Roboter mitnehmen
									foreach($flotte[5][1] as $id=>$anzahl)
									{
										if(!isset($new_planet_array['roboter'][$id]))
										{
											$new_planet_array['roboter'][$id] = 0;
											$new_planet_array['ids'][$id] = & $new_planet_array['roboter'][$id];
										}
										$new_planet_array['roboter'][$id] += $anzahl;
									}

									# Ingeneurstechnik in die Planetengroesse einbeziehen
									$ing_lvl = 0;
									if(isset($user_array['forschung']['F9']))
										$ing_lvl = $user_array['forschung']['F9'];

									$new_planet_array['size'] = array(0, $planet_info[0]*($ing_lvl+1));

									$new_planet_array['building'] = array();

									$new_planet_array['pos'] = $flotte[3][1];

									# Forschungen in IDs aufnehmen
									$new_planet_array['ids'] = array();
									foreach($user_array['forschung'] as $id=>$level)
										$new_planet_array['ids'][$id] = & $user_array['forschung'][$id];

									universe::set_planet_info($koords[0], $koords[1], $koords[2], $planet_info[0], $ev_username, $new_planet_array['name']);

									$user_array['planets'][] = $new_planet_array;

									# Punkte fuer ein Besiedelungsschiff abziehen
									$points = array_sum($items['schiffe']['S6']['ress'])/1000;
									$user_array['punkte'][3] -= $points;

									# Statistiken neu berechnen
									highscores::recalc();

									if((count($flotte[0]) == 1 && isset($flotte[0]['S6']) && $flotte[0]['S6'] <= 1) || count($flotte[0]) < 1)
									{
										# Es braucht nichts zurueckfliegen

										# Flotte entfernen
										unset($user_array['flotten'][$i]);

										if($user_array['receive'][5][0])
										{
											$message_text = 'Ihr '.$items['schiffe']['S6']['name'].' erreicht den Planeten '.$flotte[3][1].' und beginnt mit seiner Besiedelung. Durch den Abbau des Schiffes konnten folgende Rohstoffe gewonnen werden: '.ths(round($items['schiffe']['S6']['ress'][0]*0.8), true).$nbsp.'Carbon, '.ths(round($items['schiffe']['S6']['ress'][1]*0.8), true).$nbsp.'Aluminium, '.ths(round($items['schiffe']['S6']['ress'][2]*0.8), true).$nbsp.'Wolfram, '.ths(round($items['schiffe']['S6']['ress'][3]*0.8), true).$nbsp.'Radium, '.ths(round($items['schiffe']['S6']['ress'][3]*0.8), true).$nbsp.'Tritium. Das Besiedelungsschiff liefert außerdem '.ths(round($tritium), true).' überflüssigen Tritiums ab.';
											messages::new_message(array($ev_username=>5), '', 'Besiedelung von '.$flotte[3][1], $message_text);
										}

										$user_array['punkte'][5] += $tritium/1000;
									}
									else
									{
										if($user_array['receive'][5][0])
										{
											$schiffe_string = array();
											foreach($flotte[0] as $id=>$anzahl)
											{
												if(!isset($items['schiffe'][$id]) || $anzahl <= 0)
													continue;
												$schiffe_string[] = $items['schiffe'][$id]['name'].': '.ths($anzahl, true);
											}
											$schiffe_string = implode(', ', $schiffe_string);
											$message_text = 'Eine Ihrer Flotten ('.$schiffe_string.') erreicht den Planeten '.$flotte[3][1].' und beginnt mit seiner Besiedelung. Ein '.$items['schiffe']['S6']['name'].' wurde abgebaut, um folgende Rohstoffe auf dem Planeten zur Verfügung zu stellen: '.ths(round($items['schiffe']['S6']['ress'][0]*0.8), true).$nbsp.'Carbon, '.ths(round($items['schiffe']['S6']['ress'][1]*0.8), true).$nbsp.'Aluminium, '.ths(round($items['schiffe']['S6']['ress'][2]*0.8), true).$nbsp.'Wolfram, '.ths(round($items['schiffe']['S6']['ress'][3]*0.8), true).$nbsp.'Radium, '.ths(round($items['schiffe']['S6']['ress'][3]*0.8), true).$nbsp.'Tritium. Das abgebaute Schiff liefert außerdem '.ths(round($tritium), true).' überflüssigen Tritiums ab.';
											$rohstoff_sum = array_sum($flotte[5][0]);
											$roboter_sum = array_sum($flotte[5][1]);
											if($rohstoff_sum > 0 || $roboter_sum > 0)
											{
												$message_text .= "\n\nDie mitfliegende Flotte liefert au\xc3\x9ferdem folgendes Transportgut ab:\n";
												if($rohstoff_sum > 0)
												{
													$message_text .= ths(&$flotte[5][0][0], true).$nbsp.'Carbon, '.ths(&$flotte[5][0][1], true).$nbsp.'Aluminium, '.ths(&$flotte[5][0][2], true).$nbsp.'Wolfram, '.ths(&$flotte[5][0][3], true).$nbsp.'Radium, '.ths(&$flotte[5][0][4], true).$nbsp.'Tritium';
													if($roboter_sum > 0)
														$message_text .= ",\n";
													else
														$message_text .= '.';
												}
												if($roboter_sum > 0)
												{
													$roboter_text = array();
													foreach($flotte[5][1] as $id=>$anzahl)
													{
														if(!isset($items['roboter'][$id]) || $anzahl <= 0)
															continue;
														$roboter_text[] = $items['roboter'][$id]['name'].': '.ths($anzahl, true);
													}
													$roboter_text = implode(', ', $roboter_text);
													$message_text .= $roboter_text.'.';
												}
											}

											messages::new_message(array($ev_username=>5), '', 'Besiedelung von '.$flotte[3][1], $message_text);
										}

										# Der Rest der Flotte muss zurueckfliegen

										# Besiedelungsschiff entfernen
										$flotte[0]['S6']--;

										# Masse und Beschleunigung berechnen
										$mass = 0;
										$speed = 0;
										foreach($flotte[0] as $id=>$anzahl)
										{
											$mass += $items['schiffe'][$id]['mass']*$anzahl;
											$speed += $items['schiffe'][$id]['speed']*$anzahl;
										}

										# Tritiumverbrauch und Flugzeit neu berechnen
										list($time,) = fleet::calc($mass, $distance, $speed);

										$time /= $flotte[6];
										$tritium *= $flotte[6];

										$flotte[1] = array($flotte[1][1], $flotte[1][1]+round($time)); # Start- und Ankunftszeit
										list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]); # Start- und Zielkoordinaten
										$flotte[4] = array($flotte[4][0]-round($tritium), 0); # Tritium
										$flotte[5] = array(0=>array(0,0,0,0,0), 1=>array()); # Transport
										$flotte[7] = true; # Rueckflug?

										$user_array['flotten'][$i] = $flotte;

										usort($user_array['flotten'], 'usort_fleet');
									}
								}
							}
							elseif($flotte[2] == 2)
							{
								# Sammeln

								# Truemmerfeld herausfinden
								$target_pos = explode(':', $flotte[3][1]);
								$truemmerfeld = truemmerfeld::get($target_pos[0], $target_pos[1], $target_pos[2]);
								if($truemmerfeld === false)
									$truemmerfeld = array(0, 0, 0, 0);

								# Transportkapazitaet berechnen
								$transport = 0;
								foreach($flotte[0] as $id=>$anzahl)
									$transport += $items['schiffe'][$id]['trans'][0]*$anzahl;

								# Laderaumerweiterung
								$l_level = 0;
								if(isset($user_array['forschung']['F11']))
									$l_level = $user_array['forschung']['F11'];
								$transport = floor($transport*pow(1.2, $l_level));
								$transport -= array_sum($flotte[5][0]);

								$mitnahme = $truemmerfeld;

								$truemmerfeld_ges = array_sum($mitnahme);
								if($truemmerfeld_ges > $transport)
								{
									# Rohstoffe im richtigen Verhaeltnis kuerzen

									$k = $transport/$truemmerfeld_ges;
									$mitnahme[0] = floor($mitnahme[0]*$k);
									$mitnahme[1] = floor($mitnahme[0]*$k);
									$mitnahme[2] = floor($mitnahme[0]*$k);
									$mitnahme[3] = floor($mitnahme[0]*$k);

									# Rundungsfehler ausmerzen
									$uebrig = $transport-array_sum($mitnahme);

									$mitnahme[0] += floor($uebrig/4);
									$mitnahme[1] += floor($uebrig/4);
									$mitnahme[2] += floor($uebrig/4);
									$mitnahme[3] += floor($uebrig/4);

									$uebrig %= 4;

									switch($uebrig)
									{
										case 3: $mitnahme[2]++;
										case 2: $mitnahme[1]++;
										case 1: $mitnahme[0]++;
									}
								}

								# Flotte zurueckrufen

								# Zeit neu berechnen
								$distance = fleet::get_distance($flotte[3][0], $flotte[3][1]);
								# Masse und Geschwindigkeit
								$mass = 0;
								$speed = 0;
								foreach($flotte[0] as $id=>$anzahl)
								{
									if(!isset($items['schiffe'][$id]))
										continue;
									$mass += $items['schiffe'][$id]['mass']*$anzahl;
									$speed += $items['schiffe'][$id]['speed']*$anzahl;
								}
								$mass += array_sum($flotte[5][0]);
								foreach($flotte[5][1] as $id=>$anzahl)
								{
									if(!isset($items['roboter'][$id]))
										continue;
									$mass += $items['roboter'][$id]['mass']*$anzahl;
								}
								list($time_diff) = fleet::calc($mass, $distance, $speed);
								# Geschwindigkeitsfaktor
								$time_diff *= $flotte[6];
								$flotte[1] = array($flotte[1][1], $flotte[1][1]+$time_diff);

								# Koordinaten vertauschen
								list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

								# Rohstoffe
								$flotte[5][0][0] += $mitnahme[0];
								$flotte[5][0][1] += $mitnahme[1];
								$flotte[5][0][2] += $mitnahme[2];
								$flotte[5][0][3] += $mitnahme[3];

								# Rueckflug?
								$flotte[7] = true;


								# Rohstoffe vom Truemmerfeld abziehen
								truemmerfeld::sub($target_pos[0], $target_pos[1], $target_pos[2], $mitnahme[0], $mitnahme[1], $mitnahme[2], $mitnahme[3]);

								# Nachricht verfassen
								$nachrichten_text = "<p>\n";
								$nachrichten_text .= "\tIhre Flotte erreicht das Tr\xc3\xbcmmerfeld auf ".$flotte[3][0]." und bel\xc3\xa4dt ihre ".ths($transport, true)."&nbsp;Tonnen \xc3\xbcbriger Transportkapazit\xc3\xa4t mit folgenden Rohstoffen: ".ths($mitnahme[0], true)."&nbsp;Carbon, ".ths($mitnahme[1], true)."&nbsp;Aluminium, ".ths($mitnahme[2], true)."&nbsp;Wolfram und ".ths($mitnahme[3], true)."&nbsp;Radium.\n";
								$nachrichten_text .= "</p>\n";
								$nachrichten_text .= "<h3>Verbleibende Rohstoffe im Tr\xc3\xbcmmerfeld</h3>\n";
								$nachrichten_text .= "<dl class=\"ress truemmerfeld-verbleibend\">\n";
								$nachrichten_text .= "\t<dt class=\"c-carbon\">Carbon</dt>\n";
								$nachrichten_text .= "\t<dd class=\"c-carbon\">".ths($truemmerfeld[0]-$mitnahme[0])."</dd>\n";
								$nachrichten_text .= "\t<dt class=\"c-aluminium\">Aluminium</dt>\n";
								$nachrichten_text .= "\t<dd class=\"c-aluminium\">".ths($truemmerfeld[1]-$mitnahme[1])."</dd>\n";
								$nachrichten_text .= "\t<dt class=\"c-wolfram\">Wolfram</dt>\n";
								$nachrichten_text .= "\t<dd class=\"c-wolfram\">".ths($truemmerfeld[2]-$mitnahme[2])."</dd>\n";
								$nachrichten_text .= "\t<dt class=\"c-radium\">Radium</dt>\n";
								$nachrichten_text .= "\t<dd class=\"c-radium\">".ths($truemmerfeld[3]-$mitnahme[3])."</dd>\n";
								$nachrichten_text .= "</dl>";

								messages::new_message(array($ev_username=>4), '', 'Abbau auf '.$flotte[3][0], $nachrichten_text, true);

								$user_array['flotten'][$i] = $flotte;
								uasort($user_array['flotten'], 'usort_fleet');
							}
							elseif($flotte[2] == 3)
							{
								# Angriff

								# Planeten herausfinden, der spioniert werden soll
								$start_pos = explode(':', $flotte[3][0]);
								$start_info = universe::get_planet_info($start_pos[0], $start_pos[1], $start_pos[2]);
								$start_own = ($start_info[1] == $ev_username);

								$target_pos = explode(':', $flotte[3][1]);
								$target_info = universe::get_planet_info($target_pos[0], $target_pos[1], $target_pos[2]);
								$target_own = ($target_info[1] == $ev_username);

								if(!$target_own)
								{
									# Eventhandler des Angegriffenen laufenlassen
									eventhandler::run_eventhandler($target_info[1], false);
								}

								# User-Arrays bekommen
								if($start_own)
									$start_user_array = & $user_array;
								else
									$start_user_array = get_user_array($start_info[1]);

								if($target_own)
									$target_user_array = & $user_array;
								else
									$target_user_array = get_user_array($target_info[1]);

								$planets = array_keys($target_user_array['planets']);
								foreach($planets as $planet)
								{
									if($target_user_array['planets'][$planet]['pos'] == $flotte[3][1])
									{
										$that_planet = & $target_user_array['planets'][$planet];
										break;
									}
								}

								# Spionagetechnik fuer Erstschlag
								$angreifer_spiotech = $verteidiger_spiotech = 0;
								if(isset($start_user_array['forschung']['F1']))
									$angreifer_spiotech = $start_user_array['forschung']['F1'];
								if(isset($target_user_array['forschung']['F1']))
									$verteidiger_spiotech = $target_user_array['forschung']['F1'];

								# Waffentechnik
								$angreifer_waffentechnik = $verteidiger_waffentechnik = 0;
								if(isset($start_user_array['forschung']['F4']))
									$angreifer_waffentechnik = $start_user_array['forschung']['F4'];
								if(isset($target_user_array['forschung']['F4']))
									$verteidiger_waffentechnik = $target_user_array['forschung']['F4'];

								# Verteidigungsstrategie
								$angreifer_verteid = $verteidiger_verteid = 0;
								if(isset($start_user_array['forschung']['F5']))
									$angreifer_verteid = $start_user_array['forschung']['F5'];
								if(isset($target_user_array['forschung']['F5']))
									$verteidier_verteid = $target_user_array['forschung']['F5'];

								# Schildtechnik
								$angreifer_schildtechnik = $verteidiger_schildtechnik = 0;
								if(isset($start_user_array['forschung']['F10']))
									$angreifer_schildtechnik = $start_user_array['forschung']['F10'];
								if(isset($target_user_array['forschung']['F10']))
									$verteidiger_schildtechnik = $target_user_array['forschung']['F10'];

								# Angreifer-Flotte zusammenstellen
								$angreifer_flotte = array();
								foreach($flotte[0] as $id=>$anzahl)
								{
									if(!isset($items['ids'][$id]) || !isset($items['schiffe'][$id]))
										continue;
									if($anzahl > 0)
										$angreifer_flotte[$id] = array($anzahl, $items['schiffe'][$id]['def']*$anzahl*pow(1.05, $angreifer_verteid));
								}

								# Verteidiger-Flotte (inklusive Verteidigung) zusammenstellen
								$verteidiger_flotte = array();
								foreach($that_planet['schiffe'] as $id=>$anzahl)
								{
									if(!isset($items['ids'][$id]) || !isset($items['schiffe'][$id]))
										continue;
									if($anzahl > 0)
										$verteidiger_flotte[$id] = array($anzahl, $items['schiffe'][$id]['def']*$anzahl*pow(1.05, $verteidiger_verteid));
								}
								foreach($that_planet['verteidigung'] as $id=>$anzahl)
								{
									if(!isset($items['ids'][$id]) || !isset($items['verteidigung'][$id]))
										continue;
									if($anzahl > 0)
										$verteidiger_flotte[$id] = array($anzahl, $items['verteidigung'][$id]['def']*$anzahl*pow(1.05, $verteidiger_verteid));
								}

								# Namen
								$angreifer_name = $start_info[1];
								$verteidiger_name = $target_info[1];

								# Nachrichtentext
								$nachrichten_text = "<p>\n";
								$nachrichten_text .= "\tEine Flotte vom Planeten \xe2\x80\x9e".utf8_htmlentities($start_info[2])."\xe2\x80\x9c (".utf8_htmlentities($flotte[3][0]).", Eigent\xc3\xbcmer: ".utf8_htmlentities($start_info[1]).") greift den Planeten \xe2\x80\x9e".utf8_htmlentities($target_info[2])."\xe2\x80\x9c (".utf8_htmlentities($flotte[3][1]).", Eigent\xc3\xbcmer: ".$target_info[1].") an.\n";
								$nachrichten_text .= "</p>\n";
								$nachrichten_text .= "<h3>Flotten des Angreifers ".utf8_htmlentities($start_info[1])."</h3>\n";
								$nachrichten_text .= "<table>\n";
								$nachrichten_text .= "\t<thead>\n";
								$nachrichten_text .= "\t\t<tr>\n";
								$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
								$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
								$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
								$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
								$nachrichten_text .= "\t\t</tr>\n";
								$nachrichten_text .= "\t</thead>\n";
								$nachrichten_text .= "\t<tbody>\n";

								$ges_anzahl = $ges_staerke = $ges_schild = 0;
								foreach($angreifer_flotte as $id=>$anzahl)
								{
									$staerke = round($items['ids'][$id]['att']*$anzahl[0]*pow(1.05, $angreifer_waffentechnik));
									$schild = round($anzahl[1]);

									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($items['ids'][$id]['name'])."</a></td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl[0])."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
									$nachrichten_text .= "\t\t</tr>\n";

									$ges_anzahl += $anzahl[0];
									$ges_staerke += $staerke;
									$ges_schild += $schild;
								}
								$nachrichten_text .= "\t</tbody>\n";
								$nachrichten_text .= "\t<tfoot>\n";
								$nachrichten_text .= "\t\t<tr>\n";
								$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
								$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
								$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
								$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
								$nachrichten_text .= "\t\t</tr>\n";
								$nachrichten_text .= "\t</tfoot>\n";
								$nachrichten_text .= "</table>\n";

								$nachrichten_text .= "<h3>Flotten des Verteidigers ".utf8_htmlentities($target_info[1])."</h3>\n";
								if(count($verteidiger_flotte) > 0)
								{
									$nachrichten_text .= "<table>\n";
									$nachrichten_text .= "\t<thead>\n";
									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
									$nachrichten_text .= "\t\t</tr>\n";
									$nachrichten_text .= "\t</thead>\n";
									$nachrichten_text .= "\t<tbody>\n";

									$ges_anzahl = $ges_staerke = $ges_schild = 0;
									foreach($verteidiger_flotte as $id=>$anzahl)
									{
										$staerke = round($items['ids'][$id]['att']*$anzahl[0]*pow(1.05, $angreifer_waffentechnik));
										$schild = round($anzahl[1]);

										$nachrichten_text .= "\t\t<tr>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($items['ids'][$id]['name'])."</a></td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl[0])."</td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
										$nachrichten_text .= "\t\t</tr>\n";

										$ges_anzahl += $anzahl[0];
										$ges_staerke += $staerke;
										$ges_schild += $schild;
									}
									$nachrichten_text .= "\t</tbody>\n";
									$nachrichten_text .= "\t<tfoot>\n";
									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
									$nachrichten_text .= "\t\t</tr>\n";
									$nachrichten_text .= "\t</tfoot>\n";
									$nachrichten_text .= "</table>\n";
								}
								else
								{
									$nachrichten_text .= "<p class=\"keine\">\n";
									$nachrichten_text .= "\tKeine.\n";
									$nachrichten_text .= "</p>\n";
								}

								# Erstschlag
								if($angreifer_spiotech > $verteidiger_spiotech)
								{
									$runde_starter = 'angreifer';
									$runde_anderer = 'verteidiger';

									$nachrichten_text .= "<p class=\"erstschlag angreifer\">\n";
									$nachrichten_text .= "\tDie Sensoren des Angreifers ".utf8_htmlentities($start_info[1])." sind st\xc3\xa4rker ausgebildet und erm\xc3\xb6glichen es ihm, den Erstschlag auszuf\xc3\xbchren.\n";
									$nachrichten_text .= "</p>\n";
								}
								else
								{
									$runde_starter = 'verteidiger';
									$runde_anderer = 'angreifer';

									$nachrichten_text .= "<p class=\"erstschlag verteidiger\">\n";
									$nachrichten_text .= "\tDie Sensoren des Angreifers sind denen des Verteidigers ".utf8_htmlentities($target_info[1])." nicht \xc3\xbcberlegen, weshalb letzterer den Erstschlag ausf\xc3\xbchrt.\n";
									$nachrichten_text .= "</p>\n";
								}

								if(count($verteidiger_flotte) <= 0)
								{
									$runde_starter = 'angreifer';
									$runde_anderer = 'verteidiger';
								}

								$truemmerfeld = array(0, 0, 0, 0);

								# Einzelne Runden
								for($runde = 1; $runde <= 20; $runde++)
								{
									$a = & ${$runde_starter.'_flotte'};
									$d = & ${$runde_anderer.'_flotte'};

									if(count($a) <= 0 || count($d) <= 0)
									{
										unset($a);
										unset($d);
										break;
									}

									if($runde%2)
									{
										$nachrichten_text .= "<div class=\"runde\">\n";
										$nachrichten_text .= "\t<h3>Runde ".(($runde+1)/2)."</h3>\n";
									}
									$nachrichten_text .= "\t<h4><span class=\"name\">".utf8_htmlentities(${$runde_starter.'_name'})."</span> ist am Zug</h4>\n";
									$nachrichten_text .= "\t<ol>\n";

									# Flottengesamtstaerke
									$staerke = 0;
									foreach($a as $id=>$anzahl)
									{
										if(!isset($items['ids'][$id]) || (!isset($items['schiffe'][$id]) && !isset($items['schiffe'][$id])))
											continue;
										$staerke += $items['ids'][$id]['att']*$anzahl[0];
									}
									$staerke *= pow(1.05, ${$runde_starter.'_waffentechnik'});

									while($staerke > 0 && count($d))
									{
										# Prozentual meistgeschwaechte Einheit herausfinden
										$angriff = array(1, array());
										foreach($d as $id=>$anzahl)
										{
											$prozentsatz = $anzahl[1]%$items['ids'][$id]['def'];
											if($prozentsatz == 0)
												$prozentsatz = $items['ids'][$id]['def'];
											$prozentsatz = $prozentsatz/$items['ids'][$id]['def'];
											if($prozentsatz < $angriff[0])
											{
												$angriff[0] = $prozentsatz;
												$angriff[1] = array($id);
											}
											elseif($prozentsatz == $angriff[0])
												$angriff[1][] = $id;
										}
										$angriff = $angriff[1][array_rand($angriff[1])];

										$tf_anzahl = 0;
										$d[$angriff][1] -= $staerke;
										if($d[$angriff][1] < 0)
										{
											$nachrichten_text .= "\t\t<li>Alle Einheiten des Typs ".utf8_htmlentities($items['ids'][$angriff]['name'])." (".ths($d[$angriff][0]).") werden zerst\xc3\xb6rt.</li>\n";
											$staerke = $d[$angriff][1]*(-1);
											$tf_anzahl = $d[$angriff][0];
											unset($d[$angriff]);
										}
										else
										{
											$old_anzahl = $d[$angriff][0];
											$d[$angriff][0] = ceil($d[$angriff][1]/($items['ids'][$angriff]['def']*pow(1.05, ${$runde_anderer.'_verteid'})));

											$diff = $old_anzahl-$d[$angriff][0];
											if($diff > 0)
											{
												$nachrichten_text .= "\t\t<li>".ths($diff)."&nbsp;Einheit";
												if($diff != 1)
													$nachrichten_text .= "en";
												$nachrichten_text .= " des Typs ".utf8_htmlentities($items['ids'][$angriff]['name'])." werden zerst\xc3\xb6rt. ".$d[$angriff][0]." verbleiben.</li>\n";
												$tf_anzahl = $diff;
											}
											else
												$nachrichten_text .= "\t\t<li>Ein Schiff des Typs ".utf8_htmlentities($items['ids'][$angriff]['name'])." wird angeschossen.</li>\n";
											$staerke = 0;
										}

										if(!isset($items['schiffe'][$angriff]))
											$tf_anzahl = 0;

										if($tf_anzahl > 0)
										{
											$truemmerfeld[0] += $items['schiffe'][$angriff]['ress'][0]*$tf_anzahl*0.4;
											$truemmerfeld[1] += $items['schiffe'][$angriff]['ress'][1]*$tf_anzahl*0.4;
											$truemmerfeld[2] += $items['schiffe'][$angriff]['ress'][2]*$tf_anzahl*0.4;
											$truemmerfeld[3] += $items['schiffe'][$angriff]['ress'][3]*$tf_anzahl*0.4;
										}
									}

									$nachrichten_text .= "\t</ol>\n";
									if(!$runde%2)
										$nachrichten_text .= "</div>\n";

									# Schilde des Angeschossenen je nach Schildtechnik heilen
									foreach($d as $id=>$anzahl)
									{
										$diff = $items['ids'][$id]['def']-($anzahl[1]%$items['ids'][$id]['def']);
										$add = $diff*pow(0.025, ${$runde_starter.'_schildtechnik'});
										if($add > $diff)
											$add = $diff;
										$d[$id][1] += $add;
									}

									# Vertauschen
									list($runde_starter, $runde_anderer) = array($runde_anderer, $runde_starter);
									unset($a);
									unset($d);
								}

								$nachrichten_text .= "<p>\n";
								$nachrichten_text .= "\tDer Kampf ist vor\xc3\xbcber. ";
								if(count($angreifer_flotte) == 0)
									$nachrichten_text .= "Gewinner ist der Verteidiger ".utf8_htmlentities($target_info[1]).".";
								elseif(count($verteidiger_flotte) == 0)
									$nachrichten_text .= "Gewinner ist der Angreifer ".utf8_htmlentities($start_info[1]).".";
								else
									$nachrichten_text .= "Er endet unentschieden.";
								$nachrichten_text .= "\n";
								$nachrichten_text .= "</p>\n";

								$nachrichten_text .= "<h3>Flotten des Angreifers ".utf8_htmlentities($start_info[1])."</h3>\n";
								if(count($angreifer_flotte) > 0)
								{
									$nachrichten_text .= "<table>\n";
									$nachrichten_text .= "\t<thead>\n";
									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
									$nachrichten_text .= "\t\t</tr>\n";
									$nachrichten_text .= "\t</thead>\n";
									$nachrichten_text .= "\t<tbody>\n";

									$ges_anzahl = $ges_staerke = $ges_schild = 0;
									foreach($angreifer_flotte as $id=>$anzahl)
									{
										$staerke = round($items['ids'][$id]['att']*$anzahl[0]*pow(1.05, $angreifer_waffentechnik));
										$schild = round($anzahl[1]);

										$nachrichten_text .= "\t\t<tr>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($items['ids'][$id]['name'])."</a></td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl[0])."</td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
										$nachrichten_text .= "\t\t</tr>\n";

										$ges_anzahl += $anzahl[0];
										$ges_staerke += $staerke;
										$ges_schild += $schild;
									}
									$nachrichten_text .= "\t</tbody>\n";
									$nachrichten_text .= "\t<tfoot>\n";
									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
									$nachrichten_text .= "\t\t</tr>\n";
									$nachrichten_text .= "\t</tfoot>\n";
									$nachrichten_text .= "</table>\n";
								}
								else
								{
									$nachrichten_text .= "<p class=\"keine\">\n";
									$nachrichten_text .= "\tKeine.\n";
									$nachrichten_text .= "</p>\n";
								}

								$nachrichten_text .= "<h3>Flotten des Verteidigers ".utf8_htmlentities($target_info[1])."</h3>\n";
								if(count($verteidiger_flotte) > 0)
								{
									$nachrichten_text .= "<table>\n";
									$nachrichten_text .= "\t<thead>\n";
									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-schiffstyp\">Schiffstyp</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-anzahl\">Anzahl</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-gesamtstaerke\">Gesamtst\xc3\xa4rke</th>\n";
									$nachrichten_text .= "\t\t\t<th class=\"c-gesamtschild\">Gesamtschild</th>\n";
									$nachrichten_text .= "\t\t</tr>\n";
									$nachrichten_text .= "\t</thead>\n";
									$nachrichten_text .= "\t<tbody>\n";

									$ges_anzahl = $ges_staerke = $ges_schild = 0;
									foreach($verteidiger_flotte as $id=>$anzahl)
									{
										$staerke = round($items['ids'][$id]['att']*$anzahl[0]*pow(1.05, $angreifer_waffentechnik));
										$schild = round($anzahl[1]);

										$nachrichten_text .= "\t\t<tr>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\"><a href=\"help/description.php?id=".htmlentities(urlencode($id))."\" title=\"Genauere Informationen anzeigen\">".utf8_htmlentities($items['ids'][$id]['name'])."</a></td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($anzahl[0])."</td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($staerke)."</td>\n";
										$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($schild)."</td>\n";
										$nachrichten_text .= "\t\t</tr>\n";

										$ges_anzahl += $anzahl[0];
										$ges_staerke += $staerke;
										$ges_schild += $schild;
									}
									$nachrichten_text .= "\t</tbody>\n";
									$nachrichten_text .= "\t<tfoot>\n";
									$nachrichten_text .= "\t\t<tr>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-schiffstyp\">Gesamt</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-anzahl\">".ths($ges_anzahl)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtstaerke\">".ths($ges_staerke)."</td>\n";
									$nachrichten_text .= "\t\t\t<td class=\"c-gesamtschild\">".ths($ges_schild)."</td>\n";
									$nachrichten_text .= "\t\t</tr>\n";
									$nachrichten_text .= "\t</tfoot>\n";
									$nachrichten_text .= "</table>\n";
								}
								else
								{
									$nachrichten_text .= "<p class=\"keine\">\n";
									$nachrichten_text .= "\tKeine.\n";
									$nachrichten_text .= "</p>\n";
								}
								# Flottenbestaende neu eintragen

								# Angreifer
								# Verlorene Punkte
								$angreifer_punkte = 0;
								foreach($flotte[0] as $id=>$anzahl)
								{
									if(!isset($items['schiffe'][$id]))
										continue;
									$a = $anzahl;
									if(isset($angreifer_flotte[$id]))
										$a -= $angreifer_flotte[$id][0];
									$angreifer_punkte += array_sum($items['schiffe'][$id]['ress'])*$a;
								}
								$angreifer_punkte /= 1000;
								$start_user_array['punkte'][3] -= $angreifer_punkte;
								$target_user_array['punkte'][6] += $angreifer_punkte/1000;

								$flotte[0] = array();
								foreach($angreifer_flotte as $id=>$anzahl)
									$flotte[0][$id] = $anzahl[0];

								# Verteidiger
								# Verlorene Punkte
								$verteidiger_punkte_schiffe = 0;
								$verteidiger_punkte_vert = 0;
								$verteidiger_ress = array(0,0,0,0);
								foreach($that_planet['schiffe'] as $id=>$anzahl)
								{
									if(!isset($items['schiffe'][$id]))
										continue;
									$a = $anzahl;
									if(isset($verteidiger_flotte[$id]))
										$a -= $verteidiger_flotte[$id][0];
									$verteidiger_punkte_schiffe += array_sum($items['schiffe'][$id]['ress'])*$a;
								}
								foreach($that_planet['verteidigung'] as $id=>$anzahl)
								{
									if(!isset($items['verteidigung'][$id]))
										continue;
									$a = $anzahl;
									if(isset($verteidiger_flotte[$id]))
										$a -= $verteidiger_flotte[$id][0];
									$verteidiger_punkte_vert += array_sum($items['verteidigung'][$id]['ress'])*$a;
									$verteidiger_ress[0] += $items['verteidigung'][$id]['ress'][0]*0.8;
									$verteidiger_ress[1] += $items['verteidigung'][$id]['ress'][1]*0.8;
									$verteidiger_ress[2] += $items['verteidigung'][$id]['ress'][2]*0.8;
									$verteidiger_ress[3] += $items['verteidigung'][$id]['ress'][3]*0.8;
								}
								$verteidiger_punkte_schiffe /= 1000;
								$verteidiger_punkte_vert /= 1000;

								$target_user_array['punkte'][3] -= $verteidiger_punkte_schiffe;
								$target_user_array['punkte'][4] -= $verteidiger_punkte_vert;
								$start_user_array['punkte'][6] += ($verteidiger_punkte_schiffe+$verteidiger_punkte_vert)/1000;

								$that_planet['schiffe'] = array();
								$that_planet['verteidigung'] = array();

								foreach($verteidiger_flotte as $id=>$anzahl)
								{
									if(isset($items['verteidigung'][$id]))
										$that_planet['verteidigung'][$id] = $anzahl[0];
									elseif(isset($items['schiffe'][$id]))
										$that_planet['schiffe'][$id] = $anzahl[0];
								}

								# Koordinaten vertauschen
								list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

								if(count($verteidiger_flotte) <= 0)
								{
									# Transportkapazitaet berechnen
									$transport = 0;
									foreach($flotte[0] as $id=>$anzahl)
										$transport += $items['schiffe'][$id]['trans'][0]*$anzahl;

									# Laderaumerweiterung
									$l_level = 0;
									if(isset($user_array['forschung']['F11']))
										$l_level = $user_array['forschung']['F11'];
									$transport = floor($transport*pow(1.2, $l_level));
									$transport -= array_sum($flotte[5][0]);

									# Rohstoffe erbeuten

									# Maximal erbeutbar
									$erbeut = array();
									$erbeut[0] = floor($that_planet['ress'][0]/2);
									$erbeut[1] = floor($that_planet['ress'][1]/2);
									$erbeut[2] = floor($that_planet['ress'][2]/2);
									$erbeut[3] = floor($that_planet['ress'][3]/2);
									$erbeut[4] = floor($that_planet['ress'][4]/2);

									# Im Verhaeltnis mit Ladekapazitaet abgleichen
									$k = $transport/array_sum($erbeut);
									if($k < 1)
									{
										$erbeut[0] = floor($erbeut[0]*$k);
										$erbeut[1] = floor($erbeut[1]*$k);
										$erbeut[2] = floor($erbeut[2]*$k);
										$erbeut[3] = floor($erbeut[3]*$k);
										$erbeut[4] = floor($erbeut[4]*$k);

										# Rundungsfehler ausmerzen
										$uebrig = $transport-array_sum($erbeut);
										$jedes = floor($uebrig/5);
										$erbeut[0] += $jedes;
										$erbeut[1] += $jedes;
										$erbeut[2] += $jedes;
										$erbeut[3] += $jedes;
										$erbeut[4] += $jedes;
										$uebrig = $uebrig%5;
										switch($uebrig)
										{
											case 4: $erbeut[3]++;
											case 3: $erbeut[2]++;
											case 2: $erbeut[1]++;
											case 1: $erbeut[0]++;
										}
									}

									# Rohstoffe vom Planeten abziehen und beladen
									$that_planet['ress'][0] -= $erbeut[0];
									$that_planet['ress'][1] -= $erbeut[1];
									$that_planet['ress'][2] -= $erbeut[2];
									$that_planet['ress'][3] -= $erbeut[3];
									$that_planet['ress'][4] -= $erbeut[4];

									$flotte[5][0][0] += $erbeut[0];
									$flotte[5][0][1] += $erbeut[1];
									$flotte[5][0][2] += $erbeut[2];
									$flotte[5][0][3] += $erbeut[3];
									$flotte[5][0][4] += $erbeut[4];

									# Flotte umkehren

									# Zeit neu berechnen
									$distance = fleet::get_distance($flotte[3][0], $flotte[3][1]);
									# Masse und Geschwindigkeit
									$mass = 0;
									$speed = 0;
									foreach($flotte[0] as $id=>$anzahl)
									{
										if(!isset($items['schiffe'][$id]))
											continue;
										$mass += $items['schiffe'][$id]['mass']*$anzahl;
										$speed += $items['schiffe'][$id]['speed']*$anzahl;
									}
									$mass += array_sum($flotte[5][0]);
									foreach($flotte[5][1] as $id=>$anzahl)
									{
										if(!isset($items['roboter'][$id]))
											continue;
										$mass += $items['roboter'][$id]['mass']*$anzahl;
									}
									list($time_diff) = fleet::calc($mass, $distance, $speed);
									# Geschwindigkeitsfaktor
									$time_diff *= $flotte[6];
									$flotte[1] = array($flotte[1][1], $flotte[1][1]+$time_diff);

									# Rueckflug?
									$flotte[7] = true;

									$start_user_array['flotten'][$i] = $flotte;
									uasort($start_user_array['flotten'], 'usort_fleet');
								}
								else
									unset($start_user_array['flotten'][$i]);

								unset($target_user_array['flotten']);

								# Dem Verteidiger Verteidigungsrohstoffe zurueckerstatten
								$that_planet['ress'][0] += $verteidiger_ress[0]*0.25;
								$that_planet['ress'][1] += $verteidiger_ress[1]*0.25;
								$that_planet['ress'][2] += $verteidiger_ress[2]*0.25;
								$that_planet['ress'][3] += $verteidiger_ress[3]*0.25;

								# Punkte in Nachrichten eintragen
								$nachrichten_text .= "<p>\n";
								$nachrichten_text .= "\tDer Angreifer ".utf8_htmlentities($start_info[1])." hat ".ths(round($angreifer_punkte))."&nbsp;Punkte verloren. Der Verteidiger ".utf8_htmlentities($target_info[1])." hat ".ths(round($verteidiger_punkte_schiffe+$verteidiger_punkte_vert))."&nbsp;Punkte verloren.";
								$nachrichten_text .= "</p>\n";

								if(count($verteidiger_flotte) <= 0)
								{
									# Erbeutete Rohstoffe
									$nachrichten_text .= "<p>\n";
									$nachrichten_text .= "\tDer Angreifer erbeutet ".ths($erbeut[0])."&nbsp;Carbon, ".ths($erbeut[1])."&nbsp;Aluminium, ".ths($erbeut[2])."&nbsp;Wolfram, ".ths($erbeut[3])."&nbsp;Radium und ".ths($erbeut[4])."&nbsp;Tritium.\n";
									$nachrichten_text .= "</p>\n";
								}

								if(array_sum($truemmerfeld) > 0)
								{
									# Truemmerfeld

									$truemmerfeld[0] = round($truemmerfeld[0]);
									$truemmerfeld[1] = round($truemmerfeld[1]);
									$truemmerfeld[2] = round($truemmerfeld[2]);
									$truemmerfeld[3] = round($truemmerfeld[3]);

									truemmerfeld::add($target_pos[0], $target_pos[1], $target_pos[2], $truemmerfeld[0], $truemmerfeld[1], $truemmerfeld[2], $truemmerfeld[3]);

									$nachrichten_text .= "<p>\n";
									$nachrichten_text .= "\tFolgende Tr\xc3\xbcmmer zerst\xc3\xb6rter Schiffe sind durch dem Kampf in die Umlaufbahn des Planeten gelangt: ".ths($truemmerfeld[0])."&nbsp;Carbon, ".ths($truemmerfeld[1])."&nbsp;Aluminium, ".ths($truemmerfeld[2])."&nbsp;Wolfram und ".ths($truemmerfeld[3])."&nbsp;Radium.\n";
									$nachrichten_text .= "</p>\n";
								}

								$vert_nachrichten_text = $nachrichten_text;

								$nachrichten_text .= "<p>\n";
								$nachrichten_text .= "\tDieser Kampf hat Ihnen ".ths(round(($verteidiger_punkte_schiffe+$verteidiger_punkte_vert)/1000))."&nbsp;Kampferfahrungspunkte eingebracht.\n";
								$nachrichten_text .= "</p>";

								$vert_nachrichten_text .= "<p>\n";
								$vert_nachrichten_text .= "\tDieser Kampf hat Ihnen ".ths(round($angreifer_punkte/1000))."&nbsp;Kampferfahrungspunkte eingebracht.\n";
								$vert_nachrichten_text .= "</p>";


								# User-Arrays speichern
								if(!$start_own)
								{
									$fh = fopen(DB_PLAYERS.'/'.urlencode($start_info[1]), 'w');
									if($fh)
									{
										flock($fh, LOCK_EX);

										fwrite($fh, gzcompress(serialize($start_user_array)));

										flock($fh, LOCK_UN);
										fclose($fh);
									}
								}
								if(!$target_own)
								{
									$fh = fopen(DB_PLAYERS.'/'.urlencode($target_info[1]), 'w');
									if($fh)
									{
										flock($fh, LOCK_EX);

										fwrite($fh, gzcompress(serialize($target_user_array)));

										flock($fh, LOCK_UN);
										fclose($fh);
									}
								}

								# Nachrichten versenden
								messages::new_message(array($start_info[1]=>1), '', 'Angriff Ihrer Flotte auf '.$flotte[3][0], $nachrichten_text, true);
								messages::new_message(array($target_info[1]=>1), $start_info[1], 'Angriff einer fremden Flotte auf '.$flotte[3][0], $vert_nachrichten_text, true);

								unset($start_user_array);
								unset($target_user_array);
								if(isset($that_planet))
									unset($that_planet);
							}
							elseif($flotte[2] == 5)
							{
								# Spionieren

								# Planeten herausfinden, der spioniert werden soll
								$start_pos = explode(':', $flotte[3][0]);
								$start_info = universe::get_planet_info($start_pos[0], $start_pos[1], $start_pos[2]);
								$start_own = ($start_info[1] == $ev_username);

								$target_pos = explode(':', $flotte[3][1]);
								$target_info = universe::get_planet_info($target_pos[0], $target_pos[1], $target_pos[2]);
								$target_own = ($target_info[1] == $ev_username);

								if(!$target_own && $target_info[1])
								{
									# Eventhandler des Angegriffenen laufenlassen
									eventhandler::run_eventhandler($target_info[1], false);
								}

								# User-Arrays bekommen
								if($start_own)
									$start_user_array = & $user_array;
								else
									$start_user_array = get_user_array($start_info[1]);

								if($target_info[1])
								{
									if($target_own)
										$target_user_array = & $user_array;
									else
										$target_user_array = get_user_array($target_info[1]);

									$planets = array_keys($target_user_array['planets']);
									foreach($planets as $planet)
									{
										if($target_user_array['planets'][$planet]['pos'] == $flotte[3][1])
										{
											$that_planet = & $target_user_array['planets'][$planet];
											break;
										}
									}
								}

								# Flotte nun zurueckrufen

								$new_flotte = $flotte;

								# Zeit
								$time_diff = $new_flotte[1][1]-$new_flotte[1][0];
								$new_flotte[1] = array($new_flotte[1][1], $new_flotte[1][1]+$time_diff);

								# Start und Ziel vertauschen
								list($new_flotte[3][0], $new_flotte[3][1]) = array($new_flotte[3][1], $new_flotte[3][0]);

								# Rueckflug?
								$new_flotte[7] = true;

								if($start_own)
								{
									if($target_info[1])
									{
										unset($target_user_array['flotten'][$i]);
										$fh = fopen(DB_PLAYERS.'/'.urlencode($target_info[1]), 'w');
										if($fh)
										{
											flock($fh, LOCK_EX);

											fwrite($fh, gzcompress(serialize($target_user_array)));

											flock($fh, LOCK_UN);
											fclose($fh);
										}
									}
									else
										$fh = true;

									if($fh)
									{
										$user_array['flotten'][$i] = $new_flotte;
										uasort($user_array['flotten'], 'usort_fleet');
									}
								}
								else
								{
									$start_user_array['flotten'][$i] = $new_flotte;
									uasort($start_user_array['flotten'], 'usort_fleet');

									$fh = fopen(DB_PLAYERS.'/'.urlencode($start_info[1]), 'w');
									if($fh)
									{
										flock($fh, LOCK_EX);

										fwrite($fh, gzcompress(serialize($start_user_array)));

										flock($fh, LOCK_UN);
										fclose($fh);

										unset($user_array['flotten'][$i]);
									}
								}

								if($fh)
								{
									# Nachrichten verschicken

									if(!$target_info[1])
									{
										# Zielplanet ist nicht besiedelt
										$message_text = "<h3>Spionagebericht des Planeten ".$flotte[3][1]."</h3>\n";
										$message_text .= "<div id=\"spionage-planet\">\n";
										$message_text .= "\t<h4>Planet</h4>\n";
										$message_text .= "\t<dl class=\"planet_".universe::get_planet_class($target_pos[0], $target_pos[1], $target_pos[2])."\">\n";
										$message_text .= "\t\t<dt class=\"c-felder\">Felder</dt>\n";
										$message_text .= "\t\t<dd class=\"c-felder\">".ths($target_info[0])."</dd>\n";
										$message_text .= "\t</dl>\n";
										$message_text .= "</div>";

										if(count($start_user_array['planets']) < 15)
										{
											$message_text .= "\n<p class=\"besiedeln\">";
											$message_text .= "\n\t<a href=\"flotten.php?action=besiedeln&amp;action_galaxy=".htmlentities(urlencode($target_pos[0]))."&amp;action_system=".htmlentities(urlencode($target_pos[1]))."&amp;action_planet=".htmlentities(urlencode($target_pos[2]))."\" title=\"Schicken Sie ein Besiedelungsschiff zu diesem Planeten\">Besiedeln</a>";
											$message_text .= "\n</p>";
										}

										messages::new_message(array($start_info[1]=>2), '', 'Spionage des Planeten '.$flotte[3][1], $message_text, true);
									}
									else
									{
										# Zielplanet ist besiedelt

										# Spionagetechnikdifferenz ausrechnen
										$start_level = $flotte[0]['S5']-1;
										if(isset($start_user_array['forschung']['F1']))
											$start_level += $start_user_array['forschung']['F1'];
										$target_level = 0;
										if(isset($target_user_array['forschung']['F1']))
											$target_level += $target_user_array['forschung']['F1'];
										if($target_level == 0)
											$diff = 5;
										else
											$diff = floor(pow($start_level/$target_level, 2));
										if($diff > 5)
											$diff = 5;

										$message_text = "<h3>Spionagebericht des Planeten \xe2\x80\x9e".$target_info[2]."\xe2\x80\x9c (".$flotte[3][1].", Eigent\xc3\xbcmer: ".$target_info[1].")</h3>\n";
										$message_text .= "<div id=\"spionage-planet\">\n";
										$message_text .= "\t<h4>Planet</h4>\n";
										$message_text .= "\t<dl class=\"planet_".universe::get_planet_class($target_pos[0], $target_pos[1], $target_pos[2])."\">\n";
										$message_text .= "\t\t<dt class=\"c-felder\">Felder</dt>\n";
										$message_text .= "\t\t<dd class=\"c-felder\">".ths($that_planet['size'][1])."</dd>\n";
										$message_text .= "\t</dl>\n";
										$message_text .= "</div>";

										$message_text2 = array();
										switch($diff)
										{
											case 5: # Roboter zeigen
												$next = &$message_text2[];
												$next = "\n<div id=\"spionage-roboter\">";
												$next .= "\n\t<h4>Roboter</h4>";
												$next .= "\n\t<ul>";
												$roboter = array_keys($items['roboter']);
												foreach($roboter as $id)
												{
													if(!isset($that_planet['roboter'][$id]) || $that_planet['roboter'][$id] <= 0)
														continue;
													$next .= "\n\t\t<li>".$items['roboter'][$id]['name']." <span class=\"anzahl\">(".ths($that_planet['roboter'][$id]).")</span></li>";
												}
												$next .= "\n\t</ul>";
												$next .= "\n</div>";
												unset($next);
											case 4: # Forschung zeigen
												$next = &$message_text2[];
												$next = "\n<div id=\"spionage-forschung\">";
												$next .= "\n\t<h4>Forschung</h4>";
												$next .= "\n\t<ul>";
												$forschung = array_keys($items['forschung']);
												foreach($forschung as $id)
												{
													if(!isset($target_user_array['forschung'][$id]) || $target_user_array['forschung'][$id] <= 0)
														continue;
													$next .= "\n\t\t<li>".$items['forschung'][$id]['name']." <span class=\"stufe\">(Level&nbsp;".ths($target_user_array['forschung'][$id]).")</span>";
												}
												$next .= "\n\t</ul>";
												$next .= "\n</div>";
												unset($next);
											case 3: # Schiffe und Verteidigungsanlagen anzeigen
												$next = &$message_text2[];
												$next = "\n<div id=\"spionage-schiffe\">";
												$next .= "\n\t<h4>Schiffe</h4>";
												$next .= "\n\t<ul>";
												$schiffe = array_keys($items['schiffe']);
												foreach($schiffe as $id)
												{
													if(!isset($that_planet['schiffe'][$id]) || $that_planet['schiffe'][$id] <= 0)
														continue;
													$next .= "\n\t\t<li>".$items['schiffe'][$id]['name']." <span class=\"anzahl\">(".ths($that_planet['schiffe'][$id]).")</span></li>";
												}
												$next .= "\n\t</ul>";
												$next .= "\n</div>";
												$next .= "\n<div id=\"spionage-verteidigung\">";
												$next .= "\n\t<h4>Verteidigung</h4>";
												$next .= "\n\t<ul>";
												$verteidigung = array_keys($items['verteidigung']);
												foreach($verteidigung as $id)
												{
													if(!isset($that_planet['verteidigung'][$id]) || $that_planet['verteidigung'][$id] <= 0)
														continue;
													$next .= "\n\t\t<li>".$items['verteidigung'][$id]['name']." <span class=\"anzahl\">(".ths($that_planet['verteidigung'][$id]).")</span></li>";
												}
												$next .= "\n\t</ul>";
												$next .= "\n</div>";
												unset($next);
											case 2: # Gebaeude anzeigen
												$next = &$message_text2[];
												$next = "\n<div id=\"spionage-gebaeude\">";
												$next .= "\n\t<h4>Geb\xc3\xa4ude</h4>";
												$next .= "\n\t<ul>";
												$gebaeude = array_keys($items['gebaeude']);
												foreach($gebaeude as $id)
												{
													if(!isset($that_planet['gebaeude'][$id]) || $that_planet['gebaeude'][$id] <= 0)
														continue;
													$next .= "\n\t\t<li>".$items['gebaeude'][$id]['name']." <span class=\"stufe\">(Stufe&nbsp;".ths($that_planet['gebaeude'][$id]).")</span></li>";
												}
												$next .= "\n\t</ul>";
												$next .= "\n</div>";
												unset($next);
											case 1: # Rohstoffe anzeigen
												$next = &$message_text2[];
												$next = "\n<div id=\"spionage-rohstoffe\">";
												$next .= "\n\t<h4>Rohstoffe</h4>";
												$next .= "\n\t".format_ress($that_planet['ress'], 1, true);
												$next .= "</div>";
												unset($next);
										}
										$message_text .= implode('', array_reverse($message_text2));

										messages::new_message(array($start_info[1]=>2), '', 'Spionage des Planeten '.$flotte[3][1], $message_text, true);
										messages::new_message(array($target_info[1]=>2), $start_info[1], 'Fremde Flotte auf dem Planeten '.$flotte[3][1], "Eine fremde Flotte vom Planeten \xe2\x80\x9e".$start_info[2]."\xe2\x80\x9c (".$flotte[3][0].", Eigent\xc3\xbcmer: ".$start_info[1].") wurde von Ihrem Planeten \xe2\x80\x9e".$target_info[2]."\xe2\x80\x9c (".$flotte[3][1].') aus bei der Spionage gesichtet.');
									}
								}
								unset($start_user_array);
								if($target_info[1])
									unset($target_user_array);
								if(isset($that_planet))
									unset($that_planet);
							}
							elseif($flotte[2] == 4 || $flotte[2] == 6)
							{
								# Transport oder Stationieren

								# Planeten herausfinden, auf den Rohstoffe etc. geladen werden sollen
								$target_pos = explode(':', $flotte[3][1]);
								$target_info = universe::get_planet_info($target_pos[0], $target_pos[1], $target_pos[2]);
								$target_own = ($target_info[1] == $ev_username);

								$start_pos = explode(':', $flotte[3][0]);
								$start_info = universe::get_planet_info($start_pos[0], $start_pos[1], $start_pos[2]);
								$start_own = ($start_info[1] == $ev_username);

								if(!$target_info[1])
								{
									# Planet ist nicht besiedelt

									# Flotte zurueckrufen
									# Zeit
									$time_diff = $flotte[1][1]-$flotte[1][0];
									$flotte[1] = array($flotte[1][1], $flotte[1][1]+$time_diff);

									# Koordinaten vertauschen
									list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

									# Rueckflug?
									$flotte[7] = true;

									$user_array['flotten'][$i] = $flotte;

									usort($user_array['flotten'], 'usort_fleet');

									messages::new_message(array($ev_username=>3), '', 'Transport fehlgeschlagen', 'Ihre Flotte erreicht den Planeten '.$flotte[3][0]." und will ihre Rohstoffe dort abliefern, jedoch findet Sie niemanden vor, der die Rohstoffe entgegennimmt. Frustriert, dass sie umsonst einen so weiten Flug durchgef\xc3\xbchrt hat, tritt sie den Heimweg an.");
								}
								else
								{
									if($start_own)
										$start_user_array = & $user_array;
									else
										$start_user_array = get_user_array($start_info[1]);
									if($target_own)
										$target_user_array = & $user_array;
									else
										$target_user_array = get_user_array($target_info[1]);

									$planets = array_keys($target_user_array['planets']);
									foreach($planets as $planet)
									{
										if($target_user_array['planets'][$planet]['pos'] == $flotte[3][1])
										{
											$that_planet = & $target_user_array['planets'][$planet];
											break;
										}
									}


									# Rohstoffe und Roboter abliefern
									if(isset($flotte[5][0][0]))
										$that_planet['ress'][0] += $flotte[5][0][0];
									if(isset($flotte[5][0][1]))
										$that_planet['ress'][1] += $flotte[5][0][1];
									if(isset($flotte[5][0][2]))
										$that_planet['ress'][2] += $flotte[5][0][2];
									if(isset($flotte[5][0][3]))
										$that_planet['ress'][3] += $flotte[5][0][3];
									if(isset($flotte[5][0][4]))
										$that_planet['ress'][4] += $flotte[5][0][4];

									foreach($flotte[5][1] as $id=>$anzahl)
									{
										if(!isset($that_planet['roboter'][$id]))
										{
											$that_planet['roboter'][$id] = 0;
											$that_planet['ids'][$id] = & $that_planet['roboter'][$id];
										}
										$that_planet['roboter'][$id] += $anzahl;
									}

									if($flotte[2] == 4)
									{
										# Transport

										/* FEHLT NOCH!
										Bei ankommenden Robotern Bauzeit verkürzen, Produktion aktualisieren. */

										$new_flotte = $flotte;

										# Flotte nun zurueckschicken

										# Masse neu berechnen
										$mass = 0;
										$speed = 0;
										foreach($new_flotte[0] as $id=>$anzahl)
										{
											$mass += $items['schiffe'][$id]['mass']*$anzahl;
											$speed += $items['schiffe'][$id]['speed']*$anzahl;
										}
										$distance = fleet::get_distance($new_flotte[3][1], $new_flotte[3][0]);
										list($time) = fleet::calc($mass, $distance, $speed);
										$new_flotte[1] = array($new_flotte[1][1], $new_flotte[1][1]+$time);

										# Koordinaten vertauschen
										list($new_flotte[3][0], $new_flotte[3][1]) = array($new_flotte[3][1], $new_flotte[3][0]);

										# Rohstoffe entfernen
										$new_flotte[5] = array(0=>array(0,0,0,0,0), 1=>array());

										# Rueckflug?
										$new_flotte[7] = true;

										if($start_own && $target_own)
										{
											$user_array['flotten'][$i] = $new_flotte;
											usort($user_array['flotten'], 'usort_fleet');
										}
										elseif($start_own && !$target_own)
										{
											$user_array['flotten'][$i] = $new_flotte;
											usort($user_array['flotten'], 'usort_fleet');
											unset($target_user_array['flotten'][$i]);
											$fh = fopen(DB_PLAYERS.'/'.urlencode($target_info[1]), 'w');
											flock($fh, LOCK_EX);
											fwrite($fh, gzcompress(serialize($target_user_array)));
											flock($fh, LOCK_UN);
											fclose($fh);
										}
										elseif(!$start_own && $target_own)
										{
											unset($user_array['flotten'][$i]);
											$start_user_array['flotten'][$i] = $new_flotte;
											usort($start_user_array['flotten'], 'usort_fleet');
											$fh = fopen(DB_PLAYERS.'/'.urlencode($start_info[1]), 'w');
											flock($fh, LOCK_EX);
											fwrite($fh, gzcompress(serialize($start_user_array)));
											flock($fh, LOCK_UN);
											fclose($fh);
										}

										# Nachrichten verschicken
										if($start_own && $target_own)
										{
											# Transport von eigenem zu eigenem Planeten

											if($user_array['receive'][3][0])
											{
												$message_text = "Ihre Flotte erreicht Ihren Planeten \xe2\x80\x9e".$target_info[2]."\xe2\x80\x9c (".$flotte[3][1].') und liefert ihre Waren ab: '.ths(&$flotte[5][0][0], true).$nbsp.'Carbon, '.ths(&$flotte[5][0][1], true).$nbsp.'Aluminium, '.ths(&$flotte[5][0][2], true).$nbsp.'Wolfram, '.ths(&$flotte[5][0][3], true).$nbsp.'Radium, '.ths(&$flotte[5][0][4], true).$nbsp.'Tritium';
												if(array_sum($flotte[5][1]) > 0)
												{
													$message_text .= '; ';
													$roboter_text = array();
													foreach($flotte[5][1] as $id=>$anzahl)
													{
														if(!isset($items['roboter'][$id]) || $anzahl <= 0)
															continue;
														$roboter_text[] = $items['roboter'][$id]['name'].': '.ths($anzahl, true);
													}
													$roboter_text = implode(', ', $roboter_text);
													$message_text .= $roboter_text;
												}
												$message_text .= '.';

												messages::new_message(array($ev_username=>3), '', 'Ankunft Ihres Transportes auf '.$flotte[3][0], $message_text);
											}
										}
										else
										{
											# Transport von eigenem zu fremden Planeten oder umgekehrt

											$message_text_sender = "Ihre Flotte erreicht den Planeten \xe2\x80\x9e".$target_info[2]."\xe2\x80\x9c (".$flotte[3][1].", Eigent\xc3\xbcmer: ".$target_info[1].') und liefert ihre Waren ab: ';
											$message_text_receiver = "Eine Flotte vom Planeten \xe2\x80\x9e".$start_info[2]."\xe2\x80\x9c (".$flotte[3][1].", Eigent\xc3\xbcmer: ".$start_info[1].") erreicht Ihren Planeten \xe2\x80\x9e".$target_info[2]."\xe2\x80\x9c (".$flotte[3][1].') und liefert ihre Waren ab: ';

											$add = ths(&$flotte[5][0][0], true).$nbsp.'Carbon, '.ths(&$flotte[5][0][1], true).$nbsp.'Aluminium, '.ths(&$flotte[5][0][2], true).$nbsp.'Wolfram, '.ths(&$flotte[5][0][3], true).$nbsp.'Radium, '.ths(&$flotte[5][0][4], true).$nbsp.'Tritium';
											if(array_sum($flotte[5][1]) > 0)
											{
												$add .= '; ';
												$roboter_text = array();
												foreach($flotte[5][1] as $id=>$anzahl)
												{
													if(!isset($items['roboter'][$id]) || $anzahl <= 0)
														continue;
													$roboter_text[] = $items['roboter'][$id]['name'].': '.ths($anzahl, true);
												}
												$roboter_text = implode(', ', $roboter_text);
												$add .= $roboter_text;
											}
											$add .= '.';
											$message_text_sender .= $add;
											$message_text_receiver .= $add;

											if($start_user_array['receive'][3][0])
												messages::new_message(array($start_info[1]=>3), '', 'Ankunft Ihres Transportes auf '.$flotte[3][1], $message_text_sender);
											messages::new_message(array($target_info[1]=>3), '', 'Ankunft eines fremden Transportes auf '.$flotte[3][1], $message_text_receiver);
										}
									}
									else
									{
										# Stationieren

										# Nachricht verschicken

										$schiffe_string = array();
										foreach($flotte[0] as $id=>$anzahl)
										{
											if(!isset($items['schiffe'][$id]) || $anzahl <= 0)
												continue;
											$schiffe_string[] = $items['schiffe'][$id]['name'].': '.ths($anzahl, true);
										}
										$schiffe_string = implode(', ', $schiffe_string);
										$message_text = 'Ihre Flotte ('.$schiffe_string.") erreicht Ihren Planeten \xe2\x80\x9e".$target_info[2]."\xe2\x80\x9c (".$flotte[3][1].').';

										$rohstoff_sum = array_sum($flotte[5][0]);
										$roboter_sum = array_sum($flotte[5][1]);
										if($rohstoff_sum > 0 || $roboter_sum > 0)
										{
											$message_text .= "\n\nDie Flotte liefert folgendes Transportgut ab: ";
											if($rohstoff_sum > 0)
											{
												$message_text .= ths(&$flotte[5][0][0], true).$nbsp.'Carbon, '.ths(&$flotte[5][0][1], true).$nbsp.'Aluminium, '.ths(&$flotte[5][0][2], true).$nbsp.'Wolfram, '.ths(&$flotte[5][0][3], true).$nbsp.'Radium, '.ths(&$flotte[5][0][4], true).$nbsp.'Tritium';
												if($roboter_sum > 0)
													$message_text .= '; ';
											}
											if($roboter_sum > 0)
											{
												$roboter_string = array();
												foreach($flotte[5][1] as $id=>$anzahl)
												{
													if(!isset($items['roboter'][$id]) || $anzahl <= 0)
														continue;
													$roboter_string[] = $items['roboter'][$id]['name'].': '.$anzahl;
												}
												$roboter_string = implode('; ', $roboter_string);
												$message_text .= $roboter_string;
											}
										}

										/* FEHLT NOCH!
										Bei ankommenden Robotern Bauzeit verkürzen, Produktion aktualisieren. */

										$tritium = $flotte[4][0]/2;
										$flugerfahrung = $tritium/1000;
										$message_text .= "\n\nDie Flotte liefert ".ths(round($tritium), true)." \xc3\xbcbersch\xc3\xbcssigen Tritiums ab. Diese Flug hat Ihnen ".ths(round($flugerfahrung), true).$nbsp.'Flugerfahrungspunkte eingebracht.';

										$user_array['punkte'][5] += $flugerfahrung;

										$that_planet['ress'][4] += $tritium;

										# Flotte hinzuzaehlen
										foreach($flotte[0] as $id=>$anzahl)
										{
											if(!isset($that_planet['schiffe'][$id]))
											{
												$that_planet['schiffe'][$id] = 0;
												$that_planet['ids'][$id] = & $that_planet['ids'][$id];
											}
											$that_planet['schiffe'][$id] += $anzahl;
										}

										# Flottenauftrag entfernen
										unset($user_array['flotten'][$i]);

										# Nachricht versenden
										if($user_array['receive'][3][0])
											messages::new_message(array($ev_username=>3), '', 'Stationierung auf '.$flotte[3][1], $message_text);
									}

									unset($start_user_array);
									unset($target_user_array);
									if(isset($that_planet))
										unset($that_planet);
								}
							}
						}

						if(isset($user_array['flotten'][$i]))
							$flotte = $user_array['flotten'][$i];

						if($flotte[7] && $flotte[1][1] <= time())
						{
							# Rueckflug

							# Planeten herausfinden, auf den Rohstoffe etc. geladen werden sollen
							$planet_index = false;
							$planets = array_keys($user_array['planets']);
							foreach($planets as $planet)
							{
								if($user_array['planets'][$planet]['pos'] == $flotte[3][1])
								{
									$planet_index = $planet;
									break;
								}
							}
							if($planet_index !== false)
							{
								$that_planet = & $user_array['planets'][$planet];

								# Rohstoffe abliefern
								if(isset($flotte[5][0][0]))
									$that_planet['ress'][0] += $flotte[5][0][0];
								if(isset($flotte[5][0][1]))
									$that_planet['ress'][1] += $flotte[5][0][1];
								if(isset($flotte[5][0][2]))
									$that_planet['ress'][2] += $flotte[5][0][2];
								if(isset($flotte[5][0][3]))
									$that_planet['ress'][3] += $flotte[5][0][3];
								if(isset($flotte[5][0][4]))
									$that_planet['ress'][4] += $flotte[5][0][4];

								# Roboter abliefern
								foreach($flotte[5][1] as $id=>$anzahl)
								{
									if(!isset($that_planet['roboter'][$id]))
									{
										$that_planet['roboter'][$id] = 0;
										$that_planet['ids'][$id] = & $that_planet['roboter'][$id];
									}
									$that_planet['roboter'][$id] += $anzahl;
								}

								# Ueberschuessiges Tritium abliefern
								$that_planet['ress'][4] += $flotte[4][1];

								# Schiffe zurueckkommen lassen
								foreach($flotte[0] as $id=>$anzahl)
								{
									if(!isset($that_planet['schiffe'][$id]))
									{
										$that_planet['schiffe'][$id] = 0;
										$that_planet['ids'][$id] = & $that_planet['schiffe'][$id];
									}
									$that_planet['schiffe'][$id] += $anzahl;
								}

								# Eintrag entfernen
								unset($user_array['flotten'][$i]);

								# Flugerfarung
								$flugerfahrung = $flotte[4][0]/1000;
								$user_array['punkte'][5] += $flugerfahrung;
								highscores::recalc();

								# Nachricht hinterlassen
								if($user_array['receive'][$types_message_types[$flotte[2]]][1])
								{
									$schiffe_string = array();
									foreach($flotte[0] as $id=>$anzahl)
									{
										if(!isset($items['schiffe'][$id]) || $anzahl <= 0)
											continue;
										$schiffe_string[] = $items['schiffe'][$id]['name'].': '.ths($anzahl, true);
									}
									$schiffe_string = implode(', ', $schiffe_string);
									$message_text = 'Eine Ihrer Flotten ('.$schiffe_string.') kommt ';
									$start_pos = explode(':', $flotte[3][0]);
									$start_info = universe::get_planet_info($start_pos[0], $start_pos[1], $start_pos[2]);
									if($start_info[1] == $ev_username)
										$message_text .= "von Ihrem Planeten \xe2\x80\x9e".$start_info[2]."\xe2\x80\x9c (".$flotte[3][0].')';
									elseif($start_info[1])
										$message_text .= "vom Planeten \xe2\x80\x9e".$start_info[2]."\xe2\x80\x9c (".$flotte[3][0].', Eigentümer: '.$start_info[1].')';
									else
										$message_text .= 'vom Planeten '.$flotte[3][0].' (unbesiedelt)';
									$message_text .= " zur\xc3\xbcck zu Ihrem Planeten \xe2\x80\x9e".$that_planet['name']."\xe2\x80\x9c (".$flotte[3][1]."). Dieser Flug hat Ihnen ".ths(round($flugerfahrung), true).$nbsp."Flugerfahrungspunkte eingebracht.";
									if($flotte[4][1] > 0)
										$message_text .= ' Die Flotte bringt '.ths($flotte[4][1], true)." \xc3\xbcbersch\xc3\xbcssigen Tritiums mit.";

									$rohstoff_sum = array_sum($flotte[5][0]);
									$roboter_sum = array_sum($flotte[5][1]);
									if($rohstoff_sum > 0 || $roboter_sum > 0)
									{
										$message_text .= "\n\nDie Flotte liefert au\xc3\x9ferdem folgendes Transportgut ab:\n";
										if($rohstoff_sum > 0)
										{
											$message_text .= ths(&$flotte[5][0][0], true).$nbsp.'Carbon, '.ths(&$flotte[5][0][1], true).$nbsp.'Aluminium, '.ths(&$flotte[5][0][2], true).$nbsp.'Wolfram, '.ths(&$flotte[5][0][3], true).$nbsp.'Radium, '.ths(&$flotte[5][0][4], true).$nbsp.'Tritium';
											if($roboter_sum > 0)
												$message_text .= ",\n";
											else
												$message_text .= '.';
										}
										if($roboter_sum > 0)
										{
											$roboter_text = array();
											foreach($flotte[5][1] as $id=>$anzahl)
											{
												if(!isset($items['roboter'][$id]) || $anzahl <= 0)
													continue;
												$roboter_text[] = $items['roboter'][$id]['name'].': '.ths($anzahl, true);
											}
											$roboter_text = implode(', ', $roboter_text);
											$message_text .= $roboter_text.'.';
										}
									}

									messages::new_message(array($ev_username => $types_message_types[$flotte[2]]), '', "R\xc3\xbcckkehr von ".$flotte[3][0], $message_text);

									/* FEHLT NOCH!
									Bei ankommenden Robotern Bauzeit verkürzen, Produktion aktualisieren. */
								}
								unset($that_planet);
							}
						}
					}
				}
			}

			write_user_array($ev_username, $user_array);
			if(isset($user_array_save))
			{
				$user_array = $user_array_save;
				unset($user_array_save);
			}
			if(isset($username_save))
				$GLOBALS['_SESSION']['username'] = $username_save;
			if(isset($_SESSION['act_planet']))
				$GLOBALS['this_planet'] = & $user_array['planets'][$_SESSION['act_planet']];
		}
	}

	function print_this_planet()
	{
		global $this_planet;
		print_r($this_planet);
	}
?>