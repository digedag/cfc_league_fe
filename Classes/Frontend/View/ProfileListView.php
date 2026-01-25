<?php

namespace System25\T3sports\Frontend\View;

use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use System25\T3sports\Frontend\Marker\ProfileMarker;
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
 * Viewklasse für die Anzeige eines Personenprofils.
 */
class ProfileListView extends BaseView
{
    /**
     * {@inheritDoc}
     *
     * @see BaseView::createOutput()
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $markerArray = []; // Eventuell später für allgemeine Daten oder Labels
        $subpartArray = [];

        $viewData = $request->getViewContext();
        $profiles = &$viewData->offsetGet('profiles');

        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $out = $listBuilder->render($profiles, $viewData, $template, ProfileMarker::class, 'profilelist.profile.', 'PROFILE', $formatter);

        // Zum Schluß das Haupttemplate zusammenstellen
        $out = Templates::substituteMarkerArrayCached($out, $markerArray, $subpartArray); // , $wrappedSubpartArray);

        return $out;
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###PROFILE_LIST###';
    }
}
