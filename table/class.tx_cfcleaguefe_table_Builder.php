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
 * Builder class for league tables. 
 */
class tx_cfcleaguefe_table_Builder {

	/**
	 * 
	 * @param tx_cfcleague_util_MatchTable $matchTable
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return tx_cfcleaguefe_table_ITableType
	 */
	public static function buildByRequest($scopeArr, $configurations, $confId) {
		$matchTable = tx_cfcleague_util_ServiceRegistry::getMatchService()->getMatchTableBuilder();
		$matchTable->setScope($scope);

		tx_rnbase::load('tx_cfcleaguefe_table_Factory');
		$prov = tx_cfcleaguefe_table_Factory::createMatchProviderByMatchTable($matchTable, $configurations, $confId);
		$table = tx_cfcleaguefe_table_Factory::createTableType('football');
		$table->setMatchProvider($prov);
		$result = $table->getTableData();
		return $table;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_Builder.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_Builder.php']);
}

?>