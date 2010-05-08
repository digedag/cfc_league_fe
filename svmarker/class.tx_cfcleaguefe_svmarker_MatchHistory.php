<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Rene Nitzsche (rene@system25.de)
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
require_once(PATH_t3lib.'class.t3lib_svbase.php');

/**
 * Service to output historic matches of two match opponents
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_svmarker_MatchHistory extends t3lib_svbase {
	function addMatches($params, $parent) {

		$marker = $params['marker'];
		$template = $params['template'];
		if(tx_rnbase_util_BaseMarker::containsMarker($template, 'MARKERMODULE__MATCHHISTORY') ||
			tx_rnbase_util_BaseMarker::containsMarker($template, $marker.'_MATCHHISTORY')) {
				
			$formatter = $params['formatter'];
			$matches = $this->getMarkerValue($params, $formatter);
			$markerArray['###MARKERMODULE__MATCHHISTORY###'] = $matches; // backward
			$markerArray['###'.$marker.'_MATCHHISTORY###'] = $matches;
			$params['template'] = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		}
	}
	
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
		$options = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->configurations, 'matchreport.historic.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->configurations, 'matchreport.historic.options.');

		$srv = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchTable = $srv->getMatchTable();

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

		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($matches,
						new ArrayObject(), $templateCode, 'tx_cfcleaguefe_util_MatchMarker',
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