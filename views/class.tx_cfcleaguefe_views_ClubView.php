<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2017 Rene Nitzsche (rene@system25.de)
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

/**
 * Viewklasse für die Anzeige des Vereins.
 */
class tx_cfcleaguefe_views_ClubView extends tx_rnbase_view_Base
{
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        $item = &$viewData->offsetGet('item');
        if (!is_object($item)) {
            return 'Sorry, no item found...';
        }

        $out = '';
        $marker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_ClubMarker');
        $out .= $marker->parseTemplate($template, $item, $formatter, 'clubview.club.', 'CLUB');

        return $out;
    }

    public function getMainSubpart(&$viewData)
    {
        return '###CLUB_VIEW###';
    }
}
