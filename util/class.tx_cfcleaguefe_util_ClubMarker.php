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
tx_rnbase::load('tx_rnbase_util_BaseMarker');
tx_rnbase::load('tx_rnbase_util_Templates');


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
		$this->prepareRecord($club, $template, $formatter->getConfigurations(), $confId, $marker);
		// Es wird das MarkerArray mit Daten gefüllt
		$ignore = self::findUnusedCols($club->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($club->record, $confId , $ignore, $marker.'_',$club->getColumnNames());
		$this->prepareLinks($club, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
		// Die Adressdaten setzen
		if($this->containsMarker($template, $marker.'_ADDRESS'))
			$template = $this->_addAddress($template, $club->getAddress(), $formatter, $confId.'address.', $marker.'_ADDRESS');
		if($this->containsMarker($template, $marker.'_STADIUMS'))
			$template = $this->addStadiums($template, $club, $formatter, $confId.'stadium.', $marker.'_STADIUM');
		if($this->containsMarker($template, $marker.'_TEAMS'))
			$template = $this->addTeams($template, $club, $formatter, $confId.'team.', $marker.'_TEAM');

		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $out;
	}

	protected function prepareRecord(&$item, $template, $configurations, $confId, $marker) {
		$item->record['distance'] = '';
		if($this->containsMarker($template, $marker.'_DISTANCE') && self::hasGeoData($item)) {
			$lat = doubleval($configurations->get($confId.'_basePosition.latitude'));
			$lng = doubleval($configurations->get($confId.'_basePosition.longitude'));
			tx_rnbase::load('tx_cfcleaguefe_util_Maps');
			$item->record['distance'] = tx_cfcleaguefe_util_Maps::getDistance($item, $lat, $lng);
		}
	}
	protected function _addAddress($template, &$address, &$formatter, $addressConf, $markerPrefix) {
		$addressMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_AddressMarker');
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

		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($children,
						false, $template, 'tx_cfcleaguefe_util_StadiumMarker',
						$confId, $markerPrefix, $formatter, $options);
		return $out;
	}

	/**
	 * Hinzufügen der Teams.
	 * @param string $template HTML-Template
	 * @param tx_cfcleaguefe_models_club $item
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId Config-String
	 * @param string $markerPrefix
	 */
	private function addTeams($template, &$item, &$formatter, $confId, $markerPrefix) {
    $srv = tx_cfcleague_util_ServiceRegistry::getTeamService();
		$fields = array();
    $fields['TEAM.CLUB'][OP_EQ_INT] = $item->getUid();
		$options = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->configurations, $confId.'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->configurations, $confId.'options.');
		$children = $srv->searchTeams($fields, $options);

		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($children,
						false, $template, 'tx_cfcleaguefe_util_TeamMarker',
						$confId, $markerPrefix, $formatter);
		return $out;
	}

	private static function hasGeoData($item) {
		return !(!$item->getCity() && !$item->getZip() && !$item->getLongitute() && !$item->getLatitute());
	}
	/**
	 * Create a marker for GoogleMaps. This can be done if the club has address data.
	 * @param string $template
	 * @param tx_cfcleague_models_Club $item
	 */
	public function createMapMarker($template, $item, $formatter, $confId, $markerPrefix) {
		if(!self::hasGeoData($item)) return false;
		tx_rnbase::load('tx_rnbase_maps_DefaultMarker');
		
		$marker = new tx_rnbase_maps_DefaultMarker();
		if($item->getLongitute() || $item->getLatitute()) {
			$marker->setCoords($item->getCoords());
		}
		else {
			$marker->setCity($item->getCity());
			$marker->setZip($item->getZip());
			$marker->setStreet($item->getStreet());
			$marker->setCountry($item->getCountryCode());
		}
		//$marker->setTitle($item->getName());
		$bubble = $this->parseTemplate($template, $item, $formatter, $confId, $markerPrefix);
		$marker->setDescription($bubble);
		return $marker;
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