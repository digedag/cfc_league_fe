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

tx_div::load('tx_cfcleaguefe_util_ScopeController');
tx_div::load('tx_rnbase_action_BaseIOC');

/**
 * Controller für die Anzeige der Scopeauswahl
 */
class tx_cfcleaguefe_actions_CompetitionSelection extends tx_rnbase_action_BaseIOC {

  /**
   *
   */
	function handleRequest(&$parameters,&$configurations, &$viewdata) {
	
		$this->viewType = $configurations->get('scopeSelection.viewType');
		// Die Werte des aktuellen Scope ermitteln
		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations, $this->viewType == 'HTML');
		return null;
//t3lib_div::debug($scopeArr ,'paras act_CompSel');

//    $view = ($viewType == 'HTML') ? tx_div::makeInstance('tx_cfcleaguefe_views_ScopeSelection') : 
//                                    tx_div::makeInstance('tx_rnbase_view_phpTemplateEngine');
//
//
//    $view->setTemplatePath($configurations->getTemplatePath());
//    // Das Template wird komplett angegeben
//    $view->setTemplateFile($configurations->get('scopeTemplate'));
//    $out = $view->render( ($viewType == 'HTML') ? 'scopeselection' : 'competitionselection', $configurations);
//
//    return $out;
  }
	function getTemplateName() {return 'scope';}
	function getViewClassName() {
		return ($this->viewType == 'HTML') ? 'tx_cfcleaguefe_views_ScopeSelection' : 'tx_rnbase_view_phpTemplateEngine';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_CompetitionSelection.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_CompetitionSelection.php']);
}

?>