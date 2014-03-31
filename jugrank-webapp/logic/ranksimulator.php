<?php

require_once('jugrank.php');

function sgn($a) {
	if ($a>0) return 1;
	if ($a<0) return -1;
	return 0;
}
class RankingSimulator {

	public function __construct() {	
		$this->setAveragePointsPerTeam(9);
		$this->setMaxPointsPerTeam(25);
		$this->setStdDevPointsPerTeam(1.5);
		$this->setRandomTeams(22);
	}

	public function setRandomTeams($num, $min=10, $max=100) {
		$teams = array();
		for ($i=0;$i<$num;$i++) {
			$strength = mt_rand($min/2, $max/2) + mt_rand($min/2, $max/2);
			//$strength = gauss_ms($avgStrength, $stddev);
			$tstddev = mt_rand($strength/40,$strength/10);
			if ($tstddev<1) {
				$tstddev=1;
			}
			$teams["RTeam#$i"] = new SimTeam("RTeam#$i", $strength, $tstddev);
		}
		$this->teams = $teams;
	}

	public function setTeams($teams) {
		$this->teams = $teams;
	}

	public function simulateGameResults($teamA,$teamB) {
		return $this->simulateGameResultsImplementation1($teamA,$teamB, 0.4);
	}	
	
	public function setAveragePointsPerTeam($points) {
		$this->averagePointsPerTeam = $points;
	}

	public function setStdDevPointsPerTeam($points) {
		$this->stdDevPointsPerTeam = $points;
	}

	public function setMaxPointsPerTeam($points) {
		$this->maxPointsPerTeam = $points;
	}

	public function simulateGameResultsImplementation1($teamA,$teamB, $power) {
		// Gesamtanzahl der Punkte die in diesem Spiel gemacht werden berechnen
		
		$totalPoints = gauss_ms($this->averagePointsPerTeam*2, $this->stdDevPointsPerTeam*2);
		if ($totalPoints<2) $totalPoints = 2;
		// Aktuelle 
		$strengthA = gauss_ms($teamA->strength, $teamA->stddev);
		$strengthB = gauss_ms($teamB->strength, $teamB->stddev);
		
		if ($strengthA<1) {
			$strengthB -= $strengthA-1;
			$strengthA = 1;
		}
		if ($strengthB<1) {
			$strengthA -= $strengthB-1;
			$strengthB = 1;
		}
		$pointsA = round($totalPoints*(0.5 + 0.5 * sgn($strengthA-$strengthB)*(pow(abs($strengthA-$strengthB), $power) / pow($strengthA+$strengthB, $power))));
		$pointsB = round($totalPoints*(0.5 + 0.5 * sgn($strengthB-$strengthA)*(pow(abs($strengthB-$strengthA), $power) / pow($strengthB+$strengthA, $power))));
		if ($pointsA==$pointsB) {
			if ($strengthA>$strengthB) {
				$pointsA+=1;
			} else if ($strengthB>$strengthA) {
				$pointsB+=1;
			} else if (round(random_0_1())==1) { 
				$pointsA += 1;	
			} else {
				$pointsB += 1;
			}
		}
		if (!$teamA->name) throw new Exception("No Team Name");
		return array($pointsA,$pointsB,$strengthA,$strengthB);
	}


	// Berechnet einen numerischen Fehlerwert fuer ein gegebenes Turnierergebnis.
	// Dabei wird eine Rangliste der Teams anhand ihrer tatsaechlichen Staerke 
	// mit der vom Turnier produzierten Rangliste verglichen.
	// Ein Rueckgabewert von 0 entspricht keinem Fehler, also uebereinstimmung der Listen.
	// Desto hoeher der Rueckgabewert, desto groesser die Diskrepanz der Listen.
	// Fehler in den ersten Positionen fliessen besonders stark in die Wertung ein.
	// Fehler die auftreten obwohl zwei Teams deutliche Staerkeunterschiede haben werden auch etwas verstaerkt gewertet.
	public function calculateTournamentError($rankedTeamNames, $bodyFactor=0.5) {
		$realRanks = array();
		$tournamentRanks = array();
		$this->errormessages = array();
		// Echte Rangliste und Turnier-Rangliste vorbereiten
		foreach ($rankedTeamNames as $rank =>$teamName) {
			$simteam = $this->teams[$teamName];
			$realRanks[$teamName] = $simteam->strength;
			$tournamentRanks[$teamName] = $rank;
		}
		// Echte Rangliste sortieren
		arsort($realRanks);
		$i=0;
		$error = 0;
		foreach ($realRanks as $team=>$strength) {
			// Differenz der Positionen bestimmen.
			$rankDiff = abs($i - $tournamentRanks[$team]);
			$i++;
			if ($rankDiff==0) continue; // Wenn kein Fehler, dann weiter
			$perror = $error;
			$strength = $this->teams[$team]->strength;			
			$factor = 1;
			// Fehler an der Spitze wiegen schwerer
			if ($rankDiff>0) {
				switch ($tournamentRanks[$team]) {
					case 0:
						$error += 3;
						$factor = 3;
						break;
					case 1:
						$error += 2;
						$factor = 2;
						break;
					case 2:
						$error += 1;
						$factor = 1.5;
						break;
					default: 
						// Fehler hinter den ersten drei Plaetzen werden deutlich geringer gewichtet.
						// Gehen aber dennoch ein.
						$factor = $bodyFactor;
				}
			}
			// Vergleich mit dem oberen Nachbar
			$neighbourA = $rankedTeamNames[$tournamentRanks[$team]-1];
			if ($neighbourA) {
				$neighbourAStrength = $this->teams[$neighbourA]->strength;
				$quot = $strength/$neighbourAStrength;
				// Fehler bei leichten Staerkeunterschieden wiegen schwerer als solche bei schwachen.
				// Ein Wert groesser 1 bedeutet dass der in der Rangliste obere Nachbar schwaecher ist
				if ($quot>1.05) {
					$error += $factor;
				}
				if ($quot>1.1) {
					$error += $factor;
				}
				if ($quot>1.2) {
					$error += $factor;
				}
			} 
			
			
			$error += $factor * $rankDiff;
			$points = $error - $perror;
			if ($points>0) {
				$this->errormessages[] = "Team $team an Position ".($tournamentRanks[$team]+1)." statt an $i : $points Fehlerpunkte";
			}
		}
		return $error;
	}


}

// Repraesentiert ein simuliertes Team
// Eigenschaften sind:
// name : Name des Teams
// strength: Staerke des Teams (i.A. ein numerischer Wert von 20 bis 100)
// stddev: Standardabweichung der Staerke. Gibt an, wie konstant die Staerke dieses Teams ist.
class SimTeam {

	public function __construct($name, $averageStrength, $stdDeviation) {
		$this->name = $name;
		$this->strength = $averageStrength;
		$this->stddev = $stdDeviation;
	}
}

// Abstrakter Turniersimulator ohne konkrete Implementierung.
abstract class TournamentSimulator {
	
	// Konstruktor - erwartet einen RankingSimulator als Argument, bei dem bereits Teams eingetragen sind.
	public function __construct($rankingSimulator) {
		$this->sim = $rankingSimulator;
		$this->messages = array();
	}

	// Setzt, ob Textausgaben ueber den Verlauf der Simulation geloggt werden sollen.
	public function setVerbosity($verbosity=0) {
		$this->verbosity = $verbosity;
	}

	public function log($message) {
		if ($this->verbosity) {
			$this->messages[] = $message;
		}
	}

	// Turnier simulieren. Soll eine Rangliste (Array von Team-Namen) zurueckgeben.
	abstract public function simulateTournament();

}

// Simulator fuer Turniere nach dem Schweizer System mit Buchholz-Zahl und Feinbuchholz-Zahl
class SwissTournamentSimulator extends TournamentSimulator {
	
	// Rundenzahl kann festgelegt werden. Wird sie nicht vorher festgelegt, wird eine gute Rundenzahl nach einer Formel von Dr. Model bestimmt.
	public function setRounds($numRounds) {
		$this->rounds = $numRounds;
	}

	// Turnier simulieren. Gibt eine Rangliste (Array von Team-Namen) zurueck.
	// Verwendet werden dabei die Teams, die im Konstruktor uebergeben wurden.
	public function simulateTournament() {
		// Das JugRank Objekt wird zur Berechnung der Sieganzahlen, Buchholz- und Feinbuchholzzahlen benoetigt.
		$jrank = new JugRank();
		$sim = $this->sim;
		$teams = array();
		$this->log("Simuliertes Turnier nach Schweizer System");
		$this->log("Teilnehmende Teams:");
		// In der ersten Runde werden die Teams ausgelost. 
		foreach ($sim->teams as $t) {
			// Jedem Team wird ein zufaelliger Staerkewert zugeordnet
			$teams[$t->name] = mt_rand(0,10000000);
			$this->log("Team $t->name - Tatsaechliche Staerke $t->strength - Standardabweichung $t->stddev");
			$jrank->addTeam($t->name, gauss_ms($t->strength, 6+$t->stddev));
		}
		$rounds = $this->rounds;
		if (!$rounds) {
			// Wenn die Zahl der Runden nicht festgesetzt wurde, ermitteln wir eine automatisch
			$rounds = round(count($teams)/5 + 3.5); // Entspricht einer optimalen Rundenberechnung f. 2,5 Plaetze nach Dr. Model
		}
		$this->log("Es werden $rounds Runden mit ".count($teams)." Teams gespielt");

		// Zufaellige Paarungen bestimmen
		arsort($teams);
		$played = array(); // Array in dem sich gemerkt wird, wer schon gegen wen gespielt hat. Wiederholungen gibt es nicht.
		for($round=0;$round<$rounds;$round++) {
			$this->log("Runde 1 - Spiele");
			$teams_paired = array(); // Hier merken wir uns, wer in dieser Runde schon fuer ein Spiel eingeteilt wurde.
			$games = array(); // Hier werden die Spiele fuer diese Runde eingeteilt. 
			foreach ($teams as $teamA=>$scoreA) {
				if ($teams_paired[$teamA]) continue; // Schon eingeteilt ? Dann weiter
				foreach ($teams as $teamB=>$scoreB) {
					if ($teamA==$teamB) continue;
					if ($teams_paired[$teamB]) continue; // Schon eingeteilt ? Dann weiter ..
					if ($played["$teamA VS $teamB"]) continue; // Schon gegeneinander gespielt ? Auch weiter ..
					$games[] = array($teamA,$teamB); // All das trifft nicht zu ? Dann Spiel einteilen
					$teams_paired[$teamA]=true;
					$teams_paired[$teamB]=true;
					$played["$teamA VS $teamB"] = true;
					$played["$teamB VS $teamA"] = true;
					break;
				}
			}
			// Eingeteilte Spiele simulieren
			$proposedGames = $games;
			foreach ($proposedGames as $pgame) {
				list($teamA,$teamB) = $pgame;
				// Bei der Simulation wird ein Messfehler simuliert indem die Staerke der Teams anhand ihrer
				// zugeordneten Standardabweichung mit einem Gaussverteilten Zufallswert modifiziert wird.
				list($pointsA,$pointsB,$sm,$sb) = $this->sim->simulateGameResults($sim->teams[$teamA], $sim->teams[$teamB]);
				// Spielergebnis aufzeichnen
				$this->log("$teamA VS $teamB = $pointsA:$pointsB");
				$jrank->addGameResult($teamA,$teamB, $pointsA,$pointsB, 1.0);
			}
			// Wertungen fuer die Teams neu berechnen
			$jrank->calculate(true);
			// Hier werden die Anzahl der Siege, Buchholzzahl und Feinbuchholzzahl berechnet.
			$jrank->calculateSwiss();
			
			// Neue Rangliste erstellen, wobei nach: 
			// - Anzahl der Siege
			// - Buchholzzahl
			// - Feinbuchholzzahl
			// absteigend sortiert wird.
			$nteams = array();
			foreach ($teams as $team=>$score) {
				$nteams[$team] = sprintf("%05u %08u %08u", $jrank->wins[$team], $jrank->buchholz[$team], $jrank->feinbuchholz[$team]);
			}	
			// Sortieren der Teams, und weiter gehts
			arsort($nteams);
			$this->log("Runde beendet. Aktuelle Rangliste:");
			$rnk=1;
			foreach ($nteams as $team=>$score) {
				$this->log("$rnk.) $team ($score)");
				$rnk++;
			}
			$teams = $nteams;
		}
		$this->log("Turnier beendet. Letzte Rangliste ist Endergebnis. Es wurden ".count($jrank->games). " Spiele gespielt");
		// Fertig ? Dann anzahl der Spiele merken und letzte berechnete Rangliste zurueckgeben
		$this->games = count($jrank->games);
		return array_keys($teams);
	}

}

// Turniersimultator nach dem JugRank 
class JugRankTournamentSimulator extends TournamentSimulator {

	// Rundenzahl festlegen. Wenn keine festgelegt wird, wird eine Rundenzahl nach einer Faustformel anhand der Anzahl der
	// Turnierteilnehmer bestimmt. Diese Faustformel produziert immer weniger Runden als die Faustformel fuer das Schweizer
	// System.
	public function setRounds($numRounds) {
		$this->rounds = $numRounds;
	}

	public function simulateTournamentImpl($rounds=4, $autorank=true, $randomPairFactor=0.5) {
		$jrank = new JugRank();
		$sim = $this->sim;
		$rounds = $this->rounds;
		$this->log("Simuliertes Turnier nach JugRank System. Autorank=$autorank - Zufallspaarungsfaktor=$randomPairFactor");
		$this->log("Teilnehmende Teams:");		
		// Staerke raten um gute Anfangspaarungen berechnen zu koennen. Dabei fliesst natuerlich ein Schaetzfehler (Gaussverteilt) ein.
		foreach ($sim->teams as $t) {
			$this->log("Team $t->name - Tatsaechliche Staerke $t->strength - Standardabweichung $t->stddev");
			$jrank->addTeam($t->name, gauss_ms($t->strength, 6+$t->stddev));
		}
		if (!$rounds) {
			// Wenn die Zahl der Runden nicht festgelegt ist, anhand dieser Faustformel bestimmen.
			$rounds = round((count($sim->teams)/8) + 3); 
		}
		$this->log("Es werden zunaechst $rounds Runden mit ".count($sim->teams). " Teams gespielt");
		// Normale Spielrunden durchfuehren
		for ($round=0;$round<$rounds;$round++) {
			$this->log("Runde $round");
			$proposedGames = $jrank->proposeGames($randomPairFactor);
			foreach ($proposedGames as $pgame) {
				list($teamA,$teamB) = $pgame;
				list($pointsA,$pointsB,$sm,$sb) = $this->sim->simulateGameResults($sim->teams[$teamA], $sim->teams[$teamB]);
				$jrank->addGameResult($teamA,$teamB, $pointsA,$pointsB);
				$this->log("$teamA VS $teamB = $pointsA:$pointsB");
			}		
			$this->log("Neue Rangliste wird berechnet:");
			$ranks = $jrank->calculate($autorank);

			$rnk=1;
			foreach ($jrank->teams as $team=>$score) {
				$this->log("$rnk.) $team ($score)");
				$rnk++;
			}

		}
		arsort($ranks);
		// Finalspiele
		$this->log("Finalspiele: Jeder gegen jeden in den Top 4. Dazu Entscheidungsspiele auch weiter unten.");
		$finals = array();
		$top4 = array_slice(array_keys($ranks), 0, 4);

		// Jeder der Top4 spielt gegen jeden anderen in den Top4
		foreach ($top4 as $a) {
			foreach ($top4 as $b) {
				if (($a!=$b) && (!$finals["$b -VS- $a"])) {
					$finals["$a -VS- $b"]=array($a,$b);
				}
			}
		}
		// Entscheidungsspiele werden angesetzt fuer alle Faelle in denen direkter Vergleich und Ranking nicht uebereinstimmen
		$replays = $jrank->proposeReplays();
		
		foreach ($replays as $replay) {
			list($a,$b) = $replay;
			$finals["$a -VS- $b"]=array($a,$b);
		}

		$proposedGames = $finals;
		foreach ($proposedGames as $pgame) {
			list($teamA,$teamB) = $pgame;
			list($pointsA,$pointsB,$sm,$sb) = $this->sim->simulateGameResults($sim->teams[$teamA], $sim->teams[$teamB]);
			
			// Finalspiele erhalten ein leicht erhoehtes Gewicht. Das verbessert zwar nicht die Qualitaet des Rankings
			// aber die Spannung.
			$jrank->addGameResult($teamA,$teamB, $pointsA,$pointsB, 1.2);
			$this->log("$teamA VS $teamB = $pointsA:$pointsB");
		}
		// JugScore und ELO Ranks berechnen.
		$this->log("Finale Rangliste wird berechnet:");
		$jrank->calculate(true);
		$jrank->calculateEloRanks();	
		// Anzahl der gespielten Spiele merken.	
		$this->games = count($jrank->games);
		return $jrank;
	}

	// Spiele mit gleichstarken Gegnern staerker gewichten als solche bei starken Ungleichgewichten ?
	public function setAutorank($ar) {
		$this->autorank = $ar;
	}

	// Zufallsfaktor bei der Bestimmung von Paarungen in den Runden
	public function setRandomPairs($rnd=0.5) {
		$this->randompairs = $rnd;
	}

	// Turnier simulieren
	public function simulateTournament() {
		if (!isset($this->autorank)) {
			$this->autorank = true;	
			}
			if (!isset($this->randompairs)) {
			$this->randompairs = 0.5;	
		}
		$jrank = $this->simulateTournamentImpl($this->autorank, $this->randompairs);
		$teams = $jrank->teams;
		arsort($teams);

		$rnk=1;
		$this->log("Turnier beendet. Rangliste nach JugRank:");
		foreach ($teams as $team=>$score) {
			$this->log("$rnk.) $team ($score)");
			$rnk++;
		}
		return array_keys($teams);
	}

}

// Turniersimultator nach dem Gruppen + KO System, vergleichbar 
// Dem Verfahren bei der DM 2009: http://jugger.de/events/berlin_ergeb_09.html
class GroupKOTournamentSimulator extends TournamentSimulator {

	public function setGroupCount($groupcount) {
		$this->groupcount = $groupcount;
	}

	// Turnier simulieren
	public function simulateTournament() {
		$sim = $this->sim;
		$groups = array();
		$groupcount = $this->groupcount;
		if (!$groupcount) {
			$groupcount = 8;
		}
		$this->gamecount = 0;
		$teams = array();
		$this->log("Gruppen + KO Turnier mit $groupcount Gruppen");
		foreach ($sim->teams as $t) {
			// Staerke schaetzen, mit Standardfehler
			$teams[$t->name] = gauss_ms($t->strength, 6+$t->stddev);
		}
		arsort($teams);
		for ($i=0;$i<count($groupcount);$i++) {
			$groups[$i] = array();
		}
		$i = 0;
		// Es wird der geschaetzten Staerke nach gesetzt.
		foreach ($teams as $team=>$s) {
			if (( $i % 2)==0) {
				$pos = ($i/2) % $groupcount;
				$groups[$pos][] = $team;
				$this->log("Team $team in Gruppe $pos");
			} else {
				if (( $i % 2)==1) {
					$pos = $groupcount - 1 - ((($i-1)/2) % $groupcount);
					$groups[$pos][] = $team;
					$this->log("Team $team in Gruppe $pos");
				}
			}
			$i++;	
		}
		// Gruppenspiele austragen
		$groupresults = array();
		$this->log("Spiele Gruppenspiele");
		foreach ($groups as $idx=>$group) {
			$this->log("Spiele der Gruppe $idx");
			$groupresults[$idx] = $this->playGroup($group);
		}
		$max = ceil(count($sim->teams)/$groupcount);
		$kogroups = array();
		for ($i=0;$i<$max;$i++) {
			$kogroups[$i] = array();
			foreach ($groupresults as $gres) {
				if ($gres[$i]) {
					$kogroups[$i][] = $gres[$i];
				}
			}
		}
		$results = array();
		$this->log("Spiele KO Runden\n");
		$i=0;
		foreach ($kogroups as $kogroup) {
			$i++;
			if ((is_array($kogroup)) && (count($kogroup)>1)) {
				$this->log("KO Runde der Gruppen $i-ten\n");
				$results = array_merge($results, $this->playKOGroup($kogroup));
			} else if (count($kogroup)==1) {
				$results = array_merge($results, $kogroup);
			}
		}
		$this->games = $this->gamecount;
		return $results;
	}

	public function playKOGroup($group) {
		$jrank = new JugRank();
		while (count($group)>1) {
			list($winners, $losers) = $this->playKO($group, $jrank);
			$group = $winners;
		}
		$jrank->calculate(true);
		$jrank->calculateSwiss();
		$nteams = array();
		foreach ($jrank->teams as $team=>$score) {
			if ($team!="__FREILOSGEGNER") {
				$nteams[$team] = sprintf("%05u %08u", $jrank->wins[$team], $jrank->punkte[$team]);
			}
		}	
		// Sortieren der Teams, und weiter gehts
		$this->gamecount += count($jrank->games);
		arsort($nteams);
		$res = array_keys($nteams);
		if (count($res)>3) {
			// Spiel um PLatz 3 dieser KO Gruppe
			$this->log("Spiel um den dritten Platz dieser KO Gruppe:");
			list($pointsA,$pointsB,$sm,$sb) = $this->sim->simulateGameResults($this->sim->teams[$res[2]], $this->sim->teams[$res[3]]);
			$this->log("$res[3] VS $res[4] = $pointsA : $pointsB");
			$this->gamecount++;
			if ($pointsB>$pointsA) {
				// Entscheidungsspiel entscheidet Positionen
				$tmp = $res[2];
				$res[2] = $res[3];
				$res[3] = $tmp;
			}
		}
		return $res;
	}


	public function playKO($group, $jrank) {
		$sim = $this->sim;
		$remaining = array();
		$this->log("KO-Runde mit ".count($group). " Teams");
		for ($i=1;$i<count($group);$i+=2) {
			$teamA = $group[$i-1];
			$teamB = $group[$i];
			if ($teamA && $teamB) {
				list($pointsA,$pointsB,$sm,$sb) = $this->sim->simulateGameResults($sim->teams[$teamA], $sim->teams[$teamB]);
				$this->log("$teamA VS $teamB = $pointsA:$pointsB");

				$jrank->addGameResult($teamA,$teamB, $pointsA,$pointsB, 1.0);
				if ($pointsA>$pointsB) {
					$winners[] = $teamA;
					$losers[] = $teamB;
				} else {
					$winners[] = $teamB;
					$losers[] = $teamA;
				}
			} 
		}
		if (count($group) % 2) {
			$winners[] = $group[count($group)-1]; // Freilos
			$this->log("Freilos fuer ".$group[count($group)-1]);
			$jrank->addGameResult("__FREILOSGEGNER" ,$group[count($group)-1], 0, 1, 1.0);
		}
		return array($winners, $losers);
	}

	public function playGroup($group) {
		$results = array();
		// Das JugRank Objekt wird zur Berechnung der Sieganzahlen, Buchholz- und Feinbuchholzzahlen benoetigt.
		$jrank = new JugRank();
		$sim = $this->sim;
		$teams = array();
		$this->log("Jeder gegen jeden in Gruppe mit ".count($group)." Teams");
		// In der ersten Runde werden die Teams ausgelost. 
		foreach ($group as $team) {
			$t = $teamsByName[$team];
			$teams[$t->name] = mt_rand(0,10000000);
			$jrank->addTeam($t->name, gauss_ms($t->strength, 6+$t->stddev));
		}
		$played = array();
		// Jeder gegen jeden in der Gruppe
		foreach ($group as $teamA) {
			foreach ($group as $teamB) {
				if ($teamA==$teamB) continue;
				$k = "$teamB VS $teamA";
				$k2 = "$teamA VS $teamB";
				if ($played[$k]) continue;
				$played[$k] = true;
				$played[$k2] = true;
				list($pointsA,$pointsB,$sm,$sb) = $this->sim->simulateGameResults($sim->teams[$teamA], $sim->teams[$teamB]);
				$this->log("$teamA VS $teamB = $pointsA:$pointsB");
				$jrank->addGameResult($teamA,$teamB, $pointsA,$pointsB, 1.0);
			}	
		}
		// Wertungen fuer die Teams neu berechnen
		$jrank->calculate(true);
		// Hier werden die Anzahl der Siege, Buchholzzahl und Feinbuchholzzahl berechnet.
		$jrank->calculateSwiss();
			
		// Neue Rangliste erstellen, wobei nach: 
		// - Anzahl der Siege
		// - Buchholzzahl
		// - Feinbuchholzzahl
		// absteigend sortiert wird.
		$nteams = array();
		foreach ($jrank->teams as $team=>$score) {
			$nteams[$team] = sprintf("%05u %08u", $jrank->wins[$team], $jrank->punkte[$team]);
		}	
		// Sortieren der Teams, und weiter gehts
		arsort($nteams);
		$this->log("Rangliste nach Gruppenspielen: ".implode(", ", array_keys($nteams)));
		$this->gamecount += count($jrank->games);
		return array_keys($nteams);
	}
		
}

?>