<?php
	require('scripts/include.php');

	login_gui::html_head();
	
	$mode = (isset($_GET['alliances']) && $_GET['alliances']);
	
	if(!$mode)
	{
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
		$platz = 1;
	
		$fh = fopen(DB_HIGHSCORES, 'r');
		flock($fh, LOCK_SH);
		while($bracket = fread($fh, 38))
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
?>
<table class="highscores allianzen">
	<thead>
		<tr>
			<th class="c-platz">Platz</th>
			<th class="c-allianz">Allianz</th>
			<th class="c-punktzahl">Punktzahl</th>
		</tr>
	</thead>
	<tbody>
<?php
		$platz = 1;
	
		$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'r');
		flock($fh, LOCK_SH);
		while($bracket = fread($fh, 14))
		{
			$info = highscores_alliances::get_info($bracket);
	
			$class = 'fremd';
			if($info[0] == $user_array['alliance'])
				$class = 'verbuendet';
?>
		<tr class="<?=$class?>">
			<th class="c-platz"><?=ths($platz)?></th>
			<td class="c-allianz"><a href="help/allianceinfo.php?alliance=<?=htmlentities(urlencode($info[2]))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Informationen zu dieser Allianz anzeigen"><?=utf8_htmlentities($info[1])?></a></td>
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
	
	login_gui::html_foot();
?>