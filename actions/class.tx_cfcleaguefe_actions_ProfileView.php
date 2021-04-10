<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2017 Rene Nitzsche (rene@system25.de)
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
 * Controller für die Anzeige eines Personenprofils.
 */
class tx_cfcleaguefe_actions_ProfileView extends tx_rnbase_action_BaseIOC
{
    public static $exclude = [];

    /**
     * handle request.
     *
     * @param arrayobject $parameters
     * @param tx_rnbase_configurations $configurations
     * @param arrayobject $viewData
     *
     * @return string
     */
    protected function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        $fields = [];
        $options = [];
        $this->initSearch($fields, $options, $parameters, $configurations);

        $service = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
        $profiles = $service->search($fields, $options);
        $profile = count($profiles) ? $profiles[0] : null;
        if (!$profile) {
            return 'No profile found!';
        }

        $viewData->offsetSet('profile', $profile);
        self::$exclude[] = $profile->uid;

        return '';
    }

    protected function initSearch(&$fields, &$options, $parameters, $configurations)
    {
        // ggf. die Konfiguration aus der TS-Config lesen
        tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'profileview.fields.');
        tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'profileview.options.');

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
        return 'tx_cfcleaguefe_views_ProfileView';
    }
}
