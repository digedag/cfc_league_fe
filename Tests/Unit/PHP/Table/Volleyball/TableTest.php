<?php

namespace System25\T3sports\Tests\Table\Volleyball;

use Sys25\RnBase\Testing\BaseTestCase;
use Sys25\RnBase\Utility\Spyc;
use System25\T3sports\Model\Competition;
use System25\T3sports\Table\Builder;
use System25\T3sports\Table\ITableResult;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2013-2021 Rene Nitzsche (rene@system25.de)
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
class TableTest extends BaseTestCase
{
    public function testLeagueTableWithTwoPointSystem()
    {
        $league = $this->prepareLeague('league_volley_1');
        $league->setProperty('point_system', 0); // Punktsystem einstellen

        $matches = $league->getMatches(2);

        $params = new \ArrayObject();
        $config = $this->createConfigurations([
            'tableType' => '0',
        ], 'cfc_league_fe');

        $leagueTable = Builder::buildByCompetitionAndMatches($league, $matches, $config, $confId);
        $leagueTable->getMatchProvider()->setTeams($league->getTeams());

        $result = $leagueTable->getTableData();

        $this->assertTrue($result instanceof ITableResult, 'Got no valid result');
        $scoreLine = $result->getScores();

        $this->assertEquals(4, count($scoreLine), 'Table should contain 4 teams.');

        // Tabelle 2-P.
        // Sp Set Pkt Balls
        // T1 - 3 6:1 6:0 173:149
        // T2 - 2 3:3 2:2 134:140
        // T3 - 2 2:2 2:2 93:93
        // T4 - 3 1:6 0:6 152:170
        $this->assertEquals(1, $scoreLine[0]['teamId'], 'Team 1 should be 1. place');
        $this->assertEquals(2, $scoreLine[1]['teamId'], 'Team 2 should be 2. place');
        $this->assertEquals(3, $scoreLine[2]['teamId'], 'Team 1 should be 3. place');
        $this->assertEquals(4, $scoreLine[3]['teamId'], 'Team 4 should be 4. place');

        $this->assertEquals(6, $scoreLine[0]['points'], 'Wrong points for team 1');
        $this->assertEquals(0, $scoreLine[0]['points2'], 'Wrong points for team 1');

        $this->assertEquals(6, $scoreLine[0]['sets1'], 'Wrong sets for team 1');
        $this->assertEquals(1, $scoreLine[0]['sets2'], 'Wrong sets for team 1');

        $this->assertEquals(173, $scoreLine[0]['balls1'], 'Wrong balls for team 1');
        $this->assertEquals(149, $scoreLine[0]['balls2'], 'Wrong balls for team 1');
    }

    private function getFixturePath($filename)
    {
        return \tx_rnbase_util_Extensions::extPath('cfc_league_fe').'tests/fixtures/'.$filename;
    }

    private function makeInstances($yamlData, $clazzName)
    {
        foreach ($yamlData as $arr) {
            if (isset($arr['record']) && is_array($arr['record'])) {
                $ret[] = new $clazzName($arr['record']);
            }
        }

        return $ret;
    }

    /**
     * Returns a league from yaml file.
     *
     * @param string $leagueName
     *
     * @return Competition
     */
    private function prepareLeague($leagueName)
    {
        // Laden der Daten
        $data = Spyc::YAMLLoad($this->getFixturePath('util_LeagueTable.yaml'));
        $data = $data[$leagueName];

        $league = Competition::getCompetitionInstance($data['record']['uid'], $data['record']);
        $teams = $this->makeInstances($data['teams'], $data['teams']['clazz']);
        // TODO: so geht das nicht mehr!
        // foreach ($teams As $team)
        // tx_cfcleaguefe_models_team::addInstance($team);
        $matches = $this->makeInstances($data['matches'], $data['matches']['clazz']);
        $league->setTeams($teams);
        $league->setPenalties([]);
        $league->setMatches($matches, 2);
        // Und jetzt die Spiele
        return $league;
    }
}
