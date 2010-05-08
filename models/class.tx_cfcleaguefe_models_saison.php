<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_model_base');


/**
 * Model für eine Saison.
 */
class tx_cfcleaguefe_models_saison extends tx_rnbase_model_base{

  function getTableName(){return 'tx_cfcleague_saison';}

  /**
   * statische Methode, die ein Array mit Instanzen dieser Klasse liefert. Ist der übergebene
   * Parameter leer, dann werden alle Saison-Datensätze aus der Datenbank geliefert. Ansonsten 
   * wird ein String mit der uids der gesuchten Saisons erwartet ('2,4,10,...').
   */
  function findItems($uids) {
    if(is_string($uids) && strlen($uids) > 0) {
      $where = 'uid IN (' . $uids .')';
    }
    else
      $where = '1';
    /*
    SELECT * FROM tx_cfcleague_saison WHERE uid IN ($uid)
    */

    return  tx_rnbase_util_DB::queryDB('*','tx_cfcleague_saison',$where,
              '','sorting','tx_cfcleaguefe_models_saison',0);
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_saison.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_saison.php']);
}

?>