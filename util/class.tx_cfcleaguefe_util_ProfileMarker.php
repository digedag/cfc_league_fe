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

/**
 * Diese Klasse ist für die Erstellung von Markerarrays für Profile verantwortlich.
 */
class tx_cfcleaguefe_util_ProfileMarker extends tx_rnbase_util_SimpleMarker
{
    private $options;

    /**
     * Initialisiert den Marker Array.
     */
    public function __construct(&$options = array())
    {
        $this->options = $options;
        $this->setClassname('tx_cfcleaguefe_models_profile');
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Initialisiert die Labels für die Profile-Klasse.
     *
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param array $defaultMarkerArr
     */
    public function initLabelMarkers(&$formatter, $profileConfId, $defaultMarkerArr = 0, $profileMarker = 'PROFILE')
    {
        return $this->prepareLabelMarkers('tx_cfcleaguefe_models_profile', $formatter, $profileConfId, $defaultMarkerArr, $profileMarker);
    }

    /**
     * @param string $template das HTML-Template
     * @param tx_cfcleaguefe_models_profile $profile
     *            das Profil
     * @param tx_rnbase_util_FormatUtil $formatter
     *            der zu verwendente Formatter
     * @param string $confId Pfad
     *            der TS-Config des Profils, z.B. 'listView.profile.'
     * @param string $marker Name
     *            des Markers für ein Profil, z.B. PROFILE, COACH, SUPPORTER
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###COACH_SIGN###, ###COACH_LINK###
     *
     * @return string das geparste Template
     */
    protected function prepareTemplate($template, $profile, $formatter, $confId, $marker = 'PROFILE')
    {
        $profile->addTeamNotes($this->options['team']);
        $profile->setProperty('firstpicture', $profile->getProperty('t3images'));
        $profile->setProperty('pictures', $profile->getProperty('t3images'));

        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'profileMarker_initRecord', array(
            'item' => &$profile,
            'template' => &$template,
        ), $this);

        $profile->setProperty('sign', $profile->getSign());

        return $template;
    }

    /**
     * {@inheritdoc}
     *
     * @see tx_rnbase_util_SimpleMarker::finishTemplate()
     */
    protected function finishTemplate($template, $item, $formatter, $confId, $marker)
    {
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'profileMarker_afterSubst', array(
            'item' => $item,
            'template' => &$template,
            'confId' => $confId,
            'marker' => $marker,
            'conf' => $formatter->getConfigurations(),
        ), $this);

        return $template;
    }

    /**
     * Links vorbereiten.
     *
     * @param tx_cfcleaguefe_models_profile $profile
     * @param string $marker
     * @param array $markerArray
     * @param array $wrappedSubpartArray
     * @param string $confId
     * @param tx_rnbase_util_FormatUtil $formatter
     */
    protected function prepareLinks($profile, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, $formatter, $template)
    {
        parent::prepareLinks($profile, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

        // $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid));
        if ($profile->hasReport()) {
            $params = array(
                'profileId' => $profile->getUid(),
            );
            if (is_object($this->options['team'])) {
                // Transfer current team to profile view
                $params['team'] = $this->options['team']->getUid();
            }
            $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showprofile', $marker, $params, $template);
        } else {
            $linkMarker = $marker.'_'.strtoupper('showprofile').'LINK';
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, false);
        }
        $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'refereematches', $marker, array(
            'refereeId' => $profile->getUid(),
        ), $template);
    }
}
