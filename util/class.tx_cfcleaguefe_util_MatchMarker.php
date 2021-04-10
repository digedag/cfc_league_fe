<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2018 Rene Nitzsche (rene@system25.de)
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
tx_rnbase::load('tx_rnbase_util_SimpleMarker');
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('Tx_Rnbase_Utility_T3General');

/**
 * Diese Klasse ist für die Erstellung von Markerarrays für Spiele verantwortlich.
 */
class tx_cfcleaguefe_util_MatchMarker extends tx_rnbase_util_SimpleMarker
{
    private $recursion = 0;

    /** @var tx_cfcleaguefe_util_TeamMarker */
    private $teamMarker;

    /** @var tx_cfcleaguefe_util_CompetitionMarker */
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
        $this->teamMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_TeamMarker');
        $this->competitionMarker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_CompetitionMarker');
    }

    /**
     * {@inheritdoc}
     *
     * @param tx_cfcleaguefe_models_match $match
     *
     * @see tx_rnbase_util_SimpleMarker::prepareTemplate()
     */
    protected function prepareTemplate($template, $match, $formatter, $confId, $marker)
    {
        $this->prepareFields($match, $formatter, $confId);
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'matchMarker_initRecord', [
            'match' => $match,
            'template' => &$template,
            'confid' => $confId,
            'marker' => $marker,
            'formatter' => $formatter,
        ], $this);

        // Jetzt die dynamischen Werte setzen, dafür müssen die Ticker vorbereitet werden
        $this->pushTT('addDynamicMarkers');
        $this->addDynamicMarkers($template, $match, $formatter, $confId, $marker);
        $this->pullTT();

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
     * @see tx_rnbase_util_SimpleMarker::finishTemplate()
     */
    protected function finishTemplate($template, $match, $formatter, $confId, $marker)
    {
        // Nochmal nach Markern schauen, falls SubTemplates aus dem TS neue Marker verwendet haben
        if (self::containsMarker($template, $marker.'_') && 0 == $this->recursion) {
            // einmalige Rekursion starten
            ++$this->recursion;
            $template = $this->parseTemplate($template, $match, $formatter, $confId, $marker);
        }
        tx_rnbase_util_Misc::callHook('cfc_league_fe', 'matchMarker_afterSubst', [
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
     * @param tx_cfcleaguefe_models_match $item
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
        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
        $out = $listBuilder->render($sets, false, $template, 'tx_rnbase_util_SimpleMarker', $confId, $markerPrefix, $formatter, $options);

        return $out;
    }

    /**
     * Bindet die Arena ein.
     *
     * @param string $template
     * @param tx_cfcleaguefe_models_match $item
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     * @param string $markerPrefix
     *
     * @return string
     */
    protected function _addArena($template, $item, $formatter, $confId, $markerPrefix)
    {
        $sub = $item->getArena();
        if (!$sub) {
            // Kein Stadium vorhanden. Leere Instanz anlegen und altname setzen
            $sub = tx_rnbase_util_BaseMarker::getEmptyInstance('tx_cfcleague_models_Stadium');
        }
        $sub->setProperty('altname', $item->getProperty('stadium'));
        $marker = tx_rnbase::makeInstance('tx_cfcleaguefe_util_StadiumMarker');
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
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_rnbase_util_FormatUtil $formatter
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
     * @param tx_cfcleaguefe_models_match $match
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
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $matchConfId
     * @param string $matchMarker
     *
     * @return string
     */
    private function addDynamicMarkers($template, $match, $formatter, $matchConfId, $matchMarker)
    {
        $report = $match->getMatchReport();
        if (!is_object($report)) {
            return $template;
        }

        $dynaMarkers = $formatter->getConfigurations()->getKeyNames($matchConfId.'dynaMarkers.');
        for ($i = 0, $size = count($dynaMarkers); $i < $size; ++$i) {
            $typeArr = $formatter->getConfigurations()->get($matchConfId.'dynaMarkers.'.$dynaMarkers[$i].'.');
            $match->setProperty($dynaMarkers[$i], $report->getTickerList($matchConfId.'dynaMarkers.'.$dynaMarkers[$i].'.'));
        }
    }

    /**
     * Add dynamic defined markers for profiles and matchnotes.
     *
     * @param string $template
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $matchConfId
     * @param string $matchMarker
     *
     * @return string
     */
    private function addTickerLists($template, $match, $formatter, $matchConfId, $matchMarker)
    {
        $configurations = $formatter->getConfigurations();
        $dynaMarkers = $configurations->getKeyNames($matchConfId.'tickerLists.');
        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');

        for ($i = 0, $size = count($dynaMarkers); $i < $size; ++$i) {
            // Prüfen ob der Marker existiert
            $markerPrefix = $matchMarker.'_'.strtoupper($dynaMarkers[$i]);
            if (!self::containsMarker($template, $markerPrefix)) {
                continue;
            }
            $confId = $matchConfId.'tickerLists.'.$dynaMarkers[$i].'.';
            // Jetzt der DB Zugriff. Wir benötigen aber eigentlich nur die UIDs. Die eigentlichen Objekte
            // stehen schon im report bereit
            $srv = tx_cfcleague_util_ServiceRegistry::getMatchService();
            $fields = [];
            $fields['MATCHNOTE.GAME'][OP_EQ_INT] = $match->getUid();
            $options = [];
            $options['what'] = 'uid';
            tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $confId.'filter.fields.');
            tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $confId.'filter.options.');
            $children = $srv->searchMatchNotes($fields, $options);
            // Die gefundenen Notes werden jetzt durch ihre aufbereiteten Duplikate ersetzt
            $items = [];
            $tickerHash = $this->getTickerHash($match);
            for ($ci = 0, $cnt = count($children); $ci < $cnt; ++$ci) {
                if (array_key_exists($children[$ci]['uid'], $tickerHash)) {
                    $items[] = $tickerHash[$children[$ci]['uid']];
                }
            }
            $template = $listBuilder->render($items, false, $template, 'tx_cfcleaguefe_util_MatchNoteMarker', $confId, $markerPrefix, $formatter);
        }

        return $template;
    }

    /**
     * Liefert die Ticker als Hash.
     * Key ist die UID des Datensatzes.
     *
     * @param tx_cfcleague_models_Match $match
     *
     * @return array
     */
    protected function getTickerHash($match)
    {
        if (!is_array($this->tickerHash)) {
            $this->tickerHash = [];
            $tickerArr = &tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
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
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_rnbase_util_FormatUtil $formatter
     */
    protected function setMatchSubparts($template, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $match, $formatter)
    {
        // Je Spielstatus wird ein anderer Subpart gefüllt
        for ($i = -1; $i < 3; ++$i) {
            $subpartArray['###RESULT_STATUS_'.$i.'###'] = '';
        }
        $subpartArray['###RESULT_STATUS_-10###'] = '';

        $subTemplate = tx_rnbase_util_Templates::getSubpart($template, '###RESULT_STATUS_'.$match->getStatus().'###');
        if ($subTemplate) {
            $subpartArray['###RESULT_STATUS_'.$match->getStatus().'###'] = tx_rnbase_util_Templates::substituteMarkerArrayCached($subTemplate, $markerArray, $subpartArray, $wrappedSubpartArray);
        }
    }

    /**
     * Die vorhandenen Mediadateien hinzufügen.
     *
     * @param array $gSubpartArray
     * @param array $firstMarkerArray
     * @param tx_cfcleaguefe_models_match $match
     * @param tx_rnbase_util_FormatUtil $formatter
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

        if (!tx_rnbase_util_Extensions::isLoaded('dam')) {
            // Not supported without DAM!
            $gSubpartArray['###'.$baseMarker.'_MEDIAS###'] = '';
            $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $firstMarkerArray, $gSubpartArray);

            return $out;
        }

        $damMedia = tx_dam_db::getReferencedFiles('tx_cfcleague_games', $match->getUid(), 'dam_media');
        if (0 == count($damMedia['files'])) { // Keine Daten vorhanden
            // Alle Marker löschen
            $gSubpartArray['###'.$baseMarker.'_MEDIAS###'] = '';
            $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $firstMarkerArray, $gSubpartArray);

            return $out;
        }

        // Zuerst wieder das Template laden
        $gPictureTemplate = tx_rnbase_util_Templates::getSubpart($template, '###'.$baseMarker.'_MEDIAS###');

        $pictureTemplate = tx_rnbase_util_Templates::getSubpart($gPictureTemplate, '###'.$baseMarker.'_MEDIA###');
        $markerArray = [];
        $out = '';
        $serviceObj = Tx_Rnbase_Utility_T3General::makeInstanceService('mediaplayer');

        // Alle Daten hinzufügen
        while (list($uid, $filePath) = each($damMedia['files'])) {
            $media = tx_rnbase::makeInstance('tx_dam_media', $filePath);
            $markerArray = $formatter->getItemMarkerArray4DAM($media, $baseConfId.'media.', $baseMarker.'_MEDIA');
            $markerArray['###'.$baseMarker.'_MEDIA_PLAYER###'] = is_object($serviceObj) ? $serviceObj->getPlayer($damMedia['rows'][$uid], $formatter->configurations->get($baseConfId.'media.')) : '<b>No media service available</b>';
            $out .= tx_rnbase_util_Templates::substituteMarkerArrayCached($pictureTemplate, $markerArray);
        }
        // Der String mit den Bilder ersetzt jetzt den Subpart ###MATCH_MEDIAS_2###
        if (strlen(trim($out)) > 0) {
            $subpartArray['###'.$baseMarker.'_MEDIA###'] = $out;
            $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($gPictureTemplate, $firstMarkerArray, $subpartArray); // , $wrappedSubpartArray);
        }
        $gSubpartArray['###'.$baseMarker.'_MEDIAS###'] = $out;
        $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $firstMarkerArray, $gSubpartArray);

        return $out;
    }

    /**
     * Links vorbereiten
     * TODO: auf Linkerzeugung im SimpleMarker umstellen.
     *
     * @param tx_cfcleaguefe_models_match $match
     * @param string $marker
     * @param array $markerArray
     * @param array $wrappedSubpartArray
     * @param string $confId
     * @param tx_rnbase_util_FormatUtil $formatter
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
            $remove = intval($formatter->getConfigurations()->get($confId.'links.'.$linkId.'.removeIfDisabled'));
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
