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

tx_div::load('tx_cfcleaguefe_util_ScopeController');
tx_div::load('tx_cfcleaguefe_actions_MatchTable');

/**
 * Controller für die Anzeige eines Spielplans als Kreuztabelle
 */
class tx_cfcleaguefe_actions_MatchCrossTable extends tx_cfcleaguefe_actions_MatchTable {

	/**
	 * Set search criteria
	 *
	 * @param array $fields
	 * @param array $options
	 * @param array $parameters
	 * @param tx_rnbase_configurations $configurations
	 */
	protected function initSearch(&$fields, &$options, &$parameters, &$configurations) {
		$options['distinct'] = 1;
//  	$options['debug'] = 1;
		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'matchcrosstable.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'matchcrosstable.options.');

		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);

		$matchtable = $this->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->getFields($fields, $options);
	}

	function ___handleRequest(&$parameters,&$configurations, &$viewdata) {

    // Die Werte des aktuellen Scope ermitteln
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
    $saisonUids = $scopeArr['SAISON_UIDS'];
    $groupUids = $scopeArr['GROUP_UIDS'];
    $compUids = $scopeArr['COMP_UIDS'];
    $roundUid = $scopeArr['ROUND_UIDS'];
    $club = $scopeArr['CLUB_UIDS'];
    // Die Kreuztabelle wird nur für komplette Wettbewerbe erzeugt
    if(strlen($compUids) == 0) {
      $comps = tx_cfcleaguefe_models_competition::findAll($saisonUids, $groupUids, $compUids);
      if(count($comps) > 0) {
        $currCompetition = $comps[0];
        $currCompetition = $currCompetition->uid;
        // Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
      }
      else
        return $out; // Ohne Wettbewerb keine Tabelle!
    }
    else {
      $currCompetition = t3lib_div::intExplode(',', $compUids);
      $currCompetition = $currCompetition[0];
    }

//t3lib_div::debug($scopeArr, 'ac_MatchTable');

    $matchTable = tx_div::makeInstance('tx_cfcleaguefe_models_matchtable');
    $extended = $configurations->get('matchcrosstable.allData');
    $matches = $matchTable->findMatches($saisonUids, $groupUids, $currCompetition, '', '', $status, $extended);
    
    $teams = $this->_resolveTeams($matches);
    
    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen
    $viewData->offsetSet('teams', $teams); // Die Teams für den View bereitstellen
    
    return '';
  }
	function getTemplateName() {return 'matchcrosstable';}
	function getViewClassName() {	return 'tx_cfcleaguefe_views_MatchCrossTable'; }
  
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchCrossTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchCrossTable.php']);
}

?>