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
 * Viewklasse für die Anzeige der Ligatabelle mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_MatchTable extends tx_rnbase_view_Base {

	function createOutput($template, &$viewData, &$configurations, &$formatter){
    $out = $this->_createView($template, $viewData, $configurations);
    return $out;
	}
	
  function getMainSubpart() {return '###MATCHTABLE###';}
	
  /**
   * Erstellung des Outputstrings
   */
  function _createView($template, &$viewData, &$configurations) {
    $cObj =& $this->formatter->cObj;
    $matches = $viewData->offsetGet('matches');
	  $builderClass = tx_div::makeInstanceClassName('tx_rnbase_util_ListBuilder');
	  $listBuilder = new $builderClass(tx_div::makeInstance('tx_cfcleaguefe_util_MatchMarkerBuilderInfo'));
	  
    $out = $listBuilder->render($matches, 
    								$viewData, $template, 'tx_cfcleaguefe_util_MatchMarker', 
    								'matchtable.match.', 'MATCH', $this->formatter);
    return $out;
  }

  /**
   * Vorbereitung der Link-Objekte
   */
  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    $this->links = array();

    $reportPage = $configurations->get('reportPage');
    if($reportPage) {
      $link = new $linkClass;
      $link->designatorString = $configurations->getQualifier();
      $link->destination($reportPage); // Das Ziel der Seite vorbereiten
      $this->links['match'] = $link;
    }
  }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchTable.php']);
}
?>