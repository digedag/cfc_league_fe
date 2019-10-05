<?php
namespace System25\T3sports\Twig\Extension;

/***************************************************************
*  Copyright notice
*
*  (c) 2017-2019 Rene Nitzsche (rene@system25.de)
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
 */
class DataProvider extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('buildMatchReport', array($this, 'buildMatchReport')),
        ];
    }

    public function buildMatchReport(\tx_cfcleague_models_Match $match)
    {
        return \tx_rnbase::makeInstance(\System25\T3sports\Twig\Data\MatchReport::class, $match);
    }

    /**
     * Get Extension name
     *
     * @return string
     */
    public function getName()
    {
        return 't3sports_DataProvider';
    }
}

