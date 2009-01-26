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
	 * Include-Datei zum Login, sorgt dafür, dass der Benutzer angemeldet ist.
	 * @author Candid Dauth
	 * @package sua
	*/
	namespace sua\psua;

	use \sua\Config;
	use \sua\Classes;
	use \sua\UserException;
	use \sua\Dataset;

	error_reporting(4095);
	ignore_user_abort(true);

	# 10 Minuten sollten wohl auch bei hoher Serverlast genuegen
	set_time_limit(600);

	/* TODO
	L::language("de_DE", true);

	HTTPOutput::sendContentType();
	HTTPOutput::disableMagicQuotes();

	$config = Classes::Config(dirname(__FILE__)."/config.xml");
	$config_arr = $config->getConfig();
	// Richtigen Hostname sicherstellen
	if(isset($config_arr["hostname"]) && $config_arr["hostname"] && $_SERVER["HTTP_HOST"] != $config_arr["hostname"])
		HTTPOutput::changeHostname($config_arr["hostname"]);

	if(isset($config_arr["timezone"]))
		date_default_timezone_set($config_arr["timezone"]);
	*/

	$include_path = explode(":", get_include_path());
	array_unshift($include_path, dirname(__FILE__)."/classes/");
	array_unshift($include_path, ".");
	set_include_path(implode(":", array_unique($include_path)));

	require_once(dirname(__FILE__)."/lib/sua/engine.php");

	$resume = false;
	$del_email_passwd = false;

	header('Cache-Control: no-cache', true);

	const TEXTDOMAIN = "psua";
	bindtextdomain(TEXTDOMAIN, dirname(__FILE__)."/locale");
	bind_textdomain_codeset(TEXTDOMAIN, "utf-8");
	function _($message) { return gettext($message); }
	function gettext($message) { return dgettext(TEXTDOMAIN, $message); }
	function ngettext($msgid1, $msgid2, $n) { return dngettext(TEXTDOMAIN, $msgid1, $msgid2, $n); }

	$CONFIG = Classes::Config(dirname(__FILE__)."/config.xml");
	$DATABASE = Classes::Database($CONFIG->getConfigValueE("database"));
	Dataset::setDatabase($DATABASE);

	$GUI = Classes::__callStatic("\sua\psua\LoginGui");

	if(isset($_SESSION["username"]))
	{
		# Schnellklicksperre
		$now_time = microtime(true);

		if(!isset($_SESSION['last_click_sleep']))
			$_SESSION['last_click_sleep'] = 0;
		if(isset($_SESSION['last_click']) && (!isset($_SESSION['last_click_ignore']) || !$_SESSION['last_click_ignore']))
		{
			$last_click_diff = $now_time-$_SESSION['last_click']-pow($_SESSION['last_click_sleep'], 1.5);
			if($last_click_diff < global_setting("MIN_CLICK_DIFF"))
			{
				$_SESSION['last_click_sleep']++;
				$sleep_time = round(pow($_SESSION['last_click_sleep'], 1.5));
				sleep($sleep_time);
			}
			else
				$_SESSION['last_click_sleep'] = 0;
		}

		if(isset($_SESSION['last_click_ignore']))
			unset($_SESSION['last_click_ignore']);
		$_SESSION['last_click'] = $now_time;

		$me = Classes::User($_SESSION['username']);

		$_SESSION['username'] = $me->getName();
		L::language($me->checkSetting("lang"));
		date_default_timezone_set($me->checkSetting("timezone"));
		$GUI->setOption("user", $me);

		if(isset($_SESSION["ip"]) && $_SESSION['ip'] != $_SERVER['REMOTE_ADDR'])
		{
			if(isset($_COOKIE[global_setting("SESSION_NAME")]))
				setcookie(global_setting("SESSION_NAME"), '', 0, global_setting("h_root")."/");
			$GUI->fatal(sprintf(h(_("Diese Session wird bereits von einer anderen IP-Adresse benutzt. Bitte %sneu anmelden%s.")), "<a href=\"http://".htmlspecialchars(Config::get_default_hostname().global_setting("h_root"))."/index.php\">", "</a>"));
		}

		if(!isset($_GET['planet']) || !$me->planetExists($_GET['planet']))
		{
			$planets = $me->getPlanetsList();
			$_GET["planet"] = array_shift($planets);
		}

		$me->setActivePlanet($_GET['planet']);

		define_url_suffix();

		# Captcha-Abfrage
		while($me->challengeNeeded() && !isset($_SESSION["admin_username"]))
		{
			$error = null;
			try
			{
				if(isset($_POST["recaptcha_challenge_field"]) && isset($_POST["recaptcha_response_field"]))
				{
					Captcha::validate($_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
					$me->_challengePassed();
					break;
				}
			}
			catch(CaptchaException $e)
			{
				switch($e->getCode())
				{
					case CaptchaException::HTTP_ERROR:
						$error = _("Fehler beim Auswerten der Captcha-Informationen.");
						break;
					case CaptchaException::USER_ERROR:
						$me->_challengeFailed();
						$error = $e->getMessage();
						break;
				}
			}

			try
			{
				Captcha::getConfig();
				$GUI->init();
				if($error)
				{
?>
<p class="error"><?=htmlspecialchars($error)?></p>
<?php
				}

				Captcha::challenge($tabindex);

				$GUI->end();
				exit(0);
			}
			catch(CaptchaException $e)
			{
				break;
			}
		}

		if((!isset($_SESSION['ghost']) || !$_SESSION['ghost']) && !defined('ignore_action'))
			$me->registerAction();
	}

	function delete_request()
	{
		$_SESSION['last_click_ignore'] = true;
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.global_setting("URL_SUFFIX");
		header('Location: '.$url, true, 303);
		$GUI->fatal(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
	}

	function define_url_suffix()
	{
		global $me;
		$url_suffix = array(
			"planet" => $me->getActivePlanet()
		);
		$url_session_suffix = array();
		if(!isset($_SESSION["cookie"]) || !$_SESSION["cookie"])
		{
			$url_suffix[global_setting("SESSION_NAME")] = session_id();
			$url_session_suffix[global_setting("SESSION_NAME")] = session_id();
		}

		$url_suffix_g = array();
		$url_session_suffix_g = array();
		$url_formular_g = "";
		foreach($url_suffix as $k=>$v)
		{
			$url_suffix_g[] = urlencode($k)."=".urlencode($v);
			$url_formular_g .= "<input type=\"hidden\" name=\"".htmlspecialchars($k)."\" value=\"".htmlspecialchars($v)."\" />";
		}
		foreach($url_session_suffix as $k=>$v)
			$url_session_suffix_g[] = urlencode($k)."=".urlencode($v);
		global_setting("URL_SUFFIX", implode("&", $url_suffix_g));
		global_setting("URL_FORMULAR", $url_formular_g);
		global_setting("URL_SESSION_SUFFIX", implode("&", $url_session_suffix_g));
	}

	/**
		 * Sucht nach installierten Skins und liefert ein Array des folgenden
		 * Formats zurueck:
		 * ( ID => [ Name, ( Einstellungsname => ( moeglicher Wert ) ) ] )
		 * @return array
		 * @todo
		*/

		function getSkins()
		{
			# Vorgegebene Skins-Liste bekommen
			$skins = array();
			if(is_dir(global_setting("s_root")."/login/res/style") && is_readable(global_setting("s_root")."/login/res/style"))
			{
				$dh = opendir(global_setting("s_root")."/login/res/style");
				while(($fname = readdir($dh)) !== false)
				{
					if($fname[0] == ".") continue;
					$path = global_setting("s_root")."/login/res/style/".$fname;
					if(!is_dir($path) || !is_readable($path)) continue;
					if(!is_file($path."/types") || !is_readable($path."/types")) continue;
					$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents($path."/types"));
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