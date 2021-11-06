<?php

namespace System25\T3sports\Frontend\Marker;

use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\SimpleMarker;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Decorator\TeamNoteDecorator;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\TeamNoteRepository;

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
 * Diese Klasse ist für die Erstellung von Markerarrays für Profile verantwortlich.
 */
class ProfileMarker extends SimpleMarker
{
    private $options;
    private $tnDecorator;

    /**
     * Initialisiert den Marker Array.
     */
    public function __construct(&$options = [])
    {
        $this->options = $options;
        $this->tnDecorator = new TeamNoteDecorator(new TeamNoteRepository());
        $this->setClassname('tx_cfcleaguefe_models_profile');
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Initialisiert die Labels für die Profile-Klasse.
     *
     * @param FormatUtil $formatter
     * @param array $defaultMarkerArr
     */
    public function initLabelMarkers(FormatUtil $formatter, $profileConfId, $defaultMarkerArr = 0, $profileMarker = 'PROFILE')
    {
        return $this->prepareLabelMarkers(Profile::class, $formatter, $profileConfId, $defaultMarkerArr, $profileMarker);
    }

    /**
     * @param string $template das HTML-Template
     * @param Profile $profile das Profil
     * @param FormatUtil $formatter
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
        $this->tnDecorator->addTeamNotes($profile, $this->options['team']);
//        $profile->addTeamNotes($this->options['team']);
        $profile->setProperty('firstpicture', $profile->getProperty('t3images'));
        $profile->setProperty('pictures', $profile->getProperty('t3images'));

        Misc::callHook('cfc_league_fe', 'profileMarker_initRecord', [
            'item' => &$profile,
            'template' => &$template,
        ], $this);

        $signs = \System25\T3sports\Utility\Signs::getInstance();
        $birthday = $profile->getProperty('birthday');
        $profile->setProperty('sign', 0 !== intval($birthday) ? $signs->getSign($birthday) : '');

        return $template;
    }

    /**
     * {@inheritdoc}
     *
     * @see SimpleMarker::finishTemplate()
     */
    protected function finishTemplate($template, $item, $formatter, $confId, $marker)
    {
        Misc::callHook('cfc_league_fe', 'profileMarker_afterSubst', [
            'item' => $item,
            'template' => &$template,
            'confId' => $confId,
            'marker' => $marker,
            'conf' => $formatter->getConfigurations(),
        ], $this);

        return $template;
    }

    /**
     * Links vorbereiten.
     *
     * @param Profile $profile
     * @param string $marker
     * @param array $markerArray
     * @param array $wrappedSubpartArray
     * @param string $confId
     * @param FormatUtil $formatter
     */
    protected function prepareLinks(Profile $profile, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, $formatter, $template)
    {
        parent::prepareLinks($profile, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

        // $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showmatchtable', $marker, array('teamId' => $team->uid));
        if ($profile->hasReport()) {
            $params = [
                'profileId' => $profile->getUid(),
            ];
            if (is_object($this->options['team'])) {
                // Transfer current team to profile view
                $params['team'] = $this->options['team']->getUid();
            }
            $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'showprofile', $marker, $params, $template);
        } else {
            $linkMarker = $marker.'_'.strtoupper('showprofile').'LINK';
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, false);
        }
        $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'refereematches', $marker, [
            'refereeId' => $profile->getUid(),
        ], $template);
    }
}
