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
 * Controller für die Anzeige der Spiele, für die ein LiveTicker geschaltet ist.
 */
class tx_cfcleaguefe_actions_LiveTickerList {

  /**
   * Die Spiele ermitteln und an den View weiterreichen
   */
  function execute($parameters,&$configurations){
    // Die Werte des aktuellen Scope ermitteln
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
    $saisonUids = $scopeArr['SAISON_UIDS'];
    $groupUids = $scopeArr['GROUP_UIDS'];
    $compUids = $scopeArr['COMP_UIDS'];
    $roundUid = $scopeArr['ROUND_UIDS'];
    $club = $scopeArr['CLUB_UIDS'];

//t3lib_div::debug($scopeArr , 'ac_tickerlist');

    $matchTable = tx_div::makeInstance('tx_cfcleaguefe_models_matchtable');
    $matchTable->setTimeRange($configurations->get('tickerlist.timeRangePast'),$configurations->get('tickerlist.timeRangeFuture'));
    $matchTable->setLimit($configurations->get('tickerlist.limit'));
    $matchTable->setOrderDesc($configurations->get('tickerlist.orderDesc') ? true : false );
    $matchTable->setLiveTicker(1); // Nur LiveTickerspiele holen

    $matches = $matchTable->findMatches($saisonUids, $groupUids, $compUids, $club, $roundUid);

    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('matches', $matches);

// t3lib_div::debug($viewData,'ac_tickerlist');

    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_LiveTickerList');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('templateLiveTickerList'));
    $out = $view->render('livetickerlist', $configurations);

    return $out;
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LiveTickerList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LiveTickerList.php']);
}

?>