<?php

use System25\T3sports\Table\Builder;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2017 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('Tx_Rnbase_Service_Base');

/**
 * Service to output a chart to compare two match opponents.
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_svmarker_ChartMatch extends Tx_Rnbase_Service_Base
{
    public function addChart($params, $parent)
    {
        $marker = $params['marker'];
        $confId = $params['confid'];
        $template = $params['template'];
        if (!tx_rnbase_util_BaseMarker::containsMarker($template, 'MARKERMODULE__CHARTMATCH') &&
            !tx_rnbase_util_BaseMarker::containsMarker($template, $marker.'_CHARTMATCH')) {
            return;
        }
        $formatter = $params['formatter'];
        $chart = $this->getMarkerValue($params, $formatter, $confId.'chart.');
        //		$chart = '<!-- TODO: convert to JS -->';
        $markerArray = $subpartArray = $wrappedSubpartArray = array();
        $markerArray['###MARKERMODULE__CHARTMATCH###'] = $chart; // backward
        $markerArray['###'.$marker.'_CHARTMATCH###'] = $chart;
        $params['template'] = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    /**
     * Generate chart
     * FIXME: Umstellen auf ChartJS.
     *
     * @param array $params
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId matchreport.match.
     */
    protected function getMarkerValue($params, $formatter, $confId)
    {
        if (!isset($params['match'])) {
            return false;
        }
        /* @var $match tx_cfcleaguefe_models_match */
        $match = $params['match'];
        $competition = $match->getCompetition();
        if (!$competition->isTypeLeague()) {
            return '';
        }

        $configurations = $formatter->getConfigurations();

        $table = Builder::buildByCompetitionAndMatches(
            $competition,
            $competition->getMatches(tx_cfcleague_models_Match::MATCH_STATUS_FINISHED),
            $configurations,
            $confId
        );

        /* @var $builder System25\T3sports\Chart\ChartBuilder */
        $builder = tx_rnbase::makeInstance(System25\T3sports\Chart\ChartBuilder::class);
        $json = $builder->buildJson($table, [$match->getHome()->getClubUid(), $match->getGuest()->getClubUid()], $formatter->getConfigurations(), $confId);

        $chartTemplate = tx_rnbase_util_Templates::getSubpartFromFile($configurations->get($confId.'template.file'), $configurations->get($confId.'template.subpart'));

        $markerArray = $subpartArray = $wrappedSubpartArray = [];
        $markerArray['###JSON###'] = $json;
        $chartTemplate = tx_rnbase_util_Templates::substituteMarkerArrayCached($chartTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $chartTemplate;
    }

    /**
     * @return tx_cfcleaguefe_util_MatchTable
     */
    protected function getMatchTable()
    {
        return tx_rnbase::makeInstance('tx_cfcleaguefe_util_MatchTable');
    }
}
