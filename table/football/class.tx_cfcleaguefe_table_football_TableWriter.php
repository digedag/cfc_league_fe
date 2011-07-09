<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_ITableWriter');
tx_rnbase::load('tx_rnbase_util_BaseMarker');

/**
 * Computes league tables for football.
 */
class tx_cfcleaguefe_table_football_TableWriter implements tx_cfcleaguefe_table_ITableWriter {

	/**
	 * Set table data by round
	 * @param tx_cfcleaguefe_table_ITableType $table
	 * @param string $template
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return string
	 */
	public function writeTable($table, $template, $configurations, $confId) {
		$result = $table->getTableData();
		// Zuerst den Wettbewerb
		if(tx_rnbase_util_BaseMarker::containsMarker($template, 'LEAGUE_')) {
			$compMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_CompetitionMarker');
			$template = $compMarker->parseTemplate($template, $result->getCompetition(), $configurations->getFormatter(), $confId.'league.', 'LEAGUE');
		}

		$penalties = array(); // Strafen sammeln
		$subpartArray['###ROWS###'] = $this->createTable(tx_rnbase_util_Templates::getSubpart($template, '###ROWS###'), 
				$result, $penalties, $configurations, $confId);

		
		
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		return $out;
	}

	/**
	 * Erstellt die Ligatabelle.
	 * @param $result tx_cfcleaguefe_table_ITableResult
	 */
	protected function createTable($templateList, $result, &$penalties, &$configurations, $confId) {
		$marks = $result->getMarks();
	
		$tableData = $result->getScores(1); // TODO: Woher kommt die aktuelle Runde?
		// Sollen alle Teams gezeigt werden?
		$tableSize = intval($configurations->get($confId.'table.leagueTableSize'));
		if($tableSize && $tableSize < count($tableData)) {
			// Es sollen weniger Teams gezeigt werden als vorhanden sind
			// Diesen Ausschnitt müssen wir jetzt ermitteln
			$tableData = $this->cropTable($tableData, $tableSize);
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
			$this->setMark($row, $marks);
			// auf Strafen prüfen
			$this->preparePenalties($row, $penalties);

			$team = $row['team'];
			unset($row['team']); // Gibt sonst Probleme mit PHP5.2
			$team->record = t3lib_div::array_merge($row, $team->record);

			$parts[] = $teamMarker->parseTemplate($templateEntry, $team, $configurations->getFormatter(), $confId.'table.', 'ROW');
			$rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
		}

		// Jetzt die einzelnen Teile zusammenfügen
    $markerArray = array();
    $subpartArray['###ROW###'] = implode($parts, $configurations->get($confId.'table.implode'));
		return tx_rnbase_util_Templates::substituteMarkerArrayCached($templateList, $markerArray, $subpartArray);
	}

  /**
   * Wenn nur ein Teil der Tabelle gezeigt werden soll, dann wird dieser Ausschnitt hier
   * ermittelt und zurückgeliefert. 
   * @param &$tableData Daten der Tabelle
   * @param $tableSize Maximale Anzahl Teams, die gezeigt werden soll
   */
  protected function cropTable(&$tableData, $tableSize) {
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
//        $idxEnd = count($tableData);
        $idxStart = $idxEnd - $teams2Show;
      }
    }

    return array_slice($tableData, $idxStart, $tableSize);
  }

  /**
   * Die Strafen müssen gesammelt und gezählt werden.
   */
  protected function preparePenalties(&$row, &$penalties) {
    if(is_array($row['penalties'])) {
      $penalties[] = $row['penalties'];
      $row['penalties'] = count($row['penalties']);
    }
    else
      $row['penalties'] = 0;
  }

  /**
   * Setzt die Tabellenmarkierungen für eine Zeile
   */
  protected function setMark(&$row, &$marks) {
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_football_TableWriter.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_football_TableWriter.php']);
}

?>