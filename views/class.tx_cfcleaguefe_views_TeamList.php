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
 * Viewklasse für die Anzeige des Kalenders
 */
class tx_cfcleaguefe_views_TeamList extends tx_rnbase_view_Base {
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

    $template = $cObj->getSubpart($templateCode,'###TEAM_LIST###');

    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();
    $teams =& $viewData->offsetGet('teams');
    if(is_array($teams))
      $out = $this->_createView($template, $teams, $cObj, $configurations);
    else
      $out = 'Sorry, no teams found...';
    return $out;
  }

  function _createView($template, &$teams, &$cObj, &$configurations) {
    $out = '';

    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $teamMarker = new $teamMarkerClass;
    $subTemplate = $cObj->getSubpart($template, '###TEAM###');
    $rowRoll = intval($configurations->get('teamlist.team.roll.value'));
    $rowRollCnt = 0;
    $parts = array();
    
    $links = array( 'team' => $this->linkTeam, 
                    'player' => $this->linkPlayer, 
                    'coach' => $this->linkProfile, 
                    'supporter' => $this->linkProfile);

    for($i = 0, $size = count($teams); $i < $size; $i++) {
      $team = $teams[$i];
      $team->record['roll'] = $rowRollCnt;
      $parts[] = $teamMarker->parseTemplate($subTemplate, $team, $this->formatter, 'teamlist.team.', $links, 'TEAM');
      $rowRollCnt = ($rowRollCnt >= $rowRoll) ? 0 : $rowRollCnt + 1;
    }

    $subpartArray['###TEAM###'] = implode($parts, $configurations->get('teamlist.team.implode'));
    $markerArray = array('###TEAMCOUNT###' => count($teams), );
    
    return $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
  }

  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    $this->linkPlayer = new $linkClass;
    $this->linkPlayer->designatorString = $configurations->getQualifier();
    $this->linkPlayer->destination(intval($configurations->get('teamlist.playerPage')));

    $this->linkProfile = new $linkClass;
    $this->linkProfile->designatorString = $configurations->getQualifier();
    $this->linkProfile->destination(intval($configurations->get('teamlist.profilePage'))); // Das Ziel der Seite vorbereiten

    $this->linkTeam = new $linkClass;
    $this->linkTeam->designatorString = $configurations->getQualifier();
    $this->linkTeam->destination(intval($configurations->get('teamlist.teamPage'))); // Das Ziel der Seite vorbereiten
  }

//t3lib_div::debug($damPics['files'][603] , 'view_teamview');
//t3lib_div::debug($media, 'view_teamview');

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_TeamList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_TeamList.php']);
}
?>