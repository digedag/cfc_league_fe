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

tx_rnbase::load('tx_rnbase_view_Base');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_rnbase_util_Templates');


/**
 * Viewklasse für die Anzeige der Ligatabelle mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_MatchCrossTable extends tx_rnbase_view_Base {
	function getMainSubpart() {return '###CROSSTABLE###';}

  /**
   * Erstellen des Frontend-Outputs
   * @param string $template
   * @param array $viewData
   * @param tx_rnbase_configurations $configurations
   * @param tx_rnbase_util_FormatUtil $formatter
   */
	function createOutput($template, &$viewData, &$configurations, &$formatter){
		$matches = $viewData->offsetGet('matches');
		if(!is_array($matches) || !count($matches)) {
			return $configurations->getLL('matchcrosstable.noData');
		}
		// Wir benötigen die beteiligten Teams
		$teams = $viewData->offsetGet('teams');
		$this->removeDummyTeams($teams);
		// Mit den Teams können wir die Headline bauen
		$headlineTemplate = tx_rnbase_util_Templates::getSubpart($template, '###HEADLINE###');
		tx_rnbase_util_Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'createHeadline');
		$subpartArray['###HEADLINE###'] = $this->_createHeadline($headlineTemplate, $teams, $configurations);
		tx_rnbase_util_Misc::pullTT();

		tx_rnbase_util_Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'generateTableData');
		$teamsArray = $this->generateTableData($matches, $teams);
		tx_rnbase_util_Misc::pullTT();

		$datalineTemplate = tx_rnbase_util_Templates::getSubpart($template, '###DATALINE###');
		tx_rnbase_util_Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'createDatalines');
		$subpartArray['###DATALINE###'] = $this->_createDatalines($datalineTemplate, $teamsArray, $teams, $configurations, $viewData);
		tx_rnbase_util_Misc::pullTT();
		$markerArray = array('###MATCHCOUNT###' => count($matches), );

		return tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
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

  	reset($matches);
  	reset($teams);
  	$teamIds = array_keys($teams);
  	$teamCnt = count($teamIds);
  	$initArray = array_flip($teamIds);
  	$opponents = $teams;
  	while (list($uid, $team)=each($teams))	{
  		$ret[$uid] = $initArray;
  		$ret[$uid][$uid] = ''; // Das Spiel gegen sich selbst
  		// In das Array alle Heimspiele des Teams legen
  		for($i=0; $i < $teamCnt; $i++) {
  			if($uid == $teamIds[$i])
  				$ret[$uid][$uid] = $this->ownMatchStr; // Das Spiel gegen sich selbst
  			else {
	  			$ret[$uid][$teamIds[$i]] = $this->findMatch($matches, $uid, $teamIds[$i]);
  			}
  		}
  	}
  	return $ret;
  }
  /**
   * Sucht aus dem Spielarray die Paarung mit der Heim- und Gastmannschaft
   *
   * @param array $matches
   * @param int $home uid der Heimmannschaft
   * @param int $guest uid der Gastmannschaft
   * @return tx_cfcleaguefe_models_match
   */
  private function findMatch(&$matches, $home, $guest) {
  	$ret = array();
  	for($i=0, $cnt = count($matches); $i < $cnt; $i++) {
  		if($matches[$i]->record['home'] == $home && $matches[$i]->record['guest'] == $guest)
//  			return $matches[$i];
  			$ret[] = $matches[$i];
  	}
  	// Die Paarung gibt es nicht.
  	return count($ret) ? $ret : $this->noMatchStr;
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
	private function _createDatalines($template, $datalines, &$teams, $configurations, $viewData) {
		$subTemplate = '###MATCHS###' . tx_rnbase_util_Templates::getSubpart($template, '###MATCHS###') . '###MATCHS###';
		$freeTemplate = tx_rnbase_util_Templates::getSubpart($template, '###MATCH_FREE###');
		$rowRoll = intval($configurations->get('matchcrosstable.dataline.match.roll.value'));

		$teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder', tx_rnbase::makeInstance('tx_cfcleaguefe_util_MatchMarkerBuilderInfo'));

		$lines = array();
		// Über alle Zeilen iterieren
		foreach($datalines as $uid=>$matches) {
			$rowRollCnt = 0;
			$parts = array();
			foreach($matches As $matchArr){
				if(is_array($matchArr)) {
					// Da sind Spiele im Array. Anzeigen mit ListMarker
					$parts[] = $listBuilder->render($matchArr,
										$viewData, $subTemplate, 'tx_cfcleaguefe_util_MatchMarker',
										'matchcrosstable.dataline.match.', 'MATCH', $this->formatter);
				}
				else
					$parts[] = $matchArr; // Sollte ein String sein...

				$rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
			}
			// Jetzt die einzelnen Teile zusammenfügen
			$subpartArray['###MATCHS###'] = implode($parts, $configurations->get('matchcrosstable.dataline.implode'));
			// Und das Team ins MarkerArray
			$lineTemplate = $teamMarker->parseTemplate($template, $teams[$uid], $this->formatter, 'matchcrosstable.dataline.team.', 'DATALINE_TEAM');
			$lines[] = tx_rnbase_util_Templates::substituteMarkerArrayCached($lineTemplate, $markerArray, $subpartArray);
		}
		return  implode($lines, $configurations->get('matchcrosstable.dataline.implode'));
	}
	/**
	 * Creates the table head
	 *
	 * @param string $headlineTemplate
	 * @param array $teams
	 * @param tslib_content $cObj
	 * @param tx_rnbase_configurations $configurations
	 */
	private function _createHeadline($template, &$teams, $configurations) {
		// Im Prinzip eine normale Teamliste...
		$teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
		$subTemplate = tx_rnbase_util_Templates::getSubpart($template, '###TEAM###');
		$rowRoll = intval($configurations->get('matchcrosstable.headline.team.roll.value'));
		$rowRollCnt = 0;
		$parts = array();

		tx_rnbase_util_Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'include teams');
		while (list($uid, $team)=each($teams))	{
			$team->record['roll'] = $rowRollCnt;
			$parts[] = $teamMarker->parseTemplate($subTemplate, $team, $this->formatter, 'matchcrosstable.headline.team.', 'TEAM');
			$rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
		}
		tx_rnbase_util_Misc::pullTT();

		$subpartArray['###TEAM###'] = implode($parts, $configurations->get('matchcrosstable.headline.team.implode'));
		$markerArray = array('###TEAMCOUNT###' => count($teams), );

		return tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
	}
  private function removeDummyTeams(&$teams) {
  	// Das Team 'Spielfrei' vorher entfernen
  	$dummyTeams = array();
  	reset($teams);
  	while (list($uid, $team)=each($teams))	{
  		if($team->isDummy())
  			$dummyTeams[] = $uid;
  	}
  	foreach($dummyTeams As $uid)
  		unset($teams[$uid]);
  	reset($teams);
  }
	/**
	 * Vorbereitung der Link-Objekte
	 */
	function _init(&$configurations) {
		$this->formatter = &$configurations->getFormatter();

		// String für Zellen ohne Spielansetzung
		$this->noMatchStr = $configurations->get('matchcrosstable.dataline.nomatch');
		$this->ownMatchStr = $configurations->get('matchcrosstable.dataline.ownmatch');
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchCrossTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchCrossTable.php']);
}
?>