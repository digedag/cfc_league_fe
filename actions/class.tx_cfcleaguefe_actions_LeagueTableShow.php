<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_models_saison');
tx_rnbase::load('tx_cfcleaguefe_models_competition');
tx_rnbase::load('tx_cfcleague_models_Group');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_cfcleaguefe_util_LeagueTable');
tx_rnbase::load('tx_rnbase_util_Math');

tx_rnbase::load('tx_rnbase_action_BaseIOC');

/**
 * Controller für die Anzeige eines Liga-Tabelle
 * TODO: Umstellung der Tabellenerzeugung:
 * Zur Berechnung einer Tabelle werden zunächst die gewünschten Spiele benötigt. Diese können über die MatchTable bereitgestellt
 * werden. Die Spiele werden dann einem LeagueTableProvider übergeben. Dieser kann die Spiele für die berechnung der Tabelle aufbereiten.
 *
 * Dann muss über alle Spiele iteriert werden. Jedes Spiel wird einer Visitorklasse
 * übergeben, die die Punkte ermittelt. Diese Visitorklasse wird vom Wettbewerb bereitgestellt und hängt von der Sportart ab.
 */
class tx_cfcleaguefe_actions_LeagueTableShow extends tx_rnbase_action_BaseIOC
{
    /**
     * Zeigt die Tabelle für eine Liga. Die Tabelle wird nur dann berechnet, wenn auf der
     * aktuellen Seite genau ein Wettbewerb ausgewählt ist und dieser Wettbewerb eine Liga ist.
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        if ($configurations->get($this->getConfId().'newMode')) {
            $action = tx_rnbase::makeInstance('tx_cfcleaguefe_actions_LeagueTable');

            return $action->handleRequest($parameters, $configurations, $viewData);
        }
        // Die Werte des aktuellen Scope ermitteln
        $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
        // Hook to manipulate scopeArray
        tx_rnbase_util_Misc::callHook(
            'cfc_league_fe',
            'action_LeagueTable_handleScope_hook',
            array('scopeArray' => &$scopeArr, 'parameters' => $parameters, 'configurations' => $configurations, 'confId' => $this->getConfId()),
            $this
        );
        $saisonUids = $scopeArr['SAISON_UIDS'];
        $groupUids = $scopeArr['GROUP_UIDS'];
        $compUids = $scopeArr['COMP_UIDS'];
        $roundUid = $scopeArr['ROUND_UIDS'];
        // TODO: der folgende Block ist für die Darstellung der Tabellenfahrt identisch und wird ggf. doppelt ausgeführt
        $out = ' ';
        // Sollte kein Wettbewerb ausgewählt bzw. konfiguriert worden sein, dann suchen wir eine
        // passende Liga
        if (0 == strlen($compUids)) {
            $comps = tx_cfcleaguefe_models_competition::findAll($saisonUids, $groupUids, $compUids, '1');
            if (count($comps) > 0) {
                $currCompetition = $comps[0];
            }
            // Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
            // Das ist aber generell eine Fehlkonfiguration.
            else {
                return $out;
            } // Ohne Liga keine Tabelle!
        } else {
            // Die Tabelle wird berechnet, wenn der aktuelle Scope auf eine Liga zeigt
            if (!(isset($compUids) && tx_rnbase_util_Math::testInt($compUids))) {
                return $out;
            }
            // Wir müssen den Typ des Wettbewerbs ermitteln.
            $currCompetition = new tx_cfcleaguefe_models_competition($compUids);
            if (!$currCompetition->isTypeLeague()) {
                return $out;
            }
        }

        // Okay, es ist eine Liga
        $dataArr = $this->buildTable($parameters, $configurations, $currCompetition, $roundUid);
        $viewData->offsetSet('tableData', $dataArr['table']); // Die Tabelle für den View bereitstellen
        $viewData->offsetSet('tablePointSystem', $dataArr['pointsystem']); // Die Tabelle für den View bereitstellen
        $viewData->offsetSet('league', $currCompetition); // Die Liga für den View bereitstellen

        // Müssen zusätzliche Selectboxen gezeigt werden?
        $this->_handleSBTableType($parameters, $configurations, $viewData);
        $this->_handleSBPointSystem($parameters, $configurations, $viewData);
        $this->_handleSBTableScope($parameters, $configurations, $viewData);

        return '';
    }

    /**
     * Sorgt bei Bedarf für die Einblendung der SelectBox für die Auswahl des Punktsystems.
     */
    protected function _handleSBPointSystem($parameters, &$configurations, &$viewData)
    {
        if ($configurations->get('pointSystemSelectionInput')) {
            // Die Daten für das Punktsystem kommen aus dem TCA der Tabelle tx_cfcleague_competition
            // Die TCA laden
            $table = 'tx_cfcleague_competition';
            tx_rnbase_util_TCA::loadTCA($table);
            //			$items = $this->translateItems($TCA[$table]['columns']['point_system']['config']['items']);
            //			$items = array(1=>0,0=>1);

            $srv = tx_cfcleague_util_ServiceRegistry::getCompetitionService();
            $systems = $srv->getPointSystems('football');
            $items = array();
            foreach ($systems as $system) {
                $items[] = $system[1];
            }

            // Wir bereiten die Selectbox vor
            $arr = array();
            $arr[0] = $items;
            $arr[1] = $viewData->offsetGet('tablePointSystem');
            $viewData->offsetSet('pointsystem_select', $arr);
            $configurations->addKeepVar('pointsystem', $arr[1]);
        }
    }

    /**
     * Sorgt bei Bedarf für die Einblendung der SelectBox für den Tabellentyp.
     */
    protected function _handleSBTableType($parameters, &$configurations, &$viewData)
    {
        if ($configurations->get('tabletypeSelectionInput')) {
            $flex = &$this->getFlexForm($configurations);
            $items = $this->translateItems($this->getItemsArrayFromFlexForm($flex, 's_leaguetable', 'tabletype'));

            // Wir bereiten die Selectbox vor
            $arr = array();
            $arr[0] = $items;
            $arr[1] = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : 0;
            $viewData->offsetSet('tabletype_select', $arr);
            $configurations->addKeepVar('tabletype', $arr[1]);
        }
    }

    /**
     * Sorgt bei Bedarf für die Einblendung der SelectBox für den Tabellenscope.
     */
    protected function _handleSBTableScope($parameters, &$configurations, &$viewData, $confId = '')
    {
        if ($configurations->get($confId.'tablescopeSelectionInput')) {
            $flex = &$this->getFlexForm($configurations);
            $items = $this->translateItems($this->getItemsArrayFromFlexForm($flex, 's_leaguetable', 'tablescope'));

            // Wir bereiten die Selectbox vor
            $arr = array();
            $arr[0] = $items;
            $arr[1] = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : 0;
            $viewData->offsetSet('tablescope_select', $arr);
            $configurations->addKeepVar('tablescope', $arr[1]);
        }
    }

    private function &getFlexForm(&$configurations)
    {
        static $flex;
        if (!is_array($flex)) {
            $flex = Tx_Rnbase_Utility_T3General::getURL(tx_rnbase_util_Extensions::extPath($configurations->getExtensionKey()).$configurations->get('flexform'));
            $flex = Tx_Rnbase_Utility_T3General::xml2array($flex);
        }

        return $flex;
    }

    /**
     * Liefert die möglichen Werte für ein Attribut aus einem FlexForm-Array.
     */
    private function getItemsArrayFromFlexForm($flexArr, $sheetName, $valueName)
    {
        return $flexArr['sheets'][$sheetName]['ROOT']['el'][$valueName]['TCEforms']['config']['items'];
    }

    private function translateItems($items)
    {
        global $TSFE;

        $ret = array();
        foreach ($items as $item) {
            $ret[$item[1]] = $TSFE->sL($item[0]);
        }

        return $ret;
    }

    /**
     * Sammelt die Daten für die Erstellung der Tabelle.
     */
    private function buildTable($parameters, &$configurations, &$league, $roundUid)
    {
        $tableProvider = tx_rnbase::makeInstance('tx_cfcleaguefe_util_league_DefaultTableProvider', $parameters, $configurations, $league);

        // Tabelle nur bis bestimmten Spieltag anzeigen
        // FIXME: Bei eine spezial-Tabelle (Hin-/Rückrunde) muss diese Option ignoriert werden
        if (intval($configurations->get('leaguetable.useRoundFromScope'))) {
            $tableProvider->setCurrentRound($roundUid);
        }

        $leagueTable = new tx_cfcleaguefe_util_LeagueTable();
        $arr = array(
            'table' => $leagueTable->generateTable($tableProvider),
            'pointsystem' => $tableProvider->cfgPointSystem,
        );

        return $arr;
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LeagueTableShow.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LeagueTableShow.php'];
}
