<?php

use Sys25\RnBase\Database\Connection;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Match;

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

/**
 * Model für einen Spiel.
 * Liefert Zugriff auf die Daten eines Spiels.
 */
class tx_cfcleaguefe_models_match extends Match
{
    private static $instances = [];

    public $_report;

    /**
     * Returns the match report.
     *
     * @return tx_cfcleaguefe_models_matchreport
     * FIXME
     */
    public function getMatchReport()
    {
        return $this->_report;
    }

    /**
     * Set the instance of matchreport.
     *
     * @param tx_cfcleaguefe_models_matchreport $report
     */
    public function setMatchReport($report)
    {
        $this->_report = $report;
    }

    /**
     * Returns the state as string.
     *
     * @return string
     * FIXME
     */
    public function getStateName()
    {
        $items = $GLOBALS['TCA']['tx_cfcleague_games']['columns']['status']['config']['items'];
        foreach ($items as $item) {
            if ($item[1] == $this->getProperty('status')) {
                return $GLOBALS['LANG']->sL($item[0]);
            }
        }

        return '';
    }

    /**
     * Fügt diesem Match eine neue Note hinzu.
     * Die Notes werden mit diesem Spiel verlinkt.
     * FIXME
     */
    public function addMatchNote(&$note)
    {
        if (!isset($this->matchNotes)) {
            $this->matchNotes = [];
        } // Neues TickerArray erstellen
        $note->setMatch($this);
        $this->matchNotes[] = $note;
        // Zusätzlich die Notes nach ihrem Typ sortieren
        $this->matchNoteTypes[(int) $note->getProperty('type')][] = $note;
    }

    public function getMatchNotesByType($type)
    {
        if (is_array($type)) {
            $ret = [];
            for ($i = 0, $size = count($type); $i < $size; ++$i) {
                $notes = $this->matchNoteTypes[intval($type[$i])];
                if (is_array($notes)) {
                    $ret = array_merge($ret, $notes);
                }
            }

            return $ret;
        } else {
            return $this->matchNoteTypes[intval($type)];
        }
    }


    /**
     * Liefert den Heimtrainer als Datenobjekt.
     */
    public function getCoachHome()
    {
        $ret = null;
        if ($this->getProperty('coach_home')) {
            $this->_resolveProfiles();
            // Wir suchen jetzt den Trainer
            $ret = $this->_profiles[$this->getProperty('coach_home')];
        }

        return $ret;
    }

    /**
     * Liefert den Gasttrainer als Datenobjekt.
     */
    public function getCoachGuest()
    {
        $ret = null;
        if ($this->getProperty('coach_guest')) {
            $this->_resolveProfiles();
            // Wir suchen jetzt den Trainer
            $ret = $this->_profiles[$this->getProperty('coach_guest')];
        }

        return $ret;
    }

    /**
     * Liefert die Schiedsrichterassistenten als Datenobjekte in einem Array.
     */
    public function getAssists()
    {
        return $this->_getProfiles($this->getProperty('assists'));
    }

    protected $sets = null;

    /**
     * Return sets if available.
     *
     * @return array[tx_cfcleague_models_Set]
     */
    public function getSets()
    {
        if (!is_array($this->sets)) {
            $this->sets = tx_cfcleague_models_Set::buildFromString($this->getProperty('sets'));
            $this->sets = $this->sets ? $this->sets : [];
        }

        return $this->sets;
    }

    /**
     * Liefert die Spieler des Heimteams der Startelf als Datenobjekte in einem Array.
     *
     * @param bool $all wenn > 0 werden auch die Ersatzspieler mit geliefert
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    public function getPlayersHome($all = 0)
    {
        $ids = $this->getProperty('players_home');
        if ($all > 0 && strlen($this->getProperty('substitutes_home')) > 0) {
            // Auch Ersatzspieler anhängen
            if (strlen($ids) > 0) {
                $ids = $ids.','.$this->getProperty('substitutes_home');
            }
        }

        return $this->_getProfiles($ids);
    }

    /**
     * Liefert die Spieler des Gastteams der Startelf als Datenobjekte in einem Array.
     *
     * @param int $all wenn > 0 werden auch die Ersatzspieler mit geliefert
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    public function getPlayersGuest($all = 0)
    {
        $ids = $this->getProperty('players_guest');
        if ($all > 0 && strlen($this->getProperty('substitutes_guest')) > 0) {
            // Auch Ersatzspieler anhängen
            if (strlen($ids) > 0) {
                $ids = $ids.','.$this->getProperty('substitutes_guest');
            }
        }

        return $this->_getProfiles($ids);
    }

    /**
     * Liefert die Ersatzspieler des Heimteams als Datenobjekte in einem Array.
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    public function getSubstitutesHome()
    {
        return $this->_getProfiles($this->getProperty('substitutes_home'));
    }

    /**
     * Liefert die Ersatzspieler des Gastteams als Datenobjekte in einem Array.
     */
    public function getSubstitutesGuest()
    {
        return $this->_getProfiles($this->getProperty('substitutes_guest'));
    }

    /**
     * Ermittelt zu welchem Team die SpielerID gehört.
     *
     * @param $playerUid int
     *            UID eines Spielers
     *
     * @return int 1 - Heimteam, 2- Gastteam, 0 - unbekannt
     */
    public function getTeam4Player($playerUid)
    {
        $playerUid = intval($playerUid);
        if (!$playerUid) {
            return 0;
        } // Keine ID vorhanden
        $uids = [];
        if ($this->getProperty('players_home')) {
            $uids[] = $this->getProperty('players_home');
        }
        if ($this->getProperty('substitutes_home')) {
            $uids[] = $this->getProperty('substitutes_home');
        }
        $uids = implode($uids, ',');
        $uids = Strings::intExplode(',', $uids);
        if (in_array($playerUid, $uids)) {
            return 1;
        }

        $uids = [];
        if ($this->getProperty('players_guest')) {
            $uids[] = $this->getProperty('players_guest');
        }
        if ($this->getProperty('substitutes_guest')) {
            $uids[] = $this->getProperty('substitutes_guest');
        }
        $uids = implode($uids, ',');
        $uids = Strings::intExplode(',', $uids);
        if (in_array($playerUid, $uids)) {
            return 2;
        }

        return 0;
    }

    /**
     * Liefert die Profiles des UID-Strings als Array.
     * Key ist die UID, Value das Profile.
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    public function _getProfiles($uidStr)
    {
        $ret = [];
        if ($uidStr) {
            $this->_resolveProfiles();

            // *********
            // INFO: Unter PHP5 ist es zu einem Problem bei der Behandlung der Referenzen gekommen.
            // Wenn direkt mit dem Array $this->_profiles gearbeitet wird, dann wird bei der Erstellung
            // der MatchNotes für die Auswechslungen die Instanz des ausgewechselten Spielers gelöscht.
            // Durch die Verwendung des zweiten Arrays wird das verhindert. Ursache ist mir aber unbekannt...

            // $ret = array();
            $uids = Strings::intExplode(',', $uidStr);
            foreach ($uids as $uid) {
                $ret[$uid] = &$this->_profiles[$uid];
            }
        }

        return $ret;
    }

    /**
     * Erstellt für alle Personen des Spiels die passenden Objekte.
     * Dies wird aber nur gemacht
     * wenn die entsprechenden IDs noch nicht geladen sind.
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    public function _resolveProfiles()
    {
        if (isset($this->_profiles)) {
            return;
        } // Die Profile sind schon geladen
        // Wir sammeln zunächst die UIDs zusammen
        $uids = [];
        if ($this->getProperty('referee')) {
            $uids[] = $this->getProperty('referee');
        }
        if ($this->getProperty('assists')) {
            $uids[] = $this->getProperty('assists');
        }
        if ($this->getProperty('coach_home')) {
            $uids[] = $this->getProperty('coach_home');
        }
        if ($this->getProperty('coach_guest')) {
            $uids[] = $this->getProperty('coach_guest');
        }
        if ($this->getProperty('players_home')) {
            $uids[] = $this->getProperty('players_home');
        }
        if ($this->getProperty('players_guest')) {
            $uids[] = $this->getProperty('players_guest');
        }
        if ($this->getProperty('substitutes_home')) {
            $uids[] = $this->getProperty('substitutes_home');
        }
        if ($this->getProperty('substitutes_guest')) {
            $uids[] = $this->getProperty('substitutes_guest');
        }

        $uids = implode($uids, ',');

        $what = '*';
        $from = 'tx_cfcleague_profiles';
        $options['where'] = 'uid IN ('.$uids.')';
        $options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';

        $rows = Connection::getInstance()->doSelect($what, $from, $options);

        $this->_profiles = [];
        // Wir erstellen jetzt ein Array dessen Key die UID des Profiles ist
        foreach ($rows as $profile) {
            $this->_profiles[$profile->getUid()] = $profile;
        }
    }

    /**
     * Liefert das Team als Objekt.
     */
    public function _getTeam($uid)
    {
        if (!$uid) {
            throw new Exception('Invalid match with uid '.$this->getUid().': At least one team is not set.');
        }
        // TODO: Umstellen auf tx_cfcleague_models_team
        // $team = tx_cfcleague_util_ServiceRegistry::getTeamService()->getTeam($uid);
        $team = tx_cfcleaguefe_models_team::getTeamInstance($uid);

        return $team;
    }

    /**
     * Liefert den Spieltermin.
     * Wenn ein Formatter übergeben wird, dann wird dieser formatiert,
     * ansonsten wird der eigentliche Wert geliefert.
     */
    public function getDate($formatter = 0, $configKey = 'match.date.')
    {
        if ($formatter) {
            return $formatter->wrap($this->getProperty('date'), $configKey);
        } else {
            return $this->getProperty('date');
        }
    }

    /**
     * Liefert das Stadion.
     *
     * @return tx_cfcleague_models_Stadium Arena as object or false
     */
    public function getArena()
    {
        if (!intval($this->getProperty('arena'))) {
            return false;
        }

        return tx_cfcleague_models_Stadium::getStadiumInstance($this->getProperty('arena'));
    }

    public function getStadium($formatter = 0, $configKey = 'match.stadium.')
    {
        if ($formatter) {
            return $formatter->wrap($this->getProperty('stadium'), $configKey);
        } else {
            return $this->getProperty('stadium');
        }
    }

    /**
     * Returns the visitors of match.
     *
     * @return int
     */
    public function getVisitors()
    {
        return (int) $this->getProperty('visitors');
    }

    /**
     * Liefert den Autor des Spielberichts.
     */
    public function getReportAuthor($formatter = 0, $configKey = 'match.author.')
    {
        if ($formatter) {
            return $formatter->wrap($this->getProperty('game_report_author'), $configKey);
        } else {
            return $this->getProperty('game_report_author');
        }
    }

    /**
     * Liefert den Spielbericht.
     */
    public function getReport($formatter = 0, $configKey = 'match.report.')
    {
        if ($formatter) {
            return $formatter->wrap($this->getProperty('game_report'), $configKey);
        } else {
            return $this->getProperty('game_report');
        }
    }

    /**
     * Liefert den TOTO-Wert des Spiels.
     * Als 0 für ein Unentschieden, 1 für einen Heim-
     * und 2 für einen Auswärstsieg.
     *
     * @param string $matchPart
     *            The matchpart is 1,2,3...,et,ap,last
     */
    public function getToto($matchPart = '')
    {
        $goalsHome = $this->getGoalsHome($matchPart);
        $goalsGuest = $this->getGoalsGuest($matchPart);

        $goalsDiff = $goalsHome - $goalsGuest;

        if (0 == $goalsDiff) {
            return 0;
        }

        return ($goalsDiff < 0) ? 2 : 1;
    }

    /**
     * Returns the match round.
     *
     * @return int
     */
    public function getRound()
    {
        return (int) $this->getProperty('round');
    }

    /**
     * Returns the competition.
     *
     * @return tx_cfcleague_models_Competition
     */
    public function getCompetition()
    {
        if (!$this->competition) {
            $this->competition = tx_cfcleague_models_Competition::getCompetitionInstance($this->getProperty('competition'));
            if (!is_object($this->competition)) {
                tx_rnbase_util_Logger::warn('Match with UID '.$this->getUid().' has no valid competition!', 't3sports', [
                    'match' => $this->getProperty(),
                ]);

                throw new Exception('Match with UID '.$this->getUid().' has no valid competition!');
            }
        }

        return $this->competition;
    }

    public function setCompetition($competition)
    {
        $this->competition = $competition;
    }

    /**
     * Liefert keine Daten zum Wettbewerb.
     */
    public function getWhatMedium()
    {
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
     * Returns the from-Clause for a medium data request.
     * Dabei wird eine Query über folgende Tabellen
     * erstellt: <ul>
     * <li>tx_cfcleague_games
     * <li>tx_cfcleague_teams
     * </ul>.
     *
     * @return string ein Array mit From-Clause und 'tx_cfcleague_games'
     */
    public function getFromMedium()
    {
        return [
            '
       tx_cfcleague_games
         INNER JOIN tx_cfcleague_teams As t1 ON tx_cfcleague_games.home = t1.uid
         INNER JOIN tx_cfcleague_teams As t2 ON tx_cfcleague_games.guest = t2.uid',
            'tx_cfcleague_games',
        ];
    }

    /**
     * Static Methode, die einen Datenbank-String mit allen relevanten Felder eines Spiels liefert.
     * Dies sind alle Felder der Tabellen games und teams. Für die Team-Tabelle werden die Aliase
     * t1 und t1 verwendet.
     *
     * @param bool $extended Optional kann auch der Spielbericht mit geladen werden
     */
    public function getWhatFull($extended = 0)
    {
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
             '.($extended ? '
                , game_report, game_report_author, tx_cfcleague_games.t3images, referee, assists, system_home, system_guest
                , players_home, players_guest, substitutes_home, substitutes_guest
                , coach_home, coach_guest' : '');
    }

    /**
     * Returns the from-Clause for a full data request.
     * Dabei wird eine Query über folgende Tabellen
     * erstellt: <ul>
     * <li>tx_cfcleague_games
     * <li>tx_cfcleague_competition
     * <li>tx_cfcleague_teams
     * </ul>.
     *
     * @return [] ein Array mit From-Clause und 'tx_cfcleague_games'
     */
    public function getFromFull()
    {
        return [
            '
       tx_cfcleague_games
         INNER JOIN tx_cfcleague_competition ON tx_cfcleague_games.competition = tx_cfcleague_competition.uid
         INNER JOIN tx_cfcleague_teams As t1 ON tx_cfcleague_games.home = t1.uid
         INNER JOIN tx_cfcleague_teams As t2 ON tx_cfcleague_games.guest = t2.uid',
            'tx_cfcleague_games',
        ];

        /*
         * Funktioniert bei MySQL 5 nicht mehr:
         * return Array( '
         * tx_cfcleague_games
         * INNER JOIN tx_cfcleague_competition
         * INNER JOIN tx_cfcleague_teams As t1
         * INNER JOIN tx_cfcleague_teams As t2
         * ON tx_cfcleague_games.competition = tx_cfcleague_competition.uid
         * ON tx_cfcleague_games.home = t1.uid
         * ON tx_cfcleague_games.guest = t2.uid', 'tx_cfcleague_games');
         */
    }

    /**
     * Liefert die Instance mit der übergebenen UID.
     * Die Daten werden gecached, so daß
     * bei zwei Anfragen für die selbe UID nur ein DB Zugriff erfolgt.
     *
     * @param int $uid
     *
     * @return tx_cfcleaguefe_models_match
     */
    public static function getMatchInstance($uid)
    {
        $uid = (int) $uid;
        if (!$uid) {
            throw new Exception('Invalid uid for match');
        }

        if (!is_object(self::$instances[$uid])) {
            self::$instances[$uid] = new self($uid);
        }

        return self::$instances[$uid];
    }

    public static function addInstance(&$match)
    {
        self::$instances[$match->getUid()] = $match;
    }
}
