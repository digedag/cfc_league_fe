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
 * </pre>.
 */
class tx_cfcleaguefe_models_match_note extends tx_cfcleague_models_MatchNote
{
    public $match;

    /**
     * Die Instanz des unbekannten Spielers.
     */
    private static $unknownPlayer;

    /**
     * Die Instanz des fehlenden Profils.
     */
    private static $notFoundProfile;


    /**
     * Liefert bei einem Wechsel den eingewechselten Spieler.
     */
    public function getPlayerChangeIn()
    {
        return $this->_getPlayerChange(0);
    }

    /**
     * Liefert bei einem Wechsel den ausgewechselten Spieler.
     */
    public function getPlayerChangeOut()
    {
        return $this->_getPlayerChange(1);
    }

    /**
     * Liefert das aktuelle Zwischenergebnis zum Zeitpunkt der Meldung.
     * Diese kommt in der Form 0:0
     * Für eine formatierte Ausgabe sollte die Methode wrap verwendete werden.
     */
    public function getScore()
    {
        return $this->getProperty('goals_home').' : '.$this->getProperty('goals_guest');
    }

    /**
     * Liefert true wenn die Meldung eine Strafe ist (Karten).
     */
    public function isPenalty()
    {
        $type = (int) $this->getProperty('type');

        return $type >= 70 && $type < 80;
    }

    /**
     * Liefert true wenn die Meldung eine gelb/rote Karte ist.
     */
    public function isYellowRedCard()
    {
        return $this->isType(71);
    }

    /**
     * Liefert true wenn die Meldung eine rote Karte ist.
     */
    public function isRedCard()
    {
        return $this->isType(72);
    }

    /**
     * Liefert true wenn die Meldung eine gelbe Karte ist.
     */
    public function isYellowCard()
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
     *
     * @return bool
     * @deprecated MatchNoteDecorator
     */
    public function isVisible($conf)
    {
        $minMinute = intval($conf['noteMinimumMinute']);

        return $minMinute <= $this->getProperty('minute') && $this->isType($conf);
    }

    /**
     * Liefert true, wenn die Meldung dem Typ entspricht
     * Parameter ist entweder die Typnummer oder ein Array mit den Keys
     * noteType und noteTeam.
     * Bei noteType kann eine Liste von Typnummern
     * angegeben werden. NoteTeam ist entweder home oder guest.
     *
     * @param array $typeNumberOrArray
     * @deprecated MatchNoteDecorator
     */
    public function isType($typeNumberOrArray)
    {
        $ret = false;
        if (is_array($typeNumberOrArray)) {
            $typeArr = $typeNumberOrArray;
            // Wenn es ein Array ist, dann zunächst die Typen ermitteln
            // Keine Typen bedeutet, daß alle verwendet werden
            $types = $typeArr['noteType'] ? Tx_Rnbase_Utility_Strings::intExplode(',', $typeArr['noteType']) : [];
            $ignoreTypes = $typeArr['noteIgnoreType'] ? Tx_Rnbase_Utility_Strings::intExplode(',', $typeArr['noteIgnoreType']) : [];

            // Wenn Typen definiert sind, dann wird ignoreType nicht betrachtet
            if (in_array($this->getType(), $types) || (!count($types) && !count($ignoreTypes)) || (!in_array($this->getType(), $ignoreTypes) && count($ignoreTypes))) {
                // Wird nach Home/Guest unterschieden?
                if (array_key_exists('noteTeam', $typeArr) && strlen($typeArr['noteTeam'])) {
                    // Eigentore beim jeweils anderen Team zeigen
                    if ('HOME' == strtoupper($typeArr['noteTeam']) && ($this->isGoalOwn() ? $this->isGuest() : $this->isHome())) {
                        $ret = true;
                    } elseif ('GUEST' == strtoupper($typeArr['noteTeam']) && ($this->isGoalOwn() ? $this->isHome() : $this->isGuest())) {
                        $ret = true;
                    }
                } else {
                    $ret = true;
                }
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
     * da der Wert über das Flexform ermittelt wird.
     */
    public function getTypeName(&$configurations)
    {
        global $LANG, $TSFE;
        if (100 == $this->getType()) {
            return '';
        }

        $flex = $this->_getFlexForm($configurations);
        $types = $this->_getItemsArrayFromFlexForm($flex, 's_matchreport', 'tickerTypes');
        foreach ($types as $type) {
            if (intval($type[1]) == intval($this->getProperty('type'))) {
                return $TSFE->sL($type[0]);
            }
        }

        return '';
    }

    public function getExtraTime()
    {
        return $this->getProperty('extra_time');
    }

    /**
     * Liefert die Singleton-Instanz des unbekannten Spielers.
     * Dieser hat die ID -1 und
     * wird für MatchNotes verwendet, wenn der Spieler nicht bekannt ist.
     * @deprecated MatchNoteDecorator
     */
    public function getUnknownPlayer()
    {
        if (!is_object(self::$unknownPlayer)) {
            self::$unknownPlayer = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', '-1');
        }

        return self::$unknownPlayer;
    }

    /**
     * Liefert die Singleton-Instanz eines nicht gefundenen Profils.
     * Dieses hat die ID -2 und
     * wird für MatchNotes verwendet, wenn das Profil nicht mehr in der Datenbank gefunden wurde.
     * FIXME: Vermutlich ist diese Funktionalität in der Matchklasse besser aufgehoben.
     * @deprecated MatchNoteDecorator
     */
    public function &getNotFoundProfile()
    {
        if (!is_object(self::$notFoundProfile)) {
            self::$notFoundProfile = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', '-2');
        }

        return self::$notFoundProfile;
    }

    /**
     * Liefert das Profil des an der Aktion beteiligten Spielers der Heimmannschaft.
     * Wenn nicht vorhanden wird der Spieler "Unbekannt" geliefert.
     * @deprecated MatchNoteDecorator
     */
    protected function getInstancePlayerHome()
    {
        // Innerhalb der Matchnote gibt es das Konstrukt des unbekannten Spielers. Dieser
        // Wird verwendet, wenn der eigentliche Spieler nicht mehr in der Datenbank gefunden
        // wird, oder wenn die ID des Spielers -1 ist.
        if (0 == intval($this->getProperty('player_home'))) { // ID 0 ist nicht vergeben
            $player = null;
        } elseif (-1 == intval($this->getProperty('player_home'))) {
            $player = $this->getUnknownPlayer();
        } else {
            $players = $this->match->getPlayersHome(1); // Spieler und Wechselspieler holen
            $player = &$players[$this->getProperty('player_home')];
        }

        return $player;
    }

    /**
     * Liefert das Profil, des an der Aktion beteiligten Spielers der Gastmannschaft.
     * @deprecated MatchNoteDecorator
     */
    protected function getInstancePlayerGuest()
    {
        if (0 == intval($this->getProperty('player_guest'))) { // ID 0 ist nicht vergeben
            $player = null;
        } elseif (-1 == intval($this->getProperty('player_guest'))) {
            $player = &$this->getUnknownPlayer();
        } else {
            $players = $this->match->getPlayersGuest(1);
            $player = &$players[$this->getProperty('player_guest')];
            if (!is_object($player)) {
                $player = &$this->getNotFoundProfile();
            }
        }

        return $player;
    }

    /**
     * Liefert den Spieler dem diese Meldung zugeordnet ist.
     *
     * @return tx_cfcleaguefe_models_profile ein Profil oder 0
     * @deprecated MatchNoteDecorator
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
    public function setMatch(&$match)
    {
        $this->match = $match;
    }

    /**
     * Liefert das Spiel.
     *
     * @return tx_cfcleaguefe_models_match
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * Returns the team of the player assigned to this match note.
     *
     * @return tx_cfcleaguefe_models_team|null
     */
    public function getTeam()
    {
        $team = null;
        if ($this->isHome()) {
            $team = $this->getMatch()->getHome();
        } elseif ($this->isGuest()) {
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
     *
     * @param tx_cfcleaguefe_models_match[] $matches
     * @param int $types
     *
     * @return
     */
    public static function &retrieveMatchNotes(&$matches, $types = 1)
    {
        throw new Exception('use match note repo');
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
            if (count($showOnly) > 0 && in_array($ticker->getProperty('type'), $showOnly)) {
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
        if (!is_array($flex)) {
            $flex = Tx_Rnbase_Utility_T3General::getURL(tx_rnbase_util_Extensions::extPath($configurations->getExtensionKey()).$configurations->get('flexform'));
            $flex = Tx_Rnbase_Utility_T3General::xml2array($flex);
        }

        return $flex;
    }

    /**
     * Liefert die möglichen Werte für ein Attribut aus einem FlexForm-Array.
     */
    protected function _getItemsArrayFromFlexForm($flexArr, $sheetName, $valueName)
    {
        return $flexArr['sheets'][$sheetName]['ROOT']['el'][$valueName]['TCEforms']['config']['items'];
    }

    /**
     * Whether or not the match note is for favorite club.
     * @deprecated ist im MatchNoteDecorator
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
        if (!is_object($team)) {
            return 0;
        }
        $club = $team->getClub();
        if (!is_object($club)) {
            return 0;
        }

        return $club->isFavorite() ? 1 : 0;
    }

    /**
     * Liefert den ausgewechselten Spieler, wenn der Tickertyp ein Wechsel ist.
     *
     * @param int $type
     *            0 liefert den eingewechselten Spieler, 1 den ausgewechselten
     */
    protected function _getPlayerChange($type)
    {
        // Ist es ein Wechsel?
        if ($this->isChange() && ($this->getProperty('player_home') || $this->getProperty('player_guest'))) {
            // Heim oder Gast?
            if ($this->getProperty('player_home')) {
                $players = $this->match->getPlayersHome(1);
                $playerField = '80' == $this->getProperty('type') ? ($type ? 'player_home' : 'player_home_2') : ($type ? 'player_home_2' : 'player_home');
            } else {
                $players = $this->match->getPlayersGuest(1);
                $playerField = '80' == $this->getProperty('type') ? ($type ? 'player_guest' : 'player_guest_2') : ($type ? 'player_guest_2' : 'player_guest');
            }
            if ($this->getProperty($playerField) < 0) {
                return $this->getUnknownPlayer();
            }

            return $players[$this->getProperty($playerField)];
        }
    }
}
