<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_table_football_Table');

/**
 * Computes league tables for icehockey.
 * Since icehockey is very similar to football, the same code base is used.
 * Rules:
 * 2 points for winner
 * 1 point for looser if draw after extra time
 */
class tx_cfcleaguefe_table_icehockey_Table extends tx_cfcleaguefe_table_football_Table {

	/**
	 * @return tx_cfcleaguefe_table_icehockey_Configurator
	 */
	public function getConfigurator($forceNew=false) {
		if($forceNew || !is_object($this->configurator)) {
			$configuratorClass = $this->getConfValue('configuratorClass');
			$configuratorClass = $configuratorClass ? $configuratorClass : 'tx_cfcleaguefe_table_icehockey_Configurator';
			$this->configurator = tx_rnbase::makeInstance($configuratorClass, $this->getMatchProvider(), $this->configuration, $this->confId);
		}
		return $this->configurator;
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
			$this->addPoints($homeId, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
			$this->addPoints($guestId, $configurator->getPointsLoose($match->isExtraTime(), $match->isPenalty()));
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($guestId, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
			}

			$this->addWinCount($homeId);
			$this->addLoseCount($guestId);
		}
		else { // Auswärtssieg
			$this->addPoints($homeId, $configurator->getPointsLoose($match->isExtraTime(), $match->isPenalty()));
			$this->addPoints($guestId, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
			if($configurator->isCountLoosePoints()) {
				$this->addPoints2($homeId, $configurator->getPointsWin($match->isExtraTime(), $match->isPenalty()));
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
	protected function countHome(&$match, $toto, tx_cfcleaguefe_table_icehockey_Configurator $configurator) {
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

	public function getTypeID() {return 'icehockey';}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/icehockey/class.tx_cfcleaguefe_table_icehockey_Table.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/icehockey/class.tx_cfcleaguefe_table_icehockey_Table.php']);
}

?>