<?php

namespace System25\T3sports\Table;

use System25\T3sports\Model\Competition;

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
class TableResult implements ITableResult
{
    private $tableData = [];

    /**
     * Add score for round.
     *
     * @param int $round round starts with 1
     * @param array $scoreLine
     */
    public function addScore($round, $scoreLine)
    {
        $this->tableData[$round][] = $scoreLine;
    }

    public function getRoundSize()
    {
        return count($this->tableData);
    }

    /**
     * Return table data by round
     * If round is 0 the highest available round is returned.
     *
     * @param int $round
     *
     * @return array
     */
    public function getScores($round = 0)
    {
        if (0 == $round) {
            $rounds = array_keys($this->tableData);
            $round = $rounds[count($rounds) - 1];
        } elseif (!array_key_exists($round, $this->tableData)) {
            // Wenn für den übergebenen Spieltag keine Daten vorhanden sind, wird der nächst vorher liegende Spieltag geliefert.
            $rounds = array_keys($this->tableData);
            $usedRound = 1;
            foreach ($rounds as $availableRound) {
                if ($availableRound <= $round) {
                    $usedRound = $availableRound;
                } else {
                    break;
                }
            }
            $round = $usedRound;
        }
        $ret = $this->tableData[$round];

        return is_array($ret) ? $ret : [];
    }

    public function getMarks()
    {
        return $this->marks;
    }

    public function setMarks($marks)
    {
        $this->marks = $marks;
    }

    public function getPenalties()
    {
        return $this->penalties;
    }

    public function setPenalties($penalties)
    {
        $this->penalties = $penalties;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * @param Competition $competition
     */
    public function setCompetition($competition)
    {
        $this->competition = $competition;
    }

    /**
     * @return IConfigurator
     */
    public function getConfigurator(): IConfigurator
    {
        return $this->configurator;
    }

    /**
     * @param IConfigurator $configurator
     */
    public function setConfigurator(IConfigurator $configurator)
    {
        $this->configurator = $configurator;
    }
}
