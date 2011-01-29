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


/**
 * Configurator for football league tables. 
 * Diese Klasse erweitert den MatchProvider und liefert Daten zur Steuerung der Tabellenberechnung.
 */
class tx_cfcleaguefe_table_football_Configurator {
	/**
	 * @var tx_cfcleaguefe_table_IMatchProvider
	 */
	private $matchProvider;
	private $configurations;
	private $confId;
	public function __construct(tx_cfcleaguefe_table_IMatchProvider $matchProvider, $configurations, $confId) {
		$this->matchProvider = $matchProvider;
		$this->configurations = $configurations;
		$this->confId = $confId;
		$this->init();
	}
	/**
	 * 2-point-system
	 * @return boolean
	 */
	public function isCountLoosePoints() {
		return $this->cfgPointSystem == '1'; // im 2-Punktesystem die Minuspunkte sammeln
	}

	public function getTeams() {
		return $this->getMatchProvider()->getTeams();
	}
	/**
	 * Returns the unique key for a team. For alltime table this can be club uid.
	 * @param tx_cfcleague_models_Team $team
	 */
	public function getTeamId($team) {
		if($this->getConfValue('teamMode') == 'club') {
			return $team->record['club'];
		}
		return $team->getUid();
	}

	/**
	 * @return tx_cfcleaguefe_table_IMatchProvider
	 */
	protected function getMatchProvider() {
		return $this->matchProvider;
	}
	protected function getConfValue($key) {
		if(!is_object($this->configuration)) return false;
		return $this->configuration->get($this->confId.$key);
	}

	public function getMarkClubs(){
		return $this->markClubs ? $this->markClubs : t3lib_div::intExplode(',',$this->getConfValue('markClubs'));
	}
	/**
	 * Returns the table type. This means which matches to use: all, home or away matches only
	 * @return int 0-normal, 1-home, 2-away
	 */
	public function getTableType() {
		return $this->cfgTableType;
	}
	public function getPointsWin() {
		return $this->cfgPointSystem == '1' ? 2 : 3;
	}
	public function getPointsDraw() {
		return 1;
	}
	public function getPointsLoose() {
		return 0;
	}
	/**
	 * @return tx_cfcleaguefe_table_football_IComparator
	 */
	public function getComparator() {
		$compareClass = $this->cfgComparatorClass ? $this->cfgComparatorClass : 'tx_cfcleaguefe_table_football_Comparator';
		$comparator = tx_rnbase::makeInstance($compareClass);
		if(!is_object($comparator))
			throw new Exception('Could not instanciate comparator: '.$compareClass);

		tx_rnbase::load('tx_cfcleaguefe_table_football_IComparator');
		if(!($comparator instanceof tx_cfcleaguefe_table_football_IComparator))
			throw new Exception('Comparator is no instance of tx_cfcleaguefe_table_football_IComparator: '.get_class($comparator));
		return $comparator;
	}

	protected function init() {
		// Der TableScope wirkt sich auf die betrachteten Spiele (Hin-Rückrunde) aus
		$parameters = $this->configurations->getParameters();
		$this->cfgTableScope = $this->getConfValue('tablescope');
		if($this->getConfValue('tablescopeSelectionInput')) {
			$this->cfgTableScope = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : $this->cfgTableScope;
		}

		// tabletype means home or away matches only
		$this->cfgTableType = $this->getConfValue('tabletype');
		if($this->getConfValue('tabletypeSelectionInput')) {
			$this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
		}

		$this->cfgPointSystem = $this->getMatchProvider()->getBaseCompetition()->record['point_system'];
		if($this->getConfValue('pointSystemSelectionInput')) {
			$this->cfgPointSystem = is_string($parameters->offsetGet('pointsystem')) ? intval($parameters->offsetGet('pointsystem')) : $this->cfgPointSystem;
		}
		$this->cfgLiveTable = intval($this->getConfValue('showLiveTable'));
		$this->cfgComparatorClass = $this->getConfValue('comparatorClass');
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_Configurator.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_Configurator.php']);
}

?>