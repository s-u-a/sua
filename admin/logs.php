<?php
	require('include.php');

	if(!$admin_array['permissions'][10])
		die('No access.');

	admin_gui::html_head();

	if(!isset($_GET['session']))
	{
		$sessions = array();
		$fh = fopen(DB_ADMIN_LOGFILE, 'r');
		fancy_flock($fh, LOCK_SH);

		while(($line = fgets($fh)) !== false)
		{
			$expl = explode("\t", preg_replace("/(\r|\n|(\r\n))$/", "", $line));
			if(count($expl) < 4) continue;

			if(!isset($sessions[$expl[0]])) $sessions[$expl[0]] = array($expl[2], false, $expl[1], ftell($fh)-strlen($line), false);

			if(!$sessions[$expl[0]][1] && $expl[3] == "0" && count($expl) >= 5) $sessions[$expl[0]][1] = $expl[4];
			$sessions[$expl[0]][4] = ftell($fh);
		}

		flock($fh, LOCK_UN);
		fclose($fh);
?>
<ul>
<?php
		foreach($sessions as $sid=>$sess)
		{
			$string = $sid.": ".$sess[0].", ".date('Y-m-d, H:i:s', $sess[2]).", ";
			if(!$sess[1]) $string .= "unbekannte Datenbank";
			elseif(isset($databases[$sess[1]])) $string .= $databases[$sess[1]][1];
			else $string .= $sess[1];
?>
	<li><a href="logs.php?<?=htmlspecialchars('session='.urlencode($sid).'&start='.urlencode($sess[3]).'&end='.urlencode($sess[4]))?>"><?=htmlspecialchars($string)?></a></li>
<?php
		}
?>
</ul>
<?php
	}
	else
	{
		protocol("10", $_GET['session']);

		$fh = fopen(DB_ADMIN_LOGFILE, 'r');
		fancy_flock($fh, LOCK_SH);

		if(isset($_GET['start'])) fseek($fh, $_GET['start']);
?>
<table border="1">
	<thead>
		<tr>
			<th>Zeit</th>
			<th>Benutzername</th>
			<th>Runde</th>
			<th>Aktion</th>
		</tr>
	</thead>
	<tbody>
<?php
		$cur_database = false;
		while(($line = fgets($fh)) !== false)
		{
			$expl = explode("\t", preg_replace("/(\r|\n|(\r\n))$/", "", $line));
			$count = count($expl);
			if($count < 4) continue;

			if($expl[3] == "0" && $count >= 5) $cur_database = $expl[4];

			if(!$cur_database) $db_string = 'Unbekannte Datenbank';
			elseif(isset($databases[$cur_database])) $db_string = $databases[$cur_database][1];
			else $db_string = $cur_database;

			if(isset($actions[$expl[3]]))
			{
				if($count == 4) $action_string = $actions[$expl[3]];
				else
				{
					$code = '$action_string = sprintf($actions[$expl[3]], $expl[2]';
					for($i=4; $i<$count; $i++) $code .= ', \''.preg_replace("/['\\\\]/", "\\\\$0", $expl[$i]).'\'';
					$code .= ');';
					eval($code);
				}
			}
			else
			{
				if($count == 4) $action_string = $expl[3];
				else
				{
					$action_string = $expl[3];
					for($i=4; $i<$count; $i++)
					{
						if($i == 4) $action_string .= ": ";
						else $action_string .= ", ";
						$action_string .= $expl[$i];
					}
				}
			}
?>
		<tr>
			<td><?=date('Y-m-d, H:i:s', $expl[1])?></td>
			<td><?=htmlspecialchars($expl[2])?></td>
			<td><?=htmlspecialchars($db_string)?></td>
			<td><?=htmlspecialchars($action_string)?></td>
		</tr>
<?php
			if(isset($_GET['end']) && ftell($fh) >= $_GET['end']) break;
		}
?>
	</tbody>
</table>
<?php
		flock($fh, LOCK_UN);
		fclose($fh);
	}

	admin_gui::html_foot();
?>