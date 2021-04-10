<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use System25\T3sports\Table\Football\Table as FootballTable;
use System25\T3sports\Table\Handball\Table as HandballTable;
use System25\T3sports\Table\Icehockey\Table as IcehockeyTable;
use System25\T3sports\Table\Volleyball\Table as VolleyballTable;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2020 Rene Nitzsche (rene@system25.de)
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
 * Factory for table classes.
 */
class Factory
{
    /**
     * @param string $tableType
     * @param ConfigurationInterface $configurations
     * @param string $confId
     *
     * @return DefaultMatchProvider
     */
    public static function createMatchProvider($tableType, $configurations, $confId)
    {
        $clazz = $configurations->get($confId.$tableType.'.matchProviderClass');
        $clazz = $clazz ? $clazz : DefaultMatchProvider::class;
        $prov = tx_rnbase::makeInstance($clazz, $configurations, $confId);

        return $prov;
    }

    /**
     * @param string $type
     *
     * @return ITableType
     */
    public static function createTableType($type)
    {
        $map = [
            FootballTable::TABLE_TYPE => FootballTable::class,
            HandballTable::TABLE_TYPE => HandballTable::class,
            IcehockeyTable::TABLE_TYPE => IcehockeyTable::class,
            VolleyballTable::TABLE_TYPE => VolleyballTable::class,
        ];

        $table = null;
        if (array_key_exists($type, $map)) {
            $table = tx_rnbase::makeInstance($map[$type]);
        }

        return $table;
    }
}
