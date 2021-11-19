<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Filter\Utility\PageBrowserFilter;
use Sys25\RnBase\Frontend\Marker\ListProvider;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Frontend\View\MatchTableView;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2017 Rene Nitzsche (rene@system25.de)
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
 * Controller für die Anzeige eines Spielplans.
 */
class MatchTable extends AbstractAction
{
    /**
     * Handle request.
     *
     * @param RequestInterface $request
     *
     * @return string error message
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $viewdata = $request->getViewContext();
        // Wir suchen über den Scope, sowie über zusätzlich per TS gesetzte Bedingungen
        // ggf. die Konfiguration aus der TS-Config lesen
        $filter = BaseFilter::createFilter($request, $this->getConfId());
        $fields = [];
        $options = [];

        $filter->init($fields, $options);
        $service = ServiceRegistry::getMatchService();
        // Soll ein PageBrowser verwendet werden
        $pbFilter = new PageBrowserFilter();
        $pbFilter->handle($configurations, $this->getConfId().'match.pagebrowser', $viewdata, $fields, $options, [
            'searchcallback' => [
                $service,
                'search',
            ],
            'pbid' => 'mt'.$configurations->getPluginId(),
        ]);

        $prov = tx_rnbase::makeInstance(ListProvider::class);
        $prov->initBySearch([
            $service,
            'search',
        ], $fields, $options);
        $viewdata->offsetSet('provider', $prov);

        // View
        $this->viewType = $configurations->get($this->getConfId().'viewType');

        return '';
    }

    public function getTemplateName()
    {
        return 'matchtable';
    }

    public function getViewClassName()
    {
        return MatchTableView::class;
    }
}
