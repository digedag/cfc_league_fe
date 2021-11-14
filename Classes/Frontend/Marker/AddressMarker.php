<?php

namespace System25\T3sports\Frontend\Marker;

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\Templates;
use System25\T3sports\Model\Address;

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
 * Diese Klasse ist für die Erstellung von Markerarrays von Adressen verantwortlich.
 */
class AddressMarker extends BaseMarker
{
    /**
     * @param string $template das HTML-Template
     * @param Address $address die Adresse
     * @param FormatUtil $formatter der zu verwendente Formatter
     * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.club.'
     * @param array $links Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
     * @param string $clubMarker Name des Markers für den Club, z.B. CLUB
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
     *
     * @return string das geparste Template
     */
    public function parseTemplate($template, &$address, &$formatter, $confId, $links = 0, $addressMarker = 'ADDRESS')
    {
        if (!is_object($address)) {
            // Ist kein Verein vorhanden wird ein leeres Objekt verwendet.
            $address = self::getEmptyInstance('tx_cfcleaguefe_models_address');
        }
        $ignore = self::findUnusedCols($address->getProperty(), $template, $addressMarker);
        // Es wird das MarkerArray mit den Daten des Teams gefüllt.
        $markerArray = $formatter->getItemMarkerArrayWrapped($address->getProperty(), $confId, $ignore, $addressMarker.'_', $address->getColumnNames());
        $subpartArray = $wrappedSubpartArray = [];
        $out = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $out;
    }
}
