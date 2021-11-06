<?php

use Sys25\RnBase\Frontend\Marker\ListBuilder;
use System25\T3sports\Frontend\Marker\MatchMarkerBuilderInfo;
use tx_rnbase;

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
 * Viewklasse für die Anzeige eines Spielplans mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_MatchTable extends tx_rnbase_view_Base
{
    /**
     * Erstellung des Outputstrings.
     */
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class, tx_rnbase::makeInstance(MatchMarkerBuilderInfo::class));

        /* @var $prov tx_rnbase_util_ListProvider */
        $prov = $viewData->offsetGet('provider');
        $out = $listBuilder->renderEach($prov, $viewData, $template, 'tx_cfcleaguefe_util_MatchMarker', 'matchtable.match.', 'MATCH', $formatter);

        return $out;
    }

    public function getMainSubpart(&$viewData)
    {
        return '###MATCHTABLE###';
    }
}
