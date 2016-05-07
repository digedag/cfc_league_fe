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

tx_rnbase::load('tx_cfcleaguefe_table_DefaultMatchProvider');

/**
 * Match provider
 */
class tx_cfcleaguefe_table_football_MatchProvider extends tx_cfcleaguefe_table_DefaultMatchProvider {

	public function getPenalties() {
		// Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
		// für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
		if($this->getConfigurator()->getTableScope() || $this->getConfigurator()->getTableType())
			return array();

		return $this->getLeague()->getPenalties();
	}

	/**
	 * Entry point for child classes to modify fields and options for match lookup.
	 * @param array $fields
	 * @param array $options
	 */
	protected function modifyMatchFields(&$fields, &$options) {
		if($tableScope = $this->getConfigurator()->getTableScope()) {
			$round = count(t3lib_div::intExplode(',',$this->getLeague()->record['teams']));
			$round = ($round) ? $round - 1 : $round;
			if($round) {
				// Wir packen die Bedingung in ein JOINED_FIELD weil nochmal bei $currRound auf die Spalte zugegriffen wird
				$joined['value'] = $round;
				$joined['cols'] = array('MATCH.ROUND');
				$joined['operator'] = $tableScope==1 ? OP_LTEQ_INT : OP_GT_INT;
				$fields[SEARCH_FIELD_JOINED][] = $joined;
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_MatchProvider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/football/class.tx_cfcleaguefe_table_football_MatchProvider.php']);
}
