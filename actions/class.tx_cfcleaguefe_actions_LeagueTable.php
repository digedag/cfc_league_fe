<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_cfcleaguefe_models_competition');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');

tx_rnbase::load('tx_rnbase_action_BaseIOC');

/**
 * Controller für die Anzeige eines Liga-Tabelle
 * TODO: Umstellung der Tabellenerzeugung:
 * Zur Berechnung einer Tabelle werden zunächst die gewünschten Spiele benötigt. Diese können über die MatchTable bereitgestellt 
 * werden. Die Spiele werden dann einem LeagueTableProvider übergeben. Dieser kann die Spiele für die berechnung der Tabelle aufbereiten.
 * 
 * Dann muss über alle Spiele iteriert werden. Jedes Spiel wird einer Visitorklasse 
 * übergeben, die die Punkte ermittelt. Diese Visitorklasse wird vom Wettbewerb bereitgestellt und hängt von der Sportart ab.
 */
class tx_cfcleaguefe_actions_LeagueTable extends tx_rnbase_action_BaseIOC {

	/**
	 * Zeigt die Tabelle für eine Liga. Die Tabelle wird nur dann berechnet, wenn auf der
	 * aktuellen Seite genau ein Wettbewerb ausgewählt ist und dieser Wettbewerb eine Liga ist.
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData){

		// Die Werte des aktuellen Scope ermitteln
		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
		// Hook to manipulate scopeArray
		tx_rnbase_util_Misc::callHook('cfc_league_fe','action_LeagueTable_handleScope_hook',
			array('scopeArray' => &$scopeArr, 'parameters' =>$parameters, 'configurations'=> $configurations, 'confId' => $this->getConfId()), $this);
		$saisonUids = $scopeArr['SAISON_UIDS'];
		$groupUids = $scopeArr['GROUP_UIDS'];
		$compUids = $scopeArr['COMP_UIDS'];
		$roundUid = $scopeArr['ROUND_UIDS'];


		$out = ' ';
		// Sollte kein Wettbewerb ausgewählt bzw. konfiguriert worden sein, dann suchen wir eine
		// passende Liga
		if(strlen($compUids) == 0) {
			$comps = tx_cfcleaguefe_models_competition::findAll($saisonUids, $groupUids, $compUids, '1');
			if(count($comps) > 0)
				$currCompetition = $comps[0];
				// Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
				// Das ist aber generell eine Fehlkonfiguration.
			else
				return $out; // Ohne Liga keine Tabelle!
		}
		else {
			// Die Tabelle wird berechnet, wenn der aktuelle Scope auf eine Liga zeigt
			if(!(isset($compUids) && t3lib_div::testInt($compUids))) {
				return $out;
			}
			// Wir müssen den Typ des Wettbewerbs ermitteln.
			$currCompetition = new tx_cfcleaguefe_models_competition($compUids);
			if(intval($currCompetition->record['type']) != 1) {
				return $out;
			}
		}

		// Okay, es ist mindestens eine Liga enthalten
		tx_rnbase::load('tx_cfcleaguefe_table_Builder');
		$table = tx_cfcleaguefe_table_Builder::buildByRequest($scopeArr, $configurations, $this->getConfId());

//		$viewData->offsetSet('tableData', $dataArr['table']); // Die Tabelle für den View bereitstellen
//		$viewData->offsetSet('tablePointSystem', $dataArr['pointsystem']); // Die Tabelle für den View bereitstellen
//		$viewData->offsetSet('league', $currCompetition); // Die Liga für den View bereitstellen
		$viewData->offsetSet('table', $table); // Die Tabelle für den View bereitstellen
		
//		t3lib_div::debug($table->getTableData(), 'class.tx_cfcleaguefe_actions_LeagueTable.php'); // TODO: remove me

//		// Müssen zusätzliche Selectboxen gezeigt werden?
//		$this->_handleSBTableType($parameters, $configurations, $viewData);
//		$this->_handleSBPointSystem($parameters, $configurations, $viewData);
//		$this->_handleSBTableScope($parameters, $configurations, $viewData);

		return '';
	}


  function getTemplateName() { return 'leaguetable';}
	function getViewClassName() { return 'tx_cfcleaguefe_views_LeagueTable';}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LeagueTableShow.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_LeagueTableShow.php']);
}

?>