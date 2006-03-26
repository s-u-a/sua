<?php
	require('scripts/include.php');

	$DISABLE_ADS = true;
	login_gui::html_head();

	$pos = $me->getPos();

	$galaxy_n = $pos[0];
	$system_n = $pos[1];
	if(isset($_GET['galaxy']))
		$galaxy_n = $_GET['galaxy'];
	if(isset($_GET['system']))
		$system_n = $_GET['system'];

	__autoload('Galaxy');
	$galaxy_count = getGalaxiesCount();

	$next_galaxy = $galaxy_n+1;
	$prev_galaxy = $galaxy_n-1;
	if($next_galaxy > $galaxy_count)
		$next_galaxy = 1;
	if($prev_galaxy < 1)
		$prev_galaxy = $galaxy_count;

	$galaxy = Classes::Galaxy($galaxy_n);
	$system_count = $galaxy->getSystemsCount();

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
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($prev_galaxy))?>&amp;system=<?=htmlentities(urlencode($system_n))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" tabindex="6" accesskey="u" title="[U]">Vorige</a></li>
			<li><input type="text" name="galaxy" value="<?=utf8_htmlentities($galaxy_n)?>" tabindex="1" /></li>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($next_galaxy))?>&amp;system=<?=htmlentities(urlencode($system_n))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" tabindex="5" accesskey="x" title="[X]">Nächste</a></li>
		</ul>
	</fieldset>
	<fieldset class="karte-systemwahl">
		<legend>System</legend>
		<ul>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;system=<?=htmlentities(urlencode($prev_system))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" tabindex="4" rel="prev" accesskey="o">V<kbd>o</kbd>rige</a></li>
			<li><input type="text" name="system" value="<?=utf8_htmlentities($system_n)?>" tabindex="2" /></li>
			<li><a href="karte.php?galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;system=<?=htmlentities(urlencode($next_system))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" tabindex="3" rel="next" accesskey="n"><kbd>N</kbd>ächste</a></li>
		</ul>
	</fieldset>
	<div class="karte-wahl-absenden">
		<button type="submit" tabindex="7" accesskey="w"><kbd>W</kbd>echseln</button><input type="hidden" name="<?=htmlentities(SESSION_COOKIE)?>" value="<?=htmlentities(session_id())?>" />
	</div>
</form>
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
	$planets_count = $galaxy->getPlanetsCount($system_n);
	for($i=1; $i <= $planets_count; $i++)
	{
		$planet = array(false, $galaxy->getPlanetOwner($system_n, $i), $galaxy->getPlanetName($system_n, $i), $galaxy->getPlanetOwnerAlliance($system_n, $i), $galaxy->getPlanetOwnerFlag($system_n, $i));
		$class = $galaxy->getPlanetClass($system_n, $i);

		$that_uname = $planet[1];

		if($planet[1])
		{
			if($that_uname == $_SESSION['username'])
				$class2 = 'eigen';
			elseif($me->isVerbuendet($that_uname))
				$class2 = 'verbuendet';
			else
				$class2 = 'fremd';
			if($planet[4] == 'U')
				$class2 .= ' urlaub';
			elseif($planet[4] == 'g')
				$class2 .= ' gesperrt';
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
			<td class="c-name"><?php if($planet[3]){?><span class="allianz<?=($planet[3] == $me->allianceTag()) ? ' verbuendet' : ''?>">[<a href="help/allianceinfo.php?alliance=<?=htmlentities(urlencode($planet[3]))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Informationen zu dieser Allianz anzeigen"><?=utf8_htmlentities($planet[3])?></a>]</span> <?php }?><?=utf8_htmlentities($planet[2])?> <span class="playername">(<a href="help/playerinfo.php?player=<?=htmlentities(urlencode($that_uname))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($that_uname)?></a><?=$planet[4] ? ' ('.htmlspecialchars($planet[4]).')' : ''?>)</span></td>
<?php
		}
		else
		{
?>
			<td class="c-name"></td>
<?php
		}

		$show_sammeln = ($me->permissionToAct() && $me->getItemLevel('S3', 'schiffe') > 0 && array_sum($truemmerfeld) > 0);
?>
			<td class="c-aktionen">
				<ul>
					<li class="c-koordinaten-verwenden"><a href="flotten.php?action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Die Koordinaten dieses Planeten ins Flottenmenü einsetzen">Koordinaten verwenden</a></li>
<?php
		if($that_uname != $_SESSION['username'])
		{
			if($planet[4] != 'U' && $me->permissionToAct() && $me->getItemLevel('S5', 'schiffe') > 0)
			{
?>
					<li class="c-spionieren"><a href="flotten.php?action=spionage&amp;action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Spionieren Sie diesen Planeten aus"<?php if($me->checkSetting('ajax')){?> onclick="return fast_action(this, 'spionage', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>);"<?php }?>>Spionieren</a></li>
<?php
			}

			if($planet[1])
			{
?>
					<li class="c-nachricht"><a href="nachrichten.php?to=<?=htmlentities(urlencode($that_uname))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Schreiben Sie diesem Spieler eine Nachricht">Nachricht</a></li>
<?php
			}

			if(!$planet[1] && $me->permissionToAct() && $me->checkPlanetCount() && $me->getItemLevel('S6', 'schiffe') > 0)
			{
?>
					<li class="c-besiedeln"><a href="flotten.php?action=besiedeln&amp;action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Schicken Sie ein Besiedelungsschiff zu diesem Planeten"<?php if($me->checkSetting('ajax')){?>  onclick="return fast_action(this, 'besiedeln', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>);"<?php }?>>Besiedeln</a></li>
<?php
			}
		}

		if($show_sammeln)
		{
?>
					<li class="c-truemmerfeld"><a href="flotten.php?action=sammeln&amp;action_galaxy=<?=htmlentities(urlencode($galaxy_n))?>&amp;action_system=<?=htmlentities(urlencode($system_n))?>&amp;action_planet=<?=htmlentities(urlencode($i))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Schicken Sie ausreichend Sammler zu diesem Trümmerfeld"<?php if($me->checkSetting('ajax')){?>  onclick="return fast_action(this, 'sammeln', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>);"<?php }?>>Trümmerfeld</a></li>
<?php
		}
?>
				</ul>
			</td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
	login_gui::html_foot();
?>