<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_model_base');

tx_rnbase::load('tx_cfcleaguefe_models_competition');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');


/**
 * Model für einen Spiel. Liefert Zugriff auf die Daten eines Spiels.
 */
class tx_cfcleaguefe_models_match extends tx_rnbase_model_base {
	private static $instances = array();
	var $_profiles, $_matchNotes, $_teamHome, $_teamGuest;
	var $_report;

	function __construct($rowOrUid) {
		parent::__construct($rowOrUid);
		$this->initResult();
	}
	function getTableName(){return 'tx_cfcleague_games';}

	/**
	 * Notwendige Initialisierung für das Ergebnis des Spieldatensatzes
	 *
	 */
	public function initResult() {
		if($this->resultInited) return;

		// Um das Endergebnis zu ermitteln, muss bekannt sein, wieviele Spielabschnitte
		// es gibt. Dies steht im Wettbewerb
		$comp = $this->getCompetition();
		$this->record['matchparts'] = $comp->getMatchParts();
		if($comp->isAddPartResults())
			$this->initResultAdded($comp, $comp->getMatchParts());
		else
			$this->initResultSimple($comp, $comp->getMatchParts());
		$this->resultInited = true;
	}

	/**
	 * Init result and expect the endresult in last match part.
	 * @param tx_cfcleague_models_Competition $comp
	 * @param int $matchParts
	 */
	private function initResultSimple($comp, $matchParts) {
		$goalsHome = $this->record['goals_home_'.$matchParts];
		$goalsGuest = $this->record['goals_guest_'.$matchParts];
		// Gab es Verländerung oder Elfmeterschiessen
		if($this->isPenalty()) {
			$goalsHome = $this->record['goals_home_ap'];
			$goalsGuest = $this->record['goals_guest_ap'];
		}
		elseif($this->isExtraTime()) {
			$goalsHome = $this->record['goals_home_et'];
			$goalsGuest = $this->record['goals_guest_et'];
		}
		$this->record['goals_home'] = $goalsHome;
		$this->record['goals_guest'] = $goalsGuest;
	}
	/**
	 * Init result and add all matchpart results.
	 * @param tx_cfcleague_models_Competition $comp
	 * @param int $matchParts
	 */
	private function initResultAdded($comp, $matchParts) {
		$goalsHome = 0;
		$goalsGuest = 0;

		// Teilergebnisse holen
		$matchParts = $matchParts > 0 ? $matchParts : 1;
		for($i=1; $i<=$matchParts; $i++) {
			$goalsHome += $this->record['goals_home_'.$i];
			$goalsGuest += $this->record['goals_guest_'.$i];
		}
		// Gab es Verländerung oder Elfmeterschiessen
		if($this->isPenalty()) {
			$goalsHome += $this->record['goals_home_ap'];
			$goalsGuest += $this->record['goals_guest_ap'];
		}
		elseif($this->isExtraTime()) {
			$goalsHome += $this->record['goals_home_et'];
			$goalsGuest += $this->record['goals_guest_et'];
		}
		$this->record['goals_home'] = $goalsHome;
		$this->record['goals_guest'] = $goalsGuest;
	}


	public function getGoalsHome($matchPart = '') {
		$ret = $this->record['goals_home'];
		if(strlen($matchPart))
			$ret = $this->record['goals_home_'.(($matchPart == 'last') ? $this->record['matchparts'] : $matchPart) ];
		return $ret;
	}
	public function getGoalsGuest($matchPart = '') {
		$ret = $this->record['goals_guest'];
		if(strlen($matchPart))
			$ret = $this->record['goals_guest_'.(($matchPart == 'last') ? $this->record['matchparts'] : $matchPart) ];
		return $ret;
	}
	/**
	 * Returns the match report
	 *
	 * @return tx_cfcleaguefe_models_matchreport
	 */
	public function &getMatchReport() {
		return $this->_report;
	}

	/**
	 * Set the instance of matchreport
	 *
	 * @param tx_cfcleaguefe_models_matchreport $report
	 */
	public function setMatchReport(&$report) {
		$this->_report = $report;
	}
	public function getResult() {
		return $this->record['status'] > 0 ? $this->getGoalsHome() .' : ' . $this->getGoalsGuest() : '- : -';
	}
	/**
	 * Returns the state as string
	 *
	 * @return string
	 */
	public function getStateName() {
		tx_rnbase::load('tx_rnbase_util_TCA');
		tx_rnbase_util_TCA::loadTCA('tx_cfcleague_games');
		$items = $GLOBALS['TCA']['tx_cfcleague_games']['columns']['status']['config']['items'];
		foreach($items As $item) {
			if($item[1] == $this->record['status'])
				return $GLOBALS['LANG']->sL($item[0]);
		}
		return '';
	}
  /**
   * Returns true if match is finished
   *
   * @return boolean
   */
  public function isFinished(){
    return intval($this->record['status']) == 2;
  }
  /**
   * Returns true if match is running
   *
   * @return boolean
   */
  public function isRunning(){
    return intval($this->record['status']) == 1;
  }
  /**
   * Returns true if match has extra time
   *
   * @return boolean
   */
  public function isExtraTime() {
    return intval($this->record['is_extratime']) == 1;
  }
  /**
   * Returns true if match has extra time
   *
   * @return boolean
   */
  public function isPenalty() {
    return intval($this->record['is_penalty']) == 1;
  }
  /**
   * Liefert true, wenn für das Spiel ein Spielbericht vorliegt.
   */
  function hasReport() {
    return (intval($this->record['has_report']) + intval($this->record['link_report'])) > 0;
  }

  /**
   * @return true if live ticker is turn on
   */
  function isTicker() {
    return $this->record['link_ticker'] > 0;
  }

  /**
   * Returns true of match is a dummy (free of play).
   *
   * @return boolean
   */
  function isDummy(){
    // Aus Performancegründen fragen wir hier den eigenen Record ab
    return $this->getHome()->isDummy() || $this->getGuest()->isDummy();
  }
  /**
   * Liefert alle MatchNotes des Spiels als Referenz auf ein Array.
   * Die Ticker werden in chronologischer Reihenfolge geliefert.
   * Alle MatchNotes haben eine Referenz auf das zugehörige Spiel
   */
  function &getMatchNotes() {
    $this->_resolveMatchNotes();
    return $this->_matchNotes;
  }

  /**
   * Fügt diesem Match eine neue Note hinzu. Die Notes werden mit diesem Spiel verlinkt.
   */
  function addMatchNote(&$note) {
    if(!isset($this->_matchNotes))
      $this->_matchNotes = array(); // Neues TickerArray erstellen
    $note->setMatch($this);
    $this->_matchNotes[] = $note;
    // Zusätzlich die Notes nach ihrem Typ sortieren
    $this->_matchNoteTypes[intval($note->record['type'])][] = $note;
  }

  function &getMatchNotesByType($type) {
    if(is_array($type)) {
      $ret = array();
      for($i=0, $size = count($type); $i < $size; $i++) {
        $notes = $this->_matchNoteTypes[intval($type[$i])];
        if(is_array($notes)) {
          $ret = array_merge($ret, $notes);
        }
      }
      return $ret;
    }
    else
      return $this->_matchNoteTypes[intval($type)];
  }
	/**
	 * Lädt die MatchNotes dieses Spiels. Sollten sie schon geladen sein, dann
	 * wird nix gemacht.
	 */
	function _resolveMatchNotes($orderBy = 'asc') {
		if(isset($this->_matchNotes)) return; // Die Ticker sind schon geladen

		$what = '*';
		$from = 'tx_cfcleague_match_notes';
		$options['where'] = 'game = ' .$this->uid;
		$options['wrapperclass'] = 'tx_cfcleaguefe_models_match_note';
		// HINT: Die Sortierung nach dem Typ ist für die Auswechslungen wichtig.
		$options['orderby'] = 'minute asc, extra_time asc, uid asc';
		$this->_matchNotes = tx_rnbase_util_DB::doSelect($what, $from, $options,0);

		// Das Match setzen (foreach geht hier nicht weil es nicht mit Referenzen arbeitet...)
		$anz = count($this->_matchNotes);
		for($i=0; $i<$anz; $i++) {
			$this->_matchNotes[$i]->setMatch($this);
			// Zusätzlich die Notes nach ihrem Typ sortieren
			$this->_matchNoteTypes[intval($this->_matchNotes[$i]->record['type'])][] = $this->_matchNotes[$i];
		}
	}

  /**
   * Liefert das Heim-Team als Objekt
   * @return tx_cfcleaguefe_models_team
   */
  function getHome() {
    $this->_teamHome = isset($this->_teamHome) ? $this->_teamHome : $this->_getTeam($this->record['home']);
    return $this->_teamHome;
  }

  /**
   * Setzt das Heim-Team
   */
  function setHome(&$team) {
    $this->_teamHome = $team;
  }

  /**
   * Liefert das Gast-Team als Objekt
   * @return tx_cfcleaguefe_models_team
   */
  function getGuest() {
    $this->_teamGuest = isset($this->_teamGuest) ? $this->_teamGuest : $this->_getTeam($this->record['guest']);
    return $this->_teamGuest;
  }
  function getHomeNameShort() {
  	return $this->getHome()->getNameShort();
  }
  function getGuestNameShort() {
  	return $this->getGuest()->getNameShort();
  }

  /**
   * Setzt das Gast-Team
   */
  function setGuest(&$team) {
    $this->_teamGuest = $team;
  }

  /**
   * Liefert den Referee als Datenobjekt
   * @return tx_cfcleaguefe_models_profile
   */
  function getReferee() {
    $ret = null;
    if($this->record['referee']) {
      $this->_resolveProfiles();
      // Wir suchen jetzt den Schiedsrichter
      $ret = $this->_profiles[$this->record['referee']];
    }
    return $ret;
  }

  /**
   * Liefert den Heimtrainer als Datenobjekt
   */
  function getCoachHome() {
    $ret = null;
    if($this->record['coach_home']) {
      $this->_resolveProfiles();
      // Wir suchen jetzt den Trainer
      $ret = $this->_profiles[$this->record['coach_home']];
    }
    return $ret;
  }

  /**
   * Liefert den Gasttrainer als Datenobjekt
   */
  function getCoachGuest() {
    $ret = null;
    if($this->record['coach_guest']) {
      $this->_resolveProfiles();
      // Wir suchen jetzt den Trainer
      $ret = $this->_profiles[$this->record['coach_guest']];
    }
    return $ret;
  }

	/**
	 * Liefert die Schiedsrichterassistenten als Datenobjekte in einem Array
	 */
	function getAssists() {
		return $this->_getProfiles($this->record['assists']);
	}
	protected $sets = null;

	/**
	 * Return sets if available
	 * @return array[tx_cfcleague_models_Set]
	 */
	public function getSets() {
		if(!is_array($this->sets)) {
			tx_rnbase::load('tx_cfcleague_models_Set');
			$this->sets = tx_cfcleague_models_Set::buildFromString($this->record['sets']);
			$this->sets = $this->sets ? $this->sets : array();
		}
		return $this->sets;
	}

  /**
   * Liefert die Spieler des Heimteams der Startelf als Datenobjekte in einem Array
   * @param $all wenn > 0 werden auch die Ersatzspieler mit geliefert
   * @return Array Key ist UID, Value ist Profile als Object
   */
  function getPlayersHome($all = 0) {
    $ids = $this->record['players_home'];
    if($all > 0 &&  strlen($this->record['substitutes_home']) > 0){
      // Auch Ersatzspieler anhängen
      if(strlen($ids) > 0)
        $ids = $ids . ',' . $this->record['substitutes_home'];
    }
    return $this->_getProfiles($ids);
  }

  /**
   * Liefert die Spieler des Gastteams der Startelf als Datenobjekte in einem Array
   * @param $all wenn > 0 werden auch die Ersatzspieler mit geliefert
   * @return Array Key ist UID, Value ist Profile als Object
   */
  function getPlayersGuest($all = 0) {
    $ids = $this->record['players_guest'];
    if($all > 0 &&  strlen($this->record['substitutes_guest']) > 0){
      // Auch Ersatzspieler anhängen
      if(strlen($ids) > 0)
        $ids = $ids . ',' . $this->record['substitutes_guest'];
    }
    return $this->_getProfiles($ids);
  }

  /**
   * Liefert die Ersatzspieler des Heimteams als Datenobjekte in einem Array
   * @return Array Key ist UID, Value ist Profile als Object
   */
  function getSubstitutesHome() {
    return $this->_getProfiles($this->record['substitutes_home']);
  }

  /**
   * Liefert die Ersatzspieler des Gastteams als Datenobjekte in einem Array
   */
  function getSubstitutesGuest() {
    return $this->_getProfiles($this->record['substitutes_guest']);
  }

  /**
   * Ermittelt zu welchem Team die SpielerID gehört.
   * @param $playerUid int UID eines Spielers
   * @return 1 - Heimteam, 2- Gastteam, 0 - unbekannt
   */
  function getTeam4Player($playerUid) {
    $playerUid = intval($playerUid);
    if(!$playerUid) return 0; // Keine ID vorhanden
    $uids = array();
    if($this->record['players_home']) $uids[] = $this->record['players_home'];
    if($this->record['substitutes_home']) $uids[] = $this->record['substitutes_home'];
    $uids = implode($uids, ',');
    $uids = Tx_Rnbase_Utility_Strings::intExplode(',', $uids);
    if(in_array($playerUid, $uids))
      return 1;

    $uids = array();
    if($this->record['players_guest']) $uids[] = $this->record['players_guest'];
    if($this->record['substitutes_guest']) $uids[] = $this->record['substitutes_guest'];
    $uids = implode($uids, ',');
    $uids = Tx_Rnbase_Utility_Strings::intExplode(',', $uids);
    if(in_array($playerUid, $uids))
      return 2;
    return 0;

  }

  /**
   * Liefert die Profiles des UID-Strings als Array. Key ist die UID, Value das Profile
   * @return Array Key ist UID, Value ist Profile als Object
   */
  function _getProfiles($uidStr) {
    $ret = array();
    if($uidStr) {
      $this->_resolveProfiles();

      // *********
      // INFO: Unter PHP5 ist es zu einem Problem bei der Behandlung der Referenzen gekommen.
      // Wenn direkt mit dem Array $this->_profiles gearbeitet wird, dann wird bei der Erstellung
      // der MatchNotes für die Auswechslungen die Instanz des ausgewechselten Spielers gelöscht.
      // Durch die Verwendung des zweiten Arrays wird das verhindert. Ursache ist mir aber unbekannt...
      $this->_profiles = $this->_profiles2;

//      $ret = array();
      $uids = Tx_Rnbase_Utility_Strings::intExplode(',', $uidStr);
      foreach($uids As $uid) {
        $ret[$uid] =& $this->_profiles[$uid];
      }
    }
    return $ret;
  }

	/**
	 * Erstellt für alle Personen des Spiels die passenden Objekte. Dies wird aber nur gemacht
	 * wenn die entsprechenden IDs noch nicht geladen sind.
	 * @return Array Key ist UID, Value ist Profile als Object
	 */
	function _resolveProfiles() {
		if(isset($this->_profiles)) return; // Die Profile sind schon geladen
		// Wir sammeln zunächst die UIDs zusammen
		$uids = array();
		if($this->record['referee']) $uids[] = $this->record['referee'];
		if($this->record['assists']) $uids[] = $this->record['assists'];
		if($this->record['coach_home']) $uids[] = $this->record['coach_home'];
		if($this->record['coach_guest']) $uids[] = $this->record['coach_guest'];
		if($this->record['players_home']) $uids[] = $this->record['players_home'];
		if($this->record['players_guest']) $uids[] = $this->record['players_guest'];
		if($this->record['substitutes_home']) $uids[] = $this->record['substitutes_home'];
		if($this->record['substitutes_guest']) $uids[] = $this->record['substitutes_guest'];

		$uids = implode($uids, ',');

		$what = '*';
		$from = 'tx_cfcleague_profiles';
		$options['where'] = 'uid IN (' .$uids . ')';
		$options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';

		$rows = tx_rnbase_util_DB::doSelect($what,$from,$options);

		$this->_profiles = array();
		// Wir erstellen jetzt ein Array dessen Key die UID des Profiles ist
		foreach($rows As $profile) {
			$this->_profiles[$profile->uid] = $profile;
			$this->_profiles2[$profile->uid] = $profile;
		}
	}

  /**
   * Liefert das Team als Objekt
   */
  function _getTeam($uid) {
		if(!$uid) throw new Exception('Invalid match with uid ' . $this->getUid() . ': At least one team is not set.');
	// TODO: Umstellen auf tx_cfcleague_models_team
	//$team = tx_cfcleague_util_ServiceRegistry::getTeamService()->getTeam($uid);
	tx_rnbase::load('tx_cfcleaguefe_models_team');
    $team = tx_cfcleaguefe_models_team::getTeamInstance($uid);
    return $team;
  }


  /**
   * Liefert den Spieltermin. Wenn ein Formatter übergeben wird, dann wird dieser formatiert,
   * ansonsten wird der eigentliche Wert geliefert.
   */
  function getDate($formatter=0, $configKey = 'match.date.') {
    if($formatter)
      return $formatter->wrap($this->record['date'], $configKey);
    else
      return $this->record['date'];
//    return $this->_formatter->stdWrap($this->match->record['date'],$this->_configurations->get('report.date.'));
  }

	/**
	 * Liefert das Stadion
	 * @return tx_cfcleague_models_Stadium Arena as object or false
	 */
	function getArena() {
		if(!intval($this->record['arena'])) return false;
		tx_rnbase::load('tx_cfcleague_models_Stadium');
		return tx_cfcleague_models_Stadium::getInstance($this->record['arena']);
	}

  function getStadium($formatter=0, $configKey = 'match.stadium.') {
    if($formatter)
      return $formatter->wrap($this->record['stadium'], $configKey);
    else
      return $this->record['stadium'];
  }

  /**
   * Returns the visitors of match
   *
   * @return int
   */
  function getVisitors() {
    return intval($this->record['visitors']);
  }
  /**
   * Liefert den Autor des Spielberichts
   */
  function getReportAuthor($formatter=0, $configKey = 'match.author.') {
    if($formatter)
      return $formatter->wrap($this->record['game_report_author'], $configKey);
    else
      return $this->record['game_report_author'];
  }

  /**
   * Liefert den Spielbericht
   */
  function getReport($formatter=0, $configKey = 'match.report.') {
    if($formatter){
      return $formatter->wrap($this->record['game_report'], $configKey);
    }
    else
      return $this->record['game_report'];
  }

	/**
	 * Liefert den TOTO-Wert des Spiels. Als 0 für ein Unentschieden, 1 für einen Heim-
	 * und 2 für einen Auswärstsieg.
	 * @param string $matchPart The matchpart is 1,2,3...,et,ap,last
	 */
	function getToto($matchPart = '') {
		$goalsHome = $this->getGoalsHome($matchPart);
		$goalsGuest = $this->getGoalsGuest($matchPart);

		$goalsDiff = $goalsHome - $goalsGuest;

		if($goalsDiff == 0)
			return 0;
		return ($goalsDiff < 0) ? 2 : 1;
	}
	/**
	 * Returns the match round
	 * @return int
	 */
	function getRound() {
		return intval($this->record['round']);
	}

	/**
	 * Returns the competition
 	 *
	 * @return tx_cfcleague_models_Competition
	 */
	public function getCompetition() {
		if(!$this->competition) {
			tx_rnbase::load('tx_cfcleague_models_Competition');
			$this->competition = tx_cfcleague_models_Competition::getCompetitionInstance($this->getProperty('competition'));
			if(!is_object($this->competition)) {
				tx_rnbase::load('tx_rnbase_util_Logger');
				tx_rnbase_util_Logger::warn('Match with UID ' . $this->getUid() . ' has no valid competition!', 't3sports', array('match'=>$this->getProperty()));
				throw new Exception('Match with UID ' . $this->getUid() . ' has no valid competition!');
			}
		}
		return $this->competition;
	}
	public function setCompetition($competition) {
		$this->competition = $competition;
	}


  /**
   * Liefert keine Daten zum Wettbewerb
   */
  function getWhatMedium() {
    return '
             tx_cfcleague_games.uid, home, t1.name as home_name, guest, t2.name as guest_name,
             t1.short_name as home_short_name, guest, t2.short_name as guest_short_name,
             t1.dummy as home_dummy, t2.dummy as guest_dummy,
             goals_home_1, goals_home_2,
             goals_guest_1, goals_guest_2,
             is_extratime, goals_home_et, goals_guest_et,
             is_penalty, goals_home_ap, goals_guest_ap,
             competition,
             date, round, stadium, round_name, status, visitors, link_ticker, link_report, LENGTH(game_report) AS has_report
             ';
  }

  /**
   * Returns the from-Clause for a medium data request. Dabei wird eine Query über folgende Tabellen
   * erstellt: <ul>
   * <li>tx_cfcleague_games
   * <li>tx_cfcleague_teams
   * </ul>
   * @return ein Array mit From-Clause und 'tx_cfcleague_games'
   */
  function getFromMedium() {
    return Array( '
       tx_cfcleague_games
         INNER JOIN tx_cfcleague_teams As t1 ON tx_cfcleague_games.home = t1.uid
         INNER JOIN tx_cfcleague_teams As t2 ON tx_cfcleague_games.guest = t2.uid',
         'tx_cfcleague_games');
  }

  /**
   * Static Methode, die einen Datenbank-String mit allen relevanten Felder eines Spiels liefert.
   * Dies sind alle Felder der Tabellen games und teams. Für die Team-Tabelle werden die Aliase
   * t1 und t1 verwendet.
   * @param $extended Optional kann auch der Spielbericht mit geladen werden
   */
  function getWhatFull($extended = 0) {
    return '
             tx_cfcleague_games.uid, home, t1.name as home_name, guest, t2.name as guest_name,
             t1.short_name as home_short_name, t2.short_name as guest_short_name,
             t1.dummy as home_dummy, t2.dummy as guest_dummy,
             t1.link_report as home_link_report, t2.link_report as guest_link_report,
             goals_home_1, goals_home_2,
             goals_guest_1, goals_guest_2,
             goals_home_et, goals_home_ap,
             goals_guest_et, goals_guest_ap,
             is_extratime, goals_home_et, goals_guest_et,
             is_penalty, goals_home_ap, goals_guest_ap,
             tx_cfcleague_games.competition,
             tx_cfcleague_competition.name As competition_name,tx_cfcleague_competition.short_name As competition_short_name, tx_cfcleague_competition.type As competition_type,
             (SELECT tx_cfcleague_group.name FROM tx_cfcleague_group WHERE tx_cfcleague_group.uid = tx_cfcleague_competition.agegroup) As agegroup,
             date, round, round_name, stadium, status, visitors, link_ticker, tx_cfcleague_games.link_report, LENGTH(game_report) AS has_report
             ' . ($extended ? '
                , game_report, game_report_author, tx_cfcleague_games.dam_images, referee, assists, system_home, system_guest
                , players_home, players_guest, substitutes_home, substitutes_guest
                , coach_home, coach_guest' : '');
  }

  /**
   * Returns the from-Clause for a full data request. Dabei wird eine Query über folgende Tabellen
   * erstellt: <ul>
   * <li>tx_cfcleague_games
   * <li>tx_cfcleague_competition
   * <li>tx_cfcleague_teams
   * </ul>
   * @return ein Array mit From-Clause und 'tx_cfcleague_games'
   */
  function getFromFull() {
    // CHECK: Funktioniert das auch bei MySQL4??
    return Array( '
       tx_cfcleague_games
         INNER JOIN tx_cfcleague_competition ON tx_cfcleague_games.competition = tx_cfcleague_competition.uid
         INNER JOIN tx_cfcleague_teams As t1 ON tx_cfcleague_games.home = t1.uid
         INNER JOIN tx_cfcleague_teams As t2 ON tx_cfcleague_games.guest = t2.uid',
         'tx_cfcleague_games');

/* Funktioniert bei MySQL 5 nicht mehr:
    return Array( '
       tx_cfcleague_games
         INNER JOIN tx_cfcleague_competition
         INNER JOIN tx_cfcleague_teams As t1
         INNER JOIN tx_cfcleague_teams As t2
         ON tx_cfcleague_games.competition = tx_cfcleague_competition.uid
         ON tx_cfcleague_games.home = t1.uid
         ON tx_cfcleague_games.guest = t2.uid', 'tx_cfcleague_games');
*/
	}
	/**
	 * Liefert die Instance mit der übergebenen UID. Die Daten werden gecached, so daß
	 * bei zwei Anfragen für die selbe UID nur ein DB Zugriff erfolgt.
	 *
	 * @param int $uid
	 * @return tx_cfcleaguefe_models_match
	 */
	public static function getMatchInstance($uid) {
		$uid = intval($uid);
		if(!uid) throw new Exception('Invalid uid for match');
		if(!is_object(self::$instances[$uid])) {
			self::$instances[$uid] = new tx_cfcleaguefe_models_match($uid);
		}
		return self::$instances[$uid];
	}

	public static function addInstance(&$match) {
		self::$instances[$match->uid] = $match;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match.php']);
}
