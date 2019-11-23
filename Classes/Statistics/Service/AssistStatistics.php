<?php
namespace System25\T3sports\Statistics\Service;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2007-2019 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * Service for assist statistics
 * Since this list is similar to player statistics, it is based on that service.
 * It simply modifies the result
 *
 * @author Rene Nitzsche
 */
class AssistStatistics extends PlayerStatistics
{

    /**
     * Liefert die Liste der besten Vorlagengeber
     *
     * @return array
     */
    public function getResult()
    {
        return $this->_findAssists($this->getPlayersArray());
    }

    /**
     * Sucht die besten Vorlagengeber aus der Liste und liefert sie sortiert in einem Array zurück
     *
     * @return Array mit den Datensätzen der Vorlagengeber
     */
    private function _findAssists(&$playerData)
    {
        $ret = array();
        foreach ($playerData as $playerStats) {
            if (intval($playerStats['goals_assist']) > 0) {
                $ret[] = $playerStats;
            }
        }
        usort($ret, function($a, $b) {
            $goal1 = $a['goals_assist'];
            $goal2 = $b['goals_assist'];

            return ($goal1 == $goal2) ? 0 : ($goal1 < $goal2) ? 1 : - 1;
        });
        return $ret;
    }
}

