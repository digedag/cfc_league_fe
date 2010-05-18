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
tx_rnbase::load('tx_rnbase_util_Templates');



/**
 * Viewklasse für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_views_ProfileList extends tx_rnbase_view_Base {
	/**
	 * Create fe output
	 *
	 * @param string $template
	 * @param arrayobject $viewData
	 * @param tx_rnbase_configurations $configurations
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @return string
	 */
	function createOutput($template, &$viewData, &$configurations, &$formatter) {
		$markerArray = array(); // Eventuell später für allgemeine Daten oder Labels

		$pagerData = $viewData->offsetGet('pagerData');
		$charPointer = $viewData->offsetGet('charpointer');
		$subpartArray['###CHARBROWSER###'] = $this->_createPager(
													tx_rnbase_util_Templates::getSubpart($template,'###CHARBROWSER###'),
													$markerArray,
													$pagerData,
													$charPointer, $configurations);

		$listCnt =& $viewData->offsetGet('listsize');
		$profiles =& $viewData->offsetGet('profiles');

		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($profiles,
						$viewData, $template, 'tx_cfcleaguefe_util_ProfileMarker',
						'profilelist.profile.', 'PROFILE', $formatter);

		// Zum Schluß das Haupttemplate zusammenstellen
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($out, $markerArray, $subpartArray); //, $wrappedSubpartArray);

		return $out ;
	}


	/**
	 * Liefert den Pagerstring
	 * TODO: Hier müssen noch Formatierungen eingebaut werden!
	 */
	function _createPager($template, $markerArray, &$pagerData, $curr_pointer, &$configurations) {
    $out = array();
		$link = $configurations->createLink(); // Link auf die eigene Seite
		$link->destination($GLOBALS['TSFE']->id); // Das Ziel der Seite vorbereiten
   	$token = md5(microtime());
    $link->label($token);
    $emptyArr = array();
		$pagerItems = $pagerData['list'];
		if(!is_array($pagerItems) || !count($pagerItems)) return '';
		
		while(list($pointer, $size) = each($pagerItems)) {
			$myMarkerArray = $markerArray;
			$myMarkerArray['###PB_ITEM###'] = $pointer;
			$myMarkerArray['###PB_ITEM_SIZE###'] = $size;

			if(strcmp($pointer, $curr_pointer)) {
				$link->parameters(array('charpointer' => $pointer));
				$wrappedSubpartArray['###PB_ITEM_LINK###'] = explode($token, $link->makeTag());
			}
			else
				$wrappedSubpartArray['###PB_ITEM_LINK###'] = $emptyArr;
			$out[] = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $myMarkerArray, $emptyArray, $wrappedSubpartArray);
		}

		return implode($configurations->get('profilelist.charbrowser.implode'),$out);
  }

	function getMainSubpart(&$viewData) {
		return '###PROFILE_LIST###';
	}
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileList.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileList.php']);
}
?>