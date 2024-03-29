<?php

namespace System25\T3sports\Hook;

use Exception;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Search\SearchBase;
use Sys25\RnBase\Utility\Files;
use Sys25\RnBase\Utility\Logger;
use System25\T3sports\Frontend\Marker\MatchMarker;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Table\Builder;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2020 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

/**
 * Integrate a league table in matchreport.
 *
 * @author Rene Nitzsche
 */
class TableMatchMarker
{
    /**
     * Add match table with current round in match report.
     *
     * @param array $params
     * @param MatchMarker $parent
     */
    public function addCurrentRound($params, $parent)
    {
        $template = $params['template'];
        $marker = $params['marker'];
        if (!BaseMarker::containsMarker($template, $marker.'_MTCURRENTROUND')) {
            return;
        }

        $formatter = $params['formatter'];
        $matches = $this->getCurrentRound($params, $formatter);
        $markerArray = $subpartArray = $wrappedSubpartArray = [];
        $markerArray['###'.$marker.'_MTCURRENTROUND###'] = $matches;
        $params['template'] = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    /**
     * Generate current round.
     *
     * @param array $params
     * @param FormatUtil $formatter
     */
    private function getCurrentRound($params, FormatUtil $formatter)
    {
        $match = $this->getMatch($params);
        if (null == $match) {
            return '';
        } // The call is not for us

        $confId = 'matchreport.mtcurrentround.';
        $fields = [];
        $options = [];
        SearchBase::setConfigFields($fields, $formatter->getConfigurations(), $confId.'fields.');
        SearchBase::setConfigOptions($options, $formatter->getConfigurations(), $confId.'options.');

        $srv = ServiceRegistry::getMatchService();
        $matchTable = $srv->getMatchTable();
        $matchTable->setCompetitions($match->getCompetition()->uid);
        $matchTable->setRounds($match->getRound());
        $matchTable->getFields($fields, $options);
        $matches = $srv->search($fields, $options);

        $subpartName = $formatter->getConfigurations()->get('subpartName');
        $subpartName = $subpartName ? $subpartName : '###CURRENTROUND_MATCHES###';
        $template = '';

        try {
            $template = Templates::getSubpartFromFile(
                $formatter->getConfigurations()->get($confId.'template'),
                $subpartName
            );
        } catch (Exception $e) {
            Logger::info('Error for matchtable current round: '.$e->getMessage(), 'cfc_league_fe');
        }
        if (!$template) {
            return '';
        }

        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $out = $listBuilder->render(
            $matches,
            false,
            $template,
            MatchMarker::class,
            $confId.'match.',
            'MATCH',
            $formatter
        );

        return $out;
    }

    /**
     * Add league table in match report.
     *
     * @param array $params
     * @param MatchMarker $parent
     */
    public function addLeagueTable($params, $parent)
    {
        $template = $params['template'];
        $marker = $params['marker'];
        if (!BaseMarker::containsMarker($template, $marker.'_LEAGUETABLE')) {
            return;
        }

        $markerArray = $subpartArray = $wrappedSubpartArray = [];
        $match = $this->getMatch($params);
        if (null == $match) {
            return;
        } // The call is not for us

        $table = '';
        $competition = $match->getCompetition();
        if ($competition->isTypeLeague()) {
            $formatter = $params['formatter'];
            $configurations = $formatter->getConfigurations();
            $confId = $params['confid'].'leaguetable.';
            $table = '<!-- Template not found -->';
            $tableTemplate = Files::getFileResource(
                $configurations->get($confId.'template'),
                ['subpart' => $configurations->get($confId.'subpartName')]
            );
            if ($tableTemplate) {
                $tableInstance = Builder::buildByMatch($match, $configurations, $confId);
                $writer = $tableInstance->getTableWriter();
                $table = $writer->writeTable($tableInstance, $tableTemplate, $configurations, $confId);
            }
        }

        $markerArray['###'.$marker.'_LEAGUETABLE###'] = $table;
        $params['template'] = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    /**
     * Liefert das Fixture.
     *
     * @param array $params
     *
     * @return Fixture
     */
    private function getMatch($params): ?Fixture
    {
        if (!isset($params['match'])) {
            return null;
        }

        return $params['match'];
    }
}
