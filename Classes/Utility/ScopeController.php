<?php

namespace System25\T3sports\Utility;

use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Request\ParametersInterface;
use Sys25\RnBase\Search\SearchBase;
use Sys25\RnBase\Utility\Math;
use System25\T3sports\Model\Competition;
use System25\T3sports\Model\CompetitionRound;
use System25\T3sports\Model\Group;
use System25\T3sports\Model\Repository\ClubRepository;
use System25\T3sports\Model\Repository\SaisonRepository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2023 Rene Nitzsche (rene@system25.de)
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
 * Viele Views dieser Extension müssen wissen, für welche Saison, Liga, Alterklasse
 * und eventuell Spielrunde und Verein die Daten angezeigt werden sollen.
 * Diese Kombination
 * ist der aktuelle <b>Scope der Anwendung</b>.
 * Diese Klasse stellt Methoden bereit, um den aktuell ausgewählten Scope zu ermitteln.
 * Wenn in der Konfiguation des Views festgelegt wurde, daß einzelne Scope-Elemente durch
 * den FE-User geändert werden können, dann werden bei Abruf des aktuellen Scopes
 * automatisch die notwendigen Daten vorbereitet und in der ViewData abgelegt.
 */
class ScopeController
{
    // Speichert die UID des aktuellen cObject
    private static $_cObjectUID = [];

    private static $_scopeParams = [];

    //public static function handleCurrentScope(RequestInterface $request, $useObjects = false)

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Ligen bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     * Es wird ein Array mit dem aktuell gültigen Scope zurückgeliefert.
     *
     * @param ParametersInterface $parameters
     * @param ConfigurationInterface $configurations
     * @param bool $useObjects Wenn true werden ganze Objekte
     *
     * @return array mit den UIDs als String
     */
    public static function handleCurrentScope(ParametersInterface $parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $cObjUid = $configurations->getCObj()->data['uid'];
        // Wenn das Plugin als lib-Objekt eingebunden wird, dann gibt es keine cObject-UID
        if (!$cObjUid || !isset(self::$_cObjectUID[$cObjUid]) || intval($configurations->get('scope.noCache'))) {
            // Dieser Teil wird pro Plugin (cObject) nur einmal aufgerufen
            $ret = [];
            $ret['SAISON_UIDS'] = self::handleCurrentSaison($parameters, $configurations, $useObjects);
            $ret['GROUP_UIDS'] = self::handleCurrentCompetitionGroup($parameters, $configurations, $useObjects);
            $ret['TEAMGROUP_UIDS'] = self::handleCurrentTeamGroup($parameters, $configurations, $useObjects);
            self::handleCurrentCompetition($ret, $parameters, $configurations, $ret['SAISON_UIDS'], $ret['GROUP_UIDS'], $useObjects);
            $ret['CLUB_UIDS'] = self::handleCurrentClub($parameters, $configurations, $ret['SAISON_UIDS'], $ret['GROUP_UIDS'], $ret['COMP_UIDS'], $useObjects);
            $ret['ROUND_UIDS'] = self::handleCurrentRound($parameters, $configurations, $ret['SAISON_UIDS'], $ret['GROUP_UIDS'], $ret['COMP_UIDS'], $ret['CLUB_UIDS'], $useObjects);

            // Die Daten für das Plugin cachen
            self::$_cObjectUID[$cObjUid] = $ret;
            // die variablen Parameter in ein T3-Register schreiben
            if (!intval($configurations->get('scope.noScopeParams'))) {
                self::setScopeParams($configurations);
            }
        }

        return self::$_cObjectUID[$cObjUid];
    }

    /**
     * Setzt die aktuellen Userdaten in ein TYPO3-Register.
     * Damit können sie per Typoscript abgefragt und in Links verwendet werden.
     *
     * @param ConfigurationInterface $configurations
     */
    private static function setScopeParams(ConfigurationInterface $configurations)
    {
        if (!count(self::$_scopeParams)) {
            return;
        }

        $params = '';
        $qualifier = $configurations->getQualifier();
        foreach (self::$_scopeParams as $key => $value) {
            $params .= '&'.$qualifier.'['.$key.']='.rawurlencode($value);
        }
        $GLOBALS['TSFE']->register['T3SPORTS_SCOPEPARAMS'] = $params;
    }

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Ligen bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     *
     * @param ParametersInterface $parameters
     * @param ConfigurationInterface $configurations
     *
     * @return string Die UIDs als String
     */
    private static function handleCurrentSaison(ParametersInterface $parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $saisonUids = $configurations->get('saisonSelection');

        // Soll eine SelectBox für Saison gezeigt werden?
        if ($configurations->get('saisonSelectionInput')) {
            $saisonRepo = new SaisonRepository();
            // Die UIDs der Saisons in Objekte umwandeln, um eine Selectbox zu bauen
            // TODO: Es sollten zusätzliche Kriterien zur Ermittlung der Saisons herangezogen werden
            // Einfach alle Saisons zu zeigen führt zu vielen leeren Seiten
            $saisons = $saisonRepo->findByUids($saisonUids);
            $dataArr = self::_prepareSelect($saisons, $parameters, 'saison', $useObjects ? '' : 'name');
            $saisonUids = $dataArr[1];
            $viewData->offsetSet('saison_select', $dataArr);
            $configurations->addKeepVar('saison', $saisonUids);
            self::$_scopeParams['saison'] = $saisonUids;
        }

        return $saisonUids;
    }

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Alterklasse für einen Wettbewerb bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     *
     * @return string Die UIDs als String
     */
    private static function handleCurrentCompetitionGroup($parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $groupUids = $configurations->get('groupSelection');

        // Soll eine SelectBox für Altersgruppe gezeigt werden?
        if ($configurations->get('groupSelectionInput')) {
            // Die UIDs der Altersklasse in Objekte umwandeln um eine Selectbox zu bauen
            $groups = Group::findAll($groupUids);
            $dataArr = self::_prepareSelect($groups, $parameters, 'group', $useObjects ? '' : 'name');
            $groupUids = $dataArr[1];
            $viewData->offsetSet('group_select', $dataArr);
            $configurations->addKeepVar('group', $groupUids);
            self::$_scopeParams['group'] = $groupUids;
        }

        return $groupUids;
    }

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Alterklasse für einen Wettbewerb bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     *
     * @return string Die UIDs als String
     */
    private static function handleCurrentTeamGroup($parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $groupUids = $configurations->get('scope.teamGroup');

        // Soll eine SelectBox für Altersgruppe gezeigt werden?
        if ($configurations->get('scope.teamGroupSelectionInput')) {
            // Die UIDs der Altersklasse in Objekte umwandeln um eine Selectbox zu bauen
            $groups = Group::findAll($groupUids);
            $dataArr = self::_prepareSelect($groups, $parameters, 'group', $useObjects ? '' : 'name');
            $groupUids = $dataArr[1];
            $viewData->offsetSet('teamgroup_select', $dataArr);
            $configurations->addKeepVar('teamgroup', $groupUids);
            self::$_scopeParams['teamgroup'] = $groupUids;
        }

        return $groupUids;
    }

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Vereine bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     *
     * @return string Die UIDs als String
     */
    private static function handleCurrentClub($parameters, &$configurations, $saisonUids, $groupUids, $compUids, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $clubUids = $configurations->get('clubSelection');
        $clubRepo = new ClubRepository();

        // Soll eine SelectBox für den Verein gezeigt werden?
        // Das machen wir nur, wenn mindestens ein Verein konfiguriert wurde
        if ($configurations->get('clubSelectionInput')) {
            // Die UIDs der Vereine in Objekte umwandeln, um eine Selectbox zu bauen
            $clubs = $clubRepo->findAllByScope($clubUids, $saisonUids, $groupUids, $compUids);
            $dataArr = self::_prepareSelect($clubs, $parameters, 'club', $useObjects ? '' : 'name');
            $clubUids = $dataArr[1];
            $viewData->offsetSet('club_select', $dataArr);
            $configurations->addKeepVar('club', $clubUids);
            self::$_scopeParams['club'] = $clubUids;
        }

        return $clubUids;
    }

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Wettbewerb bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     *
     * @return string Die UIDs als String
     */
    private static function handleCurrentCompetition(&$scopeArr, $parameters, $configurations, $saisonUids, $groupUids, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $compUids = $configurations->get('competitionSelection');

        // Soll eine SelectBox für Wettkämpfe gezeigt werden?
        // Wenn die RoundSelection aktiviert ist, dann wird die Wettbewerbs-Selection automatisch mit aktiviert
        if ($configurations->get('competitionSelectionInput') || ($configurations->get('roundSelectionInput') && !Math::isInteger($compUids))) {
            // Die UIDs der Wettkämpfe in Objekte umwandeln, um eine Selectbox zu bauen
            // Suche der Wettbewerbe über den Service
            $compServ = ServiceRegistry::getCompetitionService();
            $fields = [];
            $options = [];
            SearchBase::setConfigOptions($options, $configurations, 'scope.competition.options.');
            \System25\T3sports\Search\SearchBuilder::buildCompetitionByScope($fields, $parameters, $configurations, $saisonUids, $groupUids, $compUids);

            $competitions = $compServ->search($fields, $options);
            $dataArr = self::_prepareSelect($competitions, $parameters, 'competition', $useObjects ? '' : 'name');
            $compUids = $dataArr[1];
            $viewData->offsetSet('competition_select', $dataArr);
            $configurations->addKeepVar('competition', $compUids);
            self::$_scopeParams['competition'] = $compUids;
        }
        $scopeArr['COMP_UIDS'] = $compUids;
        // Zusätzlich noch die weiteren Einschränkungen mit in das ScopeArray legen, weil diese Infos auch
        // von anderen Views benötigt werden
        $value = $configurations->getInt('scope.competition.obligation');
        if ($value) {
            $scopeArr['COMP_OBLIGATION'] = $value; // 1 - Pflicht, 2- freie Spiele
        }
        $value = $configurations->get('scope.competition.type');
        if (strlen(trim($value))) {
            $scopeArr['COMP_TYPES'] = $value;
        }
    }

    /**
     * Diese Funktion stellt die UIDs der aktuell ausgewählten Spielrunde bereit.
     * Durch den Aufruf werden gleichzeitig die Daten für die Select-Boxen
     * vorbereitet und in die viewData der Config gelegt.
     * Die Spielrunde wird bei folgenden Bedingungen eingeblendet:<ul>
     * <li> Es ist nur ein Wettbewerb ausgewählt
     * <li> Im Flexform ist der Wert roundSelectionInput gesetzt
     * </ul>.
     *
     * @param
     *            compUids String die UIDs der aktuell eingestellten Wettbewerbe
     *
     * @return string Die UIDs als String
     */
    private static function handleCurrentRound($parameters, $configurations, $saisonUids, $groupUids, $compUids, $clubUids, $useObjects = false)
    {
        $roundUid = '';
        $viewData = $configurations->getViewData();
        // Soll eine SelectBox für Wettkämpfe gezeigt werden?
        if ($configurations->get('roundSelectionInput') && (isset($compUids) && Math::testInt($compUids))) {
            $currCompetition = new Competition($compUids);
            // Die Spielrunden ermitteln
            $rounds = $currCompetition->getRounds();
            $dataArr = self::prepareRoundSelect($rounds, $parameters, $configurations, 'scope.rounds.', $useObjects ? '' : 'uid');
            $roundUid = $dataArr[1];
            $viewData->offsetSet('round_select', $dataArr);
            $configurations->addKeepVar('round', $roundUid);
        }

        return $roundUid;
    }

    /**
     * Liefert ein Array für die Erstellung der Select-Box für eine Model-Klasse
     * Das Ergebnis-Array hat zwei Einträge: Index 0 enthält das Wertearray, Index 1 das
     * aktuelle Element.
     *
     * @param string $displayAttrName Der
     *            Name eines Attributs, um dessen Wert anzuzeigen. Wenn der
     *            String leer ist, dann wird das gesamten Objekt als Wert verwendet.
     */
    private static function _prepareSelect($objects, ParametersInterface $parameters, $parameterName, $displayAttrName = 'name')
    {
        $ret = [];
        if (count($objects)) {
            foreach ($objects as $object) {
                $ret[0][$object->getUid()] = 0 == strlen($displayAttrName) ? $object : $object->getProperty($displayAttrName);
            }
            $paramValue = $parameters->get($parameterName);
            // Der Wert im Parameter darf nur übernommen werden, wenn er in der SelectBox vorkommt
            if (isset($paramValue) && array_key_exists($paramValue, $ret[0])) {
                $ret[1] = $paramValue;
            }
            $ret[1] = $ret[1] ?? $objects[0]->getUid();
        }

        return $ret;
    }

    /**
     * Liefert ein Array für die Erstellung der Select-Box für die Spielrunden einer Liga.
     *
     * @param CompetitionRound $rounds
     * @param ParametersInterface $parameters
     * @param ConfigurationInterface $configurations
     * @param $confId
     * @param string $displayAttrName
     *
     * @return array
     */
    private static function prepareRoundSelect($rounds, $parameters, $configurations, $confId, $displayAttrName = 'name')
    {
        $ret = [];
        if (count($rounds)) {
            $skipOtherRounds = false;
            $default = $rounds[0]->getUid();
            foreach ($rounds as $object) {
                // Die Daten für die Auswahlbox füllen
                $ret[0][$object->getUid()] = 0 == strlen($displayAttrName) ? $object : $object->getProperty($displayAttrName);
                // Aktuellen Spieltag suchen. Hier sollte das aktuelle Datum mit berücksichtigt werden
                if (!$skipOtherRounds && $object->getProperty('finished')) {
                    $default = $object->getUid();
                }
                // Bei einer Lücke in den beendeten Spieltagen die Spieltage nach der Lücke ignorieren
                if (!$skipOtherRounds && !$object->getProperty('finished') && !$configurations->getBool('leaguetable.noSkipOtherRounds')) {
                    $skipOtherRounds = true;
                }
            }
            // Ist ein fester Wert für die aktuelle Spielrunde konfiguriert?
            if (($fixedRound = $configurations->getInt($confId.'fixedCurrent')) > 0) {
                $default = $fixedRound;
            }
            // Der Wert im Parameter darf nur übernommen werden, wenn er in der SelectBox vorkommt
            $paramValue = $parameters->get('round');
            if (isset($paramValue) && array_key_exists($paramValue, $ret[0])) {
                $ret[1] = $paramValue;
            }
            $ret[1] = isset($ret[1]) ? $ret[1] : $default;
        }

        return $ret;
    }
}
