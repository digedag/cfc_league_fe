<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_views_LeagueTable');
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('Tx_Rnbase_Utility_T3General');

/**
 * Viewklasse für die Anzeige der Ligatabelle mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_LeagueTableAllTime extends tx_cfcleaguefe_views_LeagueTable {

	function createOutput($template, &$viewData, &$configurations, &$formatter){

		$cObj = $formatter->cObj;
		$markerArray = array();
		$marks = array();
		$penalties = array();

		// Die Ligatabelle zusammenbauen
		$subpartArray['###ROWS###'] = $this->_createTable(tx_rnbase_util_Templates::getSubpart($template, '###ROWS###'),
							$viewData, $penalties, $marks, $configurations);

    // Jetzt die Strafen auflisten
//    $subpartArray['###PENALTIES###'] = $this->_createPenalties($cObj->getSubpart($template, '###PENALTIES###'),
//                             $penalties, $configurations);

		// Die Tabellensteuerung
		$subpartArray['###CONTROLS###'] = $this->_createControls(tx_rnbase_util_Templates::getSubpart($template, '###CONTROLS###'),
							$viewData, $configurations);

		$out .= tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		return $out;
	}

	/**
	 * Erstellt die Ligatabelle.
	 */
	protected function _createTable($templateList, &$viewData, &$penalties, &$marks, &$configurations) {
		$tableData = $viewData->offsetGet('tableData');
		// Sollen alle Teams gezeigt werden?
		$tableSize = intval($configurations->get('leagueTableSize'));
		if($tableSize && $tableSize < count($tableData)) {
			// Es sollen weniger Teams gezeigt werden als vorhanden sind
			// Diesen Ausschnitt müssen wir jetzt ermitteln
			$tableData = $this->_cropTable($tableData, $tableSize);
		}
		// Den ClubMarker erstellen
		$clubMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
		$templateEntry = $configurations->getCObj()->getSubpart($templateList, '###ROW###');

		$parts = array();
		$rowRoll = intval($configurations->get('leaguetableAllTime.table.roll.value'));
		$rowRollCnt = 0;
		foreach($tableData As $row){
			$row['roll'] = $rowRollCnt;
			// Die Marks für die Zeile setzen
			$this->_setMark($row, $marks);
			$team = $row['team'];
			unset($row['team']); // Gibt sonst Probleme mit PHP5.2
			$team->setProperty(Tx_Rnbase_Utility_T3General::array_merge($row, $team->getProperty()));
			$parts[] = $clubMarker->parseTemplate($templateEntry, $team, $configurations->getFormatter(), 'leaguetableAllTime.table.', 'ROW');
			$rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
		}
		// Jetzt die einzelnen Teile zusammenfügen
		$markerArray = array();
		$subpartArray['###ROW###'] = implode($parts, $configurations->get('leaguetableAllTime.table.implode'));
		return $configurations->getCObj()->substituteMarkerArrayCached($templateList, $markerArray, $subpartArray);
	}

	/**
	 * Wenn nur ein Teil der Tabelle gezeigt werden soll, dann wird dieser Ausschnitt hier
	 * ermittelt und zurückgeliefert.
	 * @param &$tableData Daten der Tabelle
	 * @param $tableSize Maximale Anzahl Teams, die gezeigt werden soll
	 */
	protected function _cropTable(&$tableData, $tableSize) {
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
	 * Setzt die Tabellenmarkierungen für eine Zeile
	 */
	protected function _setMark(&$row, &$marks) {
		if(is_array($marks) && array_key_exists($row['position'], $marks)){
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
	 * Erstellt das Steuerungspanel für die Tabelle.
	 */
	protected function _createControls($template, &$viewData, &$configurations) {
		$link = $configurations->createLink();
		$pid = $GLOBALS['TSFE']->id; // Das Ziel der Seite vorbereiten
		$link->destination($pid); // Das Ziel der Seite vorbereiten

		$subpartArray = array('###CONTROL_TABLETYPE###' => '', '###CONTROL_TABLESCOPE###' => '', '###CONTROL_POINTSYSTEM###' =>'', );
		if($viewData->offsetGet('tabletype_select')) {
			$subpartArray['###CONTROL_TABLETYPE###'] = $this->_fillControlTemplate(tx_rnbase_util_Templates::getSubpart($template, '###CONTROL_TABLETYPE###'),
				$viewData->offsetGet('tabletype_select'), $link, 'TABLETYPE', $configurations);
		}

		if($viewData->offsetGet('tablescope_select')) {
			$subpartArray['###CONTROL_TABLESCOPE###'] = $this->_fillControlTemplate(tx_rnbase_util_Templates::getSubpart($template, '###CONTROL_TABLESCOPE###'),
				$viewData->offsetGet('tablescope_select'), $link, 'TABLESCOPE', $configurations);
		}

		if($viewData->offsetGet('pointsystem_select')) {
			$subpartArray['###CONTROL_POINTSYSTEM###'] = $this->_fillControlTemplate(tx_rnbase_util_Templates::getSubpart($template, '###CONTROL_POINTSYSTEM###'),
				$viewData->offsetGet('pointsystem_select'), $link, 'POINTSYSTEM', $configurations);
		}

		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		return $out;
	}

	/**
	 * Die Auswahl für Tabellentyp, Tabellenscope und Punktesystem.
	 * @param string $template HTML- Template
	 * @param array &$itemsArr Datensätze für die Auswahl
	 * @param tx_rnbase_util_Link &$link Linkobjekt
	 * @param string $markerName Name des Markers (TYPE, SCOPE oder SYSTEM)
	 * @param tx_rnbase_configurations $configurations Konfig-Objekt
	 */
	protected function _fillControlTemplate($template, &$itemsArr, $link, $markerName, $configurations) {
		$items = $itemsArr[0];
		$currItem = $itemsArr[1];
		$confName = strtolower($markerName); // Konvention
		$noLink = array('', '');

		// Aus den KeepVars den aktuellen Wert entfernen
		$keepVars = $configurations->getKeepVars()->getArrayCopy();
		unset($keepVars[$confName]);

		if($link) {
			$token = md5(microtime());
			$link->label($token);
		}

		$currentNoLink = intval($configurations->get('leaguetable.controls.'. $confName .'.current.noLink'));

		$markerArray = array();

		// Jetzt über die vorhandenen Items iterieren
		while( list($key, $value) = each($itemsArr[0])) {
			$keepVars[$confName] = $key;
			$link->parameters($keepVars);

			$markerLabel = $configurations->getFormatter()->wrap($key, 'leaguetable.controls.'. $confName .'.'.$key.'.');

			$markerArray['###CONTROL_'. $markerName .'_'. $markerLabel .'###'] = $configurations->getFormatter()->wrap($value, 'leaguetable.controls.'. $confName .'.value.');
			$markerArray['###CONTROL_'. $markerName .'_'. $markerLabel .'_LINK_URL###'] = $configurations->getFormatter()->wrap($link->makeUrl(false), 'leaguetable.controls.'. $confName . (($key == $currItem) ? '.current.' : '.normal.') );

			$linkStr = ($currentNoLink && $key == $currItem) ? $token : $link->makeTag();
			// Ein zusätzliche Wrap um das generierte Element inkl. Link
			$linkStr = $configurations->getFormatter()->wrap($linkStr, 'leaguetable.controls.'. $confName . (($key == $currItem) ? '.current.' : '.normal.') );
			$wrappedSubpartArray['###CONTROL_'.$markerName.'_'. $markerLabel .'_LINK###'] = explode($token, $linkStr);
		}
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $out;
	}
}

