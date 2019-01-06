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
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('Tx_Rnbase_Frontend_Marker_Utility');


/**
 * Viewklasse für die Anzeige der Scope-Auswahl mit Hilfe eines HTML-Templates.
 * Die Verlinkung
 * erfolgt nicht mehr über ein HTML-Formular, sondern mit echten Links, wodurch die
 * Caching-Mechanismen von TYPO3 zur Wirkung kommen können.
 */
class tx_cfcleaguefe_views_ScopeSelection extends tx_rnbase_view_Base
{

    public function getMainSubpart(&$viewData)
    {
        return '###SCOPE_SELECTION###';
    }

    /**
     * Erstellen des Frontend-Outputs
     *
     * @param string $template
     * @param ArrayObject $viewData
     * @param tx_rnbase_configurations $configurations
     * @param tx_rnbase_util_FormatUtil $formatter
     */
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        $cObj = & $configurations->getCObj(0);

        $link = $configurations->createLink();
        $link->destination($GLOBALS['TSFE']->id); // Das Ziel der Seite vorbereiten
        $out = '';
        $markerArray = array();
        $subpartArray = array();
        $subpartArray['###SAISON_SELECTION###'] = '';
        $subpartArray['###GROUP_SELECTION###'] = '';
        $subpartArray['###COMPETITION_SELECTION###'] = '';
        $subpartArray['###ROUND_SELECTION###'] = '';
        $subpartArray['###CLUB_SELECTION###'] = '';

        // Wenn Saison gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('saison_select')) {
            // Das Template holen
            $subTemplate = $cObj->getSubpart($template, '###SAISON_SELECTION###');

            $items = $viewData->offsetGet('saison_select');
            $subpartArray['###SAISON_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'SAISON', $configurations);
        }

        // Wenn Altersklasse gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('group_select')) {
            // Das Template holen
            $subTemplate = $cObj->getSubpart($template, '###GROUP_SELECTION###');

            $items = $viewData->offsetGet('group_select');
            $subpartArray['###GROUP_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'GROUP', $configurations);
        }

        // Wenn Wettbewerb gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('competition_select')) {
            // Das Template holen
            $subTemplate = $cObj->getSubpart($template, '###COMPETITION_SELECTION###');

            $items = $viewData->offsetGet('competition_select');
            $subpartArray['###COMPETITION_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'COMPETITION', $configurations);
        }
        // Wenn Spieltag gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('round_select')) {
            // Das Template holen
            $subTemplate = $cObj->getSubpart($template, '###ROUND_SELECTION###');

            $items = $viewData->offsetGet('round_select');
            $subpartArray['###ROUND_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'ROUND', $configurations);
        }
        // Wenn Verein gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('club_select')) {
            // Das Template holen
            $subTemplate = $cObj->getSubpart($template, '###CLUB_SELECTION###');
            $items = $viewData->offsetGet('club_select');
            $subpartArray['###CLUB_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'CLUB', $configurations);
        }

        $out .= $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);

        return $out;
    }

    /**
     * Erstellt die einzelnen Teile der Scopeauswahl.
     *
     * @param string $template
     *            HTML- Template
     * @param
     *            array &$itemsArr Datensätze für die Auswahl
     * @param
     *            tx_rnbase_util_Link $link Linkobjekt
     * @param string $markerName
     *            Name des Markers (SAISON, ROUND usw.)
     * @param
     *            tx_rnbase_configurations &$configurations Config-Objekt
     */
    protected function _fillTemplate($template, &$itemsArr, $link, $markerName, $configurations)
    {
        $items = $itemsArr[0];
        $currItem = $items[$itemsArr[1]];
        $confName = strtolower($markerName); // Konvention

        // Aus den KeepVars den aktuellen Wert entfernen
        $keepVars = $configurations->getKeepVars()->getArrayCopy();
        unset($keepVars[strtolower($markerName)]);

        if ($link) {
            $token = md5(microtime());
            $link->label($token);
        }

        // Das Template für die einzelnen Datensätze
        $subTemplate = tx_rnbase_util_Templates::getSubpart($template, '###' . $markerName . '_SELECTION_2###');

        $itemConfId = 'scopeSelection.' . $confName . '.';
        $currentNoLink = intval($configurations->get($itemConfId . 'current.noLink'));

        $parts = array();
        // Jetzt über die vorhandenen Items iterieren
        foreach ($items as $item) {
            if (! is_object($item))
                continue; // Sollte eigentlich nicht vorkommen.
            $keepVars[strtolower($markerName)] = $item->uid;
            $link->parameters($keepVars);
            $isCurrent = ($item->uid == $currItem->uid);
            $item->setProperty('isCurrent', $isCurrent ? 1 : 0);

            $ignore = Tx_Rnbase_Frontend_Marker_Utility::findUnusedAttributes($item, $subTemplate, $markerName);
            $markerArray = $configurations->getFormatter()->getItemMarkerArrayWrapped($item->getProperty(), 'scopeSelection.' . $confName . '.', $ignore, $markerName . '_', $item->getColumnNames());
            $markerArray['###' . $markerName . '_LINK_URL###'] = $link->makeUrl(false);
            $linkStr = ($currentNoLink && $isCurrent) ? $token : $link->makeTag();
            // Ein zusätzliche Wrap um das generierte Element inkl. Link
            $linkStr = $configurations->getFormatter()->wrap($linkStr, 'scopeSelection.' . $confName . (($item->uid == $currItem->uid) ? '.current.' : '.normal.'));
            $subpartArray = array();
            $wrappedSubpartArray = array();
            $wrappedSubpartArray['###' . $markerName . '_LINK###'] = explode($token, $linkStr);

            $parts[] = tx_rnbase_util_Templates::substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);
            unset($keepVars[strtolower($markerName)]);
        }
        // Jetzt die einzelnen Teile zusammenfügen
        $out = implode($parts, $configurations->get('scopeSelection.' . $confName . '.implode'));

        // Im Haupttemplate stellen wir die ausgewählte Saison als Marker zur Verfügung
        $markerArray = $configurations->getFormatter()->getItemMarkerArrayWrapped($currItem->record, $itemConfId . 'current.', 0, $markerName . '_CURRENT_', $currItem->getColumnNames());
        $subpartArray['###' . $markerName . '_SELECTION_2###'] = $out;

        $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);

        return $out;
    }
}
