<?php

namespace System25\T3sports\Table\Volleyball;

use System25\T3sports\Model\Fixture;
use System25\T3sports\Table\Football\Table as FootballTable;
use System25\T3sports\Table\IConfigurator;
use System25\T3sports\Table\ITeam;
use System25\T3sports\Utility\MatchSets;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2023 Rene Nitzsche (rene@system25.de)
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
 * Computes league tables for volleyball.
 * Rules:
 * We will count points, sets and balls
 * team 1: 2:0, 2:1, 43:41
 * 2 point for winner.
 */
class Table extends FootballTable
{
    public const TABLE_TYPE = 'volleyball';

    /**
     * @return Configurator
     */
    public function getConfigurator($forceNew = false): IConfigurator
    {
        if ($forceNew || !is_object($this->configurator)) {
            $configuratorClass = $this->getConfValue('configuratorClass');
            $configuratorClass = $configuratorClass ? $configuratorClass : Configurator::class;
            $this->configurator = tx_rnbase::makeInstance($configuratorClass, $this->getMatchProvider()->getBaseCompetition(), $this->configuration, $this->confId);
        }

        return $this->configurator;
    }

    /**
     * Zählt die Punkte für eine normale Tabelle.
     *
     * @param Fixture $match
     * @param int $toto
     * @param IConfigurator $configurator
     */
    protected function countStandard($match, $toto, IConfigurator $configurator)
    {
        // Anzahl Spiele aktualisieren
        $home = $match->getHome();
        $guest = $match->getGuest();
        $this->addMatchCount($home);
        $this->addMatchCount($guest);

        // Für H2H modus das Spielergebnis merken
        $this->addResult($home, $guest, $match->getResult());

        // Beim Volleyball gibt es kein Unentschieden
        if (1 == $toto) {  // Heimsieg
            // Für die
            $this->addPoints($home, $configurator->getPointsWinVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            $this->addPoints($guest, $configurator->getPointsLooseVolley($match->getGoalsHome(), $match->getGoalsGuest()));

            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsLooseVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            }

            $this->addWinCount($home);
            $this->addLoseCount($guest);
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLooseVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            $this->addPoints($guest, $configurator->getPointsWinVolley($match->getGoalsHome(), $match->getGoalsGuest()));

            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWinVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            }
            $this->addLoseCount($home);
            $this->addWinCount($guest);
        }

        $ballsHome = MatchSets::countSetPointsHome($match);
        $ballsGuest = MatchSets::countSetPointsGuest($match);
        $this->addBalls($home, $ballsHome, $ballsGuest);
        $this->addBalls($guest, $ballsGuest, $ballsHome);

        // Jetzt die Tore summieren
        $this->addSets($home, $match->getGoalsHome(), $match->getGoalsGuest());
        $this->addSets($guest, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    protected function initTeam(ITeam $team)
    {
        $this->_teamData->setTeamData($team, 'balls1', 0);
        $this->_teamData->setTeamData($team, 'balls2', 0);
        $this->_teamData->setTeamData($team, 'balls_diff', 0);
        $this->_teamData->setTeamData($team, 'sets1', 0);
        $this->_teamData->setTeamData($team, 'sets2', 0);
        $this->_teamData->setTeamData($team, 'sets_diff', 0);
    }

    /**
     * Addiert Sätze zu einem Team.
     */
    protected function addSets(ITeam $team, $sets1, $sets2)
    {
        $data = $this->_teamData->getTeamData($team->getTeamId());
        $newSets1 = $data['sets1'] + $sets1;
        $newSets2 = $data['sets2'] + $sets2;
        $this->_teamData->setTeamData($team, 'sets1', $newSets1);
        $this->_teamData->setTeamData($team, 'sets2', $newSets2);
        $this->_teamData->setTeamData($team, 'sets_diff', $newSets1 - $newSets2);
        // TODO: Muss hier ggf. gerundet werden??
        $this->_teamData->setTeamData($team, 'sets_quot', $newSets1 / ($newSets2 > 0 ? $newSets2 : 1));
    }

    /**
     * Addiert Bälle zu einem Team.
     */
    protected function addBalls(ITeam $team, $balls1, $balls2)
    {
        $data = $this->_teamData->getTeamData($team->getTeamId());
        $newBalls1 = $data['balls1'] + $balls1;
        $newBalls2 = $data['balls2'] + $balls2;
        $this->_teamData->setTeamData($team, 'balls1', $newBalls1);
        $this->_teamData->setTeamData($team, 'balls2', $newBalls2);
        $this->_teamData->setTeamData($team, 'balls_diff', $newBalls1 - $newBalls2);
        $this->_teamData->setTeamData($team, 'balls_quot', $newBalls1 / ($newBalls2 > 0 ? $newBalls2 : 1));
    }

    /**
     * Zählt die Punkte für eine Heimspieltabelle. Die Ergebnisse werden als nur für die
     * Heimmannschaft gewertet.
     *
     * @param Fixture $match
     * @param int $toto
     * @param IConfigurator $configurator
     */
    protected function countHome($match, $toto, IConfigurator $configurator)
    {
        $home = $match->getHome();
        $guest = $match->getGuest();

        // Anzahl Spiele aktualisieren
        $this->addMatchCount($home);
        $this->addResult($home, $guest, $match->getGuest());

        if (1 == $toto) {  // Heimsieg
            $this->addPoints($home, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($home);
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLooseVolley($match->isExtraTime(), $match->isPenalty()));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            }
            $this->addLoseCount($home);
        }
        $ballsHome = MatchSets::countSetPointsHome($match);
        $ballsGuest = MatchSets::countSetPointsGuest($match);
        $this->addBalls($home, $ballsHome, $ballsGuest);

        // Jetzt die Sätze summieren
        $this->addSets($home, $match->getGoalsHome(), $match->getGoalsGuest());
    }

    /**
     * Zählt die Punkte für eine Auswärtstabelle. Die Ergebnisse werden als nur für die
     * Gastmannschaft gewertet.
     *
     * @param Fixture $match
     * @param int $toto
     * @param IConfigurator $configurator
     */
    protected function countGuest($match, $toto, IConfigurator $configurator)
    {
        $home = $match->getHome();
        $guest = $match->getGuest();
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($guest);
        $this->addResult($home, $guest, $match->getGuest());

        if (1 == $toto) {  // Heimsieg
            $this->addPoints($guest, $configurator->getPointsLooseVolley($match->isExtraTime(), $match->isPenalty()));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            }
            $this->addLoseCount($guest);
        } else { // Auswärtssieg
            $this->addPoints($guest, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($guest);
        }

        $ballsHome = MatchSets::countSetPointsHome($match);
        $ballsGuest = MatchSets::countSetPointsGuest($match);
        $this->addBalls($guest, $ballsGuest, $ballsHome);

        // Jetzt die Tore summieren
        $this->addSets($guest, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    public function getTypeID(): string
    {
        return self::TABLE_TYPE;
    }
}
