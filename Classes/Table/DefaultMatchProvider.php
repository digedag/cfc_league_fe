<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Model\Competition;
use System25\T3sports\Model\Repository\MatchRepository;
use System25\T3sports\Model\Team;
use System25\T3sports\Search\SearchBuilder;
use System25\T3sports\Table\Football\Configurator;
use System25\T3sports\Utility\MatchTableBuilder;
use System25\T3sports\Utility\ServiceRegistry;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2024 Rene Nitzsche (rene@system25.de)
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

/**
 * Match provider.
 */
class DefaultMatchProvider implements IMatchProvider
{
    /**
     * @var ConfigurationInterface
     */
    private $configurations;

    /**
     * Conf-ID des Views.
     *
     * @var string
     */
    private $confId;

    /**
     * @var IConfigurator|Configurator
     */
    private $configurator;

    /**
     * @var Competition
     */
    private $league = null;

    private $scope;

    /**
     * @var int
     */
    private $currRound = 0;

    private $matchRepo;

    /**
     * @var MatchTableBuilder
     */
    private $matchTableBuilder;

    /**
     * @var array
     */
    private $clubIdsOfRunningMatches = null;

    /**
     * @var array
     */
    private $teams;

    /**
     * @var array
     */
    private $matches;

    /**
     * @param ConfigurationInterface $configurations
     * @param string $confId ConfId des Views
     * @param MatchRepository $matchRepo
     */
    public function __construct($configurations, $confId, MatchRepository $matchRepo = null)
    {
        $this->configurations = $configurations;
        $this->confId = $confId;
        $this->matchRepo = $matchRepo ?: new MatchRepository();
    }

    public function setMatchTable(MatchTableBuilder $matchTableBuilder)
    {
        $this->matchTableBuilder = $matchTableBuilder;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    /**
     * {@inheritDoc}
     *
     * @see \System25\T3sports\Table\IMatchProvider::setConfigurator()
     */
    public function setConfigurator(IConfigurator $configurator)
    {
        $this->configurator = $configurator;
    }

    /**
     * @return IConfigurator
     */
    public function getConfigurator(): IConfigurator
    {
        return $this->configurator;
    }

    /**
     * It is possible to set teams from external.
     * Useful to avoid database access.
     *
     * @param Team[] $teams
     * @param bool $useClubs
     */
    public function setTeams(array $teams, bool $useClubs)
    {
        $this->teams = [];
        foreach ($teams as $team) {
            // Was passiert mit Teams ohne Verein im Club-Modus? Einfach ignorieren?
            $team = new TeamAdapter($team, $useClubs);
            if (!array_key_exists($team->getTeamId(), $this->teams)) {
                $this->teams[$team->getTeamId()] = $team;
            } else {
                // In den Spielen bekommen wir am Ende immer nur die Team-UIDs. Daher müssen wir
                // uns jede Team-UID merken, damit wird direkten Zugriff auf den TeamAdapter haben.
                $baseTeam = $this->teams[$team->getTeamId()];
                $baseTeam->addTeamUid($team->getUid());
            }
        }
    }

    /**
     * Return all teams or clubs of given matches.
     * All dummy teams are ignored.
     *
     * @return ITeam[]
     */
    public function getTeams()
    {
        if (is_array($this->teams)) {
            return $this->teams;
        }

        // Es ist keine gute Idee, die Teams über die beendeten Spiele zu holen.
        // Dadurch kann am Saisonbeginn keine Tabelle erstellt werden.
        // Es ist besser die Spiele über die Wettbewerbe zu laden.
        $fields = $options = [];
        SearchBuilder::setField($fields, 'COMPETITION.SAISON', OP_IN_INT, $this->scope['SAISON_UIDS'] ?? '');
        SearchBuilder::setField($fields, 'COMPETITION.AGEGROUP', OP_INSET_INT, $this->scope['GROUP_UIDS'] ?? '');
        SearchBuilder::setField($fields, 'COMPETITION.UID', OP_IN_INT, $this->scope['COMP_UIDS'] ?? '');
        SearchBuilder::setField($fields, 'TEAM.CLUB', OP_IN_INT, $this->scope['CLUB_UIDS'] ?? '');

        if (isset($this->scope['COMP_OBLIGATION'])) {
            if (1 == intval($this->scope['COMP_OBLIGATION'])) {
                $fields['COMPETITION.OBLIGATION'][OP_EQ_INT] = 1;
            } else {
                $fields['COMPETITION.OBLIGATION'][OP_NOTEQ_INT] = 1;
            }
        }

        SearchBuilder::setField($fields, 'COMPETITION.TYPE', OP_IN_INT, $this->scope['COMP_TYPES'] ?? '');
        SearchBuilder::setField($fields, 'TEAM.AGEGROUP', OP_IN_INT, $this->scope['TEAMGROUP_UIDS'] ?? '');

        $options['distinct'] = 1;
        $options['orderby']['TEAM.SORTING'] = 'asc'; // Nach Sortierung auf Seite
        $teams = ServiceRegistry::getTeamService()->searchTeams($fields, $options);
        $useClubs = $this->useClubs();
        $this->setTeams($teams->toArray(), $useClubs);

        return $this->teams;
    }

    private function useClubs()
    {
        // Wenn die Tabelle über mehrere Saisons geht, dann müssen Clubs verwendet werden
        $matchTable = $this->getMatchTableBuilder();
        $fields = [];
        $options = [];
        $matchTable->getFields($fields, $options);
        $options['what'] = 'count(DISTINCT COMPETITION.SAISON) AS `saison`';
        $compSrv = ServiceRegistry::getCompetitionService();
        $result = $compSrv->search($fields, $options);

        return intval($result[0]['saison']) > 1;
    }

    /**
     * @return Competition
     */
    public function getBaseCompetition()
    {
        return $this->getLeague();
    }

    /**
     * If table is written for a single league, this league will be returned.
     * return Competition|null.
     */
    protected function getLeague()
    {
        if (null === $this->league) {
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
        $rounds = [];
        $matches = $this->getMatches();
        for ($i = 0, $cnt = count($matches); $i < $cnt; ++$i) {
            $match = $matches[$i];
            $rounds[$match->getProperty('round')][] = $match;
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
     * @return MatchTableBuilder
     */
    private function getMatchTableBuilder()
    {
        if (is_object($this->matchTableBuilder)) {
            return $this->matchTableBuilder;
        }

        // Was ist, wenn der MatchTableBuilder nicht extern gesetzt wurde??
        $matchSrv = ServiceRegistry::getMatchService();
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
        $this->matchTableBuilder = $matchTable;

        return $this->matchTableBuilder;
    }

    private function getMatchStatus()
    {
        $status = $this->configurations->get($this->confId.'filter.matchstatus');
        if (!$status) {
            $status = $this->configurator->isLiveTable() ? '1,2' : '2';
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

        $matchTableBuilder = $this->getMatchTableBuilder();
        $fields = [];
        $options = [];
        $options['orderby']['MATCH.ROUND'] = 'asc';
        $matchTableBuilder->getFields($fields, $options);
        // Bei der Spielrunde gehen sowohl der TableScope (Hin-Rückrunde) als auch
        // die currRound ein: Rückrundentabelle bis Spieltag X -> JOINED Field
        // Haben wir eine $currRound
        SearchBase::setConfigFields($fields, $this->configurations, $this->confId.'filter.fields.');
        SearchBase::setConfigOptions($options, $this->configurations, $this->confId.'filter.options.');

        $this->modifyMatchFields($fields, $options);
        $this->matches = $this->matchRepo->search($fields, $options);

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
     * @return Competition
     */
    public static function getLeagueFromScope($scopeArr)
    {
        if (!($scopeArr['COMP_UIDS'] && $scopeArr['COMP_UIDS'] == intval($scopeArr['COMP_UIDS']))) {
            $matchSrv = ServiceRegistry::getMatchService();
            $matchTable = $matchSrv->getMatchTableBuilder();
            $matchTable->setScope($scopeArr);

            $fields = [];
            $options = [];
            $matchTable->getFields($fields, $options);
            $options['what'] = 'distinct competition';

            $result = ServiceRegistry::getMatchService()->search($fields, $options);
            // es wird immer nur der 1. Wettbewerb verwendet
            $leagueUid = count($result) ? $result[0]['competition'] : false;
        } else {
            $leagueUid = (int) $scopeArr['COMP_UIDS'];
        }
        if (!$leagueUid) {
            throw new \Exception('Could not find a valid competition.');
        }
        $league = Competition::getCompetitionInstance($leagueUid);
        if (!$league->isValid()) {
            throw new \Exception('Competition with uid '.intval($leagueUid).' is not valid!');
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

    public function setCurrentRound($round)
    {
        $this->currRound = $round;
    }

    /**
     * {@inheritDoc}
     *
     * @see \System25\T3sports\Table\IMatchProvider::getClubIdsOfRunningMatches()
     */
    public function getClubIdsOfRunningMatches()
    {
        if (null === $this->clubIdsOfRunningMatches) {
            $values = [];

            foreach ($this->getRounds() as $round) {
                foreach ($round as $matchs) {
                    if ($matchs->isRunning()) {
                        $values[] = $matchs->getHome()->getClub()->getUid();
                        $values[] = $matchs->getGuest()->getClub()->getUid();
                    }
                }
            }
            $this->clubIdsOfRunningMatches = $values;
        }

        return $this->clubIdsOfRunningMatches;
    }
}
