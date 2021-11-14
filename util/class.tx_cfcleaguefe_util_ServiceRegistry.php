<?php

use System25\T3sports\Utility\ServiceRegistry;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2018 Rene Nitzsche (rene@system25.de)
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
 * Keine echte Registry, aber eine zentrale Klasse für den Zugriff auf verschiedene
 * Services.
 */
class tx_cfcleaguefe_util_ServiceRegistry
{
    /**
     * Liefert die vorhandenen Statistic-Services für die Auswahl im Flexform.
     */
    public static function lookupStatistics($config)
    {
        $services = tx_rnbase_util_Misc::lookupServices('cfcleague_statistics');
        tx_rnbase::load('tx_rnbase_util_Lang');
        foreach ($services as $subtype => $info) {
            $title = $info['title'];
            if ('LLL:' === substr($title, 0, 4)) {
                $title = tx_rnbase_util_Lang::sL($title);
            }
            $config['items'][] = [
                $title,
                $subtype,
            ];
        }

        return $config;
    }

    /**
     * Liefert den Profile-Service.
     *
     * @return System25\T3sports\Service\ProfileService
     */
    public static function getProfileService()
    {
        return tx_rnbase_util_Misc::getService('cfcleague_data', 'profile');
    }

    /**
     * Liefert den Match-Service.
     *
     * @return \System25\T3sports\Service\MatchService
     */
    public static function getMatchService()
    {
        return tx_rnbase_util_Misc::getService('t3sports_srv', 'match');
    }

    /**
     * Liefert den Team-Service.
     *
     * @return \System25\T3sports\Service\TeamService
     */
    public static function getTeamService()
    {
        return tx_rnbase_util_Misc::getService('cfcleague_data', 'team');
    }

    /**
     * Liefert den Wettbewerbsservice.
     *
     * @return \System25\T3sports\Service\CompetitionService
     */
    public static function getCompetitionService()
    {
        return ServiceRegistry::getCompetitionService();
    }
}
