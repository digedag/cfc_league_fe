<?php

namespace System25\T3sports\Tests\Table;

use Sys25\RnBase\Testing\BaseTestCase;
use System25\T3sports\Model\Team;
use System25\T3sports\Table\TeamAdapter;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2013-2022 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * @group unit
 */
class TeamAdapterTest extends BaseTestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testAdapter($teamData, $useClubs, $expTeamId, $expClubUid)
    {
        $team = new Team($teamData);
        $adapter = new TeamAdapter($team, $useClubs);
        $this->assertEquals($expTeamId, $adapter->getTeamId());
        $this->assertEquals($expClubUid, $adapter->getClubUid());
    }

    public function testAddTeam()
    {
        $team = new Team(['uid' => 2, 'club' => 4, 'name' => 'Team A']);
        $adapter = new TeamAdapter($team, true);
        $adapter->addTeamUid(5);
        $adapter->addTeamUid(15);
        $teamUids = $adapter->getTeamUids();
        $this->assertCount(3, $teamUids);
        $this->assertContains(2, $teamUids);
        $this->assertContains(5, $teamUids);
        $this->assertContains(15, $teamUids);
    }

    public function getTestData()
    {
        return [
            [['uid' => 5, 'club' => 4, 'name' => 'Team A'], false, 't_5', 0],
            [['uid' => 5, 'club' => 4, 'name' => 'Team A'], true, 'c_4', 4],
        ];
    }
}
