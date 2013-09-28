<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_cfcleaguefe_table_volleyball_IComparator');

/**
 * Comperator methods for volleyball league tables with 3 point system. 
 * http://sourceforge.net/apps/trac/cfcleague/ticket/74
 */
class tx_cfcleaguefe_table_volleyball_Comparator3Point implements tx_cfcleaguefe_table_volleyball_IComparator {
	public function setTeamData(array &$teamdata) {
		$this->_teamData = $teamdata;
	}
	/**
	 * Funktion zur Sortierung der Tabellenzeilen
	 * 1. Anzahl der Punkte
	 * 2. Anzahl gewonnener Spiele
	 * 3. Satzquotient
	 * 4. Ballpunktequotient
	 * 5. direkter Vergleich
	 */
	public static function compare($t1, $t2) {
		// Zwangsabstieg prüfen
		if($t1['last_place']) return 1;
		if($t2['last_place']) return -1;

		// Zuerst die Punkte
		if($t1['points'] == $t2['points']) {
			tx_rnbase_util_Debug::debug($t1,'compare'.__LINE__);
			// Die gewonnenen Spiele prüfen
			if($t1['winCount'] == $t2['winCount']) {
				// Jetzt den Satzquotient prüfen
				// TODO: die Quotienten schon vorberechnen
				$t1diff = $t1['sets1'] / ($t1['sets2'] > 0 ? $t1['sets2'] : 1);
				$t2diff = $t2['sets1'] / ($t2['sets2'] > 0 ? $t2['sets2'] : 1);
				if($t1diff == $t2diff) {
					// Jetzt der Ballquotient
					$t1balls = $t1['balls1'] / ($t1['balls2'] > 0 ? $t1['balls2'] : 1);
					$t2balls = $t2['balls1'] / ($t2['balls2'] > 0 ? $t2['balls2'] : 1);
					if($t1balls == $t2balls) {
						// TODO: Und jetzt der direkte Vergleich
						if($t1['matchCount'] == $t2['matchCount']) {
							return 0; // Punkt und Torgleich
						}
						return $t1['matchCount'] > $t2['matchCount'];
					}
					return $t1balls > $t2balls ? -1 : 1;
				}
				return $t1diff > $t2diff ? -1 : 1;
			}
			return $t1['winCount'] > $t2['winCount'] ? -1 : 1;
		}
		return $t1['points'] > $t2['points'] ? -1 : 1;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/volleyball/class.tx_cfcleaguefe_table_volleyball_Comparator.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/volleyball/class.tx_cfcleaguefe_table_volleyball_Comparator.php']);
}
