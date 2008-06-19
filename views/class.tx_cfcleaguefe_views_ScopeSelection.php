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

tx_div::load('tx_rnbase_view_Base');


/**
 * Viewklasse für die Anzeige der Scope-Auswahl mit Hilfe eines HTML-Templates. Die Verlinkung
 * erfolgt nicht mehr über ein HTML-Formular, sondern mit echten Links, wodurch die 
 * Caching-Mechanismen von TYPO3 zur Wirkung kommen können.
 */
class tx_cfcleaguefe_views_ScopeSelection extends tx_rnbase_view_Base {

	function getMainSubpart() {return '###SCOPE_SELECTION###';}
	
  /**
   * Erstellen des Frontend-Outputs
   */
	function createOutput($template, &$viewData, &$configurations, &$formatter){
    $cObj =& $configurations->getCObj(0);

    $out = '';
    $markerArray = array();
    $subpartArray['###SAISON_SELECTION###'] = '';
    $subpartArray['###GROUP_SELECTION###'] = '';
    $subpartArray['###COMPETITION_SELECTION###'] = '';
    $subpartArray['###ROUND_SELECTION###'] = '';
    $subpartArray['###CLUB_SELECTION###'] = '';
    
    // Wenn Saison gezeigt werden soll, dann Abschnitt erstellen
    if($viewData->offsetGet('saison_select')) {
      // Das Template holen
      $subTemplate = $cObj->getSubpart($template, '###SAISON_SELECTION###');

      $items = $viewData->offsetGet('saison_select');
      $subpartArray['###SAISON_SELECTION###'] = 
          $this->_fillTemplate($subTemplate, $items, $this->link, 'SAISON', $configurations);
    }

    // Wenn Altersklasse gezeigt werden soll, dann Abschnitt erstellen
    if($viewData->offsetGet('group_select')) {
      // Das Template holen
      $subTemplate = $cObj->getSubpart($template, '###GROUP_SELECTION###');

      $items = $viewData->offsetGet('group_select');
      $subpartArray['###GROUP_SELECTION###'] = 
          $this->_fillTemplate($subTemplate, $items, $this->link, 'GROUP', $configurations);
    }

    // Wenn Wettbewerb gezeigt werden soll, dann Abschnitt erstellen
    if($viewData->offsetGet('competition_select')) {
      // Das Template holen
      $subTemplate = $cObj->getSubpart($template, '###COMPETITION_SELECTION###');

      $items = $viewData->offsetGet('competition_select');
      $subpartArray['###COMPETITION_SELECTION###'] = 
          $this->_fillTemplate($subTemplate, $items, $this->link, 'COMPETITION', $configurations);
    }
    // Wenn Spieltag gezeigt werden soll, dann Abschnitt erstellen
    if($viewData->offsetGet('round_select')) {
      // Das Template holen
      $subTemplate = $cObj->getSubpart($template, '###ROUND_SELECTION###');

      $items = $viewData->offsetGet('round_select');
      $subpartArray['###ROUND_SELECTION###'] = 
          $this->_fillTemplate($subTemplate, $items, $this->link, 'ROUND', $configurations);
    }
    // Wenn Verein gezeigt werden soll, dann Abschnitt erstellen
    if($viewData->offsetGet('club_select')) {
      // Das Template holen
      $subTemplate = $cObj->getSubpart($template, '###CLUB_SELECTION###');
      $items = $viewData->offsetGet('club_select');
      $subpartArray['###CLUB_SELECTION###'] = 
          $this->_fillTemplate($subTemplate, $items, $this->link, 'CLUB', $configurations);
    }
    
    $out .= $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);

    return $out;
  }

  /**
   * Erstellt die einzelnen Teile der Scopeauswahl. 
   * @param string $template HTML- Template
   * @param array &$itemsArr Datensätze für die Auswahl
   * @param tx_lib_link &$link Linkobjekt
   * @param string $markerName Name des Markers (SAISON, ROUND usw.)
   * @param tx_rnbase_configurations &$configurations Config-Objekt
   */
  function _fillTemplate($template, &$itemsArr, &$link, $markerName, &$configurations) {
    $items = $itemsArr[0];
    $currItem = $items[$itemsArr[1]];
    $confName = strtolower($markerName); // Konvention
    $noLink = array('','');

    // Aus den KeepVars den aktuellen Wert entfernen
    $keepVars = $configurations->getKeepVars()->getArrayCopy();
    unset($keepVars[strtolower($markerName)]);

    if($link) {
      $token = md5(microtime());
      $link->label($token);
    }

    // Das Template für die einzelnen Datensätze
    $subTemplate = $this->formatter->cObj->getSubpart($template, '###' . $markerName . '_SELECTION_2###');

    $currentNoLink = intval($configurations->get('scopeSelection.'. $confName .'.current.noLink'));

    $parts = array();
    // Jetzt über die vorhandenen Items iterieren
    foreach($items As $item) {
      $keepVars[strtolower($markerName)] = $item->uid;
      $link->parameters($keepVars);
      $isCurrent = ($item->uid == $currItem->uid);
      $item->record['isCurrent'] = $isCurrent ? 1 : 0;
//t3lib_div::debug($itemConfId, 'tx_cfcleaguefe_views_ScopeSelection');
      $markerArray = $this->formatter->getItemMarkerArrayWrapped($item->record, 'scopeSelection.'. $confName.'.', 0, $markerName.'_',$item->getColumnNames());
//      $markerArray['###'. $markerName .'_LINK_URL###'] = $this->formatter->wrap($link->makeUrl(false), 'scopeSelection.'. $confName . ($isCurrent ? '.current.' : '.normal.') );
      $markerArray['###'. $markerName .'_LINK_URL###'] = $link->makeUrl(false);

      $linkStr = ($currentNoLink && $isCurrent) ? $token : $link->makeTag();
      // Ein zusätzliche Wrap um das generierte Element inkl. Link
      $linkStr = $this->formatter->wrap($linkStr, 'scopeSelection.'. $confName . (($item->uid == $currItem->uid) ? '.current.' : '.normal.') );
      $wrappedSubpartArray['###'.$markerName.'_LINK###'] = explode($token, $linkStr);
      
//      if($currentNoLink && $item->uid == $currItem->uid)
//        $wrappedSubpartArray['###'.$markerName.'_LINK###'] = $noLink;
//      else
//        $wrappedSubpartArray['###'.$markerName.'_LINK###'] = explode($token, $link->makeTag());
      
      $parts[] = $this->formatter->cObj->substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);
      unset($keepVars[strtolower($markerName)]);
    }
    // Jetzt die einzelnen Teile zusammenfügen
    $out = implode($parts, $configurations->get('scopeSelection.'. $confName .'.implode'));

    // Im Haupttemplate stellen wir die ausgewählte Saison als Marker zur Verfügung
    $markerArray = $this->formatter->getItemMarkerArrayWrapped($currItem->record, $itemConfId, 0, $markerName.'_',$currItem->getColumnNames());
    $subpartArray['###' . $markerName . '_SELECTION_2###'] = $out;
    
    $out = $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);

   
    return $out;
  }

  /**
   * Vorbereitung der Link-Objekte
   */
  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    // Die Zielseite ist entweder eine TYPO3 Seite oder eine externe URL
    $pid = $GLOBALS['TSFE']->id; // Das Ziel der Seite vorbereiten

    $this->link = new $linkClass;
    $this->link->designatorString = $configurations->getQualifier();
    $this->link->destination($pid); // Das Ziel der Seite vorbereiten

  }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ScopeSelection.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_ScopeSelection.php']);
}
?>