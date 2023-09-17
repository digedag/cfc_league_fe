<?php

namespace System25\T3sports\Statistics\Service;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use System25\T3sports\Model\Competition;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Statistics\PlayerSummaryStatisticsMarker;
use tx_rnbase;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2007-2021 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * Service for summery of player statistics
 * Since this list is similar to player statistics, it is based on that service.
 * It simply modifies the result.
 *
 * @author Rene Nitzsche
 */
class PlayerSummaryStatistics extends PlayerStatistics
{
    private $result = [];

    /**
     * Array with competition IDs of handled matches.
     */
    private $compIds = [];

    public function getStatsType()
    {
        return 'playersummary';
    }

    /**
     * {@inheritdoc}
     *
     * @see \System25\T3sports\Statistics\Service\PlayerStatistics::handleMatch()
     */
    public function handleMatch(Fixture $match, $clubId)
    {
        if ($match->getProperty('players_home') || $match->getProperty('players_guest') || count($match->getMatchNotes())) {
            $this->result['numberOfUsedMatches'] = $this->result['numberOfUsedMatches'] + 1;
        }
        $this->compIds[] = $match->getProperty('competition');
    }

    /**
     * Liefert allgemeine Daten zur Spielerstatistik.
     *
     * @return array
     */
    public function getResult()
    {
        if (!array_key_exists('numberOfUsedMatches', $this->result)) {
            $this->result['numberOfUsedMatches'] = 0;
        }
        $teams = $this->getTeams($this->getScopeArray());
        $this->_setAdditionalData($this->getScopeArray(), $teams, array_unique($this->compIds));

        return $this->result;
    }

    /**
     * Returns the marker instance to map result data to HTML markers.
     *
     * @param ConfigurationInterface $configurations
     *
     * @return PlayerSummaryStatisticsMarker
     */
    public function getMarker(ConfigurationInterface $configurations)
    {
        return tx_rnbase::makeInstance(PlayerSummaryStatisticsMarker::class);
    }

    /**
     * Einige Zusatzdaten ermitteln.
     */
    public function _setAdditionalData(&$scopeArr, &$teams, &$compUids)
    {
        // Wir zählen wieviele Spiele die Wettbewerbe haben, die in der
        // Statistik betrachtet wurden.
        $ret = [];
        $this->result['numberOfMatches'] = 0;
        foreach ($compUids as $compUid) {
            $comp = tx_rnbase::makeInstance(Competition::class, $compUid);
            // Gesucht: Anzahl Spiele der Teams gesamt und beendet
            // Spiele gesamt holen wir über getRounds
            $teamIds = [];
            foreach ($teams as $team) {
                $teamIds[] = $team->getUid();
            }
            $matchCount = $comp->getNumberOfMatches(implode(',', $teamIds)); // Spiele gesamt
            $this->result['numberOfMatches'] = $this->result['numberOfMatches'] + $matchCount;
        }

        return $ret;
    }
}
