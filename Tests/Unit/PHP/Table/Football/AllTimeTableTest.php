<?php

namespace System25\T3sports\Tests\Table\Football;

use Sys25\RnBase\Testing\BaseTestCase;
use Sys25\RnBase\Testing\TestUtility;
use Sys25\RnBase\Utility\Extensions;
use Sys25\RnBase\Utility\Spyc;
use System25\T3sports\Model\Competition;
use System25\T3sports\Table\Builder;
use System25\T3sports\Table\ITableResult;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2011-2022 Rene Nitzsche (rene@system25.de)
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
class AllTimeTableTest extends BaseTestCase
{
    public function testLeagueTableAllTime()
    {
        $league1 = $this->prepareLeague('league_1');
        $matches1 = $league1->getMatches(2);

        $league = $this->prepareLeague('league_2');
        // Team 2 ist der Dummy und muss entfernt werden
        $teams = $league->getTeams();
        unset($teams[1]);
        $league->setTeams(array_values($teams));
        $matches = $league->getMatches(2);
        $config = TestUtility::createConfigurations([
        ], 'cfc_league_fe');
        $confId = '';
        $leagueTable = Builder::buildByCompetitionAndMatches($league, array_merge($matches, $matches1), $config, $confId);

        // Die Teams vorher setzen, damit kein DB-Zugriff erfolgt
        $leagueTable->getMatchProvider()->setTeams(array_merge(array_values($teams), array_values($league1->getTeams())), true);

        $result = $leagueTable->getTableData();
        $this->assertTrue($result instanceof ITableResult, 'Got no valid result');

        $scoreLine = $result->getScores();
        $this->assertEquals(4, count($scoreLine), 'Table should contain 4 teams.');

        $expected = [
            0 => ['teamId' => 'c_3', 'points' => 8, 'goals1' => 6, 'goals2' => 0],
            1 => ['teamId' => 'c_1', 'points' => 5, 'goals1' => 7, 'goals2' => 3],
            2 => ['teamId' => 'c_2', 'points' => 3, 'goals1' => 3, 'goals2' => 2],
            3 => ['teamId' => 'c_4', 'points' => 0, 'goals1' => 1, 'goals2' => 12],
        ];
        foreach ($scoreLine as $idx => $score) {
            $this->assertEquals($expected[$idx]['teamId'], $score['team']->getTeamId(), sprintf('TeamID of team %d wrong.', $idx));
            $this->assertEquals($expected[$idx]['points'], $score['points'], sprintf('Points of team %d wrong.', $idx));
            $this->assertEquals($expected[$idx]['goals1'], $score['goals1'], sprintf('Goals 1 of team %d wrong.', $idx));
            $this->assertEquals($expected[$idx]['goals2'], $score['goals2'], sprintf('Goals 2 of team %d wrong.', $idx));
        }
    }

    private function getFixturePath($filename)
    {
        return __DIR__.'/../../../../fixtures/'.$filename;
        //        return Extensions::extPath('cfc_league_fe').'Tests/fixtures/'.$filename;
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
        $matches = $this->makeInstances($data['matches'], $data['matches']['clazz']);
        $league->setTeams($teams);
        $league->setPenalties([]);
        $league->setMatches($matches, 2);

        // Und jetzt die Spiele
        return $league;
    }
}
