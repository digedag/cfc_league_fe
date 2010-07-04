<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_maps_Factory');
tx_rnbase::load('tx_rnbase_util_BaseMarker');



/**
 * Viewklasse
 */
class tx_cfcleaguefe_views_ClubList extends tx_rnbase_view_Base {

	function createOutput($template, &$viewData, &$configurations, &$formatter) {
		$items =& $viewData->offsetGet('items');

		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$template = $listBuilder->render($items,
						$viewData, $template, 'tx_cfcleaguefe_util_ClubMarker',
						'clublist.club.', 'CLUB', $formatter);
						
		$markerArray = array();
		$pagerData = $viewData->offsetGet('pagerData');
		$charPointer = $viewData->offsetGet('charpointer');
		$subpartArray['###CHARBROWSER###'] = $this->_createPager(
										tx_rnbase_util_Templates::getSubpart($template,'###CHARBROWSER###'),
										$markerArray,
										$pagerData,
										$charPointer, $configurations);

		if(tx_rnbase_util_BaseMarker::containsMarker($template, 'CLUBMAP'))
			$markerArray['###CLUBMAP###'] = $this->getMap($items, $configurations, $this->getController()->getConfId().'map.', 'CLUB');

		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);
		return $out;
	}

	private function getMap($items, $configurations, $confId, $markerPrefix) {
		$ret = '###LABEL_mapNotAvailable###';
		try {
			$map = tx_rnbase_maps_Factory::createGoogleMap($configurations, $confId);

			tx_rnbase::load('tx_cfcleaguefe_util_Maps');
			$template = tx_cfcleaguefe_util_Maps::getMapTemplate($configurations, $confId, '###CLUB_MAP_MARKER###');
			$itemMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
			tx_rnbase::load('tx_rnbase_maps_google_Icon');
			tx_rnbase::load('tx_rnbase_maps_DefaultMarker');
			foreach($items As $item) {
				$marker = $itemMarker->createMapMarker($template, $item, $configurations->getFormatter(), $confId.'stadium.', $markerPrefix);
				if(!$marker) continue;

				tx_cfcleaguefe_util_Maps::addIcon($map, $configurations, 
					$this->getController()->getConfId().'map.icon.clublogo.', $marker, 'club_'.$item->getUid(), $item->getFirstLogo());
				//$this->addIcon($map, $marker, $item, $configurations);
				$map->addMarker($marker);
			}
			$ret = $map->draw();
		} catch (Exception $e) {
			$ret = '###LABEL_mapNotAvailable###';
		}
		return $ret;
	}
	/**
	 * Setzt ein Icon für die Map
	 *
	 * @param tx_rnbase_maps_DefaultMarker $marker
	 * @param tx_cfcleague_models_Club $club
	 * @param tx_rnbase_configurations $configurations
	 */
	private function addIcon($map, &$marker, $club, $configurations) {
		$logo = $club->getFirstLogo();
		if($logo) {
			$imgConf = $configurations->get($this->getController()->getConfId().'map.icon.clublogo.');
			$imgConf['file'] = $logo;
			$url = $configurations->getCObj()->IMG_RESOURCE($imgConf);
			$icon = new tx_rnbase_maps_google_Icon($map);
			$icon->setName('club_'.$club->getUid());

			$height = intval($imgConf['file.']['maxH']);
			$width = intval($imgConf['file.']['maxW']);
			$height = $height ? $height : 20;
			$width = $width ? $width : 20;
			$icon->setImage($url,$width,$height);
			$icon->setShadow($url,$width,$height);
			$icon->setAnchorPoint($width/2,$height/2);
			$marker->setIcon($icon);
		}
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