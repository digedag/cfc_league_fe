<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

interface TeamService {
  function findTeamsByScope($scope);
}

/**
 * Service for accessing team information
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv1_Teams extends t3lib_svbase implements TeamService  {

  /**
   * Implemements team search
   *
   * @param array $scope
   */
  function findTeamsByScope($scope) {
    $saisonUids = $scope['SAISON_UIDS'];
    $groupUids = $scope['GROUP_UIDS'];
    $compUids = $scope['COMP_UIDS'];
    $clubUids = $scope['CLUB_UIDS'];

    // Wir laden alle in der TCA definiert Spalten
    $cols = tx_rnbase_util_DB::getColumnNames('tx_cfcleague_teams', 'tx_cfcleague_teams');
    $what = 'distinct tx_cfcleague_teams.uid, '. implode(', ',$cols);
    $what .= ', (SELECT tx_cfcleague_group.name FROM tx_cfcleague_group WHERE tx_cfcleague_group.uid = tx_cfcleague_competition.agegroup) As agegroup_name ';
    $from = Array( '
       tx_cfcleague_teams 
         INNER JOIN tx_cfcleague_competition ON FIND_IN_SET(tx_cfcleague_teams.uid, tx_cfcleague_competition.teams)', 
         'tx_cfcleague_teams');

    $options['where'] = '';
    if(isset($saisonUids) && strlen($saisonUids) > 0)
      $options['where'] .= 'tx_cfcleague_competition.saison IN (' . $saisonUids . ')';

    if(isset($groupUids) && strlen($groupUids) > 0) {
      if(strlen($options['where']) >0) $options['where'] .= ' AND ';
      $options['where'] .= ' tx_cfcleague_competition.agegroup IN (' . $groupUids . ')';
    }

    if(isset($compUids) && strlen($compUids) > 0) {
      if(strlen($options['where']) >0) $options['where'] .= ' AND ';
      $options['where'] .= ' tx_cfcleague_competition.uid IN (' . $compUids . ')';
    }
    
    if(isset($clubUids) && strlen($clubUids) > 0) {
      if(strlen($options['where']) >0) $options['where'] .= ' AND ';
      $options['where'] .= ' tx_cfcleague_teams.club IN (' . $clubUids . ')';
    }
    if(strlen($options['where']) >0) $options['where'] .= ' AND ';
    $options['where'] .= ' tx_cfcleague_teams.dummy = 0 '; // Keine Dummyteams laden
    
    $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';
    $options['orderby'] = 'tx_cfcleague_teams.name ' . ($this->_orderDesc ? 'DESC' : 'ASC' );

    $rows = tx_rnbase_util_DB::doSelect($what, $from, $options, 0);

    return $rows;
    
/*
SELECT distinct tx_cfcleague_teams.uid, tx_cfcleague_teams.name FROM tx_cfcleague_teams
INNER JOIN tx_cfcleague_competition ON FIND_IN_SET(tx_cfcleague_teams.uid, tx_cfcleague_competition.teams)
WHERE tx_cfcleague_competition.saison = 3
*/
  }
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_Teams.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_Teams.php']);
}

?>