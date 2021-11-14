<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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

/**
 * Model für ein Personenprofil.
 */
class tx_cfcleaguefe_models_profile extends tx_rnbase_model_base
{
    public function getTableName()
    {
        return 'tx_cfcleague_profiles';
    }

    public function __construct($rowOrUid)
    {
        if (!is_array($rowOrUid) && intval($rowOrUid) < 0) {
            // Unbekannter Spieler
            $this->uid = $rowOrUid;
            $this->setProperty('uid', $rowOrUid);
        } else {
            parent::__construct($rowOrUid);
        }
    }

    /**
     * Liefert den kompletten Namen der Person.
     *
     * @param int $reverse Wenn 1 dann ist die Form <Nachname, Vorname>
     */
    public function getName($reverse = 0)
    {
        if ($reverse) {
            $ret = $this->getProperty('last_name');
            if ($this->getProperty('first_name')) {
                $ret .= ', '.$this->getProperty('first_name');
            }
        } else {
            $ret = $this->getProperty('first_name');
            if ($this->getProperty('first_name')) {
                $ret .= ' ';
            }
            $ret .= $this->getProperty('last_name');
        }

        return $ret;
    }
}
