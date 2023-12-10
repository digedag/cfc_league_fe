<?php

namespace System25\T3sports\Statistics\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2010-2023 Rene Nitzsche (rene@system25.de)
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
 * Zentrale Klasse für den Zugriff auf verschiedene Services.
 */
class StatsServiceProvider implements \TYPO3\CMS\Core\SingletonInterface
{
    private $statsIndexer = [];

    /**
     * Used by T3 versions without DI.
     *
     * @var StatsServiceProvider
     */
    private static $instance;

    public function addStatsService(StatsServiceInterface $indexer)
    {
        $type = $indexer->getStatsType();
        $this->statsIndexer[$type] = $indexer;
    }

    /**
     * @return StatsServiceInterface
     */
    public function getStatsServiceByType(string $type)
    {
        return $this->statsIndexer[$type] ?? [];
    }

    /**
     * @return string[]
     */
    public function getStatsTypes()
    {
        return array_keys($this->statsIndexer);
    }

    /**
     * Only used by T3 versions prior to 10.x.
     *
     * @return StatsServiceProvider
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
