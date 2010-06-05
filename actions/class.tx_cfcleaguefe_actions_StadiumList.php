<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Rene Nitzsche (rene@system25.com)
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

tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');


/**
 * 
 */
class tx_cfcleaguefe_actions_StadiumList extends tx_rnbase_action_BaseIOC {
	
	/**
	 * 
	 *
	 * @param array_object $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param array $viewData
	 * @return string error msg or null
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData){
		$srv = tx_cfcleague_util_ServiceRegistry::getStadiumService();

		$filter = tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewData, $this->getConfId());

		$fields = array();
		$filter->init($fields, $options, $parameters, $configurations, $this->getConfId());

		// Soll ein PageBrowser verwendet werden
		tx_rnbase_filter_BaseFilter::handleCharBrowser($configurations, 
			$this->getConfId().'stadium.charbrowser',$viewData, $fields, $options, array(
				'searchcallback'=> array($srv, 'search'),
				'colname' => 'name'
			));
		tx_rnbase_filter_BaseFilter::handlePageBrowser($configurations, 
			$this->getConfId().'stadium.pagebrowser', $viewData, $fields, $options, array(
			'searchcallback'=> array($srv, 'search'),
			'pbid' => 'stadium',
			)
		);

		$items = $srv->search($fields, $options);
		$viewData->offsetSet('items', $items);
    return null;
  }

  function getTemplateName() { return 'stadiumlist';}
	function getViewClassName() { return 'tx_cfcleaguefe_views_StadiumList';}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_StadiumList.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_StadiumList.php']);
}

?>