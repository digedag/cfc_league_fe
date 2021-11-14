<?php

namespace System25\T3sports\Service;

use System25\T3sports\Model\Match;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2007-2019 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * @author Rene Nitzsche
 */
class MatchEventService extends \tx_cal_event_service
{
    public $callegenddescription;

    public $calnumber = 6;

    public $subheader;

    public $image;

    public $category = 'Matches';

    /* @var $configurations \tx_rnbase_configurations */

    /**
     * Finds all matches.
     *
     * @return array the array of events represented by the model
     */
    public function findAllWithin($start_date, $end_date, $pidList)
    {
        /* @var $this->configurations tx_rnbase_configurations */
        $configurations = \tx_rnbase::makeInstance('tx_rnbase_configurations');
        $configurations->init($this->conf, null, 'cal', 'cal');
        $this->_init();
        $confId = 'view.cfc_league_events.';

        $matchTable = $this->getMatchTable();
        $start_date = is_object($start_date) ? $start_date->getTime() : $start_date;
        $end_date = is_object($end_date) ? $end_date->getTime() : $end_date;
        $matchTable->setDateRange($start_date, $end_date);
        $matchTable->setPidList($pidList);
        $matchTable->setSaisons($configurations->get($confId.'saisonSelection'));
        $matchTable->setAgeGroups($configurations->get($confId.'groupSelection'));
        $matchTable->setCompetitions($configurations->get($confId.'competitionSelection'));
        $matchTable->setClubs($configurations->get($confId.'clubSelection'));
        $matchTable->setIgnoreDummy($configurations->getBool($confId.'ignoreDummy', false, false));
        $matchTable->setCompetitionTypes($configurations->get($confId.'competitionTypes'));
        $matchTable->setCompetitionObligation($configurations->getInt($confId.'competitionObligation'));
        $matchTable->setLimit($configurations->getInt($confId.'limit'));
        $matchTable->setLiveTicker($configurations->getBool('view.cfc_league_events.livetickerOnly', false, false));

        $fields = [];
        $options = [];
        if ($this->conf['view.']['cfc_league_events.']['debug']) {
            $options['debug'] = 1;
        }
        $matchTable->getFields($fields, $options);

        \tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $confId.'fields.');
        // Optionen
        \tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $confId.'options.');

        $srv = \tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
        $matches = $srv->search($fields, $options);

        $events = [];

        foreach ($matches as $match) {
            $events[date('Ymd', $match->getProperty('date'))][date('Hi', $match->getProperty('date'))][$match->uid] = $this->createEvent($match, false);
        }

        return $events;
    }

    /**
     * Returns a new matchtable instance.
     *
     * @return \tx_cfcleague_util_MatchTableBuilder
     */
    private function getMatchTable()
    {
        return \tx_rnbase::makeInstance('tx_cfcleague_util_MatchTableBuilder');
    }

    /**
     * Finds a single event.
     *
     * @return object the event represented by the model
     */
    public function find($uid, $pidList)
    {
        $this->_init();
        $match = \tx_rnbase::makeInstance(Match::class, $uid);
        $event = $this->createEvent($match, false);
        /*
         * $events = array();
         * $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("*", "tt_news", " uid=".$uid);
         * while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
         * $event = $this->createEvent($row, false);
         * }
         */
        return $event;
    }

    public function createEvent($match, $isException)
    {
        $event = \tx_rnbase::makeInstance('tx_cfcleaguefe_models_match_calevent', $this->controller, $match, $isException, $this->getServiceKey());

        return $event;
    }

    /**
     * Gets the legend description.
     *
     * @return array the legend array
     */
    public function getCalLegendDescription()
    {
        return $this->callegenddescription;
    }

    /**
     * TODO Implement search function!
     *
     * @param string $pidList
     * @param string $starttime date string
     * @param string $endtime date string
     * @param string $searchword
     * @param array $locationIds
     *
     * @return array
     */
    public function search($pidList = '', $starttime, $endtime, $searchword, $locationIds)
    {
        return [];
        // return parent::search($pidList, $starttime, $endtime, $searchword, $locationIds);
    }

    public function _init()
    {
        $legendArray = [
            'title' => $this->conf['view.']['cfc_league_events.']['legendDescription'],
        ];
        $this->callegenddescription = [
            $this->conf['view.']['cfc_league_events.']['legendCalendarName'] => [
                [
                    $this->conf['view.']['cfc_league_events.']['headerStyle'] => $legendArray,
                ],
            ],
        ];
    }
}
