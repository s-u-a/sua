<?php
	require('include.php');

	$players = 0;
	$alliances = 0;
	$databases_count = 0;
	$databases = get_databases();
	foreach($databases as $dbid=>$database)
	{
		if(!$database['enabled'] || $database['dummy']) continue;
		define_globals($dbid);
		$highscores = new Highscores();
		$players += $highscores->getCount('users');
		$alliances += $highscores->getCount('alliances');
		$databases_count++;
		unset($highscores);
	}

	$items = Classes::Items();

	$messengers = get_messenger_info();
	$show_im = isset($messengers['jabber']);

	home_gui::html_head();
?>
<h2 xml:lang="en">Features</h2>
<ul>
	<li><?=count($items->getItemsList('gebaeude'))?> Gebäude</li>
	<li><?=count($items->getItemsList('forschung'))?> Forschungsmöglichkeiten</li>
	<li><?=count($items->getItemsList('roboter'))?> verschiedene Roboter</li>
	<li><?=count($items->getItemsList('schiffe'))?> Raumschiffklassen</li>
	<li><?=count($items->getItemsList('verteidigung'))?> Verteidigungsanlagen</li>
	<li>Das Spiel läuft in Echtzeit, es gibt keine lästigen <span xml:lang="en">Eventhandler</span>-Wartezeiten</li>
	<li>Forschung lässt sich global oder lokal durchführen</li>
	<li>Ausgeklügeltes Allianzsystem</li>
	<li>Schließen Sie Bündnisse mit einzelnen Spielern</li>
	<li>Variabler Handelskurs, der sich den Zuständen im Universum anpasst</li>
	<li>Handelssystem: Geben Sie sich nähernden Transporten Rohstoffe mit auf den Rückweg</li>
	<li>Komfortable Einstellungsmöglichkeiten, die das Spielen erleichtern</li>
<?php
	if($show_im)
	{
?>
	<li>Lassen Sie sich per <abbr title="I seek you" xml:lang="en">ICQ</abbr> oder über einen anderen <span xml:lang="en">Messenger</span> über Ereignisse benachrichtigen</li>
<?php
	}
?>
	<li>Völlige Ummodellierbarkeit des <span xml:lang="en">Design</span>s durch <span xml:lang="en">Skins</span></li>
	<li>Flug- und Kampferfahrungspunkte verschaffen Vorteil</li>
	<li><abbr title="Secure Hypertext Transfer Protocol" xml:lang="en"><span xml:lang="de">HTTPS</span></abbr> schützt vertrauliche Daten</li>
	<li>Geplant: Lassen Sie Flotten von einem Planeten zum nächsten und von dort zu einem weiteren fliegen</li>
	<li>Geplant: Stationieren Sie Flotten bei Ihren Verbündeten, um diesen zu unterstützen</li>
	<li>Geplant: Fliegen Sie gemeinsame Angriffe mit Ihren Verbündeten</li>
	<li>derzeit <?=$players?> Spieler</li>
	<li>derzeit <?=$alliances?> Allianz<?=($alliances != 1) ? 'en' : ''?></li>
<?php
	if($databases_count > 1)
	{
?>
	<li>derzeit <?=$databases_count?> verschiedene Runden</li>
<?php
	}
?>
</ul>
<h3 xml:lang="en">Screenshots</h3>
<ul class="screenshots">
	<li><a href="images/screenshots/screenshot_01.png"><img src="images/screenshots/preview_01.jpg" alt="Screenshot 1" /></a></li>
	<li><a href="images/screenshots/screenshot_02.png"><img src="images/screenshots/preview_02.jpg" alt="Screenshot 2" /></a></li>
	<li><a href="images/screenshots/screenshot_03.png"><img src="images/screenshots/preview_03.jpg" alt="Screenshot 3" /></a></li>
	<li><a href="images/screenshots/screenshot_04.png"><img src="images/screenshots/preview_04.jpg" alt="Screenshot 4" /></a></li>
	<li><a href="images/screenshots/screenshot_05.png"><img src="images/screenshots/preview_05.jpg" alt="Screenshot 5" /></a></li>
	<li><a href="images/screenshots/screenshot_06.png"><img src="images/screenshots/preview_06.jpg" alt="Screenshot 6" /></a></li>
	<li><a href="images/screenshots/screenshot_07.png"><img src="images/screenshots/preview_07.jpg" alt="Screenshot 7" /></a></li>
	<li><a href="images/screenshots/screenshot_08.png"><img src="images/screenshots/preview_08.jpg" alt="Screenshot 8" /></a></li>
	<li><a href="images/screenshots/screenshot_09.png"><img src="images/screenshots/preview_09.jpg" alt="Screenshot 9" /></a></li>
	<li><a href="images/screenshots/screenshot_10.png"><img src="images/screenshots/preview_10.jpg" alt="Screenshot 10" /></a></li>
	<li><a href="images/screenshots/screenshot_11.png"><img src="images/screenshots/preview_11.jpg" alt="Screenshot 11" /></a></li>
	<li><a href="images/screenshots/screenshot_12.png"><img src="images/screenshots/preview_12.jpg" alt="Screenshot 12" /></a></li>
</ul>
<?php
	home_gui::html_foot();
?>
