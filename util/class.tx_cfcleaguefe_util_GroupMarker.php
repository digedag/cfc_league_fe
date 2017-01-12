<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_util_BaseMarker');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Altersgruppen verantwortlich
 */
class tx_cfcleaguefe_util_GroupMarker extends tx_rnbase_util_BaseMarker {

	/**
	 * @param string $template das HTML-Template
	 * @param tx_cfcleague_models_Group $group die Altersklasse
	 * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
	 * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.group.'
	 * @param string $marker Name des Markers
	 * @return String das geparste Template
	 */
	public function parseTemplate($template, &$item, &$formatter, $confId, $marker = 'GROUP') {
		if(!is_object($item)) {
			// Ist kein Objekt vorhanden wird ein leeres Objekt verwendet.
			$item = self::getEmptyInstance('tx_cfcleague_models_Group');
		}
		tx_rnbase_util_Misc::callHook('cfc_league_fe','groupMarker_initRecord', array('item' => &$item, 'template'=>&$template, 'confid'=>$confId, 'marker'=>$marker, 'formatter'=>$formatter), $this);
		// Es wird das MarkerArray mit den Daten des Records gefüllt.
		$markerArray = $formatter->getItemMarkerArrayWrapped($item->record, $confId , 0, $marker.'_',$item->getColumnNames());
		$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

		tx_rnbase_util_Misc::callHook('cfc_league_fe','groupMarker_afterSubst', array('item' => &$item, 'template'=>&$template, 'confid'=>$confId, 'marker'=>$marker, 'formatter'=>$formatter), $this);
		return $template;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_GroupMarker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_GroupMarker.php']);
}
?>