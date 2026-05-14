<?php

namespace System25\T3sports\Table;

use Sys25\RnBase\Configuration\ConfigurationInterface;

/**
 * Configuration for match selection.
 * This class encapsulates all settings related to which matches are included in the table.
 */
class MatchSelectionConfig
{
    private ConfigurationInterface $configurations;
    private string $confId;
    private array $scope = [];
    private ?int $currentRound = null;
    private bool $liveTable = false;
    private string $matchStatus = '2'; // Default to finished matches

    public function __construct(ConfigurationInterface $configurations, string $confId)
    {
        $this->configurations = $configurations;
        $this->confId = $confId;
        $this->init();
    }

    private function init(): void
    {
        // Match status (live table or finished)
        $this->matchStatus = $this->configurations->get($this->confId . 'filter.matchstatus') ?: '2';
        $this->liveTable = $this->configurations->getBool($this->confId . 'showLiveTable');
        if ($this->liveTable) {
            $this->matchStatus = '1,2'; // Include running and finished matches
        }
    }

    public function setScope(array $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): array
    {
        return $this->scope;
    }

    public function setCurrentRound(?int $round): void
    {
        $this->currentRound = $round;
    }

    public function getCurrentRound(): ?int
    {
        return $this->currentRound;
    }

    public function isLiveTable(): bool
    {
        return $this->liveTable;
    }

    public function getMatchStatus(): string
    {
        return $this->matchStatus;
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