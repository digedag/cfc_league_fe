<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Frontend\Marker\MatchMarkerBuilderInfo;
use System25\T3sports\Frontend\Marker\TeamMarker;
use System25\T3sports\Model\Fixture;
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
class MatchCrossTableView extends BaseView
{
    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###CROSSTABLE###';
    }

    /**
     * @param string $template
     * @param RequestInterface $request
     * @param FormatUtil $formatter
     *
     * @return string
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        $configurations = $request->getConfigurations();
        /* @var $matches \Sys25\RnBase\Domain\Collection\BaseCollection */
        $matches = $viewData->offsetGet('matches');
        if ($matches->isEmpty()) {
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
     * @return Fixture|string
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
     * @param ConfigurationInterface $configurations
     * @param ContextInterface $viewData
     */
    private function _createDatalines($template, $datalines, &$teams, ConfigurationInterface $configurations, ContextInterface $viewData)
    {
        $subTemplate = '###MATCHS###'.Templates::getSubpart($template, '###MATCHS###').'###MATCHS###';
        $freeTemplate = Templates::getSubpart($template, '###MATCH_FREE###');
        $rowRoll = $configurations->getInt('matchcrosstable.dataline.match.roll.value');

        $teamMarker = tx_rnbase::makeInstance(TeamMarker::class);
        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class, tx_rnbase::makeInstance(MatchMarkerBuilderInfo::class));

        $lines = [];
        $markerArray = [];
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
            $subpartArray['###MATCHS###'] = implode($configurations->get('matchcrosstable.dataline.implode'), $parts);
            // Und das Team ins MarkerArray
            $lineTemplate = $teamMarker->parseTemplate($template, $teams[$uid], $this->formatter, 'matchcrosstable.dataline.team.', 'DATALINE_TEAM');
            $lines[] = Templates::substituteMarkerArrayCached($lineTemplate, $markerArray, $subpartArray);
        }

        return implode($configurations->get('matchcrosstable.dataline.implode'), $lines);
    }

    /**
     * Creates the table head.
     *
     * @param string $headlineTemplate
     * @param array $teams
     * @param ConfigurationInterface $configurations
     */
    private function _createHeadline($template, &$teams, ConfigurationInterface $configurations)
    {
        // Im Prinzip eine normale Teamliste...
        $teamMarker = tx_rnbase::makeInstance(TeamMarker::class);
        $subTemplate = Templates::getSubpart($template, '###TEAM###');
        $rowRoll = $configurations->getInt('matchcrosstable.headline.team.roll.value');
        $rowRollCnt = 0;
        $parts = [];

        Misc::pushTT('tx_cfcleaguefe_views_MatchCrossTable', 'include teams');
        foreach ($teams as $team) {
            $team->setProperty('roll', $rowRollCnt);
            $parts[] = $teamMarker->parseTemplate($subTemplate, $team, $this->formatter, 'matchcrosstable.headline.team.', 'TEAM');
            $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
        }
        Misc::pullTT();

        $subpartArray['###TEAM###'] = implode($configurations->get('matchcrosstable.headline.team.implode'), $parts);
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
    public function _init(ConfigurationInterface $configurations)
    {
        $this->formatter = $configurations->getFormatter();

        // String für Zellen ohne Spielansetzung
        $this->noMatchStr = $configurations->get('matchcrosstable.dataline.nomatch');
        $this->ownMatchStr = $configurations->get('matchcrosstable.dataline.ownmatch');
    }
}
