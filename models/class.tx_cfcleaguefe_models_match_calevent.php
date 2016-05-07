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

require_once(tx_rnbase_util_Extensions::extPath('cal').'controller/class.tx_cal_registry.php');
require_once(tx_rnbase_util_Extensions::extPath('cal').'model/class.tx_cal_phpicalendar_model.php');
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * A model for the calendar.
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_models_match_calevent extends tx_cal_phpicalendar_model {

  var $location;
  var $isException;
  var $category;
  var $_match;


  function tx_cfcleaguefe_models_match_calevent(&$controller, &$match, $isException, $serviceKey){
  	$this->tx_cal_model($controller, $serviceKey);
  	$this->createEvent($match, $isException);
  	$this->isException = $isException;
  }

  /**
   * Wir überschreiben die Methode der Basisklasse, damit wir die eigenen Marker verwenden können.
   */
  function fillTemplate($subpartMarker){
    if(is_object($this->cObj))
      $cObj = $this->cObj;
    else {
      $cObj = &tx_cal_registry::Registry('basic','cobj');
    }

    $file = $cObj->fileResource($this->conf['view.']['cfc_league_events.']['template']);
    if ($file == '') {
    	return '<h3>cal: no match template file found:</h3>'.$this->conf['view.']['cfc_league_events.']['template'];
    }

    $template = $cObj->getSubpart($file, $subpartMarker);
    if(!$template){
			preg_match("/###(.*)###/", $subpartMarker, $marker);
    	return 'could not find the -'.$marker[1].'- subpart-marker in view.cfc_league_events.template: '.$this->conf['view.']['cfc_league_events.']['template'];
    }

    $configurations = tx_rnbase::makeInstance('tx_rnbase_configurations');
    $configurations->init($this->conf, $cObj, 'cfc_league_fe', 'cfc_league_fe');
    $this->formatter = &$configurations->getFormatter();

//    $markerArray = $this->formatter->getItemMarkerArrayWrapped($this->_match->record, 'view.cfc_league_events.match.', 0, 'MATCH_');

		$matchMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_MatchMarker');
    $template = $matchMarker->parseTemplate($template, $this->_match, $this->formatter, 'view.cfc_league_events.match.', 'MATCH');

    // Die Original-Marker von Cal mit einbinden
    $markerArray = array();
    $rems = array ();
		$wrapped = array();
		$this->getMarker($template, $markerArray, $rems, $wrapped, $this->conf['view']);
    return tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $rems, $wrapped);

  }

  function createEvent(&$match){
    $this->_match = $match;

    $row = $match->record;
    $this->setType($this->serviceKey);
    $this->setUid($row['uid']);

    // In cal 0.16.x ändert sich das Datumsformat
    if(method_exists($this, 'setStart')) {
  		$start_date = new tx_cal_date($row['date']);
  		$end_date = new tx_cal_date($row['date'] + (60*105));
  		$this->setStart($start_date);
  		$this->setEnd($end_date);
    }
    else {
      $this->setStarttime($row['date']);
      $this->setEndtime($row['date'] + (60*105));
    }

    $this->setTitle('Fussball');
    $this->setSubheader($row['short']);
    $this->setImage($row['image']);
    $this->setDescription($row['bodytext']);
    if($row['title']){
      $this->setCategory($row['title']);
    }
    $this->setLocation($row['stadium']);
  }

  /**
    * Returns the headerstyle name
    */
   function getHeaderStyle(){
   	return $this->conf['view.']['cfc_league_events.']['headerStyle'];
   }

   /**
    * Returns the bodystyle name
    */
   function getBodyStyle(){
   	return $this->conf['view.']['cfc_league_events.']["bodyStyle"];
   }



  function getSubheader(){
  	return $this->subheader;
  }

  function setSubheader($s){
  	$this->subheader = $s;
  }

  function getImage(){
  	return $this->image;
  }

  function setImage($s){
  	$this->image = $s;
  }

  function getUntil(){
  	return 0;
  }

  function getCategory(){
  	return $this->category;
  }

  function setCategory($cat){
  	$this->category = $cat;
  }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match_calevent.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match_calevent.php']);
}
