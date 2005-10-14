<?php
	require('scripts/include.php');

	login_gui::html_head();
?>
<table class="highscores">
	<thead>
		<tr>
			<th class="c-spieler">Spieler</th>
			<th class="c-punktzahl">Punktzahl</th>
		</tr>
	</thead>
	<tbody>
<?php
	$fh = fopen(DB_HIGHSCORES, 'r');
	flock($fh, LOCK_SH);
	while($bracket = fread($fh, 32))
	{
		$info = highscores::get_info($bracket);
?>
		<tr>
			<td class="c-spieler"><?=utf8_htmlentities($info[0])?> <a href="nachrichten.php?to=<?=htmlentities(urlencode($info[0]))?>" title="Schreiben Sie diesem Spieler eine Nachricht" class="nachricht-schreiben">[N]</a></td>
			<td class="c-punktzahl"><?=ths($info[1])?></td>
		</tr>
<?php
	}
	flock($fh, LOCK_UN);
	fclose($fh);
?>
	</tbody>
</table>
<?php
	login_gui::html_foot();
?>