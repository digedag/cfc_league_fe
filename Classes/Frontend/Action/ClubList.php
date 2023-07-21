<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Filter\Utility\CharBrowserFilter;
use Sys25\RnBase\Frontend\Filter\Utility\PageBrowserFilter;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Utility\PageBrowser;
use System25\T3sports\Frontend\View\ClubListView;
use System25\T3sports\Model\Repository\ClubRepository;
use System25\T3sports\Utility\ServiceRegistry;

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
    /** @var ClubRepository */
    private $clubRepo;

    public function __construct(ClubRepository $repo = null)
    {
        $this->clubRepo = $repo ?: new ClubRepository();
    }

    /**
     * @param RequestInterface $request
     *
     * @return string error msg or null
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $viewData = $request->getViewContext();

        $srv = ServiceRegistry::getTeamService();

        $filter = BaseFilter::createFilter($request, $this->getConfId().'club.');
        $fields = $options = [];
        $filter->init($fields, $options);

        // Soll ein PageBrowser verwendet werden
        // Soll ein CharBrowser verwendet werden
        $cbFilter = new CharBrowserFilter();
        $cbFilter->handle($configurations, $this->getConfId().'club.charbrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $this->clubRepo,
                'search',
            ],
            'colname' => 'name',
        ]);
        // Soll ein PageBrowser verwendet werden
        $pbFilter = new PageBrowserFilter();
        $pbFilter->handle($configurations, $this->getConfId().'club.pagebrowser', $viewData, $fields, $options, [
            'searchcallback' => [
                $this->clubRepo,
                'search',
            ],
            'pbid' => 'club',
        ]);

        $items = $this->clubRepo->search($fields, $options);
        $request->getViewContext()->offsetSet('items', $items);

        return null;
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
