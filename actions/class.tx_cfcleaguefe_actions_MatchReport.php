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

/**
 * Action für die Anzeige eines Spielberichts
 */
class tx_cfcleaguefe_actions_MatchReport {
  /**
   *
   */
  function execute(&$parameters,&$configurations){
    // Die MatchID ermittlen
    // Ist sie fest definiert?
    $matchId = intval($configurations->get('matchreportMatchUid'));
    if(!$matchId) {
      $matchId = intval($parameters->offsetGet('matchId'));
      if($matchId == 0)
        return 'No matchId found!';
    }

    // Das Spiel laden
    $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_matchreport');
    $matchReport = new $className($matchId, $configurations);
    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('matchReport', $matchReport); // Den Spielreport für den View bereitstellen

    // Auf das Template verzweigen
    $viewType = $configurations->get('matchreport.viewType');
    $view = ($viewType == 'HTML') ? tx_div::makeInstance('tx_cfcleaguefe_views_MatchReport') : 
                                    tx_div::makeInstance('tx_rnbase_view_phpTemplateEngine');

    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('matchreportTemplate'));
    $out = $view->render('matchreport', $configurations);
    return $out;
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchReport.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchReport.php']);
}

?>