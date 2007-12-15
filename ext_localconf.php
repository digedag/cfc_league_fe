<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

//$tempPath = t3lib_extMgm::extPath('cfc_league_fe');

// Spiele als Ereignisse für die Extension cal bereitstellen

t3lib_extMgm::addService($_EXTKEY,  'cal_event_model' /* sv type */,  'tx_cfcleaguefe_sv1_MatchEvent' /* sv key */,
  array(
    'title' => 'Cal Match Model', 'description' => '', 'subtype' => 'event',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_MatchEvent.php',
    'className' => 'tx_cfcleaguefe_sv1_MatchEvent',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_PlayerStatistics' /* sv key */,
  array(
    'title' => 'Player Statistics', 'description' => 'Statistical data about players', 'subtype' => 'player',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_PlayerStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_PlayerStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_ScorerStatistics' /* sv key */,
  array(
    'title' => 'Scorer Statistics', 'description' => 'A list of best scorer', 'subtype' => 'scorerlist',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_ScorerStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_ScorerStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_AssistStatistics' /* sv key */,
  array(
    'title' => 'Assist Statistics', 'description' => 'A list of best assists', 'subtype' => 'assistlist',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_AssistStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_AssistStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_PlayerSummaryStatistics' /* sv key */,
  array(
    'title' => 'Summery of player statistics', 'description' => 'Some additional data of player statistics', 'subtype' => 'playersummary',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_PlayerSummaryStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_PlayerSummaryStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_statistics' /* sv type */,  'tx_cfcleaguefe_sv2_VisitorStatistics' /* sv key */,
  array(
    'title' => 'Visitor statistics', 'description' => 'Count visitors of all teams', 'subtype' => 'visitors',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv2/class.tx_cfcleaguefe_sv2_VisitorStatistics.php',
    'className' => 'tx_cfcleaguefe_sv2_VisitorStatistics',
  )
);

t3lib_extMgm::addService($_EXTKEY,  'cfcleague_teams' /* sv type */,  'tx_cfcleaguefe_sv1_Teams' /* sv key */,
  array(
    'title' => 'Team services', 'description' => 'Service functions for team access', 'subtype' => '',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_cfcleaguefe_sv1_Teams.php',
    'className' => 'tx_cfcleaguefe_sv1_Teams',
  )
);

?>