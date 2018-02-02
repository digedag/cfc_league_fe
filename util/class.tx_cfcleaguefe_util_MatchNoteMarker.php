<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2018 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_util_SimpleMarker');
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_util_Extensions');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Spielereignisse verantwortlich
 */
class tx_cfcleaguefe_util_MatchNoteMarker extends tx_rnbase_util_SimpleMarker
{

    public function __construct($options = array())
    {
        $this->setClassname('tx_cfcleaguefe_models_match_note');
        parent::__construct($options);
    }

    /**
     *
     * @param string $template
     *            das HTML-Template
     * @param tx_cfcleaguefe_models_match_note $item
     *            das Tickerereignis
     * @param tx_rnbase_util_FormatUtil $formatter
     *            der zu verwendente Formatter
     * @param string $confId
     *            Pfad der TS-Config des Vereins, z.B. 'listView.club.'
     * @param array $links
     *            Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
     * @param string $marker
     *            Name des Markers für den Club, z.B. CLUB
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
     * @return String das geparste Template
     */
    protected function finishTemplate($template, $item, $formatter, $confId, $marker = 'NOTE')
    {
        if ($this->containsMarker($template, $marker . '_PLAYER_')) {
            $template = $this->addProfile($template, $item->getPlayerInstance(), $formatter, $confId . 'player.', $marker . '_PLAYER');
        }
        if ($this->containsMarker($template, $marker . '_PLAYERCHANGEIN_')) {
            $template = $this->addProfile($template, $item->getPlayerChangeIn(), $formatter, $confId . 'playerchangein.', $marker . '_PLAYERCHANGEIN');
        }
        if ($this->containsMarker($template, $marker . '_PLAYERCHANGEOUT_')) {
            $template = $this->addProfile($template, $item->getPlayerChangeOut(), $formatter, $confId . 'playerchangeout.', $marker . '_PLAYERCHANGEOUT');
        }
        if ($this->containsMarker($template, $marker . '_TEAM_')) {
            $template = $this->addTeam($template, $item->getTeam(), $formatter, $confId . 'team.', $marker . '_TEAM');
        }

        return $template;
    }

    /**
     * Bindet eine Spieler ein
     *
     * @param string $template
     * @param tx_cfcleague_models_Profile $sub
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     * @return string
     */
    protected function addProfile($template, $sub, $formatter, $confId, $markerPrefix)
    {
        if (! $sub) {
            // Kein Datensatz vorhanden. Leere Instanz anlegen und altname setzen
            $sub = tx_rnbase_util_BaseMarker::getEmptyInstance('tx_cfcleague_models_Profile');
        }
        $marker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ProfileMarker');
        $template = $marker->parseTemplate($template, $sub, $formatter, $confId, $markerPrefix);
        return $template;
    }
    /**
     *
     * @param string $template
     * @param tx_cfcleague_models_Team $sub
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     * @return string
     */
    protected function addTeam($template, $sub, $formatter, $confId, $markerPrefix)
    {
        if (! $sub) {
            // Kein Datensatz vorhanden. Leere Instanz anlegen und altname setzen
            $sub = tx_rnbase_util_BaseMarker::getEmptyInstance('tx_cfcleague_models_Team');
        }
        $marker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
        $template = $marker->parseTemplate($template, $sub, $formatter, $confId, $markerPrefix);
        return $template;
    }
}
