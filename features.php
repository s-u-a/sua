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
	<li><?=count($items['gebaeude'])?> Gebäude</li>
	<li><?=count($items['forschung'])?> Forschungsmöglichkeiten</li>
	<li><?=count($items['roboter'])?> verschiedene Roboter</li>
	<li><?=count($items['schiffe'])?> Raumschiffklassen</li>
	<li><?=count($items['verteidigung'])?> Verteidigungsanlagen</li>
	<li>Das Spiel läuft in Echtzeit, es gibt keine lästigen <span xml:lang="en">Eventhandler</span>-Wartezeiten</li>
	<li>Forschung lässt sich global oder lokal durchführen</li>
	<li>Ausgeklügeltes Allianzsystem</li>
	<li>Schließen Sie Bündnisse mit einzelnen Spielern</li>
	<li>Variabler Handelskurs, der sich den Zuständen im Universum anpasst</li>
	<li>Handelssystem: Geben Sie sich nähernden Transporten Rohstoffe mit auf den Rückweg</li>
	<li>Handeln Sie auch Roboter</li>
	<li>Komfortable Einstellungsmöglichkeiten, die das Spielen erleichtern</li>
	<li>Völlige Ummodellierbarkeit des <span xml:lang="en">Design</span>s durch <span xml:lang="en">Skins</span></li>
	<li>Flug- und Kampferfahrungspunkte verschaffen Vorteil</li>
	<li><abbr title="Secure Hypertext Transfer Protocol" xml:lang="en"><span xml:lang="de">HTTPS</span></abbr> schützt vertrauliche Daten</li>
	<li>Geplant: Lassen Sie Flotten von einem Planeten zum nächsten und von dort zu einem weiteren fliegen</li>
	<li>Geplant: Stationieren Sie Flotten bei Ihren Verbündeten, um diese zu unterstützen</li>
	<li>Geplant: Fliegen Sie gemeinsame Angriffe mit Ihren Verbündeten</li>
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
<?php
	gui::html_foot();
?>