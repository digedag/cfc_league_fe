<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_action_BaseIOC');

tx_rnbase::load('tx_rnbase_util_Spyc');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_cfcleaguefe_util_MatchTicker');
tx_rnbase::load('tx_cfcleaguefe_util_Statistics');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');

/**
 * Controller für die Anzeige von Spielerstatistiken.
 *
 * Zunächst ist wichtig welche Spieler betrachtet werden sollen. Dieser
 * Scope ist zunächst auf die Spieler eines Teams und damit auch einer
 * Saison beschränkt. (Ein Team spielt ja nur in einer Saison.) Später
 * könnte man den Scope aber auch erweitern:
 * - Spieler eines Vereins in einer bestimmten Altersgruppe in allen Saisons
 * - Anzeige der besten Torschützen einer Liga (teamübergreifend)
 * - usw.
 * Vermutlich wäre es aber besser dafür eigene Views zu erstellen. Diese
 * könnten dann die entsprechenden Flexforms zur Verfügung stellen.
 *
 * Diese Klasse zeigt zunächst die Auswertung für die Spieler eines Teams.
 */
class tx_cfcleaguefe_actions_Statistics extends tx_rnbase_action_BaseIOC
{
    /**
     * handle request.
     *
     * @param arrayobject $parameters
     * @param tx_rnbase_configurations $configurations
     * @param arrayobject $viewData
     *
     * @return string
     */
    protected function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
        // Die notwendigen Statistikklassen ermitteln
        $types = Tx_Rnbase_Utility_Strings::trimExplode(',', $configurations->get('statisticTypes'), 1);
        if (!count($types)) {
            // Abbruch kein Typ angegeben
            return $configurations->getLL('statistics_noTypeFound');
        }
        $services = [];
        foreach ($types as $type) {
            $service = tx_rnbase::makeInstanceService('cfcleague_statistics', $type);
            if (is_object($service)) {
                $services[$type] = $service;
            }
        }
        $mode = $configurations->get('statistic.callbackmode');
        if ($mode) {
            $stats = tx_cfcleaguefe_util_Statistics::createInstance();
            $data = $stats->createStatisticsCallback($scopeArr, $services, $configurations, $parameters);
        } else {
            $matches = &tx_cfcleaguefe_util_MatchTicker::getMatches4Scope($scopeArr);
            $data = tx_cfcleaguefe_util_Statistics::createStatistics($matches, $scopeArr, $services, $configurations, $parameters);
        }

        // Aufruf der Statistik
        $viewData->offsetSet('data', $data); // Services bereitstellen

        return null;
    }

    protected function getTemplateName()
    {
        return 'statistics';
    }

    protected function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_Statistics';
    }
}
