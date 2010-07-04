<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_maps_google_Icon');

/**
 * Utility methods for google maps integration
 *
 * @author	René Nitzsche <rene[at]system25.de>
 */
class tx_cfcleaguefe_util_Maps {
	public static function getMapTemplate($configurations, $confId, $subpartName) {
		$file = $configurations->get($confId.'template');
		if(!$file) return '';
		$templateCode = $configurations->getCObj()->fileResource($file);
		$subpart = tx_rnbase_util_Templates::getSubpart($templateCode,$subpartName);
		$order  = array("\r\n", "\n", "\r");
		$replace = '';
		return str_replace($order, $replace, $subpart);
	}
	/**
	 * Setzt ein Icon für die Map
	 *
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId confid for image configuration
	 * @param tx_rnbase_maps_DefaultMarker $marker
	 * @param string $logo image path
	 * @param string $shadow image path to shadow, not supported yet!
	 */
	public static function addIcon($map, $configurations, $confId, $marker, $iconName, $logo, $shadow = '') {
		$GLOBALS['TSFE']->register['T3SPORTS_MAPICON'] = $logo;
		if($logo) {
			$imgConf = $configurations->get($confId);
			$imgConf['file'] = $imgConf['file'] ? $imgConf['file'] : $logo;
			$url = $configurations->getCObj()->IMG_RESOURCE($imgConf);
			$icon = new tx_rnbase_maps_google_Icon($map);
			$icon->setName($iconName);

			$height = intval($imgConf['file.']['maxH']);
			$width = intval($imgConf['file.']['maxW']);
			$height = $height ? $height : 20;
			$width = $width ? $width : 20;
			$icon->setImage($url,$width,$height);
			$icon->setShadow($url,$width,$height);
			$icon->setAnchorPoint($width/2,$height/2);
			$icon->setInfoWindowAnchorPoint($width/2,$height/2);
			$marker->setIcon($icon);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_Maps.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_Maps.php']);
}
?>