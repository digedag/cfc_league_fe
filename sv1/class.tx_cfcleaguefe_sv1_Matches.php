<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2014 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_util_DB');

interface tx_cfcleaguefe_MatchService {
  function search($fields, $options);
	/**
	 * @return tx_cfcleaguefe_util_MatchTable
	 */
  function getMatchTable();
}

/**
 * Service for accessing match information
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv1_Matches extends t3lib_svbase implements tx_cfcleaguefe_MatchService  {

	/**
	 * Search database for matches
	 *
	 * @param array $fields
	 * @param array $options
	 * @return array of tx_cfcleaguefe_models_match
	 */
	function search($fields, $options) {
		tx_rnbase::load('tx_rnbase_util_SearchBase');
		$searcher = tx_rnbase_util_SearchBase::getInstance('tx_cfcleaguefe_search_Match');
		return $searcher->search($fields, $options);
	}
	/**
	 * @return tx_cfcleaguefe_util_MatchTableBuilder
	 */
	public function getMatchTable() {
		return tx_rnbase::makeInstance('tx_cfcleague_util_MatchTableBuilder');
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_Matches.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_Matches.php']);
}

?>