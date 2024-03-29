<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Frontend\View\StatisticsView;
use System25\T3sports\Statistics\Service\StatsServiceProvider;
use System25\T3sports\Statistics\Statistics as StatisticsUtil;
use System25\T3sports\Utility\MatchTicker;
use System25\T3sports\Utility\ScopeController;

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
class Statistics extends AbstractAction
{
    /**
     * handle request.
     *
     * @param RequestInterface $request
     *
     * @return string|null
     */
    protected function handleRequest(RequestInterface $request)
    {
        $parameters = $request->getParameters();
        $configurations = $request->getConfigurations();
        $scopeArr = ScopeController::handleCurrentScope($parameters, $configurations);
        // Die notwendigen Statistikklassen ermitteln
        $types = Strings::trimExplode(',', $configurations->get('statisticTypes'), 1);
        if (!count($types)) {
            // Abbruch kein Typ angegeben
            return $configurations->getLL('statistics_noTypeFound');
        }
        $services = [];
        foreach ($types as $type) {
            $service = StatsServiceProvider::getInstance()->getStatsServiceByType($type);
            if (is_object($service)) {
                $services[$type] = $service;
            }
        }
        $mode = $configurations->get('statistic.callbackmode');
        if ($mode) {
            $stats = StatisticsUtil::createInstance();
            $data = $stats->createStatisticsCallback($scopeArr, $services, $configurations, $parameters);
        } else {
            $ticker = new MatchTicker();
            $matches = $ticker->getMatches4Scope($scopeArr);
            $data = StatisticsUtil::createStatistics($matches, $scopeArr, $services, $configurations, $parameters);
        }

        // Aufruf der Statistik
        $request->getViewContext()->offsetSet('data', $data); // Services bereitstellen

        return null;
    }

    protected function getTemplateName()
    {
        return 'statistics';
    }

    protected function getViewClassName()
    {
        return StatisticsView::class;
    }
}
