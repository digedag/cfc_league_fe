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
tx_rnbase::load('tx_cfcleaguefe_table_ITableType');

/**
 * Computes league tables for football.
 */
class tx_cfcleaguefe_table_TableIceHockey extends t3lib_svbase implements tx_cfcleaguefe_table_ITableType {

	/**
	 * Set match provider
	 * @param tx_cfcleaguefe_table_IMatchProvider $matchProvider
	 * @return void
	 */
	public function setMatchProvider(tx_cfcleaguefe_table_IMatchProvider $matchProvider) {
		$this->matchProvider = $matchProvider;
	}
	/**
	 * Returns the final table data
	 * @return tx_cfcleaguefe_table_ITableResult
	 */
	public function getTableData() {
		
	}

	public function getTableWriter() {
		
	}
	
	public function getTCALabel() {
		return 'Icehockey';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_TableIceHockey.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_TableIceHockey.php']);
}

?>