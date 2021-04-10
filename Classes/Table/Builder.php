<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use tx_cfcleague_models_Competition;
use tx_cfcleaguefe_models_match;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2020 Rene Nitzsche (rene@system25.de)
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
 * Builder class for league tables.
 */
class Builder
{
    /**
     * @param tx_cfcleague_models_Competition $league
     * @param array $matches
     * @param ConfigurationInterface $configurations
     * @param string $confId
     *
     * @return ITableType
     */
    public static function buildByCompetitionAndMatches($league, $matches, $configurations, $confId)
    {
        $tableType = $league->getSports();

        $prov = Factory::createMatchProvider($tableType, $configurations, $confId);
        $prov->setLeague($league);
        $prov->setMatches($matches);
        // Der Scope muss gesetzt werden, damit die Team gefunden werden
        $prov->setScope([
            'COMP_UIDS' => $league->getUid(),
        ]);
        $table = Factory::createTableType($tableType);
        $table->setConfigurations($configurations, $confId.'tablecfg.');
        // MatchProvider und Configurator müssen sich gegenseitig kennen
        $table->setMatchProvider($prov);
        $c = $table->getConfigurator(true);
        $prov->setConfigurator($c);

        return $table;
    }

    /**
     * @param array $scopeArr
     * @param ConfigurationInterface $configurations
     * @param string $confId
     *
     * @return ITableType
     */
    public static function buildByRequest($scopeArr, $configurations, $confId)
    {
        // Zuallererst muss die Sportart ermittelt werden, weil diese für
        // die weiteren Klassen notwendig ist.
        // die Sportart wird daher static auf Basis des Scopes ermittelt
        $tableType = DefaultMatchProvider::getLeagueFromScope($scopeArr)->getSports();

        // Vereine dürfen die Spielauswahl nicht einschränken
        unset($scopeArr['CLUB_UIDS']);

        $prov = Factory::createMatchProvider($tableType, $configurations, $confId);
        $prov->setScope($scopeArr);
        // Der Provider liefert alle realen Daten auf Basis der Daten
        // Für Sondertabellen können diese Werte später überschrieben werden. Hier folgt
        // dann die Integration der GUI
        // Der Provider kennt die Spiele, also könnte er auch die Sportart kennen...
        $table = Factory::createTableType($tableType);

        $table->setConfigurations($configurations, $confId.'tablecfg.');
        // MatchProvider und Configurator müssen sich gegenseitig kennen
        $table->setMatchProvider($prov);
        $c = $table->getConfigurator(true);
        $prov->setConfigurator($c);

        return $table;
    }

    /**
     * Build league table to compare two opponents of a single match.
     * FIXME: implement!
     *
     * @param tx_cfcleaguefe_models_match $match
     * @param ConfigurationInterface $configurations
     * @param string $confId
     *
     * @return ITableType
     */
    public static function buildByMatch($match, $configurations, $confId)
    {
        $tableType = $league->getSports();

        $prov = Factory::createMatchProvider($tableType, $configurations, $confId);
        $prov->setLeague($league);
        $prov->setMatches($matches);
    }
}
