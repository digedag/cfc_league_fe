<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_IConfigurator');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');


/**
 * Configurator for football league tables.
 * Diese Klasse erweitert den MatchProvider und liefert Daten zur Steuerung der Tabellenberechnung.
 */
class tx_cfcleaguefe_table_football_Configurator implements tx_cfcleaguefe_table_IConfigurator {
	/**
	 * @var tx_cfcleaguefe_table_IMatchProvider
	 */
	protected $matchProvider;
	protected $configurations;
	protected $confId;
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
			return $team->getProperty('club');
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
		if(!is_object($this->configurations)) return false;
		return $this->configurations->get($this->confId.$key);
	}
	/**
	 * @return tx_cfcleague_models_Competition
	 */
	public function getCompetition() {
		return $this->getMatchProvider()->getBaseCompetition();
	}

	public function getRunningClubGames() {
	    if (!$this->runningGamesClub) {
            $values = [];

            foreach($this->getMatchProvider()->getRounds() as $round) {
                /* @var $match tx_cfcleaguefe_table_DefaultMatchProvider */
                foreach ($round as $matchs) {

                    if($matchs->isRunning()) {
                        $values[] = $matchs->getHome()->getClub()->getUid();
                        $values[] = $matchs->getGuest()->getClub()->getUid();
                    }
                }
            }
            $this->runningGamesClub = $values;
        }
       return $this->runningGamesClub;
    }

	public function getMarkClubs(){
		if(!$this->markClubs) {
			$values = $this->getConfValue('markClubs');
			if(!$values)
				$values = $this->configurations->get('markClubs'); // used from flexform
			$this->markClubs = Tx_Rnbase_Utility_Strings::intExplode(',',$values);
		}
		return $this->markClubs;
	}
	/**
	 * Returns the table type. This means which matches to use: all, home or away matches only
	 * @return int 0-normal, 1-home, 2-away
	 */
	public function getTableType() {
		return $this->cfgTableType;
	}
	/**
	 * Returns the table scope. This means which matches to use: all, first saison part or second saison part only
	 * @return int 0-normal, 1-first, 2-second
	 */
	public function getTableScope() {
		return $this->cfgTableScope;
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
	public function getPointSystem() {
		return $this->cfgPointSystem;
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
		// Wir bleiben mit den alten falschen TS-Einstellungen kompatibel und fragen
		// beide Einstellungen ab
		if($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tablescopeSelectionInput')) {
			$this->cfgTableScope = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : $this->cfgTableScope;
		}

		// tabletype means home or away matches only
		$this->cfgTableType = $this->getConfValue('tabletype');
		if($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tabletypeSelectionInput')) {
			$this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
		}

		$this->cfgPointSystem = $this->getMatchProvider()->getBaseCompetition()->record['point_system'];
		if($this->configurations->get('pointSystemSelectionInput') || $this->getConfValue('pointSystemSelectionInput')) {
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