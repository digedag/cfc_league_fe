<?php

namespace System25\T3sports\Utility;

use Sys25\RnBase\Utility\Queue;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\MatchNote;
use System25\T3sports\Model\Repository\MatchNoteRepository;
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
 * Controllerklasse für den MatchTicker.
 *
 * Die Funktion des Spieltickers geht über einen reinen Infoticker hinaus. Viele Daten des
 * Tickers werden gleichzeitig für statistische Zwecke verwendet. Um hier die Funktionalität
 * nicht ins uferlose auswachsen zu lassen, werden die möglichen Tickertypen fest vorgegeben.
 * Diese sind zum einen in der tca.php der Extension cfc_league definiert und des weiteren
 * noch einmal in dieser Klasse als Konstanten abgelegt. Die Angaben müssen übereinstimmen!
 *
 * UseCases:
 * - Anzeige des Ereignistickers zu einem Spiel
 * - Abruf von bestimmten Tickertypen über mehrere Spiel hinweg (für Statistik)
 *
 * Es muß immer bekannt sein, welche Tickertypen benötigt werden und welche Spiele
 * betrachtet werden sollen.
 */
class MatchTicker
{
    private static $cache = [];
    private $mnRepo;

    public function __construct(MatchNoteRepository $mnRepo = null)
    {
        $this->mnRepo = $mnRepo ?: new MatchNoteRepository();
    }

    /**
     * Liefert alle Spiele des Scopes mit den geladenen Tickermeldungen.
     */
    public function getMatches4Scope($scopeArr, $types = 0)
    {
        // Wir liefern alle Spiele des Scopes mit den zugehörigen Tickermeldungen
        // Die Spiele bekommen wir über die Matchtable
        $service = ServiceRegistry::getMatchService();
        $matchtable = $service->getMatchTable();
        $matchtable->setScope($scopeArr);
        $matchtable->setStatus(2);
        $fields = [];
        $options = [];
        $options['orderby']['MATCH.DATE'] = 'asc';
        $matchtable->getFields($fields, $options);
        $matches = $service->search($fields, $options);

        // Jetzt holen wir die Tickermeldungen für diese Spiele
        $matches = $this->mnRepo->retrieveMatchNotes($matches->toArray());

        return $matches;
    }

    /**
     * Liefert die TickerInfos für einzelne Spiele in chronologischer Reihenfolge.
     * Die Meldungen enthalten den aktuellen Spielstand. Spielerwechsel werden als eine einzelne
     * Tickermeldung zusammengefasst.
     *
     * @param Fixture $match
     * @param mixed $types unused!
     *
     * @return MatchNote[]
     */
    public function getTicker4Match(Fixture $match, $types = 0)
    {
        $arr = self::get('matchnotes_'.$match->getUid());
        if ($arr) {
            return $arr;
        }

        $arr = $this->mnRepo->loadMatchNotesByMatch($match);

        // Die Notes werden jetzt noch einmal aufbereitet
        $ret = [];
        $anz = count($arr);

        for ($i = 0; $i < $anz; ++$i) {
            $ticker = $arr[$i];
            // Datensatz im Zielarray ablegen
            $ret[] = $ticker;

            $tickerRemoved = self::_handleChange($ret, $ticker);
            if (!$tickerRemoved) {
                self::_handleResult($ret[count($ret) - 1]);
            }
        }
        self::add('matchnotes_'.$match->getUid(), $ret);

        return $ret;
    }

    /**
     * Trägt den Spielstand im Ticker ein.
     * Dies funktioniert natürlich nur, wenn die Meldungen
     * in chronologischer Reihenfolge ankommen.
     *
     * @param MatchNote $ticker der zuletzt hinzugefügte Ticker
     */
    private static function _handleResult(&$ticker)
    {
        static $goals_home, $goals_guest;
        if (!isset($goals_home)) {
            $goals_home = 0;
            $goals_guest = 0;
        }
        // Ist die Meldung ein Heimtor?
        if ($ticker->isGoalHome()) {
            $goals_home = $goals_home + 1;
        }        // Ist die Meldung ein Gasttor?
        elseif ($ticker->isGoalGuest()) {
            $goals_guest = $goals_guest + 1;
        }

        // Stand speichern
        $ticker->setProperty('goals_home', $goals_home);
        $ticker->setProperty('goals_guest', $goals_guest);
    }

    /**
     * Ein- und Auswechslungen werden durch Aufruf dieser Methode zusammengefasst.
     * Die beiden
     * betroffenen Spieler werden dabei in der Tickermeldung der Auswechslung zusammengefasst. Der zweite
     * Spieler wird unter dem Key 'player_home_2' bzw. 'player_guest_2' abgelegt.
     * Der zweite Datensatz wird aus dem Ergebnisarray entfernt.
     *
     * @param array $ret Referenz auf Array mit den bisher gefundenen Ticker-Daten
     * @param MatchNote $ticker der zuletzt hinzugefügte Ticker
     *
     * @return bool wether or not the ticker record was removed
     */
    private static function _handleChange(&$ret, MatchNote $ticker)
    {
        $isRemoved = false;
        if (!$ticker->isChange()) {
            return $isRemoved;
        }
        // TODO: Es muss immer die Auswechslung erhalten bleiben!
        // 1. Ein- und Auswechslungen zusammenfassen
        static $changeInHome, $changeInGuest; // Hier liegen die IDX von Einwechslungen im Zielarray
        static $changeOutHome, $changeOutGuest; // Hier die AUswechslungen

        // Bevor es losgeht, müssen einmalig die Arrays initialisiert werden
        if (!is_object($changeInHome)) {
            $changeInHome = tx_rnbase::makeInstance(Queue::class);
            $changeOutHome = tx_rnbase::makeInstance(Queue::class);
            $changeInGuest = tx_rnbase::makeInstance(Queue::class);
            $changeOutGuest = tx_rnbase::makeInstance(Queue::class);
        }

        if ($ticker->isHome()) {
            if (MatchNote::TYPE_CHANGEIN == $ticker->getType()) { // Wenn Einwechslung
                // Gibt es schon die Auswechslung?
                if (!$changeOutHome->isEmpty()) {
                    $change = $changeOutHome->get();
                    $change->setProperty('player_home_2', $ticker->getProperty('player_home'));
                    $change->setProperty('comment2', $ticker->getProperty('comment'));
                } else {
                    // Einwechslung ablegen
                    $changeInHome->put($ticker);
                }
                array_pop($ret); // Die Einwechslung fliegt aus dem Ticker
                $isRemoved = true;
            }

            if (MatchNote::TYPE_CHANGEOUT == $ticker->getType()) { // Wenn Auswechslung
                // Gibt es schon die Einwechslung?
                if (!$changeInHome->isEmpty()) {
                    // Wartet schon so ein Wechsel
                    $change = &$changeInHome->get();
                    $ticker->setProperty('player_home_2', $change->getProperty('player_home'));
                    $change->setProperty('comment2', $ticker->getProperty('comment'));
                } else {
                    // Auswechselung ablegen
                    $changeOutHome->put($ticker);
                }
            }
        } // end if HOME
        elseif ($ticker->isGuest()) {
            if (MatchNote::TYPE_CHANGEIN == $ticker->getType()) { // Ist Einwechslung
                // Gibt es schon die Auswechslung?
                if (!$changeOutGuest->isEmpty()) {
                    // Die Auswechslung holen
                    $change = &$changeOutGuest->get();
                    $change->setProperty('player_guest_2', $ticker->getProperty('player_guest'));
                    $change->setProperty('comment2', $ticker->getProperty('comment'));
                } else {
                    // Einwechslung ablegen
                    $changeInGuest->put($ticker);
                }
                array_pop($ret); // Die Einwechslung fliegt aus dem Ticker
                $isRemoved = true;
            }
            if (MatchNote::TYPE_CHANGEOUT == $ticker->getType()) { // Auswechslung
                // Gibt es schon die Einwechslung?
                if (!$changeInGuest->isEmpty()) {
                    // Es muss immer die Auswechslung erhalten bleiben
                    $change = &$changeInGuest->get();
                    $ticker->setProperty('player_guest_2', $change->getProperty('player_guest'));
                    $change->setProperty('comment2', $ticker->getProperty('comment'));
                } else {
                    // Auswechselung ablegen
                    $changeOutGuest->put($ticker);
                }
            }
        } // end if GUEST

        return $isRemoved;
    }

    /**
     * Ein Objekt in den Cache legen.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function add($key, $value)
    {
        self::$cache[$key] = $value;
    }

    /**
     * Liefert ein Objekt aus dem Cache.
     *
     * @param $key
     *
     * @return mixed
     */
    public static function get($key)
    {
        return self::$cache[$key] ?? null;
    }

    /*
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.ticker", '100'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.goal", '10'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.goal.header", '11'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.goal.penalty", '12'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.goal.own", '30'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.goal.assist", '31'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.penalty.forgiven", '32'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.corner", '33'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.yellow", '70'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.yellowred", '71'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.red", '72'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.changeout", '80'),
     * Array("LLL:EXT:cfc_league/Resources/Private/Language/locallang_db.xml:tx_cfcleague_match_notes.type.changein", '81'),
     */
}
