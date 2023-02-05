<?php

namespace System25\T3sports\Table;

use System25\T3sports\Model\Competition;
use System25\T3sports\Model\CompetitionPenalty;
use System25\T3sports\Model\Fixture;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2022 Rene Nitzsche (rene@system25.de)
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
interface IMatchProvider
{
    /**
     * The base competition.
     * It is normally used to retrieve some basic
     * configuration for table calculation.
     *
     * @return Competition
     */
    public function getBaseCompetition();

    /**
     * Fixture penalties to handle.
     *
     * @return CompetitionPenalty[]
     */
    public function getPenalties();

    /**
     * Teams to handle.
     *
     * @return ITeam[]
     */
    public function getTeams();

    /**
     * Matches sorted by rounds.
     *
     * @return array[int][Fixture]
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

    /**
     * @param IConfigurator $configurator
     */
    public function setConfigurator(IConfigurator $configurator);

    /**
     * Return club uids of all running matches.
     *
     * @return array
     */
    public function getClubIdsOfRunningMatches();
}
