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

tx_rnbase::load('tx_cfcleaguefe_sv2_PlayerStatistics');

/**
 * Service for assist statistics
 * Since this list is similar to player statistics, it is based on that service.
 * It simply modifies the result
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv2_AssistStatistics extends tx_cfcleaguefe_sv2_PlayerStatistics {

  /**
   * Liefert die Liste der besten Vorlagengeber
   *
   * @return array
   */
  public function getResult() {
    return $this->_findAssists($this->getPlayersArray());
  }

  /**
   * Sucht die besten Vorlagengeber aus der Liste und liefert sie sortiert in einem Array zurück
   * @return Array mit den Datensätzen der Vorlagengeber
   */
  private function _findAssists(&$playerData) {
    $ret = array();
    foreach($playerData As $playerStats) {
      if(intval($playerStats['goals_assist']) > 0) {
        $ret[] = $playerStats;
      }
    }
    usort($ret, 'cmpAssists');
    return $ret;
  }
}

function cmpAssists($a, $b) {
  $goal1 = $a['goals_assist'];
  $goal2 = $b['goals_assist'];

  return ($goal1 == $goal2) ? 0 : ($goal1 < $goal2) ? 1 : -1;
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_AssistStatistics.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_AssistStatistics.php']);
}

?>