<?php
	require('include.php');

	home_gui::html_head();
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
	<li><?=sprintf(h(_("Melden Sie Fehler im %sBugtracker%s.", "<a href=\"https://bugs.s-u-a.net/\">", "</a>")?></li>
</ul>
<p><?=h(_("Es besteht selbstverständlich keine Garantie auf eine Antwort, normalerweise erfolgt eine solche aber innerhalb eines Tages."))?></p>
<p><strong><?=h(_("Fragen zum Spiel über andere Wege als die hier angegebenen, wie zum Beispiel über die private ICQ-Nummer des Betreibers, werden als unverschämt erachtet und höchstwahrscheinlich ignoriert."))?></strong></p>

<h3>Mitwirkende</h3>
<dl>
	<dt><a href="mailto:webmaster@s-u-a.net">Candid Dauth</a></dt>
	<dd><?=h(_("Idee, Programmierung, Projektleitung und -verwaltung"))?></dd>

	<dt><a href="mailto:rmueller@s-u-a.net">rmueller</a></dt>
	<dd><?=h(_("Finanzen, Design, Hauptseite, Items"))?></dd>

	<dt><a href="mailto:soltari@s-u-a.net">Soltari</a></dt>
	<dd><?=h(_("Moderation, Testing"))?></dd>

	<dt>Geki</dt>
	<dd><?=h(_("Design der Hauptseite"))?></dd>

	<dt><a href="mailto:barade@s-u-a.net">Barade</a></dt>
	<dd><?=h(_("Diverse Schiffsbeschreibungen"))?></dd>

	<dt><?php if(_("[translator_email]") && _("[translator_email]") != "[translator_email]"){?><a href="mailto:<?=h(_("[translator_email]"))?>"><?php }?><?=h(_("[translator_name]"))?><?php if(_("[translator_email]") && _("[translator_email]") != "[translator_email]"){?></a><?php }?></dt>
	<dd><?=h(_("[language] translation"))?></dd>
</dl>
<?php
	home_gui::html_foot();
?>
