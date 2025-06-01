<?php

namespace System25\T3sports\Filter;

use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Utility\Misc;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\MatchRepository;
use System25\T3sports\Utility\MatchTableBuilder;
use System25\T3sports\Utility\ScopeController;
use tx_rnbase;

/***************************************************************
*  Copyright notice
*
*  (c) 2010-2025 Rene Nitzsche (rene@system25.de)
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

class MatchFilter extends BaseFilter
{
    private $data;

    private static $profileData = ['profile', 'player', 'coach', 'referee'];
    private $matchRepo;

    public function __construct(?MatchRepository $matchRepo = null)
    {
        $this->matchRepo = $matchRepo ?: tx_rnbase::makeInstance(MatchRepository::class);
    }

    /**
     * Abgeleitete Filter können diese Methode überschreiben und zusätzliche Filter setzen.
     *
     * @param array $fields
     * @param array $options
     */
    protected function initFilter(&$fields, &$options, RequestInterface $request)
    {
        $parameters = $request->getParameters();
        $configurations = $request->getConfigurations();
        $confId = $request->getConfId();
        $options['distinct'] = 1;
        $scopeArr = ScopeController::handleCurrentScope($parameters, $configurations);
        // Spielplan für ein Team
        $teamId = $configurations->get($confId.'teamId');
        if ($configurations->get($confId.'acceptTeamIdFromRequest')) {
            $teamId = $parameters->get('teamId');
        }

        if (!is_array($options['orderby'] ?? null) && $orderby = $options['orderby']) {
            list($key, $value) = Strings::trimExplode('=', $orderby);
            $options['orderby'] = [$key => $value];
        }
        $matchtable = new MatchTableBuilder();
        $matchtable->setScope($scopeArr);
        $matchtable->setTeams($teamId);
        $clubId = $configurations->get($confId.'fixedOpponentClub');
        if ($clubId) {
            // Show matches against a defined club
            $scopeClub = $matchtable->getClubs();
            $matchtable->setClubs('');
            if ($scopeClub) {
                $clubId .= ','.$scopeClub;
            }
            $matchtable->setHomeClubs($clubId);
            $matchtable->setGuestClubs($clubId);
        }
        if ($matchId = $parameters->get('historic')) {
            /** @var Fixture $fixture */
            $fixture = $this->matchRepo->findByUid($matchId);
            $clubHomeId = $fixture->getHome()->getClubUid();
            $clubGuestId = $fixture->getGuest()->getClubUid();
            $comp = $fixture->getCompetition();
            if ($comp && $comp->getGroup()) {
                $matchtable->setAgeGroups($comp->getGroup()->getUid());
            }

            if ($clubHomeId && $clubGuestId) {
                $matchtable->setHomeClubs($clubHomeId.','.$clubGuestId);
                $matchtable->setGuestClubs($clubHomeId.','.$clubGuestId);
            }
        }

        $matchtable->setTimeRange($configurations->get($confId.'timeRangePast'), $configurations->get($confId.'timeRangeFuture'));
        if ($configurations->get($confId.'acceptRefereeIdFromRequest')) {
            $ids = $parameters->getInt('refereeId');
            if ($ids) {
                $matchtable->setReferees($ids);
                $this->addFilterData('referee', $ids);
            }
        }
        Misc::callHook(
            'cfc_league_fe',
            'filterMatch_setfields',
            [
                'matchtable' => &$matchtable,
                'fields' => &$fields,
                'options' => &$options,
                'configurations' => $configurations,
                'confid' => $confId,
            ],
            $this
        );
        $matchtable->getFields($fields, $options);
    }

    public function parseTemplate($template, &$formatter, $confId, $marker = 'FILTER')
    {
        $markerArray = [];
        $subpartArray = ['###FILTERITEMS###' => ''];
        $subpart = Templates::getSubpart($template, '###FILTERITEMS###');
        if (is_array($this->data) && count($this->data) > 0) {
            // Dafür sorgen, daß überflüssige Subpart verschwinden
            foreach (self::$profileData as $key) {
                $subpartArray['###'.strtoupper($key).'###'] = '';
            }
            $profileKeys = array_flip(self::$profileData);
            foreach ($this->data as $key => $filterData) {
                if (array_key_exists($key, $profileKeys) && intval($filterData) > 0) {
                    // Person anzeigen, die anderen Subparts entfernen
                    $profSubpartName = '###'.strtoupper($key).'###';
                    $profileSubpart = Templates::getSubpart($template, $profSubpartName);

                    $profile = Profile::getProfileInstance($filterData);
                    $profileMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ProfileMarker');
                    $subpartArray[$profSubpartName] = $profileMarker->parseTemplate($profileSubpart, $profile, $formatter, $confId.$key.'.', strtoupper($key));
                }
            }
            $subpartArray['###FILTERITEMS###'] = Templates::substituteMarkerArrayCached($subpart, $markerArray, $subpartArray);
        }
        $template = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);

        return $template;
    }

    public function addFilterData($type, $value)
    {
        $this->data[$type] = $value;
    }

    /**
     * Derzeit notwendig, um die Filter zu rendern.
     */
    public function getMarker()
    {
        return $this;
    }
}
