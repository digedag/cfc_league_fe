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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_cfcleaguefe_models_team');
tx_rnbase::load('tx_rnbase_action_BaseIOC');

tx_rnbase::load('tx_cfcleaguefe_util_MatchTable');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');


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
		$filter = tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewData, $this->getConfId());
		$fields = array();
		$options = array();
		
//  	$options['debug'] = 1;
		$filter->init($fields, $options, $parameters, $configurations, $this->getConfId());
		//$this->initSearch($fields, $options, $parameters, $configurations);
		$listSize = 0;
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		// Soll ein PageBrowser verwendet werden
		tx_rnbase_filter_BaseFilter::handlePageBrowser($configurations, 
			$this->getConfId().'match.pagebrowser', $viewdata, $fields, $options, array(
			'searchcallback'=> array($service, 'search'),
			'pbid' => 'mt'.$configurations->getPluginId(),
			)
		);

		$prov = tx_rnbase::makeInstance('tx_rnbase_util_ListProvider');
		$prov->initBySearch(array($service, 'search'), $fields, $options);
		$viewdata->offsetSet('provider', $prov);

		// View
		$this->viewType = $configurations->get($this->getConfId().'viewType');
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
		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $this->getConfId().'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $this->getConfId().'options.');

		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
		// Spielplan für ein Team
		$teamId = $configurations->get($this->getConfId().'teamId');
		if($configurations->get($this->getConfId().'acceptTeamIdFromRequest')) {
			$teamId = $parameters->offsetGet('teamId');
		}

		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchtable = $service->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->setTeams($teamId);
		$clubId = $configurations->get($this->getConfId().'fixedOpponentClub');
		if($clubId) {
			// Show matches against a defined club
			$scopeClub = $matchtable->getClubs();
			$matchtable->setClubs('');
			if($scopeClub)
				$clubId .= ','.$scopeClub;
			$matchtable->setHomeClubs($clubId);
			$matchtable->setGuestClubs($clubId);
		}
		
		$matchtable->setTimeRange($configurations->get($this->getConfId().'timeRangePast'),$configurations->get('matchtable.timeRangeFuture'));
		if($configurations->get($this->getConfId().'acceptRefereeIdFromRequest')) {
			$matchtable->setReferees($parameters->offsetGet('refereeId'));
		}

		$matchtable->getFields($fields, $options);
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