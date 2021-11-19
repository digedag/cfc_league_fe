<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Frontend\View\ProfileListView;
use System25\T3sports\Utility\ServiceRegistry;
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
 * Controller für die Anzeige einer Personenliste
 * Die Liste wird sortiert nach Namen angezeigt.
 * Dabei wird ein Pager verwendet, der für
 * jeden Buchstaben eine eigene Seite erstellt.
 */
class ProfileList extends AbstractAction
{
    /**
     * {@inheritDoc}
     *
     * @see \Sys25\RnBase\Frontend\Controller\AbstractAction::handleRequest()
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewData = $request->getViewContext();
        // Zunächst sollten wir die Anfangsbuchstaben ermitteln
        $service = ServiceRegistry::getProfileService();

        if ($configurations->get('profilelist.charbrowser')) {
            $pagerData = $this->findPagerData($service, $configurations);

            $firstChar = $parameters->offsetGet('charpointer');
            $firstChar = (strlen(trim($firstChar)) > 0) ? substr($firstChar, 0, 1) : $pagerData['default'];
            $viewData->offsetSet('pagerData', $pagerData);
            $viewData->offsetSet('charpointer', $firstChar);
        }

        $fields = [];
        $options = [
            'count' => 1,
        ];
        $this->initSearch($fields, $options, $parameters, $configurations, $firstChar);
        $listSize = $service->search($fields, $options);
        unset($options['count']);
        // PageBrowser initialisieren
        $pageBrowser = tx_rnbase::makeInstance('tx_rnbase_util_PageBrowser', 'profiles');
        $pageSize = $this->getPageSize($parameters, $configurations);
        // Wurde neu gesucht?
        if ($parameters->offsetGet('plnewsearch')) {
            $pageBrowser->setState(null, $listSize, $pageSize);
            $configurations->removeKeepVar('plnewsearch');
        } else {
            $pageBrowser->setState($parameters, $listSize, $pageSize);
        }
        $limit = $pageBrowser->getState();
        $options = array_merge($options, $limit);
        $result = $service->search($fields, $options);
        $viewData->offsetSet('profiles', $result);
        $viewData->offsetSet('pagebrowser', $pageBrowser);

        return null;
    }

    protected function initSearch(&$fields, &$options, &$parameters, &$configurations, $firstChar)
    {
        // ggf. die Konfiguration aus der TS-Config lesen
        SearchBase::setConfigFields($fields, $configurations, 'profilelist.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'profilelist.options.');
        $timeRange = $configurations->get('profilelist.birthdays');
        if ($timeRange) {
            $timePattern = 'DAY' == $timeRange ? '%d%m' : '%m';
            $where .= " ( (DATE_FORMAT(FROM_UNIXTIME(tx_cfcleague_profiles.birthday), '${timePattern}') = DATE_FORMAT(CURDATE(), '${timePattern}')";
            $where .= ' AND tx_cfcleague_profiles.birthday > 0) OR ';
            // Variante 2 für Zeiten vor 1970
            $where .= " (DATE_FORMAT(SUBDATE('1970-01-01', DATEDIFF(FROM_UNIXTIME( ABS( tx_cfcleague_profiles.birthday )), '1970-01-01')), '${timePattern}') = DATE_FORMAT(CURDATE(), '${timePattern}')";
            $where .= ' AND tx_cfcleague_profiles.birthday < 0 ))';
            if ($fields[SEARCH_FIELD_CUSTOM]) {
                $fields[SEARCH_FIELD_CUSTOM] .= ' AND ';
            }
            $fields[SEARCH_FIELD_CUSTOM] .= $where;

            // Sortierung nach Datum
            $sort = [
                'DATE_FORMAT(FROM_UNIXTIME(tx_cfcleague_profiles.birthday), \'%m%d\')' => 'asc',
            ];
            if (is_array($options['orderby'])) {
                $options['orderby'] = array_merge($sort, $options['orderby']);
            } else {
                $options['orderby'] = $sort;
            }
        }
        if ($firstChar) {
            $specials = SearchBase::getSpecialChars();
            $firsts = $specials[$firstChar];
            if ($firsts) {
                $firsts = implode('\',\'', $firsts);
            } else {
                $firsts = $firstChar;
            }

            if ($fields[SEARCH_FIELD_CUSTOM]) {
                $fields[SEARCH_FIELD_CUSTOM] .= ' AND ';
            }
            $fields[SEARCH_FIELD_CUSTOM] .= "LEFT(UCASE(last_name),1) IN ('$firsts') ";
        }
    }

    /**
     * Wir verwenden einen alphabetischen Pager.
     * Also muß zunächst ermittelt werden, welche
     * Buchstaben überhaupt vorkommen.
     *
     * @param \System25\T3sports\Service\ProfileService $service
     * @param ConfigurationInterface $configurations
     */
    protected function findPagerData($service, ConfigurationInterface $configurations)
    {
        $options = [];
        $options['what'] = 'LEFT(UCASE(last_name),1) As first_char, count(LEFT(UCASE(last_name),1)) As size';
        $options['groupby'] = 'LEFT(UCASE(last_name),1)';
        $fields = [];
        SearchBase::setConfigFields($fields, $configurations, 'profilelist.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'profilelist.options.');
        // replace orderby
        unset($options['orderby']);
        $options['orderby']['first_char'] = 'asc';

        $rows = $service->search($fields, $options);

        $specials = SearchBase::getSpecialChars();
        $wSpecials = [];
        foreach ($specials as $key => $special) {
            foreach ($special as $char) {
                $wSpecials[$char] = $key;
            }
        }

        $ret = [];
        foreach ($rows as $row) {
            if (array_key_exists(($row['first_char']), $wSpecials)) {
                $ret[$wSpecials[$row['first_char']]] = intval($ret[$wSpecials[$row['first_char']]]) + $row['size'];
            } else {
                $ret[$row['first_char']] = $row['size'];
            }
        }

        $current = 0;
        if (count($ret)) {
            $keys = array_keys($ret);
            $current = $keys[0];
        }
        $data = [
            'list' => $ret,
            'default' => $current,
        ];

        return $data;
    }

    /**
     * Liefert die Anzahl der Ergebnisse pro Seite.
     *
     * @param array $parameters
     * @param ConfigurationInterface $configurations
     *
     * @return int
     */
    protected function getPageSize(&$parameters, ConfigurationInterface $configurations): int
    {
        return $configurations->getInt('profilelist.profile.pagebrowser.limit');
    }

    protected function getTemplateName()
    {
        return 'profilelist';
    }

    protected function getViewClassName()
    {
        return ProfileListView::class;
    }
}
