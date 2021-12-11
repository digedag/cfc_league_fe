<?php

namespace System25\T3sports\Frontend\Marker;

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Model\Group;

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
 * Diese Klasse ist für die Erstellung von Markerarrays der Altersgruppen verantwortlich.
 */
class GroupMarker extends BaseMarker
{
    /**
     * @param string $template das HTML-Template
     * @param Group $group die Altersklasse
     * @param FormatUtil $formatter der zu verwendente Formatter
     * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.group.'
     * @param string $marker Name des Markers
     *
     * @return string das geparste Template
     */
    public function parseTemplate($template, $item, $formatter, $confId, $marker = 'GROUP')
    {
        if (!is_object($item)) {
            // Ist kein Objekt vorhanden wird ein leeres Objekt verwendet.
            $item = self::getEmptyInstance(Group::class);
        }
        Misc::callHook('cfc_league_fe', 'groupMarker_initRecord', [
            'item' => $item,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter,
        ], $this);
        // Es wird das MarkerArray mit den Daten des Records gefüllt.
        $markerArray = $formatter->getItemMarkerArrayWrapped($item->getProperty(), $confId, 0, $marker.'_', $item->getColumnNames());
        $subpartArray = $wrappedSubpartArray = [];
        $template = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

        Misc::callHook('cfc_league_fe', 'groupMarker_afterSubst', [
            'item' => $item,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter,
        ], $this);

        return $template;
    }
}
