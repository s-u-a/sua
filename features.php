<?php
	require('engine/include.php');
	
	$players = 0;
	$alliances = 0;
	$databases = get_databases();
	$first = true;
	foreach($databases as $database)
	{
		if($first)
		{
			define_globals($database[0]);
			$first = false;
		}
		$players += filesize($database[0].'/highscores')/38;
		$alliances += filesize($database[0].'/highscores_alliances')/26;
	}
	
	$items = get_items();
	
	gui::html_head();
?>
<h2 xml:lang="en">Features</h2>
<ul>
	<li><?=count($items['gebaeude'])?> Geb�ude</li>
	<li><?=count($items['forschung'])?> Forschungsm�glichkeiten</li>
	<li><?=count($items['roboter'])?> verschiedene Roboter</li>
	<li><?=count($items['schiffe'])?> Raumschiffklassen</li>
	<li><?=count($items['verteidigung'])?> Verteidigungsanlagen</li>
	<li>Das Spiel l�uft in Echtzeit, es gibt keine l�stigen <span xml:lang="en">Eventhandler</span>-Wartezeiten</li>
	<li>Forschung l�sst sich global oder lokal durchf�hren</li>
	<li>Ausgekl�geltes Allianzsystem</li>
	<li>Schlie�en Sie B�ndnisse mit einzelnen Spielern</li>
	<li>Variabler Handelskurs, der sich den Zust�nden im Universum anpasst</li>
	<li>Handelssystem: Geben Sie sich n�hernden Transporten Rohstoffe mit auf den R�ckweg</li>
	<li>Handeln Sie auch Roboter</li>
	<li>Komfortable Einstellungsm�glichkeiten, die das Spielen erleichtern</li>
	<li>V�llige Ummodellierbarkeit des <span xml:lang="en">Design</span>s durch <span xml:lang="en">Skins</span></li>
	<li>Flug- und Kampferfahrungspunkte verschaffen Vorteil</li>
	<li><abbr title="Secure Hypertext Transfer Protocol" xml:lang="en"><span xml:lang="de">HTTPS</span></abbr> sch�tzt vertrauliche Daten</li>
	<li>Geplant: Lassen Sie Flotten von einem Planeten zum n�chsten und von dort zu einem weiteren fliegen</li>
	<li>Geplant: Stationieren Sie Flotten bei Ihren Verb�ndeten, um diesen zu unterst�tzen</li>
	<li>Geplant: Fliegen Sie gemeinsame Angriffe mit Ihren Verb�ndeten</li>
	<li>derzeit <?=$players?> Spieler</li>
	<li>derzeit <?=$alliances?> Allianzen</li>
<?php
	if(count($databases) > 1)
	{
?>
	<li>derzeit <?=count($databases)?> verschiedene Runden</li>
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
</ul>
<?php
	gui::html_foot();
?>