<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');


/**
 * Controller für die Anzeige einer Personenliste
 * Die Liste wird sortiert nach Namen angezeigt. Dabei wird ein Pager verwendet, der für
 * jeden Buchstaben eine eigene Seite erstellt.
 */
class tx_cfcleaguefe_actions_ProfileList {

  /**
   *
   */
  function execute($parameters,&$configurations){
// Zunächst sollten wir die Anfangsbuchstaben ermitteln
    $pagerData = $this->findPagerData($configurations);
    $defaultChar = 0;
    if(count($pagerData)) {
      $keys = array_keys($pagerData);
      $defaultChar = $keys[0];
    }

    // Wir benötigen die aktuelle Seite
    $firstChar = $parameters->offsetGet('pointer');
    $firstChar = (strlen(trim($firstChar)) > 0) ? substr($firstChar,0,1) : $defaultChar;

    // Jetzt laden wir die Profile
    $profiles = $this->findProfiles($firstChar, $configurations);


    $viewData =& $configurations->getViewData();
    $viewData->offsetSet('profiles', $profiles);
    $viewData->offsetSet('pagerData', $pagerData);
    $viewData->offsetSet('pointer', $firstChar);

// t3lib_div::debug($viewData,'ac_proflist');

    // View
    $view = tx_div::makeInstance('tx_cfcleaguefe_views_ProfileList');
    $view->setTemplatePath($configurations->getTemplatePath());
    // Das Template wird komplett angegeben
    $view->setTemplateFile($configurations->get('profilelistTemplate'));
    $out = $view->render('profileview', $configurations);

    return $out;
  }

  /**
   * Sucht den Spieler, der gezeigt werden soll.
   */
  function findProfiles($firstChar, &$configurations) {

    $what = '*';
    $from = 'tx_cfcleague_profiles';
    $options['where'] = "LEFT(UCASE(last_name),1) = '$firstChar' ";
    $options['pidlist'] = $configurations->get('profilelistPages');
    $options['recursive'] = $configurations->get('profilelistRecursive');
    $options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';
    $options['orderby'] = 'last_name,first_name';

    $rows = tx_rnbase_util_DB::doSelect($what,$from,$options,0);

    return $rows;
  }

  /**
   * Wir verwenden einen alphabetischen Pager. Also muß zunächst ermittelt werden, welche
   * Buchstaben überhaupt vorkommen.
   */
  function findPagerData(&$configurations) {

    $what = 'LEFT(UCASE(last_name),1) As first_char, count(LEFT(UCASE(last_name),1)) As size';
    $from = 'tx_cfcleague_profiles';
//    $options['where'] = '1';
    $options['pidlist'] = $configurations->get('profilelistPages');
    $options['recursive'] = $configurations->get('profilelistRecursive');
    $options['groupby'] = 'LEFT(UCASE(last_name),1)';

    $rows = tx_rnbase_util_DB::doSelect($what,$from,$options,0);
    $ret = array();
    foreach($rows As $row) {
      $ret[$row['first_char']] = $row['size'];
    }

    return $ret;

//t3lib_div::debug($rows, 'act_proflist');
/*
SELECT LEFT(UCASE(`last_name`),1), count(LEFT(UCASE(`last_name`),1)) 
FROM `tx_cfcleague_profiles` 
WHERE 1
GROUP BY LEFT(UCASE(`last_name`),1)
*/

  }


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/actions/class.tx_cfcleaguefe_actions_ProfileList.php']);
}

?>