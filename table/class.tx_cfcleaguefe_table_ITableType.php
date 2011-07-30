<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Rene Nitzsche (rene@system25.de)
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


/**
 * Implementors provide strategies to compute a league table.
 */
interface tx_cfcleaguefe_table_ITableType {
	/**
	 * Set match provider
	 * @param tx_cfcleaguefe_table_IMatchProvider $matchProvider
	 * @return void
	 */
	public function setMatchProvider(tx_cfcleaguefe_table_IMatchProvider $matchProvider);
	/**
	 * Get match provider
	 * @return tx_cfcleaguefe_table_IMatchProvider
	 */
	public function getMatchProvider();
	/**
	 * Set configuration
	 * @param tx_rnbase_configurations $configuration
	 * @param string confId
	 * @return void
	 */
	public function setConfigurations($configuration, $confId);
	/**
	 * Returns the final table data
	 * @return tx_cfcleaguefe_table_ITableResult
	 */
	public function getTableData();

	/**
	 * @return tx_cfcleaguefe_table_ITableWriter
	 */
	public function getTableWriter();
	/**
	 * @return tx_cfcleaguefe_table_IConfigurator
	 */
	public function getConfigurator();
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_ITableType.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_ITableType.php']);
}

?>