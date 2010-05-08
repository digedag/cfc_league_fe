<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
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
 *
 */
class tx_cfcleaguefe_util_StatisticsHelper {

  /**
   * No instance necessary.
   *
   * @return tx_cfcleaguefe_util_StatisticsHelper
   */
  private function tx_cfcleaguefe_util_StatisticsHelper(){
  }

  /**
   * Allgemeine Prüffunktion auf einen bestimmten Note-Typ für einen Spieler. Alle gefundenen
   * Notes werden als Ergebnis zurückgeliefert.
   * 
   * @param $type int MatchNote-Typ
   * @param $player Referenz auf den Spieler
   * @param $match Referenz auf das Spiel
   * @returns liefert die MatchNotes des Typs als Array oder 0
   */
  public function isNote($type, &$player, &$match) {
    $ret = array();
//    $tickerArr = &$match->getMatchNotes();
    $tickerArr = &$match->getMatchNotesByType($type);
    
    for($i = 0; $i < count($tickerArr); $i++) {
      $matchNote = &$tickerArr[$i];
      $notePlayer = $matchNote->getPlayer();
      if($notePlayer && $notePlayer->uid == $player->uid) {
        if($matchNote->isType($type)) {
          $ret[] = $matchNote;
        }
      }
    }
    return count($ret) > 0 ? $ret : 0;
  }
  /** Alle Typen für ein Tor */
  private static $goalTypes = array(10,11,12);
  /**
   * Prüft, ob der Spieler ein Tor geschossen hat. Eigentore werden hier ignoriert.
   * 
   * @param $type int MatchNote-Typ des Tors oder 0 für alle Tore
   * @param $player Referenz auf den Spieler
   * @param $match Referenz auf das Spiel
   * @returns liefert die MatchNotes der Tore als Array oder 0
   */
  public function isGoal($type, &$player, &$match) {
    $tickerType = $type == 0 ? self::$goalTypes : $type; 
    $tickerArr = &$match->getMatchNotesByType($tickerType);
    
    $ret = array();
//    $tickerArr = &$match->getMatchNotes();

    for($i = 0; $i < count($tickerArr); $i++) {
      $matchNote = &$tickerArr[$i];
      $notePlayer = $matchNote->getPlayer();
      if($notePlayer && $notePlayer->uid == $player->uid) {
//        if($matchNote->isGoal() && ($type == 0 || $matchNote->isType($type)) && !$matchNote->isGoalOwn()) {
          $ret[] = $matchNote;
//        }
      }
    }
    return count($ret) > 0 ? $ret : 0;
  }


  /**
   * Prüft, ob der Spieler eine gelbe Karte gesehen hat
   * 
   * @param tx_cfcleaguefe_models_profile $player
   * @param tx_cfcleaguefe_models_match $match
   * @returns liefert die Spielminute oder 0
   */
  public function isCardYellow(&$player, &$match) {
    return tx_cfcleaguefe_util_StatisticsHelper::_isCard('Y', $player, $match);
  }
  /**
   * Prüft, ob der Spieler eine gelb-rote Karte gesehen hat
   * 
   * @param tx_cfcleaguefe_models_profile $player
   * @param tx_cfcleaguefe_models_match $match
   * @returns liefert die Spielminute oder 0
   */
  public function isCardRed(&$player, &$match) {
    return tx_cfcleaguefe_util_StatisticsHelper::_isCard('R', $player, $match);
  }
  /**
   * Prüft, ob der Spieler eine rote Karte gesehen hat
   * 
   * @param tx_cfcleaguefe_models_profile $player
   * @param tx_cfcleaguefe_models_match $match
   * @returns liefert die Spielminute oder 0
   */
  public function isCardYellowRed(&$player, &$match) {
    return tx_cfcleaguefe_util_StatisticsHelper::_isCard('YR', $player, $match);
  }

  /**
   * Prüft, ob der Spieler eine Karte gesehen hat
   * 
   * @param string $type Typ der Karte: Y,R,YR
   * @param tx_cfcleaguefe_models_profile $player
   * @param tx_cfcleaguefe_models_match $match
   * @returns liefert die Spielminute oder 0
   */
  private function _isCard($type, &$player, &$match) {
    $tickerType = $type == 'Y' ? 70 : ($type == 'YR' ? 71 : 72 ); 
    $tickerArr = &$match->getMatchNotesByType($tickerType);

//    $tickerArr = &$match->getMatchNotes();
    for($i = 0; $i < count($tickerArr); $i++) {
      $matchNote = &$tickerArr[$i];
      $notePlayer = $matchNote->getPlayer();
      if($notePlayer && $notePlayer->uid == $player->uid) {
        if($type == 'Y' && $matchNote->isYellowCard()) {

          return $matchNote->getMinute();
        }
        if($type == 'R' && $matchNote->isRedCard()) {
          return $matchNote->getMinute();
        }
        if($type == 'YR' && $matchNote->isYellowRedCard()) {
          return $matchNote->getMinute();
        }
      }
    }
  }

  /**
   * Prüft, ob der Spieler eingewechselt wurde
   * @returns liefert die Spielminute oder 0
   */
  public function isChangedIn(&$player, &$match) {
    return tx_cfcleaguefe_util_StatisticsHelper::_isPlayerChanged('IN', $player, $match);
  }

  /**
   * Prüft, ob der Spieler ausgewechselt wurde
   * @returns liefert die Spielminute oder 0
   */
  public function isChangedOut(&$player, &$match) {
    return tx_cfcleaguefe_util_StatisticsHelper::_isPlayerChanged('OUT', $player, $match);
  }

  /**
   * Prüft, ob der Spieler ausgewechselt wurde und liefert in diesem Fall die Spielminute
   * @param string $inOut - Werte sind 'in' oder 'out'
   * @param tx_cfcleaguefe_models_match $match
   * @returns liefert die Spielminute oder 0
   */
  private function _isPlayerChanged($inOut, &$player, &$match) {
    $tickerArr = &$match->getMatchNotesByType(($inOut == 'IN') ?  81 : 80);
//t3lib_div::debug($tickerArr, $inOut.' canges tx_cfcleaguefe_util_StatisticsHelper');
    for($i = 0 , $size = count($tickerArr); $i < $size; $i++) {
      $matchNote = &$tickerArr[$i];
        $playerChange = ($inOut == 'IN') ? $matchNote->getPlayerChangeIn() : $matchNote->getPlayerChangeOut();
        if($playerChange && $playerChange->uid == $player->uid) {
//  t3lib_div::debug($matchNote->toString(),'Wechsel utl_stats');
          // Es ist nicht möglich einen Spieler zweimal auszuwechseln!
          return $matchNote->getMinute();
        }
    }


//    $tickerArr = &$match->getMatchNotes();
//    for($i = 0 , $size = count($tickerArr); $i < $size; $i++) {
//      $matchNote = &$tickerArr[$i];
//      if($matchNote->isChange()) {
//        $playerChange = ($inOut == 'IN') ? $matchNote->getPlayerChangeIn() : $matchNote->getPlayerChangeOut();
//        if($playerChange && $playerChange->uid == $player->uid) {
////  t3lib_div::debug($matchNote->toString(),'Wechsel utl_stats');
//          // Es ist nicht möglich einen Spieler zweimal auszuwechseln!
//          return $matchNote->getMinute();
//        }
//      }
//    }
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_StatisticsHelper.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_StatisticsHelper.php']);
}

?>