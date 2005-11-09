<?php
	require('../scripts/include.php');

	login_gui::html_head();

	if(!isset($_GET['player']) || !is_file(DB_PLAYERS.'/'.urlencode($_GET['player'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_GET['player'])))
	{
?>
<p class="error">
	Diesen Spieler gibt es nicht.
</p>
<?php
	}
	else
	{
		logfile::action('16', $_GET['player']);
?>
<h2>Spielerinfo <em class="playername"><?=utf8_htmlentities($_GET['player'])?></em></h2>
<?php
		$player_info = get_user_array($_GET['player']);
?>
<?php
		if(!isset($player_info['punkte'][0])) $player_info['punkte'][0] = 0;
		if(!isset($player_info['punkte'][1])) $player_info['punkte'][1] = 0;
		if(!isset($player_info['punkte'][2])) $player_info['punkte'][2] = 0;
		if(!isset($player_info['punkte'][3])) $player_info['punkte'][3] = 0;
		if(!isset($player_info['punkte'][4])) $player_info['punkte'][4] = 0;
		if(!isset($player_info['punkte'][5])) $player_info['punkte'][5] = 0;
		if(!isset($player_info['punkte'][6])) $player_info['punkte'][6] = 0;
		if(!isset($player_info['punkte'][7])) $player_info['punkte'][7] = 0;
		if(!isset($player_info['punkte'][8])) $player_info['punkte'][8] = 0;
		if(!isset($player_info['punkte'][9])) $player_info['punkte'][9] = 0;
		if(!isset($player_info['punkte'][10])) $player_info['punkte'][10] = 0;
		if(!isset($player_info['punkte'][11])) $player_info['punkte'][11] = 0;
?>
<h3 id="punkte">Punkte</h2>
<dl class="punkte">
	<dt class="c-gebaeude">Gebäude</dt>
	<dd class="c-gebaeude"><?=ths($player_info['punkte'][0])?></dd>

	<dt class="c-forschung">Forschung</dt>
	<dd class="c-forschung"><?=ths($player_info['punkte'][1])?></dd>

	<dt class="c-roboter">Roboter</dt>
	<dd class="c-roboter"><?=ths($player_info['punkte'][2])?></dd>

	<dt class="c-flotte">Flotte</dt>
	<dd class="c-flotte"><?=ths($player_info['punkte'][3])?></dd>

	<dt class="c-verteidigung">Verteidigung</dt>
	<dd class="c-verteidigung"><?=ths($player_info['punkte'][4])?></dd>

	<dt class="c-flugerfahrung">Flugerfahrung</dt>
	<dd class="c-flugerfahrung"><?=ths($player_info['punkte'][5])?></dd>

	<dt class="c-kampferfahrung">Kampferfahrung</dt>
	<dd class="c-kampferfahrung"><?=ths($player_info['punkte'][6])?></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($player_info['punkte'][0]+$player_info['punkte'][1]+$player_info['punkte'][2]+$player_info['punkte'][3]+$player_info['punkte'][4]+$player_info['punkte'][5]+$player_info['punkte'][6])?> <span class="platz">(Platz&nbsp;<?=ths($player_info['punkte'][12])?>)</span></dd>
</dl>
<?php
		if($_GET['player'] == $_SESSION['username'] || in_array($_GET['player'], $user_array['verbuendete']))
		{
?>
<h3 id="ausgegebene-rohstoffe">Ausgegebene Rohstoffe</h3>
<dl class="punkte">
	<dt class="c-carbon">Carbon</dt>
	<dd class="c-carbon"><?=ths($player_info['punkte'][7])?></dd>

	<dt class="c-eisenerz">Aluminium</dt>
	<dd class="c-eisenerz"><?=ths($player_info['punkte'][8])?></dd>

	<dt class="c-wolfram">Wolfram</dt>
	<dd class="c-wolfram"><?=ths($player_info['punkte'][9])?></dd>

	<dt class="c-radium">Radium</dt>
	<dd class="c-radium"><?=ths($player_info['punkte'][10])?></dd>

	<dt class="c-tritium">Tritium</dt>
	<dd class="c-tritium"><?=ths($player_info['punkte'][11])?></dd>

	<dt class="c-gesamt">Gesamt</dt>
	<dd class="c-gesamt"><?=ths($player_info['punkte'][7]+$player_info['punkte'][8]+$player_info['punkte'][9]+$player_info['punkte'][10]+$player_info['punkte'][11])?></dd>
</dl>
<?php
		}
?>
<h3 id="benutzerbeschreibung">Benutzerbeschreibung</h3>
<div class="benutzerbeschreibung">
<?php
		function repl_nl($nls)
		{
			$len = strlen($nls);
			if($len == 1)
				return "<br />\n\t\t";
			elseif($len == 2)
				return "\n\t</p>\n\t<p>\n\t\t";
			elseif($len > 2)
				return "\n\t</p>\n\t".str_repeat('<br />', $len-2)."\n\t<p>\n\t";
		}

		echo "\t<p>\n\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', utf8_htmlentities($player_info['description']))."\n\t</p>\n";
?>
</div>
<h3 id="buendnisse">Bündnisse</h3>
<?php
		if(count($player_info['verbuendete']) <= 0)
		{
?>
<p>
	Dieser Benutzer ist derzeit in keinem Bündnis.
</p>
<?php
		}
		else
		{
?>
<ul class="buendnis-informationen">
<?php
			foreach($player_info['verbuendete'] as $verbuendeter)
			{
?>
	<li><a href="playerinfo.php?player=<?=htmlentities(urlencode($verbuendeter))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($verbuendeter)?></a></li>
<?php
			}
?>
</ul>
<?php
		}
?>
<h3 id="daten">Daten</h3>
<dl class="daten">
	<dt class="c-letzte-aktivitaet">Letzte Aktivität</dt>
<?php
		if(isset($player_info['last_active']))
		{
?>
	<dd class="c-letzte-aktivitaet"><?=date('H:i:s, Y-m-d', $player_info['last_active'])?> (Serverzeit)</dd>
<?php
		}
		else
		{
?>
	<dd class="c-letzte-aktivitaet nie">Nie</dd>
<?php
		}
?>

	<dt class="c-registrierung">Registrierung</dt>
<?php
		if(isset($player_info['registration']))
		{
?>
	<dd class="c-registrierung"><?=date('H:i:s, Y-m-d', $player_info['registration'])?> (Serverzeit)</dd>
<?php
		}
		else
		{
?>
	<dd class="c-registriergung unbekannt">Unbekannt</dd>
<?php
		}
?>
</dl>
<?php
		if($_GET['player'] == $_SESSION['username'] || in_array($_GET['player'], $user_array['verbuendete']))
		{
?>
<h3 id="planeten">Planeten</h3>
<ul class="playerinfo-planeten">
<?php
			$planets = array_keys($player_info['planets']);
			foreach($planets as $planet)
			{
				$pos = explode(':', $player_info['planets'][$planet]['pos']);
?>
	<li><?=utf8_htmlentities($player_info['planets'][$planet]['name'])?> <span class="koords">(<a href="../karte.php?galaxy=<?=htmlentities(urlencode($pos[0]))?>&amp;system=<?=htmlentities(urlencode($pos[1]))?>" title="Jenes Sonnensystem in der Karte ansehen"><?=utf8_htmlentities($player_info['planets'][$planet]['pos'])?></a>)</span></li>
<?php
			}
?>
</ul>
<?php
		}

		if($_GET['player'] != $_SESSION['username'])
		{
?>
<h3 id="nachricht">Nachricht</h3>
<form action="../nachrichten.php?to=" method="post" class="playerinfo-nachricht">
	<dl>
		<dt class="c-betreff"><label for="betreff-input">Betreff</label></dt>
		<dd class="c-betreff"><input type="text" id="betreff-input" name="betreff" maxlength="30" tabindex="1" /></dd>

		<dt class="c-inhalt"><label for="inhalt-input">Inhalt</label></dt>
		<dd class="c-inhalt"><textarea id="inhalt-input" name="inhalt" cols="50" rows="10" tabindex="2"></textarea></dd>
	</dl>
	<div><button type="submit" accesskey="n" tabindex="3"><kbd>N</kbd>achricht absenden</button><input type="hidden" name="empfaenger" value="<?=utf8_htmlentities($_GET['player'])?>" /></div>
</form>
<?php
		}
	}
	login_gui::html_foot();
?>