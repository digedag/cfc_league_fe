<?php

namespace System25\T3sports\Statistics;

use Sys25\RnBase\Frontend\Marker\ListProvider;
use Sys25\RnBase\Utility\Language;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\Repository\MatchNoteRepository;
use System25\T3sports\Model\Repository\MatchRepository;
use System25\T3sports\Utility\MatchTableBuilder;
use tx_rnbase;

/***************************************************************
*  Copyright notice
*
*  (c) 2007-2023 Rene Nitzsche (rene@system25.de)
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
 * Erstellung von Statistiken.
 */
class Statistics
{
    private $clubId;
    private $matchRepo;
    private $matchNodeRepo;
    private $servicesArr;
    private $serviceKeys;
    private $servicesArrCnt;

    public function __construct(MatchNoteRepository $mnRepo = null)
    {
        $this->matchRepo = new MatchRepository();
        $this->matchNodeRepo = $mnRepo ?: new MatchNoteRepository();
    }

    /**
     * Returns a new instance.
     *
     * @return Statistics
     */
    public static function createInstance()
    {
        return tx_rnbase::makeInstance(Statistics::class);
    }

    public function createStatisticsCallback($scopeArr, $services, $configuration, $parameters)
    {
        $matchtable = new MatchTableBuilder();
        $matchtable->setScope($scopeArr);
        $matchtable->setStatus(2);
        $fields = [];
        $options = [];
        $options['orderby']['MATCH.DATE'] = 'asc';
        //		$options['debug'] = 1;
        $matchtable->getFields($fields, $options);
        $prov = tx_rnbase::makeInstance(ListProvider::class);
        $prov->initBySearch([$this->matchRepo, 'search'], $fields, $options);

        $this->initServices($services, $scopeArr, $configuration, $parameters);
        $prov->iterateAll([$this, 'handleMatch']);
        $ret = $this->collectData();

        return $ret;
    }

    private function initServices($services, $scopeArr, $configuration, $parameters)
    {
        // Das scheint unnötig kompliziert zu sein...
        $this->clubId = $scopeArr['CLUB_UIDS'];
        $this->servicesArr = array_values($services);
        $this->serviceKeys = array_keys($services);
        $this->servicesArrCnt = count($this->servicesArr);
        foreach ($services as $service) {
            $service->prepare($scopeArr, $configuration, $parameters);
        }
    }

    /**
     * Callback methode.
     *
     * @param Fixture $match
     */
    public function handleMatch(Fixture $match)
    {
        $matches = [$match];
        $matches = $this->matchNodeRepo->retrieveMatchNotes($matches);

        for ($i = 0; $i < $this->servicesArrCnt; ++$i) {
            $service = &$this->servicesArr[$i];
            $service->handleMatch($match, $this->clubId);
        }
    }

    private function collectData()
    {
        // Abschließend die Daten zusammenpacken
        $ret = [];
        for ($i = 0; $i < $this->servicesArrCnt; ++$i) {
            $service = &$this->servicesArr[$i];
            $ret[$this->serviceKeys[$i]] = $service->getResult();
        }

        return $ret;
    }

    /**
     * Start creation of statistical data.
     *
     * @param array $matches
     * @param array $scopeArr
     * @param array $services
     */
    public static function createStatistics(&$matches, $scopeArr, &$services, &$configuration, &$parameters)
    {
        $clubId = $scopeArr['CLUB_UIDS'];
        $servicesArr = array_values($services);
        $serviceKeys = array_keys($services);
        $servicesArrCnt = count($servicesArr);
        for ($i = 0; $i < $servicesArrCnt; ++$i) {
            $service = &$servicesArr[$i];
            $service->prepare($scopeArr, $configuration, $parameters);
        }

        // Über alle Spiele iterieren und diese an die Services geben
        for ($j = 0, $mc = count($matches); $j < $mc; ++$j) {
            for ($i = 0; $i < $servicesArrCnt; ++$i) {
                $service = &$servicesArr[$i];
                $service->handleMatch($matches[$j], $clubId);
            }
        }
        // Abschließend die Daten zusammenpacken
        $ret = [];
        for ($i = 0; $i < $servicesArrCnt; ++$i) {
            $service = &$servicesArr[$i];
            $ret[$serviceKeys[$i]] = $service->getResult();
        }

        return $ret;
    }

    /**
     * Liefert die vorhandenen Statistic-Services für die Auswahl im Flexform.
     */
    public static function lookupStatistics($config)
    {
        $services = Misc::lookupServices('cfcleague_statistics');
        foreach ($services as $subtype => $info) {
            $title = $info['title'];
            if ('LLL:' === substr($title, 0, 4)) {
                $title = Language::sL($title);
            }
            $config['items'][] = [
                $title,
                $subtype,
            ];
        }

        return $config;
    }
}
