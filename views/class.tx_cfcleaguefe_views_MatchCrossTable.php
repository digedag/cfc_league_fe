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

tx_div::load('tx_rnbase_view_Base');
tx_div::load('tx_rnbase_util_Misc');


/**
 * Viewklasse für die Anzeige der Ligatabelle mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_MatchCrossTable extends tx_rnbase_view_Base {

  /**
   * Erstellen des Frontend-Outputs
   */
  function render($view, &$configurations){
    $this->_init($configurations);
    $cObj =& $configurations->getCObj(0);
    $templateCode = $cObj->fileResource($this->getTemplate($view,'.html'));
    if(!$templateCode)
    	tx_rnbase_util_Misc::mayday('Template File not found: ' . $this->getTemplate($view,'.html'), 'T3sports');
    // Den entscheidenden Teil herausschneiden
    $templateCode = $cObj->getSubpart($templateCode, '###CROSSTABLE###');
    if(!$templateCode)
    	tx_rnbase_util_Misc::mayday('Subpart ###CROSSTABLE### not found in template!', 'T3sports');
    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();
    $out = $this->_createView($templateCode, $viewData, $configurations);
    return $out;
  }

  /**
   * Erstellung des Outputstrings
   */
  function _createView($template, &$viewData, &$configurations) {
    $cObj =& $this->formatter->cObj;
    $matches = $viewData->offsetGet('matches');
    // Wir benötigen die beteiligten Teams
    $teams = $viewData->offsetGet('teams');
    
    // Mit den Teams können wir die Headline bauen
    $headlineTemplate = $cObj->getSubpart($template, '###HEADLINE###');
    $subpartArray['###HEADLINE###'] = $this->_createHeadline($headlineTemplate, $teams, $cObj, $configurations);

    $teamsArray = $this->generateTableData($matches, $teams);

    $datalineTemplate = $cObj->getSubpart($template, '###DATALINE###');
    $subpartArray['###DATALINE###'] = $this->_createDatalines($datalineTemplate, $teamsArray, $teams, $cObj, $configurations);
    

    $markerArray = array('###MATCHCOUNT###' => count($matches), );
    return $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
  }

  /**
   * Erstellt ein Array dessen Key die UIDs der Teams sind. Value ist ein Array
   * mit den Spielen des Teams
   *
   * @param array $matches
   * @param array $teams
   */
  private function generateTableData(&$matches, &$teams) {
  	$ret = array();
  	$teamIds = array_keys($teams);
  	$teamCnt = count($teamIds);
  	$initArray = array_flip($teamIds);
  	reset($teams);
  	$opponents = $teams;
  	while (list($uid,$team)=each($teams))	{
  		$ret[$uid] = $initArray;
  		$ret[$uid][$uid] = ''; // Das Spiel gegen sich selbst
  		// In das Array alle Heimspiele des Teams legen
  		for($i=0; $i < $teamCnt; $i++) {
  			if($uid == $teamIds[$i])
  				$ret[$uid][$uid] = '<td>-</td>'; // Das Spiel gegen sich selbst
  			else
	  			$ret[$uid][$teamIds[$i]] = $this->findMatch($matches, $uid, $teamIds[$i]);
  		}
  	}
  	return $ret;
  }
  private function findMatch(&$matches, $home, $guest) {
  	for($i=0, $cnt = count($matches); $i < $cnt; $i++) {
  		if($matches[$i]->record['home'] == $home && $matches[$i]->record['guest'] == $guest)
  			return $matches[$i];
  	}
  	return 'NONE';
  }
  /**
   * Erstellt die Datenzeilen der Tabelle
   *
   * @param string $headlineTemplate
   * @param array $datalines
   * @param array $teams
   * @param tslib_content $cObj
   * @param tx_rnbase_configurations $configurations
   */
  private function _createDatalines($template, $datalines, &$teams, &$cObj, &$configurations) {
    $subTemplate = $cObj->getSubpart($template, '###MATCH###');
    $freeTemplate = $cObj->getSubpart($template, '###MATCH_FREE###');
    $rowRoll = intval($configurations->get('matchcrosstable.dataline.match.roll.value'));

    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_MatchMarker');
    $matchMarker = new $markerClass($this->links);
    $matchMarker->setFullMode(intval($configurations->get('matchcrosstable.dataline.matchFullMode') != 0));
    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $teamMarker = new $teamMarkerClass;
    
    $lines = array();
    // Über alle Zeilen iterieren
    foreach($datalines as $uid=>$matches) {
    	$rowRollCnt = 0;
	    $parts = array();
	    foreach($matches As $match){
    		if(is_object($match)) {
		      $match->record['roll'] = $rowRollCnt;
		      $parts[] = $matchMarker->parseTemplate($match->isDummy() ? $freeTemplate : $subTemplate, $match, $this->formatter, 'matchcrosstable.dataline.match.', 'MATCH');
    		}
    		else
    			$parts[] = $match;

        $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
	    }
	    // Jetzt die einzelnen Teile zusammenfügen
	    $subpartArray['###MATCH###'] = implode($parts, $configurations->get('matchcrosstable.dataline.match.implode'));
	    // Und das Team ins MarkerArray
      $lineTemplate = $teamMarker->parseTemplate($template, $teams[$uid], $this->formatter, 'matchcrosstable.dataline.team.', $this->links, 'DATALINE_TEAM');
	    
    	$lines[] = $this->formatter->cObj->substituteMarkerArrayCached($lineTemplate, $markerArray, $subpartArray);
    }
    return  implode($lines, $configurations->get('matchcrosstable.dataline.implode'));
    
//    return $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
  }
  /**
   * Creates the table head
   *
   * @param string $headlineTemplate
   * @param array $teams
   * @param tslib_content $cObj
   * @param tx_rnbase_configurations $configurations
   */
  private function _createHeadline($template, &$teams, &$cObj, &$configurations) {
  	// Im Prinzip eine normale Teamliste...
    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $teamMarker = new $teamMarkerClass;
    $subTemplate = $cObj->getSubpart($template, '###TEAM###');
    $rowRoll = intval($configurations->get('matchcrosstable.headline.team.roll.value'));
    $rowRollCnt = 0;
    $parts = array();
    
    $links = array( 'team' => $this->links['team']);

  	while (list($uid,$team)=each($teams))	{
      $team->record['roll'] = $rowRollCnt;
      $parts[] = $teamMarker->parseTemplate($subTemplate, $team, $this->formatter, 'matchcrosstable.headline.team.', $links, 'TEAM');
      $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
    }

    $subpartArray['###TEAM###'] = implode($parts, $configurations->get('matchcrosstable.headline.team.implode'));
    $markerArray = array('###TEAMCOUNT###' => count($teams), );
    
    return $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
  }
  /**
   * Vorbereitung der Link-Objekte
   */
  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    $this->links = array();

    $reportPage = $configurations->get('reportPage');
    if($reportPage) {
      $link = new $linkClass;
      $link->designatorString = $configurations->getQualifier();
      $link->destination($reportPage); // Das Ziel der Seite vorbereiten
      $this->links['match'] = $link;
    }

    $teamPage = $configurations->get('matchcrosstable.teamPage');
    if($teamPage) {
      $linkTeam = new $linkClass;
      $linkTeam->designatorString = $configurations->getQualifier();
      $linkTeam->destination($teamPage); // Das Ziel der Seite vorbereiten

      $this->links['team'] = $linkTeam;
    }
  }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchCrossTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchCrossTable.php']);
}
?>