<?php

namespace System25\T3sports\Frontend\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Search\SearchBase;
use System25\T3sports\Utility\ServiceRegistry;

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
 * Controller für die Anzeige eines Personenprofils.
 */
class ProfileDetails extends AbstractAction
{
    public static $exclude = [];

    /**
     * handle request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function handleRequest(RequestInterface $request)
    {
        $fields = [];
        $options = [];
        $this->initSearch($fields, $options, $request->getParameters(), $request->getConfigurations());

        $service = ServiceRegistry::getProfileService();
        $profiles = $service->search($fields, $options);
        $profile = count($profiles) ? $profiles[0] : null;

        if (!$profile) {
            return 'No profile found!';
        }

        $request->getViewContext()->offsetSet('profile', $profile);
        self::$exclude[] = $profile->uid;

        return '';
    }

    protected function initSearch(&$fields, &$options, $parameters, $configurations)
    {
        // ggf. die Konfiguration aus der TS-Config lesen
        SearchBase::setConfigFields($fields, $configurations, 'profileview.fields.');
        SearchBase::setConfigOptions($options, $configurations, 'profileview.options.');

        $options['limit'] = 1;
        if (intval($configurations->get('profileview.excludeAlreadyDisplayed'))) {
            // Doppelte Anzeige von Personen vermeiden
            if (count(self::$exclude)) {
                $fields['PROFILE.UID'][OP_NOTIN_INT] = implode(',', self::$exclude);
            }
        } else {
            // Parameter prüfen
            $value = $parameters->offsetGet('profileId');
            if (intval($value)) {
                $fields['PROFILE.UID'][OP_EQ_INT] = intval($value);
            }
        }
    }

    public function getConfId()
    {
        return 'profileview.';
    }

    protected function getTemplateName()
    {
        return 'profile';
    }

    protected function getViewClassName()
    {
        return \System25\T3sports\Frontend\View\ProfileView::class;
    }
}
