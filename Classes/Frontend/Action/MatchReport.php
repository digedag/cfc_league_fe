<?php

namespace System25\T3sports\Frontend\Action;

use Exception;
use Sys25\RnBase\Exception\PageNotFound404;
use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Frontend\View\MatchReportView;
use System25\T3sports\Model\Match;
use System25\T3sports\Model\MatchReportModel;
use System25\T3sports\Utility\MatchProfileProvider;
use Throwable;
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
 * Action für die Anzeige eines Spielberichts.
 */
class MatchReport extends AbstractAction
{
    /**
     * handle request.
     *
     * @param RequestInterface $request
     *
     * @return string|null
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewData = $request->getViewContext();

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
            $match = tx_rnbase::makeInstance(Match::class, $matchId);
            if (null != $configurations->get($this->getConfId().'viewClassName')) {
                if (!$match->isValid()) {
                    throw new Exception('Match is not valid');
                }
                $viewData->offsetSet('match', $match); // Den Spielreport für den View bereitstellen
            } else {
                /* @var $matchReport MatchReportModel */
                $matchReport = tx_rnbase::makeInstance(MatchReportModel::class, $match, $configurations, new MatchProfileProvider());
                $viewData->offsetSet('match', $matchReport->getMatch()); // Den Spielreport für den View bereitstellen
            }
        } catch (Throwable $e) {
            throw tx_rnbase::makeInstance(PageNotFound404::class, $e->getMessage()."\nX-t3sports-msg: match not found\nX-t3sports-match: ".$matchId);
        }

        return null;
    }

    protected function getTemplateName()
    {
        return 'matchreport';
    }

    protected function getViewClassName()
    {
        return MatchReportView::class;
    }
}
