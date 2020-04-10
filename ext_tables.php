<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


/**
 *  The plugins need to be included in ext_tables.php
 */

// TCA für tt_content laden
tx_rnbase_util_TCA::loadTCA('tt_content');

////////////////////////////////
// Plugin Competition anmelden
////////////////////////////////

// tx_rnbase_util_Extensions::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.php:plugin.competition.label','tx_cfcleaguefe_competition'));

////////////////////////////////
// Plugin Report anmelden
////////////////////////////////

// // Unnötige Felder ausblenden
// $TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_cfcleaguefe_report']='layout,select_key,pages';
// // Das tt_content-Feld pi_flexform einblenden
// $TCA['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_report']='pi_flexform';
// //$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_cfcleaguefe_report']='pi_flexform,imagewidth;;13, --palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgLinks;7, --palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11,tx_hldamgallery_displaypage';


// tx_rnbase_util_Extensions::addPiFlexFormValue('tx_cfcleaguefe_report','FILE:EXT:'.$_EXTKEY.'/Configuration/Flexform/'.(tx_rnbase_util_TYPO3::isTYPO70OrHigher() ? 'plugin' : 'flexform' ).'_report.xml');
// tx_rnbase_util_Extensions::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.php:plugin.report.label','tx_cfcleaguefe_report'));


# Add plugin wizards
if (TYPO3_MODE=='BE')	{
	tx_rnbase::load('tx_rnbase_util_Wizicon');
	tx_rnbase_util_Wizicon::addWizicon('tx_cfcleaguefe_util_wizicon', tx_rnbase_util_Extensions::extPath($_EXTKEY).'util/class.tx_cfcleaguefe_util_wizicon.php');
}

// list static templates in templates selection
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'static/', 'T3sports');
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'static/cal/','T3sports cal-events');
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'static/volleyball/','T3sports for Volleyball');

