<?php

namespace System25\T3sports\Search;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2008-2019 Rene Nitzsche
 * Contact: rene@system25.de
 * All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 * *************************************************************
 */

/**
 * Class to search profiles from database.
 *
 * @author Rene Nitzsche
 *
 * @deprecated use \System25\T3sports\Search\ProfileSearch
 */
class ProfileFeSearch extends \tx_rnbase_util_SearchBase
{
    protected function getTableMappings()
    {
        $tableMapping = [];
        $tableMapping['PROFILE'] = 'tx_cfcleague_profiles';

        return $tableMapping;
    }

    protected function getBaseTable()
    {
        return 'tx_cfcleague_profiles';
    }

    public function getWrapperClass()
    {
        return 'tx_cfcleaguefe_models_profile';
    }

    protected function getJoins($tableAliases)
    {
        $join = '';

        return $join;
    }
}
