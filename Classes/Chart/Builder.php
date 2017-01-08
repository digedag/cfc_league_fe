<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2010-2016 Rene Nitzsche (rene@system25.de)
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


/**
 */
class Tx_Cfcleaguefe_Chart_Builder {

	/**
	 *
	 * @param tx_cfcleaguefe_table_ITableType $table
	 */
	public function buildJson($table, $clubIds, $configurations, $confId) {
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
				if(in_array($scoreArr['clubId'], $clubIds)) {
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
		$chartData['datasets'] = array_values($dataSets);

		return json_encode($chartData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT);

	}
}

