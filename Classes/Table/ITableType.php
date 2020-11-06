<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2020 Rene Nitzsche (rene@system25.de)
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
 * Implementors provide strategies to compute a league table.
 */
interface ITableType
{
    /**
     * Set match provider.
     *
     * @param IMatchProvider $matchProvider
     */
    public function setMatchProvider(IMatchProvider $matchProvider);

    /**
     * Get match provider.
     *
     * @return IMatchProvider
     */
    public function getMatchProvider() : IMatchProvider;

    /**
     * Set configuration.
     *
     * @param ConfigurationInterface $configuration
     * @param string confId
     */
    public function setConfigurations($configuration, $confId);

    /**
     * Returns the final table data.
     *
     * @return ITableResult
     */
    public function getTableData() : ITableResult;

    /**
     * @return ITableWriter
     */
    public function getTableWriter() : ITableWriter;

    /**
     * @return IConfigurator
     */
    public function getConfigurator() : IConfigurator;

    /**
     * Unique id string for this type of table.
     *
     * @return string
     */
    public function getTypeID() : string;
}
