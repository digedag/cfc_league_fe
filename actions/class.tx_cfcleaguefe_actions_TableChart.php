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

tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');
tx_rnbase::load('tx_cfcleaguefe_util_LeagueTable');

require_once(PATH_t3lib.'error/class.t3lib_error_exception.php');


/**
 * Controller für die Anzeige einer Tabellenfahrt
 */
class tx_cfcleaguefe_actions_TableChart {

  /**
   *
   */
  function execute($parameters,$configurations){

    // Die Werte des aktuellen Scope ermitteln
    $scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
    $saisonUids = $scopeArr['SAISON_UIDS'];
    $groupUids = $scopeArr['GROUP_UIDS'];
    $compUids = $scopeArr['COMP_UIDS'];
    $roundUid = $scopeArr['ROUND_UIDS'];


    //TODO: der folgende Block ist für die Darstellung der Tabelle identisch und wird ggf. doppelt ausgeführt
    $out = '';
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
        return "";
      }
      // Wir müssen den Typ des Wettbewerbs ermitteln.
      $currCompetition = new tx_cfcleaguefe_models_competition($compUids);
      if(intval($currCompetition->record['type']) != 1) {
        return $out;
      }
    }

    // Okay, es ist eine Liga
    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('plot', $this->generateGraph($parameters, $configurations,$currCompetition)); // Die Testplot für den View bereitstellen

    // View
    $view = tx_rnbase::makeInstance('tx_rnbase_view_phpTemplateEngine');
    $view->setTemplatePath($configurations->getTemplatePath());
    $out = $view->render('tablechart', $configurations);
		return $out;
	}

	/**
	 * Erzeugt den Graphen
	 */
	function generateGraph(&$parameters, &$configurations, &$league) {
		$tableProvider = tx_rnbase::makeInstance('tx_cfcleaguefe_util_league_DefaultTableProvider',$parameters,$configurations, $league);

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
		$this->createChartDataset($xyDataset, $tsArr, $configurations, $league);
		try {
			require_once(PATH_site.t3lib_extMgm::siteRelPath('pbimagegraph').'class.tx_pbimagegraph_ts.php');
			$chart = tx_pbimagegraph_ts::make($tsArr);
		}
		catch(Exception $e) {
			$chart = 'Not possible';
		}
		return $chart;
	}

  /**
   * Fügt in das TS-Array die zusätzlichen Daten ein
   */
  function createChartDataset($xyDataset, &$tsArr, &$configurations, &$league, $confId = 'chart.') {

    $defaultLine = $configurations->get($confId.'defaults.line');
    $defaultLineArr = $configurations->get($confId.'defaults.line.');

    $colors = t3lib_div::trimExplode(',',$configurations->get($confId.'defaults.colors'));

    $title = $configurations->get($confId.'defaults.title');
//    t3lib_div::debug($title ,'ac_chart');
    if($tsArr['10.']['10.']['text']) {
      if($title) {
        $tsArr['10.']['10.']['text'] = str_replace('COMPETITION_NAME',$league->record['name'],$title);
        // Hier könnten noch zusätzliche Ersetzungsstrings eingebaut werden..
      }
    }
//t3lib_div::debug($tsArr['10.']['10.']['text'], 'ac_chart');
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

//    t3lib_div::debug($tsArr['10.']['20.']['10.'][$seriesCnt . '.']['lineColor'] ,'ac_chart');
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TableChart.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_TableChart.php']);
}

?>