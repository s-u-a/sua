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
	 * Basis-Include-Datei. Kümmert sich um die richtige Konfiguration aller
	 * Parameter.
	 * @author Candid Dauth
	 * @package sua
	*/

	namespace sua;

	define('start_mtime', microtime(true));

	error_reporting(4095);
	ignore_user_abort(true);

	# 10 Minuten sollten wohl auch bei hoher Serverlast genuegen
	set_time_limit(600);

	# s_root ermitteln: Absoluter Pfad zum Spielverzeichnis
	$this_filename = '/engine.php';
	$__FILE__ = str_replace('\\', '/', __FILE__);
	if(substr($__FILE__, -strlen($this_filename)) !== $this_filename)
	{
		echo "The absolute path could not be detected. Please modify \$this_filename in ".__FILE__.", line ".(__LINE__-4)."\n";
		exit(1);
	}
	define('s_root', realpath(substr($__FILE__, 0, -strlen($this_filename))));

	if(isset($_SERVER['SCRIPT_FILENAME']) && isset($_SERVER['PHP_SELF']) && substr($_SERVER['SCRIPT_FILENAME'], -strlen($_SERVER['PHP_SELF'])) == $_SERVER['PHP_SELF'])
		$document_root = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['PHP_SELF']));
	elseif(isset($_SERVER['DOCUMENT_ROOT']) && substr(s_root, strlen($tmp = realpath($_SERVER["DOCUMENT_ROOT"]))) == $tmp)
		$document_root = $_SERVER['DOCUMENT_ROOT'];
	else $document_root = '/';

	# h_root ermitteln: Absoluter Pfad zum Spielverzeichnis vom Webserver-Document-Root aus gesehen
	if(substr($document_root, -1) == '/')
		$document_root = substr($document_root, 0, -1);
	$document_root = realpath($document_root);
	define('h_root', "".substr(s_root, strlen($document_root)));

	# Locale eintragen
	bindtextdomain("sua", s_root."/locale");
	bind_textdomain_codeset("sua", "utf-8");
	textdomain("sua");

	# PEAR einbinden
	if(is_dir(s_root."/pear"))
		set_include_path(".:".s_root."/pear/:".get_include_path());

	# SSL auf Wunsch abschalten
	if(isset($_GET['nossl']))
	{
		if($_GET['nossl'] && (!isset($_COOKIE['use_ssl']) || $_COOKIE['use_ssl']))
		{
			setcookie('use_ssl', '0', time()+4838400, h_root."/");
			$_COOKIE['use_ssl'] = '0';
		}
		elseif(!$_GET['nossl'] && isset($_COOKIE['use_ssl']) && !$_COOKIE['use_ssl'])
		{
			setcookie('use_ssl', '1', time()+4838400, h_root."/");
			$_COOKIE['use_ssl'] = '1';
		}
	}

	# Cookies testen
	if(!isset($_COOKIE["use_cookies"]) && !headers_sent())
		setcookie("use_cookies", "1", time()+4838400, h_root."/");
	ini_set("session.use_cookies", "0");

	/**
	  * Liest oder setzt eine globale Einstellung.
	  * @param $key Name der Einstellung
	  * @param $value Wenn angegeben, setzt die Einstellung auf den gegebenen Wert
	*/

	function global_setting($key, $value=null)
	{
		static $settings;

		if($value === null)
		{
			if(!isset($settings[$key])) return null;
			else return $settings[$key];
		}
		else
		{
			$settings[$key] = $value;
			return true;
		}
	}

	$GDB_DIR = s_root.'/database.global';
	global_setting('GDB_DIR', $GDB_DIR);
	global_setting('DB_NEWS', $GDB_DIR.'/news');
	global_setting('DB_CHANGELOG', $GDB_DIR.'/changelog');
	global_setting('DB_VERSION', $GDB_DIR.'/version');
	global_setting('DB_REVISION', $GDB_DIR.'/revision');
	global_setting("DB_CONFIG", $GDB_DIR."/config.xml");
	global_setting("DB_CONFIG_CACHE", $GDB_DIR."/config.db");
	global_setting('DB_NOTIFICATIONS', $GDB_DIR.'/notifications');
	global_setting('DB_EVENTHANDLER_LOG', $GDB_DIR.'/eventhandler.log');
	global_setting('DB_EVENTHANDLER_PIDFILE', $GDB_DIR.'/eventhandler.pid');
	global_setting("DB_IMSERVER_PIDFILE", $GDB_DIR."/imserver.pid");
	global_setting('EVENTHANDLER_INTERVAL', 2);
	global_setting('EVENTHANDLER_MARKETCACHE', 10); # Wieviele Eventhandler-Intervalle sollen aus der Boersendatenbank gecacht werden?
	global_setting('MIN_CLICK_DIFF', 0.3); # Sekunden, die zwischen zwei Klicks mindestens vergehen muessen, sonst Bremsung
	global_setting('EMAIL_FROM', 'webmaster@s-u-a.net');
	global_setting('MAX_PLANETS', 15);
	global_setting('LIST_MIN_CHARS', 1); # Fuer Ajax-Auswahllisten
	global_setting('ALLIANCE_RENAME_PERIOD', 3); # Minimalabstand fuers Umbenennen von Allianzen in Tagen
	global_setting('PROTOCOL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http');
	global_setting('USE_PROTOCOL', (isset($_SESSION['use_protocol']) ? $_SESSION['use_protocol'] : (((!isset($_COOKIE['use_ssl']) || $_COOKIE['use_ssl'])) ? 'https' : 'http')));
	global_setting('MIN_BUILDING_TIME', 12); # Minimale Bauzeit in Sekunden
	global_setting('DATABASE_VERSION', 9); # Aktuelle Datenbankversion
	global_setting('EVENTHANDLER_RUNTIME', 16200); # Sekunden seit Tagesbeginn, wann der Eventhandler laufen soll
	global_setting('MARKET_MIN_AMOUNT', 10); # Das Wievielfache eines Angebotes muss insgesamt geboten worden sein, damit ein Auftrag angenommen wird?
	global_setting('MARKET_MIN_USERS', 5); # Wieviele verschiedene Benutzer muessen den Rohstoff als Angebot auf dem Markt haben, damit ein Auftrag angenommen wird?
	global_setting('MARKET_DELAY', 7200); # Wieviele Sekunden soll es von der Annahme bis zur Fertigstellung eines Angebotes dauern?
	global_setting('EMAIL_CHANGE_DELAY', 604800); # Nach wie vielen Sekunden soll eine Aenderung der E-Mail-Adresse gueltig werden?
	global_setting("HIGHSCORES_PERPAGE", 100); # Wieviele Spieler sollen in den Highscores pro Seite angezeigt werden?
	global_setting("CHALLENGE_MIN_TIME", 900); # Wieviele Sekunden müssen mindestens zwischen zwei Captcha-Abfragen vergehen?
	global_setting("CHALLENGE_MAX_TIME", 5400); # Wieviele Sekunden dürfen maximal zwischen zwei Captcha-Abfragen vergehen?
	global_setting("CHALLENGE_MAX_FAILURES", 8); # Wieoft hintereinander darf ein Benutzer maximal eine Captcha-Abfrage falsch beantworten?
	global_setting("CHALLENGE_LOCK_TIME", 86400); # Für wieviele Sekunden wird ein Benutzer gesperrt, wenn er eine Captcha-Abfrage zu oft falsch beantwortet hat?
	global_setting("MIN_PRODUCTION", array(20, 10, 0, 0, 0)); # Die Produktion kann nicht unter diesen Wert sinken
	global_setting("PRODUCTION_LIMIT_INITIAL", array(500000, 500000, 500000, 500000, 500000, 1000000)); # Initiallimits für Rohstoffspeicher
	global_setting("PRODUCTION_LIMIT_STEPS", array(100000, 100000, 100000, 100000, 100000, 10000000)); # Wachstum der Rohstoffspeicher je gebauten Roboter/Energietechnik
	global_setting("RELOAD_LIMIT", 100); # Alle wieviel gebauten Roboter/Schiffe/Verteidigungsanlagen soll der Benutzeraccount neugeladen werden?
	global_setting("RELOAD_STACK_INTERVAL", 120); # Alle wieviel Sekunden sollen die Benutzeraccounts neugeladen werden?
	global_setting("SESSION_TIMEOUT", 1800); # Wieviele Sekunden Inaktivität sollen zur Zerstörung der Session führen?
	global_setting("CLASSES", dirname(__FILE__)."/classes");
	global_setting("SESSION_NAME", "session"); # Name des URL-Parameters mit der Session-ID
	global_setting("IM_UNRECOGNISED_NUMBER", 10); # Wieviele „Unrecognised Command“-Fehler sollen maximal an einen Benutzer hintereinander vom IM-Bot verschickt werden?
	global_setting("IM_UNRECOGNISED_TIME", 300); # Wieviel Zeit muss vergehen, damit dieses Limit zurückgesetzt wird?
	global_setting("LOG", fopen("php://stderr", "w"));

	function __autoload($classname)
	{
		$fname = global_setting("CLASSES")."/".strtolower($classname).".php";
		if(!is_file($fname)) return false;
		include_once($fname);
	}

	date_default_timezone_set(@date_default_timezone_get());
	l::language("de_DE");

	// TODO: Get rid of document.write and innerHTML
	//if(isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false)
		//define('CONTENT_TYPE', 'application/xhtml+xml; charset=UTF-8');
	//else
		define('CONTENT_TYPE', 'text/html; charset=UTF-8');
	if(isset($_SERVER["HTTP_HOST"]))
		header('Content-type: '.CONTENT_TYPE);

	// Script-Filename herausfinden
	if(!isset($_SERVER["SCRIPT_FILENAME"]) && substr($_SERVER["PHP_SELF"], 0, strlen(h_root)) == $_SERVER["PHP_SELF"])
		$_SERVER["SCRIPT_FILENAME"] = s_root.substr($_SERVER["PHP_SELF"], strlen(h_root));

	#if(!isset($USE_OB) || $USE_OB)
	#	ob_start('ob_gzhandler');

	$tabindex = 1;

	if(!isset($LOGIN) || !$LOGIN)
		Functions::checkHostname();

	if(!isset($_SESSION))
		$GLOBALS['_SESSION'] = array();

	# TODO: Die folgenden Dinge in eine globale Einstellung auslagern

	# Maximales Alter in Tagen der Nachrichtensorten
	$message_type_times = array (
		Message::TYPE_KAEMPFE => 3,
		Message::TYPE_SPIONAGE => 3,
		Message::TYPE_TRANSPORT => 2,
		Message::TYPE_SAMMELN => 2,
		Message::TYPE_BESIEDELUNG => 1,
		Message::TYPE_BENUTZERNACHRICHTEN => 5,
		Message::TYPE_VERBUENDETE => 4,
		Message::TYPE_POSTAUSGANG => 2
	);

	# Fuer veroeffentlichte Nachrichten
	$public_messages_time = 30;

	# Zu jeder Flottenauftragsart die zugehoerige Nachrichtensorte
	$types_message_types = array (
		Fleet::TYPE_BESIEDELN => Message::TYPE_BESIEDELUNG,
		Fleet::TYPE_SAMMELN => Message::TYPE_SAMMELN,
		Fleet::TYPE_ANGRIFF => Message::TYPE_KAEMPFE,
		Fleet::TYPE_TRANSPORT => Message::TYPE_TRANSPORT,
		Fleet::TYPE_SPIONIEREN => Message::TYPE_SPIONAGE,
		Fleet::TYPE_STATIONIEREN => Message::TYPE_TRANSPORT
	);

	# Version herausfinden
	$version = Config::getVersion();
	define('VERSION', $version);

	function &stripslashes_r(&$var)
	{ # Macht rekursiv in einem Array addslashes() rueckgaengig
		if(is_array($var))
		{
			foreach($var as $key=>$val)
				stripslashes_r($var[$key]);
		}
		else
			$var = stripslashes($var);
		return $var;
	}
	# magic_quotes_gpc abschalten
	if(get_magic_quotes_gpc())
	{
		stripslashes_r($_POST);
		stripslashes_r($_GET);
		stripslashes_r($_COOKIE);
	}

	if(isset($_GET["agpl"]) && $_GET["agpl"] == "!" && isset($_SERVER["SCRIPT_FILENAME"]))
	{
		header("Content-type: application/x-httpd-php;charset=UTF-8");
		print file_get_contents($_SERVER["SCRIPT_FILENAME"]);
		exit(0);
	}

	Config::getConfig();

	function h(){ $args = func_get_args(); return call_user_func_array(array("l", "h"), $args); }