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


/**
 * Marker class for player statistics
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv2_PlayerStatisticsMarker {
	/**
	 * Fills template of player statistics service. 
	 *
	 * @param string $srvTemplate
	 * @param array $stats
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $statsConfId
	 * @param string $statsMarker
	 * @return string
	 */
	function parseTemplate($srvTemplate, &$stats, &$formatter, $statsConfId, $statsMarker) {
		$configurations =& $formatter->configurations;
		// Das Template für einen Spieler holen
		$playerTemplate = $formatter->cObj->getSubpart($srvTemplate,'###'.$statsMarker.'_PROFILE###');

		// Es wird der ProfileMarker verwendet
		$profileMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_ProfileMarker');
		$profileMarkerObj = new $profileMarkerClass;
		$profileMarkerObj->initLabelMarkers($formatter, $statsConfId.'profile.', $statsMarker.'_PROFILE');
		$markerArray = $profileMarkerObj->initTSLabelMarkers($formatter, $statsConfId, $statsMarker);

		$rowRoll = intval($configurations->get($statsConfId.'profile.roll.value'));
		$rowRollCnt = 0;
		$parts = array();
		foreach ($stats as $playerStat) {
			$player = $playerStat['player'];
			if(!is_object($player))
				continue; // Ohne Spieler wird auch nix gezeigt
			unset($playerStat['player']); // PHP 5.2, sonst klappt der merge nicht
			$player->record = array_merge($playerStat, $player->record);
			// Jetzt für jedes Profil das Template parsen
			$parts[] = $profileMarkerObj->parseTemplate($playerTemplate, $player, $formatter, $statsConfId.'profile.', $statsMarker.'_PROFILE');
		}
		// Jetzt die einzelnen Teile zusammenfügen
		$subpartArray['###'.$statsMarker.'_PROFILE###'] = implode($parts, $configurations->get($statsMarker.'profile.implode'));

		$markerArray['###PLAYERCOUNT###'] = count($parts);
		return $formatter->cObj->substituteMarkerArrayCached($srvTemplate, $markerArray, $subpartArray);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_PlayerStatisticsMarker.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_PlayerStatisticsMarker.php']);
}

?>