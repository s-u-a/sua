<?php
	$include_filename = substr(__FILE__, 0, strrpos(__FILE__, '/')).'/../../engine/include.php';
	require($include_filename);

	lock_database();

	$resume = false;
	$del_email_passwd = false;
	session_start();
	header('Cache-Control: no-cache', true);
	if(!isset($_SESSION['username']) || !is_file(DB_PLAYERS.'/'.urlencode($_SESSION['username'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_SESSION['username'])))
	{
		if(isset($_POST['username']) && isset($_POST['password']))
		{
			# Anmelden

			if(!is_file(DB_PLAYERS.'/'.urlencode($_POST['username'])))
				$loggedin = false;
			else
			{
				$user_array = get_user_array($_POST['username']);
				if(md5($_POST['password']) != $user_array['password'])
					$loggedin = false;
				else
					$loggedin = true;
			}

			# Loggen nicht vergessen!
		}
		else
			$loggedin = false;

		if(!$loggedin)
		{
			if(isset($_POST['username']))
				$_SESSION['username'] = $_POST['username'];
			logfile::action('2.1');
			if(isset($_SESSION['username']))
				unset($_SESSION['username']);

			# Auf die Startseite zurueckleiten
			$url = explode('/', $_SERVER['PHP_SELF']);
			array_pop($url); array_pop($url);
			$url = 'http://'.$_SERVER['HTTP_HOST'].implode('/', $url).'/index.php';
			if(!isset($_POST['username']) || !isset($_POST['password']))
			{
				header('Location: '.$url, true, 303);
				die('Not logged in. Please <a href="'.htmlentities($url).'">relogin</a>.');
			}
			else
				die('Anmeldung fehlgeschlagen. Haben Sie sich bereits registriert und Ihren Benutzernamen und Ihr Passwort korrekt in die zugehörigen Felder über dem Anmelden-Button eingetragen? Haben Sie Groß-Klein-Schreibung sowohl beim Passwort als auch beim Benutzernamen beachtet? <a href="'.htmlentities($url).'">Probieren Sie es noch einmal.</a>');
		}
		else
		{
			# Session aktualisieren
			$_SESSION['username'] = $_POST['username'];
			$_SESSION['act_planet'] = 0;
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$resume = true;
			$del_email_passwd = true;

			logfile::action('2');
		}
	}

	$user_array = get_user_array($_SESSION['username']);

	if($_SESSION['ip'] != $_SERVER['REMOTE_ADDR'] && (!isset($user_array['ipcheck']) || $user_array['ipcheck']))
	{
		logfile::action('3.1', $_SESSION['ip']);
		if(isset($_COOKIE[SESSION_COOKIE]))
			setcookie(SESSION_COOKIE, '');
		die('Diese Session wird bereits von einer anderen IP-Adresse benutzt. Bitte <a href="'.htmlentities(h_root).'/index.php">neu anmelden</a>.');
	}

	if($del_email_passwd && isset($user_array['email_passwd']))
	{
		unset($user_array['email_passwd']);
		write_user_array();
	}

	if(isset($_SESSION['resume']) && $_SESSION['resume'])
	{
		$resume = true;
		unset($_SESSION['resume']);
	}

	# Wiederherstellen
	if($resume && isset($user_array['last_request']))
	{
		if($_SERVER['REQUEST_URI'] != $user_array['last_request'])
		{
			if(isset($user_array['last_planet']) && isset($user_array['planets'][$_SESSION['act_planet']]))
				$_SESSION['act_planet'] = $user_array['last_planet'];
			$url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].$user_array['last_request'];
			$url = explode('?', $url, 2);
			if(isset($url[1]))
				$url[1] = explode('&', $url[1]);
			else
				$url[1] = array();
			$one = false;
			foreach($url[1] as $key=>$val)
			{
				$val = explode("=", $val, 2);
				if($val[0] == SESSION_COOKIE)
				{
					$url[1][$key] = SESSION_COOKIE.'='.urlencode(session_id());
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
				$url .= SESSION_COOKIE.'='.urlencode(session_id());
			}
			header('Location: '.$url, true, 303);
			die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
		}
	}

	# Schnellklicksperre
	$now_time = array_sum(explode(' ', microtime()));

	if(!isset($_SESSION['last_click_sleep']))
		$_SESSION['last_click_sleep'] = 0;
	if(isset($_SESSION['last_click']) && (!isset($_SESSION['last_click_ignore']) || !$_SESSION['last_click_ignore']))
	{
		$last_click_diff = $now_time-$_SESSION['last_click']-pow($_SESSION['last_click_sleep'], 1.5);
		if($last_click_diff < MIN_CLICK_DIFF)
		{
			logfile::action('0', $last_click_diff);

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

	if(isset($_GET['planet']) && $_GET['planet'] != '' && isset($user_array['planets'][$_GET['planet']])) # Planeten wechseln
		$_SESSION['act_planet'] = $_GET['planet'];
	if(!isset($_SESSION['act_planet']) || !isset($user_array['planets'][$_SESSION['act_planet']]))
		$_SESSION['act_planet'] = 0;

	$this_planet = & $user_array['planets'][$_SESSION['act_planet']];

	if(!isset($_SESSION['ghost']) || !$_SESSION['ghost'])
	{
		$user_array['last_request'] = $_SERVER['REQUEST_URI'];
		$user_array['last_planet'] = $_SESSION['act_planet'];
		$user_array['last_active'] = time();
	}

	$items = get_items();

	write_user_array();

	include(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/eventhandler.php');
	eventhandler::run_eventhandler();
	$items = get_items();

	# Skins bekommen
	$skins = get_skins();

	# Version herausfinden
	$version = get_version();
	define('VERSION', $version);

	class login_gui
	{
		function html_head()
		{
			global $user_array;
			global $this_planet;
			global $ges_prod;
			global $skins;
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<title xml:lang="en">S-U-A &ndash; Stars Under Attack</title>
		<script type="text/javascript" src="<?=htmlentities(h_root.'/login/scripts.js.php')?>"></script>
		<script type="text/javascript">
			set_time_globals(<?=time()+1?>);
			var session_cookie = '<?=str_replace('\'', '\\\'', SESSION_COOKIE)?>';
			var session_id = '<?=str_replace('\'', '\\\'', session_id())?>';
		</script>
<?php
			$skin_path = '';
			if(isset($user_array['skin']))
			{
				if(isset($skins[$user_array['skin']]))
					$skin_path = h_root.'/login/style/skin.php?'.urlencode($user_array['skin']);
				else
					$skin_path = $user_array['skin'];
			}
			elseif(count($skins) > 0)
				$skin_path = h_root.'/login/style/skin.php?'.urlencode(array_shift(array_keys($skins)));

			if(trim($skin_path) != '')
			{
?>
		<link rel="stylesheet" href="<?=utf8_htmlentities($skin_path)?>" type="text/css" />
<?php
			}

			if(!isset($user_array['schrift']) || $user_array['schrift'])
			{ # Schrift ueberschreiben
?>
		<style type="text/css">
			html { font-size:9pt; font-family:Arial,Tahoma,"Adobe Helvetica",sans-serif; }
		</style>
<?php
			}

			$this_pos = explode(':', $this_planet['pos']);
			$class = 'planet-'.universe::get_planet_class($this_pos[0], $this_pos[1], $this_pos[2]);
			if(!isset($user_array['noads']) || !$user_array['noads'])
				$class .= ' mit-werbung';
			else
				$class .= ' ohne-werbung';
?>
	</head>
	<body class="<?=$class?>"><div id="content-1"><div id="content-2"><div id="content-3"><div id="content-4"><div id="content-5"><div id="content-6"><div id="content-7"><div id="content-8">
		<dl id="time">
			<dt>Serverzeit</dt>
			<dd id="time-server"><?=date('H:i:s', time()+1)?></dd>
		</dl>
		<script type="text/javascript">
			var dd_element = document.createElement('dd');
			dd_element.setAttribute('id', 'time-local');
			dd_element.appendChild(document.createTextNode(mk2(local_time_obj.getHours())+':'+mk2(local_time_obj.getMinutes())+':'+mk2(local_time_obj.getSeconds())));
			var dt_element = document.createElement('dt');
			dt_element.appendChild(document.createTextNode('Lokalzeit'));
			var time_element = document.getElementById('time');
			time_element.insertBefore(dd_element, time_element.firstChild);
			time_element.insertBefore(dt_element, dd_element);
			setInterval('time_up()', 1000);
		</script>
		<div id="navigation">
			<form action="<?=htmlentities($_SERVER['PHP_SELF'])?>" method="get" id="change-planet">
				<fieldset>
					<legend>Planet wechseln<input type="hidden" name="<?=htmlentities(SESSION_COOKIE)?>" value="<?=htmlentities(session_id())?>" /></legend>
					<select name="planet" onchange="if(this.value != <?=$_SESSION['act_planet']?>) this.form.submit();" onkeyup="if(this.value != <?=$_SESSION['act_planet']?>) this.form.submit();" accesskey="p" title="Ihre Planeten [P]">
<?php
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
?>
						<option value="<?=utf8_htmlentities($planet)?>"<?=($planet == $_SESSION['act_planet']) ? ' selected="selected"' : ''?>><?=utf8_htmlentities($user_array['planets'][$planet]['name'])?> (<?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?>)</option>
<?php
			}
?>
					</select>
					<noscript><div><button type="submit">Wechseln</button></div></noscript>
				</fieldset>
			</form>
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlentities(h_root)?>/login/index.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="ü"><kbd>Ü</kbd>bersicht</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlentities(h_root)?>/login/rohstoffe.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="r"><kbd>R</kbd>ohstoffe</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlentities(h_root)?>/login/gebaeude.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="g"><kbd>G</kbd>ebäude</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlentities(h_root)?>/login/forschung.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="f"><kbd>F</kbd>orschung</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlentities(h_root)?>/login/roboter.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="b">Ro<kbd>b</kbd>oter</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlentities(h_root)?>/login/flotten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="l">F<kbd>l</kbd>otten</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlentities(h_root)?>/login/schiffswerft.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="s"><kbd>S</kbd>chiffswerft</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlentities(h_root)?>/login/verteidigung.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="v"><kbd>V</kbd>erteidigung</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/imperium.php') ? ' class="active"' : ''?> id="navigation-imperium"><a href="<?=htmlentities(h_root)?>/login/imperium.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="m">I<kbd>m</kbd>perium</a></li>
			</ul>
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlentities(h_root)?>/login/karte.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="k"><kbd>K</kbd>arte</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/handelsrechner.php') ? ' class="active"' : ''?> id="navigation-handel"><a href="<?=htmlentities(h_root)?>/login/handelsrechner.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="d">Han<kbd>d</kbd>elsrechner</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/allianz.php') ? ' class="active"' : ''?> id="navigation-allianz"><a href="<?=htmlentities(h_root)?>/login/allianz.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="i">All<kbd>i</kbd>anz</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlentities(h_root)?>/login/verbuendete.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="e">V<kbd>e</kbd>rbündete</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlentities(h_root)?>/login/highscores.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" xml:lang="en" accesskey="h"><kbd>H</kbd>ighscores</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlentities(h_root)?>/login/nachrichten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="c">Na<kbd>c</kbd>hrichten</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/help/dependencies.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlentities(h_root)?>/login/help/dependencies.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="a">Forschungsb<kbd>a</kbd>um</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlentities(h_root)?>/login/einstellungen.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="t">Eins<kbd>t</kbd>ellungen</a></li>
<?php
			if(isset($_SESSION['admin_username']))
			{
?>
				<li id="navigation-abmelden"><a href="<?=htmlentities(h_root)?>/admin/index.php">Adminbereich</a></li>
<?php
			}
			else
			{
?>
				<li id="navigation-abmelden"><a href="<?=htmlentities(h_root)?>/login/scripts/logout.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>">Abmelden</a></li>
<?php
			}
?>
			</ul>
<?php
			if(isset($user_array['show_extern']) && $user_array['show_extern'])
			{
?>
			<ul id="external-navigation">
				<li id="navigation-board" xml:lang="en"><a href="http://board.s-u-a.net/">Board</a></li>
				<li id="navigation-faq" xml:lang="en"><a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/faq.php"><abbr title="Frequently Asked Questions">FAQ</abbr></a></li>
			</ul>
<?php
			}
?>
		</div>
		<div id="version">
			<a href="<?=htmlentities(h_root)?>/login/changelog.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Changelog anzeigen">Version <?=VERSION?></a>
		</div>
		<div id="content-9">
			<dl id="ress" class="ress">
				<dt class="ress-carbon">Carbon</dt>
				<dd class="ress-carbon" id="ress-carbon"><?=ths(utf8_htmlentities($this_planet['ress'][0]))?></dd>

				<dt class="ress-aluminium">Aluminium</dt>
				<dd class="ress-aluminium" id="ress-aluminium"><?=ths(utf8_htmlentities($this_planet['ress'][1]))?></dd>

				<dt class="ress-wolfram">Wolfram</dt>
				<dd class="ress-wolfram" id="ress-wolfram"><?=ths(utf8_htmlentities($this_planet['ress'][2]))?></dd>

				<dt class="ress-radium">Radium</dt>
				<dd class="ress-radium" id="ress-radium"><?=ths(utf8_htmlentities($this_planet['ress'][3]))?></dd>

				<dt class="ress-tritium">Tritium</dt>
				<dd class="ress-tritium" id="ress-tritium"><?=ths(utf8_htmlentities($this_planet['ress'][4]))?></dd>

				<dt class="ress-energie">Energie</dt>
				<dd class="ress-energie" id="ress-energie"><?=ths(utf8_htmlentities($ges_prod[5]))?></dd>
			</dl>
			<div id="content-10"><div id="content-11"><div id="content-12"><div id="content-13">
<?php
			if(isset($user_array['game_locked']) && $user_array['game_locked'])
			{
?>
				<p id="gesperrt-hinweis" class="spiel"><strong>Das Spiel ist derzeit gesperrt.</strong></p>
<?php
			}
			elseif(isset($user_array['locked']) && $user_array['locked'])
			{
?>
				<p id="gesperrt-hinweis" class="account"><strong>Ihr Benutzeraccount ist gesperrt.</strong></p>
<?php
			}
?>
				<h1>Planet <em><?=utf8_htmlentities($this_planet['name'])?></em> <span class="koords">(<?=utf8_htmlentities($this_planet['pos'])?>)</span></h1>
<?php
			if(isset($user_array['notify']) && $user_array['notify'])
			{
				global $message_type_names;
				
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
			
				if(isset($user_array['messages']))
				{
					foreach($user_array['messages'] as $cat=>$messages)
					{
						foreach($messages as $message_id=>$unread)
						{
							if(!is_file(DB_MESSAGES.'/'.$message_id) || !is_readable(DB_MESSAGES.'/'.$message_id))
								continue;
			
							if($unread && $cat != 8)
							{
								$ncount[$cat]++;
								$ges_ncount++;
							}
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
							$title[] = htmlentities($message_type_names[$type]).':&nbsp;'.htmlentities($count);
						if($count == $ges_ncount)
							$link .= '?type='.urlencode($type);
					}
					$title = implode('; ', $title);
					if(strpos($link, '?') === false)
						$link .= '?';
					else
						$link .= '&';
					$link .= SESSION_COOKIE.'='.urlencode(session_id());
?>
<p class="neue-nachrichten">
	<a href="<?=htmlentities($link)?>" title="<?=$title?>">Sie haben <?=htmlentities($ges_ncount)?> neue <kbd>N</kbd>achricht<?=($ges_ncount != 1) ? 'en' : ''?>.</a>
</p>
<?php
				}
			}
		}

		function html_foot()
		{
			global $user_array;
			global $this_planet;
			global $ges_prod;
?>
			</div></div>
<?php
			if(!isset($user_array['noads']) || !$user_array['noads'])
			{
?>
			<div id="werbung">
<?php
				global $DISABLE_ADS;
				if(!isset($DISABLE_ADS) || !$DISABLE_ADS)
				{
?>
				<script type="text/javascript">
					google_ad_client = "pub-2073027150149821";
					google_ad_width = 120;
					google_ad_height = 600;
					google_ad_format = "120x600_as";
					google_ad_type = "text_image";
					google_ad_channel ="";
					google_color_border = "556688";
					google_color_text = "FFFFFF";
					google_color_bg = "445577";
					google_color_link = "FFFFFF";
					google_color_url = "FFFFFF";
				</script>
				<script type="text/javascript" src="<?=htmlentities(h_root)?>/show_ads.js"></script>
<?php
				}
?>
			</div>
<?php
			}
?>
		</div></div>
		</div>
		</div></div></div></div></div></div></div></div>
<?php
			if($user_array['tooltips'] || $user_array['shortcuts'] || $user_array['ress_refresh'] > 0)
			{
?>
		<script type="text/javascript">
<?php
				if($user_array['shortcuts'])
				{
?>
			get_key_elements();
<?php
				}
				if($user_array['tooltips'])
				{
?>
			load_titles();
<?php
				}
				if($user_array['ress_refresh'] > 0 && !$user_array['umode'])
				{
?>
			refresh_ress(<?=$user_array['ress_refresh']*1000?>, <?=$this_planet['ress'][0]?>, <?=$this_planet['ress'][1]?>, <?=$this_planet['ress'][2]?>, <?=$this_planet['ress'][3]?>, <?=$this_planet['ress'][4]?>, <?=$ges_prod[0]?>, <?=$ges_prod[1]?>, <?=$ges_prod[2]?>, <?=$ges_prod[3]?>, <?=$ges_prod[4]?>);
<?php
				}
?>
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
		$url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.SESSION_COOKIE.'='.urlencode(session_id());
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}
?>