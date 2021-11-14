<?php

namespace System25\T3sports\Hook;

use Exception;
use tx_rnbase;
use tx_rnbase_util_BaseMarker;
use Tx_Rnbase_Utility_Strings;
use System25\T3sports\Model\Match;
use System25\T3sports\Model\Repository\MatchRepository;
use Sys25\RnBase\Utility\Strings;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Configuration\Processor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2020 Rene Nitzsche
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

/**
 * Make links to match reports from tt_news.
 *
 * @author Rene Nitzsche
 */
class TtNewsMarker
{
    /**
     * Hook um weitere Marker in tt_news einzufügen. Es sollte möglich sein auf alle
     * Views von T3sports direkt zu verlinken. Die meisten Einstellungen kommen aus der
     * TS-Config.
     * Beispiel für einen Link in der News:
     * [t3sports:matchreport:123 Zum Spielbericht]
     * t3sports - Konstante
     * matchreport - Verweis auf die TS-Config plugin.tt_news.external_links.matchreport
     * 123 - dynamischer Parameter.
     *
     * @param array $markerArray marker array from tt_news
     * @param array $row tt_news record
     * @param array $lConf tt_news config-array
     * @param \tx_ttnews $ttnews tt_news plugin instance
     */
    public function extraItemMarkerProcessor($markerArray, $row, $lConf, $ttnews)
    {
        $this->configurations = tx_rnbase::makeInstance(Processor::class);
        // Dieses cObj wird dem Controller von T3 übergeben
        $this->configurations->init($ttnews->conf, $ttnews->cObj, 'cfc_league_fe', 'cfc_league_fe');
        $this->linkConf = $this->configurations->get('external_links.');
        $regExpr[] = "/\[t3sports:([\w]*):(\w+) (.*?)]/";
        $markerNames = ['CONTENT', 'SUBHEADER'];
        foreach ($markerNames as $markerName) {
            $markerArray['###NEWS_'.$markerName.'###'] = $this->handleMarker($markerArray['###NEWS_'.$markerName.'###'], $regExpr);
        }
        //  	$GLOBALS['TSFE']->register['SECTION_FRAME'] = $pObj->cObj->data['section_frame']; // Access to section_frame by TS
        return $markerArray;
    }

    public function handleMarker($marker, $expr)
    {
        $marker = preg_replace_callback($expr, [$this, 'replace'], $marker);

        return $marker;
    }

    public function replace($match)
    {
        $linkId = $match[1];
        $conf = $this->linkConf[$linkId.'.'];
        $linkParams = [];
        $params = Strings::trimExplode(',', $conf['ext_parameters']);
        $paramValues = Strings::trimExplode(',', $match[2]);
        for ($i = 0, $cnt = count($params); $i < $cnt; ++$i) {
            $linkParams[$params[$i]] = $paramValues[$i];
        }

        // Wenn ein Spiel im Link ist, dann suchen setzen wir die Altersgruppe als Registerwert
        if (array_key_exists('matchId', array_flip($params))) {
            $uid = $linkParams['matchId'];

            try {
                $matchRepo = new MatchRepository();
                $t3match = $matchRepo->findByUid($uid);
                $competition = $t3match->getCompetition();
                $GLOBALS['TSFE']->register['T3SPORTS_GROUP'] = $competition->getProperty('agegroup');
            } catch (Exception $e) {
                $GLOBALS['TSFE']->register['T3SPORTS_GROUP'] = 0;
            }
        }

        $wrappedSubpartArray = [];
        $empty = [];
        BaseMarker::initLink(
            $empty,
            $empty,
            $wrappedSubpartArray,
            $this->configurations->getFormatter(),
            'external_',
            $linkId,
            'TTNEWS',
            $linkParams
        );
        $out = $wrappedSubpartArray['###TTNEWS_'.strtoupper($linkId).'LINK###'][0].$match[3].$wrappedSubpartArray['###TTNEWS_'.strtoupper($linkId).'LINK###'][1];

        return $out;
    }

    public function getLinkData($marker)
    {
        return $marker;
    }
}
