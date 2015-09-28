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
tx_rnbase::load('tx_rnbase_model_base');
tx_rnbase::load('tx_cfcleague_models_Competition');
tx_rnbase::load('tx_rnbase_util_Math');

/**
 * Model für einen Spielplan. Dieser kann für einen oder mehrere Wettbewerbe abgerufen werden.
 */
class tx_cfcleaguefe_models_competition extends tx_cfcleague_models_Competition {
	private static $instances = array();
	/** array of teams */
	private $teams;
	/**
	 * array of matches
	 * Containes retrieved matches by state
	 */
	private $matchesByState = array();
	/** array of penalties */
	private $penalties;

	function getTableName(){return 'tx_cfcleague_competition';}

	/**
	 * Liefert alle Spiele des Wettbewerbs mit einem bestimmten Status.
	 * Der Status kann sein:
	 * <ul>
	 * <li> 0 - angesetzt
	 * <li> 1 - läuft
	 * <li> 2 - beendet
	 * </ul>
	 * @param scope - 0,1,2 für alle, Hin-, Rückrunde
	 */
	function getMatches($status, $scope=0) {
		// Sicherstellen, dass wir eine Zahl bekommen
		if((isset($status) && tx_rnbase_util_Math::testInt($status))) {
			$status = intval($status);
			// Wir laden die Spieldaten zunächst ohne die Teams
			// Um die Datenmenge in Grenzen zu halten
			$round = 0;
			$scope = intval($scope);
			if($scope) {
				// Feststellen wann die Hinrunde endet: Anz Teams - 1
				$round = count(t3lib_div::intExplode(',',$this->record['teams']));
				$round = ($round) ? $round - 1 : $round;
			}
			// Check if data is already cached
			if(!is_array($this->matchesByState[$status . '_' . $scope])) {
				$what = '*';
				# Die UID der Liga setzen
				$where = 'competition="'.$this->uid.'" ';
				switch($status) {
					case 1:
						$where .= ' AND status>="' . $status . '"';
						break;
					default:
						$where .= ' AND status="' . $status . '"';
				}
				if($scope && $round) {
					switch($scope) {
						case 1:
							$where .= ' AND round<="' . $round . '"';
							break;
						case 2:
							$where .= ' AND round>"' . $round . '"';
							break;
					}
				}
				$options['where'] = $where;
				$options['wrapperclass'] = 'tx_cfcleaguefe_models_match';
				// Issue 1880237: Return matches sorted by round
				$options['orderby'] = 'round, date';
				$this->matchesByState[$status . '_' . $scope] = tx_rnbase_util_DB::doSelect($what,'tx_cfcleague_games',$options, 0);
			}
			return $this->matchesByState[$status . '_' . $scope];
		}
	}

	function getName() {
		return $this->record['name'];
	}
	function getInternalName() {
		$ret = $this->record['internal_name'];
		$ret = strlen($ret) ? $ret : $this->record['short_name'];
		$ret = strlen($ret) ? $ret : $this->record['name'];
		return $ret;
	}
	/**
	 * Set matches for a state and scope
	 *
	 * @param array $matchesArr
	 * @param int $status
	 * @param int $scope
	 */
	function setMatches($matchesArr, $status, $scope = 0) {
 		$this->matchesByState[intval($status) . '_' . intval($scope)] = is_array($matchesArr) ? $matchesArr : NULL;
	}

	/**
	 * Whether or not this competition is type league
	 * @return boolean
	 */
	function isTypeLeague() {
		return $this->record['type'] == 1;
	}
	/**
	 * Whether or not this competition is type league
	 * @return boolean
	 */
	function isTypeCup() {
		return $this->record['type'] == 2;
	}
	/**
	 * Whether or not this competition is type league
	 * @return boolean
	 */
	function isTypeOther() {
		return $this->record['type'] == 0;
	}
	/**
	 * Returns the number of match parts. Default is two.
	 *
	 * @return int
   */
	public function getMatchParts() {
		$parts = intval($this->record['match_parts']);
		return $parts > 0 ? $parts : 2;
	}
	/**
	 * Liefert ein Array mit allen Spielrunden der Liga
	 *
	 * @return array[tx_cfcleaguefe_models_competition_round]
	 */
	function getRounds(){
		# build SQL for select
		$what = 'distinct round as uid,round AS number,round_name As name, max(status) As finished';
		# WHERE
		$options = array();
		# Die UID der Liga setzen
		$options['where'] = 'competition="'.$this->uid.'"';
		$options['groupby'] = 'round,round_name';
		$options['orderby'] = 'round';
		$options['wrapperclass'] = 'tx_cfcleaguefe_models_competition_round';

		return tx_rnbase_util_DB::doSelect($what, 'tx_cfcleague_games', $options);
	}
	/**
	 * Liefert die Spiele einer bestimmten Spielrunde
	 *
	 * @param int $roundId
	 */
	function getMatchesByRound($roundId) {
		$fields = array();
		$options = array();
	  $fields['MATCH.ROUND'][OP_EQ_INT] = $roundId;
	  $fields['MATCH.COMPETITION'][OP_EQ_INT] = $this->uid;
//	  $options['debug'] = 1;
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
  	$matches = $service->search($fields, $options);
//t3lib_div::debug($roundId, 'tx_cfcleaguefe_models_competition'); // TODO: Remove me!
  	return $matches;
  }

  /**
   * Anzahl der Spiele des/der Teams in diesem Wettbewerb
   */
  function getNumberOfMatches($teamIds, $status = '0,1,2'){
    $what = 'count(uid) As matches';
    $from = 'tx_cfcleague_games';
    $options['where'] = 'status IN(' . $status . ') AND ';
    if($teamIds) {
      $options['where'] .= '( home IN(' . $teamIds . ') OR ';
      $options['where'] .= 'guest IN(' . $teamIds . ')) AND ';
    }
    $options['where'] .= 'competition = ' . $this->uid . ' ';
    $rows = tx_rnbase_util_DB::doSelect($what,$from,$options,0);
    $ret = 0;
    if(count($rows))
      $ret = intval($rows[0]['matches']);
    return $ret;
  }

  /**
   * Returns the age croup of this competition.
   * Since version 0.6.0 there are multiple agegroups possible. For backward compatibility this
   * method returns the first competition per default.
   *
   * @return tx_cfcleaguefe_models_group
   */
  function getGroup($all=false) {
		tx_rnbase::load('tx_cfcleaguefe_models_group');
		$groupIds = t3lib_div::intExplode(',',$this->record['agegroup']);
		if(!count($groupIds)) return null;
		if(!$all) return tx_cfcleaguefe_models_group::getInstance($groupIds[0]);
		$ret = array();
		foreach($groupIds As $groupId) {
			$ret[] = tx_cfcleaguefe_models_group::getInstance($groupId);
		}
  	return $ret;
  }
  /**
   * Returns all team participating this competition.
   * @return array of tx_cfcleaguefe_models_team
   */
  function getTeams($ignoreDummies = true) {
    if(!is_array($this->teams)) {
      $uids = $this->record['teams'];
      $options['where'] = 'uid IN (' . $uids .') ';
      if($ignoreDummies)
      	$options['where'] .= ' AND dummy = 0  ';

      $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';
      $options['orderby'] = 'sorting';
      $this->teams = tx_rnbase_util_DB::doSelect('*','tx_cfcleague_teams',$options, 0);
    }
    return $this->teams;
  }

  /**
   * Set participating teams. This is usually not necessary, since getTeams()
   * makes an automatic lookup in database.
   *
   * @param array $teamsArr if $teamsArr is no array the internal array is removed
   */
  function setTeams($teamsArr) {
    $this->teams = is_array($teamsArr) ? $teamsArr : NULL;
  }
  /**
   * Returns an instance of tx_cfcleaguefe_models_competition
   * @param int $uid
   * @return tx_cfcleaguefe_models_competition
   */
  public static function &getInstance($uid, $record = 0) {
    $uid = intval($uid);
    $comp = self::$instances[$uid];
    if(!is_object($comp)) {
      $comp = new tx_cfcleaguefe_models_competition(is_array($record) ? $record : $uid);
      self::$instances[$uid] = $comp;
    }
    return $comp;
  }
  /**
   * statische Methode, die ein Array mit Instanzen dieser Klasse liefert.
   * Es werden entweder alle oder nur bestimmte Wettkämpfe einer Saison geliefert.
   * @param string $saisonUid int einzelne UID einer Saison
   * @param string $groupUid int einzelne UID einer Altersklasse
   * @param string $uids String kommaseparierte Liste von Competition-UIDs
   * @param string $compTypes String kommaseparierte Liste von Wettkampftypen (1-Liga;2-Pokal;0-Sonstige)
   * @return Array der gefundenen Wettkämpfe
   */
  function findAll($saisonUid = '', $groupUid = '', $uids = '', $compTypes='') {
    if(is_string($uids) && strlen($uids) > 0) {
      $where = 'uid IN (' . $uids .')';
    }
    else
      $where = '1';

    if(is_numeric($saisonUid)) {
      $where .= ' AND saison = ' . $saisonUid .'';
    }

    if(is_numeric($groupUid)) {
      $where .= ' AND agegroup = ' . $groupUid .'';
    }

    if(strlen($compTypes)) {
      $where .= ' AND type IN (' . implode(t3lib_div::intExplode(',', $compTypes), ',') . ')';
    }

    /*
    SELECT * FROM tx_cfcleague_competition WHERE uid IN ($uid)
    */

		$options = array();
		$options['where'] = $where;
		$options['orderby'] = 'sorting';
		$options['wrapperclass'] = 'tx_cfcleaguefe_models_competition';
		return tx_rnbase_util_DB::doSelect('*','tx_cfcleague_competition', $options);
  }

  /**
   * Liefert ein Array mit den Tabellen-Markierungen
   * arr[$position] = array(markId, comment);
   */
  function getTableMarks() {
    $str = $this->record['table_marks'];
    if(!$str) return 0;

    $ret = array();
    $arr = t3lib_div::trimExplode('|',$str);
    foreach($arr As $item) {
      // Jedes Item splitten
      $mark = t3lib_div::trimExplode(';',$item);
      $positions = t3lib_div::intExplode(',',$mark[0]);
      $comments = t3lib_div::trimExplode(',',$mark[1]);
      // Jetzt das Ergebnisarray aufbauen
      foreach($positions As $position) {
        $ret[$position] = Array($comments[0], $comments[1]);
      }

    }
    return $ret;
  }

	/**
	 * Liefert die verhängten Strafen für Teams des Wettbewerbs.
	 */
	function getPenalties() {
		if(!is_array($this->penalties)) {
			$options = array();
			# Die UID der Liga setzen
			$options['where'] = 'competition="'.$this->uid.'" ';
			$options['orderby'] = 'sorting';
			$options['wrapperclass'] = 'tx_cfcleaguefe_models_competition_penalty';
			$this->penalties = tx_rnbase_util_DB::doSelect('*','tx_cfcleague_competition_penalty', $options);
		}
		return $this->penalties;
	}
	/**
	 * Set penalties
	 *
	 * @param array $penalties
	 */
	function setPenalties($penalties) {
		$this->penalties = is_array($penalties) ? $penalties : NULL;
	}
}

/**
 * Die Spielrunde hat keine eigene Tabelle. Die Verwendung der Basisklasse hat aber den
 * Vorteil des besseren Handlings im weiteren Verlauf.
 */
class tx_cfcleaguefe_models_competition_round extends tx_rnbase_model_base {
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_competition.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_competition.php']);
}

?>