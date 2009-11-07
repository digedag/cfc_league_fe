<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Rene Nitzsche (rene@system25.de)
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
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');


tx_rnbase::load('tx_rnbase_view_Base');
tx_rnbase::load('tx_rnbase_maps_Factory');
tx_rnbase::load('tx_rnbase_maps_DefaultMarker');



/**
 * Viewklasse
 */
class tx_cfcleaguefe_views_ClubList extends tx_rnbase_view_Base {

	function createOutput($template, &$viewData, &$configurations, &$formatter) {
		$items =& $viewData->offsetGet('items');

		$builderClass = tx_div::makeInstanceClassName('tx_rnbase_util_ListBuilder');
		$listBuilder = new $builderClass();
		$template = $listBuilder->render($items,
						$viewData, $template, 'tx_cfcleaguefe_util_ClubMarker',
						'clublist.club.', 'CLUB', $formatter);
						
		$markerArray = array();
		$pagerData = $viewData->offsetGet('pagerData');
		$charPointer = $viewData->offsetGet('charpointer');
		$subpartArray['###CHARBROWSER###'] = $this->_createPager(
										$formatter->cObj->getSubpart($template,'###CHARBROWSER###'),
										$markerArray,
										$pagerData,
										$charPointer, $configurations);
		try {
			$map = tx_rnbase_maps_Factory::createGoogleMap($configurations, $this->getController()->getConfId().'map.');

			foreach($items As $item) {
				$marker = new tx_rnbase_maps_DefaultMarker();
				$marker->setTitle($item->getName());
				$marker->setCity($item->getCity());
				$map->addMarker($marker);
			}
			$markerArray['###CLUBMAP###'] = $map->draw();
		} catch (Exception $e) {
			$markerArray['###CLUBMAP###'] = '###LABEL_mapNotAvailable###';
		}

		$out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);
		return $out;
	}

	/**
	 * Liefert den Pagerstring
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
			$out[] = $configurations->getCObj()->substituteMarkerArrayCached($template, $myMarkerArray, $emptyArray, $wrappedSubpartArray);
		}

		return implode($configurations->get('listorganisations.org.charbrowser.implode'),$out);
	}

	/**
	 * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
	 * createOutput automatisch als $template übergeben. 
	 *
	 * @return string
	 */
	function getMainSubpart() {
		return '###CLUB_LIST###';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ClubList.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ClubList.php']);
}
?>