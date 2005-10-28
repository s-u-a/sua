<?php
	require('include.php');

	$planet_error = false;
	if(isset($_POST['planet_name']))
	{
		if(trim($_POST['planet_name']) == '')
			$_POST['planet_name'] = $this_planet['name'];
		elseif(strlen($_POST['planet_name']) <= 24)
		{
			$old_name = $this_planet['name'];
			$this_planet['name'] = $_POST['planet_name'];
			if(!write_user_array())
				$planet_error = true;
			else
			{
				$pos = explode(':', $this_planet['pos']);

				$old_info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				if(!$old_info || !universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $old_info[1], $_POST['planet_name']))
				{
					$this_planet['name'] = $old_name;
					write_user_array();
					$planet_error = true;
				}
			}
		}
	}

	# Herausfinden, ob eigene Flotten zu/von diesem Planeten unterwegs sind

	$koords = array();
	$planets = array_keys($user_array['planets']);
	foreach($planets as $planet)
		$koords[] = $user_array['planets'][$planet]['pos'];

	$flotte_unterwegs = false;
	foreach($user_array['flotten'] as $i=>$flotte)
	{
		if($flotte[3][0] == $this_planet['pos'] || ($flotte[3][1] == $this_planet['pos'] && in_array($flotte[3][0], $koords)))
		{
			$flotte_unterwegs = true;
			break;
		}
	}

	if(isset($_POST['act_planet']) && isset($_POST['password']) && !$flotte_unterwegs && count($user_array['planets']) > 1)
	{
		if(md5($_POST['password']) != $user_array['password'])
			$aufgeben_error = 'Sie haben ein falsches Passwort eingegeben.';
		elseif($_POST['act_planet'] != $_SESSION['act_planet'])
			$aufgeben_error = 'Sicherheit: Da inzwischen der Planet gewechselt wurde, hätten Sie es wohl bereut, wenn Sie den aktuellen aufgegeben hätten.';
		else
		{
			# Alle feindlichen Flotten, die auf diesen Planeten, zurueckrufen
			foreach($user_array['flotten'] as $i=>$flotte)
			{
				if($flotte[3][1] != $this_planet['pos'])
					continue;
				$pos = explode(':', $flotte[3][0]);
				$start_info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				$that_user_array = get_user_array($start_info[1]);

				# Flotte umkehren
				# Zeit
				$time_diff = $flotte[1][1]-$flotte[1][0];
				$time_done = time()-$flotte[1][0];
				$time_left = $flotte[1][1]-time();
				$flotte[1] = array(time(), time()+$time_done);

				# Koordinaten vertauschen
				list($flotte[3][0], $flotte[3][1]) = array($flotte[3][1], $flotte[3][0]);

				# Ueberschuessiges Tritium
				$flotte[4][1] = round($flotte[4][0]*($time_left/$time_diff));
				$flotte[4][0] = 0;

				# Rueckflug?
				$flotte[7] = true;

				$that_user_array['flotten'][$i] = $flotte;
				uasort($that_user_array['flotten'], 'usort_fleet');

				write_user_array($start_info[1], $that_user_array);
				unset($user_array['flotten'][$i]);
				unset($that_user_array);

				messages::new_messages(array($start_info[1], $types_message_types[$flotte[2]]), '', "Flotte zur\xc3\xbcckgerufen", "Ihre Flotte befand sich auf dem Weg zum Planeten \xe2\x80\x9e".$this_planet['name']."\xe2\x80\x9c (".$this_planet['pos'].", Eigent\xc3\xbcmer: ".$_SESSION['username']."). Soeben wurde jener Planet verlassen, weshalb Ihre Flotte sich auf den R\xc3\xbcckweg zu Ihrem Planeten \xe2\x80\x9e".$start_info[2]."\xe2\x80\x9c (".$flotte[3][0].") macht.");
			}

			# Punkte abziehen

			# Gebaeudepunkte
			foreach($this_planet['gebaeude'] as $id=>$level)
			{
				if(!isset($items['gebaeude'][$id]))
					continue;
				$costs = $items['gebaeude'][$id]['ress'];
				for($i = 1; $i <= $level; $i++)
					$user_array['punkte'][0] -= array_sum($costs)*pow($i, 2.4)/1000;
			}

			# Roboterpunkte werden nicht abgezogen, siehe FAQ

			# Schiffspunkte
			foreach($this_planet['schiffe'] as $id=>$level)
			{
				if(!isset($items['schiffe'][$id]))
					continue;
				$costs = $items['schiffe'][$id]['ress'];
				$user_array['punkte'][3] -= array_sum($costs)*$level/1000;
			}

			# Verteidigungspukte
			foreach($this_planet['verteidigung'] as $id=>$level)
			{
				if(!isset($items['verteidigung'][$id]))
					continue;
				$costs = $items['verteidigung'][$id]['ress'];
				$user_array['punkte'][4] -= array_sum($costs)*$level/1000;
			}

			# Highscores neu berechnen
			highscores::recalc();

			# Planeten aus der Karte loeschen
			$this_pos = explode(':', $this_planet['pos']);
			$this_info = universe::get_planet_info($this_pos[0], $this_pos[1], $this_pos[2]);
			universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], '', '');

			$i = $_SESSION['act_planet'];
			$max = count($user_array['planets'])-1;
			while($i < $max)
			{
				$user_array['planets'][$i] = $user_array['planets'][$i+1];
				$i++;
			}
			unset($user_array['planets'][$i]);

			write_user_array();

			$url = 'http://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php?planet=';
			if(isset($user_array['planets'][$_SESSION['act_planet']]))
				$url .= urlencode($_SESSION['act_planet']);
			else
				$url .= urlencode($_SESSION['act_planet']-1);
			header('Location: '.$url, true, 303);
			die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
		}
	}

	login_gui::html_head();

	if($planet_error)
	{
?>
<p class="error">
	Datenbankfehler.
</p>
<?php
	}
	elseif(isset($_POST['planet_name']) && strlen($_POST['planet_name']) > 24)
	{
?>
<p class="error">
	Der Name darf maximal 24&nbsp;Bytes lang sein.
</p>
<?php
	}
?>
<form action="rename.php" method="post">
	<fieldset>
		<legend>Planeten umbenennen</legend>
		<dl>
			<dt><label for="name"><kbd>N</kbd>euer Name</label></dt>
			<dd><input type="text" id="name" name="planet_name" value="<?=utf8_htmlentities($this_planet['name'])?>" maxlength="24" accesskey="n" tabindex="1" /></dd>
		</dl>
		<div><button type="submit" accesskey="u" tabindex="2"><kbd>U</kbd>mbenennen</button></div>
	</fieldset>
</form>
<?php
	if($flotte_unterwegs || count($user_array['planets']) <= 1)
	{
?>
<p class="planeten-nicht-aufgeben">
	Sie können diesen Planeten derzeit nicht aufgeben, da Flottenbewegungen Ihrerseits von/zu diesem Planeten unterwegs sind oder dies Ihr einziger Planet ist.
</p>
<?php
	}
	else
	{
		if(isset($aufgeben_error) && trim($aufgeben_error) != '')
		{
?>
<p class="error">
	<?=htmlentities($aufgeben_error)."\n"?>
</p>
<?php
		}
?>
<form action="rename.php" method="post">
	<fieldset>
		<legend>Planeten aufgeben<input type="hidden" name="act_planet" value="<?=htmlentities($_SESSION['act_planet'])?>" /></legend>
		<dl>
			<dt><label for="password">Passwort</label></dt>
			<dd><input type="password" id="password" name="password" tabindex="3" /></dd>
		</dl>
		<div><button type="submit" tabindex="4">Aufgeben</button></div>
	</fieldset>
</form>
<?php
	}

	login_gui::html_foot();
?>