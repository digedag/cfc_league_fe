<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_ITableType');

/**
 * Computes league tables for football.
 */
class tx_cfcleaguefe_table_football_Table extends t3lib_svbase implements tx_cfcleaguefe_table_ITableType {

	protected $_teamData = array();

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
	public function getMatchProvider() {
		return $this->matchProvider;
	}
	/**
	 * @return tx_cfcleaguefe_table_football_Configurator
	 */
	public function getConfigurator($forceNew=false) {
		if($forceNew || !is_object($this->configurator)) {
			$configuratorClass = $this->getConfValue('configuratorClass');
			$configuratorClass = $configuratorClass ? $configuratorClass : 'tx_cfcleaguefe_table_football_Configurator';
			$this->configurator = tx_rnbase::makeInstance($configuratorClass, $this->getMatchProvider(), $this->configuration, $this->confId);
		}
		return $this->configurator;
	}
	/**
	 * Returns the final table data
	 * @return tx_cfcleaguefe_table_ITableResult
	 */
	public function getTableData() {
    $tableData = tx_rnbase::makeInstance('tx_cfcleaguefe_table_TableResult');
    $configurator = $this->getConfigurator();
		$this->initTeams($configurator);
		$this->handlePenalties($tableData); // Strafen können direkt berechnet werden
		$tableData->setMarks($this->getMatchProvider()->getTableMarks());
		$tableData->setCompetition($this->getMatchProvider()->getBaseCompetition());
		$tableData->setConfigurator($configurator);

    $rounds = $this->getMatchProvider()->getRounds();
		$comparator = $configurator->getComparator();

		if(!empty($rounds)) {
	    foreach($rounds As $round => $roundMatches) {
				$this->handleMatches($roundMatches, $configurator);
				// Jetzt die Tabelle sortieren, dafür benötigen wir eine Kopie des Arrays
				$teamData = $this->_teamData;
				$comparator->setTeamData($teamData);
				usort($teamData, array($comparator, 'compare'));
				// Nun setzen wir die Tabellenstände
				reset($teamData);
				$this->addScore4Round($round, $teamData, $tableData);
			}
		}
		else {
			// Tabelle ohne Spiele, nur die Teams zeigen
			$teamData = array_values($this->_teamData);
			reset($teamData);
			$this->addScore4Round(0, $teamData, $tableData);
		}

		return $tableData;
	}
	protected function addScore4Round($round, $teamData, $tableData) {

		for($i=0; $i < count($teamData); $i++) {
			$newPosition = $i +1;
			$team = $teamData[$i];
			if($this->_teamData[$team['teamId']]['position']) {
				$oldPosition = $this->_teamData[$team['teamId']]['position'];
				$this->_teamData[$team['teamId']]['oldposition'] = $oldPosition;
				$this->_teamData[$team['teamId']]['positionchange'] = $this->getPositionChange($oldPosition, $newPosition);
			}
			$this->_teamData[$team['teamId']]['position'] = $newPosition;
			// Jetzt die Daten des Teams übernehmen
			$tableData->addScore($round, $this->_teamData[$team['teamId']]);
		}
	}

	public function getTableWriter() {
		return tx_rnbase::makeInstance('tx_cfcleaguefe_table_football_TableWriter');
	}

  /**
   * Lädt die Namen der Teams in der Tabelle
   * @param tx_cfcleaguefe_models_competition $tableProvider
   */
  protected function initTeams(tx_cfcleaguefe_table_football_Configurator $configurator) {
  	$this->_teamData = array();
		$teams = $configurator->getTeams();
		foreach($teams As $team) {
			$teamId = $configurator->getTeamId($team);
			if(!$teamId) continue; // Ignore teams without given id
			if($team->isDummy()) continue; // Ignore dummy teams
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
			// Für die Formatierung im FE muss das Punktsystem bekannt sein.
			$this->_teamData[$teamId]['point_system'] = $configurator->getPointSystem();

			$this->_teamData[$teamId]['matchCount'] = 0;
			$this->_teamData[$teamId]['winCount'] = 0;
			$this->_teamData[$teamId]['drawCount'] = 0;
			$this->_teamData[$teamId]['loseCount'] = 0;

			// Muss das Team hervorgehoben werden?
			$markClubs = $configurator->getMarkClubs();
			if(count($markClubs)) {
				$this->_teamData[$teamId]['markClub'] = in_array($team->record['club'], $markClubs) ? 1 : 0;
			}
			$this->initTeam($teamId);
		}
	}
	/**
	 * This methods is intended to be overwritten by subclasses to init team data
	 * @param int $teamId
	 */
	protected function initTeam($teamId) {
	}

	/**
	 * Returns position change, either UP or DOWN or EQ.
	 *
	 * @param int $oldPosition
	 * @param int $newPosition
	 * @return string UP, DOWN or EQ
	 */
	protected function getPositionChange($oldPosition, $newPosition) {
		return $oldPosition == $newPosition ? 'EQ' : ($oldPosition > $newPosition ? 'UP' : 'DOWN');
	}

	/**
	 * Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
	 * für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
	 */
	protected function handlePenalties($tableData) {
		$penalties = $this->getMatchProvider()->getPenalties();
		$tableData->setPenalties($penalties);

		foreach($penalties As $penalty) {
			// Welches Team ist betroffen?
			if(array_key_exists($penalty->record['team'], $this->_teamData)) {
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


	/**
	 * Die Spiele werden zum aktuellen Tabellenstand hinzugerechnet
	 * @param array[tx_cfcleague_models_Match] $matches
	 * @param tx_cfcleaguefe_table_football_Configurator $configurator
	 */
	protected function handleMatches(&$matches, tx_cfcleaguefe_table_football_Configurator $configurator) {
		// Wir laufen jetzt über alle Spiele und legen einen Punktespeicher für jedes Team an
		foreach($matches As $match) {

			if($match->isDummy()) continue; // Ignore Dummy-Matches
			// Wie ist das Spiel ausgegangen?
			$toto = $match->getToto();
			tx_rnbase_util_Misc::callHook('cfc_league_fe','leagueTableFootball_handleMatches', 
				array('match' => &$match, 'teamdata'=>&$this->_teamData), $this);

			// Die eigentliche Punktezählung richtet sich nach dem Typ der Tabelle
			// Daher rufen wir jetzt die passende Methode auf
			switch($configurator->getTableType()) {
				case 1 :
					$this->countHome($match, $toto, $configurator);
					break;
				case 2 :
					$this->countGuest($match, $toto, $configurator);
					break;
				default:
					$this->countStandard($match, $toto, $configurator);
			}
		}

		unset($this->_teamData[0]); // Remove dummy data from teams without id
	}
	/**
	 * Zählt die Punkte für eine normale Tabelle
	 * @param tx_cfcleague_models_Match $match
	 * @param int $toto
	 */
	protected function countStandard(&$match, $toto, tx_cfcleaguefe_table_football_Configurator $configurator) {
		// Anzahl Spiele aktualisieren
		$homeId = $configurator->getTeamId($match->getHome());
		$guestId = $configurator->getTeamId($match->getGuest());
		$this->addMatchCount($homeId);
		$this->addMatchCount($guestId);
		// Für H2H modus das Spielergebnis merken
		$this->addResult($homeId, $guestId, $match->getResult());

		if($toto == 0) { // Unentschieden
			$this->addPoints($homeId, $configurator->getPointsDraw());
			$this->addPoints($guestId, $configurator->getPointsDraw());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($homeId, $configurator->getPointsDraw());
				$this->addPoints2($guestId, $configurator->getPointsDraw());
			}

			$this->addDrawCount($homeId);
			$this->addDrawCount($guestId);
		}
		elseif($toto == 1) {  // Heimsieg
			$this->addPoints($homeId, $configurator->getPointsWin());
			$this->addPoints($guestId, $configurator->getPointsLoose());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($guestId, $configurator->getPointsWin());
			}

			$this->addWinCount($homeId);
			$this->addLoseCount($guestId);
		}
		else { // Auswärtssieg
			$this->addPoints($homeId, $configurator->getPointsLoose());
			$this->addPoints($guestId, $configurator->getPointsWin());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($homeId, $configurator->getPointsWin());
			}
			$this->addLoseCount($homeId);
			$this->addWinCount($guestId);
		}

		// Jetzt die Tore summieren
		$this->addGoals($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
		$this->addGoals($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
	}

  /**
   * Zählt die Punkte für eine Heimspieltabelle. Die Ergebnisse werden als nur für die 
   * Heimmannschaft gewertet.
	 * @param tx_cfcleague_models_Match $match
	 * @param int $toto
	 */
	protected function countHome(&$match, $toto, tx_cfcleaguefe_table_football_Configurator $configurator) {
		$homeId = $configurator->getTeamId($match->getHome());
		$guestId = $configurator->getTeamId($match->getGuest());
		// Anzahl Spiele aktualisieren
		$this->addMatchCount($homeId);
		$this->addResult($homeId, $guestId, $match->getGuest());

		if($toto == 0) { // Unentschieden
			$this->addPoints($homeId, $configurator->getPointsDraw());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($homeId, $configurator->getPointsDraw());
			}
			$this->addDrawCount($homeId);
		}
		elseif($toto == 1) {  // Heimsieg
			$this->addPoints($homeId, $configurator->getPointsWin());
			$this->addWinCount($homeId);
		}
		else { // Auswärtssieg
			$this->addPoints($homeId, $configurator->getPointsLoose());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($homeId, $configurator->getPointsWin());
			}
			$this->addLoseCount($homeId);
		}
		// Jetzt die Tore summieren
		$this->addGoals($homeId, $match->getGoalsHome(), $match->getGoalsGuest());
	}

	/**
	 * Zählt die Punkte für eine Auswärtstabelle. Die Ergebnisse werden als nur für die 
   * Gastmannschaft gewertet.
	 * @param tx_cfcleague_models_Match $match
	 * @param int $toto
	 */
	protected function countGuest(&$match, $toto, tx_cfcleaguefe_table_football_Configurator $configurator) {
		$homeId = $configurator->getTeamId($match->getHome());
		$guestId = $configurator->getTeamId($match->getGuest());
		// Anzahl Spiele aktualisieren
		$this->addMatchCount($guestId);
		$this->addResult($homeId, $guestId, $match->getGuest());

		if($toto == 0) { // Unentschieden
			$this->addPoints($guestId, $configurator->getPointsDraw());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($guestId, $configurator->getPointsDraw());
			}
			$this->addDrawCount($guestId);
		}
		elseif($toto == 1) {  // Heimsieg
			$this->addPoints($guestId, $configurator->getPointsLoose());
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($guestId, $configurator->getPointsWin());
			}
			$this->addLoseCount($guestId);
		}
		else { // Auswärtssieg
			$this->addPoints($guestId, $configurator->getPointsWin());
			$this->addWinCount($guestId);
		}

		// Jetzt die Tore summieren
		$this->addGoals($guestId, $match->getGoalsGuest(), $match->getGoalsHome());
	}

	protected function addResult($homeId, $guestId, $result) {
		$this->_teamData[$homeId]['matches'][$guestId] = $result;
	}
	/**
	 * Addiert Punkte zu einem Team
	 */
	protected function addPoints($teamId, $points) {
		$this->_teamData[$teamId]['points'] = $this->_teamData[$teamId]['points'] + $points;
	}

	/**
	 * Addiert negative Punkte zu einem Team. Diese Funktion wird nur im 2-Punkte-System
	 * verwendet.
	 */
	protected function addPoints2($teamId, $points) {
		$this->_teamData[$teamId]['points2'] = $this->_teamData[$teamId]['points2'] + $points;
	}
	/**
	 * Addiert die absolvierten Spiele zu einem Team
 	 */
	protected function addMatchCount($teamId) {
		$this->_teamData[$teamId]['matchCount'] = $this->_teamData[$teamId]['matchCount'] + 1;
	}

	protected function addWinCount($teamId) {
		$this->_teamData[$teamId]['winCount'] = $this->_teamData[$teamId]['winCount'] + 1;
	}
	protected function addDrawCount($teamId) {
		$this->_teamData[$teamId]['drawCount'] = $this->_teamData[$teamId]['drawCount'] + 1;
	}
	protected function addLoseCount($teamId) {
		$this->_teamData[$teamId]['loseCount'] = $this->_teamData[$teamId]['loseCount'] + 1;
	}

	public function getTypeID() {return 'football';}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_Table.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_Table.php']);
}
