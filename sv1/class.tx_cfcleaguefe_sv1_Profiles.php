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
tx_div::load('tx_rnbase_util_DB');

interface tx_cfcleaguefe_ProfileService {
  function search($fields, $options);
}

/**
 * Service for accessing profile information
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv1_Profiles extends t3lib_svbase implements tx_cfcleaguefe_ProfileService  {

	/**
	 * Search database for teams
	 *
	 * @param array $fields
	 * @param array $options
	 * @return array of tx_cfcleaguefe_models_team
	 */
	function search($fields, $options) {
		tx_div::load('tx_rnbase_util_SearchBase');
		$searcher = tx_rnbase_util_SearchBase::getInstance('tx_cfcleaguefe_search_Profile');
		return $searcher->search($fields, $options);
	}
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_Profiles.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_Profiles.php']);
}

?>