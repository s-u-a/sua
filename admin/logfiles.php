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
		case 'select_username': case 'select_ip': case 'select_session':
			if(!isset($_GET['which']))
			{
				# Liste ausgeben

				$list = array();
?>
<ul>
<?php
				$fh = gzopen(LOG_FILE, 'r');
				#flock($fh, LOCK_SH);

				while($line = gzgets($fh, 65536))
				{
					$line = explode("\t", trim($line));
					switch($_GET['action'])
					{
						case 'select_username': $which = $line[1]; break;
						case 'select_ip': $which = $line[2]; break;
						case 'select_session': $which = $line[3]; break;
					}
					if(!isset($list[$which]))
					{
?>
	<li><a href="logfiles.php?action=<?=htmlentities(urlencode($_GET['action']))?>&amp;which=<?=htmlentities(urlencode($which))?>"><?=utf8_htmlentities($which)?></a></li>
<?php
						$list[$which] = true;
						flush();
					}
				}

				#flock($fh, LOCK_UN);
				gzclose($fh);
?>
</ul>
<?php
				break;
			}

		case 'whole':
?>
<table class="admin-logfile" border="1">
	<thead>
		<tr>
			<th>Zeit</th>
			<th>Benutzername</th>
			<th><abbr title="Internet Protocol" xml:lang="en"><span xml:lang="de">IP</span></abbr>-Adresse</th>
			<th><span xml:lang="en">Session</span></th>
			<th>Planet</th>
			<th>Aktion</th>
		</tr>
	</thead>
	<tbody>
<?php
			$fh = gzopen(LOG_FILE, 'r');
			#flock($fh, LOCK_SH);

			while($line = gzgets($fh, 65536))
			{
				$line = explode("\t", trim($line));
				if(($_GET['action'] == 'select_username' && $line[1] != $_GET['which'])
				|| ($_GET['action'] == 'select_ip' && $line[2] != $_GET['which'])
				|| ($_GET['action'] == 'select_session' && $line[3] != $_GET['which']))
					continue;
?>
		<tr>
			<td><?=date('Y-m-d, H:i:s', array_shift($line))?></td>
			<td><?=utf8_htmlentities(array_shift($line))?></td>
			<td><?=utf8_htmlentities(array_shift($line))?></td>
			<td><?=utf8_htmlentities(array_shift($line))?></td>
			<td><?=utf8_htmlentities(array_shift($line))?></td>
			<td><?=@logfile::to_human($line)?></td>
		</tr>
<?php
				flush();
			}

			#flock($fh, LOCK_UN);
			gzclose($fh);
?>
	</tbody>
</table>
<?php
			break;

		case 'scan_multis':
			# Nach Multi-Accounts suchen

			$users = array();
			$users_min_max = array();
			$flotten = array();

			$fh = gzopen(LOG_FILE, 'r');
			#flock($fh, LOCK_SH);

			while($line = gzgets($fh, 65536))
			{
				$line = explode("\t", trim($line));

				if(!$line[1])
					continue;

				if(!isset($users[$line[1]]))
				{
					$users[$line[1]] = array($line[2]);
					$users_min_max[$line[1]] = array(array($line[0], $line[0]));
				}
				else
				{
					$ip_key = array_search($line[2], $users[$line[1]]);
					if($ip_key === false)
						$ip_key = count($users[$line[1]]);
					$users[$line[1]][$ip_key] = $line[2];
					if(isset($users_min_max[$line[1]][$ip_key]))
					{
						if($line[0] < $users_min_max[$line[1]][$ip_key][0])
							$users_min_max[$line[1]][$ip_key][0] = $line[0];
						if($line[0] > $users_min_max[$line[1]][$ip_key][1])
							$users_min_max[$line[1]][$ip_key][1] = $line[0];
					}
					else
						$users_min_max[$line[1]][$ip_key] = array($line[0], $line[0]);
				}

				if($line[5] == '12' && ($line[8] == 3 || $line[8] == 4))
					$flotten[$line[12]] = array($line[1], $line[6], $line[0]);
				elseif($line[5] == '13' && isset($flotten[$line[6]]))
					unset($flotten[$line[6]]);
			}

			#flock($fh, LOCK_EX);
			gzclose($fh);

			$multis = array();

			foreach($flotten as $flotte)
			{
				$pos = explode(':', $flotte[1]);
				list(,$owner) = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				if($owner && isset($users[$flotte[0]]) && isset($users[$owner]))
				{
					$ip_1 = array();
					$ip_2 = array();
					foreach($users[$flotte[0]] as $key=>$ip)
					{
						if($flotte[2] < $users_min_max[$flotte[0]][$key][0] && $users_min_max[$flotte[0]][$key][0]-$flotte[2] > 86400)
							continue;
						elseif($flotte[2] > $users_min_max[$flotte[0]][$key][1] && $flotte[2]-$users_min_max[$flotte[0]][$key][1] > 86400)
							continue;
						$ip_1[] = $ip;
					}
					foreach($users[$owner] as $key=>$ip)
					{
						if($flotte[2] < $users_min_max[$flotte[0]][$key][0] && $users_min_max[$flotte[0]][$key][0]-$flotte[2] > 86400)
							continue;
						elseif($flotte[2] > $users_min_max[$flotte[0]][$key][1] && $flotte[2]-$users_min_max[$flotte[0]][$key][1] > 86400)
							continue;
						$ip_2[] = $ip;
					}
					if(count(array_intersect($ip_1, $ip_2)) > 0)
					{
						if(isset($multis[$flotte[0]."\n".$owner]))
							$multis[$flotte[0]."\n".$owner]++;
						elseif(isset($multis[$owner."\n".$flotte[0]]))
							$multis[$owner."\n".$flotte[0]]++;
						else
							$multis[$flotte[0]."\n".$owner] = 1;
					}
				}
			}

			unset($users);
			unset($flotten);

			if(count($multis) == 0)
			{
?>
<p>Keine Multi-<span xml:lang="en">Accounts</span> entdeckt.</p>
<?php
			}
			else
			{
?>
<ul>
<?php
				arsort($multis, SORT_NUMERIC);

				foreach($multis as $multi=>$verdaechte)
				{
					$multi = explode("\n", $multi, 2);
?>
	<li>&bdquo;<?=utf8_htmlentities($multi[0])?>&ldquo; mit &bdquo;<?=utf8_htmlentities($multi[1])?>&ldquo; (<?=$verdaechte?> verdächtige Aktion<?=($verdaechte==1) ? '' : 'en'?>).</li>
<?php
				}
?>
</ul>
<?php
			}

			unset($multis);

			break;

		default:
?>
<p class="error">Ungültiger Aktionstyp.</p>
<?php
			break;
	}

	admin_gui::html_foot();
?>