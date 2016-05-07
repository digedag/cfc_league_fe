<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2016 Rene Nitzsche
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

tx_rnbase::load('tx_rnbase_util_SearchBase');

define('COMPSRV_FIELD_COMP_NAME', 'COMP.NAME');
define('COMPSRV_FIELD_TEAM_NAME', 'TEAM.NAME');

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