<?php

use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Frontend\Marker\MatchMarkerBuilderInfo;

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
class tx_cfcleaguefe_views_MatchCrossTable extends tx_rnbase_view_Base
{
    public function getMainSubpart(&$viewData)
    {
        return '###CROSSTABLE###';
    }

    /**
     * Erstellen des Frontend-Outputs.
     *
     * @param string $template
     * @param array $viewData
     * @param tx_rnbase_configurations $configurations
     * @param tx_rnbase_util_FormatUtil $formatter
     */
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        $matches = $viewData->offsetGet('matches');
        if (!is_array($matches) || !count($matches)) {
            return $configurations->getLL('matchcrosstable.noData');
        }
        // Wir benötigen die beteiligten Teams
        $teams = $viewData->offsetGet('teams');
        $this->removeDummyTeams($teams);
        // Mit den Teams können wir die Headline bauen
        $headlineTemplate = Templates::getSubpart($template, '###HEADLINE###');
        Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'createHeadline');
        $subpartArray['###HEADLINE###'] = $this->_createHeadline($headlineTemplate, $teams, $configurations);
        Misc::pullTT();

        Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'generateTableData');
        $teamsArray = $this->generateTableData($matches, $teams);
        Misc::pullTT();

        $datalineTemplate = Templates::getSubpart($template, '###DATALINE###');
        Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'createDatalines');
        $subpartArray['###DATALINE###'] = $this->_createDatalines($datalineTemplate, $teamsArray, $teams, $configurations, $viewData);
        Misc::pullTT();
        $markerArray = [
            '###MATCHCOUNT###' => count($matches),
        ];

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    }

    /**
     * Erstellt ein Array dessen Key die UIDs der Teams sind.
     * Value ist ein Array
     * mit den Spielen des Teams.
     *
     * @param array $matches
     * @param array $teams
     */
    private function generateTableData(&$matches, &$teams)
    {
        $ret = [];

        reset($matches);
        reset($teams);
        $teamIds = array_keys($teams);
        $teamCnt = count($teamIds);
        $initArray = array_flip($teamIds);
        foreach ($teams as $uid => $team) {
            $ret[$uid] = $initArray;
            $ret[$uid][$uid] = ''; // Das Spiel gegen sich selbst
            // In das Array alle Heimspiele des Teams legen
            for ($i = 0; $i < $teamCnt; ++$i) {
                if ($uid == $teamIds[$i]) {
                    $ret[$uid][$uid] = $this->ownMatchStr;
                } // Das Spiel gegen sich selbst
                else {
                    $ret[$uid][$teamIds[$i]] = $this->findMatch($matches, $uid, $teamIds[$i]);
                }
            }
        }

        return $ret;
    }

    /**
     * Sucht aus dem Spielarray die Paarung mit der Heim- und Gastmannschaft.
     *
     * @param array $matches
     * @param int $home
     *            uid der Heimmannschaft
     * @param int $guest
     *            uid der Gastmannschaft
     *
     * @return tx_cfcleaguefe_models_match
     */
    private function findMatch(&$matches, $home, $guest)
    {
        $ret = [];
        for ($i = 0, $cnt = count($matches); $i < $cnt; ++$i) {
            if ($matches[$i]->getProperty('home') == $home && $matches[$i]->getProperty('guest') == $guest) {
                $ret[] = $matches[$i];
            }
        }
        // Die Paarung gibt es nicht.
        return count($ret) ? $ret : $this->noMatchStr;
    }

    /**
     * Erstellt die Datenzeilen der Tabelle.
     *
     * @param string $headlineTemplate
     * @param array $datalines
     * @param array $teams
     * @param tx_rnbase_configurations $configurations
     * @param ArrayObject $viewData
     */
    private function _createDatalines($template, $datalines, &$teams, $configurations, $viewData)
    {
        $subTemplate = '###MATCHS###'.Templates::getSubpart($template, '###MATCHS###').'###MATCHS###';
        $freeTemplate = Templates::getSubpart($template, '###MATCH_FREE###');
        $rowRoll = $configurations->getInt('matchcrosstable.dataline.match.roll.value');

        $teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class, tx_rnbase::makeInstance(MatchMarkerBuilderInfo::class));

        $lines = [];
        // Über alle Zeilen iterieren
        foreach ($datalines as $uid => $matches) {
            $rowRollCnt = 0;
            $parts = [];
            foreach ($matches as $matchArr) {
                if (is_array($matchArr)) {
                    // Da sind Spiele im Array. Anzeigen mit ListMarker
                    $parts[] = $listBuilder->render($matchArr, $viewData, $subTemplate, 'tx_cfcleaguefe_util_MatchMarker', 'matchcrosstable.dataline.match.', 'MATCH', $this->formatter);
                } else {
                    $parts[] = $matchArr;
                } // Sollte ein String sein...

                $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
            }
            // Jetzt die einzelnen Teile zusammenfügen
            $subpartArray['###MATCHS###'] = implode($parts, $configurations->get('matchcrosstable.dataline.implode'));
            // Und das Team ins MarkerArray
            $lineTemplate = $teamMarker->parseTemplate($template, $teams[$uid], $this->formatter, 'matchcrosstable.dataline.team.', 'DATALINE_TEAM');
            $lines[] = Templates::substituteMarkerArrayCached($lineTemplate, $markerArray, $subpartArray);
        }

        return implode($lines, $configurations->get('matchcrosstable.dataline.implode'));
    }

    /**
     * Creates the table head.
     *
     * @param string $headlineTemplate
     * @param array $teams
     * @param tx_rnbase_configurations $configurations
     */
    private function _createHeadline($template, &$teams, $configurations)
    {
        // Im Prinzip eine normale Teamliste...
        $teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
        $subTemplate = Templates::getSubpart($template, '###TEAM###');
        $rowRoll = $configurations->getInt('matchcrosstable.headline.team.roll.value');
        $rowRollCnt = 0;
        $parts = [];

        tx_rnbase_util_Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'include teams');
        foreach ($teams as $team) {
            $team->setProperty('roll', $rowRollCnt);
            $parts[] = $teamMarker->parseTemplate($subTemplate, $team, $this->formatter, 'matchcrosstable.headline.team.', 'TEAM');
            $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
        }
        Misc::pullTT();

        $subpartArray['###TEAM###'] = implode($parts, $configurations->get('matchcrosstable.headline.team.implode'));
        $markerArray = [
            '###TEAMCOUNT###' => count($teams),
        ];

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    }

    private function removeDummyTeams(&$teams)
    {
        // Das Team 'Spielfrei' vorher entfernen
        $dummyTeams = [];
        reset($teams);
        foreach ($teams as $uid => $team) {
            if ($team->isDummy()) {
                $dummyTeams[] = $uid;
            }
        }
        foreach ($dummyTeams as $uid) {
            unset($teams[$uid]);
        }
        reset($teams);
    }

    /**
     * Vorbereitung der Link-Objekte.
     */
    public function _init(&$configurations)
    {
        $this->formatter = &$configurations->getFormatter();

        // String für Zellen ohne Spielansetzung
        $this->noMatchStr = $configurations->get('matchcrosstable.dataline.nomatch');
        $this->ownMatchStr = $configurations->get('matchcrosstable.dataline.ownmatch');
    }
}
