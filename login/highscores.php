<?php
	require('scripts/include.php');

	login_gui::html_head();
	
	$mode = (isset($_GET['alliances']) && $_GET['alliances']);
?>
<ul class="highscores-modi">
	<li class="c-spieler<?=$mode ? '' : ' active'?>"><a href="highscores.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Spieler</a></li>
	<li class="c-allianzen<?=$mode ? ' active' : ''?>"><a href="highscores.php?alliances=1&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Allianzen</a></li>
</ul>
<?php
	if(!$mode)
	{
		$start = 1;
		$count = highscores::get_players_count();
		if(isset($_GET['start']) && $_GET['start'] <= $count && $_GET['start'] >= 1)
			$start = (int) $_GET['start'];
		if($count > 100)
		{
?>
<ul class="highscores-seiten">
<?php
			if($start > 1)
			{
				$start_prev = $start-100;
				if($start_prev < 1) $start_prev = 1;
?>
	<li class="c-vorige"><a href="highscores.php?start=<?=htmlentities(urlencode($start_prev))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">&larr; <?=htmlentities($start_prev)?>&ndash;<?=htmlentities($start_prev+99)?></a></li>
<?php
			}
			if($start+100 <= $count)
			{
				$start_next = $start+100;
				$end_next = $start_next+100;
				if($end_next > $count) $end_next = $count;
?>
	<li class="c-naechste"><a href="highscores.php?start=<?=htmlentities(urlencode($start_next))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"><?=htmlentities($start_next)?>&ndash;<?=htmlentities($end_next)?> &rarr;</a></li>
<?php
			}
?>
</ul>
<?php
		}
?>
<table class="highscores spieler">
	<thead>
		<tr>
			<th class="c-platz">Platz</th>
			<th class="c-spieler">Spieler</th>
			<th class="c-allianz">Allianz</th>
			<th class="c-punktzahl">Punktzahl</th>
		</tr>
	</thead>
	<tbody>
<?php
		$platz = $start;
	
		$fh = fopen(DB_HIGHSCORES, 'r');
		flock($fh, LOCK_SH);
		
		fseek($fh, ($start-1)*38, SEEK_SET);
		
		while($platz < $start+100 && $bracket = fread($fh, 38))
		{
			$info = highscores::get_info($bracket);
	
			$class = 'fremd';
			if($info[0] == $_SESSION['username'])
				$class = 'eigen';
			elseif(in_array($info[0], $user_array['verbuendete']))
				$class = 'verbuendet';
?>
		<tr class="<?=$class?>">
			<th class="c-platz"><?=ths($platz)?></th>
			<td class="c-spieler"><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($info[0]))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Informationen zu diesem Spieler anzeigen" class="playername"><?=utf8_htmlentities($info[0])?></a><?php if($info[0] != $_SESSION['username']){?> <a href="nachrichten.php?to=<?=htmlentities(urlencode($info[0]))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Schreiben Sie diesem Spieler eine Nachricht" class="nachricht-schreiben">[N]</a><?php }?></td>
<?php
			if($info[0])
			{
?>
			<td class="c-allianz"><a href="help/allianceinfo.php?alliance=<?=htmlentities(urlencode($info[2]))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Informationen zu dieser Allianz anzeigen"><?=utf8_htmlentities($info[2])?></a></td>
<?php
			}
			else
			{
?>
			<td class="c-allianz keine"></td>
<?php
			}
?>
			<td class="c-punktzahl"><?=ths($info[1])?></td>
		</tr>
<?php
			$platz++;
		}
		flock($fh, LOCK_UN);
		fclose($fh);
?>
	</tbody>
</table>
<?php
	}
	else
	{
		$start = 1;
		$count = highscores_alliances::get_alliances_count();
		if(isset($_GET['start']) && $_GET['start'] <= $count && $_GET['start'] >= 1)
			$start = (int) $_GET['start'];
		if($count > 100)
		{
?>
<ul class="highscores-seiten">
<?php
			if($start > 1)
			{
				$start_prev = $start-100;
				if($start_prev < 1) $start_prev = 1;
?>
	<li class="c-vorige"><a href="highscores.php?start=<?=htmlentities(urlencode($start_prev))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">&larr; <?=htmlentities($start_prev)?>&ndash;<?=htmlentities($start_prev+99)?></a></li>
<?php
			}
			if($start+100 <= $count)
			{
				$start_next = $start+100;
				$end_next = $start_next+100;
				if($end_next > $count) $end_next = $count;
?>
	<li class="c-naechste"><a href="highscores.php?start=<?=htmlentities(urlencode($start_next))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"><?=htmlentities($start_next)?>&ndash;<?=htmlentities($end_next)?> &rarr;</a></li>
<?php
			}
?>
</ul>
<?php
		}
?>
<table class="highscores allianzen">
	<thead>
		<tr>
			<th class="c-platz">Platz</th>
			<th class="c-allianz">Allianz</th>
			<th class="c-mitglieder">Mitglieder</th>
			<th class="c-punkteschnitt"><?=($_GET['alliances']=='2') ? '<a href="highscores.php?alliances=1&amp;'.htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id())).'">Punkteschnitt</a>' : 'Punkteschnitt'?></th>
			<th class="c-gesamtpunkte"><?=($_GET['alliances']=='2') ? 'Gesamtpunkte' : '<a href="highscores.php?alliances=2&amp;'.htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id())).'">Gesamtpunkte</a>'?></th>
		</tr>
	</thead>
	<tbody>
<?php
		$platz = $start;

		if($_GET['alliances'] == '2')
			$fh = fopen(DB_HIGHSCORES_ALLIANCES2, 'r');
		else
			$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'r');
		flock($fh, LOCK_SH);
		while($platz < $start+100 && $bracket = fread($fh, 26))
		{
			$info = highscores_alliances::get_info($bracket);
	
			$class = 'fremd';
			if($info[0] == $user_array['alliance'])
				$class = 'verbuendet';
?>
		<tr class="<?=$class?>">
			<th class="c-platz"><?=ths($platz)?></th>
			<td class="c-allianz"><a href="help/allianceinfo.php?alliance=<?=htmlentities(urlencode($info[0]))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Informationen zu dieser Allianz anzeigen"><?=utf8_htmlentities($info[0])?></a></td>
			<td class="c-mitglieder"><?=ths($info[1])?></td>
			<td class="c-punkteschnitt"><?=ths($info[2])?></td>
			<td class="c-gesamtpunkte"><?=ths($info[3])?></td>
		</tr>
<?php
			$platz++;
		}
		flock($fh, LOCK_UN);
		fclose($fh);
?>
	</tbody>
</table>
<?php
	}
	
	login_gui::html_foot();
?>