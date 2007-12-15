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
require_once(t3lib_extMgm::extPath('dam') . 'lib/class.tx_dam_media.php');

tx_div::load('tx_rnbase_view_Base');
//tx_div::load('tx_dam_db');
//tx_div::load('tx_dam_media');


/**
 * Viewklasse für die Anzeige des Teams
 */
class tx_cfcleaguefe_views_TeamView extends tx_rnbase_view_Base {
  /**
   * Erstellen des Frontend-Outputs
   */
  function render($view, &$configurations){
    $this->_init($configurations);
    $cObj =& $configurations->getCObj(0);
    $templateCode = $cObj->fileResource($this->getTemplate($view,'.html'));
    if(!$templateCode) {
      return 'Sorry, template not found!'; // . (' . $this->getTemplate($view,'.html') .')';
    }

    $template = $cObj->getSubpart($templateCode,'###TEAM_VIEW###');

    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();

    $team =& $viewData->offsetGet('team');
    if(is_object($team))
      $out = $this->_createView($template, $team, $cObj, $configurations);
    else
      $out = 'Sorry, no team found...';
    return $out;
  }

  function _createView($template, &$team, &$cObj, &$configurations) {
    $out = '';

    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $teamMarker = new $teamMarkerClass;

    $links = array( 'team' => $this->linkTeam, 
                    'player' => $this->linkPlayer, 
                    'coach' => $this->linkProfile, 
                    'supporter' => $this->linkProfile);

    $out .= $teamMarker->parseTemplate($template, $team, $this->formatter, 'teamview.team.', $links, 'TEAM');


    return $out;
  }

  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    $this->linkPlayer = new $linkClass;
    $this->linkPlayer->designatorString = $configurations->getQualifier();
    $this->linkPlayer->destination(intval($configurations->get('playerPage')));

    $this->linkProfile = new $linkClass;
    $this->linkProfile->designatorString = $configurations->getQualifier();
    $this->linkProfile->destination(intval($configurations->get('profilePage'))); // Das Ziel der Seite vorbereiten

    $this->linkTeam = new $linkClass;
    $this->linkTeam->designatorString = $configurations->getQualifier();
    $this->linkTeam->destination(intval($configurations->get('teamPage'))); // Das Ziel der Seite vorbereiten

  }

//t3lib_div::debug($damPics['files'][603] , 'view_teamview');
//t3lib_div::debug($media, 'view_teamview');

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_TeamView.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_TeamView.php']);
}
?>