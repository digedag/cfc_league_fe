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
require_once(t3lib_extMgm::extPath('dam') . 'lib/class.tx_dam_media.php');

require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_BaseMarker.php');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays für Profile verantwortlich
 */
class tx_cfcleaguefe_util_ProfileMarker extends tx_rnbase_util_BaseMarker {
  private $defaultMarkerArr;
  private $options;

  /**
   * Initialisiert den Marker Array. 
   */
  function tx_cfcleaguefe_util_ProfileMarker(&$options = array()){
  	$this->options = $options;
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
		// Es wird das MarkerArray mit den Daten des Spielers gefüllt.
		$markerArray = $formatter->getItemMarkerArrayWrapped($profile->record, $confId , 0, $marker.'_',$profile->getColumnNames());
		$markerArray['###'.$marker.'_SIGN###'] = $profile->getSign();
		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($profile, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter);
    
		// Jetzt die Bilder einbinden
		$subpartArray['###'.$marker.'_PICTURES###'] = $this->_addProfilePictures($markerArray,$profile,$formatter, $template, $confId, $marker);

		$template = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		
		// Okay the normal stuff is done. Now lookout for external marker services.
		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();

		$params['confid'] = $confId;
		$params['marker'] = $marker;
		$params['profile'] = $profile;
		self::callModules($template, $markerArray, $subpartArray, $wrappedSubpartArray, $params, $formatter);
		$out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    
		return $out;
	}
	
  /**
   * Es werden die Bilder eingebunden
   */
  private function _addProfilePictures(&$firstMarkerArray,$profile, $formatter, $template, $profileConfId, $profileMarker) {
    // Das erste Bild ermitteln
    $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_profiles', $profile->uid, 'dam_images');
    list($uid, $filePath) = each($damPics['files']);

    if(count($damPics['files']) == 0) { // Keine Bilder vorhanden
      // Alle Marker löschen
    	
    	$firstMarkerArray['###'.$profileMarker.'_FIRST_PICTURE_IMGTAG###'] = $formatter->dataStdWrap($profile->record, '', $profileConfId.'firstImage.dummy.');
//      $gSubpartArray['###'. $profileMarker .'_PICTURES###'] = '';
      tx_rnbase_util_FormatUtil::fillEmptyMarkers($firstMarkerArray, 
                        tx_rnbase_util_FormatUtil::getDAMColumns(), $profileMarker.'_FIRST_PICTURE_');
//t3lib_div::debug($firstMarkerArray, 'view_profile');
      return '';
    }

    $mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
    $media = new $mediaClass($filePath);
		// Check DAM-Version
		if(method_exists($media, 'fetchFullMetaData'))
			$media->fetchFullMetaData();
		else
			$media->fetchFullIndex();
    
    $markerFirst = $formatter->getItemMarkerArray4DAM($media, $profileConfId.'firstImage.',$profileMarker.'_FIRST_PICTURE');
    $firstMarkerArray = array_merge($firstMarkerArray, $markerFirst);
    
    // Jetzt ersetzen wir die weiteren Bilder
    // Das Template holen wir aus dem übergebenen Template
    $pictureTemplate = $formatter->cObj->getSubpart($template,'###'.$profileMarker.'_PICTURES###');
    if(!$pictureTemplate) { // Ohne Template binden wir auch keine Bilder ein
      return '';
    }

    // Diese werden sofort durch das Template gejagt
    $markerArray = array();

    while(list($uid, $filePath) = each($damPics['files'])) {
      $media = new $mediaClass($filePath);
      $markerArray = $this->formatter->getItemMarkerArray4DAM($media, $profileConfId.'images.',$profileMarker.'_PICTURE');
      $out .= $formatter->cObj->substituteMarkerArrayCached($pictureTemplate, $markerArray);
    }
    return $out;
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
	protected function prepareLinks(&$profile, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter) {

//		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid));
		if($profile->hasReport()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showprofile', $marker, array('profileId' => $profile->uid));
		}
		else {
			$linkMarker = $marker . '_' . strtoupper('showprofile').'LINK';
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, false);
		}
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'refereematches', $marker, array('refereeId' => $profile->uid));
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ProfileMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ProfileMarker.php']);
}
?>