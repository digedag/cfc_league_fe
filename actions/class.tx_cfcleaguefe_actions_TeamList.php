<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2018 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');

/**
 * Controller für die Anzeige einer Teamliste.
 */
class tx_cfcleaguefe_actions_TeamList extends tx_rnbase_action_BaseIOC
{
    public function handleRequest(&$parameters, &$configurations, &$viewdata)
    {
        // Wir suchen über den Scope, sowie über zusätzlich per TS gesetzte Bedingungen
        // ggf. die Konfiguration aus der TS-Config lesen
        $srv = tx_cfcleaguefe_util_ServiceRegistry::getTeamService();
        $filter = tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewdata, $this->getConfId());

        $fields = array();
        $options = array();
        $filter->init($fields, $options, $parameters, $configurations, $this->getConfId());

        tx_rnbase_filter_BaseFilter::handlePageBrowser($configurations, $this->getConfId().'team.pagebrowser', $viewdata, $fields, $options, array(
            'searchcallback' => array(
                $srv,
                'search',
            ),
            'pbid' => 'teams',
        ));

        $teams = $srv->search($fields, $options);
        $viewdata->offsetSet('teams', $teams); // Die Teams für den View bereitstellen
    }

    public function getTemplateName()
    {
        return 'teamlist';
    }

    public function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_TeamList';
    }
}
