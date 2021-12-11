<?php

namespace System25\T3sports\Frontend\Marker;

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\SimpleMarker;
use System25\T3sports\Model\MatchNote;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\ProfileRepository;
use System25\T3sports\Model\Team;
use tx_rnbase;

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

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Spielereignisse verantwortlich.
 */
class MatchNoteMarker extends SimpleMarker
{
    private $profileRepo;

    public function __construct($options = [])
    {
        $this->setClassname(MatchNote::class);
        parent::__construct($options);
        $this->profileRepo = new ProfileRepository();
    }

    /**
     * @param string $template das HTML-Template
     * @param MatchNote $item das Tickerereignis
     * @param FormatUtil $formatter der zu verwendente Formatter
     * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.club.'
     * @param array $links Array mit Link-Instanzen, wenn Verlinkung möglich sein soll.
     *            Zielseite muss vorbereitet sein.
     * @param string $marker Name des Markers für den Club, z.B. CLUB
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
     *
     * @return string das geparste Template
     */
    protected function finishTemplate($template, $item, $formatter, $confId, $marker = 'NOTE')
    {
        if ($this->containsMarker($template, $marker.'_MATCH_')) {
            $template = $this->addMatch($template, $item, $formatter, $confId.'match.', $marker.'_MATCH');
        }
        if ($this->containsMarker($template, $marker.'_PLAYER_')) {
            $sub = $this->profileRepo->findByMatchNote($item);
            $template = $this->addProfile($template, $sub, $formatter, $confId.'player.', $marker.'_PLAYER');
        }
        if ($this->containsMarker($template, $marker.'_PLAYERCHANGEIN_')) {
            $player = $this->profileRepo->findByUid($item->getPlayerUidChangeIn());
            $template = $this->addProfile($template, $player, $formatter, $confId.'playerchangein.', $marker.'_PLAYERCHANGEIN');
        }
        if ($this->containsMarker($template, $marker.'_PLAYERCHANGEOUT_')) {
            $player = $this->profileRepo->findByUid($item->getPlayerUidChangeOut());
            $template = $this->addProfile($template, $player, $formatter, $confId.'playerchangeout.', $marker.'_PLAYERCHANGEOUT');
        }
        if ($this->containsMarker($template, $marker.'_TEAM_')) {
            $template = $this->addTeam($template, $item->getTeam(), $formatter, $confId.'team.', $marker.'_TEAM');
        }

        return $template;
    }

    /**
     * Bindet einen Spieler ein.
     *
     * @param string $template
     * @param Profile $sub
     * @param FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     *
     * @return string
     */
    protected function addProfile($template, ?Profile $sub, $formatter, $confId, $markerPrefix)
    {
        if (!$sub) {
            // Kein Datensatz vorhanden. Leere Instanz anlegen und altname setzen
            $sub = BaseMarker::getEmptyInstance(Profile::class);
        }
        $marker = tx_rnbase::makeInstance(ProfileMarker::class);
        $template = $marker->parseTemplate($template, $sub, $formatter, $confId, $markerPrefix);

        return $template;
    }

    /**
     * Bindet ein Spiel ein.
     *
     * @param string $template
     * @param MatchNote $note
     * @param FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     *
     * @return string
     */
    protected function addMatch($template, MatchNote $note, $formatter, $confId, $markerPrefix)
    {
        $match = $note->getMatch();
        $marker = tx_rnbase::makeInstance(MatchMarker::class);
        $template = $marker->parseTemplate($template, $match, $formatter, $confId, $markerPrefix);

        return $template;
    }

    /**
     * @param string $template
     * @param Team $sub
     * @param FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     *
     * @return string
     */
    protected function addTeam($template, $sub, $formatter, $confId, $markerPrefix)
    {
        if (!$sub) {
            // Kein Datensatz vorhanden. Leere Instanz anlegen und altname setzen
            $sub = BaseMarker::getEmptyInstance(Team::class);
        }
        $marker = tx_rnbase::makeInstance(TeamMarker::class);
        $template = $marker->parseTemplate($template, $sub, $formatter, $confId, $markerPrefix);

        return $template;
    }
}
