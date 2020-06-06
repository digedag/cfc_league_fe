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

/**
 * Viewklasse für die Anzeige des Teams.
 */
class tx_cfcleaguefe_views_TeamView extends tx_rnbase_view_Base
{
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        $team = &$viewData->offsetGet('team');
        if (is_object($team)) {
            $out = $this->_createView($template, $team, $configurations);
        } else {
            $out = 'Sorry, no team found...';
        }

        return $out;
    }

    public function getMainSubpart(&$viewData)
    {
        return '###TEAM_VIEW###';
    }

    public function _createView($template, $team, $configurations)
    {
        $out = '';

        $teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
        $out .= $teamMarker->parseTemplate($template, $team, $configurations->getFormatter(), 'teamview.team.', 'TEAM');

        return $out;
    }

    public function _init(&$configurations)
    {
        $this->formatter = &$configurations->getFormatter();
    }
}
