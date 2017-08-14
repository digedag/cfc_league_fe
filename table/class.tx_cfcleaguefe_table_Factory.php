<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2017 Rene Nitzsche (rene@system25.de)
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
 * Factory for table classes
 */
class tx_cfcleaguefe_table_Factory
{

    /**
     *
     * @param tx_cfcleague_util_MatchTable $matchTable
     * @param Tx_Rnbase_Configuration_ProcessorInterface $configurations
     * @param string $confId
     * @return tx_cfcleaguefe_table_DefaultMatchProvider
     */
    public static function createMatchProvider($tableType, $configurations, $confId)
    {
        $clazz = $configurations->get($confId . $tableType . '.matchProviderClass');
        $clazz = $clazz ? $clazz : 'tx_cfcleaguefe_table_DefaultMatchProvider';
        $prov = tx_rnbase::makeInstance($clazz, $configurations, $confId);
        return $prov;
    }

    /**
     *
     * @param string $type
     * @return tx_cfcleaguefe_table_ITableType
     */
    public static function createTableType($type)
    {
        tx_rnbase::load('tx_rnbase_util_Misc');
        $srv = tx_rnbase_util_Misc::getService('t3sports_sports', $type);
        return $srv->getLeagueTable();
    }
}
