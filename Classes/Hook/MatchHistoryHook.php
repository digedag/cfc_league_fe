<?php

namespace System25\T3sports\Hook;

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2023 Rene Nitzsche (rene@system25.de)
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
 * Service to output historic matches of two match opponents.
 *
 * @author Rene Nitzsche
 */
class MatchHistoryHook
{
    /**
     * Add historic matches.
     *
     * @param $params
     * @param $parent
     */
    public function addMatches($params, $parent)
    {
        $marker = $params['marker'];
        $template = $params['template'];
        if (BaseMarker::containsMarker($template, 'MARKERMODULE__MATCHHISTORY')
            || BaseMarker::containsMarker($template, $marker.'_MATCHHISTORY')) {
            $formatter = $params['formatter'];
            $matches = $this->getMarkerValue($params, $formatter);
            $markerArray['###MARKERMODULE__MATCHHISTORY###'] = $matches; // backward
            $markerArray['###'.$marker.'_MATCHHISTORY###'] = $matches;
            $subpartArray = $wrappedSubpartArray = [];
            $params['template'] = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
        }
    }

    /**
     * Generate matchtable.
     *
     * @param array $params
     * @param FormatUtil $formatter
     */
    private function getMarkerValue($params, FormatUtil $formatter)
    {
        //function parseTemplate($templateCode, $params, $formatter) {
        $match = $this->getMatch($params);
        if (!is_object($match)) {
            return false;
        } // The call is not for us
        $competition = $match->getCompetition();
        $group = $competition->getGroup();

        $home = $match->getHome()->getClub();
        if (!$home) {
            return '<!-- Home has no club defined -->';
        }
        $guest = $match->getGuest()->getClub();
        if (!$guest) {
            return '<!-- Guest has no club defined -->';
        }

        $confId = 'matchreport.historic.';
        $fields = [];
        $options = [];
        SearchBase::setConfigFields($fields, $formatter->getConfigurations(), $confId.'fields.');
        SearchBase::setConfigOptions($options, $formatter->getConfigurations(), $confId.'options.');

        $srv = ServiceRegistry::getMatchService();
        $matchTable = $srv->getMatchTable();

        if ($group && !intval($formatter->configurations->get($confId.'ignoreAgeGroup'))) {
            $matchTable->setAgeGroups($group->getUid());
        }
        $matchTable->setHomeClubs($home->getUid().','.$guest->getUid());
        $matchTable->setGuestClubs($home->getUid().','.$guest->getUid());
        $matchTable->getFields($fields, $options);
        $matches = $srv->search($fields, $options);

        // Wir brauchen das Template
        $subpartName = $formatter->getConfigurations()->get($confId.'subpartName');
        $subpartName = $subpartName ? $subpartName : '###HISTORIC_MATCHES###';
        $templateCode = Templates::getSubpartFromFile(
            $formatter->getConfigurations()->get($confId.'template'),
            $subpartName
        );
        if (!$templateCode) {
            return '<!-- NO SUBPART '.$subpartName.' FOUND -->';
        }

        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $out = $listBuilder->render(
            $matches,
            false,
            $templateCode,
            'tx_cfcleaguefe_util_MatchMarker',
            $confId.'match.',
            'MATCH',
            $formatter
        );

        return $out;
    }

    /**
     * Liefert das Fixture.
     *
     * @param array $params
     *
     * @return Fixture|null
     */
    private function getMatch($params)
    {
        if (!isset($params['match'])) {
            return null;
        }

        return $params['match'];
    }

    public function parseTemplate($templateCode, $params, $formatter)
    {
        $match = $this->getMatch($params);
        if (!is_object($match)) {
            return false;
        } // The call is not for us

        return '<h2>Not implemented. This is a single marker module!</h2>';
    }
}
