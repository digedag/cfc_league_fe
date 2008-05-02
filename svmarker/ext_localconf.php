<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


t3lib_extMgm::addService($_EXTKEY,  'markermodule' /* sv type */,  'tx_cfcleaguefe_svmarker_ChartMatch' /* sv key */,
  array(
    'title' => 'Chart for match', 'description' => 'Compares match opponents in league match', 'subtype' => 'CHARTMATCH',
    'available' => TRUE, 'priority' => 50, 'quality' => 50,
    'os' => '', 'exec' => '',
    'classFile' => t3lib_extMgm::extPath($_EXTKEY).'svmarker/class.tx_cfcleaguefe_svmarker_ChartMatch.php',
    'className' => 'tx_cfcleaguefe_svmarker_ChartMatch',
  )
);


?>