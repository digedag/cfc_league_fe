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

// Die Datenbank-Klasse
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');
require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'models/class.tx_cfcleaguefe_models_match.php');

require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'models/class.tx_cfcleaguefe_models_profile.php');
require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_MatchTicker.php');
require_once(t3lib_extMgm::extPath('dam') . 'lib/class.tx_dam_media.php');


/**
 * Model für einen Spielbericht.
 * Über diese Klasse können Informationen zu einem Spiel abgerufen werden.
 */
class tx_cfcleaguefe_models_matchreport {
  var $match, $_configurations;
  var $_formatter;
  var $_tickerArr; // enthält alle Tickermeldungen

  /**
   * Konstruktor
   * Im Gegensatz zu anderen Modelklassen, holt sich diese Klasse den notwendigen Formatter
   * direkt aus der Configuration
   *
   * @param $matchId UID eines Spiels
   */
  function tx_cfcleaguefe_models_matchreport($matchId, &$configurations) {
    // Laden des Spiels
    $this->match = tx_cfcleaguefe_models_matchreport::_loadMatch($matchId);
    $this->match->setMatchReport($this);
    $this->_configurations = $configurations;

    $this->_formatter =& $configurations->getFormatter();

    // Die MatchNotes laden
    $this->_initMatchTicker();
  }

  /**
   * Returns the match instance
   *
   * @return tx_cfcleaguefe_models_match
   */
  function &getMatch(){
    return $this->match;
  }
  /**
   * Returns all match pictures as html string
   * @return HTML-String for match pictures
   */
  function getPictures() {
    $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $this->match->uid, 'dam_images');
    $out = '';
//t3lib_div::debug($this->_formatter->cObj->data, 'mdl_report');
    while(list($uid, $filePath) = each($damPics['files'])) {
      $out .= $this->_formatter->getDAMImage($filePath, 'matchreport.images.', 'cfc_league');
    }
    return $out;
  }


  /**
   * Es wird ein Array von String für die Darstellung von Mediendateien geliefert
   * @return array of string
   */
  function getMedia() {
    $arr = array();
    $damMedia = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $this->match->uid, 'dam_media');
    if (is_object($serviceObj = t3lib_div::makeInstanceService('mediaplayer'))) {

//t3lib_div::debug($damMedia, 'mdl_report');

      // Player holen
      while(list($uid, $media) = each($damMedia['rows'])) {
        $arr[] = $serviceObj->getPlayer($media, $this->_configurations->get('matchreport.media.'));
      }
    }
    return $arr;
  }

  /**
   * Liefert die Tickermeldungen der Strafen des Heimteams (außer Elfmeter)
   */
  function getPenaltiesHome() {
    $conf = $this->_configurations->get('matchreport.penalties.');
    // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isPenalty() && $ticker->isHome())
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,'matchreport.penalties.');
  }

  function getPenaltiesGuest() {
    $conf = $this->_configurations->get('matchreport.penalties.');
    // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isPenalty() && $ticker->isGuest())
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,'matchreport.penalties.');
  }

  /**
   * Liefert die Tickermeldungen der Spielerwechsel des Heimteams
   */
  function getChangesHome() {
    $conf = $this->_configurations->get('matchreport.changes.');
    // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isChange() && $ticker->isHome())
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,'matchreport.changes.');
  }

  /**
   * Liefert die Tickermeldungen der Spielerwechsel des Heimteams
   */
  function getChangesGuest() {
    $conf = $this->_configurations->get('report.changes.');
    // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isChange() && $ticker->isGuest())
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,'matchreport.changes.');
  }

  /**
   * Liefert die Tickermeldunge der Heimtorschützen
   */
  function getScorerHome() {
    $conf = $this->_configurations->get('matchreport.scorer.');
    // Aus dem gesamten Ticker suchen wir die Tormeldungen heraus und formatieren sie
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isGoalHome())
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,'matchreport.scorer.');
  }

  /**
   * Returns a list of tickers defined by Typoscript.
   *
   * @param string $confId
   */
  function getTickerList($confId) {
    $conf = $this->_configurations->get($confId);
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isVisible($conf))
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,$confId);
  }
  /**
   * Liefert die Tickermeldunge der Gasttorschützen
   */
  function getScorerGuest() {
    $conf = $this->_configurations->get('matchreport.scorer.');
    // Aus dem gesamten Ticker suchen wir die Tormeldungen heraus und formatieren sie
    $tickers = array();
    $tickerArr = $this->_getMatchTicker($conf['cron']);
    foreach($tickerArr As $ticker) {
      if($ticker->isGoalGuest())
        $tickers[] = $ticker;
    }
    return $this->_wrapTickers($tickers,'matchreport.scorer.');
  }

  /**
   * Liefert alle vorhandenen Ticker als Array
   * Die Tickermeldungen sind nach dem Aufruf schon für die FE Ausgabe vorbereitet. Spielerwechsel
   * sind z.B. schon zusammengelegt und Spielstände berechnet.
   *
   * @param $cron chronologischer Reihenfolge: "0" - 90 bis 0, "1" - 0 bis 90
   */
  function _getMatchTicker($cron = 0) {
    $ret = ($cron != 1) ? array_reverse($this->_tickerArr) : $this->_tickerArr;
    return $ret;
  }

  /**
   * Liefert alle vorhandenen Tickernews als Array
   * Die Tickermeldungen sind nach dem Aufruf schon für die FE Ausgabe vorbereitet. Spielerwechsel
   * sind z.B. schon zusammengelegt und Spielstände berechnet.
   *
   * @param $cron chronologischer Reihenfolge: "0" - 90 bis 0, "1" - 0 bis 90
   * @param $all wenn nicht 0 werden alle Meldungen geliefert, sonst entsprechend der Konfig
   */
  function getMatchTicker() {
    // Man kann einstellen welche Tickernachrichten gezeigt werden
    // z.B. soll evt. nicht jeder Eckball im Ticker erscheinen und ist nur für die Statistik interessant

    $conf = $this->_configurations->get('matchreport.tickerlist.');
    $tickers = array();
    $tickerArr = $this->_getMatchTicker();
    if($this->_configurations->get('tickerTypes')) {

//t3lib_div::debug($this->_configurations->get('tickerTypes'), 'mdl_report');

      foreach($tickerArr As $ticker) {
        if( !(t3lib_div::inList($this->_configurations->get('tickerTypes'), $ticker->getType()) ))
          $tickers[] = $ticker;
      }
    }
    else {
      $tickers = $tickerArr;
    }
//    return $this->_wrapTickers($tickers,'matchreport.tickerlist.');

    return $tickers;
  }

  /**
   * Liefert den Namen des Schiedsrichters
   */
  function getRefereeName($confId = 'matchreport.referee.') {
    return $this->_getNames2($this->match->getReferee(), $confId);

  }

  /**
   * Liefert die Namen der Linienrichters
   */
  function getAssistNames($confId = 'matchreport.assists.') {
    return $this->_getNames2($this->match->getAssists(), $confId);
  }

  /**
   * Liefert den Namen des Heimtrainers
   */
  function getCoachNameHome($confId = 'matchreport.coach.') {
    return $this->_getNames2($this->match->getCoachHome(), $confId);
  }

  /**
   * Liefert den Namen des Gasttrainers
   */
  function getCoachNameGuest($confId = 'matchreport.coach.') {
    return $this->_getNames2($this->match->getCoachGuest(), $confId);
  }

  /**
   * Liefert die Startaufstellung des Heimteams
   * @deprecated 
   */
  function getPlayerNamesHome() {
    return $this->_getLineUp($this->match->getPlayersHome(), $this->match->record['system_home'], 'matchreport.players.');
  }

  /**
   * Liefert den Namen der Spieler in der Startaufstellung des Heimteams
   * @deprecated 
   */
  function getPlayerNamesGuest() {
    return $this->getLineupGuest();
  }
  
  /**
   * Build the line_up string for home team
   * @param string $confId
   * @return string
   */
  function getLineupHome($confId = 'matchreport.players.') {
    return $this->_getLineUp($this->match->getPlayersHome(), $this->match->record['system_home'], $confId);
  }

  /**
   * Build the line_up string for home team
   * @param string $confId
   * @return string
   */
  function getLineupGuest($confId = 'matchreport.players.') {
    return $this->_getLineUp($this->match->getPlayersGuest(), $this->match->record['system_guest'], $confId);
  }
  

  /**
   * Liefert den Namen der Spieler in der Reservespieler des Heimteams
   * @param string $confId TS-Config
   */
  function getSubstituteNamesHome($confId = 'matchreport.substitutes.') {
    return $this->_getNames2($this->match->getSubstitutesHome(), $confId);
  }

  /**
   * Liefert den Namen der Spieler in der Reservespieler des Gastteams
   */
  function getSubstituteNamesGuest($confId = 'matchreport.substitutes.') {
    return $this->_getNames2($this->match->getSubstitutesGuest(), $confId);
  }

  /**
   * Liefert das Logo der Heimmannschaft als komplettes Image-Tag
   */
  function getLogoHome() {
    // Wir suchen den Verein der Heimmannschaft
    return $this->_getLogo($this->match->getHome());
  }

  /**
   * Liefert das Logo der Gastmannschaft als komplettes Image-Tag
   */
  function getLogoGuest() {
    return $this->_getLogo($this->match->getGuest());
  }

  /**
   * Liefert den Namen des Gastgebers
   */
  function getTeamNameHome() {
    return $this->match->getHome()->getColumnWrapped($this->_formatter, 'name', 'matchreport.teamHome.');
  }

  /**
   * Liefert den Namen des Gastes
   */
  function getTeamNameGuest() {
    return $this->match->getGuest()->getColumnWrapped($this->_formatter, 'name', 'matchreport.teamGuest.');
  }

  /**
   * Liefert den Spieltermin als String
   */
  function getDate() {
    return $this->match->getColumnWrapped($this->_formatter, 'date', 'matchreport.match.');
  }

  /**
   * Liefert das Stadion
   */
  function getStadium() {
    return $this->match->getColumnWrapped($this->_formatter, 'stadium', 'matchreport.match.');
  }

  /**
   * Liefert den Autor des Spielberichts
   */
  function getReportAuthor() {
    return $this->match->getColumnWrapped($this->_formatter, 'game_report_author', 'matchreport.match.');
  }

  /**
   * Liefert den Spielberichts
   */
  function getReport() {
    return $this->match->getColumnWrapped($this->_formatter, 'game_report', 'matchreport.match.');
  }

  /**
   * Liefert den Namen des Wettbewerbs
   */
  function getCompetitionName() {
    return $this->match->getColumnWrapped($this->_formatter, 'competition_name', 'matchreport.match.');
  }

  /**
   * Liefert den Namen der Spielrunde
   */
  function getRoundName() {
    return $this->match->getColumnWrapped($this->_formatter, 'round_name', 'matchreport.match.');
  }

  /**
   *
   */
  function getVisitors() {
    return $this->match->getColumnWrapped($this->_formatter, 'visitors', 'matchreport.match.');
  }

  /**
   * Initialisiert die MatchNotes. Diese werden auch den Spieler zugeordnet
   */
  function _initMatchTicker() {
    if(!is_array($this->_tickerArr)){
      // Der Ticker wird immer chronologisch ermittelt
      $this->_tickerArr =& tx_cfcleaguefe_util_MatchTicker::getTicker4Match($this->match, 0, 1);
      // Jetzt die Tickermeldungen noch den Spielern zuordnen
      $playersHome = $this->match->getPlayersHome(1);
      $playersGuest = $this->match->getPlayersGuest(1);

      for($i=0; $i < count($this->_tickerArr); $i++) {
        $note = $this->_tickerArr[$i];
        $player = &$note->getPlayer();

        if(is_object($player)) {
          if($note->isHome()) {
            if(is_object($player)) {
              $player->addMatchNote($note);
            }
          }
          else { // Gastspieler
//            $player =& $playersGuest[$player->uid]; //Funktioniert mit php5 nicht mehr
            if(is_object($player)) {
              $player->addMatchNote($note);
            }
          }
        }
      }
    }
  }


  /**
   * Liefert die gewrappten Namen einer Profilliste
   * @param array $profiles Array mit den Personen. Kann auch direkt ein Profil sein.
   * @param string $confIdAll TS-Config String. Sollte einen Eintrag profile. enthalten
   * @return einen String mit allen Namen
   */
  function _getNames2($profiles, $confIdAll) {
    $conf = $this->_configurations->get($confIdAll);
    $ret = $this->_wrapProfiles($profiles, $conf['profile.']);

    // Jetzt noch die einzelnen Strings verbinden 
    // Der Seperator sollte mit zwei Pipes eingeschlossen sein
    $sep = (strlen($conf['seperator']) > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : $conf['seperator'];
    $ret = implode($sep, $ret);
    // Jetzt noch ein Wrap über alles
    return $this->_formatter->stdWrap($ret, $conf);
//    return $ret;
  }

  /**
   * Erstellt die Wrapps für ein Array von Profiles. Der übergebene Parameter kann aber
   * auch ein einzelnes Profile sein. Das Ergebnis ist aber in jedem Fall ein Array von Strings.
   * @return Array of Strings or an empty array
   */
  function _wrapProfiles($profiles, $conf) {
    $ret = array();
    if(!is_array($profiles)) {
      if(is_object($profiles))
        $profiles = array($profiles);
      else
       return array();
    }

    foreach($profiles As $profile) {
      if(is_object($profile)) {
        $name = tx_cfcleaguefe_models_profile::wrap($this->_formatter, $conf, $profile);
        if(strlen($name) > 0)
          $ret[] =  $name;
      }
      else { // Wenn $profile kein Objekt ist, dann wurde das Profil nicht geladen...
          $ret[] =  '??';
      }
    }
    return $ret;
  }

  /**
   * Wrappt alle übergebenen Tickermeldungen
   */
  function _wrapTickers(&$tickerArr, $confIdAll) {
    $conf = $this->_configurations->get($confIdAll);

    foreach($tickerArr As $ticker) {
      $ret[] = tx_cfcleaguefe_models_match_note::wrap($this->_formatter, $conf['ticker.'], $ticker, $this->_configurations);
    }
    // Die einzelnen Meldungen verbinden
    if(count($ret)) {
      $sep = (strlen($conf['seperator']) > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : $conf['seperator'];
      $ret = implode($sep, $ret);
    }
    else $ret = null;

// t3lib_div::debug($ret,'ret mdl_report');

    // Jetzt noch ein Wrap über alles
    return $this->_formatter->stdWrap($ret, $conf);
  }

  /**
   * Liefert den Namen der Spieler in der Startaufstellung eines Teams
   */
  function _getLineUp($players, $system, $confId) {
    $conf = $this->_configurations->get($confId);

    $system = t3lib_div::trimExplode('-',$system);
    $players = is_array($players) ? array_values($players) : array();

    // Jetzt die Spieler nach dem System aufteilen
    $parts = count($system);
    $partCnt = 0;
    $partArr = array();
    $splitSum = $system[$partCnt];
    for($i=0; $i < count($players); $i++) {
      $partArr[$partCnt][] = $players[$i];
      // Muss umgeschaltet werden?
      if(count($partArr[$partCnt]) >= $splitSum) {
        // Die Spielernamen holen
        $partArr[$partCnt] = $this->_getNames2($partArr[$partCnt], $confId);
        $partCnt++;
        $splitSum = $system[$partCnt];
      }
    }

//    $sep = (strlen($conf['seperator']) > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : $conf['seperator'];
    $ret = implode(' - ', $partArr);

    // Jetzt noch ein Wrap über alles
    return $this->_formatter->stdWrap($ret, $conf);
  }

  /**
   * Lädt das Spiel aus der Datenbank
   */
  function _loadMatch($matchId) {
    // Wir holen gleich einige Zusatzinfos mit
    $what = tx_cfcleaguefe_models_match::getWhatFull(1);
    $from = tx_cfcleaguefe_models_match::getFromFull();
    $where = 'tx_cfcleague_games.uid = ' . intval($matchId);

    $rows = tx_rnbase_util_DB::queryDB($what,$from,$where,
              '','','tx_cfcleaguefe_models_match',0);
    // Wir finden wahrscheinlich nur genau ein Spiel...
    return count($rows) ? $rows[0] : 0;

  }

  /**
   * Liefert das Logo eines Teams. Es ist entweder das zugeordnete Logo des Teams oder 
   * das Logo des Vereins.
   */
  function _getLogo(&$team) {
    return $team->getLogo($this->_formatter, 'matchreport.logo.');
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_matchreport.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_matchreport.php']);
}

?>