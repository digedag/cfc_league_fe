<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Database\Connection;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Search\SearchBase;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Frontend\View\MatchCrossTableView;
use System25\T3sports\Model\Team;
use System25\T3sports\Utility\ServiceRegistry;
use tx_cfcleaguefe_util_ScopeController as ScopeController;

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
 * Controller für die Anzeige eines Spielplans als Kreuztabelle.
 */
class MatchCrossTable extends AbstractAction
{
    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function handleRequest(RequestInterface $request)
    {
        // Wir suchen über den Scope, sowie über zusätzlich per TS gesetzte Bedingungen
        // ggf. die Konfiguration aus der TS-Config lesen
        $fields = $options = [];

        //  	$options['debug'] = 1;
        $this->initSearch($fields, $options, $request);

        $service = ServiceRegistry::getMatchService();
        $matches = $service->search($fields, $options);
        $teams = $this->_resolveTeams($matches);
        $request->getViewContext()->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen
        $request->getViewContext()->offsetSet('teams', $teams); // Die Teams für den View bereitstellen

        return '';
    }

    /**
     * Set search criteria.
     *
     * @param array $fields
     * @param array $options
     * @param RequestInterface $request
     */
    protected function initSearch(&$fields, &$options, RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $options['distinct'] = 1;
        //  	$options['debug'] = 1;
        SearchBase::setConfigFields($fields, $configurations, 'matchcrosstable.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'matchcrosstable.options.');

        $scopeArr = ScopeController::handleCurrentScope($request->getParameters(), $configurations);

        $service = ServiceRegistry::getMatchService();
        $matchtable = $service->getMatchTable();
        $matchtable->setScope($scopeArr);
        $matchtable->getFields($fields, $options);
    }

    /**
     * Lädt alle Teams der Spiele und verknüpft sie mit den jeweiligen Spielen.
     */
    public function _resolveTeams(&$matches)
    {
        // Einmal über alle Matches iterieren und die UIDs sammeln
        $mCnt = count($matches);
        if (!$mCnt) {
            return;
        } // Ohne Spiele gibt es nix zu tun
        $uids = [];
        for ($i = 0; $i < $mCnt; ++$i) {
            $uids[] = $matches[$i]->getProperty('home');
            $uids[] = $matches[$i]->getProperty('guest');
        }
        $uids = array_unique($uids);
        $teams = $this->getTeamsByUid($uids);
        $teamsArr = [];
        for ($i = 0; $i < count($teams); ++$i) {
            $teamsArr[$teams[$i]->getUid()] = $teams[$i];
        }

        for ($i = 0; $i < $mCnt; ++$i) {
            $matches[$i]->setHome($teamsArr[$matches[$i]->getProperty('home')]);
            $matches[$i]->setGuest($teamsArr[$matches[$i]->getProperty('guest')]);
        }

        return $teamsArr;
    }

    /**
     * Return all teams by an array of uids.
     *
     * @param mixed $teamIds
     *
     * @return array of tx_cfcleaguefe_models_team
     */
    private function getTeamsByUid($teamIds)
    {
        if (!is_array($teamIds)) {
            $teamIds = Strings::intExplode(',', $teamIds);
        }
        if (!count($teamIds)) {
            return [];
        }
        $teamIds = implode($teamIds, ',');
        $what = '*';
        $from = 'tx_cfcleague_teams';
        $options['where'] = 'tx_cfcleague_teams.uid IN ('.$teamIds.') ';
        $options['wrapperclass'] = Team::class;

        return Connection::getInstance()->doSelect($what, $from, $options);
    }

    public function getTemplateName()
    {
        return 'matchcrosstable';
    }

    public function getViewClassName()
    {
        return MatchCrossTableView::class;
    }
}
