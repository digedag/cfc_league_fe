<?php

namespace System25\T3sports\Frontend\Marker;

use Sys25\RnBase\Frontend\Marker\ListMarkerInfo;
use Sys25\RnBase\Frontend\Marker\Templates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2020 Rene Nitzsche (rene@system25.de)
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

class MatchMarkerListInfo extends ListMarkerInfo
{
    public function init($template, $formatter, $marker)
    {
        // Im Template ist noch das Template für Spielfrei enthalten
        $this->freeTemplate = Templates::getSubpart($template, '###'.$marker.'_FREE###');
        // Dieses enfernen wir jetzt direkt aus dem Template
        $subpartArray = ['###'.$marker.'_FREE###' => ''];
        $this->template = Templates::substituteMarkerArrayCached($template, [], $subpartArray);
    }

    public function getTemplate($item)
    {
        return $item->isDummy() ? $this->freeTemplate : $this->template;
    }
}
