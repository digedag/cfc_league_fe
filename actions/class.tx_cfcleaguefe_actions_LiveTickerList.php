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
tx_div::load('tx_rnbase_action_BaseIOC');
tx_div::load('tx_cfcleaguefe_util_MatchTable');

/**
 * Controller für die Anzeige der Spiele, für die ein LiveTicker geschaltet ist.
 */
class tx_cfcleaguefe_actions_LiveTickerList extends tx_rnbase_action_BaseIOC {

	/**
	 * Handle request
	 *
	 * @param arrayobject $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param arrayobject $viewdata
	 * @return string error message
	 */
	function handleRequest(&$parameters,&$configurations, &$viewdata) {
		$fields = array();
		$options = array();
//  	$options['debug'] = 1;
		$this->initSearch($fields, $options, $parameters, $configurations);
		$listSize = 0;
		// Soll ein PageBrowser verwendet werden
		$this->handlePageBrowser($parameters,$configurations, $viewdata, $fields, $options);
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matches = $service->search($fields, $options);

		$viewdata->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen

		return '';
/////////////

    $matchTable = tx_div::makeInstance('tx_cfcleaguefe_models_matchtable');
    $matchTable->setTimeRange($configurations->get('tickerlist.timeRangePast'),$configurations->get('tickerlist.timeRangeFuture'));
    $matchTable->setLimit($configurations->get('tickerlist.limit'));
    $matchTable->setOrderDesc($configurations->get('tickerlist.orderDesc') ? true : false );
    $matchTable->setLiveTicker(1); // Nur LiveTickerspiele holen

    $matches = $matchTable->findMatches($saisonUids, $groupUids, $compUids, $club, $roundUid);

    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('matches', $matches);

// t3lib_div::debug($viewData,'ac_tickerlist');

    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_LiveTickerList');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('templateLiveTickerList'));
    $out = $view->render('livetickerlist', $configurations);

    return $out;
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
		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'tickerlist.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'tickerlist.options.');

		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
		
		$matchtable = $this->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->setTeams($teamId);
		$matchtable->setTimeRange($configurations->get('tickerlist.timeRangePast'),$configurations->get('tickerlist.timeRangeFuture'));
    $matchtable->setLiveTicker();; // Nur Live-Tickerspiele holen
		
		$matchtable->getFields($fields, $options);
	}
	/**
	 * @return tx_cfcleaguefe_util_MatchTable
	 */
	function getMatchTable() {
		return t3lib_div::makeInstance('tx_cfcleaguefe_util_MatchTable');
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
		if(is_array($configurations->get('tickerlist.match.pagebrowser.'))) {
			$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
			// Mit Pagebrowser benötigen wir zwei Zugriffe, um die Gesamtanzahl der Spiele zu ermitteln
			$options['count']= 1;
			$listSize = $service->search($fields, $options);
			unset($options['count']);
			// PageBrowser initialisieren
			$className = tx_div::makeInstanceClassName('tx_rnbase_util_PageBrowser');
			$pageBrowser = new $className('tickerlist_' . $configurations->getPluginId());
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
	function getTemplateName() {return 'tickerlist';}
	function getViewClassName() {	return 'tx_cfcleaguefe_views_LiveTickerList';	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LiveTickerList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LiveTickerList.php']);
}

?>