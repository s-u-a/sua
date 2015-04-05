<?php
	require('engine/include.php');

	gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; <abbr title="Frequently Asked Questions" xml:lang="en"><span xml:lang="de">FAQ</span></abbr></h2>
<p>
	Hier eine Liste der Fragen. Sollte die Frage, die Sie suchen, nicht dabei sein, schreiben Sie einfach ins <a href="http://board.s-u-a.net/index.php" xml:lang="en">Board</a>.
</p>
<ol id="question-list">
	<li><a href="#requirements">Was brauche ich, um <abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> zu spielen?</a></li>
	<li><a href="#register">Wie kann ich mich anmelden?</a></li>
	<li><a href="#certification">Warum bekomme ich immer eine Meldung &bdquo;Ungültiges Zertifikat&ldquo;?</a></li>
	<li><a href="#scores">Wie bekomme ich Punkte?</a></li>
	<li><a href="#noobs">Gibt es einen Anfängerschutz?</a></li>
	<li><a href="#planets">Kann man mehrere Planeten haben?</a></li>
	<li><a href="#times">Wie kann man die Bauzeiten verkürzen?</a></li>
	<li><a href="#time">Was bedeuten <span xml:lang="en">Server</span>- und Lokalzeit?</a></li>
	<li><a href="#research">Wo ist der Unterschied zwischen lokaler und globaler Forschung?</a></li>
	<li><a href="#robots">Wie funktionieren die Roboter?</a></li>
	<li><a href="#fleet">Wieviele Flotten kann ich gleichzeitig verschicken?</a></li>
	<li><a href="#alliance">Was bringt mir ein Bündnis?</a></li>
	<li><a href="#resources">Die Rohstoffanzeige passt nicht in mein <span xml:lang="en">Browser</span>fenster und Quer<span xml:lang="en">scrollen</span> ist nicht möglich.</a></li>
	<li><a href="#administrators">Wie kann ich die Administratoren erreichen?</a></li>
	<li><a href="#name">Kann ich meinen Namen ändern?</a></li>
	<li><a href="#universe">Wie viele Universen gibt es?</a></li>
	<li><a href="#expand">Bei einem Gebäude/Forschung fehlt der Ausbauknopf oder bei meinen Robotern/Schiffen/Verteidigungsanlagen fehlt das Ausbaufeld.</a></li>
	<li><a href="#distance">Wie berechnet sich die Entfernung zwischen zwei Planeten?</a></li>
	<li><a href="#download">Wie kann ich mir das Spiel herunterladen?</a></li>
</ol>

<div class="faq">
	<h3 id="requirements">Was brauche ich, um <abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> zu spielen?</h3>
	<p>Um das Spiel zu spielen, benötigen Sie lediglich einen <span xml:lang="en">Internetbrowser</span>, der <abbr title="Hypertext Markup Language" xml:lang="en"><span xml:lang="de">HTML</span></abbr>&thinsp;4 unterstützt. Sie können also quasi jeden <span xml:lang="en">Browser</span> verwenden, vom normalen <span xml:lang="en">Firefox</span> über den <span xml:lang="en">Terminalbrowser</span> bis hin zum Vorleseprogramm für Blinde.</p>
	<p>Um das <span xml:lang="en">Design</span> in seinen vollen Zügen zu genießen, benötigen Sie einen Browser aus der folgenden Liste.</p>
	<ul>
		<li>Mozilla <span xml:lang="en">Firefox</span> (oder gleichwertige <span xml:lang="en">Gecko</span>-<span xml:lang="en">Browser</span>, wie <span xml:lang="en">Netscape Navigator</span> oder Mozilla<span xml:lang="en"> Suite</span>)</li>
		<li><span xml:lang="en">Konqueror</span> (in einer neueren Version, bestenfalls 3.2 oder neuer)</li>
		<li>Safari (siehe <span xml:lang="en">Konqueror</span>)</li>
		<li>Opera (ab Version 7)</li>
		<li><span xml:lang="en">Internet Explorer</span> (ab Version 5.5)</li>
	</ul>
	<p>Für einige praktische <span xml:lang="en">Features</span> können Sie außerdem <span xml:lang="en">JavaScript</span> aktivieren.</p>
	<p>Wir möchten Sie wärmstens darum bitten, nicht den <span xml:lang="en">Internet Explorer</span> zu verwenden, sofern Sie die Möglichkeit dazu haben, da dieser keine Kompression unterstützt und Sie uns somit etwa das Zehnfache des Datenvolumens erzeugen, das andere <span xml:lang="en">Browser</span> verursachen.</p>
</div>

<div class="faq">
	<h3 id="register">Wie kann ich mich anmelden?</h3>
	<p>Bereits registrierte Benutzer müssen das Formular, das auf der rechten Seite zu sehen ist, einfach nur ausfüllen (mit Benutzernamen und Passwort) und auf &bdquo;Anmelden&ldquo; klicken.</p>
	<p>Noch nicht registrierte Benutzer wählen bitte in der Hauptnavigation &bdquo;<a href="register.php">Registrieren</a>&ldquo; aus, füllen das dortige Formular aus und klicken auf &bdquo;registrieren&ldquo;. Danach können Sie sich, wie oben beschrieben, anmelden.</p>
</div>

<div class="faq">
	<h3 id="certification">Warum bekomme ich immer eine Meldung &bdquo;Ungültiges Zertifikat&ldquo;?</h3>
	<p>Diese Meldung ist nichts Schlimmes, Sie erhalten sie, weil das Zertifikat der sicheren <abbr title="Hypertext Tranfer Protocol" xml:lang="en"><span xml:lang="de">HTTP</span></abbr>-Verbindung nicht für teures Geld bei irgendeinem Anbieter signiert wurde, sondern nur von der <a href="http://cacert.org/"><span xml:lang="en">CAcert</span>-Organisation</a> unterzeichnet ist, welche in vielen <span xml:lang="en">Browser</span>n nicht in der Liste der vertrauenswürdigen Firmen aufgeführt ist. Sie können die Warnung jedenfalls getrost ignorieren.</p>
	<p>Falls Sie sich von der Meldung gestört fühlen, haben Sie folgende Möglichkeiten, sie zu vermeiden.</p>
	<ul>
		<li>Sie verzichten auf eine verschlüsselte Verbindung, indem Sie rechts auf der Seite <abbr title="Secure Sockets Layer" xml:lang="en"><span xml:lang="de">SSL</span></abbr> abschalten. Dies ist aber weniger sicher und ist deshalb nicht empfohlen.</li>
		<li>Sie laden sich das Zertifikat herunter und installieren es bei sich. Keine Sorge, das Zertifikat ist nur eine kleine Textdatei, die auf Ihrem <span xml:lang="en">Computer</span> unter den etlichen anderen Zertifikaten, die schon auf Ihrem Betriebssystem vorinstalliert sind, gespeichert wird. Es kann keinerlei Schaden anrichten. Um die Installation zu starten, <a href="http://www.cacert.org/certs/root.crt">öffnen Sie das Zertifikat</a> und folgen den Anweisungen. (In manchen <span xml:lang="en">Browser</span>n müssen Sie zusätzlich noch auf &bdquo;Öffnen&ldquo; klicken.)</li>
	</ul>
</div>

<div class="faq">
	<h3 id="scores">Wie bekomme ich Punkte?</h3>
	<p>Gebäude-, Forschungs-, Roboter-, Flotten- und Verteidigungspunkte berechnen sich aus den Rohstoffen, die die Gebäude/Forschungen/Robotern/Flotten/Verteidigungsanlagen an Rohstoffen gekostet haben, die Sie besitzen. Pro Tausend Rohstoffe bekommen Sie dabei einen Punkt in der entsprechenden Kategorie.</p>
	<p>Je mehr Tritium Sie im Flug verbrauchen, desto mehr Flugerfahrungspunkte erhalten Sie. Pro Tausend Tonnen Tritium, das Sie in einem erfolgreich am Ziel angelangten Flug verbraucht haben, bekommen Sie einen Flugerfahrungspunkt. Erfahrung im Flug lässt Ihre Schiffe schneller fliegen.</p>
	<p>Pro Tausend Punkte, das Sie im Kampf Ihren Gegner verlieren ließen, bekommen Sie einen Kampferfahrungspunkt. Mehr Kampferfahrung schafft Ihnen Vorteile im Kampf.</p>
</div>

<div class="faq">
	<h3 id="noobs">Gibt es einen Anfängerschutz?</h3>
	<p>Ja, den gesamten Spielverlauf durch gilt eine Angriffssperre auf Spieler, die weniger als 5&thinsp;<abbr title="Prozent">%</abbr> des eigenen Gesamtpunktestandes besitzen. Außerdem ist es Ihnen nicht möglich, Spieler anzugreifen, die aufgrund von Einschränkungen im Spiel (zum Beispiel dem Anfängerschutz) keine Angriffe auf Sie selbst fliegen können.</p>
</div>

<div class="faq">
	<h3 id="planets">Kann man mehrere Planeten haben?</h3>
	<p>Ja, selbstverständlich. Derzeit können Sie maximal <?=htmlentities(MAX_PLANETS)?>&nbsp;Planeten haben.</p>
	<p>Wenn Sie mit der Spionagesonde einen leeren Planeten ausspionieren, können Sie nachsehen, wieviele Felder dieser hat. Die Felderzahl variiert dabei unabhängig von der Position des Planeten, die Felderzahl beträgt 100&ndash;500&nbsp;Felder. Haben Sie einen Planeten gefunden, den Sie besiedeln möchten, können Sie einem Besiedelungsschiff den Auftrag dazu erteilen.</p>
	<p>Optional können Sie dem Besiedelungsschiff zusätzliche Flotte mitschicken, diese wird nach der Besiedelung rückkehren. (Anders als das Besiedelungsschiff, dieses wird bei der Besiedelung abgebaut, um ein Startkapital auf dem Planeten zur Verfügung zu stellen.) Auf diese Weise können Sie auf dem neuen Planeten sofort Rohstoffe und Roboter durch die Transportkapazität der Flotte bereitstellen.</p>
	<p>Überlegen Sie sich gut, wo Sie Ihre Planeten platzieren. Wenn Sie vorhaben, auf bestimmten Kolonien nur bestimmte Minen auszubauen, ist es möglicherweise sinnvoll für Sie, Ihre Planeten nah beieinander zu halten, um schnell Rohstoffe zwischen den Kolonien hin- und herzuschicken. Wenn Sie sich mehr auf den Handel spezialisieren möchten, ist es vielleicht geschickt, in jeder Galaxie mindestens einen Planeten zu besitzen, damit der Transport vom und zum Kunden nicht so lange dauert.</p>
	<p>Einige Spieler bevorzugen es, ein oder zwei Kolonien des <?=htmlentities(MAX_PLANETS)?>-Planeten-<span xml:lang="en">Limit</span>s offenzulassen, um später sogenannte &bdquo;<span xml:lang="en">Raid</span>kolonien&ldquo; besetzen zu können &ndash; solche Kolonien werden temporär besiedelt und auf Tritiumproduktion spezialisiert, damit in einem Krieg der Feind schneller und billiger angeflogen werden kann.</p>
</div>

<div class="faq">
	<h3 id="times">Wie kann man die Bauzeiten verkürzen?</h3>
	<p>Gebäudebauzeiten können Sie durch Roboter verkürzen. (&rarr; <a href="#robots">Wie funktionieren die Roboter?</a>)</p>
	<p>Die Zeiten der lokalen Forschung werden pro Ausbaulevel des Forschungslabors um 2,5&thinsp;<abbr title="Prozent">%</abbr> verkürzt. Zusätzlich wirkt auf die globale Forschung jeder Ausbaulevel eines jeden Forschungslabors auf einem anderen Planeten um 1&thinsp;<abbr title="Prozent">%</abbr>.</p>
	<p>Roboterbauzeiten verkürzen Sie durch den Ausbau der Roboterfabrik. Jede Ausbaustufe der Roboterfabrik verschnellert den Bau Ihrer Roboter um 5&thinsp;<abbr title="Prozent">%</abbr>.</p>
	<p>Jeder Ausbaulevel Ihrer Werft verkürzt außerdem die Bauzeit von Schiffen und Verteidigungsanlangen um 2,5&thinsp;<abbr title="Prozent">%</abbr>.</p>
</div>

<div class="faq">
	<h3 id="time">Was bedeuten <span xml:lang="en">Server</span>- und Lokalzeit?</h3>
	<p>Die <span xml:lang="en">Server</span>zeit bezeichnet die Zeit, die auf dem <span xml:lang="en">Server</span> eingestellt ist, auf dem <abbr title="Star Under Attack" xml:lang="en">S-U-A</abbr> läuft. Wenn Sie <span xml:lang="en">JavaScript</span> aktiviert haben, rechnet das Spiel automatisch die meisten Angaben, die in der Serverzeit angegeben sind, zusätzlich in die Lokalzeit um. Die Lokalzeit entspricht dann Ihrer Systemuhr, also der Zeit, die Sie normalerweise unten rechts auf Ihrem Bildschirm sehen.</p>
</div>

<div class="faq">
	<h3 id="research">Wo ist der Unterschied zwischen globaler und lokaler Forschung?</h3>
	<p>Die globale Forschung vernetzt sämtliche Forschungslabore Ihres Imperiums miteinander, während die lokale Forschung nur in demjenigen Forschungslabor weiterentwickelt wird, in dem sie in Auftrag gegeben wurde. Beide Forschungsmethoden benötigen die gleiche Rohstoffmenge, bei beiden werden die Rohstoffe desjenigen Planeten benutzt, auf dem die Forschung in Auftrag gegeben wurde. Die globale Forschung ist schneller als die lokale, da bei ihr mehrere Forschungslabore zusammenarbeiten, dafür besitzt sie den Nachteil, dass während einer globalen Forschung eben alle Forschungslabore beschäftigt sind, während Sie mit lokaler Forschung auf mehreren Planeten gleichzeitig forschen können.</p>
	<p>Beachten Sie: Die Bauzeit der globalen Forschung ist nicht unabhängig davon, von welchem Planeten man aus sie ausbaut. Eine höhere Ausbaustufe des Planeten, auf dem man die Forschung in Auftrag gibt, verkürzt die Bauzeit mehr als Ausbaustufen von Forschungslaboren auf anderen Planeten.</p>
</div>

<div class="faq">
	<h3 id="robots">Wie funktionieren die Roboter?</h3>
	<p>Die Roboter helfen, Gebäudebauzeiten zu verkürzen und die Rohstoffproduktion zu erhöhen. Der Auswirkungsgrad der Roboter hängt von der aktuellen Stufe der Roboterbautechnik ab. Jede Stufe der Roboterbautechnik erhöht dabei den Auswirkungsgrad Ihrer Bauroboter um ein Viertelprozent, den Ihrer Minenroboter um ein Sechzehntelprozent.</p>
	<p>Beachten Sie, dass nur begrenzt Platz für Roboter auf Ihrem Planeten zur Verfügung steht. Für Bauroboter liegt die Grenze bei der Hälfte der ursprünglichen Felderzahl des Planeten (Ingenieurswissenschaft zeigt also keine Wirkung), für Minenroboter liegt sie bei der aktuellen Ausbaustufe der zugehörigen Mine (Ausnahme: Tritiumroboter, hier liegt die Grenze beim 2-Fachen der Ausbaustufe des Tritiumgenerators). Übersteigt die Roboterzahl die Grenze, zeigen überschüssige Roboter keinerlei Wirkung mehr.</p>
</div>

<div class="faq">
	<h3 id="fleet">Wieviele Flotten kann ich gleichzeitig verschicken?</h3>
	<p>Die Anzahl der gleichzeitig koordinierbaren Flotten berechnet sich aus der Entwicklungsstufe Ihres Kontrollwesens und der Anzahl der Werften in Ihrem Imperium. Potenzieren Sie einfach das Produkt der beiden Werte mit 0,7 und runden Sie das Ergebnis ab.</p>
	<p>Nocheinmal zum Mitschreiben: Anzahl gleichzeitig koordinierbarer Flotten = abgerundet((Kontrollwesen*Werftenzahl)^0,7).</p>
</div>

<div class="faq">
	<h3 id="alliance">Was bringt mir ein Bündnis?</h3>
	<p>Wenn Sie sich mit einem Spieler verbünden, dient das nicht nur der Diplomatie, sondern hat auch praktische Auswirkungen im Spielverlauf.</p>
	<ul>
		<li>In der Karte werden verbündete Spieler farblich hervorgehoben, sodass Sie immer die Übersicht behalten.</li>
		<li>Sie können verbündete Spieler nicht angreifen.</li>
		<li>Wenn Sie Ihren Verbündeten ausspionieren, wird Ihre Spionage nicht abgewehrt und Sie sehen vollständige Informationen über den Planeten, unabhängig von Spionagetechnik und der Anzahl der Spionagesonden, die Sie geschickt haben.</li>
		<li>In der Spielerinformation sehen Sie die Koordinaten Ihrer Verbündeten und deren ausgegebene Rohstoffe.</li>
		<li>Bündnisrundschreiben machen es möglich, dass Sie all Ihren Verbündeten gleichzeitig eine Nachricht zukommen lassen.</li>
	</ul>
</div>

<div class="faq">
	<h3 id="resources">Die Rohstoffanzeige passt nicht in mein <span xml:lang="en">Browser</span>fenster und Quer<span xml:lang="en">scrollen</span> ist nicht möglich.</h3>
	<p>Dies ist eine kleine Einschränkung der derzeitigen Formatierungsmöglichkeiten, die einem <span xml:lang="en">Webdesigner</span> zur Verfügung stehen. Ein Quer<span xml:lang="en">scrollen</span> ist nur in der &bdquo;<span xml:lang="en">non-fixed ress</span>&ldquo;-Variante der <span xml:lang="en">Skins</span> verfügbar, wählen Sie in den Einstellungen einfach diese aus.</p>
</div>

<div class="faq">
	<h3 id="administrators">Wie kann ich die Administratoren erreichen?</h3>
	<p>Ab und zu treiben sich unsere Administratoren und Entwickler im <a href="irc://irc.epd-me.net/#sua-dev"><em xml:lang="en">#sua-dev</em>-<span xml:lang="en">Channel</span> auf <em>irc.epd-me.net</em></a> herum. Jener Kanal wird hauptsächlich für Entwicklergespräche zwischen den <abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr>-Entwicklern verwendet.</p>
	<p>Beachten Sie: Der <span xml:lang="en">Developer</span>-<span xml:lang="en">Channel</span> sollte nicht zum Klatsch und Tratsch verwendet werden, dazu ist der <a href="#chat">allgemeine <span xml:lang="en">Chat</span></a> da. Sollten sich zu viele Leute nicht an diese Regel halten, kann es vorkommen, dass der Entwickler-<span xml:lang="en">Channel</span> auf &bdquo;<span xml:lang="en">moderated</span>&ldquo; geschaltet wird, das heißt, dass ein Administrator Ihnen zuerst manuell erlauben muss, etwas zu schreiben, nachdem Sie den Kanal betreten haben.</p>
</div>

<div class="faq">
	<h3 id="name">Kann ich meinen Namen ändern?</h3>
	<p>Namensänderungen sind eher unerwünscht (Benutzer könnten sich auf diese Weise vor anderen &bdquo;verstecken&ldquo; oder Verwirrung auslösen), weswegen das Spiel es Spielern nicht erlaubt, Ihren Benutzernamen zu ändern. Sollte es dennoch einen Grund geben, der für eine Namensänderung spricht (<span xml:lang="en">Account</span>übernahme, <abbr title="et cetera">etc.</abbr>), kann ein Administrator Ihren Namen ändern. (&rarr; <a href="#administrators">Wie kann ich die Administratoren erreichen?</a>)</p>
</div>

<div class="faq">
	<h3 id="universe">Wie viele Universen gibt es?</h3>
	<p>Derzeit gibt es nur ein Universum, und das wird sich auch so schnell nicht ändern. Das Universum enthält aber mehrere Galaxien, die Anzahl letzterer wird je nach Anzahl besiedelter Planeten ständig erweitert.</p>
</div>

<div class="faq">
	<h3 id="expand">Bei einem Gebäude/Forschung fehlt der Ausbauknopf oder bei meinen Robotern/Schiffen/Verteidigungsanlagen fehlt das Ausbaufeld.</h3>
	<p>Ein solcher Fall kann viele Ursachen haben. Dies hier stellt eine kleine Auswahl dar.</p>
	<ul>
		<li>Der Planet ist ausgebaut. Ein Weiterbau der meisten Gebäude ist somit nicht möglich. Entwickeln Sie die Ingeneurswissenschaft oder bauen Sie Gebäude zurück, um wieder freie Felder zu erlangen.</li>
		<li>Das Gebäude, das Sie ausbauen wollen, ist derzeit in Benutzung. Warten Sie, bis alle laufenden Forschungen/Roboter/Raumschiffe/Verteidigungsanlagen fertiggestellt sind.</li>
		<li>Das Gebäude, in dem Sie etwas bauen möchten, wird gerade ausgebaut. Sie können deswegen nicht forschen oder keine Roboter/Raumschiffe/Verteidigungsanlangen in Auftrag geben.</li>
		<li>Sie haben nicht die nötigen Abhängigkeiten erfüllt. Die Forschung/Roboter/Raumschiffe/Verteidigungsanlagen werden nur angezeigt, weil Sie diese bereits entwickelt/auf dem Planeten stationiert haben. Sie können aber keine weiteren dieser Gegenstände bauen. Schauen Sie sich den <em>Abhängigkeitenbaum</em> an und erfüllen Sie alle Forderungen.</li>
		<li>Sie wollen global forschen, aber es können nicht alle Forschungslabore benutzt werden, da sich ein Forschungslabor im Ausbau befindet oder irgendwo geforscht wird.</li>
		<li>Es wird bereits gebaut/geforscht. Vermutlich haben Sie dies übersehen und müssen <span xml:lang="en">scrollen</span>, um den entsprechenden Ausbau zu erreichen.</li>
	</ul>
</div>

<div class="faq">
	<h3 id="distance">Wie berechnet sich die Entfernung zwischen zwei Planeten?</h3>
	<p>Entfernungen in <abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> werden in <em><abbr title="Orbits">Or</abbr></em> angegeben. Ein Orbit bezeichnet dabei die Entfernung zum eigenen Trümmerfeld.</p>
	<p>Planeten innerhalb des Sonnensystems sind in einer Reihe angeordnet. Die Distanz zum nächsten Planeten beträgt hierbei 10&thinsp;<abbr title="Orbits">Or</abbr>, die zum übernächsten 20&thinsp;<abbr title="Orbits">Or</abbr>, und so weiter.</p>
	<p>Die Entfernung zu einem Planeten in derselben Galaxie, aber in einem anderen Sonnensystem gestaltet sich etwas schwieriger. Sie müssen sich eine Galaxie in <abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> als riesigen Quader vorstellen, der eine Seitenlänge von 9&thinsp;000&thinsp;<abbr title="Orbits">Or</abbr> hat. Stellen Sie sich ein dreidimensionales Koordinatensystem vor, in welchem die Längeneinheiten der x-, der y- und der z-Achse jeweils 1&thinsp;000&thinsp;<abbr title="Orbits">Or</abbr> sind. Die Koordinaten eines Sonnensystems sind nun einfach herausfinden, das Sonnensystem 123 hat zum Beispiel die Koordinaten (1|2|3), also (1&thinsp;000&thinsp;<abbr title="Orbits">Or</abbr>|2&thinsp;000&thinsp;<abbr title="Orbits">Or</abbr>|3&thinsp;000&thinsp;<abbr title="Orbits">Or</abbr>). Sollten Sie sich gut in der Mathematik und der Geometrie auskennen, werden Sie nun wissen, wie sich der Abstand zwischen zwei Sonnensystemen berechnet.</p>
	<p>Wenn Sie mit einem Raumschiff in eine andere Galaxie fliegen wollen, müssen Sie größere Distanzen zurücklegen. Stellen Sie sich einen riesigen Kreis vor, auf dem die Galaxien gleichmäßig verteilt sind. Das Raumschiff nimmt in diesem Kreis den kürzesten Weg (&bdquo;Luftlinie&ldquo;) zur Zielgalaxie. Für die Mathematiker unter uns: Die Größe des Kreises wird so ausgelegt, dass der Abstand von einer Galaxie zur nächsten <strong>auf der Kreislinie entlang</strong> genau 30&thinsp;000&thinsp;<abbr title="Orbits">Or</abbr> entspricht.</p>
</div>

<div class="faq">
	<h3 id="download">Wie kann ich mir das Spiel herunterladen?</h3>
	<p>Das Spiel ist per <a href="http://subversion.tigris.org/" xml:lang="en">Subversion</a> verfügbar, das <span xml:lang="en">Repository</span> liegt unter <a href="svn://svn.s-u-a.net/home/srv/svn/sua/">svn://svn.s-u-a.net/home/srv/svn/sua/</a>.</p>
	<p>Den Zugriff auf das <abbr title="Subversion" xml:lang="en"><span xml:lang="de">SVN</span></abbr>-<span xml:lang="en">Repository</span> erhalten Sie auch per <a href="http://websvn.tigris.org/">WebSVN</a>, unter <a href="http://svn.s-u-a.net/listing.php?repname=Stars%20Under%20Attack&amp;path=%2F&amp;sc=0">http://svn.s-u-a.net/</a>.</p>
</div>
<?php
	gui::html_foot();
?>