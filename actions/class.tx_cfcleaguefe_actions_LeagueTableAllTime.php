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

tx_rnbase::load('tx_cfcleaguefe_actions_LeagueTableShow');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_cfcleaguefe_util_LeagueTable');
tx_rnbase::load('tx_cfcleaguefe_util_MatchTable');

/**
 * Controller für die Anzeige eines unbegrenzten Liga-Tabelle
 * TODO: Controller für Hin-Rückrunde entfernen
 */
class tx_cfcleaguefe_actions_LeagueTableAllTime extends tx_cfcleaguefe_actions_LeagueTableShow {

	/**
	 * Zeigt die Tabelle für eine Liga. Die Tabelle wird nur dann berechnet, wenn auf der
	 * aktuellen Seite genau ein Wettbewerb ausgewählt ist und dieser Wettbewerb eine Liga ist.
	 */
	function handleRequest(&$parameters, &$configurations, &$viewData){
		// Die Werte des aktuellen Scope ermitteln
		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
		$this->initSearch($fields, $options, $parameters, $configurations);
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matches = $service->search($fields, $options);

		$dataArr = $this->buildTable($parameters, $configurations, $matches);
		$viewData =& $configurations->getViewData();
		$viewData->offsetSet('tableData', $dataArr['table']); // Die Tabelle für den View bereitstellen
		$viewData->offsetSet('tablePointSystem', $dataArr['pointsystem']); // Die Tabelle für den View bereitstellen

		// Müssen zusätzliche Selectboxen gezeigt werden?
		$this->_handleSBTableType($parameters, $configurations, $viewData);
		$this->_handleSBPointSystem($parameters, $configurations, $viewData);
		$this->_handleSBTableScope($parameters, $configurations, $viewData, 'leaguetableAllTime');

		return '';
	}

	function getTemplateName() { return 'leaguetableAllTime';}
	function getViewClassName() { return 'tx_cfcleaguefe_views_LeagueTableAllTime';}

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
		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'leaguetableAllTime.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'leaguetableAllTime.options.');

		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);

		$matchtable = $this->getMatchTable();
		$matchtable->setScope($scopeArr);
//		$matchtable->setStatus(2);

		$matchtable->getFields($fields, $options);
	}
	/**
	 * @return tx_cfcleaguefe_util_MatchTable
	 */
	function getMatchTable() {
		return t3lib_div::makeInstance('tx_cfcleaguefe_util_MatchTable');
	}

	/**
	 * Sammelt die Daten für die Erstellung der Tabelle
	 */
	function buildTable($parameters, &$configurations, &$matches) {
		$tableProvider = tx_rnbase::makeInstance('tx_cfcleaguefe_util_league_AllTimeTableProvider', $parameters, $configurations, $matches, 'leaguetableAllTime.');

		$leagueTable = new tx_cfcleaguefe_util_LeagueTable();
		$arr = Array(
			'table' => $leagueTable->generateTable($tableProvider),
			'pointsystem' => $tableProvider->cfgPointSystem,
		);
		return $arr;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LeagueTableAllTime.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LeagueTableAllTime.php']);
}

?>