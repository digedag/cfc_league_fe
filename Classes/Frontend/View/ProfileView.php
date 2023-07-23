<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Frontend\Marker\ProfileMarker;
use System25\T3sports\Frontend\Marker\TeamMarker;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Team;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2023 Rene Nitzsche (rene@system25.de)
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
 * Viewklasse für die Anzeige eines Personenprofils.
 */
class ProfileView extends BaseView
{
    /**
     * Erstellen des Frontend-Outputs.
     */
    //public function createOutput($template, &$viewData, &$configurations, &$formatter)
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        $configurations = $request->getConfigurations();

        $profile = $viewData->offsetGet('profile');
        if (is_object($profile)) {
            $team = null;
            $teamId = $configurations->getParameters()->getInt('team');
            if (!$teamId) {
                // Id per TS suchen
                $teamId = $configurations->getInt('profileview.staticteam');
            }
            if ($teamId) {
                $team = ServiceRegistry::getTeamService()->getTeam($teamId);
            }

            $out = $this->createView($template, $profile, $team, $configurations);
        } else {
            $out = 'Sorry, profile not found...';
        }

        return $out;
    }

    protected function createView($template, Profile $profile, ?Team $team, ConfigurationInterface $configurations)
    {
        $out = '';
        $markerOptions = [];
        $markerOptions['team'] = $team;

        /** @var ProfileMarker $profileMarker */
        $profileMarker = tx_rnbase::makeInstance(ProfileMarker::class, $markerOptions);
        $out .= $profileMarker->parseTemplate($template, $profile, $configurations->getFormatter(), 'profileview.profile.', 'PROFILE');
        $profiles = $this->findNextAndPrevProfiles($profile, $markerOptions['team']);

        $subType = 1 == $profiles['next']->memberType ? 'coach.' : (2 == $profiles['next']->memberType ? 'supporter.' : 'player.');
        $out = $profileMarker->parseTemplate($out, $profiles['next'], $configurations->getFormatter(), 'profileview.nextprofile.'.$subType, 'NEXTPROFILE');
        $subType = 1 == $profiles['prev']->memberType ? 'coach.' : (2 == $profiles['prev']->memberType ? 'supporter.' : 'player.');
        $out = $profileMarker->parseTemplate($out, $profiles['prev'], $configurations->getFormatter(), 'profileview.prevprofile.'.$subType, 'PREVPROFILE');

        $markerArray = $subpartArray = $wrappedSubpartArray = [];

        if ($team) {
            $teamMarker = tx_rnbase::makeInstance(TeamMarker::class);
            $out = $teamMarker->parseTemplate($out, $team, $configurations->getFormatter(), 'profileview.team.', 'TEAM');
            $wrappedSubpartArray['###TEAM###'] = [
                '',
                '',
            ];
            $wrappedSubpartArray['###PROFILEPAGER###'] = [
                '',
                '',
            ];
        } else {
            $subpartArray['###TEAM###'] = '';
            $subpartArray['###PROFILEPAGER###'] = '';
        }

        $out = Templates::substituteMarkerArrayCached($out, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $out;
    }

    /**
     * @param Profile $profile
     * @param Team $team
     *
     * @return Profile[]
     */
    protected function findNextAndPrevProfiles(Profile $profile, ?Team $team)
    {
        $ret = [];
        $playerIds = [];
        $coachIds = [];
        $supporterIds = [];

        if ($team && $team->isValid()) {
            // Alle Profile des Teams sammeln
            $teamProfiles = [];
            if ($team->getProperty('players')) {
                $playerIds = Strings::intExplode(',', $team->getProperty('players'));
                $teamProfiles = array_merge($teamProfiles, $playerIds);
                $playerIds = array_flip($playerIds);
            }
            if ($team->getProperty('coaches')) {
                $coachIds = Strings::intExplode(',', $team->getProperty('coaches'));
                $teamProfiles = array_merge($teamProfiles, $coachIds);
                $coachIds = array_flip($coachIds);
            }
            if ($team->getProperty('supporters')) {
                $supporterIds = Strings::intExplode(',', $team->getProperty('supporters'));
                $teamProfiles = array_merge($teamProfiles, $supporterIds);
                $supporterIds = array_flip($supporterIds);
            }
            // Das aktuelle Profil suchen
            foreach ($teamProfiles as $idx => $uid) {
                if ($uid == $profile->getUid()) {
                    // Gefunden! Was ist der Prev?
                    $prevId = 0 == $idx ? count($teamProfiles) - 1 : $idx - 1;
                    $nextId = $idx == count($teamProfiles) - 1 ? 0 : $idx + 1;
                    // TODO: In Schleife packen und den nächsten sichtbaren Link suchen.
                    $ret['prev'] = tx_rnbase::makeInstance(Profile::class, $teamProfiles[$prevId]);
                    $ret['next'] = tx_rnbase::makeInstance(Profile::class, $teamProfiles[$nextId]);
                }
            }
        }
        // Sind es Trainer oder Spieler??
        if (isset($ret['prev'])) {
            $this->setMemberType($ret['prev'], $coachIds, $supporterIds);
        } else {
            $ret['prev'] = tx_rnbase::makeInstance(Profile::class, 0);
        }
        if (isset($ret['next'])) {
            $this->setMemberType($ret['next'], $coachIds, $supporterIds);
        } else {
            $ret['next'] = tx_rnbase::makeInstance(Profile::class, 0);
        }

        return $ret;
    }

    private function setMemberType(Profile $profile, $coachIds, $supporterIds)
    {
        $profileUid = $profile->getUid();
        $profile->memberType = 0;
        if (array_key_exists($profileUid, $coachIds)) {
            $profile->memberType = 1;
        } elseif (array_key_exists($profileUid, $supporterIds)) {
            $profile->memberType = 2;
        }
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###PROFILE_VIEW###';
    }
}
