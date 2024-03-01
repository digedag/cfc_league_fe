<?php

namespace System25\T3sports\Statistics\Service;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Utility\Logger;
use Sys25\RnBase\Utility\Misc;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\TeamRepository;
use System25\T3sports\Model\Team;
use System25\T3sports\Service\ProfileService;
use System25\T3sports\Statistics\PlayerStatisticsMarker;
use System25\T3sports\Statistics\StatisticsHelper;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2023 Rene Nitzsche (rene@system25.de)
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
 * Service for player statistics.
 *
 * @author Rene Nitzsche
 */
class PlayerStatistics implements StatsServiceInterface
{
    /**
     * Für jeden Spieler wird ein Datenarray erstellt
     * In jedem Datenarray liegt für jede Info ein Zähler,
     * sowie zusätzlich noch eine Referenz auf den Spieler.
     */
    private $playersArr = [];

    private $scopeArr;

    private $teams;

    private $configurations;

    private $statData = [
        'match_count',
        'match_minutes',
        'changed_out',
        'changed_in',
        'card_yellow',
        'card_yellowred',
        'card_red',
        'goals_all',
        'goals_head',
        'goals_own',
        'goals_penalty',
        'goals_home',
        'goals_away',
        'goals_assist',
        'goals_joker',
    ];

    /** @var TeamRepository */
    protected $teamRepo;
    /** @var ProfileService */
    protected $profileSrv;

    public function __construct(?TeamRepository $teamRepo = null, ?ProfileService $profileSrv = null)
    {
        $this->teamRepo = $teamRepo ?: new TeamRepository();
        $this->profileSrv = $profileSrv ?: new ProfileService();
    }

    public function getStatsType()
    {
        return 'player';
    }

    /**
     * This method is called one time before the statistic process starts.
     *
     * @param array $scope
     */
    public function prepare($scope, &$configurations, &$parameters)
    {
        $this->scopeArr = $scope;
        $this->configurations = $configurations;
    }

    public static $total = 0;

    /**
     * Ein einzelnes Spiel auswerten.
     *
     * @param Fixture $match
     * @param int $clubId
     */
    public function handleMatch(Fixture $match, $clubId)
    {
        // Zunächst müssen alle Spieler des Spiels ermittelt werden
        // Jeder Spieler, der am Spiel beteiligt ist, steht in der Aufstellung oder als
        // Wechselspieler im Match.
        // Wir betrachten nur die Spieler des gesetzten Clubs
        $team = $match->getHome();
        if ($this->isObservedTeam($team)) {
            $players = $this->getPlayer($match, true); // All Spieler des Heimteams holen

            if (is_array($players)) {
                foreach ($players as $player) {
                    // Jeden Spieler aktualisieren
                    $this->_countMatch4Player($player, $match, $this->playersArr);
                }
            }
        }
        $team = $match->getGuest();
        if ($this->isObservedTeam($team)) {
            // Nochmal die Spieler des Auswärtsteams
            $players = $this->getPlayer($match, false); // All Spieler des Gastteams holen
            if (is_array($players)) {
                foreach ($players as $player) {
                    // Jeden Spieler aktualisieren
                    $this->_countMatch4Player($player, $match, $this->playersArr);
                }
            }
        }
    }

    /**
     * Entscheidet, ob die Spieler des Teams in die Statistik eingehen.
     *
     * @param Team $team
     *
     * @return bool
     */
    protected function isObservedTeam($team)
    {
        $clubId = $this->scopeArr['CLUB_UIDS'];
        $ret = $team->getProperty('club') == $clubId || !$clubId;
        $groupId = $this->scopeArr['TEAMGROUP_UIDS'];
        $ret = $ret && ($team->getProperty('agegroup') == $groupId || !$groupId);

        return $ret;
    }

    /**
     * Liefert die Spieler eines beteiligten Teams.
     *
     * @param Fixture $match
     * @param bool $home
     *            true, wenn das Heimteam geholt werden soll
     *
     * @return Profile[]
     */
    protected function getPlayer($match, $home)
    {
        $playerUids = $home ? $match->getPlayersHome(true) : $match->getPlayersGuest(true);
        $players = $this->profileSrv->loadProfiles($playerUids);

        // Fehlerhafte Spieler entfernen
        foreach ($players as $key => $player) {
            if (!is_object($player)) {
                Logger::warn('Player with UID '.$key.' not found. Probably the profile was deleted, but still has references.', 'cfc_league_fe');
                unset($players[$key]);
            }
        }
        reset($players);

        return $players;
    }

    public function getResult()
    {
        // Die Spieler vorher noch sortieren
        // Is there exactly one team?
        $teams = $this->getTeams($this->getScopeArray());

        if (1 == count($teams) && 1 == intval($this->configurations->get('statistics.player.profileSortOrder'))) {
            // sort by team members
            $this->playersArr = $this->_sortPlayer($this->playersArr, $teams[0]);
        } else {
            // Die Spieler alphabetisch sortieren
            usort($this->playersArr, function ($a, $b) {
                return $this->playerStatsCmpPlayer($a, $b);
            });
        }

        return $this->playersArr;
    }

    public function getMarker(ConfigurationInterface $configurations)
    {
        return tx_rnbase::makeInstance(PlayerStatisticsMarker::class);
    }

    /**
     * Kindklassen Zugriff auf das Ergebnisarray bieten.
     *
     * @return array
     */
    protected function getPlayersArray()
    {
        return $this->playersArr;
    }

    /**
     * Find teams that were handled by this scope.
     *
     * @param array $scopeArr
     *
     * @return Team[]
     */
    protected function getTeams($scopeArr)
    {
        $fields = [];
        $options = [];
        $fields['TEAM.CLUB'][OP_IN_INT] = $scopeArr['CLUB_UIDS'];
        $fields['COMPETITION.UID'][OP_IN_INT] = $scopeArr['COMP_UIDS'];
        $teams = $this->teamRepo->search($fields, $options);

        return $teams->toArray();
    }

    /**
     * Kindklassen Zugriff auf das Scopearray bieten.
     *
     * @return array
     */
    protected function getScopeArray()
    {
        return $this->scopeArr;
    }

    /**
     * Prüft, ob der Spieler im aktuellen Spiel beteiligt war.
     * Folgende Daten
     * werden aktualisiert:<pre>
     * - MATCH_COUNT Anzahl der Spiele
     * - MATCH_MINUTES Spielminuten
     * - CHANGED_OUT Auswechslungen
     * - CHANGED_IN Einwechslungen
     * - CARD_YELLOWRED Anzahl Gelb/rote Karten
     * - CARD_YELLOW Anzahl Gelb Karten
     * - CARD_RED Anzahl rote Karten
     * - GOAL_ALL Gesamtzahl der Tore des Spieler
     * </pre>.
     *
     * @param Profile $player Spieler, der gezählt werden soll
     * @param Fixture $match Spiel, das ausgewertet wird
     * @param array $playersArr Datenarray, welches die ermittelten Daten aufnimmt
     */
    protected function _countMatch4Player($player, $match, &$playersArr)
    {
        $ignorePlayer = 1;
        $isYellowRed = false;
        $playerData = &$this->_getPlayerData($playersArr, $player);

        // In welchem Team steht der Spieler?
        $team = $match->getTeam4Player($player->getUid());

        if (1 == $team) {
            $startPlayer = $match->getPlayersHome();
        } elseif (2 == $team) {
            $startPlayer = $match->getPlayersGuest();
        } else {
            // Wenn der Spieler nicht im Spiel vorkommt, können wir abbrechen
            return;
        }
        $startPlayer = $this->profileSrv->loadProfiles($startPlayer);
        $startPlayers = [];
        foreach ($startPlayer as $p) {
            $startPlayers[$p->getUid()] = $p;
        }

        // Steht der Spieler in der Startelf
        if (array_key_exists($player->getUid(), $startPlayers)) {
            $playerData['match_count'] = intval($playerData['match_count']) + 1;

            // Wurde der Spieler ausgewechselt?
            $min = StatisticsHelper::isChangedOut($player, $match);
            if ($min > 0) {
                $playerData['changed_out'] = intval($playerData['changed_out']) + 1;
            }

            // Nicht ausgewechselt, aber wurde der Spieler vom Platz gestellt?
            if (0 == intval($min)) {
                $min = StatisticsHelper::isCardYellowRed($player, $match);
                if (0 != $min) {
                    $playerData['card_yellowred'] = intval($playerData['card_yellowred']) + 1;
                    $isYellowRed = true;
                }
            }
            // Keine gelbrote, aber vielleicht rot?
            if (0 == intval($min)) {
                $min = StatisticsHelper::isCardRed($player, $match);
                if (0 != $min) {
                    $playerData['card_red'] = intval($playerData['card_red']) + 1;
                }
            }

            // Bei Wechsel in Nachspielzeit zählt die 89 min
            $min = ($min > 0) ? ($min > 89) ? 89 : $min : 90;

            $playerData['match_minutes'] = intval($playerData['match_minutes']) + $min;
            $ignorePlayer = 0;
        }

        if ($ignorePlayer) {
            // Hier betrachten wir die eingewechselten Spieler
            $min = StatisticsHelper::isChangedIn($player, $match);
            if ($min > 0) {
                $playerData['match_count'] = intval($playerData['match_count']) + 1;
                $playerData['changed_in'] = intval($playerData['changed_in']) + 1;

                // Wurde der Spieler wieder ausgewechselt?
                $min2 = StatisticsHelper::isChangedOut($player, $match);
                if ($min2 > 0) {
                    $playerData['changed_out'] = intval($playerData['changed_out']) + 1;
                }

                // Wurde der Spieler vom Platz gestellt?
                if (0 == intval($min2)) {
                    $min2 = StatisticsHelper::isCardYellowRed($player, $match);
                    if (0 != $min2) {
                        $playerData['card_yellowred'] = intval($playerData['card_yellowred']) + 1;
                        $isYellowRed = true;
                    }
                }
                if (0 == intval($min2)) {
                    $min2 = StatisticsHelper::isCardRed($player, $match);
                    if (0 != $min2) {
                        $playerData['card_red'] = intval($playerData['card_red']) + 1;
                    }
                }

                $min2 = ($min2 > 0) ? ($min2 > 89) ? 89 : $min2 : 90;

                $min = ($min > 89) ? 89 : $min; // Es geht nur bis zur 90. Minute
                $min2 = $min2 <= $min ? $min + 1 : $min2;

                $playerData['match_minutes'] = intval($playerData['match_minutes']) + ($min2 - $min);
                $ignorePlayer = 0;
            }
        }
        if ($ignorePlayer) {
            // Bug 1864066 - Spieler, die nicht im Spiel waren, können trotzdem rote Karten bekommen
            if (0 != StatisticsHelper::isCardRed($player, $match)) {
                $playerData['card_red'] = intval($playerData['card_red']) + 1;
            }
        }
        if (!$ignorePlayer) {
            // Der Spieler war im Spiel. Wir suchen die restlichen Daten
            // Bug 1864071 - Gelbe Karten nur zählen, wenn nicht gelbrot
            if (!$isYellowRed) {
                $min = StatisticsHelper::isCardYellow($player, $match);
                if (0 != $min) {
                    $playerData['card_yellow'] = intval($playerData['card_yellow']) + 1;
                }
            }
            $this->_countGoals(0, 'goals_all', $player, $match, $playerData);
            $this->_countGoals(11, 'goals_head', $player, $match, $playerData);
            $this->_countGoals(12, 'goals_penalty', $player, $match, $playerData);
            $this->_countNote(30, 'goals_own', $player, $match, $playerData);
            $this->_countNote(31, 'goals_assist', $player, $match, $playerData);
        }
    }

    /**
     * Zählt einen bestimmten Note-Typ für einen Spieler.
     *
     * @param int $type 0 oder MatchNote-Typ
     * @param string $key der konkrete Statistiktyp, der aktualisiert werden soll. Dieser muss zum Typ passen.
     * @param Profile $player Referenz auf den Spieler
     * @param Fixture $match Referenz auf das Spiel
     * @param array $playerData Referenz auf die Statistikdaten des Spielers
     */
    protected function _countNote($type, $key, &$player, &$match, &$playerData)
    {
        // Die passenden Notes des Spielers ermitteln
        $notes = StatisticsHelper::isNote($type, $player, $match);
        if (is_array($notes)) {
            // Die Anzahl der Notes im Spiel für den Spieler hinzufügen
            $playerData[$key] = intval($playerData[$key]) + count($notes);
        }
        $playerData[$key.'_per_match'] = intval($playerData[$key]) / intval($playerData['match_count']);
    }

    /**
     * Zählt die Tore für einen Spieler.
     * Der Typ ist entweder 0 für alle Tore oder
     * entspricht dem MatchNote-Typ für ein bestimmtes Tor. Wenn der Typ 0 ist, dann werden
     * auch auch die Werte für GOALS_HOME, GOALS_AWAY und GOALS_JOKER aktualisiert.
     *
     * @param int $type 0 oder MatchNote-Typ
     * @param string $key der konkrete Statistiktyp, der aktualisiert werden soll. Dieser muss zum Typ passen.
     * @param Profile $player Referenz auf den Spieler
     * @param Fixture $match Referenz auf das Spiel
     * @param array $playerData Referenz auf die Statistikdaten des Spielers
     */
    protected function _countGoals($type, $key, $player, $match, &$playerData)
    {
        // Die Tore des Spielers ermitteln
        $notes = StatisticsHelper::isGoal($type, $player, $match);
        if (is_array($notes)) {
            // Die Anzahl der Tore im Spiel für den Spieler hinzufügen
            $playerData[$key] = intval($playerData[$key]) + count($notes);
            if (0 == $type) {
                // Hier zählen wir zusätzlich weitere Daten
                $firstNote = $notes[0];
                if ($firstNote->isHome()) {
                    $playerData['goals_home'] = intval($playerData['goals_home']) + count($notes);
                } else {
                    $playerData['goals_away'] = intval($playerData['goals_away']) + count($notes);
                }
                if (StatisticsHelper::isChangedIn($player, $match)) {
                    $playerData['goals_joker'] = intval($playerData['goals_joker']) + count($notes);
                }
            }
        }
        // Tore pro Spiel berechnen
        $playerData[$key.'_per_match'] = intval($playerData[$key]) / intval($playerData['match_count']);
        // Spielminuten pro Tor (hier DIV/0 verhindern)
        if (intval($playerData[$key])) {
            $playerData[$key.'_after_minutes'] = intval($playerData['match_minutes']) / intval($playerData[$key]);
        }
    }

    /**
     * Liefert das Datenarray für einen Spieler als Referenz.
     * Sollte es noch nicht
     * vorhanden sein, dann wird es angelegt. Außerdem wird jeder Statistikeintrag mit 0 initialisiert.
     * Die ist notwendig, damit später alle Marker im HTML-Template ersetzt werden.
     */
    public function &_getPlayerData(&$players, $player)
    {
        if (!array_key_exists($player->uid, $players)) {
            $players[$player->uid] = [];
            // Alle Daten initialisieren
            foreach ($this->statData as $col) {
                $players[$player->uid][$col] = 0;
            }
            $players[$player->uid]['player'] = $player;
        }

        return $players[$player->uid];
    }

    /**
     * Sortiert die Spieler entsprechend der Reihenfolge im Team.
     *
     * @param Profile[] $players
     * @param Team $team
     */
    public function _sortPlayer($players, $team)
    {
        $ret = [];
        if (strlen(trim($team->getProperty('players'))) > 0) {
            if (count($players)) {
                // Jetzt die Spieler in die richtige Reihenfolge bringen
                $uids = Strings::intExplode(',', $team->getProperty('players'));
                $uids = array_flip($uids);
                foreach ($players as $record) {
                    // In $record liegt der Statistikdatensatz des Spielers
                    $player = $record['player'];
                    $ret[$uids[$player->getUid()]] = $record;
                }
            }
        } else {
            // Wenn keine Spieler im Team geladen sind, dann wird das Array unverändert zurückgegeben
            return $players;
        }

        return $ret;
    }

    /**
     * Sortierfunktion um die korrekte Reihenfolge nach Namen zu ermittlen.
     */
    public function playerStatsCmpPlayer($a, $b)
    {
        $player1 = $a['player'];
        $player2 = $b['player'];

        return strcmp(
            Misc::removeUmlauts(strtoupper($player1->getName(1))),
            Misc::removeUmlauts(strtoupper($player2->getName(1)))
        );
    }
}
