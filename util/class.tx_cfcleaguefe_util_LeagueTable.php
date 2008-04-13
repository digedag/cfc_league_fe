<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

// Die Datenbank-Klasse
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php'); 
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

  function tx_cfcleaguefe_util_LeagueTable() {
    $this->_teamData = Array();
  }


  /**
   * Für die Berechnung des Charts benötigen wir die Config, die Parameter und die Liga
   * Was brauchen wir für eine Tabellenfahrt?
   * - der Tabellenstand muss nach jeden Spieltag berechnet werden
   * - die Position für für jedes Team in einem Array abgelegt
   * - Die Punkte und Tore sind für die Ausgabe uninteressant
   * @param tx_lib_parameters $parameters
   * @param tx_rnbase_configurations $configurations
   * @param tx_cfcleaguefe_models_competition $league
   */
  function generateChart(&$parameters,&$configurations, &$league) {
    // Wir setzen die notwendigen Einstellungen
    $this->_initConfig($parameters, $configurations, $league);
    // Zuerst die Namen der Teams laden und dabei alle Werte auf 0 setzen
    $this->_initTeams($configurations, $league);
    // Hier je nach TableScope die Spiele holen
    $matches = $league->getMatches(2, $this->cfgTableScope);
    
    // Wir berechnen die Tabelle jetzt häppchenweise für jeden Spieltag einzeln
    // Daher zerlegen wir die Spiele zunächst in die einzelnen Spieltage
    $rounds = array();
    foreach($matches As $match) {
      $rounds[$match->record['round']][] = $match;
    }
    $xyData = Array();
    $this->handlePenalties();
    foreach($rounds As $round => $roundMatches) {
      $this->handleMatches($roundMatches);
      // Jetzt die Tabelle sortieren, dafür benötigen wir eine Kopie des Arrays
      $teamData = $this->_teamData;
      usort($teamData, 'compareTeams');
      // Nun setzen wir die Tabellenstände
      foreach($teamData As $position => $team) {
        if(in_array($team['clubId'], $this->cfgChartClubs))
          $xyData[$team['teamName']][$round] = $position +1;
      }
    }
    
		// Issue 1880245: Chart auf der X-Achse bis Saisonende erweitern
		// Den höchsten absolvierten Spieltag ermitteln
		$lastRound = intval(array_pop(array_keys($rounds))) + 1;
		$maxRound = count($league->getRounds());
		$teamName = array_pop(array_keys($xyData));
		for( ; $lastRound <= $maxRound; $lastRound++) {
			// Es muss nur für ein Team ein weiterer Wert hinzugefügt werden
			$xyData[$teamName][$lastRound] = null;
		}
		return $xyData;
	}

  /**
   * Für die Berechnung der Liga benötigen wir die Config, die Parameter und die Liga
   * @param tx_lib_parameters $parameters
   * @param tx_rnbase_configurations $configurations
   * @param tx_cfcleaguefe_models_competition $league
   */
  function generateTable(&$parameters,&$configurations, &$league) {
    // Wir setzen die notwendigen Einstellungen
    $this->_initConfig($parameters, $configurations, $league);
    // Zuerst die Namen der Teams laden und dabei alle Werte auf 0 setzen
    $this->_initTeams($configurations, $league);

    // Hier je nach TableScope (Hin-/Rückrunde) die Spiele holen
    $matches = $league->getMatches(2, $this->cfgTableScope); // TODO: Status aus Config holen
//    t3lib_div::debug($league, 'Liga');

    $this->handleMatches($matches);
    $this->handlePenalties();

    
    // Jetzt die Tabelle sortieren
    usort($this->_teamData, 'compareTeams');

    // Nun setzen wir die Tabellenstände
    for($i=0; $i < count($this->_teamData); $i++) {
      $this->_teamData[$i]['position'] = $i +1;
    }

//t3lib_div::debug($this->_teamData,'util_leaguetable');
    
    return $this->_teamData;
  }

  /**
   * Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
   * für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
   */
  function handlePenalties() {
    if($this->cfgTableType || $this->cfgTableScope)
      return;


    foreach($this->penalties As $penalty) {
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
      // Wie ist das Spiel ausgegangen?
      $toto = $match->getToto();

      // Die eigentliche Punktezählung richtet sich nach dem Typ der Tabelle
      // Daher rufen wir jetzt die passende Methode auf
      switch($this->cfgTableType) {
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
  }

  /**
   * Zählt die Punkte für eine normale Tabelle
   */
  function _countStandard(&$match, $toto) {
      // Anzahl Spiele aktualisieren
      $this->addMatchCount($match->record['home']);
      $this->addMatchCount($match->record['guest']);


      if($toto == 0) { // Unentschieden
        $this->addPoints($match->record['home'], $this->cfgPointsDraw);
        $this->addPoints($match->record['guest'], $this->cfgPointsDraw);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['home'], $this->cfgPointsDraw);
          $this->addPoints2($match->record['guest'], $this->cfgPointsDraw);
        }

        $this->addDrawCount($match->record['home']);
        $this->addDrawCount($match->record['guest']);
      }
      elseif($toto == 1) {  // Heimsieg
        $this->addPoints($match->record['home'], $this->cfgPointsWin);
        $this->addPoints($match->record['guest'], $this->cfgPointsLose);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['guest'], $this->cfgPointsWin);
        }

        $this->addWinCount($match->record['home']);
        $this->addLoseCount($match->record['guest']);
      }
      else { // Auswärtssieg
        $this->addPoints($match->record['home'], $this->cfgPointsLose);
        $this->addPoints($match->record['guest'], $this->cfgPointsWin);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['home'], $this->cfgPointsWin);
        }
        $this->addLoseCount($match->record['home']);
        $this->addWinCount($match->record['guest']);
      }

      // Jetzt die Tore summieren
      $this->addGoals($match->record['home'], $match->record['goals_home_2'], $match->record['goals_guest_2']);
      $this->addGoals($match->record['guest'], $match->record['goals_guest_2'], $match->record['goals_home_2']);
  }

  /**
   * Zählt die Punkte für eine Heimspieltabelle. Die Ergebnisse werden als nur für die 
   * Heimmannschaft gewertet.
   */
  function _countHome(&$match, $toto) {
      // Anzahl Spiele aktualisieren
      $this->addMatchCount($match->record['home']);


      if($toto == 0) { // Unentschieden
        $this->addPoints($match->record['home'], $this->cfgPointsDraw);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['home'], $this->cfgPointsDraw);
        }
        $this->addDrawCount($match->record['home']);
      }
      elseif($toto == 1) {  // Heimsieg
        $this->addPoints($match->record['home'], $this->cfgPointsWin);
        $this->addWinCount($match->record['home']);
      }
      else { // Auswärtssieg
        $this->addPoints($match->record['home'], $this->cfgPointsLose);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['home'], $this->cfgPointsWin);
        }
        $this->addLoseCount($match->record['home']);
      }

      // Jetzt die Tore summieren
      $this->addGoals($match->record['home'], $match->record['goals_home_2'], $match->record['goals_guest_2']);
  }

  /**
   * Zählt die Punkte für eine normale Tabelle
   */
  function _countGuest(&$match, $toto) {
      // Anzahl Spiele aktualisieren
      $this->addMatchCount($match->record['guest']);


      if($toto == 0) { // Unentschieden
        $this->addPoints($match->record['guest'], $this->cfgPointsDraw);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['guest'], $this->cfgPointsDraw);
        }

        $this->addDrawCount($match->record['guest']);
      }
      elseif($toto == 1) {  // Heimsieg
        $this->addPoints($match->record['guest'], $this->cfgPointsLose);
        if($this->cfgPointSystem == 1) {
          $this->addPoints2($match->record['guest'], $this->cfgPointsWin);
        }

        $this->addLoseCount($match->record['guest']);
      }
      else { // Auswärtssieg
        $this->addPoints($match->record['guest'], $this->cfgPointsWin);
        $this->addWinCount($match->record['guest']);
      }

      // Jetzt die Tore summieren
      $this->addGoals($match->record['guest'], $match->record['goals_guest_2'], $match->record['goals_home_2']);
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
   * @param tx_rnbase_configurations $configurations
   * @param tx_cfcleaguefe_models_competition $league
   */
  function _initTeams(&$configurations, &$league) {
    // Wir laden die Teams aus der Liga
//    $uids = $league->record['teams'];
//    $where = 'uid IN (' . $uids .')';
//    $teams = tx_rnbase_util_DB::queryDB('*','tx_cfcleague_teams',$where,
//              '','sorting','tx_cfcleaguefe_models_team',0);

    $teams = $league->getTeams();
    // Besondere Teams suchen
    $arr = t3lib_div::intExplode(',',$configurations->get('markClubs'));

    foreach($teams As $team) {
      if($team->isDummy()) continue; // Ignore dummy teams
      $this->_teamData[$team->uid]['team'] = $team;
      $this->_teamData[$team->uid]['teamId'] = $team->uid;
      $this->_teamData[$team->uid]['teamName'] = $team->record['name'];
      $this->_teamData[$team->uid]['teamNameShort'] = $team->record['short_name'];
      $this->_teamData[$team->uid]['clubId'] = $team->record['club'];
      $this->_teamData[$team->uid]['points'] = 0;
      // Bei 3-Punktssystem muss mit -1 initialisiert werden, damit der Marker später ersetzt wird
      $this->_teamData[$team->uid]['points2'] = ($this->cfgPointSystem == '1') ? 0 : -1;
      $this->_teamData[$team->uid]['goals1'] = 0;
      $this->_teamData[$team->uid]['goals2'] = 0;
      $this->_teamData[$team->uid]['goals_diff'] = 0;

      $this->_teamData[$team->uid]['matchCount'] = 0;
      $this->_teamData[$team->uid]['winCount'] = 0;
      $this->_teamData[$team->uid]['drawCount'] = 0;
      $this->_teamData[$team->uid]['loseCount'] = 0;

      // Muss das Team hervorgehoben werden?
      if($configurations->get('markClubs')) {
        $this->_teamData[$team->uid]['markClub'] = in_array($team->record['club'], $arr) ? 1 : 0;
      }
    }
//      t3lib_div::debug($this->_teamData,'Vereine');
  }

  /**
   * Initialisiert die Instanz mit den notwendigen Daten
   * @param tx_lib_parameters $parameters
   * @param tx_rnbase_configurations $configurations
   * @param tx_cfcleaguefe_models_competition $league
   */
  function _initConfig(&$parameters, &$configurations, &$league) {
    // Das Punktesystem kommt aus der Liga, kann aber über einen Parameter verändert werden
    $this->cfgPointSystem = $league->record['point_system'];
    // Das das Punktesystem geändert werden?
    if($configurations->get('pointSystemSelectionInput')) {
      // Prüfen, ob der Parameter im Request liegt
      if($parameters->offsetGet('pointsystem'))
        $this->cfgPointSystem = intval($parameters->offsetGet('pointsystem'));
    }

    $this->cfgChartClubs = t3lib_div::intExplode(',',$configurations->get('chartClubs'));

    $this->cfgTableType = $configurations->get('tabletype');
//t3lib_div::debug($configurations);
    // Wird der TableType per SelectBox angeboten?
    if($configurations->get('tabletypeSelectionInput')) {
      $this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
    }

    // Der TableScope wirkt sich auf die betrachteten Spiele aus
    $this->cfgTableScope = $configurations->get('tablescope');
    if($configurations->get('tablescopeSelectionInput')) {
      $this->cfgTableScope = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : $this->cfgTableScope;
    }

    if($this->cfgPointSystem == '0') { // 3-Punkt
      $this->cfgPointsDraw = 1;
      $this->cfgPointsWin = 3;
      $this->cfgPointsLose = 0;
    }
    elseif($this->cfgPointSystem == '1') { // 2-Punkt
      $this->cfgPointsDraw = 1;
      $this->cfgPointsWin = 2;
      $this->cfgPointsLose = 0;
    }
    else {
      t3lib_div::debug($league->record['point_system'],'Error: Unknown Pointsystem-ID');
    }
    // Die Ligastrafen laden
    $this->penalties = $league->getPenalties();

  }

}

/**
 * Funktion zur Sortierung der Tabellenzeilen
 */
function compareTeams($t1, $t2) {
  // Zwangsabstieg prüfen
  if($t1['last_place']) return 1;
  if($t2['last_place']) return -1;

  if($t1['points'] == $t2['points']) {
    // Im 2-Punkte-Modus sind die Minuspunkte auschlaggebend
    // da sie im 3-PM immer identisch sein sollten, können wir immer testen
    if($t1['points2'] == $t2['points2']) {
      // Jetzt die Tordifferenz prüfen
      $t1diff = $t1['goals1'] - $t1['goals2'];
      $t2diff = $t2['goals1'] - $t2['goals2'];
      if($t1diff == $t2diff) {
        // Jetzt zählen die mehr geschossenen Tore
        if($t1['goals1'] == $t2['goals1'])
          return 0; // Punkt und Torgleich
        return $t1['goals1'] > $t2['goals1'] ? -1 : 1;
      }
      return $t1diff > $t2diff ? -1 : 1;
    }
    // Bei den Minuspunkten ist weniger mehr
    return $t1['points2'] < $t2['points2'] ? -1 : 1;
  }
  return $t1['points'] > $t2['points'] ? -1 : 1;
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_LeagueTable.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_LeagueTable.php']);
}

?>