<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_IMatchProvider');

/**
 * Match provider
 */
class tx_cfcleaguefe_table_DefaultMatchProvider implements tx_cfcleaguefe_table_IMatchProvider {
	/**
	 * @var tx_rnbase_configurations
	 */
	private $configurations;
	private $confId;
	private $league = 0;
	private $scope;
	/**
	 * @var tx_cfcleague_util_MatchTableBuilder
	 */
	private $matchTable;

	public function __construct($configurations, $confId) {
		$this->configurations = $configurations;
		$this->confId = $confId;
	}
	public function setMatchTable($matchTable) {
		$this->matchTable = $matchTable;
	}
	public function setScope($scope) {
		$this->scope = $scope;
	}
	public function setConfigurator($configurator) {
		$this->configurator = $configurator;
	}
	/**
	 * 
	 * @return tx_cfcleaguefe_table_IConfigurator
	 */
	public function getConfigurator() {
		return $this->configurator;
	}
	/**
	 * Return all teams or clubs of given matches. It returns teams for simple league tables.
	 * But for alltime table, teams are useless. It exists one saison only! 
	 * So for alltime table clubs are returned.
	 *
	 * @return array[tx_cfcleague_models_Team]
	 */
	public function getTeams() {
		if(is_array($this->teams))
			return $this->teams;
		if($this->useClubs()) {
			$this->teams = $this->getClubs();
			return $this->teams;
		}
		$this->teams = array();
		$matches = $this->getMatches();
		for($i=0, $cnt = count($matches); $i < $cnt; $i++) {
			$match = $matches[$i];
			$team = $match->getHome();
			if($team->getUid() && !array_key_exists($team->getUid(), $this->teams)) {
				$this->teams[$team->getUid()] = $team;
			}
			$team = $match->getGuest();
			if($team->getUid() && !array_key_exists($team->getUid(), $this->teams)) {
				$this->teams[$team->uid] = $team;
			}
		}
		return $this->teams;
	}
	private function getClubs() {
		$teams = array();
		for($i=0, $cnt = count($this->matches); $i < $cnt; $i++) {
			$match = $this->matches[$i];
			$club = $match->getHome()->getClub();
			if($club->uid && !array_key_exists($club->uid, $teams)) {
				$club->record['club'] = $club->uid; // necessary for mark clubs
				$teams[$club->uid] = $club;
			}
			$club = $match->getGuest()->getClub();
			if($club->uid && !array_key_exists($club->uid, $teams)) {
				$club->record['club'] = $club->uid; // necessary for mark clubs
				$teams[$club->uid] = $club;
			}
		}
		return $teams;
	}
	private function useClubs() {
		// Wenn die Tabelle über mehrere Saisons geht, dann müssen Clubs verwendet werden
		$matchTable = $this->getMatchTable();
		$fields = array();
		$options = array();
		$matchTable->getFields($fields, $options);
		$options['what'] = 'count(DISTINCT COMPETITION.SAISON) AS `saison`';
		$compSrv = tx_cfcleague_util_ServiceRegistry::getCompetitionService();
		$result = $compSrv->search($fields, $options);
		return intval($result[0]['saison']) > 1;
	}
	/**
	 * @return tx_cfcleague_models_Competition
	 */
	public function getBaseCompetition() {
		return $this->getLeague();
	}
	/**
	 * If table is written for a single league, this league will be returned.
	 * return tx_cfcleague_models_Competition or false
	 */
	protected function getLeague() {
		if($this->league === 0) {
			// Den Wettbewerb müssen wir initial auf Basis des Scopes laden.
			$this->league = self::getLeagueFromScope($this->scope);
		}
		return $this->league;
	}
	public function setLeague($league) {
		$this->league = $league;
	}
	
	public function getRounds() {
    $rounds = array();
    $matches = $this->getMatches();
    for($i=0, $cnt = count($matches); $i < $cnt; $i ++) {
    	$match = $matches[$i];
      $rounds[$match->record['round']][] = $match;
    }
    return $rounds;
	}
	/**
	 * Liefert die Gesamtzahl der Spieltage einer Saison
	 */
	public function getMaxRounds() {
		// TODO: Das geht nur, wenn der Scope auf eine Liga zeigt
		$league = $this->getLeague();
		return $league ? count($league->getRounds()) : 0;
	}

	/**
	 * @return tx_cfcleague_util_MatchTableBuilder
	 */
	private function getMatchTable() {
		if(is_object($this->matchTable))
			return $this->matchTable;

		// Todo: Was ist, wenn MatchTable nicht extern gesetzt wurde??
		$matchSrv = tx_cfcleague_util_ServiceRegistry::getMatchService();
		$matchTable = $matchSrv->getMatchTableBuilder();
		$matchTable->setStatus($this->getMatchStatus()); //Status der Spiele
		// Der Scope zählt. Wenn da mehrere Wettbewerbe drin sind, ist das ein Problem 
		// in der Plugineinstellung. Somit funktionieren aber auch gleich die Alltimetabellen
		$matchTable->setScope($this->scope);
//		$matchTable->setCompetitions($this->getLeague()->uid);

		if($this->currRound) {
			// Nur bis zum Spieltag anzeigen
			$matchTable->setMaxRound($this->currRound);
		}
		if(is_array($this->scope)) {
			// Die Runde im Scope wird gesondert gesetzt.
			unset($scopeArr['ROUND_UIDS']);
			$matchTable->setScope($this->scope);
		}
		$this->matchTable = $matchTable;
		return $this->matchTable;
	}

	private function getMatchStatus() {
		$status = $this->configurations->get($this->confId.'filter.matchstatus');
		if(!$status)
			$status = $this->configurations->get($this->confId.'filter.livetable') ? '1,2' : '2';
		return $status;
	}
	public function setMatches($matches) {
		$this->matches = $matches;
	}
	/**
	 * Liefert die Spiele, die für die Berechnung der Tabelle notwendig sind.
	 * Hier werden auch die Einstellungen des Configurators verwendet.
	 */
	protected function getMatches() {
		if(is_array($this->matches))
			return $this->matches;

		$matchTable = $this->getMatchTable();
		$fields = array();
		$options = array();
		$matchTable->getFields($fields, $options);
		// Bei der Spielrunde gehen sowohl der TableScope (Hin-Rückrunde) als auch
		// die currRound ein: Rückrundentabelle bis Spieltag X -> JOINED Field
		// Haben wir eine $currRound

		$this->modifyMatchFields($fields, $options);
		//$options['debug'] = 1;
		$this->matches = tx_cfcleague_util_ServiceRegistry::getMatchService()->search($fields, $options);
		return $this->matches;
	}
	/**
	 * Entry point for child classes to modify fields and options for match lookup.
	 * @param array $fields
	 * @param array $options
	 */
	protected function modifyMatchFields(&$fields, &$options) {
		
	}

	public function getPenalties() {
		// Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
		// für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
//		if($this->cfgTableType || $this->cfgTableScope) 
//			return array();

//		$this->getConfigurator()->
		return $this->getLeague()->getPenalties();
	}
	/**
	 * Returns the first league found from given scope
	 * @param array $scopeArr
	 */
	public static function getLeagueFromScope($scopeArr) {
		$matchSrv = tx_cfcleague_util_ServiceRegistry::getMatchService();
		$matchTable = $matchSrv->getMatchTableBuilder();
		$matchTable->setScope($scopeArr);

		$fields = array();
		$options = array();
		$matchTable->getFields($fields, $options);
		$options['what'] = 'distinct competition';

		$result = tx_cfcleague_util_ServiceRegistry::getMatchService()->search($fields, $options);
		// es wird immer nur der 1. Wettbewerb verwendet
		$leagueUid = count($result) ? $result[0]['competition'] : false;
		if(!$leagueUid) throw new Exception('Could not find a valid competition.');
		$league = tx_cfcleague_models_Competition::getInstance($leagueUid);
		if(!$league->isValid())
			throw new Exception('Competition with uid '.intval($leagueUid). ' is not valid!');
		return $league;
	}
	/**
	 * Returns the table marks used to mark some posititions in table. It should be normally 
	 * retrieved from competition.
	 * @return string
	 */
	public function getTableMarks(){
		return $this->getLeague()->getTableMarks();
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_DefaultMatchProvider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_DefaultMatchProvider.php']);
}

?>