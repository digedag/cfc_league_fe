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
 * Builder class for league tables. 
 */
class tx_cfcleaguefe_table_Builder {
	/**
	 * 
	 * @param tx_cfcleague_model_Competition $league
	 * @param array $matches
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return tx_cfcleaguefe_table_ITableType
	 */
	public static function buildByCompetitionAndMatches($league, $matches, $configurations, $confId) {
		$tableType = $league->getSports();

		tx_rnbase::load('tx_cfcleaguefe_table_Factory');
		$prov = tx_cfcleaguefe_table_Factory::createMatchProvider($tableType, $configurations, $confId);
		$prov->setLeague($league);
		$prov->setMatches($matches);
		// Der Scope muss gesetzt werden, damit die Team gefunden werden
		$prov->setScope(array('COMP_UIDS' => $league->getUid()));
		$table = tx_cfcleaguefe_table_Factory::createTableType($tableType);
		$table->setConfigurations($configurations, $confId.'tablecfg.');
		// MatchProvider und Configurator müssen sich gegenseitig kennen
		$table->setMatchProvider($prov);
		$c = $table->getConfigurator(true);
		$prov->setConfigurator($c);

		return $table;
	}
	
	/**
	 * 
	 * @param array $scopeArr
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return tx_cfcleaguefe_table_ITableType
	 */
	public static function buildByRequest($scopeArr, $configurations, $confId) {
		// Zuallererst muss die Sportart ermittelt werden, weil diese für
		// die weiteren Klassen notwendig ist.
		// die Sportart wird daher static auf Basis des Scopes ermittelt
		tx_rnbase::load('tx_cfcleaguefe_table_DefaultMatchProvider');
		$tableType = tx_cfcleaguefe_table_DefaultMatchProvider::getLeagueFromScope($scopeArr)->getSports();

		// Vereine dürfen die Spielauswahl nicht einschränken
		unset($scopeArr['CLUB_UIDS']);

		tx_rnbase::load('tx_cfcleaguefe_table_Factory');
		$prov = tx_cfcleaguefe_table_Factory::createMatchProvider($tableType, $configurations, $confId);
		$prov->setScope($scopeArr);
		// Der Provider liefert alle realen Daten auf Basis der Daten
		// Für Sondertabellen können diese Werte später überschrieben werden. Hier folgt
		// dann die Integration der GUI
		// Der Provider kennt die Spiele, also könnte er auch die Sportart kennen...
		$table = tx_cfcleaguefe_table_Factory::createTableType($tableType);

		$table->setConfigurations($configurations, $confId.'tablecfg.');
		// MatchProvider und Configurator müssen sich gegenseitig kennen
		$table->setMatchProvider($prov);
		$c = $table->getConfigurator(true);
		$prov->setConfigurator($c);
		return $table;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_Builder.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/table/class.tx_cfcleaguefe_table_Builder.php']);
}

?>