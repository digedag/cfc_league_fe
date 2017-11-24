<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2017 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('Tx_Cfcleaguefe_Twig_Data_Player');
tx_rnbase::load('Tx_Cfcleaguefe_Twig_Data_MatchNote');

/**
 * Provide additional data for match
 * @author rene
 */
class Tx_Cfcleaguefe_Twig_Data_MatchReport
{
    protected $match;
    /** @var tx_cfcleague_models_MatchNote[] */
    protected $tickerArr;
    /** @var Tx_Cfcleaguefe_Twig_Data_Player[] */
    protected $players = [];
    protected $playersByLastname = [];

    protected $profileSrv;
    public function __construct(tx_cfcleague_models_Match $match)
    {
        $this->match = $match;
        $this->profileSrv = tx_cfcleague_util_ServiceRegistry::getProfileService();
        $this->initMatchTicker();
        $this->getLineupHome();
        $this->getLineupGuest();
        $this->getSubstitutesHome();
        $this->getSubstitutesGuest();
        $this->uniquePlayerNames();
    }

    /**
     * Build the lineup structure for home team
     *
     * @param string $confId
     * @return []
     */
    public function getLineupHome()
    {
        return $this->getLineup($this->getPlayersHome(), $this->match->getProperty('system_home'));
    }

    /**
     * Substitutes for home team
     *
     * @param string $confId
     * @return Tx_Cfcleaguefe_Twig_Data_Player[]
     */
    public function getSubstitutesHome()
    {
        $players = $this->profileSrv->loadProfiles($this->match->getSubstitutesHome());
        $ret = [];
        foreach ($players as $player) {
            $ret[] = $this->substPlayer($player);
        }
        return $ret;
    }

    /**
     *
     * @param string $all
     * @return unknown
     */
    public function getPlayersHome($all = false)
    {
        return $this->profileSrv->loadProfiles($this->match->getPlayersHome($all));
    }

    /**
     * Build the lineup structure for guest team
     *
     * @param string $confId
     * @return string
     */
    public function getLineupGuest()
    {
        return $this->getLineup($this->getPlayersGuest(), $this->match->getProperty('system_guest'));
    }

    /**
     *
     * @param string $all
     * @return tx_cfcleague_models_Profile[]
     */
    public function getPlayersGuest($all = false)
    {
        return $this->profileSrv->loadProfiles($this->match->getPlayersGuest($all));
    }

    /**
     * Substitutes for guest team
     *
     * @param string $confId
     * @return Tx_Cfcleaguefe_Twig_Data_Player[]
     */
    public function getSubstitutesGuest()
    {
        $players = $this->profileSrv->loadProfiles($this->match->getSubstitutesGuest());
        $ret = [];
        foreach ($players as $player) {
            $ret[] = $this->substPlayer($player);
        }
        return $ret;
    }

    /**
     *
     * @return tx_cfcleague_models_Profile
     */
    public function getCoachHome()
    {
        $ret = $this->profileSrv->loadProfiles($this->match->getProperty('coach_home'));
        return empty($ret) ? null : reset($ret);
    }

    /**
     *
     * @return tx_cfcleague_models_Profile
     */
    public function getCoachGuest()
    {
        $ret = $this->profileSrv->loadProfiles($this->match->getProperty('coach_guest'));
        return empty($ret) ? null : reset($ret);
    }

    /**
     *
     * @return tx_cfcleague_models_Profile[]
     */
    public function getAssists()
    {
        return $this->profileSrv->loadProfiles($this->match->getProperty('assists'));
    }

    /**
     *
     * @param tx_cfcleague_models_Profile[] $players
     * @param string $system
     * @return []
     */
    protected function getLineup($players, $system)
    {
        $system = Tx_Rnbase_Utility_Strings::trimExplode('-', $system);
        $players = is_array($players) ? array_values($players) : array();

        $partCnt = 0;
        $partArr = array();
        $splitSum = $system[$partCnt];
        for ($i = 0; $i < count($players); $i ++) {
            $partArr[$partCnt][] = $this->substPlayer($players[$i]);
            // Muss umgeschaltet werden?
            if (count($partArr[$partCnt]) >= $splitSum) {
                $partCnt ++;
                $splitSum = $system[$partCnt];
            }
        }

        return $partArr;
    }
    /**
     *
     * @param tx_cfcleague_models_Profile $player
     * @return Tx_Cfcleaguefe_Twig_Data_Player
     */
    protected function substPlayer(tx_cfcleague_models_Profile $player)
    {
        return array_key_exists($player->getUid(), $this->players) ?
            $this->players[$player->getUid()] : $this->buildPlayer($player);
    }

    /**
     * Initialisiert die MatchNotes.
     * Diese werden auch den Spieler zugeordnet
     */
    protected function initMatchTicker()
    {
        if (! is_array($this->tickerArr)) {
            $this->tickerArr = [];
            // Der Ticker wird immer chronologisch ermittelt
            $matchNotes =& tx_cfcleaguefe_util_MatchTicker::getTicker4Match($this->match);
            // Jetzt die Tickermeldungen noch den Spielern zuordnen
            for ($i = 0; $i < count($matchNotes); $i ++) {
                $note = $matchNotes[$i];
                $player = null;
                $matchNote = null;
                try {
                    $playerUid = $note->getPlayer();
                    $player = tx_cfcleague_models_Profile::getProfileInstance($playerUid);
                    $player = $this->buildPlayer($player);
                    $matchNote = new Tx_Cfcleaguefe_Twig_Data_MatchNote($note, $player);
                    if ($player) {
                        $player->addMatchNote($matchNote);
                    }
                    if($matchNote->isChange()) {
                        $player2 = tx_cfcleague_models_Profile::getProfileInstance($matchNote->getMatchNote()->getPlayer2());
                        $player2 = $this->buildPlayer($player2);
                        $matchNote->setPlayer2($player2);
                    }
                    $this->tickerArr[] = $matchNote;
                }
                catch (Exception $e) {

                }
            }
        }
    }
    /**
     * mark players with same lastname as not unique
     */
    protected function uniquePlayerNames()
    {
        foreach ($this->playersByLastname as $players) {
            if (count($players) > 1) {
                foreach ($players as $player) {
                    $player->setUniqueName(false);
                }
            }
        }
    }
    /**
     *
     * @param tx_cfcleague_models_Profile $player
     * @return Tx_Cfcleaguefe_Twig_Data_Player
     */
    protected function buildPlayer($player)
    {
        if (is_object($player)) {
            if (isset($this->players[$player->getUid()])) {
                $player = $this->players[$player->getUid()];
            }
            else {
                $player = new Tx_Cfcleaguefe_Twig_Data_Player($player);
                // Keep a reference to each player
                $this->players[$player->getUid()] = $player;

                // Collect players by lastname
                // Hier sollte noch nach Team unterschieden werden...
                $lastName = $player->getProfile()->getLastName();
                if ($lastName) {
                    if (!isset($this->playersByLastname[$lastName])) {
                        $this->playersByLastname[$lastName] = [];
                    }
                    $this->playersByLastname[$lastName][] = $player;
                }
            }
        }
        return $player;
    }

}

