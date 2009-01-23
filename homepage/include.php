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
	 * Include-Datei für die Hauptseite.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage homepage
	*/

	namespace sua\homepage;

	use sua\l;
	use sua\Classes;
	use sua\Functions;
	use sua\HTTPOutput;

	error_reporting(4095);
	ignore_user_abort(true);

	$include_path = explode(":", get_include_path());
	array_unshift($include_path, dirname(__FILE__)."/classes/");
	array_unshift($include_path, ".");
	set_include_path(implode(":", array_unique($include_path)));

	require_once(dirname(__FILE__).'/lib/sua/engine.php');

	L::language("de_DE", true);

	HTTPOutput::sendContentType();
	HTTPOutput::disableMagicQuotes();

	$CONFIG = Classes::Config(dirname(__FILE__)."/config.xml");
	// Richtigen Hostname sicherstellen
	$hostname = $CONFIG->getConfigValue("hostname");
	if($hostname && $_SERVER["HTTP_HOST"] != $hostname)
		HTTPOutput::changeHostname($hostname);
	unset($hostname);

	$timezone = $CONFIG->getConfigValue("timezone");
	if($timezone)
		date_default_timezone_set($timezone);
	unset($timezone);

	$GUI = Classes::__callStatic("homepage\HomeGui");

	$GUI->setOption("databases", $CONFIG->getConfigValue("databases"));

	define("SROOT", dirname(__FILE__));
	define("HROOT", HTTPOutput::getHPath(SROOT));
	$GUI->setOption("h_root", HROOT);

	# SSL auf Wunsch abschalten
	if(isset($_GET["nossl"]))
	{
		if($_GET["nossl"] && (!isset($_COOKIE["use_ssl"]) || $_COOKIE["use_ssl"]))
		{
			setcookie("use_ssl", "0", time()+4838400, HROOT."/");
			$_COOKIE["use_ssl"] = "0";
		}
		elseif(!$_GET["nossl"] && isset($_COOKIE["use_ssl"]) && !$_COOKIE["use_ssl"])
		{
			setcookie("use_ssl", "1", time()+4838400, HROOT."/");
			$_COOKIE["use_ssl"] = "1";
		}
	}

	$GUI->setOption("protocol", (!isset($_COOKIE["use_ssl"]) || $_COOKIE["use_ssl"]) ? "https" : "http");

	# OpenID verwenden?
	if(isset($_GET["use_openid"]))
	{
		$_COOKIE["use_openid"] = $_GET["use_openid"] ? "1" : "0";
		setcookie("use_openid", $_COOKIE["use_openid"], time()+4838400, HROOT."/");
	}

	$GUI->setOption("openid", (isset($_COOKIE["use_openid"]) && $_COOKIE["use_openid"]));

	$TABINDEX = 1;

	if(!isset($_COOKIE["use_cookies"]) && !headers_sent())
		setcookie("use_cookies", "1", time()+4838400, HROOT."/");

	bindtextdomain("sua-homepage", dirname(__FILE__)."/locale");
	bind_textdomain_codeset("sua-homepage", "utf-8");