<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2013 Rene Nitzsche (rene@system25.de)
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
 * Diese Klasse ist für die Erstellung von Markerarrays für Spiele verantwortlich
 */
class tx_cfcleaguefe_util_MatchMarker extends tx_rnbase_util_BaseMarker{
	private $fullMode = true;

  /**
   * Erstellt eine neue Instanz
   * @param $options Array with options. not used until now.
   */
  function __construct(&$options = array()) {
    // Den TeamMarker erstellen
  	$this->teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
  	$this->competitionMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_CompetitionMarker');
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
		tx_rnbase_util_Misc::callHook('cfc_league_fe','matchMarker_initRecord', 
			array('match' => &$match, 'template'=>&$template, 'confid'=>$confId, 'marker'=>$marker, 'formatter'=>$formatter), $this);

		// Jetzt die dynamischen Werte setzen, dafür müssen die Ticker vorbereitet werden
		if($this->fullMode) {
			$this->pushTT('addDynamicMarkers');
			$this->addDynamicMarkers($template, $match, $formatter, $confId,$marker);
			$this->pullTT();
		}
		// Das Markerarray wird mit den Spieldaten und den Teamdaten gefüllt
		$ignore = self::findUnusedCols($match->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($match->record, $confId, $ignore, $marker.'_');

		$wrappedSubpartArray = array();
		$subpartArray = array();
		$this->prepareLinks($match, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
		// Es wird jetzt das Template verändert und die Daten der Teams eingetragen
		$this->pushTT('parse home team');
		if($this->containsMarker($template, $marker.'_HOME'))
			$template = $this->teamMarker->parseTemplate($template, $match->getHome(), $formatter, $confId.'home.', $marker.'_HOME');
		$this->pullTT();
		$this->pushTT('parse guest team');
		if($this->containsMarker($template, $marker.'_GUEST'))
			$template = $this->teamMarker->parseTemplate($template, $match->getGuest(), $formatter, $confId.'guest.', $marker.'_GUEST');
		$this->pushTT('parse arena');
		if($this->containsMarker($template, $marker.'_ARENA_'))
			$template = $this->_addArena($template, $match, $formatter, $confId.'arena.', $marker.'_ARENA');
		if($this->containsMarker($template, $marker.'_SETRESULTS'))
			$template = $this->_addSetResults($template, $match, $formatter, $confId.'setresults.', $marker.'_SETRESULT');
		$this->pullTT();

		$template = $this->addTickerLists($template, $match, $formatter, $confId,$marker);

		if($this->fullMode) {
			$this->pushTT('add media');
			$this->_addMedia($subpartArray, $markerArray,$match,$formatter, $template, $confId, $marker);
			$this->pullTT();
		}
		// Add competition
		$template = $this->competitionMarker->parseTemplate($template, $match->getCompetition(), $formatter, $confId.'competition.', $marker.'_COMPETITION');
    
		$this->setMatchSubparts($template, $markerArray, $subpartArray, $wrappedSubpartArray, $match, $formatter);
//$total['total'] = t3lib_div::milliseconds() - $time;
//if($total['total'] > 40	)
//t3lib_div::debug($total, 'tx_cfcleaguefe_views_MatchMarker'); // TODO: Remove me!
		$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		tx_rnbase_util_Misc::callHook('cfc_league_fe','matchMarker_afterSubst', 
			array('match' => &$match, 'template'=>&$template, 'confid'=>$confId, 'marker'=>$marker, 'formatter'=>$formatter), $this);
		return $template;

		// Now lookout for external marker services.
//		$markerArray = array();
//		$subpartArray = array();
//		$wrappedSubpartArray = array();
//    
//		$params['confid'] = $confId;
//		$params['marker'] = $marker;
//		$params['match'] = $match;
//		self::callModules($template, $markerArray, $subpartArray, $wrappedSubpartArray, $params, $formatter);
//		return $formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
	}

	/**
	 * Integriert die Satzergebnisse
	 * 
	 * @param string $template
	 * @param tx_cfcleaguefe_models_match $item
	 * @param $formatter
	 * @param $confId
	 * @param $markerPrefix
	 */
	protected function _addSetResults($template, $item, $formatter, $confId, $markerPrefix) {
    if(strlen(trim($template)) == 0) return '';
    $sets = $item->getSets();
		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
		$out = $listBuilder->render($sets,
					false, $template, 'tx_rnbase_util_SimpleMarker',
					$confId, $markerPrefix, $formatter, $options);
		return $out;
	}
	/**
	 * Bindet die Arena ein
	 *
	 * @param string $template
	 * @param tx_cfcleaguefe_models_match $item
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $markerPrefix
	 * @return string
	 */
	protected function _addArena($template, $item, $formatter, $confId, $markerPrefix) {
		$sub = $item->getArena();
		if(!$sub) {
			// Kein Stadium vorhanden. Leere Instanz anlegen und altname setzen
			$sub = tx_rnbase_util_BaseMarker::getEmptyInstance('tx_cfcleague_models_Stadium');
		}
		$sub->record['altname'] = $item->record['stadium'];
		$marker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_StadiumMarker');
		$template = $marker->parseTemplate($template, $sub, $formatter, $confId, $markerPrefix);
		return $template;
	}

	/**
	 * Im folgenden werden einige Personenlisten per TS aufbereitet. Jede dieser Listen 
	 * ist über einen einzelnen Marker im FE verfügbar. Bei der Ausgabe der Personen
	 * werden auch vorhandene MatchNotes berücksichtigt, so daß ein Spieler mit gelber 
	 * Karte diese z.B. neben seinem Namen angezeigt bekommt.
	 *
	 * @param tx_cfcleaguefe_models_match $match
	 */
	private function prepareFields(&$match) {
		// Zuerst einen REGISTER-Wert für die Altergruppe setzen. Dieser kann bei der
		// Linkerstellung verwendet werden.
		try {
			// Zuerst die Teams prüfen
			$groupId = $match->getHome()->getAgeGroupUid();
			$groupId = $groupId ? $groupId : $match->getGuest()->getAgeGroupUid();
			if(!$groupId) {
				$competition = $match->getCompetition();
				$group = $competition->getGroup(false);
				$groupId = $group ? $group->getUid() : 0;
			}
			$GLOBALS['TSFE']->register['T3SPORTS_GROUP'] = $groupId;
		}
		catch(Exception $e) {
			$GLOBALS['TSFE']->register['T3SPORTS_GROUP'] = 0;
		}

		$match->record['pictures'] = $match->record['dam_images'];
		$match->record['firstpicture'] = $match->record['dam_images'];
		
		$report =&$match->getMatchReport();
		if(!is_object($report)) return;
		// Die Aufstellungen setzen
		$match->record['lineup_home'] = $report->getLineupHome('matchreport.lineuphome.');
		$match->record['lineup_guest'] = $report->getLineupGuest('matchreport.lineupguest.');
		$match->record['substnames_home'] = $report->getSubstituteNamesHome('matchreport.substnameshome.');
		$match->record['substnames_guest'] = $report->getSubstituteNamesGuest('matchreport.substnamesguest.');
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
	 * Add dynamic defined markers for profiles and matchnotes
	 *
	 * @param string $template
	 * @param tx_cfcleaguefe_models_match $match
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $matchConfId
	 * @param string $matchMarker
	 * @return string
	 */
	private function addTickerLists($template, $match, $formatter, $matchConfId, $matchMarker) {
		$configurations = $formatter->getConfigurations();
		$dynaMarkers = $configurations->getKeyNames($matchConfId.'tickerLists.');
		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');

		for($i=0, $size = count($dynaMarkers); $i < $size; $i++) {
			// Prüfen ob der Marker existiert
			$markerPrefix = $matchMarker .'_'.strtoupper($dynaMarkers[$i]);
			if(!self::containsMarker($template, $markerPrefix))
				continue;
			$confId = $matchConfId.'tickerLists.'.$dynaMarkers[$i] .'.';
			// Jetzt der DB Zugriff. Wir benötigen aber eigentlich nur die UIDs. Die eigentlichen Objekte 
			// stehen schon im report bereit
	    $srv = tx_cfcleague_util_ServiceRegistry::getMatchService();
			$fields = array();
	    $fields['MATCHNOTE.GAME'][OP_EQ_INT] = $match->getUid();
			$options = array();
			$options['what'] = 'uid';
			tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $confId.'filter.fields.');
			tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $confId.'filter.options.');
			$children = $srv->searchMatchNotes($fields, $options);
			// Die gefundenen Notes werden jetzt durch ihre aufbereiteten Dublikate ersetzt
			$items = array();
			$tickerHash = $this->getTickerHash($match);
			for($i=0, $cnt=count($children); $i < $cnt; $i++) {
				if(array_key_exists($children[$i]['uid'], $tickerHash)) {
					$items[] = $tickerHash[$children[$i]['uid']];
				}
			}

			$template = $listBuilder->render($items,
							false, $template, 'tx_cfcleaguefe_util_MatchNoteMarker',
							$confId, $markerPrefix, $formatter);
		}
		return $template;
	}
	/**
	 * Liefert die Ticker als Hash. Key ist die UID des Datensatzes
	 * @param tx_cfcleague_models_Match $match
	 */
	protected function getTickerHash($match) {
		if(!is_array($this->tickerHash)) {
			$this->tickerHash = array();
      $tickerArr =& tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
			for($i=0, $cnt=count($tickerArr); $i<$cnt; $i++)
				$this->tickerHash[$tickerArr[$i]->uid] = $tickerArr[$i];
		}
		return $this->tickerHash;
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
               tx_rnbase_util_Templates::substituteMarkerArrayCached($subTemplate, 
                                               $markerArray, $subpartArray, $wrappedSubpartArray);
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
		if(!t3lib_extMgm::isLoaded('dam')) {
			// Not supported without DAM!
			$gSubpartArray['###'. $baseMarker .'_MEDIAS###'] = $out;
			return;
		}
		
		$damMedia = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $match->uid, 'dam_media');
		if(count($damMedia['files']) == 0) { // Keine Daten vorhanden
			// Alle Marker löschen
			$gSubpartArray['###'. $baseMarker .'_MEDIAS###'] = '';
			return;
		}

//		$mediaClass = tx_rnbase::makeInstanceClassName('tx_dam_media');
    
		// Zuerst wieder das Template laden
		$gPictureTemplate = tx_rnbase_util_Templates::getSubpart($template,'###'. $baseMarker .'_MEDIAS###');

		$pictureTemplate = tx_rnbase_util_Templates::getSubpart($gPictureTemplate,'###'. $baseMarker .'_MEDIA###');
		$markerArray = array();
		$out = '';
		$serviceObj = t3lib_div::makeInstanceService('mediaplayer');

		// Alle Daten hinzufügen
		while(list($uid, $filePath) = each($damMedia['files'])) {
			$media = tx_rnbase::makeInstance('tx_dam_media', $filePath);
//			$media = new $mediaClass($filePath);
			$markerArray = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'media.',$baseMarker.'_MEDIA');
			$markerArray['###'. $baseMarker.'_MEDIA_PLAYER###'] = is_object($serviceObj) ? $serviceObj->getPlayer($damMedia['rows'][$uid], $formatter->configurations->get($baseConfId.'media.')) : '<b>No media service available</b>';
			$out .= tx_rnbase_util_Templates::substituteMarkerArrayCached($pictureTemplate, $markerArray);
		}
		// Der String mit den Bilder ersetzt jetzt den Subpart ###MATCH_MEDIAS_2###
		if(strlen(trim($out)) > 0) {
			$subpartArray['###'. $baseMarker .'_MEDIA###'] = $out;
			$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); //, $wrappedSubpartArray);
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
	private function prepareLinks(&$match, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		$linkId = 'report';
		if($match->hasReport()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array('matchId' => $match->uid), $template);
		}
		else {
			$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
			$remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled')); 
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
		}
		$linkId = 'ticker';
		if($match->isTicker()) {
			$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array('matchId' => $match->uid), $template);
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