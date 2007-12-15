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

require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_ScopeController.php');
require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'models/class.tx_cfcleaguefe_models_team.php');

/**
 * Controller für die Anzeige eines Spielplans
 */
class tx_cfcleaguefe_actions_MatchTable {

  /**
   *
   */
  function execute($parameters,$configurations){

// t3lib_div::debug($T3_SERVICES['cal_event_model'], 'ac_matchtable');


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
      $teamId = $parameters->offsetGet('$teamId');
    }
    $matchTable->setTeam($teamId);

    $matches = $matchTable->findMatches($saisonUids, $groupUids, $compUids, $club, $roundUid, $status, $extended);
    
    $this->_resolveTeams($matches);
    
    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen

    // View
    $viewType = $configurations->get('matchtable.viewType');
    $view = ($viewType == 'HTML') ? tx_div::makeInstance('tx_cfcleaguefe_views_MatchTable') : 
                                    tx_div::makeInstance('tx_rnbase_view_phpTemplateEngine');

    $view->setTemplatePath($configurations->getTemplatePath());
    $view->setTemplateFile($configurations->get('matchTableTemplate'));
    $out = $view->render('matchtable', $configurations);
    return $out;
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
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchTable.php']);
}

?>