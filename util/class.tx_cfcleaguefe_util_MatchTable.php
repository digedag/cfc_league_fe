<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');
tx_div::load('tx_cfcleaguefe_search_Builder');

/**
 * This is a facade to build search queries for matches from database.
 */
class tx_cfcleaguefe_util_MatchTable  {
	var $_saisonIds;
	var $_groupIds;
	var $_teamgroupIds;
	var $_compIds;
	var $_roundIds;
	var $_clubIds;
	var $_homeClubIds;
	var $_guestClubIds;
	var $_refereeIds;

	var $_teamIds;
	var $_daysPast;
	var $_daysAhead;
	var $_dateStart; // bestimmter Starttermin
	var $_dateEnd; // bestimmter Endtermin
	var $_limit; // Anzahl Spiele limitieren
	var $_orderbyDate = false;
	var $_orderbyDateDesc = false;
	var $_status;
	var $_ticker;
	var $_report;
	var $_ignoreDummy;
	var $_compTypes; // Wettbewerbstypen
	var $_compObligation; // Pflichtwettbewerbe
	var $_pidList;
	
	public function tx_cfcleaguefe_util_MatchTable() {
	}
	/**
	 * This is the final call to get all search fields and options
	 *
	 * @param array $fields
	 * @param array $options
	 */
	public function getFields(&$fields, &$options) {
		tx_cfcleaguefe_search_Builder::setField($fields,'COMPETITION.SAISON', OP_IN_INT, $this->_saisonIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'COMPETITION.AGEGROUP', OP_INSET_INT, $this->_groupIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.COMPETITION', OP_IN_INT, $this->_compIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.ROUND', OP_IN_INT, $this->_roundIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'TEAM1.CLUB', OP_IN_INT, $this->_homeClubIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'TEAM2.CLUB', OP_IN_INT, $this->_guestClubIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.REFEREE', OP_IN_INT, $this->_refereeIds);
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.ROUND', OP_LTEQ_INT, $this->_maxRound);
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.ROUND', OP_GTEQ_INT, $this->_minRound);

		$this->handleClubInternals($fields);

		tx_cfcleaguefe_search_Builder::buildMatchByClub($fields, $this->_clubIds);
		tx_cfcleaguefe_search_Builder::buildMatchByTeam($fields, $this->_teamIds);
		tx_cfcleaguefe_search_Builder::buildMatchByTeamAgeGroup($fields, $this->_teamgroupIds);
		// Wird der Zeitraum eingegrenzt?
		if(intval($this->_daysPast) || intval($this->_daysAhead)) {
			// Wenn in eine Richtung eingegrenzt wird und in der anderen Richtung kein
			// Wert gesetzt wurde, dann wird dafür das aktuelle Datum verwendet.
			// Auf jeden Fall wird immer in beide Richtungen eingegrenzt
			$cal = tx_div::makeInstance('tx_rnbase_util_Calendar');
			$cal->clear(CALENDAR_SECOND);
			$cal->clear(CALENDAR_HOUR);
			$cal->clear(CALENDAR_MINUTE);
			$cal->add(CALENDAR_DAY_OF_MONTH,$this->_daysPast * -1);
			$fields['MATCH.DATE'][OP_GTEQ_INT] = $cal->getTime();
//			$where .= ' tx_cfcleague_games.date >= ' . $cal->getTime();

			$cal = tx_div::makeInstance('tx_rnbase_util_Calendar');
			$cal->clear(CALENDAR_SECOND);
			$cal->clear(CALENDAR_HOUR);
			$cal->clear(CALENDAR_MINUTE);
			$cal->add(CALENDAR_DAY_OF_MONTH,$this->_daysAhead);
			$fields['MATCH.DATE'][OP_LT_INT] = $cal->getTime();
		}
		// bestimmtes Startdatum
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.DATE',OP_GTEQ_INT,$this->_dateStart);
		// bestimmtes Enddatum
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.DATE',OP_LT_INT,$this->_dateEnd);
		if($this->_ignoreDummy) {
			tx_cfcleaguefe_search_Builder::setField($fields,'TEAM1.DUMMY',OP_EQ_INT,0);
			tx_cfcleaguefe_search_Builder::setField($fields,'TEAM2.DUMMY',OP_EQ_INT,0);
		}
		// Spielstatus
		tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.STATUS',OP_IN_INT,$this->_status);
		if($this->_ticker) tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.LINK_TICKER',OP_EQ_INT,1);
		if($this->_report) tx_cfcleaguefe_search_Builder::setField($fields,'MATCH.LINK_REPORT',OP_EQ_INT,1);

		tx_cfcleaguefe_search_Builder::setField($fields,'COMPETITION.TYPE',OP_IN_INT,$this->_compTypes);
		if(intval($this->_compObligation)) {
			if(intval($this->_compObligation) == 1)
	  		$fields['COMPETITION.OBLIGATION'][OP_EQ_INT] = 1;
	  	else
	  		$fields['COMPETITION.OBLIGATION'][OP_NOTEQ_INT] = 1;
		}

		// Match limit
		if(intval($this->_limit))
			$options['limit'] = intval($this->_limit);
		if($this->_orderbyDate)
			$options['orderby']['MATCH']['DATE'] = $this->_orderbyDateDesc ? 'DESC' : 'ASC';
		if($this->_pidList)
			$options['pidlist'] = $this->_pidList;
	}

	/**
	 * Set orderby date match.
	 *
	 * @param boolean $asc true for ascending, false for descending
	 */
	function setOrderByDate($asc = true){
		$this->_orderbyDate = true;
		$this->_orderbyDateDesc = $asc;
	}

	/**
	 * Wenn Spiele unterschiedlicher Vereine gesucht werden, dann müssen vereinsinterne
	 * Duelle ausgeschlossen werden.
	 * @param $fields
	 */
	private function handleClubInternals(&$fields) {
		$homeClubs = t3lib_div::intExplode(',', $this->_homeClubIds);
		$clubs = array_merge($homeClubs, t3lib_div::intExplode(',', $this->_guestClubIds));
		$clubs = array_unique($clubs);
		if(count($clubs) > 1) {
			// Interne Spiele der Vereine ausschließen
			$fields[SEARCH_FIELD_CUSTOM] = 't1.club != t2.club';
		}
	}
	/**
	 * Find matches by given scope array
	 *
	 * @param array $scope
	 */
  function setScope($scope){
		$this->setSaisons($scope['SAISON_UIDS']);
		$this->setAgeGroups($scope['GROUP_UIDS']);
		$this->setCompetitions($scope['COMP_UIDS']);
		$this->setRounds($scope['ROUND_UIDS']);
		$this->setClubs($scope['CLUB_UIDS']);
		$this->setCompetitionObligation($scope['COMP_OBLIGATION']);
		$this->setCompetitionTypes($scope['COMP_TYPES']);
		$this->setTeamAgeGroups($scope['TEAMGROUP_UIDS']);

		// Maybe we need it later...
  	$this->_scopeArr = $scope;
  }
	/**
	 * Search for matches of competitions with a specific age groups
	 *
	 * @param string $uids
	 */
	function setAgeGroups($uids){
		$this->_groupIds = $uids;
	}
	/**
	 * Search for matches of teams with a specific age groups
	 *
	 * @param string $uids
	 */
	function setTeamAgeGroups($uids){
		$this->_teamgroupIds = $uids;
	}
	/**
	 * Search for matches of specific competitions
	 *
	 * @param string $uids
	 */
	function setCompetitions($uids){
		$this->_compIds = $uids;
	}
	/**
	 * Search for matches of specific competition rounds
	 *
	 * @param string $uids
	 */
  function setRounds($uids){
    $this->_roundIds = $uids;
  }
  /**
   * Search for matches up to a specific matchround (including)
   *
   * @param int $round
   */
  function setMaxRound($round){
    $this->_maxRound = $round;
  }
  /**
   * Search for matches from a specific matchround (including)
   *
   * @param int $round
   */
  function setMinRound($round){
    $this->_minRound = $round;
  }
  /**
	 * Search for matches of specific clubs
	 *
	 * @param string $uids
	 */
	function setClubs($uids){
		$this->_clubIds = $uids;
	}
	/**
	 * Returns clubs ids
	 *
	 * @return string
	 */
	function getClubs() {
		return $this->_clubIds;
	}
  /**
	 * Search for home matches of specific clubs
	 *
	 * @param string $uids
	 */
	function setHomeClubs($uids){
		$this->_homeClubIds = $uids;
	}
  /**
	 * Search for guest matches of specific clubs
	 *
	 * @param string $uids
	 */
	function setGuestClubs($uids){
		$this->_guestClubIds = $uids;
	}
	/**
	 * Wether or not to include dummy matches
	 *
	 * @param boolean $flag
	 */
	function setIgnoreDummy($flag=true){
		$this->_ignoreDummy = $flag;
	}
	/**
	 * Search for matches of specific saisons
	 *
	 * @param string $uids
	 */
  function setSaisons($uids){
    $this->_saisonIds = $uids;
  }
  /**
	 * Search for matches of specific teams
	 *
	 * @param string $teamUids
	 */
  function setTeams($teamUids){
    $this->_teamIds = $teamUids;
  }
  /**
	 * Search for matches of specific referees
	 *
	 * @param string $refUids
	 */
  function setReferees($refUids){
    $this->_refereeIds = $refUids;
  }
  /**
	 * Grenzt den Zeitraum für den Spielplan auf genaue Termine ein
	 * @param $start_date int Timestamp des Startdatums
	 * @param $end_date int Timestamp des Enddatum
	 */
	function setDateRange($start_date, $end_date){
		$this->_dateStart = $start_date;
		$this->_dateEnd = $end_date;
	}
	/**
	 * Grenzt den Zeitraum für den Spielplan ein
	 * @param $daysPast int Anzahl Tage in der Vergangenheit
	 * @param $daysAhead int Anzahl Tage in der Zukunft
	 */
	function setTimeRange($daysPast = 0, $daysAhead = 0){
		$this->_daysPast = $daysPast;
		$this->_daysAhead = $daysAhead;
	}
	/**
	 * Limit the number of returned matches.
	 * @param $limit 
	 */
	function setLimit($limit){
		$this->_limit = $limit;
	}
	/**
	 * Set the state of returned matches.
	 * @param $status 
	 */
	function setStatus($status){
		$this->_status = $status;
	}
	/**
	 * Matches with live ticker only.
	 * @param $flag 
	 */
	function setLiveTicker($flag = true){
		$this->_ticker = $flag;
	}
	/**
	 * Matches with report only.
	 * @param $flag 
	 */
	function setReport($flag = true){
		$this->_report = $flag;
	}

	/**
	 * Whether or not matches belong to obligate competitions.
	 * @param $value 0 - all, 1 - obligate only, 2 - no obligates
	 */
	function setCompetitionObligation($value){
		$this->_compObligation = $value;
	}
	/**
	 * Whether or not matches belong to specific competitions types.
	 * @param $value comma separated competition types
	 */
	function setCompetitionTypes($value){
		$this->_compTypes = $value;
	}
	/**
	 * Findet nur Spiele von bestimmten Seiten
	 */
	function setPidList($pidList){
		$this->_pidList = $pidList;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchTable.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchTable.php']);
}

?>