<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2017 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_cfcleague_models_Team');
tx_rnbase::load('tx_cfcleaguefe_models_club');
tx_rnbase::load('tx_cfcleaguefe_search_Builder');
tx_rnbase::load('tx_cfcleague_models_Group');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');

/**
 * Model für ein Team.
 */
class tx_cfcleaguefe_models_team extends tx_cfcleague_models_Team
{

    private $_players;

    private $_coaches;

    private $_supporters;

    private $agegroup = null;

    /**
     * Array with loaded team instances
     */
    private static $instances;

    public function getName()
    {
        return $this->getProperty('name');
    }

    public function getNameShort()
    {
        return $this->getProperty('short_name');
    }

    /**
     * Liefert den Verein des Teams als Objekt
     *
     * @return tx_cfcleaguefe_models_club Verein als Objekt oder 0
     */
    public function getClub()
    {
        if (! $this->getProperty('club'))
            return 0;
        return tx_cfcleaguefe_models_club::getClubInstance($this->getProperty('club'));
    }

    /**
     * Returns the UID of club
     *
     * @return int
     */
    public function getClubUid()
    {
        return $this->getProperty('club');
    }

    /**
     * Returns the teams age group.
     * This value is retrieved from the teams competitions. So
     * the first competition found, decides about the age group.
     *
     * @return tx_cfcleague_models_Group or null
     */
    public function getAgeGroup()
    {
        if (! $this->agegroup) {
            if (intval($this->getProperty('agegroup')))
                $this->agegroup = tx_cfcleague_models_Group::getGroupInstance($this->getProperty('agegroup'));
            if (! $this->agegroup) {
                $comps = $this->getCompetitions(true);
                for ($i = 0, $cnt = count($comps); $i < $cnt; $i ++) {
                    if (is_object($comps[$i]->getGroup())) {
                        $this->agegroup = $comps[$i]->getGroup();
                        break;
                    }
                }
            }
        }
        return $this->agegroup;
    }

    /**
     * Returns the group uid set in team.
     * This may be 0.
     *
     * @return int
     */
    public function getAgeGroupUid()
    {
        return $this->getProperty('agegroup');
    }

    /**
     * Returns the competitons of this team
     *
     * @param boolean $obligateOnly
     *            if true, only obligate competitions are returned
     * @return array of tx_cfcleaguefe_models_competition
     */
    public function getCompetitions($obligateOnly = false)
    {
        $fields = array();
        tx_cfcleaguefe_search_Builder::buildCompetitionByTeam($fields, $this->getUid(), $obligateOnly);
        $srv = tx_cfcleaguefe_util_ServiceRegistry::getCompetitionService();
        return $srv->search($fields, $options);
    }

    /**
     * Liefert die Trainer des Teams in der vorgegebenen Reihenfolge als Profile.
     * Der
     * Key ist die laufende Nummer und nicht die UID!
     */
    public function getCoaches()
    {
        if (is_array($this->_coaches))
            return $this->_coaches;
        $this->_coaches = $this->_getTeamMember('coaches');
        return $this->_coaches;
    }

    /**
     * Liefert die Betreuer des Teams in der vorgegebenen Reihenfolge als Profile.
     * Der
     * Key ist die laufende Nummer und nicht die UID!
     */
    public function getSupporters()
    {
        if (is_array($this->_supporters))
            return $this->_supporters;
        $this->_supporters = $this->_getTeamMember('supporters');
        return $this->_supporters;
    }

    /**
     * Liefert die Spieler des Teams in der vorgegebenen Reihenfolge als Profile.
     * Der
     * Key ist die laufende Nummer und nicht die UID!
     */
    public function getPlayers()
    {
        if (is_array($this->_players))
            return $this->_players;
        $this->_players = $this->_getTeamMember('players');
        return $this->_players;
    }

    /**
     * Liefert true, wenn für das Team eine Einzelansicht verlinkt werden kann.
     * @return
     */
    public function hasReport()
    {
        return intval($this->getProperty('link_report')) > 0;
    }

    /**
     * Returns cached instances of teams
     *
     * @param int $teamUid
     * @return tx_cfcleaguefe_models_team
     */
    public static function getTeamInstance($teamUid)
    {
        $uid = intval($teamUid);
        if (! $uid)
            throw new Exception('Team uid expected. Was: >' . $teamUid . '<', - 1);
        if (! self::$instances[$uid]) {
            self::$instances[$uid] = tx_rnbase::makeInstance('tx_cfcleaguefe_models_team', $teamUid);
        }
        return self::$instances[$uid];
    }

    public static function addInstance($team)
    {
        self::$instances[$team->getUid()] = $team;
    }

    /**
     * Liefert Mitglieder des Teams als Array.
     * Teammitglieder sind Spieler, Trainer und Betreuer.
     * Die gefundenen Profile werden sortiert in der Reihenfolge im Team geliefert.
     * @column Name der DB-Spalte mit den gesuchten Team-Mitgliedern
     */
    protected function _getTeamMember($column)
    {
        if (strlen(trim($this->getProperty($column))) > 0) {
            $what = '*';
            $from = 'tx_cfcleague_profiles';
            $options['where'] = 'uid IN (' . $this->getProperty($column) . ')';
            $options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';

            $rows = tx_rnbase_util_DB::doSelect($what, $from, $options, 0);
            return $this->sortPlayer($rows, $column);
        }
        return array();
    }

    /**
     * Sortiert die Personen (Spieler/Trainer) entsprechend der Reihenfolge im Team
     *
     * @param $profiles array
     *            of tx_cfcleaguefe_models_profile
     */
    protected function sortPlayer($profiles, $recordKey = 'players')
    {
        $ret = array();
        if (strlen(trim($this->getProperty($recordKey))) > 0) {
            if (count($profiles)) {
                // Jetzt die Spieler in die richtige Reihenfolge bringen
                $uids = Tx_Rnbase_Utility_Strings::intExplode(',', $this->getProperty($recordKey));
                $uids = array_flip($uids);
                foreach ($profiles as $player) {
                    $ret[$uids[$player->getUid()]] = $player;
                }
            }
        } else {
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
    public function isDummy()
    {
        return intval($this->getProperty('dummy')) != 0;
    }

    /**
     * Return all teams by an array of uids.
     *
     * @param mixed $teamIds
     * @return array of tx_cfcleaguefe_models_team
     */
    function getTeamsByUid($teamIds)
    {
        if (! is_array($teamIds)) {
            $teamIds = Tx_Rnbase_Utility_Strings::intExplode(',', $teamIds);
        }
        if (! count($teamIds))
            return array();
        $teamIds = implode($teamIds, ',');
        $what = '*';
        $from = 'tx_cfcleague_teams';
        $options['where'] = 'tx_cfcleague_teams.uid IN (' . $teamIds . ') ';
        $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

        return tx_rnbase_util_DB::doSelect($what, $from, $options, 0);
    }

    /**
     * Returns Teams by competition and club.
     * This method can be used static.
     * @return tx_cfcleaguefe_models_team[]
     */
    public static function getTeams($competitionIds, $clubIds)
    {
        $competitionIds = implode(Tx_Rnbase_Utility_Strings::intExplode(',', $competitionIds), ',');
        $clubIds = implode(Tx_Rnbase_Utility_Strings::intExplode(',', $clubIds), ',');

        // $what = tx_cfcleaguefe_models_team::getWhat();
        $what = 'tx_cfcleague_teams.*';
        $from = Array(
            '
			tx_cfcleague_teams
				JOIN tx_cfcleague_competition ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )',
            'tx_cfcleague_teams'
        );

        $options['where'] = 'tx_cfcleague_teams.club IN (' . $clubIds . ') AND ';
        $options['where'] .= 'tx_cfcleague_competition.uid IN (' . $competitionIds . ') ';
        $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

        return tx_rnbase_util_DB::doSelect($what, $from, $options, 0);

        /*
         * SELECT tx_cfcleague_teams.uid, tx_cfcleague_teams.name, tx_cfcleague_competition.uid AS comp_uid, tx_cfcleague_competition.name AS comp_name, tx_cfcleague_competition.teams AS comp_teams
         * FROM tx_cfcleague_teams
         * JOIN tx_cfcleague_competition ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )
         * WHERE tx_cfcleague_teams.club =1
         * AND tx_cfcleague_competition.uid =1
         */
    }
}

