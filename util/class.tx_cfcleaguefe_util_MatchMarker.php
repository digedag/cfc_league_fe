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
 * Diese Klasse ist für die Erstellung von Markerarrays für Spiele verantwortlich
 */
class tx_cfcleaguefe_util_MatchMarker extends tx_rnbase_util_BaseMarker{
	private $fullMode = true;

  /**
   * Erstellt eine neue Instanz
   * @param $options Array with options. not used until now.
   */
  function tx_cfcleaguefe_util_MatchMarker(&$options = array()) {
    // Den TeamMarker erstellen
    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $this->teamMarker = new $markerClass;
    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_CompetitionMarker');
    $this->competitionMarker = new $markerClass;
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
   * @param tx_cfcleaguefe_models_match $match das Spiel
   * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
   * @param $confId Pfad der TS-Config des Spiels, z.B. 'listView.match.'
   * @param $marker Name des Markers für ein Spiel, z.B. MATCH
   * @return String das geparste Template
   */
	public function parseTemplate($template, &$match, &$formatter, $confId, $marker = 'MATCH') {
		if(!is_object($match)) {
			return $formatter->configurations->getLL('match_notFound');
		}
//$time = t3lib_div::milliseconds();
    
		$this->prepareFields($match);
		// Jetzt die dynamischen Werte setzen, dafür müssen die Ticker vorbereitet werden
		if($this->fullMode) {
			$this->pushTT('addDynamicMarkers');
			$this->addDynamicMarkers($template, $match, $formatter, $confId,$marker);
			$this->pullTT();
		}
		// Das Markerarray wird mit den Spieldaten und den Teamdaten gefüllt
		$markerArray = $formatter->getItemMarkerArrayWrapped($match->record, $confId, 0, $marker.'_');
		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($match, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter);

		// Es wird jetzt das Template verändert und die Daten der Teams eingetragen
		$this->pushTT('parse home team');
		if($this->containsMarker($template, $marker.'_HOME'))
			$template = $this->teamMarker->parseTemplate($template, $match->getHome(), $formatter, $confId.'home.', $marker.'_HOME');
		$this->pullTT();
		$this->pushTT('parse guest team');
		if($this->containsMarker($template, $marker.'_GUEST'))
			$template = $this->teamMarker->parseTemplate($template, $match->getGuest(), $formatter, $confId.'guest.', $marker.'_GUEST');
		$this->pullTT();
		if($this->fullMode) {
			$this->pushTT('add media');
			$this->_addPictures($subpartArray, $markerArray,$match,$formatter, $template, $confId, $marker);
			$this->_addMedia($subpartArray, $markerArray,$match,$formatter, $template, $confId, $marker);
			$this->pullTT();
		}
		// Add competition
		$template = $this->competitionMarker->parseTemplate($template, $match->getCompetition(), $formatter, $confId.'competition.', $marker.'_COMPETITION');
    
		$this->setMatchSubparts($template, $markerArray, $subpartArray, $wrappedSubpartArray, $match, $formatter);
//$total['total'] = t3lib_div::milliseconds() - $time;
//if($total['total'] > 40	)
//t3lib_div::debug($total, 'tx_cfcleaguefe_views_MatchMarker'); // TODO: Remove me!
		$template = $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

		// Now lookout for external marker services.
		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();
    
		$params['confid'] = $confId;
		$params['marker'] = $marker;
		$params['match'] = $match;
		self::callModules($template, $markerArray, $subpartArray, $wrappedSubpartArray, $params, $formatter);
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
	 * @param tx_rnbase_util_FormatUtil $formatter
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
   * ###RESULT_STATUS_-1###, ###RESULT_STATUS_0###, ###RESULT_STATUS_1###, ###RESULT_STATUS_2### und ###RESULT_STATUS_-10###.
   * Übersetzt bedeutet das "ungültig", "angesetzt", "läuft", "beendet" und "verschoben".
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
   * Hinzufügen der Bilder des Spiels. Das erste Bild wird gesondert gemarkert, die restlichen 
   * werden als Liste behandelt.
   * @param $gSubPartArray globales Subpart-Array, welches die Ergebnisse aufnimmt
   * @param $firstMarkerArray
   */
  private function _addPictures(&$gSubpartArray, &$firstMarkerArray, $match, $formatter, $template, $baseConfId, $baseMarker) {
  	// Prüfen, ob Marker vorhanden sind
  	if(!(self::containsMarker($template, $baseMarker .'_FIRST_PICTURE') && self::containsMarker($template, $baseMarker .'_PICTURES')))
  		return;
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
		// Check DAM-Version
		if(method_exists($media, 'fetchFullMetaData'))
			$media->fetchFullMetaData();
		else
			$media->fetchFullIndex();
    $markerFirst = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'firstImage.', $baseMarker.'_FIRST_PICTURE');
    $firstMarkerArray = array_merge($firstMarkerArray, $markerFirst);
//t3lib_div::debug($formatter->cObj->data, 'match_marker');
    
    // Jetzt ersetzen wir die weiteren Bilder
    // Zuerst wieder das Template laden
    $gPictureTemplate = $formatter->cObj->getSubpart($template,'###'. $baseMarker .'_PICTURES###');

    $pictureTemplate = $formatter->cObj->getSubpart($gPictureTemplate,'###'. $baseMarker .'_PICTURE###');
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
      $subpartArray['###'. $baseMarker .'_PICTURE###'] = $out;
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
  	// Prüfen, ob Marker vorhanden sind
		if(!self::containsMarker($template, $baseMarker .'_MEDIAS'))
			return;

		$damMedia = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $match->uid, 'dam_media');
		if(count($damMedia['files']) == 0) { // Keine Daten vorhanden
			// Alle Marker löschen
			$gSubpartArray['###'. $baseMarker .'_MEDIAS###'] = '';
			return;
		}

		$mediaClass = tx_div::makeInstanceClassName('tx_dam_media');
    
		// Zuerst wieder das Template laden
		$gPictureTemplate = $formatter->cObj->getSubpart($template,'###'. $baseMarker .'_MEDIAS###');

		$pictureTemplate = $formatter->cObj->getSubpart($gPictureTemplate,'###'. $baseMarker .'_MEDIA###');
		$markerArray = array();
		$out = '';
		$serviceObj = t3lib_div::makeInstanceService('mediaplayer');

		// Alle Daten hinzufügen
		while(list($uid, $filePath) = each($damMedia['files'])) {
			$media = new $mediaClass($filePath);
			$markerArray = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'media.',$baseMarker.'_MEDIA');
			$markerArray['###'. $baseMarker.'_MEDIA_PLAYER###'] = is_object($serviceObj) ? $serviceObj->getPlayer($damMedia['rows'][$uid], $formatter->configurations->get($baseConfId.'media.')) : '<b>No media service available</b>';
			$out .= $formatter->cObj->substituteMarkerArrayCached($pictureTemplate, $markerArray);
		}
		// Der String mit den Bilder ersetzt jetzt den Subpart ###MATCH_MEDIAS_2###
		if(strlen(trim($out)) > 0) {
			$subpartArray['###'. $baseMarker .'_MEDIA###'] = $out;
			$out = $formatter->cObj->substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
		}
		$gSubpartArray['###'. $baseMarker .'_MEDIAS###'] = $out;
	}

	/**
	 * Links vorbereiten
	 *
	 * @param tx_cfcleaguefe_models_match $match
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	private function prepareLinks(&$match, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter) {
		$linkId = 'report';
		if($match->hasReport()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array('matchId' => $match->uid));
		}
		else {
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
		$linkId = 'ticker';
		if($match->isTicker()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array('matchId' => $match->uid));
		}
		else {
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchMarker.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchMarker.php']);
}
?>