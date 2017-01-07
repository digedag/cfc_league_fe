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

tx_rnbase::load('tx_cfcleague_util_Cache');
tx_rnbase::load('tx_rnbase_util_Queue');
tx_rnbase::load('tx_rnbase_util_Extensions');
require_once(tx_rnbase_util_Extensions::extPath('cfc_league_fe') . 'models/class.tx_cfcleaguefe_models_match_note.php');



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
class tx_cfcleaguefe_util_MatchTicker {

	/**
	 * Liefert alle Spiele des Scopes mit den geladenen Tickermeldungen.
	 */
	function &getMatches4Scope($scopeArr, $types = 0) {
// Wir liefern alle Spiele des Scopes mit den zugehörigen Tickermeldungen
		// Die Spiele bekommen wir über die Matchtable
		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchtable = $service->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->setStatus(2);
		$fields = array();
		$options = array();
		$options['orderby']['MATCH.DATE'] = 'asc';
		$matchtable->getFields($fields, $options);
		$matches = $service->search($fields, $options);

		// Jetzt holen wir die Tickermeldungen für diese Spiele
		$matches = tx_cfcleaguefe_models_match_note::retrieveMatchNotes($matches);
		return $matches;
	}


	/**
	 * Liefert die TickerInfos für einzelne Spiele
	 * @param tx_cfcleaguefe_models_match $match
	 * @param mixed $types unused!
	 */
	public static function &getTicker4Match($match, $types = 0) {
		$arr = tx_cfcleague_util_Cache::get('matchnotes_'.$match->getUid());
		if($arr) return $arr;

		$arr =& $match->getMatchNotes();

		// Die Notes werden jetzt noch einmal aufbereitet
		$ret = array();
		$anz = count($arr);

		for($i = 0; $i<$anz; $i++) {
			$ticker = $arr[$i];
			// Datensatz im Zielarray ablegen
			$ret[] = $ticker;

			$tickerRemoved = self::_handleChange($ret, $ticker);
			if(!$tickerRemoved) {
				self::_handleResult($ret[count($ret)-1]);
			}
		}
		tx_cfcleague_util_Cache::add('matchnotes_'.$match->getUid(), $ret);
		return $ret;
	}

	/**
	 * Trägt den Spielstand im Ticker ein. Dies funktioniert natürlich nur, wenn die Meldungen
	 * in chronologischer Reihenfolge ankommen.
	 * @param tx_cfcleaguefe_models_match_note $ticker der zuletzt hinzugefügte Ticker
	 */
	private static function _handleResult(&$ticker) {
		static $goals_home, $goals_guest;
		if (!isset($goals_home)) {
			$goals_home = 0;
			$goals_guest = 0;
		}
		// Ist die Meldung ein Heimtor?
		if($ticker->isGoalHome()) {
			$goals_home = $goals_home + 1;
		}
		// Ist die Meldung ein Gasttor?
		elseif($ticker->isGoalGuest()) {
			$goals_guest = $goals_guest + 1;
		}

		// Stand speichern
		$ticker->record['goals_home'] = $goals_home;
		$ticker->record['goals_guest'] = $goals_guest;

	}

	/**
	 * Ein- und Auswechslungen werden durch Aufruf dieser Methode zusammengefasst. Die beiden
	 * betroffenen Spieler werden dabei in der Tickermeldung der Auswechslung zusammengefasst. Der zweite
	 * Spieler wird unter dem Key 'player_home_2' bzw. 'player_guest_2' abgelegt.
	 * Der zweite Datensatz wird aus dem Ergebnisarray entfernt.
	 * @param array $ret Referenz auf Array mit den bisher gefundenen Ticker-Daten
	 * @param tx_cfcleaguefe_models_match_note $ticker der zuletzt hinzugefügte Ticker
	 * @return boolean wether or not the ticker record was removed
	 */
	private static function _handleChange(&$ret, &$ticker) {
		$isRemoved = false;
		if(!$ticker->isChange())
			return $isRemoved;
// TODO: Es muss immer die Auswechslung erhalten bleiben!
		// 1. Ein- und Auswechslungen zusammenfassen
		static $changeInHome, $changeInGuest; // Hier liegen die IDX von Einwechslungen im Zielarray
		static $changeOutHome, $changeOutGuest; // Hier die AUswechslungen

		// Bevor es losgeht, müssen einmalig die Arrays initialisiert werden
		if(!is_object($changeInHome)) {
			$changeInHome = tx_rnbase::makeInstance('tx_rnbase_util_Queue');
			$changeOutHome = tx_rnbase::makeInstance('tx_rnbase_util_Queue');
			$changeInGuest = tx_rnbase::makeInstance('tx_rnbase_util_Queue');
			$changeOutGuest = tx_rnbase::makeInstance('tx_rnbase_util_Queue');
		}

		if($ticker->isHome()) {
			if($ticker->record['type'] == '81') { // Wenn Einwechslung
				// Gibt es schon die Auswechslung?
				if(!$changeOutHome->isEmpty()) {
					$change =& $changeOutHome->get();
					$change->record['player_home_2'] = $ticker->record['player_home'];
					$change->record['comment2'] = $ticker->record['comment'];
				}
				else {
					// Einwechslung ablegen
					$changeInHome->put($ticker);
				}
				array_pop($ret); // Die Einwechslung fliegt aus dem Ticker
				$isRemoved = true;
			}

			if($ticker->record['type'] == '80') { // Wenn Auswechslung
				// Gibt es schon die Einwechslung?
				if(!$changeInHome->isEmpty()) {
					// Wartet schon so ein Wechsel
					$change =& $changeInHome->get();
					$ticker->record['player_home_2'] = $change->record['player_home'];
					$change->record['comment2'] = $ticker->record['comment'];
				}
				else {
					// Auswechselung ablegen
					$changeOutHome->put($ticker);
				}
			}
		} // end if HOME
		elseif($ticker->isGuest()) {

			if($ticker->record['type'] == '81') { // Ist Einwechslung
				// Gibt es schon die Auswechslung?
				if(!$changeOutGuest->isEmpty()) {
					// Die Auswechslung holen
					$change =& $changeOutGuest->get();
					$change->record['player_guest_2'] = $ticker->record['player_guest'];
					$change->record['comment2'] = $ticker->record['comment'];
				}
				else {
					// Einwechslung ablegen
					$changeInGuest->put($ticker);
				}
				array_pop($ret); // Die Einwechslung fliegt aus dem Ticker
				$isRemoved = true;
			}
			if($ticker->record['type'] == '80') { // Auswechslung
				// Gibt es schon die Einwechslung?
				if(!$changeInGuest->isEmpty()) {
					// Es muss immer die Auswechslung erhalten bleiben
					$changeIn =& $changeInGuest->get();
					$ticker->record['player_guest_2'] = $changeIn->record['player_guest'];
					$change->record['comment2'] = $ticker->record['comment'];
				}
				else {
					// Auswechselung ablegen
					$changeOutGuest->put($ticker);
				}
			}
		} // end if GUEST
		return $isRemoved;
	}

/*
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.ticker", '100'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.goal", '10'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.goal.header", '11'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.goal.penalty", '12'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.goal.own", '30'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.goal.assist", '31'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.penalty.forgiven", '32'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.corner", '33'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.yellow", '70'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.yellowred", '71'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.red", '72'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.changeout", '80'),
Array("LLL:EXT:cfc_league/locallang_db.xml:tx_cfcleague_match_notes.type.changein", '81'),
*/

}

