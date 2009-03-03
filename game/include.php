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
	use \sua\L;
	use \sua\HTTPOutput;
	use \sua\ConfigException;
	use \sua\Planet;
	use \sua\Functions;
	use \sua\Captcha;
	use \sua\CaptchaException;

	error_reporting(4095);
	ignore_user_abort(true);

	# 10 Minuten sollten wohl auch bei hoher Serverlast genuegen
	//set_time_limit(600);
	set_time_limit(30);

	$include_path = explode(":", get_include_path());
	array_unshift($include_path, dirname(__FILE__)."/classes/");
	array_unshift($include_path, ".");
	set_include_path(implode(":", array_unique($include_path)));

	require_once(dirname(__FILE__)."/lib/sua/engine.php");

	header('Cache-Control: no-cache', true);

	$CONFIG = Classes::Config(dirname(__FILE__)."/config.xml");

	try { HTTPOutput::changeHostname($CONFIG->getConfigValueE("hostname")); }
	catch(ConfigException $e) { }

	$DATABASE = Classes::Database($CONFIG->getConfigValueE("database"));
	Dataset::setDatabase($DATABASE);

	L::language("de_DE", true);

	try { date_default_timezone_set($CONFIG->getConfigValueE("timezone")); }
	catch(ConfigException $e) { }

	const TEXTDOMAIN = "psua";
	bindtextdomain(TEXTDOMAIN, dirname(__FILE__)."/locale");
	bind_textdomain_codeset(TEXTDOMAIN, "utf-8");
	function _($USERssage) { return gettext($USERssage); }
	function gettext($USERssage) { return dgettext(TEXTDOMAIN, $USERssage); }
	function ngettext($msgid1, $msgid2, $n) { return dngettext(TEXTDOMAIN, $msgid1, $msgid2, $n); }

	define("SROOT", dirname(__FILE__));
	define("HROOT", HTTPOutput::getHPath(SROOT));

	$GUI = Classes::__callStatic("\sua\psua\LoginGui");
	$GUI->setOption("h_root", HROOT);
	$GUI->setOption("s_root", SROOT);

	if(isset($_REQUEST[session_name()]))
		session_id($_REQUEST[session_name()]);
	if(!isset($_SESSION))
		session_start();

	$loggedin = isset($_SESSION["username"]);

	if(isset($_SESSION["ip"]) && $_SESSION['ip'] != $_SERVER['REMOTE_ADDR'])
	{
		if(isset($_COOKIE[session_name()]))
			setcookie(session_name(), '', 0, HROOT."/");
		$GUI->setOption("error", _("Diese Session wird bereits von einer anderen IP-Adresse benutzt."));
		$loggedin = false;
		if(isset($_SESSION["username"]))
			$GUI->setOption("login_username", $_SESSION["username"]);
	}

	try
	{
		if(isset($_SESSION["last_click"]) && time()-$_SESSION["last_click"] > $CONFIG->getConfigValueE("session_timeout"))
		{
			if(isset($_SESSION["username"]))
				$GUI->setOption("login_username", $_SESSION["username"]);
			session_regenerate_id(true);
			$loggedin = false;
		}
	}
	catch(ConfigException $e) { }

	if(!isset($_SESSION["username"]))
		$loggedin = false;

	$_SESSION["last_click"] = time();

	$URL_SUFFIX_ARR = array();
	if(!isset($_COOKIE[session_name()]) || $_COOKIE[session_name()] != session_id())
		$URL_SUFFIX_ARR[session_name()] = session_id();
	$URL_SUFFIX = HTTPOutput::arrayToQueryString($URL_SUFFIX_ARR);
	$GUI->setOption("url_suffix", $URL_SUFFIX);

	if(isset($_SESSION["disable_javascript"]) && $_SESSION["disable_javascript"])
		$GUI->setOption("disable_javascript", true);

	if(isset($_COOKIE["no_ssl"]) && $_COOKIE["no_ssl"])
		$GUI->setOption("protocol", "http");
	else
		$GUI->setOption("protocol", "https");

	if($_SERVER["PHP_SELF"] == HROOT."/login.php" || $_SERVER["PHP_SELF"] == HROOT."/register.php")
		$loggedin = false;

	if(!$loggedin)
	{
		if($_SERVER["PHP_SELF"] != HROOT."/login.php" && $_SERVER["PHP_SELF"] != HROOT."/register.php")
		{
			$GUI->setOption("login_resume", HTTPOutput::getURL());
			$GUI->setOption("login_keep_post", true);
		}
	}
	else
	{
		$USER = Classes::User($_SESSION['username']);
		$_SESSION['username'] = $USER->getName();

		L::language($USER->checkSetting("lang"));
		date_default_timezone_set($USER->checkSetting("timezone"));
		$GUI->setOption("user", $USER);

		$PLANET = null;
		if(isset($_GET["planet"]))
		{
			$PLANET = Classes::Planet($_GET["planet"]);
			if($PLANET->getOwner() != $USER->getName())
				unset($PLANET);
		}
		if(!isset($PLANET))
		{
			$planets = Planet::getPlanetsByUser($USER->getName());
			$PLANET = $planets[Functions::first($planets)];
			unset($planets);
		}

		$GUI->setOption("planet", $PLANET);

		$URL_SUFFIX_ARR["planet"] = $PLANET->getName();
		$URL_SUFFIX = HTTPOutput::arrayToQueryString($URL_SUFFIX_ARR);
		$GUI->setOption("url_suffix", $URL_SUFFIX);

		# Captcha-Abfrage
		if($USER->challengeNeeded() && !isset($_SESSION["admin_username"]))
		{
			$error = null;

			if(isset($_POST["recaptcha_challenge_field"]) && isset($_POST["recaptcha_response_field"]))
			{
				try
				{
					Captcha::validate($_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
					$USER->_challengePassed();
					break;
				}
				catch(\sua\CaptchaException $e)
				{
					switch($e->getCode())
					{
						case CaptchaException::HTTP_ERROR:
							$error = _("Fehler beim Auswerten der Captcha-Informationen.");
							break;
						case CaptchaException::USER_ERROR:
							$USER->_challengeFailed();
							$error = $e->getMessage();
							break;
					}
				}
			}

			try
			{
				Captcha::prepareConfig();
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
			catch(\sua\CaptchaException $e)
			{
				if(defined("DEBUG") && DEBUG)
					var_dump($e);
			}
		}

		if((!isset($_SESSION['ghost']) || !$_SESSION['ghost']) && !defined('ignore_action'))
		{
			if(substr($_SERVER["REQUEST_URI"], 0, strlen(HROOT)) == HROOT)
				$USER->registerAction(substr($_SERVER["REQUEST_URI"], strlen(HROOT)));
			else
				$USER->registerAction();
		}
	}

	unset($loggedin);

	function define_url_suffix()
	{
		global $USER;
		$url_suffix = array(
			"planet" => $USER->getActivePlanet()
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