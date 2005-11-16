<?php
	define('start_mtime', microtime());

	error_reporting(2047);
	ignore_user_abort();

	# Konstanten, die wichtige Pfade enthalten
	$DB_DIR = '../sua.db'; # Relativ zum Hauptverzeichnis des Spiels

		# Auswertung von $DB_DIR
		if(substr($DB_DIR, 0, 1) != '/')
		{ # Wenn der Pfad nicht absolut angegeben wurde, wird nun ein absoluter Pfad daraus
			$this_filename = '/engine/include.php';
			if(substr(__FILE__, -strlen($this_filename)) !== $this_filename)
			{
				logfile::panic('Der absolute Pfad der Datenbank konnte nicht ermittelt werden. Bitte gib ihn in der Datei /engine/include.php an.');
				exit(1);
			}
			define('s_root', substr(__FILE__, 0, -strlen($this_filename)));
			$DB_DIR = s_root.'/'.$DB_DIR;
			$document_root = $_SERVER['DOCUMENT_ROOT'];
			if(substr($document_root, -1) == '/')
				$document_root = substr($document_root, 0, -1);
			define('h_root', substr(s_root, strlen($document_root)));
		}

	$EVENT_FILE = $DB_DIR.'/events';
	$LOG_FILE = $DB_DIR.'/logfile';
	$LOCK_FILE = $DB_DIR.'/locked';
	$DB_PLAYERS = $DB_DIR.'/players';
	$DB_UNIVERSE = $DB_DIR.'/universe';
	$DB_ITEMS = $DB_DIR.'/items';
	$DB_MESSAGES = $DB_DIR.'/messages';
	$DB_HIGHSCORES = $DB_DIR.'/highscores';
	$DB_TRUEMMERFELDER = $DB_DIR.'/truemmerfelder';
	$DB_HOSTNAME = $DB_DIR.'/hostname';
	$DB_HANDEL = $DB_DIR.'/handel';
	$DB_HANDELSKURS = $DB_DIR.'/handelskurs';
	$DB_ADMINS = $DB_DIR.'/admins';
	$EVENTHANDLER_INTERVAL = 30;
	$THS_HTML = '&nbsp;';
	$THS_UTF8 = "\xc2\xa0";
	#$THS_UTF8 = "\xe2\x80\x89";
	$MIN_CLICK_DIFF = 0.5; # Sekunden, die zwischen zwei Klicks mindestens vergehen muessen, sonst Bremsung
	$EMAIL_FROM = 'webmaster@s-u-a.net';
	$MAX_PLANETS = 15;
	$SESSION_COOKIE = ini_get('session.name');
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
		$PROTOCOL = 'https';
	else
		$PROTOCOL = 'http';

	if(isset($_GET['nossl']))
	{
		if($_GET['nossl'] && (!isset($_COOKIE['use_ssl']) || $_COOKIE['use_ssl']))
		{
			setcookie('use_ssl', '0', time()+4838400, h_root);
			$_COOKIE['use_ssl'] = '0';
		}
		elseif(!$_GET['nossl'] && isset($_COOKIE['use_ssl']) && !$_COOKIE['use_ssl'])
		{
			setcookie('use_ssl', '1', time()+4838400, h_root);
			$_COOKIE['use_ssl'] = '1';
		}
	}
	if(!isset($_COOKIE['use_ssl']) || $_COOKIE['use_ssl'])
		$USE_PROTOCOL = 'https';
	else
		$USE_PROTOCOL = 'http';

	# Variablen als Konstanten speichern

	define('DB_DIR', $DB_DIR);
	define('EVENT_FILE', $EVENT_FILE);
	define('LOG_FILE', $LOG_FILE);
	define('LOCK_FILE', $LOCK_FILE);
	define('DB_PLAYERS', $DB_PLAYERS);
	define('DB_UNIVERSE', $DB_UNIVERSE);
	define('DB_ITEMS', $DB_ITEMS);
	define('DB_MESSAGES', $DB_MESSAGES);
	define('DB_HIGHSCORES', $DB_HIGHSCORES);
	define('DB_TRUEMMERFELDER', $DB_TRUEMMERFELDER);
	define('DB_HOSTNAME', $DB_HOSTNAME);
	define('DB_HANDEL', $DB_HANDEL);
	define('DB_HANDELSKURS', $DB_HANDELSKURS);
	define('DB_ADMINS', $DB_ADMINS);
	define('EVENTHANDLER_INTERVAL', $EVENTHANDLER_INTERVAL);
	define('THS_HTML', $THS_HTML);
	define('THS_UTF8', $THS_UTF8);
	define('MIN_CLICK_DIFF', $MIN_CLICK_DIFF);
	define('EMAIL_FROM', $EMAIL_FROM);
	define('MAX_PLANETS', $MAX_PLANETS);
	define('SESSION_COOKIE', $SESSION_COOKIE);
	define('PROTOCOL', $PROTOCOL);
	define('USE_PROTOCOL', $USE_PROTOCOL);

	header('Content-type: text/html; charset=UTF-8');

	if(!isset($USE_OB) || $USE_OB)
	{
		ob_start('ob_gzhandler');
		ob_start('ob_utf8');
	}

	if(!isset($_SESSION))
		$GLOBALS['_SESSION'] = array();

	# Ueberpruefen, ob der Hostname korrekt ist
	if(isset($_SERVER['HTTP_HOST']))
	{
		$redirect = false;
		$hostname = $_SERVER['HTTP_HOST'];
		if(is_file(DB_HOSTNAME) && is_readable(DB_HOSTNAME))
		{
			$hostname = trim(file_get_contents(DB_HOSTNAME));
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
			$url = PROTOCOL.'://'.$hostname.$_SERVER['PHP_SELF'];
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

	$message_type_times = array (
		1 => 3,
		2 => 3,
		3 => 2,
		4 => 2,
		5 => 1,
		6 => 5,
		7 => 4,
		8 => 2
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

		function action($type)
		{
			global $this_planet;
			global $_SESSION;

			$cols = func_get_args();
			if(isset($this_planet)) array_unshift($cols, $this_planet['pos']);
			else array_unshift($cols, '');
			array_unshift($cols, session_id());
			array_unshift($cols, $_SERVER['REMOTE_ADDR']);
			if(isset($_SESSION['username'])) array_unshift($cols, $_SESSION['username']);
			else array_unshift($cols, '');
			array_unshift($cols, time());

			$line = implode("\t", $cols);

			$fh = gzopen(LOG_FILE, 'a');
			if(!$fh)
				return false;
			#flock($fh, LOCK_EX);

			gzwrite($fh, $line."\n");

			#flock($fh, LOCK_UN);
			gzclose($fh);

			return true;
		}

		function panic($message) # Gibt eine fatale Fehlermeldung aus, aufgrund deren das Script
		{ # nicht weiter ausgefuehrt werden kann und bricht es ab.
			if(isset($_SERVER['HTTP_HOST'])) # Wurde per HTTP aufgerufen
				die($message);
			else # Wurde per Programm aufgerufen, zum Beispiel Eventhandler
				echo $message."\n";
		}

		function to_human($array)
		{
			global $items;
			if(!isset($items))
				$items = get_items(false);
			global $type_names;

			$type = array_shift($array);

			switch($type)
			{
				case '0': return sprintf('Schnellklicksperre (Klickabstand %f)', $array[0]);
				case '1': return sprintf('Registrierung (E-Mail-Adresse: %s)', utf8_htmlentities($array[0]));
				case '2': return sprintf('Anmeldung');
				case '2.1': return sprintf('Anmeldung mit falschem Passwort');
				case '3': return sprintf('Abgemeldet');
				case '3.1': return sprintf('Zwangsabgemeldet (Richtige IP wäre %s)', $array[0]);
				case '4': return sprintf('Passwort zugeschickt');
				case '4.1': return sprintf('Passwort nicht zugeschickt (falsch: %s, richtig: %s)', utf8_htmlentities($array[0]), utf8_htmlentities($array[1]));
				case '5': return sprintf('Passwort geändert');
				case '6': return sprintf('Rohstoffproduktion gespeichert');
				case '7':
					if($array[1]) return sprintf('Gebäuderückbau (%s)', utf8_htmlentities($items['gebaeude'][$array[0]]['name']));
					else return sprintf('Gebäudeausbau (%s)', utf8_htmlentities($items['gebaeude'][$array[0]]['name']));
				case '8': return sprintf('Gebäude-Abbruch (%s)', utf8_htmlentities($items['gebaeude'][$array[0]]['name']));
				case '9': return sprintf('Forschung (%s, %s)', utf8_htmlentities($items['forschung'][$array[0]]['name']), ($array[1] ? 'global' : 'lokal'));
				case '10': return sprintf('Forschungs-Abbruch (%s)', utf8_htmlentities($items['forschung'][$array[0]]['name']));
				case '11': return sprintf('<span title="%s">Roboterbau</span>', logfile::crowd_to_human($array[0]));
				case '12': return sprintf('<span title="%s; %s; %s">Flotte</span> nach %s (%s, %u%%, ID: %s)', logfile::crowd_to_human($array[1]), logfile::ress_to_human($array[4]), logfile::crowd_to_human($array[5]), $array[0], $type_names[$array[2]], $array[3]*100, utf8_htmlentities($array[6]));
				case '13': return sprintf('Flottenrückruf (%s)', utf8_htmlentities($array[0]));
				case '14': return sprintf('<span title="%s">Schiffsbau</span>', logfile::crowd_to_human($array[0]));
				case '15': return sprintf('<span title="%s">Verteidigungsbau</span>', logfile::crowd_to_human($array[0]));
				case '16': return sprintf('Benutzerinfo angeschaut (%s)', utf8_htmlentities($array[0]));
				case '17': return sprintf('Bündnisanfrage gesandt (%s)', utf8_htmlentities($array[0]));
				case '17.5': return sprintf('Bündnisanfrage zurückgezogen (%s)', utf8_htmlentities($array[0]));
				case '18': return sprintf('Bündnisanfrage angenommen (%s)', utf8_htmlentities($array[0]));
				case '19': return sprintf('Bündnisanfrage abgelehnt (%s)', utf8_htmlentities($array[0]));
				case '20': return sprintf('Bündnis gekündigt (%s)', utf8_htmlentities($array[0]));
				case '21': return sprintf('<span title="%s">Nachricht</span> an %s (%s)', utf8_htmlentities($array[2]), utf8_htmlentities($array[1]), $array[0]);
				case '22': return sprintf('Nachricht gelesen (%s)', utf8_htmlentities($array[0]));
				case '23': return sprintf('Nachricht gelöscht (%s)', utf8_htmlentities($array[0]));
				case '24': return sprintf('Urlaubsmodus %s', ($array[0] ? 'betreten' : 'verlassen'));
				default: return sprintf('Unbekannte Aktion (%s)', $type);
			}
		}

		function crowd_to_human($crowd2)
		{
			global $items;

			if(trim($crowd2) == '')
				return '';

			$return = array();
			$crowd = explode(' ', trim($crowd2));

			for($i=0; $i<count($crowd); $i+=2)
				$return[] = utf8_htmlentities($items['ids'][$crowd[$i]]['name']).': '.ths($crowd[$i+1]);

			$return = implode(', ', $return);
			return $return;
		}

		function ress_to_human($ress)
		{
			$return = 'Carbon: '.ths($ress[0]).', Aluminium: '.ths($ress[1]).', Wolfram: '.ths($ress[2]).', Radium: '.ths($ress[3]).', Tritium: '.ths($ress[4]);
			return $return;
		}
	}

	########################################

	class universe
	{
		function get_planet_info($check_galaxy, $check_system=false, $check_planet=false) # Findet die Groesse, Eigentuemer und Namen des Planeten heraus
		{ # unter den angegebenen Koordinaten heraus.
			if($check_system !== false && $check_planet !== false)
			{
				$check_planets = array($check_galaxy.':'.$check_system.':'.$check_planet);
				$return_array = false;
			}
			else
			{
				$check_planets = $check_galaxy;
				$return_array = true;
			}

			uasort($check_planets, 'sort_koords');

			$planets_ass = array();
			foreach($check_planets as $id=>$planet)
			{
				$planet = explode(':', $planet);
				if(count($planet) < 3 || $planet[2]%1 != 0 || $planet[2] < 1 || $planet[1]%1 != 0 || $planet[1] < 1 || !is_file(DB_UNIVERSE.'/'.$planet[0]) || !is_readable(DB_UNIVERSE.'/'.$planet[0]))
					continue;
				if(!isset($planets_ass[$planet[0]]))
					$planets_ass[$planet[0]] = array();
				if(!isset($planets_ass[$planet[0]][$planet[1]]))
					$planets_ass[$planet[0]][$planet[1]] = array();
				$planets_ass[$planet[0]][$planet[1]][] = $planet[2];
			}

			$filesize = array();
			foreach($planets_ass as $galaxy=>$systems)
			{
				$filesize[$galaxy] = filesize(DB_UNIVERSE.'/'.$galaxy);
				$system_keys = array_keys($systems);
				while($filesize[$galaxy] < ($last_key = array_pop($system_keys))*1475) # System existiert nicht
					unset($planets_ass[$galaxy][$last_key]);
				if(count($planets_ass[$galaxy]) <= 0)
					unset($planets_ass[$galaxy]);
			}

			if(count($planets_ass) <= 0)
				return false;

			$strings = array();

			foreach($planets_ass as $galaxy=>$systems)
			{
				$fh = fopen(DB_UNIVERSE.'/'.$galaxy, 'rb');
				flock($fh, LOCK_SH);

				$strings[$galaxy] = array();
				$system_keys = array_keys($systems);
				foreach($system_keys as $system)
				{
					fseek($fh, ($system-1)*1475, SEEK_SET);
					$strings[$galaxy][$system] = fread($fh, 1475);
				}

				flock($fh, LOCK_UN);
				fclose($fh);
			}

			$info = array();

			foreach($planets_ass as $galaxy=>$systems)
			{
				foreach($systems as $system=>$planets)
				{
					$this_string = & $strings[$galaxy][$system];
					$planets_count = floor(ord($this_string{0})/8)+10;

					foreach($planets as $i=>$planet)
					{
						if($planet <= $planets_count)
						{
							$bin = '';
							$len = strlen($this_string);
							for($i=0; $i < $len; $i++)
								$bin .= add_nulls(decbin(ord($this_string{$i})), 8);

							$size = bindec(substr($bin, 5+($planet-1)*9, 9))+100;
							$owner_name = trim(bin2string(substr($bin, 275+($planet-1)*192, 192)));
							$planet_name = trim(bin2string(substr($bin, 6035+($planet-1)*192, 192)));

							$info[$galaxy.':'.$system.':'.$planet] = array($size, $owner_name, $planet_name);
							unset($bin);
						}
					}
				}
			}

			foreach($check_planets as $koords)
			{
				if(!isset($info[$koords]))
					$info[$koords] = false;
			}

			if($return_array)
				return $info;
			else
				return array_shift($info);
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

			return $id;
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
		<title xml:lang="en">S-U-A &ndash; Stars Under Attack</title>
		<link rel="stylesheet" href="<?=h_root?>/style.css" type="text/css" />
	</head>
	<body><div id="content-1"><div id="content-2"><div id="content-3"><div id="content-4"><div id="content-5"><div id="content-6"><div id="content-7"><div id="content-8">
		<h1 id="logo"><a href="./" title="Zurück zur Startseite" xml:lang="en">Stars Under Attack</a></h1>
		<form action="<?=htmlentities(USE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/login/index.php')?>" method="post" id="login-form">
			<fieldset>
				<legend>Anmelden</legend>
				<dl>
					<dt><label for="login-username">Name</label></dt>
					<dd><input type="text" id="login-username" name="username" /></dd>

					<dt><label for="login-password">Passwort</label></dt>
					<dd><input type="password" id="login-password" name="password" /></dd>
				</dl>
				<ul>
					<li class="c-anmelden"><button type="submit">Anmelden</button></li>
					<li class="c-registrieren"><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/register.php">Registrieren</a></li>
					<li class="c-passwort-vergessen"><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/passwd.php">Passwort vergessen?</a></li>
<?php
			if(USE_PROTOCOL == 'https')
			{
?>
					<li class="c-ssl-abschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=1"><abbr title="Secure Sockets Layer" xml:lang="en"><span xml:lang="de">SSL</span></abbr> abschalten</a></li>
<?php
			}
			else
			{
?>
					<li class="c-ssl-einschalten"><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']?>?nossl=0"><abbr title="Secure Sockets Layer" xml:lang="en"><span xml:lang="de">SSL</span></abbr> einschalten</a></li>
<?php
			}
?>
				</ul>
			</fieldset>
		</form>
		<ol id="navigation">
			<li><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/index.php">Neuigkeiten</a></li>
			<li><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/register.php">Registrieren</a></li>
			<li><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/rules.php">Regeln</a></li>
			<li><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/faq.php"><abbr title="Frequently Asked Questions" xml:lang="en">FAQ</abbr></a></li>
			<li><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/forum/">Forum</a></li>
			<li><a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/impressum.php">Impressum</a></li>
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

	function get_laufende_forschungen()
	{
		global $user_array;

		$laufende_forschungen = array();
		$planets = array_keys($user_array['planets']);
		foreach($planets as $planet)
		{
			if(isset($user_array['planets'][$planet]['building']['forschung']) && trim($user_array['planets'][$planet]['building']['forschung'][0]) != '')
				$laufende_forschungen[] = $user_array['planets'][$planet]['building']['forschung'][0];
			elseif(isset($user_array['planets'][$planet]['building']['gebaeude']) && $user_array['planets'][$planet]['building']['gebaeude'][0] == 'B8')
				$laufende_forschungen[] = false;
		}
		return $laufende_forschungen;
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

		function get_time($mass, $distance, $speed)
		{
			if($speed <= 0)
				$time = 900;
			else
				$time = round((pow($mass, 0.95)/$speed)*pow($distance, 0.3)*100);
			#$time = round(pow(1.125*$mass*pow($distance, 2)/$speed, 0.33333)*10);

			return $time;
		}

		function get_tritium($mass, $distance)
		{
			return round(($distance*$mass)/1000000);
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
			if($carbon <= 0 && $aluminium <= 0 && $wolfram <= 0 && $radium <= 0)
			{
				if(is_file(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet))
					return unlink(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet);
				else
					return true;
			}

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
		function recalc($use_username=false)
		{
			if($use_username === false && !isset($_SESSION['username']))
				return false;
			elseif($use_username === false)
				$use_username = $_SESSION['username'];

			if(isset($_SESSION['username']) && $use_username == $_SESSION['username'])
				global $user_array;
			else
				$user_array = get_user_array($use_username);

			$use_username = $user_array['username'];

			$old_position = $user_array['punkte'][12];
			$old_position_f = ($old_position-1)*32;

			$new_points = floor($user_array['punkte'][0]+$user_array['punkte'][1]+$user_array['punkte'][2]+$user_array['punkte'][3]+$user_array['punkte'][4]+$user_array['punkte'][5]+$user_array['punkte'][6]);
			$new_points_bin = add_nulls(base_convert($new_points, 10, 2), 64);
			$new_points_str = '';
			for($i = 0; $i < strlen($new_points_bin); $i+=8)
				$new_points_str .= chr(bindec(substr($new_points_bin, $i, 8)));
			unset($new_points_bin);
			$my_string = substr($use_username, 0, 24);
			if(strlen($use_username) < 24)
				$my_string .= str_repeat(' ', 24-strlen($use_username));
			if(strlen($use_username) > 24)
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

		function make_info($username, $points)
		{
			$string = substr($username, 0, 24);
			if(strlen($string) < 24)
				$string .= str_repeat(' ', 24-strlen($string));
			$points_bin = add_nulls(base_convert($points, 10, 2), 64);
			for($i = 0; $i < strlen($points_bin); $i+=8)
				$string .= chr(bindec(substr($points_bin, $i, 8)));
			return $string;
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
		$user_array = unserialize(gzuncompress(file_get_contents(DB_PLAYERS.'/'.urlencode($username))));
		if(file_exists(LOCK_FILE))
		{
			# Spiel ist gesperrt
			if(isset($user_array['umode']))
				$user_array['umode.sav'] = $user_array['umode'];
			if(isset($user_array['locked']))
				$user_array['locked.sav'] = $user_array['locked'];
			$user_array['locked'] = $user_array['umode'] = true;
			$user_array['game_locked'] = true;
		}

		return $user_array;
	}

	function write_user_array($username=false, $that_user_array=false)
	{
		if($username !== false && $that_user_array === false)
			return false;
		if($username === false)
		{
			if(!isset($_SESSION['username']))
				return false;
			else
				$username = $_SESSION['username'];
		}

		if(isset($_SESSION['username']) && $username == $_SESSION['username'])
		{
			global $user_array;
			if(isset($user_array['game_locked']) && $user_array['game_locked'])
				$that_user_array = $user_array;
			else
				$that_user_array = &$user_array;
		}

		if(isset($that_user_array['game_locked']) && $that_user_array['game_locked'])
		{
			unset($that_user_array['locked']);
			unset($that_user_array['locked.sav']);
			if(isset($that_user_array['locked.sav']))
			{
				$that_user_array['locked'] = $that_user_array['locked.sav'];
				unset($that_user_array['locked.sav']);
			}
			if(isset($that_user_array['umode.sav']))
			{
				$that_user_array['umode'] = $that_user_array['umode.sav'];
				unset($that_user_array['umode.sav']);
			}
			unset($that_user_array['game_locked']);
		}

		if(!isset($that_user_array['username']) || $that_user_array['username'] != $username)
		{
			$meldung = 'Schutz vor sich selbst: Gerade wollte das Programm den Benutzeraccount des Spielers '.$username.' mit dem ';
			if(!isset($that_user_array['username']))
				$meldung .= 'eines unbekannten Spielers';
			else
				$meldung .= 'von '.$that_user_array['username'];
			$meldung .= ' ueberschreiben.';
			logfile::panic($meldung);
			return false;
		}

		$fh = fopen(DB_PLAYERS.'/'.urlencode($username), 'w');
		if(!$fh)
			return false;
		flock($fh, LOCK_EX);
		fwrite($fh, gzcompress(serialize($that_user_array)));
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

					$items['ids'][$item[0]]['buildable'] = $deps;
				}
			}
		}

		return $items;
	}

	function get_ges_prod($globalise=false)
	{
		global $this_planet;
		global $items;
		global $user_array;

		if($globalise)
		{
			global $carbon_f;
			global $aluminium_f;
			global $wolfram_f;
			global $radium_f;
			global $tritium_f;
			global $energie_f;

			global $energie_mangel;
		}

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
			$this_prod[5] *= pow($stufe, 2)*$prod;

			if($this_prod[5] > 0)
				$this_prod[5] *= $energie_f;

			$this_prod[5] = round($this_prod[5]);

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

	function get_admin_list()
	{
		$admins = array();
		if(!is_file(DB_ADMINS) || !is_readable(DB_ADMINS))
			return false;
		$admin_file = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ADMINS));
		foreach($admin_file as $line)
		{
			$line = explode("\t", $line);
			if(count($line) < 2)
				continue;

			$this = &$admins[urldecode(array_shift($line))];
			$this = array();
			$this['password'] = array_shift($line);
			$this['permissions'] = $line;

			unset($this);
		}

		return $admins;
	}

	function write_admin_list($admins)
	{
		$admin_file = array();
		foreach($admins as $name=>$settings)
		{
			$this = &$admin_file[];
			$this = $name;
			$this .= "\t".$settings['password'];
			if(count($settings['permissions']) > 0)
				$this .= "\t".implode("\t", $settings['permissions']);
			unset($this);
		}

		$fh = fopen(DB_ADMINS, 'w');
		if(!$fh)
			return false;
		flock($fh, LOCK_EX);

		fwrite($fh, implode("\n", $admin_file));

		flock($fh, LOCK_UN);
		fclose($fh);

		return true;
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

	function refresh_ress($globalise=false)
	{
		global $this_planet;
		global $user_array;
		if($globalise)
			global $ges_prod;

		# Rohstoffe aktualisieren
		$now_time = time();
		$last_time = $this_planet['last_refresh'];
		$secs = $now_time-$last_time;

		$ges_prod = get_ges_prod($globalise);

		if(!$user_array['umode'])
		{
			$this_planet['ress'][0] += ($ges_prod[0]/3600)*$secs;
			$this_planet['ress'][1] += ($ges_prod[1]/3600)*$secs;
			$this_planet['ress'][2] += ($ges_prod[2]/3600)*$secs;
			$this_planet['ress'][3] += ($ges_prod[3]/3600)*$secs;
			$this_planet['ress'][4] += ($ges_prod[4]/3600)*$secs;
		}

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

	function ths($count, $utf8=false, $round=0)
	{
		if(!isset($count))
			$count = 0;
		if($round == 0)
			$count = floor($count);
		else
			$count = round($count, $round);

		$neg = false;
		if($count < 0)
		{
			$neg = true;
			if($round == 0)
				$count = (int) substr($count, 1);
			else
				$count = (double) substr($count, 1);
		}

		$ths = THS_HTML;
		if($utf8)
			$ths = THS_UTF8;
		$count = str_replace('.', $ths, number_format($count, $round, ',', '.'));

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

		$string .= '<!-- '.(array_sum($now_mtime)-array_sum($start_mtime)).' -->'."\n";

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

	function sort_koords($a, $b)
	{
		$a_expl = explode(':', $a);
		$b_expl = explode(':', $b);

		if($a_expl[0] > $b_expl[0])
			return 1;
		elseif($a_expl[0] < $b_expl[0])
			return -1;
		else
		{
			if($a_expl[1] > $b_expl[1])
				return 1;
			elseif($a_expl[1] < $b_expl[1])
				return -1;
			else
			{
				if($a_expl[2] > $b_expl[2])
					return 1;
				elseif($a_expl[2] < $b_expl[2])
					return -1;
				else
					return 0;
			}
		}
	}

	function sort_planets($a, $b)
	{
		return sort_koords($a['pos'], $b['pos']);
	}
?>