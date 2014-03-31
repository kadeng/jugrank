<h2>Das JugRank System</h2>

<p>Beim JugRank System handelt es sich um ein neues Turnier- und Rangsystem das im Jahr 2009 von Kai Londenberg speziell f&uuml;r den Juggersport entworfen wurde, allerdings
sicher auch in anderen Sportarten Anwendung finden kann.</p>

<ul>
<li>Das JugRank Rangsystem ist ein Berechnungsverfahren das f&uuml;r alle Teams ihre Spielst&auml;rke berechnet</li>
<li>Der Turniermodus gibt vor, wie ein Turnier abl&auml;uft, wer gegen wen spielt etc.</li>
</ul>

<h3>Warum ein neues System ?</h3>

<p>Bei einer steigenden Anzahl von Teams die an Turnieren teilnehmen ist es wichtig, ein effizientes Turniersystem zu haben das
mit m&ouml;glichst wenigen Spielen ein m&ouml;glichst genaues Turnierergebnis produziert.</p>

<p>Herk&ouml;mmliche Verfahren wie das Gruppen+KO System oder das Schweizer System haben prinzipielle Schw&auml;chen die daf&uuml;r sorgen
dass die Ergebnisse von Turnieren sehr ungenau sind - insbesondere im Mittelfeld</p>

<p>Ein neues System sollte entweder mit weniger Spielen eine genauso hohe Genauigkeit wie bestehende Systeme erzeugen oder mit h&ouml;chstens
gleich vielen Spielen eine h&ouml;here Genauigkeit erzielen. Dabei darf die Genauigkeit in den ersten 3 Pl&auml;tzen nicht schlechter werden.</p>

<h3>Wie gut ist es ?</h3>

<p>Simulationen die das neue JugRank System mit dem Schweizer System und einem Gruppen+KO System wie es z.B. bei der 
Deutschen Jugger Meisterschaft die 2009 in Berlin ausgetragen wurde vergleichen, zeigen:</p>

<ul>
<li>Das JugRank System macht ca. nur halb so viele Fehler wie das Schweizer oder das Gruppen+KO System, sowohl an der Spitze als auch
in der gesamten Rangliste</li>
<li>Es ben&ouml;tigt daf&uuml;r i.A. weniger Spiele</li>
</ul>

<p>Erstmals ist es m&ouml;glich ein Turnier zu spielen, bei dem die mittleren und hinteren Pl&auml;tze der Rangliste sehr gut mit den tats&auml;chlichen
Spielst&auml;rken &uuml;bereinstimmen. Auch in der Spitze sind die Ergebnisse deutlich besser.</p>

<h3>Wie funktioniert es ?</h3>

<h4>Turniermodus</h4>

Der Turniermodus &auml;hnelt dem des <a href="http://de.wikipedia.org/wiki/Schweizer_System" target="_blank">Schweizer Systems</a> - wobei es jedoch folgende Unterschiede gibt:

<ul>
<li>Es wird das JugRank Rangsystem verwendet, statt nach Anzahl der Siege, Buchholzzahl und Feinbuchholzzahl zu sortieren</li>
<li>Bei der Berechnung der Paarungen kann ein Zufallsfaktor verwendet werden der daf&uuml;r sorgt, dass nicht immer die gleichen Teams
miteinander spielen</li>
<li>Es wird ein Finale gespielt, bei dem die Top 4 noch eine "Jeder-gegen-Jeden" Runde ausspielen.</li>
<li>Gleichzeitig mit dem Finale werden Entscheidungsspiele f&uuml;r alle F&auml;lle angesetzt in denen sich direkter Vergleich und Rang widersprechen</li>
</ul>

<p>Auf diese Art werden die bekannten Schw&auml;chen des Schweizer Systems gr&ouml;sstenteils eliminiert.</p>

<h4>Rangsystem - Berechnung der Spielst&auml;rken von Teams</h4>

<h5>Annahmen</h5>

<p>Das Rangsystem ist ein iteratives Berechnungsverfahren, das darauf beruht dass folgende Annahmen im statistischen Mittel korrekt sind:</p>

<ul>
	<li>Wenn Mannschaft A besser als Mannschaft B ist, wird sie im direkten Vergleich gewinnen</li>
	<li>Desto besser Mannschaft A im Vergleich zu Mannschaft B ist, desto besser wird ihr Punkteverh&auml;ltnis im direkten Vergleich sein. Dieser Zusammenhang ist nicht unbedingt linear.</li>
	<li>Wenn Mannschaft A besser als Mannschaft B ist, und Mannschaft B besser als Mannschaft C, dann ist Mannschaft A auch besser als Mannschaft C</li>
	<li>Wenn Mannschaft A besser ist als Mannschaft B, dann wird sie gegen Mannschaft C kein schlechteres Punkteverh&auml;ltnis erzielen als Mannschaft B</li>
	<li><em>Optional:</em> Spiele ungef&auml;hr gleichstarker Mannschaften gegeneinander werden mit mehr Einsatz gef&uuml;hrt - das Punkteverh&auml;ltnis ist aussagekr&auml;ftiger</li>
</ul>

<h5>Berechnung</h5>

<p>Grunds&auml;tzlich sollten Punkte gegen starke Teams "mehr Wert" sein als Punkte gegen schwache Teams. Dadurch haben wir jedoch ein Henne-Ei Problem.
Wie berechnet man die St&auml;rke eines Teams unabh&auml;ngig von seinen Ergebnissen ? Genau, es geht nicht.
Ein iteratives Verfahren kann dennoch das Problem l&ouml;sen</üp>

<p>Die Spielst&auml;rke eines Teams wird numerisch angegeben und zu Turnierbeginn gesch&auml;tzt. Da solche Sch&auml;tzungen Fehlerbehaftet sind, darf die
Sch&auml;tzung das Turnierergebnis nicht beeinflussen - es beeinflusst jedoch die Wahl der Spielpartner</p>

<p>Die Spielst&auml;rke eines Teams im Turnier ergibt sich als Mittelwert der Spielst&auml;rke die es in Einzelspielen bewiesen hat. Da sich dieses Verfahren am
geeignetsten erwiesen hat, wird ein gewichteter geometrischer Mittelwert zur Berechnung dieses Wertes verwendet</p>

<p>F&uuml;r jedes Spiel wird die Spielst&auml;rke die man darin bewiesen hat, anhand folgender Formel berechnet</p>

<p>Spielst&auml;rke Team A  = (St&auml;rke Team B) * Wurzel(Punkte Team A + 1) / Wurzel(Punkte Team B + 1)</p>

<h5>Iterationen</h5>

Dabei gibt es allerdings ein Problem - die Berechnung des Spielergebnisses ist von der Berechnung des Spielergebnisses des Gegners abh&auml;ngig - welche
wiederum von der Berechnung der St&auml;rke aller Gegner abh&auml;ngt gegen die das andere Team gespielt hat etc. Kurz gesagt, jede Spielst&auml;rke h&auml;ngt von
jedem Spielergebnis ab, sobald eine gewisse Anzahl von Spielen gemacht wurde.

Aus diesem Grund werden folgende Schritte wiederholt, bis sich ein Gleichgewichtszustand einstellt - also die Spielst&auml;rken einem stabilen
Wert zustreben:

<ol>
	<li>Einzelne Spielst&auml;rken werden anhand der Spielergebnisse und den Spielst&auml;rken aus der vorherigen Iteration berechnet</li>
	<li>Die Spielst&auml;rke eines Teams wird als (gewichteter) geometrischer Mittelwert dieser Ergebnisse gebildet</li>
</ol>

<p>Als Anfangswerte f&uuml;r die Spielst&auml;rken k&ouml;nnen dabei beliebige Konstanten verwendet werden. z.B. 50 f&uuml;r alle. Oder auch Sch&auml;tzwerte f&uuml;r die Spielst&auml;rken.
Auf die Reihenfolge der Endergebnisse hat das keinen Einfluss</p>

<p>Die exakten Details zum Berechnungsverfahren finden sich im Quellcode (Siehe Seitennavigation). Am wichtigsten ist dabei die
Datei jugrank.php und die Methoden JugRank::calculate, JugRank::iterateCalculation und JugRank::calcSingleScore</p> 

<h4>Effekte der Berechnung</h4>

<ul>
<li>Punkte gegen starke Teams sind mehr Wert als solche gegen schwache Teams</li>
<li>Es kommt auf jeden Punkt gegen jedes Team an, nicht nur auf Sieg oder Niederlage</li>
<li>Durch die Bildung des Mittelwerts werden Spielst&auml;rkeschwankungen und Zufallseffekte ausgeglichen</li>
</ul>

<h4>Transparenz und Nachvollziehbarkeit</h4>

<p>Das JugRank verfahren ist - im Gegensatz zu den herk&ouml;mmlichen Verfahren - nicht darauf ausgelegt mit der Hand ausgerechnet zu werden.</p>

<p>Es ist aus diesem Grund allerdings nicht weniger transparent, denn jeder kann den Quelltext des Programms lesen, und die Turnierergebnisse
selber damit nachberechnen. Es ist ebenfalls jedem m&ouml;glich, anhand von Simulationen die Qualit&auml;t des Verfahrens zu pr&uuml;fen.</p>

<h4>Einladung zur Pr&uuml;fung des Verfahrens</h4>

<p>Jeder der will und kann, ist herzlich eingeladen das Verfahren zu testen, zu kritisieren und Verbesserungsvorschl&aumnl;ge zu machen. 
Den PHP Quellcode kann man unter dem Men&uuml;punkt Quellcode begutachten und herunterladen. Damit ist es m&ouml;glich, sich selber ein Bild 
zu machen, Turniere zu simulieren oder einfach zu versuchen durch herumspielen am Verfahren bessere Ergebnisse zu erzielen</p>

<p>Das Verfahren ist sicherlich noch nicht perfekt - aber viel besser als die bisherigen Systeme</p>