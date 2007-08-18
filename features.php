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
<h2><?=h(_("Features"))?></h2>
<ul>
	<li><?=h(sprintf(ngettext("%d Gebäudetyp", "%d Gebäudetypen", count($items->getItemsList('gebaeude'))), count($items->getItemsList('gebaeude'))))?></li>
	<li><?=h(sprintf(ngettext("%d Forschungsmöglichkeit", "%d Forschungsmöglichkeiten", count($items->getItemsList('forschung'))), count($items->getItemsList('forschung'))))?></li>
	<li><?=h(sprintf(ngettext("%d Roboter", "%d verschiedene Roboter", count($items->getItemsList('roboter'))), count($items->getItemsList('roboter'))))?></li>
	<li><?=h(sprintf(ngettext("%d Raumschiffklasse", "%d Raumschiffklassen", count($items->getItemsList('schiffe'))), count($items->getItemsList('schiffe'))))?></li>
	<li><?=h(sprintf(ngettext("%d Verteidigungsanlage", "%d Verteidigungsanlagen", count($items->getItemsList('verteidigung'))), count($items->getItemsList('verteidigung'))))?></li>
	<li><?=h(_("Das Spiel läuft in Echtzeit, es gibt keine lästigen Eventhandler-Wartezeiten."))?></li>
	<li><?=h(_("Forschung lässt sich global oder lokal durchführen."))?></li>
	<li><?=h(_("Ausgeklügeltes Allianzsystem"))?></li>
	<li><?=h(_("Schließen Sie Bündnisse mit einzelnen Spielern."))?></li>
	<li><?=h(_("Variabler Handelskurs, der sich den Zuständen im Universum anpasst"))?></li>
	<li><?=h(_("Handelssystem: Geben Sie sich nähernden Transporten Rohstoffe mit auf den Rückweg."))?></li>
	<li><?=h(_("Komfortable Einstellungsmöglichkeiten, die das Spielen erleichtern"))?></li>
<?php
	if($show_im)
	{
?>
	<li><?=h(_("Lassen Sie sich per Instant Messenger über Ereignisse benachrichtigen."))?></li>
<?php
	}
?>
	<li><?=h(_("Völlige Ummodellierbarkeit des Designs durch Skins"))?></li>
	<li><?=h(_("Flug- und Kampferfahrungspunkte verschaffen Vorteil"))?></li>
	<li><?=h(_("HTTPS schützt vertrauliche Daten"))?></li>
	<li><?=h(sprintf(_("Geplant: %s"), _("Lassen Sie Flotten von einem Planeten zum nächsten und von dort zu einem weiteren fliegen.")))?></li>
	<li><?=h(_("Stationieren Sie Flotten bei Ihren Verbündeten, um diesen zu unterstützen."))?></li>
	<li><?=h(_("Fliegen Sie gemeinsame Angriffe mit Ihren Verbündeten."))?></li>
	<li><?=h(sprintf(ngettext("derzeit %d Spieler", "derzeit %d Spieler", $players), $players))?></li>
	<li><?=h(sprintf(ngettext("derzeit %d Allianz", "derzeit %d Allianzen", $alliances), $alliances))?></li>
<?php
	if($databases_count > 1)
	{
?>
	<li><?=h(sprintf(ngettext("derzeit %d Runde", "derzeit %d verschiedene Runden", $databases_count), $databases_count))?></li>
<?php
	}
?>
</ul>
<?php
	$i = 1;
?>
<h3><?=h(_("Screenshots"))?></h3>
<ul class="screenshots">
	<li><a href="images/screenshots/screenshot_01.png"><img src="images/screenshots/preview_01.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_02.png"><img src="images/screenshots/preview_02.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_03.png"><img src="images/screenshots/preview_03.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_04.png"><img src="images/screenshots/preview_04.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_05.png"><img src="images/screenshots/preview_05.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_06.png"><img src="images/screenshots/preview_06.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_07.png"><img src="images/screenshots/preview_07.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_08.png"><img src="images/screenshots/preview_08.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_09.png"><img src="images/screenshots/preview_09.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_10.png"><img src="images/screenshots/preview_10.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_11.png"><img src="images/screenshots/preview_11.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_12.png"><img src="images/screenshots/preview_12.jpg" alt="<?=h(sprintf(_("Screenshot %d"), $i++))?>" /></a></li>
</ul>
<?php
	home_gui::html_foot();
?>
