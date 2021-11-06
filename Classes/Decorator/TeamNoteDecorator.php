<?php

namespace System25\T3sports\Decorator;

use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\TeamNoteRepository;
use System25\T3sports\Model\Team;
use System25\T3sports\Model\TeamNoteType;

class TeamNoteDecorator
{
    private $tnRepo;

    public function __construct(TeamNoteRepository $teamNoteRepo)
    {
        $this->tnRepo = $teamNoteRepo;
    }

    /**
     * Fügt die TeamNotes für den Spieler hinzu.
     * Wird kein Team übergeben, dann passiert nichts.
     *
     * @param Profile $profile
     * @param Team $team
     */
    public function addTeamNotes(Profile $profile, $team)
    {
        // Zunächst alle Daten initialisieren
        $types = TeamNoteType::getAll();
        for ($i = 0, $cnt = count($types); $i < $cnt; ++$i) {
            $type = $types[$i];
            $profile->setProperty('tn'.$type->getMarker(), '');
            $profile->setProperty('tn'.$type->getMarker().'_type', '0');
        }

        if (is_object($team)) {
            // Mit Team können die TeamNotes geholt werden
            $notes = $this->getTeamNotes($profile, $team);
            for ($i = 0, $cnt = count($notes); $i < $cnt; ++$i) {
                $note = $notes[$i];
                $noteType = $note->getType();
                $profile->setProperty('tn'.$noteType->getMarker(), $note->getUid());
                $profile->setProperty('tn'.$noteType->getMarker().'_type', $note->getProperty('mediatype'));
            }
        }
    }

    /**
     * Returns the team notes for this player.
     *
     * @param Team $team
     */
    private function getTeamNotes($profile, $team)
    {
        return $this->tnRepo->getTeamNotes($profile, $team);
    }
}
