<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Filter\Utility\PageBrowserFilter;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Frontend\View\TeamListView;
use System25\T3sports\Model\Repository\TeamRepository;

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
 * Controller für die Anzeige einer Teamliste.
 */
class TeamList extends AbstractAction
{
    private $repo;

    public function __construct(TeamRepository $repo = null)
    {
        $this->repo = $repo ?: new TeamRepository();
    }

    protected function handleRequest(RequestInterface $request)
    {
        // Wir suchen über den Scope, sowie über zusätzlich per TS gesetzte Bedingungen
        // ggf. die Konfiguration aus der TS-Config lesen
        $configurations = $request->getConfigurations();
        $filter = BaseFilter::createFilter($request, $this->getConfId());

        $fields = [];
        $options = [];
        $filter->init($fields, $options, $parameters, $configurations, $this->getConfId());

        // Soll ein PageBrowser verwendet werden
        $pbFilter = new PageBrowserFilter();
        $pbFilter->handle($configurations, $request->getConfId().'team.pagebrowser', $request->getViewContext(), $fields, $options, [
            'searchcallback' => [
                $this->repo,
                'search',
            ],
            'pbid' => 'teams',
        ]);

        $teams = $this->repo->search($fields, $options);
        $request->getViewContext()->offsetSet('teams', $teams); // Die Teams für den View bereitstellen
    }

    protected function getTemplateName()
    {
        return 'teamlist';
    }

    protected function getViewClassName()
    {
        return TeamListView::class;
    }
}
