<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Backend\Utility\TCA;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Utility\Extensions;
use Sys25\RnBase\Utility\T3General;
use System25\T3sports\Table\Builder;
use System25\T3sports\Utility\ScopeController;
use System25\T3sports\Utility\ServiceRegistry;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2022 Rene Nitzsche (rene@system25.de)
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
 * Controller für die Anzeige eines unbegrenzten Liga-Tabelle
 * TODO: Controller für Hin-Rückrunde entfernen.
 */
class LeagueTableAllTime extends LeagueTable
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
        // Die Werte des aktuellen Scope ermitteln
        $scopeArr = ScopeController::handleCurrentScope($parameters, $configurations);

        // Okay, es ist mindestens eine Liga enthalten
        $table = Builder::buildByRequest($scopeArr, $configurations, $this->getConfId());

        $viewData = $request->getViewContext();
        $viewData->offsetSet('table', $table); // Die Tabelle für den View bereitstellen

        // Müssen zusätzliche Selectboxen gezeigt werden?
        $this->_handleSBTableType($parameters, $configurations, $viewData);
        $this->_handleSBPointSystem($parameters, $configurations, $viewData);
        $this->_handleSBTableScope($parameters, $configurations, $viewData, 'leaguetableAllTime');

        return '';
    }

    public function getTemplateName()
    {
        return 'leaguetableAllTime';
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
            TCA::loadTCA($table);
            //			$items = $this->translateItems($TCA[$table]['columns']['point_system']['config']['items']);
            //			$items = array(1=>0,0=>1);

            $srv = ServiceRegistry::getCompetitionService();
            $systems = $srv->getPointSystems('football');
            $items = [];
            foreach ($systems as $system) {
                $items[] = $system[1];
            }

            // Wir bereiten die Selectbox vor
            $arr = [];
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
            $arr = [];
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
            $arr = [];
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
            $flex = T3General::getURL(Extensions::extPath($configurations->getExtensionKey()).$configurations->get('flexform'));
            $flex = T3General::xml2array($flex);
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

        $ret = [];
        foreach ($items as $item) {
            $ret[$item[1]] = $TSFE->sL($item[0]);
        }

        return $ret;
    }
}
