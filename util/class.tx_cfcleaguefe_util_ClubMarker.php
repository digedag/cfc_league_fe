<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Rene Nitzsche (rene@system25.de)
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

tx_div::load('tx_rnbase_util_BaseMarker');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Vereine verantwortlich
 */
class tx_cfcleaguefe_util_ClubMarker extends tx_rnbase_util_BaseMarker {

  /**
   * @param string $template das HTML-Template
   * @param tx_cfcleaguefe_models_club $club der Verein
   * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
   * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.club.'
   * @param array $links Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
   * @param string $clubMarker Name des Markers für den Club, z.B. CLUB
   *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
   * @return String das geparste Template
   */
  public function parseTemplate($template, &$club, &$formatter, $confId, $links=0, $clubMarker = 'CLUB') {
    if(!is_object($club)) {
    	// Ist kein Verein vorhanden wird ein leeres Objekt verwendet.
    	$club = self::getEmptyInstance('tx_cfcleaguefe_models_club');
//      return $formatter->configurations->getLL('team.notFound');
    }

//t3lib_div::debug($team->record , 'utl_teammarker');

    // Es wird das MarkerArray mit den Daten des Teams gefüllt.
    $markerArray = $formatter->getItemMarkerArrayWrapped($club->record, $confId , 0, $clubMarker.'_',$club->getColumnNames());

    // Die Adressdaten setzen
    $template = $this->_addAddress($template, $club->getAddress(), $formatter, $confId.'address.', $clubMarker.'_ADDRESS');

    $out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

    return $out;
  }

  protected function _addAddress($template, &$address, &$formatter, $addressConf, $markerPrefix) {
    $addressMarker = tx_div::makeInstance('tx_cfcleaguefe_util_AddressMarker');
    $template = $addressMarker->parseTemplate($template, $address, $formatter, $addressConf, null, $markerPrefix);
  	return $template;
  }
  
  /**
   * Initialisiert die Labels für die Club-Klasse
   *
   * @param tx_rnbase_util_FormatUtil $formatter
   * @param array $defaultMarkerArr
   */
  public function initLabelMarkers(&$formatter, $confId, $defaultMarkerArr = 0, $marker = 'CLUB') {
    return $this->prepareLabelMarkers('tx_cfcleaguefe_models_club', $formatter, $confId, $defaultMarkerArr, $marker);
  }
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ClubMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ClubMarker.php']);
}
?>