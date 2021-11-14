<?php

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\Templates;
use System25\T3sports\Model\Competition;
use tx_cfcleaguefe_util_GroupMarker as GroupMarker;

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
tx_rnbase::load('tx_rnbase_util_BaseMarker');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays der Wettbewerbe verantwortlich.
 */
class tx_cfcleaguefe_util_CompetitionMarker extends BaseMarker
{
    /**
     * @param string $template das HTML-Template
     * @param Competition $competition der Wettbewerb
     * @param FormatUtil $formatter der zu verwendente Formatter
     * @param string $confId Pfad der TS-Config des Vereins, z.B. 'matchtable.match.competition.'
     * @param string $marker Name des Markers für den Wettbewerb, z.B. COMPETITION
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###COMPETITION_NAME###
     *
     * @return string das geparste Template
     */
    public function parseTemplate($template, &$competition, &$formatter, $confId, $marker = 'COMPETITION')
    {
        if (!is_object($competition)) {
            // Ist kein Wettbewerb vorhanden wird ein leeres Objekt verwendet.
            $competition = self::getEmptyInstance(Competition::class);
        }
        // Es wird das MarkerArray mit Daten gefüllt.
        $ignore = self::findUnusedCols($competition->getProperty(), $template, $marker);
        $markerArray = $formatter->getItemMarkerArrayWrapped($competition->getProperty(), $confId, $ignore, $marker.'_', $competition->getColumnNames());
        $subpartArray = $wrappedSubpartArray = [];
        $template = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

        if ($this->containsMarker($template, $marker.'_GROUP')) {
            $template = $this->getGroupMarker()->parseTemplate($template, $competition->getGroup(), $formatter, $confId.'group.', $marker.'_GROUP');
        }

        return $template;
    }

    /**
     * @return tx_cfcleaguefe_util_GroupMarker
     */
    private function getGroupMarker()
    {
        if (!is_object($this->groupMarker)) {
            $this->groupMarker = tx_rnbase::makeInstance(GroupMarker::class);
        }

        return $this->groupMarker;
    }
}
