<?php
	require('../../engine/include.php');

	session_start();

	logfile::action('3');

	$_SESSION = array();
	if(isset($_COOKIE[SESSION_COOKIE]))
		setcookie(SESSION_COOKIE, '');
	session_destroy();

	$url = explode('/', $_SERVER['PHP_SELF']);
	array_pop($url); array_pop($url); array_pop($url);
	$url = 'http://'.$_SERVER['HTTP_HOST'].implode('/', $url).'/index.php';
	header('Location: '.$url);
	die('Logged out successfully. <a href="'.htmlentities($url).'">Back to home page</a>.');
?>