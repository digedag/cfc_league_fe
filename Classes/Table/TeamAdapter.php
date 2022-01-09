<?php

namespace System25\T3sports\Table;

use InvalidArgumentException;
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
 * Adapter for teams in league tables that wrappes teams or clubs.
 */
class TeamAdapter implements ITeam
{
    /** @var Team */
    private $team;
    /** @var Club */
    private $club;
    private $isTeam = true;

    public function __construct($teamOrClub)
    {
        if ($teamOrClub instanceof Team) {
            $this->team = $teamOrClub;
        } elseif ($teamOrClub instanceof Team) {
            $this->club = $teamOrClub;
            $this->isTeam = false;
        } else {
            throw new InvalidArgumentException('Unsupported team instance given.');
        }
    }

    public function getUid(): int
    {
        return $this->getInstance()->getUid();
    }

    public function getTeamId(): string
    {
        return sprintf('%s_%d', $this->isTeam ? 't' : 'c', $this->isTeam ? $this->team->getUid() : $this->club->getUid());
    }

    private function getInstance(): BaseModel
    {
        return $this->isTeam ? $this->team : $this->club;
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
}
