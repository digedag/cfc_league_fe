<?php

namespace System25\T3sports\Service;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2008-2016 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */


/**
 * Service for accessing match information
 *
 * @author Rene Nitzsche
 */
class MatchService extends \Tx_Rnbase_Service_Base
{

    /**
     * Search database for matches
     *
     * @param array $fields
     * @param array $options
     * @return array of tx_cfcleaguefe_models_match
     */
    public function search($fields, $options)
    {
        $searcher = \tx_rnbase_util_SearchBase::getInstance(\System25\T3sports\Search\MatchSearch::class);
        return $searcher->search($fields, $options);
    }

    /**
     *
     * @return \tx_cfcleague_util_MatchTableBuilder
     */
    public function getMatchTable()
    {
        return \tx_rnbase::makeInstance('tx_cfcleague_util_MatchTableBuilder');
    }
}
