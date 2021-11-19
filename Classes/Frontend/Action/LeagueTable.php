<?php

namespace System25\T3sports\Frontend\Action;

use System25\T3sports\Table\Builder;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use tx_cfcleaguefe_util_ScopeController as ScopeController;
use Sys25\RnBase\Utility\Misc;
use Sys25\RnBase\Utility\Math;
use System25\T3sports\Model\Competition;
use tx_rnbase;
use System25\T3sports\Frontend\View\LeagueTableView;

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
 * Controller für die Anzeige eines Liga-Tabelle
 * TODO: Umstellung der Tabellenerzeugung:
 * Zur Berechnung einer Tabelle werden zunächst die gewünschten Spiele benötigt.
 * Diese können über die MatchTable bereitgestellt
 * werden. Die Spiele werden dann einem LeagueTableProvider übergeben. Dieser kann die Spiele für die berechnung der Tabelle aufbereiten.
 *
 * Dann muss über alle Spiele iteriert werden. Jedes Spiel wird einer Visitorklasse
 * übergeben, die die Punkte ermittelt. Diese Visitorklasse wird vom Wettbewerb bereitgestellt und hängt von der Sportart ab.
 */
class LeagueTable extends AbstractAction
{
    /**
     * Zeigt die Tabelle für eine Liga.
     * Die Tabelle wird nur dann berechnet, wenn auf der
     * aktuellen Seite genau ein Wettbewerb ausgewählt ist und dieser Wettbewerb eine Liga ist.
     */
    protected function handleRequest(RequestInterface $request)
    {
        $parameters = $request->getParameters();
        $configurations = $request->getConfigurations();
        if ($configurations->getBool('showLiveTable')) {
            $configurations->convertToUserInt();
        }

        // Die Werte des aktuellen Scope ermitteln
        //$scopeArr = ScopeController::handleCurrentScope($request);
        $scopeArr = ScopeController::handleCurrentScope($parameters, $configurations);
        // Hook to manipulate scopeArray
        Misc::callHook('cfc_league_fe', 'action_LeagueTable_handleScope_hook', [
            'scopeArray' => &$scopeArr,
            'parameters' => $parameters,
            'configurations' => $configurations,
            'confId' => $this->getConfId(),
        ], $this);
        $saisonUids = $scopeArr['SAISON_UIDS'];
        $groupUids = $scopeArr['GROUP_UIDS'];
        $compUids = $scopeArr['COMP_UIDS'];

        $out = ' ';
        // Sollte kein Wettbewerb ausgewählt bzw. konfiguriert worden sein, dann suchen wir eine
        // passende Liga
        if (0 == strlen($compUids)) {
            $comps = Competition::findAll($saisonUids, $groupUids, $compUids, '1');
            if (count($comps) > 0) {
                $currCompetition = $comps[0];
            }
            // Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
            // Das ist aber generell eine Fehlkonfiguration.
            else {
                return $out; // Ohne Liga keine Tabelle!
            }
        } else {
            // Wenn ein einzelner Wettbewerb ausgewählt ist, muss es eine Liga sein
            // Bei mehreren liegt es in der Verantwortungen
            if (isset($compUids) && Math::isInteger($compUids)) {
                // Wir müssen den Typ des Wettbewerbs ermitteln.
                $currCompetition = tx_rnbase::makeInstance(Competition::class, $compUids);
                if (!$currCompetition->isTypeLeague()) {
                    return $out;
                }
            }
        }

        // Okay, es ist mindestens eine Liga enthalten
        $table = Builder::buildByRequest($scopeArr, $configurations, $this->getConfId());

        $request->getViewContext()->offsetSet('table', $table); // Die Tabelle für den View bereitstellen

        return '';
    }

    public function getTemplateName()
    {
        return 'leaguetable';
    }

    public function getViewClassName()
    {
        return LeagueTableView::class;
    }
}
