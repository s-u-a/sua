<?php
	define('start_mtime', microtime());

	error_reporting(2047);

	# Konstanten, die wichtige Pfade enthalten
	$DB_DIR = '../sua.db'; # Relativ zum Hauptverzeichnis des Spiels

		# Auswertung von $DB_DIR
		if(substr($DB_DIR, 0, 1) != '/')
		{ # Wenn der Pfad nicht absolut angegeben wurde, wird nun ein absoluter Pfad daraus

			$included_files = get_included_files();

			$this_filename = '/engine/include.php';
			define('s_root', substr(__FILE__, 0, -strlen($this_filename)));
			$DB_DIR = s_root.'/'.$DB_DIR;
			$document_root = $_SERVER['DOCUMENT_ROOT'];
			if(substr($document_root, -1) == '/')
				$document_root = substr($document_root, 0, -1);
			define('h_root', substr(s_root, strlen($document_root)));

			if($this_filename !== false)
				logfile::panic('Der absolute Pfad der Datenbank konnte nicht ermittelt werden. Bitte gib ihn in der Datei /engine/include.php an.');
		}

	$EVENT_FILE = $DB_DIR.'/events';
	$LOG_FILE = $DB_DIR.'/logfile';
	$DB_PLAYERS = $DB_DIR.'/players';
	$DB_UNIVERSE = $DB_DIR.'/universe';
	$DB_ITEMS = $DB_DIR.'/items';
	$DB_MESSAGES = $DB_DIR.'/messages';
	$DB_HIGHSCORES = $DB_DIR.'/highscores';
	$DB_TRUEMMERFELDER = $DB_DIR.'/truemmerfelder';
	$DB_HOSTNAME = $DB_DIR.'/hostname';
	$DB_EVENT_ID = $DB_DIR.'/event_id';
	$THS_HTML = '&nbsp;';
	$THS_UTF8 = "\xc2\xa0";
	#$THS_UTF8 = "\xe2\x80\x89";


	# Variablen als Konstanten speichern

	define('DB_DIR', $DB_DIR);
	define('EVENT_FILE', $EVENT_FILE);
	define('LOG_FILE', $LOG_FILE);
	define('DB_PLAYERS', $DB_PLAYERS);
	define('DB_UNIVERSE', $DB_UNIVERSE);
	define('DB_ITEMS', $DB_ITEMS);
	define('DB_MESSAGES', $DB_MESSAGES);
	define('DB_HIGHSCORES', $DB_HIGHSCORES);
	define('DB_TRUEMMERFELDER', $DB_TRUEMMERFELDER);
	define('DB_HOSTNAME', $DB_HOSTNAME);
	define('THS_HTML', $THS_HTML);
	define('THS_UTF8', $THS_UTF8);

	header('Content-type: text/html; charset=UTF-8');

	ob_start('ob_gzhandler');
	ob_start('ob_utf8');

	# Ueberpruefen, ob der Hostname korrekt ist
	$redirect = false;
	$hostname = $_SERVER['HTTP_HOST'];
	if(is_file(DB_DIR.'/hostname') && is_readable(DB_DIR.'/hostname'))
	{
		$hostname = trim(file_get_contents(DB_DIR.'/hostname'));
		if($_SERVER['HTTP_HOST'] != $hostname)
			$redirect = true;
	}

	$request_uri = $_SERVER['REQUEST_URI'];
	if(strpos($request_uri, '?') !== false)
		$request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
	if(substr($request_uri, -1) == '/')
		$redirect = true;

	if($redirect)
	{
		$url = 'http://'.$hostname.$_SERVER['PHP_SELF'];
		if($_SERVER['QUERY_STRING'] != '')
			$url .= '?'.$_SERVER['QUERY_STRING'];
		header('Location: '.$url, true, 307);

		if(count($_POST) > 0)
		{
			echo '<form action="'.htmlentities($url).'" method="post">';
			foreach($_POST as $key=>$val)
				echo '<input type="hidden" name="'.htmlentities($key).'" value="'.htmlentities($val).'" />';
			echo '<button type="submit">'.htmlentities($url).'</button>';
			echo '</form>';
		}
		else
			echo 'HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>';
		die();
	}

	$message_type_names = array (
		1 => 'Kämpfe',
		2 => 'Spionage',
		3 => 'Transport',
		4 => 'Sammeln',
		5 => 'Besiedelung',
		6 => 'Benutzernachrichten',
		7 => 'Verbündete',
		8 => 'Postausgang'
	);

	$type_names = array (
		1 => 'Besiedeln',
		2 => 'Sammeln',
		3 => 'Angriff',
		4 => 'Transport',
		5 => 'Spionieren',
		6 => 'Stationieren'
	);

	$types_message_types = array(1=>5, 2=>4, 3=>1, 4=>3, 5=>2, 6=>3);

	########################################
	### Hier beginnen die Klassen
	########################################


	class logfile # Enthaelt Funktionen fuer das Schreiben der Logfiles
	{
		function error()
		{
		}

		function action($user, $action)
		{
		}

		function panic($message) # Gibt eine fatale Fehlermeldung aus, aufgrund deren das Script
		{ # nicht weiter ausgefuehrt werden kann und bricht es ab.
		}
	}

	########################################

	class universe
	{
		function get_planet_info($galaxy, $system, $planet) # Findet die Groesse, Eigentuemer und Namen des Planeten heraus
		{ # unter den angegebenen Koordinaten heraus.
			if($planet%1 != 0 || $planet < 1 || $system%1 != 0 || $system < 1 || !is_file(DB_UNIVERSE.'/'.$galaxy) || !is_readable(DB_UNIVERSE.'/'.$galaxy))
				return false;

			$filesize = filesize(DB_UNIVERSE.'/'.$galaxy);

			if($filesize < ($system)*1475) # System existiert nicht
				return false;

			$fh = fopen(DB_UNIVERSE.'/'.$galaxy, 'rb');
			flock($fh, LOCK_SH);

			fseek($fh, ($system-1)*1475, SEEK_SET);
			$string = fread($fh, 1475);

			flock($fh, LOCK_UN);
			fclose($fh);

			$bin = '';

			for($i=0; $i < strlen($string); $i++)
				$bin .= add_nulls(decbin(ord($string{$i})), 8);

			$planet_count = bindec(substr($bin, 0, 5))+10;
			if($planet > $planet_count) # Der Planet existiert nicht
				return false;

			$size = bindec(substr($bin, 5+($planet-1)*9, 9))+100;

			$owner_name = trim(bin2string(substr($bin, 275+($planet-1)*192, 192)));
			$planet_name = trim(bin2string(substr($bin, 6035+($planet-1)*192, 192)));

			return array($size, $owner_name, $planet_name);
		}

		function set_planet_info($galaxy, $system, $planet, $size, $owner, $name)
		{
			if($planet%1 != 0 || $planet < 1 || $system%1 != 0 || $system < 1 || !is_file(DB_UNIVERSE.'/'.$galaxy) || !is_readable(DB_UNIVERSE.'/'.$galaxy))
				return false;

			$filesize = filesize(DB_UNIVERSE.'/'.$galaxy);

			if($filesize < ($system)*1475) # System existiert nicht
				return false;

			$fh = fopen(DB_UNIVERSE.'/'.$galaxy, 'rb');
			flock($fh, LOCK_SH);

			fseek($fh, ($system-1)*1475, SEEK_SET);
			$string = fread($fh, 1475);

			flock($fh, LOCK_UN);
			fclose($fh);

			$bin = '';

			for($i=0; $i < strlen($string); $i++)
				$bin .= add_nulls(decbin(ord($string{$i})), 8);

			$planet_count = bindec(substr($bin, 0, 5))+10;
			if($planet > $planet_count) # Der Planet existiert nicht
				return false;

			$strlen = strlen($bin);

			$bin = substr($bin, 0, 5+($planet-1)*9).add_nulls(decbin($size-100), 9).substr($bin, 5+$planet*9);

			while(strlen($owner) < 24)
				$owner .= ' ';
			$owner = substr($owner, 0, 24);

			$bin = substr($bin, 0, 275+($planet-1)*192).string2bin($owner).substr($bin, 275+$planet*192);

			while(strlen($name) < 24)
				$name .= ' ';
			$name = substr($name, 0, 24);
			$bin = substr($bin, 0, 6035+($planet-1)*192).string2bin($name).substr($bin, 6035+$planet*192);


			if(strlen($bin) != $strlen)
				return false;

			$fh = fopen(DB_UNIVERSE.'/'.$galaxy, 'r+b');
			flock($fh, LOCK_EX);
			fseek($fh, ($system-1)*1475, SEEK_SET);

			for($i=0; $i < strlen($bin); $i+=8)
				fwrite($fh, chr(bindec(substr($bin, $i, 8))));

			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}

		function get_galaxies_count()
		{
			if(!is_file(DB_UNIVERSE.'/count') || !is_readable(DB_UNIVERSE.'/count'))
				return false;

			return (int) trim(file_get_contents(DB_UNIVERSE.'/count'));
		}

		function get_systems_count($galaxy)
		{
			return 999;
		}

		function get_planets_count($galaxy, $system)
		{
			if($system%1 != 0 || $system < 1 || !is_file(DB_UNIVERSE.'/'.$galaxy) || !is_readable(DB_UNIVERSE.'/'.$galaxy))
				return false;

			$filesize = filesize(DB_UNIVERSE.'/'.$galaxy);

			if($filesize < ($system)*1475) # System existiert nicht
				return false;

			$fh = fopen(DB_UNIVERSE.'/'.$galaxy, 'rb');
			flock($fh, LOCK_SH);

			fseek($fh, ($system-1)*1475, SEEK_SET);
			$planet_count = floor(ord(fread($fh, 1))/8)+10;

			flock($fh, LOCK_UN);
			fclose($fh);

			return $planet_count;
		}

		function get_system_info($galaxy, $system)
		{
			if($system%1 != 0 || $system < 1 || !is_file(DB_UNIVERSE.'/'.$galaxy) || !is_readable(DB_UNIVERSE.'/'.$galaxy))
				return false;

			$filesize = filesize(DB_UNIVERSE.'/'.$galaxy);

			if($filesize < ($system)*1475) # System existiert nicht
				return false;

			$fh = fopen(DB_UNIVERSE.'/'.$galaxy, 'rb');
			flock($fh, LOCK_SH);

			fseek($fh, ($system-1)*1475, SEEK_SET);
			$string = fread($fh, 1475);

			flock($fh, LOCK_UN);
			fclose($fh);

			$bin = '';

			$planet_count = floor(ord(substr($string, 0, 1))/8)+10;

			for($i=0; $i < strlen($string); $i++)
				$bin .= add_nulls(decbin(ord($string{$i})), 8);

			$planets = array();

			# Groessen
			$max_pos = $planet_count*9+5;
			for($i=1, $pos=5; $i <= $planet_count; $i++, $pos+=9)
				$planets[$i] = array(bindec(substr($bin, $pos, 9))+100);

			# Eigentuemer
			$max_pos = $planet_count*192+275;
			for($i=1, $pos=275; $i <= $planet_count; $i++, $pos+=192)
				$planets[$i][1] = trim(bin2string(substr($bin, $pos, 192)));

			# Name
			$max_pos = $planet_count*192+6035;
			for($i=1, $pos=6035; $i <= $planet_count; $i++, $pos+=192)
				$planets[$i][2] = trim(bin2string(substr($bin, $pos, 192)));

			return $planets;
		}

		function find_truemmerfeld($galaxy, $system, $planet)
		{
			return false;
		}

		function get_planet_class($galaxy, $system, $planet)
		{
			$type = (floor($system/100)*floor(($system%100)/10)*($system%10)+$galaxy)*$planet;
			return $type%20+1;
		}
	}

	########################################

	class messages
	{
		function new_message($to, $from, $subject, $text, $html=false)
		{
			global $user_array;


			$message_array = array('text' => $text, 'from' => $from, 'time' => time(), 'subject' => $subject, 'users' => array(), 'html' => $html);
			if(!$message_array['html'])
				$message_array['text'] = htmlspecialchars($message_array['text']);
			foreach($to as $user=>$type)
			{
				if(!is_file(DB_PLAYERS.'/'.urlencode($user)) || !is_readable(DB_PLAYERS.'/'.urlencode($user)) || !is_writable(DB_PLAYERS.'/'.urlencode($user)))
					continue;

				$message_array['users'][$user] = $type;
			}

			if(count($message_array['users']) <= 0)
				return false;

			do $id = substr(md5(rand()), 0, 16); while(file_exists(DB_MESSAGES.'/'.$id));

			$fh = fopen(DB_MESSAGES.'/'.$id, 'w');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);

			$one = false;
			foreach($message_array['users'] as $user=>$type)
			{
				$that_user_array = get_user_array($user);
				if(!isset($that_user_array['messages']))
					$that_user_array['messages'] = array();
				if(!isset($that_user_array['messages'][$type]))
					$that_user_array['messages'][$type] = array();
				$that_user_array['messages'][$type][$id] = true;

				if(isset($_SESSION['username']) && isset($user_array) && $user == $_SESSION['username'])
					$user_array['messages'][$type][$id] = true;

				$fh_ua = fopen(DB_PLAYERS.'/'.urlencode($user), 'w');
				if(!$fh_ua)
					continue;
				flock($fh_ua, LOCK_EX);
				fwrite($fh_ua, gzcompress(serialize($that_user_array)));
				flock($fh_ua, LOCK_UN);
				fclose($fh_ua);

				unset($that_user_array);

				$one = true;
			}

			if(!$one)
			{
				die('felse');
				flock($fh, LOCK_UN);
				fclose($fh);
				unlink(DB_MESSAGES.'/'.$id);
				return false;
			}

			fwrite($fh, gzcompress(serialize($message_array)));
			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}
	}

	########################################

	class gui
	{
		function html_head()
		{
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
	<head>
		<title xml:lang="en">S-U-A &ndash; Stars under Attack</title>
		<link rel="stylesheet" href="<?=h_root?>/style.css" type="text/css" />
	</head>
	<body><div id="content-1"><div id="content-2"><div id="content-3"><div id="content-4"><div id="content-5"><div id="content-6"><div id="content-7"><div id="content-8">
		<img src="images/sonne.png" alt="" id="design-img-1" />
		<img src="images/planet.gif" alt="" id="design-img-2" />
		<img src="images/komet.png" alt="" id="design-img-3" />
		<h1 id="logo"><a href="./" title="Zurück zur Startseite"><img src="images/logo_text.png" alt="Stars Under Attack" xml:lang="en" /></a></h1>
		<form action="<?=h_root?>/login/index.php" method="post" id="login-form">
			<fieldset>
				<legend>Anmelden</legend>
				<dl>
					<dt><label for="login-username">Name</label></dt>
					<dd><input type="text" id="login-username" name="username" /></dd>

					<dt><label for="login-password">Passwort</label></dt>
					<dd><input type="password" id="login-password" name="password" /></dd>
				</dl>
				<ul>
					<li><button type="submit">Anmelden</button></li>
					<li><a href="register.php">Registrieren</a></li>
				</ul>
			</fieldset>
		</form>
		<ol id="navigation">
			<li><a href="<?=h_root?>/index.php">Neuigkeiten</a></li>
			<li><a href="<?=h_root?>/register.php">Registrieren</a></li>
			<li><a href="<?=h_root?>/rules.php">Regeln</a></li>
			<li><a href="<?=h_root?>/faq.php"><abbr title="Frequently Asked Questions" xml:lang="en">FAQ</abbr></a></li>
			<li><a href="<?=h_root?>/forum/">Forum</a></li>
			<li><a href="<?=h_root?>/impressum.php">Impressum</a></li>
		</ol>
		<div id="content-9"><div id="content-10"><div id="content-11"><div id="content-12">
<?php
		}

		function html_foot()
		{
?>
		<br style="clear:both;" /></div></div></div></div>
	</div></div></div></div></div></div></div></div></body>
</html>
<?php
		}
	}

	########################################

	class admin_gui
	{
		function html_head()
		{
		}

		function html_foot()
		{
		}
	}

	########################################

	function calc_btime_gebaeude($time, $level=0)
	{
		global $this_planet;
		global $user_array;

		# Bauzeitberechnung mit der aktuellen Ausbaustufe

		$time *= pow($level+1, 2);

		# Roboter einberechnen
		$robs = 0;
		if(isset($this_planet['roboter']['R01']))
			$robs = $this_planet['roboter']['R01'];

		$max_robs = $this_planet['size'][1];
		$ing_tech = 0;
		if(isset($user_array['forschung']['F9']))
			$ing_tech = $user_array['forschung']['F9'];
		$max_robs /= $ing_tech+1;
		$max_robs = floor($max_robs/2);

		if($robs > $max_robs)
			$robs = $max_robs;
		if($robs != 0)
		{
			$f = 1;
			if(isset($user_array['forschung']['F2']))
				$f = 1-$user_array['forschung']['F2']*0.0025;
			if($f != 1)
				$time *= pow($f, $robs);
		}

		$time = round($time);

		return $time;
	}

	function get_jemand_forscht()
	{
		global $user_array;

		$jemand_forscht = false;
		$planets = array_keys($user_array['planets']);
		foreach($planets as $planet)
		{
			if((isset($user_array['planets'][$planet]['building']['forschung']) && trim($user_array['planets'][$planet]['building']['forschung'][0]) != '') || (isset($user_array['planets'][$planet]['building']['gebaeude']) && $user_array['planets'][$planet]['building']['gebaeude'][0] == 'B8'))
			{
				$jemand_forscht = true;
				break;
			}
		}
		return $jemand_forscht;
	}

	function calc_btime_forschung($time, $level=0, $loc_glob=1)
	{
		global $this_planet;
		global $user_array;

		# Bauzeitberechnung mit der aktuellen Ausbaustufe

		$time *= pow($level+1, 2);

		# Einberechnen der Stufe des aktuellen Forschungslabors
		$folab_level = 0;
		if(isset($this_planet['gebaeude']['B8']))
			$folab_level = $this_planet['gebaeude']['B8'];
		$time *= pow(0.975, $folab_level);

		# Bei globaler Forschung Stufen der anderen Forschungslabore
		if($loc_glob == 2)
		{
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				if($planet == $_SESSION['act_planet']) # Aktueller Planet wurde schon einberechnet
					continue;

				if(isset($user_array['planets'][$planet]['gebaeude']['B8']))
					$time *= pow(0.995, $user_array['planets'][$planet]['gebaeude']['B8']);
			}
		}

		$time = round($time);

		return $time;
	}

	function calc_btime_roboter($time)
	{
		global $this_planet;
		global $user_array;

		# Einberechnen der Stufe der Roboterfabrik
		$robfa_level = 0;
		if(isset($this_planet['gebaeude']['B9']))
			$robfa_level = $this_planet['gebaeude']['B9'];
		$time *= pow(0.95, $robfa_level);

		$time = round($time);

		return $time;
	}

	function calc_btime_schiffe($time)
	{
		global $this_planet;
		global $user_array;

		# Einberechnen der Stufe der Werft
		$werft_level = 0;
		if(isset($this_planet['gebaeude']['B10']))
			$werft_level = $this_planet['gebaeude']['B10'];
		$time *= pow(0.975, $werft_level);

		$time = round($time);

		return $time;
	}

	function calc_btime_verteidigung($time)
	{
		global $this_planet;
		global $user_array;

		# Einberechnen der Stufe der Werft
		$werft_level = 0;
		if(isset($this_planet['gebaeude']['B10']))
			$werft_level = $this_planet['gebaeude']['B10'];
		$time *= pow(0.975, $werft_level);

		$time = round($time);

		return $time;
	}

	########################################

	class fleet
	{
		function get_distance($start, $target)
		{
			$this_pos = explode(':', $start);
			$that_pos = explode(':', $target);

			# Entfernung berechnen
			if($this_pos[0] == $that_pos[0]) # Selbe Galaxie
			{
				if($this_pos[1] == $that_pos[1]) # Selbes System
				{
					if($this_pos[2] == $that_pos[2]) # Selber Planet
						$distance = 0.001;
					else # Anderer Planet
						$distance = 0.01*diff($this_pos[2], $that_pos[2]);
				}
				else
				{
					# Anderes System

					$this_x_value = $this_pos[1]-($this_pos[1]%100);
					$this_y_value = $this_pos[1]-$this_x_value;
					$this_y_value -= $this_y_value%10;
					$this_z_value = $this_pos[1]-$this_x_value-$this_y_value;
					$this_x_value /= 100;
					$this_y_value /= 10;

					$that_x_value = $that_pos[1]-($that_pos[1]%100);
					$that_y_value = $that_pos[1]-$that_x_value;
					$that_y_value -= $that_y_value%10;
					$that_z_value = $that_pos[1]-$that_x_value-$that_y_value;
					$that_x_value /= 100;
					$that_y_value /= 10;

					$x_diff = diff($this_x_value, $that_x_value);
					$y_diff = diff($this_y_value, $that_y_value);
					$z_diff = diff($this_z_value, $that_z_value);

					$distance = sqrt(pow($x_diff, 2)+pow($y_diff, 2)+pow($z_diff, 2));
				}
			}
			else # Andere Galaxie
			{
				$galaxy_count = universe::get_galaxies_count()*2;

				$galaxy_diff_1 = diff($this_pos[0], $that_pos[0]);
				$galaxy_diff_2 = diff($this_pos[0]+$galaxy_count, $that_pos[0]);
				$galaxy_diff_3 = diff($this_pos[0], $that_pos[0]+$galaxy_count);
				$galaxy_diff = min($galaxy_diff_1, $galaxy_diff_2, $galaxy_diff_3);

				$radius = (30*$galaxy_count)/(2*pi());
				$distance = sqrt(2*pow($radius, 2)-2*$radius*$radius*cos(($galaxy_diff/$galaxy_count)*2*pi()));
			}

			$distance = round($distance*1000);

			return $distance;
		}

		function calc($mass, $distance, $speed)
		{
			$tritium = round(($distance*$mass)/1000000);

			if($speed <= 0)
				$time = 900;
			else
				$time = round(pow(($mass*$distance)/$speed, 0.3)*300);
			#$time = round(pow(1.125*$mass*pow($distance, 2)/$speed, 0.33333)*10);

			return array($time, $tritium);
		}

		function check_own($flotte) # Ueberprueft, ob eine Flotte eine eigene ist
		{
			global $user_array;

			$own = false;
			$check = ($flotte[7] ? $flotte[3][1] : $flotte[3][0]);
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				if($user_array['planets'][$planet]['pos'] == $check)
				{
					$own = true;
					break;
				}
			}
			return $own;
		}
	}

	########################################

	class truemmerfeld
	{
		function get($galaxy, $system, $planet)
		{
			# Bekommt die Groesse eines Truemmerfelds

			if(!is_file(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet))
				return array(0, 0, 0, 0);
			elseif(!is_readable(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet))
				return false;
			else
			{
				$string = file_get_contents(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet);

				$rohstoffe = array('', '', '', '');

				$index = 0;
				for($i = 0; $i < strlen($string); $i++)
				{
					$bin = add_nulls(decbin(ord($string{$i})), 8);
					$rohstoffe[$index] .= substr($bin, 0, -1);
					if(!substr($bin, -1)) # Naechste Zahl
						$index++;
				}
				for($rohstoff = 0; $rohstoff < 4; $rohstoff++)
				{
					if($rohstoffe[$rohstoff] == '')
						$rohstoffe[$rohstoff] = 0;
					else
						$rohstoffe[$rohstoff] = base_convert($rohstoffe[$rohstoff], 2, 10);
				}

				return array($rohstoffe[0], $rohstoffe[1], $rohstoffe[2], $rohstoffe[3]);
			}
		}

		function add($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			# Fuegt einem Truemmerfeld Rohstoffe hinzu
			$old = truemmerfeld::get($galaxy, $system, $planet);
			if($old === false)
				return false;
			$old[0] += $carbon;
			$old[1] += $aluminium;
			$old[2] += $wolfram;
			$old[3] += $radium;

			return truemmerfeld::set($galaxy, $system, $planet, $old[0], $old[1], $old[2], $old[3]);
		}

		function sub($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			# Zieht einem Truemmerfeld Rohstoffe ab
			$old = truemmerfeld::get($galaxy, $system, $planet);
			if($old === false)
				return false;
			$old[0] -= $carbon;
			$old[1] -= $aluminium;
			$old[2] -= $wolfram;
			$old[3] -= $radium;

			if($old[0] < 0)
				$old[0] = 0;
			if($old[1] < 0)
				$old[1] = 0;
			if($old[2] < 0)
				$old[2] = 0;
			if($old[3] < 0)
				$old[3] = 0;

			return truemmerfeld::set($galaxy, $system, $planet, $old[0], $old[1], $old[2], $old[3]);
		}

		function set($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			$new = array(
				base_convert($carbon, 10, 2),
				base_convert($aluminium, 10, 2),
				base_convert($wolfram, 10, 2),
				base_convert($radium, 10, 2)
			);

			$string = '';

			for($i = 0; $i < 4; $i++)
			{
				if(strlen($new[$i])%7)
					$new[$i] = str_repeat('0', 7-strlen($new[$i])%7).$new[$i];

				$strlen = strlen($new[$i]);
				for($j = 0; $j < $strlen; $j+=7)
				{
					if($j == $strlen-7)
						$suf = '0';
					else
						$suf = '1';
					$string .= chr(bindec(substr($new[$i], $j, 7).$suf));
				}
			}

			unset($new);

			# Schreiben
			$fh = fopen(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet, 'w');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);
			fwrite($fh, $string);
			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}
	}

	########################################

	class highscores
	{
		function recalc()
		{
			global $user_array;

			$old_position = $user_array['punkte'][12];
			$old_position_f = ($old_position-1)*32;

			$new_points = floor($user_array['punkte'][0]+$user_array['punkte'][1]+$user_array['punkte'][2]+$user_array['punkte'][3]+$user_array['punkte'][4]+$user_array['punkte'][5]+$user_array['punkte'][6]);
			$new_points_bin = add_nulls(base_convert($new_points, 10, 2), 64);
			$new_points_str = '';
			for($i = 0; $i < strlen($new_points_bin); $i+=8)
				$new_points_str .= chr(bindec(substr($new_points_bin, $i, 8)));
			unset($new_points_bin);
			$my_string = substr($_SESSION['username'], 0, 24);
			if(strlen($_SESSION['username']) < 24)
				$my_string .= str_repeat(' ', 24-strlen($_SESSION['username']));
			if(strlen($_SESSION['username']) > 24)
				$my_string = substr($my_string, 0, 24);
			$my_string .= $new_points_str;

			$filesize = filesize(DB_HIGHSCORES);

			$fh = fopen(DB_HIGHSCORES, 'r+');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);

			fseek($fh, $old_position_f, SEEK_SET);

			$up = true;

			# Ueberpruefen, ob man in den Highscores abfaellt
			if($filesize-$old_position_f >= 64)
			{
				fseek($fh, 32, SEEK_CUR);
				list(,$this_points) = highscores::get_info(fread($fh, 32));
				fseek($fh, -64, SEEK_CUR);

				if($this_points > $new_points)
					$up = false;
			}

			if($up)
			{
				# In den Highscores nach oben rutschen
				while(true)
				{
					if(ftell($fh) == 0) # Schon auf Platz 1
					{
						fwrite($fh, $my_string);
						break;
					}
					fseek($fh, -32, SEEK_CUR);
					$cur = fread($fh, 32);
					list($this_user,$this_points) = highscores::get_info($cur);

					if($this_points < $new_points)
					{
						# Es muss weiter nach oben verschoben werden

						# Aktuellen Eintrag nach unten verschieben
						fwrite($fh, $cur);
						fseek($fh, -64, SEEK_CUR);
						# In dessen User-Array speichern
						$this_user_array = get_user_array($this_user);
						$this_user_array['punkte'][12]++;
						$this_fh = fopen(DB_PLAYERS.'/'.urlencode($this_user), 'w');
						flock($this_fh, LOCK_EX);
						fwrite($this_fh, gzcompress(serialize($this_user_array)));
						flock($this_fh, LOCK_UN);
						fclose($this_fh);
					}
					else
					{
						fwrite($fh, $my_string);
						break;
					}
				}
			}
			else
			{
				# In den Highscores nach unten rutschen

				while(true)
				{
					if($filesize-ftell($fh) <= 32) # Schon auf dem letzten Platz
					{
						fwrite($fh, $my_string);
						break;
					}

					fseek($fh, 32, SEEK_CUR);
					$cur = fread($fh, 32);
					list($this_user, $this_points) = highscores::get_info($cur);
					fseek($fh, -64, SEEK_CUR);

					if($this_points > $new_points)
					{
						# Es muss weiter nach unten verschoben werden

						# Aktuellen Eintrag nach oben verschieben
						fwrite($fh, $cur);
						# In dessen User-Array speichern
						$this_user_array = get_user_array($this_user);
						$this_user_array['punkte'][12]--;
						$this_fh = fopen(DB_PLAYERS.'/'.urlencode($this_user), 'w');
						flock($this_fh, LOCK_EX);
						fwrite($this_fh, gzcompress(serialize($this_user_array)));
						flock($this_fh, LOCK_UN);
						fclose($this_fh);
					}
					else
					{
						fwrite($fh, $my_string);
						break;
					}
				}
			}

			$act_position = ftell($fh);

			flock($fh, LOCK_UN);
			fclose($fh);

			$act_platz = $act_position/32;
			if($act_platz != $old_position)
			{
				$user_array['punkte'][12] = $act_platz;
				write_user_array();
			}

			return true;
		}

		function get_info($string)
		{
			$username = trim(substr($string, 0, 24));
			$points_str = substr($string, 24);

			$points_bin = '';
			for($i = 0; $i < strlen($points_str); $i++)
				$points_bin .= add_nulls(decbin(ord($points_str{$i})), 8);

			$points = base_convert($points_bin, 2, 10);

			return array($username, $points);
		}

		function get_players_count()
		{
			$filesize = filesize(DB_HIGHSCORES);
			if($filesize === false)
				return false;
			$players = floor($filesize/32);
			return $players;
		}
	}

	########################################
	### Hier beginnen die Funktionen
	########################################

	function usort_fleet($fleet1, $fleet2)
	{
		if($fleet1[1][1] > $fleet2[1][1])
			return 1;
		elseif($fleet1[1][1] < $fleet2[1][1])
			return -1;
		else
			return 0;
	}

	function get_user_array($username)
	{
		if(!is_file(DB_PLAYERS.'/'.urlencode($username)) || !is_readable(DB_PLAYERS.'/'.urlencode($username)))
			return false;
		return unserialize(gzuncompress(file_get_contents(DB_PLAYERS.'/'.urlencode($username))));
	}

	function write_user_array($username=false, $that_user_array=false)
	{
		if($username !== false && $user_array === false)
			return false;
		if($username === false)
		{
			if(!isset($_SESSION['username']))
				return false;
			else
				$username = $_SESSION['username'];
		}

		if($username == $_SESSION['username'])
		{
			global $user_array;
			$that_user_array = &$user_array;
		}

		$fh = fopen(DB_PLAYERS.'/'.urlencode($username), 'w');
		if(!$fh)
			return false;
		flock($fh, LOCK_EX);
		fwrite($fh, gzcompress(serialize($user_array)));
		flock($fh, LOCK_UN);
		fclose($fh);
		return true;
	}

	function get_skins()
	{
		# Skins bekommen
		$skins = array();
		if(is_file(s_root.'/login/style/skins') && is_readable(s_root.'/login/style/skins'))
		{
			$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents(s_root.'/login/style/skins'));
			foreach($skins_file as $skins_line)
			{
				$skins_line = explode("\t", $skins_line, 3);
				if(count($skins_line) < 3)
					continue;
				$skins[$skins_line[0]] = $skins_line[1];
			}
			unset($skins_file);
			unset($skins_line);
		}
		return $skins;
	}

	function get_version()
	{
		$version = '';
		if(is_file(s_root.'/db_things/version') && is_readable(s_root.'/db_things/version'))
			$version = trim(file_get_contents(s_root.'/db_things/version'));
		return $version;
	}

	function get_items($check_buildability=true)
	{
		global $user_array;
		global $this_planet;

		$items = array('gebaeude' => array(), 'forschung' => array(), 'roboter' => array(), 'schiffe' => array(), 'verteidigung' => array(), 'ids' => array());
		if(is_file(DB_ITEMS.'/gebaeude') && is_readable(DB_ITEMS.'/gebaeude'))
		{
			$geb_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/gebaeude'));
			foreach($geb_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 8)
					continue;
				$items['ids'][$item[0]] = & $items['gebaeude'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', $item[4]),
					'prod' => explode('.', $item[5]),
					'fields' => $item[6],
					'caption' => $item[7]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				if($check_buildability)
				{
					$level = 0;
					if(isset($this_planet['gebaeude'][$item[0]]))
						$level = $this_planet['gebaeude'][$item[0]];
					$deps = 1;
					foreach($items['ids'][$item[0]]['deps'] as $dep)
					{
						if(!check_dep($dep))
						{
							$deps = 0;
							break;
						}
					}
					if(!$deps && $level > 0)
						$deps = 2;
					$items['ids'][$item[0]]['buildable'] = $deps;
				}
			}
		}

		if(is_file(DB_ITEMS.'/forschung') && is_readable(DB_ITEMS.'/forschung'))
		{
			$for_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/forschung'));
			foreach($for_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 6)
					continue;
				$items['ids'][$item[0]] = & $items['forschung'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', trim($item[4])),
					'caption' => $item[5]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				if($check_buildability)
				{
					$deps = true;
					foreach($items['ids'][$item[0]]['deps'] as $dep)
					{
						if(!check_dep($dep))
						{
							$deps = false;
							break;
						}
					}
					$items['ids'][$item[0]]['buildable'] = $deps;
				}
			}
		}

		if(is_file(DB_ITEMS.'/roboter') && is_readable(DB_ITEMS.'/roboter'))
		{
			$rob_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/roboter'));
			foreach($rob_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 6)
					continue;
				$items['ids'][$item[0]] = & $items['roboter'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', trim($item[4])),
					'caption' => $item[5]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				if($check_buildability)
				{
					$deps = true;
					foreach($items['ids'][$item[0]]['deps'] as $dep)
					{
						if(!check_dep($dep))
						{
							$deps = false;
							break;
						}
					}
					$items['ids'][$item[0]]['buildable'] = $deps;
				}

				$items['ids'][$item[0]]['mass'] = round(array_sum($items['ids'][$item[0]]['ress'])*0.8);
			}
		}

		if(is_file(DB_ITEMS.'/schiffe') && is_readable(DB_ITEMS.'/schiffe'))
		{
			$sch_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/schiffe'));
			foreach($sch_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 11)
					continue;
				$items['ids'][$item[0]] = & $items['schiffe'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', $item[4]),
					'trans' => explode('.', $item[5]),
					'att' => $item[6],
					'def' => $item[7],
					'speed' => $item[8],
					'types' => explode(' ', $item[9]),
					'caption' => $item[10]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				if($check_buildability)
				{
					$deps = true;
					foreach($items['ids'][$item[0]]['deps'] as $dep)
					{
						if(!check_dep($dep))
						{
							$deps = false;
							break;
						}
					}
					$items['ids'][$item[0]]['buildable'] = $deps;
				}

				$items['ids'][$item[0]]['mass'] = round(array_sum($items['ids'][$item[0]]['ress'])*0.8);
			}
		}

		if(is_file(DB_ITEMS.'/verteidigung') && is_readable(DB_ITEMS.'/verteidigung'))
		{
			$ver_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/verteidigung'));
			foreach($ver_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 8)
					continue;
				$items['ids'][$item[0]] = & $items['verteidigung'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', $item[4]),
					'att' => $item[5],
					'def' => $item[6],
					'caption' => $item[7]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				if($check_buildability)
				{
					$deps = true;
					foreach($items['ids'][$item[0]]['deps'] as $dep)
					{
						if(!check_dep($dep))
						{
							$deps = false;
							break;
						}
					}
				}

				$items['ids'][$item[0]]['buildable'] = $deps;
			}
		}

		return $items;
	}

	function get_ges_prod()
	{
		global $this_planet;
		global $items;
		global $user_array;

		global $carbon_f;
		global $aluminium_f;
		global $wolfram_f;
		global $radium_f;
		global $tritium_f;
		global $energie_f;

		global $energie_mangel;

		# Roboterfaktoren berechnen
		$robtech = 0;
		if(isset($user_array['forschung']['F2']))
			$robtech = $user_array['forschung']['F2'];
		$f = 1+$robtech*0.000625;

		$carbon_rob = $aluminium_rob = $wolfram_rob = $radium_rob = $tritium_rob = 0;
		$carbon_f = $aluminium_f = $wolfram_f = $radium_f = $tritium_f = 1;
		if(isset($this_planet['roboter']['R02']))
		{
			$carbon_rob = $this_planet['roboter']['R02'];
			$carbon_stufe = 0;
			if(isset($this_planet['gebaeude']['B0']))
				$carbon_stufe = $this_planet['gebaeude']['B0'];
			if($carbon_rob > $carbon_stufe*10)
				$carbon_rob = $carbon_stufe*10;

			$carbon_f = pow($f, $carbon_rob);
		}
		if(isset($this_planet['roboter']['R03']))
		{
			$aluminium_rob = $this_planet['roboter']['R03'];
			$aluminium_stufe = 0;
			if(isset($this_planet['gebaeude']['B1']))
				$aluminium_stufe = $this_planet['gebaeude']['B1'];
			if($aluminium_rob > $aluminium_stufe*10)
				$aluminium_rob = $aluminium_stufe*10;

			$aluminium_f = pow($f, $aluminium_rob);
		}

		if(isset($this_planet['roboter']['R04']))
		{
			$wolfram_rob = $this_planet['roboter']['R04'];
			$wolfram_stufe = 0;
			if(isset($this_planet['gebaeude']['B2']))
				$wolfram_stufe = $this_planet['gebaeude']['B2'];
			if($wolfram_rob > $wolfram_stufe*10)
				$wolfram_rob = $wolfram_stufe*10;

			$wolfram_f = pow($f, $wolfram_rob);
		}
		if(isset($this_planet['roboter']['R05']))
		{
			$radium_rob = $this_planet['roboter']['R05'];
			$radium_stufe = 0;
			if(isset($this_planet['gebaeude']['B3']))
				$radium_stufe = $this_planet['gebaeude']['B3'];
			if($radium_rob > $radium_stufe*10)
				$radium_rob = $radium_stufe*10;

			$radium_f = pow($f, $radium_rob);
		}
		if(isset($this_planet['roboter']['R06']))
		{
			$tritium_rob = $this_planet['roboter']['R06'];
			$tritium_stufe = 0;
			if(isset($this_planet['gebaeude']['B4']))
				$tritium_stufe = $this_planet['gebaeude']['B4'];
			if($tritium_rob > $tritium_stufe*15)
				$tritium_rob = $tritium_stufe*15;

			$tritium_f = pow($f, $tritium_rob);
		}

		# Energietechnik

		$etech = 0;
		$energie_f = 1;
		if(isset($user_array['forschung']['F3']))
		{
			$etech = $user_array['forschung']['F3'];
			$energie_f = pow(1.05, $etech);
		}

		$ges_prod = array(0, 0, 0, 0, 0, 0);

		# Zuerst Energie berechnen
		$energie_prod = 0;
		$energie_need = 0;
		foreach($this_planet['gebaeude'] as $id=>$stufe)
		{
			if(!isset($items['gebaeude'][$id]))
				continue;
			$prod = 1;
			if(isset($this_planet['prod'][$id]) && $this_planet['prod'][$id] >= 0 && $this_planet['prod'][$id] <= 1)
				$prod = $this_planet['prod'][$id];

			$this_prod = $items['gebaeude'][$id]['prod'];
			$this_prod[5] = round($this_prod[5]*pow($stufe, 2)*$prod*$energie_f);

			if($this_prod[5] < 0)
				$energie_need -= $this_prod[5];
			elseif($this_prod[5] > 0)
				$energie_prod += $this_prod[5];
		}

		if($energie_need > $energie_prod) # Nicht genug Energie
			$energie_mangel = $energie_prod/$energie_need;
		else
			$energie_mangel = 1;

		foreach($this_planet['gebaeude'] as $id=>$stufe)
		{
			if(!isset($items['gebaeude'][$id]))
				continue;
			$prod = 1;
			if(isset($this_planet['prod'][$id]) && $this_planet['prod'][$id] >= 0 && $this_planet['prod'][$id] <= 1)
				$prod = $this_planet['prod'][$id];

			$this_prod = $items['gebaeude'][$id]['prod'];

			$this_prod[0] *= pow($stufe, 2)*$prod;
			$this_prod[1] *= pow($stufe, 2)*$prod;
			$this_prod[2] *= pow($stufe, 2)*$prod;
			$this_prod[3] *= pow($stufe, 2)*$prod;
			$this_prod[4] *= pow($stufe, 2)*$prod;
			$this_prod[5] *= pow($stufe, 2)*$prod;

			if($this_prod[0] > 0)
				$this_prod[0] *= $carbon_f;
			if($this_prod[1] > 0)
				$this_prod[1] *= $aluminium_f;
			if($this_prod[2] > 0)
				$this_prod[2] *= $wolfram_f;
			if($this_prod[3] > 0)
				$this_prod[3] *= $radium_f;
			if($this_prod[4] > 0)
				$this_prod[4] *= $tritium_f;
			if($this_prod[5] > 0)
				$this_prod[5] *= $energie_f;

			if($this_prod[5] < 0)
			{
				$this_prod[0] *= $energie_mangel;
				$this_prod[1] *= $energie_mangel;
				$this_prod[2] *= $energie_mangel;
				$this_prod[3] *= $energie_mangel;
				$this_prod[4] *= $energie_mangel;
			}

			$ges_prod[0] += round($this_prod[0]);
			$ges_prod[1] += round($this_prod[1]);
			$ges_prod[2] += round($this_prod[2]);
			$ges_prod[3] += round($this_prod[3]);
			$ges_prod[4] += round($this_prod[4]);
			$ges_prod[5] += round($this_prod[5]);
		}

		return $ges_prod;
	}

	########################################

	function check_dep($dep)
	{
		global $this_planet;

		$dep = explode('-', $dep);
		if(count($dep) < 2)
			return 2;
		global $this_planet;
		if(!isset($this_planet['ids'][$dep[0]]) || $this_planet['ids'][$dep[0]] < $dep[1])
			return 0;
		return 1;
	}

	function refresh_ress()
	{
		global $this_planet;

		# Rohstoffe aktualisieren
		$now_time = time();
		$last_time = $this_planet['last_refresh'];
		$secs = $now_time-$last_time;

		$ges_prod = get_ges_prod();

		$this_planet['ress'][0] += ($ges_prod[0]/3600)*$secs;
		$this_planet['ress'][1] += ($ges_prod[1]/3600)*$secs;
		$this_planet['ress'][2] += ($ges_prod[2]/3600)*$secs;
		$this_planet['ress'][3] += ($ges_prod[3]/3600)*$secs;
		$this_planet['ress'][4] += ($ges_prod[4]/3600)*$secs;

		$this_planet['last_refresh'] = $now_time;
	}

	function format_btime($time2)
	{
		$time = $time2;
		$days = $hours = $minutes = $seconds = 0;

		if($time >= 86400)
		{
			$days = floor($time/86400);
			$time -= $days*86400;
		}
		if($time >= 3600)
		{
			$hours = floor($time/3600);
			$time -= $hours*3600;
		}
		if($time >= 60)
		{
			$minutes = floor($time/60);
			$time -= $minutes*60;
		}
		$seconds = $time;

		$return = array();
		if($time2 > 86400)
		{
			if($days == 1)
				$days .= '&nbsp;Tag';
			else
				$days .= '&nbsp;Tage';
			$return[] = $days;
		}
		if($time2 > 3600)
		{
			if($hours == 1)
				$hours .= '&nbsp;Stunde';
			else
				$hours .= '&nbsp;Stunden';
			$return[] = $hours;
		}
		if($time2 > 60)
		{
			if($minutes == 1)
				$minutes .= '&nbsp;Minute';
			else
				$minutes .= '&nbsp;Minuten';
			$return[] = $minutes;
		}

		if($seconds == 1)
			$seconds .= '&nbsp;Sekunde';
		else
			$seconds .= '&nbsp;Sekunden';
		$return[] = $seconds;

		$return = implode(' ', $return);
		return $return;
	}

	function format_ress($ress, $tabs_count=0, $tritium=false)
	{
		$tabs = '';
		if($tabs_count >= 1)
			$tabs = str_repeat("\t", $tabs_count);

		$return = "<dl class=\"ress\">\n";
		$return .= $tabs."\t<dt class=\"ress-carbon\">Carbon</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-carbon\">".ths($ress[0])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-aluminium\">Aluminium</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-aluminium\">".ths($ress[1])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-wolfram\">Wolfram</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-wolfram\">".ths($ress[2])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-radium\">Radium</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-radium\">".ths($ress[3])."</dd>\n";
		if($tritium)
		{
			$return .= $tabs."\t<dt class=\"ress-tritium\">Tritium</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-tritium\">".ths($ress[4])."</dd>\n";
		}
		$return .= $tabs."</dl>\n";
		return $return;
	}

	function ths($count, $utf8=false)
	{
		if(!isset($count))
			$count = 0;
		$count = floor($count);

		$neg = false;
		if($count < 0)
		{
			$neg = true;
			$count = (int) substr($count, 1);
		}

		$ths = THS_HTML;
		if($utf8)
			$ths = THS_UTF8;
		$count = str_replace('.', $ths, number_format($count, 0, ',', '.'));

		if($neg)
			$count = '&minus;'.$count;

		return $count;
	}

	function utf8_htmlentities($string, $nospecialchars=false, $js=false)
	{
		if($js)
			$rep = array("'\\\\u'.add_nulls(dechex(", "), 4)");
		else
			$rep = array("'&#'.(", ").';'");

		if(!$nospecialchars)
			$string = htmlspecialchars($string);

		$string = preg_replace("/([\\xc0-\\xdf])([\\x80-\\xbf])/e", $rep[0]."64*ord('$1')+ord('$2')-12416".$rep[1], $string);
		$string = preg_replace("/([\\xe0-\\xef])([\\x80-\\xbf])([\\x80-\\xbf])/e", $rep[0]."4096*ord('$1')+64*ord('$2')+ord('$3')-925824".$rep[1], $string);
		$string = preg_replace("/([\\xf0-\\xf7])([\\x80-\\xbf])([\\x80-\\xbf])([\\x80-\\xbf])/e", $rep[0]."262144*ord('$1')+2048*ord('$2')+64*ord('$3')+ord('$4')-63185024)".$rep[1], $string);

		return $string;
	}

	function utf8_jsentities($string)
	{
		return utf8_htmlentities($string, true, true);
	}

	function ob_utf8($string)
	{
		$now_mtime = explode(' ', microtime());
		$start_mtime = explode(' ', start_mtime);

		$string .= /*'<!-- '.*/(array_sum($now_mtime)-array_sum($start_mtime))/*.' -->'*/;

		return utf8_encode($string);
	}

	if(!function_exists('array_product'))
	{
		function array_product($array)
		{
			$return = 1;
			foreach($array as $val)
				$return *= $val;
			return $return;
		}
	}

	function add_nulls($count, $len)
	{
		while(strlen($count) < $len)
			$count = '0'.$count;

		return $count;
	}

	function string2bin($string)
	{
		$return = '';

		$len = strlen($string);
		for($i = 0; $i < $len; $i++)
			$return .= add_nulls(decbin(ord($string{$i})), 8);

		return $return;
	}

	function bin2string($bin)
	{
		$return = '';

		$len = strlen($bin);
		for($i=0; $i < $len; $i+=8)
		{
			$substr = substr($bin, $i, 8);
			$return .= chr(bindec($substr));
		}

		return $return;
	}

	function diff($ao, $bo)
	{
		$diff = max($ao, $bo)-min($ao, $bo);

		return $diff;
	}
?>