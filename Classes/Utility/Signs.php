<?php

namespace System25\T3sports\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2010-2019 Rene Nitzsche (rene@system25.de)
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
 * Liefert Sternzeichen zu einem Datum
 */
class Signs
{
	private static $signs;

	public static function getInstance ()
	{
		static $instance;
		if (!isset($instance)) {
			$c = __CLASS__;
			$instance = new $c;
			$instance->createSigns();
		} // if
		return $instance;
	}


	public function getSign($date) {
		$days = date('z', $date);
		while(list($key,$value) = each($this->signs)) {
			if($days <= $key)
				return $value;
		}
		return 'unbekannt';
	}

	private function createSigns() {
		global $TSFE;
		$this->signs = [
			date('z',strtotime("2006-01-20")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.capricorn'),
			date('z',strtotime("2006-02-19")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.aquarius'),
			date('z',strtotime("2006-03-20")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.pisces'),
			date('z',strtotime("2006-04-20")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.aries'),
			date('z',strtotime("2006-05-20")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.taurus'),
			date('z',strtotime("2006-06-20")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.gemini'),
			date('z',strtotime("2006-07-22")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.cancer'),
			date('z',strtotime("2006-08-23")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.leo'),
			date('z',strtotime("2006-09-23")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.virgo'),
			date('z',strtotime("2006-10-23")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.libra'),
			date('z',strtotime("2006-11-22")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.scorpio'),
			date('z',strtotime("2006-12-21")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.sagittarius'),
			date('z',strtotime("2004-12-31")) => $TSFE->sL('LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.report.sign.capricorn')
		];
	}

}

