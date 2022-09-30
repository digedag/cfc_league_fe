<?php

namespace System25\T3sports\Table;

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
 * Container for ITeams in league tables.
 */
class TeamDataContainer
{
    private $dataByTeamId = [];
    private $teamByUid = [];

    public function __construct()
    {
        $this->dataByTeamId = [];
    }

    public function addTeam(ITeam $team)
    {
        $this->dataByTeamId[$team->getTeamId()] = [];
        foreach ($team->getTeamUids() as $teamUid) {
            $this->teamByUid[$teamUid] = $team;
        }
    }

    public function teamIdExists($teamUid): bool
    {
        return array_key_exists($teamUid, $this->dataByTeamId);
    }

    public function teamExists(ITeam $team): bool
    {
        return $this->teamIdExists($team->getTeamId());
    }

    public function setTeamData(ITeam $team, $key, $value)
    {
        $this->dataByTeamId[$team->getTeamId()][$key] = $value;
    }

    /**
     * Zugriff auf den TeamAdapter über die UID eines konkreten Teams.
     *
     * @param int $teamUid
     *
     * @return ITeam|null
     */
    public function getTeamByTeamUid(int $teamUid): ?ITeam
    {
        return array_key_exists($teamUid, $this->teamByUid) ? $this->teamByUid[$teamUid] : null;
    }

    public function addGoals(ITeam $team, int $goals1, int $goals2)
    {
        $teamId = $team->getTeamId();
        $this->dataByTeamId[$teamId]['goals1'] = $this->dataByTeamId[$teamId]['goals1'] + $goals1;
        $this->dataByTeamId[$teamId]['goals2'] = $this->dataByTeamId[$teamId]['goals2'] + $goals2;
        $this->dataByTeamId[$teamId]['goals_diff'] = $this->dataByTeamId[$teamId]['goals1'] - $this->dataByTeamId[$teamId]['goals2'];
    }

    public function addMatchCount(ITeam $team)
    {
        $this->dataByTeamId[$team->getTeamId()]['matchCount'] = $this->dataByTeamId[$team->getTeamId()]['matchCount'] + 1;
    }

    public function addWinCount(ITeam $team)
    {
        $this->dataByTeamId[$team->getTeamId()]['winCount'] = $this->dataByTeamId[$team->getTeamId()]['winCount'] + 1;
    }

    public function addDrawCount(ITeam $team)
    {
        $this->dataByTeamId[$team->getTeamId()]['drawCount'] = $this->dataByTeamId[$team->getTeamId()]['drawCount'] + 1;
    }

    public function addLoseCount(ITeam $team)
    {
        $this->dataByTeamId[$team->getTeamId()]['loseCount'] = $this->dataByTeamId[$team->getTeamId()]['loseCount'] + 1;
    }

    public function addPoints(ITeam $team, int $points)
    {
        $teamId = $team->getTeamId();
        $this->dataByTeamId[$teamId]['points'] = $this->dataByTeamId[$teamId]['points'] + $points;
    }

    public function addPoints2(ITeam $team, int $points)
    {
        $teamId = $team->getTeamId();
        $this->dataByTeamId[$teamId]['points2'] = $this->dataByTeamId[$teamId]['points2'] + $points;
    }

    public function addPPM(ITeam $team)
    {
        $teamId = $team->getTeamId();
        if ($this->dataByTeamId[$teamId]['matchCount'] > 0) {
            $this->dataByTeamId[$teamId]['ppm'] = round($this->_teamData[$teamId]['points'] / $this->dataByTeamId[$teamId]['matchCount'], 3);
        }
    }

    public function addResult(ITeam $teamHome, ITeam $teamGuest, $result)
    {
        // TODO: das funktioniert in der Alltime vermutlich so nicht...
        $this->dataByTeamId[$teamHome->getTeamId()]['matches'][$teamGuest->getTeamId()] = $result;
    }

    public function addPosition($teamId, int $newPosition)
    {
        if ($this->dataByTeamId[$teamId]['position']) {
            $oldPosition = $this->dataByTeamId[$teamId]['position'];
            $this->dataByTeamId[$teamId]['oldposition'] = $oldPosition;
            $this->dataByTeamId[$teamId]['positionchange'] = $this->getPositionChange($oldPosition, $newPosition);
        }
        $this->dataByTeamId[$teamId]['position'] = $newPosition;
    }

    /**
     * Liefert das Daten-Array. Key ist die TeamID.
     *
     * @return array
     */
    public function getTeamData(string $teamId): array
    {
        return $this->dataByTeamId[$teamId];
    }

    /**
     * Liefert das Daten-Array aller Teams. Das Team "spielfrei" ist bereits entfernt. Key ist die TeamID.
     *
     * @return array
     */
    public function getTeamDataArray(): array
    {
        $teamData = $this->dataByTeamId;
        $teamData = array_filter($teamData, function ($teamDataArr) {
            return !$teamDataArr['team']->isDummy();
        });

        return $teamData;
    }

    /**
     * Returns position change, either UP or DOWN or EQ.
     *
     * @param int $oldPosition
     * @param int $newPosition
     *
     * @return string UP, DOWN or EQ
     */
    private function getPositionChange($oldPosition, $newPosition)
    {
        return $oldPosition == $newPosition ? 'EQ' : ($oldPosition > $newPosition ? 'UP' : 'DOWN');
    }
}
