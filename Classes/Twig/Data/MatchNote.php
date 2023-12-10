<?php

namespace System25\T3sports\Twig\Data;

use tx_cfcleague_models_MatchNote;
use tx_cfcleague_util_ServiceRegistry;

/***************************************************************
*  Copyright notice
*
*  (c) 2017-2019 Rene Nitzsche (rene@system25.de)
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
 * Provide additional data for match notes.
 */
class MatchNote
{
    protected $profileSrv;

    /** @var tx_cfcleague_models_MatchNote[] */
    protected $matchNote;

    /** @var Player */
    protected $player;

    /** @var Player */
    protected $player2;

    /**
     * @param tx_cfcleague_models_MatchNote $note
     * @param Player $player
     */
    public function __construct(tx_cfcleague_models_MatchNote $note, $player = null)
    {
        $this->matchNote = $note;
        $this->player = $player;
        $this->profileSrv = tx_cfcleague_util_ServiceRegistry::getProfileService();
    }

    public function getUid()
    {
        return $this->matchNote->getUid();
    }

    public function getMinute()
    {
        return $this->matchNote->getMinute();
    }

    public function getComment()
    {
        return $this->matchNote->getProperty('comment');
    }

    public function isHome()
    {
        $ret = $this->matchNote->isHome();
        if ($this->matchNote->isGoalOwn()) {
            $ret = !$ret;
        }

        return $ret;
    }

    public function isGuest()
    {
        $ret = $this->matchNote->isGuest();
        if ($this->matchNote->isGoalOwn()) {
            $ret = !$ret;
        }

        return $ret;
    }

    public function getGoalsHome()
    {
        return $this->matchNote->getProperty('goals_home');
    }

    public function getGoalsGuest()
    {
        return $this->matchNote->getProperty('goals_guest');
    }

    public function getType()
    {
        return $this->matchNote->getType();
    }

    public function isChange()
    {
        return tx_cfcleague_models_MatchNote::TYPE_CHANGEOUT == $this->getType();
    }

    public function isGoal()
    {
        $goalTypes = [
            tx_cfcleague_models_MatchNote::TYPE_GOAL,
            tx_cfcleague_models_MatchNote::TYPE_GOAL_HEADER,
            tx_cfcleague_models_MatchNote::TYPE_GOAL_PENALTY,
            tx_cfcleague_models_MatchNote::TYPE_GOAL_OWN,
        ];

        return in_array($this->getType(), $goalTypes);
    }

    /**
     * @return tx_cfcleague_models_MatchNote
     */
    public function getMatchNote()
    {
        return $this->matchNote;
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    public function getPlayer2()
    {
        return $this->player2;
    }

    public function setPlayer2(Player $player2)
    {
        $this->player2 = $player2;

        return $this;
    }
}
