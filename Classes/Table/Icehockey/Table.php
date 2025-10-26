<?php

namespace System25\T3sports\Table\Icehockey;

use System25\T3sports\Model\Fixture;
use System25\T3sports\Table\Football\Table as FootballTable;
use System25\T3sports\Table\IConfigurator;
use System25\T3sports\Table\ITeam;
use System25\T3sports\Table\PointOptions;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2022 Rene Nitzsche (rene@system25.de)
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
 * Computes league tables for icehockey.
 * Since icehockey is very similar to football, the same code base is used.
 * Rules:
 * 2 points for winner
 * 1 point for looser if draw after extra time.
 */
class Table extends FootballTable
{
    public const TABLE_TYPE = 'icehockey';

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
     */
    protected function countStandard($match, $toto, IConfigurator $configurator)
    {
        /** @var \System25\T3sports\Table\TeamAdapter $home */
        $home = $match->getHome();
        /** @var \System25\T3sports\Table\TeamAdapter $guest */
        $guest = $match->getGuest();
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($home);
        $this->addMatchCount($guest);
        // Für H2H modus das Spielergebnis merken
        $this->addResult($home, $guest, $match->getResult());

        $options = tx_rnbase::makeInstance(PointOptions::class, [
            PointOptions::AFTER_EXTRA_TIME => $match->isExtraTime(),
            PointOptions::AFTER_EXTRA_PENALTY => $match->isPenalty(),
        ]);

        if (0 == $toto) { // Unentschieden
            $this->addPoints($home, $configurator->getPointsDraw($options));
            $this->addPoints($guest, $configurator->getPointsDraw($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsDraw($options));
                $this->addPoints2($guest, $configurator->getPointsDraw($options));
            }

            $this->addDrawCount($home);
            $this->addDrawCount($guest);
        } elseif (1 == $toto) {  // Heimsieg
            $this->addPoints($home, $configurator->getPointsWin($options));
            $this->addPoints($guest, $configurator->getPointsLoose($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsWin($options));
            }

            $this->addWinCount($home);
            $this->addLoseCount($guest);
            if ($match->isPenalty()) {
                $this->addWinCountPenalty($home);
                $this->addLooseCountPenalty($guest);
            } elseif ($match->isExtraTime()) {
                $this->addWinCountOvertime($home);
                $this->addLooseCountOvertime($guest);
            }
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLoose($options));
            $this->addPoints($guest, $configurator->getPointsWin($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWin($options));
            }
            $this->addLoseCount($home);
            $this->addWinCount($guest);
            if ($match->isPenalty()) {
                $this->addWinCountPenalty($guest);
                $this->addLooseCountPenalty($home);
            } elseif ($match->isExtraTime()) {
                $this->addWinCountOvertime($guest);
                $this->addLooseCountOvertime($home);
            }
        }

        // Jetzt die Tore summieren
        $this->addGoals($home, $match->getGoalsHome(), $match->getGoalsGuest());
        $this->addGoals($guest, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    /**
     * Zählt die Punkte für eine Heimspieltabelle. Die Ergebnisse werden als nur für die
     * Heimmannschaft gewertet.
     *
     * @param Fixture $match
     * @param int $toto
     */
    protected function countHome($match, $toto, IConfigurator $configurator)
    {
        /** @var \System25\T3sports\Table\TeamAdapter $home */
        $home = $match->getHome();
        /** @var \System25\T3sports\Table\TeamAdapter $guest */
        $guest = $match->getGuest();
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($home);
        $this->addResult($home, $guest, $match->getResult());

        $options = tx_rnbase::makeInstance(PointOptions::class, [
            PointOptions::AFTER_EXTRA_TIME => $match->isExtraTime(),
            PointOptions::AFTER_EXTRA_PENALTY => $match->isPenalty(),
        ]);

        if (0 == $toto) { // Unentschieden
            $this->addPoints($home, $configurator->getPointsDraw($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsDraw($options));
            }
            $this->addDrawCount($home);
        } elseif (1 == $toto) {  // Heimsieg
            $this->addPoints($home, $configurator->getPointsWin($options));
            $this->addWinCount($home);
            if ($match->isPenalty()) {
                $this->addWinCountPenalty($home);
            } elseif ($match->isExtraTime()) {
                $this->addWinCountOvertime($home);
            }
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLoose($options));
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWin($options));
            }
            $this->addLoseCount($home);
            if ($match->isPenalty()) {
                $this->addLooseCountPenalty($home);
            } elseif ($match->isExtraTime()) {
                $this->addLooseCountOvertime($home);
            }
        }
        // Jetzt die Tore summieren
        $this->addGoals($home, $match->getGoalsHome(), $match->getGoalsGuest());
    }

    /**
     * Zählt die Punkte für eine Auswärtstabelle. Die Ergebnisse werden als nur für die
     * Gastmannschaft gewertet.
     *
     * @param Fixture $match
     * @param int $toto
     */
    protected function countGuest($match, $toto, IConfigurator $configurator)
    {
        /** @var \System25\T3sports\Table\TeamAdapter $home */
        $home = $match->getHome();
        /** @var \System25\T3sports\Table\TeamAdapter $guest */
        $guest = $match->getGuest();
        // Anzahl Spiele aktualisieren
        $this->addMatchCount($guest);
        $this->addResult($home, $guest, $match->getResult());

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
            if ($match->isPenalty()) {
                $this->addLooseCountPenalty($guest);
            } elseif ($match->isExtraTime()) {
                $this->addLooseCountOvertime($guest);
            }
        } else { // Auswärtssieg
            $this->addPoints($guest, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
            $this->addWinCount($guest);
            if ($match->isPenalty()) {
                $this->addWinCountPenalty($guest);
            } elseif ($match->isExtraTime()) {
                $this->addWinCountOvertime($guest);
            }
        }

        // Jetzt die Tore summieren
        $this->addGoals($guest, $match->getGoalsGuest(), $match->getGoalsHome());
    }

    /**
     * Addiert Siege nach Penalty.
     */
    protected function addWinCountPenalty(ITeam $team)
    {
        $this->raiseCount($team, 'wincount_penalty');
    }

    /**
     * Addiert Niederlagen nach Penalty.
     */
    protected function addLooseCountPenalty(ITeam $team)
    {
        $this->raiseCount($team, 'loosecount_penalty');
    }

    /**
     * Addiert Siege nach Verlängerung.
     */
    protected function addWinCountOvertime(ITeam $team)
    {
        $this->raiseCount($team, 'wincount_overtime');
    }

    /**
     * Addiert Niederlagen nach Verlängerung.
     */
    protected function addLooseCountOvertime(ITeam $team)
    {
        $this->raiseCount($team, 'loosecount_overtime');
    }

    private function raiseCount(ITeam $team, $countKey)
    {
        $data = $this->_teamData->getTeamData($team->getTeamId());
        $this->_teamData->setTeamData($team, $countKey, $data[$countKey] + 1);
    }

    protected function initTeam(ITeam $team)
    {
        $this->_teamData->setTeamData($team, 'wincount_penalty', 0);
        $this->_teamData->setTeamData($team, 'loosecount_penalty', 0);
        $this->_teamData->setTeamData($team, 'wincount_overtime', 0);
        $this->_teamData->setTeamData($team, 'loosecount_overtime', 0);
    }

    public function getTypeID(): string
    {
        return self::TABLE_TYPE;
    }
}
