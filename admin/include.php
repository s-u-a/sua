<?php
	require('../engine/include.php');

	if(PROTOCOL != 'https')
	{
		$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		header('Location: '.$url, true, 307);
		die('Please use SSL: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}

	session_start();

	if((isset($_SESSION['ip']) && $_SESSION['ip'] != $_SERVER['REMOTE_ADDR']) || (isset($_GET['logout']) && $_GET['logout']) || (isset($_SESSION['last_admin_access']) && time()-$_SESSION['last_admin_access'] > 600))
	{
		if(isset($_COOKIE[SESSION_COOKIE]))
			setcookie(SESSION_COOKIE, '');
		unset($_SESSION);
		$_SESSION = array();
	}
	if((isset($_GET['logout']) && $_GET['logout']) || (isset($_SESSION['last_admin_access']) && time()-$_SESSION['last_admin_access'] > 600))
	{
		session_destroy();
		if(isset($_SESSION[SESSION_COOKIE]))
			setcookie(SESSION_COOKIE, '');
	}
	
	$databases = get_databases();
	if(isset($_SESSION['database']) && isset($databases[$_SESSION['database']]))
	{
		define_globals($databases[$_SESSION['database']][0]);
		$admins = get_admin_list();
	}

	if(!isset($_SESSION['admin_username']) || !isset($admins) || !isset($admins[$_SESSION['admin_username']]))
	{
		$show_login = true;
		if(isset($_POST['admin_username']) && isset($_POST['admin_password']) && isset($_POST['database']) && isset($databases[$_POST['database']]))
		{
			define_globals($databases[$_POST['database']][0]);
			$admins = get_admin_list();
			
			if(isset($admins[$_POST['admin_username']]) && md5($_POST['admin_password']) == $admins[$_POST['admin_username']]['password'])
			{
				$_SESSION['admin_username'] = $_POST['admin_username'];
				$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['debug'] = true;
				$_SESSION['database'] = $_POST['database'];
				$show_login = false;
			}
		}
		
		if($show_login)
		{
			admin_gui::html_head();

			$request_uri = $_SERVER['PHP_SELF'];
			$request_string = array();
			foreach($_GET as $key=>$val)
			{
				if($key != 'logout')
					$request_string[] = urlencode($key).'='.urlencode($val);
			}
			$request_string = implode('&', $request_string);
			if($request_string != '')
				$request_uri .= '?'.$request_string;
?>
<form action="<?=htmlentities($request_uri)?>" method="post">
	<dl>
		<dt><label for="admin-runde-select">Runde</label></dt>
		<dd><select name="database">
<?php
			foreach($databases as $id=>$info)
			{
?>
			<option value="<?=utf8_htmlentities($id)?>"><?=utf8_htmlentities($info[1])?></option>
<?php
			}
?>
		</select></dd>
		
		<dt><label for="admin-benutzername-input">Benutzername</label></dt>
		<dd><input type="text" name="admin_username" id="admin-benutzername-input" /></dd>

		<dt><label for="admin-passwort-input">Passwort</label></dt>
		<dd><input type="password" name="admin_password" id="admin-passwort-input" /></dd>
	</dl>
	<div><button type="submit">Anmelden</button></div>
</form>
<?php
			admin_gui::html_foot();
			die();
		}
	}

	$admin_array = &$admins[$_SESSION['admin_username']];
	$_SESSION['last_admin_access'] = time();

	class admin_gui
	{
		function html_head()
		{
?>
<?='<?=xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<title>S-U-A &ndash; Adminbereich</title>
	</head>
	<body>
		<h1><a href="<?=htmlentities(h_root.'/admin/index.php')?>"><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Adminbereich</a> [<a href="?logout=1">Abmelden nicht vergessen</a>]</h1>
<?php
		}

		function html_foot()
		{
?>
	</body>
</html>
<?php
		}
	}
?>