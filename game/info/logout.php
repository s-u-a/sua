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
	 * Meldet den Benutzer ab und löscht die Session.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('../../engine.php');

	if(isset($_GET[global_setting("SESSION_NAME")]))
		session_id($_GET[global_setting("SESSION_NAME")]);
	elseif(isset($_COOKIE[global_setting("SESSION_NAME")]))
		session_id($_COOKIE[global_setting("SESSION_NAME")]);
	session_start();


	$_SESSION = array();
	if(isset($_COOKIE[global_setting("SESSION_NAME")]) && $_COOKIE[global_setting("SESSION_NAME")] == session_id())
		setcookie(global_setting("SESSION_NAME"), '', 0, global_setting("h_root")."/");
	session_destroy();

	$url = explode('/', $_SERVER['PHP_SELF']);
	array_pop($url); array_pop($url); array_pop($url);
	$url = 'http://'.$_SERVER['HTTP_HOST'].implode('/', $url).'/index.php';
	header('Location: '.$url);
	die('Logged out successfully. <a href="'.htmlspecialchars($url).'">Back to home page</a>.');