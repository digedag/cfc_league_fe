<?php

namespace System25\T3sports\Table\Judo;

use System25\T3sports\Model\Fixture;
use System25\T3sports\Table\Football\Table as FootballTable;
use System25\T3sports\Table\IConfigurator;
use System25\T3sports\Table\ITeam;
use System25\T3sports\Utility\MatchSets;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2024 Rene Nitzsche (rene@system25.de)
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
 * Computes league tables for judo.
 * TODO: implement
 * Rules:
 * We will count points, sets and balls
 * team 1: 2:0, 2:1, 43:41
 * 2 point for winner.
 */
class Table extends FootballTable
{
    public const TABLE_TYPE = 'judo';

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

        if (0 == $toto) { // Unentschieden
            $this->addPoints($home, $configurator->getPointsDraw());
            $this->addPoints($guest, $configurator->getPointsDraw());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsDraw());
                $this->addPoints2($guest, $configurator->getPointsDraw());
            }

            $this->addDrawCount($home);
            $this->addDrawCount($guest);
        } elseif (1 == $toto) {  // Heimsieg
            // Für die
            $this->addPoints($home, $configurator->getPointsWin($match->getGoalsHome(), $match->getGoalsGuest()));
            $this->addPoints($guest, $configurator->getPointsLoose($match->getGoalsHome(), $match->getGoalsGuest()));

            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsLoose($match->getGoalsHome(), $match->getGoalsGuest()));
            }

            $this->addWinCount($home);
            $this->addLoseCount($guest);
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLoose($match->getGoalsHome(), $match->getGoalsGuest()));
            $this->addPoints($guest, $configurator->getPointsWin($match->getGoalsHome(), $match->getGoalsGuest()));

            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWin($match->getGoalsHome(), $match->getGoalsGuest()));
            }
            $this->addLoseCount($home);
            $this->addWinCount($guest);
        }

        // Jetzt die Kämpfe und Wertungen summieren
        $this->addScores($home, $match->getGoalsHome(), $match->getGoalsGuest(), $match->getScoreHome(), $match->getScoreGuest());
        $this->addScores($guest, $match->getGoalsGuest(), $match->getGoalsHome(), $match->getScoreGuest(), $match->getScoreHome());
    }

    protected function initTeam(ITeam $team)
    {
        $this->_teamData->setTeamData($team, 'scores1', 0); // score -> point
        $this->_teamData->setTeamData($team, 'scores2', 0);
        $this->_teamData->setTeamData($team, 'scores_diff', 0);
        $this->_teamData->setTeamData($team, 'fights1', 0); // fight -> goal
        $this->_teamData->setTeamData($team, 'fights2', 0);
        $this->_teamData->setTeamData($team, 'fights_diff', 0);
    }

    /**
     * Addiert Kämpfe und Wertungen zu einem Team.
     */
    protected function addScores(ITeam $team, $fights1, $fights2, $scores1, $scores2)
    {
        $data = $this->_teamData->getTeamData($team->getTeamId());
        $newFights1 = $data['fights1'] + $fights1;
        $newFights2 = $data['fights2'] + $fights2;
        $this->_teamData->setTeamData($team, 'fights1', $newFights1);
        $this->_teamData->setTeamData($team, 'fights2', $newFights2);
        $this->_teamData->setTeamData($team, 'fights_diff', $newFights1 - $newFights2);
        $this->_teamData->setTeamData($team, 'fights_quot', $newFights1 / ($newFights2 > 0 ? $newFights2 : 1));

        $newScores1 = $data['scores1'] + $scores1;
        $newScores2 = $data['scores2'] + $scores2;
        $this->_teamData->setTeamData($team, 'scores1', $newScores1);
        $this->_teamData->setTeamData($team, 'scores2', $newScores2);
        $this->_teamData->setTeamData($team, 'scores_diff', $newScores1 - $newScores2);
        $this->_teamData->setTeamData($team, 'scores_quot', $newScores1 / ($newScores2 > 0 ? $newScores2 : 1));
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

        if (0 == $toto) { // Unentschieden
            $this->addPoints($home, $configurator->getPointsDraw());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsDraw());
            }
            $this->addDrawCount($home);
        } elseif (1 == $toto) {  // Heimsieg
            $this->addPoints($home, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($home);
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLoose($match->isExtraTime(), $match->isPenalty()));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
            }
            $this->addLoseCount($home);
        }

        // Jetzt die Sätze summieren
        $this->addScores($home, $match->getGoalsHome(), $match->getGoalsGuest(), $match->getScoreHome(), $match->getScoreGuest());
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

        if (0 == $toto) { // Unentschieden
            $this->addPoints($guest, $configurator->getPointsDraw());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsDraw());
            }
            $this->addDrawCount($guest);
        } elseif (1 == $toto) {  // Heimsieg
            $this->addPoints($guest, $configurator->getPointsLoose($match->isExtraTime(), $match->isPenalty()));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
            }
            $this->addLoseCount($guest);
        } else { // Auswärtssieg
            $this->addPoints($guest, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($guest);
        }

        $ballsHome = MatchSets::countSetPointsHome($match);
        $ballsGuest = MatchSets::countSetPointsGuest($match);
        $this->addScores($guest, $ballsGuest, $ballsHome);

        // Jetzt die Tore summieren
        $this->addScores($home, $match->getGoalsGuest(), $match->getGoalsHome(), $match->getScoreGuest(), $match->getScoreHome());
    }

    public function getTypeID(): string
    {
        return self::TABLE_TYPE;
    }
}
