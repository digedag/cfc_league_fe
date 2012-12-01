<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Rene Nitzsche (rene@system25.de)
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
 * Diese Klasse ist für die Erstellung von Markerarrays für Profile verantwortlich
 */
class tx_cfcleaguefe_util_ProfileMarker extends tx_rnbase_util_BaseMarker {
	private $defaultMarkerArr;
	private $options;

	/**
	 * Initialisiert den Marker Array. 
	 */
	public function __construct(&$options = array()){
		$this->options = $options;
	}

	public function getOptions() {
		return $this->options;
	}
	/**
	 * Initialisiert die Labels für die Profile-Klasse
	 *
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param array $defaultMarkerArr
	 */
	public function initLabelMarkers(&$formatter, $profileConfId, $defaultMarkerArr = 0, $profileMarker = 'PROFILE') {
		return $this->prepareLabelMarkers('tx_cfcleaguefe_models_profile', $formatter, $profileConfId, $defaultMarkerArr, $profileMarker);
	}

	/**
	 * @param $template das HTML-Template
	 * @param tx_cfcleaguefe_models_profile $profile das Profil
	 * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
	 * @param $confId Pfad der TS-Config des Profils, z.B. 'listView.profile.'
	 * @param $marker Name des Markers für ein Profil, z.B. PROFILE, COACH, SUPPORTER
	 *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###COACH_SIGN###, ###COACH_LINK###
	 * @return String das geparste Template
	 */
	public function parseTemplate($template, &$profile, &$formatter, $confId, $marker = 'PROFILE') {
		if(!is_object($profile)) {
			return $formatter->configurations->getLL('profile_notFound');
		}
		$profile->addTeamNotes($this->options['team']);
		$this->prepareRecord($profile);
		tx_rnbase_util_Misc::callHook('cfc_league_fe','profileMarker_initRecord', array('item' => &$profile, 'template'=>&$template), $this);

		// Es wird das MarkerArray mit den Daten des Spielers gefüllt.
		$ignore = self::findUnusedCols($profile->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($profile->record, $confId , $ignore, $marker.'_',$profile->getColumnNames());
		$markerArray['###'.$marker.'_SIGN###'] = $profile->getSign();
		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($profile, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
    
//		// Jetzt die Bilder einbinden
//		$subpartArray['###'.$marker.'_PICTURES###'] = $this->_addProfilePictures($markerArray,$profile,$formatter, $template, $confId, $marker);

		$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		tx_rnbase_util_Misc::callHook('cfc_league_fe','profileMarker_afterSubst', array('item' => &$profile, 'template'=>&$template, 'confId'=>$confId, 'marker'=>$marker, 'conf' => $formatter->getConfigurations()), $this);
		return $template;
	}
	protected function prepareRecord($item) {
		$item->record['firstpicture'] = $item->record['dam_images'];
		$item->record['pictures'] = $item->record['dam_images'];
	}

	/**
	 * Links vorbereiten
	 *
	 * @param tx_cfcleaguefe_models_profile $profile
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	protected function prepareLinks(&$profile, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {

//		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid));
		if($profile->hasReport()) {
			$params = array('profileId' => $profile->uid);
			if(is_object($this->options['team'])) {
				// Transfer current team to profile view
				$params['team'] = $this->options['team']->uid;
			}
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showprofile', $marker, $params, $template);
		}
		else {
			$linkMarker = $marker . '_' . strtoupper('showprofile').'LINK';
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, false);
		}
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'refereematches', $marker, array('refereeId' => $profile->uid), $template);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ProfileMarker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ProfileMarker.php']);
}
?>