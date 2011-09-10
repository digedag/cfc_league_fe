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

tx_rnbase::load('tx_cfcleaguefe_util_league_TableProvider');

/**
 * The default table provider can handle match data for a single competition of type league
 */
class tx_cfcleaguefe_util_league_DefaultTableProvider implements tx_cfcleaguefe_util_league_TableProvider{

	private $league;
	private $parameters;
	private $conf;
	private $confId;
	private $currRound;
	private $matches;
	private $markClubs = null;
	
	function __construct($parameters, $configurations, $league, $confId='') {
		$this->setLeague($league);
		$this->setConfigurations($configurations, $confId);
		$this->setParameters($parameters);
		$this->init();
	}

	function getPointsWin() {
		return $this->cfgPointSystem == '1' ? 2 : 3;
	}

	function getPointsDraw() {
		return 1;
	}
	function getPointsLoose() {
		return 0;
	}
	function getCompareMethod() {
		return $this->cfgCompareMethod ? $this->cfgCompareMethod : 'compareTeams';
	}
	function isCountLoosePoints() {
		return $this->cfgPointSystem == '1'; // im 2-Punktesystem die Minuspunkte sammeln
	}
	function getChartClubs(){
		return t3lib_div::intExplode(',',$this->getConfigurations()->get($this->confId.'chartClubs'));
	}
	function getMarkClubs(){
		return $this->markClubs ? $this->markClubs : t3lib_div::intExplode(',',$this->getConfigurations()->get($this->confId.'markClubs'));
	}
	public function setMarkClubs($clubUids) {
		$this->markClubs = $clubUids;
	}
	function getTableType() {
		return $this->cfgTableType;
	}
	function getPenalties() {
		// Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
		// für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
		if($this->cfgTableType || $this->cfgTableScope) 
			return array();

		return $this->getLeague()->getPenalties();
	}
	function getTeamId($team) {
		return $team->uid;
	}
	
	function getTeams() {
		return $this->getLeague()->getTeams(true);
	}
	function getMatches() {
		if(is_array($this->matches))
			return $this->matches;
		$matchSrv = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchTable = $matchSrv->getMatchTable();
		$matchTable->setStatus($this->cfgLiveTable ? '1,2' : 2); //Status der Spiele
		$matchTable->setCompetitions($this->getLeague()->uid);
		if($this->currRound) {
			// Nur bis zum Spieltag anzeigen
			$matchTable->setMaxRound($this->currRound);
		}
		$fields = array();
		$options = array();
		$options['orderby']['MATCH.ROUND'] = 'asc';
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
		$this->matches = $matchSrv->search($fields, $options);
//    return $this->getLeague()->getMatches(2, $this->cfgTableScope);
		return $this->matches;
	}
	function getRounds() {
    $rounds = array();
    $matches = $this->getMatches();
    for($i=0, $cnt = count($matches); $i < $cnt; $i ++) {
    	$match = $matches[$i];
      $rounds[$match->record['round']][] = $match;
    }
    return $rounds;
	}
	function getMaxRounds() {
		return count($this->getLeague()->getRounds());
	}

	protected function init() {
		// Der TableScope wirkt sich auf die betrachteten Spiele (Hin-Rückrunde) aus
		$parameters = $this->getParameters();
		$this->cfgTableScope = $this->getConfigurations()->get($this->confId.'tablescope');
		if($this->getConfigurations()->get($this->confId.'tablescopeSelectionInput')) {
			$this->cfgTableScope = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : $this->cfgTableScope;
		}

		// tabletype means home or away matches only
		$this->cfgTableType = $this->getConfigurations()->get($this->confId.'tabletype');
		if($this->getConfigurations()->get($this->confId.'tabletypeSelectionInput')) {
			$this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
		}

		$this->cfgPointSystem = $this->getLeague()->record['point_system'];
		if($this->getConfigurations()->get($this->confId.'pointSystemSelectionInput')) {
			$this->cfgPointSystem = is_string($parameters->offsetGet('pointsystem')) ? intval($parameters->offsetGet('pointsystem')) : $this->cfgPointSystem;
		}
		$this->cfgLiveTable = intval($this->getConfigurations()->get($this->confId.'showLiveTable'));
		$this->cfgCompareMethod = $this->getConfigurations()->get(($this->confId ? $this->confId : 'leaguetable.').'compareMethod');
	}

	/**
	 * Current competition if used
	 * @return tx_cfcleaguefe_models_competition
	 */
	protected function getLeague() {
		return $this->league;
	}
	protected function setLeague($league) {
		$this->league = $league;
	}
	/**
	 * current config
	 *
	 * @return tx_rnbase_configurations
	 */
	protected function getConfigurations() {
		return $this->conf;
	}
	/**
	 * current fe parameters
	 *
	 * @return array_object
	 */
	protected function getParameters() {
		return $this->parameters;
	}
	protected function setParameters($parameters) {
		$this->parameters = $parameters;
	}
	protected function setConfigurations($configurations, $confId) {
		$this->conf = $configurations;
		$this->confId = $confId;
	}
	public function setCurrentRound($round) {
		$this->currRound = $round;
	}
	/**
	 * Set matches to use. Useful for unit testing. 
	 *
	 * @param array[tx_cfcleaguefe_models_match] $matches
	 */
	public function setMatches($matches) {
		$this->matches = $matches;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/league/class.tx_cfcleaguefe_util_league_DefaultTableProvider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/league/class.tx_cfcleaguefe_util_league_DefaultTableProvider.php']);
}

?>