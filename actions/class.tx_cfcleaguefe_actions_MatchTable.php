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
tx_div::load('tx_cfcleaguefe_models_team');
tx_div::load('tx_rnbase_action_BaseIOC');

/**
 * Controller für die Anzeige eines Spielplans
 */
class tx_cfcleaguefe_actions_MatchTable extends tx_rnbase_action_BaseIOC {

	/**
	 * Handle reuest
	 *
	 * @param arrayobject $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param arrayobject $viewdata
	 * @return string error message
	 */
	function handleRequest(&$parameters,&$configurations, &$viewdata) {

    // Die Werte des aktuellen Scope ermitteln
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
    $saisonUids = $scopeArr['SAISON_UIDS'];
    $groupUids = $scopeArr['GROUP_UIDS'];
    $compUids = $scopeArr['COMP_UIDS'];
    $roundUid = $scopeArr['ROUND_UIDS'];
    $club = $scopeArr['CLUB_UIDS'];

//t3lib_div::debug($scopeArr, 'ac_MatchTable');

    $matchTable = tx_div::makeInstance('tx_cfcleaguefe_models_matchtable');
    $matchTable->setTimeRange($configurations->get('matchTableTimeRangePast'),$configurations->get('matchTableTimeRangeFuture'));
    $matchTable->setLimit($configurations->get('matchtable.limit'));
    $matchTable->setOrderDesc($configurations->get('matchtable.orderDesc') ? true : false );
    $status = $configurations->get('matchtable.status');
    $extended = $configurations->get('matchtable.allData');
    // Spielplan für ein Team
    $teamId = $configurations->get('matchtable.teamId');
    if(!$teamId && $configurations->get('matchtable.acceptTeamIdFromRequest')) {
      $teamId = $parameters->offsetGet('teamId');
    }
    $matchTable->setTeam($teamId);

    $matches = $matchTable->findMatches($saisonUids, $groupUids, $compUids, $club, $roundUid, $status, $extended);
    
    $this->_resolveTeams($matches);
    
    $viewdata->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen

    // View
    $this->viewType = $configurations->get('matchtable.viewType');
    return '';
  }

  /**
   * Lädt alle Teams der Spiele und verknüpft sie mit den jeweiligen Spielen.
   */
  function _resolveTeams(&$matches) {
    // Einmal über alle Matches iterieren und die UIDs sammeln
    $mCnt = count($matches);
    if(!$mCnt) return; // Ohne Spiele gibt es nix zu tun
    $uids = array();
    for($i=0; $i < $mCnt; $i++) {
      $uids[] = $matches[$i]->record['home'];
      $uids[] = $matches[$i]->record['guest'];
    }
    $uids = array_unique($uids);
    $teams = tx_cfcleaguefe_models_team::getTeamsByUid($uids);
    $teamsArr = array();
    for($i=0; $i < count($teams); $i++) {
      $teamsArr[$teams[$i]->uid] = $teams[$i];
    }

//t3lib_div::debug($teamsArr, 'vw_matchtable');

    for($i=0; $i < $mCnt; $i++) {
      $matches[$i]->setHome( $teamsArr[$matches[$i]->record['home']]);
      $matches[$i]->setGuest( $teamsArr[$matches[$i]->record['guest']]);
    }
    return $teamsArr;
  }

	function getTemplateName() {return 'matchtable';}
	function getViewClassName() {
		return ($this->viewType == 'HTML') ? 'tx_cfcleaguefe_views_MatchTable' : 'tx_rnbase_view_phpTemplateEngine';
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchTable.php']);
}

?>