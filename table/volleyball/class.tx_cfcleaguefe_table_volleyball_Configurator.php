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

tx_rnbase::load('tx_cfcleaguefe_table_football_Configurator');

/**
 * Configurator for volleyball league tables. 
 */
class tx_cfcleaguefe_table_volleyball_Configurator extends tx_cfcleaguefe_table_football_Configurator {
	const POINT_SYSTEM_2POINT = 0;
	const POINT_SYSTEM_3POINT = 1;
	/**
	 * Whether or not loose points are count
	 * @return boolean
	 */
	public function isCountLoosePoints() {
		// Im Volleyball werden zukünftig auch Minuspunkte gezählt.
		return $this->getPointSystem() == POINT_SYSTEM_2POINT;
	}

	/**
	 * Für die Punktberechnung ist im Volleyball die Satzverteilung relevant.
	 */
	public function getPointsWin($winSetsHome, $winSetsGuest) {
//	tx_rnbase_util_Debug::debug($this->getPointSystem(), 'volley_Conf'.__LINE__);
		$points = 2;
		if($this->getPointSystem()==self::POINT_SYSTEM_3POINT) {
			$points = $this->isSplitResult($winSetsHome, $winSetsGuest) ? 2 : 3;
		}
		return $points;
	}
	protected function isSplitResult($winSetsHome, $winSetsGuest) {
		// Wenn die Satzdifferenz 1 ist, werden die Punkte geteilt
		return abs($winSetsHome - $winSetsGuest) == 1;
	}
	public function getPointsDraw($afterExtraTime, $afterPenalty) {
		return 0; // Unentschieden gibt es eigentlich nicht...
	}
	public function getPointsLoose($winSetsHome, $winSetsGuest) {
		$points = 0;
		if($this->getPointSystem()==self::POINT_SYSTEM_3POINT) {
			// Wenn die Satzdifferenz 1 ist, werden die Punkte geteilt
			$points = $this->isSplitResult($winSetsHome, $winSetsGuest) ? 1 : 0;
		}
		return $points;
	}
	/**
	 * Quelle: http://de.wikipedia.org/wiki/Punkteregel#Eishockey
	 * 0- 3-Punktsystem
	 * 1- 2-Punktsystem
	 */
	public function getPointSystem() {
		return $this->cfgPointSystem;
	}
	/**
	 * @return tx_cfcleaguefe_table_volleyball_IComparator
	 */
	public function getComparator() {
		$compareClass = $this->cfgComparatorClass ? $this->cfgComparatorClass : 'tx_cfcleaguefe_table_volleyball_Comparator';
		$comparator = tx_rnbase::makeInstance($compareClass);
		if(!is_object($comparator))
			throw new Exception('Could not instanciate comparator: '.$compareClass);

		tx_rnbase::load('tx_cfcleaguefe_table_volleyball_IComparator');
		if(!($comparator instanceof tx_cfcleaguefe_table_volleyball_IComparator))
			throw new Exception('Comparator is no instance of tx_cfcleaguefe_table_volleyball_IComparator: '.get_class($comparator));
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


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/volleyball/class.tx_cfcleaguefe_volleyball_football_Configurator.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/volleyball/class.tx_cfcleaguefe_table_volleyball_Configurator.php']);
}

?>