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
	 * Nimmt die Login-Daten von der Homepage entgegen und baut die Session auf.
	 * @author Candid Dauth
	 * @package sua
	*/
	namespace sua\psua;

	use \sua\Config;
	use \sua\User;
	use \sua\SuaException;
	use \sua\Classes;
	use \sua\HTTPOutput;

	require_once("lib/sua/engine.php");

	if(!isset($_COOKIE["use_cookies"]) && !headers_sent())
		setcookie("use_cookies", "1", time()+4838400, global_setting("h_root")."/");
	ini_set("session.use_cookies", "0");

	if(isset($_GET[session_name()]))
		unset($_GET[session_name()]);

	require("include.php");

	if(HTTPOutput::getProtocol() != "https" && (!isset($_COOKIE["use_ssl"]) || $_COOKIE["use_ssl"]))
	{
		setcookie("use_ssl", "0", time()+4838400, HROOT."/");
		$_COOKIE["use_ssl"] = "0";
	}
	elseif(HTTPOutput::getProtocol() == "https" && isset($_COOKIE["use_ssl"]) && !$_COOKIE["use_ssl"])
	{
		setcookie("use_ssl", "1", time()+4838400, HROOT."/");
		$_COOKIE["use_ssl"] = "1";
	}
	$GUI->setOption("protocol", HTTPOutput::getProtocol());

	if(isset($_REQUEST["keep_post"]))
	{
		$GUI->setOption("login_keep_post", true);
		if(isset($_GET["keep_post"]) && !isset($_POST["keep_post"]))
			$_POST["keep_post"] = $_GET["keep_post"];
	}

	$username = null;
	try
	{
		if(isset($_REQUEST["openid"]))
		{
			require_once("Zend/OpenId/Consumer.php");

			$consumer = new \Zend_OpenId_Consumer();
			if(!$consumer->login($_REQUEST["openid"]))
				throw new SuaException(_("OpenID-Anmeldung fehlgeschlagen."));
		}
		elseif(isset($_REQUEST["openid_mode"]))
		{
			if($_REQUEST["openid_mode"] == "id_res")
			{
				require_once("Zend/OpenId/Consumer.php");

				$consumer = new \Zend_OpenId_Consumer();

				if($consumer->verify($_REQUEST, &$id))
				{
					$username = User::getUserByOpenId($id);
					if(!$username)
						throw new SuaException(sprintf(_("Die OpenID %s ist nicht bekannt."), $id));
				}
			}
			if(!$username)
				throw new SuaException(_("OpenID-Authentifizierung fehlgeschlagen."));
		}
		elseif(isset($_REQUEST["username"]))
		{
			if(!User::exists($_REQUEST["username"]))
				throw new SuaException(_("Unbekannter Benutzername."));
			$user_obj = Classes::User($_REQUEST["username"]);
			if(!isset($_REQUEST["password"]) || !$user_obj->checkPassword($_REQUEST["password"]))
				throw new SuaException(_("Falsches Passwort."));
			$username = $_REQUEST["username"];
		}
	}
	catch(SuaException $e)
	{
		$GUI->setOption("error", $e->getMessage());
	}

	if($username)
	{
		# Session aktualisieren
		$user_obj = Classes::User($username);
		$_SESSION['username'] = $user_obj->getName();
		if(!isset($_REQUEST["options"]) || !isset($_REQUEST["options"]["ipcheck"]))
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

		$_SESSION["init"] = time();
		if(isset($_REQUEST["options"]) && isset($_REQUEST["options"]["javascript"]))
		{
			$_SESSION["disable_javascript"] = true;
			$GUI->setOption("disable_javascript", true);
		}
		if(isset($_REQUEST["referrer"]))
			$_SESSION["homepage_url"] = $_REQUEST["referrer"];

		$_SESSION["last_click"] = time();

		if(!isset($_COOKIE[session_name()]))
			setcookie(session_name(), session_id(), 0, HROOT."/");

		var_dump($_SESSION);

		if(isset($_REQUEST["resume"]))
			HTTPOutput::changeURL($_REQUEST["resume"]);
		elseif($last_request = $user_obj->lastRequest())
		{
			$url = 'http://'.$_SERVER["HTTP_HOST"].HROOT.$last_request;

			$url = explode('?', $url, 2);
			if(isset($url[1]))
				$url[1] = explode('&', $url[1]);
			else
				$url[1] = array();
			foreach($url[1] as $key=>$val)
			{
				$val = explode("=", $val, 2);
				if($val[0] == urlencode(session_name()))
					unset($url[1][$key]);
			}
			array_unshift($url[1], urlencode(session_name())."=".urlencode(session_id()));
			$url = $url[0]."?".implode("&", $url[1]);
			HTTPOutput::changeURL($url, false);
		}
		else
			HTTPOutput::changeURL("http://".$_SERVER["HTTP_HOST"].HROOT."/?".urlencode(session_name())."=".urlencode(session_id()), false);
	}
	else
	{
		$GUI->loginFormular();
	}