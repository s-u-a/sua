<?php
/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/
	/**
	 * Listet die Features des Spiels auf.
	 * @author Candid Dauth
	 * @package sua-homepage
	*/

	namespace sua\homepage;

	use sua\L;

	require('include.php');

	$players = 0;
	$alliances = 0;
	$databases_count = 0;
	// TODO
	/*$databases = Config::get_databases();
	foreach($databases as $dbid=>$database)
	{
		if(!$database['enabled'] || $database['dummy']) continue;
		define_globals($dbid);
		$players += User::getNumber();
		$alliances += Alliance::getNumber();
		$databases_count++;
	}

	$items = Classes::Items();

	$messengers = Config::get_messenger_info();
	$show_im = isset($messengers['jabber']);*/

	$GUI->init();
?>
<h2><?=L::h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Features")))?></h2>
<ul>
	<li><?=L::h(sprintf(ngettext("%s Gebäudetyp", "%s Gebäudetypen", count($items->getItemsList('gebaeude'))), F::ths(count($items->getItemsList('gebaeude')))))?></li>
	<li><?=L::h(sprintf(ngettext("%s Forschungsmöglichkeit", "%s Forschungsmöglichkeiten", count($items->getItemsList('forschung'))), F::ths(count($items->getItemsList('forschung')))))?></li>
	<li><?=L::h(sprintf(ngettext("%s Roboter", "%s verschiedene Roboter", count($items->getItemsList('roboter'))), F::ths(count($items->getItemsList('roboter')))))?></li>
	<li><?=L::h(sprintf(ngettext("%s Raumschiffklasse", "%s Raumschiffklassen", count($items->getItemsList('schiffe'))), F::ths(count($items->getItemsList('schiffe')))))?></li>
	<li><?=L::h(sprintf(ngettext("%s Verteidigungsanlage", "%s Verteidigungsanlagen", count($items->getItemsList('verteidigung'))), F::ths(count($items->getItemsList('verteidigung')))))?></li>
	<li><?=L::h(_("Das Spiel läuft in Echtzeit, es gibt keine lästigen Eventhandler-Wartezeiten."))?></li>
	<li><?=L::h(_("Forschung lässt sich global oder lokal durchführen."))?></li>
	<li><?=L::h(_("Ausgeklügeltes Allianzsystem"))?></li>
	<li><?=L::h(_("Schließen Sie Bündnisse mit einzelnen Spielern."))?></li>
	<li><?=L::h(_("Variabler Handelskurs, der sich den Zuständen im Universum anpasst"))?></li>
	<li><?=L::h(_("Handelssystem: Geben Sie sich nähernden Transporten Rohstoffe mit auf den Rückweg."))?></li>
	<li><?=L::h(_("Komfortable Einstellungsmöglichkeiten, die das Spielen erleichtern"))?></li>
<?php
	if($show_im)
	{
?>
	<li><?=L::h(_("Lassen Sie sich per Instant Messenger über Ereignisse benachrichtigen."))?></li>
<?php
	}
?>
	<li><?=L::h(_("Völlige Ummodellierbarkeit des Designs durch Skins"))?></li>
	<li><?=L::h(_("Flug- und Kampferfahrungspunkte verschaffen Vorteil"))?></li>
	<li><?=L::h(_("HTTPS schützt vertrauliche Daten"))?></li>
	<li><?=L::h(_("Lassen Sie Flotten von einem Planeten zum nächsten und von dort zu einem weiteren fliegen."))?></li>
	<li><?=L::h(_("Stationieren Sie Flotten bei Ihren Verbündeten, um diesen zu unterstützen."))?></li>
	<li><?=L::h(_("Fliegen Sie gemeinsame Angriffe mit Ihren Verbündeten."))?></li>
	<li><?=L::h(sprintf(ngettext("derzeit %s Spieler", "derzeit %s Spieler", $players), $players))?></li>
	<li><?=L::h(sprintf(ngettext("derzeit %s Allianz", "derzeit %s Allianzen", $alliances), $alliances))?></li>
<?php
	if($databases_count > 1)
	{
?>
	<li><?=L::h(sprintf(ngettext("derzeit %s Runde", "derzeit %s verschiedene Runden", $databases_count), F::ths($databases_count)))?></li>
<?php
	}
?>
</ul>
<?php
	$i = 1;
?>
<h3><?=L::h(_("Screenshots"))?></h3>
<ul class="screenshots">
	<li><a href="images/screenshots/screenshot_01.png"><img src="images/screenshots/preview_01.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_02.png"><img src="images/screenshots/preview_02.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_03.png"><img src="images/screenshots/preview_03.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_04.png"><img src="images/screenshots/preview_04.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_05.png"><img src="images/screenshots/preview_05.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_06.png"><img src="images/screenshots/preview_06.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_07.png"><img src="images/screenshots/preview_07.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_08.png"><img src="images/screenshots/preview_08.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_09.png"><img src="images/screenshots/preview_09.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_10.png"><img src="images/screenshots/preview_10.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_11.png"><img src="images/screenshots/preview_11.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
	<li><a href="images/screenshots/screenshot_12.png"><img src="images/screenshots/preview_12.jpg" alt="<?=L::h(sprintf(_("Screenshot %s"), $i++))?>" /></a></li>
</ul>
<?php
	$GUI->end();
?>
