<?php

// Berechnet eine Rangliste anhand von Spielergebnissen
class JugRank {

     // Standard-Konstruktor
     public function _construct() {
	$this->games = array(); 
	$this->elo = array();
	$this->teams = array();
	$this->initialTeams = array();
	$this->teamStdDev = array();
	$this->playcounts = array();
     }

     // Team hinzufuegen mit einem initialen Schaetzwert seiner Spielstaerke (am besten Werte von 20 bis 100)
     // Dieser Schaetzwert fliesst nicht in die Endberechnung der Staerke von Teams ein.
     public function addTeam($team, $initialScore) {
	$this->initialTeams[$team] = $initialScore;
	$this->teams[$team] = $initialScore;
     }

     // Spielergebnis hinzufuegen
     public function addGameResult($teamA, $teamB, $pointsA, $pointsB, $importance=1.0) {
	$this->games[] = array($teamA, $teamB, $pointsA, $pointsB, $importance);
	if (count($this->initialTeams)>0) {
		$this->teams[$teamA] = $this->initialTeams[$teamA];
		$this->teams[$teamB] = $this->initialTeams[$teamB];
		if (!$this->teams[$teamA]) {
			$this->teams[$teamA] = array_sum($this->initialTeams)/count($this->initialTeams);		
			$this->initialTeams[$teamA] = $this->teams[$teamA];
		}
		if (!$this->teams[$teamA]) {
			$this->teams[$teamA] = array_sum($this->initialTeams)/count($this->initialTeams);		
			$this->initialTeams[$teamB] = $this->teams[$teamB];
		}
	} else {
		$this->teams[$teamA] = $this->initialTeams[$teamA] = 60;
                $this->teams[$teamB] = $this->initialTeams[$teamB] = 60;
		$this->initialTeams[$teamB] = $this->teams[$teamB];
		$this->initialTeams[$teamA] = $this->teams[$teamA];
	}
	$this->setPlayCount($teamA, $teamB, $this->getPlayCount($teamA,$teamB)+1);
     }

     // Rangliste mit dem iterativen JugRank verfahren berechnen.
     // Wenn $weightgames gesetzt ist, werden Begegnungen anhand der relativen Staerke der Teams gewichtet.
     // (Gleichstarke Begegnungen erhalten ein hohes, stark ungleiche ein niedrigeres Gewicht)
     public function calculate($weightgames) {
	// Staerke der Teams mit einem Konstanten Startwert festlegen. Falls schon eine Rangliste existiert kann die auch zur
	// Festlegung der Startwerte verwendet werden.
	if (!$this->teams) return;
	foreach ($this->teams as $team=>$score) {
		$this->teams[$team] = 60;
	}
	$this->iterateCalculation(50, $weightgames);
	return $this->teams;
     }

     // Hilfsfunktion: Berechnet einen Einzel-Score. Wird waehrend der iterativen Berechnung benoetigt
     protected function calcSingleScore($teamAScore, $teamBScore, $teamAPoints, $teamBPoints) {
	$factor = sqrt($teamAPoints+1) / sqrt($teamBPoints+1);
	$score = $teamBScore * $factor;
	return $score;
     }

     // Hilfsfunktion: Berechnet das Gewicht einer Begegnung anhand der Spielstaerke der Teams. Ergebnisse liegen i.A. im Bereich 0.7 bis 1.4
     protected function calcGameWeight($teamAScore, $teamBScore) {
	return 1 / pow(0.7+abs($teamAScore-$teamBScore)/($teamAScore+$teamBScore), 1);
     }

     // Berechnet den geometrischen Mittelwert der uebergebenen Werteliste. Kann gewichten.
     protected function geometricAverage($results, $cnt=0, $weights=false) {
	$n = count($results);
	if ($cnt) {
		$n = $cnt;
	}
	$geo = 1;
	$wsum = 0;
	foreach ($results as $i=>$r) {
		$weight = 1.0;
		if ($weights && array_key_exists($i, $weights)) {
			$weight = $weights[$i];
		}
		$wsum += $weight;	
		$geo = $geo * pow($r, $weight);
	}
	if ($wsum==0) return 0;
	return pow($geo, 1/$wsum);
     }


     // Standardabweichung um einen gegebenen Mittelwert ermitteln
     public function stddev($mean, $results) {
		$sumsqdev = 0;
		foreach ($results as $value) {
			$sumsqdev += ($value-$mean)*($value-$mean);
		}
		return sqrt($sumsqdev/count($results));
     }


     // Fuehrt die Berechnung mit einer vorgegebenen Anzahl von Iterationen durch.
     public function iterateCalculation($iterations, $useGameWeight=false) {
	$this->diffs = array();
	if (!$this->games) return;
	for ($iter=0;$iter<$iterations;$iter++) {
		// Datenstruktur initialisieren
		$presults = array();
		$gameWeights = array();

		foreach ($this->teams as $team=>$score) {
			// Da das beste Team nicht gegen sich selber spielen kann, muss man um eine Konvergenz zu erreichen
			// Den aktuellen Score mit einbeziehen. Auf das Endergebnis hat es keinen Einfluss.
			$presults[$team] = array($score);
			$gameWeights[$team] = array(1.0);
		}
		// Fuer jedes Spielergebnis
		$maxgames = 0;
		foreach ($this->games as $game) {
			list($teamA, $teamB, $pointsA, $pointsB, $importance) = $game;
			// Fuer beide Teams die Einzel-Scores berechnen. Dabei wird die Staerke des Teams gegen das gespielt wurde
			// beruecksichtigt. Punkte gegen Starke Teams bringen mehr, als Punkte gegen schlechte Teams.
			$gameWeight = 1.0;
			if ($useGameWeight) {
				$gameWeight = $importance * $this->calcGameWeight($this->teams[$teamA], $this->teams[$teamB]);
			}
			$presults[$teamA][] = $this->calcSingleScore($this->teams[$teamA], $this->teams[$teamB], $pointsA, $pointsB);
			$presults[$teamB][] = $this->calcSingleScore($this->teams[$teamB], $this->teams[$teamA], $pointsB, $pointsA);
			$gameWeights[$teamA][] = $gameWeight;
			$gameWeights[$teamB][] = $gameWeight;
			if ($useGameWeight) {
				// echo "Weight $teamA VS $teamB = $gameWeight<br/>";
			}
		}
		// Mittelwerte der Einzel-Scores berechnen.
		$diff = 0;
		foreach ($this->teams as $team => $score) {
			$newScore = $this->geometricAverage($presults[$team], false, $gameWeights[$team]);
			//echo "Iteration $n: Team $team from $score to $newScore\n";
			$diff += abs($score-$newScore);
			$this->teams[$team] = $newScore;
			$this->teamStdDev[$team] = $this->stddev($newScore, $presults[$team]);
		}
		$this->presults = $presults;
		$this->gameWeights = $gameWeights;
		$this->diffs[$iter] = $diff;
		
	}
     }

     // Findet Faelle in denen berechneter Score und tatsaechliches Spielergebnis (Sieg) abweichen und schlaegt Entscheidungsspiel vor
     public function proposeReplays($maxPreviousPlays=1) {
     	$replays = array();
	foreach ($this->games as $game) {
		list($teamA, $teamB, $pointsA, $pointsB, $importance) = $game;
		if ((($pointsA>$pointsB) && ($this->teams[$teamA]>$this->teams[$teamB])) || 
			(($pointsA<$pointsB) && ($this->teams[$teamA]<$this->teams[$teamB]))) {
			// Spielergebnis wie erwartet, keine Entscheidung noetig
			continue;
		}
		if ($this->getPlayCount($teamA,$teamB)<=$maxPreviousPlays) {
			$replays[] = array($teamA, $teamB);
		}	
	}
	return $replays;
     }

     // Schlaegt neue Paarungen vor. Dabei kann ein Zufallsfaktor mit uebergeben werden. Bei einem Zufallsfaktor von 0 versucht
     // der Algorithmus, gleichstarke Gegner zu paaren sofern sie noch kein Spiel hatten.
     public function proposeGames($random_factor=0.2) {
		// Nehmen wir an, die berechneten Spielwerte sind Schaetzungen mit einem Fehler der einer Gauss-Verteilung entspricht.
		// Dieser Fehler wird umso geringer, je mehr Spiele (=Messungen der Spielstaerke) ein Team durchgefuehrt hat.

		// Hat ein Team jedoch starke schwankungen in seiner Spielstaerke, muss das beruecksichtigt werden. Daher wird
		// Graduell die Standardabweichung von einer allgemeinen auf eine Teamspezifische hin verschoben.
		if (!$this->teams) return array();
		// Zunaechst, Mittelwert und Standardabweichung ueber die Wertungen der Teams berechnen
		$avg_score = array_sum($this->teams)/count($this->teams);
		$sumsqdev = 0;
		foreach ($this->teams as $team=>$score) {
			$sumsqdev += ($score-$avg_score)*($score-$avg_score);
		}
		$stddev_score = sqrt($sumsqdev/count($this->teams));
		// Keine Standardabweichung ? Dann gabs vermutlich noch keine Spiele.
		if ($stddev_score==0) {
			$stddev_score = $avg_score / 4;
		}
		// Diese Faktoren geben an, mit welchem Faktor die allgemeine Standardabweichung zugunsten der spezifischen des Teams
		// verschoben wird. Ein Wert von 0.7 bedeutet, dass die allgemeine zu 0.7 und die spezifische zu 0.3 verwendet wird.
		$stddev_factors = array(1, 0.5, 0.3, 0.2, 0.15, 0.1, 0.5, 0.25, 0.1);
		
		// Wir erstellen jetzt eine neue Rangliste die den Teams eine zufaellige Spielstaerke zuordnet die 
		// mit einer Gauss-Zufallsfunktion um ihren aktuellen Schaetzwert herum liegt. 
		// Dabei wird ihr aktueller Spielstaerkewert als Mittelwert, und eine Mischung aus ihrer eigenen und der
		// allgemeinen Standardabweichung fuer die Standardabweichung verwendet.
		$rnd_ranks = array();
		foreach ($this->teams as $team => $score) {
			$gamecount = count($this->presults[$team])-1;
			if ($gamecount>count($stddev_factors)) {
				$stddev_factor = $stddev_factors[count($stddev_factors)-1];
			} else {
				$stddev_factor = $stddev_factors[$gamecount];
			}
			if ($this->teamStdDev[$team]) {
				$team_stddev = $stddev_score * $stddev_factor + ($this->teamStdDev[$team] * (1-$stddev_factor));
			} else {
				$team_stddev = $stddev_score;
			}
			$randomized_score = gauss_ms($this->teams[$team], $random_factor*$team_stddev);
			$rnd_ranks[$team] = $randomized_score;
		}		
		arsort($rnd_ranks); // Sortieren nach Zufallsmodifizierter Spielstaerke, staerkste nach oben
		$rlist = array_keys($rnd_ranks);
		$proposed = array();
		$eliminated = array();
		for ($i=0;$i<count($rlist);$i++) {
			if ($eliminated[$i]) {
				continue; // Hat schon ein Spiel diese Runde ?
			}
			for ($k=$i+1;$k<count($rlist);$k++) {
				if ($eliminated[$k]) {
					continue; // Hat schon ein Spiel diese Runde ?
				}
				if ($this->getPlayCount($rlist[$i], $rlist[$k])==0) {
					$proposed[] = array($rlist[$i], $rlist[$k]);
					$eliminated[$i] = true;
					$eliminated[$k] = true;
					break;
				} 		
			}
		}
		return $proposed;
     }	
 
     // Kalkuliert anzahl der Siege, Bucholzzahl und Feinbuchholzzahl fuer alle Teams. Kann verwendet werden um
     // Ein Turnier nach Schweizer System auszutragen.
     public function calculateSwiss() {
	$this->wins = array();
	$this->buchholz = array();
	$gegner = array();
	$punkte = array();
	if (!$this->games) throw new Exception( "No Games");
	foreach ($this->teams as $team=>$score) {
		$gegner[$team] = array();
	}
	foreach ($this->games as $game) {
		list($teamA, $teamB, $pointsA,$pointsB, $importance) = $game;
		$this->wins[$teamA] += ($pointsA>$pointsB) ? 1 : 0 ; 
		$this->wins[$teamB] += ($pointsB>$pointsA) ? 1 : 0 ; 
		$gegner[$teamA][$teamB] += 1;
		$gegner[$teamB][$teamA] += 1;
		$punkte[$teamA] += $pointsA;
		$punkte[$teamB] += $pointsB;
	}
	$this->punkte = array($punkte);
	$this->feinbuchholz = array();
	foreach ($gegner as $team=>$others) {
		foreach ($others as $ot=>$num) {
			$this->buchholz[$team] += $punkte[$ot];
		}
	}
	foreach ($gegner as $team=>$others) {
		foreach ($others as $ot=>$num) {
			$this->feinbuchholz[$team] += $this->buchholz[$ot];
		}
	}
     }

     // Kalkuliert den neuen ELO Wert einer Mannschaft anhand eines Spielergebnisses, den vorherigen ELO Werten und einiger Parameter
     public function calculateSingleElo($pointsA, $pointsB, $importance, $k, $quot, $eloA,$eloB) {
		switch(abs($pointsA-$pointsB)) {
			case 0:
			case 1:
				$g = 1;
				break;
			case 2:
				$g = 1.5;
				break;
			default:
				$g = (11 + abs($pointsA-$pointsB) / 8);
		}
		$we = 1 / ( 1+pow(10, -1*($eloA-$eloB)/400));
		if ($pointsA>$pointsB) {
			$w = 1;
		} else if ($pointsA<$pointsB) {
			$w = 0;
		} else {
			$w = 0.5;
		}
		$elo = $eloA + $k * $importance * $g * ($w-$we);
		return $elo;
     }

     // Berechnet die ELO Werte aller Teams anhand der Spielergebnisse die in Eingangsreihenfolge ausgewertet werden.
     public function calculateEloRanks($k=32, $quot=400, $startelo=1500) {
	$elo = array();
	foreach ($this->teams as $team => $score) {
		$elo[$team] = $startelo;
	}
	foreach ($this->games as $game) {
		list($teamA, $teamB, $pointsA,$pointsB, $importance) = $game;
		$elo[$teamA] = $this->calculateSingleElo($pointsA,$pointsB,$importance,$k,$quot,$elo[$teamA],$elo[$teamB]);
		$elo[$teamB] = $this->calculateSingleElo($pointsB,$pointsA,$importance,$k,$quot,$elo[$teamB],$elo[$teamA]);
	}
	arsort($elo);
	$this->elo= $elo;
	return $elo;
     }

     // Hilfsfunktion: Anzahl der Spiele die zwei Teams gegeneinander gefuehrt haben setzen
     public function setPlayCount($teamA, $teamB, $count) {
	$p = array($teamA, $teamB);
	sort($p);
	$key = implode(":\t:", $p);
	$this->playcounts[$key] = $count;
	return $count;
     }	

     // Hilfsfunktion: Anzahl der Spiele die zwei Teams gegeneinander gefuehrt haben auslesen
     public function getPlayCount($teamA, $teamB) {
	$p = array($teamA, $teamB);
	sort($p);
	$key = implode(":\t:", $p);
        if ($this->playcounts[$key]) {
		return $this->playcounts[$key];
	}
	return 0;
     }


}

/*
Random numbers with Gauss distribution (normal distribution).
A correct alghoritm. Without aproximations
*/
function gauss() {   // N(0,1)
    // returns random number with normal distribution:
    //   mean=0
    //   std dev=1
   
    // auxilary vars
    $x=random_0_1();
    $y=random_0_1();
   
    // two independent variables with normal distribution N(0,1)
    $u=sqrt(-2*log($x))*cos(2*pi()*$y);
    $v=sqrt(-2*log($x))*sin(2*pi()*$y);
   
    // i will return only one, couse only one needed
    return $u;
}

function gauss_ms($m=0.0,$s=1.0) {   // N(m,s)
    // returns random number with normal distribution:
    //   mean=m
    //   std dev=s
    return gauss()*$s+$m;
}

function random_0_1()
{   // auxiliary function
    // returns random number with flat distribution from 0 to 1
    return (float)rand()/(float)getrandmax();
}

/*
// Beispielcode zur Anwendung
$jscore = new JugRank();
$jscore->addGameResult("HLU","Rigor", 7,13);
$jscore->addGameResult("HLU","Falco", 9,7);
$jscore->addGameResult("Falco","Rigor", 8,14);
$jscore->addGameResult("Mix","Rigor", 2,23);
$jscore->addGameResult("Mix","HLU", 4,18);
$jscore->addGameResult("Falco","Mix", 20,3);
$results = $jscore->calculate();
arsort($results);

*/
?>