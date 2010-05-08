<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_util_ListMarkerInfo');

/**
 * 
 */
class tx_cfcleaguefe_util_MatchMarkerListInfo extends tx_rnbase_util_ListMarkerInfo {

  function init($template, &$formatter, $marker) {
    // Im Template ist noch das Template für Spielfrei enthalten
    $this->freeTemplate = $formatter->cObj->getSubpart($template, '###'.$marker.'_FREE###');
    // Dieses enfernen wir jetzt direkt aus dem Template
    $subpartArray['###'.$marker.'_FREE###'] = '';
    $this->template = $formatter->cObj->substituteMarkerArrayCached($template, array(), $subpartArray);
  }
  
	function getTemplate(&$item) {
		return $item->isDummy() ? $this->freeTemplate : $this->template;
	}
  
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchMarkerListInfo.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_MatchMarkerListInfo.php']);
}
?>