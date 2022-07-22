<?php

defined('TYPO3_MODE') or exit;

call_user_func(function () {
    $extKey = 'cfc_league_fe';

    ////////////////////////////////
    // Plugin Competition anmelden
    ////////////////////////////////

    // Einige Felder ausblenden
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_cfcleaguefe_competition'] = 'layout,select_key,pages,recursive';

    // Das tt_content-Feld pi_flexform einblenden
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_competition'] = 'pi_flexform';

    tx_rnbase_util_Extensions::addPiFlexFormValue(
        'tx_cfcleaguefe_competition',
        'FILE:EXT:cfc_league_fe/Configuration/Flexform/plugin_competition.xml'
    );

    tx_rnbase_util_Extensions::addPlugin(
        [
            'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_db.xml:plugin.competition.label',
            'tx_cfcleaguefe_competition',
        ],
        'list_type',
        $extKey
    );

    ////////////////////////////////
    // Plugin Report anmelden
    ////////////////////////////////

    // Einige Felder ausblenden
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_cfcleaguefe_report'] = 'layout,select_key,pages,recursive';

    // Das tt_content-Feld pi_flexform einblenden
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_report'] = 'pi_flexform';

    tx_rnbase_util_Extensions::addPiFlexFormValue(
        'tx_cfcleaguefe_report',
        'FILE:EXT:cfc_league_fe/Configuration/Flexform/plugin_report.xml'
    );

    /*
     * Adds an entry to the list of plugins in content elements of type "Insert plugin"
     * Takes the $itemArray (label, value[,icon]) and adds to the items-array of
     * $GLOBALS['TCA'][tt_content] elements with CType "listtype" (or another field if
     * $type points to another fieldname)
     * If the value (array pos. 1) is already found in that items-array, the entry is substituted,
     * otherwise the input array is added to the bottom.
     * Use this function to add a frontend plugin to this list of plugin-types - or more generally
     * use this function to add an entry to any selectorbox/radio-button set in the TCEFORMS
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param array $itemArray Numerical array: [0] => Plugin label, [1] => Underscored extension key, [2] => Path to plugin icon relative to TYPO3_mainDir
     * @param string $type Type (eg. "list_type") - basically a field from "tt_content" table
     * @param string $extensionKey The extension key
     * @throws \RuntimeException
     */
    tx_rnbase_util_Extensions::addPlugin(
        [
            'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xml:plugin.report.label',
            'tx_cfcleaguefe_report',
        ],
        'list_type',
        $extKey
    );
});
