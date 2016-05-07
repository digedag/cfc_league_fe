<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2016 Rene Nitzsche (rene@system25.de)
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

tx_rnbase::load('tx_rnbase_model_base');


/**
 * Model for a team note.
 */
class tx_cfcleaguefe_models_teamNote extends tx_rnbase_model_base {
	function getTableName(){return 'tx_cfcleague_team_notes';}

	/**
	 * Returns the value according to media type
	 *
	 * @return mixed
	 */
	function getValue() {
		if($this->record['mediatype'] == 0) // Text
			return $this->record['comment'];
		elseif($this->record['mediatype'] == 1) // DAM-Media
			return $this->record['media'];
		elseif($this->record['mediatype'] == 2) // Integer
			return $this->record['number'];
	}
	/**
	 * Returns the NoteType
	 *
	 * @return tx_cfcleaguefe_models_teamNoteType
	 */
	function getType() {
		tx_rnbase::load('tx_cfcleaguefe_models_teamNoteType');
		return tx_cfcleaguefe_models_teamNoteType::getInstance($this->record['type']);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_teamNote.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_teamNote.php']);
}

?>