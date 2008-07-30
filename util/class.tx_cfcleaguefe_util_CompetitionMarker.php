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
 * Diese Klasse ist für die Erstellung von Markerarrays der Wettbewerbe verantwortlich
 */
class tx_cfcleaguefe_util_CompetitionMarker extends tx_rnbase_util_BaseMarker {

  /**
   * @param string $template das HTML-Template
   * @param tx_cfcleaguefe_models_competition $competition der Wettbewerb
   * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
   * @param string $confId Pfad der TS-Config des Vereins, z.B. 'matchtable.match.competition.'
   * @param string $marker Name des Markers für den Wettbewerb, z.B. COMPETITION
   *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###COMPETITION_NAME###
   * @return String das geparste Template
   */
  public function parseTemplate($template, &$competition, &$formatter, $confId, $marker = 'COMPETITION') {
  	if(!is_object($competition)) {
    	// Ist kein Verein vorhanden wird ein leeres Objekt verwendet.
    	$competition = self::getEmptyInstance('tx_cfcleaguefe_models_competition');
    }

    // Es wird das MarkerArray mit Daten gefüllt.
    $markerArray = $formatter->getItemMarkerArrayWrapped($competition->record, $confId , 0, $marker.'_',$competition->getColumnNames());
    $template = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

    if($this->containsMarker($template, $marker.'_GROUP'))
			$template = $this->getGroupMarker()->parseTemplate($template, $competition->getGroup(), $formatter, $confId.'group.', $marker.'_GROUP');
    
    return $template;
  }

  private function getGroupMarker() {
  	if(!is_object($this->groupMarker)) {
  		$clazzname = tx_div::makeInstanceClassname('tx_cfcleaguefe_util_GroupMarker');
  		$this->groupMarker = new $clazzname();
  	}
  	return $this->groupMarker;
  }
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_CompetitionMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_CompetitionMarker.php']);
}
?>