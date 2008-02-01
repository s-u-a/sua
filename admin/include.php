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
	require('../engine/include.php');

	language("de_DE", true);

	$actions = array(
		"0" => _("%s hat sich angemeldet."),
		"1" => _("%s hat sich als Geist unter dem Benutzer %s angemeldet."),
		"2" => _("%s hat das Passwort des Benutzers %s geändert."),
		"3" => _("%s hat die Passwörter von %s und %s verglichen."),
		"4" => _("%s hat den Benutzer %s gelöscht."),
		"5.1" => _("%s hat den Benutzer %s gesperrt."),
		"5.2" => _("%s hat den Benutzer %s entsperrt."),
		"6" => _("%s hat den Benutzer %s nach %s umbenannt."),
		"7.1" => _("%s hat den Anfängerschutz ausgeschaltet."),
		"7.2" => _("%s hat den Anfängerschutz eingeschaltet."),
		"8.1" => _("%s hat einen Eintrag zum Changelog hinzugefügt: %s"),
		"8.2" => _("%s hat einen Eintrag aus dem Changelog gelöscht: %s"),
		"9" => _("%s hat einen Eintrag eine Nachricht mit dem Betreff %s an %s versandt."),
		"10" => _("%s hat den Logeintrag %s angeschaut."),
		"11.1" => _("%s hat den Administrator %s hinzugefügt."),
		"11.2" => _("%s hat die Rechte des Administrators %s verändert."),
		"11.3" => _("%s hat den Administrator %s nach %s umbenannt."),
		"11.4" => _("%s hat den Administrator %s entfernt."),
		"12.1" => _("%s hat die Wartungsarbeiten eingeschaltet."),
		"12.2" => _("%s hat die Wartungsarbeiten ausgeschaltet."),
		"13.1" => _("%s hat das Spiel gesperrt."),
		"13.2" => _("%s hat das Spiel entsperrt."),
		"14.1" => _("%s hat einen Newseintrag mit dem Titel %s hinzugefügt."),
		"14.2" => _("%s hat den Newseintrag mit dem Titel %s verändert."),
		"14.3" => _("%s hat den Newseintrag mit dem Titel %s gelöscht.")
	);

	if(global_setting("PROTOCOL") != 'https')
	{
		$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		header('Location: '.$url, true, 307);
		die(sprintf(h(_("Please use SSL: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
	}

	session_start();

	if((isset($_SESSION['ip']) && $_SESSION['ip'] != $_SERVER['REMOTE_ADDR']) || (isset($_GET['logout']) && $_GET['logout']) || (isset($_SESSION['last_admin_access']) && time()-$_SESSION['last_admin_access'] > 600))
	{
		if(isset($_COOKIE[session_name()]))
			setcookie(session_name(), '', 0, h_root.'/admin/');
		unset($_SESSION);
		$_SESSION = array();
	}
	if((isset($_GET['logout']) && $_GET['logout']) || (isset($_SESSION['last_admin_access']) && time()-$_SESSION['last_admin_access'] > 600))
	{
		session_destroy();
		if(isset($_COOKIE[session_name()]))
			setcookie(session_name(), '', 0, h_root.'/admin/');
	}

	$databases = get_databases();
	if(isset($_SESSION['database']) && isset($databases[$_SESSION['database']]))
	{
		define_globals($_SESSION['database']);
		$admins = get_admin_list();
	}

	if(!isset($_SESSION['admin_username']) || !isset($admins) || !isset($admins[$_SESSION['admin_username']]))
	{
		$show_login = true;
		if(isset($_POST['admin_username']) && isset($_POST['admin_password']) && isset($_POST['database']) && isset($databases[$_POST['database']]))
		{
			define_globals($_POST['database']);
			$admins = get_admin_list();

			if(isset($admins[$_POST['admin_username']]) && md5($_POST['admin_password']) == $admins[$_POST['admin_username']]['password'])
			{
				$_SESSION['admin_username'] = $_POST['admin_username'];
				$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['debug'] = true;
				$_SESSION['database'] = $_POST['database'];
				$show_login = false;

				setcookie("sua_is_admin", "1", time()+2419200, h_root.'/');

				protocol("0", $_SESSION['database']);
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
<form action="<?=htmlspecialchars($request_uri)?>" method="post">
	<dl>
		<dt><label for="admin-runde-select"><?=h(_("Runde&[admin/include.php|1]"))?></label></dt>
		<dd><select name="database"<?=accesskey_attr(_("Runde&[admin/include.php|1]"))?>>
<?php
			foreach($databases as $id=>$info)
			{
				if($info['dummy']) continue;
?>
			<option value="<?=htmlspecialchars($id)?>"><?=htmlspecialchars($info['name'])?></option>
<?php
			}
?>
		</select></dd>

		<dt><label for="admin-benutzername-input"><?=h(_("Benutzername&[admin/include.php|1]"))?></label></dt>
		<dd><input type="text" name="admin_username" id="admin-benutzername-input"<?=accesskey_attr(_("Benutzername&[admin/include.php|1]"))?> /></dd>

		<dt><label for="admin-passwort-input"><?=h(_("Passwort&[admin/include.php|1]"))?></label></dt>
		<dd><input type="password" name="admin_password" id="admin-passwort-input"<?=accesskey_attr(_("Passwort&[admin/include.php|1]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Anmelden&[admin/include.php|1]"))?>><?=h(_("Anmelden&[admin/include.php|1]"))?></button></div>
</form>
<ul>
	<li><a href="http://<?=htmlspecialchars($_SERVER['HTTP_HOST'].h_root)?>/index.php"<?=accesskey_attr(_("Zurück zum Spiel&[admin/include.php|1]"))?>><?=h(_("Zurück zum Spiel&[admin/include.php|1]"))?></a></li>
</ul>
<?php
			admin_gui::html_foot();
			die();
		}
	}

	$admin_array = &$admins[$_SESSION['admin_username']];
	$_SESSION['last_admin_access'] = time();

	class admin_gui
	{
		static function html_head()
		{
?>
<?='<?=xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
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
-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=h(_("[LANG]"))?>">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<title><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Adminbereich")))?></title>
		<link rel="stylesheet" href="<?=htmlspecialchars(h_root.'/admin/style.css')?>" type="text/css" />
		<script type="text/javascript">
			var session_cookie = '<?=str_replace('\'', '\\\'', session_name())?>';
			var session_id = '<?=str_replace('\'', '\\\'', session_id())?>';
<?php
			if(isset($_SESSION['database']))
			{
?>
			var database_id = '<?=str_replace('\'', '\\\'', $_SESSION['database'])?>';
<?php
			}
?>
			var ths_utf8 = '<?=utf8_jsentities(global_setting("THS_UTF8"))?>';
			var h_root = '<?=utf8_jsentities(h_root)?>';
			var last_min_chars = '<?=utf8_jsentities(global_setting("LIST_MIN_CHARS"))?>';
		</script>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root.'/login/scripts/javascript-2.js')?>"></script>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root.'/sarissa.js')?>"></script>
	</head>
	<body>
		<h1><a href="<?=htmlspecialchars(h_root.'/admin/index.php')?>"<?=accesskey_attr(_("Adminbereich&[admin/include.php|2]"))?>><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Adminbereich&[admin/include.php|2]")))?></a> [<a href="?logout=1"<?=accesskey_attr(_("Abmelden nicht vergessen&[admin/include.php|2]"))?>><?=h(_("Abmelden nicht vergessen&[admin/include.php|2]"))?></a>]</h1>
<?php
		}

		static function html_foot()
		{
?>
	</body>
</html>
<?php
		}
	}

	function protocol($type)
	{
		$fh = fopen(global_setting("DB_ADMIN_LOGFILE"), "a");
		if(!$fh) return false;
		flock($fh, LOCK_EX);
		fwrite($fh, session_id()."\t".time()."\t".$_SESSION['admin_username']);
		foreach(func_get_args() as $arg)
			fwrite($fh, "\t".preg_replace("/[\n\t]/", " ", $arg));
		fwrite($fh, "\n");
		flock($fh, LOCK_UN);
		fclose($fh);
		return true;
	}
?>
