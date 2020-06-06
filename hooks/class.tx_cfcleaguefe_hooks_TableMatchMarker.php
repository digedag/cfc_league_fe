<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2016 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

tx_rnbase::load('tx_rnbase_util_BaseMarker');
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * Integrate a league table in matchreport.
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_hooks_TableMatchMarker
{
    /**
     * Add match table with current round in match report.
     *
     * @param array $params
     * @param tx_cfcleaguefe_util_MatchReport $parent
     */
    public function addCurrentRound($params, $parent)
    {
        $template = $params['template'];
        $marker = $params['marker'];
        if (!tx_rnbase_util_BaseMarker::containsMarker($template, $marker.'_MTCURRENTROUND')) {
            return;
        }

        $formatter = $params['formatter'];
        $matches = $this->getCurrentRound($params, $formatter);
        $markerArray['###'.$marker.'_MTCURRENTROUND###'] = $matches;
        $params['template'] = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    /**
     * Generate current round.
     *
     * @param array $params
     * @param tx_rnbase_util_FormatUtil $formatter
     */
    private function getCurrentRound($params, $formatter)
    {
        $match = $this->getMatch($params);
        if (!is_object($match)) {
            return '';
        } // The call is not for us

        $confId = 'matchreport.mtcurrentround.';
        $fields = array();
        $options = array();
        tx_rnbase_util_SearchBase::setConfigFields($fields, $formatter->getConfigurations(), $confId.'fields.');
        tx_rnbase_util_SearchBase::setConfigOptions($options, $formatter->getConfigurations(), $confId.'options.');

        $srv = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
        $matchTable = $srv->getMatchTable();
        $matchTable->setCompetitions($match->getCompetition()->uid);
        $matchTable->setRounds($match->getRound());
        $matchTable->getFields($fields, $options);
        $matches = $srv->search($fields, $options);

        $subpartName = $formatter->getConfigurations()->get('subpartName');
        $subpartName = $subpartName ? $subpartName : '###CURRENTROUND_MATCHES###';
        $template = '';

        try {
            $template = tx_rnbase_util_Templates::getSubpartFromFile(
                $formatter->configurations->get($confId.'template'),
                $subpartName
            );
        } catch (Exception $e) {
            tx_rnbase::load('tx_rnbase_util_Logger');
            tx_rnbase_util_Logger::info('Error for matchtable current round: '.$e->getMessage(), 'cfc_league_fe');
        }
        if (!$template) {
            return '';
        }

        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
        $out = $listBuilder->render(
            $matches,
            false,
            $template,
            'tx_cfcleaguefe_util_MatchMarker',
            $confId.'match.',
            'MATCH',
            $formatter
        );

        return $out;
    }

    /**
     * Add league table in match report.
     *
     * @param array $params
     * @param tx_cfcleaguefe_util_MatchReport $parent
     */
    public function addLeagueTable($params, $parent)
    {
        $template = $params['template'];
        $marker = $params['marker'];
        if (!tx_rnbase_util_BaseMarker::containsMarker($template, $marker.'_LEAGUETABLE')) {
            return;
        }

        $match = $this->getMatch($params);
        if (!is_object($match)) {
            return false;
        } // The call is not for us
        $competition = $match->getCompetition();
        if (!$competition->isTypeLeague()) {
            // remove marker
            $markerArray['###'.$marker.'_LEAGUETABLE###'] = $table;
        } else {
            $formatter = $params['formatter'];
            $configurations = $formatter->getConfigurations();
            $confId = $params['confid'].'leaguetable.';
            $table = '#####<!-- Template not found -->';
            tx_rnbase::load('tx_rnbase_util_Files');
            $tableTemplate = tx_rnbase_util_Files::getFileResource(
                $configurations->get($confId.'template'),
                array('subpart' => $configurations->get($confId.'subpartName'))
            );
            if ($tableTemplate) {
                $tableData = $this->getTableData($formatter->getConfigurations(), $confId, $competition, $match);
                $writer = tx_rnbase::makeInstance('tx_cfcleaguefe_util_LeagueTableWriter');
                $table = $writer->writeLeagueTable($tableTemplate, $tableData, $competition->getTableMarks(), $configurations, $confId);
            }
        }

        $markerArray['###'.$marker.'_LEAGUETABLE###'] = $table;
        $params['template'] = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }

    private function getTableData($configurations, $confId, $competition, $match)
    {
        $tableProvider = tx_rnbase::makeInstance('tx_cfcleaguefe_util_league_DefaultTableProvider', $configurations->getParameters(), $configurations, $competition, $confId);
        if (intval($configurations->get($confId.'leaguetable.useRoundFromMatch'))) {
            $tableProvider->setCurrentRound($match->getRound());
        }

        // Wir benötigen noch die beiden Club-UIDs
        $clubMarks = array();
        $clubUid = $match->getHome()->getClubUid();
        if ($clubUid) {
            $clubMarks[] = $clubUid;
        }
        $clubUid = $match->getGuest()->getClubUid();
        if ($clubUid) {
            $clubMarks[] = $clubUid;
        }
        $tableProvider->setMarkClubs($clubMarks);

        $leagueTable = tx_rnbase::makeInstance('tx_cfcleaguefe_util_LeagueTable');
        $leagueTable = new tx_cfcleaguefe_util_LeagueTable();
        $tableData = $leagueTable->generateTable($tableProvider);

        return $tableData;
    }

    /**
     * Liefert das Match.
     *
     * @param array $params
     *
     * @return tx_cfcleaguefe_models_match or false
     */
    private function getMatch($params)
    {
        if (!isset($params['match'])) {
            return false;
        }

        return $params['match'];
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/hooks/class.tx_cfcleaguefe_hooks_TableMatchMarker.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/hooks/class.tx_cfcleaguefe_hooks_TableMatchMarker.php'];
}
