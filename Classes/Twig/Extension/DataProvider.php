<?php

namespace System25\T3sports\Twig\Extension;

use System25\T3sports\Model\Fixture;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use tx_rnbase;

/***************************************************************
*  Copyright notice
*
*  (c) 2017-2025 Rene Nitzsche (rene@system25.de)
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

class DataProvider extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('buildMatchReport', [$this, 'buildMatchReport']),
        ];
    }

    public function buildMatchReport(Fixture $match)
    {
        return tx_rnbase::makeInstance(\System25\T3sports\Twig\Data\MatchReport::class, $match);
    }

    /**
     * Get Extension name.
     *
     * @return string
     */
    public function getName()
    {
        return 't3sports_DataProvider';
    }
}
