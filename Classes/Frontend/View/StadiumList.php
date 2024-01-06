<?php

namespace System25\T3sports\Frontend\View;

use Exception;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Maps\Coord;
use Sys25\RnBase\Maps\DefaultMarker;
use Sys25\RnBase\Maps\Factory;
use System25\T3sports\Frontend\Marker\StadiumMarker;
use System25\T3sports\Utility\MapsUtil;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2022 Rene Nitzsche (rene@system25.de)
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
 * Viewklasse für die Anzeige einer Stadionliste.
 */
class StadiumList extends BaseView
{
    /**
     * Erstellen des Frontend-Outputs.
     */
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        $configurations = $request->getConfigurations();
        // Die ViewData bereitstellen
        $items = &$viewData->offsetGet('items');
        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);

        $template = $listBuilder->render($items, $viewData, $template, StadiumMarker::class, $request
            ->getConfId().'stadium.', 'STADIUM', $formatter);

        $markerArray = [];
        if (BaseMarker::containsMarker($template, 'STADIUMMAP')) {
            $markerArray['###STADIUMMAP###'] = $this->getMap($items, $configurations, $request
                ->getConfId().'map.', 'STADIUM');
        }
        $out = Templates::substituteMarkerArrayCached($template, $markerArray); // , $wrappedSubpartArray);

        return $out;
    }

    private function getMap($items, $configurations, $confId, $markerPrefix)
    {
        $ret = '###LABEL_mapNotAvailable###';

        try {
            $map = Factory::createGoogleMap($configurations, $confId);

            $template = MapsUtil::getMapTemplate($configurations, $confId, '###STADIUM_MAP_MARKER###');
            $itemMarker = tx_rnbase::makeInstance(StadiumMarker::class);
            foreach ($items as $item) {
                $marker = $itemMarker->createMapMarker($template, $item, $configurations->getFormatter(), $confId.'stadium.', $markerPrefix);
                if (!$marker) {
                    continue;
                }
                MapsUtil::addIcon($map, $configurations, $this->getController()->getConfId().'map.icon.stadiumlogo.', $marker, 'stadium_'.$item->getUid(), $item->getLogoPath());
                $map->addMarker($marker);
            }
            if ($configurations->get($confId.'showBasePoint')) {
                $lng = floatval($configurations->get($confId.'stadium._basePosition.longitude'));
                $lat = floatval($configurations->get($confId.'stadium._basePosition.latitude'));
                $coords = tx_rnbase::makeInstance(Coord::class);
                $coords->setLongitude($lng);
                $coords->setLatitude($lat);
                $marker = tx_rnbase::makeInstance(DefaultMarker::class);
                $marker->setCoords($coords);
                $marker->setDescription('###LABEL_BASEPOINT###');
                $map->addMarker($marker);
            }
            $ret = $map->draw();
        } catch (Exception $e) {
            $ret = '###LABEL_mapNotAvailable###';
        }

        return $ret;
    }

    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###STADIUM_LIST###';
    }
}
