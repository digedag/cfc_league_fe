<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;

/**
 * Configuration for table calculation modes.
 * This class encapsulates settings related to how the table is calculated (e.g., home/away, first/second half, point system).
 */
class TableModeConfig
{
    private ConfigurationInterface $configurations;
    private string $confId;
    private int $tableType = 0; // 0-normal, 1-home, 2-away
    private int $tableScope = 0; // 0-normal, 1-first, 2-second
    private int $pointSystem = 0; // 0-3-point, 1-2-point

    public function __construct(ConfigurationInterface $configurations, string $confId)
    {
        $this->configurations = $configurations;
        $this->confId = $confId;
        $this->init();
    }

    private function init(): void
    {
        // Table type: home/away matches only
        $this->tableType = (int) $this->getConfValue('tabletype');

        // Table scope: first/second half of season
        $this->tableScope = (int) $this->getConfValue('tablescope');

        // Point system: 3-point or 2-point
        $this->pointSystem = (int) $this->getConfValue('pointSystem');
        if (null !== $this->configurations->get($this->confId . 'forcePointSystem')) {
            $this->pointSystem = (int) $this->configurations->get($this->confId . 'forcePointSystem');
        }

        // Handle request parameters for dynamic selection
        $parameters = $this->configurations->getParameters();
        // Wir bleiben mit den alten falschen TS-Einstellungen kompatibel und fragen beide Einstellungen ab
        if ($this->configurations->get('tablescopeSelectionInput') || $this->getConfValue('tablescopeSelectionInput')) {
            $this->tableScope = $parameters->get('tablescope') ?? $this->tableScope;
        }
        if ($this->configurations->get('tabletypeSelectionInput') || $this->getConfValue('tabletypeSelectionInput')) {
            $this->tableType = $parameters->get('tabletype') ?? $this->tableType;
        }
        if ($this->configurations->get('pointSystemSelectionInput') || $this->getConfValue('pointSystemSelectionInput')) {
            $this->pointSystem = is_string($parameters->get('pointsystem')) ? intval($parameters->get('pointsystem')) : $this->pointSystem;
        }
    }

    private function getConfValue(string $key): ?string
    {
        return $this->configurations->get($this->confId . $key);
    }

    public function getTableType(): int
    {
        return $this->tableType;
    }

    public function getTableScope(): int
    {
        return $this->tableScope;
    }

    public function getPointSystem(): int
    {
        return $this->pointSystem;
    }

    public function setTableType(int $tableType): void
    {
        $this->tableType = $tableType;
    }

    public function setTableScope(int $tableScope): void
    {
        $this->tableScope = $tableScope;
    }

    public function setPointSystem(int $pointSystem): void
    {
        $this->pointSystem = $pointSystem;
    }

    public function getConfiguration(): ConfigurationInterface
    {
        return $this->configurations;
    }

    public function getConfId(): string
    {
        return $this->confId;
    }
}