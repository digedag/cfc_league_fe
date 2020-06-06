<?php

namespace System25\T3sports\Tests\Table\Football;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2011-2020 Rene Nitzsche (rene@system25.de)
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
class TableTest extends \tx_rnbase_tests_BaseTestCase
{
    /**
     * FIXME.
     *
     * @_group unit
     */
    public function test_LeagueTableWithDummyTeam()
    {
        $league = $this->prepareLeague('league_2');
        // Team 2 ist der Dummy und muss entfernt werden
        $teams = $league->getTeams();
        unset($teams[1]);
        $league->setTeams(array_values($teams));
        $matches = $league->getMatches(2);
        $params = new \ArrayObject();
        $config = $this->createConfigurations([
            'tableType' => '0',
        ], 'cfc_league_fe');
        $leagueTable = \tx_cfcleaguefe_table_Builder::buildByCompetitionAndMatches($league, $matches, $config, $confId);

        // Die Teams vorher setzen, damit kein DB-Zugriff erfolgt
        $leagueTable->getMatchProvider()->setTeams(array_values($teams));
        $result = $leagueTable->getTableData();
        $this->assertTrue($result instanceof \tx_cfcleaguefe_table_ITableResult, 'Got no valid result');

        $scoreLine = $result->getScores();
        $this->assertEquals(3, count($scoreLine), 'Table should contain 3 teams.');
    }

    /**
     * FIXME.
     *
     * @_group unit
     */
    public function test_LeagueTableWithTwoPointSystem()
    {
        $league = $this->prepareLeague('league_1');
        $league->record['point_system'] = 1; // Punktsystem einstellen

        $matches = $league->getMatches(2);

        $params = new \ArrayObject();
        $config = $this->createConfigurations([
            'tableType' => '0',
        ], 'cfc_league_fe');

        $leagueTable = \tx_cfcleaguefe_table_Builder::buildByCompetitionAndMatches($league, $matches, $config, $confId);
        $leagueTable->getMatchProvider()->setTeams($league->getTeams());

        $result = $leagueTable->getTableData();

        $this->assertTrue($result instanceof \tx_cfcleaguefe_table_ITableResult, 'Got no valid result');
        $scoreLine = $result->getScores();

        $this->assertEquals(4, count($scoreLine), 'Table should contain 4 teams.');

        // Tabelle 2-P.
        // T3 - 2 3:0 4:0
        // T2 - 2 3:2 3:1
        // T1 - 3 4:2 3:3
        // T4 - 3 1:7 0:6
        $this->assertEquals(3, $scoreLine[0]['teamId'], 'Team 3 should be 1. place');
        $this->assertEquals(2, $scoreLine[1]['teamId'], 'Team 2 should be 2. place');
        $this->assertEquals(1, $scoreLine[2]['teamId'], 'Team 1 should be 3. place');
        $this->assertEquals(4, $scoreLine[3]['teamId'], 'Team 4 should be 4. place');
    }

    /**
     * FIXME.
     *
     * @_group unit
     */
    public function test_LeagueTableWithThreePointSystem()
    {
        $league = $this->prepareLeague('league_1');
        $league->record['point_system'] = 0; // Punktsystem umstellen
        $matches = $league->getMatches(2);

        $params = new \ArrayObject();
        $config = $this->createConfigurations(array(
            'tableType' => '0',
        ), 'cfc_league_fe');
        $leagueTable = \tx_cfcleaguefe_table_Builder::buildByCompetitionAndMatches($league, $matches, $config, $confId);
        $leagueTable->getMatchProvider()->setTeams($league->getTeams());
        $result = $leagueTable->getTableData();

        // Tabelle 3-P.
        // T3 - 2 3:0 6
        // T1 - 3 4:2 4
        // T2 - 2 3:2 4
        // T4 - 3 1:7 0
        $this->assertTrue($result instanceof \tx_cfcleaguefe_table_ITableResult, 'Got no valid result');
        $scoreLine = $result->getScores();

        $this->assertEquals(4, count($scoreLine), 'Table should contain 4 teams.');
        $this->assertEquals(6, $scoreLine[0]['points'], 'Team 3 should have 6 points');
        $this->assertEquals(-1, $scoreLine[0]['points2'], 'Team 3 should have no negative points in 3 point system.');
        $this->assertEquals(3, $scoreLine[0]['teamId'], 'Team 3 should be 1. place');
        $this->assertEquals(1, $scoreLine[1]['teamId'], 'Team 1 should be 2. place');
        $this->assertEquals(2, $scoreLine[2]['teamId'], 'Team 2 should be 3. place');
        $this->assertEquals(4, $scoreLine[3]['teamId'], 'Team 4 should be 4. place');
        $this->assertEquals(6, $scoreLine[0]['points'], 'Team 3 should has wrong points');
        $this->assertEquals(0, $scoreLine[3]['points'], 'Team 4 should has wrong points');
        // Alle Teams müssen bei den Minuspunkten -1 haben
        for ($i = 0, $size = count($scoreLine); $i < $size; ++$i ) {
            $this->assertEquals(-1, $scoreLine[$i]['points2'], 'Team at '.($i + 1).'. place wrong neg points');
        }
    }

    private function getFixturePath($filename)
    {
        return \tx_rnbase_util_Extensions::extPath('cfc_league_fe').'Tests/fixtures/'.$filename;
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
     * @return \tx_cfcleaguefe_models_competition
     */
    public function prepareLeague($leagueName)
    {
        // Laden der Daten
        $data = \tx_rnbase_util_Spyc::YAMLLoad($this->getFixturePath('util_LeagueTable.yaml'));
        $data = $data[$leagueName];

        $league = \tx_cfcleague_models_Competition::getCompetitionInstance($data['record']['uid'], $data['record']);
        $teams = $this->makeInstances($data['teams'], $data['teams']['clazz']);
        foreach ($teams as $team) {
            \tx_cfcleaguefe_models_team::addInstance($team);
        }
        $matches = $this->makeInstances($data['matches'], $data['matches']['clazz']);
        $league->setTeams($teams);
        $league->setPenalties([]);
        $league->setMatches($matches, 2);
        // Und jetzt die Spiele
        return $league;
    }
}
