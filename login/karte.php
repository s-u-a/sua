<?php
	require('scripts/include.php');

	login_gui::html_head();

	$pos = explode(':', $this_planet['pos']);

	$galaxy_n = $pos[0];
	$system_n = $pos[1];
	if(isset($_GET['galaxy']))
		$galaxy_n = $_GET['galaxy'];
	if(isset($_GET['system']))
		$system_n = $_GET['system'];

	$galaxy_count = universe::get_galaxies_count();

	$next_galaxy = $galaxy_n+1;
	$prev_galaxy = $galaxy_n-1;
	if($next_galaxy > $galaxy_count)
		$next_galaxy = 1;
	if($prev_galaxy < 1)
		$prev_galaxy = $galaxy_count;

	$system_count = universe::get_systems_count($galaxy_n);

	$next_system = $system_n+1;
	$prev_system = $system_n-1;
	if($next_system > $system_count)
		$next_system = 1;
	if($prev_system < 1)
		$prev_system = $system_count;
?>
<h3>Karte <span class="karte-koords">(<?=utf8_htmlentities($galaxy_n)?>:<?=utf8_htmlentities($system_n)?>)</span></h3>
<form action="karte.php" method="get" class="karte-wahl">
	<fieldset class="karte-galaxiewahl">
		<legend>Galaxie</legend>
		<ul>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($prev_galaxy))?>&amp;system=<?=htmlentities(urlencode($system_n))?>" tabindex="6" accesskey="u" title="[U]">Vorige</a></li>
			<li><input type="text" name="galaxy" value="<?=utf8_htmlentities($galaxy_n)?>" tabindex="1" /></li>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($next_galaxy))?>&amp;system=<?=htmlentities(urlencode($system_n))?>" tabindex="5" accesskey="x" title="[X]">Nächste</a></li>
		</ul>
	</fieldset>
	<fieldset class="karte-systemwahl">
		<legend>System</legend>
		<ul>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;system=<?=htmlentities(urlencode($prev_system))?>" tabindex="4" rel="prev" accesskey="o">V<kbd>o</kbd>rige</a></li>
			<li><input type="text" name="system" value="<?=utf8_htmlentities($system_n)?>" tabindex="2" /></li>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;system=<?=htmlentities(urlencode($next_system))?>" tabindex="3" rel="next" accesskey="n"><kbd>N</kbd>ächste</a></li>
		</ul>
	</fieldset>
	<div class="karte-wahl-absenden">
		<button type="submit" tabindex="7" accesskey="w"><kbd>W</kbd>echseln</button>
	</div>
</form>
<?php
	$system = universe::get_system_info($galaxy_n, $system_n);

	if(!$system)
	{
?>
<p class="error">
	Datenbankfehler.
</p>
<?php
	}
?>
<table class="karte-system">
	<thead>
		<tr>
			<th class="c-planet">Planet</th>
			<th class="c-name">Name (Eigentümer)</th>
			<th class="c-aktionen">Aktionen</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach($system as $i=>$planet)
	{
		$class = universe::get_planet_class($galaxy_n, $system_n, $i);

		$that_uname = $planet[1];
		$umode = false;
		if(substr($that_uname, -4) == ' (U)')
		{
			$that_uname = substr($that_uname, 0, -4);
			$umode = true;
		}

		if($planet[1])
		{
			if($that_uname == $_SESSION['username'])
				$class2 = 'eigen';
			elseif(in_array($that_uname, $user_array['verbuendete']))
				$class2 = 'verbuendet';
			else
				$class2 = 'fremd';
			if($umode)
				$class2 .= ' urlaub';
		}
		else
			$class2 = 'leer';
?>
		<tr class="<?=$class2?> planet_<?=$class?>">
<?php
		$truemmerfeld = truemmerfeld::get($galaxy_n, $system_n, $i);
		if($truemmerfeld !== false && array_sum($truemmerfeld) > 0)
			$tf_string = ' <abbr title="Trümmerfeld: '.ths($truemmerfeld[0]).'&nbsp;Carbon, '.ths($truemmerfeld[1]).'&nbsp;Aluminium, '.ths($truemmerfeld[2]).'&nbsp;Wolfram, '.ths($truemmerfeld[3]).'&nbsp;Radium">T</abbr>';
		else
			$tf_string = '';
?>
			<td class="c-planet"><?=utf8_htmlentities($i)?><?=$tf_string?></td>
<?php
		if($planet[1])
		{
?>
			<td class="c-name"><?=utf8_htmlentities($planet[2])?> <span class="playername">(<a href="help/playerinfo.php?player=<?=htmlentities(urlencode($that_uname))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($that_uname)?></a><?=$umode ? ' (U)' : ''?>)</span></td>
<?php
		}
		else
		{
?>
			<td class="c-name"></td>
<?php
		}

		$show_sammeln = (!$user_array['umode'] && isset($this_planet['schiffe']['S3']) && $this_planet['schiffe']['S3'] > 0 && array_sum($truemmerfeld) > 0);

		if($that_uname == $_SESSION['username'] && !$show_sammeln)
		{
?>
			<td class="c-aktionen"></td>
<?php
		}
		else
		{
?>
			<td class="c-aktionen">
				<ul>
<?php
			if($that_uname != $_SESSION['username'])
			{
				if(!$umode && !$user_array['umode'] && isset($this_planet['schiffe']['S5']) && $this_planet['schiffe']['S5'] > 0)
				{
?>
					<li class="c-spionieren"><a href="flotten.php?action=spionage&amp;action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>" title="Spionieren Sie diesen Planeten aus">Spionieren</a></li>
<?php
				}

				if($planet[1])
				{
?>
					<li class="c-nachricht"><a href="nachrichten.php?to=<?=htmlentities(urlencode($that_uname))?>" title="Schreiben Sie diesem Spieler eine Nachricht">Nachricht</a></li>
<?php
				}

				if(!$planet[1] && !$user_array['umode'] && count($user_array['planets']) < 15 && isset($this_planet['schiffe']['S6']) && $this_planet['schiffe']['S6'] > 0)
				{
?>
					<li class="c-besiedeln"><a href="flotten.php?action=besiedeln&amp;action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>" title="Schicken Sie ein Besiedelungsschiff zu diesem Planeten">Besiedeln</a></li>
<?php
				}
			}

			if($show_sammeln)
			{
?>
					<li class="c-truemmerfeld"><a href="flotten.php?action=sammeln&amp;action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>" title="Schicken Sie ausreichend Sammler zu diesem Trümmerfeld">Trümmerfeld</a></li>
<?php
			}
?>
				</ul>
			</td>
<?php
		}
?>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
	login_gui::html_foot();
?>