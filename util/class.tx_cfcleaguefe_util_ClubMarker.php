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
	public function parseTemplate($template, &$club, &$formatter, $confId, $marker = 'CLUB') {
		if(!is_object($club)) {
			// Ist kein Verein vorhanden wird ein leeres Objekt verwendet.
			$club = self::getEmptyInstance('tx_cfcleaguefe_models_club');
		}
		$this->prepareRecord($club);

		// Es wird das MarkerArray mit Daten gefüllt
		$ignore = self::findUnusedCols($club->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($club->record, $confId , 0, $marker.'_',$club->getColumnNames());
		$this->prepareLinks($club, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
		// Die Adressdaten setzen
		if($this->containsMarker($template, $marker.'_ADDRESS'))
			$template = $this->_addAddress($template, $club->getAddress(), $formatter, $confId.'address.', $marker.'_ADDRESS');
		if($this->containsMarker($template, $marker.'_STADIUMS'))
			$template = $this->addStadiums($template, $club, $formatter, $confId.'stadium.', $marker.'_STADIUM');

		$out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $out;
	}

	protected function prepareRecord(&$item) {
		$item->record['logo'] = $item->record['dam_logo'];
	}
	protected function _addAddress($template, &$address, &$formatter, $addressConf, $markerPrefix) {
		$addressMarker = tx_div::makeInstance('tx_cfcleaguefe_util_AddressMarker');
		$template = $addressMarker->parseTemplate($template, $address, $formatter, $addressConf, null, $markerPrefix);
		return $template;
	}
	/**
	 * Hinzufügen der Stadien.
	 * @param string $template HTML-Template für die Profile
	 * @param tx_cfcleaguefe_models_club $item
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId Config-String
	 * @param string $markerPrefix
	 */
	private function addStadiums($template, &$item, &$formatter, $confId, $markerPrefix) {
    $srv = tx_cfcleague_util_ServiceRegistry::getStadiumService();
		$fields = array();
    $fields['STADIUMMM.UID_FOREIGN'][OP_IN_INT] = $item->getUid();
		$options = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->configurations, $confId.'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->configurations, $confId.'options.');
		$children = $srv->search($fields, $options);

		$builderClass = tx_div::makeInstanceClassName('tx_rnbase_util_ListBuilder');
		$listBuilder = new $builderClass();
		$out = $listBuilder->render($children,
						tx_div::makeInstance('tx_lib_spl_arrayObject'), $template, 'tx_cfcleaguefe_util_StadiumMarker',
						$confId, $markerPrefix, $formatter, $options);
		return $out;
	}

	/**
	 * Links vorbereiten
	 *
	 * @param tx_cfcleague_models_Club $item
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	protected function prepareLinks(&$item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		$linkId = 'show';
		if($item->isPersisted()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array('club' => $item->uid), $template);
		}
		else {
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ClubMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ClubMarker.php']);
}
?>