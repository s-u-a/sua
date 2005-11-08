<?php
	require('scripts/include.php');

	login_gui::html_head();
?>
<table class="highscores">
	<thead>
		<tr>
			<th class="c-platz">Platz</th>
			<th class="c-spieler">Spieler</th>
			<th class="c-punktzahl">Punktzahl</th>
		</tr>
	</thead>
	<tbody>
<?php
	$platz = 1;

	$fh = fopen(DB_HIGHSCORES, 'r');
	flock($fh, LOCK_SH);
	while($bracket = fread($fh, 32))
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
			<td class="c-spieler"><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($info[0]))?>" title="Informationen zu diesem Spieler anzeigen" class="playername"><?=utf8_htmlentities($info[0])?></a> <a href="nachrichten.php?to=<?=htmlentities(urlencode($info[0]))?>" title="Schreiben Sie diesem Spieler eine Nachricht" class="nachricht-schreiben">[N]</a></td>
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
	login_gui::html_foot();
?>