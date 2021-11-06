<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2016 Rene Nitzsche (rene@system25.de)
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
 * Model für ein Personenprofil.
 */
class tx_cfcleaguefe_models_profile extends tx_rnbase_model_base
{
    public function getTableName()
    {
        return 'tx_cfcleague_profiles';
    }

    public function __construct($rowOrUid)
    {
        if (!is_array($rowOrUid) && intval($rowOrUid) < 0) {
            // Unbekannter Spieler
            $this->uid = $rowOrUid;
            $this->setProperty('uid', $rowOrUid);
        } else {
            parent::__construct($rowOrUid);
        }
    }

    /**
     * Gibt das Profile formatiert aus.
     * Dabei werden auch MatchNotes berücksichtigt,
     * die dem Profil zugeordnet sind. Die Person wird im FE daher nur über einen
     * einzelnen Marker ausgegeben.
     *
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId Configuration path
     * @param tx_cfcleague_models_Profile $profile
     */
    public static function wrap($formatter, $confId, $profile)
    {
        if (!is_object($profile)) {
            // Es wurde kein Profil übergeben, also gibt es nicht weiter zu tun
            return $formatter->wrap('', $confId);
        }
        if (intval($profile->uid) < 0) {
            // Bei unbekannten Profilen holen wir den Namen aus der Config
            $profile->setProperty('last_name', $formatter->configurations->getLL('profile_unknownLastname'));
            $profile->setProperty('first_name', $formatter->configurations->getLL('profile_unknownFirstname'));
        }
        self::prepareLinks($formatter, $confId, $profile);
        // TODO Das sollte dynamisch gestaltet werden, damit alle Daten der Tabelle verwendet
        // werden können.
        $conf = $formatter->getConfigurations()->get($confId);
        $arr = [];
        // Über alle Felder im record iterieren
        foreach ($profile->getProperty() as $key => $val) {
            if ($conf[$key] || $conf[$key.'.']) {
                $value = $formatter->wrap($profile->getProperty($key), $confId.$key.'.', $profile->getProperty());
                if (strlen($value) > 0) {
                    $weight = intval($formatter->getConfigurations()->get($confId.$key.'.s_weight'));
                    $arr[] = [
                        $value,
                        $weight,
                    ];
                    $value = '';
                }
            }
        }

        $ticker = $profile->isChangedOut();
        if (is_object($ticker) && $conf['ifChangedOut.']['ticker.']) {
            $value = tx_cfcleaguefe_models_match_note::wrap($formatter, $confId.'ifChangedOut.ticker.', $ticker);
            if (strlen($value) > 0) {
                $weight = intval($formatter->getConfigurations()->get($confId.'ifChangedOut.s_weight'));
                $arr[] = [
                    $value,
                    $weight,
                ];
                $value = '';
            }
        }

        $ticker = $profile->isPenalty();
        if (is_object($ticker) && $conf['ifPenalty.']['ticker.']) {
            $value = tx_cfcleaguefe_models_match_note::wrap($formatter, $confId.'ifPenalty.ticker.', $ticker);
            if (strlen($value) > 0) {
                $weight = intval($formatter->configurations->get($confId.'ifPenalty.s_weight'));
                $arr[] = [
                    $value,
                    $weight,
                ];
                $value = '';
            }
        }
        if (!count($arr)) { // Wenn das Array leer ist, wird nix gezeigt
            return $formatter->wrap('', $confId, $profile->getProperty());
        }

        // Jetzt die Teile sortieren
        usort($arr, function ($a, $b) {
            if ($a[1] == $b[1]) {
                return 0;
            }

            return ($a[1] < $b[1]) ? -1 : 1;
        });
        // Jetzt die Strings extrahieren
        $ret = [];
        foreach ($arr as $val) {
            $ret[] = $val[0];
        }

        $sep = (strlen($conf['seperator']) > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : $conf['seperator'];
        $ret = implode($sep, $ret);

        // Abschließend nochmal den Ergebnisstring wrappen
        return $formatter->wrap($ret, $confId, $profile->getProperty());
    }

    /**
     * Bereitet Links im Spielbericht vor.
     * Da hier keine Marker verwendet werden, muss für die Verlinkung der
     * normale typolink im TS verwendet werden. Die Zusatz-Parameter müssen hier als String vorbereitet und
     * in ein Register gelegt werden.
     *
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     * @param tx_cfcleaguefe_models_profile $profile
     */
    protected static function prepareLinks($formatter, $confId, $profile)
    {
        $link = $formatter->getConfigurations()->createLink();
        $link->destination($GLOBALS['TSFE']->id);
        $link->parameters([
            'refereeId' => $profile->getUid(),
            'profileId' => $profile->getUid(),
        ]);
        $cfg = $link->_makeConfig('url');
        $GLOBALS['TSFE']->register['T3SPORTS_PARAMS_REFEREE_MATCHES'] = $cfg['additionalParams'];
    }

    /**
     * Liefert den kompletten Namen der Person.
     *
     * @param int $reverse Wenn 1 dann ist die Form <Nachname, Vorname>
     */
    public function getName($reverse = 0)
    {
        if ($reverse) {
            $ret = $this->getProperty('last_name');
            if ($this->getProperty('first_name')) {
                $ret .= ', '.$this->getProperty('first_name');
            }
        } else {
            $ret = $this->getProperty('first_name');
            if ($this->getProperty('first_name')) {
                $ret .= ' ';
            }
            $ret .= $this->getProperty('last_name');
        }

        return $ret;
    }

    /**
     * Fügt diesem Profile eine neue Note hinzu.
     */
    public function addMatchNote(&$note)
    {
        if (!isset($this->_matchNotes)) {
            $this->_matchNotes = [];
        } // Neues TickerArray erstellen
        $this->_matchNotes[] = $note;
        // Wir prüfen direkt auf Teamcaptain
        $this->check4Captain($note);
    }

    protected function check4Captain(&$note)
    {
        if ($note->isType(200)) {
            // Wenn das im Record liegt, kann es auch per TS ausgewertet werden!
            $this->setProperty('teamCaptain', '1');
        }
    }

    /**
     * Returns 1 if player is team captain in a match.
     * Works if match_notes set.
     */
    public function isCaptain()
    {
        return (int) $this->getProperty('teamCaptain');
    }

    /**
     * Returns a match_note if player was changed out during a match.
     * Works if match_notes set.
     */
    public function isChangedOut()
    {
        if (is_array($this->_matchNotes)) {
            for ($i = 0; $i < count($this->_matchNotes); ++$i) {
                $note = $this->_matchNotes[$i];
                if ($note->isType(80)) {
                    return $note;
                }
            }
        }

        return 0;
    }

    /**
     * Returns a match_note if player received a penalty card during a match.
     * Works if match_notes set.
     *
     * @return
     */
    public function isPenalty()
    {
        // Die Matchnotes müssen absteigend durchsucht werden, da die letzte Strafe entscheidend ist
        if (is_array($this->_matchNotes)) {
            $arr = array_reverse($this->_matchNotes);
            for ($i = 0; $i < count($arr); ++$i) {
                $note = $arr[$i];
                if ($note->isPenalty()) {
                    return $note;
                }
            }
        }

        return 0;
    }

    /**
     * Liefert true, wenn für den Spieler eine Einzelansicht verlinkt werden soll.
     *
     * @return bool
     */
    public function hasReport()
    {
        return intval($this->getProperty('link_report')) > 0;
    }

    /**
     * Liefert das Sternzeichen der Person.
     *
     * @return string
     */
    public function getSign()
    {
        $signs = System25\T3sports\Utility\Signs::getInstance();

        return 0 != intval($this->getProperty('birthday')) ? $signs->getSign($this->getProperty('birthday')) : '';
    }
}
