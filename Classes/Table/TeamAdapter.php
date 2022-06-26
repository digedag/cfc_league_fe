<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Domain\Model\BaseModel;
use System25\T3sports\Model\Club;
use System25\T3sports\Model\Team;

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
 * Adapter for teams in league tables that wraps teams or clubs.
 *
 * Eine Tabelle wird für Teams oder Vereine erstellt. Ein Spiel ist aber immer
 * mit Teams verbunden. Innerhalb eines Wettbewerbs ist das Team eindeutig.
 * Wenn wir mit mehreren Wettbewerben arbeiten, dann bekommen wir Spiele, bei
 * denen mehrere Teams zu einem Verein gehören.
 * Es ist Quatsch das Team per Club zu initialisieren. Es muss nur sichergestellt sein,
 * das für alle Teams eines Clubs der selbe TeamAdapter verwendet wird. Das geht natürlich nur,
 * wenn im Team der Club gesetzt ist.
 */
class TeamAdapter implements ITeam
{
    /** @var Team */
    private $team;
    private $teamUids = [];
    /** @var Club */
    private $clubUid;
    private $useClubs = false;
    private $isDummy = false;

    public function __construct(Team $team, bool $useClubs = false)
    {
        $this->team = $team;
        $this->teamUids[] = $team->getUid();
        $this->isDummy = $team->isDummy();
        $this->useClubs = $useClubs;
        $clubUid = (int) $team->getClubUid();
        $this->clubUid = $useClubs ? $clubUid : 0;
    }

    public function getUid(): int
    {
        return $this->getInstance()->getUid();
    }

    public function addTeamUid(int $uid)
    {
        $this->teamUids[] = $uid;
    }

    public function getTeamUids(): array
    {
        return $this->teamUids;
    }

    public function getClubUid(): int
    {
        return $this->clubUid;
    }

    public function getTeamId(): string
    {
        return sprintf('%s_%d', $this->useClubs ? 'c' : 't', $this->useClubs ? $this->clubUid : $this->team->getUid());
    }

    private function getInstance(): BaseModel
    {
        return $this->team;
    }

    public function getProperty($property = null)
    {
        return $this->getInstance()->getProperty($property);
    }

    public function setProperty($property, $value = null)
    {
        return $this->getInstance()->setProperty($property, $value);
    }

    public function getDataModel(): BaseModel
    {
        return $this->getInstance();
    }

    public function isDummy(): bool
    {
        return $this->isDummy;
    }

    public function isOutOfCompetition(): bool
    {
        return $this->isTeam ? $this->getInstance()->isOutOfCompetition() : false;
    }
}
