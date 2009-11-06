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

tx_div::load('tx_cfcleaguefe_util_MatchTable');
tx_div::load('tx_cfcleaguefe_search_Builder');


/**
 * Controller für die Anzeige eines Spielplans
 */
class tx_cfcleaguefe_actions_MatchTable extends tx_rnbase_action_BaseIOC {

	
	/**
	 * Handle request
	 *
	 * @param arrayobject $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param arrayobject $viewdata
	 * @return string error message
	 */
	function handleRequest(&$parameters,&$configurations, &$viewdata) {
		// Wir suchen über den Scope, sowie über zusätzlich per TS gesetzte Bedingungen
		// ggf. die Konfiguration aus der TS-Config lesen
		$fields = array();
		$options = array();

//  	$options['debug'] = 1;
		$this->initSearch($fields, $options, $parameters, $configurations);
		$listSize = 0;
		// Soll ein PageBrowser verwendet werden
		$this->handlePageBrowser($parameters,$configurations, $viewdata, $fields, $options);
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matches = $service->search($fields, $options);
		$teams = $this->_resolveTeams($matches);

		$viewdata->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen
		$viewdata->offsetSet('teams', $teams); // Die Teams für den View bereitstellen

		// View
		$this->viewType = $configurations->get('matchtable.viewType');
		return '';
	}

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
		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'matchtable.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'matchtable.options.');

		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
		// Spielplan für ein Team
		$teamId = $configurations->get('matchtable.teamId');
		if($configurations->get('matchtable.acceptTeamIdFromRequest')) {
			$teamId = $parameters->offsetGet('teamId');
		}

		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchtable = $service->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->setTeams($teamId);
		$clubId = $configurations->get('matchtable.fixedOpponentClub');
		if($clubId) {
			// Show matches against a defined club
			$scopeClub = $matchtable->getClubs();
			$matchtable->setClubs('');
			if($scopeClub)
				$clubId .= ','.$scopeClub;
			$matchtable->setHomeClubs($clubId);
			$matchtable->setGuestClubs($clubId);
		}
		
		$matchtable->setTimeRange($configurations->get('matchtable.timeRangePast'),$configurations->get('matchtable.timeRangeFuture'));
		if($configurations->get('matchtable.acceptRefereeIdFromRequest')) {
			$matchtable->setReferees($parameters->offsetGet('refereeId'));
		}

		$matchtable->getFields($fields, $options);
	}
  /**
   * Initializes page browser
   *
   * @param arrayobject $parameters
   * @param tx_rnbase_configurations $configurations
   * @param arrayobject $viewdata
   * @param array $fields
   * @param array $options
   */
	function handlePageBrowser(&$parameters,&$configurations, &$viewdata, &$fields, &$options) {
		if(is_array($configurations->get('matchtable.match.pagebrowser.'))) {
			$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
			// Mit Pagebrowser benötigen wir zwei Zugriffe, um die Gesamtanzahl der Spiele zu ermitteln
			$options['count']= 1;
			$listSize = $service->search($fields, $options);
			unset($options['count']);
			// PageBrowser initialisieren
			$className = tx_div::makeInstanceClassName('tx_rnbase_util_PageBrowser');
			$pageBrowser = new $className('matchtable_' . $configurations->getPluginId());
			$pageSize = $this->getPageSize($parameters, $configurations);
			//Wurde neu gesucht?
			if($parameters->offsetGet('NK_newsearch')) {
				// Der Suchbutton wurde neu gedrückt. Der Pager muss initialisiert werden
				$pageBrowser->setState(null, $listSize, $pageSize);
			}
			else {
				$pageBrowser->setState($parameters, $listSize, $pageSize);
			}
			$limit = $pageBrowser->getState();
			$options = array_merge($options, $limit);
			$viewdata->offsetSet('pagebrowser', $pageBrowser);
		}
	}

	/**
	 * Liefert die Anzahl der Ergebnisse pro Seite
	 *
	 * @param array $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @return int
	 */
	protected function getPageSize(&$parameters, &$configurations) {
		return intval($configurations->get('matchtable.match.pagebrowser.limit'));
	}

	function _handleRequest(&$parameters,&$configurations, &$viewdata) {

    // Die Werte des aktuellen Scope ermitteln
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
    $saisonUids = $scopeArr['SAISON_UIDS'];
    $groupUids = $scopeArr['GROUP_UIDS'];
    $compUids = $scopeArr['COMP_UIDS'];
    $roundUid = $scopeArr['ROUND_UIDS'];
    $club = $scopeArr['CLUB_UIDS'];

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