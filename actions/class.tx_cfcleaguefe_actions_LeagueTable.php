<?php
use System25\T3sports\Table\Builder;

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
tx_rnbase::load('tx_cfcleague_models_Competition');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');

tx_rnbase::load('tx_rnbase_util_Math');
tx_rnbase::load('tx_rnbase_action_BaseIOC');

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
class tx_cfcleaguefe_actions_LeagueTable extends tx_rnbase_action_BaseIOC
{
    /**
     * Zeigt die Tabelle für eine Liga.
     * Die Tabelle wird nur dann berechnet, wenn auf der
     * aktuellen Seite genau ein Wettbewerb ausgewählt ist und dieser Wettbewerb eine Liga ist.
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        if ($configurations->getBool('showLiveTable')) {
            $configurations->convertToUserInt();
        }

        // Die Werte des aktuellen Scope ermitteln
        $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
        // Hook to manipulate scopeArray
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'action_LeagueTable_handleScope_hook', array(
            'scopeArray' => &$scopeArr,
            'parameters' => $parameters,
            'configurations' => $configurations,
            'confId' => $this->getConfId(),
        ), $this);
        $saisonUids = $scopeArr['SAISON_UIDS'];
        $groupUids = $scopeArr['GROUP_UIDS'];
        $compUids = $scopeArr['COMP_UIDS'];

        $out = ' ';
        // Sollte kein Wettbewerb ausgewählt bzw. konfiguriert worden sein, dann suchen wir eine
        // passende Liga
        if (0 == strlen($compUids)) {
            $comps = tx_cfcleague_models_Competition::findAll($saisonUids, $groupUids, $compUids, '1');
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
            if (isset($compUids) && tx_rnbase_util_Math::testInt($compUids)) {
                // Wir müssen den Typ des Wettbewerbs ermitteln.
                $currCompetition = tx_rnbase::makeInstance('tx_cfcleague_models_competition', $compUids);
                if (!$currCompetition->isTypeLeague()) {
                    return $out;
                }
            }
        }

        // Okay, es ist mindestens eine Liga enthalten
        $table = Builder::buildByRequest($scopeArr, $configurations, $this->getConfId());

        $viewData->offsetSet('table', $table); // Die Tabelle für den View bereitstellen

        return '';
    }

    public function getTemplateName()
    {
        return 'leaguetable';
    }

    public function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_LeagueTable';
    }
}
