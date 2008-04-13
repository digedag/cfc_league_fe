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

tx_div::load('tx_rnbase_util_BaseMarker');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Teams verantwortlich
 */
class tx_cfcleaguefe_util_TeamMarker extends tx_rnbase_util_BaseMarker {
	function tx_cfcleaguefe_util_TeamMarker(&$options = null) {
	}

  /**
   * @param string $template das HTML-Template
   * @param tx_cfcleaguefe_models_team $team das Team
   * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
   * @param string $teamConfId Pfad der TS-Config des Profils, z.B. 'listView.profile.'
   * @param array $links Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
   * @param string $teamMarker Name des Markers für das Team, z.B. TEAM, MATCH_HOME usw.
   *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###COACH_SIGN###, ###COACH_LINK###
   * @return String das geparste Template
   */
  public function parseTemplate($template, &$team, &$formatter, $teamConfId, $teamMarker = 'TEAM') {
    if(!is_object($team)) {
      return $formatter->configurations->getLL('team_notFound');
//      return ''; // Ohne Team kein Ergebnis
    }
    $this->prepareRecord($team);
    // Es wird das MarkerArray mit den Daten des Teams gefüllt.
		$markerArray = $formatter->getItemMarkerArrayWrapped($team->record, $teamConfId , 0, $teamMarker.'_',$team->getColumnNames());
    $wrappedSubpartArray = array();
    $subpartArray = array();
    $this->prepareLinks($team, $teamMarker, $markerArray, $subpartArray, $wrappedSubpartArray, $teamConfId, $formatter);

		$markerArray['###'.$teamMarker.'_LOGO###'] = $team->getLogo($formatter, $teamConfId.'logo.');

		// Jetzt die Bilder einbinden
    $this->_addTeamPictures($subpartArray, $markerArray,$team,$formatter, $template, $teamConfId, $teamMarker);

    // Die Spieler setzen
    $subpartArray['###'.$teamMarker.'_PLAYERS###'] = $this->_addTeamProfiles($markerArray,
                                   $team->getPlayers(),$formatter, 
                                   $formatter->cObj->getSubpart($template,'###'.$teamMarker.'_PLAYERS###'),
                                   '###'.$teamMarker.'_PLAYER###', $teamConfId.'player.', $teamMarker.'_PLAYER');

    // Die Trainer setzen
    $subpartArray['###'.$teamMarker.'_COACHES###'] = $this->_addTeamProfiles($markerArray,
                                   $team->getCoaches(),$formatter, 
                                   $formatter->cObj->getSubpart($template,'###'.$teamMarker.'_COACHES###'),
                                   '###'.$teamMarker.'_COACH###', $teamConfId.'coach.', $teamMarker.'_COACH');

    // Die Betreuer setzen
    $subpartArray['###'.$teamMarker.'_SUPPORTERS###'] = $this->_addTeamProfiles($markerArray,
                                   $team->getSupporters(),$formatter, 
                                   $formatter->cObj->getSubpart($template,'###'.$teamMarker.'_SUPPORTERS###'),
                                   '###'.$teamMarker.'_SUPPORTER###', $teamConfId.'supporter.', $teamMarker.'_SUPPORTER');

    // set club data
    $template = $this->_addClubData($template, $team->getClub(), $formatter, $teamConfId.'club.', $teamMarker.'_CLUB');

    $out = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    
    return $out;
  }

  /**
   * Prepare team record before rendering
   *
   * @param tx_cfcleaguefe_models_team $team
   */
  private function prepareRecord(&$team) {
  	$group = $team->getAgeGroup();
  	$team->record['agegroup_name'] = is_object($group) ?  $group->getName() : '';
  }
  
  /**
   * Hinzufügen der Daten des Vereins
   *
   * @param array $firstMarkerArray
   * @param tx_cfcleaguefe_models_club $club
   */
  protected function _addClubData($template, &$club, &$formatter, $clubConf, $markerPrefix) {
    $clubMarker = tx_div::makeInstance('tx_cfcleaguefe_util_ClubMarker');
    $template = $clubMarker->parseTemplate($template, $club, $formatter, $clubConf, null, $markerPrefix);
  	return $template;
  }
  /**
   * Hinzufügen der Spieler des Teams.
   * param $template HTML-Template für die Profile
   * param $profileMarker Name des Markers für den Abschnitt eines Profils
   * param $profileConf Config-String für den Wrap der Profile
   * param $markerPrefix Prefix für die Daten des Profile-Records
   */
  private function _addTeamProfiles(&$firstMarkerArray, &$profiles, &$formatter, $template, $profileMarker, $profileConf, $markerPrefix) {
    // Ohne Template gibt es nichts zu tun!
    if(strlen(trim($template)) == 0) return '';

//t3lib_div::debug($template, 'utl_marker');

    $playerTemplate = $formatter->cObj->getSubpart($template,$profileMarker);

    $profileMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_ProfileMarker');
    $profileMarkerObj = new $profileMarkerClass;

    $out = '';

    for($i = 0; $i < count($profiles); $i++) {
      $profile = $profiles[$i];
      // Jetzt für jedes Profile das Template parsen
      $out .= $profileMarkerObj->parseTemplate($playerTemplate, $profile, $formatter, $profileConf, $markerPrefix);
    }

    if(count($profiles)) {
      $subpartArray[$profileMarker] = $out;
      $out = $formatter->cObj->substituteMarkerArrayCached($template, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
    }
    else { // Keine Spieler vorhanden, es wird ein leerer String gesendet
      $out = '';
    }

    return $out;
  }

  /**
   * Hinzufügen der Bilder des Teams. Das erste Bild wird gesondert gemarkert, die restlichen 
   * werden als Liste behandelt.
   * @param $gSubPartArray globales Subpart-Array, welches die Ergebnisse aufnimmt
   * @param $firstMarkerArray
   */
  private function _addTeamPictures(&$gSubpartArray, &$firstMarkerArray, $team, $formatter, $template, $teamConfId, $teamMarker) {
    // Das erste Bild ermitteln
    $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_teams', $team->uid, 'dam_images');
    list($uid, $filePath) = each($damPics['files']);

    if(count($damPics['files']) == 0) { // Keine Bilder vorhanden
      // Alle Marker löschen
      $firstMarkerArray['###'. $teamMarker .'_FIRST_PICTURE###'] = '';
      $gSubpartArray['###'. $teamMarker .'_PICTURES###'] = '';
      tx_rnbase_util_FormatUtil::fillEmptyMarkers($firstMarkerArray, 
                        tx_rnbase_util_FormatUtil::getDAMColumns(), $teamMarker.'_FIRST_PICTURE_');
      return;
    }

    $mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
    $media = new $mediaClass($filePath);
		// Check DAM-Version
		if(method_exists($media, 'fetchFullMetaData'))
			$media->fetchFullMetaData();
		else
			$media->fetchFullIndex();
    $markerFirst = $formatter->getItemMarkerArray4DAM($media, $teamConfId.'firstImage.', $teamMarker.'_FIRST_PICTURE');
    $firstMarkerArray = array_merge($firstMarkerArray, $markerFirst);

    // Jetzt ersetzen wir die weiteren Bilder
    // Zuerst wieder das Template laden
    $gPictureTemplate = $formatter->cObj->getSubpart($template,'###'. $teamMarker .'_PICTURES###');

    $pictureTemplate = $formatter->cObj->getSubpart($gPictureTemplate,'###'. $teamMarker .'_PICTURES_2###');
    $markerArray = array();
    $out = '';

//t3lib_div::debug($gPictureTemplate, 'utl_teammarker');
    // Alle Bilder hinzufügen
    while(list($uid, $filePath) = each($damPics['files'])) {
      $media = new $mediaClass($filePath);
      $markerArray = $formatter->getItemMarkerArray4DAM($media, $teamConfId.'images.',$teamMarker.'_PICTURE');
      $out .= $formatter->cObj->substituteMarkerArrayCached($pictureTemplate, $markerArray);
    }
    // Der String mit den Bilder ersetzt jetzt den Subpart ###TEAM_PICTURES_2###
    if(strlen(trim($out)) > 0) {
      $subpartArray['###'. $teamMarker .'_PICTURES_2###'] = $out;
      $out = $formatter->cObj->substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
    }
    $gSubpartArray['###'. $teamMarker .'_PICTURES###'] = $out;
  }

  /**
   * Initialisiert die Labels für die Team-Klasse
   *
   * @param tx_rnbase_util_FormatUtil $formatter
   * @param array $defaultMarkerArr
   */
  public function initLabelMarkers(&$formatter, $confId, $defaultMarkerArr = 0, $marker = 'TEAM') {
    return $this->prepareLabelMarkers('tx_cfcleaguefe_models_team', $formatter, $confId, $defaultMarkerArr, $marker);
  }
	/**
	 * Links vorbereiten
	 *
	 * @param tx_cfcleaguefe_models_team $team
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	private function prepareLinks(&$team, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter) {
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid));
		if($team->hasReport()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showteam', $marker, array('teamId' => $team->uid));
		}
		else {
			$linkMarker = $marker . '_' . strtoupper('showteam').'LINK';
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, false);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_TeamMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_TeamMarker.php']);
}
?>