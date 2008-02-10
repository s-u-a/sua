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
	require('include.php');

	$gui->init();
?>
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Impressum")))?></h2>
<h3><?=h(_("Verantwortlicher"))?></h3>
<address>
	Candid Dauth<br />
	Rotbachstr. 9<br />
	88433 Schemmerhofen-Ingerkingen<br />
	<?=h(_("Deutschland"))?><br />
	<a href="mailto:webmaster@s-u-a.net">webmaster@s-u-a.net</a>
</address>
<p><?=sprintf(h(_("%s ist ein privates Projekt, das keinerlei kommerzielle Absichten verfolgt.")), "<em>".h(_("[title_full]"))."</em>")?></p>

<h3><?=h(_("Support"))?></h3>
<p><?=h(_("Sollten Sie eine Frage oder Anmerkung bezüglich des Spiels haben, haben Sie folgende Möglichkeiten:"))?></p>
<ul>
	<li><?=sprintf(h(_("Setzen Sie sich per %sE-Mail%3\$s mit Candid Dauth in Verbindung.")), "<a href=\"mailto:webmaster@s-u-a.net\">", "webmaster@s-u-a.net", "</a>")?></li>
	<li><?=sprintf(h(_("Stellen Sie ihre Frage öffentlich im %sBoard%s.")), "<a href=\"".global_setting("USE_PROTOCOL")."://board.s-u-a.net/index.php\">", "</a>")?></li>
	<li><?=sprintf(h(_("Fragen Sie im Entwickler-Kanal des %sChats%s nach.")), "<a href=\"http://".$_SERVER['HTTP_HOST'].h_root."/chat.php\">", "</a>")?></li>
	<li><?=sprintf(h(_("Melden Sie Fehler im %sBugtracker%s.")), "<a href=\"https://bugs.s-u-a.net/\">", "</a>")?></li>
</ul>
<p><?=h(_("Es besteht selbstverständlich keine Garantie auf eine Antwort, normalerweise erfolgt eine solche aber innerhalb eines Tages."))?></p>
<p><strong><?=h(_("Fragen zum Spiel über andere Wege als die hier angegebenen, wie zum Beispiel über die private ICQ-Nummer des Betreibers, werden als unverschämt erachtet und höchstwahrscheinlich ignoriert."))?></strong></p>

<h3>Mitwirkende</h3>
<dl>
	<dt><?=h(_("Idee, Programmierung, Projektleitung und -verwaltung"))?></dt>
	<dd><a href="mailto:webmaster@s-u-a.net">Candid Dauth</a></dd>

	<dt><?=h(_("Finanzen"))?></dt>
	<dd><a href="mailto:rmueller@s-u-a.net">rmueller</a></dd>

	<dt><?=h(_("Moderation"))?></dt>
	<dd>Soltari</dd>

	<dt><?=h(_("Items"))?></dt>
	<dd><a href="mailto:rmueller@s-u-a.net">rmueller</a>, <a href="mailto:barade@s-u-a.net">Barade</a></dd>

	<dt><?=h(_("Design der Hauptseite"))?></dt>
	<dd>Geki</dd>

	<dt><?=h(_("[language] translation"))?></dt>
	<dd><?php if(_("[translator_email]") && _("[translator_email]") != "[translator_email]"){?><a href="mailto:<?=h(_("[translator_email]"))?>"><?php }?><?=h(_("[translator_name]"))?><?php if(_("[translator_email]") && _("[translator_email]") != "[translator_email]"){?></a><?php }?></dd>

	<dt><?=h(_("Testing"))?></dt>
	<dd>Soltari, pyr0t0n</dd>
</dl>
<?php
	$gui->end();
?>
