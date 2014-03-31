<?php
require_once('ranksimulator.php');

// Experiment durchfuehren
$results = array();
// Fuer verschiedene Teamzahlen, von 8 bis 40 in einem Turnier die Berechnungen durchfuehren
for ($teamcount=14;$teamcount<42;$teamcount+=3) {
	$errsums = array();
	$gamecount = array();
	echo "Teams: $teamcount\n";
	// Experiment X mal mit jeweils unterschiedlichen Zufallsteams, aber gleicher TEamzahl wiederholen

	$results[$teamcount] = array();

	for ($repetitions=0;$repetitions<100;$repetitions++) {
		echo "Repetition $repetitions\n";
		$sim = new RankingSimulator();
		$sim->setRandomTeams($teamcount);
		$jsims = array();
		$jsims['group+ko'] = new GroupKOTournamentSimulator($sim);
		if ($teamcount>20) {
			$jsims['group+ko']->setGroupCount(8);
		} else {
			$jsims['group+ko']->setGroupCount(4);
		}
		$jsims['jugrank-4-rounds-ar-0.2'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-4-rounds-ar-0.2']->setAutorank(true);
		$jsims['jugrank-4-rounds-ar-0.2']->setRandomPairs(0.2);
		$jsims['jugrank-4-rounds-ar-0.2']->setRounds(4);

		$jsims['jugrank-5-rounds-ar-0.2'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-5-rounds-ar-0.2']->setAutorank(true);
		$jsims['jugrank-5-rounds-ar-0.2']->setRandomPairs(0.2);
		$jsims['jugrank-5-rounds-ar-0.2']->setRounds(5);

		$jsims['jugrank-6-rounds-ar-0.2'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-6-rounds-ar-0.2']->setAutorank(true);
		$jsims['jugrank-6-rounds-ar-0.2']->setRandomPairs(0.2);
		$jsims['jugrank-6-rounds-ar-0.2']->setRounds(6);

		$jsims['jugrank-7-rounds-ar-0.2'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-7-rounds-ar-0.2']->setAutorank(true);
		$jsims['jugrank-7-rounds-ar-0.2']->setRandomPairs(0.2);
		$jsims['jugrank-7-rounds-ar-0.2']->setRounds(7);

		$jsims['jugrank-7-rounds-noar-0.2'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-7-rounds-noar-0.2']->setAutorank(false);
		$jsims['jugrank-7-rounds-noar-0.2']->setRandomPairs(0.2);
		$jsims['jugrank-7-rounds-noar-0.2']->setRounds(7);

		$jsims['jugrank-7-rounds-ar-0.1'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-7-rounds-ar-0.1']->setAutorank(true);
		$jsims['jugrank-7-rounds-ar-0.1']->setRandomPairs(0.1);
		$jsims['jugrank-7-rounds-ar-0.1']->setRounds(7);

		$jsims['jugrank-8-rounds-ar-0.2'] = new JugRankTournamentSimulator($sim);
		$jsims['jugrank-8-rounds-ar-0.2']->setAutorank(true);
		$jsims['jugrank-8-rounds-ar-0.2']->setRandomPairs(0.2);
		$jsims['jugrank-8-rounds-ar-0.2']->setRounds(8);

		$jsims['swiss-5-rounds'] = new SwissTournamentSimulator($sim);
		$jsims['swiss-5-rounds']->setRounds(5);

		$jsims['swiss-6-rounds'] = new SwissTournamentSimulator($sim);
		$jsims['swiss-6-rounds']->setRounds(6);

		$jsims['swiss-7-rounds'] = new SwissTournamentSimulator($sim);
		$jsims['swiss-7-rounds']->setRounds(7);

		$jsims['swiss-8-rounds'] = new SwissTournamentSimulator($sim);
		$jsims['swiss-8-rounds']->setRounds(8);

		$jsims['swiss-9-rounds'] = new SwissTournamentSimulator($sim);
		$jsims['swiss-9-rounds']->setRounds(9);

		$jsims['swiss-10-rounds'] = new SwissTournamentSimulator($sim);
		$jsims['swiss-10-rounds']->setRounds(10);

		foreach ($jsims as $name=>$jsim) {
			if (!is_array($results[$teamcount][$name])) {
				$results[$teamcount][$name] = array( "errors" => 0, "repetitions" => 0, "error_sum" => 0, "error_top_sum" => 0, "gamecount" => 0 );	
			}
		}
		// Experiment 4 mal mit gleichen Teams wiederholen
		for ($i=0;$i<6;$i++) {
			echo "Iteration $i\n";
			foreach ($jsims as $name=>$jsim) {
				try {
					$res = $jsim->simulateTournament();
					$results[$teamcount][$name]["repetitions"]++;
					$results[$teamcount][$name]["error_sum"]+=$sim->calculateTournamentError($res);
					$results[$teamcount][$name]["error_top_sum"]+=$sim->calculateTournamentError($res, 0.0);
					$results[$teamcount][$name]["gamecount"]+= $jsim->games;
				} catch(Exception $ex) {
					$results["errors"]++;
				}
			}
		}	
	}
	// Ergebnisstatistiken merken
	
}
// Endergebnisse ausgeben
echo "\n\n----- FINAL RESULTS------\n";
echo json_encode($results);
?>