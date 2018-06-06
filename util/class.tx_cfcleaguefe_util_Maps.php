<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2018 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_maps_google_Icon');
tx_rnbase::load('tx_rnbase_util_Extensions');

/**
 * Utility methods for google maps integration
 *
 * @author René Nitzsche <rene[at]system25.de>
 */
class tx_cfcleaguefe_util_Maps
{
    private static $key = false;

    public static function getMapTemplate($configurations, $confId, $subpartName)
    {
        $file = $configurations->get($confId . 'template');
        if (! $file) {
            return '';
        }
        $templateCode = $configurations->getCObj()->fileResource($file);
        $subpart = tx_rnbase_util_Templates::getSubpart($templateCode, $subpartName);
        $order = array(
            "\r\n",
            "\n",
            "\r"
        );
        $replace = '';
        return str_replace($order, $replace, $subpart);
    }

    /**
     * Setzt ein Icon für die Map
     *
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     *            confid for image configuration
     * @param tx_rnbase_maps_DefaultMarker $marker
     * @param string $logo
     *            image path
     * @param string $shadow
     *            image path to shadow, not supported yet!
     */
    public static function addIcon($map, $configurations, $confId, $marker, $iconName, $logo, $shadow = '')
    {
        $GLOBALS['TSFE']->register['T3SPORTS_MAPICON'] = $logo;
        if ($logo) {
            $imgConf = $configurations->get($confId);
            $imgConf['file'] = $imgConf['file'] ? $imgConf['file'] : $logo;
            $url = $configurations->getCObj()->IMG_RESOURCE($imgConf);
            $icon = new tx_rnbase_maps_google_Icon($map);
            $icon->setName($iconName);

            $height = intval($imgConf['file.']['maxH']);
            $width = intval($imgConf['file.']['maxW']);
            $height = $height ? $height : 20;
            $width = $width ? $width : 20;
            $icon->setImage($url, $width, $height);
            $icon->setShadow($url, $width, $height);
            $icon->setAnchorPoint($width / 2, $height / 2);
            $icon->setInfoWindowAnchorPoint($width / 2, $height / 2);
            $marker->setIcon($icon);
        }
    }

    /**
     * Calculate distance for two long/lat-points.
     * Method used from wec_map
     *
     * @param double $lat1
     * @param double $lon1
     * @param double $lat2
     * @param double $lon2
     * @param string $distanceType
     * @return double
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2, $distanceType = 'K')
    {
        $l1 = deg2rad($lat1);
        $l2 = deg2rad($lat2);
        $o1 = deg2rad($lon1);
        $o2 = deg2rad($lon2);
        $radius = $distanceType == 'K' ? 6372.795 : 3959.8712;
        $distance = 2 * $radius * asin(min(1, sqrt(pow(sin(($l2 - $l1) / 2), 2) + cos($l1) * cos($l2) * pow(sin(($o2 - $o1) / 2), 2))));
        return $distance;
    }

    public static function getDistance($item, $lat, $lng, $distanceType = 'K')
    {
        if ($item->getLongitute() || $item->getLatitute()) {
            $coord = $item->getCoords();
        } else {
            $coord = self::lookupAddress($item->getStreet(), $item->getCity(), '', $item->getZip(), $item->getCountryCode());
        }
        if ($coord)
            return self::calculateDistance($coord->getLatitude(), $coord->getLongitude(), $lat, $lng);
        return 0;
    }

    /**
     * Address lookup
     *
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string $zip
     * @param string $country
     * @return tx_rnbase_maps_ICoord or false
     */
    public static function lookupAddress($street, $city, $state, $zip, $country)
    {
        if (! tx_rnbase_util_Extensions::isLoaded('wec_map')) {
            return false;
        }
        require_once (tx_rnbase_util_Extensions::extPath('wec_map') . 'class.tx_wecmap_cache.php');

        $lookupTable = tx_rnbase::makeInstance('tx_wecmap_cache');
        $latlong = $lookupTable->lookup($street, $city, $state, $zip, $country, self::getKey());

        $coord = tx_rnbase::makeInstance('tx_rnbase_maps_Coord');
        $coord->setLongitude($latlong['long']);
        $coord->setLatitude($latlong['lat']);
        return $coord;
    }

    private static function getKey()
    {
        if (! self::$key) {
            require_once (tx_rnbase_util_Extensions::extPath('wec_map') . 'class.tx_wecmap_domainmgr.php');
            $domainmgr = tx_rnbase::makeInstance('tx_wecmap_domainmgr');
            self::$key = $domainmgr->getKey();
        }
        return self::$key;
    }
}
