<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Rene Nitzsche (rene@system25.de)
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

require_once(PATH_t3lib.'class.t3lib_svbase.php');
tx_rnbase::load('tx_cfcleaguefe_table_ITableType');

/**
 * Computes league tables for football.
 */
class tx_cfcleaguefe_table_TableFootball extends t3lib_svbase implements tx_cfcleaguefe_table_ITableType {

	/**
	 * Set configuration
	 * @param tx_rnbase_configurations $configuration
	 * @param string confId
	 * @return void
	 */
	public function setConfigurations($configuration, $confId){
		$this->configuration = $configuration;
		$this->confId = $confId;
	}
	protected function getConfValue($key) {
		if(!is_object($this->configuration)) return false;
		return $this->configuration->get($this->confId.$key);
	}
	/**
	 * Set match provider
	 * @param tx_cfcleaguefe_table_IMatchProvider $matchProvider
	 * @return void
	 */
	public function setMatchProvider(tx_cfcleaguefe_table_IMatchProvider $matchProvider) {
		$this->matchProvider = $matchProvider;
	}
	/**
	 * @return tx_cfcleaguefe_table_IMatchProvider
	 */
	protected function getMatchProvider() {
		return $this->matchProvider;
	}
	/**
	 * Returns the final table data
	 * @return tx_cfcleaguefe_table_ITableResult
	 */
	public function getTableData() {
		$configuratorClass = $this->getConfValue('configuratorClass');
		$configuratorClass = $configuratorClass ? $configuratorClass : 'tx_cfcleaguefe_table_football_Configurator';
		$configurator = tx_rnbase::makeInstance($configuratorClass, $this->getMatchProvider(), $this->configuration, $this->confId);
		$this->initTeams($configurator);
		$this->handlePenalties(); // Strafen können direkt berechnet werden

    $xyData = Array();
		$rounds = $this->getMatchProvider()->getRounds();
	}

	public function getTableWriter() {
		
	}
	public function getTCALabel() {
		return 'Football';
	}

  /**
   * Lädt die Namen der Teams in der Tabelle
   * @param tx_cfcleaguefe_models_competition $tableProvider
   */
  protected function initTeams(tx_cfcleaguefe_table_football_Configurator $configurator) {
		$teams = $configurator->getTeams();
		foreach($teams As $team) {
			$teamId = $configurator->getTeamId($team);
			if(!$teamId) continue; // Ignore teams without given id
//			if($team->isDummy()) continue; // Ignore dummy teams
			if(array_key_exists($teamId, $this->_teamData)) continue;

			$this->_teamData[$teamId]['team'] = $team;
			$this->_teamData[$teamId]['teamId'] = $teamId;
			$this->_teamData[$teamId]['teamName'] = $team->record['name'];
			$this->_teamData[$teamId]['teamNameShort'] = $team->record['short_name'];
			$this->_teamData[$teamId]['clubId'] = $team->record['club'];
			$this->_teamData[$teamId]['points'] = 0;
			// Bei 3-Punktssystem muss mit -1 initialisiert werden, damit der Marker später ersetzt wird
			// isLooseCount sollte zunächst über den matchProvider geholt werden
			// Später sollte eine Steuerklasse zwischengeschaltet sein, die ggf. die Information
			// aus der GUI holt.
			$this->_teamData[$teamId]['points2'] = ($configurator->isCountLoosePoints()) ? 0 : -1;
			$this->_teamData[$teamId]['goals1'] = 0;
			$this->_teamData[$teamId]['goals2'] = 0;
			$this->_teamData[$teamId]['goals_diff'] = 0;
			$this->_teamData[$teamId]['position'] = 0;
			$this->_teamData[$teamId]['oldposition'] = 0;
			$this->_teamData[$teamId]['positionchange'] = 'EQ';

			$this->_teamData[$teamId]['matchCount'] = 0;
			$this->_teamData[$teamId]['winCount'] = 0;
			$this->_teamData[$teamId]['drawCount'] = 0;
			$this->_teamData[$teamId]['loseCount'] = 0;

			// Muss das Team hervorgehoben werden?
			$markClubs = $configurator->getMarkClubs();
			if(count($markClubs)) {
				$this->_teamData[$teamId]['markClub'] = in_array($team->record['club'], $markClubs) ? 1 : 0;
			}
		}
	}

	/**
	 * Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
	 * für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
	 */
	protected function handlePenalties() {
		$penalties = $this->getMatchProvider()->getPenalties();

		foreach($penalties As $penalty) {
			// Welches Team ist betroffen?
			if(array_key_exists($penalty->record['team'], $this->_teamData)) {
//    t3lib_div::debug($penalty, 'tx_cfcleaguefe_util_LeagueTable'); // TODO: Remove me!
				// Die Strafe wird für den View mit abgespeichert
				// Falls es eine Korrektur ist, dann nicht speichern
				if(!$penalty->isCorrection())
					$this->_teamData[$penalty->record['team']]['penalties'][] = $penalty;
				// Die Punkte abziehen
				$this->_teamData[$penalty->record['team']]['points'] -= $penalty->record['points_pos'];
				$this->_teamData[$penalty->record['team']]['points2'] += $penalty->record['points_neg'];

				$this->addGoals($penalty->record['team'], ($penalty->record['goals_pos'] * -1), $penalty->record['goals_neg']);

				$this->_teamData[$penalty->record['team']]['matchCount'] += $penalty->record['matches'];
				$this->_teamData[$penalty->record['team']]['winCount'] += $penalty->record['wins'];
				$this->_teamData[$penalty->record['team']]['drawCount'] += $penalty->record['draws'];
				$this->_teamData[$penalty->record['team']]['loseCount'] += $penalty->record['loses'];

				// Den Zwangsabstieg tragen wir nur ein, damit der in die Sortierung eingeht 
				if($penalty->record['static_position'])
					$this->_teamData[$penalty->record['team']]['last_place'] = $penalty->record['static_position'];
			}
		}
	}
	/**
	 * Addiert Tore zu einem Team
	 */
	protected function addGoals($teamId, $goals1, $goals2) {
		$this->_teamData[$teamId]['goals1'] = $this->_teamData[$teamId]['goals1'] + $goals1;
		$this->_teamData[$teamId]['goals2'] = $this->_teamData[$teamId]['goals2'] + $goals2;
		$this->_teamData[$teamId]['goals_diff'] = $this->_teamData[$teamId]['goals1'] - $this->_teamData[$teamId]['goals2'];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_TableFootball.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_TableFootball.php']);
}

?>