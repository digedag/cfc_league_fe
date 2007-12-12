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
//require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php'); // Prüfen!!
//require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'models/class.tx_cfcleaguefe_models_base.php');


/**
 * Erstellung von Statistiken.
 *
 */
class tx_cfcleaguefe_util_Statistics {

  /**
   * Start creation of statistical data
   *
   * @param array $matches
   * @param array $scopeArr
   * @param array $services
   */
  function createStatistics(&$matches, $scopeArr, &$services, &$configuration, &$parameters) {
    $clubId = $scopeArr['CLUB_UIDS'];
    $servicesArr = array_values($services);
    $serviceKeys = array_keys($services);
    $servicesArrCnt = count($servicesArr);
    for($i=0; $i < $servicesArrCnt; $i++) {
      $service =& $servicesArr[$i];
      $service->prepare($scopeArr, $configuration, $parameters);
    }
    
//    $time = t3lib_div::milliseconds();
    // Über alle Spiele iterieren und diese an die Services geben
    for($j=0, $mc = count($matches); $j < $mc; $j++){
      for($i=0; $i < $servicesArrCnt; $i++) {
//t3lib_div::debug( $matches[$j], 'util_statistics');
        $time = t3lib_div::milliseconds();
        $service =& $servicesArr[$i];
        $service->handleMatch($matches[$j], $clubId);
        $times[$i] = $times[$i] + t3lib_div::milliseconds() - $time;
      }
    }
    //t3lib_div::debug( $times, 'util_statistics');
//t3lib_div::debug( t3lib_div::milliseconds() - $time, 'util_statistics');
    // Abschließend die Daten zusammenpacken
    $ret = array();
    for($i=0; $i < $servicesArrCnt; $i++) {
      $service =& $servicesArr[$i];
      $ret[$serviceKeys[$i]] = $service->getResult();
    }
    return $ret;
  }
/*
   * Erstellt die Statistiken für Spieler. Die übergebenen Spiele müssen die 
   * notwendigen Tickerdaten schon enthalten.

  function getPlayerStatistics(&$matches, $clubId) {
    // Für jeden Spieler wird ein Datenarray erstellt
    // In jedem Datenarray liegt für jede Info ein Zähler, 
    // sowie zusätzlich noch eine Referenz auf den Spieler
    $playersArr = array();
    foreach($matches As $match){

      // Zunächst müssen alle Spieler des Spiels ermittelt werden
      // Jeder Spieler, der am Spiel beteiligt ist, steht in der Aufstellung oder als
      // Wechselspieler im Match.

      // Wir betrachten nur die Spieler des gesetzten Clubs
      $team = $match->getHome();
      if($team->record['club'] == $clubId) {
        $players = $match->getPlayersHome(1); // All Spieler des Heimteams holen

        if(is_array($players)) 
          foreach($players As $player) {
            // Jeden Spieler aktualisieren
//if($player->uid == '5') t3lib_div::debug($match->record['round'], 'stats');
            tx_cfcleaguefe_util_Statistics::_countMatch4Player($player, $match, $playersArr);
          }
      }

      $team = $match->getGuest();
      if($team->record['club'] == $clubId) {
        // Nochmal die Spieler des Auswärstteams
        $players = $match->getPlayersGuest(1); // All Spieler des Gastteams holen

        if(is_array($players)) 
          foreach($players As $player) {
            // Jeden Spieler aktualisieren
            tx_cfcleaguefe_util_Statistics::_countMatch4Player($player, $match, $playersArr);
          }
      }

    }
//  t3lib_div::debug($playersArr, 'utl_stats');
    return $playersArr;
  }
*/

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_Statistics.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_Statistics.php']);
}

?>