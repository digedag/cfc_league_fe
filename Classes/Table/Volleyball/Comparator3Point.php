<?php

namespace System25\T3sports\Table\Volleyball;

use System25\T3sports\Table\Util;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2020 Rene Nitzsche (rene@system25.de)
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
 * Comperator methods for volleyball league tables with 3 point system.
 * http://sourceforge.net/apps/trac/cfcleague/ticket/74.
 */
class Comparator3Point implements IComparator
{
    public function setTeamData(array &$teamdata)
    {
        $this->_teamData = $teamdata;
    }

    /**
     * Funktion zur Sortierung der Tabellenzeilen
     * 1.
     * Anzahl der Punkte
     * 2. Anzahl gewonnener Spiele
     * 3. Satzquotient
     * 4. Ballpunktequotient
     * 5. direkter Vergleich.
     */
    public function compare($t1, $t2)
    {
        // Zwangsabstieg prüfen
        if ($t1['static_position']) {
            return 1;
        }
        if ($t2['static_position']) {
            return -1;
        }

        // Zuerst die Punkte
        if ($t1['points'] == $t2['points']) {
            // tx_rnbase_util_Debug::debug($t1,'compare'.__LINE__);
            // Die gewonnenen Spiele prüfen
            if ($t1['winCount'] == $t2['winCount']) {
                // Jetzt den Satzquotient prüfen
                $t1setquot = $t1['sets_quot'];
                $t2setquot = $t2['sets_quot'];
                if ($t1setquot == $t2setquot) {
                    // Jetzt der Ballquotient
                    $t1balls = $t1['balls_quot'];
                    $t2balls = $t2['balls_quot'];
                    if ($t1balls == $t2balls) {
                        // Und jetzt der direkte Vergleich
                        $baseData = Util::prepareH2H($this->_teamData, $t1, $t2);
                        $t1vst2 = $baseData['t1vst2'];
                        $t2vst1 = $baseData['t2vst1'];
                        $t1H2HPoints = $baseData['t1H2HPoints'];
                        $t2H2HPoints = $baseData['t2H2HPoints'];
                        if ($t1H2HPoints == $t2H2HPoints) {
                            // dann eben zuerst die Satzdifferenz der 2 Spiele prüfen (Hin- und Rückspiel)
                            $t1H2HDiff = 0 + $t1vst2[0] + $t2vst1[1] - $t1vst2[1] - $t2vst1[0];
                            $t2H2HDiff = 0 + $t1vst2[1] + $t2vst1[0] - $t1vst2[0] - $t2vst1[1];
                            if ($t1H2HDiff == $t2H2HDiff) {
                                return 0; // Gleichstand. Entscheidungsspiel wird nicht beachtet
                            }

                            return $t1H2HDiff > $t2H2HDiff ? -1 : 1;
                        }

                        return $t1H2HPoints > $t2H2HPoints ? -1 : 1;
                    }

                    return $t1balls > $t2balls ? -1 : 1;
                }

                return $t1setquot > $t2setquot ? -1 : 1;
            }

            return $t1['winCount'] > $t2['winCount'] ? -1 : 1;
        }

        return $t1['points'] > $t2['points'] ? -1 : 1;
    }
}
