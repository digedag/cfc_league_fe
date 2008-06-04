<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-08 Rene Nitzsche (rene@system25.de)
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
tx_div::load('tx_rnbase_action_BaseIOC');

//require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_ScopeController.php');

/**
 * Controller für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_actions_ProfileView extends tx_rnbase_action_BaseIOC {
	static $exclude = array();

	/**
	 * handle request
	 *
	 * @param arrayobject $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param arrayobject $viewData
	 * @return string
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData) {

		$fields = array();
		$options = array();
		$this->initSearch($fields, $options, $parameters, $configurations, $firstChar);

		$service = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
		$profiles = $service->search($fields, $options);
		$profile = count($profiles) ? $profiles[0] : null;
		if(!$profile)
			return 'No profile found!';

		$viewData->offsetSet('profile', $profile);
		return $out;
	}

	protected function initSearch(&$fields, &$options, &$parameters, &$configurations, $firstChar) {
		// ggf. die Konfiguration aus der TS-Config lesen
		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'profileview.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'profileview.options.');

		$options['limit'] = 1;
		if(intval($configurations->get('profileview.excludeAlreadyDisplayed'))) {
			// Doppelte Anzeige von Personen vermeiden
			if(count(self::$exclude)) {
				$fields['COMPANY.UID'][OP_NOTIN_INT] = implode(',', self::$exclude);
			}
		}
		else {
			// Parameter prüfen
			$value = $parameters->offsetGet('profileId');
			if(intval($value)) {
				$fields['PROFILE.UID'][OP_EQ_INT] = intval($value);
			}
		}
	}

	function getTemplateName() {return 'profile';}
	function getViewClassName() { return 'tx_cfcleaguefe_views_ProfileView'; }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileView.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileView.php']);
}

?>