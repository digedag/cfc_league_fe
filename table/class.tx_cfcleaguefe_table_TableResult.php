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

tx_rnbase::load('tx_cfcleaguefe_table_ITableResult');

/**
 * Implementors provide access to computed table result.
 */
class tx_cfcleaguefe_table_TableResult implements tx_cfcleaguefe_table_ITableResult {
	private $tableData = array();
	/**
	 * Add score for round
	 * @param int $round round starts with 1
	 * @param array $scoreLine
	 * @return void
	 */
	public function addScore($round, $scoreLine) {
		$this->tableData[$round][] = $scoreLine;
	}
	/**
	 * Return table data by round
	 * @param int $round
	 * @return array
	 */
	public function getScores($round=0) {
		$ret = $round > 0 ? $this->tableData[$round] : $this->tableData[count($this->tableData)];
		return is_array($ret) ? $ret : array();
	}

	public function getMarks(){
		return $this->marks;
	}
	public function setMarks($marks){
		$this->marks = $marks;
	}
	public function getPenalties(){
		return $this->penalties;
	}
	public function setPenalties($penalties){
		$this->penalties = $penalties;
	}
	/**
	 * @return tx_cfcleague_competition
	 */
	public function getCompetition(){
		return $this->competition;
	}
	/**
	 * 
	 * @param tx_cfcleague_competition $competition
	 */
	public function setCompetition($competition){
		$this->competition = $competition;
	}
	/**
	 * @return tx_cfcleaguefe_table_IConfigurator
	 */
	public function getConfigurator() {
		return $this->configurator;
	}
	/**
	 * 
	 * @param tx_cfcleaguefe_table_IConfigurator $configurator
	 */
	public function setConfigurator($configurator) {
		$this->configurator = $configurator;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_TableResult.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_TableResult.php']);
}

?>