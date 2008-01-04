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
	define('start_mtime', microtime(true));

	error_reporting(4095);
	ignore_user_abort(true);

	# 10 Minuten sollten wohl auch bei hoher Serverlast genuegen
	set_time_limit(600);

	date_default_timezone_set(@date_default_timezone_get());
	language("de_DE");

	class IOException extends Exception {};

	# s_root ermitteln: Absoluter Pfad zum Spielverzeichnis
	$this_filename = '/engine/include.php';
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
	define('h_root', substr(s_root, strlen($document_root)));

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
			setcookie('use_ssl', '0', time()+4838400, h_root);
			$_COOKIE['use_ssl'] = '0';
		}
		elseif(!$_GET['nossl'] && isset($_COOKIE['use_ssl']) && !$_COOKIE['use_ssl'])
		{
			setcookie('use_ssl', '1', time()+4838400, h_root);
			$_COOKIE['use_ssl'] = '1';
		}
	}

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
	global_setting('DB_MESSENGERS', $GDB_DIR.'/messengers');
	global_setting('DB_NOTIFICATIONS', $GDB_DIR.'/notifications');
	global_setting('DB_EVENTHANDLER_LOG', $GDB_DIR.'/eventhandler.log');
	global_setting('DB_EVENTHANDLER_PIDFILE', $GDB_DIR.'/eventhandler.pid');
	global_setting('DB_DATABASES', $GDB_DIR.'/databases');
	global_setting('DB_HOSTNAME', $GDB_DIR.'/hostname');
	global_setting('DB_GPG', $GDB_DIR.'/gpg');
	global_setting('EVENTHANDLER_INTERVAL', 2);
	global_setting('EVENTHANDLER_MARKETCACHE', 10); # Wieviele Eventhandler-Intervalle sollen aus der Boersendatenbank gecacht werden?
	global_setting('MIN_CLICK_DIFF', 0.3); # Sekunden, die zwischen zwei Klicks mindestens vergehen muessen, sonst Bremsung
	global_setting('EMAIL_FROM', 'webmaster@s-u-a.net');
	global_setting('MAX_PLANETS', 15);
	global_setting('LIST_MIN_CHARS', 2); # Fuer Ajax-Auswahllisten
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

	/**
	  * Initialisiert die Standardwerte fuer die globalen Einstellungen.
	  * Kann mehrmals aufgerufen werden, zum Beispiel, um auf eine andere
	  * Datenbank umzustellen.
	  * @param $DB Datenbank-ID, auf die die Pfade eingestellt werden sollen
	*/

	function define_globals($DB)
	{ # Setzt diverse Spielkonstanten zu einer bestimmten Datenbank
		static $instances_cache;

		if(!isset($instances_cache)) $instances_cache = array();

		$databases = get_databases(false, $databases_aliases);

		$had = array(); # Um Endlosschleifen zu vermeiden
		while(!isset($databases[$DB]))
		{
			if(isset($databases_aliases[$DB]) && !in_array($databases_aliases[$DB], $had))
			{
				$DB = $databases_aliases[$DB];
				$had[] = $DB;
			}
			else return false;
		}

		// Instanzen-Cache auslagern, damit keine Konflikte entstehen
		$old_db = global_setting('DB');
		if($old_db && isset($GLOBALS['objectInstances']) && $GLOBALS['objectInstances'])
		{
			$instances[$old_db] = &$GLOBALS['objectInstances'];
			unset($GLOBALS['objectInstances']);
		}

		if(isset($instances[$DB]))
			$GLOBALS['objectInstances'] = &$instances[$DB];
		else
			$GLOBALS['objectInstances'] = array();

		global_setting('DB', $DB);

		$DB_DIR = $databases[$DB]['directory'];
		if(substr($DB_DIR, 0, 1) != '/')
			$DB_DIR = s_root.'/'.$DB_DIR;

		global_setting('DB_DIR', $DB_DIR);

		global_setting('DB_LOCKED', $DB_DIR.'/locked');
		global_setting('DB_ALLIANCES', $DB_DIR.'/alliances');
		global_setting('DB_PLAYERS', $DB_DIR.'/players');
		global_setting('DB_UNIVERSE', $DB_DIR.'/universe');
		global_setting('DB_ITEMS', $DB_DIR.'/items');
		global_setting('DB_ITEM_DB', $DB_DIR.'/items.db');
		global_setting('DB_TRUEMMERFELDER', $DB_DIR.'/truemmerfelder');
		global_setting('DB_HANDEL', $DB_DIR.'/handel');
		global_setting('DB_HANDELSKURS', $DB_DIR.'/handelskurs');
		global_setting('DB_ADMINS', $DB_DIR.'/admins');
		global_setting('DB_NONOOBS', $DB_DIR.'/nonoobs');
		global_setting('DB_ADMIN_LOGFILE', $DB_DIR.'/admin_logfile');
		global_setting('DB_NO_STRICT_ROB_LIMITS', $DB_DIR.'/no_strict_rob_limits');
		global_setting('DB_GLOBAL_TIME_FACTOR', $DB_DIR.'/global_time_factor');
		global_setting('DB_GLOBAL_PROD_FACTOR', $DB_DIR.'/global_prod_factor');
		global_setting('DB_GLOBAL_COST_FACTOR', $DB_DIR.'/global_cost_factor');
		global_setting('DB_USE_OLD_INGTECH', $DB_DIR.'/use_old_ingtech');
		global_setting('DB_USE_OLD_ROBTECH', $DB_DIR.'/use_old_robtech');
		global_setting('DB_NO_ATTS', $DB_DIR.'/no_atts');
		global_setting("DB_SQLITE", $DB_DIR."/sqlite");

		return true;
	}

	function __autoload($class)
	{
		if(strtolower($class) == 'items') $class = 'Item';
		$filename = s_root.'/engine/classes/'.strtolower($class).'.php';
		if(is_file($filename) && is_readable($filename)) require_once($filename);
	}

	__autoload('Classes');

	//if(isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false)
	//	define('CONTENT_TYPE', 'application/xhtml+xml; charset=UTF-8');
	//else
		define('CONTENT_TYPE', 'text/html; charset=UTF-8');
	if(isset($_SERVER["HTTP_HOST"]))
		header('Content-type: '.CONTENT_TYPE);

	if(!isset($USE_OB) || $USE_OB)
		ob_start('ob_gzhandler');
	$tabindex = 1;

	if(!isset($LOGIN) || !$LOGIN)
		check_hostname();

	if(!isset($_SESSION))
		$GLOBALS['_SESSION'] = array();

	define("MESSAGE_KAEMPFE", 1);
	define("MESSAGE_SPIONAGE", 2);
	define("MESSAGE_TRANSPORT", 3);
	define("MESSAGE_SAMMELN", 4);
	define("MESSAGE_BESIEDELUNG", 5);
	define("MESSAGE_BENUTZERNACHRICHTEN", 6);
	define("MESSAGE_VERBUENDETE", 7);
	define("MESSAGE_POSTAUSGANG", 8);

	define("FLEET_BESIEDELN", 1);
	define("FLEET_SAMMELN", 2);
	define("FLEET_ANGRIFF", 3);
	define("FLEET_TRANSPORT", 4);
	define("FLEET_SPIONIEREN", 5);
	define("FLEET_STATIONIEREN", 6);

	# Maximales Alter in Tagen der Nachrichtensorten
	$message_type_times = array (
		MESSAGE_KAEMPFE => 3,
		MESSAGE_SPIONAGE => 3,
		MESSAGE_TRANSPORT => 2,
		MESSAGE_SAMMELN => 2,
		MESSAGE_BESIEDELUNG => 1,
		MESSAGE_BENUTZERNACHRICHTEN => 5,
		MESSAGE_VERBUENDETE => 4,
		MESSAGE_POSTAUSGANG => 2
	);

	# Fuer veroeffentlichte Nachrichten
	$public_messages_time = 30;

	# Zu jeder Flottenauftragsart die zugehoerige Nachrichtensorte
	$types_message_types = array (
		FLEET_BESIEDELN => MESSAGE_BESIEDELUNG,
		FLEET_SAMMELN => MESSAGE_SAMMELN,
		FLEET_ANGRIFF => MESSAGE_KAEMPFE,
		FLEET_TRANSPORT => MESSAGE_TRANSPORT,
		FLEET_SPIONIEREN => MESSAGE_SPIONAGE,
		FLEET_STATIONIEREN => MESSAGE_TRANSPORT
	);

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


	/**
	  * Statische Funktionen zum Auslesen und Bearbeiten von Truemmerfeldern.
	*/

	class truemmerfeld
	{
		/**
		  * Liest das Truemmerfeld an den gegebenen Koordinaten aus.
		  * @return Array mit den Rohstoffen
		*/

		static function get($galaxy, $system, $planet)
		{
			# Bekommt die Groesse eines Truemmerfelds

			if(!is_file(global_setting("DB_TRUEMMERFELDER").'/'.$galaxy.'_'.$system.'_'.$planet))
				return array(0, 0, 0, 0);
			elseif(!is_readable(global_setting("DB_TRUEMMERFELDER").'/'.$galaxy.'_'.$system.'_'.$planet))
				return false;
			else
			{
				$string = file_get_contents(global_setting("DB_TRUEMMERFELDER").'/'.$galaxy.'_'.$system.'_'.$planet);

				$rohstoffe = array('', '', '', '');

				$index = 0;
				for($i = 0; $i < strlen($string); $i++)
				{
					$bin = add_nulls(decbin(ord($string[$i])), 8);
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

		/**
		  * Fuegt dem Truemmerfeld Rohstoffe hinzu.
		*/

		static function add($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
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

		/**
		  * Zieht dem Truemmerfeld Rohstoffe ab.
		*/

		static function sub($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
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

		/**
		  * Setzt die Rohstoffe des Truemmerfelds neu.
		*/

		static function set($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			if($carbon <= 0 && $aluminium <= 0 && $wolfram <= 0 && $radium <= 0)
			{
				if(is_file(global_setting("DB_TRUEMMERFELDER").'/'.$galaxy.'_'.$system.'_'.$planet))
					return unlink(global_setting("DB_TRUEMMERFELDER").'/'.$galaxy.'_'.$system.'_'.$planet);
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
			$fh = fopen(global_setting("DB_TRUEMMERFELDER").'/'.$galaxy.'_'.$system.'_'.$planet, 'w');
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
	### Hier beginnen die Funktionen
	########################################

	/**
	  * Sucht nach installierten Skins und liefert ein Array des folgenden
	  * Formats zurueck:
	  * ( ID => [ Name, ( Einstellungsname => ( moeglicher Wert ) ) ] )
	*/

	function get_skins()
	{
		# Vorgegebene Skins-Liste bekommen
		$skins = array();
		if(is_dir(s_root.'/login/style') && is_readable(s_root.'/login/style'))
		{
			$dh = opendir(s_root.'/login/style');
			while(($fname = readdir($dh)) !== false)
			{
				if($fname[0] == '.') continue;
				$path = s_root.'/login/style/'.$fname;
				if(!is_dir($path) || !is_readable($path)) continue;
				if(!is_file($path.'/types') || !is_readable($path.'/types')) continue;
				$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents($path.'/types'));
				$new_skin = &$skins[$fname];
				$new_skin = array(array_shift($skins_file), array());
				foreach($skins_file as $skins_line)
				{
					$skins_line = explode("\t", $skins_line);
					if(count($skins_line) < 2)
						continue;
					$new_skin[1][array_shift($skins_line)] = $skins_line;
				}
				unset($new_skin);
			}
			closedir($dh);
		}
		return $skins;
	}

	/**
	  * Liefert die Spielversion zurueck.
	*/

	function get_version()
	{
		$version = '';
		if(is_file(global_setting("DB_VERSION")) && is_readable(global_setting("DB_VERSION")))
			$version = trim(file_get_contents(global_setting("DB_VERSION")));
		return $version;
	}

	/**
	  * Liefert die aktuell geladene SVN-Revision des Spielverzeichnisses zurueck.
	  * @return false, wenn keine Revision ermittelt werden kann
	*/

	function get_revision()
	{
		# Aktuell laufende Revision herausfinden

		if(!is_dir(s_root.'/.svn')) return false;

		$revision_file = global_setting("DB_REVISION");
		$entries_file = s_root.'/.svn/entries';

		if(!is_file($revision_file) && !is_file($entries_file)) return false;

		if(is_file($entries_file))
		{
			if(!is_file($revision_file) || filemtime($entries_file) > filemtime($revision_file))
			{
				# Update revision file
				if(!function_exists('simplexml_load_file')) return false;
				$entries_xml = new DomDocument;
				@$entries_xml->loadXML(file_get_contents($entries_file), LIBXML_NSCLEAN);
				if(!$entries_xml) return false;

				$new_revision = false;
				foreach($entries_xml->getElementsByTagName('entry') as $e)
				{
					if($e->hasAttribute('name') && $e->getAttribute('name') == '' && $e->hasAttribute('revision'))
					{
						$new_revision = $e->getAttribute('revision');
						break;
					}
				}
				if($new_revision === false) return false;

				file_put_contents($revision_file, $new_revision, LOCK_EX);
			}
		}

		return floor(file_get_contents($revision_file));
	}

	/**
	  * Liest die Liste der Datenbanken aus und liefert diese in einem Array zurueck:
	  * ID => ( 'directory' => Datenbankverzeichnis; 'name' => Anzeigename der Datenbank; 'enabled' => fuer Benutzer sichtbar?; 'hostname' => Hostname, unter dem die Datenbank laeuft; 'dummy' => Ist dieser Eintrag nur ein Alias? )
	*/

	function get_databases($force_reload=false, &$aliases=null)
	{
		# Liste der Runden/Universen herausfinden

		static $databases;
		static $aliases_cache;

		if(!isset($databases) || $force_reload)
		{
			if(!is_file(global_setting("DB_DATABASES")) || !is_readable(global_setting("DB_DATABASES")))
				return false;

			$databases_raw = parse_ini_file(global_setting("DB_DATABASES"), true);

			$aliases_cache = array();
			$databases = array();

			foreach($databases_raw as $i=>$database)
			{
				if(!isset($database['directory'])) continue;

				$databases[$i] = array (
					'directory' => $database['directory'],
					'name' => (isset($database['name']) && strlen($database['name'] = trim($database['name'])) > 0) ? $database['name'] : $i,
					'enabled' => (!isset($database['enabled']) || $database['enabled']),
					'hostname' => (isset($database['hostname']) && strlen($database['hostname'] = trim($database['hostname'])) > 0) ? $database['hostname'] : get_default_hostname(),
					'dummy' => false
				);

				if(isset($database['aliases']) && strlen($database['aliases'] = trim($database['aliases'])) > 0)
				{
					foreach(preg_split("/\s+/", $database['aliases']) as $alias)
					{
						if(!isset($aliases_cache[$alias]))
						{
							$aliases_cache[$alias] = $i;
							$databases[$alias] = $databases[$i];
							$databases[$alias]['dummy'] = true;
						}
					}
				}
			}
		}

		$aliases = $aliases_cache;
		return $databases;
	}

	/**
	  * Liefert den Hostname zurueck, auf dem die Hauptseite laeuft.
	  * @return (string)
	  * @return null bei Fehlschlag
	*/

	function get_default_hostname()
	{
		if(is_file(global_setting("DB_HOSTNAME")) && !is_readable(global_setting("DB_HOSTNAME")) && strlen($hostname = trim(file_get_contents(global_setting("DB_HOSTNAME")))) > 0) return $hostname;
		elseif(isset($_SERVER["HTTP_HOST"])) return $_SERVER["HTTP_HOST"];
		else return null;
	}

	/**
	  * Ueberprueft, ob der richtige Hostname aufgerufen wurde und leitet sonst um.
	*/

	function check_hostname()
	{
		if(isset($_SERVER['HTTP_HOST']))
		{
			$hostname = $_SERVER['HTTP_HOST'];
			$real_hostname = get_default_hostname();
			if(isset($_SESSION['database']))
			{
				$databases = get_databases();
				if(isset($databases[$_SESSION['database']]) && $databases[$_SESSION['database']]['hostname'])
					$real_hostname = $databases[$_SESSION['database']]['hostname'];
			}

			if($real_hostname)
			{
				$request_uri = $_SERVER['REQUEST_URI'];
				if(strpos($request_uri, '?') !== false)
					$request_uri = substr($request_uri, 0, strpos($request_uri, '?'));

				if(strtolower($hostname) == strtolower($real_hostname) && substr($request_uri, -1) != '/')
					return true;

				$url = global_setting("PROTOCOL").'://'.$real_hostname.$_SERVER['PHP_SELF'];
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
	}

	/**
	  * Gibt ein Array aller Administratoren zurueck:
	  * Benutzername => ( 'password' => md5(Passwort), 'permissions' => ( Nummer => Erlaubnis? ) )
	  * @return (array)
	  * @return false bei Fehlschlag
	*/

	function get_admin_list()
	{
		$admins = array();
		if(!is_file(global_setting("DB_ADMINS")) || !is_readable(global_setting("DB_ADMINS")))
			return false;
		$admin_file = preg_split("/\r\n|\r|\n/", file_get_contents(global_setting("DB_ADMINS")));
		foreach($admin_file as $line)
		{
			$line = explode("\t", $line);
			if(count($line) < 2)
				continue;

			$this_admin = &$admins[urldecode(array_shift($line))];
			$this_admin = array();
			$this_admin['password'] = array_shift($line);
			$this_admin['permissions'] = $line;

			unset($this);
		}

		return $admins;
	}

	/**
	  * Speichert eine mit get_admin_list() geholte Liste wieder ab.
	  * @return (boolean)
	*/

	function write_admin_list($admins)
	{
		$admin_file = array();
		foreach($admins as $name=>$settings)
		{
			$this_admin = &$admin_file[];
			$this_admin = $name;
			$this_admin .= "\t".$settings['password'];
			if(count($settings['permissions']) > 0)
				$this_admin .= "\t".implode("\t", $settings['permissions']);
			unset($this_admin);
		}

		$fh = fopen(global_setting("DB_ADMINS"), 'w');
		if(!$fh)
			return false;
		flock($fh, LOCK_EX);

		fwrite($fh, implode("\n", $admin_file));

		flock($fh, LOCK_UN);
		fclose($fh);

		return true;
	}

	########################################

	/**
	  * Formatiert eine Bauzeitangabe zu einem menschlich lesbaren Format.
	  * Beispiel: 650 wird zu 10 Minuten, 50 Sekunden
	  * @param $time2 Bauzeit in Sekunden
	  * @param $short Sollen Werte, die 0 sind, weggelassen werden (Beispiel: 5 Minuten, 0 Sekunden)
	  * @return (string)
	*/

	function format_btime($time2, $short=false)
	{
		# Formatiert eine in Punkten angegebene Bauzeitangabe,
		# sodass diese auf den Seiten angezeigt werden kann
		# (zum Beispiel 2 Stunden, 5 Minuten und 30 Sekunden)

		$time = round($time2);
		$days = $hours = $minutes = $seconds = 0;

		if($time >= 86400)
		{
			$mod = $time%86400;
			$days = ($time-$mod)/86400;
			$time = $mod;
		}
		if($time >= 3600)
		{
			$mod = $time%3600;
			$hours = ($time-$mod)/3600;
			$time = $mod;
		}
		if($time >= 60)
		{
			$mod = $time%60;
			$minutes = ($time-$mod)/60;
			$time = $mod;
		}
		$seconds = $time;

		$return = array();
		if($time2 >= 86400 && (!$short || $days != 0))
			$return[] = sprintf(ngettext("%d Tag", "%d Tage", $days), $days);
		if($time2 >= 3600 && (!$short || $hours != 0))
			$return[] = sprintf(ngettext("%d Stunde", "%d Stunden", $hours), $hours);
		if($time2 >= 60 && (!$short || $minutes != 0))
			$return[] = sprintf(ngettext("%d Minute", "%d Minuten", $minutes), $minutes);
		if(!$short || $seconds != 0)
			$return[] = sprintf(ngettext("%d Sekunde", "%d Sekunden", $seconds), $seconds);

		$return = h(implode(' ', $return));
		return $return;
	}

	/**
	  * Formatiert die angegeben Rohstoffmenge zu einem menschlich lesbaren Format.
	  * @param $ress Ein Array mit den Rohstoffmengen als Werte
	  * @param $tabs_count Die Anzahl der einzurueckenden Tabs des HTML-Codes
	  * @param $tritium Soll der Array-Wert 4 beachtet werden (Tritium)
	  * @param $energy Soll der Array-Wert 5 beachtet werden (Energie)
	  * @param $i_ Die Ausgabe wird so formatiert, dass sie nachtraeglich durch i_() gejagt werden kann
	  * @return (string) Eine den HTML-Code einer dl-Liste mit den formatierten Rohstoffangaben
	*/

	function format_ress($ress, $tabs_count=0, $tritium=false, $energy=false, $i_=false)
	{
		# Erstellt eine Definitionsliste aus der uebergebenen
		# Rohstoffanzahl, beispielsweise fuer die Rohstoffkosten
		# der Gebaeude verwendbar

		$tabs = '';
		if($tabs_count >= 1)
			$tabs = str_repeat("\t", $tabs_count);

		$return = "<dl class=\"ress\">\n";
		$return .= $tabs."\t<dt class=\"ress-carbon\">".($i_ ? "[ress_0]" : h(_("[ress_0]")))."</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-carbon\">".ths($ress[0])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-aluminium\">".($i_ ? "[ress_1]" : h(_("[ress_1]")))."</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-aluminium\">".ths($ress[1])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-wolfram\">".($i_ ? "[ress_2]" : h(_("[ress_2]")))."</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-wolfram\">".ths($ress[2])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-radium".($tritium ? "" : " ress-last")."\">".($i_ ? "[ress_3]" : h(_("[ress_3]")))."</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-radium".($tritium ? "" : " ress-last")."\">".ths($ress[3])."</dd>\n";
		if($tritium)
		{
			$return .= $tabs."\t<dt class=\"ress-tritium".($energy ? "" : " ress-last")."\">".($i_ ? "[ress_4]" : h(_("[ress_4]")))."</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-tritium".($energy ? "" : " ress-last")."\">".ths($ress[4])."</dd>\n";
		}
		if($energy)
		{
			$return .= $tabs."\t<dt class=\"ress-energie ress-last\">".($i_ ? "[ress_5]" : h(_("[ress_5]")))."</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-energie ress-last\">".h(_($ress[5]))."</dd>\n";
		}
		$return .= $tabs."</dl>\n";
		return $return;
	}

	/**
	  * Formatiert eine Zahl in ein lesbares Format.
	  * @param $count Die zu formatierende Zahl
	  * @param $utf8 Ohne Auswirkungen, Kompatiblitaetsparameter
	  * @param $round Anzahl der zu rundenden Stellen, standardmaessig 0
	*/

	function ths($count, $utf8=null, $round=0)
	{
		# Fuegt Tausendertrennzeichen ein
		# (Oben als THS_UTF8 und THS_HTML definiert)

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

		$count = str_replace('.', _("[thousand_separator]"), number_format($count, $round, ',', '.'));

		if($neg)
			$count = _("[minus_sign]").$count;

		return $count;
	}

	/**
	  * Funktioniert wie htmlentities(), jedoch mit einem UTF-8-String.
	  * @param $nospecialchars <, >, & und " werden nicht ersetzt
	  * @param $js Sollen keine HTML-Entities, sondern JavaScript-Entities (\uXXXX) erzeugt werden?
	  * @deprecated
	*/

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

	/**
	  * Ersetzt alle Nicht-ASCII-Zeichen durch JavaScript-Entities (\uXXXX).
	  * @deprecated
	*/

	function utf8_jsentities($string)
	{
		return utf8_htmlentities($string, true, true);
	}

	/**
	  * Escapt alle ' und \ mit einem Backslash, sodass der String in JavaScript innerhalb von einfachen Anfuehrungszeichen verwendet werden kann.
	*/

	function jsentities($string)
	{
		return preg_replace("/['\\\\]/", "\\\\\$1", $string);
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

	/**
	  * Fuegt soviele Nullen vorne an $count, dass diese mindestens $len Stellen hat.
	*/

	function add_nulls($count, $len)
	{
		while(strlen($count) < $len)
			$count = '0'.$count;

		return $count;
	}

	/**
	  * Liefert einen String aus Einsen und Nullen zurueck, in dem jeweils 8 Ziffern den ASCII-Code eines Zeichens darstellen.
	  * @deprecated
	*/

	function string2bin($string)
	{
		$return = '';

		$len = strlen($string);
		for($i = 0; $i < $len; $i++)
			$return .= add_nulls(decbin(ord($string[$i])), 8);

		return $return;
	}

	/**
	  * Konvertiert einen string2bin()-String zurueck zu einem String.
	  * @deprecated
	*/

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

	/**
	  * Liefert die Differenz zwischen $ao und $bo zurueck (immer positiv).
	*/

	function diff($ao, $bo)
	{
		return abs($ao-$bo);
	}

	/**
	  * Callback-Funktion fuer usort() zum Sortieren von Koordinaten nach Galaxie, System und Planet.
	  * @return 1 wenn $a > $b
	  * @return -1 wenn $a < $b
	  * @return 0 wenn $a == $b
	*/

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

	/**
	  * Entfernt ungueltiges HTML aus dem uebergebenen Code.
	  * Auf diese Weise kann sichergestellt werden, dass zum Beispiel in der Benutzerbeschreibung nur sauberes HTML ausgegeben wird.
	  * Ungueltige Elemente werden entfernt.
	*/

	function parse_html($string)
	{
		$root = parse_html_get_element_information('div');

		$remaining_string = str_replace("\t", " ", preg_replace("/\r\n|\r|\n/", "\n", $string));
		$string = '';
		$open_elements = array();
		while(($next_bracket = strpos($remaining_string, '<')) !== false)
		{
			if($next_bracket != 0)
			{
				$string .= htmlspecialchars(substr($remaining_string, 0, $next_bracket));
				$remaining_string = substr($remaining_string, $next_bracket);
			}

			if(substr($remaining_string, 1, 1) == '/')
			{
				if(!preg_match('/^<\\/([a-z]+) *>/', $remaining_string, $match) || count($open_elements) <= 0 || $open_elements[count($open_elements)-1] != strtolower($match[1]))
				{
					$string .= '&lt;';
					$remaining_string = substr($remaining_string, 1);
				}
				else
				{
					$string .= '</'.strtolower($match[1]).'>';
					$remaining_string = substr($remaining_string, strlen($match[0]));
					array_pop($open_elements);
				}
				continue;
			}

			if(!preg_match('/^<([a-z]+)( |>)/i', $remaining_string, $match) || ($close_bracket = strpos($remaining_string, '>')) === false)
			{
				$string .= '&lt;';
				$remaining_string = substr($remaining_string, 1);
				continue;
			}

			$element_name = strtolower($match[1]);
			$info = parse_html_get_element_information($element_name);
			if(!$info)
			{
				$string .= '&lt;';
				$remaining_string = substr($remaining_string, 1);
				continue;
			}
			if(count($open_elements))
				$parent_info = parse_html_get_element_information($open_elements[count($open_elements)-1]);
			else
				$parent_info = $root;

			if(!in_array($element_name, $parent_info[0]))
			{
				$string .= '&lt;';
				$remaining_string = substr($remaining_string, 1);
				continue;
			}

			$part = substr($remaining_string, 0, $close_bracket);
			$part = ' '.substr($part, strlen($element_name)+2);

			if($part != ' ' && !preg_match('/^( +(xml:)?[a-z]+="[^"]*")*( *\\/)?$/i', $part))
			{
				$string .= '&lt;';
				$remaining_string = substr($remaining_string, 1);
				continue;
			}

			$closed = (substr($part, -1) == '/');
			if($closed)
				$part = substr($part, 0, -1);
			else
				$open_elements[] = $element_name;

			preg_match_all('/ +([a-z:]+)="([^"]*)"/i', $part, $attrs, PREG_SET_ORDER);
			$attrs2 = array();
			foreach($attrs as $attr)
			{
				if(!isset($info[1][strtolower($attr[1])]))
					continue;
				$attrs2[] = strtolower($attr[1]).'="'.$attr[2].'"';
				unset($info[1][strtolower($attr[1])]);
			}

			if(in_array(true, $info[1]))
			{
				$string .= '&lt;';
				$remaining_string = substr($remaining_string, 1);
				continue;
			}

			array_unshift($attrs2, '<'.$element_name);
			$string .= implode(' ', $attrs2);
			if($closed)
				$string .= ' />';
			else
				$string .= '>';

			$remaining_string = substr($remaining_string, $close_bracket+1);
		}

		$string .= htmlspecialchars($remaining_string);

		$open_elements = array_reverse($open_elements);
		foreach($open_elements as $el)
			$string .= '</'.$el.'>';

		# Zeilenumbruchstruktur aufbauen
		$string = preg_replace("/> *(\r\n|\r|\n) *</", "><", $string);

		$remaining_string = $string;
		$string = '';
		$open_elements = array();
		$p_open = false;
		$span = parse_html_get_element_information('span');
		while(($next_bracket = strpos($remaining_string, '<')) !== false)
		{
			if($next_bracket != 0)
			{
				$part = substr($remaining_string, 0, $next_bracket);
				if(count($open_elements))
					$parent_info = parse_html_get_element_information($open_elements[count($open_elements)-1]);
				else
					$parent_info = $root;
				if(parse_html_trim($part) != '' && in_array('span', $parent_info[0]))
				{
					if(!$p_open && count($open_elements) <= 0)
					{
						$string .= '<p>';
						$p_open = true;
					}
					if(in_array('br', $parent_info[0]))
					{
						if(count($open_elements) <= 0)
						{
							if(substr($part, -1) == "\n")
								$string .= preg_replace('/[\n]+/e', 'parse_html_repl_nl(strlen(\'$0\'))', substr($part, 0, -1));
							else
								$string .= preg_replace('/[\n]+/e', 'parse_html_repl_nl(strlen(\'$0\'))', $part);
						}
						else
						{
							if(substr($part, -1) == "\n")
								$string .= str_replace("\n", "<br />", substr($part, 0, -1));
							else
								$string .= str_replace("\n", "<br />", $part);
						}
					}
					else
						$string .= str_replace("\n", '', $part);
				}
				$remaining_string = substr($remaining_string, $next_bracket);
			}
			$close_bracket = strpos($remaining_string, '>');
			if(substr($remaining_string, 1, 1) == '/')
			{
				preg_match('/^<\\/([a-z]+) *>/', $remaining_string, $match);
				if(count($open_elements) > 0 && $open_elements[count($open_elements)-1] == $match[1])
					array_pop($open_elements);
			}
			elseif(preg_match('/^<([a-z]+)( |>)/', $remaining_string, $match))
			{
				if($p_open && !in_array($match[1], $span[0]))
				{
					$string .= "</p>\n";
					$p_open = false;
				}
				if(substr($remaining_string, $close_bracket-1, 1) != '/')
					$open_elements[] = $match[1];
			}

			$string .= substr($remaining_string, 0, $close_bracket+1);
			$remaining_string = substr($remaining_string, $close_bracket+1);
		}

		if(strlen($remaining_string) > 0 && trim($remaining_string) != '')
		{
			if(!$p_open)
			{
				$string .= '<p>';
				$p_open = true;
			}
			$string .= preg_replace('/[\n]+/e', 'parse_html_repl_nl(strlen(\'$0\'))', $remaining_string);
		}
		if($p_open)
			$string .= '</p>';

		$string = preg_replace('/&amp;(#[0-9]{1,6};)/', '&$1', $string);
		$string = preg_replace('/&amp;(#x[0-9a-fA-F]{1,4};)/', '&$1', $string);
		$string = preg_replace('/&amp;([a-zA-Z0-9]{2,8};)/', '&$1', $string);

		$string = str_replace("\n<p></p>", '<br /><br />', $string);

		$string = utf8_htmlentities($string, true);

		return $string;
	}

	/**
	  * Hilfsfunktion fuer parse_html(). Liefert ein Array mit Informationen zu einem HTML-Element zurueck:
	  * ( ( Erlaubtes Kind-Element ); ( Erlaubtes Attribut => Attribut erforderlich? ) )
	*/

	function parse_html_get_element_information($element)
	{
		$elements = array(
			'div' => array('br div span table h4 h5 h6 a img em strong var code abbr acronym address blockquote cite dl dfn hr bdo ins kbd ul ol q samp var p', 'class title xml:lang dir datafld datasrc dataformates'),
			'span' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir datafld datasrc dataformates'),
			'table' => array('thead tbody tfoot', 'class title xml:lang dir summary'),
			'thead' => array('tr', 'class title xml:lang dir'),
			'tbody' => array('tr', 'class title xml:lang dir'),
			'tfoot' => array('tr', 'class title xml:lang dir'),
			'tr' => array('th td', 'class title xml:lang dir'),
			'td' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir abbr colspan rowspan'),
			'th' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir abbr colspan rowspan'),
			'caption' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'h4' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'h5' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'h6' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'a' => array('span img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir !href hreflang rel rev'),
			'img' => array('', 'class title xml:lang dir !src !alt longdesc'),
			'em' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'strong' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'var' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'code' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'abbr' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'acronym' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'address' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'blockquote' => array('div span table h4 h5 h6 a img em strong var code abbr acronym address blockquote cite dl dfn hr bdo ins kbd ul ol q samp var p', 'class title xml:lang dir cite'),
			'cite' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'dl' => array('dt dd', 'class title xml:lang dir'),
			'dt' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'dd' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'dfn' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'hr' => array('', 'class title xml:lang'),
			'bdo' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'ins' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir cite datetime'),
			'kbd' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'ul' => array('li', 'class title xml:lang dir'),
			'ol' => array('li', 'class title xml:lang dir'),
			'li' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'q' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir cite'),
			'samp' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'var' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
			'p' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir datafld datasrc dataformates')
		);

		if(!isset($elements[$element]))
			return false;

		$return = array(explode(' ', $elements[$element][0]), array());
		$el_attrs = explode(' ', $elements[$element][1]);
		foreach($el_attrs as $el_attr)
		{
			if(substr($el_attr, 0, 1) == '!')
				$return[1][substr($el_attr, 1)] = true;
			else
				$return[1][$el_attr] = false;
		}
		return $return;
	}

	/**
	  * Hilfsfunktion fuer parse_html(). Ersetzt Zeilenumbrueche je nach Anzahl durch HTML-Absaetze oder -Zeilenumbrueche.
	*/

	function parse_html_nls($string, $minus1)
	{
		$string2 = $string;
		$string = preg_replace('/[\n]+/e', 'repl_nl(strlen(\'$0\')-$minus1);', utf8_htmlentities($player_info['description']));
		return $string;
	}

	/**
	  * Hilfsfunktion fuer parse_html_nls(). Ersetzt einen String aus Zeilenumbruechen je nach deren Anzahl durch &lt;br /&gt; oder &lt;/p&gt;(&lt;br /&gt;)*&lt;p&gt;.
	*/

	function parse_html_repl_nl($len)
	{
		if($len == 1)
			return "<br />";
		elseif($len == 2)
			return "</p>\n<p>";
		elseif($len > 2)
			return "</p>\n".str_repeat('<br />', $len-2)."\n<p>";
	}

	/**
	  * Hilfsfunktion fuer parse_html(). Wie trim(), entfernt jedoch nur Leerzeichen.
	*/

	function parse_html_trim($string)
	{
		while(strlen($string) > 0 && $string[0] === ' ')
			$string = substr($string, 1);
		while(substr($string, -1) === ' ')
			$string = substr($string, 0, -1);
		return $string;
	}

	/**
	  * Hilfsfunktion zum Parsen von Nachrichten. Ersetzt einen String aus Zeilenumbruechen je nach deren Anzahl durch &lt;br /&gt; oder &lt;/p&gt;(&lt;br /&gt;)*&lt;p&gt;.
	*/

	function message_repl_nl($nls)
	{
		$len = strlen($nls);
		if($len == 1)
			return "<br />\n\t";
		elseif($len == 2)
			return "\n</p>\n<p>\n\t";
		elseif($len > 2)
			return "\n</p>\n".str_repeat('<br />', $len-2)."\n<p>\n\t";
	}

	/**
	  * Hilfsfunktion zum Ersetzen von Links beim Parsen von Nachrichten. Haengt bei Bedarf einen Parameter mit der Session-ID an die URL in $b an.
	  * @param $a Praefix, zum Beispiel &lt;a href=&quot;
	  * @param $b Die URL, die ersetzt werden soll
	  * @param $c Suffix, zum Beispiel &quot;>
	  * @return (string) $a.$b.$c
	*/

	function message_repl_links($a, $b, $c)
	{
		if(!session_id())
			return $a.$b.$c;

		$url2 = html_entity_decode($b);
		if(substr($url2, 0, 7) != 'http://')
		{
			$url3 = explode('#', $url2);
			$url3[0] = explode('?', $url3[0]);
			$url = array($url3[0][0]);
			if(isset($url3[0][1]))
				$url[1] = $url3[0][1];
			else
				$url[1] = '';
			if(isset($url3[1]))
				$url[2] = $url[1];
			else
				$url[2] = '';

			if($url[1] != '')
				$url[1] .= '&';
			$url[1] .= session_name().'='.urlencode(session_id());

			$url2 = $url[0].'?'.$url[1];
			if($url[2] != '')
				$url2 .= '#'.$url[2];
		}

		return $a.htmlentities($url2).$c;
	}

	/**
	  * Rundet $a (call by reference) auf $d Stellen nach dem Komma. $d kann auch negativ sein.
	  * @return $a
	*/

	function stdround(&$a, $d=0)
	{
		$f = pow(10, $d);
		$a *= $f;
		$i = floor($a+.5);
		$a = $i/$f;
		return $a;
	}

	/**
	  * Wrapper fuer flock(), jedoch mit einem Timeout (1 Sekunde fuer LOCK_SH, sonst 5).
	  * @return (booolean) War das Sperren erfolreich?
	*/

	function fancy_flock($file, $lock_flag)
	{
		if($lock_flag == LOCK_SH) $timeout = 1;
		else $timeout = 5;

		$flag = $lock_flag|LOCK_NB;

		$steps = $timeout*10000;
		for($i=0; $i<100; $i++)
		{
			if(flock($file, $flag)) return true;
			usleep($steps);
		}
		return false;
	}

	/**
	  * Verkleinert das Rohstoffarray $array gleichmaessig so, dass dessen Summe den Wert $max nicht uebersteigt.
	*/

	function fit_to_max($array, $max)
	{
		if(!is_array($array) || $max < 0) return false;

		$sum = 0;
		foreach($array as $k=>$v)
		{
			if($v<0) $array[$k] = 0;
			else $sum += $v;
		}

		if($sum > $max)
		{
			$f = $max/$sum;
			$sum = 0;
			global $_fit_to_max_usort;
			$_fit_to_max_usort = array();
			foreach($array as $k=>$v)
			{
				$new_c = $v*$f;
				$fl = ceil($new_c)-$new_c;
				if($fl > 0) $_fit_to_max_usort[$k] = $fl;
				$array[$k] = floor($new_c);
				$sum += $array[$k];
			}

			$remaining = $max-$sum;
			uksort($_fit_to_max_usort, "_fit_to_max_usort");
			while($remaining > 0 && count($_fit_to_max_usort) > 0)
			{
				foreach($_fit_to_max_usort as $k=>$v)
				{
					if($v <= 0) continue;
					$array[$k]++;
					if(--$remaining <= 0) break 2;
				}
			}
		}
		return $array;
	}

	/**
	  * Hilfsfunktion fuer fit_to_max().
	*/

	function _fit_to_max_usort($a, $b)
	{
		global $_fit_to_max_usort;

		if($_fit_to_max_usort[$a] > $_fit_to_max_usort[$b]) return -1;
		elseif($_fit_to_max_usort[$a] < $_fit_to_max_usort[$b]) return 1;
		elseif($a > $b) return 1;
		elseif($a < $b) return -1;
		else return 0;
	}

	/**
	  * Parst die Messenger-Konfigurationsdatei und schmeisst ungueltige Eintraege hinaus.
	  * @param $type Liefert nur die Konfiguration zum gegebenen Protokoll zurueck
	  * @param $force_reload Soll die Konfigurationsdatei unbedingt neu eingelesen werden?
	*/

	function get_messenger_info($type=false, $force_reload=false)
	{
		global $messengers_parsed_file;

		if(!isset($messenger_parsed_file) || $force_reload)
		{
			if(!is_file(global_setting("DB_MESSENGERS")) || !is_readable(global_setting("DB_MESSENGERS"))) $messenger_parsed_file = false;
			else
			{
				$messenger_parsed_file = parse_ini_file(global_setting("DB_MESSENGERS"), true);
				foreach($messenger_parsed_file as $k=>$v)
				{
					if(!is_array($v) || !isset($v['server']) || !isset($v['username']) || !isset($v['server']))
						unset($messenger_parsed_file[$k]);
				}
			}
		}

		if(!$messenger_parsed_file) return false;

		if($type)
		{
			if(!isset($messenger_parsed_file[$type])) return false;
			return $messenger_parsed_file[$type];
		}
		else return $messenger_parsed_file;
	}

	/**
	  * Liefert ein Array der eingestellten globalen Faktoren der Datenbank zurueck:
	  * ( 'time' => Zeit; 'prod' => Produktion; 'cost' => Kosten )
	  * @param $force_reload Sollen die Konfigurationsdateien unbedingt neu eingelesen werden?
	*/

	function get_global_factors($force_reload=false)
	{
		static $factors;

		if(!isset($factors) || $force_reload)
		{
			$factors = array('time' => 1, 'prod' => 1, 'cost' => 1);
			if(is_file(global_setting('DB_GLOBAL_TIME_FACTOR')) && is_readable(global_setting('DB_GLOBAL_TIME_FACTOR')))
			{
				$content = str_replace(',', '.', trim(file_get_contents(global_setting('DB_GLOBAL_TIME_FACTOR'))));
				if(strlen($content) > 0 && preg_match("/^[0-9]*(\.[0-9]+)?$/", $content))
					$factors['time'] = $content;
			}
			if(is_file(global_setting('DB_GLOBAL_PROD_FACTOR')) && is_readable(global_setting('DB_GLOBAL_PROD_FACTOR')))
			{
				$content = str_replace(',', '.', trim(file_get_contents(global_setting('DB_GLOBAL_PROD_FACTOR'))));
				if(strlen($content) > 0 && preg_match("/^[0-9]*(\.[0-9]+)?$/", $content))
					$factors['prod'] = $content;
			}
			if(is_file(global_setting('DB_GLOBAL_COST_FACTOR')) && is_readable(global_setting('DB_GLOBAL_COST_FACTOR')))
			{
				$content = str_replace(',', '.', trim(file_get_contents(global_setting('DB_GLOBAL_COST_FACTOR'))));
				if(strlen($content) > 0 && preg_match("/^[0-9]*(\.[0-9]+)?$/", $content))
					$factors['cost'] = $content;
			}
		}

		return $factors;
	}

	/**
	  * Gibt zurueck, ob eine Handlungssperre in der Datenbank vorliegt.
	*/

	function database_locked()
	{
		if(!file_exists(global_setting("DB_LOCKED"))) return false;

		if(!is_readable(global_setting("DB_LOCKED"))) return true;

		$until = trim(file_get_contents(global_setting("DB_LOCKED")));
		if($until && time() > $until)
		{
			unlink(global_setting("DB_LOCKED"));
			return false;
		}
		return ($until ? $until : true);
	}

	/**
	  * Gibt zurueck, ob eine Flottensperre in der Datenbank vorliegt.
	*/

	function fleets_locked()
	{
		if(!file_exists(global_setting("DB_NO_ATTS"))) return false;

		if(!is_readable(global_setting("DB_NO_ATTS"))) return true;

		$until = trim(file_get_contents(global_setting("DB_NO_ATTS")));
		if($until && time() > $until)
		{
			unlink(global_setting("DB_NO_ATTS"));
			return false;
		}
		return ($until ? $until : true);
	}

	/**
	  * Fuegt an der Zeigerposition im Dateizeiger $fh den String $string ein. Der nachfolgende Inhalt wird nach hinten verschoben.
	  * @param $bs Groesse der Bloecke, in denen der Inhalt verschoben wird, in Bytes.
	  * @return (boolean) Erfolg
	*/

	function finsert($fh, $string, $bs=1024)
	{
		if($bs <= 0) return false;
		$pos = ftell($fh);
		$len = strlen($string);

		fseek($fh, 0, SEEK_END);

		$do_break = false;
		while(!$do_break)
		{
			$bytes = $bs;
			if(ftell($fh)-$bytes < $pos)
			{
				$bytes -= $pos-ftell($fh)+$bytes;
				fseek($fh, $pos, SEEK_SET);
			}
			else
				fseek($fh, -$bytes, SEEK_CUR);
			if(ftell($fh) <= $pos)
				$do_break = true;

			$part = fread($fh, $bytes);
			fseek($fh, -$bytes+$len, SEEK_CUR);
			fwrite($fh, $part);
			fseek($fh, -$bytes-$len, SEEK_CUR);
		}
		return fwrite($fh, $string);
	}

	/**
	  * Loescht an der Zeigerposition im Dateizeiger $fh die $len Bytes. Der Nachfolgende Inhalt wird vorgezogen.
	  * @param $bs Groesse der Bloecke, in denen der Inhalt verschoben wird, in Bytes.
	  * @return (boolean) Erfolg
	*/

	function fdelete($fh, $len, $bs=1024)
	{
		if($bs <= 0) return false;
		$pos = ftell($fh);
		while(true)
		{
			fseek($fh, $len, SEEK_CUR);
			$part = fread($fh, $bs);
			if($part === false) break;
			fseek($fh, -strlen($part)-$len, SEEK_CUR);
			fwrite($fh, $part);
			if(strlen($part) < $bs) break;
		}
		$ret = ftruncate($fh, ftell($fh));
		fseek($fh, $pos, SEEK_SET);
		return $ret;
	}

	/**
	  * Liefert einen zufaelligen Index des Arrays $array zurueck. Die Wahrscheinlichkeitenverteilung entspricht den Werten von $array.
	*/

	function irrand($array)
	{
		$sum = array_sum($array);
		$rand = rand(1, $sum);
		$c_sum = 0;
		foreach($array as $k=>$v)
		{
			$c_sum += $v;
			if($c_sum > $rand)
				return $k;
		}
		return null;
	}

	/**
	  * Liefert den GGT von $i und $j zurueck.
	*/

	function gcd2($i,$j)
	{
		if($i == $j) return $i;
		elseif($i>$j) list($i, $j) = array($j, $i);

		$r = $i%$j;
		while($r != 0)
		{
			$i = $j;
			$j = $r;
			$r = $i%$j;
		}
		return $j;
	}

	/**
	  * Liefert den GGT aller Werte des Arrays $a zurueck.
	*/

	function gcd($a)
	{
		while(($c = count($a)) > 1)
		{
			$b = array();
			for($i=0; $i<$c; $i+=2)
			{
				$o = $a[$i];
				if(isset($a[$i+1])) $p = $a[$i+1];
				else $p = $last;

				$last = gcd2($o, $p);
				$b[] = $last;
			}
			$a = $b;
		}
		if(count($a) == 1) return array_shift($a);
		else return false;
	}

	/**
	  * Manuelle Portierung der Tabellen einer SQLite2- in eine SQLite3-Datenbank.
	*/

	function sqlite2sqlite3($old_fname, $new_fname)
	{
		$old_db = new PDO("sqlite2:".$old_fname);
		$old_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$new_db = new PDO("sqlite:".$new_fname);
		$new_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$tables = array();
		$master_query = $old_db->query("SELECT * FROM sqlite_master;");
		while(($res = $master_query->fetch(PDO::FETCH_ASSOC)) !== false)
		{
			if($res['sql'])
				$new_db->query($res['sql']);
			if($res['type'] == "table")
				$tables[] = $res['name'];
		}

		foreach($tables as $table)
		{
			$data_query = $old_db->query("SELECT * FROM ".$table.";");
			while(($res = $data_query->fetch(PDO::FETCH_ASSOC)) !== false)
			{
				foreach($res as $k=>$v)
					$res[$k] = $new_db->quote($v);
				$new_db->query("INSERT INTO ".$table." ( ".implode(", ", array_keys($res)).") VALUES ( ".implode(", ", array_values($res))." );");
			}
		}
	}

	/**
	  * Liefert die im Datenbankverzeichnis eingetragene Version zurueck.
	*/

	function get_database_version()
	{
		if(is_file(global_setting("DB_DIR").'/.version'))
		{
			if(!is_readable(global_setting("DB_DIR").'/.version'))
			{
				fputs(STDERR, "Could not read ".global_setting("DB_DIR")."/.version.\n");
				exit(1);
			}
			$current_version = trim(file_get_contents(global_setting("DB_DIR").'/.version'));
		}
		elseif(file_exists(global_setting("DB_DIR").'/highscores') && !file_exists(global_setting("DB_DIR").'/highscores_alliances') && !file_exists(global_setting("DB_DIR").'/highscores_alliances2')) $current_version = '4';
		elseif(file_exists(global_setting("DB_DIR").'/events') && @sqlite_open(global_setting("DB_DIR").'/events')) $current_version = '3';
		elseif(is_dir(global_setting("DB_DIR").'/fleets')) $current_version = '2';
		else $current_version = '1';

		return $current_version;
	}

	/**
	  * Debug-Funktion zum Ausgeben der Ausfuehrungsdauer. Ist $set auf true, wird die aktuelle Zeit unter dem Namen $name abgespeichert und $name ausgegeben. Ist $set false, werden die Dauer seit dem letzten Setzen der unter $name gespeicherten Zeit und $name ausgegeben.
	*/

	function mtime($name, $set=false)
	{
		static $mtimes;
		if(!isset($mtimes)) $mtimes = array();

		if($set)
		{
			$mtimes[$name] = microtime(true);
			echo $name."\n";
		}
		elseif(isset($mtimes[$name])) echo $name.": ".(microtime(true)-$mtimes[$name])."\n";
		else return false;
	}

	/**
	  * Liefert den Index zurueck, unter dem in $arr der groesste Wert gespeichert ist.
	*/

	function max_index($arr)
	{
		$max = null;
		$index = null;

		foreach($arr as $k=>$v)
		{
			if($v === null || is_array($v) || is_object($v)) continue;

			if($max === null || $v > $max)
			{
				$max = $v;
				$index = $k;
			}
		}

		return $index;
	}

	/**
	  * Liefert den Index zurueck, unter dem in $arr der kleinste Wert gespeichert ist.
	*/

	function min_index($arr)
	{
		$min = null;
		$index = null;

		foreach($arr as $k=>$v)
		{
			if($v === null || is_array($v) || is_object($v)) continue;

			if($min === null || $v < $min)
			{
				$min = $v;
				$index = $k;
			}
		}

		return $index;
	}

	/**
	  * Konvertiert eine Array, das Items eine Anzahl zuweist, in einen String. Format: (Item-ID ' ' Anzahl ( ' ' Item-ID ' ' Anzahl)* )?
	*/

	function encode_item_list($list)
	{
		$ret = array();
		foreach($list as $k=>$v)
			$ret[] = $k." ".$v;
		return implode(" ", $ret);
	}

	/**
	  * Konvertiert einen mit encode_item_list() kodierten String zurueck einem Array ( Item-ID => Anzahl ).
	*/

	function decode_item_list($encoded)
	{
		$list = array();
		$encoded_sp = (strlen($encoded) > 0 ? explode(" ", $encoded) : array());
		for($i=0; $i<count($encoded_sp); $i++)
			$list[$encoded_sp[$i]] = (float)$encoded_sp[++$i];
		return $list;
	}

	/**
	  * Kodiert ein Rohstoff-Array zu einem String. Format: Menge1 ' ' Menge 2 ' ' ...
	*/

	function encode_ress_list($list)
	{
		return implode(" ", $list);
	}

	/**
	  * Konvertiert einen mit encode_ress_list() kodierten String zurueck zu einem Rohstoff-Array.
	*/

	function decode_ress_list($encoded)
	{
		$list = (strlen($encoded) > 0 ? explode(" ", $encoded) : array());
		foreach($list as $k=>$v)
			$list[$k] = (float) $v;
		return $list;
	}

	/**
	  * Hebt in einem String $text Tastenkuerzel, welche durch ein voranstehendes &amp; gekennzeichnet sind, hervor, und Kodiert HTML-Steuerzeichen mit htmlspecialchars().
	  * @param $make_text Wenn true, wird das Kuerzel durch ein kbd-HTML-Tag hervorgehoben. Wenn false, wird es als ' [' Kuerzel ']' angehaengt.
	*/

	function h($text, $make_tags=true)
	{
		if($make_tags)
			return preg_replace("/&amp;([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß])/", "<kbd>$1</kbd>", htmlspecialchars($text));
		elseif(preg_match("/^(.*?)&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)(.*)\$/", $text, $m))
			return htmlspecialchars($m[1].$m[2].$m[3])." [".htmlspecialchars(str_replace(array("ä", "ö", "ü"), array("Ä", "Ö", "Ü"), strtoupper($m[2])))."]";
		else
			return htmlspecialchars($text);
	}

	/**
	  * Liefert den HTML-Code des Attributs fuer das in $message angegebene Tastenkuerzel (durch ein voranstehende &amp; gekennzeichnet) zurueck.
	  * @return Zum Beispiel ' accesskey="a"'. Wenn kein Tastenkuerzel existiert, ''.
	*/

	function accesskey_attr($message)
	{
		if(!preg_match("/&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)/", $message, $m))
			return "";
		return " accesskey=\"".htmlspecialchars(str_replace(array("Ä", "Ö", "Ü"), array("ä", "ö", "ü"), strtolower($m[1])))."\"";
	}

	/**
	  * Liefert das Titel-HTML-Attribut zur Darstellung eines Tastenkuerzels (in $message durch ein voranstehendes &amp; markiert) zurueck.
	  * @return Zum Beispiel ' title="[A]"'. Wenn kein Tastenkuerzel existiert, ''.
	*/

	function accesskey_title($message)
	{
		if(!preg_match("/&([a-zA-Z0-9]|ä|ö|ü|Ä|Ö|Ü|ß)/", $message, $m))
			return "";
		return " title=\"[".htmlspecialchars(str_replace(array("ä", "ö", "ü"), array("Ä", "Ö", "Ü"), strtoupper($m[1])))."]\"";
	}

	/**
	  * Setzt die Sprach-Locale fuer die uebergebene Sprache. Dadurch liefert gettext die Nachrichten in der neuen Sprache zurueck.
	*/

	function language($lang=null, $die=false)
	{
		$languages = array (
			"de_DE" => array("de_DE.utf8", "de_DE@utf8", "de_DE", "de", "german", "ger", "deutsch", "deu")
		);

		if($lang === null)
			return getenv("LANGUAGE");

		if(!isset($languages[$lang]) || !setlocale(LC_MESSAGES, $languages[$lang]))
		{
			if($die) die("Could not set language to ".$lang."!");
			return false;
		}
		putenv("LANGUAGE=".$lang);
		putenv("LANG=".$lang);
		return true;
	}

	/**
	  * Oeffnet den GPG-Schluessel entsprechend der Konfiguration und liefert bei Erfolg ein gnupg-Object zurueck.
	  * @return null, wenn keine Konfiguration vorliegt oder der Schluessel nicht geoeffnet werden kann.
	  * @return (gnupg)
	*/

	function gpg_init($return_public_key=false)
	{
		static $gpg,$config;

		if(!isset($config))
		{
			if(!is_file(global_setting("DB_GPG"))) return null;
			$config = parse_ini_file(global_setting("DB_GPG"));
		}
		if(!$config || !isset($config["fingerprint"]) || !isset($config["password"]))
			return null;

		if(!isset($gpg))
		{
			if(!class_exists("gnupg"))
				return null;
			$gpg = new gnupg();
			$gpg->seterrormode(gnupg::ERROR_WARNING);
			if(!$gpg->addsignkey($config["fingerprint"], $config["password"]))
				return null;
		}
		if($return_public_key)
			return $gpg->export($config["fingerprint"]);
		else
			return $gpg;
	}

	/**
	  * Signiert den gegebenen Text wenn moeglich per GPG.
	*/

	function gpg_sign($text)
	{
		$gpg = gpg_init();
		if(!$gpg)
			return $text;
		return $gpg->sign($text);
	}

	/**
	  * Signiert und verschluesselt den gegebenen Text wenn moeglich per GPG.
	*/

	function gpg_encrypt($text, $fingerprint)
	{
		$gpg = gpg_init();
		if(!$gpg)
			return $text;
		$gpg->addencryptkey($fingerprint);
		$encrypted = $gpg->encryptsign($text);
		$gpg->clearencryptkeys();
		return $encrypted;
	}

	/**
	  * Ersetzt Dinge wie [item_B0] durch den entsprechenden gettext-String.
	*/

	function _i($string)
	{
		return preg_replace("/\\[(item|ress)_[a-zA-Z0-9]\\]/e", "_('\$0')", $string);
	}
