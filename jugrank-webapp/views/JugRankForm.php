<?php
if ($_REQUEST['randomfactor']) {
	$randomfactor = $_REQUEST['randomfactor'];
} else {
        $randomfactor = 0.4;
}
	require_once('logic/jugrank.php');
	$jscore = new JugRank();
	$lines = explode("\n", $_REQUEST['teams']);
	foreach ($lines as $line) {
		if (preg_match("/([^=]+):([0-9.]+)/", $line, $matches)) {
			$jscore->addTeam($matches[1],$matches[2]);
		} else {
			if (trim($line)) {
				echo "<span style=\"color:red;\">ACHTUNG: Konnte Zeile '$line' bei den Teams nicht auswerten</span><br/>";
			}
		}
	}
	if ($_REQUEST['gameresults']) {

		$lines = explode("\n", $_REQUEST['gameresults']);
		foreach ($lines as $line) {
			if (preg_match("/([^:]+):([^=]+)=([0-9]+):([0-9]+)/", $line, $matches)) {
				$jscore->addGameResult($matches[1],$matches[2], $matches[3], $matches[4]);
			} else {
				if (trim($line)) {
					echo "<span style=\"color:red;\">ACHTUNG: Konnte Zeile '$line' bei den Spielen nicht auswerten</span><br/>";
				}
			}
		}
		$results = $jscore->calculate($_REQUEST['weightedgames']);
		arsort($results);
		$jscore->calculateEloRanks();
		$jscore->calculateSwiss();

		echo "<h3>Rangliste</h3>";
		
		echo "<table border='0' cellspacing='5'>";
		echo "<tr style='background-color: #ffffee;'><th>Rang</th><th>Team</th><th>IterScore</th><th>Stddev</th><th>Spiele</th><th>Siege</th><th>Buchholz</th><th>Feinbuchholz</th><th>ELO</th></tr>";
		$rang = 0;
		foreach ($results as $result=>$score) {
			echo "<tr>";
			echo "<td>".$rang++."</td>";
			echo "<td>".htmlentities($result). "</td>";
			echo "<td>".round($score,4)."</td>";
			echo "<td>".round($jscore->teamStdDev[$result],4)."</td>";
			echo "<td>".(count($jscore->presults[$result])-1)."</td>";
			echo "<td>".($jscore->wins[$result])."</td>";
			echo "<td>".($jscore->buchholz[$result])."</td>";
			echo "<td>".($jscore->feinbuchholz[$result])."</td>";
			echo "<td>".$jscore->elo[$result]."</td>";
			echo "</tr>";
		}
	}
	echo "</table>";

?>

<h2>Turnier spielen</h2>

<p>Auf dieser Seite ist es m&ouml;glich, ein Turnier nach dem JugRank System durchzuspielen.</p>

<h4> Empfohlener Turniermodus</h4>

<ul>
<li>Zun&auml;chst oben eine Liste aller teilnehmenden Teams, zusammen mit einer Sch&auml;tzung ihrer Spielst&auml;rke eintragen</li>
<li>Formular absenden - die Liste der empfohlenen Paarungen ausspielen und eintragen - Diesen Schritt 3 bis 6 mal wiederholen, je nach Turnierumfang und zur Verfügung stehenden Plätzen, Zeit etc.</li>
<li>Finale Spielen: Die Top 4 der Rangliste spielen Jeder gegen Jeden. Ausserdem werden alle vorgeschlagenen Wiederholungsspiele durchgef&uuml;hrt</li>
<li>Die Rangliste nach dem Finale ist das Endergebnis. Alle Mannschaften die vorzeitig (z.B. wegen Spieluntauglichkeit) ausgeschieden sind, müssen entfernt werden</li>
</ul>

<p>
Dabei wird jede Flexibilität geboten, vom normalen Turniermodus abzuweichen. 
Es bestehen keine Automatismen oder Zwangsmechanismen die Spiele in einer bestimmten Reihenfolge abzuhalten. Es kann vorkommen dass es Zwänge gibt die einen
Ablauf der exakt dem Schema folgt unmöglich machen. Das ist in Ordnung, das JugRank System funktioniert inmmer noch sehr gut wenn man sich
nicht exakt an die Empfehlungen der Spielpaarungen h&auml;lt</p>

<form action="JugRankForm.php" method="GET">

<h3>Mannschaften</h3>
<p>Bitte im Format "Mannschaft:Geschaetzte Spielstaerke" eintragen.Also z.B. "HLU:80". Jeweils eine Mannschaft pro Zeile
</p>
<textarea cols="100" rows="6" name="teams"><?=$_REQUEST[teams]?></textarea>
<br/>
<br/>

<h3>Spielergebnisse</h3>
<p>Bitte im Format "MannschaftA:MannschaftB=PunkteA:PunkteB" eintragen.Also z.B. "HLU:Rigor=7:12". Jeweils ein Spielergebnis pro Zeile
</p>
<textarea cols="100" rows="10" name="gameresults"><?=$_REQUEST[gameresults]?></textarea>
<br/>
<br/>
Zufallsfaktor f&uuml;r neue Paarungsvorschl&auml;ge (von 0 = kein Zufall &uuml;ber 1 (Standardwert) bis 100 = fast rein zufaellig<br/>
<input type="text" value="<?=$randomfactor?>" name="randomfactor" />
<br/>
<br/>
<input type="checkbox" value="1" name="weightedgames" <?=$_REQUEST['weightedgames'] ? 'checked' : ''?> /> Spiele automatisch gewichten
<br/>
<br/>

<input type="submit" value="Rangliste Berechnen" />

</form>
<?php
	echo "<h3>Vorschl&auml;ge f&uuml;r n&auml;chste Paarungen</h3>";
	echo "<textarea rows='8' cols='100'>";
	$proposed = $jscore->proposeGames($randomfactor);
	foreach ($proposed as $p) {
		list($teamA,$teamB) = $p;
		echo htmlentities("$teamA:$teamB=?:?\n");
	}
	echo "</textarea>";
	if ($_REQUEST['gameresults']) {
		echo "<h3>Vorschlaege f&uuml;r Entscheidungsspiele</h3>";
		echo "<textarea rows='4' cols='100'>";

		$proposed = $jscore->proposeReplays();
		foreach ($proposed as $p) {
			list($teamA,$teamB) = $p;
			echo htmlentities("$teamA:$teamB=?:?\n");
		}
		echo "</textarea>";
	}
?>