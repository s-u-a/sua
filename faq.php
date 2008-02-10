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
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("FAQ")))?></h2>
<p><?=sprintf(h(_("Hier eine Liste häufig gestellter Fragen. Sollte die Frage, die Sie suchen, nicht dabei sein, schreiben Sie einfach ins %sBoard%s.")), "<a href=\"".global_setting("USE_PROTOCOL")."://board.s-u-a.net/index.php\">", "</a>")?></p>
<ol id="question-list">
	<li><a href="#requirements"><?=h(sprintf(_("Was brauche ich, um %s zu spielen?"), _("title_abbr")))?></a></li>
	<li><a href="#register"><?=h(_("Wie kann ich mich anmelden?"))?></a></li>
	<li><a href="#certification"><?=h(_("Warum bekomme ich immer eine Meldung „Ungültiges Zertifikat“?"))?></a></li>
	<li><a href="#scores"><?=h(_("Wie bekomme ich Punkte?"))?></a></li>
	<li><a href="#noobs"><?=h(_("Gibt es einen Anfängerschutz?"))?></a></li>
	<li><a href="#planets"><?=h(_("Kann man mehrere Planeten haben?"))?></a></li>
	<li><a href="#umode"><?=h(_("Was bewirkt der Urlaubsmodus?"))?></a></li>
	<li><a href="#times"><?=h(_("Wie kann man die Bauzeiten verkürzen?"))?></a></li>
	<li><a href="#avatars"><?=h(_("Wie füge ich ein Bild in meine Allianz- oder Benutzerbeschreibung ein?"))?></a></li>
	<li><a href="#time"><?=h(_("Was bedeuten Server- und Lokalzeit?"))?></a></li>
	<li><a href="#research"><?=h(_("Wo ist der Unterschied zwischen lokaler und globaler Forschung?"))?></a></li>
	<li><a href="#robots"><?=h(_("Wie funktionieren die Roboter?"))?></a></li>
	<li><a href="#fleet"><?=h(_("Wieviele Flotten kann ich gleichzeitig verschicken?"))?></a></li>
	<li><a href="#alliance"><?=h(_("Was bringt mir ein Bündnis?"))?></a></li>
	<li><a href="#resources"><?=h(_("Die Rohstoffanzeige passt nicht in mein Browserfenster und Querscrollen ist nicht möglich."))?></a></li>
	<li><a href="#administrators"><?=h(_("Wie kann ich die Administratoren erreichen?"))?></a></li>
	<li><a href="#name"><?=h(_("Kann ich meinen Namen ändern?"))?></a></li>
	<li><a href="#universe"><?=h(_("Wie viele Universen gibt es?"))?></a></li>
	<li><a href="#expand"><?=h(_("Bei einem Gebäude/Forschung fehlt der Ausbauknopf oder bei meinen Robotern/Schiffen/Verteidigungsanlagen fehlt das Ausbaufeld."))?></a></li>
	<li><a href="#distance"><?=h(_("Wie berechnet sich die Entfernung zwischen zwei Planeten?"))?></a></li>
	<li><a href="#download"><?=h(_("Wie kann ich mir das Spiel herunterladen?"))?></a></li>
</ol>

<div class="faq" id="requirements">
	<h3><?=h(sprintf(_("Was brauche ich, um %s zu spielen?"), _("title_abbr")))?></h3>
	<p><?=h(_("Um das Spiel zu spielen, benötigen Sie lediglich einen Internetbrowser, der HTML 4 unterstützt. Sie können also quasi jeden Browser verwenden, vom normalen Firefox über den Terminalbrowser bis hin zum Vorleseprogramm für Blinde."))?></p>
	<p><?=h(_("Um das Design in seinen vollen Zügen zu genießen, benötigen Sie einen Browser aus der folgenden Liste."))?></p>
	<ul>
		<li><?=h(_("Mozilla Firefox (oder gleichwertige Gecko-Browser, wie Netscape Navigator oder Mozilla Suite)"))?></li>
		<li><?=h(_("Konqueror (in einer neueren Version, bestenfalls 3.2 oder neuer)"))?></li>
		<li><?=h(_("Safari (siehe Konqueror)"))?></li>
		<li><?=h(_("Opera (ab Version 7)"))?></li>
		<li><?=h(_("Internet Explorer (Version 5.5 oder 6)"))?></li>
	</ul>
	<p><?=h(_("Für einige praktische Features können Sie außerdem JavaScript aktivieren."))?></p>
	<p><?=h(_("Wir möchten Sie wärmstens darum bitten, nicht den Internet Explorer zu verwenden, sofern Sie die Möglichkeit dazu haben, da dieser sich in zu geringem Maße an Internet-Standards hält und dadurch für ein unnötig hohes zusätzliches Arbeitsaufkommen bei uns sorgt."))?></p>
</div>

<div class="faq" id="register">
	<h3><?=h(_("Wie kann ich mich anmelden?"))?></h3>
	<p><?=h(_("Bereits registrierte Benutzer müssen das Formular, das auf der rechten Seite zu sehen ist, einfach nur ausfüllen (mit Benutzernamen und Passwort) und auf „Anmelden“ klicken."))?></p>
	<p><?=sprintf(h(_("Noch nicht registrierte Benutzer wählen bitte in der Hauptnavigation „%sRegistrieren%s“ aus, füllen das dortige Formular aus und klicken auf „registrieren“. Danach können Sie sich, wie oben beschrieben, anmelden.")), "<a href=\"register.php\">", "</a>")?></p>
</div>

<div class="faq" id="certification">
	<h3><?=h(_("Warum bekomme ich immer eine Meldung „Ungültiges Zertifikat“?"))?></h3>
	<p><?=sprintf(h(_("Diese Meldung ist nichts Schlimmes, Sie erhalten sie, weil das Zertifikat der sicheren HTTP-Verbindung nicht für teures Geld bei irgendeinem Anbieter signiert wurde, sondern nur von der %sCAcert%s-Organisation unterzeichnet ist, welche in vielen Browsern nicht in der Liste der vertrauenswürdigen Firmen aufgeführt ist. Sie können die Warnung jedenfalls getrost ignorieren.")), "<a href=\"http://cacert.org/\">", "</a>")?></p>
	<p><?=h(_("Falls Sie sich von der Meldung gestört fühlen, haben Sie folgende Möglichkeiten, sie zu vermeiden."))?></p>
	<ul>
		<li><?=h(_("Sie verzichten auf eine verschlüsselte Verbindung, indem Sie rechts auf der Seite SSL abschalten. Dies ist aber weniger sicher und ist deshalb nicht empfohlen."))?></li>
		<li><?=sprintf(h(_("Sie laden sich das Zertifikat herunter und installieren es bei sich. Keine Sorge, das Zertifikat ist nur eine kleine Textdatei, die auf Ihrem Computer unter den etlichen anderen Zertifikaten, die schon auf Ihrem Betriebssystem vorinstalliert sind, gespeichert wird. Es kann keinerlei Schaden anrichten. Um die Installation zu starten, %söffnen Sie das Zertifikat%s und folgen den Anweisungen. (In manchen Browsern müssen Sie zusätzlich noch auf „Öffnen“ klicken.)")), "<a href=\"http://www.cacert.org/certs/root.crt\">", "</a>")?></li>
	</ul>
</div>

<div class="faq" id="scores">
	<h3><?=h(_("Wie bekomme ich Punkte?"))?></h3>
	<p><?=h(_("Gebäude-, Forschungs-, Roboter-, Flotten- und Verteidigungspunkte berechnen sich aus den Rohstoffen, die die Gebäude/Forschungen/Robotern/Flotten/Verteidigungsanlagen an Rohstoffen gekostet haben, die Sie besitzen. Pro Tausend Rohstoffe bekommen Sie dabei einen Punkt in der entsprechenden Kategorie."))?></p>
	<p><?=h(sprintf(_("Je mehr %s Sie im Flug verbrauchen, desto mehr Flugerfahrungspunkte erhalten Sie. Pro Tausend Tonnen %1\$s, das Sie in einem erfolgreich am Ziel angelangten Flug verbraucht haben, bekommen Sie einen Flugerfahrungspunkt. Erfahrung im Flug lässt Ihre Schiffe schneller fliegen."), _("Tritium")))?></p>
	<p><?=h(_("Pro Tausend Punkte, das Sie im Kampf Ihren Gegner verlieren ließen, bekommen Sie einen Kampferfahrungspunkt. Mehr Kampferfahrung schafft Ihnen Vorteile im Kampf."))?></p>
</div>

<div class="faq" id="noobs">
	<h3><?=h(_("Gibt es einen Anfängerschutz?"))?></h3>
	<p><?=h(sprintf(_("Ja, den gesamten Spielverlauf durch gilt eine Angriffssperre auf Spieler, die weniger als %s %% des eigenen Gesamtpunktestandes besitzen. Außerdem ist es Ihnen nicht möglich, Spieler anzugreifen, die aufgrund von Einschränkungen im Spiel (zum Beispiel dem Anfängerschutz) keine Angriffe auf Sie selbst fliegen können."), 5))?></p>
</div>

<div class="faq" id="planets">
	<h3><?=h(_("Kann man mehrere Planeten haben?"))?></h3>
	<p><?=h(sprintf(ngettext("Ja, selbstverständlich. Derzeit können Sie maximal %s Planet haben.", "Ja, selbstverständlich. Derzeit können Sie maximal %s Planeten haben.", global_setting("MAX_PLANETS")), ths(global_setting("MAX_PLANETS"))))?></p>
	<p><?=h(sprintf(_("Wenn Sie mit der Spionagesonde einen leeren Planeten ausspionieren, können Sie nachsehen, wieviele Felder dieser hat. Die Felderzahl variiert dabei unabhängig von der Position des Planeten, die Felderzahl beträgt %s–%s Felder. Haben Sie einen Planeten gefunden, den Sie besiedeln möchten, können Sie einem Besiedelungsschiff den Auftrag dazu erteilen."), 100, 500))?></p>
	<p><?=h(_("Optional können Sie dem Besiedelungsschiff zusätzliche Flotte mitschicken, diese wird nach der Besiedelung rückkehren. (Anders als das Besiedelungsschiff, dieses wird bei der Besiedelung abgebaut, um ein Startkapital auf dem Planeten zur Verfügung zu stellen.) Auf diese Weise können Sie auf dem neuen Planeten sofort Rohstoffe und Roboter durch die Transportkapazität der Flotte bereitstellen."))?></p>
	<p><?=h(_("Überlegen Sie sich gut, wo Sie Ihre Planeten platzieren. Wenn Sie vorhaben, auf bestimmten Kolonien nur bestimmte Minen auszubauen, ist es möglicherweise sinnvoll für Sie, Ihre Planeten nah beieinander zu halten, um schnell Rohstoffe zwischen den Kolonien hin- und herzuschicken. Wenn Sie sich mehr auf den Handel spezialisieren möchten, ist es vielleicht geschickt, in jeder Galaxie mindestens einen Planeten zu besitzen, damit der Transport vom und zum Kunden nicht so lange dauert."))?></p>
	<p><?=h(sprintf(ngettext("Einige Spieler bevorzugen es, ein oder zwei Kolonien des %s-Planet-Limits offenzulassen, um später sogenannte „Raidkolonien“ besetzen zu können – solche Kolonien werden temporär besiedelt und auf %sproduktion spezialisiert, damit in einem Krieg der Feind schneller und billiger angeflogen werden kann.", "Einige Spieler bevorzugen es, ein oder zwei Kolonien des %s-Planeten-Limits offenzulassen, um später sogenannte „Raidkolonien“ besetzen zu können – solche Kolonien werden temporär besiedelt und auf %sproduktion spezialisiert, damit in einem Krieg der Feind schneller und billiger angeflogen werden kann.", global_setting("MAX_PLANETS")), ths(global_setting("MAX_PLANETS")), _("Tritium")))?></p>
</div>

<div class="faq" id="umode">
	<h3><?=h(_("Was bewirkt der Urlaubsmodus?"))?></h3>
	<p><?=h(_("Mithilfe des Urlaubsmodus kann man seinen Account „einfrieren“, um während einer längeren Abwesenheit zu vermeiden, dass man angegriffen wird, ohne etwas dagegen tun zu können."))?></p>
	<p><?=h(_("Im Urlaubsmodus werden keine Rohstoffe mehr produziert, außerdem können keine Flotten mehr verschickt werden und kein Bau in Auftrag gegeben werden."))?></p>
	<p><?=h(sprintf(_("Im Urlaubsmodus erscheint in der Karte hinter dem Spielernamen ein „ (%s)“, daran können andere Spieler erkennen, dass der Benutzer sich im Urlaubsmodus befindet. Flüge zu Planeten, deren Besitzer sich im Urlaubsmodus befinden, sind nicht möglich, Trümmerfelder unterliegen dieser Einschränkung nicht."), _("U[rlaubsmodus]")))?></p>
	<p><?=h(_("Der Urlaubsmodus wird manuell durch den Benutzer beendet."))?></p>
</div>

<div class="faq" id="times">
	<h3><?=h(_("Wie kann man die Bauzeiten verkürzen?"))?></h3>
	<p><?=sprintf(h(_("Gebäudebauzeiten können Sie durch Roboter verkürzen. (→ %sWie funktionieren die Roboter?%s)")), "<a href=\"#robots\">", "</a>")?></p>
	<p><?=h(_("Die Zeiten der lokalen Forschung werden pro Ausbaulevel des Forschungslabors um 2,5 % verkürzt. Zusätzlich wirkt auf die globale Forschung jeder Ausbaulevel eines jeden Forschungslabors auf einem anderen Planeten um 1 %."))?></p>
	<p><?=h(_("Roboterbauzeiten verkürzen Sie durch den Ausbau der Roboterfabrik. Jede Ausbaustufe der Roboterfabrik verschnellert den Bau Ihrer Roboter um 5 %."))?></p>
	<p><?=h(_("Jeder Ausbaulevel Ihrer Werft verkürzt außerdem die Bauzeit von Schiffen und Verteidigungsanlangen um 2,5 %."))?></p>
</div>

<div class="faq" id="time">
	<h3><?=h(_("Was bedeuten Server- und Lokalzeit?"))?></h3>
	<p><?=h(sprintf(_("Die Serverzeit bezeichnet die Zeit, die auf dem Server eingestellt ist, auf dem %s läuft. Wenn Sie JavaScript aktiviert haben, rechnet das Spiel automatisch die meisten Angaben, die in der Serverzeit angegeben sind, zusätzlich in die Lokalzeit um. Die Lokalzeit entspricht dann Ihrer Systemuhr, also der Zeit, die Sie normalerweise unten rechts auf Ihrem Bildschirm sehen."), _("title_abbr")))?></p>
</div>

<div class="faq" id="avatars">
	<h3><?=h(_("Wie füge ich ein Bild in meine Allianz- oder Benutzerbeschreibung ein?"))?></h3>
	<p><?=h(_("Sie haben die Möglichkeit, XHTML-Code in Nachrichten, Allianzbeschreibungen und Spielerbeschreibungen einzugeben. Sofern der Code korrekt ist, wird er dann vom Browser interpretiert."))?></p>
	<p><?=sprintf(h(_("Ein Bild können Sie einfügen, indem Sie folgenden Code in Ihre Allianzbeschreibung einfügen: %s – als Pfad sollten Sie normalerweise eine Adresse angeben, die mit %s beginnt, das heißt, dass Sie das Bild auf einen öffentlich erreichbaren Webspace hinaufladen müssen. Denken Sie daran, wenn Sie ein Bild von Ihrer Festplatte einbinden (also als Pfad etwas wie %s oder %s angeben), wird die Datei auf der Festplatte eines Anderen höchstwahrscheinlich nicht vorhanden sein und deswegen nicht angezeigt werden. Ein Alternativtext muss aus Rücksicht auf Browser, die keine Bilder darstellen können/sollen, angegeben werden. Stellen Sie sich einfach vor, jemand sieht nichts davon, dass Sie versucht haben ein Bild einzubinden, sondern sieht den Alternativtext, den Sie angegeben haben, im normalen Fließtext stehen. Überlegen Sie nun, was für ein Text für einen solchen Benutzer am sinnvollsten wäre, mit Texten wie „Logo“, „Bild“ oder gar „Alternativtext“ wird niemand wirklich etwas anfangen können. Und lassen Sie sich bloß nicht durch die Tatsache verwirren, dass der Internet Explorer den Alternativtext teilweise auch anzeigt, obwohl das Bild geladen werden kann!")), "<code class=\"xhtml\">&lt;img src=&quot;<var>[".h(_("Pfad zum Bild"))."]</var>&quot; alt=&quot;<var>[".h(_("Alternativtext"))."]</var>&quot; /&gt;</code>", "<code>http://</code>", "<code>C:\\".h(_("…"))."</code>", "<code>/home/".h(_("…"))."</code>")?></p>
	<p><?=h(_("Es stehen zusätzlich übrigens folgende XHTML-1.1-Elemente mit allen semantisch ausschlaggebenden Attributen zur Verfügung:"))?></p>
	<ul>
		<li><code>table</code> (<code>thead</code>, <code>tbody</code>, <code>tfoot</code>, <code>tr</code>, <code>th</code>, <code>td</code>, <code>caption</code>)</li>
		<li><code>dl</code> (<code>dt</code>, <code>dd</code>)</li>
		<li><code>img</code>, <code>a</code></li>
		<li><code>p</code>, <code>div</code>, <code>span</code>, <code>h4</code>, <code>h5</code>, <code>h6</code>, <code>hr</code></li>
		<li><code>em</code>, <code>strong</code>, <code>var</code>, <code>code</code>, <code>abbr</code>, <code>acronym</code>, <code>address</code>, <code>blockquote</code>, <code>cite</code>, <code>q</code>, <code>dfn</code>, <code>bdo</code>, <code>ins</code>, <code>kbd</code>, <code>samp</code>, <code>var</code></li>
	</ul>
</div>

<div class="faq" id="research">
	<h3><?=h(_("Wo ist der Unterschied zwischen globaler und lokaler Forschung?"))?></h3>
	<p><?=h(_("Die globale Forschung vernetzt sämtliche Forschungslabore Ihres Imperiums miteinander, während die lokale Forschung nur in demjenigen Forschungslabor weiterentwickelt wird, in dem sie in Auftrag gegeben wurde. Beide Forschungsmethoden benötigen die gleiche Rohstoffmenge, bei beiden werden die Rohstoffe desjenigen Planeten benutzt, auf dem die Forschung in Auftrag gegeben wurde. Die globale Forschung ist schneller als die lokale, da bei ihr mehrere Forschungslabore zusammenarbeiten, dafür besitzt sie den Nachteil, dass während einer globalen Forschung eben alle Forschungslabore beschäftigt sind, während Sie mit lokaler Forschung auf mehreren Planeten gleichzeitig forschen können."))?></p>
	<p><?=h(_("Beachten Sie: Die Bauzeit der globalen Forschung ist nicht unabhängig davon, von welchem Planeten man aus sie ausbaut. Eine höhere Ausbaustufe des Planeten, auf dem man die Forschung in Auftrag gibt, verkürzt die Bauzeit mehr als Ausbaustufen von Forschungslaboren auf anderen Planeten."))?></p>
</div>

<div class="faq" id="robots">
	<h3><?=h(_("Wie funktionieren die Roboter?"))?></h3>
	<p><?=h(sprintf(_("Die Roboter helfen, Gebäudebauzeiten zu verkürzen und die Rohstoffproduktion zu erhöhen. Der Auswirkungsgrad der Roboter hängt von der aktuellen Stufe %s ab. Jede Stufe %1\$s erhöht dabei den Auswirkungsgrad %s um einen Viertelprozentpunkt."), _("[item_F2_gen_def]"), _("[item_R01_pl_gen_poss_2]")))?></p>
	<p><?=h(sprintf(_("Für die Minenroboter gilt folgende Formel bei der Berechnung der Produktion:")))?></p>
	<p><?=h(_("Produktion = Ausgangsproduktion × ( Roboterzahl ⁄ 250 ) × ( 1 + WURZEL( Ausbaustufe der Roboterbautechnik ) )"))?></p>
	<p><?=h(sprintf(_("Beachten Sie, dass nur begrenzt Platz für Roboter auf Ihrem Planeten zur Verfügung steht. Die Grenze liegt bei der Hälfte der ursprünglichen Felderzahl des Planeten (%s zeigt also keine Wirkung), für Minenroboter besteht eine zusätzliche Grenze bei der aktuellen Ausbaustufe der zugehörigen Mine (Ausnahme: %s, hier liegt die Grenze beim 2-Fachen der Ausbaustufe %s). Übersteigt die Roboterzahl die Grenze, zeigen überschüssige Roboter keinerlei Wirkung mehr."), _("[item_F9_nom]"), _("[item_R06_pl_nom]"), _("[item_B4_gen_def]")))?></p>
</div>

<div class="faq" id="fleet">
	<h3><?=h(_("Wieviele Flotten kann ich gleichzeitig verschicken?"))?></h3>
	<p><?=h(sprintf(_("Die Anzahl der gleichzeitig koordinierbaren Flotten berechnet sich aus der Entwicklungsstufe %s und der Anzahl der Werften in Ihrem Imperium. Potenzieren Sie einfach das Produkt der beiden Werte mit 0,7 und runden Sie das Ergebnis auf."), _("[item_F0_gen_def]")))?></p>
	<p><?=h(sprintf(_("Nocheinmal zum Mitschreiben: Anzahl gleichzeitig koordinierbarer Flotten = aufgerundet((%s*Werftenzahl)^0,7)."), _("[item_F0]")))?></p>
</div>

<div class="faq" id="alliance">
	<h3><?=h(_("Was bringt mir ein Bündnis?"))?></h3>
	<p><?=h(_("Wenn Sie sich mit einem Spieler verbünden, dient das nicht nur der Diplomatie, sondern hat auch praktische Auswirkungen im Spielverlauf."))?></p>
	<ul>
		<li><?=h(_("In der Karte werden verbündete Spieler farblich hervorgehoben, sodass Sie immer die Übersicht behalten."))?></li>
		<li><?=h(_("Sie können verbündete Spieler nicht angreifen."))?></li>
		<li><?=h(_("Wenn Sie Ihren Verbündeten ausspionieren, wird Ihre Spionage nicht abgewehrt und Sie sehen vollständige Informationen über den Planeten, unabhängig von Spionagetechnik und der Anzahl der Spionagesonden, die Sie geschickt haben."))?></li>
		<li><?=h(_("In der Spielerinformation sehen Sie die Koordinaten Ihrer Verbündeten und deren ausgegebene Rohstoffe."))?></li>
		<li><?=h(_("Bündnisrundschreiben machen es möglich, dass Sie all Ihren Verbündeten gleichzeitig eine Nachricht zukommen lassen."))?></li>
	</ul>
</div>

<div class="faq" id="resources">
	<h3><?=h(_("Die Rohstoffanzeige passt nicht in mein Browserfenster und Querscrollen ist nicht möglich."))?></h3>
	<p><?=h(_("Dies ist eine kleine Einschränkung der derzeitigen Formatierungsmöglichkeiten, die einem Webdesigner zur Verfügung stehen. Ein Querscrollen ist nur in der „non-fixed ress“-Variante der Skins verfügbar, wählen Sie in den Einstellungen einfach diese aus."))?></p>
</div>

<div class="faq" id="administrators">
	<h3><?=h(_("Wie kann ich die Administratoren erreichen?"))?></h3>
	<p><?=sprintf(h(_("Ab und zu treiben sich unsere Administratoren und Entwickler im %s#sua-dev-Channel auf irc.epd-me.net%s herum. Jener Kanal wird hauptsächlich für Entwicklergespräche zwischen den %s-Entwicklern verwendet.")), "<a href=\"irc://irc.epd-me.net/#sua-dev\">", "</a>", h(_("title_abbr")))?></p>
	<p><?=sprintf(h(_("Beachten Sie: Der Developer-Channel sollte nicht zum Klatsch und Tratsch verwendet werden, dazu ist der %sallgemeine Chat%s da. Sollten sich zu viele Leute nicht an diese Regel halten, kann es vorkommen, dass der Entwickler-Channel auf „moderated“ geschaltet wird, das heißt, dass ein Administrator Ihnen zuerst manuell erlauben muss, etwas zu schreiben, nachdem Sie den Kanal betreten haben.")), "<a href=\"#chat\">", "</a>")?></p>
</div>

<div class="faq" id="name">
	<h3><?=h(_("Kann ich meinen Namen ändern?"))?></h3>
	<p><?=sprintf(h(_("Namensänderungen sind eher unerwünscht (Benutzer könnten sich auf diese Weise vor anderen „verstecken“ oder Verwirrung auslösen), weswegen das Spiel es Spielern nicht erlaubt, Ihren Benutzernamen zu ändern. Sollte es dennoch einen Grund geben, der für eine Namensänderung spricht (Accountübernahme, etc.), kann ein Administrator Ihren Namen ändern. (→ %sWie kann ich die Administratoren erreichen?%s)")), "<a href=\"#administrators\">", "</a>")?></p>
</div>

<div class="faq" id="universe">
	<h3><?=h(_("Wie viele Universen gibt es?"))?></h3>
	<p><?=h(_("Derzeit gibt es nur ein Universum, und das wird sich auch so schnell nicht ändern. Das Universum enthält aber mehrere Galaxien, die Anzahl letzterer wird je nach Anzahl besiedelter Planeten ständig erweitert."))?></p>
</div>

<div class="faq" id="expand">
	<h3><?=h(_("Bei einem Gebäude/Forschung fehlt der Ausbauknopf oder bei meinen Robotern/Schiffen/Verteidigungsanlagen fehlt das Ausbaufeld."))?></h3>
	<p><?=h(_("Ein solcher Fall kann viele Ursachen haben. Dies hier stellt eine kleine Auswahl dar."))?></p>
	<ul>
		<li><?=sprintf(h(_("Der Planet ist ausgebaut. Ein Weiterbau der meisten Gebäude ist somit nicht möglich. Entwickeln Sie %s oder bauen Sie Gebäude zurück, um wieder freie Felder zu erlangen.")), _("[item_F9_acc_def]"))?></li>
		<li><?=h(_("Das Gebäude, das Sie ausbauen wollen, ist derzeit in Benutzung. Warten Sie, bis alle laufenden Forschungen/Roboter/Raumschiffe/Verteidigungsanlagen fertiggestellt sind."))?></li>
		<li><?=h(_("Das Gebäude, in dem Sie etwas bauen möchten, wird gerade ausgebaut. Sie können deswegen nicht forschen oder keine Roboter/Raumschiffe/Verteidigungsanlangen in Auftrag geben."))?></li>
		<li><?=h(_("Sie haben nicht die nötigen Abhängigkeiten erfüllt. Die Forschung/Roboter/Raumschiffe/Verteidigungsanlagen werden nur angezeigt, weil Sie diese bereits entwickelt/auf dem Planeten stationiert haben. Sie können aber keine weiteren dieser Gegenstände bauen. Schauen Sie sich den Forschungsbaum an und erfüllen Sie alle Forderungen."))?></li>
		<li><?=h(_("Sie wollen global forschen, aber es können nicht alle Forschungslabore benutzt werden, da sich ein Forschungslabor im Ausbau befindet oder irgendwo geforscht wird."))?></li>
		<li><?=h(_("Es wird bereits gebaut/geforscht. Vermutlich haben Sie dies übersehen und müssen scrollen, um den entsprechenden Ausbau zu erreichen."))?></li>
	</ul>
</div>

<div class="faq" id="distance">
	<h3><?=h(_("Wie berechnet sich die Entfernung zwischen zwei Planeten?"))?></h3>
	<p><?=sprintf(h(_("Entfernungen in %s werden in %s angegeben. Ein %s bezeichnet dabei die Entfernung zum eigenen Trümmerfeld.")), h(_("title_abbr")), "<abbr title=\"".h(_("Orbits"))."\">".h(_("Or"))."</abbr>", h(ngettext("Orbit", "Orbits", 1)))?></p>
	<p><?=sprintf(h(_("Planeten innerhalb des Sonnensystems sind in einer Reihe angeordnet. Die Distanz zum nächsten Planeten beträgt hierbei %s, die zum übernächsten %s, und so weiter.")), ths(10).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 10))."\">".h(_("Or"))."</abbr>", ths(20).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 20))."\">".h(_("Or"))."</abbr>")?></p>
	<p><?=sprintf(h(_("Die Entfernung zu einem Planeten in derselben Galaxie, aber in einem anderen Sonnensystem gestaltet sich etwas schwieriger. Sie müssen sich eine Galaxie in %s als riesigen Quader vorstellen, der eine Seitenlänge von %s hat. Stellen Sie sich ein dreidimensionales Koordinatensystem vor, in welchem die Längeneinheiten der x-, der y- und der z-Achse jeweils %s sind. Die Koordinaten eines Sonnensystems sind nun einfach herausfinden, das Sonnensystem 123 hat zum Beispiel die Koordinaten (1|2|3), also (%s|%s|%s). Sollten Sie sich gut in der Mathematik und der Geometrie auskennen, werden Sie nun wissen, wie sich der Abstand zwischen zwei Sonnensystemen berechnet.")), h(_("title_abbr")), ths(9000).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 9000))."\">".h(_("Or"))."</abbr>", h(_("title_abbr")), ths(1000).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 1000))."\">".h(_("Or"))."</abbr>", h(_("title_abbr")), ths(1000).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 1000))."\">".h(_("Or"))."</abbr>", h(_("title_abbr")), ths(2000).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 2000))."\">".h(_("Or"))."</abbr>", h(_("title_abbr")), ths(3000).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 3000))."\">".h(_("Or"))."</abbr>")?></p>
	<p><?=sprintf(h(_("Wenn Sie mit einem Raumschiff in eine andere Galaxie fliegen wollen, müssen Sie größere Distanzen zurücklegen. Stellen Sie sich einen riesigen Kreis vor, auf dem die Galaxien gleichmäßig verteilt sind. Das Raumschiff nimmt in diesem Kreis den kürzesten Weg (&bdquo;Luftlinie&ldquo;) zur Zielgalaxie. Für die Mathematiker unter uns: Die Größe des Kreises wird so ausgelegt, dass der Abstand von einer Galaxie zur nächsten auf der Kreislinie entlang genau %s entspricht.")), ths(30000).h(_("[unit_separator]"))."<abbr title=\"".h(ngettext("Orbit", "Orbits", 30000))."\">".h(_("Or"))."</abbr>")?></p>
</div>

<div class="faq" id="download">
	<h3><?=h(_("Wie kann ich mir das Spiel herunterladen?"))?></h3>
	<p><?=sprintf(h(_("Besuchen Sie doch einmal die %s%s-Entwicklerseiten%s, dort finden Sie weitere Informationen, wie Sie das Spiel bekommen können.")), "<a href=\"http://dev.s-u-a.net/\">", h(_("title_abbr")), "</a>")?></p>
</div>
<?php
	$gui->end();
?>
