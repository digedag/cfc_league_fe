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
tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');

/**
 * Controller für die Anzeige eines Teams.
 */
class tx_cfcleaguefe_actions_TeamView extends tx_rnbase_action_BaseIOC
{
    /**
     * handle request.
     *
     * @param arrayobject $parameters
     * @param tx_rnbase_configurations $configurations
     * @param arrayobject $viewData
     *
     * @return string
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        $teams = array();
        // Im Flexform kann direkt ein Team ausgwählt werden
        $teamId = intval($configurations->get('teamviewTeam'));
        if (!$teamId) {
            // Alternativ ist eine Parameterübergabe möglich
            $teamId = intval($parameters->offsetGet('teamId'));
            // Wenn die TeamID über den Parameter übergeben wird, dann müssen wir sie aus den
            // Keepvars entfernen. Sonst funktionieren die Links auf den Scope nicht mehr.
            $configurations->removeKeepVar('teamId');
            $parameters->offsetUnset('teamId');
        }
        if ($teamId <= 0) {
            // Nix angegeben also über den Scope suchen
            // Die Werte des aktuellen Scope ermitteln
            $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
            $saisonUids = $scopeArr['SAISON_UIDS'];
            $groupUids = $scopeArr['GROUP_UIDS'];
            $club = $configurations->get('teamviewClub');

            // Ohne Club können wir nichts zeigen
            if (0 == intval($club)) {
                return 'Error: No club defined.';
            }

            $club = tx_rnbase::makeInstance('tx_cfcleaguefe_models_club', $club);
            $teams = $club->getTeams($saisonUids, $groupUids);
        } else {
            $team = tx_rnbase::makeInstance('tx_cfcleaguefe_models_team', $teamId);
            $teams[] = $team;
        }

        $viewData = &$configurations->getViewData();
        // Wir zeigen immer nur das erste Team im Ergebnis, selbst wenn es durch Fehlkonfiguration
        // mehrere sein sollten
        $viewData->offsetSet('team', $teams[0]);

        return null;
    }

    public function getTemplateName()
    {
        return 'teamview';
    }

    public function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_TeamView';
    }
}
