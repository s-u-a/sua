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
	 * Die Regeln im Spiel.
	 * @author Candid Dauth
	 * @package sua-frontend
	 * @subpackage home
	*/

	namespace sua::frontend;

	require('include.php');

	$gui->init();
?>
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Regeln")))?></h2>
<h3 id="p1"><?=h(sprintf(_("§ %d: %s"), 1, _("Allgemeine Nutzungsbedingungen")))?></h3>
<p id="p1-1"><?=h(sprintf(_("(%2\$d) %3\$s"), 1, 1, sprintf(_("Um an %s teilzunehmen, ist es notwendig, den folgenden Nutzungsbedingungen zuzustimmen. Diese Nutzungsbedingungen gelten für das gesamte Angebot von %1\$s, einschließlich dem Spiel selbst und dem Forum."), _("[title_abbr]"))))?></p>
<p id="p1-2"><?=h(sprintf(_("(%2\$d) %3\$s"), 1, 2, sprintf(_("%s ist ein kostenloses Spiel. Es besteht kein Anspruch auf Verfügbarkeit, Funktionalität oder Schadensersatz. Zu jedem Zeitpunkt sind sämtliche Accounts inklusive der virtuellen Ressourcen und Schiffe Eigentum des Betreibers von %1\$s."), _("[title_abbr]"))))?></p>

<h3 id="p2"><?=h(sprintf(_("§ %d: %s"), 2, _("Mitgliedschaft")))?></h3>
<p id="p2-1"><?=h(sprintf(_("(%2\$d) %3\$s"), 2, 1, sprintf(_("Die Mitgliedschaft bei %s beginnt mit der Registrierung im Spiel."), _("[title_abbr]"))))?></p>
<p id="p2-2"><?=h(sprintf(_("(%2\$d) %3\$s"), 2, 2, sprintf(_("Die Mitgliedschaft kann zu jeder Zeit von Seiten des Mitglieds beendet werden. Die Löschung der Daten kann nach der Beendigung der Mitgliedschaft aus technischen Gründen verzögert werden. Um die Löschung zu beschleunigen, hat das Mitglied die Möglichkeit, sich an einen der Administratoren zu wenden."), _("[title_abbr]"))))?></p>
<p id="p2-3"><?=h(sprintf(_("(%2\$d) %3\$s"), 2, 3, sprintf(_("Der Betreiber von %s behält sich das Recht vor, die Mitgliedschaft eines Benutzers seinerseits zu kündigen. Der Nutzer hat weder Einfluss auf die Entscheidung des Administrators hierzu, noch jedweden Rechtsanspruch auf eine Mitgliedschaft bei %1\$s."), _("[title_abbr]"))))?></p>

<h3 id="p3"><?=h(sprintf(_("§ %d: %s"), 3, _("Nutzungsbeschränkungen")))?></h3>
<p id="p3-1"><?=h(sprintf(_("(%2\$d) %3\$s"), 3, 1, sprintf(_("Es ist dem Benutzer nicht gestattet:"), _("[title_abbr]"))))?></p>
<ul>
	<li><?=h(_("Programme oder andere Personen ohne sein gleichzeitiges Zutun Aktionen im Spiel durchführen zu lassen"))?></li>
	<li><?=h(_("Sich mehrere Benutzeraccounts zu registrieren, um sich damit einen Vorteil vor anderen Spielern, zum Beispiel durch Versammlung der Rohstoffe verschiedener Accounts, zu verschaffen (so genannte „Multiaccounts“)"))?></li>
	<li><?=h(_("Etwaige Sicherheitslücken zu seinem eigenen Vorteil auszunutzen. Er sollte solche Sicherheitslücken, wenn möglich, unmittelbar nach der Entdeckung einem Administrator melden."))?></li>
	<li><?=h(_("Seinen Account ohne die ausdrückliche Genehmigung des Betreibers an andere Personen weiterzugeben"))?></li>
	<li><?=h(_("Gegenstände des Spiels (wie zum Beispiel Rohstoffe) gegen Gegenstände oder Arbeitsleistungen außerhalb des Spiels (wie zum Beispiel Geld) einzutauschen"))?></li>
	<li><?=h(_("Seinen Account mit anderen Spielern zu teilen"))?></li>
	<li><?=h(_("Gegen jegliches deutsches Recht zu verstoßen, hierauf folgt eine sofortige Sperrung."))?></li>
</ul>
<p id="p3-2"><?=h(sprintf(_("(%2\$d) %3\$s"), 3, 2, sprintf(_("Der Spieler wird ersucht, sich „fair“ gegenüber seinen Mitspielern zu verhalten, und diesen die Möglichkeit, sich im Spiel aufzubauen, nicht zu nehmen. Sollte ein Spieler sich auffällig oft nicht an diese Selbstverständlichkeit halten, so behält es sich der Betreiber vor, dem Spieler die Teilnahme an %s zu untersagen."), _("[title_abbr]"))))?></p>
<p id="p3-3"><?=h(sprintf(_("(%2\$d) %3\$s"), 3, 3, sprintf(_("Greift der Spieler einen anderen Spieler mehr als fünfmal in 24 Stunden an, so muss er mit Benachteiligungen im Spielverlauf rechnen, sofern der Verstoß den Administratoren von einer der betroffenen Parteien gemeldet wird und der Spieler nicht vorweisen kann, dass ein Abkommen in beidseitigem Einverständnis getroffen wurde, das diese Angriffe gestattet. Als Beweise für Anklage oder Einspruch in dieser Sache werden ausschließlich vorhandene (archivierte) Nachrichten im spielinternen Posteingang der betroffenen Partei akzeptiert."), _("[title_abbr]"))))?></p>
<p id="p3-4"><?=h(sprintf(_("(%2\$d) %3\$s"), 3, 4, sprintf(_("Der Betreiber behält sich aus Gründen der Fairness das Recht vor, Allianzen aufzuteilen beziehungsweise aufzulösen und Bündnisse zu kündigen."), _("[title_abbr]"))))?></p>

<h3 id="p4"><?=h(sprintf(_("§ %d: %s"), 4, _("Datenschutz")))?></h3>
<p id="p4-1"><?=h(sprintf(_("(%2\$d) %3\$s"), 4, 1, sprintf(_("Während der Mitgliedschaft speichert der Betreiber den vom Benutzer angegebenen Namen sowie die von ihm angegebenen freiwilligen Angaben (wie zum Beispiel E-Mail-Adresse) in einer für Dritte uneinsehbaren Form. Auf Wunsch des Benutzers werden diese Daten inklusive seinem Account aus dem Spiel entfernt."), _("[title_abbr]"))))?></p>
<p id="p4-2"><?=h(sprintf(_("(%2\$d) %3\$s"), 4, 2, sprintf(_("Aus Sicherheitsgründen werden sämtliche Aktionen innerhalb des Spiels zusammen mit deren aufrufender IP-Adresse und Benutzernamen protokolliert. Diese Daten werden nicht an Dritte weitergegeben und dienen ausschließlich der Aufspürung von Regelverstößen. Aus technischen Gründen ist es den Administratoren im Spiel nicht möglich, jene Protokolldateien zu modifizieren."), _("[title_abbr]"))))?></p>

<h3 id="p5"><?=h(sprintf(_("§ %d: %s"), 5, _("Haftung")))?></h3>
<p id="p5-1"><?=h(sprintf(_("(%2\$d) %3\$s"), 5, 1, sprintf(_("Der Betreiber von %s haftet in keinem Fall für eventuelle Schäden, die das Spiel beim Benutzer verursacht. Solche Schäden sind nicht im Sinne des Betreibers."), _("[title_abbr]"))))?></p>

<h3 id="p6"><?=h(sprintf(_("§ %d: %s"), 6, _("Änderung der Nutzungsbedingungen")))?></h3>
<p id="p6-1"><?=h(sprintf(_("(%2\$d) %3\$s"), 6, 1, sprintf(_("Der Betreiber von %s behält sich das Recht vor, diese Nutzungsbedingungen jederzeit zu ändern. Er verpflichtet sich jedoch, dies spätestens zwei Wochen vor Inkrafttreten der Änderungen auf der Startseite anzukündigen."), _("[title_abbr]"))))?></p>
<?php
	$gui->end();
?>
