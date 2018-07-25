<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2018 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_cfcleague_search_Builder');
tx_rnbase::load('tx_cfcleaguefe_util_ScopeController');

/**
 * Default filter for match notes
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_filter_MatchNote extends tx_rnbase_filter_BaseFilter
{

    /**
     * Abgeleitete Filter können diese Methode überschreiben und zusätzliche Filter setzen
     *
     * @param array $fields
     * @param array $options
     * @param tx_rnbase_IParameters $parameters
     * @param Tx_Rnbase_Configuration_ProcessorInterface $configurations
     * @param string $confId
     */
    protected function initFilter(&$fields, &$options, &$parameters, &$configurations, $confId)
    {
        $daysPast = $configurations->get($confId.'timePastExpression');
        if ($daysPast) {
            $fields['MATCHNOTE.CRDATE'][OP_GT_INT] = strtotime($daysPast);
        }
    }
}
