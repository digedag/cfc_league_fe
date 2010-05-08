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


require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
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
	function lookupStatistics($config) {
		$services = self::lookupServices('cfcleague_statistics');
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
	static function getProfileService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'profile');
	}
	
	/**
	 * Liefert den Match-Service
	 * @return tx_cfcleaguefe_MatchService
	 */
	static function getMatchService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'match');
	}
	/**
	 * Liefert den Team-Service
	 * @return tx_cfcleaguefe_TeamService
	 */
	static function getTeamService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'team');
	}
	/**
	 * Liefert den Wettbewerbsservice
	 * @return tx_cfcleaguefe_CompetitionService
	 */
	static function getCompetitionService() {
		return tx_rnbase_util_Misc::getService('cfcleague_data', 'competition');
	}

	/**
	 *
	 * @deprecated
	 * @see tx_rnbase_util_Misc::getService
	 */
	static function getService($type, $subType) {
    $srv = t3lib_div::makeInstanceService($type, $subType);
    if(!is_object($srv)) {
    	tx_rnbase::load('tx_rnbase_util_Misc');
      return tx_rnbase_util_Misc::mayday('Service ' . $type . ' - ' . $subType . ' not found!');;
    }
    return $srv;
	}
	/**
	 * Returns an array with all subtypes for given service key.
	 *
	 * @param string $type
	 * @deprecated
	 * @see tx_rnbase_util_Misc::lookupServices
	 */
	static function lookupServices($serviceType) {
		global $T3_SERVICES;
		$priority = array(); // Remember highest priority
		$services = array();
		if(is_array($T3_SERVICES[$serviceType])) {
			foreach($T3_SERVICES[$serviceType] As $key => $info) {
				if($info['available'] AND (!isset($priority[$info['subtype']]) || $info['priority'] >= $priority[$info['subtype']]) ) {
					$priority[$info['subtype']] = $info['priority'];
					$services[$info['subtype']] = $info;
				}
			}
		}
		return $services;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ServiceRegistry.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ServiceRegistry.php']);
}
?>