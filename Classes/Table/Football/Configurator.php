<?php

namespace System25\T3sports\Table\Football;

use Exception;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Club;
use System25\T3sports\Model\Competition;
use System25\T3sports\Model\Team;
use System25\T3sports\Table\IComparator;
use System25\T3sports\Table\IConfigurator;
use System25\T3sports\Table\PointOptions;
use System25\T3sports\Utility\Misc;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/***************************************************************
*  Copyright notice
*
*  (c) 2008-2023 Rene Nitzsche (rene@system25.de)
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
 * Configurator for football league tables.
 * Diese Klasse liefert Daten zur Steuerung der Tabellenberechnung.
 * Alle Einstellungen werden im TS unterhalb von tablecfg konfiguriert.
 */
class Configurator implements IConfigurator
{
    /**
     * @var Competition
     */
    protected $baseCompetition;

    /**
     * @var ConfigurationInterface
     */
    protected $configurations;

    /**
     * Array mit Angaben zur Berechnung der Tabelle. Einziger Key derzeit "comparator".
     *
     * @var array
     */
    protected $cfgTableStrategy;

    /**
     * 0-normal, 1-first, 2-second.
     *
     * @var int
     */
    protected $cfgTableScope;
    /**
     * 0-normal, 1-home, 2-away.
     *
     * @var int
     */
    protected $cfgTableType;
    /**
     * 0 - 3-Punktsystem, 1 - 2-Punktsystem.
     *
     * @var int
     */
    protected $cfgPointSystem;

    /**
     * Anzeige einer Livetabelle mit Einbeziehung von laufenden Spielen.
     *
     * @var bool
     */
    protected $cfgLiveTable;
    protected $cfgComparatorClass;

    protected $confId;
    protected $markClubs;

    public function __construct(Competition $baseCompetition, $configurations, $confId)
    {
        $this->baseCompetition = $baseCompetition;
        $this->configurations = $configurations;
        $this->confId = $confId;
        $this->init();
    }

    public function isLiveTable(): bool
    {
        return $this->cfgLiveTable;
    }

    /**
     * 2-point-system.
     *
     * @return bool
     */
    public function isCountLoosePoints()
    {
        return '1' == $this->cfgPointSystem; // im 2-Punktesystem die Minuspunkte sammeln
    }

    /**
     * Returns the unique key for a team. For alltime table this can be club uid.
     * TODO: die Methode sollte entfallen. Die ID liefert immer der TeamAdapter.
     *
     * @param Team|Club $teamOrClub
     *
     * @deprecated
     */
    public function getTeamId($teamOrClub)
    {
        if ('club' == $this->getConfValue('teamMode') && $teamOrClub instanceof Club) {
            return $teamOrClub->getProperty('club');
        }

        return $teamOrClub->getUid();
    }

    protected function getConfValue($key)
    {
        if (!is_object($this->configurations)) {
            return false;
        }

        return $this->configurations->get($this->confId.$key);
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->baseCompetition;
    }

    public function getMarkClubs()
    {
        if (!$this->markClubs) {
            $values = $this->getConfValue('markClubs');
            if (!$values) {
                $values = $this->configurations->get('markClubs');
            } // used from flexform
            $this->markClubs = Strings::intExplode(',', $values);
        }

        return $this->markClubs;
    }

    public function setMarkClubs(array $clubIds)
    {
        $this->markClubs = $clubIds;
    }

    /**
     * Returns the table type. This means which matches to use: all, home or away matches only.
     *
     * @return int 0-normal, 1-home, 2-away
     */
    public function getTableType()
    {
        return $this->cfgTableType;
    }

    /**
     * Returns the table scope. This means which matches to use: all, first saison part or second saison part only.
     *
     * @return int 0-normal, 1-first, 2-second
     */
    public function getTableScope()
    {
        return $this->cfgTableScope;
    }

    /**
     * @param PointOptions $options
     *
     * @return number
     */
    public function getPointsWin($options = null)
    {
        return '1' == $this->cfgPointSystem ? 2 : 3;
    }

    /**
     * @param PointOptions $options
     *
     * @return number
     */
    public function getPointsDraw($options = null)
    {
        return 1;
    }

    /**
     * @param PointOptions $options
     *
     * @return number
     */
    public function getPointsLoose($options = null)
    {
        return 0;
    }

    public function getPointSystem()
    {
        return $this->cfgPointSystem;
    }

    /**
     * @return IComparator
     */
    public function getComparator()
    {
        $compareClass = $this->cfgComparatorClass ? $this->cfgComparatorClass : Comparator::class;
        $comparator = tx_rnbase::makeInstance($compareClass);
        if (!is_object($comparator)) {
            throw new Exception('Could not instanciate comparator: '.$compareClass);
        }
        if (!($comparator instanceof IComparator)) {
            throw new Exception('Comparator is no instance of System25\T3sports\Table\IComparator: '.get_class($comparator));
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
            $this->cfgTableScope = $parameters->get('tablescope') ?? $this->cfgTableScope;
        }

        // tabletype means home or away matches only
        $this->cfgTableType = $this->getConfValue('tabletype');
        if ($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tabletypeSelectionInput')) {
            $this->cfgTableType = $parameters->get('tabletype') ?? $this->cfgTableType;
        }

        $this->cfgPointSystem = $this->getCompetition()->getProperty('point_system');
        if (null !== $this->configurations->get($this->confId.'forcePointSystem')) {
            $this->cfgPointSystem = (int) $this->configurations->get($this->confId.'forcePointSystem');
        }
        if ($this->configurations->get('pointSystemSelectionInput') || $this->getConfValue('pointSystemSelectionInput')) {
            $this->cfgPointSystem = is_string($parameters->get('pointsystem')) ? intval($parameters->get('pointsystem')) : $this->cfgPointSystem;
        }

        $this->cfgLiveTable = $this->configurations->getBool($this->confId.'showLiveTable');

        $this->cfgComparatorClass = $this->getStrategyValue('comparator');
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    protected function getStrategyValue(string $key)
    {
        if (null === $this->cfgTableStrategy) {
            $strategy = $this->getCompetition()->getProperty('tablestrategy');
            if (null === $strategy) {
                $srv = ServiceRegistry::getCompetitionService();
                $strategies = reset($srv->getTableStrategies4TCA());
                $strategy = $strategies[1];
            }
            $this->cfgTableStrategy = Misc::lookupTableStrategy($strategy);
        }

        return array_key_exists($key, $this->cfgTableStrategy) ? $this->cfgTableStrategy[$key] : null;
    }
}
