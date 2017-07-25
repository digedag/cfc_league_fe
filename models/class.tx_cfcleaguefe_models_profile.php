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
tx_rnbase::load('Tx_Cfcleaguefe_Utility_Signs');


/**
 * Model für ein Personenprofil.
 */
class tx_cfcleaguefe_models_profile extends tx_rnbase_model_base {

  function getTableName(){return 'tx_cfcleague_profiles';}

  function __construct($rowOrUid) {
    if(!is_array($rowOrUid) && intval($rowOrUid) < 0) {
      // Unbekannter Spieler
      $this->uid = $rowOrUid;
      $this->record['uid'] = $rowOrUid;
    }
    else
      parent::tx_rnbase_model_base($rowOrUid);
  }

  /**
   * Gibt das Profile formatiert aus. Dabei werden auch MatchNotes berücksichtigt,
   * die dem Profil zugeordnet sind. Die Person wird im FE daher nur über einen
   * einzelnen Marker ausgegeben.
   * @param tx_rnbase_util_FormatUtil $formatter
   * @param array $conf Configuration array
   */
  static function wrap(&$formatter, $confId, $profile) {
    if(!is_object($profile)) {
      // Es wurde kein Profil übergeben, also gibt es nicht weiter zu tun
      return $formatter->wrap('',$confId);
    }
    if(intval($profile->uid) < 0) {
      // Bei unbekannten Profilen holen wir den Namen aus der Config
      $profile->record['last_name'] = $formatter->configurations->getLL('profile_unknownLastname');
      $profile->record['first_name'] = $formatter->configurations->getLL('profile_unknownFirstname');
    }
    self::prepareLinks($formatter, $confId, $profile);
// TODO Das sollte dynamisch gestaltet werden, damit alle Daten der Tabelle verwendet
// werden können.
		$conf = $formatter->configurations->get($confId);
    $arr = array();
    // Über alle Felder im record iterieren
    foreach($profile->record AS $key => $val) {
      if($conf[$key] || $conf[$key.'.']) {
        $value = $formatter->wrap($profile->record[$key],$confId.$key.'.', $profile->record);
        if(strlen($value) > 0) {
					$weight = intval($formatter->configurations->get($confId.$key.'.s_weight'));
					$arr[] = array($value, $weight);
					$value = '';
				}
			}
		}

		$ticker = $profile->isChangedOut();
		if(is_object($ticker) && $conf['ifChangedOut.']['ticker.']) {
			$value = tx_cfcleaguefe_models_match_note::wrap($formatter, $confId.'ifChangedOut.ticker.', $ticker);
			if(strlen($value) > 0) {
				$weight = intval($formatter->configurations->get($confId.'ifChangedOut.s_weight'));
				$arr[] = array($value, $weight);
				$value = '';
			}
		}

		$ticker = $profile->isPenalty();
		if(is_object($ticker) && $conf['ifPenalty.']['ticker.']) {
			$value = tx_cfcleaguefe_models_match_note::wrap($formatter, $confId.'ifPenalty.ticker.', $ticker);
			if(strlen($value) > 0) {
				$weight = intval($formatter->configurations->get($confId.'ifPenalty.s_weight'));
				$arr[] = array($value, $weight);
				$value = '';
			}
		}
    if(!count($arr)) // Wenn das Array leer ist, wird nix gezeigt
      return $formatter->wrap('', $confId, $profile->record);

    // Jetzt die Teile sortieren
    usort($arr, 'cmpWeight');
    // Jetzt die Strings extrahieren
    foreach($arr As $val) {
      $ret[] = $val[0];
    }

    $sep = (strlen($conf['seperator']) > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : $conf['seperator'];
    $ret = implode($sep, $ret);

    // Abschließend nochmal den Ergebnisstring wrappen
    return $formatter->wrap($ret, $confId, $profile->record);

  }
  /**
   * Bereitet Links im Spielbericht vor. Da hier keine Marker verwendet werden, muss für die Verlinkung der
   * normale typolink im TS verwendet werden. Die Zusatz-Parameter müssen hier als String vorbereitet und
   * in ein Register gelegt werden.
   *
   * @param tx_rnbase_util_FormatUtil $formatter
   * @param string $confId
   * @param tx_cfcleaguefe_models_profile $profile
   */
	static function prepareLinks(&$formatter, $confId, &$profile) {
		$link = $formatter->configurations->createLink();
		$link->destination($GLOBALS['TSFE']->id);
		$link->parameters(array('refereeId' => $profile->uid, 'profileId' => $profile->uid));
		$cfg = $link->_makeConfig('url');
		$GLOBALS['TSFE']->register['T3SPORTS_PARAMS_REFEREE_MATCHES'] = $cfg['additionalParams'];
	}

  /**
   * Returns the team notes for this player
   *
   * @param tx_cfcleaguefe_models_team $team
   */
  function getTeamNotes(&$team) {
  	$srv = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
  	return $srv->getTeamNotes($this, $team);
  }
  /**
   * Liefert den kompletten Namen der Person
   * @param $reverse Wenn 1 dann ist die Form <Nachname, Vorname>
   */
  function getName($reverse = 0) {
    if($reverse) {
      $ret = $this->record['last_name'];
      if($this->record['first_name'])
        $ret .= ', ' . $this->record['first_name'];
    }
    else {
      $ret = $this->record['first_name'];
      if($this->record['first_name'])
        $ret .= ' ';
      $ret .= $this->record['last_name'];
    }
    return $ret;
  }

  /**
   * Liefert den Namen des Profiles formatiert zurück.
   */
  function getNameWrapped($formatter, $conf) {
    if($conf['lastname'] || $conf['lastname.'])
      $last_name = $formatter->stdWrap($this->record['last_name'],$conf['lastname.']);
    if($conf['firstname'] || $conf['firstname.'])
      $first_name = $formatter->stdWrap($this->record['first_name'], $conf['firstname.']);
    $name = $conf['reverse'] == '1' ? $last_name . $first_name : $first_name . $last_name;

    // Abschließend nochmal den gesamten String wrappen
    return $formatter->stdWrap($name, $conf);
  }

  /**
   * Fügt diesem Profile eine neue Note hinzu.
   */
  function addMatchNote(&$note) {
    if(!isset($this->_matchNotes))
      $this->_matchNotes = array(); // Neues TickerArray erstellen
    $this->_matchNotes[] = $note;
    // Wir prüfen direkt auf Teamcaptain
    $this->check4Captain($note);
  }

  function check4Captain(&$note) {
    if($note->isType(200)) {
      // Wenn das im Record liegt, kann es auch per TS ausgewertet werden!
      $this->record['teamCaptain'] = '1';
    }
  }

  /**
   * Returns 1 if player is team captain in a match. Works if match_notes set.
   */
  function isCaptain() {
    return intval($this->record['teamCaptain']);
  }
  /**
   * Returns a match_note if player was changed out during a match. Works if match_notes set.
   */
  function isChangedOut() {
    if(is_array($this->_matchNotes)) {
      for($i = 0; $i < count($this->_matchNotes); $i++) {
        $note = $this->_matchNotes[$i];
        if($note->isType(80)) return $note;
      }
    }
    return 0;
  }

  /**
   * Returns a match_note if player received a penalty card during a match. Works if match_notes set.
   */
  function isPenalty() {
    // Die Matchnotes müssen absteigend durchsucht werden, da die letzte Strafe entscheidend ist
    if(is_array($this->_matchNotes)) {
      $arr = array_reverse($this->_matchNotes);
      for($i = 0; $i < count($arr); $i++) {
        $note = $arr[$i];
        if($note->isPenalty()) return $note;
      }
    }
    return 0;
  }

  /**
   * Liefert den Namen formatiert über einen stdWrap
   */
  function getNameWrapped2($formatter, $conf, $profile) {
//    if($conf['hide'] == '1') // Das Profil soll nicht gezeigt werden
//      return '';

    if(is_object($profile)) {
//      $confLast = $conf['lastname.'];
//      if($confLast['hide'] != '1')
      if($conf['lastname'] || $conf['lastname.'])
        $last_name = $formatter->stdWrap($profile->record['last_name'],$conf['lastname.']);

//      $confFirst = $conf['firstname.'];
//      if($confFirst['hide'] != '1')
      if($conf['firstname'] || $conf['firstname.'])
        $first_name = $formatter->stdWrap($profile->record['first_name'], $conf['firstname.']);
      $name = $conf['reverse'] == '1' ? $last_name . $first_name : $first_name . $last_name;
    }

    // Abschließend nochmal den gesamten String wrappen
    return $formatter->stdWrap($name, $conf);
  }

	/**
	 * Fügt die TeamNotes für den Spieler hinzu. Wird kein Team übergeben, dann passiert nichts.
	 * @param tx_cfcleaguefe_models_team $team
	 */
	public function addTeamNotes($team) {
		// Zunächst alle Daten initialisieren
		tx_rnbase::load('tx_cfcleaguefe_models_teamNoteType');
		$types = tx_cfcleaguefe_models_teamNoteType::getAll();
		for($i=0, $cnt=count($types); $i < $cnt; $i++) {
			$type = $types[$i];
			$this->record['tn'.$type->getMarker()] = '';
			$this->record['tn'.$type->getMarker().'_type'] = '0';
		}

		if(is_object($team)) {
			// Mit Team können die TeamNotes geholt werden
			$notes = $this->getTeamNotes($team);
			for($i=0, $cnt=count($notes); $i < $cnt; $i++ ) {
				$note = $notes[$i];
				$noteType = $note->getType();
				$this->record['tn'.$noteType->getMarker()] = $note->uid;
				//$this->record['tn'.$noteType->getMarker()] = $note->getValue();
				$this->record['tn'.$noteType->getMarker().'_type'] = $note->record['mediatype'];
			}
		}
	}
	/**
	 * Liefert true, wenn für den Spieler eine Einzelansicht verlinkt werden soll.
	 */
	function hasReport() {
		return intval($this->record['link_report']);
	}
	/**
	 * Liefert das Sternzeichen der Person.
	 */
	public function getSign() {
		$signs = Tx_Cfcleaguefe_Utility_Signs::getInstance();
		return intval($this->getProperty('birthday')) != 0 ? $signs->getSign($this->record['birthday']) : '';
	}
}

