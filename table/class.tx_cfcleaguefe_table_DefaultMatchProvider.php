<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2017 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_cfcleaguefe_table_IMatchProvider');

/**
 * Match provider.
 */
class tx_cfcleaguefe_table_DefaultMatchProvider implements tx_cfcleaguefe_table_IMatchProvider
{
    /**
     * @var tx_rnbase_configurations
     */
    private $configurations;

    private $confId;

    private $league = 0;

    private $scope;

    /**
     * @var tx_cfcleague_util_MatchTableBuilder
     */
    private $matchTable;

    public function __construct($configurations, $confId)
    {
        $this->configurations = $configurations;
        $this->confId = $confId;
    }

    public function setMatchTable($matchTable)
    {
        $this->matchTable = $matchTable;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setConfigurator($configurator)
    {
        $this->configurator = $configurator;
    }

    /**
     * @return tx_cfcleaguefe_table_IConfigurator
     */
    public function getConfigurator()
    {
        return $this->configurator;
    }

    /**
     * It is possible to set teams from external.
     * Useful to avoid database access.
     *
     * @param array[tx_cfcleague_models_Team] $teams
     */
    public function setTeams($teams)
    {
        $this->teams = $teams;
    }

    /**
     * Return all teams or clubs of given matches.
     * It returns teams for simple league tables.
     * But for alltime table, teams are useless. It exists one saison only!
     * So for alltime table clubs are returned.
     *
     * @return array[tx_cfcleague_models_Team]
     */
    public function getTeams()
    {
        if (is_array($this->teams)) {
            return $this->teams;
        }
        $this->teams = array();
        // Es ist keine gute Idee, die Teams über die beendeten Spiele zu holen.
        // Dadurch kann am Saisonbeginn keine Tabelle erstellt werden.
        // Es ist besser die Spiele über die Wettbewerbe zu laden.
        $fields = array();
        $options = array();
        tx_rnbase::load('tx_cfcleague_search_Builder');
        tx_cfcleague_search_Builder::setField($fields, 'COMPETITION.SAISON', OP_IN_INT, $this->scope['SAISON_UIDS']);
        tx_cfcleague_search_Builder::setField($fields, 'COMPETITION.AGEGROUP', OP_INSET_INT, $this->scope['GROUP_UIDS']);
        tx_cfcleague_search_Builder::setField($fields, 'COMPETITION.UID', OP_IN_INT, $this->scope['COMP_UIDS']);
        tx_cfcleague_search_Builder::setField($fields, 'TEAM.CLUB', OP_IN_INT, $this->scope['CLUB_UIDS']);

        if (intval($this->scope['COMP_OBLIGATION'])) {
            if (1 == intval($this->scope['COMP_OBLIGATION'])) {
                $fields['COMPETITION.OBLIGATION'][OP_EQ_INT] = 1;
            } else {
                $fields['COMPETITION.OBLIGATION'][OP_NOTEQ_INT] = 1;
            }
        }

        tx_cfcleague_search_Builder::setField($fields, 'COMPETITION.TYPE', OP_IN_INT, $this->scope['COMP_TYPES']);
        tx_cfcleague_search_Builder::setField($fields, 'TEAM.AGEGROUP', OP_IN_INT, $this->scope['TEAMGROUP_UIDS']);

        $options['distinct'] = 1;
        $options['orderby']['TEAM.SORTING'] = 'asc'; // Nach Sortierung auf Seite
        $teams = tx_cfcleague_util_ServiceRegistry::getTeamService()->searchTeams($fields, $options);
        $useClubs = $this->useClubs();
        foreach ($teams as $team) {
            if (!$useClubs) {
                if ($team->getUid() && !array_key_exists($team->getUid(), $this->teams)) {
                    $this->teams[$team->getUid()] = $team;
                }
            } else {
                $club = $team->getClub();
                if ($club->getUid() && !array_key_exists($club->getUid(), $this->teams)) {
                    $club->setProperty('club', $club->getUid()); // necessary for mark clubs
                    $this->teams[$club->getUid()] = $club;
                }
            }
        }

        return $this->teams;
    }

    private function useClubs()
    {
        // Wenn die Tabelle über mehrere Saisons geht, dann müssen Clubs verwendet werden
        $matchTable = $this->getMatchTable();
        $fields = array();
        $options = array();
        $matchTable->getFields($fields, $options);
        $options['what'] = 'count(DISTINCT COMPETITION.SAISON) AS `saison`';
        $compSrv = tx_cfcleague_util_ServiceRegistry::getCompetitionService();
        $result = $compSrv->search($fields, $options);

        return intval($result[0]['saison']) > 1;
    }

    /**
     * @return tx_cfcleague_models_Competition
     */
    public function getBaseCompetition()
    {
        return $this->getLeague();
    }

    /**
     * If table is written for a single league, this league will be returned.
     * return tx_cfcleague_models_Competition or false.
     */
    protected function getLeague()
    {
        if (0 === $this->league) {
            // Den Wettbewerb müssen wir initial auf Basis des Scopes laden.
            $this->league = self::getLeagueFromScope($this->scope);
        }

        return $this->league;
    }

    public function setLeague($league)
    {
        $this->league = $league;
    }

    public function getRounds()
    {
        $rounds = array();
        $matches = $this->getMatches();
        for ($i = 0, $cnt = count($matches); $i < $cnt; ++$i) {
            $match = $matches[$i];
            $rounds[$match->record['round']][] = $match;
        }

        return $rounds;
    }

    /**
     * Liefert die Gesamtzahl der Spieltage einer Saison.
     */
    public function getMaxRounds()
    {
        // TODO: Das geht nur, wenn der Scope auf eine Liga zeigt
        $league = $this->getLeague();

        return $league ? count($league->getRounds()) : 0;
    }

    /**
     * @return tx_cfcleague_util_MatchTableBuilder
     */
    private function getMatchTable()
    {
        if (is_object($this->matchTable)) {
            return $this->matchTable;
        }

        // Todo: Was ist, wenn MatchTable nicht extern gesetzt wurde??
        $matchSrv = tx_cfcleague_util_ServiceRegistry::getMatchService();
        $matchTable = $matchSrv->getMatchTableBuilder();
        $matchTable->setStatus($this->getMatchStatus()); // Status der Spiele
        // Der Scope zählt. Wenn da mehrere Wettbewerbe drin sind, ist das ein Problem
        // in der Plugineinstellung. Somit funktionieren aber auch gleich die Alltimetabellen
        $matchTable->setScope($this->scope);
        // $matchTable->setCompetitionTypes(1);

        if ($this->currRound) {
            // Nur bis zum Spieltag anzeigen
            $matchTable->setMaxRound($this->currRound);
        }
        if (is_array($this->scope)) {
            $scopeArr = $this->scope;
            // Die Runde im Scope wird gesondert gesetzt.
            unset($scopeArr['ROUND_UIDS']);
            $matchTable->setScope($scopeArr);
        }
        $this->matchTable = $matchTable;

        return $this->matchTable;
    }

    private function getMatchStatus()
    {
        $status = $this->configurations->get($this->confId.'filter.matchstatus');
        if (!$status) {
            $status = $this->configurations->get('showLiveTable') ? '1,2' : '2';
        }

        return $status;
    }

    public function setMatches($matches)
    {
        $this->matches = $matches;
    }

    /**
     * Liefert die Spiele, die für die Berechnung der Tabelle notwendig sind.
     * Hier werden auch die Einstellungen des Configurators verwendet.
     */
    protected function getMatches()
    {
        if (is_array($this->matches)) {
            return $this->matches;
        }

        $matchTable = $this->getMatchTable();
        $fields = array();
        $options = array();
        $options['orderby']['MATCH.ROUND'] = 'asc';
        $matchTable->getFields($fields, $options);
        // Bei der Spielrunde gehen sowohl der TableScope (Hin-Rückrunde) als auch
        // die currRound ein: Rückrundentabelle bis Spieltag X -> JOINED Field
        // Haben wir eine $currRound
        tx_rnbase_util_SearchBase::setConfigFields($fields, $this->configurations, $this->confId.'filter.fields.');
        tx_rnbase_util_SearchBase::setConfigOptions($options, $this->configurations, $this->confId.'filter.options.');

        $this->modifyMatchFields($fields, $options);
        $this->matches = tx_cfcleague_util_ServiceRegistry::getMatchService()->search($fields, $options);

        return $this->matches;
    }

    /**
     * Entry point for child classes to modify fields and options for match lookup.
     *
     * @param array $fields
     * @param array $options
     */
    protected function modifyMatchFields(&$fields, &$options)
    {
    }

    public function getPenalties()
    {
        // Die Ligastrafen werden in den Tabellenstand eingerechnet. Dies wird allerdings nur
        // für die normale Tabelle gemacht. Sondertabellen werden ohne Strafen berechnet.
        // if($this->cfgTableType || $this->cfgTableScope)
        // return array();

        // $this->getConfigurator()->
        return $this->getLeague()->getPenalties();
    }

    /**
     * Returns the first league found from given scope.
     *
     * @param array $scopeArr
     *
     * @return tx_cfcleague_models_Competition
     */
    public static function getLeagueFromScope($scopeArr)
    {
        if (!($scopeArr['COMP_UIDS'] && $scopeArr['COMP_UIDS'] == intval($scopeArr['COMP_UIDS']))) {
            $matchSrv = tx_cfcleague_util_ServiceRegistry::getMatchService();
            $matchTable = $matchSrv->getMatchTableBuilder();
            $matchTable->setScope($scopeArr);

            $fields = array();
            $options = array();
            $matchTable->getFields($fields, $options);
            $options['what'] = 'distinct competition';

            $result = tx_cfcleague_util_ServiceRegistry::getMatchService()->search($fields, $options);
            // es wird immer nur der 1. Wettbewerb verwendet
            $leagueUid = count($result) ? $result[0]['competition'] : false;
        } else {
            $leagueUid = intval($scopeArr['COMP_UIDS']);
        }
        if (!$leagueUid) {
            throw new Exception('Could not find a valid competition.');
        }
        $league = tx_cfcleague_models_Competition::getCompetitionInstance($leagueUid);
        if (!$league->isValid()) {
            throw new Exception('Competition with uid '.intval($leagueUid).' is not valid!');
        }

        return $league;
    }

    /**
     * Returns the table marks used to mark some posititions in table.
     * It should be normally
     * retrieved from competition.
     *
     * @return string
     */
    public function getTableMarks()
    {
        return $this->getLeague()->getTableMarks();
    }
}
