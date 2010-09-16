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

tx_rnbase::load('tx_cfcleaguefe_util_league_DefaultTableProvider');

/**
 * The a table provider used to build table from all given matches
 */
class tx_cfcleaguefe_util_league_AllTimeTableProvider extends tx_cfcleaguefe_util_league_DefaultTableProvider{

	private $matches;
	private $teams;
	
	function tx_cfcleaguefe_util_league_AllTimeTableProvider($parameters, $configurations, $matches, $confId='') {
		$this->setConfigurations($configurations, $confId);
		$this->setParameters($parameters);
		
		$this->matches = $matches;
		$this->init();
	}

	/**
	 * Return all clubs of given matches. Since this is an alltime table, teams are useless. It exists one saison
	 * only! Thats why we return clubs. 
	 *
	 * @return array[tx_cfcleaguefe_models_club]
	 */
	function getTeams() {
		if(is_array($this->teams))
			return $this->teams;
		$this->teams = array();
		for($i=0, $cnt = count($this->matches); $i < $cnt; $i++) {
			$match = $this->matches[$i];
			$club = $match->getHome()->getClub();
			if($club->uid && !array_key_exists($club->uid, $this->teams)) {
				$club->record['club'] = $club->uid; // necessary for mark clubs
				$this->teams[$club->uid] = $club;
			}
			$club = $match->getGuest()->getClub();
			if($club->uid && !array_key_exists($club->uid, $this->teams)) {
				$club->record['club'] = $club->uid; // necessary for mark clubs
				$this->teams[$club->uid] = $club;
			}
		}
		return $this->teams;
	}
	
	function getRounds() {
    return array(0 => $this->matches);
	}
	
	function getPenalties() {
		return array(); // Bring hier wohl nichts...
	}

	protected function init() {
		$parameters = $this->getParameters();
		// Der TableScope wirkt sich auf die betrachteten Spiele (Hin-Rückrunde) aus
		$this->cfgTableScope = 0; // Normale Tabelle
		$this->cfgTableType = $this->getConfigurations()->get($this->confId.'tabletype');
		if($this->getConfigurations()->get($this->confId.'tabletypeSelectionInput')) {
			$this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
		}
		$this->cfgPointSystem = $this->getConfigurations()->get($this->confId.'pointSystem');
		if($this->getConfigurations()->get($this->confId.'pointSystemSelectionInput')) {
			$this->cfgPointSystem = $parameters->offsetGet('pointsystem') ? $parameters->offsetGet('pointsystem') : $this->cfgPointSystem;
		}
	}

	function getTeamId($team) {
		return $team->record['club'];
	}
	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/league/class.tx_cfcleaguefe_util_league_AllTimeTableProvider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/league/class.tx_cfcleaguefe_util_league_AllTimeTableProvider.php']);
}

?>