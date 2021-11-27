<?php

namespace System25\T3sports\Frontend\View;

use Exception;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Maps\Factory;
use System25\T3sports\Utility\MapsUtil;
use tx_cfcleaguefe_util_TeamMarker as TeamMarker;
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
 * Viewklasse für die Anzeige einer Teamliste.
 * Isn't it tiny! ;-).
 */
class TeamListView extends BaseView
{
    /**
     * Erstellen des Frontend-Outputs.
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        // Die ViewData bereitstellen
        $teams = $viewData->offsetGet('teams');
        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');

        $template = $listBuilder->render($teams, $viewData, $template, TeamMarker::class, 'teamlist.team.', 'TEAM', $formatter);
        if (BaseMarker::containsMarker($template, 'TEAMMAP')) {
            $markerArray['###TEAMMAP###'] = $this->getMap($teams, $request->getConfigurations(), $request
                ->getConfId().'map.', 'TEAM');
        }

        $out = Templates::substituteMarkerArrayCached($template, $markerArray); // , $wrappedSubpartArray);

        return $out;
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###TEAM_LIST###';
    }

    private function getMap($items, $configurations, $confId, $markerPrefix)
    {
        $ret = '###LABEL_mapNotAvailable###';

        try {
            $map = Factory::createGoogleMap($configurations, $confId);

            $template = MapsUtil::getMapTemplate($configurations, $confId, '###TEAM_MAP_MARKER###');
            $itemMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
            foreach ($items as $item) {
                $marker = $itemMarker->createMapMarker($template, $item, $configurations->getFormatter(), $confId.'team.', $markerPrefix);
                if (!$marker) {
                    continue;
                }
                MapsUtil::addIcon($map, $configurations, $this->getController()->getConfId().'map.icon.teamlogo.', $marker, 'team_'.$item->getUid(), $item->getLogoPath());
                $map->addMarker($marker);
            }
            $ret = $map->draw();
        } catch (Exception $e) {
            $ret = '###LABEL_mapNotAvailable###';
        }

        return $ret;
    }
}
