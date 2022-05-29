<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Domain\Model\BaseModel;

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
 * Interface for a team instance in tables. This is, because league tables can be calculated
 * for real teams as well as for clubs.
 */
interface ITeam
{
    public function getTeamId(): string;

    public function getUid(): int;

    /**
     * Ordnet der Instanz eine zusätzliche UID eines konkreten Teams zu.
     *
     * @param int $uid
     */
    public function addTeamUid(int $uid);

    /**
     * Liefert die UIDs aller konkreten Teams, die dieser Team-Instanz zugeordnet sind.
     *
     * @return array
     */
    public function getTeamUids(): array;

    public function getDataModel(): BaseModel;

    public function getProperty($property = null);

    public function setProperty($property, $value = null);
}
