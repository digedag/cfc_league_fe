<?php

namespace System25\T3sports\Twig\Data;

use tx_cfcleague_models_MatchNote;
use tx_cfcleague_models_Profile;
use tx_cfcleague_util_ServiceRegistry;

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

/**
 * Provide additional data for player.
 *
 * @author rene
 */
class Player
{
    /** @var tx_cfcleague_models_MatchNote[] */
    protected $matchNotes = [];

    protected $matchNotesByType = [];

    protected $profileSrv;

    protected $profile;

    protected $uniqueName = true;

    public function __construct(tx_cfcleague_models_Profile $profile)
    {
        $this->profile = $profile;
        $this->profileSrv = tx_cfcleague_util_ServiceRegistry::getProfileService();
    }

    public function getUid()
    {
        return $this->profile->getUid();
    }

    public function addMatchNote(MatchNote $note)
    {
        $this->matchNotes[] = $note;
        if (!array_key_exists($note->getType(), $this->matchNotesByType)) {
            $this->matchNotesByType[$note->getType()] = [];
        }
        $this->matchNotesByType[$note->getType()][] = $note;
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function hasMatchNoteType($type)
    {
        return isset($this->matchNotesByType[$type]);
    }

    /**
     * @param int $type
     *
     * @return tx_cfcleague_models_MatchNote[]
     */
    public function getMatchNotesByType($type)
    {
        return $this->matchNotesByType[$type];
    }

    /**
     * @return bool
     */
    public function isCardYellow()
    {
        return $this->hasMatchNoteType(tx_cfcleague_models_MatchNote::TYPE_CARD_YELLOW);
    }

    /**
     * @return tx_cfcleague_models_MatchNote[]
     */
    public function getChangedOut()
    {
        return $this->getMatchNotesByType(tx_cfcleague_models_MatchNote::TYPE_CHANGEOUT);
    }

    /**
     * @return bool
     */
    public function isCardYellowRed()
    {
        return $this->hasMatchNoteType(tx_cfcleague_models_MatchNote::TYPE_CARD_YELLOWRED);
    }

    /**
     * @return bool
     */
    public function isCardRed()
    {
        return $this->hasMatchNoteType(tx_cfcleague_models_MatchNote::TYPE_CARD_RED);
    }

    /**
     * @return bool
     */
    public function isCaptain()
    {
        return $this->hasMatchNoteType(tx_cfcleague_models_MatchNote::TYPE_CAPTAIN);
    }

    /**
     * @return tx_cfcleague_models_MatchNote[]
     */
    public function getMatchNotes()
    {
        return $this->matchNotes;
    }

    /**
     * @return tx_cfcleague_models_Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * whether or not the lastname of this player is unique in this match.
     *
     * @return bool
     */
    public function getUniqueName()
    {
        return $this->uniqueName;
    }

    public function setUniqueName($uniqueName)
    {
        $this->uniqueName = $uniqueName;

        return $this;
    }
}
