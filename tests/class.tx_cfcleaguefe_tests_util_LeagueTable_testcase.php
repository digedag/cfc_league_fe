<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');


tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_rnbase_util_Spyc');
//tx_rnbase::load('tx_cfcleaguefe_models_team');
tx_rnbase::load('tx_cfcleaguefe_util_league_DefaultTableProvider');
tx_rnbase::load('tx_cfcleague_models_Competition');
tx_rnbase::load('tx_cfcleaguefe_util_LeagueTable');

class tx_cfcleaguefe_tests_LeagueTable_testcase extends tx_phpunit_testcase {
	function test_dummyTeam() {
		$league = $this->prepareLeague('league_2');

		// Team 2 ist der Dummy und muss entfernt werden
		$teams = $league->getTeams();
		unset($teams[1]);
		$league->setTeams(array_values($teams));
		$matches = $league->getMatches(2);

		$params = new ArrayObject();
		$config = new tx_rnbase_configurations();
		$config->_dataStore->offsetSet('tableType', '0');
		$prov = new tx_cfcleaguefe_util_league_DefaultTableProvider($params, $config,$league);
		$prov->setMatches($league->getMatches(2));

		$leagueTable = new tx_cfcleaguefe_util_LeagueTable();
		$result = $leagueTable->generateTable($prov);
		$this->assertTrue(is_array($result), 'Got no result array');
		$this->assertEquals(3, count($result), 'Table should contain 3 teams, but is: ' . count($result));
	}
	function test_twoPointSystem() {
		$league = $this->prepareLeague('league_1');
		$league->record['point_system'] = 1; // Punktsystem umstellen

		$params = new ArrayObject();
		$config = new tx_rnbase_configurations();
		$config->_dataStore->offsetSet('tableType', '0');
		$prov = new tx_cfcleaguefe_util_league_DefaultTableProvider($params, $config,$league);
		$prov->setMatches($league->getMatches(2));

		$leagueTable = new tx_cfcleaguefe_util_LeagueTable();
		$result = $leagueTable->generateTable($prov);

		$this->assertTrue(is_array($result), 'Got no result array');
		$this->assertEquals(4, count($result), 'Table should contain 4 teams, but is: ' . count($result));

		// Tabelle 2-P.
		// T3 - 2 3:0 4:0
		// T2 - 2 3:2 3:1
		// T1 - 3 4:2 3:3
		// T4 - 3 1:7 0:6
		$this->assertEquals(3, $result[0]['teamId'], 'Team 3 should be 1. place');
		$this->assertEquals(2, $result[1]['teamId'], 'Team 2 should be 2. place');
		$this->assertEquals(1, $result[2]['teamId'], 'Team 1 should be 3. place');
		$this->assertEquals(4, $result[3]['teamId'], 'Team 4 should be 4. place');
	}

	function test_threePointSystem() {
		$league = $this->prepareLeague('league_1');
		$league->record['point_system'] = 0; // Punktsystem einstellen

		$params = new ArrayObject();
		$config = new tx_rnbase_configurations();
		$config->_dataStore->offsetSet('tableType', '0');
		$prov = new tx_cfcleaguefe_util_league_DefaultTableProvider($params, $config,$league);
		$prov->setMatches($league->getMatches(2));

		$leagueTable = new tx_cfcleaguefe_util_LeagueTable();
		$result = $leagueTable->generateTable($prov);

		// Tabelle 3-P.
		// T3 - 2 3:0 6
		// T1 - 3 4:2 4
		// T2 - 2 3:2 4
		// T4 - 3 1:7 0
		$this->assertTrue(is_array($result), 'Got no result array');
		$this->assertEquals(4, count($result), 'Table should contain 4 teams, but is: ' . count($result));
		$this->assertEquals(3, $result[0]['teamId'], 'Team 3 should be 1. place');
		$this->assertEquals(1, $result[1]['teamId'], 'Team 1 should be 2. place');
		$this->assertEquals(2, $result[2]['teamId'], 'Team 2 should be 3. place');
		$this->assertEquals(4, $result[3]['teamId'], 'Team 4 should be 4. place');
		$this->assertEquals(6, $result[0]['points'], 'Team 3 should has wrong points');
		$this->assertEquals(0, $result[3]['points'], 'Team 4 should has wrong points');
		// Alle Teams müssen bei den Minuspunkten -1 haben
		for($i = 0, $size = count($result); $i < $size; $i++) {
			$this->assertEquals(-1, $result[$i]['points2'], 'Team at '. ($i + 1) . '. place wrong neg points');
		}
	}

	function getFixturePath($filename) {
		return t3lib_extMgm::extPath('cfc_league_fe').'tests/fixtures/'.$filename;
	}
	function makeInstances($yamlData, $clazzName) {
		// Sicherstellen, daß die Klasse geladen wurde
		tx_rnbase::load($clazzName);
		foreach($yamlData As $arr) {
			if(is_array($arr['record']))
				$ret[] = new $clazzName($arr['record']);
		}
		return $ret;
	}
	/**
	 * Returns a league from yaml file
	 *
	 * @param string $leagueName
	 * @return tx_cfcleaguefe_models_competition
	 */
	function prepareLeague($leagueName) {
		// Laden der Daten
		$data = tx_rnbase_util_Spyc::YAMLLoad($this->getFixturePath('util_LeagueTable.yaml'));
		$data = $data[$leagueName];
		
		$league = &tx_cfcleague_models_Competition::getInstance($data['record']['uid'], $data['record']);
		$teams = $this->makeInstances($data['teams'],$data['teams']['clazz']);
		foreach ($teams As $team)
			tx_cfcleaguefe_models_team::addInstance($team);
		$matches = $this->makeInstances($data['matches'],$data['matches']['clazz']);
		$league->setTeams($teams);
		$league->setPenalties(array());
		$league->setMatches($matches,2);
		// Und jetzt die Spiele
		return $league;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/tests/class.tx_cfcleaguefe_tests_util_LeagueTable_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/tests/class.tx_cfcleaguefe_tests_util_LeagueTable_testcase.php']);
}
?>