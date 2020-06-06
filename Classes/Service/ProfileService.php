<?php

namespace System25\T3sports\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2016 Rene Nitzsche (rene@system25.de)
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
 * Service for accessing profile information.
 *
 * @author Rene Nitzsche
 */
class ProfileService extends \Tx_Rnbase_Service_Base
{
    /**
     * Find team notes for a profile.
     *
     * @param \tx_cfcleaguefe_models_profile $profile
     * @param \tx_cfcleaguefe_models_team $team
     */
    public function getTeamNotes(&$profile, &$team)
    {
        $what = '*';
        $from = 'tx_cfcleague_team_notes';
        $options = [];
        $options['where'] = 'player = '.$profile->uid.' AND team = '.$team->uid;
        $options['wrapperclass'] = 'tx_cfcleaguefe_models_teamNote';
        //		$options['orderby'] = 'minute asc, extra_time asc, uid asc';
        $teamNotes = \tx_rnbase_util_DB::doSelect($what, $from, $options, 0);

        return $teamNotes;
    }

    /**
     * Search database for teams.
     *
     * @param array $fields
     * @param array $options
     *
     * @return array of tx_cfcleaguefe_models_team
     */
    public function search($fields, $options)
    {
        $searcher = \tx_rnbase_util_SearchBase::getInstance(\System25\T3sports\Search\ProfileSearch::class);

        return $searcher->search($fields, $options);
    }
}
