<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
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


tx_rnbase::load('tx_rnbase_util_Misc');

/**
 * Keine echte Registry, aber eine zentrale Klasse für den Zugriff auf verschiedene
 * Services
 */
class tx_cfcleaguefe_util_ServiceRegistry {
	/**
	 * Liefert die vorhandenen Statistic-Services für die Auswahl im Flexform
	 *
	 */
	public static function lookupStatistics($config) {
		$services = tx_rnbase_util_Misc::lookupServices('cfcleague_statistics');
		foreach ($services As $subtype => $info) {
			$title = $info['title'];
			if(substr($title, 0, 4) === 'LLL:') {
				$title = $GLOBALS['LANG']->sL($title);
			}
			$config['items'][] = array($title, $subtype);
		}
		return $config;
	}

	/**
	 * Liefert den Profile-Service
	 * @return tx_cfcleaguefe_ProfileService
	 */
	public static function getProfileService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'profile');
	}

	/**
	 * Liefert den Match-Service
	 * @return tx_cfcleaguefe_MatchService
	 */
	public static function getMatchService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'match');
	}
	/**
	 * Liefert den Team-Service
	 * @return tx_cfcleaguefe_TeamService
	 */
	public static function getTeamService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'team');
	}
	/**
	 * Liefert den Wettbewerbsservice
	 * @return tx_cfcleaguefe_CompetitionService
	 */
	public static function getCompetitionService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'competition');
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ServiceRegistry.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ServiceRegistry.php']);
}
