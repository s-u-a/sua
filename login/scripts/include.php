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
	$include_filename = dirname($__FILE__).'/../../engine/include.php';
	$LOGIN = true;
	require_once($include_filename);

	$resume = false;
	$del_email_passwd = false;
	session_start();
	header('Cache-Control: no-cache', true);

	$databases = get_databases();
	if(isset($_SESSION['database']) && isset($databases[$_SESSION['database']]) && ($databases[$_SESSION['database']]['enabled'] || isset($_SESSION['admin_username'])))
		define_globals($_SESSION['database']);

	if(!isset($_SESSION['username']) || !isset($_SESSION['database']) || (isset($_SESSION['database']) && (!isset($databases[$_SESSION['database']]) || (!$databases[$_SESSION['database']]['enabled'] && !isset($_SESSION['admin_username'])) || !User::userExists($_SESSION['username']))))
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
			# Auf die Startseite zurueckleiten
			$url = explode('/', $_SERVER['PHP_SELF']);
			array_pop($url); array_pop($url);
			$url = 'http://'.get_default_hostname().implode('/', $url).'/index.php';
			if(!isset($_REQUEST['username']) || !isset($_REQUEST['password']))
			{
				header('Location: '.$url, true, 303);
				die(sprintf(h(_("Nicht angemeldet. Bitte %serneut anmelden%s.")), "<a href=\"".htmlspecialchars($url)."\">", "</a>"));
			}
			else
				die(sprintf(h(_("Anmeldung fehlgeschlagen. Haben Sie sich bereits registriert und Ihren Benutzernamen und Ihr Passwort korrekt in die zugehörigen Felder über dem Anmelden-Button eingetragen? Haben Sie Groß-Klein-Schreibung beim Passwort beachtet? %sProbieren Sie es noch einmal.")), "<a href=\"".htmlspecialchars($url)."\">", "</a>"));
		}
		else
		{
			# Session aktualisieren
			$_SESSION['username'] = $_REQUEST['username'];
			$_SESSION['act_planet'] = 0;
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['database'] = $_REQUEST['database'];
			$_SESSION['use_protocol'] = global_setting("USE_PROTOCOL");
			$resume = true;
			$del_email_passwd = true;
		}
	}

	# Ueberpruefen, ob Datenbank aktuell ist
	if(($_cv = get_database_version()) < ($_sv = global_setting("DATABASE_VERSION")))
	{
		echo h(sprintf(_("Error: Database version is %s but should be %s. Please run db_things/update_database.\n"), $_cv, $_sv));
		exit(1);
	}

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

	# Skins bekommen
	$skins = get_skins();

	# Version herausfinden
	$version = get_version();
	define('VERSION', $version);

	$me = Classes::User($_SESSION['username']);
	$_SESSION['username'] = $me->getName();
	language($me->checkSetting("lang"));
	date_default_timezone_set($me->checkSetting("timezone"));

	if(!$me->getStatus())
	{
		login_gui::html_head();
?>
<p class="error"><?=h(_("Datenbankfehler."))?></p>
<?php
		login_gui::html_foot();
		exit(1);
	}

	if($_SESSION['ip'] != $_SERVER['REMOTE_ADDR'] && $me->checkSetting('ipcheck'))
	{
		if(isset($_COOKIE[session_name()]))
			setcookie(session_name(), '');
		die(sprintf(h(_("Diese Session wird bereits von einer anderen IP-Adresse benutzt. Bitte %sneu anmelden%s.")), "<a href=\"http://".htmlspecialchars(get_default_hostname().h_root)."/index.php\">", "</a>"));
	}

	if(isset($_SESSION['resume']) && $_SESSION['resume'])
	{
		$resume = true;
		unset($_SESSION['resume']);
	}

	# Wiederherstellen
	if($resume && $last_request = $me->lastRequest())
	{
		$_SESSION['act_planet'] = $last_request[1];
		$url = 'http://'.$databases[$_SESSION["database"]]["hostname"].$last_request[0];

		$url = explode('?', $url, 2);
		if(isset($url[1]))
			$url[1] = explode('&', $url[1]);
		else
			$url[1] = array();
		$one = false;
		foreach($url[1] as $key=>$val)
		{
			$val = explode("=", $val, 2);
			if($val[0] == session_name())
			{
				$url[1][$key] = urlencode(session_name()).'='.urlencode(session_id());
				$one = true;
			}
		}
		$url2 = $url[0];
		if(count($url[1]) > 0)
			$url2 .= '?'.implode('&', $url[1]);
		$url = $url2;
		if(!$one)
		{
			if(strpos($url, '?') === false)
				$url .= '?';
			else
				$url .= '&';
			$url .= urlencode(session_name()).'='.urlencode(session_id());
		}
		header('Location: '.$url, true, 303);
		die(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
	}

	if(isset($_GET['planet']) && $me->planetExists($_GET['planet'])) # Planeten wechseln
		$_SESSION['act_planet'] = $_GET['planet'];
	if(!isset($_SESSION['act_planet']) || !$me->planetExists($_SESSION['act_planet']))
	{
		$planets = $me->getPlanetsList();
		$_SESSION['act_planet'] = array_shift($planets);
	}

	$me->setActivePlanet($_SESSION['act_planet']);

	if((!isset($_SESSION['ghost']) || !$_SESSION['ghost']) && !defined('ignore_action'))
		$me->registerAction();

	class login_gui
	{
		static function html_head()
		{
			global $me;
			global $skins;
			global $databases;
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
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
		<title><?=h(_("[title_abbr_full]"))?></title>
		<script type="text/javascript">
			var session_cookie = '<?=str_replace('\'', '\\\'', session_name())?>';
			var session_id = '<?=str_replace('\'', '\\\'', session_id())?>';
			var database_id = '<?=str_replace('\'', '\\\'', $_SESSION['database'])?>';
			var ths_utf8 = '<?=jsentities(_("[thousand_separator]"))?>';
			var h_root = '<?=jsentities(h_root)?>';
			var last_min_chars = '<?=jsentities(global_setting("LIST_MIN_CHARS"))?>';
		</script>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root.'/login/scripts/javascript-'.$me->checkSetting('performance').".js")?>"></script>
<?php
			if($me->checkSetting('performance') != 0)
			{
				if($me->checkSetting('ajax'))
				{
?>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root.'/sarissa.js')?>"></script>
<?php
				}
?>
		<script type="text/javascript">
			set_time_globals(<?=time()+1?>);
		</script>
<?php
			}

			$skin_path = '';
			$my_skin = $me->checkSetting('skin');
			if(!$my_skin || !is_array($my_skin) || $my_skin[0] != "custom" && !isset($skins[$my_skin[0]]))
			{
				$my_skin = array("default", null, array());
				$me->setSetting("skin", $my_skin);
			}

			if($my_skin[0] == 'custom')
				$skin_path = $my_skin[1];
			elseif(isset($skins[$my_skin[0]]))
				$skin_path = h_root.'/login/style/'.urlencode($my_skin[0]).'/style.css';

			if(trim($skin_path) != '')
			{
?>
		<link rel="stylesheet" href="<?=htmlspecialchars($skin_path)?>" type="text/css" />
<?php
			}

			$class = 'planet-'.$me->getPlanetClass();
			if(!$me->checkSetting('noads'))
				$class .= ' mit-werbung';
			else
				$class .= ' ohne-werbung';

			if($my_skin[0] == "custom" && isset($my_skin[2]) && !is_array($my_skin[2]))
				$class .= preg_replace("/^|\s+/", " skin-", $my_skin[2]);
			elseif($my_skin[0] != "custom")
			{
				$i = 0;
				foreach($skins[$my_skin[0]][1] as $vs)
				{
					if(isset($my_skin[2]) && is_array($my_skin[2]) && isset($my_skin[2][$i]) && isset($vs[$my_skin[2][$i]]))
						$v = $my_skin[2][$i];
					else
						$v = 0;
					$class .= " skin-".$i."-".$v;
					$i++;
				}
			}
?>
	</head>
	<body class="<?=htmlspecialchars($class)?>" id="body-root"><div id="content-1" class="<?=htmlspecialchars($class)?>"><div id="content-2" class="<?=htmlspecialchars($class)?>"><div id="content-3" class="<?=htmlspecialchars($class)?>"><div id="content-4" class="<?=htmlspecialchars($class)?>"><div id="content-5" class="<?=htmlspecialchars($class)?>"><div id="content-6" class="<?=htmlspecialchars($class)?>"><div id="content-7" class="<?=htmlspecialchars($class)?>"><div id="content-8" class="<?=htmlspecialchars($class)?>">
		<ul id="links-down" class="cross-navigation">
			<li><a href="#inner-content"<?=accesskey_attr(_("Zum Inhalt&[login/scripts/include.php|1]"))?>><?=h(_("Zum Inhalt&[login/scripts/include.php|1]"))?></a></li>
			<li><a href="#navigation"<?=accesskey_attr(_("Zur Navigation&[login/scripts/include.php|1]"))?>><?=h(_("Zur Navigation&[login/scripts/include.php|1]"))?></a></li>
			<li><a href="#time"<?=accesskey_attr(_("Zu den Spieldaten&[login/scripts/include.php|1]"))?>><?=h(_("Zu den Spieldaten&[login/scripts/include.php|1]"))?></a></li>
		</ul>
		<hr class="separator" />
<?php
			$cur_ress = $me->getRess();
?>
		<div id="content-9" class="<?=htmlspecialchars($class)?>">
			<dl id="ress" class="ress">
				<dt class="ress-carbon"><?=h(_("[ress_0]"))?></dt>
				<dd class="ress-carbon<?=($cur_ress[0]<0) ? " negativ" : ""?>" id="ress-carbon"><?=ths($cur_ress[0])?></dd>

				<dt class="ress-aluminium"><?=h(_("[ress_1]"))?></dt>
				<dd class="ress-aluminium<?=($cur_ress[1]<0) ? " negativ" : ""?>" id="ress-aluminium"><?=ths($cur_ress[1])?></dd>

				<dt class="ress-wolfram"><?=h(_("[ress_2]"))?></dt>
				<dd class="ress-wolfram<?=($cur_ress[2]<0) ? " negativ" : ""?>" id="ress-wolfram"><?=ths($cur_ress[2])?></dd>

				<dt class="ress-radium"><?=h(_("[ress_3]"))?></dt>
				<dd class="ress-radium<?=($cur_ress[3]<0) ? " negativ" : ""?>" id="ress-radium"><?=ths($cur_ress[3])?></dd>

				<dt class="ress-tritium"><?=h(_("[ress_4]"))?></dt>
				<dd class="ress-tritium<?=($cur_ress[4]<0) ? " negativ" : ""?>" id="ress-tritium"><?=ths($cur_ress[4])?></dd>

				<dt class="ress-energie"><?=h(_("[ress_5]"))?></dt>
				<dd class="ress-energie<?=($cur_ress[5]<0) ? " negativ" : ""?>" id="ress-energie"><?=ths($cur_ress[5])?></dd>
			</dl>
			<div id="content-10" class="<?=htmlspecialchars($class)?>"><div id="content-11" class="<?=htmlspecialchars($class)?>"><div id="content-12" class="<?=htmlspecialchars($class)?>"><div id="content-13" class="<?=htmlspecialchars($class)?>">
<?php
			$locked_until = false;
			if($l = database_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="spiel"><strong><?=h(_("Das Spiel ist derzeit gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s', $locked_until)))))?></span><?php }?></p>
<?php
			}
			elseif($me->userLocked())
			{
				$l = $me->lockedUntil();
				if($l) $locked_until = $l;
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="account"><strong><?=h(_("Ihr Benutzeraccount ist gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s', $locked_until)))))?></span><?php }?></p>
<?php
			}
			elseif($me->umode())
			{
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="urlaub"><strong><?=h(_("Ihr Benutzeraccount befindet sich im Urlaubsmodus."))?></strong></p>
<?php
			}
			elseif($l = fleets_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="flotten"><strong><?=h(_("Es herrscht eine Flottensperre für feindliche Flüge."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s', $locked_until)))))?></span><?php }?></p>
<?php
			}
			if($locked_until)
			{
?>
				<script type="text/javascript">
					init_countdown("sperre", <?=$locked_until?>, false);
				</script>
<?php
			}
?>
				<hr class="separator" />
				<h1><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?></h1>
<?php
			if($me->checkSetting('notify') || $_SERVER['PHP_SELF'] == h_root."/login/index.php")
			{
				$ncount = array(
					1 => 0,
					2 => 0,
					3 => 0,
					4 => 0,
					5 => 0,
					6 => 0,
					7 => 0
				);
				$ges_ncount = 0;

				$cats = $me->getMessageCategoriesList();
				foreach($cats as $cat)
				{
					$message_ids = $me->getMessagesList($cat);
					foreach($message_ids as $message)
					{
						$status = $me->checkMessageStatus($message, $cat);
						if($status == 1 && $cat != 8)
						{
							$ncount[$cat]++;
							$ges_ncount++;
						}
					}
				}

				if($ges_ncount > 0)
				{
					$title = array();
					$link = 'nachrichten.php';
					foreach($ncount as $type=>$count)
					{
						if($count > 0)
							$title[] = sprintf(h(_("%s: %s")), h(_("[message_".$type."]")), ths($count));
						if($count == $ges_ncount)
							$link .= '?type='.urlencode($type);
					}
					$title = implode('; ', $title);
					if(strpos($link, '?') === false)
						$link .= '?';
					else
						$link .= '&';
					$link .= urlencode(session_name()).'='.urlencode(session_id());
?>
				<hr class="separator" />
				<p class="neue-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root.'/login/'.$link)?>" title="<?=$title?>"<?=accesskey_attr(ngettext("Sie haben %s neue &Nachricht.[login/scripts/include.php|2]", "Sie haben %s neue &Nachrichten.[login/scripts/include.php|2]", $ges_ncount))?>><?=h(sprintf(ngettext("Sie haben %s neue &Nachricht.[login/scripts/include.php|2]", "Sie haben %s neue &Nachrichten.[login/scripts/include.php|2]", $ges_ncount), $ges_ncount))?></a></p>
<?php
				}
			}
?>
				<hr class="separator" />
				<div id="inner-content">

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

<?php
		}

		static function html_foot()
		{
			global $me,$databases;
?>

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

				</div>
			</div></div>
<?php
			if($me->checkSetting('noads'))
			{
?>
<!--[if IE]>
<?php
			}
?>
			<div id="werbung">
<?php
			global $DISABLE_ADS;
			if($me->checkSetting('performance') != 0 && (!isset($DISABLE_ADS) || !$DISABLE_ADS) && global_setting("PROTOCOL") == 'http')
			{
?>
				<script type="text/javascript">
					google_ad_client = "pub-2662652449578921";
					google_ad_slot = "2761845030";
					google_ad_width = 120;
					google_ad_height = 600;
				</script>
				<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
<?php
			}
?>
			</div>
<?php
			if($me->checkSetting('noads'))
			{
?>
<![endif]-->
<?php
			}
?>
			<div id="css-1"></div>
		</div></div>
		<div id="css-2"></div>
		</div>

		<hr class="separator" />

		<ul id="links-up-1" class="cross-navigation">
			<li><a href="#ress"<?=accesskey_attr(_("Zur Rohstoffanzeige&[login/scripts/include.php|1]"))?>><?=h(_("Zur Rohstoffanzeige&[login/scripts/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=h(_("Zum Inhalt&[login/scripts/include.php|1]"))?></a></li>
		</ul>

		<hr class="separator" />

		<div id="navigation">
			<form action="<?=htmlspecialchars($_SERVER['PHP_SELF'])?>" method="get" id="change-planet">
				<fieldset>
					<legend><?=h(_("Planeten wechseln"))?></legend>
<?php
			foreach($_GET as $key=>$val)
			{
				if($key == 'planet') continue;
?>
					<input type="hidden" name="<?=htmlspecialchars($key)?>" value="<?=htmlspecialchars($val)?>" />
<?php
			}
?>
					<select name="planet" onchange="if(this.value != <?=$_SESSION['act_planet']?>) this.form.submit();" onkeyup="if(this.value != <?=$_SESSION['act_planet']?>) this.form.submit();"<?=accesskey_attr(_("Ihre &Planeten[login/scripts/include.php|3]"))?> title="<?=h(_("Ihre &Planeten[login/scripts/include.php|3]"), false)?>">
<?php
			$planets = $me->getPlanetsList();
			foreach($planets as $planet)
			{
				$me->setActivePlanet($planet);
?>
						<option value="<?=htmlspecialchars($planet)?>"<?=($planet == $_SESSION['act_planet']) ? ' selected="selected"' : ''?>><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?></option>
<?php
			}
			$me->setActivePlanet($_SESSION['act_planet']);
?>
					</select>
					<noscript><div><button type="submit"><?=h(_("Wechseln"))?></button></div></noscript>
				</fieldset>
			</form>
			<hr class="separator" />
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/index.php?<?=htmlspecialchars(session_name().'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Übersicht[login/scripts/include.php|3]"))?>><?=h(_("&Übersicht[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/rohstoffe.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Rohstoffe[login/scripts/include.php|3]"))?>><?=h(_("&Rohstoffe[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/gebaeude.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Gebäude[login/scripts/include.php|3]"))?>><?=h(_("&Gebäude[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/forschung.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Forschung[login/scripts/include.php|3]"))?>><?=h(_("&Forschung[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/roboter.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Ro&boter[login/scripts/include.php|3]"))?>><?=h(_("Ro&boter[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/flotten.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("F&lotten[login/scripts/include.php|3]"))?>><?=h(_("F&lotten[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/schiffswerft.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Schiffswerft[login/scripts/include.php|3]"))?>><?=h(_("&Schiffswerft[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/verteidigung.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Verteidigung[login/scripts/include.php|3]"))?>><?=h(_("&Verteidigung[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/boerse.php') ? ' class="active"' : ''?> id="navigation-boerse"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/boerse.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Han&delsbörse[login/scripts/include.php|3]"))?>><?=h(_("Han&delsbörse[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/imperium.php') ? ' class="active"' : ''?> id="navigation-imperium"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/imperium.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("I&mperium[login/scripts/include.php|3]"))?>><?=h(_("I&mperium[login/scripts/include.php|3]"))?></a></li>
			</ul>
			<hr class="separator" />
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/karte.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Karte[login/scripts/include.php|3]"))?>><?=h(_("&Karte[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/allianz.php') ? ' class="active"' : ''?> id="navigation-allianz"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/allianz.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("All&ianz[login/scripts/include.php|3]"))?>><?=h(_("All&ianz[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/verbuendete.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("V&erbündete[login/scripts/include.php|3]"))?>><?=h(_("V&erbündete[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/highscores.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("&Highscores[login/scripts/include.php|3]"))?>><?=h(_("&Highscores[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/nachrichten.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Na&chrichten[login/scripts/include.php|3]"))?>><?=h(_("Na&chrichten[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/help/dependencies.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/help/dependencies.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Forschungsb&aum[login/scripts/include.php|3]"))?>><?=h(_("Forschungsb&aum[login/scripts/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/einstellungen.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Eins&tellungen[login/scripts/include.php|3]"))?>><?=h(_("Eins&tellungen[login/scripts/include.php|3]"))?></a></li>
<?php
			if(isset($_SESSION['admin_username']))
			{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('https://'.$_SERVER['HTTP_HOST'].h_root)?>/admin/index.php"<?=accesskey_attr(_("Adminbereich&[login/scripts/include.php|3]"))?>><?=h(_("Adminbereich&[login/scripts/include.php|3]"))?></a></li>
<?php
			}
			else
			{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/scripts/logout.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>"<?=accesskey_attr(_("Abmelden&[login/scripts/include.php|3]"))?>><?=h(_("Abmelden&[login/scripts/include.php|3]"))?></a></li>
<?php
			}
?>
			</ul>
<?php
			if($me->checkSetting('show_extern'))
			{
?>
			<hr class="separator" />
			<ul id="external-navigation">
				<li id="navigation-board"><a href="<?=htmlspecialchars(global_setting("USE_PROTOCOL"))?>://board.s-u-a.net/"<?=accesskey_attr(_("Board&[login/scripts/include.php|3]"))?>><?=h(_("Board&[login/scripts/include.php|3]"))?></a></li>
				<li id="navigation-faq"><a href="http://<?=htmlspecialchars(get_default_hostname().h_root)?>/faq.php"<?=accesskey_attr(_("FAQ&[login/scripts/include.php|3]"))?>><?=h(_("FAQ&[login/scripts/include.php|3]"))?></a></li>
				<li id="navigation-chat"><a href="http://<?=htmlspecialchars(get_default_hostname().h_root)?>/chat.php"<?=accesskey_attr(_("Chat&[login/scripts/include.php|3]"))?>><?=h(_("Chat&[login/scripts/include.php|3]"))?></a></li>
				<li id="navigation-developers"><a href="http://dev.s-u-a.net/"<?=accesskey_attr(_("Entwicklerseite&[login/scripts/include.php|3]"))?>><?=h(_("Entwicklerseite&[login/scripts/include.php|3]"))?></a></li>
			</ul>
<?php
			}
?>
		</div>

		<hr class="separator" />

		<dl id="time">
			<dt><?=h(_("Serverzeit"))?></dt>
			<dd id="time-server"><?=date(_("H:i:s"), time()+1)?></dd>
		</dl>
<?php
			if($me->checkSetting('performance') != 0)
			{
?>
		<script type="text/javascript">
			var dd_element = document.createElement('dd');
			dd_element.setAttribute('id', 'time-local');
			dd_element.appendChild(document.createTextNode(<?=_("mk2(local_time_obj.getHours())+':'+mk2(local_time_obj.getMinutes())+':'+mk2(local_time_obj.getSeconds())")?>));
			var dt_element = document.createElement('dt');
			dt_element.appendChild(document.createTextNode('<?=jsentities(_("Lokalzeit"))?>'));
			var time_element = document.getElementById('time');
			time_element.insertBefore(dd_element, time_element.firstChild);
			time_element.insertBefore(dt_element, dd_element);
			setInterval('time_up()', 1000);
		</script>
<?php
			}
?>

		<hr class="separator" />

		<ul id="gameinfo">
			<li class="username"><?=htmlspecialchars($_SESSION['username'])?></li>
			<li class="database"><?=htmlspecialchars($databases[$_SESSION['database']]['name'])?></li>
			<li class="version"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/changelog.php?<?=htmlspecialchars(urlencode(session_name()).'='.urlencode(session_id()))?>" title="<?=h(_("Changelog anzeigen&[login/scripts/include.php|4]"), false)?>"<?=accesskey_attr(_("Changelog anzeigen&[login/scripts/include.php|4]"))?>><?=sprintf(h(_("Version %s")), htmlspecialchars(VERSION))?></a></li>
<?php
			if(($rev = get_revision()) !== false)
			{
?>
			<li class="revision"><?=sprintf(h(_("Revision %s")), htmlspecialchars($rev))?></li>
<?php
			}
?>
		</ul>

		<hr class="separator" />

		<ul id="links-up-2" class="cross-navigation">
			<li><a href="#ress"><?=h(_("Zur Rohstoffanzeige&[login/scripts/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=h(_("Zum Inhalt&[login/scripts/include.php|1]"))?></a></li>
		</ul>

		<div id="css-3"></div>
		</div></div></div></div></div></div></div></div>
		<div id="css-4"></div>
<?php
			if($me->checkSetting('performance') != 0)
			{
				if($me->checkSetting('tooltips') || $me->checkSetting('shortcuts') || $me->checkSetting('ress_refresh') > 0)
				{
?>
		<script type="text/javascript">
<?php
					if($me->checkSetting('shortcuts'))
					{
?>
			get_key_elements();
<?php
					}
					if($me->checkSetting('tooltips'))
					{
?>
			load_titles();
<?php
					}
					if($me->checkSetting('ress_refresh') > 0)
					{
						$ress = $me->getRess();
						$prod = $me->getProduction();
?>
			refresh_ress(<?=$me->checkSetting('ress_refresh')*1000?>, <?=$ress[0]?>, <?=$ress[1]?>, <?=$ress[2]?>, <?=$ress[3]?>, <?=$ress[4]?>, <?=$prod[0]?>, <?=$prod[1]?>, <?=$prod[2]?>, <?=$prod[3]?>, <?=$prod[4]?>);
<?php
					}
?>
		</script>
<?php
				}

				if(global_setting("PROTOCOL") == "https")
					$analytics_prefix = "https://ssl";
				else
					$analytics_prefix = "http://www";
?>
		<script src="<?=htmlspecialchars($analytics_prefix)?>.google-analytics.com/ga.js" type="text/javascript"></script>
		<script type="text/javascript">
			var pageTracker = _gat._getTracker("UA-471643-1");
			pageTracker._initData();
			pageTracker._trackPageview();
		</script>
<?php
			}
?>
	</body>
</html>
<?php
		}
	}

	function delete_request()
	{
		$_SESSION['last_click_ignore'] = true;
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.urlencode(session_name()).'='.urlencode(session_id());
		header('Location: '.$url, true, 303);
		die(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
	}
?>
