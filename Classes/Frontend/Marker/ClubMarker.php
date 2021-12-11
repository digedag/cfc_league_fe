<?php

namespace System25\T3sports\Frontend\Marker;

use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Maps\DefaultMarker;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Model\Club;
use System25\T3sports\Utility\MapsUtil;
use System25\T3sports\Utility\ServiceRegistry;
use tx_cfcleaguefe_util_StadiumMarker as StadiumMarker;
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
 * Diese Klasse ist für die Erstellung von Markerarrays der Vereine verantwortlich.
 */
class ClubMarker extends BaseMarker
{
    /**
     * @param string $template das HTML-Template
     * @param Club $club der Verein
     * @param FormatUtil $formatter der zu verwendente Formatter
     * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.club.'
     * @param array $links Array mit Link-Instanzen, wenn Verlinkung möglich sein soll. Zielseite muss vorbereitet sein.
     * @param string $clubMarker Name des Markers für den Club, z.B. CLUB
     *            Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
     *
     * @return string das geparste Template
     */
    public function parseTemplate($template, &$club, &$formatter, $confId, $marker = 'CLUB')
    {
        if (!is_object($club)) {
            // Ist kein Verein vorhanden wird ein leeres Objekt verwendet.
            $club = self::getEmptyInstance(Club::class);
        }
        $this->prepareRecord($club, $template, $formatter->getConfigurations(), $confId, $marker);
        // Es wird das MarkerArray mit Daten gefüllt
        $ignore = self::findUnusedCols($club->getProperty(), $template, $marker);
        $markerArray = $formatter->getItemMarkerArrayWrapped($club->getProperty(), $confId, $ignore, $marker.'_', $club->getColumnNames());
        $subpartArray = $wrappedSubpartArray = [];
        $this->prepareLinks($club, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
        // Die Adressdaten setzen
        if ($this->containsMarker($template, $marker.'_ADDRESS')) {
            $template = $this->_addAddress($template, $club->getAddress(), $formatter, $confId.'address.', $marker.'_ADDRESS');
        }
        if ($this->containsMarker($template, $marker.'_STADIUMS')) {
            $template = $this->addStadiums($template, $club, $formatter, $confId.'stadium.', $marker.'_STADIUM');
        }
        if ($this->containsMarker($template, $marker.'_TEAMS')) {
            $template = $this->addTeams($template, $club, $formatter, $confId.'team.', $marker.'_TEAM');
        }

        $out = Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $out;
    }

    /**
     * @param Club $item
     * @param string $template
     * @param $configurations
     * @param string $confId
     * @param $marker
     */
    protected function prepareRecord($item, $template, $configurations, $confId, $marker)
    {
        $item->setProperty('distance', '');
        if ($this->containsMarker($template, $marker.'_DISTANCE') && self::hasGeoData($item)) {
            $lat = floatval($configurations->get($confId.'_basePosition.latitude'));
            $lng = floatval($configurations->get($confId.'_basePosition.longitude'));
            $item->setProperty('distance', MapsUtil::getDistance($item, $lat, $lng));
        }
    }

    protected function _addAddress($template, $address, $formatter, $addressConf, $markerPrefix)
    {
        $addressMarker = tx_rnbase::makeInstance(AddressMarker::class);
        $template = $addressMarker->parseTemplate($template, $address, $formatter, $addressConf, null, $markerPrefix);

        return $template;
    }

    /**
     * Hinzufügen der Stadien.
     *
     * @param string $template HTML-Template für die Profile
     * @param Club $item
     * @param FormatUtil $formatter
     * @param string $confId Config-String
     * @param string $markerPrefix
     */
    private function addStadiums($template, &$item, &$formatter, $confId, $markerPrefix)
    {
        $srv = ServiceRegistry::getStadiumService();
        $fields = [];
        $fields['STADIUMMM.UID_FOREIGN'][OP_IN_INT] = $item->getUid();
        $options = [];
        SearchBase::setConfigFields($fields, $formatter->configurations, $confId.'fields.');
        SearchBase::setConfigOptions($options, $formatter->configurations, $confId.'options.');
        $children = $srv->search($fields, $options);

        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $out = $listBuilder->render($children, false, $template, StadiumMarker::class, $confId, $markerPrefix, $formatter, $options);

        return $out;
    }

    /**
     * Hinzufügen der Teams.
     *
     * @param string $template
     *            HTML-Template
     * @param Club $item
     * @param FormatUtil $formatter
     * @param string $confId
     *            Config-String
     * @param string $markerPrefix
     */
    private function addTeams($template, Club $item, FormatUtil $formatter, $confId, $markerPrefix)
    {
        $srv = ServiceRegistry::getTeamService();
        $fields = [];
        $fields['TEAM.CLUB'][OP_EQ_INT] = $item->getUid();
        $options = [];
        SearchBase::setConfigFields($fields, $formatter->getConfigurations(), $confId.'fields.');
        SearchBase::setConfigOptions($options, $formatter->getConfigurations(), $confId.'options.');
        $children = $srv->searchTeams($fields, $options);

        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $out = $listBuilder->render($children, false, $template, TeamMarker::class, $confId, $markerPrefix, $formatter);

        return $out;
    }

    private static function hasGeoData($item)
    {
        return !(!$item->getCity() && !$item->getZip() && !$item->getLongitute() && !$item->getLatitute());
    }

    /**
     * Create a marker for GoogleMaps.
     * This can be done if the club has address data.
     *
     * @param string $template
     * @param Club $item
     */
    public function createMapMarker($template, Club $item, $formatter, $confId, $markerPrefix)
    {
        if (!self::hasGeoData($item)) {
            return false;
        }

        $marker = new DefaultMarker();
        if ($item->getLongitute() || $item->getLatitute()) {
            $marker->setCoords($item->getCoords());
        } else {
            $marker->setCity($item->getCity());
            $marker->setZip($item->getZip());
            $marker->setStreet($item->getStreet());
            $marker->setCountry($item->getCountryCode());
        }
        // $marker->setTitle($item->getName());
        $bubble = $this->parseTemplate($template, $item, $formatter, $confId, $markerPrefix);
        $marker->setDescription($bubble);

        return $marker;
    }

    /**
     * Links vorbereiten.
     *
     * @param Club $item
     * @param string $marker
     * @param array $markerArray
     * @param array $wrappedSubpartArray
     * @param string $confId
     * @param FormatUtil $formatter
     */
    protected function prepareLinks($item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, $formatter, $template)
    {
        $linkId = 'show';
        $cObjData = $formatter->getConfigurations()->getCObj()->data;
        $formatter->getConfigurations()->getCObj()->data = $item->getProperty();
        if ($item->isPersisted()) {
            $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, [
                'club' => $item->uid,
            ], $template);
        } else {
            $linkMarker = $marker.'_'.strtoupper($linkId).'LINK';
            $remove = intval($formatter->configurations->get($confId.'links.'.$linkId.'.removeIfDisabled'));
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
        }
        $formatter->getConfigurations()->getCObj()->data = $cObjData;
    }
}
