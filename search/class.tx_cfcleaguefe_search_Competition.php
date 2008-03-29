<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006 Rene Nitzsche
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

define('AKAGSRV_FIELD_AKAG_NAME', 'AKAG.NAME');
define('AKAGSRV_FIELD_AKAG_SORTING', 'AKAG.SORTING');
define('AKAGSRV_FIELD_AKAG_BESCHREIBUNG', 'AKAG.BESCHREIBUNG');
define('AKAGSRV_FIELD_AKAG_RUBRIK', 'AKAG.RUBRIK');
define('AKAGSRV_FIELD_AKAG_THEMEN', 'AKAG.THEMEN');
define('AKAGSRV_FIELD_MEMBER_PERSON', 'MEMBER.PERSON');
define('AKAGSRV_FIELD_MANDAT_PERSON', 'MANDAT.PERSON');
define('AKAGSRV_FIELD_MANDAT_FUNKTION', 'MANDAT.FUNKTION');
define('AKAGSRV_FIELD_MANDAT_SORTING', 'MANDAT.SORTING');
define('AKAGSRV_FIELD_MANDAT_GRUPPENTYP', 'MANDAT.GRUPPENTYP');

/**
 * Class to search comptitions from database
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_search_Competition extends tx_rnbase_util_SearchBase {

	protected function getTableMappings() {
		$tableMapping['TEAM'] = 'tx_cfcleague_teams';
		$tableMapping['COMPETITION'] = 'tx_cfcleague_competition';
		return $tableMapping;
	}

  protected function getBaseTable() {
  	return 'tx_cfcleague_competition';
  }
  function getWrapperClass() {
  	return 'tx_cfcleaguefe_models_competition';
  }
	
  protected function getJoins($tableAliases) {
  	$join = '';
    if(isset($tableAliases['TEAM'])) {
    	$join .= ' JOIN tx_cfcleague_teams ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )';
    }
    return $join;
  }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/search/class.tx_cfcleaguefe_search_Competition.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/search/class.tx_cfcleaguefe_search_Competition.php']);
}

?>