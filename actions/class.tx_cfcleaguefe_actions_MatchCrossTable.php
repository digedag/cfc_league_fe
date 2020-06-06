<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_cfcleaguefe_models_team');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');

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
     * @return string error 	/**
     * Handle request
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
        $fields = array();
        $options = array();

        //  	$options['debug'] = 1;
        $this->initSearch($fields, $options, $parameters, $configurations);
        $listSize = 0;

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
        $uids = array();
        for ($i = 0; $i < $mCnt; ++$i) {
            $uids[] = $matches[$i]->record['home'];
            $uids[] = $matches[$i]->record['guest'];
        }
        $uids = array_unique($uids);
        $teams = tx_cfcleaguefe_models_team::getTeamsByUid($uids);
        $teamsArr = array();
        for ($i = 0; $i < count($teams); ++$i) {
            $teamsArr[$teams[$i]->uid] = $teams[$i];
        }

        for ($i = 0; $i < $mCnt; ++$i) {
            $matches[$i]->setHome($teamsArr[$matches[$i]->record['home']]);
            $matches[$i]->setGuest($teamsArr[$matches[$i]->record['guest']]);
        }

        return $teamsArr;
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
                return $out;
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchCrossTable.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchCrossTable.php'];
}
