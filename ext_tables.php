<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// TCA für tt_content laden
t3lib_div::loadTCA('tt_content');

/**
 *  The plugins need to be included in ext_tables.php 
 */

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase_controller.php');

//t3lib_div::debug($_EXTKEY,'Test');

////////////////////////////////
// Plugin Competition anmelden
////////////////////////////////

// Einige Felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_cfcleaguefe_competition']='layout,select_key,pages';

// Das tt_content-Feld pi_flexform einblenden
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_competition']='pi_flexform';

t3lib_extMgm::addPiFlexFormValue('tx_cfcleaguefe_competition','FILE:EXT:'.$_EXTKEY.'/flexform_competition.xml');
t3lib_extMgm::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.php:plugin.competition.label','tx_cfcleaguefe_competition'));

////////////////////////////////
// Plugin Report anmelden
////////////////////////////////

// Unnötige Felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_cfcleaguefe_report']='layout,select_key,pages';
// Das tt_content-Feld pi_flexform einblenden
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_report']='pi_flexform,imagewidth;;13, --palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgLinks;7, --palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11,tx_hldamgallery_displaypage';

// Die Felder für die Bildbearbeitung einblenden
//$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_report']='imagewidth'
//$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_report']='imagewidth;;13'
//                        --palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11';

// Zeige Bilder auf Gallerieseite
//t3lib_extMgm::addToAllTCAtypes('tt_content','tx_hldamgallery_displaypage','tx_cfcleaguefe_report','');
//t3lib_extMgm::addToAllTCAtypes('tt_content','tx_hldamgallery_displaypage','image','after:tx_damttcontent_files');

// Galleriemodus
//t3lib_extMgm::addToAllTCAtypes('tt_content','tx_hldamgallery_displaymode','tx_cfcleaguefe_report','after:tx_hldamgallery_displaypage');
//t3lib_extMgm::addToAllTCAtypes('tt_content','tx_hldamgallery_displaymode','textpic','after:tx_hldamgallery_displaypage');


t3lib_extMgm::addPiFlexFormValue('tx_cfcleaguefe_report','FILE:EXT:'.$_EXTKEY.'/flexform_report.xml');
t3lib_extMgm::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.php:plugin.report.label','tx_cfcleaguefe_report'));

# Add plugin wizards
if (TYPO3_MODE=='BE')	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_cfcleaguefe_util_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'util/class.tx_cfcleaguefe_util_wizicon.php';

// list static templates in templates selection
t3lib_extMgm::addStaticFile($_EXTKEY,'static/', 'T3sports');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/cal/','T3sports cal-events');

?>
