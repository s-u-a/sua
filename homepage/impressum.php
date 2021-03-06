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
	 * Zeigt das Impressum des Spiels.
	 * @author Candid Dauth
	 * @package sua-homepage
	*/

	namespace sua\homepage;

	use \sua\L;

	require('include.php');

	$GUI->init();
?>
<h2><?=L::h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Impressum")))?></h2>
<h3><?=L::h(_("Verantwortlicher"))?></h3>
<address>
	Candid Dauth<br />
	Rotbachstr. 9<br />
	88433 Schemmerhofen-Ingerkingen<br />
	<?=L::h(_("Deutschland"))?><br />
	<a href="mailto:webmaster@s-u-a.net">webmaster@s-u-a.net</a>
</address>
<p><?=sprintf(L::h(_("%s ist ein privates Projekt, das keinerlei kommerzielle Absichten verfolgt.")), "<em>".L::h(_("[title_full]"))."</em>")?></p>

<h3><?=L::h(_("Support"))?></h3>
<p><?=L::h(_("Sollten Sie eine Frage oder Anmerkung bezüglich des Spiels haben, haben Sie folgende Möglichkeiten:"))?></p>
<ul>
	<li><?=sprintf(L::h(_("Setzen Sie sich per %sE-Mail%3\$s mit Candid Dauth in Verbindung.")), "<a href=\"mailto:webmaster@s-u-a.net\">", "webmaster@s-u-a.net", "</a>")?></li>
	<li><?=sprintf(L::h(_("Stellen Sie ihre Frage öffentlich im %sBoard%s.")), "<a href=\"".$GUI->getOption("protocol")."://board.s-u-a.net/index.php\">", "</a>")?></li>
	<li><?=sprintf(L::h(_("Fragen Sie im Entwickler-Kanal des %sChats%s nach.")), "<a href=\"http://".$_SERVER['HTTP_HOST'].$GUI->getOption("h_root")."/chat.php\">", "</a>")?></li>
	<li><?=sprintf(L::h(_("Melden Sie Fehler im %sBugtracker%s.")), "<a href=\"https://bugs.s-u-a.net/\">", "</a>")?></li>
</ul>
<p><?=L::h(_("Es besteht selbstverständlich keine Garantie auf eine Antwort, normalerweise erfolgt eine solche aber innerhalb eines Tages."))?></p>
<p><strong><?=L::h(_("Fragen zum Spiel über andere Wege als die hier angegebenen, wie zum Beispiel über die private ICQ-Nummer des Betreibers, werden als unverschämt erachtet und höchstwahrscheinlich ignoriert."))?></strong></p>

<h3>Mitwirkende</h3>
<dl>
	<dt><?=L::h(_("Idee, Programmierung, Projektleitung und -verwaltung"))?></dt>
	<dd><a href="mailto:webmaster@s-u-a.net">Candid Dauth</a></dd>

	<dt><?=L::h(_("Finanzen"))?></dt>
	<dd><a href="mailto:rmueller@s-u-a.net">rmueller</a></dd>

	<dt><?=L::h(_("Moderation"))?></dt>
	<dd>Soltari</dd>

	<dt><?=L::h(_("Items"))?></dt>
	<dd><a href="mailto:rmueller@s-u-a.net">rmueller</a>, <a href="mailto:barade@s-u-a.net">Barade</a></dd>

	<dt><?=L::h(_("Design der Hauptseite"))?></dt>
	<dd>Geki</dd>

	<dt><?=L::h(_("[language] translation"))?></dt>
	<dd><?php if(_("[translator_email]") && _("[translator_email]") != "[translator_email]"){?><a href="mailto:<?=L::h(_("[translator_email]"))?>"><?php }?><?=L::h(_("[translator_name]"))?><?php if(_("[translator_email]") && _("[translator_email]") != "[translator_email]"){?></a><?php }?></dd>

	<dt><?=L::h(_("Testing"))?></dt>
	<dd>Soltari, pyr0t0n, <a href="http://www.michael-busching.de/">Michael Busching</a></dd>
</dl>
<?php
	$GUI->end();
?>
