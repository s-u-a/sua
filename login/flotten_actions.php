<?php
	require('scripts/include.php');
	
	if(!isset($_GET['action']))
		$_GET['action'] = false;
	
	switch($_GET['action'])
	{
		case 'handel':
			if(!isset($_GET['id'])) $flotten_id = false;
			else $flotten_id = $_GET['id'];
			
			if(!isset($user_array['flotten'][$flotten_id]) || $user_array['flotten'][$flotten_id][2] != '4' || $user_array['flotten'][$flotten_id][7])
				$flotten_id = false;
			
			if($flotten_id)
			{
				$from_pos = explode(':', $user_array['flotten'][$flotten_id][3][0]);
				$from_info = universe::get_planet_info($from_pos[0], $from_pos[1], $from_pos[2]);
				
				if(!$from_info[1])
					$flotten_id = false;
				
				$to_pos = explode(':', $user_array['flotten'][$flotten_id][3][1]);
				$to_info = universe::get_planet_info($to_pos[0], $to_pos[1], $to_pos[2]);
				
				if($to_info[1] != $_SESSION['username'])
					$flotten_id = false;
			}
			
			if(!$flotten_id)
			{
				login_gui::html_head();
?>
<p class="error">Ungültiger Transport ausgewählt.</p>
<?php
				login_gui::html_foot();
				exit();
			}
			
			$flotte = & $user_array['flotten'][$flotten_id];
			
			if($from_info[1] == $_SESSION['username'])
				$that_user_array = & $user_array;
			else
				$that_user_array = get_user_array($from_info[1]);
			
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				if($user_array['planets'][$planet]['pos'] == $flotte[3][1])
				{
					$that_planet = & $user_array['planets'][$planet];
					break;
				}
			}
			
			$bisher_handel = array(array(0,0,0,0,0), array());
			if(isset($flotte[8]))
				$bisher_handel = $flotte[8];
			
			$laderaum = 0;
			if(isset($that_user_array['forschung']['F11']))
				$laderaum = $that_user_array['forschung']['F11'];
			
			$transport = array(0, 0);
			foreach($flotte[0] as $id=>$anzahl)
			{
				if(!isset($items['schiffe'][$id]))
					continue;
				$transport[0] += $items['schiffe'][$id]['trans'][0]*$anzahl;
				$transport[1] += $items['schiffe'][$id]['trans'][1]*$anzahl;
			}
			
			$laderaum_f = pow(1.2, $laderaum);
			$transport[0] *= $laderaum_f;
			$transport[1] *= $laderaum_f;
			
			$transport[0] -= array_sum($bisher_handel[0]);
			$transport[1] -= array_sum($bisher_handel[1]);
			
			$transport[0] = floor($transport[0]);
			$transport[1] = floor($transport[1]);
			
			if(isset($_POST['beladen']))
			{
				$beladen = array(array(0,0,0,0,0), array());
				if(isset($_POST['beladen'][0]) && $transport[0] > 0)
				{
					if(isset($_POST['beladen'][0][0]) && $_POST['beladen'][0][0] > 0) $beladen[0][0] = (int) $_POST['beladen'][0][0];
					if(isset($_POST['beladen'][0][1]) && $_POST['beladen'][0][1] > 0) $beladen[0][1] = (int) $_POST['beladen'][0][1];
					if(isset($_POST['beladen'][0][2]) && $_POST['beladen'][0][2] > 0) $beladen[0][2] = (int) $_POST['beladen'][0][2];
					if(isset($_POST['beladen'][0][3]) && $_POST['beladen'][0][3] > 0) $beladen[0][3] = (int) $_POST['beladen'][0][3];
					if(isset($_POST['beladen'][0][4]) && $_POST['beladen'][0][4] > 0) $beladen[0][4] = (int) $_POST['beladen'][0][4];
					
					if($beladen[0][0] > $that_planet['ress'][0]) $beladen[0][0] = $that_planet['ress'][0];
					if($beladen[0][1] > $that_planet['ress'][1]) $beladen[0][1] = $that_planet['ress'][1];
					if($beladen[0][2] > $that_planet['ress'][2]) $beladen[0][2] = $that_planet['ress'][2];
					if($beladen[0][3] > $that_planet['ress'][3]) $beladen[0][3] = $that_planet['ress'][3];
					if($beladen[0][4] > $that_planet['ress'][4]) $beladen[0][4] = $that_planet['ress'][4];
				}
				elseif(isset($_POST['beladen'][1]) && $transport[1] > 0)
				{
					foreach($items['roboter'] as $id=>$info)
					{
						if(isset($_POST['beladen'][1][$id]) && $_POST['beladen'][1][$id] > 0 && isset($that_planet['roboter'][$id]))
						{
							$beladen[1][$id] = (int) $_POST['beladen'][1][$id];
							if($beladen[1][$id] > $that_planet['roboter'][$id])
								$beladen[1][$id] = $that_planet['roboter'][$id];
						}
					}
				}
				
				if(array_sum($beladen[0]) > 0)
				{
					$k = $transport[0]/array_sum($beladen[0]);
					if($k < 1)
					{
						$beladen[0][0] = floor($beladen[0][0]*$k);
						$beladen[0][1] = floor($beladen[0][1]*$k);
						$beladen[0][2] = floor($beladen[0][2]*$k);
						$beladen[0][3] = floor($beladen[0][3]*$k);
						$beladen[0][4] = floor($beladen[0][4]*$k);
						$uebrig = $transport[0]-array_sum($beladen[0]);
						$uebrig2 = $uebrig%5;
						$uebrig -= $uebrig2;
						$uebrig /= 5;
						$beladen[0][0] += $uebrig;
						$beladen[0][1] += $uebrig;
						$beladen[0][2] += $uebrig;
						$beladen[0][3] += $uebrig;
						$beladen[0][4] += $uebrig;
						switch($uebrig2)
						{
							case 4: $beladen[0][3]++;
							case 3: $beladen[0][2]++;
							case 2: $beladen[0][1]++;
							case 1: $beladen[0][0]++;
						}
					}
				}
				if(array_sum($beladen[1]) > 0)
				{
					$k = $transport[1]/array_sum($beladen[1]);
					if($k < 1)
					{
						foreach($beladen[1] as $id=>$anzahl)
							$beladen[1][$id] = floor($beladen[1][$id]*$k);
						$uebrig = array_sum($transport[1])-array_sum($beladen[1]);
						$roboter_anzahl = count($beladen[1]);
						$uebrig2 = $uebrig%$roboter_anzahl;
						$uebrig -= $uebrig2;
						$uebrig /= $roboter_anzahl;
						foreach($beladen[1] as $id=>$anzahl)
							$beladen[1][$id] += $uebrig;
						$roboter = array_keys($beladen[1]);
						for($i=0; $i<$uebrig2; $i++)
							$beladen[1][$roboter[$i]]++;
					}
				}
				
				$bisher_handel[0][0] += $beladen[0][0];
				$bisher_handel[0][1] += $beladen[0][1];
				$bisher_handel[0][2] += $beladen[0][2];
				$bisher_handel[0][3] += $beladen[0][3];
				$bisher_handel[0][4] += $beladen[0][4];
				
				$that_planet['ress'][0] -= $beladen[0][0];
				$that_planet['ress'][1] -= $beladen[0][1];
				$that_planet['ress'][2] -= $beladen[0][2];
				$that_planet['ress'][3] -= $beladen[0][3];
				$that_planet['ress'][4] -= $beladen[0][4];
				
				foreach($beladen[1] as $id=>$anzahl)
				{
					if(!isset($bisher_handel[1][$id]))
						$bisher_handel[1][$id] = $anzahl;
					else
						$bisher_handel[1][$id] += $anzahl;
					$that_planet['roboter'][$id] -= $anzahl;
					
					# FEHLT NOCH:
					# Bauzeiten verlaengern, wenn hier Arbeitsroboter eingelagert wurden
				}
				
				# Speichern
				$user_array['flotten'][$flotten_id][8] = $bisher_handel;
				$that_user_array['flotten'][$flotten_id][8] = $bisher_handel;
				write_user_array();
				if($from_info[1] != $_SESSION['username'])
					write_user_array($from_info[1], $that_user_array);
				
				$transport[0] -= array_sum($beladen[0]);
				$transport[1] -= array_sum($beladen[1]);
			}
			
			login_gui::html_head();
?>
<h2 id="handel">Handel</h2>
<p>Die Handelsfunktion ermöglicht es Ihnen, herannahenden Transporten Rohstoffe oder Roboter mit auf den Weg zu geben, ohne dass Sie dazu einen zusätzlichen Transport starten müssen.</p>
<h3 id="bisher-zum-handel-eingelagert">Bisher zum Handel eingelagert</h3>
<dl class="handel-ress">
	<dt class="c-carbon">Carbon</dt>
	<dd class="c-carbon"><?=ths($bisher_handel[0][0])?></dd>
	
	<dt class="c-aluminium">Aluminium</dt>
	<dd class="c-aluminium"><?=ths($bisher_handel[0][1])?></dd>
	
	<dt class="c-wolfram">Wolfram</dt>
	<dd class="c-wolfram"><?=ths($bisher_handel[0][2])?></dd>
	
	<dt class="c-radium">Radium</dt>
	<dd class="c-radium"><?=ths($bisher_handel[0][3])?></dd>
	
	<dt class="c-tritium">Tritium</dt>
	<dd class="c-tritium"><?=ths($bisher_handel[0][4])?></dd>
</dl>
<?php
			if(array_sum($bisher_handel[1]) > 0)
			{
?>
<dl class="handel-roboter">
<?php
				foreach($bisher_handel[1] as $id=>$anzahl)
				{
					if(!isset($items['roboter'][$id]))
						continue;
?>
	<dt class="c-<?=utf8_htmlentities($id)?>"><?=utf8_htmlentities($items['roboter'][$id]['name'])?></dt>
	<dd class="c-<?=utf8_htmlentities($id)?>"><?=ths($anzahl)?></dd>
<?php
				}
?>
</dl>
<?php
			}
?>
<p>Es verbleibt Platz für <?=ths($transport[0])?>&thinsp;<abbr title="Tonnen">t</abbr> Rohstoffe und <?=ths($transport[1])?>&nbsp;Roboter.</p>
<?php
			if($transport[0] > 0 || $transport[1] > 0)
			{
?>
<h3 id="zusaetzliche-rohstoffe-einlagern">Zusätzliche Rohstoffe einlagern</h3>
<form action="flotten_actions.php?action=handel&amp;id=<?=htmlentities(urlencode($_GET['id']))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="handel-einlagern-form">
<?php
				if($transport[0] > 0)
				{
?>
	<dl class="handel-einlagern-ress">
		<dt class="c-carbon"><label for="carbon-einlagern-input">Carbon</label></dt>
		<dd class="c-carbon"><input type="text" name="beladen[0][0]" id="carbon-einlagern-input" value="0" /> <span class="vorhanden">(<?=ths($that_planet['ress'][0])?>)</span></dd>
		
		<dt class="c-aluminium"><label for="aluminium-einlagern-input">Aluminium</label></dt>
		<dd class="c-aluminium"><input type="text" name="beladen[0][1]" id="aluminium-einlagern-input" value="0" /> <span class="vorhanden">(<?=ths($that_planet['ress'][1])?>)</span></dd>
		
		<dt class="c-wolfram"><label for="carbon-einlagern-input">Wolfram</label></dt>
		<dd class="c-wolfram"><input type="text" name="beladen[0][2]" id="wolfram-einlagern-input" value="0" /> <span class="vorhanden">(<?=ths($that_planet['ress'][2])?>)</span></dd>
		
		<dt class="c-radium"><label for="carbon-einlagern-input">Radium</label></dt>
		<dd class="c-radium"><input type="text" name="beladen[0][3]" id="radium-einlagern-input" value="0" /> <span class="vorhanden">(<?=ths($that_planet['ress'][3])?>)</span></dd>
		
		<dt class="c-tritium"><label for="carbon-einlagern-input">Tritium</label></dt>
		<dd class="c-tritium"><input type="text" name="beladen[0][4]" id="tritium-einlagern-input" value="0" /> <span class="vorhanden">(<?=ths($that_planet['ress'][4])?>)</span></dd>
	</dl>
<?php
				}
				if($transport[1] > 0)
				{
?>
	<dl class="handel-einlagern-roboter">
<?php
					foreach($items['roboter'] as $id=>$info)
					{
						$vorhanden = 0;
						if(isset($that_planet['roboter'][$id]))
							$vorhanden = $that_planet['roboter'][$id];
?>
		<dt class="c-<?=utf8_htmlentities($id)?>"><label for="einlagern-<?=utf8_htmlentities($id)?>-input"><?=utf8_htmlentities($info['name'])?></label></dt>
		<dd class="c-<?=utf8_htmlentities($id)?>"><input type="text" name="beladen[1][<?=utf8_htmlentities($id)?>]" id="einlagern-<?=utf8_htmlentities($id)?>-input" value="0" /> <span class="vorhanden">(<?=ths($vorhanden)?>)</span></dd>
<?php
					}
?>
	</dl>
<?php
				}
?>
	<div><button type="submit">Einlagern</button></div>
</form>
<?php
			}
			
			login_gui::html_foot();
			
			break;
		
		default:
			login_gui::html_head();
?>
<p class="error">Ungültige Aktion.</p>
<?php
			login_gui::html_foot();
			break;
	}
?>