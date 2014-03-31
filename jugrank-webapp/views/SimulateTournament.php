

<h2>Turnier simulieren</h2>
<p>
Auf dieser Seite ist es m&ouml;glich, Turniere anhand verschiedener Verfahren zu simulieren. Dabei werden zuf&auml;llige Teams zusammengestellt
die dann virtuell gegeneinander antreten. Jedes Team hat eine mittlere Spielst&auml;rke und eine Standardabweichung, mit der angegeben wird wie
stark die Stärke dieses Teams variiert. Bei der Simulation haben Spielergebnisse immer einen Gaussverteilten Zufallsfaktor, dessen St&auml;rke
von der Varianz der Spielst&auml;rke der beteiligten Teams abh&auml;ngt. </p>

<p>Details zum den Simulationsverfahren und zur Fehlerberechnung finden sich im Quellcode. Jeder ist eingeladen, diese Simulationsverfahren zu kritisieren 
und zu verbessern</p>


<form action="index.php?page=SimulateTournament" method="POST">
	<table border="0" cellpadding="10">
	<tr>
	<td>
	<b>Turnier-Typ</b><br/>
	<select name="type">
		<option value="jugrank" <?= ($_REQUEST['type']=='jugrank') ? 'selected="selected"' : '' ?>>JugRank System</option>
		<option value="swiss" <?= ($_REQUEST['type']=='swiss') ? 'selected="selected"' : '' ?>>Schweizer System</option>
		<option value="groupko" <?= ($_REQUEST['type']=='groupko') ? 'selected="selected"' : '' ?>>Gruppen + KO System (wie DM 2009, Berlin)</option>
	</select>
	</td>
	<td>
	<td>
	<b>Team-Anzahl</b><br/>
	<select name="teamcount">
		<option selected="selected"><?= isset($_REQUEST['teamcount']) ? $_REQUEST['teamcount'] : 28 ?></option>
		<option>8</option>
		<option>9</option>
		<option>10</option>
		<option>11</option>
		<option>12</option>
		<option>13</option>
		<option>14</option>
		<option>15</option>
		<option>16</option>
		<option>17</option>
		<option>18</option>
		<option>19</option>
		<option>20</option>
		<option>21</option>
		<option>22</option>
		<option>23</option>
		<option>24</option>
		<option>25</option>
		<option>26</option>
		<option>27</option>
		<option>28</option>
		<option>29</option>
		<option>30</option>
		<option>31</option>
		<option>32</option>
		<option>33</option>
		<option>34</option>
		<option>35</option>
		<option>36</option>
		<option>37</option>
		<option>38</option>
		<option>39</option>
		<option>40</option>
		<option>41</option>
		<option>42</option>
		<option>43</option>
		<option>44</option>
		<option>45</option>
		<option>46</option>
		<option>47</option>
		<option>48</option>
		<option>49</option>
		<option>50</option>
	</select>
	</td>
	<td>
	<b>Rundenanzahl</b><br/>
	<select name="rounds">
		<option selected="selected"><?= isset($_REQUEST['rounds']) ? $_REQUEST['rounds'] : 6 ?></option>
		<option>3</option>
		<option>4</option>
		<option>5</option>
		<option>6</option>
		<option>7</option>
		<option>8</option>
		<option>9</option>
		<option>10</option>
		<option>11</option>
		<option>12</option>
		<option>13</option>
		<option>14</option>
	</select>
	</td>
	<tr>
	<td>
	<b>Zufallsbegegnungen (Faktor, Standard ist 1)</b><br/>
	<select name="randomfactor">
		<option selected="selected"><?= isset($_REQUEST['randomfactor']) ? $_REQUEST['randomfactor'] : 1?></option>		
		<option>0.0</option>
		<option>0.1</option>
		<option>0.2</option>
		<option>0.3</option>
		<option>0.4</option>
		<option>0.5</option>
		<option>0.6</option>
		<option>0.7</option>
		<option>0.8</option>
		<option>0.9</option>
		<option>1</option>
		<option>1.5</option>
		<option>2</option>
		<option>4</option>
		<option>10</option>
		<option>100</option>
	</select>
	</td>
	<td colspan="3">
	<input type="checkbox" value="1" name="noautorank" <?= isset($_REQUEST['noautorank']) ? 'checked="checked"' : ''?>/><b>Automatisches Gewichten von Begegnungen (nur bei JugRank System)</b>
	</td>
	</tr>
	</table>
	<center><input type="submit" style='font-size: 1.2em;' name="simulate" value="Simulation ausf&uuml;hren" /></center>
</form>
<hr/>
<?php
if ($_REQUEST['simulate']) {
	
	require_once('logic/ranksimulator.php');

	$teamcount = $_REQUEST['teamcount'];
	if (!$teamcount) {
		$teamcount = 25;
	}

	$randomfactor = $_REQUEST['randomfactor'];
	$autorank = $_REQUEST['autorank'];
	if (!$randomfactor) {
		$randomfactor = 0.5;
	}
	$errorTopSum = 0;
	$errorSum = 0;
	$repetitions = 30;
	for ($i=0;$i<$repetitions;$i++) {
		$sim = new RankingSimulator();
		$sim->setRandomTeams($teamcount);

		if ($_REQUEST['type']=='swiss') {
			$jsim = new SwissTournamentSimulator($sim);
			if ($_REQUEST['rounds']) {
				$jsim->setRounds($_REQUEST['rounds']);
			}
		} else if ($_REQUEST['type']=='jugrank') {
			$jsim = new JugRankTournamentSimulator($sim);
			if (!$_REQUEST['noautorank']) {
				$jsim->setAutorank(true);
			} {
				$jsim->setAutorank(false);
			}
			$jsim->setRandomPairs($randomfactor);
			if ($_REQUEST['rounds']) {
				$jsim->setRounds($_REQUEST['rounds']);
			}

		} else {
			$jsim = new GroupKOTournamentSimulator($sim);
			if ($teamcount>20) {
				$jsim->setGroupCount(8);
			} else {
				$jsim->setGroupCount(4);
			}
		}
		$jsim->setVerbosity(1);	
		$results = $jsim->simulateTournament();

		$errorTop = $sim->calculateTournamentError($results, 0.0);
		$error = $sim->calculateTournamentError($results, 0.5);
		$errorTopSum += $errorTop;
		$errorSum += $error;
	}
	$avgError = round($errorSum/$repetitions,2);
	$avgErrorTop = round($errorTopSum/$repetitions,2);
	$messages = $jsim->messages;
	$gamecount = $jsim->games;
	echo "<h2>Turnier Simulation durchgef&uuml;hrt</h2>";
	echo "<br/><b>Endergebnis nach $gamecount Spielen</b><br/>";
	echo "<h4>Fehlerbewertung</h4>";
	echo "<p>Desto naeher an 0, desto besser</p>";
	echo "<b>Fehler in der Rangliste:</b> $error - Durchschnittswert: $avgError<br/>";
	echo "<b>Fehler in den ersten 3 Positionen</b> $errorTop - Durchschnittswert: $avgErrorTop<br/>";
	echo "Durchschnittswerte anhand von $repetitions Wiederholungen (mit unterschiedlichen Teams).<br/>";
	echo "<h5>Logfile der Fehlerbewertungen</h5>";
	echo "<div style='background-color: #ffeeee;overflow:auto; height: 150; width: 100%;'>";

	echo implode("<br/>", $sim->errormessages);
	echo "</div>";

	echo "<h2>Turnier Logfile</h2>";
	echo "<div style='background-color: #ffffee;overflow:auto; height: 300; width: 100%;'>";
	echo implode("<br/>", $messages);
	echo "</div>";
	
}
?>