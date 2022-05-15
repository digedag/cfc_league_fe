<?php

namespace System25\T3sports\Table\Volleyball;

use Exception;
use System25\T3sports\Table\Football\Configurator as FootballConfigurator;
use System25\T3sports\Table\IComparator;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2022 Rene Nitzsche (rene@system25.de)
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
 * Configurator for volleyball league tables.
 */
class Configurator extends FootballConfigurator
{
    public const POINT_SYSTEM_2POINT = 0;

    public const POINT_SYSTEM_3POINT = 1;

    /**
     * Whether or not loose points are count.
     *
     * @return bool
     */
    public function isCountLoosePoints()
    {
        // Im Volleyball werden zukünftig auch Minuspunkte gezählt.
        return self::POINT_SYSTEM_2POINT == $this->getPointSystem();
    }

    /**
     * Für die Punktberechnung ist im Volleyball die Satzverteilung relevant.
     */
    public function getPointsWinVolley($winSetsHome, $winSetsGuest)
    {
        //	tx_rnbase_util_Debug::debug($this->getPointSystem(), 'volley_Conf'.__LINE__);
        $points = 2;
        if (self::POINT_SYSTEM_3POINT == $this->getPointSystem()) {
            $points = $this->isSplitResult($winSetsHome, $winSetsGuest) ? 2 : 3;
        }

        return $points;
    }

    protected function isSplitResult($winSetsHome, $winSetsGuest)
    {
        // Wenn die Satzdifferenz 1 ist, werden die Punkte geteilt
        return 1 == abs($winSetsHome - $winSetsGuest);
    }

    public function getPointsDrawVolley($afterExtraTime, $afterPenalty)
    {
        return 0; // Unentschieden gibt es eigentlich nicht...
    }

    public function getPointsLooseVolley($winSetsHome, $winSetsGuest)
    {
        $points = 0;
        if (self::POINT_SYSTEM_3POINT == $this->getPointSystem()) {
            // Wenn die Satzdifferenz 1 ist, werden die Punkte geteilt
            $points = $this->isSplitResult($winSetsHome, $winSetsGuest) ? 1 : 0;
        }

        return $points;
    }

    /**
     * Quelle: https://sourceforge.net/apps/trac/cfcleague/ticket/74
     * 0- 2-Punktsystem
     * 1- 3-Punktsystem.
     */
    public function getPointSystem()
    {
        return $this->cfgPointSystem;
    }

    /**
     * @return IComparator
     */
    public function getComparator()
    {
        $comparatorClass = $this->cfgComparatorClass;
        if (!$comparatorClass) {
            $compareClass = self::POINT_SYSTEM_2POINT == $this->getPointSystem() ?
                    Comparator::class :
                    Comparator3Point::class;
        }
        $comparator = tx_rnbase::makeInstance($compareClass);
        if (!is_object($comparator)) {
            throw new Exception('Could not instanciate comparator: '.$compareClass);
        }
        if (!($comparator instanceof IComparator)) {
            throw new Exception('Comparator is no instance of tx_cfcleaguefe_table_volleyball_IComparator: '.get_class($comparator));
        }

        return $comparator;
    }

    protected function init()
    {
        // Der TableScope wirkt sich auf die betrachteten Spiele (Hin-Rückrunde) aus
        $parameters = $this->configurations->getParameters();
        $this->cfgTableScope = $this->getConfValue('tablescope');
        // Wir bleiben mit den alten falschen TS-Einstellungen kompatibel und fragen
        // beide Einstellungen ab
        if ($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tablescopeSelectionInput')) {
            $this->cfgTableScope = $parameters->offsetGet('tablescope') ? $parameters->offsetGet('tablescope') : $this->cfgTableScope;
        }

        // tabletype means home or away matches only
        $this->cfgTableType = $this->getConfValue('tabletype');
        if ($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tabletypeSelectionInput')) {
            $this->cfgTableType = $parameters->offsetGet('tabletype') ? $parameters->offsetGet('tabletype') : $this->cfgTableType;
        }

        $this->cfgPointSystem = $this->getCompetition()->getProperty('point_system');
        if ($this->configurations->get('pointSystemSelectionInput') || $this->getConfValue('pointSystemSelectionInput')) {
            $this->cfgPointSystem = is_string($parameters->offsetGet('pointsystem')) ? intval($parameters->offsetGet('pointsystem')) : $this->cfgPointSystem;
        }
        $this->cfgLiveTable = intval($this->getConfValue('showLiveTable'));
        $this->cfgComparatorClass = $this->getStrategyValue('comparator');
    }
}
