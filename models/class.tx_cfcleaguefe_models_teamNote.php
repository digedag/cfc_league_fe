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
class tx_cfcleaguefe_models_teamNote extends tx_rnbase_model_base
{
    public function getTableName()
    {
        return 'tx_cfcleague_team_notes';
    }

    /**
     * Returns the value according to media type.
     *
     * @return mixed
     */
    public function getValue()
    {
        if (0 == $this->getProperty('mediatype')) { // Text

            return $this->getProperty('comment');
        } elseif (1 == $this->getProperty('mediatype')) { // DAM-Media

            return $this->getProperty('media');
        } elseif (2 == $this->getProperty('mediatype')) { // Integer

            return $this->getProperty('number');
        }
    }

    /**
     * Returns the NoteType.
     *
     * @return tx_cfcleaguefe_models_teamNoteType
     */
    public function getType()
    {
        tx_rnbase::load('tx_cfcleaguefe_models_teamNoteType');

        return tx_cfcleaguefe_models_teamNoteType::getTeamNoteInstance($this->getProperty('type'));
    }
}
