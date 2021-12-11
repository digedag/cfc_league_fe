<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Frontend\View\LiveTickerListView;
use System25\T3sports\Utility\MatchTableBuilder;
use System25\T3sports\Utility\ScopeController;
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
 * Controller für die Anzeige der Spiele, für die ein LiveTicker geschaltet ist.
 */
class LiveTickerList extends AbstractAction
{
    /**
     * Handle request.
     *
     * @param RequestInterface $request
     *
     * @return string|null error message
     */
    protected function handleRequest(RequestInterface $request)
    {
        $parameters = $request->getParameters();
        $configurations = $request->getConfigurations();
        $viewdata = $request->getViewContext();
        $fields = [];
        $options = [];
        // $options['debug'] = 1;
        $this->initSearch($fields, $options, $parameters, $configurations);

        // Soll ein PageBrowser verwendet werden
        $this->handlePageBrowser($parameters, $configurations, $viewdata, $fields, $options);
        $service = ServiceRegistry::getMatchService();
        $matches = $service->search($fields, $options);

        $viewdata->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen

        return '';
    }

    /**
     * Set search criteria.
     *
     * @param array $fields
     * @param array $options
     * @param array $parameters
     * @param ConfigurationInterface $configurations
     */
    protected function initSearch(&$fields, &$options, &$parameters, &$configurations)
    {
        $options['distinct'] = 1;
        // $options['debug'] = 1;
        SearchBase::setConfigFields($fields, $configurations, 'tickerlist.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'tickerlist.options.');

        $scopeArr = ScopeController::handleCurrentScope($parameters, $configurations);
        $matchtable = $this->getMatchTable();
        $matchtable->setScope($scopeArr);
        $teamId = $configurations->get($this->getConfId().'teamId');
        $matchtable->setTeams($teamId);
        $matchtable->setTimeRange($configurations->get('tickerlist.timeRangePast'), $configurations->get('tickerlist.timeRangeFuture'));
        $matchtable->setLiveTicker();
        // Nur Live-Tickerspiele holen

        $matchtable->getFields($fields, $options);
    }

    /**
     * @return MatchTableBuilder
     */
    public function getMatchTable()
    {
        return tx_rnbase::makeInstance(MatchTableBuilder::class);
    }

    /**
     * Initializes page browser.
     *
     * @param arrayobject $parameters
     * @param ConfigurationInterface $configurations
     * @param arrayobject $viewdata
     * @param array $fields
     * @param array $options
     */
    public function handlePageBrowser(&$parameters, &$configurations, &$viewdata, &$fields, &$options)
    {
        if (is_array($configurations->get('tickerlist.match.pagebrowser.'))) {
            $service = ServiceRegistry::getMatchService();
            // Mit Pagebrowser benötigen wir zwei Zugriffe, um die Gesamtanzahl der Spiele zu ermitteln
            $options['count'] = 1;
            $listSize = $service->search($fields, $options);
            unset($options['count']);
            // PageBrowser initialisieren
            $pageBrowser = tx_rnbase::makeInstance('tx_rnbase_util_PageBrowser', 'tickerlist_'.$configurations->getPluginId());
            $pageSize = $this->getPageSize($parameters, $configurations);
            // Wurde neu gesucht?
            if ($parameters->offsetGet('NK_newsearch')) {
                // Der Suchbutton wurde neu gedrückt. Der Pager muss initialisiert werden
                $pageBrowser->setState(null, $listSize, $pageSize);
            } else {
                $pageBrowser->setState($parameters, $listSize, $pageSize);
            }
            $limit = $pageBrowser->getState();
            $options = array_merge($options, $limit);
            $viewdata->offsetSet('pagebrowser', $pageBrowser);
        }
    }

    public function getTemplateName()
    {
        return 'tickerlist';
    }

    public function getViewClassName()
    {
        return LiveTickerListView::class;
    }
}
