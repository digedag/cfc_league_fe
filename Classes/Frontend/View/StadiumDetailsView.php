<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use System25\T3sports\Frontend\Marker\StadiumMarker;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2021 Rene Nitzsche (rene@system25.de)
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
 * Viewklasse für die Anzeige des Stadien.
 */
class StadiumDetailsView extends BaseView
{
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();

        $item = &$viewData->offsetGet('item');
        if (!is_object($item)) {
            return 'Sorry, no item found...';
        }

        $out = '';
        $marker = tx_rnbase::makeInstance(StadiumMarker::class);
        $out .= $marker->parseTemplate($template, $item, $formatter, 'stadiumview.stadium.', 'STADIUM');

        return $out;
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###STADIUM_VIEW###';
    }
}
