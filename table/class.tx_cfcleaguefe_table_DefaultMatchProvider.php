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
		$options['what'] = 'COMP.SAISON';
//		$this->matches = $matchSrv->search($fields, $options);

		return false;
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
	private function getLeague() {
		if($this->league === 0) {
			$matchTable = $this->getMatchTable();

			$fields = array();
			$options = array();
			$matchTable->getFields($fields, $options);
			$options['what'] = 'distinct competition';
//			$options['debug'] = 1;

			$result = tx_cfcleague_util_ServiceRegistry::getMatchService()->search($fields, $options);
			// es wird immer nur der 1. Wettbewerb verwendet
			$leagueUid = count($result) ? $result[0]['competition'] : false;
			if(!$leagueUid) throw new Exception('Could not find a valid competition.');
			$this->league = tx_cfcleague_models_Competition::getInstance($leagueUid);
			if(!$this->league->isValid())
				throw new Exception('Competition with uid '.intval($leagueUid). ' is not valid!');
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
		$matchTable->setCompetitions($this->getLeague()->uid);
		if($this->currRound) {
			// Nur bis zum Spieltag anzeigen
			$matchTable->setMaxRound($this->currRound);
		}
	}

	private function getMatchStatus() {
		$status = $this->configurations->get($this->confId.'filter.matchstatus');
		if(!$status)
			$status = $this->configurations->get($this->confId.'filter.livetable') ? '1,2' : '2';
		return $status;
	}
	private function getMatches() {
		if(is_array($this->matches))
			return $this->matches;

		$matchTable = $this->getMatchTable();
		$fields = array();
		$options = array();
		$matchTable->getFields($fields, $options);
		// Bei der Spielrunde gehen sowohl der TableScope (Hin-Rückrunde) als auch
		// die currRound ein: Rückrundentabelle bis Spieltag X -> JOINED Field
		// Haben wir eine $currRound
		if($this->cfgTableScope) {
			$round = count(t3lib_div::intExplode(',',$this->getLeague()->record['teams']));
			$round = ($round) ? $round - 1 : $round;
			if($round) {
				// Wir packen die Bedingung in ein JOINED_FIELD weil nochmal bei $currRound auf die Spalte zugegriffen wird
				$joined['value'] = $round;
				$joined['cols'] = array('MATCH.ROUND');
				$joined['operator'] = $this->cfgTableScope==1 ? OP_LTEQ_INT : OP_GT_INT;
				$fields[SEARCH_FIELD_JOINED][] = $joined;
			}
		}
//		$options['debug'] = 1;
		$this->matches = tx_cfcleague_util_ServiceRegistry::getMatchService()->search($fields, $options);
//    return $this->getLeague()->getMatches(2, $this->cfgTableScope);
		return $this->matches;
	}

	public function getPenalties() {
		// Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
		// für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
//		if($this->cfgTableType || $this->cfgTableScope) 
//			return array();

		return $this->getLeague()->getPenalties();
	}
	/**
	 * Returns the table type to be used for matches. It should be normally retrieved from
	 * competitions.
	 * @return string
	 */
	public function getTableType(){
		// Alle Wettbewerbe laden und den Typ ermitteln
		return $this->getLeague()->getTableType();
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