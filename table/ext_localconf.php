<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');



t3lib_extMgm::addService($_EXTKEY,  't3sports_leaguetable' /* sv type */,  'tx_cfcleaguefe_table_TableFootball' /* sv key */,
  array(
    'title' => 'T3sports league table for football', 'description' => 'Compute league tables for football.', 'subtype' => 'football',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'table/class.tx_cfcleaguefe_table_TableFootball.php',
    'className' => 'tx_cfcleaguefe_table_TableFootball',
  )
);

t3lib_extMgm::addService($_EXTKEY,  't3sports_leaguetable' /* sv type */,  'tx_cfcleaguefe_table_TableIceHockey' /* sv key */,
  array(
    'title' => 'T3sports league table for IceHockey', 'description' => 'Compute league tables for IceHockey.', 'subtype' => 'icehockey',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'table/class.tx_cfcleaguefe_table_TableIceHockey.php',
    'className' => 'tx_cfcleaguefe_table_TableIceHockey',
  )
);


?>