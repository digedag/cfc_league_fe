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
class tx_cfcleaguefe_actions_TeamView {

  /**
   *
   */
  function execute($parameters,$configurations){

// t3lib_div::debug($parameters,'ac_matchtable');

    $teams = array();
    // Im Flexform kann direkt ein Team ausgwählt werden
    $teamId = intval($configurations->get('teamviewTeam'));
    if(!$teamId) {
      // Alternativ ist eine Parameterübergabe möglich
      $teamId = intval($parameters->offsetGet('teamId'));
      // Wenn die TeamID über den Parameter übergeben wird, dann müssen wir sie aus den 
      // Keepvars entfernen. Sonst funktionieren die Links auf den Scope nicht mehr.
      $configurations->_keepVars->offsetUnset('teamId');
      $parameters->offsetUnset('teamId');
    }
    if($teamId <= 0) {
      // Nix angegeben also über den Scope suchen
      // Die Werte des aktuellen Scope ermitteln
      $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
      $saisonUids = $scopeArr['SAISON_UIDS'];
      $groupUids = $scopeArr['GROUP_UIDS'];
//    $compUids = $scopeArr['COMP_UIDS'];
//    $roundUid = $scopeArr['ROUND_UIDS'];
      $club = $configurations->get('teamviewClub');

      // Ohne Club können wir nichts zeigen
      if(intval($club) == 0) {
        return 'Error: No club defined.';
      }

      $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_club');
      $club = new $className($club);
      $teams = $club->getTeams($saisonUids, $groupUids);
    }
    else {
      $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_team');
      $team = new $className($teamId);
      $teams[] = $team;

//t3lib_div::debug($teams, 'ac_teamview');
    }

    $viewData =& $configurations->getViewData();
    // Wir zeigen immer nur das erste Team im Ergebnis, selbst wenn es durch Fehlkonfiguration
    // mehrere sein sollten
    $viewData->offsetSet('team', $teams[0]);

//t3lib_div::debug(strftime('%y',0), 'ac_MatchTable');


    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_TeamView');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('teamviewTemplate'));
    $out = $view->render('teamview', $configurations);

    return $out;
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TeamView.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TeamView.php']);
}

?>