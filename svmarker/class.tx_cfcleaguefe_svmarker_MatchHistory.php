<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rene Nitzsche (rene@system25.de)
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
require_once(PATH_t3lib.'class.t3lib_svbase.php');

/**
 * Service to output historic matches of two match opponents
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_svmarker_MatchHistory extends t3lib_svbase {

	/**
	 * Generate chart
	 *
	 * @param array $params
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	function getMarkerValue($params, $formatter) {
	//function parseTemplate($templateCode, $params, $formatter) {
		$match = $this->getMatch($params);
		if(!is_object($match)) return false; // The call is not for us
		$competition = $match->getCompetition();
		$group = $competition->getGroup();

		$home = $match->getHome()->getClub();
		if(!$home) return '<!-- Home has no club defined -->';
		$guest = $match->getGuest()->getClub();
		if(!$guest) return '<!-- Guest has no club defined -->';

		$fields = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->configurations, 'matchreport.historic.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->configurations, 'matchreport.historic.options.');

		$matchTable = $this->getMatchTable();
		if(!intval($formatter->configurations->get('matchreport.historic.ignoreAgeGroup')))
			$matchTable->setAgeGroups($group->uid);
		$matchTable->setHomeClubs($home->uid . ',' . $guest->uid);
		$matchTable->setGuestClubs($home->uid . ',' . $guest->uid);
		$matchTable->getFields($fields, $options);
		$srv = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matches = $srv->search($fields, $options);

		// Wir brauchen das Template
		$templateCode = $formatter->configurations->getCObj()->fileResource($formatter->configurations->get('matchreport.historic.template'));
		if(!$templateCode) return '<!-- NO TEMPLATE FOUND -->';
		$subpartName = $formatter->configurations->get('subpartName');
		$subpartName = $subpartName ? $subpartName : '###HISTORIC_MATCHES###';
		$templateCode = $formatter->configurations->getCObj()->getSubpart($templateCode,$subpartName);
		if(!$templateCode) return '<!-- NO SUBPART '.$subpartName.' FOUND -->';

		$builderClass = tx_div::makeInstanceClassName('tx_rnbase_util_ListBuilder');
		$listBuilder = new $builderClass();
		$out = $listBuilder->render($matches,
						tx_div::makeInstance('tx_lib_spl_arrayObject'), $templateCode, 'tx_cfcleaguefe_util_MatchMarker',
						'matchreport.historic.match.', 'MATCH', $formatter);
		return $out;
	}
	/**
	 * Liefert das Match
	 *
	 * @param array $params
	 * @return tx_cfcleaguefe_models_match or false
	 */
	function getMatch($params){
		if(!isset($params['match'])) return false;
		return $params['match'];
	}
	/**
	 * @return tx_cfcleaguefe_util_MatchTable
	 */
	function getMatchTable() {
		return tx_div::makeInstance('tx_cfcleaguefe_util_MatchTable');
	}
	function parseTemplate($templateCode, $params, $formatter) {
		$match = $this->getMatch($params);
		if(!is_object($match)) return false; // The call is not for us
	  return '<h2>Not implemented. This is a single marker module!</h2>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/svmarker/class.tx_cfcleaguefe_svmarker_MatchHistory.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/svmarker/class.tx_cfcleaguefe_svmarker_MatchHistory.php']);
}

?>