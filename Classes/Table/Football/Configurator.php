<?php

namespace System25\T3sports\Table\Football;

use Exception;
use System25\T3sports\Table\IComparator;
use System25\T3sports\Table\IConfigurator;
use System25\T3sports\Table\IMatchProvider;
use System25\T3sports\Table\PointOptions;
use tx_cfcleague_models_Competition;
use tx_cfcleague_models_Team;
use tx_rnbase;
use Tx_Rnbase_Utility_Strings;

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
 * Configurator for football league tables.
 * Diese Klasse erweitert den MatchProvider und liefert Daten zur Steuerung der Tabellenberechnung.
 */
class Configurator implements IConfigurator
{
    /**
     * @var IMatchProvider
     */
    protected $matchProvider;

    protected $configurations;
    protected $cfgTableStrategy;

    protected $confId;

    public function __construct(IMatchProvider $matchProvider, $configurations, $confId)
    {
        $this->matchProvider = $matchProvider;
        $this->configurations = $configurations;
        $this->confId = $confId;
        $this->init();
    }

    /**
     * 2-point-system.
     *
     * @return bool
     */
    public function isCountLoosePoints()
    {
        return '1' == $this->cfgPointSystem; // im 2-Punktesystem die Minuspunkte sammeln
    }

    public function getTeams()
    {
        return $this->getMatchProvider()->getTeams();
    }

    /**
     * Returns the unique key for a team. For alltime table this can be club uid.
     *
     * @param tx_cfcleague_models_Team $team
     */
    public function getTeamId($team)
    {
        if ('club' == $this->getConfValue('teamMode')) {
            return $team->getProperty('club');
        }

        return $team->getUid();
    }

    /**
     * @return IMatchProvider
     */
    protected function getMatchProvider()
    {
        return $this->matchProvider;
    }

    protected function getConfValue($key)
    {
        if (!is_object($this->configurations)) {
            return false;
        }

        return $this->configurations->get($this->confId.$key);
    }

    /**
     * @return tx_cfcleague_models_Competition
     */
    public function getCompetition()
    {
        return $this->getMatchProvider()->getBaseCompetition();
    }

    public function getRunningClubGames()
    {
        if (!$this->runningGamesClub) {
            $values = [];

            foreach ($this->getMatchProvider()->getRounds() as $round) {
                foreach ($round as $matchs) {
                    if ($matchs->isRunning()) {
                        $values[] = $matchs->getHome()->getClub()->getUid();
                        $values[] = $matchs->getGuest()->getClub()->getUid();
                    }
                }
            }
            $this->runningGamesClub = $values;
        }

        return $this->runningGamesClub;
    }

    public function getMarkClubs()
    {
        if (!$this->markClubs) {
            $values = $this->getConfValue('markClubs');
            if (!$values) {
                $values = $this->configurations->get('markClubs');
            } // used from flexform
            $this->markClubs = Tx_Rnbase_Utility_Strings::intExplode(',', $values);
        }

        return $this->markClubs;
    }

    /**
     * Returns the table type. This means which matches to use: all, home or away matches only.
     *
     * @return int 0-normal, 1-home, 2-away
     */
    public function getTableType()
    {
        return $this->cfgTableType;
    }

    /**
     * Returns the table scope. This means which matches to use: all, first saison part or second saison part only.
     *
     * @return int 0-normal, 1-first, 2-second
     */
    public function getTableScope()
    {
        return $this->cfgTableScope;
    }

    /**
     * @param PointOptions $options
     *
     * @return number
     */
    public function getPointsWin($options = null)
    {
        return '1' == $this->cfgPointSystem ? 2 : 3;
    }

    /**
     * @param PointOptions $options
     *
     * @return number
     */
    public function getPointsDraw($options = null)
    {
        return 1;
    }

    /**
     * @param PointOptions $options
     *
     * @return number
     */
    public function getPointsLoose($options = null)
    {
        return 0;
    }

    public function getPointSystem()
    {
        return $this->cfgPointSystem;
    }

    /**
     * @return IComparator
     */
    public function getComparator()
    {
        $compareClass = $this->cfgComparatorClass ? $this->cfgComparatorClass : Comparator::class;
        $comparator = tx_rnbase::makeInstance($compareClass);
        if (!is_object($comparator)) {
            throw new Exception('Could not instanciate comparator: '.$compareClass);
        }
        if (!($comparator instanceof IComparator)) {
            throw new Exception('Comparator is no instance of tx_cfcleaguefe_table_football_IComparator: '.get_class($comparator));
        }

        return $comparator;
    }

    protected function init()
    {
        // Der TableScope wirkt sich auf die betrachteten Spiele (Hin-Rückrunde) aus
        $parameters = $this->configurations->getParameters();
        $this->cfgTableScope = $this->getConfValue('tablescope');
        // Wir bleiben mit den alten falschen TS-Einstellungen kompatibel und fragen
        // beide Einstellungen ab
        if ($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tablescopeSelectionInput')) {
            $this->cfgTableScope = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : $this->cfgTableScope;
        }

        // tabletype means home or away matches only
        $this->cfgTableType = $this->getConfValue('tabletype');
        if ($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tabletypeSelectionInput')) {
            $this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
        }

        $this->cfgPointSystem = $this->getMatchProvider()->getBaseCompetition()->getProperty('point_system');
        if ($this->configurations->get('pointSystemSelectionInput') || $this->getConfValue('pointSystemSelectionInput')) {
            $this->cfgPointSystem = is_string($parameters->offsetGet('pointsystem')) ? intval($parameters->offsetGet('pointsystem')) : $this->cfgPointSystem;
        }
        $this->cfgLiveTable = (int) $this->getConfValue('showLiveTable');

        $this->cfgComparatorClass = $this->getStrategyValue('comparator');
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    protected function getStrategyValue(string $key)
    {
        if (null === $this->cfgTableStrategy) {
            $strategy = $this->getMatchProvider()->getBaseCompetition()->getProperty('tablestrategy');
            if ($strategy === null) {
                $srv = \tx_cfcleague_util_ServiceRegistry::getCompetitionService();
                $strategies = reset($srv->getTableStrategies4TCA());
                $strategy = $strategies[1];
            }
            $this->cfgTableStrategy = \tx_cfcleague_util_Misc::lookupTableStrategy($strategy);
        }

        return array_key_exists($key, $this->cfgTableStrategy) ? $this->cfgTableStrategy[$key] : null;
    }
}
