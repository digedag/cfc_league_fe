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

// FIXME: use DI for T3 10.x and higher
// if (!\Sys25\RnBase\Utility\TYPO3::isTYPO104OrHigher()) {
// }
$provider = \System25\T3sports\Statistics\Service\StatsServiceProvider::getInstance();
$provider->addStatsService(new \System25\T3sports\Statistics\Service\PlayerStatistics());
$provider->addStatsService(new \System25\T3sports\Statistics\Service\ScorerStatistics());
$provider->addStatsService(new \System25\T3sports\Statistics\Service\AssistStatistics());
$provider->addStatsService(new \System25\T3sports\Statistics\Service\PlayerSummaryStatistics());
$provider->addStatsService(new \System25\T3sports\Statistics\Service\VisitorStatistics());

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
