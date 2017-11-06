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

/**
 * Computes league tables for handball.
 * Since handball is very similar to football, the same code base is used.
 * Rules:
 * 2 points for winner
 * 1 point if draw for each team
 */
class tx_cfcleaguefe_table_handball_Table extends tx_cfcleaguefe_table_football_Table
{

    /**
     *
     * @return tx_cfcleaguefe_table_handball_Configurator
     */
    public function getConfigurator($forceNew = false)
    {
        if ($forceNew || ! is_object($this->configurator)) {
            $configuratorClass = $this->getConfValue('configuratorClass');
            $configuratorClass = $configuratorClass ? $configuratorClass : 'tx_cfcleaguefe_table_handball_Configurator';
            $this->configurator = tx_rnbase::makeInstance($configuratorClass, $this->getMatchProvider(), $this->configuration, $this->confId);
        }
        return $this->configurator;
    }

    /**
     * Zählt die Punkte für eine normale Tabelle
     *
     * @param tx_cfcleague_models_Match $match
     * @param int $toto
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

        $options = tx_rnbase::makeInstance('tx_cfcleaguefe_table_PointOptions', [
            tx_cfcleaguefe_table_PointOptions::AFTER_EXTRA_TIME => $match->isExtraTime(),
            tx_cfcleaguefe_table_PointOptions::AFTER_EXTRA_PENALTY => $match->isPenalty(),
        ]);
        if ($toto == 0) { // Unentschieden
            $this->addPoints($homeId, $configurator->getPointsDraw($options));
            $this->addPoints($guestId, $configurator->getPointsDraw($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($homeId, $configurator->getPointsDraw($options));
                $this->addPoints2($guestId, $configurator->getPointsDraw($options));
            }

            $this->addDrawCount($homeId);
            $this->addDrawCount($guestId);
        } elseif ($toto == 1) { // Heimsieg
            $this->addPoints($homeId, $configurator->getPointsWin($options));
            $this->addPoints($guestId, $configurator->getPointsLoose($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guestId, $configurator->getPointsWin($options));
            }

            $this->addWinCount($homeId);
            $this->addLoseCount($guestId);
            if ($match->isPenalty()) {
                $this->addWinCountPenalty($homeId);
                $this->addLooseCountPenalty($guestId);
            } elseif ($match->isExtraTime()) {
                $this->addWinCountOvertime($homeId);
                $this->addLooseCountOvertime($guestId);
            }
        } else { // Auswärtssieg
            $this->addPoints($homeId, $configurator->getPointsLoose($options));
            $this->addPoints($guestId, $configurator->getPointsWin($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($homeId, $configurator->getPointsWin($options));
            }
            $this->addLoseCount($homeId);
            $this->addWinCount($guestId);
            if ($match->isPenalty()) {
                $this->addWinCountPenalty($guestId);
                $this->addLooseCountPenalty($homeId);
            } elseif ($match->isExtraTime()) {
                $this->addWinCountOvertime($guestId);
                $this->addLooseCountOvertime($homeId);
            }
        }

        // Jetzt die Tore summieren
        $this->addGoals($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
        $this->addGoals($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    /**
     * Zählt die Punkte für eine Heimspieltabelle.
     * Die Ergebnisse werden als nur für die
     * Heimmannschaft gewertet.
     *
     * @param tx_cfcleague_models_Match $match
     * @param int $toto
     */
    protected function countHome($match, $toto, tx_cfcleaguefe_table_IConfigurator $configurator)
    {
        $homeId = $configurator->getTeamId($match->getHome());
        $guestId = $configurator->getTeamId($match->getGuest());
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($homeId);
        $this->addResult($homeId, $guestId, $match->getGuest());

        $options = tx_rnbase::makeInstance('tx_cfcleaguefe_table_PointOptions', [
            tx_cfcleaguefe_table_PointOptions::AFTER_EXTRA_TIME => $match->isExtraTime(),
            tx_cfcleaguefe_table_PointOptions::AFTER_EXTRA_PENALTY => $match->isPenalty(),
        ]);

        if ($toto == 0) { // Unentschieden
            $this->addPoints($homeId, $configurator->getPointsDraw($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($homeId, $configurator->getPointsDraw($options));
            }
            $this->addDrawCount($homeId);
        } elseif ($toto == 1) { // Heimsieg
            $this->addPoints($homeId, $configurator->getPointsWin($options));
            $this->addWinCount($homeId);
            if ($match->isPenalty())
                $this->addWinCountPenalty($homeId);
            elseif ($match->isExtraTime())
                $this->addWinCountOvertime($homeId);
        } else { // Auswärtssieg
            $this->addPoints($homeId, $configurator->getPointsLoose($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($homeId, $configurator->getPointsWin($options));
            }
            $this->addLoseCount($homeId);
            if ($match->isPenalty())
                $this->addLooseCountPenalty($homeId);
            elseif ($match->isExtraTime())
                $this->addLooseCountOvertime($homeId);
        }
        // Jetzt die Tore summieren
        $this->addGoals($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
    }

    /**
     * Zählt die Punkte für eine Auswärtstabelle.
     * Die Ergebnisse werden als nur für die
     * Gastmannschaft gewertet.
     *
     * @param tx_cfcleague_models_Match $match
     * @param int $toto
     */
    protected function countGuest($match, $toto, tx_cfcleaguefe_table_IConfigurator $configurator)
    {
        $homeId = $configurator->getTeamId($match->getHome());
        $guestId = $configurator->getTeamId($match->getGuest());
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($guestId);
        $this->addResult($homeId, $guestId, $match->getGuest());

        $options = tx_rnbase::makeInstance('tx_cfcleaguefe_table_PointOptions', [
            tx_cfcleaguefe_table_PointOptions::AFTER_EXTRA_TIME => $match->isExtraTime(),
            tx_cfcleaguefe_table_PointOptions::AFTER_EXTRA_PENALTY => $match->isPenalty(),
        ]);

        if ($toto == 0) { // Unentschieden
            $this->addPoints($guestId, $configurator->getPointsDraw($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guestId, $configurator->getPointsDraw($options));
            }
            $this->addDrawCount($guestId);
        } elseif ($toto == 1) { // Heimsieg
            $this->addPoints($guestId, $configurator->getPointsLoose($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guestId, $configurator->getPointsWin($options));
            }
            $this->addLoseCount($guestId);
            if ($match->isPenalty())
                $this->addLooseCountPenalty($guestId);
            elseif ($match->isExtraTime())
                $this->addLooseCountOvertime($guestId);
        } else { // Auswärtssieg
            $this->addPoints($guestId, $configurator->getPointsWin($options));
            $this->addWinCount($guestId);
            if ($match->isPenalty())
                $this->addWinCountPenalty($guestId);
            elseif ($match->isExtraTime())
                $this->addWinCountOvertime($guestId);
        }

        // Jetzt die Tore summieren
        $this->addGoals($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    /**
     * Addiert Siege nach Penalty
     */
    protected function addWinCountPenalty($teamId)
    {
        $this->_teamData[$teamId]['wincount_penalty'] = $this->_teamData[$teamId]['wincount_penalty'] + 1;
    }

    /**
     * Addiert Niederlagen nach Penalty
     */
    protected function addLooseCountPenalty($teamId)
    {
        $this->_teamData[$teamId]['loosecount_penalty'] = $this->_teamData[$teamId]['loosecount_penalty'] + 1;
    }

    /**
     * Addiert Siege nach Verlängerung
     */
    protected function addWinCountOvertime($teamId)
    {
        $this->_teamData[$teamId]['wincount_overtime'] = $this->_teamData[$teamId]['wincount_overtime'] + 1;
    }

    /**
     * Addiert Niederlagen nach Verlängerung
     */
    protected function addLooseCountOvertime($teamId)
    {
        $this->_teamData[$teamId]['loosecount_overtime'] = $this->_teamData[$teamId]['loosecount_overtime'] + 1;
    }

    protected function initTeam($teamId)
    {
        $this->_teamData[$teamId]['wincount_penalty'] = 0;
        $this->_teamData[$teamId]['loosecount_penalty'] = 0;
        $this->_teamData[$teamId]['wincount_overtime'] = 0;
        $this->_teamData[$teamId]['loosecount_overtime'] = 0;
    }

    public function getTypeID()
    {
        return 'icehockey';
    }
}

