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

/**
 * Diese Klasse ist für die Erstellung von Markerarrays für Spiele verantwortlich
 */
class tx_cfcleaguefe_util_MatchMarker {
	private $fullMode = true;

  /**
   * Erstellt eine neue Instanz
   * @param $$options Array with options. not used until now.
   */
  function tx_cfcleaguefe_util_MatchMarker(&$options = null) {
    // Den TeamMarker erstellen
    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $this->teamMarker = new $teamMarkerClass;
  }

  /**
   * Set fillMode on or off 
   *
   * @param boolean $mode
   */
  public function setFullMode($mode) {
  	$this->fullMode = $mode;
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
  public function parseTemplate($template, &$match, &$formatter, $matchConfId, $matchMarker = 'MATCH') {
    if(!is_object($match)) {
      return $formatter->configurations->getLL('match.notFound');
    }
    $this->initLinks($formatter->configurations, $matchConfId);
//$time = t3lib_div::milliseconds();
    
    $this->prepareFields($match);
    // Jetzt die dynamischen Werte setzen, dafür müssen die Ticker vorbereitet werden
    if($this->fullMode)
	    $this->addDynamicMarkers($template, $match, $formatter, $matchConfId,$matchMarker);
    // Das Markerarray wird mit den Spieldaten und den Teamdaten gefüllt
    $markerArray = $formatter->getItemMarkerArrayWrapped($match->record, $matchConfId, 0, $matchMarker.'_');
    
    $subpartArray = array();
    // Es wird jetzt das Template verändert und die Daten der Teams eingetragen
    if($this->fullMode) {
	    $template = $this->teamMarker->parseTemplate($template, $match->getHome(), $formatter, $matchConfId.'home.', 'MATCH_HOME');
  	  $template = $this->teamMarker->parseTemplate($template, $match->getGuest(), $formatter, $matchConfId.'guest.', 'MATCH_GUEST');
	    $this->_addPictures($subpartArray, $markerArray,$match,$formatter, $template, $matchConfId, $matchMarker);
	    $this->_addMedia($subpartArray, $markerArray,$match,$formatter, $template, $matchConfId, $matchMarker);
    }
    
    $wrappedSubpartArray = array('###'.$matchMarker.'_LINK###' => $noLink, 
                                 '###'.$matchMarker.'_HOME_LINK###' => $noLink, 
                                 '###'.$matchMarker.'_GUEST_LINK###' => $noLink);

    $this->prepareLinks($match, $matchMarker, $markerArray, $wrappedSubpartArray);

    $this->setMatchSubparts($template, $markerArray, $subpartArray, $wrappedSubpartArray, $match, $formatter);
//$total['total'] = t3lib_div::milliseconds() - $time;
//if($total['total'] > 40	)
//t3lib_div::debug($total, 'tx_cfcleaguefe_views_MatchMarker'); // TODO: Remove me!
    
    return $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
  }

  /**
   * Im folgenden werden einige Personenliste per TS aufbereitet. Jede dieser Listen 
   * ist über einen einzelnen Marker im FE verfügbar. Bei der Ausgabe der Personen
   * werden auch vorhandene MatchNotes berücksichtigt, so daß ein Spieler mit gelber 
   * Karte diese z.B. neben seinem Namen angezeigt bekommt.
   *
   * @param tx_cfcleaguefe_models_match $match
   */
  private function prepareFields(&$match) {
    $report =&$match->getMatchReport();
    if(!is_object($report)) return;
    // Die Aufstellungen setzen
    $match->record['lineup_home'] = $report->getLineupHome('matchreport.lineup.');
    $match->record['lineup_guest'] = $report->getLineupGuest('matchreport.lineup.');
    $match->record['substnames_home'] = $report->getSubstituteNamesHome('matchreport.substnames.');
    $match->record['substnames_guest'] = $report->getSubstituteNamesGuest('matchreport.substnames.');
    $match->record['coachnames_home'] = $report->getCoachNameHome('matchreport.coachnames.');
    $match->record['coachnames_guest'] = $report->getCoachNameGuest('matchreport.coachnames.');
    $match->record['refereenames'] = $report->getRefereeName('matchreport.refereenames.');
    $match->record['assistsnames'] = $report->getAssistNames('matchreport.assistsnames.');
    //    t3lib_div::debug($match->record['lineup_home'], 'tx_cfcleaguefe_util_MatchMarker');
  }

  /**
   * Add dynamic defined markers for profiles and matchnotes
   *
   * @param string $template
   * @param tx_cfcleaguefe_models_match $match
   * @param tx_rnbase_util_FormatUtil unknown_type $formatter
   * @param string $matchConfId
   * @param string $matchMarker
   * @return string
   */
  private function addDynamicMarkers($template, &$match, &$formatter, $matchConfId, $matchMarker) {
    $report =&$match->getMatchReport();
    if(!is_object($report)) return $template;

    $dynaMarkers = $formatter->configurations->getKeyNames($matchConfId.'dynaMarkers.');
    
    for($i=0, $size = count($dynaMarkers); $i < $size; $i++) {
      $typeArr = $formatter->configurations->get($matchConfId.'dynaMarkers.'.$dynaMarkers[$i] .'.');

      $match->record[$dynaMarkers[$i]] = $report->getTickerList($matchConfId.'dynaMarkers.'.$dynaMarkers[$i] .'.');
//      t3lib_div::debug($typeArr, '#'. $dynaMarkers[$i] . '# tx_cfcleaguefe_util_MatchMarker');
    }
  }

  /**
   * Die Anzeige des Spiels kann je nach Status variieren. Daher gibt es dafür verschiedene Template-Subparts.
   * ###RESULT_STATUS_-1###, ###RESULT_STATUS_0###, ###RESULT_STATUS_1### und ###RESULT_STATUS_2###.
   * Übersetzt bedeutet das "ungültig", "angesetzt", "läuft" und "beendet".
   */
  function setMatchSubparts($template, &$markerArray, &$subpartArray, &$wrappedSubpartArray, &$match, &$formatter) {
    // Je Spielstatus wird ein anderer Subpart gefüllt
    for($i = -1; $i < 3; $i++) {
      $subpartArray['###RESULT_STATUS_'.$i.'###'] = '';
    }
    $subpartArray['###RESULT_STATUS_-10###'] = '';
    
    $subTemplate = $formatter->cObj->getSubpart($template, '###RESULT_STATUS_'.$match->record['status'].'###');
    if($subTemplate)
      $subpartArray['###RESULT_STATUS_'.$match->record['status'].'###'] = 
               $formatter->cObj->substituteMarkerArrayCached($subTemplate, 
                                               $markerArray, $subpartArray, $wrappedSubpartArray);
  }

  /**
   * Vorbereiten der Links 
   *
   * @param tx_cfcleaguefe_match $match
   * @param string $matchMarker
   * @param array $markerArray
   * @param array $wrappedSubpartArray
   */
  private function prepareLinks(&$match, $matchMarker, &$markerArray, &$wrappedSubpartArray) {
    // Links vorbereiten
    if($this->links['match'] && $match->hasReport()) {
      $link = $this->links['match'];
      $link->parameters(array('matchId' => $match->uid));
      $wrappedSubpartArray['###'.$matchMarker.'_LINK###'] = explode($this->token, $link->makeTag());
      $markerArray['###'.$matchMarker.'_LINK_URL###'] = $link->makeUrl();
    }
    if($this->links['ticker'] && $match->isTicker()) {
      $link = $this->links['ticker'];
      $link->parameters(array('matchId' => $match->uid));
      $wrappedSubpartArray['###'.$matchMarker.'_TICKER_LINK###'] = explode($this->token, $link->makeTag());
      $markerArray['###'.$matchMarker.'_TICKER_LINK_URL###'] = $link->makeUrl();
    }
//    // Die Links auf die Teams
//    if($this->links['team']) {
//      $link = $this->links['team'];
//      if(intval($match->record['home_link_report'])) {
//        $link->parameters(array('teamId' => $match->record['home']));
//        $wrappedSubpartArray['###'.$matchMarker.'_HOME_LINK###'] = explode($this->token, $link->makeTag());
//      }
//      if(intval($match->record['guest_link_report'])) {
//        $link->parameters(array('teamId' => $match->record['guest']));
//        $wrappedSubpartArray['###'.$matchMarker.'_GUEST_LINK###'] = explode($this->token, $link->makeTag());
//      }
////t3lib_div::debug($wrappedSubpartArray, 'utl_matchmarker');
//    }
  }
  
  /**
   * Hinzufügen der Bilder des Spiels. Das erste Bild wird gesondert gemarkert, die restlichen 
   * werden als Liste behandelt.
   * @param $gSubPartArray globales Subpart-Array, welches die Ergebnisse aufnimmt
   * @param $firstMarkerArray
   */
  private function _addPictures(&$gSubpartArray, &$firstMarkerArray, $match, $formatter, $template, $baseConfId, $baseMarker) {
    // Das erste Bild ermitteln
    $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $match->uid, 'dam_images');
    list($uid, $filePath) = each($damPics['files']);

    if(count($damPics['files']) == 0) { // Keine Bilder vorhanden
      // Alle Marker löschen
      $firstMarkerArray['###'. $baseMarker .'_FIRST_PICTURE###'] = '';
      $gSubpartArray['###'. $baseMarker .'_PICTURES###'] = '';
      tx_rnbase_util_FormatUtil::fillEmptyMarkers($firstMarkerArray, 
                        tx_rnbase_util_FormatUtil::getDAMColumns(), $baseMarker.'_FIRST_PICTURE_');
      return;
    }

    $mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
    $media = new $mediaClass($filePath);
//    $media->fetchFullMetaData();
    $media->fetchFullIndex();
    $markerFirst = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'firstImage.', $baseMarker.'_FIRST_PICTURE');
    $firstMarkerArray = array_merge($firstMarkerArray, $markerFirst);
//t3lib_div::debug($formatter->cObj->data, 'match_marker');
    
    // Jetzt ersetzen wir die weiteren Bilder
    // Zuerst wieder das Template laden
    $gPictureTemplate = $formatter->cObj->getSubpart($template,'###'. $baseMarker .'_PICTURES###');

    $pictureTemplate = $formatter->cObj->getSubpart($gPictureTemplate,'###'. $baseMarker .'_PICTURES_2###');
    $markerArray = array();
    $out = '';
//    reset($damPics);

		// Alle Bilder hinzufügen
    while(list($uid, $filePath) = each($damPics['files'])) {
      $media = new $mediaClass($filePath);
      $markerArray = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'images.',$baseMarker.'_PICTURE');
      $out .= $formatter->cObj->substituteMarkerArrayCached($pictureTemplate, $markerArray);
    }
    // Der String mit den Bilder ersetzt jetzt den Subpart ###TEAM_PICTURES_2###
    if(strlen(trim($out)) > 0) {
      $subpartArray['###'. $baseMarker .'_PICTURES_2###'] = $out;
      $out = $formatter->cObj->substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
    }
    $gSubpartArray['###'. $baseMarker .'_PICTURES###'] = $out;
  }

  /**
   * Die vorhandenen Mediadateien hinzufügen
   *
   * @param array $gSubpartArray
   * @param array $firstMarkerArray
   * @param tx_cfcleaguefe_models_match $match
   * @param tx_rnbase_util_FormatUtil $formatter
   * @param string $template
   * @param string $baseConfId
   * @param string $baseMarker
   */
  private function _addMedia(&$gSubpartArray, &$firstMarkerArray, $match, $formatter, $template, $baseConfId, $baseMarker) {
    // Das erste Bild ermitteln
    $damMedia = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $match->uid, 'dam_media');

    if(count($damMedia['files']) == 0) { // Keine Daten vorhanden
      // Alle Marker löschen
      $gSubpartArray['###'. $baseMarker .'_MEDIAS###'] = '';
      return;
    }

    $mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
//t3lib_div::debug($formatter->cObj->data, 'match_marker');
    
    // Zuerst wieder das Template laden
    $gPictureTemplate = $formatter->cObj->getSubpart($template,'###'. $baseMarker .'_MEDIAS###');

    $pictureTemplate = $formatter->cObj->getSubpart($gPictureTemplate,'###'. $baseMarker .'_MEDIAS_2###');
    $markerArray = array();
    $out = '';
    $serviceObj = t3lib_div::makeInstanceService('mediaplayer');

//t3lib_div::debug($damMedia, 'utl_teammarker');
    // Alle Daten hinzufügen
    while(list($uid, $filePath) = each($damMedia['files'])) {
      $media = new $mediaClass($filePath);
      $markerArray = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'media.',$baseMarker.'_MEDIA');
      $markerArray['###'. $baseMarker.'_MEDIA###'] = is_object($serviceObj) ? $serviceObj->getPlayer($damMedia['rows'][$uid], $formatter->configurations->get($baseConfId.'media.')) : '<b>No media service available</b>';
      $out .= $formatter->cObj->substituteMarkerArrayCached($pictureTemplate, $markerArray);
    }
    // Der String mit den Bilder ersetzt jetzt den Subpart ###MATCH_MEDIAS_2###
    if(strlen(trim($out)) > 0) {
      $subpartArray['###'. $baseMarker .'_MEDIAS_2###'] = $out;
      $out = $formatter->cObj->substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
    }
    $gSubpartArray['###'. $baseMarker .'_MEDIAS###'] = $out;
  }

  /**
   * Create Links
   *
   * @param tx_rnbase_configurations $configurations
   * @param string $teamConfId
   */
  protected function initLinks(&$configurations, $confId) {

    $this->token = md5(microtime());
  	$this->links['match'] =& $configurations->createLink();
    $this->links['match']->destination(intval($configurations->get($confId.'links.match.parameter')));
    $this->links['match']->label($this->token);
  }
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchMarker.php']);
}
?>