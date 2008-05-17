<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Rene Nitzsche (rene@system25.de)
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
 * Controller für die Anzeige einer Personenliste
 * Die Liste wird sortiert nach Namen angezeigt. Dabei wird ein Pager verwendet, der für
 * jeden Buchstaben eine eigene Seite erstellt.
 */
class tx_cfcleaguefe_actions_ProfileList extends tx_rnbase_action_BaseIOC {

	/**
	 * handle request
	 *
	 * @param arrayobject $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param arrayobject $viewData
	 * @return string
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData) {
// Zunächst sollten wir die Anfangsbuchstaben ermitteln
    $service = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
		
		if($configurations->get('profilelist.charbrowser')) {
			$pagerData = $this->findPagerData($service, $configurations);

			$firstChar = $parameters->offsetGet('charpointer');
			$firstChar = (strlen(trim($firstChar)) > 0) ? substr($firstChar,0,1) : $pagerData['default'];
			$viewData->offsetSet('pagerData', $pagerData);
			$viewData->offsetSet('charpointer', $firstChar);
		}

		$fields = array();
		$options = array('count'=> 1);
		$this->initSearch($fields, $options, $parameters, $configurations, $firstChar);
		$listSize = $service->search($fields, $options);
		unset($options['count']);
		// PageBrowser initialisieren
		$className = tx_div::makeInstanceClassName('tx_rnbase_util_PageBrowser');
		$pageBrowser = new $className('profiles');
		$pageSize = $this->getPageSize($parameters, $configurations);
		//Wurde neu gesucht?
		if($parameters->offsetGet('plnewsearch')) {
			$pageBrowser->setState(null, $listSize, $pageSize);
			$configurations->removeKeepVar('plnewsearch');
		}
		else {
			$pageBrowser->setState($parameters, $listSize, $pageSize);
		}
		$limit = $pageBrowser->getState();
		$options = array_merge($options, $limit);
		$result = $service->search($fields, $options);
		$viewData->offsetSet('profiles', $result);
		$viewData->offsetSet('pagebrowser', $pageBrowser);

		return null;
	}
  protected function initSearch(&$fields, &$options, &$parameters, &$configurations, $firstChar) {
  	// ggf. die Konfiguration aus der TS-Config lesen
  	tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'profilelist.fields.');
  	tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'profilelist.options.');

  	if($firstChar) {
			$specials = tx_rnbase_util_SearchBase::getSpecialChars();
			$firsts = $specials[$firstChar];
			if($firsts) {
				$firsts = implode('\',\'',$firsts);
			}
			else $firsts = $firstChar;
  		
  		$fields[SEARCH_FIELD_CUSTOM] = "LEFT(UCASE(last_name),1) IN ('$firsts') ";;
  	}
  }
	
  /**
   * Wir verwenden einen alphabetischen Pager. Also muß zunächst ermittelt werden, welche
   * Buchstaben überhaupt vorkommen.
   * @param tx_cfcleaguefe_ProfileService $service
   * @param tx_rnbase_configurations $configurations
   */
  function findPagerData(&$service, &$configurations) {

  	$options['what'] = 'LEFT(UCASE(last_name),1) As first_char, count(LEFT(UCASE(last_name),1)) As size';
    $options['groupby'] = 'LEFT(UCASE(last_name),1)';
  	$fields = array();
  	tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, 'profilelist.fields.');
  	tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'profilelist.options.');

    $from = 'tx_cfcleague_profiles';

    $rows = $service->search($fields, $options);

    $specials = tx_rnbase_util_SearchBase::getSpecialChars();
  	$wSpecials = array();
  	foreach($specials As $key => $special) {
  		foreach ($special As $char) {
  			$wSpecials[$char] = $key;
  		}
  	}
    
  	$ret = array();
    foreach($rows As $row) {
      if(array_key_exists(($row['first_char']), $wSpecials)) {
      	$ret[$wSpecials[$row['first_char']]] = intval($ret[$wSpecials[$row['first_char']]]) + $row['size'];
      }
      else
	    	$ret[$row['first_char']] = $row['size'];
    }
    
    $current = 0;
    if(count($ret)) {
      $keys = array_keys($ret);
      $current = $keys[0];
    }
    $data['list'] = $ret;
    $data['default'] = $current;
    return $data;
  }
  /**
   * Liefert die Anzahl der Ergebnisse pro Seite
   *
   * @param array $parameters
   * @param tx_rnbase_configurations $configurations
   * @return int
   */
  protected function getPageSize(&$parameters, &$configurations) {
  	return intval($configurations->get('profilelist.profile.pagebrowser.limit'));
  }
  
	function getTemplateName() {return 'profilelist';}
	function getViewClassName() { return 'tx_cfcleaguefe_views_ProfileList'; }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileList.php']);
}

?>