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


require_once(tx_rnbase_util_Extensions::extPath('cal').'service/class.tx_cal_event_service.php');
require_once(tx_rnbase_util_Extensions::extPath('cfc_league_fe').'models/class.tx_cfcleaguefe_models_match_calevent.php');


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
	/* @var $configurations tx_rnbase_configurations */

	/**
	 *  Finds all matches.
	 *
	 *  @return array The array of events represented by the model.
	 */
	function findAllWithin($start_date, $end_date, $pidList) {
		/* @var $this->configurations tx_rnbase_configurations */
		$configurations = tx_rnbase::makeInstance('tx_rnbase_configurations');
    $configurations->init($this->conf, null, 'cal', 'cal');
		$this->_init();
		$confId = 'view.cfc_league_events.';

		$matchTable = $this->getMatchTable();
		$start_date = is_object($start_date) ? $start_date->getTime() : $start_date;
		$end_date = is_object($end_date) ? $end_date->getTime() : $end_date;
		$matchTable->setDateRange($start_date, $end_date);
		$matchTable->setPidList($pidList);
		$matchTable->setSaisons($configurations->get($confId.'saisonSelection'));
		$matchTable->setAgeGroups($configurations->get($confId.'groupSelection'));
		$matchTable->setCompetitions($configurations->get($confId.'competitionSelection'));
		$matchTable->setClubs($configurations->get($confId.'clubSelection'));
		$matchTable->setIgnoreDummy($configurations->getBool($confId.'ignoreDummy',false, false));
		$matchTable->setCompetitionTypes($configurations->get($confId.'competitionTypes'));
		$matchTable->setCompetitionObligation($configurations->getInt($confId.'competitionObligation'));
		$matchTable->setLimit($configurations->getInt($confId.'limit'));
		$matchTable->setLiveTicker($configurations->getBool('view.cfc_league_events.livetickerOnly', false, false));

		$fields = array();
		$options = array();
		if($this->conf['view.']['cfc_league_events.']['debug'])
			$options['debug'] = 1;
		$matchTable->getFields($fields, $options);

		tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $confId.'fields.');
		// Optionen
		tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $confId.'options.');

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
	 * @return tx_cfcleague_util_MatchTableBuilder
	 */
	private function getMatchTable() {
		return tx_rnbase::makeInstance('tx_cfcleague_util_MatchTableBuilder');
	}
	/**
	 *  Finds a single event.
	 *
	 *  @return		object			The event represented by the model.
	 */
	function find($uid, $pidList) {
		$this->_init();
		$match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $uid);
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
		$event = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match_calevent', $this->controller, $match, $isException, $this->getServiceKey());
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