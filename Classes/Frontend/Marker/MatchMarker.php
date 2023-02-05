<?php

namespace System25\T3sports\Frontend\Marker;

use Exception;
use Sys25\RnBase\Frontend\Marker\BaseMarker;
use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Frontend\Marker\ListBuilder;
use Sys25\RnBase\Frontend\Marker\SimpleMarker;
use Sys25\RnBase\Frontend\Marker\Templates;
use Sys25\RnBase\Search\SearchBase;
use Sys25\RnBase\Utility\Misc;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\MatchReportModel as MatchReport;
use System25\T3sports\Model\Stadium;
use System25\T3sports\Utility\MatchTicker;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

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
 * Diese Klasse ist für die Erstellung von Markerarrays für Spiele verantwortlich.
 */
class MatchMarker extends SimpleMarker
{
    private $recursion = 0;

    /** @var TeamMarker */
    private $teamMarker;

    /** @var CompetitionMarker */
    private $competitionMarker;

    /**
     * Erstellt eine neue Instanz.
     *
     * @param $options Array
     *            with options. not used until now.
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        // Den TeamMarker erstellen
        $this->teamMarker = tx_rnbase::makeInstance(TeamMarker::class);
        $this->competitionMarker = tx_rnbase::makeInstance(CompetitionMarker::class);
    }

    /**
     * {@inheritdoc}
     *
     * @param Fixture $match
     *
     * @see SimpleMarker::prepareTemplate()
     */
    protected function prepareTemplate($template, $match, $formatter, $confId, $marker)
    {
        $this->prepareFields($match, $formatter, $confId);
        Misc::callHook('cfc_league_fe', 'matchMarker_initRecord', [
            'match' => $match,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter,
        ], $this);

        // Jetzt die dynamischen Werte setzen, dafür müssen die Ticker vorbereitet werden
        // Der Report wird in der Action gesetzt
        $report = $match->getMatchReport();
        if (is_object($report)) {
            $this->pushTT('addDynamicMarkers');
            $this->addDynamicMarkers($template, $report, $formatter, $confId, $marker);
            $this->pullTT();
        }

        $this->pushTT('parse home team');
        if (self::containsMarker($template, $marker.'_HOME')) {
            $template = $this->teamMarker->parseTemplate($template, $match->getHome(), $formatter, $confId.'home.', $marker.'_HOME');
        }
        $this->pullTT();
        $this->pushTT('parse guest team');
        if (self::containsMarker($template, $marker.'_GUEST')) {
            $template = $this->teamMarker->parseTemplate($template, $match->getGuest(), $formatter, $confId.'guest.', $marker.'_GUEST');
        }
        $this->pushTT('parse arena');
        if (self::containsMarker($template, $marker.'_ARENA_')) {
            $template = $this->_addArena($template, $match, $formatter, $confId.'arena.', $marker.'_ARENA');
        }
        if (self::containsMarker($template, $marker.'_SETRESULTS')) {
            $template = $this->_addSetResults($template, $match, $formatter, $confId.'setresults.', $marker.'_SETRESULT');
        }
        $this->pullTT();

        $template = $this->addTickerLists($template, $match, $formatter, $confId, $marker);

        $this->pushTT('add media');
        $template = $this->_addMedia($match, $formatter, $template, $confId, $marker);
        $this->pullTT();

        // Add competition
        if (self::containsMarker($template, $marker.'_COMPETITION_')) {
            $template = $this->competitionMarker->parseTemplate($template, $match->getCompetition(), $formatter, $confId.'competition.', $marker.'_COMPETITION');
        }

        return $template;
    }

    /**
     * {@inheritdoc}
     *
     * @see SimpleMarker::finishTemplate()
     */
    protected function finishTemplate($template, $match, $formatter, $confId, $marker)
    {
        // Nochmal nach Markern schauen, falls SubTemplates aus dem TS neue Marker verwendet haben
        if (self::containsMarker($template, $marker.'_') && 0 == $this->recursion) {
            // einmalige Rekursion starten
            ++$this->recursion;
            $template = $this->parseTemplate($template, $match, $formatter, $confId, $marker);
        }
        Misc::callHook('cfc_league_fe', 'matchMarker_afterSubst', [
            'match' => $match,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter,
        ], $this);

        return $template;
    }

    /**
     * Integriert die Satzergebnisse.
     *
     * @param string $template
     * @param Fixture $item
     * @param
     *            $formatter
     * @param
     *            $confId
     * @param
     *            $markerPrefix
     */
    protected function _addSetResults($template, $item, $formatter, $confId, $markerPrefix)
    {
        if (0 == strlen(trim($template))) {
            return '';
        }
        $sets = $item->getSets();
        /* @var $listBuilder ListBuilder */
        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);
        $out = $listBuilder->render($sets, false, $template, SimpleMarker::class, $confId, $markerPrefix, $formatter);

        return $out;
    }

    /**
     * Bindet die Arena ein.
     *
     * @param string $template
     * @param Fixture $item
     * @param FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     *
     * @return string
     */
    protected function _addArena($template, $item, FormatUtil $formatter, $confId, $markerPrefix)
    {
        $sub = $item->getArena();
        if (!$sub) {
            // Kein Stadium vorhanden. Leere Instanz anlegen und altname setzen
            $sub = BaseMarker::getEmptyInstance(Stadium::class);
        }
        $sub->setProperty('altname', $item->getProperty('stadium'));
        $marker = tx_rnbase::makeInstance(StadiumMarker::class);
        $template = $marker->parseTemplate($template, $sub, $formatter, $confId, $markerPrefix);

        return $template;
    }

    /**
     * Im folgenden werden einige Personenlisten per TS aufbereitet.
     * Jede dieser Listen
     * ist über einen einzelnen Marker im FE verfügbar. Bei der Ausgabe der Personen
     * werden auch vorhandene MatchNotes berücksichtigt, so daß ein Spieler mit gelber
     * Karte diese z.B. neben seinem Namen angezeigt bekommt.
     *
     * @param Fixture $match
     * @param FormatUtil $formatter
     * @param string $confId
     */
    private function prepareFields($match, $formatter, $confId)
    {
        // Zuerst einen REGISTER-Wert für die Altergruppe setzen. Dieser kann bei der
        // Linkerstellung verwendet werden.
        try {
            // Zuerst die Teams prüfen
            $groupId = $match->getHome()->getAgeGroupUid();
            $groupId = $groupId ? $groupId : $match->getGuest()->getAgeGroupUid();
            if (!$groupId) {
                $competition = $match->getCompetition();
                $group = $competition->getGroup(false);
                $groupId = $group ? $group->getUid() : 0;
            }
            $GLOBALS['TSFE']->register['T3SPORTS_GROUP'] = $groupId;
        } catch (Exception $e) {
            $GLOBALS['TSFE']->register['T3SPORTS_GROUP'] = 0;
        }

        $match->setProperty('pictures', $match->getProperty('dam_images'));
        $match->setProperty('firstpicture', $match->getProperty('dam_images'));

        /* @var $report MatchReport */
        $report = $match->getMatchReport();
        if (!is_object($report)) {
            return;
        }
        // Die Aufstellungen setzen
        $configurations = $formatter->getConfigurations();

        $match->setProperty(
            'lineup_home',
            $this->lookupStaticField($match, $configurations->get('matchreport.lineuphome.staticField')) ?:
                    $report->getLineupHome('matchreport.lineuphome.')
        );

        $match->setProperty(
            'lineup_guest',
            $this->lookupStaticField($match, $configurations->get('matchreport.lineupguest.staticField')) ?:
                $report->getLineupGuest('matchreport.lineupguest.')
        );
        $match->setProperty('substnames_home', $report->getSubstituteNamesHome('matchreport.substnameshome.'));
        $match->setProperty('substnames_guest', $report->getSubstituteNamesGuest('matchreport.substnamesguest.'));
        $match->setProperty('coachnames_home', $report->getCoachNameHome('matchreport.coachnames.'));
        $match->setProperty('coachnames_guest', $report->getCoachNameGuest('matchreport.coachnames.'));
        $match->setProperty('refereenames', $report->getRefereeName('matchreport.refereenames.'));
        $match->setProperty('assistsnames', $report->getAssistNames('matchreport.assistsnames.'));
    }

    /**
     * @param Fixture $match
     * @param string $fieldName
     */
    private function lookupStaticField($match, $fieldName)
    {
        if ($fieldName) {
            return $match->getProperty($fieldName);
        }

        return null;
    }

    /**
     * Add dynamic defined markers for profiles and matchnotes.
     *
     * @param string $template
     * @param Fixture $match
     * @param FormatUtil $formatter
     * @param string $matchConfId
     * @param string $matchMarker
     */
    private function addDynamicMarkers($template, MatchReport $report, FormatUtil $formatter, $matchConfId, $matchMarker)
    {
        $dynaMarkers = $formatter->getConfigurations()->getKeyNames($matchConfId.'dynaMarkers.');

        foreach ($dynaMarkers as $dynaMarker) {
            $report->getMatch()->setProperty($dynaMarker, $report->getTickerList($matchConfId.'dynaMarkers.'.$dynaMarker.'.'));
        }
    }

    /**
     * Add dynamic defined markers for profiles and matchnotes.
     *
     * @param string $template
     * @param Fixture $match
     * @param FormatUtil $formatter
     * @param string $matchConfId
     * @param string $matchMarker
     *
     * @return string
     */
    private function addTickerLists($template, $match, FormatUtil $formatter, $matchConfId, $matchMarker)
    {
        $configurations = $formatter->getConfigurations();
        $dynaMarkers = $configurations->getKeyNames($matchConfId.'tickerLists.');
        /* @var $listBuilder ListBuilder */
        $listBuilder = tx_rnbase::makeInstance(ListBuilder::class);

        for ($i = 0, $size = count($dynaMarkers); $i < $size; ++$i) {
            // Prüfen ob der Marker existiert
            $markerPrefix = $matchMarker.'_'.strtoupper($dynaMarkers[$i]);
            if (!self::containsMarker($template, $markerPrefix)) {
                continue;
            }
            $confId = $matchConfId.'tickerLists.'.$dynaMarkers[$i].'.';
            // Jetzt der DB Zugriff. Wir benötigen aber eigentlich nur die UIDs. Die eigentlichen Objekte
            // stehen schon im report bereit
            $srv = ServiceRegistry::getMatchService();
            $fields = [];
            $fields['MATCHNOTE.GAME'][OP_EQ_INT] = $match->getUid();
            $options = [];
            $options['what'] = 'uid';
            SearchBase::setConfigFields($fields, $configurations, $confId.'filter.fields.');
            SearchBase::setConfigOptions($options, $configurations, $confId.'filter.options.');
            $children = $srv->searchMatchNotes($fields, $options);

            // Die gefundenen Notes werden jetzt durch ihre aufbereiteten Duplikate ersetzt
            $items = [];
            $tickerHash = $this->getTickerHash($match);
            for ($ci = 0, $cnt = count($children); $ci < $cnt; ++$ci) {
                if (array_key_exists($children[$ci]['uid'], $tickerHash)) {
                    $items[] = $tickerHash[$children[$ci]['uid']];
                }
            }
            $template = $listBuilder->render($items, false, $template, MatchNoteMarker::class, $confId, $markerPrefix, $formatter);
        }

        return $template;
    }

    /**
     * Liefert die Ticker als Hash.
     * Key ist die UID des Datensatzes.
     *
     * @param Fixture $match
     *
     * @return array
     */
    protected function getTickerHash(Fixture $match)
    {
        if (!is_array($this->tickerHash)) {
            $this->tickerHash = [];
            $matchTicker = new MatchTicker();
            $tickerArr = $matchTicker->getTicker4Match($match);
            for ($i = 0, $cnt = count($tickerArr); $i < $cnt; ++$i) {
                $this->tickerHash[$tickerArr[$i]->getUid()] = $tickerArr[$i];
            }
        }

        return $this->tickerHash;
    }

    /**
     * Die Anzeige des Spiels kann je nach Status variieren.
     * Daher gibt es dafür verschiedene Template-Subparts.
     * ###RESULT_STATUS_-1###, ###RESULT_STATUS_0###, ###RESULT_STATUS_1###, ###RESULT_STATUS_2### und ###RESULT_STATUS_-10###.
     * Übersetzt bedeutet das "ungültig", "angesetzt", "läuft", "beendet" und "verschoben".
     *
     * @param string $template
     * @param array $markerArray
     * @param array $subpartArray
     * @param array $wrappedSubpartArray
     * @param Fixture $match
     * @param FormatUtil $formatter
     */
    protected function setMatchSubparts($template, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $match, $formatter)
    {
        // Je Spielstatus wird ein anderer Subpart gefüllt
        for ($i = -1; $i < 3; ++$i) {
            $subpartArray['###RESULT_STATUS_'.$i.'###'] = '';
        }
        $subpartArray['###RESULT_STATUS_-10###'] = '';

        $subTemplate = Templates::getSubpart($template, '###RESULT_STATUS_'.$match->getStatus().'###');
        if ($subTemplate) {
            $subpartArray['###RESULT_STATUS_'.$match->getStatus().'###'] = Templates::substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);
        }
    }

    /**
     * Die vorhandenen Mediadateien hinzufügen.
     *
     * @param array $gSubpartArray
     * @param array $firstMarkerArray
     * @param Fixture $match
     * @param FormatUtil $formatter
     * @param string $template
     * @param string $baseConfId
     * @param string $baseMarker
     *
     * @deprecated wird wohl nicht mehr verwendet...
     */
    private function _addMedia($match, $formatter, $template, $baseConfId, $baseMarker)
    {
        // Prüfen, ob Marker vorhanden sind
        if (!self::containsMarker($template, $baseMarker.'_MEDIAS')) {
            return $template;
        }

        $gSubpartArray = $firstMarkerArray = [];

        // Not supported without DAM!
        $gSubpartArray['###'.$baseMarker.'_MEDIAS###'] = '';
        $out = Templates::substituteMarkerArrayCached($template, $firstMarkerArray, $gSubpartArray);

        return $out;
    }

    /**
     * Links vorbereiten
     * TODO: auf Linkerzeugung im SimpleMarker umstellen.
     *
     * @param Fixture $match
     * @param string $marker
     * @param array $markerArray
     * @param array $wrappedSubpartArray
     * @param string $confId
     * @param FormatUtil $formatter
     */
    protected function prepareLinks($match, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, $formatter, $template)
    {
        parent::prepareLinks($match, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

        $linkId = 'report';
        $cObjData = $formatter->getConfigurations()->getCObj()->data;
        $formatter->getConfigurations()->getCObj()->data = $match->getProperty();

        if ($match->hasReport()) {
            $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, [
                'matchId' => $match->getUid(),
            ], $template);
        } else {
            $linkMarker = $marker.'_'.strtoupper($linkId).'LINK';
            $remove = $formatter->getConfigurations()->getInt($confId.'links.'.$linkId.'.removeIfDisabled');
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
        }
        $linkId = 'ticker';
        $force = $formatter->getConfigurations()->getBool($confId.'links.'.$linkId.'.force', true);
        if ($match->isTicker() || $force) {
            $this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, [
                'matchId' => $match->getUid(),
            ], $template);
        } else {
            $linkMarker = $marker.'_'.strtoupper($linkId).'LINK';
            $remove = intval($formatter->getConfigurations()->get($confId.'links.'.$linkId.'.removeIfDisabled'));
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove > 0);
        }
        $formatter->getConfigurations()->getCObj()->data = $cObjData;

        $this->setMatchSubparts($template, $markerArray, $subpartArray, $wrappedSubpartArray, $match, $formatter);
    }
}
