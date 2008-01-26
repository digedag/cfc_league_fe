<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');

tx_div::load('tx_rnbase_view_Base');


/**
 * Viewklasse für die Anzeige der Ligatabelle mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_LeagueTable extends tx_rnbase_view_Base {

  /**
   * Erstellen des Frontend-Outputs
   */
  function render($view, &$configurations){
    $this->_init($configurations);
    $cObj =& $configurations->getCObj(0);
    $templateCode = $cObj->fileResource($this->getTemplate($view,'.html'));
    // Den entscheidenden Teil herausschneiden
    $templateCode = $cObj->getSubpart($templateCode, '###LEAGUE_TABLE###');

    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();

    $out = $this->_createView($templateCode, $viewData, $configurations);
    return $out;
  }

  /**
   * Erstellung des Outputstrings
   */
  function _createView($template, &$viewData, &$configurations) {
    $cObj =& $this->formatter->cObj;

    // Liga und Tablemarks holen
    $league = $viewData->offsetGet('league');
    $marks = $league->getTableMarks();

    $markerArray = $this->formatter->getItemMarkerArrayWrapped($league->record, 'leaguetable.league.', 0, 'LEAGUE_');

    // Die Ligatabelle zusammenbauen
    $penalties = array(); // Strafen sammeln
    $subpartArray['###ROW###'] = $this->_createTable($cObj->getSubpart($template, '###ROW###'), 
                             $viewData->offsetGet('tableData'), $penalties, $marks, $configurations);

    // Jetzt die Strafen auflisten
    $subpartArray['###PENALTIES###'] = $this->_createPenalties($cObj->getSubpart($template, '###PENALTIES###'), 
                             $penalties, $configurations);

    // Die Tabellensteuerung
    $subpartArray['###CONTROLS###'] = $this->_createControls($cObj->getSubpart($template, '###CONTROLS###'), 
                             $viewData, $configurations);
    
    $out .= $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);

    return $out;
  }

  /**
   * Erstellt die Liste mit den Ligastrafen
   */
  function _createPenalties($template, &$penalties, &$configurations) {
    if(!is_array($penalties) || count($penalties) == 0)
      return '';

    $subTemplate = $this->formatter->cObj->getSubpart($template, '###PENALTY###');
    $parts = array();
    foreach($penalties As $penaltyArr) {
      foreach($penaltyArr As $penalty) {
        $markerArray = $this->formatter->getItemMarkerArrayWrapped($penalty->record, 'leaguetable.penalty.', 0, 'PENALTY_');
//t3lib_div::debug($markerArray , 'vw_leaguetable');
        $parts[] = $this->formatter->cObj->substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray);
      }
    }
//    return implode($parts, $configurations->get('leaguetable.penalty.implode'));

    if(count($parts)) {
      // Zum Schluß das Haupttemplate zusammenstellen
      $markerArray = array();
      $subpartArray['###PENALTY###'] = implode($parts, $configurations->get('leaguetable.penalty.implode'));
      $out = $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);
    }
    else { // Keine Strafen vorhanden, es wird ein leerer String gesendet
      $out = '';
    }
    return $out;

  }

  /**
   * Erstellt die Ligatabelle.
   */
  function _createTable($template, &$tableData, &$penalties, &$marks, &$configurations) {
    // Sollen alle Teams gezeigt werden?
    $tableSize = intval($configurations->get('leagueTableSize'));
    if($tableSize && $tableSize < count($tableData)) {
      // Es sollen weniger Teams gezeigt werden als vorhanden sind
      // Diesen Ausschnitt müssen wir jetzt ermitteln
      $tableData = $this->_cropTable($tableData, $tableSize);
    }

    $rowRoll = intval($configurations->get('leaguetable.table.roll.value'));
//t3lib_div::debug($rowRoll , 'roll vw_leaguetable');
    $parts = array();
    // Die einzelnen Zeilen zusammenbauen
    $rowRollCnt = 0;
    $position = 1;
    foreach($tableData As $row){
      $row['roll'] = $rowRollCnt;
      // Die Marks für die Zeile setzen
      $this->_setMark($row, $marks);
      // auf Strafen prüfen
      $this->_preparePenalties($row, $penalties);

      $team = $row['team'];
      unset($row['team']); // Gibt sonst Probleme mit PHP5.2
      $team->record = t3lib_div::array_merge($row, $team->record);

      $parts[] = $this->teamMarker->parseTemplate($template, $team, $this->formatter, 'leaguetable.table.', $this->links, 'ROW');

      $position++;
      $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
    }
    // Jetzt die einzelnen Teile zusammenfügen
    return implode($parts, $configurations->get('leaguetable.table.implode'));
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
//        $idxEnd = count($tableData);
        $idxStart = $idxEnd - $teams2Show;
      }
    }

    return array_slice($tableData, $idxStart, $tableSize);
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
   * Erstellt das Steuerungspanel für die Tabelle.
   */
  function _createControls($template, &$viewData, &$configurations) {
    $subpartArray = array('###CONTROL_TABLETYPE###' => '', '###CONTROL_TABLESCOPE###' => '', '###CONTROL_POINTSYSTEM###' =>'',);
    if($viewData->offsetGet('tabletype_select')) {
      $subpartArray['###CONTROL_TABLETYPE###'] = $this->_fillControlTemplate($this->formatter->cObj->getSubpart($template, '###CONTROL_TABLETYPE###'), 
                    $viewData->offsetGet('tabletype_select'), $this->link, 'TABLETYPE', $configurations);
    }

    if($viewData->offsetGet('tablescope_select')) {
      $subpartArray['###CONTROL_TABLESCOPE###'] = $this->_fillControlTemplate($this->formatter->cObj->getSubpart($template, '###CONTROL_TABLESCOPE###'), 
                    $viewData->offsetGet('tablescope_select'), $this->link, 'TABLESCOPE', $configurations);
    }

    if($viewData->offsetGet('pointsystem_select')) {
      $subpartArray['###CONTROL_POINTSYSTEM###'] = $this->_fillControlTemplate($this->formatter->cObj->getSubpart($template, '###CONTROL_POINTSYSTEM###'), 
                    $viewData->offsetGet('pointsystem_select'), $this->link, 'POINTSYSTEM', $configurations);
    }

    $out = $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    return $out;
//    return implode($parts, $configurations->get('leaguetable.controls.implode'));
  }

  /**
   * Die Auswahl für Tabellentyp, Tabellenscope und Punktesystem.
   * @param string $template HTML- Template
   * @param array &$itemsArr Datensätze für die Auswahl
   * @param tx_lib_link &$link Linkobjekt
   * @param string $markerName Name des Markers (TYPE, SCOPE oder SYSTEM)
   * @param tx_rnbase_configurations &$configurations Konfig-Objekt
   */
  function _fillControlTemplate($template, &$itemsArr, &$link, $markerName, &$configurations) {
    $items = $itemsArr[0];
    $currItem = $itemsArr[1];
    $confName = strtolower($markerName); // Konvention
    $noLink = array('','');

    // Aus den KeepVars den aktuellen Wert entfernen
    $keepVars = $configurations->getKeepVars()->getArrayCopy();
    unset($keepVars[$confName]);
//t3lib_div::debug($itemsArr, 'vw_leaguetable');

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

      $markerLabel = $this->formatter->wrap($key, 'leaguetable.controls.'. $confName .'.'.$key.'.');

      $markerArray['###CONTROL_'. $markerName .'_'. $markerLabel .'###'] = $this->formatter->wrap($value, 'leaguetable.controls.'. $confName .'.value.');
      $markerArray['###CONTROL_'. $markerName .'_'. $markerLabel .'_LINK_URL###'] = $this->formatter->wrap($link->makeUrl(false), 'leaguetable.controls.'. $confName . (($key == $currItem) ? '.current.' : '.normal.') );

      $linkStr = ($currentNoLink && $key == $currItem) ? $token : $link->makeTag();
      // Ein zusätzliche Wrap um das generierte Element inkl. Link
      $linkStr = $this->formatter->wrap($linkStr, 'leaguetable.controls.'. $confName . (($key == $currItem) ? '.current.' : '.normal.') );
      $wrappedSubpartArray['###CONTROL_'.$markerName.'_'. $markerLabel .'_LINK###'] = explode($token, $linkStr);
    }
    $out = $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    return $out;
  }

  /**
   * Vorbereitung der Link-Objekte
   */
  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    $this->links = array();

    // Der Link für die Controls
    $pid = $GLOBALS['TSFE']->id; // Das Ziel der Seite vorbereiten
    $this->link = new $linkClass;
    $this->link->designatorString = $configurations->getQualifier();
    $this->link->destination($pid); // Das Ziel der Seite vorbereiten

    $teamPage = $configurations->get('leaguetable.teamPage');
    if($teamPage) {
      $linkTeam = new $linkClass;
      $linkTeam->designatorString = $configurations->getQualifier();
      $linkTeam->destination($teamPage); // Das Ziel der Seite vorbereiten

      $this->links['team'] = $linkTeam;
    }

    // Den TeamMarker erstellen
    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $this->teamMarker = new $teamMarkerClass;

  }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_LeagueTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_LeagueTable.php']);
}
?>