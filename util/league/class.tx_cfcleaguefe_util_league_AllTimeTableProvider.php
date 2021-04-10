<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_util_league_DefaultTableProvider');

/**
 * The a table provider used to build table from all given matches.
 */
class tx_cfcleaguefe_util_league_AllTimeTableProvider extends tx_cfcleaguefe_util_league_DefaultTableProvider
{
    private $matches;

    private $teams;

    public function __construct($parameters, $configurations, $matches, $confId = '')
    {
        $this->setConfigurations($configurations, $confId);
        $this->setParameters($parameters);

        $this->matches = $matches;
        $this->init();
    }

    /**
     * Return all clubs of given matches. Since this is an alltime table, teams are useless. It exists one saison
     * only! Thats why we return clubs.
     *
     * @return array[tx_cfcleaguefe_models_club]
     */
    public function getTeams()
    {
        if (is_array($this->teams)) {
            return $this->teams;
        }
        $this->teams = [];
        for ($i = 0, $cnt = count($this->matches); $i < $cnt; ++$i) {
            $match = $this->matches[$i];
            $club = $match->getHome()->getClub();
            if ($club->getUid() && !array_key_exists($club->getUid(), $this->teams)) {
                $club->setProperty('club', $club->getUid()); // necessary for mark clubs
                $this->teams[$club->getUid()] = $club;
            }
            $club = $match->getGuest()->getClub();
            if ($club->getUid() && !array_key_exists($club->getUid(), $this->teams)) {
                $club->setProperty('club', $club->getUid()); // necessary for mark clubs
                $this->teams[$club->getUid()] = $club;
            }
        }

        return $this->teams;
    }

    public function getRounds()
    {
        return [0 => $this->matches];
    }

    public function getPenalties()
    {
        return []; // Bring hier wohl nichts...
    }

    protected function init()
    {
        $parameters = $this->getParameters();
        // Der TableScope wirkt sich auf die betrachteten Spiele (Hin-Rückrunde) aus
        $this->cfgTableScope = 0; // Normale Tabelle
        $this->cfgTableType = $this->getConfigurations()->get($this->confId.'tabletype');
        if ($this->getConfigurations()->get($this->confId.'tabletypeSelectionInput')) {
            $this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
        }
        $this->cfgPointSystem = $this->getConfigurations()->get($this->confId.'pointSystem');
        if ($this->getConfigurations()->get($this->confId.'pointSystemSelectionInput')) {
            $this->cfgPointSystem = $parameters->offsetGet('pointsystem') ? $parameters->offsetGet('pointsystem') : $this->cfgPointSystem;
        }
    }

    public function getTeamId($team)
    {
        return $team->getProperty('club');
    }
}
