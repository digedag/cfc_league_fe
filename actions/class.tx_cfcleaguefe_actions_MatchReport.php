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

/**
 * Action für die Anzeige eines Spielberichts.
 */
class tx_cfcleaguefe_actions_MatchReport extends tx_rnbase_action_BaseIOC
{
    /**
     * handle request.
     *
     * @param arrayobject $parameters
     * @param Tx_Rnbase_Configuration_ProcessorInterface $configurations
     * @param arrayobject $viewData
     *
     * @return string
     */
    protected function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        // Die MatchID ermittlen
        // Ist sie fest definiert?
        $matchId = intval($configurations->get('matchreportMatchUid'));
        if (!$matchId) {
            $matchId = intval($parameters->offsetGet('matchId'));
            if (0 == $matchId) {
                return 'No matchId found!';
            }
        }
        // Das Spiel laden
        try {
            if (null != $configurations->get($this->getConfId().'viewClassName')) {
                $match = tx_rnbase::makeInstance('tx_cfcleague_models_Match', $matchId);
                if (!$match->isValid()) {
                    throw new Exception('Match is not valid');
                }
                $viewData->offsetSet('match', $match); // Den Spielreport für den View bereitstellen
            } else {
                /* @var $matchReport tx_cfcleaguefe_models_matchreport */
                $matchReport = tx_rnbase::makeInstance('tx_cfcleaguefe_models_matchreport', $matchId, $configurations);
                $viewData->offsetSet('match', $matchReport->getMatch()); // Den Spielreport für den View bereitstellen
            }
        } catch (Exception $e) {
            throw tx_rnbase::makeInstance('Tx_Rnbase_Exception_PageNotFound404', $e->getMessage()."\nX-t3sports-msg: match not found\nX-t3sports-match: ".$matchId);
        }

        return null;
    }

    protected function getTemplateName()
    {
        return 'matchreport';
    }

    protected function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_MatchReport';
    }
}
