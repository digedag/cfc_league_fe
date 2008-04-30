<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');

tx_div::load('tx_rnbase_util_SearchBase');


/**
 * Class to search profiles from database
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_search_Profile extends tx_rnbase_util_SearchBase {

	protected function getTableMappings() {
		$tableMapping['PROFILE'] = 'tx_cfcleague_profiles';
		return $tableMapping;
	}

  protected function getBaseTable() {
  	return 'tx_cfcleague_profiles';
  }
  function getWrapperClass() {
  	return 'tx_cfcleaguefe_models_profile';
  }
	
  protected function getJoins($tableAliases) {
  	$join = '';
    return $join;
  }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/search/class.tx_cfcleaguefe_search_Profile.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/search/class.tx_cfcleaguefe_search_Profile.php']);
}

?>