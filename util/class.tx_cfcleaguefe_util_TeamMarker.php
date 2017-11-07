<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2017 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_util_BaseMarker');
tx_rnbase::load('tx_rnbase_util_Strings');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Teams verantwortlich
 */
class tx_cfcleaguefe_util_TeamMarker extends tx_rnbase_util_BaseMarker
{

    private $options = null;

    function __construct($options = null)
    {
        $this->options = $options;
    }

    /**
     *
     * @param string $template
     *            das HTML-Template
     * @param tx_cfcleaguefe_models_team $team
     *            das Team
     * @param tx_rnbase_util_FormatUtil $formatter
     *            der zu verwendente Formatter
     * @param string $teamConfId
     *            Pfad der TS-Config des Profils, z.B. 'listView.profile.'
     * @param array $links
     *            Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
     * @param string $teamMarker
     *            Name des Markers für das Team, z.B. TEAM, MATCH_HOME usw.
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###COACH_SIGN###, ###COACH_LINK###
     * @return String das geparste Template
     */
    public function parseTemplate($template, $team, $formatter, $confId, $marker = 'TEAM')
    {
        if (! is_object($team) || ! $team->isValid()) {
            return $formatter->getConfigurations()->getLL('team_notFound');
        }
        $this->prepareRecord($team);
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'teamMarker_initRecord', array(
            'item' => &$team,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter
        ), $this);
        // Es wird das MarkerArray mit den Daten des Teams gefüllt.
        $ignore = self::findUnusedCols($team->getRecord(), $template, $marker);
        $markerArray = $formatter->getItemMarkerArrayWrapped($team->getRecord(), $confId, $ignore, $marker . '_', $team->getColumnNames());
        $wrappedSubpartArray = array();
        $subpartArray = array();
        $this->prepareLinks($team, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
        // Die Spieler setzen
        $this->pushTT('add player');
        if ($this->containsMarker($template, $marker . '_PLAYERS'))
            $template = $this->addProfiles($template, $team, $formatter, $confId . 'player.', $marker . '_PLAYER', 'players');
        $this->pullTT();

        // Die Trainer setzen
        $this->pushTT('add coaches');
        if ($this->containsMarker($template, $marker . '_COACHS'))
            $template = $this->addProfiles($template, $team, $formatter, $confId . 'coach.', $marker . '_COACH', 'coaches');
        $this->pullTT();

        // Die Betreuer setzen
        $this->pushTT('add supporter');
        if ($this->containsMarker($template, $marker . '_SUPPORTERS'))
            $template = $this->addProfiles($template, $team, $formatter, $confId . 'supporter.', $marker . '_SUPPORTER', 'supporters');
        $this->pullTT();

        // set club data
        $this->pushTT('Club data');
        if (self::containsMarker($template, $marker . '_CLUB_'))
            $template = $this->addClubData($template, $team->getClub(), $formatter, $confId . 'club.', $marker . '_CLUB');
        $this->pullTT();

        if ($this->containsMarker($template, $marker . '_GROUP_')) {
            $template = $this->addGroup($template, $team, $formatter, $confId . 'group.', $marker . '_GROUP');
        }
        if ($this->containsMarker($template, $marker . '_PLAYERLIST')) {
            $template = $this->addProfileLists($template, $team, $formatter, $confId . 'playerLists.', $marker . '_PLAYERLIST');
        }

        $template = self::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'teamMarker_afterSubst', array(
            'item' => &$team,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter
        ), $this);
        return $template;
    }

    /**
     * Prepare team record before rendering
     *
     * @param tx_cfcleaguefe_models_team $item
     */
    private function prepareRecord($item)
    {
        $srv = tx_cfcleague_util_ServiceRegistry::getTeamService();
        $group = $srv->getAgeGroup($item);
        // $group = $item->getAgeGroup();
        $GLOBALS['TSFE']->register['T3SPORTS_TEAMGROUP'] = is_object($group) ? $group->getUid() : 0;
        $item->setProperty('group', is_object($group) ? $group->getUid() : '0');
        $item->setProperty('agegroup_name', is_object($group) ? $group->getName() : '');
        $item->setProperty('firstpicture', $item->record['dam_images']);
        $item->setProperty('pictures', $item->record['dam_images']);
    }

    /**
     * Hinzufügen der Daten des Vereins
     *
     * @param string $template
     * @param tx_cfcleaguefe_models_club $club
     */
    protected function addClubData($template, $club, $formatter, $clubConf, $markerPrefix)
    {
        $clubMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
        $template = $clubMarker->parseTemplate($template, $club, $formatter, $clubConf, $markerPrefix);
        return $template;
    }

    /**
     * Hinzufügen der Altersklasse
     *
     * @param string $template
     * @param tx_cfcleaguefe_models_team $item
     */
    protected function addGroup($template, $item, $formatter, $confId, $markerPrefix)
    {
        $srv = tx_cfcleague_util_ServiceRegistry::getTeamService();
        $group = $srv->getAgeGroup($item);

        $groupMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_GroupMarker');
        $template = $groupMarker->parseTemplate($template, $group, $formatter, $confId, $markerPrefix);
        return $template;
    }

    /**
     * Hinzufügen der Spieler des Teams.
     *
     * @param string $template
     *            HTML-Template für die Profile
     * @param tx_cfcleaguefe_models_team $team
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     *            Config-String für den Wrap der Profile
     * @param string $markerPrefix
     *            Prefix für die Daten des Profile-Records
     * @param string $joinCol
     *            Name der Teamspalte mit den Profilen players, coaches, supporters
     */
    private function addProfiles($template, $team, $formatter, $confId, $markerPrefix, $joinCol)
    {
        // Ohne Template gibt es nichts zu tun!
        if (strlen(trim($template)) == 0)
            return '';

        // $srv = tx_cfcleague_util_ServiceRegistry::getProfileService();
        $srv = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
        $fields = $options = array();
        $fields['PROFILE.UID'][OP_IN_INT] = $team->getProperty($joinCol);
        tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->getConfigurations(), $confId . 'fields.');
        tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->getConfigurations(), $confId . 'options.');
        $children = $srv->search($fields, $options);
        if (! empty($children) && ! array_key_exists('orderby', $options)) { // Default sorting
            $children = $this->sortProfiles($children, $team->getProperty($joinCol));
        }

        $options['team'] = $team;
        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
        $out = $listBuilder->render($children, new ArrayObject(), $template, 'tx_cfcleaguefe_util_ProfileMarker', $confId, $markerPrefix, $formatter, $options);
        return $out;
    }

    /**
     * Sortiert die Profile nach der Reihenfolge im Team
     *
     * @param array $profiles
     * @param string $sortArr
     * @return array
     */
    protected function sortProfiles($profiles, $sortArr)
    {
        $sortArr = array_flip(tx_rnbase_util_Strings::intExplode(',', $sortArr));
        foreach ($profiles as $profile) {
            $sortArr[$profile->uid] = $profile;
        }
        $ret = array();
        foreach ($sortArr as $profile) {
            $ret[] = $profile;
        }
        return $ret;
    }

    /**
     * Initialisiert die Labels für die Team-Klasse
     *
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param array $defaultMarkerArr
     */
    public function initLabelMarkers(&$formatter, $confId, $defaultMarkerArr = 0, $marker = 'TEAM')
    {
        return $this->prepareLabelMarkers('tx_cfcleaguefe_models_team', $formatter, $confId, $defaultMarkerArr, $marker);
    }

    /**
     *
     * @return tx_cfcleaguefe_util_ClubMarker
     */
    private function getClubMarker()
    {
        if (! $this->clubMarker) {
            $this->clubMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
        }
        return $this->clubMarker;
    }

    /**
     * Create a marker for GoogleMaps.
     * This can be done if the team has a club with address data.
     *
     * @param string $template
     * @param tx_cfcleague_models_Team $item
     */
    public function createMapMarker($template, $item, $formatter, $confId, $markerPrefix)
    {
        $club = $item->getClub();
        if (! $club)
            return false;

        $template = $this->parseTemplate($template, $item, $formatter, $confId, $markerPrefix);
        $marker = $this->getClubMarker()->createMapMarker($template, $club, $formatter, $confId, $markerPrefix);
        return $marker;
    }

    /**
     * Links vorbereiten
     *
     * @param tx_cfcleaguefe_models_team $team
     * @param string $marker
     * @param array $markerArray
     * @param array $wrappedSubpartArray
     * @param string $confId
     * @param tx_rnbase_util_FormatUtil $formatter
     */
    protected function prepareLinks(&$team, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template)
    {
        $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array(
            'teamId' => $team->uid
        ), $template);
        if ($team->hasReport()) {
            $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showteam', $marker, array(
                'teamId' => $team->uid
            ), $template);
        } else {
            $linkId = 'showteam';
            $linkMarker = $marker . '_' . strtoupper($linkId) . 'LINK';
            $remove = intval($formatter->configurations->get($confId . 'links.' . $linkId . '.removeIfDisabled'));
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
        }
    }

    /**
     * Add dynamic defined markers for profiles
     *
     * @param string $template
     * @param tx_cfcleaguefe_models_team $team
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $listConfId
     * @param string $teamMarker
     * @return string
     */
    protected function addProfileLists($template, $team, $formatter, $listConfId, $teamMarker) {
        $configurations = $formatter->getConfigurations();
        $dynaMarkers = $configurations->getKeyNames($listConfId);
        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');


        for($i=0, $size = count($dynaMarkers); $i < $size; $i++) {
            // Prüfen ob der Marker existiert
            $markerPrefix = $teamMarker . strtoupper($dynaMarkers[$i]);
            if (!self::containsMarker($template, $markerPrefix)) {
                continue;
            }
            $confId = $listConfId.$dynaMarkers[$i] .'.';
            // Jetzt der DB Zugriff. Wir benötigen aber eigentlich nur die UIDs. Die eigentlichen Objekte
            // stehen schon im report bereit
            $srv = tx_cfcleague_util_ServiceRegistry::getProfileService();
            $fields = [];
            $options = [];
            tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $confId.'filter.fields.');
            tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $confId.'filter.options.');
            if ($this->joins($fields, 'TEAMNOTE')) {
                $fields['TEAMNOTE.TEAM'][OP_EQ_INT] = $team->getUid();
            }
            else {
                // Ohne TN müssen die Spieler des Teams gefiltert werden.
                $fields['PROFILE.UID'][OP_IN_INT] = $team->getProperty('players');
            }

            $items = $srv->search($fields, $options);
            $template = $listBuilder->render($items,
                false, $template, 'tx_cfcleaguefe_util_ProfileMarker',
                $confId.'profile.', $markerPrefix, $formatter);
        }
        return $template;
    }

    /**
     * Whether or not a given alias is used in fields
     * @param array $fields
     * @param string $alias
     * @return boolean
     */
    protected function joins($fields, $alias)
    {
        foreach ($fields As $key => $op) {
            if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr($key, $alias)) {
                return true;
            }
        }
        return false;
    }
    public function getOptions()
    {
        return $this->options;
    }
}
