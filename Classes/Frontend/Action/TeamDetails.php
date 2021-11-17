<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Frontend\View\TeamDetailsView;
use System25\T3sports\Model\Repository\TeamRepository;
use System25\T3sports\Model\Team;
use tx_cfcleaguefe_util_ScopeController as ScopeController;
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
 * Controller für die Anzeige eines Teams.
 */
class TeamDetails extends AbstractAction
{
    /**
     * handle request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $teams = [];
        // Im Flexform kann direkt ein Team ausgwählt werden
        $teamId = $configurations->getInt('teamviewTeam');
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
            $scopeArr = ScopeController::handleCurrentScope($parameters, $configurations);
            $saisonUids = $scopeArr['SAISON_UIDS'];
            $groupUids = $scopeArr['GROUP_UIDS'];
            $club = $configurations->get('teamviewClub');
            // Ohne Club können wir nichts zeigen
            if (0 == intval($club)) {
                return 'Error: No club defined.';
            }

            $teamRepo = new TeamRepository();
            $teams = $teamRepo->findByClubAndSaison($club, $saisonUids, $groupUids);
        } else {
            $team = tx_rnbase::makeInstance(Team::class, $teamId);
            $teams[] = $team;
        }

        $viewData = $request->getViewContext();
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
        return TeamDetailsView::class;
    }
}
