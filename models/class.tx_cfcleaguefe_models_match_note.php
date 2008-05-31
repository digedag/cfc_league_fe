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
require_once(t3lib_extMgm::extPath('rn_base') . 'model/class.tx_rnbase_model_base.php');


/**
 * Model für eine Tickermeldung. Derzeit gibt es die folgenden Typen:
 * <pre>
 * type.ticker -> 100
 * type.goal -> 10
 * type.goal.header -> 11
 * type.goal.penalty -> 12
 * type.goal.own -> 30
 * type.goal.assist -> 31
 * type.penalty.forgiven -> 32
 * type.corner -> 33
 * type.yellow -> 70
 * type.yellowred -> 71
 * type.red -> 72
 * type.changeout -> 80
 * type.changein -> 81
 * type.captain -> 200
 * </pre>
 */
class tx_cfcleaguefe_models_match_note extends tx_rnbase_model_base {
  var $match;

  /** Die Instanz des unbekannten Spielers */
  static private $unknownPlayer;
  /** Die Instanz des fehlenden Profils */
  static private $notFoundProfile;

  function getTableName(){return 'tx_cfcleague_match_note';}

  function toString() {
    return 'tx_cfcleaguefe_models_match_note( uid['. $this->uid .
            '] type[' . $this->record['type'] . 
            '] minute[' . $this->record['minute'] . 
            '] player_home[' . $this->record['player_home'] . 
            '] player_guest[' . $this->record['player_guest'] . 
            '])';
	}

	/**
	 * Formatiert die Ausgabe der Note über TS. Die Note besteht aus insgesamt
	 * fünf Teilen:
	 * <ul><li>Die Spielminute TS: ticker.minute
	 * <li>der Typ TS: ticker.type
	 * <li>der Spieler TS: ticker.profile und ticker.profile2
	 * <li>der Kommentar TS: ticker.comment
	 * <li>der Spielstand zum Zeitpunkt der Note TS: ticker.score
	 * </ul>
	 * Für jedes Element kann ein "weight" gesetzt werden, womit die Reihenfolge bestimmt wird.
	 * Das Element mit dem höchsten Gewicht wird zuletzt dargestellt.
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param tx_cfcleaguefe_models_match_note $ticker
	 */
	function wrap($formatter, $confId, $ticker) {
		if($formatter->configurations->get($confId.'hide') == '1') // Die Meldung soll nicht gezeigt werden
			return '';

//  t3lib_div::debug($ticker->uid, 'arr mdl_note');
		// Wenn der Ticker für den eigene Vereins ist, einen Eintrag im Register setzen
		$GLOBALS['TSFE']->register['T3SPORTS_NOTE_FAVCLUB'] = $ticker->isFavClub(); // XXX: Access to image size by TS

		$arr = array();
		$conf = $formatter->configurations->get($confId.'profile.');
		// Angezeigt wird ein Spieler, sobald etwas im TS steht
		if($conf && is_object($ticker)) {
			// Bei einem Wechsel ist profile für den ausgewechselten Spieler
			if($ticker->isChange()) {
				$player = $ticker->getPlayerChangeOut();
			}
			else {
				$player = $ticker->getPlayer();
			}
//      $value = $player->wrap($formatter, $conf['profile.']);
			$value = tx_cfcleaguefe_models_profile::wrap($formatter, $confId.'profile.', $player);
			if(strlen($value) > 0) {
				$arr[] = array($value, $weights[] = $conf['s_weight'] ? intval($conf['s_weight']) : 0);
				$value = '';
			}
		}

		// Bei Spielerwechseln gibt es noch ein zweites Profil
		$conf = $formatter->configurations->get($confId.'profile2.');
		if($conf && is_object($ticker) && $ticker->isChange()) {
			$player2 = $ticker->getPlayerChangeIn();
			if(!is_object($player2)) {
				// Hier liegt vermutlich ein Fehler bei der Dateneingabe vor
				// Es wird ein Hinweistext gezeigt
				$value = 'ERROR!';
			}
			else {
				$value = tx_cfcleaguefe_models_profile::wrap($formatter, $confId.'profile2.', $player2);
			}
//			$value = $player2->wrap($formatter, $conf['profile2.']);
			if(strlen($value) > 0) {
				$arr[] = array($value, $weights[] = $conf['s_weight'] ? intval($conf['s_weight']) : 0);
				$value = '';
			}
		}
//if($ticker->uid == 1455)
//  t3lib_div::debug($ticker->record, 'tx_cfcleaguefe_models_match_note');
		$cObj = $formatter->configurations->getCObj(1);
		$cObj->data = $ticker->record;
		foreach($ticker->record AS $key => $val) {
			$conf = $formatter->configurations->get($confId.$key.'.');
			if($conf) {
				$cObj->setCurrentVal($ticker->record[$key]);
				$value = $cObj->stdWrap($ticker->record[$key],$conf);
				//$value = $cObj->stdWrap($ticker->record[$key],$conf[$key.'.']);
				if(strlen($value) > 0) {
					$arr[] = array($value, $conf['s_weight'] ? intval($conf['s_weight']) : 0);
					$value = '';
				}
			}
		}

		// Jetzt die Teile sortieren
		usort($arr, 'cmpWeight');
		$ret = array();
		// Jetzt die Strings extrahieren
		foreach($arr As $val) {
			$ret[] = $val[0];
		}

		// Der Seperator sollte mit zwei Pipes eingeschlossen sein
		$sep = $formatter->configurations->get($confId.'seperator');
		$sep = (strlen($sep) > 2) ? substr($sep, 1, strlen($sep) - 2) : $sep;
		$ret = implode($sep, $ret);

    // Abschließend nochmal den Ergebnisstring wrappen
//t3lib_div::debug($arr, 'arr mdl_note');
		return $formatter->wrap($ret, $confId, $ticker->record);
	}

  /**
   * Liefert bei einem Wechsel den eingewechselten Spieler.
   */
  function getPlayerChangeIn() {
    return $this->_getPlayerChange(0);
  }

  /**
   * Liefert bei einem Wechsel den ausgewechselten Spieler.
   */
  function getPlayerChangeOut() {
    return $this->_getPlayerChange(1);
  }

  /**
   * Liefert das aktuelle Zwischenergebnis zum Zeitpunkt der Meldung.
   * Diese kommt in der Form 0:0
   * Für eine formatierte Ausgabe sollte die Methode wrap verwendete werden.
   */
  function getScore() {
    return $this->record['goals_home'] . ' : '. $this->record['goals_guest'];
  }

  /**
   * Liefert true wenn die Aktion eine Ein- oder Auswechslung ist
   */
  function isChange() {
    return $this->record['type'] == '80' || $this->record['type'] == '81';
  }

  /**
   * Liefert true wenn die Aktion dem Heimteam zugeordnet ist
   */
  function isHome() {
    return $this->record['player_home'] > 0 || $this->record['player_home'] == -1;
  }

  /**
   * Liefert true wenn die Aktion dem Gastteam zugeordnet ist
   */
  function isGuest() {
    return $this->record['player_guest'] > 0 || $this->record['player_guest'] == -1;
  }

  /**
   * Liefert true wenn die Meldung ein Tor ist
   */
  function isGoal() {
    $type = intval($this->record['type']);
    return ($type >= 10 && $type < 20) || $type == 30; // 30 ist das Eigentor
  }

  /**
   * Liefert true wenn die Meldung eine Strafe ist (Karten)
   */
  function isPenalty() {
    $type = intval($this->record['type']);
    return ($type >= 70 && $type < 80);
  }

  /**
   * Liefert true wenn die Meldung eine gelb/rote Karte ist
   */
  function isYellowRedCard() {
    return $this->isType(71);
  }

  /**
   * Liefert true wenn die Meldung eine rote Karte ist
   */
  function isRedCard() {
    return $this->isType(72);
  }

  /**
   * Liefert true wenn die Meldung eine gelbe Karte ist
   */
  function isYellowCard() {
    return $this->isType(70);
  }

  /**
   * Entscheidet, ob die Note angezeigt werden soll. Dies wird über die Config
   * entschieden. Derzeit wird die Spielminute (noteMinimumMinute) und der 
   * Typ der Meldung (noteType und noteIgnoreType) überprüft.
   *
   * @param array $conf
   * @return boolean
   */
  function isVisible($conf) {
    $minMinute = intval($conf['noteMinimumMinute']);
		return $minMinute <= $this->record['minute'] && $this->isType($conf);
  }
  /**
   * Liefert true, wenn die Meldung dem Typ entspricht
   * Parameter ist entweder die Typnummer oder ein Array mit den Keys 
   * noteType und noteTeam. Bei noteType kann eine Liste von Typnummern
   * angegeben werden. NoteTeam ist entweder home oder guest.
   * @param array $typeNumberOrArray
   */
  function isType($typeNumberOrArray) {
    $ret = false;
    if(is_array($typeNumberOrArray)) {
      $typeArr = $typeNumberOrArray;
      // Wenn es ein Array ist, dann zunächst die Typen ermitteln
      // Keine Typen bedeutet, daß alle verwendet werden
      $types = $typeArr['noteType'] ? t3lib_div::intExplode(',', $typeArr['noteType']) : array();
      $ignoreTypes = $typeArr['noteIgnoreType'] ? t3lib_div::intExplode(',', $typeArr['noteIgnoreType']) : array();
//t3lib_div::debug($types, 'ignore tx_cfcleaguefe_models_match_note');
      
      // Wenn Typen definiert sind, dann wird ignoreType nicht betrachtet
      if(in_array($this->getType(), $types) || 
         (!count($types) && !count($ignoreTypes)) ||
         (!in_array($this->getType(), $ignoreTypes) && count($ignoreTypes)) ) {

        // Wird nach Home/Guest unterschieden?
        if(array_key_exists('noteTeam', $typeArr) && strlen($typeArr['noteTeam'])) {
          if(strtoupper($typeArr['noteTeam']) == 'HOME' && $this->isHome()) {
            $ret = true;
          }
          elseif(strtoupper($typeArr['noteTeam']) == 'GUEST' && $this->isGuest()) {
            $ret = true;
          }
        }
        else
          $ret = true;
      }
    }
    else {
      $type = intval($this->record['type']);
      $ret = ($type == intval($typeNumberOrArray));
    }
    return $ret;
  }

  /**
   * Liefert den Typ der Meldung
   * @return int den Typ der Meldung
   */
  function getType() {
    return intval($this->record['type']);
  }

  /**
   * Liefert den Namen des Tickertyps. Dafür ist die aktuelle Config notwendig,
   * da der Wert über das Flexform ermittelt wird
   */
  function getTypeName(&$configurations) {
    global $LANG,$TSFE;
    if(intval($this->record['type']) == 100)
      return '';

    $flex = $this->_getFlexForm($configurations);
    $types = $this->_getItemsArrayFromFlexForm($flex, 's_matchreport','tickerTypes');
    foreach($types As $type) {
      if(intval($type[1]) == intval($this->record['type'])) {
        return $TSFE->sL($type[0]);
      }
    }
    return '';
  }

  /**
   * Liefert true wenn es ein Eigentor ist. 
   */
  function isGoalOwn() {
    return $this->getType() == 30;
  }

  /**
   * Liefert true wenn ein Tor für das Heimteam gefallen ist. Auch Eigentore werden 
   * berücksichtigt.
   */
  function isGoalHome() {
    if($this->isGoal()) {
      return ($this->isHome() && !$this->isGoalOwn()) || ($this->isGuest() && $this->isGoalOwn() ) ;
    }
    return false;
  }

  /**
   * Liefert true wenn ein Tor für das Gastteam gefallen ist. Auch Eigentore werden 
   * berücksichtigt.
   */
  function isGoalGuest() {
    if($this->isGoal()) {
      return ($this->isGuest() && !( $this->getType() == 30)) || ($this->isHome() && ( $this->getType() == 30)) ;
    }
    return false;
  }

  /**
   * Liefert die Spielminute der Aktion
   */
  function getMinute() {
    return $this->record['minute'];
  }

  function getExtraTime() {
    return $this->record['extra_time'];
  }

  /**
   * Liefert die Singleton-Instanz des unbekannten Spielers. Dieser hat die ID -1 und 
   * wird für MatchNotes verwendet, wenn der Spieler nicht bekannt ist.
   */
  function &getUnknownPlayer() {
    if(!is_object(tx_cfcleaguefe_models_match_note::$unknownPlayer)) {
      $profileClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_profile');
      tx_cfcleaguefe_models_match_note::$unknownPlayer = new $profileClass('-1');
    }
    return tx_cfcleaguefe_models_match_note::$unknownPlayer;
  }

  /**
   * Liefert die Singleton-Instanz eines nicht gefundenen Profils. Dieses hat die ID -2 und 
   * wird für MatchNotes verwendet, wenn das Profil nicht mehr in der Datenbank gefunden wurde.
   * FIXME: Vermutlich ist diese Funktionalität in der Matchklasse besser aufgehoben
   */
  function &getNotFoundProfile() {
    if(!is_object(tx_cfcleaguefe_models_match_note::$notFoundProfile)) {
      $profileClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_profile');
      tx_cfcleaguefe_models_match_note::$notFoundProfile = new $profileClass('-2');
    }
    return tx_cfcleaguefe_models_match_note::$notFoundProfile;
  }

  /**
   * Liefert das Profil des an der Aktion beteiligten Spielers der Heimmannschaft.
   * Wenn nicht vorhanden wird der Spieler "Unbekannt" geliefert
   */
  function &getPlayerHome() {
    // Innerhalb der Matchnote gibt es das Konstrukt des unbekannten Spielers. Dieser
    // Wird verwendet, wenn der eigentliche Spieler nicht mehr in der Datenbank gefunden
    // wird, oder wenn die ID des Spielers -1 ist.

    if(intval($this->record['player_home']) == 0 ){ // ID 0 ist nicht vergeben
      $player = NULL;
    }
    elseif(intval($this->record['player_home']) == -1 ){
      $player = $this->getUnknownPlayer();
    }
    else {
      $players = $this->match->getPlayersHome(1); // Spieler und Wechselspieler holen
      $player =& $players[$this->record['player_home']];
    }
    return $player;
  }

  /**
   * Liefert das Profil, des an der Aktion beteiligten Spielers der Gastmannschaft
   */
  function &getPlayerGuest() {

    if(intval($this->record['player_guest']) == 0 ){ // ID 0 ist nicht vergeben
      $player = NULL;
    }
    elseif(intval($this->record['player_guest']) == -1 ){
      $player =& $this->getUnknownPlayer();
    }
    else {
      $players = $this->match->getPlayersGuest(1);
      $player =& $players[$this->record['player_guest']];
      if(!is_object($player)) {
        $player =& $this->getNotFoundProfile();
      }

    }
    return $player;
  }

  /**
   * Liefert den Spieler dem diese Meldung zugeordnet ist.
   * @return ein Profil oder 0
   */
  function getPlayer() {
    if($this->isHome())
      return $this->getPlayerHome();
    if($this->isGuest())
      return $this->getPlayerGuest();
    return 0;
  }

  /**
   * Zur Abfrage von Zusatzinfos wird Zugriff auf das zugehörige Spiel benötigt.
   * Diese muss vorher mit dieser Methode bekannt gemacht werden.
   * @param tx_cfcleaguefe_models_match $match
   */
  function setMatch(&$match) {
    $this->match = $match;
  }
  /**
   * Liefert das Spiel
   *
   * @return tx_cfcleaguefe_models_match
   */
  function getMatch() {
  	return $this->match;
  }
  
  /**
   * Ermittelt für die übergebenen Spiele die MatchNotes. Wenn $types = 1 dann
   * werden nur die Notes mit dem Typ < 100 geliefert. Die MatchNotes werden direkt
   * in den übergebenen Matches gesetzt.
   * Die ermittelten MatchNotes haben keine Referenz auf das zugehörige Match!
   */
  function &retrieveMatchNotes(&$matches, $types='1') {
    if(!count($matches))
      return $matches;
    // Die Spiele in einen Hash legen, damit wir sofort Zugriff auf ein Spiel haben
    $matchesHash = Array();
    $matchIds = Array();
    $anz = count($matches);
    for($i=0; $i<$anz; $i++) {
      $matchesHash[$matches[$i]->uid] =& $matches[$i];
      $matchIds[] = $matches[$i]->uid;
    }
    
//  t3lib_div::debug($matches[i]->uid, 'mdl_note');
    $matchIds = implode(',', $matchIds); // ID-String erstellen


    $what = '*';
    $from = 'tx_cfcleague_match_notes';
    $where = 'game IN (' .$matchIds . ')';
    if($types) {
      $where .= ' AND type < 100';
    }

    $matchNotes = tx_rnbase_util_DB::queryDB($what, $from, $where,
              '','game,minute asc','tx_cfcleaguefe_models_match_note','',0);

    // Das Match setzen (foreach geht hier nicht weil es nicht mit Referenzen arbeitet...)
    $anz = count($matchNotes);
    for($i=0; $i<$anz; $i++) {
      // Hier darf nur mit Referenzen gearbeitet werden
//      $matchNotes[$i]->setMatch($matchesHash[$matchNotes[$i]->record['game']]);
      $matchesHash[$matchNotes[$i]->record['game']]->addMatchNote($matchNotes[$i]);
    }

//  t3lib_div::debug($matchesHash[172], 'mdl_note');
//  t3lib_div::debug($matches[19], 'mdl_note');
    return $matches;
  }

  /**
   * Prüft die TS-Anweisung showOnly für eine MatchNote.
   * @return 1 - show, 0 - show not
   */
  function _isShowTicker($conf, $ticker) {
    $showOnly = $conf['showOnly'];
    if(strlen($showOnly) > 0) {
      $showOnly = t3lib_div::intExplode(',',$showOnly);
      // Prüfen ob der aktuelle Typ paßt
      if(count($showOnly) > 0 && in_array($ticker->record['type'], $showOnly)) {
        return 1;
      }
    }
    else{
      return 1;
    }
    return 0;
  }

  function &_getFlexForm(&$configurations) {
    static $flex;
    if (!is_array($flex)) {
      $flex = t3lib_div::getURL(t3lib_extMgm::extPath($configurations->getExtensionKey()) . $configurations->get('flexform'));
      $flex = t3lib_div::xml2array($flex);
    }
    return $flex;
  }

  /**
   * Liefert die möglichen Werte für ein Attribut aus einem FlexForm-Array
   */
  function _getItemsArrayFromFlexForm($flexArr, $sheetName, $valueName) {
    return $flexArr['sheets'][$sheetName]['ROOT']['el'][$valueName]['TCEforms']['config']['items'];
  }
	/**
	 * Whether or not the match note is for favorite club
	 *
	 * @return int 0/1
	 */
	private function isFavClub() {
		// Zuerst das Team holen
		$team = null;
		$match = $this->getMatch();
		if($this->isHome()) {
			$team = $match->getHome();
		}
		elseif($this->isGuest()) {
			$team = $match->getGuest();
		}
		if(!is_object($team)) return 0;
		$club = $team->getClub();
		if(!is_object($club)) return 0;
		return $club->isFavorite() ? 1 : 0;
	}

	/**
	 * Liefert den ausgewechselten Spieler, wenn der Tickertyp ein Wechsel ist
	 * @param int $type 0 liefert den eingewechselten Spieler, 1 den ausgewechselten
	 */
	function _getPlayerChange($type) {
		// Ist es ein Wechsel?
		if($this->isChange()) {

			// Heim oder Gast?
			if($this->record['player_home']) {
				$players = $this->match->getPlayersHome(1);
//if($this->uid == 1466)
//  t3lib_div::debug($players, 'chg home mdl_note');
				if($this->record['type'] == '80') { // Einwechslung
					return $players[$this->record[$type ? 'player_home' : 'player_home_2']];
				} else {
					return $players[$this->record[$type ? 'player_home_2' : 'player_home']];
				}
			}
			elseif($this->record['player_guest']) {
				$players = $this->match->getPlayersGuest(1);
//t3lib_div::debug($players, 'chg guest mdl_note');
				if($this->record['type'] == '80') { // Einwechslung
					return $players[$this->record[$type ? 'player_guest' : 'player_guest_2']];
				}
				return $players[$this->record[$type ? 'player_guest_2' : 'player_guest']];
			}
		}
	}
}

/**
 * Sortierfunktion, um die korrekte Reihenfolge nach weights zu ermittlen
 */
function cmpWeight($a, $b) {
  if ($a[1] == $b[1]) {
    return 0;
  }
  return ($a[1] < $b[1]) ? -1 : 1;
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match_note.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match_note.php']);
}

?>