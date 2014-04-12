<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_cfcleaguefe_sv2_PlayerStatistics');

/**
 * Service for summery of player statistics
 * Since this list is similar to player statistics, it is based on that service. 
 * It simply modifies the result
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv2_PlayerSummaryStatistics extends tx_cfcleaguefe_sv2_PlayerStatistics {
  private $result = array();
  /** Array with competition IDs of handled matches */
  private $compIds = array();
  
  public function handleMatch(&$match, $clubId) {
    if($match->record['players_home'] || $match->record['players_guest'] || count($match->getMatchNotes()))
      $this->result['numberOfUsedMatches'] = $this->result['numberOfUsedMatches'] + 1;
    $this->compIds[] = $match->record['competition']; 
  }
  
  /**
   * Liefert allgemeine Daten zur Spielerstatistik
   *
   * @return array
   */
  public function getResult() {
    if(!array_key_exists('numberOfUsedMatches', $this->result)) 
      $this->result['numberOfUsedMatches'] = 0;
    $teams = $this->getTeams($this->getScopeArray());
    $this->_setAdditionalData($this->getScopeArray(), $teams, array_unique($this->compIds));
    return $this->result;
  }

  /**
   * Returns the marker instance to map result data to HTML markers
   *
   * @param tx_rnbase_configurations $configurations
   * @return tx_cfcleaguefe_sv2_PlayerSummaryStatisticsMarker
   */
  public function getMarker($configurations) {
    return tx_rnbase::makeInstance('tx_cfcleaguefe_sv2_PlayerSummaryStatisticsMarker');
  }
  
  /**
   * Einige Zusatzdaten ermitteln
   */
  function _setAdditionalData(&$scopeArr, &$teams, &$compUids) {
    // Wir zählen wieviele Spiele die Wettbewerbe haben, die in der
    // Statistik betrachtet wurden. 
    $ret = array();
    $this->result['numberOfMatches'] = 0;
//    $compUid = t3lib_div::intExplode(',', $scopeArr['COMP_UIDS']);
//    if(count($compUid) == 1) {
    foreach($compUids As $compUid) {
      $comp = tx_rnbase::makeInstance('tx_cfcleaguefe_models_competition', $compUid);
      // Gesucht: Anzahl Spiele der Teams gesamt und beendet
      // Spiele gesamt holen wir über getRounds
      $teamIds = array();
      foreach($teams as $team) {
        $teamIds[] = $team->uid;
      }
      $matchCount = $comp->getNumberOfMatches( implode($teamIds, ',')); // Spiele gesamt
      $this->result['numberOfMatches'] = $this->result['numberOfMatches'] + $matchCount;
    }
    return $ret;
  }
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatistics.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatistics.php']);
}

?>