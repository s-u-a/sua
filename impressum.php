<?php
	require('include.php');

	home_gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Impressum</h2>
<h3>Verantwortlicher</h3>
<address>
	Candid Dauth<br />
	Rotbachstr. 9<br />
	88433 Schemmerhofen-Ingerkingen<br />
	Deutschland<br />
	<a href="mailto:webmaster@s-u-a.net">webmaster@s-u-a.net</a>
</address>
<p><em xml:lang="en">Stars Under Attack</em> ist ein privates Projekt, das keinerlei kommerzielle Absichten verfolgt.</p>

<h3 xml:lang="en">Support</h3>
<p>Sollten Sie eine Frage oder Anmerkung bezüglich des Spiels haben, haben Sie folgende Möglichkeiten:</p>
<ul>
	<li>Setzen Sie sich <a href="mailto:webmaster@s-u-a.net">per <span xml:lang="en">E-Mail</span></a> mit Candid Dauth in Verbindung.</li>
	<li>Stellen Sie ihre Frage öffentlich im <a href="<?=global_setting("USE_PROTOCOL")?>://board.s-u-a.net/index.php" xml:lang="en">Board</a>.</li>
	<li>Fragen Sie im Entwickler-Kanal des <a href="http://<?=$_SERVER['HTTP_HOST'].h_root?>/chat.php">Chats</a> nach.</li>
	<li>Melden Sie Fehler im <a href="https://bugs.s-u-a.net/" xml:lang="en">Bugtracker</a>.</li>
</ul>
<p>Es besteht selbstverständlich keine Garantie auf eine Antwort, normalerweise erfolgt eine solche aber innerhalb eines Tages.</p>
<p><strong>Fragen zum Spiel über andere Wege als die hier angegebenen, wie zum Beispiel über die private <acronym title="I seek you" xml:lang="en">ICQ</acronym>-Nummer des Betreibers, werden als unverschämt erachtet und höchstwahrscheinlich ignoriert.</strong></p>

<h3>Mitwirkende</h3>
<dl>
	<dt><a href="mailto:webmaster@s-u-a.net">Candid Dauth</a></dt>
	<dd>Idee, Programmierung, Projektleitung und -verwaltung</dd>

	<dt><a href="mailto:rmueller@s-u-a.net">rmueller</a></dt>
	<dd>Finanzen, <span xml:lang="en">Design</span>, Hauptseite, <span xml:lang="en">Items</span></dd>

	<dt><a href="mailto:soltari@s-u-a.net">Soltari</a></dt>
	<dd>Moderation, <span xml:lang="en">Testing</span></dd>

	<dt>Remosch</dt>
	<dd>Stiftung der <span xml:lang="en">Board</span>-<span xml:lang="en">Software</span></dd>

	<dt>Geki</dt>
	<dd><span xml:lang="en">Design</span> der Hauptseite</dd>

	<dt><a href="mailto:barade@s-u-a.net">Barade</a></dt>
	<dd>Diverse Schiffsbeschreibungen</dd>
</dl>
<?php
	home_gui::html_foot();
?>
