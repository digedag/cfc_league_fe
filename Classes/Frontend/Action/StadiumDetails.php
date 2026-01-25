<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Frontend\View\StadiumDetailsView;
use System25\T3sports\Model\Stadium;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2021 Rene Nitzsche (rene@system25.de)
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
 * Controller für die Anzeige eines Stadien.
 */
class StadiumDetails extends AbstractAction
{
    /**
     * {@inheritDoc}
     *
     * @see AbstractAction::handleRequest()
     */
    protected function handleRequest(RequestInterface $request)
    {
        // Im Flexform kann direkt ein Team ausgwählt werden
        $itemId = $request->getConfigurations()->getInt('stadiumview.stadium');
        if (!$itemId) {
            // Alternativ ist eine Parameterübergabe möglich
            $itemId = (int) $request->getParameters()->offsetGet('stadium');
        }

        $item = tx_rnbase::makeInstance(Stadium::class, $itemId);
        $request->getViewContext()->offsetSet('item', $item);

        return null;
    }

    public function getTemplateName()
    {
        return 'stadiumview';
    }

    public function getViewClassName()
    {
        return StadiumDetailsView::class;
    }
}
