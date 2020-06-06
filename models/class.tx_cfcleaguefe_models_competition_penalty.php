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

tx_rnbase::load('tx_rnbase_model_base');

/**
 * Model für eine Ligastrafe.
 * Diese Strafe werden für die Berechnung der Ligatabelle verwendet und sollen
 * die Sportgerichtsurteile nachvollziehbar im Datenmodell abbilden. Mit einer
 * Strafe können die Punkte und Tore eines Teams korrigiert werden. Außerdem
 * kann ein Team unabhängig vom Punktestand auf einen bestimmten Tabellenplatz
 * gesetzt werden. Dies ist z.B. bei Lizenzentzug notwendig.
 */
class tx_cfcleaguefe_models_competition_penalty extends tx_rnbase_model_base
{
    public function getTableName()
    {
        return 'tx_cfcleague_competition_penalty';
    }

    public function isCorrection()
    {
        return $this->record['correction'] > 0;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_competition_penalty.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_competition_penalty.php'];
}
