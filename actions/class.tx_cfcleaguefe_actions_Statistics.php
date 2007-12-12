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

require_once(t3lib_extMgm::extPath('rn_memento') . 'sv1/class.tx_rnmemento_sv1.php');
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_Spyc.php');


require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_ScopeController.php');
require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_MatchTicker.php');
require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_Statistics.php');

require_once(t3lib_extMgm::extPath('cfc_league') . 'util/class.tx_cfcleague_util_Memento.php');


/**
 * Controller für die Anzeige von Spielerstatistiken
 * 
 * Zunächst ist wichtig welche Spieler betrachtet werden sollen. Dieser
 * Scope ist zunächst auf die Spieler eines Teams und damit auch einer 
 * Saison beschränkt. (Ein Team spielt ja nur in einer Saison.) Später 
 * könnte man den Scope aber auch erweitern: 
 * - Spieler eines Vereins in einer bestimmten Altersgruppe in allen Saisons
 * - Anzeige der besten Torschützen einer Liga (teamübergreifend)
 * - usw.
 * Vermutlich wäre es aber besser dafür eigene Views zu erstellen. Diese
 * könnten dann die entsprechenden Flexforms zur Verfügung stellen.
 * 
 * Diese Klasse zeigt zunächst die Auswertung für die Spieler eines Teams.
 */
class tx_cfcleaguefe_actions_Statistics {
  /**
   * Erstellung von statistischen Angaben
   *
   * @param object $parameters
   * @param tx_rnbase_configurations $configurations
   * @return string HTML output
   */
  function execute($parameters,$configurations){
//    global $T3_SERVICES;
//t3lib_div::debug($T3_SERVICES['cfcleague_statistics'], 'tx_cfcleaguefe_actions_PlayerStatistics');
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
    // Die notwendigen Statistikklassen ermitteln
    $types = t3lib_div::trimExplode(',', $configurations->get('statisticTypes'), 1);
    if(!count($types)) {
      // Abbruch kein Typ angegeben
      return $configurations->getLL('statistics_noTypeFound');
    }
    $services = array();
    foreach ($types as $type) {
      $service = t3lib_div::makeInstanceService('cfcleague_statistics', $type);
      if(is_object($service))
      	$services[$type] = $service;
    }
    $matches =& tx_cfcleaguefe_util_MatchTicker::getMatches4Scope($scopeArr);
    
    // Aufruf der Statistik
    $data = tx_cfcleaguefe_util_Statistics::createStatistics($matches, $scopeArr, $services, $configurations, $parameters);
    
    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('data', $data); // Services bereitstellen
    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_Statistics');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('statisticTemplate'));
    $out = $view->render('statistics', $configurations);
    return $out;
  }
  
  function executeAlt($parameters,$configurations){
// FIXME Generische Lösung mit Services aufbauen

// Vorgehen:
// Über das Flexform werden die Statistiktypen ermittelt.
// Diese sind die Keys für den Subtype des Service
    // Die Werte des aktuellen Scope ermitteln
//t3lib_div::debug($configurations->get('playerStatsTimeout'),'act_stats');
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);

    // Die notwendigen Statistikklassen ermitteln
    $types = t3lib_div::trimExplode(',', $configurations->get('statisticTypes'), 1);
    if(!count($types)) {
      // Abbruch kein Typ angegeben
      return $configurations->getLL('statistics_noTypeFound');
    }
    $services = array();
    foreach ($types as $type) {
    	$service = t3lib_div::makeInstanceService('cfcleague_statistics', $type);
    	if(is_object($service))
      	$services[$type] = $service;
    }
    
//    t3lib_div::debug($services, 'tx_cfcleaguefe_actions_PlayerStatistics');
    // Liegt für diesen Scope schon ein gültiges Ergebnis vor?
    if (is_object($serviceObj = t3lib_div::makeInstanceService('memento'))) {
      // Daten laden
      $arr = $serviceObj->load($this->_getKey($scopeArr), 'PlayerStatsMemento');
      $playerData = $arr['player_data'];
      $additionalData = $arr['add_data'];
    }

    if(!is_array($playerData)) {
      $matches =& tx_cfcleaguefe_util_MatchTicker::getMatches4Scope($scopeArr);

      // Aufruf der Statistik
      $data = tx_cfcleaguefe_util_Statistics::createStatistics($matches, $scopeArr['CLUB_UIDS'], $services);
//      t3lib_div::debug($data, 'tx_cfcleaguefe_actions_PlayerStatistics');
      
      // Wir ermitteln die Spielerstatistik für die Spieler eines Vereins
      $playerData = tx_cfcleaguefe_util_Statistics::getPlayerStatistics($matches, $scopeArr['CLUB_UIDS']);

      // Is there exactly one team?
      $teamClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_team');
      $teams = call_user_func(array($teamClass,"getTeams"), $scopeArr['COMP_UIDS'], $scopeArr['CLUB_UIDS']);

//      $teams = $teamClass::getTeams($scopeArr['COMP_UIDS'], $scopeArr['CLUB_UIDS']);

      if(count($teams) == 1 && intval($configurations->get('playerStatsSortOrder')) == 1) {
        // sort by team members
        $playerData = $this->_sortPlayer($playerData, $teams[0]);
      }
      else {
        // Die Spieler alphabetisch sortieren
        usort($playerData, 'cmpPlayer');
      }
      $additionalData = $this->_getAdditionalData($scopeArr, $teams, $matches);


      // Alle Daten sind ermittelt und können gespeichert werden

      if (is_object($serviceObj = t3lib_div::makeInstanceService('memento'))) {
        // Daten abspeichern
        $timout = intval($configurations->get('playerStatsTimeout')) * 60;
        $serviceObj->save($this->_getKey($scopeArr), 
                          array('player_data' => $playerData,'add_data' => $additionalData), 
                          'PlayerStatsMemento', 
                          array('superKey' => $scopeArr['COMP_UIDS'], 'expires' => $timeout ));
      }
    }



    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('data', $data); // Services bereitstellen

    $viewData->offsetSet('playerData', $playerData); // Die Spielerdaten bereitstellen

    $viewData->offsetSet('scorerData', $this->_findScorer($playerData)); // Die Torschützenliste
    $viewData->offsetSet('assistData', $this->_findAssists($playerData)); // Die Assists-Liste

    $viewData->offsetSet('additionalData', $additionalData); // Die Zusatzdaten

    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_Statistics');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('playerstatsTemplate'));
    $out = $view->render('statistics', $configurations);
/*    
    // View
    $view = tx_div::makeInstance('tx_rnbase_view_phpTemplateEngine');
    $view->setTemplatePath($configurations->getTemplatePath());
//    $tmpl = $configurations->get('playerStatistics.template') ? $configurations->get('playerStatistics.template') : 'playerstatistics';
//    $out = $view->render($tmpl, $configurations);

    $view->setTemplateFile($configurations->get('playerstatsTemplate'));
    $out = $view->render('playerstatistics', $configurations);
*/

    return $out;

  }

  /**
   * Liefert auf Basis des aktuellen Scopes einen eindeutigen Key unter dem das Memento gespeichert
   * werden kann.
   * @return string md5 key for memento
   */
  function _getKey($scopeArr) {
    // Wir benötigen einfach einen eindeutigen String
    return tx_rnbase_util_Spyc::YAMLDump($scopeArr) . 'playerstats';
  }

  /**
   * Einige Zusatzdaten ermitteln
   */
  function _getAdditionalData(&$scopeArr, &$teams, &$matches) {
    // Wenn die Statistik für einen bestimmten Wettbewerb erstellt wurde, dann wollen wir jetzt 
    // noch wissen, wieviele Spiele betrachtet wurden
    $ret = array();
    $compUid = t3lib_div::intExplode(',', $scopeArr['COMP_UIDS']);
    if(count($compUid) == 1) {
      $compClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_competition');
      $comp = new $compClass($compUid[0]);
      // Gesucht: Anzahl Spiele der Teams gesamt und beendet
      // Spiele gesamt holen wir über getRounds
      $teamIds = array();
      foreach($teams as $team) {
        $teamIds[] = $team->uid;
      }
      $ret['numberOfMatches'] = $comp->getNumberOfMatches( implode($teamIds, ',')); // Spiele gesamt

      // Jetzt die gewerteten Spiele ermitteln
      $matchCnt = 0;
      for($i = 0; $i < count($matches); $i++) {
        $match = $matches[$i];
        // ein Spiel wird gewertet, wenn es Tickermeldungen hat oder Aufstellungen gesetzt sind
        if($match->record['players_home'] || $match->record['players_guest'] || count($match->getMatchNotes()))
          $matchCnt++;
      }
      $ret['numberOfUsedMatches'] = $matchCnt; // Spiele gesamt

    }
    return $ret;
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
 * Das Memento für die Spielerstatistiken
 */
class PlayerStatsMemento extends tx_cfcleaguefe_util_Memento {
  private $playerData;
  private $yamlStr;

  function PlayerStatsMemento() {
  }

  function array2Data($arr) {
    $profileClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_profile');
    $ret = array();

    foreach($arr['player_data'] as $data) {
      // Die Profile müssen wieder instanziiert werden
      if(is_array($data)) {
        $ret['player_data'][$data['player']] = $data;
        $ret['player_data'][$data['player']]['player'] = new $profileClass($data['player']);
      }
    }
    $ret['add_data'] = $arr['add_data'];


    return $ret;
  }

  function data2Array($data){
    $arr = array();

    foreach($data['player_data'] As $key => $dataArr) {
      // Das Spieler-Object wird entfernt
      $dataArr['player'] = $dataArr['player']->uid;
      $arr['player_data'][$key] = $dataArr;
    }
    $arr['add_data'] = $data['add_data']; // Die Zusatzdaten
    return $arr;
  }

  function getFunctionName(){
    return 'tx_cfcleaguefe_actions_PlayerStatistics';
  }
}

/**
 * Sortierfunktion um die korrekte Reihenfolge nach Namen zu ermittlen
 * @deprecated 
 */
function cmpPlayer($a, $b) {
  $player1 = $a['player'];
  $player2 = $b['player'];

  return strcmp(strtoupper($player1->getName(1)), strtoupper($player2->getName(1)));
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_Statistics.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_Statistics.php']);
}

?>