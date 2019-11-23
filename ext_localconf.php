<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


// Spiele als Ereignisse für die Extension cal bereitstellen

tx_rnbase::load('tx_cfcleaguefe_util_ServiceRegistry');
tx_rnbase::load('tx_rnbase_util_SearchBase');

// Hook for tt_news
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_cfcleaguefe_hooks_ttnewsMarkers.php:tx_cfcleaguefe_hooks_ttnewsMarkers';
// LeagueTable in Match
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_cfcleaguefe_hooks_TableMatchMarker.php:tx_cfcleaguefe_hooks_TableMatchMarker->addLeagueTable';
// Matchtable current round in Match
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_initRecord'][] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_cfcleaguefe_hooks_TableMatchMarker.php:tx_cfcleaguefe_hooks_TableMatchMarker->addCurrentRound';

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cfc_league_fe']);

// Cal-Service nur bei Bedarf einbinden
if(intval($confArr['enableCalService'])) {
	tx_rnbase_util_Extensions::addService($_EXTKEY,  'cal_event_model' /* sv type */,  'tx_cfcleaguefe_sv1_MatchEvent' /* sv key */,
	  [
	    'title' => 'Cal Match Model', 'description' => '', 'subtype' => 'event',
	    'available' => TRUE, 'priority' => 50, 'quality' => 50,
	    'os' => '', 'exec' => '',
	    'className' => 'System25\T3sports\Service\MatchEventService',
	  ]
	);
}

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_PlayerStatistics' /* sv key */,
  [
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.player', 'description' => 'Statistical data about players', 'subtype' => 'player',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Statistics\Service\PlayerStatistics',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_ScorerStatistics' /* sv key */,
  [
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.scorerlist', 'description' => 'A list of best scorer', 'subtype' => 'scorerlist',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Statistics\Service\ScorerStatistics',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_AssistStatistics' /* sv key */,
  [
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.assistlist', 'description' => 'A list of best assists', 'subtype' => 'assistlist',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Statistics\Service\AssistStatistics',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_PlayerSummaryStatistics' /* sv key */,
  [
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.playersummary', 'description' => 'Some additional data of player statistics', 'subtype' => 'playersummary',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Statistics\Service\PlayerSummaryStatistics',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_VisitorStatistics' /* sv key */,
  [
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.visitors', 'description' => 'Count visitors of all teams', 'subtype' => 'visitors',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Statistics\Service\VisitorStatistics',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Matches' /* sv key */,
  [
    'title' => 'Team services', 'description' => 'Service functions for match access', 'subtype' => 'match',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Service\MatchService',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Teams' /* sv key */,
  [
    'title' => 'Team services', 'description' => 'Service functions for team access', 'subtype' => 'team',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Service\TeamService',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Competitions' /* sv key */,
  [
    'title' => 'Team services', 'description' => 'Service functions for competition access', 'subtype' => 'competition',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Service\CompetitionService',
  ]
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Profiles' /* sv key */,
  [
    'title' => 'Profile services', 'description' => 'Service functions for profile access', 'subtype' => 'profile',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'className' => 'System25\T3sports\Service\ProfileService',
  ]
);

// Hook für historische Spiele
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = 'EXT:' . $_EXTKEY . '/svmarker/class.tx_cfcleaguefe_svmarker_MatchHistory.php:tx_cfcleaguefe_svmarker_MatchHistory->addMatches';
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = 'EXT:' . $_EXTKEY . '/svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php:tx_cfcleaguefe_svmarker_ChartMatch->addChart';

