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


/**
 * Service for visitor statistics
 * Count how visitors of all teams 
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv2_VisitorStatistics extends t3lib_svbase {
  /** Array with statistical results */
  private $result = array();
  private $statData = array('home_matchcount', 'home_total','home_average', 
                            'away_matchcount', 'away_total','away_average',
                            'all_matchcount', 'all_total','all_average');
  
  public function prepare($scope, &$configurations, &$parameters) {
    $this->scopeArr = $scope;
    $this->configurations = $configurations;
    $this->parameters = $parameters;
  }

  /**
   * Handle single match
   *
   * @param tx_cfcleaguefe_models_match $match
   * @param int $clubId
   */
  public function handleMatch(&$match, $clubId) {
    // Was suche wir?
    // Team Heim/Ausw/Gesamt  AnzSp, AnzZuschauer, Zuschauerschnitt
    $home = $match->getHome()->uid;
    $guest = $match->getGuest()->uid;
    $this->countVisitors($home, 'home', $match->getVisitors());
    $this->countVisitors($guest, 'away', $match->getVisitors());
    $this->countVisitors($home, 'all', $match->getVisitors());
    $this->countVisitors($guest, 'all', $match->getVisitors());
  }
  
  /**
   * Liefert die Liste der besten Vorlagengeber
   *
   * @return array
   */
  public function getResult() {
    // den Zuschauerschnitt berechnen
    $teamIds = array_keys($this->result);
    for($i = 0, $size = count($teamIds); $i < $size; $i++) {
      $this->setAverage($teamIds[$i], 'home');
      $this->setAverage($teamIds[$i], 'away');
      $this->setAverage($teamIds[$i], 'all');
    }

    // Jetzt noch sortieren
    $this->sortResult();
    
    return $this->result;
  }
  private function sortResult() {
    $sortOrder = $this->configurations->get('statistics.visitors.sortOrder');
    if($sortOrder == 'home_total') {
      usort($this->result, 'cmpVisitors_HomeTotal');
    }
    elseif ($sortOrder == 'away_total') {
      usort($this->result, 'cmpVisitors_AwayTotal');
    }
    elseif ($sortOrder == 'all_total') {
      usort($this->result, 'cmpVisitors_AllTotal');
    }
    elseif ($sortOrder == 'home_average') {
      usort($this->result, 'cmpVisitors_HomeAvg');
    }
    elseif ($sortOrder == 'away_average') {
      usort($this->result, 'cmpVisitors_AwayAvg');
    }
    elseif ($sortOrder == 'all_average') {
      usort($this->result, 'cmpVisitors_AllAvg');
    }
  }

	/**
	 * Returns the marker instance to map result data to HTML markers
	 *
	 * @param tx_rnbase_configurations $configurations
	 * @return tx_cfcleaguefe_sv2_TeamStatisticsMarker
	 */
	public function getMarker($configurations) {
		return tx_rnbase::makeInstance('tx_cfcleaguefe_sv2_TeamStatisticsMarker');
	}
  
  /**
   * Zählt die Zuschauer eines Spiels
   *
   * @param int $teamId ID des Teams
   * @param string $type home, away, all
   * @param int $visitors Zuschaueranzahl
   */
  private function countVisitors($teamId, $type, $visitors) {
    $teamData =& $this->_getData($this->result, $teamId);
    $teamData[$type.'_matchcount'] = intval($teamData[$type.'_matchcount']) + 1;
    $teamData[$type.'_total'] = intval($teamData[$type.'_total']) + $visitors;
  }
  /**
   * Berechnet den Durchschnittswert der Zuschauer
   *
   * @param int $teamId ID des Teams
   * @param string $type home, away, all
   */
  private function setAverage($teamId, $type) {
    $matches = $this->result[$teamId][$type.'_matchcount'];
    if($matches) {
      $visitors = $this->result[$teamId][$type.'_total'];
      $this->result[$teamId][$type.'_average'] = intval($visitors / $matches);
    }
  }
  /**
   * Liefert das Datenarray für einen Spieler als Referenz. Sollte es noch nicht
   * vorhanden sein, dann wird es angelegt. Außerdem wird jeder Statistikeintrag mit 0 initialisiert.
   * Die ist notwendig, damit später alle Marker im HTML-Template ersetzt werden.
   */
  private function &_getData(&$dataArray, $teamId) {
    if(!array_key_exists($teamId, $dataArray)) {
      $dataArray[$teamId] = array();
      // Alle Daten initialisieren
      foreach($this->statData as $col) {
        $dataArray[$teamId][$col] = 0;
      }
      $dataArray[$teamId]['team'] = $teamId;
    }
    return $dataArray[$teamId];
  }
  
}

function cmpVisitors_HomeAvg($a, $b) {
  return cmpVisitors($a,$b,'home_average');
}
function cmpVisitors_AwayAvg($a, $b) {
  return cmpVisitors($a,$b,'away_average');
}
function cmpVisitors_AllAvg($a, $b) {
  return cmpVisitors($a,$b,'all_average');
}

function cmpVisitors_HomeTotal($a, $b) {
  return cmpVisitors($a,$b,'home_total');
}

function cmpVisitors_AwayTotal($a, $b) {
  return cmpVisitors($a,$b,'away_total');
}
function cmpVisitors_AllTotal($a, $b) {
  return cmpVisitors($a,$b,'all_total');
}

function cmpVisitors($a, $b, $attr) {
  $v1 = $a[$attr];
  $v2 = $b[$attr];
  return ($v1 == $v2) ? 0 : ($v1 < $v2) ? 1 : -1;
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_VisitorStatistics.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_VisitorStatistics.php']);
}

?>