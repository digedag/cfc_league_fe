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
tx_div::load('tx_rnbase_action_BaseIOC');

/**
 * Action für die Anzeige eines Spielberichts
 */
class tx_cfcleaguefe_actions_MatchReport extends tx_rnbase_action_BaseIOC {
	/**
	 * handle request
	 *
	 * @param arrayobject $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param arrayobject $viewData
	 * @return string
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData) {
		// Die MatchID ermittlen
		// Ist sie fest definiert?
		$matchId = intval($configurations->get('matchreportMatchUid'));
		if(!$matchId) {
			$matchId = intval($parameters->offsetGet('matchId'));
			if($matchId == 0)
				return 'No matchId found!';
		}

		// Das Spiel laden
		$className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_matchreport');
		$matchReport = new $className($matchId, $configurations);
		$viewData->offsetSet('matchReport', $matchReport); // Den Spielreport für den View bereitstellen

		// Auf das Template verzweigen
		$this->viewType = $configurations->get('matchreport.viewType');
		return null;
	}

	function getTemplateName() {return 'matchreport';}
	function getViewClassName() { return ($this->viewType == 'HTML') ? 'tx_cfcleaguefe_views_MatchReport' : 'tx_rnbase_view_phpTemplateEngine'; }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchReport.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_MatchReport.php']);
}

?>