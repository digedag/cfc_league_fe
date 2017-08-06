<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2017 Rene Nitzsche (rene@system25.de)
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
 * Implementors provide matches necessary to compute league tables.
 */
interface tx_cfcleaguefe_table_IMatchProvider
{

    /**
     * The base competition.
     * It is normally used to retrieve some basic
     * configuration for table calculation.
     *
     * @return tx_cfcleague_models_Competition
     */
    public function getBaseCompetition();

    /**
     * Match penalties to handle
     *
     * @return array[tx_cfcleaguefe_models_penalty]
     */
    public function getPenalties();

    /**
     * Teams to handle
     *
     * @return array[tx_cfcleaguefe_models_team]
     */
    public function getTeams();

    /**
     * Matches sorted by rounds
     *
     * @return array[int][tx_cfcleaguefe_models_match]
     */
    public function getRounds();

    /**
     * Returns the number of all rounds.
     * This is used for chart generation.
     *
     * @return int
     */
    public function getMaxRounds();

    /**
     * Returns the table marks used to mark some posititions in table.
     * It should be normally
     * retrieved from competition.
     *
     * @return string
     */
    public function getTableMarks();

    // /**
    // * Return a unique key for a given team. This is not necessarily the uid of the team. Maybe it is
    // * wanted to join some team results.
    // *
    // * @param tx_cfcleaguefe_models_team $team
    // */
    // public function getTeamId($team);
}
