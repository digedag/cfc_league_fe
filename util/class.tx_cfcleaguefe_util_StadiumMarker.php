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

tx_div::load('tx_rnbase_util_BaseMarker');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays eines Stadions verantwortlich
 */
class tx_cfcleaguefe_util_StadiumMarker extends tx_rnbase_util_BaseMarker {

	/**
	 * @param string $template das HTML-Template
	 * @param tx_cfcleague_models_Stadium $item
	 * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
	 * @param string $confId Pfad der TS-Config
	 * @param string $marker Name des Markers
	 * @return String das geparste Template
	 */
	public function parseTemplate($template, &$item, &$formatter, $confId, $marker = 'ARENA') {
		if(!is_object($item)) {
			// Ist kein Item vorhanden wird ein leeres Objekt verwendet.
			$item = self::getEmptyInstance('tx_cfcleague_models_Stadium');
		}

		// Es wird das MarkerArray mit Daten gefüllt
		$ignore = self::findUnusedCols($item->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($item->record, $confId , 0, $marker.'_',$item->getColumnNames());
		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($item, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

		// Die Adressdaten setzen
		if($this->containsMarker($template, $marker.'_ADDRESS'))
			$template = $this->_addAddress($template, $item->getAddress(), $formatter, $confId.'address.', $marker.'_ADDRESS');

		$out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

		return $out;
	}

	protected function _addAddress($template, &$address, &$formatter, $addressConf, $markerPrefix) {
		$addressMarker = tx_div::makeInstance('tx_cfcleaguefe_util_AddressMarker');
		$template = $addressMarker->parseTemplate($template, $address, $formatter, $addressConf, null, $markerPrefix);
		return $template;
	}

	/**
	 * Links vorbereiten
	 *
	 * @param tx_cfcleague_models_Stadium $item
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	protected function prepareLinks(&$item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		$linkId = 'show';
		if($item->isPersisted()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array('stadium' => $item->uid), $template);
		}
		else {
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_StadiumMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_StadiumMarker.php']);
}
?>