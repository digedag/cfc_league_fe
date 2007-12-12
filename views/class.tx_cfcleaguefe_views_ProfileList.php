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


/**
 * Viewklasse für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_views_ProfileList extends tx_rnbase_view_Base {
  /**
   * Erstellen des Frontend-Outputs
   */
  function render($view, &$configurations){
    $this->_init($configurations);
    $cObj =& $configurations->getCObj(0);

    $templateCode = $cObj->fileResource($this->getTemplate($view,'.html'));

    $template = $cObj->getSubpart($templateCode,'###PROFILE_LIST###');

    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();


    $profiles =& $viewData->offsetGet('profiles');
    $pagerData =& $viewData->offsetGet('pagerData');
    $pointer = $viewData->offsetGet('pointer');

    $markerArray = array(); // Eventuell später für allgemeine Daten oder Labels
    $subpartArray['###PAGE_BROWSER###'] = $this->_createPager(
                                              $cObj->getSubpart($template,'###PAGE_BROWSER###'), 
                                              $markerArray, 
                                              $pagerData, 
                                              $pointer ,$cObj, $configurations);

    $subpartArray['###PROFILES###'] = $this->_createPlayer(
                                              $cObj->getSubpart($template,'###PROFILES###'), 
                                              $markerArray, 
                                              $profiles, 
                                              $cObj, $configurations);


    // Zum Schluß das Haupttemplate zusammenstellen
    $out = $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);

//    $out = $this->_createView($template, $profile, $cObj, $configurations);

//t3lib_div::debug($profiles  , 'view_profile');

    return $out ;
  }


  function _createPlayer($template, $markerArray, &$profiles, &$cObj, &$configurations) {
    $out = '';

    $token = md5(microtime());
    $this->linkProfile->label($token);
    $emptyArr = array();
    $noLink = array('','');
    $profileTemplate = $cObj->getSubpart($template,'###PROFILE###');

    $profileMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_ProfileMarker');
    $profileMarker = new $profileMarkerClass;


    for($i = 0; $i < count($profiles); $i++) {
      $profile = $profiles[$i];

      $out .= $profileMarker->parseTemplate($profileTemplate, $profile, $this->formatter, 'profilelist.profile.', $this->linkProfile);
    }

//t3lib_div::debug($out1, 'view_profile');

    if(count($profiles)) {
      // Zum Schluß das Haupttemplate zusammenstellen
      $subpartArray['###PROFILE###'] = $out;
      $out = $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);
    }
    else { // Keine Spieler vorhanden, es wird ein leerer String gesendet
      $out = '';
    }
    return $out;
  }

  /**
   * Liefert den Pagerstring
   * TODO: Hier müssen noch Formatierungen eingebaut werden!
   */
  function _createPager($template, $markerArray, &$pagerData, $curr_pointer , &$cObj, &$configurations) {
    $out = array();

    $token = md5(microtime());
    $this->linkList->label($token);
    $emptyArr = array();

    while(list($pointer, $size) = each($pagerData)) {
      $myMarkerArray = $markerArray;
      $myMarkerArray['###PB_ITEM###'] = $pointer;
      $myMarkerArray['###PB_ITEM_SIZE###'] = $size;

//t3lib_div::debug($pointer . '!=' . $curr_pointer  , 'view_profile');

      if(strcmp($pointer, $curr_pointer)) {
        $this->linkList->parameters(array('pointer' => $pointer));
        $wrappedSubpartArray['###PB_ITEM_LINK###'] = explode($token, $this->linkList->makeTag());
      }
      else
        $wrappedSubpartArray['###PB_ITEM_LINK###'] = $emptyArr;
//      $subpartArray['###PB_ITEM_LINK###']
      $out[] = $cObj->substituteMarkerArrayCached($template, $myMarkerArray, $emptyArray, $wrappedSubpartArray);
    }

    return implode('-',$out);
  }

  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    $target = intval($configurations->get('profilelistPage'));
    $this->linkProfile = new $linkClass;
    $this->linkProfile->designatorString = $configurations->getQualifier();
    $this->linkProfile->destination($target); // Das Ziel der Seite vorbereiten

    $cObj = &$configurations->getCObj(0);
    $target = $cObj->data['pid'];
//t3lib_div::debug($cObj->data['pid'], 'view_profile');

    $this->linkList = new $linkClass; // Link auf die eigene Seite
    $this->linkList->designatorString = $configurations->getQualifier();
    $this->linkList->destination($target); // Das Ziel der Seite vorbereiten

  }

//t3lib_div::debug($damPics['files'][603] , 'view_teamview');
//t3lib_div::debug($media, 'view_teamview');

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ProfileList.php']);
}
?>