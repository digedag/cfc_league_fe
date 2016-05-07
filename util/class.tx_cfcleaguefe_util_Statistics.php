<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
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


tx_rnbase::load('tx_cfcleaguefe_models_match_note');

/**
 * Erstellung von Statistiken.
 *
 */
class tx_cfcleaguefe_util_Statistics {
	/**
	 * Returns a new instance
	 * @return tx_cfcleaguefe_util_Statistics
	 */
	public static function createInstance() {
		return tx_rnbase::makeInstance('tx_cfcleaguefe_util_Statistics');
	}

	public function createStatisticsCallback($scopeArr, &$services, &$configuration, &$parameters) {
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchtable = $service->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->setStatus(2);
		$fields = array();
		$options = array();
		$options['orderby']['MATCH.DATE'] = 'asc';
//		$options['debug'] = 1;
		$matchtable->getFields($fields, $options);
		$prov = tx_rnbase::makeInstance('tx_rnbase_util_ListProvider');
		$prov->initBySearch(array($service, 'search'), $fields, $options);

		$this->initServices($services, $scopeArr, $configuration, $parameters);
		$prov->iterateAll(array($this, 'handleMatch'));
		$ret = $this->collectData();
		return $ret;
	}
	private function initServices($services, $scopeArr, $configuration, $parameters) {
		$this->clubId = $scopeArr['CLUB_UIDS'];
		$this->servicesArr = array_values($services);
		$this->serviceKeys = array_keys($services);
		$this->servicesArrCnt = count($this->servicesArr);
		for($i=0; $i < $servicesArrCnt; $i++) {
			$service =& $servicesArr[$i];
			$service->prepare($scopeArr, $configuration, $parameters);
		}

	}
	/**
	 * Callback methode
	 * @param tx_cfcleague_models_Match $match
	 */
	public function handleMatch($match) {
		$matches = array($match);
		$matches = tx_cfcleaguefe_models_match_note::retrieveMatchNotes($matches);

		for($i=0; $i < $this->servicesArrCnt; $i++) {
			$service =& $this->servicesArr[$i];
			$service->handleMatch($match, $this->clubId);
		}
	}
	private function collectData() {
		// Abschließend die Daten zusammenpacken
		$ret = array();
		for($i=0; $i < $this->servicesArrCnt; $i++) {
			$service =& $this->servicesArr[$i];
			$ret[$this->serviceKeys[$i]] = $service->getResult();
		}
		return $ret;
	}

	/**
	 * Start creation of statistical data
	 *
	 * @param array $matches
	 * @param array $scopeArr
	 * @param array $services
	 */
	public static function createStatistics(&$matches, $scopeArr, &$services, &$configuration, &$parameters) {
		$clubId = $scopeArr['CLUB_UIDS'];
		$servicesArr = array_values($services);
		$serviceKeys = array_keys($services);
		$servicesArrCnt = count($servicesArr);
		for($i=0; $i < $servicesArrCnt; $i++) {
			$service =& $servicesArr[$i];
			$service->prepare($scopeArr, $configuration, $parameters);
		}

		// Über alle Spiele iterieren und diese an die Services geben
		for($j=0, $mc = count($matches); $j < $mc; $j++){
			for($i=0; $i < $servicesArrCnt; $i++) {
				$service =& $servicesArr[$i];
				$service->handleMatch($matches[$j], $clubId);
			}
		}
		// Abschließend die Daten zusammenpacken
		$ret = array();
		for($i=0; $i < $servicesArrCnt; $i++) {
			$service =& $servicesArr[$i];
			$ret[$serviceKeys[$i]] = $service->getResult();
		}
		return $ret;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_Statistics.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_Statistics.php']);
}

?>