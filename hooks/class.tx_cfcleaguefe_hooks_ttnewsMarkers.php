<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');


tx_div::load('tx_rnbase_util_BaseMarker');
tx_div::load('tx_cfcleaguefe_controllers_report');


/**
 * Make links to match reports from tt_news
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_hooks_ttnewsMarkers {
	/**
	 * Hook um weitere Marker in tt_news einzufügen. Es sollte möglich sein auf alle
	 * Views von T3sports direkt zu verlinken. Die meisten Einstellungen kommen aus der 
	 * TS-Config.
	 * Beispiel für einen Link in der News:
	 * [t3sports:matchreport:123 Zum Spielbericht]
	 * t3sports - Konstante
	 * matchreport - Verweis auf die TS-Config plugin.tt_news.external_links.matchreport
	 * 123 - dynamischer Parameter
	 *
	 * @param array $markerArray marker array from tt_news
	 * @param array $row tt_news record
	 * @param array $lConf tt_news config-array
	 * @param tx_ttnews $ttnews tt_news plugin instance
	 */
	function extraItemMarkerProcessor($markerArray, $row, $lConf, $ttnews) {
		$this->configurations = tx_div::makeInstance('tx_rnbase_configurations');
		$t3sportsContr = tx_div::makeInstance('tx_cfcleaguefe_controllers_report');
		// Dieses cObj wird dem Controller von T3 übergeben
		$this->configurations->init($ttnews->conf, $ttnews->cObj, $t3sportsContr->extensionKey, $t3sportsContr->qualifier);
		$this->linkConf = $this->configurations->get('external_links.');
//		$regExpr1 =  "/\[(google|local) ([[:graph:]]+) (nolink)\]/";
//		$regExpr1 =  "/\[(google|local) ([[:graph:]]+) ([[:print:]]+)\]/";
		$regExpr[] = "/\[t3sports:([\w]*):(\w+) (.*?)]/";
		$markerNames = array('CONTENT', 'SUBHEADER');
		foreach($markerNames As $markerName) {
			$markerArray['###NEWS_'.$markerName.'###'] = $this->handleMarker($markerArray['###NEWS_'.$markerName.'###'], $regExpr);

		}

//  	$GLOBALS['TSFE']->register['SECTION_FRAME'] = $pObj->cObj->data['section_frame']; // Access to section_frame by TS
//    $markerArray['###NEWS_'. strtoupper('tx_sv98news_tickertext') .'###' ] = $ttnews->local_cObj->stdWrap($row['tx_sv98news_tickertext'],$lConf['tx_sv98news_tickertext.']);
//    $markerArray['###NEWS_'. strtoupper('tx_sv98news_title') .'###' ] = $ttnews->local_cObj->stdWrap($row['tx_sv98news_title'],$lConf['tx_sv98news_title.']);
//t3lib_div::debug($markerArray['###NEWS_CATEGORY###'], 'tx_sv98news_hooks_extraMarkers');
    return $markerArray;
//    t3lib_div::debug($row['tx_sv98news_tickertext'], 'tx_sv98news_hooks_extraMarkers');
	}

	function handleMarker($marker, $expr) {
		$marker = preg_replace_callback($expr, array($this,'replace'), $marker);
		return $marker;
	}
	function replace($match) {
		$linkId = $match[1];
		$conf = $this->linkConf[$linkId.'.'];
		$linkParams = array();
		$params = t3lib_div::trimExplode(',', $conf['ext_parameters']);
		$paramValues = t3lib_div::trimExplode(',', $match[2]);
		for($i=0, $cnt = count($params); $i < $cnt; $i++) {
			$linkParams[$params[$i]] = $paramValues[$i];
		}

		$wrappedSubpartArray = array();
		$empty = array();
		tx_rnbase_util_BaseMarker::initLink($empty, $empty, $wrappedSubpartArray, $this->configurations->getFormatter(), 
							'external_', $linkId, 'TTNEWS', $linkParams);
		$out = $wrappedSubpartArray['###TTNEWS_'.strtoupper($linkId).'LINK###'][0] . $match[3] . $wrappedSubpartArray['###TTNEWS_'.strtoupper($linkId).'LINK###'][1];
		return $out;
	}
	function getLinkData($marker) {
		return $marker;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/hooks/class.tx_cfcleaguefe_hooks_ttnewsMarkers.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/hooks/class.tx_cfcleaguefe_hooks_ttnewsMarkers.php']);
}

?>