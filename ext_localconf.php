<?php

use Sys25\RnBase\Backend\Utility\Icons;
use Sys25\RnBase\Utility\TYPO3;

if (!(defined('TYPO3') || defined('TYPO3_MODE'))) {
    exit('Access denied.');
}

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['tx_cfcleaguefe_competition']['plugin'] =
    \System25\T3sports\Hook\PageLayout::class.'->getPluginSummary';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['tx_cfcleaguefe_report']['plugin'] =
    \System25\T3sports\Hook\PageLayout::class.'->getPluginSummary';

if (Sys25\RnBase\Utility\Extensions::isLoaded('tt_news')) {
    // Hook for tt_news
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'][] = \System25\T3sports\Hook\TtNewsMarker::class;
}

// LeagueTable in Match
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = \System25\T3sports\Hook\TableMatchMarker::class.'->addLeagueTable';
// Matchtable current round in Match
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_initRecord'][] = \System25\T3sports\Hook\TableMatchMarker::class.'->addCurrentRound';
// Hook für historische Spiele
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = \System25\T3sports\Hook\MatchHistoryHook::class.'->addMatches';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = \System25\T3sports\Hook\MatchChartHook::class.'->addChart';

System25\T3sports\Utility\Misc::registerTableStrategy('default', 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:tablestrategy_default', System25\T3sports\Table\Football\Comparator::class);
System25\T3sports\Utility\Misc::registerTableStrategy('head2head', 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:tablestrategy_head2head', System25\T3sports\Table\Football\ComparatorH2H::class);
System25\T3sports\Utility\Misc::registerTableStrategy('pointpermatch', 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:tablestrategy_pointpermatch', System25\T3sports\Table\ComparatorPPM::class);
System25\T3sports\Utility\Misc::registerTableStrategy('volleyball3', 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:tablestrategy_volleyball_3point', System25\T3sports\Table\Volleyball\Comparator3Point::class);
System25\T3sports\Utility\Misc::registerTableStrategy('volleyball2', 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:tablestrategy_volleyball_2point', System25\T3sports\Table\Volleyball\Comparator::class);

// Sys25\RnBase\Utility\Extensions::addService(
//     $_EXTKEY,
//     'cfcleague_statistics' /* sv type */ ,
//     'tx_cfcleaguefe_sv2_PlayerStatistics' /* sv key */ ,
//     [
//     'title' => 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:plugin.competition.flexform.statistics.type.player', 'description' => 'Statistical data about players', 'subtype' => 'player',
//     'available' => true, 'priority' => 50, 'quality' => 50,
//     'os' => '', 'exec' => '',
//     'className' => 'System25\T3sports\Statistics\Service\PlayerStatistics',
//   ]
// );

// Sys25\RnBase\Utility\Extensions::addService(
//     $_EXTKEY,
//     'cfcleague_statistics' /* sv type */ ,
//     'tx_cfcleaguefe_sv2_ScorerStatistics' /* sv key */ ,
//     [
//     'title' => 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:plugin.competition.flexform.statistics.type.scorerlist', 'description' => 'A list of best scorer', 'subtype' => 'scorerlist',
//     'available' => true, 'priority' => 50, 'quality' => 50,
//     'os' => '', 'exec' => '',
//     'className' => 'System25\T3sports\Statistics\Service\ScorerStatistics',
//   ]
// );

// Sys25\RnBase\Utility\Extensions::addService(
//     $_EXTKEY,
//     'cfcleague_statistics' /* sv type */ ,
//     'tx_cfcleaguefe_sv2_AssistStatistics' /* sv key */ ,
//     [
//     'title' => 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:plugin.competition.flexform.statistics.type.assistlist', 'description' => 'A list of best assists', 'subtype' => 'assistlist',
//     'available' => true, 'priority' => 50, 'quality' => 50,
//     'os' => '', 'exec' => '',
//     'className' => 'System25\T3sports\Statistics\Service\AssistStatistics',
//   ]
// );

// Sys25\RnBase\Utility\Extensions::addService(
//     $_EXTKEY,
//     'cfcleague_statistics' /* sv type */ ,
//     'tx_cfcleaguefe_sv2_PlayerSummaryStatistics' /* sv key */ ,
//     [
//     'title' => 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:plugin.competition.flexform.statistics.type.playersummary', 'description' => 'Some additional data of player statistics', 'subtype' => 'playersummary',
//     'available' => true, 'priority' => 50, 'quality' => 50,
//     'os' => '', 'exec' => '',
//     'className' => 'System25\T3sports\Statistics\Service\PlayerSummaryStatistics',
//   ]
// );

// Sys25\RnBase\Utility\Extensions::addService(
//     $_EXTKEY,
//     'cfcleague_statistics' /* sv type */ ,
//     'tx_cfcleaguefe_sv2_VisitorStatistics' /* sv key */ ,
//     [
//     'title' => 'LLL:EXT:cfc_league_fe/Resources/Private/Language/locallang_db.xlf:plugin.competition.flexform.statistics.type.visitors', 'description' => 'Count visitors of all teams', 'subtype' => 'visitors',
//     'available' => true, 'priority' => 50, 'quality' => 50,
//     'os' => '', 'exec' => '',
//     'className' => 'System25\T3sports\Statistics\Service\VisitorStatistics',
//   ]
// );

if (\Sys25\RnBase\Utility\Environment::isBackend()) {
    Icons::getIconRegistry()->registerIcon(
        't3sports_plugin',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:cfc_league_fe/Resources/Public/Icons/ext_icon.svg']
    );

    // Apply PageTSconfig
    if (!TYPO3::isTYPO121OrHigher()) {
        // since T3 12 pagets is loaded by convention
        \Sys25\RnBase\Utility\Extensions::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:cfc_league_fe/Configuration/PageTS/modWizards.tsconfig">'
        );
    }
}
