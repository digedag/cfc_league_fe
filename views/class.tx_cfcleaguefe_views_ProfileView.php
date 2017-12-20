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
tx_rnbase::load('tx_rnbase_view_Base');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');

/**
 * Viewklasse für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_views_ProfileView extends tx_rnbase_view_Base
{

    /**
     * Erstellen des Frontend-Outputs
     */
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        $cObj = & $configurations->getCObj(0);
        
        // Die ViewData bereitstellen
        $viewData = & $configurations->getViewData();
        
        $profile = & $viewData->offsetGet('profile');
        if (is_object($profile))
            $out = $this->createView($template, $profile, $configurations);
        else
            $out = 'Sorry, profile not found...';
        return $out;
    }

    protected function createView($template, $profile, $configurations)
    {
        $out = '';
        $markerOptions = array();
        $teamId = $configurations->getParameters()->getInt('team');
        if (! $teamId) {
            // Id per TS suchen
            $teamId = intval($configurations->get('profileview.staticteam'));
        }
        if ($teamId) {
            $team = tx_cfcleague_util_ServiceRegistry::getTeamService()->getTeam($teamId);
            $markerOptions['team'] = $team;
        }
        $profileMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ProfileMarker', $markerOptions);
        $out .= $profileMarker->parseTemplate($template, $profile, $configurations->getFormatter(), 'profileview.profile.');
        $profiles = $this->findNextAndPrevProfiles($profile, $markerOptions['team']);
        
        $subType = $profiles['next']->memberType == 1 ? 'coach.' : ($profiles['next']->memberType == 2 ? 'supporter.' : 'player.');
        $out = $profileMarker->parseTemplate($out, $profiles['next'], $configurations->getFormatter(), 'profileview.nextprofile.' . $subType, 'NEXTPROFILE');
        $subType = $profiles['prev']->memberType == 1 ? 'coach.' : ($profiles['prev']->memberType == 2 ? 'supporter.' : 'player.');
        $out = $profileMarker->parseTemplate($out, $profiles['prev'], $configurations->getFormatter(), 'profileview.prevprofile.' . $subType, 'PREVPROFILE');
        
        $markerArray = $subpartArray = $wrappedSubpartArray = array();
        
        if ($teamId) {
            $teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
            $out = $teamMarker->parseTemplate($out, $team, $configurations->getFormatter(), 'profileview.team.', 'TEAM');
            $wrappedSubpartArray['###TEAM###'] = array(
                '',
                ''
            );
            $wrappedSubpartArray['###PROFILEPAGER###'] = array(
                '',
                ''
            );
        } else {
            $subpartArray['###TEAM###'] = '';
            $subpartArray['###PROFILEPAGER###'] = '';
        }
        
        $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($out, $markerArray, $subpartArray, $wrappedSubpartArray);
        
        return $out;
    }

    /**
     *
     * @param tx_cfcleaguefe_models_profile $profile
     * @param tx_cfcleaguefe_models_team $team
     * @return array[tx_cfcleaguefe_models_profile]
     */
    protected function findNextAndPrevProfiles($profile, $team)
    {
        $ret = array();
        $playerIds = array();
        $coachIds = array();
        $supporterIds = array();
        
        if ($team && $team->isValid()) {
            // Alle Profile des Teams sammeln
            $teamProfiles = array();
            if ($team->getProperty('players')) {
                $playerIds = Tx_Rnbase_Utility_Strings::intExplode(',', $team->getProperty('players'));
                $teamProfiles = array_merge($teamProfiles, $playerIds);
                $playerIds = array_flip($playerIds);
            }
            if ($team->getProperty('coaches')) {
                $coachIds = Tx_Rnbase_Utility_Strings::intExplode(',', $team->getProperty('coaches'));
                $teamProfiles = array_merge($teamProfiles, $coachIds);
                $coachIds = array_flip($coachIds);
            }
            if ($team->getProperty('supporters')) {
                $supporterIds = Tx_Rnbase_Utility_Strings::intExplode(',', $team->getProperty('supporters'));
                $teamProfiles = array_merge($teamProfiles, $supporterIds);
                $supporterIds = array_flip($supporterIds);
            }
            // Das aktuelle Profil suchen
            foreach ($teamProfiles as $idx => $uid) {
                if ($uid == $profile->getUid()) {
                    // Gefunden! Was ist der Prev?
                    $prevId = $idx == 0 ? count($teamProfiles) - 1 : $idx - 1;
                    $nextId = $idx == count($teamProfiles) - 1 ? 0 : $idx + 1;
                    // TODO: In Schleife packen und den nächsten sichtbaren Link suchen.
                    $ret['prev'] = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', $teamProfiles[$prevId]);
                    $ret['next'] = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', $teamProfiles[$nextId]);
                }
            }
        }
        // Sind es Trainer oder Spieler??
        if (isset($ret['prev'])) {
            $this->setMemberType($ret['prev'], $coachIds, $supporterIds);
        } else {
            $ret['prev'] = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', 0);
        }
        if (isset($ret['next'])) {
            $this->setMemberType($ret['next'], $coachIds, $supporterIds);
        } else {
            $ret['next'] = tx_rnbase::makeInstance('tx_cfcleaguefe_models_profile', 0);
        }
        return $ret;
    }

    private function setMemberType($profile, $coachIds, $supporterIds)
    {
        $profileUid = $profile->getUid();
        $profile->memberType = 0;
        if (array_key_exists($profileUid, $coachIds)) {
            $profile->memberType = 1;
        } elseif (array_key_exists($profileUid, $supporterIds))
            $profile->memberType = 2;
    }

    public function getMainSubpart(&$viewData)
    {
        return '###PROFILE_VIEW###';
    }
}

