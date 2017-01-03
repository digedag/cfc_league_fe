<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('Tx_Rnbase_Utility_T3General');
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * Die Klasse ist in der Lage, Tabellen einer Liga darzustellen. Es ist aber keine typische Markerklasse, da umfangreichere
 * Daten übergeben werden.
 */
class tx_cfcleaguefe_util_LeagueTableWriter  {

	public function writeLeagueTable($template, $tableData, $marks, &$configurations, $confId) {
		$penalties = array(); // Strafen sammeln
		$subpartArray['###ROWS###'] = $this->_createTable(tx_rnbase_util_Templates::getSubpart($template, '###ROWS###'),
				$tableData, $penalties, $marks, $configurations, $confId);

		// Jetzt die Strafen auflisten
		if(tx_rnbase_util_BaseMarker::containsMarker($template, 'PENALTIES'))
			$subpartArray['###PENALTIES###'] = $this->_createPenalties($cObj->getSubpart($template, '###PENALTIES###'), $penalties, $configurations);

		$out .= tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		return $out;
	}

	/**
	 * Erstellt die Ligatabelle.
	 */
	private function _createTable($templateList, $tableData, &$penalties, &$marks, &$configurations, $confId) {
		// Sollen alle Teams gezeigt werden?
		$tableSize = intval($configurations->get($confId.'leagueTableSize'));
		if($tableSize && $tableSize < count($tableData)) {
			// Es sollen weniger Teams gezeigt werden als vorhanden sind
			// Diesen Ausschnitt müssen wir jetzt ermitteln
			$tableData = $this->_cropTable($tableData, $tableSize);
		}
		// Den TeamMarker erstellen
		$teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
		$templateEntry = tx_rnbase_util_Templates::getSubpart($templateList,'###ROW###');

		$parts = array();
		// Die einzelnen Zeilen zusammenbauen
		$rowRoll = intval($configurations->get($confId.'table.roll.value'));
		$rowRollCnt = 0;
		foreach($tableData As $row){
			$row['roll'] = $rowRollCnt;
			// Die Marks für die Zeile setzen
			$this->_setMark($row, $marks);
			// auf Strafen prüfen
			$this->_preparePenalties($row, $penalties);
			/* @var $team tx_cfcleaguefe_models_team */
			$team = $row['team'];
			unset($row['team']); // Gibt sonst Probleme mit PHP5.2
			$team->setProperty( Tx_Rnbase_Utility_T3General::array_merge($row, $team->getProperties()));

			$parts[] = $teamMarker->parseTemplate($templateEntry, $team, $configurations->getFormatter(), $confId.'table.', 'ROW');
			$rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
		}
		// Jetzt die einzelnen Teile zusammenfügen
    $markerArray = array();
    $subpartArray['###ROW###'] = implode($parts, $configurations->get($confId.'table.implode'));
		return tx_rnbase_util_Templates::substituteMarkerArrayCached($templateList, $markerArray, $subpartArray);
	}

	/**
	 * Setzt die Tabellenmarkierungen für eine Zeile
	 */
	function _setMark(&$row, &$marks) {
		if(is_array($marks) && array_key_exists($row['position'],$marks)){
			// Markierung und Bezeichnung setzen
			$row['mark'] = $marks[$row['position']][0];
			$row['markLabel'] = $marks[$row['position']][1];
		}
		else {
			$row['mark'] = '';
			$row['markLabel'] = '';
		}
	}
	/**
	 * Die Strafen müssen gesammelt und gezählt werden.
	 */
	function _preparePenalties(&$row, &$penalties) {
		if(is_array($row['penalties'])) {
			$penalties[] = $row['penalties'];
			$row['penalties'] = count($row['penalties']);
		}
		else
			$row['penalties'] = 0;
	}

	/**
	 * Wenn nur ein Teil der Tabelle gezeigt werden soll, dann wird dieser Ausschnitt hier
	 * ermittelt und zurückgeliefert.
	 * @param &$tableData Daten der Tabelle
	 * @param $tableSize Maximale Anzahl Teams, die gezeigt werden soll
	 */
	function _cropTable(&$tableData, $tableSize) {
		// Es werden nur 5 Teams gezeigt, dabei wird das erste markierte Team in die Mitte gesetzt
		// Suche des Tabellenplatz des markierten Teams
		$cnt = 0;
		$mark = 0;
		foreach($tableData As $row){
			if($row['markClub']) {
				$markIdx = $cnt;
				$mark = 1;
				break;
			}
			$cnt++;
		}

		if($mark) {
			$teams2Show = $tableSize;
			$offsetStart = intval($teams2Show / 2);
			$idxStart = ($markIdx - $offsetStart) >= 0 ? $markIdx - $offsetStart : 0;
			$idxEnd = $idxStart + $teams2Show;
			// Am Tabellenende nachregulieren
			if($idxEnd > count($tableData)) {
				$idxStart = $idxEnd - $teams2Show;
			}
		}
		return array_slice($tableData, $idxStart, $tableSize);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_LeagueTableWriter.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_LeagueTableWriter.php']);
}

?>