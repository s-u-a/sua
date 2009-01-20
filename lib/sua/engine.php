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

	namespace sua
	{
		# s_root ermitteln: Absoluter Pfad zum Spielverzeichnis
		define("libdir", dirname(__FILE__));

		# PEAR einbinden (wenn es nicht im System installiert ist, kann man es ins lib-Verzeichnis legen)
		if(is_dir(libdir."/../pear"))
			set_include_path(".:".libdir."/../pear/:".get_include_path());

		/*$GDB_DIR = global_setting("s_root")."/database.global";
		global_setting("GDB_DIR", $GDB_DIR);
		global_setting("DB_NEWS", $GDB_DIR."/news");
		global_setting("DB_CHANGELOG", $GDB_DIR."/changelog");
		global_setting("DB_VERSION", $GDB_DIR."/version");
		global_setting("DB_REVISION", $GDB_DIR."/revision");
		global_setting("DB_CONFIG", $GDB_DIR."/config.xml");
		global_setting("DB_CONFIG_CACHE", $GDB_DIR."/config.db");
		global_setting("DB_NOTIFICATIONS", $GDB_DIR."/notifications");
		global_setting("DB_EVENTHANDLER_LOG", $GDB_DIR."/eventhandler.log");
		global_setting("DB_EVENTHANDLER_PIDFILE", $GDB_DIR."/eventhandler.pid");
		global_setting("DB_IMSERVER_PIDFILE", $GDB_DIR."/imserver.pid");
		global_setting("EVENTHANDLER_INTERVAL", 2);
		global_setting("EVENTHANDLER_MARKETCACHE", 10); # Wieviele Eventhandler-Intervalle sollen aus der Boersendatenbank gecacht werden?
		global_setting("MIN_CLICK_DIFF", 0.3); # Sekunden, die zwischen zwei Klicks mindestens vergehen muessen, sonst Bremsung
		global_setting("EMAIL_FROM", "webmaster@s-u-a.net");
		global_setting("MAX_PLANETS", 15);
		global_setting("LIST_MIN_CHARS", 1); # Fuer Ajax-Auswahllisten
		global_setting("ALLIANCE_RENAME_PERIOD", 3); # Minimalabstand fuers Umbenennen von Allianzen in Tagen
		global_setting("PROTOCOL", (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https" : "http");
		global_setting("USE_PROTOCOL", (isset($_SESSION["use_protocol"]) ? $_SESSION["use_protocol"] : (((!isset($_COOKIE["use_ssl"]) || $_COOKIE["use_ssl"])) ? "https" : "http")));
		global_setting("MIN_BUILDING_TIME", 12); # Minimale Bauzeit in Sekunden
		global_setting("DATABASE_VERSION", 9); # Aktuelle Datenbankversion
		global_setting("EVENTHANDLER_RUNTIME", 16200); # Sekunden seit Tagesbeginn, wann der Eventhandler laufen soll
		global_setting("MARKET_MIN_AMOUNT", 10); # Das Wievielfache eines Angebotes muss insgesamt geboten worden sein, damit ein Auftrag angenommen wird?
		global_setting("MARKET_MIN_USERS", 5); # Wieviele verschiedene Benutzer muessen den Rohstoff als Angebot auf dem Markt haben, damit ein Auftrag angenommen wird?
		global_setting("MARKET_DELAY", 7200); # Wieviele Sekunden soll es von der Annahme bis zur Fertigstellung eines Angebotes dauern?
		global_setting("EMAIL_CHANGE_DELAY", 604800); # Nach wie vielen Sekunden soll eine Aenderung der E-Mail-Adresse gueltig werden?
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
		global_setting("LOG", fopen("php://stderr", "w")); # File stream, in den Log-Meldungen der db_things-Scripte geschrieben werden
		global_setting("PUBLIC_MESSAGES_TIME", 30); # Die Zeit in Tagen, nach denen eine ungelesene öffentliche Nachricht gelöscht wird
		global_setting("TIME_BEFORE_HOLIDAY_RETURN", 3); # Tage, bevor ein Benutzer wieder aus dem Urlaubsmodus zurückkehren darf
		global_setting("TIME_BEFORE_HOLIDAY_GO", 3); # Tage, bevor ein Benutzer wieder in den Urlaubsmodus gehen kann*/

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

		# Anzahl der Tage, nach denen ein Benutzer wegen Inaktivität benachrichtigt bzw. gelöscht wird. Die
		# Löschung erfolgt bei der letzten Zahl des Arrays, Benachrichtigung bei allen anderen Zahlen
		$user_inactivity = array (
			array(21, 24, 25), # Nicht-Urlaubsmodus
			array(175, 189), # Urlaubsmodus
			array(7, 14) # Nie angemeldet
		);

		# Zu jeder Flottenauftragsart die zugehoerige Nachrichtensorte
		$types_message_types = array (
			Fleet::TYPE_BESIEDELN => Message::TYPE_BESIEDELUNG,
			Fleet::TYPE_SAMMELN => Message::TYPE_SAMMELN,
			Fleet::TYPE_ANGRIFF => Message::TYPE_KAEMPFE,
			Fleet::TYPE_TRANSPORT => Message::TYPE_TRANSPORT,
			Fleet::TYPE_SPIONIEREN => Message::TYPE_SPIONAGE,
			Fleet::TYPE_STATIONIEREN => Message::TYPE_TRANSPORT
		);

		if(isset($_GET["agpl"]) && $_GET["agpl"] == "!" && isset($_SERVER["SCRIPT_FILENAME"]))
		{
			header("Content-type: application/x-httpd-php;charset=UTF-8");
			print file_get_contents($_SERVER["SCRIPT_FILENAME"]);
			exit(0);
		}
	}

	namespace
	{
		function __autoload($classname)
		{
			$fname = dirname(__FILE__)."/classes/".str_replace("\\", "/", strtolower($classname)).".php";
			if(!is_file($fname)) return false;
			include_once($fname);
			if(in_array("sua\StaticInit", class_implements($classname)))
				$classname::init();
		}
	}