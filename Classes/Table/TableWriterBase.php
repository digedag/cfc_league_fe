<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\Templates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2022 Rene Nitzsche (rene@system25.de)
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
 * Implementors generate an output string for table.
 */
abstract class TableWriterBase implements ITableWriter
{
    /**
     * @param ITableType $table
     * @param string $template
     * @param ConfigurationInterface $configurations
     * @param string $confId
     *
     * @return string
     */
    public function writeTable($table, $template, $configurations, $confId)
    {
        $mainSubpart = 'SPORTS_'.strtoupper($table->getTypeID());
        if (BaseMarker::containsMarker($template, $mainSubpart)) {
            $template = Templates::getSubpart($template, '###'.$mainSubpart.'###');
        }

        return $this->renderTable($table, $template, $configurations, $confId);
    }

    abstract protected function renderTable($table, $template, $configurations, $confId);
}
