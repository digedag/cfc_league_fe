<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

//$tempPath = t3lib_extMgm::extPath('cfc_league_fe');

// Spiele als Ereignisse für die Extension cal bereitstellen

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');
tx_div::load('tx_cfcleaguefe_util_ServiceRegistry');
tx_div::load('tx_rnbase_util_SearchBase');


$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cfc_league_fe']);

// Cal-Service nur bei Bedarf einbinden
if(intval($confArr['enableCalService'])) {
	t3lib_extMgm::addService($_EXTKEY,  'cal_event_model' /* sv type */,  'tx_cfcleaguefe_sv1_MatchEvent' /* sv key */,
	  array(
	    'title' => 'Cal Match Model', 'description' => '', 'subtype' => 'event',
	    'available' => TRUE, 'priority' => 50, 'quality' => 50,
	    'os' => '', 'exec' => '',
	    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_MatchEvent.php',
	    'className' => 'tx_cfcleaguefe_sv1_MatchEvent',
	  )
	);
}

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_PlayerStatistics' /* sv key */,
  array(
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.player', 'description' => 'Statistical data about players', 'subtype' => 'player',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_PlayerStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_PlayerStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_ScorerStatistics' /* sv key */,
  array(
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.scorerlist', 'description' => 'A list of best scorer', 'subtype' => 'scorerlist',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_ScorerStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_ScorerStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_AssistStatistics' /* sv key */,
  array(
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.assistlist', 'description' => 'A list of best assists', 'subtype' => 'assistlist',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_AssistStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_AssistStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_PlayerSummaryStatistics' /* sv key */,
  array(
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.playersummary', 'description' => 'Some additional data of player statistics', 'subtype' => 'playersummary',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_PlayerSummaryStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_VisitorStatistics' /* sv key */,
  array(
    'title' => 'LLL:EXT:cfc_league_fe/locallang_db.xml:plugin.competition.flexform.statistics.type.visitors', 'description' => 'Count visitors of all teams', 'subtype' => 'visitors',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_VisitorStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_VisitorStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Matches' /* sv key */,
  array(
    'title' => 'Team services', 'description' => 'Service functions for match access', 'subtype' => 'match',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_Matches.php',
    'className' => 'tx_cfcleaguefe_sv1_Matches',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Teams' /* sv key */,
  array(
    'title' => 'Team services', 'description' => 'Service functions for team access', 'subtype' => 'team',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_Teams.php',
    'className' => 'tx_cfcleaguefe_sv1_Teams',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Competitions' /* sv key */,
  array(
    'title' => 'Team services', 'description' => 'Service functions for competition access', 'subtype' => 'competition',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_Competitions.php',
    'className' => 'tx_cfcleaguefe_sv1_Competitions',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_data' /* sv type */,  'tx_cfcleaguefe_sv1_Profiles' /* sv key */,
  array(
    'title' => 'Profile services', 'description' => 'Service functions for profile access', 'subtype' => 'profile',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_Profiles.php',
    'className' => 'tx_cfcleaguefe_sv1_Profiles',
  )
);

$tempPath = t3lib_extMgm::extPath('cfc_league_fe');
require_once($tempPath.'svmarker/ext_localconf.php');

?>