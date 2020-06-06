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

tx_rnbase::load('tx_rnbase_util_DB');
tx_rnbase::load('tx_cfcleaguefe_models_match');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');

/**
 * Model für einen Spielplan. Dieser kann für einen oder mehrere Wettbewerbe abgerufen werden.
 *
 * @deprecated use tx_cfcleaguefe_utils_MatchTable
 */
class tx_cfcleaguefe_models_matchtable
{
    public $_saison;

    public $_team;

    public $_competition;

    public $_daysPast;

    public $_daysAhead;

    public $_ticker; // Nur Spiele mit LiveTicker laden

    public $_limit; // Anzahl Spiele limitieren

    public $_dateStart; // bestimmter Starttermin

    public $_dateEnd; // bestimmter Endtermin

    public $_pidList; // bestimmte TYPO3-Seiten

    public $_orderDesc; // Sortierreihenfolge

    public function __construct()
    {
        $this->setOrderDesc(false);
    }

    /**
     * Grenzt den Zeitraum für den Spielplan ein.
     *
     * @param $daysPast int Anzahl Tage in der Vergangenheit
     * @param $daysAhead int Anzahl Tage in der Zukunft
     */
    public function setTimeRange($daysPast = 0, $daysAhead = 0)
    {
        $this->_daysPast = $daysPast;
        $this->_daysAhead = $daysAhead;
    }

    public function setOrderDesc($flag = true)
    {
        $this->_orderDesc = $flag;
    }

    /**
     * Grenzt den Zeitraum für den Spielplan auf genaue Termine ein.
     *
     * @param $daysPast int Anzahl Tage in der Vergangenheit
     * @param $daysAhead int Anzahl Tage in der Zukunft
     */
    public function setDateRange($start_date, $end_date)
    {
        $this->_dateStart = $start_date;
        $this->_dateEnd = $end_date;
    }

    /**
     * Findet nur Spiele von bestimmten Seiten.
     */
    public function setPidList($pidList)
    {
        $this->_pidList = $pidList;
    }

    /**
     * Nur Spiele mit Liveticker: 0-nein / 1-ja.
     */
    public function setLiveTicker($ticker)
    {
        $this->_ticker = $ticker;
    }

    /**
     * Anzahl der Spiele eingrenzen.
     *
     * @param $limit Anzahl der Spiele oder 0 für alle Spiele
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
    }

    /**
     * Uid des Wettbewerbs. Es kann auch ein Array angegeben werden.
     */
    public function setCompetition($uid)
    {
        $this->_competition = $uid;
    }

    /**
     * UID der Saison, es kann auch ein Array angeben werden.
     */
    public function setSaison($uid)
    {
        $this->_saison = $uid;
    }

    /**
     * comma seperated string with team uids.
     *
     * @param string $uid
     */
    public function setTeam($uid)
    {
        $this->_team = trim(implode(',', Tx_Rnbase_Utility_Strings::intExplode(',', $uid)));
    }

    /**
     * Sucht Spiele aus der Datenbank mit den definierten Werten. Die Spiele werden mit den
     * maximal verfügbaren Daten geliefert. Es finden also immer Joins auf andere Tabellen statt.
     *
     * @param $saison IDs der Saisons, die gezeigt werden sollen oder ein leerer String
     * @param $groups IDs der Alterklassen, die gezeigt werden sollen oder ein leerer String
     * @param $competitions IDs der Wettkämpfe, die gezeigt werden sollen oder ein leerer String
     * @param $club ID des Vereins für dessen Team der Spielplan erstellt werden soll oder ein leerer String
     * @param $round ID des Spielrunde oder ein leerer String
     * @param $status Status der Spiele (0-angesetzt, 1-läuft, 2-beendet) als String
     * @param $extended Optional kann das Spiel mit allen verfügbaren Daten geladen werden
     *
     * @return array of tx_cfcleaguefe_models_match
     *
     * @deprecated use tx_cfcleaguefe_util_MatchTable instead
     */
    public function findMatches($saison, $groups, $competitions, $club, $round, $status = '0,1,2', $extended = 0)
    {
        // Das cObj mus in eine Klasse für FE-Datenbankabfragen
        // Diese Klasse müssen wir dann hier übergeben und anstatt tx_cfcleague_db verwenden

        // Wir benötigen einen Wrapper für die Spiele
        // Dieser Wrapper kann die Schnittstelle zur TCA bieten

        $what = tx_cfcleaguefe_models_match::getWhatFull($extended);
        $from = tx_cfcleaguefe_models_match::getFromFull();
        $arr = array();

        $where = '';
        // TODO: Parameter prüfen!!
        if (isset($saison) && strlen($saison) > 0) {
            $where .= 'tx_cfcleague_competition.saison IN ('.$saison.')';
        }

        if (isset($groups) && strlen($groups) > 0) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_competition.agegroup IN ('.$groups.')';
        }

        if (isset($competitions) && strlen($competitions) > 0) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.competition IN ('.$competitions.')';
        }

        if (isset($round) && strlen($round) > 0) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.round = '.$round.'';
        }

        if (isset($club) && is_numeric($club)) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' (t1.club = '.$club.' OR t2.club = '.$club.')';
        }

        if ($this->_team && strlen($this->_team)) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' (home IN ('.$this->_team.') OR guest IN ('.$this->_team.'))';
        }

        if (isset($status)) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.status IN ('.$status.')';
        }

        // Wird der Zeitraum eingegrenzt?
        if (intval($this->_daysPast) || intval($this->_daysAhead)) {
            // Wenn in eine Richtung eingegrenzt wird und in der anderen Richtung kein
            // Wert gesetzt wurde, dann wird dafür das aktuelle Datum verwendet.
            // Auf jeden Fall wird immer in beide Richtungen eingegrenzt

            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $cal = tx_rnbase::makeInstance('tx_rnbase_util_Calendar');
            $cal->clear(CALENDAR_SECOND);
            $cal->clear(CALENDAR_HOUR);
            $cal->clear(CALENDAR_MINUTE);
            $cal->add(CALENDAR_DAY_OF_MONTH, $this->_daysPast * -1);
            $where .= ' tx_cfcleague_games.date >= '.$cal->getTime();

            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $cal = tx_rnbase::makeInstance('tx_rnbase_util_Calendar');
            $cal->clear(CALENDAR_SECOND);
            $cal->clear(CALENDAR_HOUR);
            $cal->clear(CALENDAR_MINUTE);
            $cal->add(CALENDAR_DAY_OF_MONTH, $this->_daysAhead);
            $where .= ' tx_cfcleague_games.date < '.$cal->getTime();
        }

        if (intval($this->_dateStart)) { // bestimmtes Startdatum
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.date >= '.$this->_dateStart;
        }
        if (intval($this->_dateEnd)) { // bestimmtes Enddatum
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.date < '.$this->_dateEnd;
        }
        if (intval($this->_pidList)) { // bestimmte Seiten
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.pid IN ('.$this->_pidList.')';
        }

        if (intval($this->_ticker)) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }
            $where .= ' tx_cfcleague_games.link_ticker > 0 ';
        }
        $arr['where'] = $where;
        $arr['wrapperclass'] = 'tx_cfcleaguefe_models_match';
        $arr['orderby'] = 'tx_cfcleague_games.date '.($this->_orderDesc ? 'DESC' : 'ASC');

        $limit = intval($this->_limit);
        if ($limit) {
            $arr['limit'] = $limit;
        }

        $rows = tx_rnbase_util_DB::doSelect($what, $from, $arr, 0);

        return $rows;

        /*
// Ermittelt alle Spiele eines Teams in einer Saison
SELECT distinct m.uid, m.home, t1.name as home_name, m.guest, t2.name as guest_name
FROM `tx_cfcleague_games` as m
  INNER JOIN tx_cfcleague_competition As c
  INNER JOIN tx_cfcleague_teams As t1
  INNER JOIN tx_cfcleague_teams As t2
  ON m.competition = c.uid
  ON m.home = t1.uid
  ON m.guest = t2.uid
WHERE
c.saison IN (1) AND
( m.home = 1 OR m.guest=1)
        */
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_matchtable.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_matchtable.php'];
}
