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
	$include_filename = dirname($__FILE__).'/../engine/include.php';
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
		$url = 'http://'.$databases[$_SESSION["database"]]["hostname"].$last_request;

		$url = explode('?', $url, 2);
		if(isset($url[1]))
			$url[1] = explode('&', $url[1]);
		else
			$url[1] = array();
		$one = false;
		foreach($url[1] as $key=>$val)
		{
			$val = explode("=", $val, 2);
			if($val[0] == urlencode(session_name()))
			{
				$url[1][$key] = urlencode(session_name())."=".urlencode(session_id());
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
			$url .= urlencode(session_name())."=".urlencode(session_id());
		}
		header('Location: '.$url, true, 303);
		die(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
	}

	if(!isset($_GET['planet']) || !$me->planetExists($_GET['planet']))
	{
		$planets = $me->getPlanetsList();
		$_GET["planet"] = array_shift($planets);
	}

	$me->setActivePlanet($_GET['planet']);

	# URL-Appendix
	define_url_suffix();
	function define_url_suffix()
	{
		global $me;
		$url_suffix = array(
			session_name() => session_id(),
			"planet" => $me->getActivePlanet()
		);
		$url_suffix_g = array();
		$url_formular_g = "";
		foreach($url_suffix as $k=>$v)
		{
			$url_suffix_g[] = urlencode($k)."=".urlencode($v);
			$url_formular_g .= "<input type=\"hidden\" name=\"".htmlspecialchars($k)."\" value=\"".htmlspecialchars($v)."\" />";
		}
		global_setting("URL_SUFFIX", implode("&", $url_suffix_g));
		global_setting("URL_FORMULAR", $url_formular_g);
	}

	if((!isset($_SESSION['ghost']) || !$_SESSION['ghost']) && !defined('ignore_action'))
		$me->registerAction();

	$tabindex = 1;

	class login_gui
	{
		static function html_head($options=null)
		{
			if(!$options) $options = array();
			if(!is_array($options)) $options = array($options => true);

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
			var url_suffix = '<?=jsentities(global_setting("URL_SUFFIX"))?>';
			var database_id = '<?=str_replace('\'', '\\\'', $_SESSION['database'])?>';
			var ths_utf8 = '<?=jsentities(_("[thousand_separator]"))?>';
			var h_root = '<?=jsentities(h_root)?>';
			var last_min_chars = '<?=jsentities(global_setting("LIST_MIN_CHARS"))?>';
			var res_now = [ <?=implode(", ", $me->getRess())?> ];
			var res_ids = [ 'carbon', 'aluminium', 'wolfram', 'radium', 'tritium' ];
			var slow_terminal = <?=$me->checkSetting("performance") ? "false" : "true"?>;
		</script>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root."/login/res/javascript.js")?>"></script>
<?php
			if($me->checkSetting('performance') && $me->checkSetting('ajax'))
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
				$skin_path = h_root.'/login/res/style/'.urlencode($my_skin[0]).'/style.css';

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
			<li><a href="#inner-content"<?=accesskey_attr(_("Zum Inhalt&[login/include.php|1]"))?>><?=h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
			<li><a href="#navigation"<?=accesskey_attr(_("Zur Navigation&[login/include.php|1]"))?>><?=h(_("Zur Navigation&[login/include.php|1]"))?></a></li>
			<li><a href="#time"<?=accesskey_attr(_("Zu den Spieldaten&[login/include.php|1]"))?>><?=h(_("Zu den Spieldaten&[login/include.php|1]"))?></a></li>
		</ul>
		<hr class="separator" />
<?php
			$cur_ress = $me->getRess();
?>
		<div id="content-9" class="<?=htmlspecialchars($class)?>">
<?php
			echo format_ress($cur_ress, 3, true, true, false, null, "inline bar", "ress");
?>
			<div id="content-10" class="<?=htmlspecialchars($class)?>"><div id="content-11" class="<?=htmlspecialchars($class)?>"><div id="content-12" class="<?=htmlspecialchars($class)?>"><div id="content-13" class="<?=htmlspecialchars($class)?>">
<?php
			$locked_until = false;
			if($l = database_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="spiel error"><strong><?=h(_("Das Spiel ist derzeit gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s', $locked_until)))))?></span><?php }?></p>
<?php
			}
			elseif($me->userLocked())
			{
				$l = $me->lockedUntil();
				if($l) $locked_until = $l;
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="account error"><strong><?=h(_("Ihr Benutzeraccount ist gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s', $locked_until)))))?></span><?php }?></p>
<?php
			}
			elseif($me->umode())
			{
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="urlaub error"><strong><?=h(_("Ihr Benutzeraccount befindet sich im Urlaubsmodus."))?></strong></p>
<?php
			}
			elseif($l = fleets_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<hr class="separator" />
				<p id="gesperrt-hinweis" class="flotten error"><strong><?=h(_("Es herrscht eine Flottensperre für feindliche Flüge."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s', $locked_until)))))?></span><?php }?></p>
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

			$active_planet = $me->getActivePlanet();
			$active_planet_0 = $active_planet-1;
			if($active_planet_0 < 0) $active_planet_0 = max($me->getPlanetsList());
			$active_planet_1 = $active_planet+1;
			if($active_planet_1 > max($me->getPlanetsList())) $active_planet_1 = 0;
?>
				<hr class="separator" />
				<h1><?php if($active_planet_0 != $active_planet){?><a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)\d+/", "\${1}".$active_planet_0, global_setting("URL_SUFFIX")))?>" title="<?=h(_("Zum vorigen Planeten wechseln&[login/include.php|1]"), false)?>"<?=accesskey_attr(_("Zum vorigen Planeten wechseln&[login/include.php|1]"))?>><?=h(_("←"))?></a> <?php }?><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?><?php if($active_planet_1 != $active_planet){?> <a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)\d+/", "\${1}".$active_planet_1, global_setting("URL_SUFFIX")))?>" title="<?=h(_("Zum nächsten Planeten wechseln&[login/include.php|1]"), false)?>"<?=accesskey_attr(_("Zum nächsten Planeten wechseln&[login/include.php|1]"))?>><?=h(_("→"))?></a><?php }?></h1>
<?php
			if(isset($options["notify"]) && $options["notify"] || !isset($options["notify"]) && $me->checkSetting('notify'))
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
						if(isset($options["ignore_messages"]) && in_array($message, $options["ignore_messages"])) continue;
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
					$link .= global_setting("URL_SUFFIX");
?>
				<hr class="separator" />
				<p id="neue-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root.'/login/'.$link)?>" title="<?=$title?>"<?=accesskey_attr(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount))?>><?=h(sprintf(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount), $ges_ncount))?></a></p>
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
			if($me->checkSetting('performance') && (!isset($DISABLE_ADS) || !$DISABLE_ADS) && global_setting("PROTOCOL") == 'http') # Per https keine Werbung einblenden, da Google nur http unterstuetzt und dann eine Sicherheitswarnung kommt
			{
?>
				<div class="google-params" id="google-color-border-red"></div>
				<div class="google-params" id="google-color-border-green"></div>
				<div class="google-params" id="google-color-border-blue"></div>
				<div class="google-params" id="google-color-text-red"></div>
				<div class="google-params" id="google-color-text-green"></div>
				<div class="google-params" id="google-color-text-blue"></div>
				<div class="google-params" id="google-color-bg-red"></div>
				<div class="google-params" id="google-color-bg-green"></div>
				<div class="google-params" id="google-color-bg-blue"></div>
				<div class="google-params" id="google-color-link-red"></div>
				<div class="google-params" id="google-color-link-green"></div>
				<div class="google-params" id="google-color-link-blue"></div>
				<div class="google-params" id="google-color-url-red"></div>
				<div class="google-params" id="google-color-url-green"></div>
				<div class="google-params" id="google-color-url-blue"></div>
				<script type="text/javascript">
				// <![CDATA[
					var mkcolourpart_digits = [ "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f" ];
					function mkcolourpart(a_value)
					{
						if(a_value > 255) a_value = 255;
						if(a_value < 0) a_value = 0;
						var digit2 = a_value%16;
						var digit1 = (a_value-digit2)/16;
						return mkcolourpart_digits[digit1]+mkcolourpart_digits[digit2];
					}

					function mkcolour(a_type)
					{
						var v_red = document.getElementById("google-color-"+a_type+"-red").offsetHeight;
						var v_green = document.getElementById("google-color-"+a_type+"-green").offsetHeight;
						var v_blue = document.getElementById("google-color-"+a_type+"-blue").offsetHeight;
						if(!v_red || !v_green || !v_blue) return undefined;
						return mkcolourpart(v_red-1)+mkcolourpart(v_green-1)+mkcolourpart(v_blue-1);
					}

					google_ad_client = "pub-2662652449578921";
					google_ad_slot = "2761845030";
					google_ad_width = 120;
					google_ad_height = 600;
					var g_vars = [ "border", "text", "bg", "link", "url" ];
					for(var i=0; i<g_vars.length; i++)
						window["google_color_"+g_vars[i]] = mkcolour(g_vars[i]);
				// ]]>
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
			<li><a href="#ress"<?=accesskey_attr(_("Zur Rohstoffanzeige&[login/include.php|1]"))?>><?=h(_("Zur Rohstoffanzeige&[login/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
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
					<select name="planet" onchange="if(this.value != <?=$me->getActivePlanet()?>) this.form.submit();" onkeyup="if(this.value != <?=$me->getActivePlanet()?>) this.form.submit();"<?=accesskey_attr(_("Ihre &Planeten[login/include.php|3]"))?> title="<?=h(_("Ihre &Planeten[login/include.php|3]"), false)?>">
<?php
			$active_planet = $me->getActivePlanet();
			$planets = $me->getPlanetsList();
			foreach($planets as $planet)
			{
				$me->setActivePlanet($planet);
?>
						<option value="<?=htmlspecialchars($planet)?>"<?=($planet == $active_planet) ? ' selected="selected"' : ''?>><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?></option>
<?php
			}
			$me->setActivePlanet($active_planet);
?>
					</select>
					<noscript><div><button type="submit"><?=h(_("Wechseln"))?></button></div></noscript>
				</fieldset>
			</form>
			<hr class="separator" id="navigation-separator-1" />
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/index.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Übersicht[login/include.php|3]"))?>><?=h(_("&Übersicht[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/rohstoffe.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Rohstoffe[login/include.php|3]"))?>><?=h(_("&Rohstoffe[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/gebaeude.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Gebäude[login/include.php|3]"))?>><?=h(_("&Gebäude[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/forschung.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Forschung[login/include.php|3]"))?>><?=h(_("&Forschung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/roboter.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Ro&boter[login/include.php|3]"))?>><?=h(_("Ro&boter[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/flotten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("F&lotten[login/include.php|3]"))?>><?=h(_("F&lotten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/schiffswerft.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Schiffswerft[login/include.php|3]"))?>><?=h(_("&Schiffswerft[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/verteidigung.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Verteidigung[login/include.php|3]"))?>><?=h(_("&Verteidigung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/boerse.php') ? ' class="active"' : ''?> id="navigation-boerse"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/boerse.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Han&delsbörse[login/include.php|3]"))?>><?=h(_("Han&delsbörse[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/imperium.php') ? ' class="active"' : ''?> id="navigation-imperium"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("I&mperium[login/include.php|3]"))?>><?=h(_("I&mperium[login/include.php|3]"))?></a></li>
			</ul>
			<hr class="separator" id="navigation-separator-2" />
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/karte.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Karte[login/include.php|3]"))?>><?=h(_("&Karte[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/allianz.php') ? ' class="active"' : ''?> id="navigation-allianz"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("All&ianz[login/include.php|3]"))?>><?=h(_("All&ianz[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/verbuendete.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("V&erbündete[login/include.php|3]"))?>><?=h(_("V&erbündete[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/highscores.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Highscores[login/include.php|3]"))?>><?=h(_("&Highscores[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/nachrichten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Na&chrichten[login/include.php|3]"))?>><?=h(_("Na&chrichten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschungsbaum.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/forschungsbaum.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Forschungsb&aum[login/include.php|3]"))?>><?=h(_("Forschungsb&aum[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/einstellungen.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Eins&tellungen[login/include.php|3]"))?>><?=h(_("Eins&tellungen[login/include.php|3]"))?></a></li>
<?php
			if(isset($_SESSION['admin_username']))
			{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('https://'.$_SERVER['HTTP_HOST'].h_root)?>/admin/index.php"<?=accesskey_attr(_("Adminbereich&[login/include.php|3]"))?>><?=h(_("Adminbereich&[login/include.php|3]"))?></a></li>
<?php
			}
			else
			{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/info/logout.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Abmelden&[login/include.php|3]"))?>><?=h(_("Abmelden&[login/include.php|3]"))?></a></li>
<?php
			}
?>
			</ul>
<?php
			if($me->checkSetting('show_extern'))
			{
?>
			<hr class="separator" id="navigation-separator-3" />
			<ul id="external-navigation">
				<li id="navigation-board"><a href="<?=htmlspecialchars(global_setting("USE_PROTOCOL"))?>://board.s-u-a.net/"<?=accesskey_attr(_("Board&[login/include.php|3]"))?>><?=h(_("Board&[login/include.php|3]"))?></a></li>
				<li id="navigation-faq"><a href="http://<?=htmlspecialchars(get_default_hostname().h_root)?>/faq.php"<?=accesskey_attr(_("FAQ&[login/include.php|3]"))?>><?=h(_("FAQ&[login/include.php|3]"))?></a></li>
				<li id="navigation-chat"><a href="http://<?=htmlspecialchars(get_default_hostname().h_root)?>/chat.php"<?=accesskey_attr(_("Chat&[login/include.php|3]"))?>><?=h(_("Chat&[login/include.php|3]"))?></a></li>
				<li id="navigation-developers"><a href="http://dev.s-u-a.net/"<?=accesskey_attr(_("Entwicklerseite&[login/include.php|3]"))?>><?=h(_("Entwicklerseite&[login/include.php|3]"))?></a></li>
			</ul>
<?php
			}
?>
		</div>

		<hr class="separator" />

		<dl class="inline bar" id="time">
			<dt><?=h(_("Serverzeit"))?></dt>
			<dd id="time-server"><?=date(_("H:i:s"), time()+1)?></dd>
		</dl>
<?php
			if($me->checkSetting('performance'))
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
			<li class="username"><?=htmlspecialchars($me->getName())?></li>
			<li class="database"><?=htmlspecialchars($databases[$_SESSION['database']]['name'])?></li>
			<li class="version"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/info/changelog.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Changelog anzeigen&[login/include.php|4]"), false)?>"<?=accesskey_attr(_("Changelog anzeigen&[login/include.php|4]"))?>><?=sprintf(h(_("Version %s")), htmlspecialchars(VERSION))?></a></li>
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
			<li><a href="#ress"><?=h(_("Zur Rohstoffanzeige&[login/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
		</ul>

		<div id="css-3"></div>
		</div></div></div></div></div></div></div></div>
		<div id="css-4"></div>
<?php
			if($me->checkSetting('performance'))
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
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.global_setting("URL_SUFFIX");
		header('Location: '.$url, true, 303);
		die(sprintf(h(_("HTTP redirect: %s")), "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($url)."</a>"));
	}
?>
