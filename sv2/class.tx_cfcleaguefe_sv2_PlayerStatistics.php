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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');
require_once(PATH_t3lib.'class.t3lib_svbase.php');
tx_div::load('tx_cfcleaguefe_util_StatisticsHelper');

/**
 * Service for player statistics
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv2_PlayerStatistics extends t3lib_svbase {

  /**
   *  Für jeden Spieler wird ein Datenarray erstellt
   *  In jedem Datenarray liegt für jede Info ein Zähler, 
   *  sowie zusätzlich noch eine Referenz auf den Spieler
   */
  private $playersArr = array();
  private $scopeArr;
  private $teams;
  private $configurations;
  private $statData = array('match_count', 'match_minutes','changed_out', 'changed_in',
                         'card_yellow', 'card_yellowred', 'card_red', 'goals_all', 'goals_head',
                         'goals_own', 'goals_penalty', 'goals_home', 'goals_away', 'goals_assist',
                         'goals_joker');
  
  /**
   * This method is called one time before the statistic process starts.
   *
   * @param array $scope
   */
  public function prepare($scope, &$configurations, &$parameters) {
    $this->scopeArr = $scope;
    $this->configurations = $configurations;
  }
  
static $total = 0; 
  public function handleMatch(&$match, $clubId) {

    // Zunächst müssen alle Spieler des Spiels ermittelt werden
    // Jeder Spieler, der am Spiel beteiligt ist, steht in der Aufstellung oder als
    // Wechselspieler im Match.
    // Wir betrachten nur die Spieler des gesetzten Clubs
    $team = $match->getHome();
    if($team->record['club'] == $clubId || !$clubId) {
      $players = $match->getPlayersHome(1); // All Spieler des Heimteams holen

    if(is_array($players)) 
        foreach($players As $player) {
          // Jeden Spieler aktualisieren
//if($player->uid == '257') t3lib_div::debug($match->record['round'], 'stats');
          $this->_countMatch4Player($player, $match, $this->playersArr);
        }
    }
    $team = $match->getGuest();
    if($team->record['club'] == $clubId || !$clubId) {
      // Nochmal die Spieler des Auswärstteams
      $players = $match->getPlayersGuest(1); // All Spieler des Gastteams holen

      if(is_array($players)) 
        foreach($players As $player) {
          // Jeden Spieler aktualisieren
          $this->_countMatch4Player($player, $match, $this->playersArr);
        }
    }

//t3lib_div::debug( self::$total, 'total sv2_playerstatistics');
    
  }
  public function getResult() {
    // Die Spieler vorher noch sortieren
    // Is there exactly one team?
    $teams = $this->getTeams($this->getScopeArray());

    if(count($teams) == 1 && intval($this->configurations->get('statistics.player.profileSortOrder')) == 1) {
      // sort by team members
      $this->playersArr = $this->_sortPlayer($this->playersArr, $teams[0]);
    }
    else {
      // Die Spieler alphabetisch sortieren
      usort($this->playersArr, 'playerStatsCmpPlayer');
    }
                         
    return $this->playersArr;
  }

  /**
   * Returns the marker instance to map result data to HTML markers
   *
   * @param tx_rnbase_configurations $configurations
   */
  public function getMarker($configurations) {
    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_sv2_PlayerStatisticsMarker');
    return new $markerClass();
  }

  /**
   * Kindklassen Zugriff auf das Ergebnisarray bieten
   * @return array
   */
  protected function getPlayersArray(){
    return $this->playersArr;
  }

  /**
   * Find teams that were handled by this scope
   *
   * @param array $scopeArr
   * @return array of tx_cfcleagefe_models_team
   */
  protected function getTeams($scopeArr){
    $teamClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_team');
    $teams = call_user_func(array($teamClass,"getTeams"), 
                         $scopeArr['COMP_UIDS'], 
                         $scopeArr['CLUB_UIDS']);
    return $teams;
  }
  /**
   * Kindklassen Zugriff auf das Scopearray bieten
   * @return array
   */
  protected function getScopeArray(){
    return $this->scopeArr;
  }
  
  /**
   * Prüft, ob der Spieler im aktuellen Spiel beteiligt war. Folgende Daten
   * werden aktualisiert:<pre>
   * - MATCH_COUNT Anzahl der Spiele
   * - MATCH_MINUTES Spielminuten
   * - CHANGED_OUT Auswechslungen
   * - CHANGED_IN Einwechslungen
   * - CARD_YELLOWRED Anzahl Gelb/rote Karten
   * - CARD_YELLOW Anzahl Gelb Karten
   * - CARD_RED Anzahl rote Karten
   * - GOAL_ALL Gesamtzahl der Tore des Spieler
   * </pre>
   * @param $player Spieler, der gezählt werden soll
   * @param $match Spiel, das ausgewertet wird
   * @param $playersArr Datenarray, welches die ermittelten Daten aufnimmt
   */
  function _countMatch4Player(&$player, &$match, &$playersArr) {
    $ignorePlayer = 1;
    $isYellowRed = false;
    $playerData = &$this->_getPlayerData($playersArr, $player);

    // In welchem Team steht der Spieler?
    $team = $match->getTeam4Player($player->uid);
//if($player->uid == 257)
//  t3lib_div::debug($match->record, '257 tx_cfcleaguefe_sv2_PlayerStatistics');


    if($team == 1)
      $startPlayer = $match->getPlayersHome();
    elseif($team == 2) {
      $startPlayer = $match->getPlayersGuest();
    }
    else    // Wenn der Spieler nicht im Spiel vorkommt, können wir abbrechen
      return;

      
    // Steht der Spieler in der Startelf
    if( is_array($startPlayer) && array_key_exists($player->uid, $startPlayer)) {
      $playerData['match_count'] = intval($playerData['match_count']) + 1;
      
      // Wurde der Spieler ausgewechselt?
      $min = tx_cfcleaguefe_util_StatisticsHelper::isChangedOut($player, $match);
      if($min > 0)
        $playerData['changed_out'] = intval($playerData['changed_out']) + 1;
//if($min > 0) t3lib_div::debug($min, 'utl_stats');

      // Nicht ausgewechselt, aber wurde der Spieler vom Platz gestellt?
      if(intval($min) == 0) {
        $min = tx_cfcleaguefe_util_StatisticsHelper::isCardYellowRed($player, $match);
        if($min != 0){
          $playerData['card_yellowred'] = intval($playerData['card_yellowred']) + 1;
          $isYellowRed = true;
        }
      }
      //Keine gelbrote, aber vielleicht rot?
      if(intval($min) == 0) {
        $min = tx_cfcleaguefe_util_StatisticsHelper::isCardRed($player, $match);
        if($min != 0)
          $playerData['card_red'] = intval($playerData['card_red']) + 1;
      }

      // Bei Wechsel in Nachspielzeit zählt die 89 min
      $min = ($min > 0) ? ($min > 89) ? 89 : $min : 90;

      $playerData['match_minutes'] = intval($playerData['match_minutes']) + $min;
      $ignorePlayer = 0;
    }
    
    if($ignorePlayer) {
      // Hier betrachten wir die eingewechselten Spieler
      $min = tx_cfcleaguefe_util_StatisticsHelper::isChangedIn($player, $match);
      if($min > 0) {
        $playerData['match_count'] = intval($playerData['match_count']) + 1;
        $playerData['changed_in'] = intval($playerData['changed_in']) + 1;

        // Wurde der Spieler wieder ausgewechselt?
        $min2 = tx_cfcleaguefe_util_StatisticsHelper::isChangedOut($player, $match);
        if($min2 > 0)
          $playerData['changed_out'] = intval($playerData['changed_out']) + 1;

        // Wurde der Spieler vom Platz gestellt?
        if(intval($min2) == 0) {
          $min2 = tx_cfcleaguefe_util_StatisticsHelper::isCardYellowRed($player, $match);
          if($min2 != 0){
            $playerData['card_yellowred'] = intval($playerData['card_yellowred']) + 1;
            $isYellowRed = true;
          }
        }
        if(intval($min2) == 0) {
          $min2 = tx_cfcleaguefe_util_StatisticsHelper::isCardRed($player, $match);
          if($min2 != 0)
            $playerData['card_red'] = intval($playerData['card_red']) + 1;
        }

        $min2 = ($min2 > 0) ? ($min2 > 89) ? 89 : $min2 : 90;

        $min = ($min > 89) ? 89 : $min; // Es geht nur bis zur 90. Minute
        $min2 = $min2 <= $min ? $min +1 : $min2;

        $playerData['match_minutes'] = intval($playerData['match_minutes']) + ($min2 - $min);
        $ignorePlayer = 0;
      }
    }
    if($ignorePlayer) {
    	// Bug 1864066 - Spieler, die nicht im Spiel waren können trotzdem rote Karten bekommen
      if(tx_cfcleaguefe_util_StatisticsHelper::isCardRed($player, $match) != 0)
        $playerData['card_red'] = intval($playerData['card_red']) + 1;
    }
    if(!$ignorePlayer) {
      // Der Spieler war im Spiel. Wir suchen die restlichen Daten
      // Bug 1864071 - Gelbe Karten nur zählen, wenn nicht gelbrot
      if(!$isYellowRed) {
	      $min = tx_cfcleaguefe_util_StatisticsHelper::isCardYellow($player, $match);
	      if($min != 0)
	        $playerData['card_yellow'] = intval($playerData['card_yellow']) + 1;
      }        
      $this->_countGoals(0,'goals_all', $player, $match, $playerData);
      $this->_countGoals(11,'goals_head', $player, $match, $playerData);
      $this->_countGoals(12,'goals_penalty', $player, $match, $playerData);
//$time = t3lib_div::milliseconds();
      $this->_countNote(30,'goals_own', $player, $match, $playerData);
      $this->_countNote(31,'goals_assist', $player, $match, $playerData);
//self::$total = self::$total + t3lib_div::milliseconds() - $time;
    }
  }
  /**
   * Zählt einen bestimmten Note-Typ für einen Spieler.
   * @param $type int MatchNote-Typ
   * @param $key der konkrete Statistiktyp, der aktualisiert werden soll. Dieser muss zum Typ passen.
   * @param $player Referenz auf den Spieler
   * @param $match Referenz auf das Spiel
   * @param $playerData Referenz auf die Statistikdaten des Spielers
   */
  function _countNote($type, $key, &$player, &$match, &$playerData) {
    // Die passenden Notes des Spielers ermitteln
    $notes = tx_cfcleaguefe_util_StatisticsHelper::isNote($type, $player, $match);
    if(is_array($notes)) {
      // Die Anzahl der Notes im Spiel für den Spieler hinzufügen
      $playerData[$key] = intval($playerData[$key]) + count($notes);
    }
    $playerData[$key.'_per_match'] = intval($playerData[$key]) / intval($playerData['match_count']);
  }

  /**
   * Zählt die Tore für einen Spieler. Der Typ ist entweder 0 für alle Tore oder
   * entspricht dem MatchNote-Typ für ein bestimmtes Tor. Wenn der Typ 0 ist, dann werden 
   * auch auch die Werte für GOALS_HOME, GOALS_AWAY und GOALS_JOKER aktualisiert
   * @param $type int 0 oder MatchNote-Typ
   * @param $key der konkrete Statistiktyp, der aktualisiert werden soll. Dieser muss zum Typ passen.
   * @param $player Referenz auf den Spieler
   * @param $match Referenz auf das Spiel
   * @param $playerData Referenz auf die Statistikdaten des Spielers
   */
  function _countGoals($type, $key, &$player, &$match, &$playerData) {
    // Die Tore des Spielers ermitteln
    $notes = tx_cfcleaguefe_util_StatisticsHelper::isGoal($type, $player, $match);
    if(is_array($notes)) {
      // Die Anzahl der Tore im Spiel für den Spieler hinzufügen
      $playerData[$key] = intval($playerData[$key]) + count($notes);
      if($type == 0) {
        // Hier zählen wir zusätzlich weitere Daten
        $firstNote = $notes[0];
        if($firstNote->isHome())
          $playerData['goals_home'] = intval($playerData['goals_home']) + count($notes);
        else
          $playerData['goals_away'] = intval($playerData['goals_away']) + count($notes);
        if(tx_cfcleaguefe_util_StatisticsHelper::isChangedIn($player, $match))
          $playerData['goals_joker'] = intval($playerData['goals_joker']) + count($notes);
      }
    }
    // Tore pro Spiel berechnen
    $playerData[$key.'_per_match'] = intval($playerData[$key]) / intval($playerData['match_count']);
    // Spielminuten pro Tor (hier DIV/0 verhindern)
    if(intval($playerData[$key]))
      $playerData[$key.'_after_minutes'] = intval($playerData['match_minutes']) / intval($playerData[$key]);
  }

  /**
   * Liefert das Datenarray für einen Spieler als Referenz. Sollte es noch nicht
   * vorhanden sein, dann wird es angelegt. Außerdem wird jeder Statistikeintrag mit 0 initialisiert.
   * Die ist notwendig, damit später alle Marker im HTML-Template ersetzt werden.
   */
  function &_getPlayerData(&$players, $player) {
    if(!array_key_exists($player->uid, $players)) {
      $players[$player->uid] = array();
      // Alle Daten initialisieren
      foreach($this->statData as $col) {
        $players[$player->uid][$col] = 0;
      }
      $players[$player->uid]['player'] = $player;
    }
    return $players[$player->uid];
  }

  /**
   * Sortiert die Spieler entsprechend der Reihenfolge im Team
   * @param $players array of tx_cfcleaguefe_models_profile
   */
  function _sortPlayer($players, $team) {
    $ret = array();
    if(strlen(trim($team->record['players'])) > 0 ) {
      if(count($players)) {
        // Jetzt die Spieler in die richtige Reihenfolge bringen
        $uids = t3lib_div::intExplode(',', $team->record['players']);
        $uids = array_flip($uids);
        foreach($players as $record) {
          // In $record liegt der Statistikdatensatz des Spielers
          $player = $record['player'];
          $ret[$uids[$player->uid]] = $record;
        }
      }
    }
    else {
      // Wenn keine Spieler im Team geladen sind, dann wird das Array unverändert zurückgegeben
      return $players;
    }
    return $ret;
  }
}

/**
 * Sortierfunktion um die korrekte Reihenfolge nach Namen zu ermittlen
 */
function playerStatsCmpPlayer($a, $b) {
  $player1 = $a['player'];
  $player2 = $b['player'];

  return strcmp(strtoupper($player1->getName(1)), strtoupper($player2->getName(1)));
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_PlayerStatistics.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_PlayerStatistics.php']);
}

?>