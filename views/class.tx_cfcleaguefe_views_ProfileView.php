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
tx_rnbase::load('tx_rnbase_view_Base');


/**
 * Viewklasse für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_views_ProfileView extends tx_rnbase_view_Base {
	/**
	 * Erstellen des Frontend-Outputs
	 */
	function createOutput($template, &$viewData, &$configurations, &$formatter) {
		$cObj =& $configurations->getCObj(0);

		// Die ViewData bereitstellen
		$viewData =& $configurations->getViewData();

		$profile =& $viewData->offsetGet('profile');
		if(is_object($profile))
			$out = $this->_createView($template, $profile, $configurations);
		else
			$out = 'Sorry, profile not found...';
		return $out;
	}

	function _createView($template, $profile, $configurations) {
		$out = '';
		$markerOptions = array();
		$teamId = $configurations->getParameters()->getInt('team');
		if(!$teamId) {
			// Id per TS suchen
			$teamId = intval($configurations->get('profileview.staticteam'));
		}
		if($teamId) {
			tx_rnbase::load('tx_cfcleaguefe_models_team');
			$markerOptions['team'] = tx_cfcleaguefe_models_team::getInstance($teamId);
		}
		$profileMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ProfileMarker', $markerOptions);
		$out .= $profileMarker->parseTemplate($template, $profile, $configurations->getFormatter(), 'profileview.profile.');
		return $out;
	}


	function getMainSubpart(&$viewData) {
		return '###PROFILE_VIEW###';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileView.php']);
}
?>