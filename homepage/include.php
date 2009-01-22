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

	$__FILE__ = str_replace("\\", "/", __FILE__);
	$include_filename = dirname($__FILE__).'/lib/sua/engine.php';
	require_once($include_filename);

	L::language("de_DE", true);

	HTTPOutput::sendContentType();
	HTTPOutput::disableMagicQuotes();

	$config = Classes::Config(dirname(__FILE__)."/config.xml");
	$config_arr = $config->getConfig();
	// Richtigen Hostname sicherstellen
	if(isset($config_arr["hostname"]) && $config_arr["hostname"] && $_SERVER["HTTP_HOST"] != $config_arr["hostname"])
		Functions::changeHostname($config_arr["hostname"]);

	if(isset($config_arr["timezone"]))
		date_default_timezone_set($config_arr["timezone"]);

	$gui = Classes::HomeGui();

	if(isset($config_arr["databases"]))
		$gui->setOption("databases", $config_arr["databases"]);

	# SSL auf Wunsch abschalten
	if(isset($_GET["nossl"]))
	{
		if($_GET["nossl"] && (!isset($_COOKIE["use_ssl"]) || $_COOKIE["use_ssl"]))
		{
			setcookie("use_ssl", "0", time()+4838400, global_setting("h_root")."/");
			$_COOKIE["use_ssl"] = "0";
		}
		elseif(!$_GET["nossl"] && isset($_COOKIE["use_ssl"]) && !$_COOKIE["use_ssl"])
		{
			setcookie("use_ssl", "1", time()+4838400, global_setting("h_root")."/");
			$_COOKIE["use_ssl"] = "1";
		}
	}

	$gui->setOption("protocol", (!isset($_COOKIE["use_ssl"]) || $_COOKIE["use_ssl"]) ? "https" : "http");
	define("s_root", dirname(__FILE__));
	define("h_root", HTTPOutput::getHPath(s_root));
	$gui->setOption("h_root", h_root);

	$tabindex = 1;

	if(!isset($_COOKIE["use_cookies"]) && !headers_sent())
		setcookie("use_cookies", "1", time()+4838400, h_root."/");