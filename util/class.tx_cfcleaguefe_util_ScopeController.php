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

tx_div::load('tx_cfcleaguefe_search_Builder');
tx_div::load('tx_cfcleaguefe_models_saison');
tx_div::load('tx_cfcleaguefe_models_competition');
tx_div::load('tx_cfcleaguefe_models_group');
tx_div::load('tx_cfcleaguefe_models_club');

/**
 * Viele Views dieser Extension müssen wissen, für welche Saison, Liga, Alterklasse
 * und eventuell Spielrunde und Verein die Daten angezeigt werden sollen. Diese Kombination 
 * ist der aktuelle <b>Scope der Anwendung</b>.
 * Diese Klasse stellt Methoden bereit, um den aktuell ausgewählten Scope zu ermitteln.
 * Wenn in der Konfiguation des Views festgelegt wurde, daß einzelne Scope-Elemente durch
 * den FE-User geändert werden können, dann werden bei Abruf des aktuellen Scopes 
 * automatisch die notwendigen Daten vorbereitet und in der ViewData abgelegt.
 */
class tx_cfcleaguefe_util_ScopeController {
  // Speichert die UID des aktuellen cObject
  static private $_cObjectUID = array();

  /**
   * Diese Funktion stellt die UIDs der aktuell ausgewählten Ligen bereit.
   * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen 
   * vorbereitet und in die viewData der Config gelegt.
   * Es wird ein Array mit dem aktuell gültigen Scope zurückgeliefert.
   * @param $useObjects Wenn true werden ganze Objekte
   * @return Array mit den UIDs als String
   */
  function handleCurrentScope($parameters,&$configurations, $useObjects = false) {
    $ret = Array();

// TODO: Die Ermittlung des aktuellen Scopes sollte pro cObj nur einmal erfolgen. Derzeit
// wird für jeden Teilview alles noch einmal neu ermittelt.


    $ret['SAISON_UIDS'] = tx_cfcleaguefe_util_ScopeController::handleCurrentSaison($parameters,$configurations, $useObjects);
    $ret['GROUP_UIDS'] = tx_cfcleaguefe_util_ScopeController::handleCurrentGroup($parameters,$configurations, $useObjects);
    tx_cfcleaguefe_util_ScopeController::handleCurrentCompetition($ret, $parameters,$configurations,$ret['SAISON_UIDS'],$ret['GROUP_UIDS'], $useObjects);
    $ret['CLUB_UIDS'] = tx_cfcleaguefe_util_ScopeController::handleCurrentClub($parameters,$configurations,$ret['SAISON_UIDS'],$ret['GROUP_UIDS'], $ret['COMP_UIDS'], $useObjects);

    $ret['ROUND_UIDS'] = tx_cfcleaguefe_util_ScopeController::handleCurrentRound($parameters,$configurations,$ret['SAISON_UIDS'],$ret['GROUP_UIDS'], $ret['COMP_UIDS'], $ret['CLUB_UIDS'], $useObjects);

    // Abschließend die UID des cObjects speichern
//    tx_cfcleaguefe_util_ScopeController::$_cObjectUID[$configurations->cObj->data['uid']] = $ret;
    return $ret;
  }

  /**
   * Diese Funktion stellt die UIDs der aktuell ausgewählten Ligen bereit.
   * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen 
   * vorbereitet und in die viewData der Config gelegt.
   * @return String Die UIDs als String
   */
  function handleCurrentSaison($parameters,&$configurations, $useObjects = false) {
    $viewData =& $configurations->getViewData();
    $saisonUids = $configurations->get('saisonSelection');

    // Soll eine SelectBox für Saison gezeigt werden?
    if($configurations->get('saisonSelectionInput')) {

      // Die UIDs der Saisons in Objekte umwandeln, um eine Selectbox zu bauen
      // TODO: Es sollten zusätzliche Kriterien zur Ermittlung der Saisons herangezogen werden
      // Einfach alle Saisons zu zeigen führt zu vielen leeren Seiten 
      $saisons = tx_cfcleaguefe_models_saison::findItems($saisonUids);
      $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareSelect($saisons,$parameters, 'saison', $useObjects ? '' : 'name');
      $saisonUids = $dataArr[1];
      $viewData->offsetSet('saison_select', $dataArr);

      $configurations->addKeepVar('saison',$saisonUids);
    }
    return $saisonUids;
  }

  /**
   * Diese Funktion stellt die UIDs der aktuell ausgewählten Alterklasse bereit.
   * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen 
   * vorbereitet und in die viewData der Config gelegt.
   * @return String Die UIDs als String
   */
  function handleCurrentGroup($parameters,&$configurations, $useObjects = false) {
    $viewData =& $configurations->getViewData();
    $groupUids = $configurations->get('groupSelection');

    // Soll eine SelectBox für Altersgruppe gezeigt werden?
    if($configurations->get('groupSelectionInput')) {
      // Die UIDs der Wettkämpfe in Objekte umwandeln um eine Selectbox zu bauen
      $groups = tx_cfcleaguefe_models_group::findAll($groupUids);
      $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareSelect($groups,$parameters,'group', $useObjects ? '' : 'name');
      $groupUids = $dataArr[1];
      $viewData->offsetSet('group_select', $dataArr);
      $configurations->addKeepVar('group',$groupUids);
    }
    return $groupUids;
  }

  /**
   * Diese Funktion stellt die UIDs der aktuell ausgewählten Vereine bereit.
   * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen 
   * vorbereitet und in die viewData der Config gelegt.
   * @return String Die UIDs als String
   */
  function handleCurrentClub($parameters,&$configurations, $saisonUids,$groupUids,$compUids, $useObjects = false) {
    $viewData =& $configurations->getViewData();
    $clubUids = $configurations->get('clubSelection');

    // Soll eine SelectBox für den Verein gezeigt werden?
    // Das machen wir nur, wenn mindestens ein Verein konfiguriert wurde
    if($configurations->get('clubSelectionInput')){ // && strlen($clubUids) > 0) {
      // Die UIDs der Vereine in Objekte umwandeln, um eine Selectbox zu bauen
      $clubs = tx_cfcleaguefe_models_club::findAll($clubUids, $saisonUids,$groupUids,$compUids);
      $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareSelect($clubs,$parameters,'club', $useObjects ? '' : 'name');
      $clubUids = $dataArr[1];
      $viewData->offsetSet('club_select', $dataArr);
      $configurations->addKeepVar('club',$clubUids);
    }
    return $clubUids;
  }

  /**
   * Diese Funktion stellt die UIDs der aktuell ausgewählten Wettbewerb bereit.
   * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen 
   * vorbereitet und in die viewData der Config gelegt.
   * @return String Die UIDs als String
   */
	function handleCurrentCompetition(&$scopeArr, $parameters,&$configurations, $saisonUids,$groupUids, $useObjects = false) {
		$viewData =& $configurations->getViewData();
		$compUids = $configurations->get('competitionSelection');

		// Soll eine SelectBox für Wettkämpfe gezeigt werden?
		// Wenn die RoundSelection aktiviert ist, dann wird die Wettbewerbs-Selection automatisch mit aktiviert
		if($configurations->get('competitionSelectionInput') || 
			($configurations->get('roundSelectionInput') && !t3lib_div::testInt($compUids)) ) {
			// Die UIDs der Wettkämpfe in Objekte umwandeln, um eine Selectbox zu bauen
			// Suche der Wettbewerbe über den Service
			$compServ = tx_cfcleaguefe_util_ServiceRegistry::getCompetitionService();
			$fields = array();
			$options = array();
			tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'scope.competition.options.');
			tx_cfcleaguefe_search_Builder::buildCompetitionByScope($fields, $parameters, $configurations, $saisonUids, $groupUids, $compUids);

			$competitions = $compServ->search($fields, $options);
//			$competitions = tx_cfcleaguefe_models_competition::findAll($saisonUids, $groupUids, $compUids);
			$dataArr = tx_cfcleaguefe_util_ScopeController::_prepareSelect($competitions,$parameters,'competition', $useObjects ? '' : 'name');
			$compUids = $dataArr[1];
			$viewData->offsetSet('competition_select', $dataArr);
			$configurations->addKeepVar('competition',$compUids);
		}
		$scopeArr['COMP_UIDS'] = $compUids;
		// Zusätzlich noch die weiteren Einschränkungen mit in das ScopeArray legen, weil diese Infos auch
		// von anderen Views benötigt werden
		$value = intval($configurations->get('scope.competition.obligation'));
		if($value) $scopeArr['COMP_OBLIGATION'] = $value; // 1 - Pflicht, 2- freie Spiele
		$value = $configurations->get('scope.competition.type');
		if(strlen(trim($value))) $scopeArr['COMP_TYPES'] = $value;
	}

  /**
   * Diese Funktion stellt die UIDs der aktuell ausgewählten Spielrunde bereit.
   * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen 
   * vorbereitet und in die viewData der Config gelegt.
   * Die Spielrunde wird bei folgenden Bedingungen eingeblendet:<ul>
   * <li> Es ist nur ein Wettbewerb ausgewählt
   * <li> Im Flexform ist der Wert roundSelectionInput gesetzt
   * </ul>
   * @param compUids String die UIDs der aktuell eingestellten Wettbewerbe
   * @return String Die UIDs als String
   */
  function handleCurrentRound($parameters,&$configurations, $saisonUids,$groupUids, $compUids, $clubUids, $useObjects = false) {
    $viewData =& $configurations->getViewData();
//    $club = $configurations->get('clubSelection');
    // Soll eine SelectBox für Wettkämpfe gezeigt werden?
    if($configurations->get('roundSelectionInput') && (isset($compUids) && t3lib_div::testInt($compUids))) {
      $currCompetition = new tx_cfcleaguefe_models_competition($compUids);
        // Die Spielrunden ermitteln
      $rounds = $currCompetition->getRounds();
//      $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareRoundSelect($rounds,$parameters, $useObjects);

      $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareRoundSelect($rounds,$parameters, $useObjects ? '' : 'uid');
      $roundUid = $dataArr[1];
      $viewData->offsetSet('round_select', $dataArr);
      $configurations->addKeepVar('round',$roundUid);
    }
      

//    if(!(isset($clubUids) && t3lib_div::testInt($clubUids)) && (isset($compUids) && t3lib_div::testInt($compUids))) {
//      // Wir müssen den Typ des Wettbewerbs ermitteln. 
////t3lib_div::debug(!(isset($clubUids) && t3lib_div::testInt($clubUids)), 'utl_scope');
//      $currCompetition = new tx_cfcleaguefe_models_competition($compUids);
//      if(intval($currCompetition->record['type']) == 1) {
//        // Die Spielrunden ermitteln
//        $rounds = $currCompetition->getRounds();
////        $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareRoundSelect($rounds,$parameters, $useObjects);
//
//        $dataArr = tx_cfcleaguefe_util_ScopeController::_prepareRoundSelect($rounds,$parameters, $useObjects ? '' : 'uid');
//        $roundUid = $dataArr[1];
//        $viewData->offsetSet('round_select', $dataArr);
//        $configurations->addKeepVar('round',$roundUid);
//      }
//    }
    return $roundUid;
  }


  /**
   * Liefert ein Array für die Erstellung der Select-Box für eine Model-Klasse
   * Das Ergebnis-Array hat zwei Einträge: Index 0 enthält das Wertearray, Index 1 das
   * aktuelle Element
   * @param $displayAttrName Der Name eines Atttributs, um dessen Wert anzuzeigen. Wenn der 
   *        String leer ist, dann wird das gesamten Objekt als Wert verwendet.
   */
  function _prepareSelect($objects,$parameters, $parameterName, $displayAttrName = 'name') {

//t3lib_div::debug($displayAttrName , 'displayname utl_scope');

    global $TSFE;
    $ret = array();
    if(count($objects)) {
      foreach($objects As $object) {
        $ret[0][$object->uid] = strlen($displayAttrName) == 0 ? $object : $object->record[$displayAttrName];
      }

      // Wenn keine Parameter gesetzt sind, dann sollten wir schauen, ob was in der Session liegt
      // Sessionspeicherung wird wieder entfernt, da dies zu falsch generierten
      // Seiten bei Verwendung des Menüs führen kann.
      $paramValue = $parameters->offsetGet($parameterName);
//      if(!isset($paramValue)) {
//        $paramValue = $TSFE->fe_user->getKey('ses', 'cfc_'.$parameterName);
//      }
      
      // Der Wert im Parameter darf nur übernommen werden, wenn er in der SelectBox vorkommt
      if(isset($paramValue) && array_key_exists($paramValue, $ret[0]))
        $ret[1] = $paramValue;
      $ret[1] = $ret[1] ? $ret[1] : $objects[0]->uid;

      // Aktuellen Wert speichern
//      $TSFE->fe_user->setKey('ses', 'cfc_'.$parameterName, $ret[1]);

    }
    return $ret;
  }

  /**
   * Liefert ein Array für die Erstellung der Select-Box für die Spielrunden einer Liga
   */
  function _prepareRoundSelect($objects, $parameters, $displayAttrName = 'name') {
    $ret = array();
    if(count($objects)) {
      $default = $objects[0]->record['uid'];
      foreach($objects As $object) {
        $ret[0][$object->record['uid']] = strlen($displayAttrName) == 0 ? $object : $object->record['name'];
        if($object->record['finished']) // Aktuellen Spieltag suchen
          $default = $object->record['uid'];
      }
      // Der Wert im Parameter darf nur übernommen werden, wenn er in der SelectBox vorkommt
      $paramValue = $parameters->offsetGet('round');
      if(isset($paramValue) && array_key_exists($paramValue, $ret[0]))
        $ret[1] = $paramValue;
      $ret[1] = $ret[1] ? $ret[1] : $default;
    }
    return $ret;
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ScopeController.php'])	{
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/util/class.tx_cfcleaguefe_util_ScopeController.php']);
}

?>
