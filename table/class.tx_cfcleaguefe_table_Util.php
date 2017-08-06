<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2017 Rene Nitzsche (rene@system25.de)
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
 * Some util methods
 */
class tx_cfcleaguefe_table_Util
{

    /**
     * Prepare base data for head2head compare.
     *
     * @return array with keys t1H2HPoints, t2H2HPoints, t1vst2, t2vst1
     */
    public static function prepareH2H($teamData, $t1, $t2)
    {
        $t1vst2 = preg_split('[ : ]', $teamData[$t1['teamId']]['matches'][$t2['teamId']]);
        $t2vst1 = preg_split('[ : ]', $teamData[$t2['teamId']]['matches'][$t1['teamId']]);

        $t1H2HPoints = 0;
        $t2H2HPoints = 0;
        if (count($t1vst2) > 0 && $t1vst2[0] > $t1vst2[1]) {
            $t1H2HPoints += 1;
        } elseif (count($t1vst2) > 0 && $t1vst2[0] < $t1vst2[1]) {
            $t2H2HPoints += 1;
        }
        if (count($t2vst1) > 0 && $t2vst1[0] > $t2vst1[1]) {
            $t2H2HPoints += 1;
        } elseif (count($t2vst1) > 0 && $t2vst1[0] < $t2vst1[1]) {
            $t1H2HPoints += 1;
        }
        return array(
            't1H2HPoints' => $t1H2HPoints,
            't2H2HPoints' => $t2H2HPoints,
            't1vst2' => $t1vst2,
            't2vst1' => $t2vst1
        );
    }
}
