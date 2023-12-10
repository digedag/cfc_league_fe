<?php

namespace System25\T3sports\Hook;

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\Templates;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Table\Builder;
use System25\T3sports\Utility\MatchTableBuilder;
use tx_rnbase;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2021 Rene Nitzsche (rene@system25.de)
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
 * Service to output a chart to compare two match opponents.
 *
 * @author Rene Nitzsche
 */
class MatchChartHook
{
    public function addChart($params, $parent)
    {
        $marker = $params['marker'];
        $confId = $params['confid'];
        $template = $params['template'];
        if (!BaseMarker::containsMarker($template, 'MARKERMODULE__CHARTMATCH')
            && !BaseMarker::containsMarker($template, $marker.'_CHARTMATCH')) {
            return;
        }

        $formatter = $params['formatter'];
        $chart = $this->getMarkerValue($params, $formatter, $confId.'chart.');
        //		$chart = '<!-- TODO: convert to JS -->';
        $markerArray = $subpartArray = $wrappedSubpartArray = [];
        $markerArray['###MARKERMODULE__CHARTMATCH###'] = $chart; // backward
        $markerArray['###'.$marker.'_CHARTMATCH###'] = $chart;
        $params['template'] = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    /**
     * Generate chart
     * FIXME: Umstellen auf ChartJS.
     *
     * @param array $params
     * @param FormatUtil $formatter
     * @param string $confId matchreport.match.
     */
    protected function getMarkerValue($params, FormatUtil $formatter, $confId)
    {
        if (!isset($params['match'])) {
            return false;
        }
        /* @var $match Fixture */
        $match = $params['match'];
        $competition = $match->getCompetition();
        if (!$competition->isTypeLeague()) {
            return '';
        }

        $configurations = $formatter->getConfigurations();

        $table = Builder::buildByCompetitionAndMatches(
            $competition,
            $competition->getMatches(Fixture::MATCH_STATUS_FINISHED),
            $configurations,
            $confId
        );

        /* @var $builder \System25\T3sports\Chart\ChartBuilder */
        $builder = tx_rnbase::makeInstance(\System25\T3sports\Chart\ChartBuilder::class);
        $json = $builder->buildJson($table, [$match->getHome()->getClubUid(), $match->getGuest()->getClubUid()], $formatter->getConfigurations(), $confId);

        $chartTemplate = Templates::getSubpartFromFile($configurations->get($confId.'template.file'), $configurations->get($confId.'template.subpart'));

        $markerArray = $subpartArray = $wrappedSubpartArray = [];
        $markerArray['###JSON###'] = $json;
        $chartTemplate = Templates::substituteMarkerArrayCached($chartTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $chartTemplate;
    }

    /**
     * @return MatchTableBuilder
     */
    protected function getMatchTable()
    {
        return tx_rnbase::makeInstance(MatchTableBuilder::class);
    }
}
