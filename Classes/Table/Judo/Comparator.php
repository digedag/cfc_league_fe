<?php

namespace System25\T3sports\Table\Judo;

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
            // Punkte sind gleich, also Differenzen der Siege prüfen
            $t1diff = $t1['goals1'] - $t1['goals2'];
            $t2diff = $t2['goals1'] - $t2['goals2'];
            if ($t1diff == $t2diff) {
                // Jetzt zählt die Differenz der Unterbewertung
                $t1scorediff = $t1['score1'] - $t1['score2'];
                $t2scorediff = $t2['score1'] - $t2['score2'];
                if ($t1scorediff == $t2scorediff) {
                    return 0; // Punkt und Torgleich
                }

                return $t1scorediff > $t2scorediff ? -1 : 1;
            }

            return $t1diff > $t2diff ? -1 : 1;
        }

        return $t1['points'] > $t2['points'] ? -1 : 1;
    }
}
