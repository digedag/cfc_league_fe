<?php

namespace System25\T3sports\Table\Volleyball;

use System25\T3sports\Table\IComparator;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2024 Rene Nitzsche (rene@system25.de)
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
 * Comperator methods for volleyball league tables.
 */
class Comparator implements IComparator
{
    private $_teamData;

    public function setTeamData(array &$teamdata)
    {
        $this->_teamData = $teamdata;
    }

    /**
     * Funktion zur Sortierung der Tabellenzeilen.
     */
    public function compare($t1, $t2)
    {
        // Zwangsabstieg prüfen
        if (-1 == ($t1['static_position'] ?? 0)) {
            return 1;
        }
        if (-1 == ($t2['static_position'] ?? 0)) {
            return -1;
        }

        if ($t1['points'] == $t2['points']) {
            // Im 2-Punkte-Modus sind die Minuspunkte ausschlaggebend
            // da sie im 3-PM immer identisch sein sollten, können wir immer testen
            if ($t1['points2'] == $t2['points2']) {
                // Jetzt die Tordifferenz prüfen
                $t1diff = $t1['sets1'] - $t1['sets2'];
                $t2diff = $t2['sets1'] - $t2['sets2'];
                if ($t1diff == $t2diff) {
                    // Jetzt zählen die mehr gewonnene Sätze
                    if ($t1['sets1'] == $t2['sets1']) {
                        // #49: Und jetzt noch die Anzahl Spiele werten
                        if ($t1['matchCount'] == $t2['matchCount']) {
                            return 0; // Punkt und Torgleich
                        }

                        return $t1['matchCount'] > $t2['matchCount'];
                    }

                    return $t1['sets1'] > $t2['sets1'] ? -1 : 1;
                }

                return $t1diff > $t2diff ? -1 : 1;
            }

            // Bei den Minuspunkten ist weniger mehr
            return $t1['points2'] < $t2['points2'] ? -1 : 1;
        }

        return $t1['points'] > $t2['points'] ? -1 : 1;
    }
}
