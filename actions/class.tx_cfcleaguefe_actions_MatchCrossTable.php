<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2020 Rene Nitzsche (rene@system25.de)
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
class tx_cfcleaguefe_actions_MatchCrossTable extends tx_rnbase_action_BaseIOC
{
    /**
     * Handle request.
     *
     * @param arrayobject $parameters
     * @param tx_rnbase_configurations $configurations
     * @param arrayobject $viewdata
     *
     * @return string error message
     */
    public function handleRequest(&$parameters, &$configurations, &$viewdata)
    {
        // Wir suchen über den Scope, sowie über zusätzlich per TS gesetzte Bedingungen
        // ggf. die Konfiguration aus der TS-Config lesen
        $fields = $options = [];

        //  	$options['debug'] = 1;
        $this->initSearch($fields, $options, $parameters, $configurations);

        $service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
        $matches = $service->search($fields, $options);
        $teams = $this->_resolveTeams($matches);
        $viewdata->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen
        $viewdata->offsetSet('teams', $teams); // Die Teams für den View bereitstellen

        return '';
    }

    /**
     * Set search criteria.
     *
     * @param array $fields
     * @param array $options
     * @param array $parameters
     * @param tx_rnbase_configurations $configurations
     */
    protected function initSearch(&$fields, &$options, &$parameters, &$configurations)
    {
        $options['distinct'] = 1;
        //  	$options['debug'] = 1;
        tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'matchcrosstable.fields.');
        tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'matchcrosstable.options.');

        $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);

        $service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
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
            $teamIds = Tx_Rnbase_Utility_Strings::intExplode(',', $teamIds);
        }
        if (!count($teamIds)) {
            return [];
        }
        $teamIds = implode($teamIds, ',');
        $what = '*';
        $from = 'tx_cfcleague_teams';
        $options['where'] = 'tx_cfcleague_teams.uid IN ('.$teamIds.') ';
        $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

        return tx_rnbase_util_DB::doSelect($what, $from, $options, 0);
    }

    public function ___handleRequest(&$parameters, &$configurations, &$viewdata)
    {
        // Die Werte des aktuellen Scope ermitteln
        $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
        $saisonUids = $scopeArr['SAISON_UIDS'];
        $groupUids = $scopeArr['GROUP_UIDS'];
        $compUids = $scopeArr['COMP_UIDS'];
        $roundUid = $scopeArr['ROUND_UIDS'];
        $club = $scopeArr['CLUB_UIDS'];
        // Die Kreuztabelle wird nur für komplette Wettbewerbe erzeugt
        if (0 == strlen($compUids)) {
            $comps = tx_cfcleaguefe_models_competition::findAll($saisonUids, $groupUids, $compUids);
            if (count($comps) > 0) {
                $currCompetition = $comps[0];
                $currCompetition = $currCompetition->uid;
            // Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
            } else {
                return '';
            } // Ohne Wettbewerb keine Tabelle!
        } else {
            $currCompetition = Tx_Rnbase_Utility_Strings::intExplode(',', $compUids);
            $currCompetition = $currCompetition[0];
        }

        $matchTable = tx_rnbase::makeInstance('tx_cfcleaguefe_models_matchtable');
        $extended = $configurations->get('matchcrosstable.allData');
        $matches = $matchTable->findMatches($saisonUids, $groupUids, $currCompetition, '', '', $status, $extended);

        $teams = $this->_resolveTeams($matches);

        $viewData = &$configurations->getViewData();
        $viewData->offsetSet('matches', $matches); // Die Spiele für den View bereitstellen
        $viewData->offsetSet('teams', $teams); // Die Teams für den View bereitstellen

        return '';
    }

    public function getTemplateName()
    {
        return 'matchcrosstable';
    }

    public function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_MatchCrossTable';
    }
}
