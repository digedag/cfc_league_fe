<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_view_Base');


/**
 * Viewklasse für die Anzeige eines Spielplans mit Hilfe eines HTML-Templates.
 */
class tx_cfcleaguefe_views_MatchTable extends tx_rnbase_view_Base {

	/**
	 * Erstellung des Outputstrings
	 */
	function createOutput($template, &$viewData, &$configurations, &$formatter){
		$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder', tx_rnbase::makeInstance('tx_cfcleaguefe_util_MatchMarkerBuilderInfo'));

		/* @var $prov tx_rnbase_util_ListProvider */
		$prov = $viewData->offsetGet('provider');
		$out = $listBuilder->renderEach($prov,
							$viewData, $template, 'tx_cfcleaguefe_util_MatchMarker',
							'matchtable.match.', 'MATCH', $formatter);

		return $out;
	}

	function getMainSubpart($viewData) {
		return '###MATCHTABLE###';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchTable.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_MatchTable.php']);
}
?>