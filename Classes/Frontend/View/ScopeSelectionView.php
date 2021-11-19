<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Utility\TYPO3;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Utility\Link;
use Sys25\RnBase\Frontend\Marker\MarkerUtility;
use Sys25\RnBase\Frontend\View\ContextInterface;

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
 * Viewklasse für die Anzeige der Scope-Auswahl mit Hilfe eines HTML-Templates.
 * Die Verlinkung
 * erfolgt nicht mehr über ein HTML-Formular, sondern mit echten Links, wodurch die
 * Caching-Mechanismen von TYPO3 zur Wirkung kommen können.
 */
class ScopeSelectionView extends BaseView
{

    /**
     * Erstellen des Frontend-Outputs.
     *
     * @param string $template
     * @param RequestInterface $request
     * @param FormatUtil $formatter
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $configurations = $request->getConfigurations();
//        $viewData = $request->getViewContext();
        $viewData = $configurations->getViewData();
        $link = $configurations->createLink();
        $link->destination(TYPO3::getTSFE()->id); // Das Ziel der Seite vorbereiten

        $markerArray = $subpartArray = [];
        $subpartArray['###SAISON_SELECTION###'] = '';
        $subpartArray['###GROUP_SELECTION###'] = '';
        $subpartArray['###COMPETITION_SELECTION###'] = '';
        $subpartArray['###ROUND_SELECTION###'] = '';
        $subpartArray['###CLUB_SELECTION###'] = '';

        // Wenn Saison gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('saison_select')) {
            // Das Template holen
            $subTemplate = Templates::getSubpart($template, '###SAISON_SELECTION###');

            $items = $viewData->offsetGet('saison_select');
            $subpartArray['###SAISON_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'SAISON', $configurations);
        }

        // Wenn Altersklasse gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('group_select')) {
            // Das Template holen
            $subTemplate = Templates::getSubpart($template, '###GROUP_SELECTION###');

            $items = $viewData->offsetGet('group_select');
            $subpartArray['###GROUP_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'GROUP', $configurations);
        }

        // Wenn Wettbewerb gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('competition_select')) {
            // Das Template holen
            $subTemplate = Templates::getSubpart($template, '###COMPETITION_SELECTION###');

            $items = $viewData->offsetGet('competition_select');
            $subpartArray['###COMPETITION_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'COMPETITION', $configurations);
        }
        // Wenn Spieltag gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('round_select')) {
            // Das Template holen
            $subTemplate = Templates::getSubpart($template, '###ROUND_SELECTION###');

            $items = $viewData->offsetGet('round_select');
            $subpartArray['###ROUND_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'ROUND', $configurations);
        }
        // Wenn Verein gezeigt werden soll, dann Abschnitt erstellen
        if ($viewData->offsetGet('club_select')) {
            // Das Template holen
            $subTemplate = Templates::getSubpart($template, '###CLUB_SELECTION###');
            $items = $viewData->offsetGet('club_select');
            $subpartArray['###CLUB_SELECTION###'] = $this->_fillTemplate($subTemplate, $items, $link, 'CLUB', $configurations);
        }

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    }

    /**
     * Erstellt die einzelnen Teile der Scopeauswahl.
     *
     * @param string $template HTML- Template
     * @param array &$itemsArr Datensätze für die Auswahl
     * @param Link $link Linkobjekt
     * @param string $markerName Name des Markers (SAISON, ROUND usw.)
     * @param ConfigurationInterface $configurations Config-Objekt
     */
    protected function _fillTemplate($template, &$itemsArr, Link $link, $markerName, ConfigurationInterface $configurations)
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
        $subTemplate = Templates::getSubpart($template, '###'.$markerName.'_SELECTION_2###');

        $itemConfId = 'scopeSelection.'.$confName.'.';
        $currentNoLink = $configurations->getBool($itemConfId.'current.noLink');

        $parts = [];
        // Jetzt über die vorhandenen Items iterieren
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            } // Sollte eigentlich nicht vorkommen.
            $keepVars[strtolower($markerName)] = $item->getUid();
            $link->parameters($keepVars);
            $isCurrent = ($item->getUid() == $currItem->getUid());
            $item->setProperty('isCurrent', $isCurrent ? 1 : 0);

            $ignore = MarkerUtility::findUnusedAttributes($item, $subTemplate, $markerName);
            $markerArray = $configurations->getFormatter()->getItemMarkerArrayWrapped($item->getProperty(), 'scopeSelection.'.$confName.'.', $ignore, $markerName.'_', $item->getColumnNames());
            $markerArray['###'.$markerName.'_LINK_URL###'] = $link->makeUrl(false);
            $linkStr = ($currentNoLink && $isCurrent) ? $token : $link->makeTag();
            // Ein zusätzliche Wrap um das generierte Element inkl. Link
            $linkStr = $configurations->getFormatter()->wrap($linkStr, 'scopeSelection.'.$confName.(($item->getUid() == $currItem->getUid()) ? '.current.' : '.normal.'));

            $subpartArray = $wrappedSubpartArray = [];
            $wrappedSubpartArray['###'.$markerName.'_LINK###'] = explode($token, $linkStr);

            $parts[] = Templates::substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);
            unset($keepVars[strtolower($markerName)]);
        }
        // Jetzt die einzelnen Teile zusammenfügen
        $out = implode($parts, $configurations->get('scopeSelection.'.$confName.'.implode'));

        // Im Haupttemplate stellen wir die ausgewählte Saison als Marker zur Verfügung
        $markerArray = $configurations->getFormatter()->getItemMarkerArrayWrapped($currItem->getProperty(), $itemConfId.'current.', 0, $markerName.'_CURRENT_', $currItem->getColumnNames());
        $subpartArray['###'.$markerName.'_SELECTION_2###'] = $out;

        return Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray);
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###SCOPE_SELECTION###';
    }
}
