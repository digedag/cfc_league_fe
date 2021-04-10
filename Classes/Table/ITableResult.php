<?php

namespace System25\T3sports\Table;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2020 Rene Nitzsche (rene@system25.de)
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
 * Implementors provide access to computed table result.
 */
interface ITableResult
{
    /**
     * Set table data by round.
     *
     * @param int $round
     * @param array $scoreLine
     */
    public function addScore($round, $scoreLine);

    /**
     * Return table data by round.
     *
     * @param int $round
     *
     * @return array
     */
    public function getScores($round = 0);

    /**
     * Number of rounds.
     *
     * @return int
     */
    public function getRoundSize();

    public function getMarks();

    public function setMarks($marks);

    public function getPenalties();

    public function setPenalties($penalties);

    /**
     * @return tx_cfcleague_competition
     */
    public function getCompetition();

    /**
     * @param tx_cfcleague_competition $competition
     */
    public function setCompetition($competition);

    /**
     * @return IConfigurator
     */
    public function getConfigurator(): IConfigurator;

    /**
     * @param IConfigurator $configurator
     */
    public function setConfigurator(IConfigurator $configurator);
}
