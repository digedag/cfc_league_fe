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
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');

tx_div::load('tx_cfcleaguefe_util_ScopeController');
tx_div::load('tx_rnbase_action_BaseIOC');
tx_div::load('tx_rnbase_util_Misc');
tx_div::load('tx_cfcleaguefe_search_Builder');

/**
 * Controller für die Anzeige einer Teamliste
 */
class tx_cfcleaguefe_actions_TeamList extends tx_rnbase_action_BaseIOC {

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
    $service = tx_cfcleaguefe_util_ServiceRegistry::getTeamService();
  	$teams = $service->search($fields, $options);

    $viewdata->offsetSet('teams', $teams); // Die Teams für den View bereitstellen
	}
	function handlePageBrowser(&$parameters,&$configurations, &$viewdata, &$fields, &$options) {
    if(is_array($configurations->get('teamlist.team.pagebrowser.'))) {
    	$service = tx_cfcleaguefe_util_ServiceRegistry::getTeamService();
    	// Mit Pagebrowser benötigen wir zwei Zugriffe um die Gesamtanzahl der Teams zu ermitteln 
  		$options['count']= 1;
	  	$listSize = $service->search($fields, $options);
	  	unset($options['count']);
	  	// PageBrowser initialisieren
	    $className = tx_div::makeInstanceClassName('tx_rnbase_util_PageBrowser');
	    $pageBrowser = new $className('teams');
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
  	tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'teamlist.fields.');
  	tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'teamlist.options.');

    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
  	tx_cfcleaguefe_search_Builder::buildTeamByScope($fields, $scopeArr);
	}

  /**
   * Liefert die Anzahl der Ergebnisse pro Seite
   *
   * @param array $parameters
   * @param tx_rnbase_configurations $configurations
   * @return int
   */
  protected function getPageSize(&$parameters, &$configurations) {
  	return intval($configurations->get('teamlist.team.pagebrowser.limit'));
  }
	
  function getTemplateName(&$configurations) {return 'teamlist';}
  function getViewClassName(&$configurations) {
    $viewType = $configurations->get('teamlist.viewType');
  	return ($viewType == 'HTML') ? 'tx_cfcleaguefe_views_TeamList' : 'tx_rnbase_view_phpTemplateEngine';
  }
  
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TeamList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TeamList.php']);
}

?>