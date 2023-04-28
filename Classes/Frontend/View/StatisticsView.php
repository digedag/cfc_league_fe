<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Utility\Misc;
use Sys25\RnBase\Utility\T3General;
use System25\T3sports\Statistics\Statistics;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2023 Rene Nitzsche (rene@system25.de)
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
 * Viewklasse für die Anzeige der Statistiken.
 */
class StatisticsView extends BaseView
{
    /**
     * Create fe output.
     *
     * @param string $template
     * @param RequestInterface $request
     * @param FormatUtil $formatter
     *
     * @return string
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $configurations = $request->getConfigurations();
        $data = $request->getViewContext()->offsetGet('data');
        if (empty($data)) {
            return $template;
        } // ohne Daten gibt's keine Marker

        // Jetzt über die einzelnen Statistiken iterieren
        $markerArray = [];
        $subpartArray = $this->_getSubpartArray($configurations);
        $services = Misc::lookupServices('cfcleague_statistics');
        foreach ($services as $subtype => $info) {
            // Init all stats with empty subpart
            $subpartArray['###STATISTIC_'.strtoupper($subtype).'###'] = '';
        }

        foreach ($data as $type => $stats) {
            $service = T3General::makeInstanceService('cfcleague_statistics', $type);
            if (!is_object($service)) { // Ohne den Service geht nix
                continue;
            }
            $srvTemplate = Templates::getSubpart($template, '###STATISTIC_'.strtoupper($type).'###');
            // Der Service muss jetzt den Marker liefert
            $srvMarker = $service->getMarker($configurations);
            $subpartArray['###STATISTIC_'.strtoupper($type).'###'] = $srvMarker->parseTemplate($srvTemplate, $stats, $configurations->getFormatter(), 'statistics.'.$type.'.', strtoupper($type));
        }
        $out = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray); // , $wrappedSubpartArray);

        return $out;
    }

    /**
     * Erstellt das initiale Subpart-Array.
     * Alle möglichen Servicetemplates sind
     * bereits leer enthalten.
     *
     * @param ConfigurationInterface $configurations
     *
     * @return array
     */
    protected function _getSubpartArray(ConfigurationInterface $configurations)
    {
        $ret = [];

        $cfg = [];
        $types = Statistics::lookupStatistics($cfg);
        $types = $types['items'];

        foreach ($types as $type) {
            $ret['###STATISTIC_'.strtoupper($type[1]).'###'] = '';
        }

        return $ret;
    }

    protected function _getItemsArrayFromFlexForm($flexArr, $sheetName, $valueName)
    {
        return $flexArr['sheets'][$sheetName]['ROOT']['el'][$valueName]['TCEforms']['config']['items'];
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###STATISTICS###';
    }
}
