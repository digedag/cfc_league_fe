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

// Die Datenbank-Klasse
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');
//require_once(t3lib_extMgm::extPath('t3lib') . 'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');

/**
 * Basisklasse für die meisten Model-Klassen. Sie stellt einen Konstruktor bereit, der sowohl
 * mit einer UID als auch mit einem Datensatz aufgerufen werden kann. Die Daten werden
 * in den Instanzvariablen $uid und $record abgelegt. Diese beiden Variablen sind also immer
 * verfügbar. Der Umfang von $record kann aber je nach Aufruf unterschiedlich sein!
 * @deprecated Klasse liegt jetzt in rn_base
 */
class tx_cfcleaguefe_models_base{

  var $uid;
  var $record;

  /**
   * Most model-classes will be initialized by a uid or a database record. So
   * this is a common contructor.
   * Ensure to overwrite getTableName()!
   */
  function tx_cfcleaguefe_models_base($rowOrUid) {
    if(is_array($rowOrUid)) {
      $this->uid = $rowOrUid['uid'];
      $this->record = $rowOrUid;
    }
    else{
      $this->uid = $rowOrUid;
      if($this->getTableName())
        $this->record = t3lib_BEfunc::getRecord($this->getTableName(),$this->uid);
    }
//    t3lib_div::debug($this->record, 'record');
  }

  /**
   * Kindklassen müssen diese Methode überschreiben und den Namen der gemappten Tabelle liefern!
   * @return Tabellenname als String
   */
  function getTableName() {
    return 0;
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_base.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_base.php']);
}

?>