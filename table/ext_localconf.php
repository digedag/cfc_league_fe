<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');



t3lib_extMgm::addService($_EXTKEY,  't3sports_leaguetable' /* sv type */,  'tx_cfcleaguefe_table_football_Table' /* sv key */,
  array(
    'title' => 'T3sports league table for football', 'description' => 'Compute league tables for football.', 'subtype' => 'football',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'table/football/class.tx_cfcleaguefe_table_football_Table.php',
    'className' => 'tx_cfcleaguefe_table_football_Table',
  )
);

t3lib_extMgm::addService($_EXTKEY,  't3sports_leaguetable' /* sv type */,  'tx_cfcleaguefe_table_icehockey_Table' /* sv key */,
  array(
    'title' => 'T3sports league table for IceHockey', 'description' => 'Compute league tables for IceHockey.', 'subtype' => 'icehockey',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'table/icehockey/class.tx_cfcleaguefe_table_icehockey_Table.php',
    'className' => 'tx_cfcleaguefe_table_icehockey_Table',
  )
);


?>