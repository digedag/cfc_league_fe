<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2017 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_football_Table');
tx_rnbase::load('tx_cfcleague_util_MatchSets');

/**
 * Computes league tables for volleyball.
 * Rules:
 * We will count points, sets and balls
 * team 1: 2:0, 2:1, 43:41
 * 2 point for winner.
 */
class tx_cfcleaguefe_table_volleyball_Table extends tx_cfcleaguefe_table_football_Table
{
    /**
     * @return tx_cfcleaguefe_table_volleyball_Configurator
     */
    public function getConfigurator($forceNew = false)
    {
        if ($forceNew || !is_object($this->configurator)) {
            $configuratorClass = $this->getConfValue('configuratorClass');
            $configuratorClass = $configuratorClass ? $configuratorClass : 'tx_cfcleaguefe_table_volleyball_Configurator';
            $this->configurator = tx_rnbase::makeInstance($configuratorClass, $this->getMatchProvider(), $this->configuration, $this->confId);
        }

        return $this->configurator;
    }

    /**
     * Zählt die Punkte für eine normale Tabelle.
     *
     * @param tx_cfcleague_models_Match $match
     * @param int $toto
     * @param tx_cfcleaguefe_table_volleyball_Configurator $configurator
     */
    protected function countStandard($match, $toto, tx_cfcleaguefe_table_IConfigurator $configurator)
    {
        // Anzahl Spiele aktualisieren
        $homeId = $configurator->getTeamId($match->getHome());
        $guestId = $configurator->getTeamId($match->getGuest());
        $this->addMatchCount($homeId);
        $this->addMatchCount($guestId);
        // Für H2H modus das Spielergebnis merken
        $this->addResult($homeId, $guestId, $match->getResult());
        $sets = $match->getSets();

        // Beim Volleyball gibt es kein Unentschieden
        if (1 == $toto) {  // Heimsieg
            // Für die
            $this->addPoints($homeId, $configurator->getPointsWinVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            $this->addPoints($guestId, $configurator->getPointsLooseVolley($match->getGoalsHome(), $match->getGoalsGuest()));

            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guestId, $configurator->getPointsLooseVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            }

            $this->addWinCount($homeId);
            $this->addLoseCount($guestId);
        } else { // Auswärtssieg
            $this->addPoints($homeId, $configurator->getPointsLooseVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            $this->addPoints($guestId, $configurator->getPointsWinVolley($match->getGoalsHome(), $match->getGoalsGuest()));

            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($homeId, $configurator->getPointsWinVolley($match->getGoalsHome(), $match->getGoalsGuest()));
            }
            $this->addLoseCount($homeId);
            $this->addWinCount($guestId);
        }

        $ballsHome = tx_cfcleague_util_MatchSets::countSetPointsHome($match);
        $ballsGuest = tx_cfcleague_util_MatchSets::countSetPointsGuest($match);
        $this->addBalls($homeId, $ballsHome, $ballsGuest);
        $this->addBalls($guestId, $ballsGuest, $ballsHome);

        // Jetzt die Tore summieren
        $this->addSets($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
        $this->addSets($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    protected function initTeam($teamId)
    {
        $this->_teamData[$teamId]['balls1'] = 0;
        $this->_teamData[$teamId]['balls2'] = 0;
        $this->_teamData[$teamId]['balls_diff'] = 0;
        $this->_teamData[$teamId]['sets1'] = 0;
        $this->_teamData[$teamId]['sets2'] = 0;
        $this->_teamData[$teamId]['sets_diff'] = 0;
    }

    /**
     * Addiert Sätze zu einem Team.
     */
    protected function addSets($teamId, $sets1, $sets2)
    {
        $this->_teamData[$teamId]['sets1'] = $this->_teamData[$teamId]['sets1'] + $sets1;
        $this->_teamData[$teamId]['sets2'] = $this->_teamData[$teamId]['sets2'] + $sets2;
        $this->_teamData[$teamId]['sets_diff'] = $this->_teamData[$teamId]['sets1'] - $this->_teamData[$teamId]['sets2'];
        // TODO: Muss hier ggf. gerundet werden??
        $this->_teamData[$teamId]['sets_quot'] = $this->_teamData[$teamId]['sets1'] /
                                                        ($this->_teamData[$teamId]['sets2'] > 0 ? $this->_teamData[$teamId]['sets2'] : 1);
    }

    /**
     * Addiert Bälle zu einem Team.
     */
    protected function addBalls($teamId, $balls1, $balls2)
    {
        $this->_teamData[$teamId]['balls1'] = $this->_teamData[$teamId]['balls1'] + $balls1;
        $this->_teamData[$teamId]['balls2'] = $this->_teamData[$teamId]['balls2'] + $balls2;
        $this->_teamData[$teamId]['balls_diff'] = $this->_teamData[$teamId]['balls1'] - $this->_teamData[$teamId]['balls2'];
        $this->_teamData[$teamId]['balls_quot'] = $this->_teamData[$teamId]['balls1'] /
                                                        ($this->_teamData[$teamId]['balls2'] > 0 ? $this->_teamData[$teamId]['balls2'] : 1);
    }

    /**
     * Zählt die Punkte für eine Heimspieltabelle. Die Ergebnisse werden als nur für die
     * Heimmannschaft gewertet.
     *
     * @param tx_cfcleague_models_Match $match
     * @param int $toto
     * @param tx_cfcleaguefe_table_volleyball_Configurator $configurator
     */
    protected function countHome($match, $toto, tx_cfcleaguefe_table_IConfigurator $configurator)
    {
        $homeId = $configurator->getTeamId($match->getHome());
        $guestId = $configurator->getTeamId($match->getGuest());
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($homeId);
        $this->addResult($homeId, $guestId, $match->getGuest());
        $sets = $match->getSets();

        if (1 == $toto) {  // Heimsieg
            $this->addPoints($homeId, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($homeId);
        } else { // Auswärtssieg
            $this->addPoints($homeId, $configurator->getPointsLooseVolley($match->isExtraTime(), $match->isPenalty()));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($homeId, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            }
            $this->addLoseCount($homeId);
        }
        $ballsHome = tx_cfcleague_util_MatchSets::countSetPointsHome($match);
        $ballsGuest = tx_cfcleague_util_MatchSets::countSetPointsGuest($match);
        $this->addBalls($homeId, $ballsHome, $ballsGuest);

        // Jetzt die Sätze summieren
        $this->addSets($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
    }

    /**
     * Zählt die Punkte für eine Auswärtstabelle. Die Ergebnisse werden als nur für die
     * Gastmannschaft gewertet.
     *
     * @param tx_cfcleague_models_Match $match
     * @param int $toto
     * @param tx_cfcleaguefe_table_football_Configurator $configurator
     */
    protected function countGuest($match, $toto, tx_cfcleaguefe_table_IConfigurator $configurator)
    {
        $homeId = $configurator->getTeamId($match->getHome());
        $guestId = $configurator->getTeamId($match->getGuest());
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($guestId);
        $this->addResult($homeId, $guestId, $match->getGuest());
        $sets = $match->getSets();

        if (1 == $toto) {  // Heimsieg
            $this->addPoints($guestId, $configurator->getPointsLooseVolley($match->isExtraTime(), $match->isPenalty()));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guestId, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            }
            $this->addLoseCount($guestId);
        } else { // Auswärtssieg
            $this->addPoints($guestId, $configurator->getPointsWinVolley($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($guestId);
        }

        $ballsHome = tx_cfcleague_util_MatchSets::countSetPointsHome($match);
        $ballsGuest = tx_cfcleague_util_MatchSets::countSetPointsGuest($match);
        $this->addBalls($guestId, $ballsGuest, $ballsHome);

        // Jetzt die Tore summieren
        $this->addSets($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    public function getTypeID()
    {
        return 'volleyball';
    }
}
