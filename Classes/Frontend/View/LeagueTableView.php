<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Utility\Link;
use System25\T3sports\Frontend\Marker\TeamMarker;
use System25\T3sports\Model\Team;
use System25\T3sports\Table\ITableType;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2021 Rene Nitzsche (rene@system25.de)
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
 * Viewklasse für die Anzeige der Ligatabelle mit Hilfe eines HTML-Templates.
 */
class LeagueTableView extends BaseView
{
    /**
     * Erstellen des Frontend-Outputs.
     *
     * @param string $template
     * @param RequestInterface $request
     * @param FormatUtil $formatter
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        $configurations = $request->getConfigurations();
        $table = $viewData->offsetGet('table');
        if (is_object($table)) {
            $viewData->offsetUnset('table');
            // Ausgabe mit neuem Verfahren
            return $this->showLeagueTable($table, $template, $request);
        }

        $this->formatter = $formatter;

        // Liga und Tablemarks holen
        $league = $viewData->offsetGet('league');
        $marks = $league->getTableMarks();

        $markerArray = $configurations->getFormatter()->getItemMarkerArrayWrapped($league->getProperty(), 'leaguetable.league.', 0, 'LEAGUE_');

        // Die Ligatabelle zusammenbauen
        $penalties = []; // Strafen sammeln
        $subpartArray = [
            '###ROWS###' => $this->_createTable(Templates::getSubpart($template, '###ROWS###'), $viewData, $penalties, $marks, $configurations),
        ];

        // Jetzt die Strafen auflisten
        $subpartArray['###PENALTIES###'] = $this->_createPenalties(Templates::getSubpart($template, '###PENALTIES###'), $penalties, $configurations);

        // Die Tabellensteuerung
        $subpartArray['###CONTROLS###'] = $this->_createControls(Templates::getSubpart($template, '###CONTROLS###'), $viewData, $configurations);

        $out .= Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);

        return $out;
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###LEAGUE_TABLE###';
    }

    /**
     * @param ITableType $table
     * @param string $template
     * @param ConfigurationInterface $configurations
     */
    private function showLeagueTable($table, $template, RequestInterface $request)
    {
        $writer = $table->getTableWriter();

        return $writer->writeTable($table, $template, $request->getConfigurations(), $request
            ->getConfId().'');
    }

    /**
     * Erstellt die Liste mit den Ligastrafen.
     */
    protected function _createPenalties($template, &$penalties, $configurations)
    {
        if (!is_array($penalties) || 0 == count($penalties)) {
            return '';
        }

        $subTemplate = Templates::getSubpart($template, '###PENALTY###');
        $parts = [];
        $subpartArray = [];
        foreach ($penalties as $penaltyArr) {
            foreach ($penaltyArr as $penalty) {
                $markerArray = $configurations->getFormatter()->getItemMarkerArrayWrapped($penalty->getProperty(), 'leaguetable.penalty.', 0, 'PENALTY_');
                $parts[] = Templates::substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray);
            }
        }

        if (count($parts)) {
            // Zum Schluß das Haupttemplate zusammenstellen
            $markerArray = [];
            $subpartArray['###PENALTY###'] = implode($parts, $configurations->get('leaguetable.penalty.implode'));
            $out = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray); // , $wrappedSubpartArray);
        } else { // Keine Strafen vorhanden, es wird ein leerer String gesendet
            $out = '';
        }

        return $out;
    }

    /**
     * Erstellt die Ligatabelle.
     */
    protected function _createTable($templateList, $viewData, &$penalties, &$marks, $configurations)
    {
        $tableData = $viewData->offsetGet('tableData');
        // Sollen alle Teams gezeigt werden?
        $tableSize = $configurations->getInt('leagueTableSize');
        if ($tableSize && $tableSize < count($tableData)) {
            // Es sollen weniger Teams gezeigt werden als vorhanden sind
            // Diesen Ausschnitt müssen wir jetzt ermitteln
            $tableData = $this->_cropTable($tableData, $tableSize);
        }
        // Den TeamMarker erstellen
        $teamMarker = tx_rnbase::makeInstance(TeamMarker::class);
        $templateEntry = Templates::getSubpart($templateList, '###ROW###');

        $parts = [];
        // Die einzelnen Zeilen zusammenbauen
        $rowRoll = intval($configurations->get('leaguetable.table.roll.value'));
        $rowRollCnt = 0;
        foreach ($tableData as $row) {
            $row['roll'] = $rowRollCnt;
            // Die Marks für die Zeile setzen
            $this->_setMark($row, $marks);
            // auf Strafen prüfen
            $this->_preparePenalties($row, $penalties);

            /* @var Team $team */
            $team = $row['team'];
            unset($row['team']); // Gibt sonst Probleme mit PHP5.2
            $team->setProperty($row + $team->getProperties());

            $parts[] = $teamMarker->parseTemplate($templateEntry, $team, $configurations->getFormatter(), 'leaguetable.table.', 'ROW');
            $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
        }
        // Jetzt die einzelnen Teile zusammenfügen
        $markerArray = [];
        $subpartArray['###ROW###'] = implode($parts, $configurations->get('leaguetable.table.implode'));

        return Templates::substituteMarkerArrayCached($templateList, $markerArray, $subpartArray);
    }

    /**
     * Wenn nur ein Teil der Tabelle gezeigt werden soll, dann wird dieser Ausschnitt hier
     * ermittelt und zurückgeliefert.
     *
     * @param array &$tableData Daten der Tabelle
     * @param int $tableSize Maximale Anzahl Teams, die gezeigt werden soll
     */
    protected function _cropTable(&$tableData, $tableSize)
    {
        // Es werden nur 5 Teams gezeigt, dabei wird das erste markierte Team in die Mitte gesetzt
        // Suche des Tabellenplatz des markierten Teams
        $cnt = 0;
        $mark = 0;
        foreach ($tableData as $row) {
            if ($row['markClub']) {
                $markIdx = $cnt;
                $mark = 1;

                break;
            }
            ++$cnt;
        }

        if ($mark) {
            $teams2Show = $tableSize;
            $offsetStart = intval($teams2Show / 2);
            $idxStart = ($markIdx - $offsetStart) >= 0 ? $markIdx - $offsetStart : 0;
            $idxEnd = $idxStart + $teams2Show;
            // Am Tabellenende nachregulieren
            if ($idxEnd > count($tableData)) {
                $idxStart = count($tableData) - $teams2Show;
            }
        }

        return array_slice($tableData, $idxStart, $tableSize);
    }

    /**
     * Die Strafen müssen gesammelt und gezählt werden.
     */
    protected function _preparePenalties(&$row, &$penalties)
    {
        if (is_array($row['penalties'])) {
            $penalties[] = $row['penalties'];
            $row['penalties'] = count($row['penalties']);
        } else {
            $row['penalties'] = 0;
        }
    }

    /**
     * Setzt die Tabellenmarkierungen für eine Zeile.
     */
    protected function _setMark(&$row, &$marks)
    {
        if (is_array($marks) && array_key_exists($row['position'], $marks)) {
            // Markierung und Bezeichnung setzen
            $row['mark'] = $marks[$row['position']][0];
            $row['markLabel'] = $marks[$row['position']][1];
        } else {
            $row['mark'] = '';
            $row['markLabel'] = '';
        }
    }

    /**
     * Erstellt das Steuerungspanel für die Tabelle.
     */
    protected function _createControls($template, $viewData, $configurations)
    {
        // Der Link für die Controls
        $link = $configurations->createLink();
        $pid = $GLOBALS['TSFE']->id; // Das Ziel der Seite vorbereiten
        $link->destination($pid); // Das Ziel der Seite vorbereiten

        $markerArray = [];
        $subpartArray = [
            '###CONTROL_TABLETYPE###' => '',
            '###CONTROL_TABLESCOPE###' => '',
            '###CONTROL_POINTSYSTEM###' => '',
        ];
        if ($viewData->offsetGet('tabletype_select')) {
            $subpartArray['###CONTROL_TABLETYPE###'] = $this->_fillControlTemplate(\tx_rnbase_util_Templates::getSubpart($template, '###CONTROL_TABLETYPE###'), $viewData->offsetGet('tabletype_select'), $link, 'TABLETYPE', $configurations);
        }

        if ($viewData->offsetGet('tablescope_select')) {
            $subpartArray['###CONTROL_TABLESCOPE###'] = $this->_fillControlTemplate(\tx_rnbase_util_Templates::getSubpart($template, '###CONTROL_TABLESCOPE###'), $viewData->offsetGet('tablescope_select'), $link, 'TABLESCOPE', $configurations);
        }

        if ($viewData->offsetGet('pointsystem_select')) {
            $subpartArray['###CONTROL_POINTSYSTEM###'] = $this->_fillControlTemplate(\tx_rnbase_util_Templates::getSubpart($template, '###CONTROL_POINTSYSTEM###'), $viewData->offsetGet('pointsystem_select'), $link, 'POINTSYSTEM', $configurations);
        }

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    }

    /**
     * Die Auswahl für Tabellentyp, Tabellenscope und Punktesystem.
     *
     * @param string $template HTML- Template
     * @param array &$itemsArr Datensätze für die Auswahl
     * @param Link $link Linkobjekt
     * @param string $markerName Name des Markers (TYPE, SCOPE oder SYSTEM)
     * @param ConfigurationInterface $configurations Konfig-Objekt
     */
    protected function _fillControlTemplate($template, &$itemsArr, Link $link, $markerName, ConfigurationInterface $configurations)
    {
        $currItem = $itemsArr[1];
        $confName = strtolower($markerName); // Konvention
        $formatter = $configurations->getFormatter();

        // Aus den KeepVars den aktuellen Wert entfernen
        $keepVars = $configurations->getKeepVars()->getArrayCopy();
        unset($keepVars[$confName]);

        if ($link) {
            $token = md5(microtime());
            $link->label($token);
        }

        $currentNoLink = $configurations->getInt('leaguetable.controls.'.$confName.'.current.noLink');
        $markerArray = $subpartArray = $wrappedSubpartArray = [];

        // Jetzt über die vorhandenen Items iterieren
        while (list($key, $value) = each($itemsArr[0])) {
            $keepVars[$confName] = $key;
            $link->parameters($keepVars);
            $isCurrent = ($key == $currItem);

            $markerLabel = $formatter->wrap($key, 'leaguetable.controls.'.$confName.'.'.$key.'.');

            $data = [
                'iscurrent' => $isCurrent ? 1 : 0,
                'value' => $value,
            ];

            $tempArray = $formatter->getItemMarkerArrayWrapped($data, 'leaguetable.controls.'.$confName.'.', 0, 'CONTROL_'.$markerName.'_'.$markerLabel.'_');
            $tempArray['###CONTROL_'.$markerName.'_'.$markerLabel.'###'] = $tempArray['###CONTROL_'.$markerName.'_'.$markerLabel.'_VALUE###'];
            $markerArray = array_merge($markerArray, $tempArray);
            $url = $formatter->wrap($link->makeUrl(false), 'leaguetable.controls.'.$confName.($isCurrent ? '.current.' : '.normal.'));
            $markerArray['###CONTROL_'.$markerName.'_'.$markerLabel.'_LINK_URL###'] = $url;
            $markerArray['###CONTROL_'.$markerName.'_'.$markerLabel.'_LINKURL###'] = $url;

            $linkStr = ($currentNoLink && $key == $currItem) ? $token : $link->makeTag();
            // Einen zusätzliche Wrap um das generierte Element inkl. Link
            $linkStr = $formatter->wrap($linkStr, 'leaguetable.controls.'.$confName.($isCurrent ? '.current.' : '.normal.'));
            $wrappedSubpartArray['###CONTROL_'.$markerName.'_'.$markerLabel.'_LINK###'] = explode($token, $linkStr);
        }

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }
}
