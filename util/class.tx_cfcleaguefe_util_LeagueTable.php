<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

tx_rnbase::load('tx_rnbase_util_DB');
require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'models/class.tx_cfcleaguefe_models_competition_penalty.php');

/**
 * Die Klasse ist in der Lage, Tabellen einer Liga zu berechnen.
 */
class tx_cfcleaguefe_util_LeagueTable  {
  var $_teamData;
  // Daten für die Punktezählung
  var $cfgPointSystem, $cfgPointsDraw, $cfgPointsWin, $cfgPointsLose;
  var $cfgTableType, $cfgTableScope;
  var $cfgChartClubs;
  var $penalties; // Ligastrafen

  function __construct() {
    $this->_teamData = array();
  }

	/**
	 * Die Berechnung der Daten für die Tabellenfahrt. Da der Rückgabewert hier eine andere
	 * Struktur hat, läuft die Berechnung etwas anders.
	 * Was brauchen wir für eine Tabellenfahrt?
	 * - der Tabellenstand muss nach jeden Spieltag berechnet werden
	 * - die Position für für jedes Team in einem Array abgelegt
	 * - Die Punkte und Tore sind für die Ausgabe uninteressant
	 *
	 * @param tx_cfcleaguefe_util_league_TableProvider $tableProvider
	 */
	function generateChartData(&$tableProvider) {
		$this->setTableProvider($tableProvider);
		$this->_initTeams($tableProvider);
		$this->handlePenalties(); // Strafen können direkt berechnet werden
    $xyData = Array();
		$rounds = $tableProvider->getRounds();
    foreach($rounds As $round => $roundMatches) {
			$this->handleMatches($roundMatches);
			// Jetzt die Tabelle sortieren, dafür benötigen wir eine Kopie des Arrays
			$teamData = $this->_teamData;
			usort($teamData, array($this, $this->getCompareMethod()));
			// Nun setzen wir die Tabellenstände
			reset($teamData);
			foreach($teamData As $position => $team) {
				if(in_array($team['clubId'], $this->getTableProvider()->getChartClubs()))
					$xyData[$team['teamName']][$round] = $position +1;
			}
		}
		// Issue 1880245: Chart auf der X-Achse bis Saisonende erweitern
		// Den höchsten absolvierten Spieltag ermitteln
		$lastRound = intval(array_pop(array_keys($rounds))) + 1;
		$maxRound = $this->getTableProvider()->getMaxRounds();
		$teamName = array_pop(array_keys($xyData));
		for( ; $lastRound <= $maxRound; $lastRound++) {
			// Es muss nur für ein Team ein weiterer Wert hinzugefügt werden
			$xyData[$teamName][$lastRound] = null;
		}
		return $xyData;
	}

	/**
	 * Für die Berechnung der Liga benötigen wir eine Datenlieferanten
	 * @param tx_cfcleaguefe_util_league_TableProvider $tableProvider
	 */
	function generateTable(&$tableProvider) {
		$this->setTableProvider($tableProvider);
		$this->_initTeams($tableProvider);
		$this->handlePenalties(); // Strafen können direkt berechnet werden

		$teamData = array();
		$rounds = $tableProvider->getRounds();
		foreach($rounds As $round => $roundMatches) {
			$this->handleMatches($roundMatches);
			// Jetzt die Tabelle sortieren, dafür benötigen wir eine Kopie des Arrays
			$teamData = $this->_teamData;
			usort($teamData, array($this, $this->getCompareMethod()));
			// Nun setzen wir die Tabellenstände
			reset($teamData);
			for($i=0; $i < count($teamData); $i++) {
				$newPosition = $i +1;
				$team = $teamData[$i];
				if($this->_teamData[$team['teamId']]['position']) {
					$oldPosition = $this->_teamData[$team['teamId']]['position'];
					$this->_teamData[$team['teamId']]['oldposition'] = $oldPosition;
					$this->_teamData[$team['teamId']]['positionchange'] = $this->getPositionChange($oldPosition, $newPosition);
				}
				$this->_teamData[$team['teamId']]['position'] = $newPosition;
			}
		}
		usort($this->_teamData, array($this, $this->getCompareMethod()));
		return $this->_teamData;
	}

	private function getCompareMethod() {
		$method = $this->getTableProvider()->getCompareMethod();
		if(!method_exists($this, $method)) throw new Exception('Compare method not available: '.$method);
		return $method;
	}
	/**
	 * Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
	 * für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
	 */
	function handlePenalties() {
		$penalties = $this->getTableProvider()->getPenalties();

    foreach($penalties As $penalty) {
      // Welches Team ist betroffen?
      if(array_key_exists($penalty->record['team'], $this->_teamData)) {
//    t3lib_div::debug($penalty, 'tx_cfcleaguefe_util_LeagueTable'); // TODO: Remove me!
        // Die Strafe wird für den View mit abgespeichert
        // Falls es eine Korrektur ist, dann nicht speichern
				if(!$penalty->isCorrection())
	        $this->_teamData[$penalty->record['team']]['penalties'][] = $penalty;
        // Die Punkte abziehen
        $this->_teamData[$penalty->record['team']]['points'] -= $penalty->record['points_pos'];
        $this->_teamData[$penalty->record['team']]['points2'] += $penalty->record['points_neg'];

        $this->addGoals($penalty->record['team'], ($penalty->record['goals_pos'] * -1), $penalty->record['goals_neg']);

        $this->_teamData[$penalty->record['team']]['matchCount'] += $penalty->record['matches'];
        $this->_teamData[$penalty->record['team']]['winCount'] += $penalty->record['wins'];
        $this->_teamData[$penalty->record['team']]['drawCount'] += $penalty->record['draws'];
        $this->_teamData[$penalty->record['team']]['loseCount'] += $penalty->record['loses'];

        // Den Zwangsabstieg tragen wir nur ein, damit der in die Sortierung eingeht
        if($penalty->record['static_position'])
         $this->_teamData[$penalty->record['team']]['last_place'] = $penalty->record['static_position'];
      }
    }
  }

  /**
   * Die Spiele werden zum aktuellen Tabellenstand hinzugerechnet
   */
  function handleMatches(&$matches) {
    // Wir laufen jetzt über alle Spiele und legen einen Punktespeicher für jedes Team an
    foreach($matches As $match) {
    	if($match->isDummy()) continue; // Ignore Dummy-Matches
      // Wie ist das Spiel ausgegangen?
      $toto = $match->getToto();
			tx_rnbase_util_Misc::callHook('cfc_league_fe','leagueTable_handleMatches',
				array('match' => &$match, 'teamdata'=>&$this->_teamData), $this);

      // Die eigentliche Punktezählung richtet sich nach dem Typ der Tabelle
      // Daher rufen wir jetzt die passende Methode auf
      switch($this->getTableProvider()->getTableType()) {
        case 1 :
          $this->_countHome($match, $toto);
          break;
        case 2 :
          $this->_countGuest($match, $toto);
          break;
        default:
          $this->_countStandard($match, $toto);
      }
    }
		unset($this->_teamData[0]); // Remove dummy data from teams without id
  }

	/**
	 * Zählt die Punkte für eine normale Tabelle
	 * @param tx_cfcleaguefe_models_match $match
	 * @param int $toto
	 */
	function _countStandard(&$match, $toto) {
		// Anzahl Spiele aktualisieren
		$homeId = $this->getTableProvider()->getTeamId($match->getHome());
		$guestId = $this->getTableProvider()->getTeamId($match->getGuest());
		$this->addMatchCount($homeId);
		$this->addMatchCount($guestId);
		// Für H2H modus das Spielergebnis merken
		$this->addResult($homeId, $guestId, $match->getResult());

		if($toto == 0) { // Unentschieden
			$this->addPoints($homeId, $this->getTableProvider()->getPointsDraw());
			$this->addPoints($guestId, $this->getTableProvider()->getPointsDraw());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($homeId, $this->getTableProvider()->getPointsDraw());
				$this->addPoints2($guestId, $this->getTableProvider()->getPointsDraw());
			}

			$this->addDrawCount($homeId);
			$this->addDrawCount($guestId);
		}
		elseif($toto == 1) {  // Heimsieg
			$this->addPoints($homeId, $this->getTableProvider()->getPointsWin());
			$this->addPoints($guestId, $this->getTableProvider()->getPointsLoose());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($guestId, $this->getTableProvider()->getPointsWin());
			}

			$this->addWinCount($homeId);
			$this->addLoseCount($guestId);
		}
		else { // Auswärtssieg
			$this->addPoints($homeId, $this->getTableProvider()->getPointsLoose());
			$this->addPoints($guestId, $this->getTableProvider()->getPointsWin());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($homeId, $this->getTableProvider()->getPointsWin());
			}
			$this->addLoseCount($homeId);
			$this->addWinCount($guestId);
		}

		// Jetzt die Tore summieren
		$this->addGoals($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
		$this->addGoals($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
	}

	private function addResult($homeId, $guestId, $result) {
		$this->_teamData[$homeId]['matches'][$guestId] = $result;
	}
  /**
   * Zählt die Punkte für eine Heimspieltabelle. Die Ergebnisse werden als nur für die
   * Heimmannschaft gewertet.
	 * @param tx_cfcleaguefe_models_match $match
	 * @param int $toto
	 */
	function _countHome(&$match, $toto) {
		$homeId = $this->getTableProvider()->getTeamId($match->getHome());
		$guestId = $this->getTableProvider()->getTeamId($match->getGuest());
		// Anzahl Spiele aktualisieren
		$this->addMatchCount($homeId);
		$this->addResult($homeId, $guestId, $match->getGuest());

		if($toto == 0) { // Unentschieden
			$this->addPoints($homeId, $this->getTableProvider()->getPointsDraw());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($homeId, $this->getTableProvider()->getPointsDraw());
			}
			$this->addDrawCount($homeId);
		}
		elseif($toto == 1) {  // Heimsieg
			$this->addPoints($homeId, $this->getTableProvider()->getPointsWin());
			$this->addWinCount($homeId);
		}
		else { // Auswärtssieg
			$this->addPoints($homeId, $this->getTableProvider()->getPointsLoose());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($homeId, $this->getTableProvider()->getPointsWin());
			}
			$this->addLoseCount($homeId);
		}
		// Jetzt die Tore summieren
		$this->addGoals($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
	}

	/**
	 * Zählt die Punkte für eine Auswärtstabelle. Die Ergebnisse werden als nur für die
   * Gastmannschaft gewertet.
	 * @param tx_cfcleaguefe_models_match $match
	 * @param int $toto
	 */
	function _countGuest(&$match, $toto) {
		$homeId = $this->getTableProvider()->getTeamId($match->getHome());
		$guestId = $this->getTableProvider()->getTeamId($match->getGuest());
		// Anzahl Spiele aktualisieren
		$this->addMatchCount($guestId);
		$this->addResult($homeId, $guestId, $match->getGuest());

		if($toto == 0) { // Unentschieden
			$this->addPoints($guestId, $this->getTableProvider()->getPointsDraw());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($guestId, $this->getTableProvider()->getPointsDraw());
			}
			$this->addDrawCount($guestId);
		}
		elseif($toto == 1) {  // Heimsieg
			$this->addPoints($guestId, $this->getTableProvider()->getPointsLoose());
			if($this->getTableProvider()->isCountLoosePoints()) {
				$this->addPoints2($guestId, $this->getTableProvider()->getPointsWin());
			}
			$this->addLoseCount($guestId);
		}
		else { // Auswärtssieg
			$this->addPoints($guestId, $this->getTableProvider()->getPointsWin());
			$this->addWinCount($guestId);
		}

		// Jetzt die Tore summieren
		$this->addGoals($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
	}


  /**
   * Addiert Punkte zu einem Team
   */
  function addPoints($teamId, $points) {
    $this->_teamData[$teamId]['points'] = $this->_teamData[$teamId]['points'] + $points;
  }

  /**
   * Addiert negative Punkte zu einem Team. Diese Funktion wird nur im 2-Punkte-System
   * verwendet.
   */
  function addPoints2($teamId, $points) {
    $this->_teamData[$teamId]['points2'] = $this->_teamData[$teamId]['points2'] + $points;
  }

  /**
   * Addiert Tore zu einem Team
   */
  function addGoals($teamId, $goals1, $goals2) {
    $this->_teamData[$teamId]['goals1'] = $this->_teamData[$teamId]['goals1'] + $goals1;
    $this->_teamData[$teamId]['goals2'] = $this->_teamData[$teamId]['goals2'] + $goals2;
    $this->_teamData[$teamId]['goals_diff'] = $this->_teamData[$teamId]['goals1'] - $this->_teamData[$teamId]['goals2'];
  }

  /**
   * Addiert die absolvierten Spiele zu einem Team
   */
  function addMatchCount($teamId) {
    $this->_teamData[$teamId]['matchCount'] = $this->_teamData[$teamId]['matchCount'] + 1;
  }

  function addWinCount($teamId) {
    $this->_teamData[$teamId]['winCount'] = $this->_teamData[$teamId]['winCount'] + 1;
  }
  function addDrawCount($teamId) {
    $this->_teamData[$teamId]['drawCount'] = $this->_teamData[$teamId]['drawCount'] + 1;
  }
  function addLoseCount($teamId) {
    $this->_teamData[$teamId]['loseCount'] = $this->_teamData[$teamId]['loseCount'] + 1;
  }

  /**
   * Lädt die Namen der Teams in der Tabelle
   * @param tx_cfcleaguefe_models_competition $tableProvider
   */
  function _initTeams(tx_cfcleaguefe_util_league_TableProvider $tableProvider) {
    // Wir laden die Teams aus der Liga
//    $uids = $league->record['teams'];
//    $where = 'uid IN (' . $uids .')';
//    $teams = tx_rnbase_util_DB::queryDB('*','tx_cfcleague_teams',$where,
//              '','sorting','tx_cfcleaguefe_models_team',0);

		$teams = $tableProvider->getTeams();
		foreach($teams As $team) {
			$teamId = $tableProvider->getTeamId($team);
			if(!$teamId) continue; // Ignore teams without given id
//			if($team->isDummy()) continue; // Ignore dummy teams
			if(array_key_exists($teamId, $this->_teamData)) continue;
			$this->_teamData[$teamId]['team'] = $team;
			$this->_teamData[$teamId]['teamId'] = $teamId;
			$this->_teamData[$teamId]['teamName'] = $team->record['name'];
			$this->_teamData[$teamId]['teamNameShort'] = $team->record['short_name'];
			$this->_teamData[$teamId]['clubId'] = $team->record['club'];
			$this->_teamData[$teamId]['points'] = 0;
			// Bei 3-Punktssystem muss mit -1 initialisiert werden, damit der Marker später ersetzt wird
			$this->_teamData[$teamId]['points2'] = ($tableProvider->isCountLoosePoints()) ? 0 : -1;
			$this->_teamData[$teamId]['goals1'] = 0;
			$this->_teamData[$teamId]['goals2'] = 0;
			$this->_teamData[$teamId]['goals_diff'] = 0;
			$this->_teamData[$teamId]['position'] = 0;
			$this->_teamData[$teamId]['oldposition'] = 0;
			$this->_teamData[$teamId]['positionchange'] = 'EQ';

			$this->_teamData[$teamId]['matchCount'] = 0;
			$this->_teamData[$teamId]['winCount'] = 0;
			$this->_teamData[$teamId]['drawCount'] = 0;
			$this->_teamData[$teamId]['loseCount'] = 0;

			// CDe begin */
			$this->_teamData[$teamId]['matches'] = array();
			// CDe end */

			// Muss das Team hervorgehoben werden?
			$markClubs = $tableProvider->getMarkClubs();
			if(count($markClubs)) {
				$this->_teamData[$teamId]['markClub'] = in_array($team->record['club'], $markClubs) ? 1 : 0;
			}
		}
	}
	/**
	 * Returns position change, either UP or DOWN or EQ.
	 *
	 * @param int $oldPosition
	 * @param int $newPosition
	 * @return string UP, DOWN or EQ
	 */
	protected function getPositionChange($oldPosition, $newPosition) {
		return $oldPosition == $newPosition ? 'EQ' : ($oldPosition > $newPosition ? 'UP' : 'DOWN');
	}


  function setTableProvider(&$tableProvider) {
  	$this->tableProvider = $tableProvider;
  }
  /**
   * The data provider
   *
   * @return tx_cfcleaguefe_util_league_TableProvider
   */
  function getTableProvider() {
  	return $this->tableProvider;
  }

	/**
	 * Funktion zur Sortierung der Tabellenzeilen
	 */
	private function compareTeams($t1, $t2) {
		// Zwangsabstieg prüfen
		if($t1['last_place']) return 1;
		if($t2['last_place']) return -1;

		if($t1['points'] == $t2['points']) {
			// Im 2-Punkte-Modus sind die Minuspunkte ausschlaggebend
			// da sie im 3-PM immer identisch sein sollten, können wir immer testen
			if($t1['points2'] == $t2['points2']) {
				// Jetzt die Tordifferenz prüfen
				$t1diff = $t1['goals1'] - $t1['goals2'];
				$t2diff = $t2['goals1'] - $t2['goals2'];
				if($t1diff == $t2diff) {
					// Jetzt zählen die mehr geschossenen Tore
					if($t1['goals1'] == $t2['goals1']) {
						// #49: Und jetzt noch die Anzahl Spiele werten
						if($t1['matchCount'] == $t2['matchCount']) {
							return 0; // Punkt und Torgleich
						}
						return $t1['matchCount'] > $t2['matchCount'];
					}
					return $t1['goals1'] > $t2['goals1'] ? -1 : 1;
				}
				return $t1diff > $t2diff ? -1 : 1;
			}
			// Bei den Minuspunkten ist weniger mehr
			return $t1['points2'] < $t2['points2'] ? -1 : 1;
		}
		return $t1['points'] > $t2['points'] ? -1 : 1;
	}

	/**
	 * Funktion zur Sortierung der Tabellenzeilen nach dem Head-to-head modus.
	 * Bei Punktgleichstand zählt hier zuerst der direkte Vergleich
	 */
	private function compareTeamsH2H($t1, $t2) {
/* CDe begin */
		$isH2HComparison = true; // = "is Head-to-head-comparison"

		// Zwangsabstieg prüfen
		if($t1['last_place']) return 1;
		if($t2['last_place']) return -1;

		if($t1['points'] == $t2['points']) {
			// Im 2-Punkte-Modus sind die Minuspunkte auschlaggebend
			// da sie im 3-PM immer identisch sein sollten, können wir immer testen
			if($t1['points2'] == $t2['points2']) {
				// direkter Vergleich gilt vor Tordifferenz / wird ignoriert, falls !$isH2HComparison
				$t1vst2 = preg_split('[ : ]', $this->_teamData[$t1['teamId']]['matches'][$t2['teamId']]);
				$t2vst1 = preg_split('[ : ]', $this->_teamData[$t2['teamId']]['matches'][$t1['teamId']]);

				$t1H2HPoints = 0;
				$t2H2HPoints = 0;
				if (count($t1vst2) > 0 && $t1vst2[0] > $t1vst2[1]) {
					$t1H2HPoints += 1;
				} elseif (count($t1vst2) > 0 && $t1vst2[0] < $t1vst2[1]) {
					$t2H2HPoints += 1;
				}
				if (count($t2vst1) > 0 && $t2vst1[0] > $t2vst1[1]) {
					$t2H2HPoints += 1;
				} elseif (count($t2vst1) > 0 && $t2vst1[0] < $t2vst1[1]) {
					$t1H2HPoints += 1;
				}

				if ($t1H2HPoints == $t2H2HPoints || !$isH2HComparison) {
					// dann eben zuerst die Tordifferenz der 2 Spiele prüfen (Hin- und Rückspiel)
					$t1H2HDiff = 0 + $t1vst2[0] + $t2vst1[1] - $t1vst2[1] - $t2vst1[0];
					$t2H2HDiff = 0 + $t1vst2[1] + $t2vst1[0] - $t1vst2[0] - $t2vst1[1];
					if($t1H2HDiff == $t2H2HDiff || !$isH2HComparison) {
						// jetzt prüfen, wer mehr Auswärtstore geschossen hat
						if ($t1vst2[1] == $t2vst1[1] || !$isH2HComparison) {
							// jetzt die allgemeine Tordifferenz prüfen
							$t1diff = $t1['goals1'] - $t1['goals2'];
							$t2diff = $t2['goals1'] - $t2['goals2'];
							if($t1diff == $t2diff) {
								// jetzt zählen die mehr geschossenen Tore
								if($t1['goals1'] == $t2['goals1']) {
									// #49: Und jetzt noch die Anzahl Spiele werten
									if($t1['matchCount'] == $t2['matchCount']) {
										return 0; // Punkt und Torgleich
									}
									return $t1['matchCount'] > $t2['matchCount'];
								}
								return $t1['goals1'] > $t2['goals1'] ? -1 : 1;
							}
							return $t1diff > $t2diff ? -1 : 1;
						}
						return $t2vst1[1] > $t1vst2[1] ? -1 : 1;
					}
					return $t1H2HDiff > $t2H2HDiff ? -1 : 1;
				}
				return $t1H2HPoints > $t2H2HPoints ? -1 : 1;
			}
			// Bei den Minuspunkten ist weniger mehr
			return $t1['points2'] < $t2['points2'] ? -1 : 1;
		}
		return $t1['points'] > $t2['points'] ? -1 : 1;
/* CDe end */
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_LeagueTable.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_LeagueTable.php']);
}

?>