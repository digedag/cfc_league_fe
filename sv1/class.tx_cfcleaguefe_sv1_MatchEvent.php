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

require_once(t3lib_extMgm::extPath('cal').'service/class.tx_cal_event_service.php');
require_once(t3lib_extMgm::extPath('cfc_league_fe').'models/class.tx_cfcleaguefe_models_match_calevent.php');

/**
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv1_MatchEvent extends tx_cal_event_service {

	var $callegenddescription;
	var $calnumber = 6;
	var $subheader;
	var $image;
	var $category = 'Matches';
  
	/**
	 *  Finds all matches.
	 *
	 *  @return array The array of events represented by the model.
	 */
	function findAllWithin($start_date, $end_date, $pidList) {
		$this->_init();

		// Aus der Config holen wir die möglichen Einschränkungen für die Suche
		$saisons = $this->conf['view.']['cfc_league_events.']['saisonSelection'];
		$groups = $this->conf['view.']['cfc_league_events.']['groupSelection'];
		$competitions = $this->conf['view.']['cfc_league_events.']['competitionSelection'];
		$club = $this->conf['view.']['cfc_league_events.']['clubSelection'];


		$arr = array();
		$matchTable = $this->getMatchTable();
		$start_date = is_object($start_date) ? $start_date->getTime() : $start_date;
		$end_date = is_object($end_date) ? $end_date->getTime() : $end_date;
		$matchTable->setDateRange($start_date, $end_date);
		$matchTable->setPidList($pidList);

		$fields = array();
		$options = array();
		if($this->conf['view.']['cfc_league_events.']['debug'])
			$options['debug'] = 1;
		$matchTable->getFields($fields, $options);

		$srv = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matches = $srv->search($fields, $options);

		$events = array();

		foreach($matches as $match) {
			$events[date('Ymd',$match->record['date'])][date('Hi',$match->record['date'])] [$match->uid] = $this->createEvent($match, false);		
		}
		return $events;
	}
	/**
	 * Returns a new matchtable instance
	 *
	 * @return tx_cfcleaguefe_util_MatchTable
	 */
	private function getMatchTable() {
		return tx_div::makeInstance('tx_cfcleaguefe_util_MatchTable');
	}
	/**
	 *  Finds a single event.
	 *
	 *  @return		object			The event represented by the model.
	 */	
	function find($uid, $pidList) {
		$this->_init();
		$className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_match');
		$match = new $className($uid);
		$event = $this->createEvent($match, false);
/*
    $events = array();
    $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("*", "tt_news", " uid=".$uid);
    while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {	
    	$event = $this->createEvent($row, false);
    }
*/
		return $event;
	}

	function createEvent($match, $isException){
		$event_model = &t3lib_div::makeInstanceClassName('tx_cfcleaguefe_models_match_calevent');
		$event = &new $event_model($this->controller, $match, $isException, $this->getServiceKey());
		return $event;
	}

	/**
	 *  Gets the legend description.
	 *
	 *  @return		array		The legend array.
	 */
	function getCalLegendDescription() {
		return $this->callegenddescription;
	}

	/**
	 * TODO Implement search function!
	 *
	 * @param string $pidList
	 * @param date $starttime
	 * @param date $endtime
	 * @param string $searchword
	 * @param array $locationIds
	 * @return array
	 */
	function search($pidList='', $starttime, $endtime, $searchword, $locationIds) {
		return array();
//    return parent::search($pidList, $starttime, $endtime, $searchword, $locationIds);
	}
	function _init(){
		$legendArray = array("title" => $this->conf['view.']['cfc_league_events.']['legendDescription']);
		$this->callegenddescription = array($this->conf['view.']['cfc_league_events.']['legendCalendarName'] => array(array($this->conf['view.']['cfc_league_events.']['headerStyle']=>$legendArray)));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_MatchEvent.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv1/class.tx_cfcleaguefe_sv1_MatchEvent.php']);
}

?>