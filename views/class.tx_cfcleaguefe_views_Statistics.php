<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

tx_div::load('tx_rnbase_view_Base');


/**
 * Viewklasse für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_views_Statistics extends tx_rnbase_view_Base {
  /**
   * Erstellen des Frontend-Outputs
   */
  function render($view, &$configurations){
    $this->_init($configurations);
    $cObj =& $configurations->getCObj(0);
    $templateCode = $cObj->fileResource($this->getTemplate($view,'.html'));


    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();
    $data =& $viewData->offsetGet('data');

//    t3lib_div::debug($data, 'vw_stats');
    
    $template = $cObj->getSubpart($templateCode,'###STATISTICS###');
    
//    $out = $template;
    $out = $this->_createView($template, $data, $cObj, $configurations);
    return $out;
  }

  function _createView($template, &$data, &$cObj, &$configurations) {
    if(!count($data)) return $template; // ohne Daten gibt's keine Marker

    // Jetzt über die einzelnen Statistiken iterieren
    $markerArray = array();
    $subpartArray = $this->_getSubpartArray($configurations);
    $parts = array();
    foreach ($data as $type => $stats) {
    	$service = t3lib_div::makeInstanceService('cfcleague_statistics', $type);
      if(!is_object($service)) // Ohne den Service geht nix
        continue;
      $srvTemplate = $cObj->getSubpart($template,'###STATISTIC_'.strtoupper($type).'###');
      // Der Service muss jetzt den Marker liefert
      $srvMarker = $service->getMarker($configurations);
      $subpartArray['###STATISTIC_'.strtoupper($type).'###'] = $srvMarker->parseTemplate($srvTemplate, $stats, $this->formatter, 'statistics.'.$type.'.', strtoupper($type));
    }
//t3lib_div::debug($subpartArray, 'tx_cfcleaguefe_views_Statistics');
    $out = $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);

/*
//    $token = md5(microtime());
//    $this->linkMatch->label($token);
    $emptyArr = array();
//    $noLink = array('','');

    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_MatchMarker');
    $matchMarker = new $markerClass($this->links);

    for($i=0; $i < count($matches); $i++) {
      $match = $matches[$i];

      $parts[] = $matchMarker->parseTemplate($matchTemplate, $match, $this->formatter, 'tickerlist.match.', 'MATCH');
    }
    // Jetzt die einzelnen Teile zusammenfügen
    $subpartArray['###MATCH###'] = implode($parts, $configurations->get('tickerlist.match.implode'));

    // Zum Schluß das Haupttemplate zusammenstellen
//    $template = $cObj->getSubpart($template,'###MATCHES###');
    $markerArray = array();
//    $subpartArray['###MATCH###'] = $out;
    $out = $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);
*/
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
  
  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

  }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_Statistics.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_Statistics.php']);
}
?>