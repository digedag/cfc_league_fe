<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_cfcleague_search_Builder');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');


/**
 * Default filter for matches
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_filter_Match extends tx_rnbase_filter_BaseFilter {
	/**
	 * Abgeleitete Filter können diese Methode überschreiben und zusätzliche Filter setzen
	 *
	 * @param array $fields
	 * @param array $options
	 * @param tx_rnbase_IParameters $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 */
	protected function initFilter(&$fields, &$options, &$parameters, &$configurations, $confId) {
		$options['distinct'] = 1;
		$scopeArr = tx_cfcleaguefe_util_ScopeController::handleCurrentScope($parameters,$configurations);
		// Spielplan für ein Team
		$teamId = $configurations->get($confId.'teamId');
		if($configurations->get($confId.'acceptTeamIdFromRequest')) {
			$teamId = $parameters->offsetGet('teamId');
		}

		$service = tx_cfcleaguefe_util_ServiceRegistry::getMatchService();
		$matchtable = $service->getMatchTable();
		$matchtable->setScope($scopeArr);
		$matchtable->setTeams($teamId);
		$clubId = $configurations->get($confId.'fixedOpponentClub');
		if($clubId) {
			// Show matches against a defined club
			$scopeClub = $matchtable->getClubs();
			$matchtable->setClubs('');
			if($scopeClub)
				$clubId .= ','.$scopeClub;
			$matchtable->setHomeClubs($clubId);
			$matchtable->setGuestClubs($clubId);
		}
		
		$matchtable->setTimeRange($configurations->get($confId.'timeRangePast'),$configurations->get($confId.'timeRangeFuture'));
		if($configurations->get($confId.'acceptRefereeIdFromRequest')) {
			$matchtable->setReferees($parameters->offsetGet('refereeId'));
		}

		tx_rnbase_util_Misc::callHook('cfc_league_fe','filterMatch_setfields', 
			array('matchtable' => &$matchtable, 'fields'=>&$fields, 'options'=>&$options, 'configurations'=>$configurations, 'confid'=>$confId), 
			$this);

		$matchtable->getFields($fields, $options);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/filter/class.tx_cfcleaguefe_filter_Match.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/filter/class.tx_cfcleaguefe_filter_Match.php']);
}

?>