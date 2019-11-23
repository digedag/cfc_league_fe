<?php
namespace System25\T3sports\Statistics;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2007-2019 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

/**
 * Marker class for player summary statistics
 *
 * @author Rene Nitzsche
 */
class PlayerSummaryStatisticsMarker extends \tx_rnbase_util_BaseMarker
{

    function parseTemplate($srvTemplate, &$stats, &$formatter, $statsConfId, $statsMarker)
    {
        $labelArr = $this->initTSLabelMarkers($formatter, $statsConfId, $statsMarker);
        $markerArray = $formatter->getItemMarkerArrayWrapped($stats, $statsConfId, 0, $statsMarker . '_');
        $markerArray = array_merge($markerArray, $labelArr);
        return $formatter->cObj->substituteMarkerArrayCached($srvTemplate, $markerArray, $subpartArray);
    }
}
