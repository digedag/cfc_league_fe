<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_football_IComparator');

/**
 * Comperator methods for football league tables.
 */
class tx_cfcleaguefe_table_football_Comparator implements tx_cfcleaguefe_table_football_IComparator {
	public function setTeamData(array &$teamdata) {
		$this->_teamData = $teamdata;
	}
	/**
	 * Funktion zur Sortierung der Tabellenzeilen
	 */
	public static function compare($t1, $t2) {
		// Zwangsabstieg prüfen
		if($t1['last_place']) return 1;
		if($t2['last_place']) return -1;

		if($t1['points'] == $t2['points']) {
			// Im 2-Punkte-Modus sind die Minuspunkte ausschlaggebend
			// da sie im 3-PM immer identisch sein sollten, können wir immer testen
			if($t1['points2'] == $t2['points2']) {
				// Jetzt die Tordifferenz prüfen
				$t1diff = $t1['goals1'] - $t1['goals2'];
				$t2diff = $t2['goals1'] - $t2['goals2'];
				if($t1diff == $t2diff) {
					// Jetzt zählen die mehr geschossenen Tore
					if($t1['goals1'] == $t2['goals1']) {
						// #49: Und jetzt noch die Anzahl Spiele werten
						if($t1['matchCount'] == $t2['matchCount']) {
							return 0; // Punkt und Torgleich
						}
						return $t1['matchCount'] > $t2['matchCount'];
					}
					return $t1['goals1'] > $t2['goals1'] ? -1 : 1;
				}
				return $t1diff > $t2diff ? -1 : 1;
			}
			// Bei den Minuspunkten ist weniger mehr
			return $t1['points2'] < $t2['points2'] ? -1 : 1;
		}
		return $t1['points'] > $t2['points'] ? -1 : 1;
	}

	/**
	 * Funktion zur Sortierung der Tabellenzeilen nach dem Head-to-head modus.
	 * Bei Punktgleichstand zählt hier zuerst der direkte Vergleich
	 */
	public static function compareH2H($t1, $t2) {
/* CDe begin */
		$isH2HComparison = true; // = "is Head-to-head-comparison"

		// Zwangsabstieg prüfen
		if($t1['last_place']) return 1;
		if($t2['last_place']) return -1;

		if($t1['points'] == $t2['points']) {
			// Im 2-Punkte-Modus sind die Minuspunkte auschlaggebend
			// da sie im 3-PM immer identisch sein sollten, können wir immer testen
			if($t1['points2'] == $t2['points2']) {
				// direkter Vergleich gilt vor Tordifferenz / wird ignoriert, falls !$isH2HComparison
				$t1vst2 = preg_split('[ : ]', $this->_teamData[$t1['teamId']]['matches'][$t2['teamId']]);
				$t2vst1 = preg_split('[ : ]', $this->_teamData[$t2['teamId']]['matches'][$t1['teamId']]);

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

				if ($t1H2HPoints == $t2H2HPoints || !$isH2HComparison) {
					// dann eben zuerst die Tordifferenz der 2 Spiele prüfen (Hin- und Rückspiel)
					$t1H2HDiff = 0 + $t1vst2[0] + $t2vst1[1] - $t1vst2[1] - $t2vst1[0];
					$t2H2HDiff = 0 + $t1vst2[1] + $t2vst1[0] - $t1vst2[0] - $t2vst1[1];
					if($t1H2HDiff == $t2H2HDiff || !$isH2HComparison) {
						// jetzt prüfen, wer mehr Auswärtstore geschossen hat
						if ($t1vst2[1] == $t2vst1[1] || !$isH2HComparison) {
							// jetzt die allgemeine Tordifferenz prüfen
							$t1diff = $t1['goals1'] - $t1['goals2'];
							$t2diff = $t2['goals1'] - $t2['goals2'];
							if($t1diff == $t2diff) {
								// jetzt zählen die mehr geschossenen Tore
								if($t1['goals1'] == $t2['goals1']) {
									// #49: Und jetzt noch die Anzahl Spiele werten
									if($t1['matchCount'] == $t2['matchCount']) {
										return 0; // Punkt und Torgleich
									}
									return $t1['matchCount'] > $t2['matchCount'];
								}
								return $t1['goals1'] > $t2['goals1'] ? -1 : 1;
							}
							return $t1diff > $t2diff ? -1 : 1;
						}
						return $t2vst1[1] > $t1vst2[1] ? -1 : 1;
					}
					return $t1H2HDiff > $t2H2HDiff ? -1 : 1;
				}
				return $t1H2HPoints > $t2H2HPoints ? -1 : 1;
			}
			// Bei den Minuspunkten ist weniger mehr
			return $t1['points2'] < $t2['points2'] ? -1 : 1;
		}
		return $t1['points'] > $t2['points'] ? -1 : 1;
/* CDe end */
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_Comparator.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_Comparator.php']);
}

?>