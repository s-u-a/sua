<?php
	require('include.php');

	if(!$admin_array['permissions'][10])
		die('No access.');

	if(!isset($_GET['action']))
	{
		$url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/admin/index.php';
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}

	admin_gui::html_head();

	switch($_GET['action'])
	{
	}

	admin_gui::html_foot();
?>