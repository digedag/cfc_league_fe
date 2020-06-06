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
 * Statische Methoden zur Ermittlung statistischer Angaben.
 */
class tx_cfcleaguefe_util_StatisticsHelper
{
    /**
     * No instance necessary.
     *
     * @return tx_cfcleaguefe_util_StatisticsHelper
     */
    private function __construct()
    {
    }

    /**
     * Allgemeine Prüffunktion auf einen bestimmten Note-Typ für einen Spieler.
     * Alle gefundenen
     * Notes werden als Ergebnis zurückgeliefert.
     *
     * @param int $type
     *            MatchNote-Typ
     * @param tx_cfcleaguefe_models_profile $player
     *            Referenz auf den Spieler
     * @param tx_cfcleaguefe_models_match $match
     *            Referenz auf das Spiel
     * @returns liefert die MatchNotes des Typs als Array oder 0
     */
    public static function isNote($type, $player, $match)
    {
        $ret = [];
        $tickerArr = &$match->getMatchNotesByType($type);

        for ($i = 0; $i < count($tickerArr); ++$i ) {
            $matchNote = &$tickerArr[$i];
            $notePlayer = $matchNote->getPlayerInstance();
            if ($notePlayer && $notePlayer->getUid() == $player->getUid()) {
                if ($matchNote->isType($type)) {
                    $ret[] = $matchNote;
                }
            }
        }

        return count($ret) > 0 ? $ret : 0;
    }

    /**
     * Alle Typen für ein Tor.
     */
    private static $goalTypes = array(
        10,
        11,
        12,
    );

    /**
     * Prüft, ob der Spieler ein Tor geschossen hat.
     * Eigentore werden hier ignoriert.
     *
     * @param int $type
     *            MatchNote-Typ des Tors oder 0 für alle Tore
     * @param tx_cfcleaguefe_models_profile $player
     *            Referenz auf den Spieler
     * @param tx_cfcleaguefe_models_match $match
     *            Referenz auf das Spiel
     * @returns liefert die MatchNotes der Tore als Array oder 0
     */
    public static function isGoal($type, $player, $match)
    {
        $tickerType = 0 == $type ? self::$goalTypes : $type;
        $tickerArr = &$match->getMatchNotesByType($tickerType);

        $ret = [];
        for ($i = 0; $i < count($tickerArr); ++$i ) {
            $matchNote = &$tickerArr[$i];
            $notePlayer = $matchNote->getPlayerInstance();
            if ($notePlayer && $notePlayer->getUid() == $player->getUid()) {
                $ret[] = $matchNote;
            }
        }

        return count($ret) > 0 ? $ret : 0;
    }

    /**
     * Prüft, ob der Spieler eine gelbe Karte gesehen hat.
     *
     * @param tx_cfcleaguefe_models_profile $player
     * @param tx_cfcleaguefe_models_match $match
     * @returns liefert die Spielminute oder 0
     */
    public static function isCardYellow($player, $match)
    {
        return self::_isCard('Y', $player, $match);
    }

    /**
     * Prüft, ob der Spieler eine gelb-rote Karte gesehen hat.
     *
     * @param tx_cfcleaguefe_models_profile $player
     * @param tx_cfcleaguefe_models_match $match
     * @returns liefert die Spielminute oder 0
     */
    public static function isCardRed($player, $match)
    {
        return self::_isCard('R', $player, $match);
    }

    /**
     * Prüft, ob der Spieler eine rote Karte gesehen hat.
     *
     * @param tx_cfcleaguefe_models_profile $player
     * @param tx_cfcleaguefe_models_match $match
     * @returns liefert die Spielminute oder 0
     */
    public static function isCardYellowRed($player, $match)
    {
        return self::_isCard('YR', $player, $match);
    }

    /**
     * Prüft, ob der Spieler eine Karte gesehen hat.
     *
     * @param string $type
     *            Typ der Karte: Y,R,YR
     * @param tx_cfcleaguefe_models_profile $player
     * @param tx_cfcleaguefe_models_match $match
     * @returns liefert die Spielminute oder 0
     */
    private static function _isCard($type, $player, $match)
    {
        $tickerType = 'Y' == $type ? 70 : ('YR' == $type ? 71 : 72);
        $tickerArr = &$match->getMatchNotesByType($tickerType);

        for ($i = 0; $i < count($tickerArr); ++$i ) {
            $matchNote = &$tickerArr[$i];
            $notePlayer = $matchNote->getPlayerInstance();
            if ($notePlayer && $notePlayer->getUid() == $player->getUid()) {
                if ('Y' == $type && $matchNote->isYellowCard()) {
                    return $matchNote->getMinute();
                }
                if ('R' == $type && $matchNote->isRedCard()) {
                    return $matchNote->getMinute();
                }
                if ('YR' == $type && $matchNote->isYellowRedCard()) {
                    return $matchNote->getMinute();
                }
            }
        }
    }

    /**
     * Prüft, ob der Spieler eingewechselt wurde.
     *
     * @returns liefert die Spielminute oder 0
     */
    public static function isChangedIn(&$player, &$match)
    {
        return self::_isPlayerChanged('IN', $player, $match);
    }

    /**
     * Prüft, ob der Spieler ausgewechselt wurde.
     *
     * @returns liefert die Spielminute oder 0
     */
    public static function isChangedOut(&$player, &$match)
    {
        return self::_isPlayerChanged('OUT', $player, $match);
    }

    /**
     * Prüft, ob der Spieler ausgewechselt wurde und liefert in diesem Fall die Spielminute.
     *
     * @param string $inOut
     *            - Werte sind 'in' oder 'out'
     * @param tx_cfcleaguefe_models_match $match
     * @returns liefert die Spielminute oder 0
     */
    private static function _isPlayerChanged($inOut, &$player, &$match)
    {
        $tickerArr = &$match->getMatchNotesByType(('IN' == $inOut) ? 81 : 80);
        for ($i = 0, $size = count($tickerArr); $i < $size; ++$i ) {
            $matchNote = &$tickerArr[$i];
            $playerChange = ('IN' == $inOut) ? $matchNote->getPlayerChangeIn() : $matchNote->getPlayerChangeOut();
            if ($playerChange && $playerChange->getUid() == $player->getUid()) {
                // Es ist nicht möglich einen Spieler zweimal auszuwechseln!
                return $matchNote->getMinute();
            }
        }
    }
}
