<?php
	require('scripts/include.php');

	if(isset($_GET['cancel']) && isset($user_array['flotten'][$_GET['cancel']]) && !$user_array['flotten'][$_GET['cancel']][7] && fleet::check_own($user_array['flotten'][$_GET['cancel']]))
	{
		# Flotte zurueckrufen

		$flotte = & $user_array['flotten'][$_GET['cancel']];

		$time_diff = $flotte[1][1]-$flotte[1][0];
		$time_left = $flotte[1][1]-time();
		$time_done = time()-$flotte[1][0];

		$flotte[4][1] = round($flotte[4][0]*($time_left/$time_diff)); # Ueberschuessiges Tritium
		$flotte[4][0] = 0; # Tritium soll nicht zu Punkten gerechnet werden

		# Neue Zeiten definieren
		$flotte[1][0] = time();
		$flotte[1][1] = time()+$time_done;

		$flotte[7] = true; # Rueckflug

		list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]); # Start- und Zielkoordinaten vertauschen

		uasort($user_array['flotten'], 'usort_fleet');

		write_user_array();

		eventhandler::add_event($flotte[1][1]);

		# Wenn der Empfaenger ein fremder ist, muss bei ihm auch der Auftrag geloescht werden
		$target = explode(':', $flotte[3][0]);
		$target_info = universe::get_planet_info($target[0], $target[1], $target[2]);
		if($target_info[1] && $target_info[1] != $_SESSION['username'])
		{
			$that_user_array = get_user_array($target_info[1]);
			unset($that_user_array['flotten'][$_GET['cancel']]);
			$fh = fopen(DB_PLAYERS.'/'.urlencode($target_info[1]), 'w');
			flock($fh, LOCK_EX);
			fwrite($fh, gzcompress(serialize($that_user_array)));
			flock($fh, LOCK_UN);
			fclose($fh);
		}

		logfile::action('13', $_GET['cancel']);

		delete_request();
	}

	login_gui::html_head();
?>
<ul id="planeten-umbenennen">
	<li><a href="scripts/rename.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Planeten umbenennen/aufgeben" accesskey="u" tabindex="2"><kbd>u</kbd>mbenennen</a></li>
</ul>
<?php
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

	if(isset($user_array['messages']))
	{
		foreach($user_array['messages'] as $cat=>$messages)
		{
			foreach($messages as $message_id=>$unread)
			{
				if(!is_file(DB_MESSAGES.'/'.$message_id) || !is_readable(DB_MESSAGES.'/'.$message_id))
					continue;

				if($unread && $cat != 8)
				{
					$ncount[$cat]++;
					$ges_ncount++;
				}
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
		$link .= SESSION_COOKIE.'='.urlencode(session_id());
?>
<p class="neue-nachrichten">
	<a href="<?=htmlentities($link)?>" title="<?=$title?>" accesskey="n" tabindex="1">Sie haben <?=htmlentities($ges_ncount)?> neue <kbd>N</kbd>achricht<?=($ges_ncount != 1) ? 'en' : ''?>.</a>
</p>
<?php
	}

	if(isset($user_array['flotten']) && count($user_array['flotten']) > 0)
	{
?>
<h2>Flottenbewegungen</h2>
<dl id="flotten">
<?php
		$infos = array();
		foreach($user_array['flotten'] as $flotte)
		{
			if(!in_array($flotte[3][0], $infos))
				$infos[] = $flotte[3][0];
			if(!in_array($flotte[3][1], $infos))
				$infos[] = $flotte[3][1];
		}
		$infos = universe::get_planet_info($infos);

		$countdowns = array();
		foreach($user_array['flotten'] as $i=>$flotte)
		{
			$own = fleet::check_own($flotte);
			$flotte_string = array();
			foreach($flotte[0] as $id=>$anzahl)
			{
				if($anzahl > 0 && isset($items['schiffe'][$id]))
					$flotte_string[] = utf8_htmlentities($items['schiffe'][$id]['name']).': '.ths($anzahl);
			}
			$flotte_string = implode('; ', $flotte_string);

			$from_info = $infos[$flotte[3][0]];
			$to_info = $infos[$flotte[3][1]];

			$string = 'Eine <span class="beschreibung schiffe" title="'.$flotte_string.'">';
			if($own)
				$string .= 'Ihrer Flotten';
			else
				$string .= 'fremde Flotte';
			$string .= '</span> ';
			if(!$flotte[7]) # Hinflug
			{
				if($from_info[1] == $_SESSION['username'])
				{
					$string .= 'von Ihrem Planeten &bdquo;'.utf8_htmlentities($from_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][0]).') erreicht ';
					if($to_info[1] == $_SESSION['username'])
						$string .= 'Ihren Planeten &bdquo;'.utf8_htmlentities($to_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][1]).')';
					else
					{
						$string .= 'den Planeten ';
						if($to_info[1])
							$string .= '&bdquo;'.utf8_htmlentities($to_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][1]).', Eigentümer: '.utf8_htmlentities($to_info[1]).')';
						else
							$string .= utf8_htmlentities($flotte[3][1]).' (unbesiedelt)';
					}
				}
				else
					$string .= 'vom Planeten &bdquo;'.utf8_htmlentities($from_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][0]).', Eigentümer: '.utf8_htmlentities($from_info[1]).') erreicht Ihren Planeten &bdquo;'.utf8_htmlentities($to_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][1]).')';
				$string .= '. Ihr Auftrag lautet ';
			}
			else # Rueckflug
			{
				$string .= 'kommt ';
				if($from_info[1] == $_SESSION['username'])
					$string .= 'von Ihrem Planeten &bdquo;'.utf8_htmlentities($from_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][0]).')';
				elseif($from_info[1])
					$string .= 'vom Planeten &bdquo;'.utf8_htmlentities($from_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][0]).', Eigentümer: '.utf8_htmlentities($from_info[1]).')';
				else
					$string .= 'vom Planeten '.utf8_htmlentities($flotte[3][0]).' (unbesiedelt)';
				$string .= ' zurück zu Ihrem Planeten &bdquo;'.utf8_htmlentities($to_info[2]).'&ldquo; ('.utf8_htmlentities($flotte[3][1]).'). Ihr Auftrag lautete ';
			}

			$ress_string = array();
			if(array_sum($flotte[5][0]) > 0)
			{
				if(isset($flotte[5][0][0]))
					$ress_string[] = 'Carbon: '.ths($flotte[5][0][0]);
				if(isset($flotte[5][0][1]))
					$ress_string[] = 'Aluminium: '.ths($flotte[5][0][1]);
				if(isset($flotte[5][0][2]))
					$ress_string[] = 'Wolfram: '.ths($flotte[5][0][2]);
				if(isset($flotte[5][0][3]))
					$ress_string[] = 'Radium: '.ths($flotte[5][0][3]);
				if(isset($flotte[5][0][4]))
					$ress_string[] = 'Tritium: '.ths($flotte[5][0][4]);
			}

			foreach($flotte[5][1] as $id=>$anzahl)
			{
				if($anzahl > 0 && isset($items['roboter'][$id]))
					$ress_string[] = utf8_htmlentities($items['roboter'][$id]['name']).': '.ths($anzahl);
			}

			$ress_string = implode(', ', $ress_string);

			$string .= '<span class="beschreibung transport"';
			if(strlen($ress_string) > 0)
				$string .= ' title="'.$ress_string.'"';
			$string .= '>';
			if(isset($type_names[$flotte[2]]))
				$string .= htmlentities($type_names[$flotte[2]]);
			else
				$string .= utf8_htmlentities($flotte[2]);
			$string .= '</span>.';
?>
	<dt class="<?=$own ? 'eigen' : 'fremd'?> type-<?=utf8_htmlentities($flotte[2])?> <?=$flotte[7] ? 'rueck' : 'hin'?>flug">
		<?=$string."\n"?>
	</dt>
	<dd class="<?=$own ? 'eigen' : 'fremd'?> type-<?=utf8_htmlentities($flotte[2])?> <?=$flotte[7] ? 'rueck' : 'hin'?>flug" id="restbauzeit-<?=utf8_htmlentities($i)?>">Ankunft: <?=date('H:i:s, Y-m-d', $flotte[1][1])?> (Serverzeit), <a href="index.php?cancel=<?=htmlentities(urlencode($i))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" class="abbrechen">Abbrechen</a></dd>
<?php
			$countdowns[] = array($i, $flotte[1][1], ($flotte[7] || !$own));
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
	init_countdown('<?=$countdown[0]?>', <?=$countdown[1]?>, <?=$countdown[2] ? 'false' : 'true'?>);
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
	$countdowns = array();
	$tabindex = 3;
	foreach($user_array['planets'] as $no=>$planet)
	{
		$pos = explode(':', $planet['pos']);
		$class = universe::get_planet_class($pos[0], $pos[1], $pos[2]);
?>
	<li class="planet_<?=htmlentities($class)?><?=($no == $_SESSION['act_planet']) ? ' active' : ''?>"><?=($no != $_SESSION['act_planet']) ? '<a href="index.php?planet='.htmlentities(urlencode($no).'&'.SESSION_COOKIE.'='.urlencode(session_id())).'" tabindex="'.$tabindex.'">' : ''?><?=utf8_htmlentities($planet['name'])?><?=($no != $_SESSION['act_planet']) ? '</a>' : ''?> <span class="koords">(<?=utf8_htmlentities($planet['pos'])?>)</span>
		<dl class="planet-info">
			<dt class="c-felder">Felder</dt>
			<dd class="c-felder"><?=ths($planet['size'][0])?> <span class="gesamtgroesse">(<?=ths($planet['size'][1])?>)</span></dd>

			<dt class="c-gebaeudebau">Gebäudebau</dt>
<?php
		if(isset($planet['building']['gebaeude']) && trim($planet['building']['gebaeude'][0]) != '')
		{
?>
			<dd class="c-gebaeudebau"><?=utf8_htmlentities($items['gebaeude'][$planet['building']['gebaeude'][0]]['name'])?> <span class="restbauzeit" id="restbauzeit-ge-<?=utf8_htmlentities($no)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $planet['building']['gebaeude'][1])?> (Serverzeit)</span></dd>
<?php
			$countdowns[] = array('ge-'.$no, $planet['building']['gebaeude'][1]);
		}
		else
		{
?>
			<dd class="c-gebaeudebau gelangweilt">Gelangweilt</dd>
<?php
		}

		if(isset($planet['gebaeude']['B8']) && $planet['gebaeude']['B8'] > 0)
		{
?>

			<dt class="c-forschung">Forschung</dt>
<?php
			if(isset($planet['building']['forschung']) && trim($planet['building']['forschung'][0]) != '')
			{
?>
			<dd class="c-forschung"><?=utf8_htmlentities($items['forschung'][$planet['building']['forschung'][0]]['name'])?> <span id="restbauzeit-fo-<?=utf8_htmlentities($no)?>">Fertigstellung: <?=date('H:i:s, Y-m-d', $planet['building']['forschung'][1])?> (Serverzeit)</span></dd>
<?php
				$countdowns[] = array('fo-'.$no, $planet['building']['forschung'][1]);
			}
			else
			{
?>
			<dd class="c-forschung gelangweilt">Gelangweilt</dd>
<?php
			}
		}
?>
		</dl>
	</li>
<?php
		if($no != $_SESSION['act_planet'])
			$tabindex++;
	}
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
<?php
	if(count($user_array['punkte']) != 13)
	{
		if(!isset($user_array['punkte'][0])) $user_array['punkte'][0] = 0;
		if(!isset($user_array['punkte'][1])) $user_array['punkte'][1] = 0;
		if(!isset($user_array['punkte'][2])) $user_array['punkte'][2] = 0;
		if(!isset($user_array['punkte'][3])) $user_array['punkte'][3] = 0;
		if(!isset($user_array['punkte'][4])) $user_array['punkte'][4] = 0;
		if(!isset($user_array['punkte'][5])) $user_array['punkte'][5] = 0;
		if(!isset($user_array['punkte'][6])) $user_array['punkte'][6] = 0;
		if(!isset($user_array['punkte'][7])) $user_array['punkte'][7] = 0;
		if(!isset($user_array['punkte'][8])) $user_array['punkte'][8] = 0;
		if(!isset($user_array['punkte'][9])) $user_array['punkte'][9] = 0;
		if(!isset($user_array['punkte'][10])) $user_array['punkte'][10] = 0;
		if(!isset($user_array['punkte'][11])) $user_array['punkte'][11] = 0;
		write_user_array();
	}
?>
<h2 id="punkte">Punkte</h2>
<dl class="punkte">
	<dt class="c-gebaeude">Gebäude</dt>
	<dd class="c-gebaeude"><?=ths($user_array['punkte'][0])?></dd>

	<dt class="c-forschung">Forschung</dt>
	<dd class="c-forschung"><?=ths($user_array['punkte'][1])?></dd>

	<dt class="c-roboter">Roboter</dt>
	<dd class="c-roboter"><?=ths($user_array['punkte'][2])?></dd>

	<dt class="c-flotte">Flotte</dt>
	<dd class="c-flotte"><?=ths($user_array['punkte'][3])?></dd>

	<dt class="c-verteidigung">Verteidigung</dt>
	<dd class="c-verteidigung"><?=ths($user_array['punkte'][4])?></dd>

	<dt class="c-flugerfahrung">Flugerfahrung</dt>
	<dd class="c-flugerfahrung"><?=ths($user_array['punkte'][5])?></dd>

	<dt class="c-kampferfahrung">Kampferfahrung</dt>
	<dd class="c-kampferfahrung"><?=ths($user_array['punkte'][6])?></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($user_array['punkte'][0]+$user_array['punkte'][1]+$user_array['punkte'][2]+$user_array['punkte'][3]+$user_array['punkte'][4]+$user_array['punkte'][5]+$user_array['punkte'][6])?> <span class="platz">(Platz&nbsp;<?=ths($user_array['punkte'][12])?> <span class="gesamt-spieler">(<?=ths(highscores::get_players_count())?>)</span>)</span></dd>
</dl>
<h2 id="ausgegebene-rohstoffe">Ausgegebene Rohstoffe</h2>
<dl class="punkte">
	<dt class="c-carbon">Carbon</dt>
	<dd class="c-carbon"><?=ths($user_array['punkte'][7])?></dd>

	<dt class="c-eisenerz">Aluminium</dt>
	<dd class="c-eisenerz"><?=ths($user_array['punkte'][8])?></dd>

	<dt class="c-wolfram">Wolfram</dt>
	<dd class="c-wolfram"><?=ths($user_array['punkte'][9])?></dd>

	<dt class="c-radium">Radium</dt>
	<dd class="c-radium"><?=ths($user_array['punkte'][10])?></dd>

	<dt class="c-tritium">Tritium</dt>
	<dd class="c-tritium"><?=ths($user_array['punkte'][11])?></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($user_array['punkte'][7]+$user_array['punkte'][8]+$user_array['punkte'][9]+$user_array['punkte'][10]+$user_array['punkte'][11])?></dd>
</dl>
<?php
	login_gui::html_foot();
?>