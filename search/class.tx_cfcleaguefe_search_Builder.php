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


/**
 * Mit dem Builder werden haufig auftretende Suchanfragen zusammengebaut
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_search_Builder {

	/**
	 * Search for competition by teams
	 *
	 * @param array $fields
	 * @param string $teamUids comma separated list of team UIDs
	 * @return boolean true if condition is set
	 */
	static function buildCompetitionByTeam(&$fields, $teamUids, $obligateOnly = 'false') {
		$result = false;
		if(strlen(trim($memberUids))) {
	  	$fields['TEAM.UID'][OP_EQ_INT] = $teamUids;
	  	if($obligateOnly)
		  	$fields['COMPETITION.OBLIGATION'][OP_EQ_INT] = '1';
   		$result = true;
		}
  	return $result;
	}
	
	/**
	 * Search for teams by scope
	 *
	 * @param array $fields
	 * @param string $scope Scope Array
	 * @return true
	 */
	static function buildTeamByScope(&$fields, $scope) {
		$result = false;
		$saisonUids = $scope['SAISON_UIDS'];
    $groupUids = $scope['GROUP_UIDS'];
    $compUids = $scope['COMP_UIDS'];
    $clubUids = $scope['CLUB_UIDS'];
		if(strlen(trim($saisonUids))) {
	  	$fields['COMPETITION.SAISON'][OP_IN_INT] = $saisonUids;
   		$result = true;
		}
		if(strlen(trim($groupUids))) {
	  	$fields['COMPETITION.AGEGROUP'][OP_IN_INT] = $groupUids;
   		$result = true;
		}
		if(strlen(trim($compUids))) {
	  	$fields['COMPETITION.UID'][OP_IN_INT] = $compUids;
   		$result = true;
		}
		if(strlen(trim($clubUids))) {
	  	$fields['TEAM.CLUB'][OP_IN_INT] = $clubUids;
   		$result = true;
		}
	  $fields['TEAM.DUMMY'][OP_EQ_INT] = 0; // Ignore dummies
	  return true;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/search/class.tx_cfcleaguefe_search_Builder.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/search/class.tx_cfcleaguefe_search_Builder.php']);
}

?>