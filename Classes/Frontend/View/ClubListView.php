<?php

namespace System25\T3sports\Frontend\View;

use Exception;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\ContextInterface;
use Sys25\RnBase\Frontend\View\Marker\BaseView;
use Sys25\RnBase\Maps\Coord;
use Sys25\RnBase\Maps\DefaultMarker;
use Sys25\RnBase\Maps\Factory;
use Sys25\RnBase\Maps\Google\Icon;
use System25\T3sports\Frontend\Marker\ClubMarker;
use System25\T3sports\Model\Club;
use System25\T3sports\Utility\MapsUtil;
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
 * Viewklasse.
 */
class ClubListView extends BaseView
{
    public function createOutput($template, RequestInterface $request, $formatter)
    {
        $configurations = $request->getConfigurations();
        $viewData = $request->getViewContext();
        $items = &$viewData->offsetGet('items');

        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $template = $listBuilder->render($items, $viewData, $template, ClubMarker::class, 'clublist.club.', 'CLUB', $formatter);

        $markerArray = [];
        $subpartArray = [];

        if (BaseMarker::containsMarker($template, 'CLUBMAP')) {
            $markerArray['###CLUBMAP###'] = $this->getMap($items, $configurations, $request->getConfId().'map.', 'CLUB');
        }

        $out = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray); // , $wrappedSubpartArray);

        return $out;
    }

    private function getMap($items, $configurations, $confId, $markerPrefix)
    {
        $ret = '###LABEL_mapNotAvailable###';

        try {
            $map = Factory::createGoogleMap($configurations, $confId);

            $template = MapsUtil::getMapTemplate($configurations, $confId, '###CLUB_MAP_MARKER###');
            $itemMarker = tx_rnbase::makeInstance(ClubMarker::class);
            foreach ($items as $item) {
                $marker = $itemMarker->createMapMarker($template, $item, $configurations->getFormatter(), $confId.'club.', $markerPrefix);
                if (!$marker) {
                    continue;
                }

                MapsUtil::addIcon($map, $configurations, $this->getController()->getConfId().'map.icon.clublogo.', $marker, 'club_'.$item->getUid(), $item->getFirstLogo());
                // $this->addIcon($map, $marker, $item, $configurations);
                $map->addMarker($marker);
            }
            if ($configurations->get($confId.'showBasePoint')) {
                $lng = floatval($configurations->get($confId.'club._basePosition.longitude'));
                $lat = floatval($configurations->get($confId.'club._basePosition.latitude'));
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

    /**
     * Setzt ein Icon für die Map.
     *
     * @param DefaultMarker $marker
     * @param Club $club
     * @param ConfigurationInterface $configurations
     */
    private function addIcon($map, DefaultMarker $marker, Club $club, ConfigurationInterface $configurations)
    {
        $logo = $club->getFirstLogo();
        if ($logo) {
            $imgConf = $configurations->get($this->getController()
                ->getConfId().'map.icon.clublogo.');
            $imgConf['file'] = $logo;
            $url = $configurations->getCObj()->IMG_RESOURCE($imgConf);
            $icon = new Icon($map);
            $icon->setName('club_'.$club->getUid());

            $height = intval($imgConf['file.']['maxH']);
            $width = intval($imgConf['file.']['maxW']);
            $height = $height ? $height : 20;
            $width = $width ? $width : 20;
            $icon->setImage($url, $width, $height);
            $icon->setShadow($url, $width, $height);
            $icon->setAnchorPoint($width / 2, $height / 2);
            $marker->setIcon($icon);
        }
    }

    /**
     * Subpart der im HTML-Template geladen werden soll.
     * Dieser wird der Methode
     * createOutput automatisch als $template übergeben.
     *
     * @return string
     */
    public function getMainSubpart(ContextInterface $viewData)
    {
        return '###CLUB_LIST###';
    }
}
