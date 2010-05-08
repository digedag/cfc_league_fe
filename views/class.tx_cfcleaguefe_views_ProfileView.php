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
require_once(t3lib_extMgm::extPath('dam') . 'lib/class.tx_dam_media.php');

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
			$out = $this->_createView($template, $profile, $formatter);
		else
			$out = 'Sorry, profile not found...';
		return $out;
	}

	function _createView($template, &$profile, &$formatter) {
		$out = '';
		$profileMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ProfileMarker');
		$out .= $profileMarker->parseTemplate($template, $profile, $formatter, 'profileview.profile.');
		return $out;
	}


	function getMainSubpart(&$viewData) {
		return '###PROFILE_VIEW###';
	}
  
//t3lib_div::debug($damPics['files'][603] , 'view_teamview');
//t3lib_div::debug($media, 'view_teamview');

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileView.php'])
{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileView.php']);
}
?>