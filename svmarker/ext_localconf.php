<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


// Hook für historische Spiele
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = 'EXT:' . $_EXTKEY . '/svmarker/class.tx_cfcleaguefe_svmarker_MatchHistory.php:tx_cfcleaguefe_svmarker_MatchHistory->addMatches';
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_afterSubst'][] = 'EXT:' . $_EXTKEY . '/svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php:tx_cfcleaguefe_svmarker_ChartMatch->addChart';


//t3lib_extMgm::addService($_EXTKEY,  'markermodule' /* sv type */,  'tx_cfcleaguefe_svmarker_ChartMatch' /* sv key */,
//  array(
//    'title' => 'Chart for match', 'description' => 'Compares match opponents in league match', 'subtype' => 'CHARTMATCH',
//    'available' => TRUE, 'priority' => 50, 'quality' => 50,
//    'os' => '', 'exec' => '',
//    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php',
//    'className' => 'tx_cfcleaguefe_svmarker_ChartMatch',
//  )
//);
//
//t3lib_extMgm::addService($_EXTKEY,  'markermodule' /* sv type */,  'tx_cfcleaguefe_svmarker_MatchHistory' /* sv key */,
//  array(
//    'title' => 'Historic match list', 'description' => 'List of historic matches', 'subtype' => 'MATCHHISTORY',
//    'available' => TRUE, 'priority' => 50, 'quality' => 50,
//    'os' => '', 'exec' => '',
//    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'svmarker/class.tx_cfcleaguefe_svmarker_MatchHistory.php',
//    'className' => 'tx_cfcleaguefe_svmarker_MatchHistory',
//  )
//);

?>