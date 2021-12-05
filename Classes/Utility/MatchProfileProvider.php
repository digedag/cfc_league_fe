<?php

namespace System25\T3sports\Utility;

use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Match;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\ProfileRepository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2021 Rene Nitzsche (rene@system25.de)
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

class MatchProfileProvider
{
    public const PLAYERS_HOME = 'getPlayersHome';
    public const PLAYERS_GUEST = 'getPlayersGuest';

    private $profileRepo;
    private $players = [];
    protected $profiles;

    public function __construct(ProfileRepository $profileRepo = null)
    {
        $this->profileRepo = $profileRepo ?: new ProfileRepository();
    }

    /**
     * Liefert die Spieler eines Spiels.
     *
     * @param Match $match
     * @param string $methodName self::PLAYERS_HOME or self::PLAYERS_GUEST
     * @param bool $all
     *
     * @return Profile[] wenn true dann wird eine Map geliefert. Key ist die UID des Spielers
     */
    public function getPlayers(Match $match, $methodName, $all = false)
    {
        $key = $methodName.'_'.($all ? 'all' : 'lineup');
        if (!isset($this->players[$match->getUid()][$key])) {
            $this->players[$match->getUid()][$key] = [];
            $playerUids = $match->$methodName($all ? 1 : 0);
            $players = $this->profileRepo->findByUids($playerUids);
            foreach ($players as $player) {
                $this->players[$match->getUid()][$key][$player->getUid()] = $player;
            }
            if (!$all) {
                $ordered = [];
                foreach (Strings::intExplode(',', $playerUids) as $uid) {
                    if (isset($this->players[$match->getUid()][$key][$uid])) {
                        $ordered[] = $this->players[$match->getUid()][$key][$uid];
                    }
                }
                $this->players[$match->getUid()][$key] = $ordered;
            }
        }

        return $this->players[$match->getUid()][$key];
    }

    /**
     * Liefert die Profiles des UID-Strings als Array.
     * Key ist die UID, Value das Profile.
     *
     * @param Match $match
     * @param string $uidStr
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    public function getProfiles(Match $match, $uidStr): array
    {
        $ret = [];
        if ($uidStr) {
            $this->resolveProfiles($match);
            $uids = Strings::intExplode(',', $uidStr);
            foreach ($uids as $uid) {
                $ret[$uid] = &$this->profiles[$match->getUid()][$uid];
            }
        }

        return $ret;
    }

    /**
     * Erstellt für alle Personen des Spiels die passenden Objekte.
     * Dies wird aber nur gemacht
     * wenn die entsprechenden IDs noch nicht geladen sind.
     *
     * @return array Key ist UID, Value ist Profile als Object
     */
    private function resolveProfiles(Match $match)
    {
        if (isset($this->profiles[$match->getUid()])) {
            return;
        } // Die Profile sind schon geladen
        // Wir sammeln zunächst die UIDs zusammen
        $uids = [];
        if ($match->getProperty('referee')) {
            $uids[] = $match->getProperty('referee');
        }
        if ($match->getProperty('assists')) {
            $uids[] = $match->getProperty('assists');
        }
        if ($match->getProperty('coach_home')) {
            $uids[] = $match->getProperty('coach_home');
        }
        if ($match->getProperty('coach_guest')) {
            $uids[] = $match->getProperty('coach_guest');
        }
        if ($match->getProperty('players_home')) {
            $uids[] = $match->getProperty('players_home');
        }
        if ($match->getProperty('players_guest')) {
            $uids[] = $match->getProperty('players_guest');
        }
        if ($match->getProperty('substitutes_home')) {
            $uids[] = $match->getProperty('substitutes_home');
        }
        if ($match->getProperty('substitutes_guest')) {
            $uids[] = $match->getProperty('substitutes_guest');
        }

        $uids = implode($uids, ',');

        $rows = $this->profileRepo->findByUids($uids);

        $this->profiles[$match->getUid()] = [];
        // Wir erstellen jetzt ein Array dessen Key die UID des Profiles ist
        foreach ($rows as $profile) {
            $this->profiles[$match->getUid()][$profile->getUid()] = $profile;
        }
    }
}
