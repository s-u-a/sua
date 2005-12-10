<?php
	require('scripts/include.php');
	
	switch(isset($_GET['action']) ? $_GET['action'] : false)
	{
		case 'roboter':
		case 'flotte':
			$action = $_GET['action'];
			break;
		default:
			$action = 'ress';
			break;
	}
	
	login_gui::html_head();
?>
<h2>Imperium</h2>
<ul class="imperium-modi">
	<li<?=($action == 'ress') ? ' class="active"' : ''?>><a href="imperium.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Rohstoffe</a></li>
	<li<?=($action == 'roboter') ? ' class="active"' : ''?>><a href="imperium.php?action=roboter&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Roboter</a></li>
	<li<?=($action == 'flotte') ? ' class="active"' : ''?>><a href="imperium.php?action=flotte&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Flotten</a></li>
</ul>
<?php
	switch($action)
	{
		case 'ress':
?>
<h3 id="rohstoffe">Rohstoffe</h3>
<table class="imperium-tabelle imperium-rohstoffe">
	<caption>Vorräte</caption>
	<thead>
		<tr>
			<th class="c-planet">Planet</th>
			<th class="c-carbon">Carbon</th>
			<th class="c-aluminium">Aluminium</th>
			<th class="c-wolfram">Wolfram</th>
			<th class="c-radium">Radium</th>
			<th class="c-tritium">Tritium</th>
			<th class="c-gesamt">Gesamt</th>
		</tr>
	</thead>
	<tbody>
<?php
			$ges = array(0, 0, 0, 0, 0, 0);
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				$ges[0] += $user_array['planets'][$planet]['ress'][0];
				$ges[1] += $user_array['planets'][$planet]['ress'][1];
				$ges[2] += $user_array['planets'][$planet]['ress'][2];
				$ges[3] += $user_array['planets'][$planet]['ress'][3];
				$ges[4] += $user_array['planets'][$planet]['ress'][4];
				$this_ges = $user_array['planets'][$planet]['ress'][0]+$user_array['planets'][$planet]['ress'][1]+$user_array['planets'][$planet]['ress'][2]+$user_array['planets'][$planet]['ress'][3]+$user_array['planets'][$planet]['ress'][4];
				$ges[5] += $this_ges;
?>
		<tr>
			<th class="c-planet" title="<?=utf8_htmlentities($user_array['planets'][$planet]['name'])?>"><?=utf8_htmlentities($user_array['planets'][$planet]['pos'])?></th>
			<td class="c-carbon"><?=ths($user_array['planets'][$planet]['ress'][0])?></td>
			<td class="c-aluminium"><?=ths($user_array['planets'][$planet]['ress'][1])?></td>
			<td class="c-wolfram"><?=ths($user_array['planets'][$planet]['ress'][2])?></td>
			<td class="c-radium"><?=ths($user_array['planets'][$planet]['ress'][3])?></td>
			<td class="c-tritium"><?=ths($user_array['planets'][$planet]['ress'][4])?></td>
			<td class="c-gesamt"><?=ths($this_ges)?></td>
		</tr>
<?php
			}
?>
	</tbody>
	<tfoot>
		<tr>
			<th class="c-planet">Gesamt</th>
			<td class="c-carbon"><?=ths($ges[0])?></td>
			<td class="c-aluminium"><?=ths($ges[1])?></td>
			<td class="c-wolfram"><?=ths($ges[2])?></td>
			<td class="c-radium"><?=ths($ges[3])?></td>
			<td class="c-tritium"><?=ths($ges[4])?></td>
			<td class="c-gesamt"><?=ths($ges[5])?></td>
		</tr>
	</tfoot>
</table>
<?php
			break;
	}
	
	login_gui::html_foot();
?>