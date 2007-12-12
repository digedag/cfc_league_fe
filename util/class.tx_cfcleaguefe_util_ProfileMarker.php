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

  /**
   * Initialisiert den Marker Array. 
   */
  function tx_cfcleaguefe_util_ProfileMarker(){
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
   * @param $profile das Profil
   * @param $formatter der zu verwendente Formatter
   * @param $profileConfId Pfad der TS-Config des Profils, z.B. 'listView.profile.'
   * @param $link Link-Instanz, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
   * @param $profileMarker Name des Markers für ein Profil, z.B. PROFILE, COACH, SUPPORTER
   *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###COACH_SIGN###, ###COACH_LINK###
   * @return String das geparste Template
   */
  public function parseTemplate($template, &$profile, &$formatter, $profileConfId, $link = 0, $profileMarker = 'PROFILE') {
    if(!is_object($profile)) {
      return $formatter->configurations->getLL('profile.notFound');
//      return ''; // Ohne Profil kein Ergebnis
    }

    if($link) {
      $token = md5(microtime());
      $link->label($token);
    }
    $emptyArr = array();
    $noLink = array('','');

    // Es wird das MarkerArray mit den Daten des Spielers gefüllt.
    $markerArray = $formatter->getItemMarkerArrayWrapped($profile->record, $profileConfId , 0, $profileMarker.'_',$profile->getColumnNames());
    $markerArray['###'.$profileMarker.'_SIGN###'] = $profile->getSign();
//t3lib_div::debug($markerArray, 'utl_marker');
    
    if($link && $profile->hasReport()) {
      $link->parameters(array('profileId' => $profile->uid));
      $wrappedSubpartArray['###'.$profileMarker.'_LINK###'] = explode($token, $link->makeTag());
    }
    else
      $wrappedSubpartArray['###'.$profileMarker.'_LINK###'] = $noLink;

    // Jetzt die Bilder einbinden
    $subpartArray['###'.$profileMarker.'_PICTURES###'] = $this->_addProfilePictures($markerArray,$profile,$formatter, $template, $profileConfId, $profileMarker);


    $out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
//t3lib_div::debug($out , 'utl_marker');
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
      $firstMarkerArray['###'.$profileMarker.'_FIRST_PICTURE###'] = '';
//      $gSubpartArray['###'. $profileMarker .'_PICTURES###'] = '';
      tx_rnbase_util_FormatUtil::fillEmptyMarkers($firstMarkerArray, 
                        tx_rnbase_util_FormatUtil::getDAMColumns(), $profileMarker.'_FIRST_PICTURE_');
//t3lib_div::debug($firstMarkerArray, 'view_profile');
      return '';
    }

    $mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
    $media = new $mediaClass($filePath);
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ProfileMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ProfileMarker.php']);
}
?>