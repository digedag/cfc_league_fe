<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('Tx_Rnbase_Service_Base');

/**
 * Service to output historic matches of two match opponents
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_svmarker_MatchHistory extends Tx_Rnbase_Service_Base {
	/**
	 * Add historic matches
	 * @param $params
	 * @param $parent
	 */
	public function addMatches($params, $parent) {
		$marker = $params['marker'];
		$template = $params['template'];
		if(tx_rnbase_util_BaseMarker::containsMarker($template, 'MARKERMODULE__MATCHHISTORY') ||
			tx_rnbase_util_BaseMarker::containsMarker($template, $marker.'_MATCHHISTORY')) {

			$formatter = $params['formatter'];
			$matches = $this->getMarkerValue($params, $formatter);
			$markerArray['###MARKERMODULE__MATCHHISTORY###'] = $matches; // backward
			$markerArray['###'.$marker.'_MATCHHISTORY###'] = $matches;
			$params['template'] = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		}
	}

	/**
	 * Generate chart
	 *
	 * @param array $params
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	private function getMarkerValue($params, $formatter) {
	//function parseTemplate($templateCode, $params, $formatter) {
		$match = $this->getMatch($params);
		if(!is_object($match)) return false; // The call is not for us
		$competition = $match->getCompetition();
		$group = $competition->getGroup();

		$home = $match->getHome()->getClub();
		if(!$home) return '<!-- Home has no club defined -->';
		$guest = $match->getGuest()->getClub();
		if(!$guest) return '<!-- Guest has no club defined -->';

		$confId = 'matchreport.historic.';
		$fields = array();
		$options = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->getConfigurations(), $confId.'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->getConfigurations(), $confId.'options.');

		$srv = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchTable = $srv->getMatchTable();

		if(!intval($formatter->configurations->get($confId.'ignoreAgeGroup')))
			$matchTable->setAgeGroups($group->uid);
		$matchTable->setHomeClubs($home->uid . ',' . $guest->uid);
		$matchTable->setGuestClubs($home->uid . ',' . $guest->uid);
		$matchTable->getFields($fields, $options);
		$matches = $srv->search($fields, $options);

		// Wir brauchen das Template
		$subpartName = $formatter->getConfigurations()->get($confId.'subpartName');
		$subpartName = $subpartName ? $subpartName : '###HISTORIC_MATCHES###';
		$templateCode = tx_rnbase_util_Templates::getSubpartFromFile(
		    $formatter->getConfigurations()->get($confId.'template'),
		    $subpartName
		);
		if(!$templateCode){
		    return '<!-- NO SUBPART '.$subpartName.' FOUND -->';
		}
		
		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($matches,
						false, $templateCode, 'tx_cfcleaguefe_util_MatchMarker',
						$confId.'match.', 'MATCH', $formatter);
		return $out;
	}
	/**
	 * Liefert das Match
	 *
	 * @param array $params
	 * @return tx_cfcleaguefe_models_match or false
	 */
	private function getMatch($params){
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