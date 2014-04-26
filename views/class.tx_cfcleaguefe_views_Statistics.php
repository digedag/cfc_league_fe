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
class tx_cfcleaguefe_views_Statistics extends tx_rnbase_view_Base {
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
    $data =& $viewData->offsetGet('data');
    if(!count($data)) return $template; // ohne Daten gibt's keine Marker

    $cObj =& $configurations->getCObj(0);
		// Jetzt über die einzelnen Statistiken iterieren
		$markerArray = array();
		$subpartArray = $this->_getSubpartArray($configurations);
		$parts = array();
		$services = tx_cfcleaguefe_util_ServiceRegistry::lookupServices('cfcleague_statistics');
		foreach($services As $subtype => $info) {
			// Init all stats with empty subpart
			$subpartArray['###STATISTIC_'.strtoupper($subtype).'###'] = '';
		}

		foreach ($data as $type => $stats) {
			$service = t3lib_div::makeInstanceService('cfcleague_statistics', $type);
			if(!is_object($service)) // Ohne den Service geht nix
				continue;
			$srvTemplate = $cObj->getSubpart($template, '###STATISTIC_'.strtoupper($type).'###');
			// Der Service muss jetzt den Marker liefert
			$srvMarker = $service->getMarker($configurations);
			$subpartArray['###STATISTIC_'.strtoupper($type).'###'] = $srvMarker->parseTemplate($srvTemplate, $stats, $configurations->getFormatter(), 'statistics.'.$type.'.', strtoupper($type));
		}
//t3lib_div::debug($subpartArray, 'tx_cfcleaguefe_views_Statistics');
 		$out = $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);
		return $out;
	}

	/**
	 * Erstellt das initiale Subpart-Array. Alle möglichen Servicetemplates sind
	 * bereits leer enthalten
	 *
	 * @param tx_rnbase_configurations $configurations
	 * @return array
	 */
	function _getSubpartArray(&$configurations) {
		$ret = array();
		// Flexform auslesen
		$flex =& $configurations->getFlexFormArray();
		$types = $this->_getItemsArrayFromFlexForm($flex, 's_statistics', 'statisticTypes');
		foreach($types As $type) {
			$ret['###STATISTIC_'.strtoupper($type[1]).'###'] = '';
		}
		return $ret;
	}

	function _getItemsArrayFromFlexForm($flexArr, $sheetName, $valueName) {
		return $flexArr['sheets'][$sheetName]['ROOT']['el'][$valueName]['TCEforms']['config']['items'];
	}

	function getMainSubpart(&$viewData) {
		return '###STATISTICS###';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_Statistics.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_Statistics.php']);
}
?>