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
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');

tx_div::load('tx_cfcleaguefe_util_ScopeController');

/**
 * Controller für die Anzeige einer Personenliste
 * Die Liste wird sortiert nach Namen angezeigt. Dabei wird ein Pager verwendet, der für
 * jeden Buchstaben eine eigene Seite erstellt.
 */
class tx_cfcleaguefe_actions_TeamList {

  /**
   *
   */
  function execute($parameters,&$configurations){
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);

    $service = t3lib_div::makeInstanceService('cfcleague_teams');
    if(!is_object($service))
      return 'Fatal Error: No team service found!';

    $teams = $service->findTeamsByScope($scopeArr);

    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('teams', $teams); // Die Teams für den View bereitstellen

    $viewType = $configurations->get('teamlist.viewType');
    $view = ($viewType == 'HTML') ? tx_div::makeInstance('tx_cfcleaguefe_views_TeamList') : 
                                    tx_div::makeInstance('tx_rnbase_view_phpTemplateEngine');

    $view->setTemplatePath($configurations->getTemplatePath());
    $view->setTemplateFile($configurations->get('teamlist.template'));
    $out = $view->render('teamlist', $configurations);
    return $out;
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TeamList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TeamList.php']);
}

?>