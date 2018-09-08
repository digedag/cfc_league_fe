<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2018 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_cfcleague_models_MatchNote');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');

/**
 * Model für eine Tickermeldung.
 * Derzeit gibt es die folgenden Typen:
 * <pre>
 * type.ticker -> 100
 * type.goal -> 10
 * type.goal.header -> 11
 * type.goal.penalty -> 12
 * type.goal.own -> 30
 * type.goal.assist -> 31
 * type.penalty.forgiven -> 32
 * type.corner -> 33
 * type.yellow -> 70
 * type.yellowred -> 71
 * type.red -> 72
 * type.changeout -> 80
 * type.changein -> 81
 * type.captain -> 200
 * </pre>
 */
class tx_cfcleaguefe_models_match_note extends tx_cfcleague_models_MatchNote
{

    var $match;

    /**
     * Die Instanz des unbekannten Spielers
     */
    private static $unknownPlayer;

    /**
     * Die Instanz des fehlenden Profils
     */
    private static $notFoundProfile;

    /**
     * Formatiert die Ausgabe der Note über TS.
     * Die Note besteht aus insgesamt
     * fünf Teilen:
     * <ul><li>Die Spielminute TS: ticker.minute
     * <li>der Typ TS: ticker.type
     * <li>der Spieler TS: ticker.profile und ticker.profile2
     * <li>der Kommentar TS: ticker.comment
     * <li>der Spielstand zum Zeitpunkt der Note TS: ticker.score
     * </ul>
     * Für jedes Element kann ein "weight" gesetzt werden, womit die Reihenfolge bestimmt wird.
     * Das Element mit dem höchsten Gewicht wird zuletzt dargestellt.
     *
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     * @param tx_cfcleaguefe_models_match_note $ticker
     */
    static function wrap($formatter, $confId, $ticker)
    {
        if ($formatter->configurations->get($confId . 'hide') == '1') // Die Meldung soll nicht gezeigt werden
            return '';

        // Wenn der Ticker für den eigene Vereins ist, einen Eintrag im Register setzen
        $GLOBALS['TSFE']->register['T3SPORTS_NOTE_FAVCLUB'] = $ticker->isFavClub(); // XXX: Access to image size by TS

        $arr = array();
        $conf = $formatter->configurations->get($confId . 'profile.');
        // Angezeigt wird ein Spieler, sobald etwas im TS steht
        if ($conf && is_object($ticker)) {
            // Bei einem Wechsel ist profile für den ausgewechselten Spieler
            if ($ticker->isChange()) {
                $player = $ticker->getPlayerChangeOut();
            } else {
                $player = $ticker->getPlayerInstance();
            }
            // $value = $player->wrap($formatter, $conf['profile.']);
            $value = tx_cfcleaguefe_models_profile::wrap($formatter, $confId . 'profile.', $player);
            if (strlen($value) > 0) {
                $arr[] = array(
                    $value,
                    $weights[] = $conf['s_weight'] ? intval($conf['s_weight']) : 0
                );
                $value = '';
            }
        }

        // Bei Spielerwechseln gibt es noch ein zweites Profil
        $conf = $formatter->configurations->get($confId . 'profile2.');
        if ($conf && is_object($ticker) && $ticker->isChange()) {
            $player2 = $ticker->getPlayerChangeIn();
            if (! is_object($player2)) {
                // Hier liegt vermutlich ein Fehler bei der Dateneingabe vor
                // Es wird ein Hinweistext gezeigt
                $value = 'ERROR!';
            } else {
                $value = tx_cfcleaguefe_models_profile::wrap($formatter, $confId . 'profile2.', $player2);
            }
            // $value = $player2->wrap($formatter, $conf['profile2.']);
            if (strlen($value) > 0) {
                $arr[] = array(
                    $value,
                    $weights[] = $conf['s_weight'] ? intval($conf['s_weight']) : 0
                );
                $value = '';
            }
        }
        $cObj = $formatter->configurations->getCObj(1);
        $cObj->data = $ticker->record;
        foreach ($ticker->record as $key => $val) {
            $conf = $formatter->configurations->get($confId . $key . '.');
            if ($conf) {
                $cObj->setCurrentVal($ticker->record[$key]);
                $value = $cObj->stdWrap($ticker->record[$key], $conf);
                // $value = $cObj->stdWrap($ticker->record[$key],$conf[$key.'.']);
                if (strlen($value) > 0) {
                    $arr[] = array(
                        $value,
                        $conf['s_weight'] ? intval($conf['s_weight']) : 0
                    );
                    $value = '';
                }
            }
        }

        // Jetzt die Teile sortieren
        usort($arr, 'cmpWeight');
        $ret = array();
        // Jetzt die Strings extrahieren
        foreach ($arr as $val) {
            $ret[] = $val[0];
        }

        // Der Seperator sollte mit zwei Pipes eingeschlossen sein
        $sep = $formatter->configurations->get($confId . 'seperator');
        $sep = (strlen($sep) > 2) ? substr($sep, 1, strlen($sep) - 2) : $sep;
        $ret = implode($sep, $ret);

        // Abschließend nochmal den Ergebnisstring wrappen
        return $formatter->wrap($ret, $confId, $ticker->record);
    }

    /**
     * Liefert bei einem Wechsel den eingewechselten Spieler.
     */
    function getPlayerChangeIn()
    {
        return $this->_getPlayerChange(0);
    }

    /**
     * Liefert bei einem Wechsel den ausgewechselten Spieler.
     */
    function getPlayerChangeOut()
    {
        return $this->_getPlayerChange(1);
    }

    /**
     * Liefert das aktuelle Zwischenergebnis zum Zeitpunkt der Meldung.
     * Diese kommt in der Form 0:0
     * Für eine formatierte Ausgabe sollte die Methode wrap verwendete werden.
     */
    function getScore()
    {
        return $this->record['goals_home'] . ' : ' . $this->record['goals_guest'];
    }

    /**
     * Liefert true wenn die Meldung eine Strafe ist (Karten)
     */
    function isPenalty()
    {
        $type = intval($this->record['type']);
        return ($type >= 70 && $type < 80);
    }

    /**
     * Liefert true wenn die Meldung eine gelb/rote Karte ist
     */
    function isYellowRedCard()
    {
        return $this->isType(71);
    }

    /**
     * Liefert true wenn die Meldung eine rote Karte ist
     */
    function isRedCard()
    {
        return $this->isType(72);
    }

    /**
     * Liefert true wenn die Meldung eine gelbe Karte ist
     */
    function isYellowCard()
    {
        return $this->isType(70);
    }

    /**
     * Entscheidet, ob die Note angezeigt werden soll.
     * Dies wird über die Config
     * entschieden. Derzeit wird die Spielminute (noteMinimumMinute) und der
     * Typ der Meldung (noteType und noteIgnoreType) überprüft.
     *
     * @param array $conf
     * @return boolean
     */
    function isVisible($conf)
    {
        $minMinute = intval($conf['noteMinimumMinute']);
        return $minMinute <= $this->record['minute'] && $this->isType($conf);
    }

    /**
     * Liefert true, wenn die Meldung dem Typ entspricht
     * Parameter ist entweder die Typnummer oder ein Array mit den Keys
     * noteType und noteTeam.
     * Bei noteType kann eine Liste von Typnummern
     * angegeben werden. NoteTeam ist entweder home oder guest.
     *
     * @param array $typeNumberOrArray
     */
    function isType($typeNumberOrArray)
    {
        $ret = false;
        if (is_array($typeNumberOrArray)) {
            $typeArr = $typeNumberOrArray;
            // Wenn es ein Array ist, dann zunächst die Typen ermitteln
            // Keine Typen bedeutet, daß alle verwendet werden
            $types = $typeArr['noteType'] ? Tx_Rnbase_Utility_Strings::intExplode(',', $typeArr['noteType']) : array();
            $ignoreTypes = $typeArr['noteIgnoreType'] ? Tx_Rnbase_Utility_Strings::intExplode(',', $typeArr['noteIgnoreType']) : array();

            // Wenn Typen definiert sind, dann wird ignoreType nicht betrachtet
            if (in_array($this->getType(), $types) || (! count($types) && ! count($ignoreTypes)) || (! in_array($this->getType(), $ignoreTypes) && count($ignoreTypes))) {

                // Wird nach Home/Guest unterschieden?
                if (array_key_exists('noteTeam', $typeArr) && strlen($typeArr['noteTeam'])) {
                    // Eigentore beim jeweils anderen Team zeigen
                    if (strtoupper($typeArr['noteTeam']) == 'HOME' && ($this->isGoalOwn() ? $this->isGuest() : $this->isHome())) {
                        $ret = true;
                    } elseif (strtoupper($typeArr['noteTeam']) == 'GUEST' && ($this->isGoalOwn() ? $this->isHome() : $this->isGuest())) {
                        $ret = true;
                    }
                } else
                    $ret = true;
            }
        } else {
            $type = $this->getType();
            $ret = ($type == intval($typeNumberOrArray));
        }
        return $ret;
    }

    /**
     * Liefert den Namen des Tickertyps.
     * Dafür ist die aktuelle Config notwendig,
     * da der Wert über das Flexform ermittelt wird
     */
    function getTypeName(&$configurations)
    {
        global $LANG, $TSFE;
        if ($this->getType() == 100) {
            return '';
        }

        $flex = $this->_getFlexForm($configurations);
        $types = $this->_getItemsArrayFromFlexForm($flex, 's_matchreport', 'tickerTypes');
        foreach ($types as $type) {
            if (intval($type[1]) == intval($this->record['type'])) {
                return $TSFE->sL($type[0]);
            }
        }
        return '';
    }

    function getExtraTime()
    {
        return $this->record['extra_time'];
    }

    /**
     * Liefert die Singleton-Instanz des unbekannten Spielers.
     * Dieser hat die ID -1 und
     * wird für MatchNotes verwendet, wenn der Spieler nicht bekannt ist.
     */
    function getUnknownPlayer()
    {
        if (! is_object(tx_cfcleaguefe_models_match_note::$unknownPlayer)) {
            tx_cfcleaguefe_models_match_note::$unknownPlayer = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', '-1');
        }
        return tx_cfcleaguefe_models_match_note::$unknownPlayer;
    }

    /**
     * Liefert die Singleton-Instanz eines nicht gefundenen Profils.
     * Dieses hat die ID -2 und
     * wird für MatchNotes verwendet, wenn das Profil nicht mehr in der Datenbank gefunden wurde.
     * FIXME: Vermutlich ist diese Funktionalität in der Matchklasse besser aufgehoben
     */
    function &getNotFoundProfile()
    {
        if (! is_object(tx_cfcleaguefe_models_match_note::$notFoundProfile)) {
            tx_cfcleaguefe_models_match_note::$notFoundProfile = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', '-2');
        }
        return tx_cfcleaguefe_models_match_note::$notFoundProfile;
    }

    /**
     * Liefert das Profil des an der Aktion beteiligten Spielers der Heimmannschaft.
     * Wenn nicht vorhanden wird der Spieler "Unbekannt" geliefert
     */
    protected function getInstancePlayerHome()
    {
        // Innerhalb der Matchnote gibt es das Konstrukt des unbekannten Spielers. Dieser
        // Wird verwendet, wenn der eigentliche Spieler nicht mehr in der Datenbank gefunden
        // wird, oder wenn die ID des Spielers -1 ist.
        if (intval($this->record['player_home']) == 0) { // ID 0 ist nicht vergeben
            $player = NULL;
        } elseif (intval($this->record['player_home']) == - 1) {
            $player = $this->getUnknownPlayer();
        } else {
            $players = $this->match->getPlayersHome(1); // Spieler und Wechselspieler holen
            $player = & $players[$this->record['player_home']];
        }
        return $player;
    }

    /**
     * Liefert das Profil, des an der Aktion beteiligten Spielers der Gastmannschaft
     */
    protected function getInstancePlayerGuest()
    {
        if (intval($this->record['player_guest']) == 0) { // ID 0 ist nicht vergeben
            $player = NULL;
        } elseif (intval($this->record['player_guest']) == - 1) {
            $player = & $this->getUnknownPlayer();
        } else {
            $players = $this->match->getPlayersGuest(1);
            $player = & $players[$this->record['player_guest']];
            if (! is_object($player)) {
                $player = & $this->getNotFoundProfile();
            }
        }
        return $player;
    }

    /**
     * Liefert den Spieler dem diese Meldung zugeordnet ist.
     *
     * @return tx_cfcleaguefe_models_profile ein Profil oder 0
     */
    public function getPlayerInstance()
    {
        if ($this->isHome()) {
            return $this->getInstancePlayerHome();
        }
        if ($this->isGuest()) {
            return $this->getInstancePlayerGuest();
        }
        return 0;
    }

    /**
     * Zur Abfrage von Zusatzinfos wird Zugriff auf das zugehörige Spiel benötigt.
     * Diese muss vorher mit dieser Methode bekannt gemacht werden.
     *
     * @param tx_cfcleaguefe_models_match $match
     */
    function setMatch(&$match)
    {
        $this->match = $match;
    }

    /**
     * Liefert das Spiel
     *
     * @return tx_cfcleaguefe_models_match
     */
    function getMatch()
    {
        return $this->match;
    }

    /**
     * Returns the team of the player assigned to this match note
     * @return NULL|tx_cfcleaguefe_models_team
     */
    public function getTeam()
    {
        $team = null;
        if ($this->isHome() ) {
            $team = $this->getMatch()->getHome();
        }
        elseif($this->isGuest()) {
            $team = $this->getMatch()->getGuest();
        }
        return $team;
    }


    /**
     * Ermittelt für die übergebenen Spiele die MatchNotes.
     * Wenn $types = 1 dann
     * werden nur die Notes mit dem Typ < 100 geliefert. Die MatchNotes werden direkt
     * in den übergebenen Matches gesetzt.
     * Die ermittelten MatchNotes haben keine Referenz auf das zugehörige Match!
     * @param tx_cfcleaguefe_models_match[] $matches
     * @param int $types
     * @return
     */
    public static function &retrieveMatchNotes(&$matches, $types = 1)
    {
        if (! count($matches)) {
            return $matches;
        }
        // Die Spiele in einen Hash legen, damit wir sofort Zugriff auf ein Spiel haben
        $matchesHash = [];
        $matchIds = [];
        $anz = count($matches);
        for ($i = 0; $i < $anz; $i ++) {
            $matchesHash[$matches[$i]->getUid()] = & $matches[$i];
            $matchIds[] = $matches[$i]->getUid();
        }

        $matchIds = implode(',', $matchIds); // ID-String erstellen

        $what = '*';
        $from = 'tx_cfcleague_match_notes';
        $options['where'] = 'game IN (' . $matchIds . ')';
        if ($types) {
            $options['where'] .= ' AND type < 100';
        }
        $options['orderby'] = 'game asc, minute asc';
        $options['wrapperclass'] = 'tx_cfcleaguefe_models_match_note';

        $matchNotes = Tx_Rnbase_Database_Connection::getInstance()->doSelect($what, $from, $options);

        // Das Match setzen (foreach geht hier nicht weil es nicht mit Referenzen arbeitet...)
        $anz = count($matchNotes);
        for ($i = 0; $i < $anz; $i ++) {
            // Hier darf nur mit Referenzen gearbeitet werden
            // $matchNotes[$i]->setMatch($matchesHash[$matchNotes[$i]->record['game']]);
            $matchesHash[$matchNotes[$i]->getProperty('game')]->addMatchNote($matchNotes[$i]);
        }

        return $matches;
    }

    /**
     * Prüft die TS-Anweisung showOnly für eine MatchNote.
     *
     * @return int 1 - show, 0 - show not
     */
    protected function _isShowTicker($conf, $ticker)
    {
        $showOnly = $conf['showOnly'];
        if (strlen($showOnly) > 0) {
            $showOnly = Tx_Rnbase_Utility_Strings::intExplode(',', $showOnly);
            // Prüfen ob der aktuelle Typ paßt
            if (count($showOnly) > 0 && in_array($ticker->record['type'], $showOnly)) {
                return 1;
            }
        } else {
            return 1;
        }
        return 0;
    }

    protected function &_getFlexForm(&$configurations)
    {
        static $flex;
        if (! is_array($flex)) {
            $flex = Tx_Rnbase_Utility_T3General::getURL(tx_rnbase_util_Extensions::extPath($configurations->getExtensionKey()) . $configurations->get('flexform'));
            $flex = Tx_Rnbase_Utility_T3General::xml2array($flex);
        }
        return $flex;
    }

    /**
     * Liefert die möglichen Werte für ein Attribut aus einem FlexForm-Array
     */
    protected function _getItemsArrayFromFlexForm($flexArr, $sheetName, $valueName)
    {
        return $flexArr['sheets'][$sheetName]['ROOT']['el'][$valueName]['TCEforms']['config']['items'];
    }

    /**
     * Whether or not the match note is for favorite club
     *
     * @return int 0/1
     */
    private function isFavClub()
    {
        // Zuerst das Team holen
        $team = null;
        $match = $this->getMatch();
        if ($this->isHome()) {
            $team = $match->getHome();
        } elseif ($this->isGuest()) {
            $team = $match->getGuest();
        }
        if (! is_object($team))
            return 0;
        $club = $team->getClub();
        if (! is_object($club))
            return 0;
        return $club->isFavorite() ? 1 : 0;
    }

    /**
     * Liefert den ausgewechselten Spieler, wenn der Tickertyp ein Wechsel ist
     *
     * @param int $type
     *            0 liefert den eingewechselten Spieler, 1 den ausgewechselten
     */
    protected function _getPlayerChange($type)
    {
        // Ist es ein Wechsel?
        if ($this->isChange() && ($this->record['player_home'] || $this->record['player_guest'])) {
            // Heim oder Gast?
            if ($this->record['player_home']) {
                $players = $this->match->getPlayersHome(1);
                $playerField = $this->record['type'] == '80' ? ($type ? 'player_home' : 'player_home_2') : ($type ? 'player_home_2' : 'player_home');
            } else {
                $players = $this->match->getPlayersGuest(1);
                $playerField = $this->record['type'] == '80' ? ($type ? 'player_guest' : 'player_guest_2') : ($type ? 'player_guest_2' : 'player_guest');
            }
            if ($this->record[$playerField] < 0)
                return $this->getUnknownPlayer();
            return $players[$this->record[$playerField]];
        }
    }
}

/**
 * Sortierfunktion, um die korrekte Reihenfolge nach weights zu ermittlen
 */
function cmpWeight($a, $b)
{
    if ($a[1] == $b[1]) {
        return 0;
    }
    return ($a[1] < $b[1]) ? - 1 : 1;
}
