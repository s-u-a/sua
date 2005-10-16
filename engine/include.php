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

	class eventhandler
	{
		function add_event($time, $event) # Fuegt einen Event hinzu, der vom Eventhandler ausgefuehrt wird
		{
			# IDs fehlen noch!

			$event_string = implode("\t", func_get_args())."\n";

			$event_filesize = filesize(EVENT_FILE);

			$fh = fopen(EVENT_FILE, 'a');
			flock($fh, LOCK_EX);

			if(!$fh)
			{
				logfile::error('Ein Event konnte nicht geschrieben werden.');
				return false;
			}

			if($event_filesize > 0)
			{
				fseek($fh, -1, SEEK_CUR);
				if(!in_array(fread($fh, 1), array("\n", "\r"))) # Eventuelles \n fuer Leerzeile anfuegen
					fwrite($fh, "\n");
			}

			fwrite($fh, $event_string);

			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}
	}

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
			{
				die('false');
				return false;
			}

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

	function get_user_array($username)
	{
		if(!is_file(DB_PLAYERS.'/'.urlencode($username)) || !is_readable(DB_PLAYERS.'/'.urlencode($username)))
			return false;
		return unserialize(gzuncompress(file_get_contents(DB_PLAYERS.'/'.urlencode($username))));
	}

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
			<li><a href="./">Neuigkeiten</a></li>
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

	class admin_gui
	{
		function html_head()
		{
		}

		function html_foot()
		{
		}
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