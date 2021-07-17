<?php
use Sys25\RnBase\Utility\Strings;

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
 * Model für einen Spielbericht.
 * Über diese Klasse können Informationen zu einem Spiel abgerufen werden.
 */
class tx_cfcleaguefe_models_matchreport
{
    protected $match;

    protected $_configurations;

    protected $_formatter;

    protected $_tickerArr;

    protected $initialized = false;

    // enthält alle Tickermeldungen

    /**
     * Konstruktor
     * Im Gegensatz zu anderen Modelklassen, holt sich diese Klasse den notwendigen Formatter
     * direkt aus der Configuration.
     *
     * @param int $matchId UID eines Spiels
     */
    public function __construct($matchId, $configurations)
    {
        // Laden des Spiels
        $this->match = self::_loadMatch($matchId);
        $this->match->setMatchReport($this);
        $this->_configurations = $configurations;

        $this->_formatter = $configurations->getFormatter();
    }

    /**
     * Returns the match instance.
     *
     * @return tx_cfcleaguefe_models_match
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * Liefert die Tickermeldungen der Strafen des Heimteams (außer Elfmeter).
     */
    public function getPenaltiesHome()
    {
        $conf = $this->_configurations->get('matchreport.penalties.');
        // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isPenalty() && $ticker->isHome()) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, 'matchreport.penalties.');
    }

    public function getPenaltiesGuest()
    {
        $conf = $this->_configurations->get('matchreport.penalties.');
        // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isPenalty() && $ticker->isGuest()) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, 'matchreport.penalties.');
    }

    /**
     * Liefert die Tickermeldungen der Spielerwechsel des Heimteams.
     */
    public function getChangesHome()
    {
        $conf = $this->_configurations->get('matchreport.changes.');
        // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isChange() && $ticker->isHome()) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, 'matchreport.changes.');
    }

    /**
     * Liefert die Tickermeldungen der Spielerwechsel des Heimteams.
     */
    public function getChangesGuest()
    {
        $conf = $this->_configurations->get('report.changes.');
        // Aus dem gesamten Ticker suchen wir die Wechselmeldungen heraus und formatieren sie
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isChange() && $ticker->isGuest()) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, 'matchreport.changes.');
    }

    /**
     * Liefert die Tickermeldunge der Heimtorschützen.
     *
     * @deprecated to be deleted
     */
    public function getScorerHome()
    {
        $conf = $this->_configurations->get('matchreport.scorer.');
        // Aus dem gesamten Ticker suchen wir die Tormeldungen heraus und formatieren sie
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isGoalHome()) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, 'matchreport.scorer.');
    }

    /**
     * Returns a list of tickers defined by Typoscript.
     *
     * @param string $confId
     */
    public function getTickerList($confId)
    {
        $conf = $this->_configurations->get($confId);
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isVisible($conf)) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, $confId);
    }

    /**
     * Liefert die Tickermeldunge der Gasttorschützen.
     *
     * @deprecated to be deleted
     */
    public function getScorerGuest()
    {
        $conf = $this->_configurations->get('matchreport.scorer.');
        // Aus dem gesamten Ticker suchen wir die Tormeldungen heraus und formatieren sie
        $tickers = [];
        $tickerArr = $this->_getMatchTicker($conf['cron']);
        foreach ($tickerArr as $ticker) {
            if ($ticker->isGoalGuest()) {
                $tickers[] = $ticker;
            }
        }

        return $this->_wrapTickers($tickers, 'matchreport.scorer.');
    }

    /**
     * Liefert alle vorhandenen Ticker als Array
     * Die Tickermeldungen sind nach dem Aufruf schon für die FE Ausgabe vorbereitet.
     * Spielerwechsel
     * sind z.B. schon zusammengelegt und Spielstände berechnet.
     *
     * @param $cron chronologischer
     *            Reihenfolge: "0" - 90 bis 0, "1" - 0 bis 90
     */
    protected function _getMatchTicker($cron = 0)
    {
        $this->_initMatchTicker();
        $ret = (1 != $cron) ? array_reverse($this->_tickerArr) : $this->_tickerArr;

        return $ret;
    }

    /**
     * Liefert alle vorhandenen Tickernews als Array
     * Die Tickermeldungen sind nach dem Aufruf schon für die FE Ausgabe vorbereitet.
     * Spielerwechsel
     * sind z.B. schon zusammengelegt und Spielstände berechnet.
     *
     * @param $cron chronologischer
     *            Reihenfolge: "0" - 90 bis 0, "1" - 0 bis 90
     * @param $all wenn
     *            nicht 0 werden alle Meldungen geliefert, sonst entsprechend der Konfig
     */
    public function getMatchTicker()
    {
        // Man kann einstellen welche Tickernachrichten gezeigt werden
        // z.B. soll evt. nicht jeder Eckball im Ticker erscheinen und ist nur für die Statistik interessant
        $tickers = [];
        $tickerArr = $this->_getMatchTicker();
        if ($this->_configurations->get('tickerTypes')) {
            foreach ($tickerArr as $ticker) {
                if (!(Tx_Rnbase_Utility_T3General::inList($this->_configurations->get('tickerTypes'), $ticker->getType()))) {
                    $tickers[] = $ticker;
                }
            }
        } else {
            $tickers = $tickerArr;
        }

        return $tickers;
    }

    /**
     * Liefert den Namen des Schiedsrichters.
     */
    public function getRefereeName($confId = 'matchreport.referee.')
    {
        return $this->_getNames2($this->match->getReferee(), $confId);
    }

    /**
     * Liefert die Namen der Linienrichters.
     */
    public function getAssistNames($confId = 'matchreport.assists.')
    {
        return $this->_getNames2($this->match->getAssists(), $confId);
    }

    /**
     * Liefert den Namen des Heimtrainers.
     */
    public function getCoachNameHome($confId = 'matchreport.coach.')
    {
        return $this->_getNames2($this->match->getCoachHome(), $confId);
    }

    /**
     * Liefert den Namen des Gasttrainers.
     */
    public function getCoachNameGuest($confId = 'matchreport.coach.')
    {
        return $this->_getNames2($this->match->getCoachGuest(), $confId);
    }

    /**
     * Liefert die Startaufstellung des Heimteams.
     *
     * @deprecated
     */
    public function getPlayerNamesHome()
    {
        return $this->_getLineUp($this->match->getPlayersHome(), $this->match->getProperty('system_home'), 'matchreport.players.');
    }

    /**
     * Liefert den Namen der Spieler in der Startaufstellung des Heimteams.
     *
     * @deprecated
     */
    public function getPlayerNamesGuest()
    {
        return $this->getLineupGuest();
    }

    /**
     * Build the line_up string for home team.
     *
     * @param string $confId
     *
     * @return string
     */
    public function getLineupHome($confId = 'matchreport.players.')
    {
        return $this->_getLineUp($this->match->getPlayersHome(), $this->match->getProperty('system_home'), $confId);
    }

    /**
     * Build the line_up string for home team.
     *
     * @param string $confId
     *
     * @return string
     */
    public function getLineupGuest($confId = 'matchreport.players.')
    {
        return $this->_getLineUp($this->match->getPlayersGuest(), $this->match->getProperty('system_guest'), $confId);
    }

    /**
     * Liefert den Namen der Spieler in der Reservespieler des Heimteams.
     *
     * @param string $confId
     *            TS-Config
     */
    public function getSubstituteNamesHome($confId = 'matchreport.substitutes.')
    {
        return $this->_getNames2($this->match->getSubstitutesHome(), $confId);
    }

    /**
     * Liefert den Namen der Spieler in der Reservespieler des Gastteams.
     */
    public function getSubstituteNamesGuest($confId = 'matchreport.substitutes.')
    {
        return $this->_getNames2($this->match->getSubstitutesGuest(), $confId);
    }

    /**
     * Liefert das Logo der Heimmannschaft als komplettes Image-Tag.
     */
    public function getLogoHome()
    {
        // Wir suchen den Verein der Heimmannschaft
        return $this->_getLogo($this->match->getHome());
    }

    /**
     * Liefert das Logo der Gastmannschaft als komplettes Image-Tag.
     */
    public function getLogoGuest()
    {
        return $this->_getLogo($this->match->getGuest());
    }

    /**
     * Liefert den Namen des Gastgebers.
     */
    public function getTeamNameHome()
    {
        return $this->match->getHome()->getColumnWrapped($this->_formatter, 'name', 'matchreport.teamHome.');
    }

    /**
     * Liefert den Namen des Gastes.
     */
    public function getTeamNameGuest()
    {
        return $this->match->getGuest()->getColumnWrapped($this->_formatter, 'name', 'matchreport.teamGuest.');
    }

    /**
     * Liefert den Spieltermin als String.
     */
    public function getDate()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'date', 'matchreport.match.');
    }

    /**
     * Liefert das Stadion.
     */
    public function getStadium()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'stadium', 'matchreport.match.');
    }

    /**
     * Liefert den Autor des Spielberichts.
     */
    public function getReportAuthor()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'game_report_author', 'matchreport.match.');
    }

    /**
     * Liefert den Spielberichts.
     */
    public function getReport()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'game_report', 'matchreport.match.');
    }

    /**
     * Liefert den Namen des Wettbewerbs.
     */
    public function getCompetitionName()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'competition_name', 'matchreport.match.');
    }

    /**
     * Liefert den Namen der Spielrunde.
     */
    public function getRoundName()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'round_name', 'matchreport.match.');
    }

    public function getVisitors()
    {
        return $this->match->getColumnWrapped($this->_formatter, 'visitors', 'matchreport.match.');
    }

    /**
     * Initialisiert die MatchNotes.
     * Diese werden auch den Spieler zugeordnet.
     */
    protected function _initMatchTicker()
    {
        if (!is_array($this->_tickerArr)) {

            // Der Ticker wird immer chronologisch ermittelt
            $this->_tickerArr = &tx_cfcleaguefe_util_MatchTicker::getTicker4Match($this->match);
            // Jetzt die Tickermeldungen noch den Spielern zuordnen
            for ($i = 0; $i < count($this->_tickerArr); ++$i) {
                $note = $this->_tickerArr[$i];
                $player = $note->getPlayerInstance();
                if (is_object($player)) {
                    $player->addMatchNote($note);
                }
            }
        }
    }

    /**
     * Liefert die gewrappten Namen einer Profilliste.
     *
     * @param array $profiles
     *            Array mit den Personen. Kann auch direkt ein Profil sein.
     * @param string $confIdAll
     *            TS-Config String. Sollte einen Eintrag profile. enthalten
     *
     * @return string mit allen Namen
     */
    protected function _getNames2($profiles, $confIdAll)
    {
        $this->_initMatchTicker();
        $ret = $this->_wrapProfiles($profiles, $confIdAll.'profile.');
        // Jetzt noch die einzelnen Strings verbinden
        // Der Seperator sollte mit zwei Pipes eingeschlossen sein
        $sep = $this->_configurations->get($confIdAll.'seperator');
        $sep = (strlen($sep) > 2) ? substr($sep, 1, strlen($sep) - 2) : $sep;
        $ret = implode($sep, $ret);
        // Jetzt noch ein Wrap über alles
        return $this->_formatter->stdWrap($ret, $this->_configurations->get($confIdAll), $this->match->getProperty());
    }

    /**
     * Erstellt die Wrapps für ein Array von Profiles.
     * Der übergebene Parameter kann aber
     * auch ein einzelnes Profile sein. Das Ergebnis ist aber in jedem Fall ein Array von Strings.
     *
     * @return array of Strings or an empty array
     */
    protected function _wrapProfiles($profiles, $confId)
    {
        $ret = [];
        if (!is_array($profiles)) {
            if (is_object($profiles)) {
                $profiles = [
                    $profiles,
                ];
            } else {
                return [];
            }
        }

        foreach ($profiles as $profile) {
            if (is_object($profile)) {
                $name = tx_cfcleaguefe_models_profile::wrap($this->_formatter, $confId, $profile);
                if (strlen($name) > 0) {
                    $ret[] = $name;
                }
            } else { // Wenn $profile kein Objekt ist, dann wurde das Profil nicht geladen...
                $ret[] = '??';
            }
        }

        return $ret;
    }

    /**
     * Wrappt alle übergebenen Tickermeldungen.
     *
     * @param array $tickerArr
     * @param string $confIdAll
     */
    protected function _wrapTickers(&$tickerArr, $confIdAll)
    {
        $ret = [];
        foreach ($tickerArr as $ticker) {
            $ret[] = tx_cfcleaguefe_models_match_note::wrap($this->_formatter, $confIdAll.'ticker.', $ticker);
        }
        // Die einzelnen Meldungen verbinden
        if (count($ret)) {
            $sep = $this->_configurations->get($confIdAll.'seperator');

            $sep = (strlen($sep) > 2) ? substr($sep, 1, strlen($sep) - 2) : $sep;
            $ret = implode($sep, $ret);
        } else {
            $ret = null;
        }

        $conf = $this->_configurations->get($confIdAll);
        // Jetzt noch ein Wrap über alles
        return $this->_formatter->stdWrap($ret, $conf, $this->match->getProperty());
    }

    /**
     * Liefert den Namen der Spieler in der Startaufstellung eines Teams.
     */
    protected function _getLineUp($players, $system, $confId)
    {
        $conf = $this->_configurations->get($confId);

        $system = Strings::trimExplode('-', $system);
        $players = is_array($players) ? array_values($players) : [];

        $strategyEnable = $this->_configurations->getBool($confId.'strategy.enable');

        // Jetzt die Spieler nach dem System aufteilen
        // $parts = count($system);
        if (!$strategyEnable) {
            $system[0] = count($players);
        }

        $partCnt = 0;
        $partArr = [];
        $splitSum = $system[$partCnt];
        for ($i = 0; $i < count($players); ++$i) {
            $partArr[$partCnt][] = $players[$i];
            // Muss umgeschaltet werden?
            if (count($partArr[$partCnt]) >= $splitSum) {
                // Die Spielernamen holen
                $partArr[$partCnt] = $this->_getNames2($partArr[$partCnt], $confId);
                ++$partCnt;
                $splitSum = $system[$partCnt];
            }
        }

        // $sep = (strlen($conf['seperator']) > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : $conf['seperator'];
        $sep = $this->_configurations->get($confId.'strategy.seperator');
        $hits = [];
        if (preg_match('/^\|(.*)\|$/', $sep, $hits)) {
            $sep = $hits[1];
        }
        $ret = implode(' - ', $partArr);

        // Jetzt noch ein Wrap über alles
        return $this->_formatter->stdWrap($ret, $conf, $this->match->getProperty());
    }

    /**
     * Lädt das Spiel aus der Datenbank.
     */
    protected function _loadMatch($matchId)
    {
        // Wir holen gleich einige Zusatzinfos mit
        $match = tx_cfcleaguefe_models_match::getMatchInstance($matchId);

        return $match->isValid() ? $match : 0;
    }

    /**
     * Liefert das Logo eines Teams.
     * Es ist entweder das zugeordnete Logo des Teams oder
     * das Logo des Vereins.
     *
     * @param
     *            tx_cfcleaguefe_models_team
     */
    protected function _getLogo($team)
    {
        return $team->getLogo($this->_formatter, 'matchreport.logo.');
    }
}
