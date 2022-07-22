<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Request\ParametersInterface;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Search\SearchBase;
use Sys25\RnBase\Utility\PageBrowser;
use System25\T3sports\Frontend\View\ClubListView;
use System25\T3sports\Service\TeamService;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2021 Rene Nitzsche (rene@system25.com)
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

class ClubList extends AbstractAction
{
    /**
     * @param RequestInterface $request
     *
     * @return string error msg or null
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewData = $request->getViewContext();

        $srv = ServiceRegistry::getTeamService();
        $this->conf = $configurations;

        $filter = BaseFilter::createFilter($request, $this->getConfId());

        $fields = $options = [];
        $filter->init($fields, $options);

        // Soll ein PageBrowser verwendet werden
        $this->handleCharBrowser($parameters, $configurations, $viewData, $fields, $options);
        $this->handlePageBrowser($parameters, $configurations, $viewData, $fields, $options);
        $items = $srv->searchClubs($fields, $options);
        $request->getViewContext()->offsetSet('items', $items);

        return null;
    }

    /**
     * Pagebrowser vorbereiten.
     *
     * @param ParametersInterface $parameters
     * @param ConfigurationInterface $configurations
     * @param \ArrayObject $viewdata
     * @param array $fields
     * @param array $options
     */
    protected function handlePageBrowser($parameters, $configurations, $viewdata, &$fields, &$options)
    {
        if (is_array($configurations->get($this->getConfId().'club.pagebrowser.'))) {
            $service = ServiceRegistry::getTeamService();
            // Mit Pagebrowser benötigen wir zwei Zugriffe, um die Gesamtanzahl der Orgs zu ermitteln
            $options['count'] = 1;
            $listSize = $service->searchClubs($fields, $options);
            unset($options['count']);
            // PageBrowser initialisieren
            $pageBrowser = tx_rnbase::makeInstance(PageBrowser::class, 'club');
            $pageSize = $configurations->getInt($this->getConfId().'club.pagebrowser.limit');
            $pageBrowser->setState($parameters, $listSize, $pageSize);
            $limit = $pageBrowser->getState();
            $options = array_merge($options, $limit);
            $viewdata->offsetSet('pagebrowser', $pageBrowser);
        }
    }

    protected function handleCharBrowser(&$parameters, &$configurations, &$viewData, &$fields, &$options)
    {
        if ($configurations->get($this->getConfId().'club.charbrowser')) {
            $srv = ServiceRegistry::getTeamService();
            $colName = $configurations->get($this->getConfId().'club.charbrowser.column');
            $colName = $colName ? $colName : 'name';

            $pagerData = $this->findPagerData($srv, $configurations, $colName);
            $firstChar = $parameters->offsetGet('charpointer');
            $firstChar = (strlen(trim($firstChar)) > 0) ? substr($firstChar, 0, 1) : $pagerData['default'];
            $viewData->offsetSet('pagerData', $pagerData);
            $viewData->offsetSet('charpointer', $firstChar);
        }
        $filter = $viewData->offsetGet('filter');
        // Der CharBrowser beachten wir nur, wenn keine Suche aktiv ist
        // TODO: Der Filter sollte eine Methode haben, die sagt, ob ein Formular aktiv ist
        if ($firstChar && !$filter->inputData) {
            $specials = SearchBase::getSpecialChars();
            $firsts = $specials[$firstChar];
            if ($firsts) {
                $firsts = implode('\', \'', $firsts);
            } else {
                $firsts = $firstChar;
            }

            if ($fields[SEARCH_FIELD_CUSTOM]) {
                $fields[SEARCH_FIELD_CUSTOM] .= ' AND ';
            }
            $fields[SEARCH_FIELD_CUSTOM] .= 'LEFT(UCASE('.$colName."),1) IN ('$firsts') ";
        }
    }

    /**
     * Wir verwenden einen alphabetischen Pager.
     * Also muß zunächst ermittelt werden, welche
     * Buchstaben überhaupt vorkommen.
     *
     * @param TeamService $service
     * @param ConfigurationInterface $configurations
     */
    protected function findPagerData($service, ConfigurationInterface $configurations, $colName)
    {
        $options = [];
        $options['what'] = 'LEFT(UCASE('.$colName.'),1) As first_char, count(LEFT(UCASE('.$colName.'),1)) As size';
        $options['groupby'] = 'LEFT(UCASE('.$colName.'),1)';
        $fields = [];
        SearchBase::setConfigFields($fields, $configurations, $this->getConfId().'fields.');
        SearchBase::setConfigOptions($options, $configurations, $this->getConfId().'options.');

        $rows = $service->searchClubs($fields, $options);

        $specials = SearchBase::getSpecialChars();
        $wSpecials = [];
        foreach ($specials as $key => $special) {
            foreach ($special as $char) {
                $wSpecials[$char] = $key;
            }
        }

        $ret = [];
        foreach ($rows as $row) {
            if (array_key_exists($row['first_char'], $wSpecials)) {
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

    public function getTemplateName()
    {
        return 'clublist';
    }

    public function getViewClassName()
    {
        return ClubListView::class;
    }
}
