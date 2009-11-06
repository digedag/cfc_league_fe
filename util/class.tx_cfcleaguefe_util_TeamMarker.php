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
	public function parseTemplate($template, &$team, &$formatter, $confId, $marker = 'TEAM') {
		if(!is_object($team)) {
			return $formatter->configurations->getLL('team_notFound');
		}
		$this->prepareRecord($team);
		// Es wird das MarkerArray mit den Daten des Teams gefüllt.
		$ignore = self::findUnusedCols($team->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($team->record, $confId , $ignore, $marker.'_',$team->getColumnNames());
		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($team, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
		$this->pushTT('TeamLogo');
		// Die Einbindung des Team-Logos erfolgt jetzt per TS.
//		if(self::containsMarker($template, $marker.'_LOGO'))
//			$markerArray['###'.$marker.'_LOGO###'] = $team->getLogo($formatter, $confId.'logo.');
		$this->pullTT();
		
		// Die Spieler setzen
		$this->pushTT('add player');
		if($this->containsMarker($template, $marker.'_PLAYERS'))
			$template = $this->_addProfiles($template, $team, $formatter, $confId.'player.', $marker.'_PLAYER','players');
		$this->pullTT();

		// Die Trainer setzen
		$this->pushTT('add coaches');
		if($this->containsMarker($template, $marker.'_COACHS'))
			$template = $this->_addProfiles($template, $team, $formatter, $confId.'coach.', $marker.'_COACH','coaches');
		$this->pullTT();

		// Die Betreuer setzen
		$this->pushTT('add supporter');
		if($this->containsMarker($template, $marker.'_SUPPORTERS'))
			$template = $this->_addProfiles($template, $team, $formatter, $confId.'supporter.', $marker.'_SUPPORTER','supporters');
		$this->pullTT();

		// set club data
		$this->pushTT('Club data');
		if(self::containsMarker($template, $marker.'_CLUB'))
			$template = $this->_addClubData($template, $team->getClub(), $formatter, $confId.'club.', $marker.'_CLUB');
		$this->pullTT();
    
		$this->pushTT('substituteMarkerArrayCached');
		$template = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		$this->pullTT();
		// Now lookout for external marker services.
		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();
		
		$params['confid'] = $confId;
		$params['marker'] = $marker;
		$params['team'] = $team;
		self::callModules($template, $markerArray, $subpartArray, $wrappedSubpartArray, $params, $formatter);
		return $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    
  }

	/**
	 * Prepare team record before rendering
	 *
	 * @param tx_cfcleaguefe_models_team $item
	 */
	private function prepareRecord(&$item) {
		$group = $item->getAgeGroup();
		$GLOBALS['TSFE']->register['T3SPORTS_TEAMGROUP'] = is_object($group) ? $group->uid : 0;
		$item->record['agegroup_name'] = is_object($group) ?  $group->getName() : '';
		$item->record['firstpicture'] = $item->record['dam_images'];
		$item->record['pictures'] = $item->record['dam_images'];
	}
  
  /**
   * Hinzufügen der Daten des Vereins
   *
   * @param array $firstMarkerArray
   * @param tx_cfcleaguefe_models_club $club
   */
  protected function _addClubData($template, &$club, &$formatter, $clubConf, $markerPrefix) {
    $clubMarker = tx_div::makeInstance('tx_cfcleaguefe_util_ClubMarker');
    $template = $clubMarker->parseTemplate($template, $club, $formatter, $clubConf, $markerPrefix);
  	return $template;
  }
	/**
	 * Hinzufügen der Spieler des Teams.
	 * @param string $template HTML-Template für die Profile
	 * @param tx_cfcleaguefe_models_team $team
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId Config-String für den Wrap der Profile
	 * @param string $markerPrefix Prefix für die Daten des Profile-Records
	 * @param string $joinCol Name der Teamspalte mit den Profilen players, coaches, supporters
	 */
	private function _addProfiles($template, &$team, &$formatter, $confId, $markerPrefix, $joinCol) {
  	// Ohne Template gibt es nichts zu tun!
    if(strlen(trim($template)) == 0) return '';

		//$srv = tx_cfcleague_util_ServiceRegistry::getProfileService();
    $srv = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
		$fields['PROFILE.UID'][OP_IN_INT] = $team->record[$joinCol];
		$options = array();
		tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->configurations, $confId.'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->configurations, $confId.'options.');
		$children = $srv->search($fields, $options);

		$builderClass = tx_div::makeInstanceClassName('tx_rnbase_util_ListBuilder');
    $options['team'] = $team;
		
		$listBuilder = new $builderClass();
		$out = $listBuilder->render($children,
						tx_div::makeInstance('tx_lib_spl_arrayObject'), $template, 'tx_cfcleaguefe_util_ProfileMarker',
						$confId, $markerPrefix, $formatter, $options);
		return $out;
	}

  /**
   * Hinzufügen der Bilder des Teams. Das erste Bild wird gesondert gemarkert, die restlichen 
   * werden als Liste behandelt.
   * @param $gSubPartArray globales Subpart-Array, welches die Ergebnisse aufnimmt
   * @param $firstMarkerArray
   */
//  private function _addTeamPictures(&$gSubpartArray, &$firstMarkerArray, $team, $formatter, $template, $teamConfId, $teamMarker) {
//    // Das erste Bild ermitteln
//    $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_teams', $team->uid, 'dam_images');
//    list($uid, $filePath) = each($damPics['files']);
//
//    if(count($damPics['files']) == 0) { // Keine Bilder vorhanden
//      // Alle Marker löschen
//      $firstMarkerArray['###'. $teamMarker .'_FIRST_PICTURE_IMGTAG###'] = '';
//      $gSubpartArray['###'. $teamMarker .'_PICTURES###'] = '';
//      tx_rnbase_util_FormatUtil::fillEmptyMarkers($firstMarkerArray, 
//                        tx_rnbase_util_FormatUtil::getDAMColumns(), $teamMarker.'_FIRST_PICTURE_');
//      return;
//    }
//
//    $mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
//    $media = new $mediaClass($filePath);
//		// Check DAM-Version
//		if(method_exists($media, 'fetchFullMetaData'))
//			$media->fetchFullMetaData();
//		else
//			$media->fetchFullIndex();
//    $markerFirst = $formatter->getItemMarkerArray4DAM($media, $teamConfId.'firstImage.', $teamMarker.'_FIRST_PICTURE');
//    $firstMarkerArray = array_merge($firstMarkerArray, $markerFirst);
//
//    // Jetzt ersetzen wir die weiteren Bilder
//    // Zuerst wieder das Template laden
//    $gPictureTemplate = $formatter->cObj->getSubpart($template,'###'. $teamMarker .'_PICTURES###');
//
//    $pictureTemplate = $formatter->cObj->getSubpart($gPictureTemplate,'###'. $teamMarker .'_PICTURES_2###');
//    $markerArray = array();
//    $out = '';
//
////t3lib_div::debug($gPictureTemplate, 'utl_teammarker');
//    // Alle Bilder hinzufügen
//    while(list($uid, $filePath) = each($damPics['files'])) {
//      $media = new $mediaClass($filePath);
//      $markerArray = $formatter->getItemMarkerArray4DAM($media, $teamConfId.'images.',$teamMarker.'_PICTURE');
//      $out .= $formatter->cObj->substituteMarkerArrayCached($pictureTemplate, $markerArray);
//    }
//    // Der String mit den Bilder ersetzt jetzt den Subpart ###TEAM_PICTURES_2###
//    if(strlen(trim($out)) > 0) {
//      $subpartArray['###'. $teamMarker .'_PICTURES_2###'] = $out;
//      $out = $formatter->cObj->substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
//    }
//    $gSubpartArray['###'. $teamMarker .'_PICTURES###'] = $out;
//  }

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
	protected function prepareLinks(&$team, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid), $template);
		if($team->hasReport()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showteam', $marker, array('teamId' => $team->uid), $template);
		}
		else {
			$linkId = 'showteam';
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_TeamMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_TeamMarker.php']);
}
?>