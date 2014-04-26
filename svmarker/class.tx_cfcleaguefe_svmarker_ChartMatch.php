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

/**
 * Service to output a chart to compare two match opponents
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_svmarker_ChartMatch extends t3lib_svbase {

	function addChart($params, $parent) {
		$marker = $params['marker'];
		$confId = $params['confid'];
		$template = $params['template'];
		if(!tx_rnbase_util_BaseMarker::containsMarker($template, 'MARKERMODULE__CHARTMATCH') &&
			!tx_rnbase_util_BaseMarker::containsMarker($template, $marker.'_CHARTMATCH')) return;
		$formatter = $params['formatter'];
		$chart = $this->getMarkerValue($params, $formatter, $confId.'chart.');
		$markerArray['###MARKERMODULE__CHARTMATCH###'] = $chart; // backward
		$markerArray['###'.$marker.'_CHARTMATCH###'] = $chart;
		$params['template'] = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
	}
	/**
	 * Generate chart
	 *
	 * @param array $params
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId matchreport.match.
	 */
	function getMarkerValue($params, $formatter, $confId) {
		if(!isset($params['match'])) return false;
		$match = $params['match'];
		$competition = $match->getCompetition();
		if(!$competition->isTypeLeague()) return '';

		// TODO: die confid sollte angepaßt werden
		$tableProvider = tx_rnbase::makeInstance('tx_cfcleaguefe_util_league_SingleMatchTableProvider', 
			$match, $formatter->getConfigurations()->getParameters(), $formatter->getConfigurations(), $competition, 'matchreport.svChartMatch.table.');

		$leagueTable = tx_rnbase::makeInstance('tx_cfcleaguefe_util_LeagueTable');
		$xyDataset = $leagueTable->generateChartData($tableProvider);
		$tsArr = $formatter->configurations->get('matchreport.svChartMatch.');

		tx_rnbase::load('tx_cfcleaguefe_actions_TableChart');
		tx_cfcleaguefe_actions_TableChart::createChartDataset($xyDataset, $tsArr, $formatter->configurations, $competition, 'matchreport.svChartMatch.');
		try {
			require_once(PATH_site.t3lib_extMgm::siteRelPath('pbimagegraph').'class.tx_pbimagegraph_ts.php');
			$chart = tx_pbimagegraph_ts::make($tsArr);
		}
		catch(Exception $e) {
			$chart = 'Chart not possible. Check devlog';
			tx_rnbase::load('tx_rnbase_util_Logger');
			tx_rnbase_util_Logger::warn('Error on chart creation.', 'cfc_league_fe', array('Exception' => $e->getMessage(), 'Match'=>$match->getUid()));
		}
		return $chart;
	}
	/**
	 * @return tx_cfcleaguefe_util_MatchTable
	 */
	function getMatchTable() {
		return tx_rnbase::makeInstance('tx_cfcleaguefe_util_MatchTable');
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php']);
}

?>