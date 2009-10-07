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

// Die Datenbank-Klasse
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');
require_once(t3lib_extMgm::extPath('rn_base') . 'model/class.tx_rnbase_model_base.php');

tx_div::load('tx_cfcleaguefe_models_club');
tx_div::load('tx_cfcleaguefe_search_Builder');

/**
 * Model für ein Team.
 */
class tx_cfcleaguefe_models_team extends tx_rnbase_model_base {
  var $_players;
  var $_coaches;
  var $_supporters;
  /** Array with loaded team instances */
  private static $instances;

  function getTableName(){return 'tx_cfcleague_teams';}

  /**
   * Liefert den Namen des Teams
   * @param $confId die TS-Config für den Teamdatensatz
   */
  function getNameWrapped($formatter, $confId = 'team.') {
    return $formatter->wrap($this->record['name'], $confId . 'teamName.');
  }
  function getName() {
  	return $this->record['name'];
  }
  function getNameShort() {
  	return $this->record['short_name'];
  }
  
  /**
   * Liefert den Verein des Teams als Objekt
   * @return tx_cfcleaguefe_models_club Verein als Objekt oder 0
   */
  function getClub() {
    if(!$this->record['club']) return 0;
    return tx_cfcleaguefe_models_club::getInstance($this->record['club']);
  }
	var $agegroup = null;
	/**
	 * Returns the teams age group. This value is retrieved from the teams competitions. So 
	 * the first competition found, decides about the age group.
	 * @return tx_cfcleaguefe_models_group or null
	 */
	function getAgeGroup() {
		if(!$this->agegroup) {
			$comps = $this->getCompetitions(true);
			for($i=0, $cnt = count($comps); $i < $cnt; $i++) {
				if(is_object($comps[$i]->getGroup())) {
					$this->agegroup = $comps[$i]->getGroup();
					break;
				}
			}
		}
		return $this->agegroup;
  }
  /**
   * Returns the competitons of this team
   * @param boolean $obligateOnly if true, only obligate competitions are returned
   * @return array of tx_cfcleaguefe_models_competition
   */
  function getCompetitions($obligateOnly = false) {
  	$fields = array();
  	tx_cfcleaguefe_search_Builder::buildCompetitionByTeam($fields, $this->uid,$obligateOnly);
  	$srv = tx_cfcleaguefe_util_ServiceRegistry::getCompetitionService();
  	return $srv->search($fields, $options);
  }

  /**
   * Liefert die Trainer des Teams in der vorgegebenen Reihenfolge als Profile. Der
   * Key ist die laufende Nummer und nicht die UID!
   */
  function getCoaches() {
    if(is_array($this->_coaches))
      return $this->_coaches;
    $this->_coaches = $this->_getTeamMember('coaches');
    return $this->_coaches;
  }

  /**
   * Liefert die Betreuer des Teams in der vorgegebenen Reihenfolge als Profile. Der
   * Key ist die laufende Nummer und nicht die UID!
   */
  function getSupporters() {
    if(is_array($this->_supporters))
      return $this->_supporters;
    $this->_supporters = $this->_getTeamMember('supporters');
    return $this->_supporters;
  }

  /**
   * Liefert die Spieler des Teams in der vorgegebenen Reihenfolge als Profile. Der
   * Key ist die laufende Nummer und nicht die UID!
   */
  function getPlayers() {
    if(is_array($this->_players))
      return $this->_players;
    $this->_players = $this->_getTeamMember('players');
    return $this->_players;
  }


	/**
	 * Liefert das Logo des Teams. Es ist entweder das zugeordnete Logo des Teams oder 
	 * das Logo des Vereins.
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @deprecated Das Logo wird per Typoscript ermittelt
	 */
	function getLogo(&$formatter, $confId) {
		$image = false;
		// Hinweis: Die TCA-Definition ist im Team und im Club verschieden. Im Team ist es eine 1-n Relation
		// Und im Club eine n-m-Beziehung. Daher muss der Zugriff unterschiedlich erfolgen.
		// Grund dafür gibt es keinen...

		// Vorrang hat das Teamlogo
		if($this->record['dam_logo']) {
			$damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_teams', $this->uid, 'relation_field_or_other_ident');
			if(list($uid, $filePath) = each($damPics['files'])) {
				// Das Bild muss mit einem alternativen cObj erzeugt werden, damit Gallerie nicht aktiviert wird
//        $image = $formatter->getDAMImage($filePath, 'matchreport.logo.', 'cfc_league', 'cObjLogo');
				$image = $formatter->getDAMImage($filePath, $confId, 'cfc_league', 'cObjLogo');
			}
		}
		if(!$image) {
			// Wir suchen den Verein
			$club = $this->getClub();
			// Ist ein Logo vorhanden?
			if(is_object($club) && $club->record['dam_logo']) {
				$damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_club', $club->uid, 'dam_images');
				if(list($uid, $filePath) = each($damPics['files'])) {
					// Das Bild muss mit einem alternativen cObj erzeugt werden, damit Gallerie nicht aktiviert wird
					$image = $formatter->getDAMImage($filePath, $confId, 'cfc_league', 'cObjLogo');
				}
			}
		}

		// Es ist kein Logo vorhanden
		if(!$image) {
			$conf = $formatter->configurations->get($confId . 'noLogo_stdWrap.');
			$image = $formatter->dataStdWrap($this->record, '', $confId . 'noLogo_stdWrap.');
		}
		return $image;
	}

  /**
   * Liefert true, wenn für das Team eine Einzelansicht verlinkt werden kann.
   */
  function hasReport() {
    return intval($this->record['link_report']);
  }

  /**
   * Returns cached instances of teams
   *
   * @param int $teamUid
   * @return tx_cfcleaguefe_models_team
   */
  static function getInstance($teamUid) {
    $uid = intval($teamUid);
    if(!$uid) throw new Exception('Team uid expected. Was: >' . $teamUid . '<', -1);
    if(! self::$instances[$uid]) {
      $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_team');
      self::$instances[$uid] = new $className($teamUid);
    }
    return self::$instances[$uid];
  }
	static function addInstance(&$team) {
		self::$instances[$team->uid] = $team;
	}
  /**
   * Liefert Mitglieder des Teams als Array. Teammitglieder sind Spieler, Trainer und Betreuer.
   * Die gefundenen Profile werden sortiert in der Reihenfolge im Team geliefert.
   * @column Name der DB-Spalte mit den gesuchten Team-Mitgliedern
   */
  function _getTeamMember($column) {
    if(strlen(trim($this->record[$column])) > 0 ) {
      $what = '*';
      $from = 'tx_cfcleague_profiles';
      $options['where'] = 'uid IN (' .$this->record[$column] . ')';
      $options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';

      $rows = tx_rnbase_util_DB::doSelect($what,$from,$options,0);
      return $this->sortPlayer($rows, $column);
    }
    return array();
  }

  /**
   * Sortiert die Personen (Spieler/Trainer) entsprechend der Reihenfolge im Team
   * @param $profiles array of tx_cfcleaguefe_models_profile
   */
  function sortPlayer($profiles, $recordKey = 'players') {
    $ret = array();
    if(strlen(trim($this->record[$recordKey])) > 0 ) {
      if(count($profiles)) {
        // Jetzt die Spieler in die richtige Reihenfolge bringen
        $uids = t3lib_div::intExplode(',', $this->record[$recordKey]);
        $uids = array_flip($uids);
        foreach($profiles as $player) {
          $ret[$uids[$player->uid]] = $player;
        }
      }
    }
    else {
      // Wenn keine Spieler im Team geladen sind, dann wird das Array unverändert zurückgegeben
      return $profiles;
    }
    return $ret;
  }

  /**
   * Check if team is a dummy for free_of_match.
   *
   * @return boolean
   */
  function isDummy(){
    return intval($this->record['dummy']) != 0;
  }
	/**
	 * Return all teams by an array of uids.
	 * @param mixed $teamIds
	 * @return array of tx_cfcleaguefe_models_team
	 */
	function getTeamsByUid($teamIds) {
		if(!is_array($teamIds)) {
			$teamIds = t3lib_div::intExplode(',',$teamIds);
		}
		if(!count($teamIds))
			return array();
		$teamIds = implode($teamIds, ',');
		$what = '*';
		$from = 'tx_cfcleague_teams';
		$options['where'] = 'tx_cfcleague_teams.uid IN (' . $teamIds . ') ';
		$options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

		return tx_rnbase_util_DB::doSelect($what,$from,$options,0);
	}

	/**
	 * Returns Teams by competition and club. This method can be used static.
	 * TODO: Als static deklarieren
	 */
	function getTeams($competitionIds, $clubIds) {
		$competitionIds = implode(t3lib_div::intExplode(',',$competitionIds), ',');
		$clubIds = implode(t3lib_div::intExplode(',',$clubIds), ',');

//    $what = tx_cfcleaguefe_models_team::getWhat();
		$what = 'tx_cfcleague_teams.*';
		$from = Array('
			tx_cfcleague_teams
				JOIN tx_cfcleague_competition ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )',
				'tx_cfcleague_teams');

		$options['where'] = 'tx_cfcleague_teams.club IN (' . $clubIds . ') AND ';
		$options['where'] .= 'tx_cfcleague_competition.uid IN (' . $competitionIds . ') ';
		$options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

		return tx_rnbase_util_DB::doSelect($what,$from,$options,0);

/*
SELECT tx_cfcleague_teams.uid, tx_cfcleague_teams.name, tx_cfcleague_competition.uid AS comp_uid, tx_cfcleague_competition.name AS comp_name, tx_cfcleague_competition.teams AS comp_teams
FROM tx_cfcleague_teams
JOIN tx_cfcleague_competition ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )
WHERE tx_cfcleague_teams.club =1
AND tx_cfcleague_competition.uid =1
*/
	}

  /**
   * Liefert alle Spalten des Teams
   * TODO: Über TCA dynamisch gestalten
   * @deprecated Should be removed
   */
  function getWhat() {
    return '
      tx_cfcleague_teams.uid, tx_cfcleague_teams.name, tx_cfcleague_teams.short_name, tx_cfcleague_teams.dummy,
      tx_cfcleague_teams.coaches, tx_cfcleague_teams.players, tx_cfcleague_teams.club,
      tx_cfcleague_teams.dam_images, tx_cfcleague_teams.comment, tx_cfcleague_teams.link_report
    ';
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_team.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_team.php']);
}

?>