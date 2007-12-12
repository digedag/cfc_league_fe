<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');

tx_div::load('tx_rnbase_view_Base');


/**
 * Viewklasse für die Anzeige eines Personenprofils
 */
class tx_cfcleaguefe_views_LiveTickerList extends tx_rnbase_view_Base {
  /**
   * Erstellen des Frontend-Outputs
   */
  function render($view, &$configurations){
    $this->_init($configurations);
    $cObj =& $configurations->getCObj(0);
    $templateCode = $cObj->fileResource($this->getTemplate($view,'.html'));

//t3lib_div::debug($this->getTemplate($view,'.html'), 'vw_ticker');

    // Die ViewData bereitstellen
    $viewData =& $configurations->getViewData();
    $matches =& $viewData->offsetGet('matches');

    $template = $cObj->getSubpart($templateCode, count($matches) ? '###LIVETICKER_VIEW###' : '###NO_LIVETICKER###');


//    $out = $template;
    $out = $this->_createView($template, $matches, $cObj, $configurations);
    return $out;
  }

  function _createView($template, &$matches, &$cObj, &$configurations) {
    if(!count($matches)) return $template; // ohne Spiel gibt's keine Marker

    $cObj =& $this->formatter->cObj;
    $matchTemplate = $cObj->getSubpart($template,'###MATCH###');

    $out = '';

//    $token = md5(microtime());
//    $this->linkMatch->label($token);
    $emptyArr = array();
//    $noLink = array('','');

    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_MatchMarker');
    $matchMarker = new $markerClass($this->links);

    $parts = array();
    for($i=0; $i < count($matches); $i++) {
      $match = $matches[$i];

      $parts[] = $matchMarker->parseTemplate($matchTemplate, $match, $this->formatter, 'tickerlist.match.', 'MATCH');

      // Es wird das MarkerArray mit den Daten des Spiels gefüllt.
/*      $markerArray = $this->formatter->getItemMarkerArrayWrapped($match->record, 'tickerlist.match.', 0, 'MATCH_',$match->getColumnNames());

      $this->linkMatch->parameters(array('matchId' => $match->uid));
      $wrappedSubpartArray['###MATCH_LINK###'] = explode($token, $this->linkMatch->makeTag());
      $markerArray['###MATCH_LINK_URL###'] = $this->linkMatch->makeUrl();

      $out .= $cObj->substituteMarkerArrayCached($matchTemplate, $markerArray, $emptyArr, $wrappedSubpartArray);
*/
    }
    // Jetzt die einzelnen Teile zusammenfügen
    $subpartArray['###MATCH###'] = implode($parts, $configurations->get('tickerlist.match.implode'));

    // Zum Schluß das Haupttemplate zusammenstellen
//    $template = $cObj->getSubpart($template,'###MATCHES###');
    $markerArray = array();
//    $subpartArray['###MATCH###'] = $out;
    $out = $cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray); //, $wrappedSubpartArray);

    return $out;
  }

  function _init(&$configurations) {
    $this->formatter = &$configurations->getFormatter();

    $this->links = array();

    $linkClass = tx_div::makeInstanceClassName('tx_lib_link');
    // Die Zielseite ist entweder eine TYPO3 Seite oder eine externe URL
    $target = intval($configurations->get('tickerReportPage'));
    if(!$target) {
      // Prüfen, ob eine externe URL gesetzt wurden
      $target = $configurations->get('tickerReportUrl');
    }
    $linkMatch = new $linkClass;
    $linkMatch->designatorString = $configurations->getQualifier();
    $linkMatch->destination($target); // Das Ziel der Seite vorbereiten
    $this->links['ticker'] = $linkMatch;

//t3lib_div::debug($linkMatch, 'vw_ticker');

    $teamPage = intval($configurations->get('tickerlist.teamPage'));
    if($teamPage) {
      $linkTeam = new $linkClass;
      $linkTeam->designatorString = $configurations->getQualifier();
      $linkTeam->destination($teamPage); // Das Ziel der Seite vorbereiten

      $this->links['team'] = $linkTeam;
    }
  }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_LiveTickerList.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/views/class.tx_cfcleaguefe_views_LiveTickerList.php']);
}
?>