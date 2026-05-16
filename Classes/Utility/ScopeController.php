<?php

namespace System25\T3sports\Utility;

use PDO;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Configuration\Processor;
use Sys25\RnBase\Database\Connection;
use Sys25\RnBase\Frontend\Request\ParametersInterface;
use Sys25\RnBase\Search\SearchBase;
use Sys25\RnBase\Utility\Math;
use Sys25\RnBase\Utility\TYPO3;
use System25\T3sports\Model\Competition;
use System25\T3sports\Model\CompetitionRound;
use System25\T3sports\Model\Group;
use System25\T3sports\Model\Repository\ClubRepository;
use System25\T3sports\Model\Repository\SaisonRepository;
use tx_rnbase;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2026 Rene Nitzsche (rene@system25.de)
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
    private const PARAM_PLUGIN = 'plgid';

    // Speichert die UID des aktuellen cObject
    private array $cObjectUID = [];

    private array $scopeParams = [];
    /** @var SaisonRepository */
    private $saisonRepo;
    /** @var ClubRepository */
    private $clubRepo;

    public function __construct(SaisonRepository $saisonRepo, ClubRepository $clubRepo)
    {
        $this->saisonRepo = $saisonRepo;
        $this->clubRepo = $clubRepo;
    }

    public function handleScope(ParametersInterface $parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        if ($configurations->getBool('scope.readFromPlugin')) {
            $pluginId = $parameters->getInt(self::PARAM_PLUGIN);
            if ($pluginId) {
                $configurations = $this->getConfigurationForPluginUid($pluginId);
            }
        }

        $cObjUid = $configurations->getCObj()->data['uid'];
        // Wenn das Plugin als lib-Objekt eingebunden wird, dann gibt es keine cObject-UID
        if (!$cObjUid || !isset($this->cObjectUID[$cObjUid]) || intval($configurations->get('scope.noCache'))) {
            // Dieser Teil wird pro Plugin (cObject) nur einmal aufgerufen
            $ret = [];
            $ret['SAISON_UIDS'] = $this->handleCurrentSaison($parameters, $configurations, $useObjects);
            $ret['GROUP_UIDS'] = $this->handleCurrentCompetitionGroup($parameters, $configurations, $useObjects);
            $ret['TEAMGROUP_UIDS'] = $this->handleCurrentTeamGroup($parameters, $configurations, $useObjects);
            $this->handleCurrentCompetition($ret, $parameters, $configurations, $ret['SAISON_UIDS'], $ret['GROUP_UIDS'], $useObjects);
            $ret['CLUB_UIDS'] = $this->handleCurrentClub($parameters, $configurations, $ret['SAISON_UIDS'], $ret['GROUP_UIDS'], $ret['COMP_UIDS'], $useObjects);
            $ret['ROUND_UIDS'] = $this->handleCurrentRound($parameters, $configurations, $ret['SAISON_UIDS'], $ret['GROUP_UIDS'], $ret['COMP_UIDS'], $ret['CLUB_UIDS'], $useObjects);

            // Die Daten für das Plugin cachen
            $this->cObjectUID[$cObjUid] = $ret;
            // die variablen Parameter in ein T3-Register schreiben
            if (!intval($configurations->get('scope.noScopeParams'))) {
                $this->setScopeParams($configurations);
            }
            // ensure that the plugin block is preserved for AJAX requests even when
            // no scope parameters are present in the current request
            $this->ensurePluginIdKeepVar($configurations);
        }

        return $this->cObjectUID[$cObjUid];
    }

    private function getConfigurationForPluginUid($pluginIdFromRequest)
    {
        $pluginId = (int) $pluginIdFromRequest;
        if (!$pluginId) {
            return null;
        }

        // Try direct match: often the unique id is simply the tt_content.uid
        $record = $this->fetchTtContentByUid($pluginId);
        if (!$record) {
            // Das funktioniert nicht für Plugins, die per TS erstellt werden.
            return null;
        }

        $listType = $record['list_type'] ?? '';
        // Sicherheitscheck: nur erwartetes Plugin (optional)
        if ('tx_cfcleaguefe_competition' !== $listType) {
            // falls du mehrere plugin keys hast, prüfe hier entsprechend oder entferne die Prüfung.
            return null;
        }

        // Erzeuge cObj mit Record-Daten
        /** @var ContentObjectRenderer $cObj */
        $cObj = tx_rnbase::makeInstance(ContentObjectRenderer::class);
        $cObj->start($record, 'tt_content');

        // TypoScript-Subtree für das Plugin holen

        $tsSetup = TYPO3::getTSFE()->tmpl->setup['plugin.'][$listType.'.'] ?? [];

        /** @var Processor $processor */
        $processor = tx_rnbase::makeInstance(Processor::class);
        $processor->init($tsSetup, $cObj, 'cfc_league_fe', 'cfc_league_fe');

        return $processor;
    }

    private function fetchTtContentByUid($uid)
    {
        $options['where'] = function (QueryBuilder $qb) use ($uid) {
            $qb->andWhere($qb->expr()->eq(
                'uid',
                $qb->createNamedParameter($uid, PDO::PARAM_INT)));
        };
        $result = Connection::getInstance()->doSelect('*', 'tt_content', $options);

        return $result[0] ?? null;
    }

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
     *
     * @deprecated inject service
     */
    public static function handleCurrentScope(ParametersInterface $parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $controller = tx_rnbase::makeInstance(self::class);

        return $controller->handleScope($parameters, $configurations, $useObjects);
    }

    /**
     * Setzt die aktuellen Userdaten in ein TYPO3-Register.
     * Damit können sie per Typoscript abgefragt und in Links verwendet werden.
     *
     * @param ConfigurationInterface $configurations
     */
    private function setScopeParams(ConfigurationInterface $configurations)
    {
        if (!count($this->scopeParams)) {
            return;
        }

        $params = '';
        $qualifier = $configurations->getQualifier();
        foreach ($this->scopeParams as $key => $value) {
            $params .= '&'.$qualifier.'['.$key.']='.rawurlencode($value);
        }
        TYPO3::getTSFE()->register['T3SPORTS_SCOPEPARAMS'] = $params;
    }

    /**
     * Ensure the plugin-specific parameter container remains available in links.
     *
     * This adds a dummy keepVar for the plugin UID when no scope parameters
     * are present in the current request. It allows AJAX requests using unique
     * parameters to rehydrate the plugin configuration and scope from the
     * original plugin record, rather than requiring the scope values to be
     * passed explicitly.
     */
    private function ensurePluginIdKeepVar(ConfigurationInterface $configurations)
    {
        $configurations->addKeepVar(self::PARAM_PLUGIN, $configurations->getPluginId());
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
    private function handleCurrentSaison(ParametersInterface $parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $saisonUids = $configurations->get('saisonSelection');

        // Soll eine SelectBox für Saison gezeigt werden?
        if ($configurations->get('saisonSelectionInput')) {
            // Die UIDs der Saisons in Objekte umwandeln, um eine Selectbox zu bauen
            // TODO: Es sollten zusätzliche Kriterien zur Ermittlung der Saisons herangezogen werden
            // Einfach alle Saisons zu zeigen führt zu vielen leeren Seiten
            $saisons = $this->saisonRepo->findByUids($saisonUids);
            $dataArr = $this->prepareSelect($saisons, $parameters, 'saison', $useObjects ? '' : 'name');
            $saisonUids = $dataArr[1];
            $viewData->offsetSet('saison_select', $dataArr);
            $configurations->addKeepVar('saison', $saisonUids);
            $this->scopeParams['saison'] = $saisonUids;
        } elseif ($configurations->getBool('leaguetable.tablecfg.ajaxControls')) {
            // Wenn keine SelectBox, aber eine Saison konfiguriert ist, dann diese in die ScopeParams übernehmen
            $saisonUids = $parameters->get('saison');
            if ($saisonUids) {
                $configurations->addKeepVar('saison', $saisonUids);
                $this->scopeParams['saison'] = $saisonUids;
            }
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
    private function handleCurrentCompetitionGroup($parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $groupUids = $configurations->get('groupSelection');

        // Soll eine SelectBox für Altersgruppe gezeigt werden?
        if ($configurations->get('groupSelectionInput')) {
            // Die UIDs der Altersklasse in Objekte umwandeln um eine Selectbox zu bauen
            $groups = Group::findAll($groupUids);
            $dataArr = $this->prepareSelect($groups, $parameters, 'group', $useObjects ? '' : 'name');
            $groupUids = $dataArr[1];
            $viewData->offsetSet('group_select', $dataArr);
            $configurations->addKeepVar('group', $groupUids);
            $this->scopeParams['group'] = $groupUids;
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
    private function handleCurrentTeamGroup($parameters, ConfigurationInterface $configurations, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $groupUids = $configurations->get('scope.teamGroup');

        // Soll eine SelectBox für Altersgruppe gezeigt werden?
        if ($configurations->get('scope.teamGroupSelectionInput')) {
            // Die UIDs der Altersklasse in Objekte umwandeln um eine Selectbox zu bauen
            $groups = Group::findAll($groupUids);
            $dataArr = $this->prepareSelect($groups, $parameters, 'group', $useObjects ? '' : 'name');
            $groupUids = $dataArr[1];
            $viewData->offsetSet('teamgroup_select', $dataArr);
            $configurations->addKeepVar('teamgroup', $groupUids);
            $this->scopeParams['teamgroup'] = $groupUids;
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
    private function handleCurrentClub($parameters, &$configurations, $saisonUids, $groupUids, $compUids, $useObjects = false)
    {
        $viewData = $configurations->getViewData();
        $clubUids = $configurations->get('clubSelection');

        // Soll eine SelectBox für den Verein gezeigt werden?
        // Das machen wir nur, wenn mindestens ein Verein konfiguriert wurde
        if ($configurations->get('clubSelectionInput')) {
            // Die UIDs der Vereine in Objekte umwandeln, um eine Selectbox zu bauen
            $clubs = $this->clubRepo->findAllByScope($clubUids, $saisonUids, $groupUids, $compUids);
            $dataArr = $this->prepareSelect($clubs, $parameters, 'club', $useObjects ? '' : 'name');
            $clubUids = $dataArr[1];
            $viewData->offsetSet('club_select', $dataArr);
            $configurations->addKeepVar('club', $clubUids);
            $this->scopeParams['club'] = $clubUids;
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
    private function handleCurrentCompetition(&$scopeArr, $parameters, $configurations, $saisonUids, $groupUids, $useObjects = false)
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
            $dataArr = $this->prepareSelect($competitions, $parameters, 'competition', $useObjects ? '' : 'name');
            $compUids = $dataArr[1];
            $viewData->offsetSet('competition_select', $dataArr);
            $configurations->addKeepVar('competition', $compUids);
            $this->scopeParams['competition'] = $compUids;
        } elseif ($configurations->getBool('leaguetable.tablecfg.ajaxControls')) {
            // Wenn keine SelectBox, aber eine Saison konfiguriert ist, dann diese in die ScopeParams übernehmen
            $compUids = $parameters->get('competition');
            if ($compUids) {
                $configurations->addKeepVar('competition', $compUids);
                $this->scopeParams['competition'] = $compUids;
            }
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
     * @param string $compUids String die UIDs der aktuell eingestellten Wettbewerbe
     *
     * @return string Die UIDs als String
     */
    private function handleCurrentRound($parameters, $configurations, $saisonUids, $groupUids, $compUids, $clubUids, $useObjects = false)
    {
        $roundUid = '';
        $viewData = $configurations->getViewData();
        // Soll eine SelectBox für Wettkämpfe gezeigt werden?
        if ($configurations->get('roundSelectionInput') && (isset($compUids) && Math::testInt($compUids))) {
            $currCompetition = new Competition($compUids);
            // Die Spielrunden ermitteln
            $rounds = $currCompetition->getRounds();
            $dataArr = $this->prepareRoundSelect($rounds, $parameters, $configurations, 'scope.rounds.', $useObjects ? '' : 'uid');
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
    private function prepareSelect($objects, ParametersInterface $parameters, $parameterName, $displayAttrName = 'name')
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
    private function prepareRoundSelect($rounds, $parameters, $configurations, $confId, $displayAttrName = 'name')
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
