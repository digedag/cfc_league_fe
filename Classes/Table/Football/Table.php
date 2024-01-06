<?php

namespace System25\T3sports\Table\Football;

use Exception;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Typo3Wrapper\Service\AbstractService;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\Team;
use System25\T3sports\Table\IConfigurator;
use System25\T3sports\Table\IMatchProvider;
use System25\T3sports\Table\ITableResult;
use System25\T3sports\Table\ITableType;
use System25\T3sports\Table\ITableWriter;
use System25\T3sports\Table\ITeam;
use System25\T3sports\Table\TableResult;
use System25\T3sports\Table\TeamDataContainer;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2023 Rene Nitzsche (rene@system25.de)
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
 * Computes league tables for football.
 */
class Table extends AbstractService implements ITableType
{
    public const TABLE_TYPE = 'football';

    /** @var TeamDataContainer */
    protected $_teamData;

    /** @var ConfigurationInterface */
    protected $configuration;
    protected $confId;

    /** @var Configurator */
    protected $configurator;

    /** @var IMatchProvider */
    protected $matchProvider;

    /**
     * Set configuration.
     *
     * @param ConfigurationInterface $configuration
     * @param string confId
     */
    public function setConfigurations(ConfigurationInterface $configuration, $confId)
    {
        $this->configuration = $configuration;
        $this->confId = $confId;
    }

    protected function getConfValue($key)
    {
        if (!is_object($this->configuration)) {
            return false;
        }

        return $this->configuration->get($this->confId.$key);
    }

    /**
     * Set match provider.
     *
     * @param IMatchProvider $matchProvider
     */
    public function setMatchProvider(IMatchProvider $matchProvider)
    {
        $this->matchProvider = $matchProvider;
        $matchProvider->setConfigurator($this->getConfigurator(true));
    }

    /**
     * @return IMatchProvider
     */
    public function getMatchProvider(): ?IMatchProvider
    {
        return $this->matchProvider;
    }

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
     * Returns the final table data.
     *
     * @return ITableResult
     */
    public function getTableData(): ITableResult
    {
        /** @var TableResult $tableData */
        $tableData = tx_rnbase::makeInstance(TableResult::class);
        $configurator = $this->getConfigurator();

        $this->initTeams($this->getMatchProvider(), $configurator);

        $this->handlePenalties($tableData); // Strafen können direkt berechnet werden
        $tableData->setMarks($this->getMatchProvider()
            ->getTableMarks());
        $tableData->setCompetition($this->getMatchProvider()
            ->getBaseCompetition());
        $tableData->setConfigurator($configurator);

        $rounds = $this->getMatchProvider()->getRounds();
        $comparator = $configurator->getComparator();
        // Hier die Tabledaten sortierbar gestalten
        if (!empty($rounds)) {
            foreach ($rounds as $round => $roundMatches) {
                $this->handleMatches($roundMatches, $configurator);
                // Jetzt die Tabelle sortieren, dafür benötigen wir eine Kopie des Arrays
                $teamData = $this->_teamData->getTeamDataArray();
                $comparator->setTeamData($teamData);
                usort($teamData, [
                    $comparator,
                    'compare',
                ]);
                // Nun setzen wir die Tabellenstände
                reset($teamData);
                // Nochmal sortieren und die statischen Positionen setzen
                $this->handleStaticPositions($teamData);
                $this->addScore4Round($round, $teamData, $tableData);
            }
        } else {
            // Tabelle ohne Spiele, nur die Teams zeigen
            $teamData = $this->_teamData->getTeamDataArray();
            $comparator->setTeamData($teamData);
            usort($teamData, [
                $comparator,
                'compare',
            ]);
            reset($teamData);
            // Nochmal sortieren und die statischen Positionen setzen
            $this->handleStaticPositions($teamData);
            $this->addScore4Round(0, $teamData, $tableData);
        }

        return $tableData;
    }

    protected function addScore4Round(int $round, array $teamData, TableResult $tableData)
    {
        for ($i = 0; $i < count($teamData); ++$i) {
            $newPosition = $i + 1;
            $team = $teamData[$i];
            $teamId = $team['teamId'];
            $this->_teamData->addPosition($teamId, $newPosition);
            // Jetzt die Daten des Teams übernehmen
            $tableData->addScore($round, $this->_teamData->getTeamData($teamId));
        }
    }

    /**
     * #27 static positions in league table defined by penalty records.
     *
     * @param array $teamData
     */
    protected function handleStaticPositions(&$teamData)
    {
        $statics = [];
        $maxIdx = count($teamData) - 1;
        foreach ($teamData as $currentIdx => $team) {
            if (isset($team['static_position'])) {
                $idx = $team['static_position'];
                $idx = $idx > 0 ? $idx - 1 : $maxIdx;
                $idx = $idx > $maxIdx ? $maxIdx : $idx;
                $statics[] = [
                    'new' => $idx,
                    'old' => $currentIdx,
                    'team' => $team,
                ];
            }
        }
        foreach ($statics as $data) {
            array_splice($teamData, $data['old'], 1);
            array_splice($teamData, $data['new'], 0, [$data['team']]);
        }
    }

    public function getTableWriter(): ITableWriter
    {
        return tx_rnbase::makeInstance(TableWriter::class);
    }

    /**
     * Lädt die Namen der Teams in der Tabelle.
     *
     * @param Configurator $configurator
     */
    protected function initTeams(IMatchProvider $matchProvider, Configurator $configurator)
    {
        $this->_teamData = new TeamDataContainer();
        $teams = $matchProvider->getTeams();

        foreach ($teams as $team) {
            $teamId = $team->getTeamId();
            if (!$teamId) {
                continue;
            } // Ignore teams without given id
            //             if ($team instanceof Team && $team->isDummy()) {
            //                 continue;
            //             } // Ignore dummy teams
            if ($this->_teamData->teamExists($team)) {
                continue;
            }
            $this->_teamData->addTeam($team);

            $this->_teamData->setTeamData($team, 'team', $team);
            $this->_teamData->setTeamData($team, 'teamId', $teamId);
            $this->_teamData->setTeamData($team, 'teamName', $team->getProperty('name'));
            $this->_teamData->setTeamData($team, 'teamNameShort', $team->getProperty('short_name'));
            $this->_teamData->setTeamData($team, 'clubId', $team->getProperty('club'));
            $this->_teamData->setTeamData($team, 'points', 0);
            // Bei 3-Punktssystem muss mit -1 initialisiert werden, damit der Marker später ersetzt wird
            // isLooseCount sollte zunächst über den matchProvider geholt werden
            // Später sollte eine Steuerklasse zwischengeschaltet sein, die ggf. die Information
            // aus der GUI holt.
            $this->_teamData->setTeamData($team, 'points2', ($configurator->isCountLoosePoints()) ? 0 : -1);
            $this->_teamData->setTeamData($team, 'goals1', 0);
            $this->_teamData->setTeamData($team, 'goals2', 0);
            $this->_teamData->setTeamData($team, 'goals_diff', 0);
            $this->_teamData->setTeamData($team, 'position', 0);
            $this->_teamData->setTeamData($team, 'oldposition', 0);
            $this->_teamData->setTeamData($team, 'positionchange', 'EQ');
            // Für die Formatierung im FE muss das Punktsystem bekannt sein.
            $this->_teamData->setTeamData($team, 'point_system', $configurator->getPointSystem());

            $this->_teamData->setTeamData($team, 'matchCount', 0);
            $this->_teamData->setTeamData($team, 'winCount', 0);
            $this->_teamData->setTeamData($team, 'drawCount', 0);
            $this->_teamData->setTeamData($team, 'loseCount', 0);
            $this->_teamData->setTeamData($team, 'ppm', 0);
            // FIXME: Zugriff auf Team geht so nicht mehr
            $this->_teamData->setTeamData($team, 'outOfCompetition', $team instanceof Team && $team->isOutOfCompetition());

            // Muss das Team hervorgehoben werden?
            $markClubs = $configurator->getMarkClubs();
            if (count($markClubs)) {
                $this->_teamData->setTeamData($team, 'markClub', in_array($team->getProperty('club'), $markClubs) ? 1 : 0);
            }

            $this->_teamData->setTeamData($team, 'markClubsIsRunningGame', in_array(
                $team->getProperty('club'),
                $this->getMatchProvider()->getClubIdsOfRunningMatches()) ? 1 : 0);

            $this->initTeam($team);
        }
    }

    /**
     * This methods is intended to be overwritten by subclasses to init team data.
     *
     * @param ITeam $teamId
     */
    protected function initTeam(ITeam $teamId)
    {
    }

    /**
     * Die Ligastrafen werden in den Tabellenstand eingerechnet.
     * Dies wird allerdings nur
     * für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
     */
    protected function handlePenalties($tableData)
    {
        $penalties = $this->getMatchProvider()->getPenalties();
        $tableData->setPenalties($penalties);

        foreach ($penalties as $penalty) {
            /* @var $penalty CompetitionPenalty */
            // Welches Team ist betroffen?
            $teamId = $penalty->getProperty('team');
            $team = $this->_teamData->getTeamByTeamUid($teamId);

            if (null !== $team) {
                // Die Strafe wird für den View mit abgespeichert
                // Falls es eine Korrektur ist, dann nicht speichern
                if (!$penalty->isCorrection()) {
                    $this->_teamData->addPenalty($team, $penalty);
                }
                // Die Punkte abziehen
                $this->_teamData->addPoints($team, $penalty->getProperty('points_pos') * -1);
                $this->_teamData->addPoints2($team, $penalty->getProperty('points_neg'));

                $this->addGoals($team, $penalty->getProperty('goals_pos') * -1, $penalty->getProperty('goals_neg'));
                $this->_teamData->addMatchCount($team, $penalty->getProperty('matches'));
                $this->_teamData->addWinCount($team, $penalty->getProperty('wins'));
                $this->_teamData->addDrawCount($team, $penalty->getProperty('draws'));
                $this->_teamData->addLoseCount($team, $penalty->getProperty('loses'));

                // Den Zwangsabstieg tragen wir nur ein, damit der in die Sortierung eingeht
                if ($penalty->getProperty('static_position')) {
                    $this->_teamData->addStaticPosition($team, (int) $penalty->getProperty('static_position'));
                }
            }
        }
    }

    /**
     * Addiert Tore zu einem Team.
     */
    protected function addGoals(ITeam $team, $goals1, $goals2)
    {
        $this->_teamData->addGoals($team, $goals1, $goals2);
    }

    /**
     * Der Spiel-Instanz werden die Team-Instanzen zugewiesen.
     * Diese entsprechen jetzt aber den TeamAdaptern. CHECK: Ist das ein Problem?
     *
     * @param Fixture $match
     * @param TeamDataContainer $teams
     *
     * @return bool
     */
    protected function applyTeams(Fixture $match, TeamDataContainer $teams): bool
    {
        $homeId = $match->getProperty('home');
        $team = $teams->getTeamByTeamUid($homeId);
        if (null === $team || $team->isDummy()) {
            return false; // Ignore Dummy-Matches
        }
        $match->setHome($team);

        $guestId = $match->getProperty('guest');
        $team = $teams->getTeamByTeamUid($guestId);
        if (null === $team || $team->isDummy()) {
            return false; // Ignore Dummy-Matches
        }
        $match->setGuest($team);

        return true;
    }

    /**
     * Die Spiele werden zum aktuellen Tabellenstand hinzugerechnet.
     *
     * @param Fixture[] $matches
     * @param Configurator $configurator
     */
    protected function handleMatches(&$matches, Configurator $configurator)
    {
        // Wir laufen jetzt über alle Spiele und legen einen Punktespeicher für jedes Team an
        foreach ($matches as $match) {
            /* @var $match Fixture */
            // Die Teams dem Spiel zuweisen

            if (false === $this->applyTeams($match, $this->_teamData)) {
                continue;
            }
            $this->assertTeamsInCompetition($match);
            // Wie ist das Spiel ausgegangen?
            $toto = $match->getToto();
            Misc::callHook('cfc_league_fe', 'leagueTableFootball_handleMatches', [
                'match' => &$match,
                'teamdata' => &$this->_teamData,
            ], $this);
            // Die eigentliche Punktezählung richtet sich nach dem Typ der Tabelle
            // Daher rufen wir jetzt die passende Methode auf
            switch ($configurator->getTableType()) {
                case 1:
                    $this->countHome($match, $toto, $configurator);

                    break;
                case 2:
                    $this->countGuest($match, $toto, $configurator);

                    break;
                default:
                    $this->countStandard($match, $toto, $configurator);
            }
        }

        //        unset($this->_teamData[0]); // Remove dummy data from teams without id
    }

    /**
     * Check if teams in match are configured in competition. This is a data error check to
     * avoid unexpected situations in table rendering.
     *
     * @param Fixture $match
     */
    protected function assertTeamsInCompetition($match)
    {
        $home = $match->getHome();
        $team = $this->_teamData->getTeamByTeamUid($home->getUid());
        if (null === $team) {
            throw new Exception(sprintf('Team with uid (%d) in match (%d) is not part of this selected competitions', $home->getUid(), $match->getUid()));
        }
        $guest = $match->getGuest();
        $team = $this->_teamData->getTeamByTeamUid($guest->getUid());
        if (null === $team) {
            throw new Exception(sprintf('Team with uid (%d) in match (%d) is not part of this selected competitions', $guest->getUid(), $match->getUid()));
        }
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
        /** @var \System25\T3sports\Table\TeamAdapter $home */
        $home = $match->getHome();
        /** @var \System25\T3sports\Table\TeamAdapter $guest */
        $guest = $match->getGuest();
        $this->addMatchCount($home);
        $this->addMatchCount($guest);
        if ($match->isOutOfCompetition()) {
            return;
        }
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
        } elseif (1 == $toto) { // Heimsieg
            $this->addPoints($home, $configurator->getPointsWin());
            $this->addPoints($guest, $configurator->getPointsLoose());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsWin());
            }

            $this->addWinCount($home);
            $this->addLoseCount($guest);
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLoose());
            $this->addPoints($guest, $configurator->getPointsWin());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWin());
            }
            $this->addLoseCount($home);
            $this->addWinCount($guest);
        }

        // Jetzt die Tore summieren
        $this->addGoals($home, $match->getGoalsHome(), $match->getGoalsGuest());
        $this->addGoals($guest, $match->getGoalsGuest(), $match->getGoalsHome());
        $this->addPPM($home);
        $this->addPPM($guest);
    }

    /**
     * Zählt die Punkte für eine Heimspieltabelle.
     * Die Ergebnisse werden als nur für die
     * Heimmannschaft gewertet.
     *
     * @param Fixture $match
     * @param int $toto
     * @param Configurator $configurator
     */
    protected function countHome($match, $toto, IConfigurator $configurator)
    {
        /** @var \System25\T3sports\Table\TeamAdapter $home */
        $home = $match->getHome();
        /** @var \System25\T3sports\Table\TeamAdapter $guest */
        $guest = $match->getGuest();

        // Anzahl Spiele aktualisieren
        $this->addMatchCount($home);

        if ($match->isOutOfCompetition()) {
            return;
        }

        $this->addResult($home, $guest, $match->getGuest());

        if (0 == $toto) { // Unentschieden
            $this->addPoints($home, $configurator->getPointsDraw());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsDraw());
            }
            $this->addDrawCount($home);
        } elseif (1 == $toto) { // Heimsieg
            $this->addPoints($home, $configurator->getPointsWin());
            $this->addWinCount($home);
        } else { // Auswärtssieg
            $this->addPoints($home, $configurator->getPointsLoose());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($home, $configurator->getPointsWin());
            }
            $this->addLoseCount($home);
        }
        // Jetzt die Tore summieren
        $this->addGoals($home, $match->getGoalsHome(), $match->getGoalsGuest());
        $this->addPPM($home);
    }

    /**
     * Zählt die Punkte für eine Auswärtstabelle.
     * Die Ergebnisse werden als nur für die
     * Gastmannschaft gewertet.
     *
     * @param Fixture $match
     * @param int $toto
     * @param Configurator $configurator
     */
    protected function countGuest($match, $toto, IConfigurator $configurator)
    {
        /** @var \System25\T3sports\Table\TeamAdapter $home */
        $home = $match->getHome();
        /** @var \System25\T3sports\Table\TeamAdapter $guest */
        $guest = $match->getGuest();

        // Anzahl Spiele aktualisieren
        $this->addMatchCount($guest);

        if ($match->isOutOfCompetition()) {
            return;
        }

        $this->addResult($home, $guest, $match->getGuest());

        if (0 == $toto) { // Unentschieden
            $this->addPoints($guest, $configurator->getPointsDraw());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsDraw());
            }
            $this->addDrawCount($guest);
        } elseif (1 == $toto) { // Heimsieg
            $this->addPoints($guest, $configurator->getPointsLoose());
            if ($configurator->isCountLoosePoints()) {
                $this->addPoints2($guest, $configurator->getPointsWin());
            }
            $this->addLoseCount($guest);
        } else { // Auswärtssieg
            $this->addPoints($guest, $configurator->getPointsWin());
            $this->addWinCount($guest);
        }

        // Jetzt die Tore summieren
        $this->addGoals($guest, $match->getGoalsGuest(), $match->getGoalsHome());
        $this->addPPM($guest);
    }

    protected function addResult(ITeam $home, ITeam $guest, $result)
    {
        $this->_teamData->addResult($home, $guest, $result);
    }

    /**
     * Berechnet den Punktquotienten (Punkte pro Spiel).
     */
    protected function addPPM(ITeam $team)
    {
        $this->_teamData->addPPM($team);
    }

    /**
     * Addiert Punkte zu einem Team.
     */
    protected function addPoints(ITeam $team, int $points)
    {
        $this->_teamData->addPoints($team, $points);
    }

    /**
     * Addiert negative Punkte zu einem Team.
     * Diese Funktion wird nur im 2-Punkte-System
     * verwendet.
     */
    protected function addPoints2(ITeam $team, int $points)
    {
        $this->_teamData->addPoints2($team, $points);
    }

    /**
     * Addiert die absolvierten Spiele zu einem Team.
     */
    protected function addMatchCount(ITeam $team)
    {
        $this->_teamData->addMatchCount($team);
    }

    protected function addWinCount(ITeam $team)
    {
        $this->_teamData->addWinCount($team);
    }

    protected function addDrawCount(ITeam $team)
    {
        $this->_teamData->addDrawCount($team);
    }

    protected function addLoseCount(ITeam $team)
    {
        $this->_teamData->addLoseCount($team);
    }

    public function getTypeID(): string
    {
        return self::TABLE_TYPE;
    }
}
