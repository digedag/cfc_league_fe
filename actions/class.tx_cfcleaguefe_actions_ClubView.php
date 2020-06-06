<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2018 Rene Nitzsche (rene@system25.de)
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
 * Controller für die Anzeige eines Vereins.
 */
class tx_cfcleaguefe_actions_ClubView extends tx_rnbase_action_BaseIOC
{
    /**
     * handle request.
     *
     * @param arrayobject $parameters
     * @param tx_rnbase_configurations $configurations
     * @param arrayobject $viewData
     *
     * @return string
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        // Im Flexform kann direkt ein Team ausgwählt werden
        $itemId = intval($configurations->get($this->getConfId().'club'));
        if (!$itemId) {
            // Alternativ ist eine Parameterübergabe möglich
            $itemId = intval($parameters->offsetGet('club'));
        }

        $item = tx_rnbase::makeInstance('tx_cfcleague_models_Club', $itemId);
        $viewData->offsetSet('item', $item);

        return null;
    }

    public function getTemplateName()
    {
        return 'clubview';
    }

    public function getViewClassName()
    {
        return 'tx_cfcleaguefe_views_ClubView';
    }
}
