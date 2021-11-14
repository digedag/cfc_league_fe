<?php

use Sys25\RnBase\Utility\Math;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Competition;
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

/**
 * Controller für die Anzeige einer Tabellenfahrt.
 */
class tx_cfcleaguefe_actions_TableChart extends tx_rnbase_action_BaseIOC
{
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        // Die Werte des aktuellen Scope ermitteln
        $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
        // Hook to manipulate scopeArray
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'action_TableChart_handleScope_hook', [
            'scopeArray' => &$scopeArr,
            'parameters' => $parameters,
            'configurations' => $configurations,
            'confId' => $this->getConfId(),
        ], $this);
        $saisonUids = $scopeArr['SAISON_UIDS'];
        $groupUids = $scopeArr['GROUP_UIDS'];
        $compUids = $scopeArr['COMP_UIDS'];

        $out = '';
        // Sollte kein Wettbewerb ausgewählt bzw. konfiguriert worden sein, dann suchen wir eine
        // passende Liga
        if (0 == strlen($compUids)) {
            $comps = Competition::findAll($saisonUids, $groupUids, $compUids, '1');
            if (count($comps) > 0) {
                $currCompetition = $comps[0];
            // Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
                // Das ist aber generell eine Fehlkonfiguration.
            } else {
                return $out;
            } // Ohne Liga keine Tabelle!
        } else {
            // Die Tabelle wird berechnet, wenn der aktuelle Scope auf eine Liga zeigt
            if (!(isset($compUids) && Math::isInteger($compUids))) {
                return $out;
            }
            // Wir müssen den Typ des Wettbewerbs ermitteln.
            $currCompetition = tx_rnbase::makeInstance(Competition::class, $compUids);
            if (!$currCompetition->isTypeLeague()) {
                return $out;
            }
        }

        $mode = $configurations->get($this->getConfId().'library');

        $chartData = $this->prepareChartData($scopeArr, $configurations, $this->getConfId());
        $viewData->offsetSet('json', $chartData);
    }

    /**
     * @param array $scopeArr
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     *
     * @return multitype:number NULL
     */
    protected function prepareChartData($scopeArr, $configurations, $confId)
    {
        $table = Builder::buildByRequest($scopeArr, $configurations, $this->getConfId());

        $builder = tx_rnbase::makeInstance(System25\T3sports\Chart\ChartBuilder::class);

        return $builder->buildJson($table, $this->getChartClubs(), $configurations, $confId);
    }

    protected function getChartClubs()
    {
        return Strings::intExplode(',', $this->getConfigurations()->get($this->getConfId().'chartClubs'));
    }

    public function getTemplateName()
    {
        return 'tablechart';
    }

    public function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_TableChart';
    }
}
