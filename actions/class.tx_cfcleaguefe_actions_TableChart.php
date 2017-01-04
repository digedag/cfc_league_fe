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

tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_cfcleaguefe_util_LeagueTable');
tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase::load('tx_rnbase_util_Math');
tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('Tx_Rnbase_Utility_Strings');


/**
 * Controller für die Anzeige einer Tabellenfahrt
 */
class tx_cfcleaguefe_actions_TableChart extends tx_rnbase_action_BaseIOC {

	/**
	 *
	 */
	function handleRequest(&$parameters, &$configurations, &$viewData){

		// Die Werte des aktuellen Scope ermitteln
		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters, $configurations);
		// Hook to manipulate scopeArray
		tx_rnbase_util_Misc::callHook('cfc_league_fe', 'action_TableChart_handleScope_hook',
			array('scopeArray' => &$scopeArr, 'parameters' =>$parameters, 'configurations'=> $configurations, 'confId' => $this->getConfId()), $this);
		$saisonUids = $scopeArr['SAISON_UIDS'];
		$groupUids = $scopeArr['GROUP_UIDS'];
		$compUids = $scopeArr['COMP_UIDS'];

		$out = '';
		// Sollte kein Wettbewerb ausgewählt bzw. konfiguriert worden sein, dann suchen wir eine
		// passende Liga
		if(strlen($compUids) == 0) {
			$comps = tx_cfcleaguefe_models_competition::findAll($saisonUids, $groupUids, $compUids, '1');
			if(count($comps) > 0) {
				$currCompetition = $comps[0];
				// Sind mehrere Wettbewerbe vorhanden, nehmen wir den ersten.
				// Das ist aber generell eine Fehlkonfiguration.
			}
			else
				return $out; // Ohne Liga keine Tabelle!
		}
		else {
			// Die Tabelle wird berechnet, wenn der aktuelle Scope auf eine Liga zeigt
			if(!(isset($compUids) && tx_rnbase_util_Math::testInt($compUids))) {
				return $out;
			}
			// Wir müssen den Typ des Wettbewerbs ermitteln.
			$currCompetition = tx_rnbase::makeInstance('tx_cfcleague_models_competition', $compUids);
			if(intval($currCompetition->record['type']) != 1) {
				return $out;
			}
		}

 		$mode = $configurations->get($this->getConfId().'library');
		$mode = $mode ? $mode : 'pbimagegraph';
		if($mode == 'pbimagegraph') {
			// Der alte Weg
			return $this->renderPbImageGraph($parameters, $configurations, $currCompetition);
		}

		$chartData = $this->prepareChartData($scopeArr, $configurations, $this->getConfId());
		$viewData->offsetSet('json', json_encode($chartData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT)); // Die Tabelle für den View bereitstellen
	}

	/**
	 *
	 * @param array $scopeArr
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return multitype:number NULL
	 */
	protected function prepareChartData($scopeArr, $configurations, $confId) {
		tx_rnbase::load('tx_cfcleaguefe_table_Builder');
		$table = tx_cfcleaguefe_table_Builder::buildByRequest($scopeArr, $configurations, $this->getConfId());
		// Aus den Table-Daten jetzt die DataSets erzeugen
		$chartData = array();
		// Anzahl der Plätze
		$chartData['ymax'] = count($table->getTableData()->getScores());
		// Anzahl der Spielrunden
		$chartData['xmax'] = $table->getMatchProvider()->getMaxRounds();

		$cObj = $configurations->getCObj();
		$cObjData = $cObj->data;

		$dataSets = array();
		$data = $table->getTableData();
		for($i=1; $i <= $data->getRoundSize(); $i++) {
			$scores = $data->getScores($i);
			foreach ($scores As $scoreArr) {
				if(in_array($scoreArr['clubId'], $this->getChartClubs())) {
					$point = array($i, $scoreArr['position']);
					if(!isset($dataSets[$scoreArr['teamId']])) {
						// Basisdaten setzen
						$team = $scoreArr['team'];
						$cObj->data = $team->getProperty();
						$logo = $cObj->cObjGetSingle(
								$configurations->get($confId.'team.logo'), $configurations->get($confId.'team.logo.'));
						$dataSets[$scoreArr['teamId']] = array(
								'info' => array(
										'teamid'=> $team->getProperty('uid'),
										'clubid'=> $scoreArr['clubId'],
										'name'=> $team->getProperty('name'),
										'short_name'=> $team->getProperty('short_name'),
										'logo' => $logo,
								),
						);
					}
					$dataSets[$scoreArr['teamId']]['data'][] = $point;
				}
			}
		}
		$cObj->data = $cObjData;
		// Issue 1880245: Chart auf der X-Achse bis Saisonende erweitern
		// Den höchsten absolvierten Spieltag ermitteln
		// 		$lastRound = $table->getTableData()->getRoundSize() + 1;
		// 		$maxRound = $table->getMatchProvider()->getMaxRounds();
		// 		$teamName = array_pop(array_keys($xyData));
		// 		for( ; $lastRound <= $maxRound; $lastRound++) {
		// 			// Es muss nur für ein Team ein weiterer Wert hinzugefügt werden
		// 			$xyData[$teamName][$lastRound] = null;
		// 		}
		$chartData['datasets'] = array_values($dataSets);
		return $chartData;
	}
	protected function getChartClubs(){
		return Tx_Rnbase_Utility_Strings::intExplode(',',$this->getConfigurations()->get($this->getConfId().'chartClubs'));
	}



	/**
	 * Der Chart wird serverseitig mit pgimagegraph generiert.
	 * @param unknown $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param tx_cfcleague_competition $currCompetition
	 * @return unknown
	 */
	protected function renderPbImageGraph($parameters, $configurations, $currCompetition) {
		// Okay, es ist eine Liga
		$viewData =& $configurations->getViewData();
		$viewData->offsetSet('plot', $this->generateGraph($parameters, $configurations, $currCompetition)); // Die Testplot für den View bereitstellen

		// View
		$view = tx_rnbase::makeInstance('tx_rnbase_view_phpTemplateEngine');
		$view->setTemplatePath($configurations->getTemplatePath());
		$out = $view->render('tablechart', $configurations);
		return $out;
	}
	/**
	 * Erzeugt den Graphen über die alte Table-API
	 */
	protected function generateGraph($parameters, $configurations, $league) {
		$tableProvider = tx_rnbase::makeInstance('tx_cfcleaguefe_util_league_DefaultTableProvider', $parameters, $configurations, $league, $this->getConfId());

		$leagueTable = new tx_cfcleaguefe_util_LeagueTable();
		$xyDataset = $leagueTable->generateChartData($tableProvider);

		$tsArr = $configurations->get('chart.');
/*
    $xyDataset = Array(
      'CFC' => Array('1' => '3', '2' => '3', '3' => '1'),
      'HFC' => Array('1' => '5', '2' => '4', '3' => '4'),
      'Cottbus' => Array('1' => '1', '2' => '1', '3' => '2')
    );
*/
//tx_rnbase_util_Debug::debug($xyDataset,__FILE__.':'.__LINE__); // TODO: remove me
		$this->createChartDataset($xyDataset, $tsArr, $configurations, $league);
		try {
			tx_rnbase::load('tx_rnbase_plot_Builder');
			$chart = tx_rnbase_plot_Builder::getInstance()->make($tsArr, false);
		}
		catch(Exception $e) {
			$chart = 'Not possible';
			tx_rnbase::load('tx_rnbase_util_Logger');
			tx_rnbase_util_Logger::warn('Chart creation failed!', 'cfc_league_fe', array('Exception' => $e->getMessage()));
		}
		return $chart;
	}

	/**
	 * Fügt in das TS-Array die zusätzlichen Daten ein
	 */
	protected function createChartDataset($xyDataset, &$tsArr, &$configurations, &$league, $confId = 'chart.') {

		$defaultLine = $configurations->get($confId.'defaults.line');
		$defaultLineArr = $configurations->get($confId.'defaults.line.');

		$colors = Tx_Rnbase_Utility_Strings::trimExplode(',', $configurations->get($confId.'defaults.colors'));

		$title = $configurations->get($confId.'defaults.title');
		if($tsArr['10.']['10.']['text']) {
			if($title) {
				$tsArr['10.']['10.']['text'] = str_replace('COMPETITION_NAME', $league->record['name'], $title);
				// Hier könnten noch zusätzliche Ersetzungsstrings eingebaut werden..
			}
		}
		// Maximum ist die Anzahl der Teams in der Liga
		$tsArr['10.']['20.']['10.']['axis.']['y.']['forceMaximum'] = count($league->getTeams());

		$seriesCnt = 20;
		$seriesIdx = 0;
		foreach($xyDataset As $key => $series) {
			$tsArr['10.']['20.']['10.'][$seriesCnt] = $defaultLine ? $defaultLine : 'LINE';
			$tsArr['10.']['20.']['10.'][$seriesCnt . '.'] = $defaultLineArr;

			$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['title'] = $key;
			// Wo muss die Farbe rein?
			if(isset($tsArr['10.']['20.']['10.'][$seriesCnt . '.']['lineStyle']))
				$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['lineStyle.']['color'] = $colors[$seriesIdx] ? $colors[$seriesIdx] : red;
			else
				$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['lineColor'] = $colors[$seriesIdx] ? $colors[$seriesIdx] : red;

			// Jetzt die Daten rein
			$dataCnt = 10;
			$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['dataset.']['10'] = 'trivial';
			foreach($series As $x => $y) {
				$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['dataset.']['10.'][$dataCnt] = 'point';
				$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['dataset.']['10.'][$dataCnt. '.']['x'] = $x;
				$tsArr['10.']['20.']['10.'][$seriesCnt . '.']['dataset.']['10.'][$dataCnt. '.']['y'] = $y;
				$dataCnt += 10;
			}
			$seriesCnt += 10;
			$seriesIdx++;
		}
	}

	function getTemplateName() { return 'tablechart';}
	function getViewClassName() { return 'tx_cfcleaguefe_views_TableChart';}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TableChart.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TableChart.php']);
}
