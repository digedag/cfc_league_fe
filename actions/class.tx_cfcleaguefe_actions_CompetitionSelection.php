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
 * Controller für die Anzeige eines Spielplans
 */
class tx_cfcleaguefe_actions_CompetitionSelection {

  /**
   *
   */
  function execute($parameters,$configurations){

    $viewType = $configurations->get('scopeSelection.viewType');

//t3lib_div::debug($parameters,'paras');

    // Die Werte des aktuellen Scope ermitteln
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations, $viewType == 'HTML');

//t3lib_div::debug($scopeArr ,'paras act_CompSel');

    $view = ($viewType == 'HTML') ? tx_div::makeInstance('tx_cfcleaguefe_views_ScopeSelection') : 
                                    tx_div::makeInstance('tx_rnbase_view_phpTemplateEngine');


    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('scopeTemplate'));
    $out = $view->render( ($viewType == 'HTML') ? 'scopeselection' : 'competitionselection', $configurations);

    return $out;
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_CompetitionSelection.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_CompetitionSelection.php']);
}

?>