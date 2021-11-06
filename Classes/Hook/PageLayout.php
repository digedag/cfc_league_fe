<?php

namespace System25\T3sports\Hook;

class PageLayout
{
    public const LLPATH = 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xml:';

    /**
     * Returns information about plugin.
     *
     * @param array $params Parameters to the hook
     *
     * @return string Information about plugin
     */
    public function getPluginSummary(array $params)
    {
        $lang = $this->getLanguageService();
        $row = $params['row'];
        if (!in_array($row['list_type'], ['tx_cfcleaguefe_competition', 'tx_cfcleaguefe_report'])) {
            return '';
        }
        $pluginType = end(\tx_rnbase_util_Strings::trimExplode('_', $row['list_type']));
        $flexformData = \tx_rnbase_util_Arrays::xml2array($params['row']['pi_flexform']);
        $actions = $this->getFieldFromFlexform($flexformData, 'action');
        if (!empty($actions)) {
            $labels = [];
            $actionList = \tx_rnbase_util_Strings::trimExplode(',', $actions);
            foreach ($actionList as $action) {
                $labels[] = $this->getLanguageService()->sL(self::LLPATH.$this->getActionLabel($action, $pluginType));
            }
        }

        $data = [
            ['Field', 'Value'],
            [$lang->sL(self::LLPATH.'plugin.competition.flexform.action'), implode(', ', $labels)],
        ];
        /* @var $tables \Tx_Rnbase_Backend_Utility_Tables */
        $tables = \tx_rnbase::makeInstance('Tx_Rnbase_Backend_Utility_Tables');

        return $tables->buildTable($data);
    }

    private function getActionLabel($action, $pluginType)
    {
        $map = ['LeagueTableShow' => 'LeagueTable'];
        $actionKey = end(\tx_rnbase_util_Strings::trimExplode('_', $action));
        if (array_key_exists($actionKey, $map)) {
            $actionKey = $map[$actionKey];
        }

        return sprintf('plugin.%s.flexform.action.%s', $pluginType, $actionKey);
    }

    /**
     * Get field value from flexform configuration,
     * including checks if flexform configuration is available.
     *
     * @param array $flexform
     * @param string $key name of the key
     * @param string $sheet name of the sheet
     *
     * @return string|null if nothing found, value if found
     */
    private function getFieldFromFlexform($flexform, $key, $sheet = 'sDEF')
    {
        if (isset($flexform['data'])) {
            $flexform = $flexform['data'];
            if (isset($flexform[$sheet]['lDEF'][$key]['vDEF'])
                ) {
                return $flexform[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return null;
    }

    /**
     * Return language service instance.
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    private function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
