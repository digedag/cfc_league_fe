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
require_once(PATH_t3lib.'class.t3lib_svbase.php');
tx_div::load('tx_rnbase_util_DB');


/**
 * Service to output a chart to compare two match opponents
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_svmarker_ChartMatch extends t3lib_svbase {

	/**
	 * Generate chart
	 *
	 * @param array $params
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	function getMarkerValue($params, $formatter) {
		return 'Hello Chart!';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php']);
}

?>