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
	$__FILE__ = str_replace("\\", "/", __FILE__);
	$include_filename = dirname($__FILE__).'/../engine.php';
	$LOGIN = true;
	require_once($include_filename);

	$resume = false;
	$del_email_passwd = false;
	if(isset($_GET[global_setting("SESSION_NAME")]))
	{
		session_id($_GET[global_setting("SESSION_NAME")]);
		session_start();
	}
	elseif(isset($_COOKIE[global_setting("SESSION_NAME")]) && !isset($_REQUEST["username"]))
	{
		session_id($_COOKIE[global_setting("SESSION_NAME")]);
		session_start();
		if(!isset($_SESSION["username"]))
		{
			setcookie(global_setting("SESSION_NAME"), "", 0, h_root."/");
			session_regenerate_id(true);
		}
	}
	else
		session_start();

	header('Cache-Control: no-cache', true);

	$databases = get_databases();
	if(isset($_SESSION['database']) && isset($databases[$_SESSION['database']]) && ($databases[$_SESSION['database']]['enabled'] || isset($_SESSION['admin_username'])))
		define_globals($_SESSION['database']);

	import("Dataset/Classes");
	import("Gui/LoginGui");

	# Skins bekommen
	$skins = get_skins();

	$gui = new LoginGui();

	if(!isset($_SESSION['username']) || !isset($_SESSION['database']) || isset($_SESSION["last_click"]) && time()-$_SESSION["last_click"] > global_setting("SESSION_TIMEOUT") || (isset($_SESSION['database']) && (!isset($databases[$_SESSION['database']]) || (!$databases[$_SESSION['database']]['enabled'] && !isset($_SESSION['admin_username'])) || !User::userExists($_SESSION['username']))))
	{
		if(isset($_REQUEST['username']) && isset($_REQUEST['password']) && isset($_REQUEST['database']))
		{
			# Anmelden

			if(!isset($databases[$_REQUEST['database']]) || !$databases[$_REQUEST['database']]['enabled'])
				$loggedin = false;
			else
			{
				define_globals($_REQUEST['database']);
				if(!User::userExists($_REQUEST['username']))
					$loggedin = false;
				else
				{
					$me = Classes::User($_REQUEST['username']);
					if(!$me->checkPassword($_REQUEST['password']))
						$loggedin = false;
					else
						$loggedin = true;
				}
			}
		}
		else
			$loggedin = false;

		if(!$loggedin)
		{
			if(isset($LOGIN_NOT_NEEDED) && $LOGIN_NOT_NEEDED)
				return;
			$gui->loginFormular();
		}
		else
		{
			# Session aktualisieren
			$_SESSION['username'] = $_REQUEST['username'];
			if(!isset($_POST["options"]) || !isset($_POST["options"]["ipcheck"]))
				$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['database'] = $_REQUEST['database'];
			$_SESSION['use_protocol'] = global_setting("USE_PROTOCOL");
			$_SESSION["init"] = time();
			if(isset($_POST["options"]) && isset($_POST["options"]["javascript"]))
				$_SESSION["disable_javascript"] = true;
			if(!isset($_REQUEST["dontresume"]))
				$resume = true;
			$del_email_passwd = true;
			if(isset($_COOKIE["use_cookies"]) && $_COOKIE["use_cookies"] && (!isset($_COOKIE[global_setting("SESSION_NAME")]) || !$_COOKIE[global_setting("SESSION_NAME")]))
			{
				setcookie(global_setting("SESSION_NAME"), session_id(), 0, h_root."/");
				$_SESSION["cookie"] = true;
			}
		}
	}

	# Ueberpruefen, ob Datenbank aktuell ist
	if(($_cv = get_database_version()) < ($_sv = global_setting("DATABASE_VERSION")))
		$gui->fatal(h(sprintf(_("Error: Database version is %s but should be %s. Please run db_things/update_database.\n"), $_cv, $_sv)));

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
	if(!$me->getStatus())
		$gui->fatal(h(_("Datenbankfehler.")));

	$_SESSION['username'] = $me->getName();
	language($me->checkSetting("lang"));
	date_default_timezone_set($me->checkSetting("timezone"));
	$gui->setOption("user", $me);

	if(isset($_SESSION["ip"]) && $_SESSION['ip'] != $_SERVER['REMOTE_ADDR'])
	{
		if(isset($_COOKIE[global_setting("SESSION_NAME")]))
			setcookie(global_setting("SESSION_NAME"), '', 0, h_root."/");
		$gui->fatal(sprintf(h(_("Diese Session wird bereits von einer anderen IP-Adresse benutzt. Bitte %sneu anmelden%s.")), "<a href=\"http://".htmlspecialchars(get_default_hostname().h_root)."/index.php\">", "</a>"));
	}

	if(!isset($_GET['planet']) || !$me->planetExists($_GET['planet']))
	{
		$planets = $me->getPlanetsList();
		$_GET["planet"] = array_shift($planets);
	}

	$me->setActivePlanet($_GET['planet']);

	define_url_suffix();

	if(isset($_SESSION['resume']) && $_SESSION['resume'])
	{
		$resume = true;
		unset($_SESSION['resume']);
	}

	# Wiederherstellen
	if($resume && false)
	{
		if($last_request = $me->lastRequest())
		{
			$url = 'http://'.$databases[$_SESSION["database"]]["hostname"].$last_request;

			$url = explode('?', $url, 2);
			if(isset($url[1]))
				$url[1] = explode('&', $url[1]);
			else
				$url[1] = array();
			foreach($url[1] as $key=>$val)
			{
				$val = explode("=", $val, 2);
				if($val[0] == urlencode(global_setting("SESSION_NAME")))
					unset($url[1][$key]);
			}
			if(strlen(global_setting("URL_SESSION_SUFFIX")) > 0)
				$url[1][] = global_setting("URL_SESSION_SUFFIX");
			$url = $url[0].(count($url[1]) > 0 ? "?".implode("&", $url[1]) : "");
			header('Location: '.$url, true, 303);
			$gui->fatal(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
		}
		else
			delete_request();
	}

	# Captcha-Abfrage
	import("Gui/Captcha");
	while($me->challengeNeeded())
	{
		$error = null;
		try
		{
			if(isset($_POST["recaptcha_challenge_field"]) && isset($_POST["recaptcha_response_field"]))
			{
				Captcha::validate($_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
				$me->challengePassed();
				break;
			}
		}
		catch(CaptchaException $e)
		{
			switch($e->getCode())
			{
				case CaptchaException::$HTTP_ERROR:
					$error = _("Fehler beim Auswerten der Captcha-Informationen.");
					break;
				case CaptchaException::$USER_ERROR:
					$me->challengeFailed();
					$error = $e->getMessage();
					break;
			}
		}

		try
		{
			Captcha::getConfig();
			$gui->init();
			if($error)
			{
?>
<p class="error"><?=htmlspecialchars($error)?></p>
<?php
			}

			Captcha::challenge($tabindex);

			$gui->end();
			exit(0);
		}
		catch(CaptchaException $e)
		{
			break;
		}
	}

	if((!isset($_SESSION['ghost']) || !$_SESSION['ghost']) && !defined('ignore_action'))
		$me->registerAction();

	function delete_request()
	{
		$_SESSION['last_click_ignore'] = true;
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.global_setting("URL_SUFFIX");
		header('Location: '.$url, true, 303);
		$gui->fatal(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
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