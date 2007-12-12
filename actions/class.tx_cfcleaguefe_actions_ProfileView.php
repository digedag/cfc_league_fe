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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');

require_once(t3lib_extMgm::extPath('cfc_league_fe') . 'util/class.tx_cfcleaguefe_util_ScopeController.php');

/**
 * Controller für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_actions_ProfileView {

  /**
   *
   */
  function execute($parameters,&$configurations){

// t3lib_div::debug($parameters,'ac_matchtable');

    $profileId = intval($configurations->get('profile'));
    if(!$profileId) {
      // Aus dem Request benötigen wir die UID des Profils
      $profileId = intval($parameters->offsetGet('profileId'));
      if($profileId == 0)
        return 'No profileId found!';
    }

    $profile = $this->findProfile($profileId, $configurations);

//t3lib_div::debug($profile, 'act_profile');

    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('profile', $profile);

    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_ProfileView');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('profileTemplate'));
    $out = $view->render('profileview', $configurations);
    return $out;
  }

  /**
   * Sucht den Spieler, der gezeigt werden soll.
   */
  function findProfile($profileId, &$configurations) {

    $what = '*';
    $from = 'tx_cfcleague_profiles';
    $options['where'] = 'uid = ' .$profileId . ' ';
    $options['pidlist'] = $configurations->get('profilePages');
    $options['recursive'] = $configurations->get('profileRecursive');
    $options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';

    $rows = tx_rnbase_util_DB::doSelect($what,$from,$options,0);
//    $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_profile');

    $profile = $rows[0];
    return $profile;
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileView.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileView.php']);
}

?>