<?php

namespace System25\T3sports\Decorator;

use Sys25\RnBase\Frontend\Marker\FormatUtil;
use System25\T3sports\Model\Profile;
use System25\T3sports\Utility\MatchProfileProvider;

class ProfileDecorator
{
    private $matchNoteDecorator;

    public function __construct(MatchNoteDecorator $matchNoteDecorator = null)
    {
        $this->matchNoteDecorator = $matchNoteDecorator ?: new MatchNoteDecorator($this, new MatchProfileProvider());
    }

    /**
     * Gibt das Profile formatiert aus.
     * Dabei werden auch MatchNotes berücksichtigt,
     * die dem Profil zugeordnet sind. Die Person wird im FE daher nur über einen
     * einzelnen Marker ausgegeben.
     *
     * @param FormatUtil $formatter
     * @param string $confId Configuration path
     * @param Profile $profile
     */
    public function wrap(FormatUtil $formatter, $confId, ?Profile $profile)
    {
        if (!is_object($profile)) {
            // Es wurde kein Profil übergeben, also gibt es nicht weiter zu tun
            return $formatter->wrap('', $confId);
        }
        if (intval($profile->getUid()) < 0) {
            // Bei unbekannten Profilen holen wir den Namen aus der Config
            $profile->setProperty('last_name', $formatter->getConfigurations()->getLL('profile_unknownLastname'));
            $profile->setProperty('first_name', $formatter->getConfigurations()->getLL('profile_unknownFirstname'));
        }

        $this->prepareLinks($formatter, $confId, $profile);
        // TODO Das sollte dynamisch gestaltet werden, damit alle Daten der Tabelle verwendet
        // werden können.
        $conf = $formatter->getConfigurations()->get($confId);
        $arr = [];
        // Über alle Felder im record iterieren
        foreach ($profile->getProperty() as $key => $val) {
            if (isset($conf[$key]) || isset($conf[$key.'.'])) {
                $value = $formatter->wrap($profile->getProperty($key), $confId.$key.'.', $profile->getProperty());
                if (strlen($value) > 0) {
                    $weight = $formatter->getConfigurations()->getInt($confId.$key.'.s_weight');
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
            $value = $this->matchNoteDecorator->wrap($formatter, $confId.'ifChangedOut.ticker.', $ticker);
            if (strlen($value) > 0) {
                $weight = $formatter->getConfigurations()->getInt($confId.'ifChangedOut.s_weight');
                $arr[] = [
                    $value,
                    $weight,
                ];
                $value = '';
            }
        }

        $ticker = $profile->isPenalty();
        if (is_object($ticker) && $conf['ifPenalty.']['ticker.']) {
            $value = $this->matchNoteDecorator->wrap($formatter, $confId.'ifPenalty.ticker.', $ticker);
            if (strlen($value) > 0) {
                $weight = $formatter->getConfigurations()->getInt($confId.'ifPenalty.s_weight');
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

        $sep = (strlen($conf['seperator'] ?? '') > 2) ? substr($conf['seperator'], 1, strlen($conf['seperator']) - 2) : ($conf['seperator'] ?? '');
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
     * @param FormatUtil $formatter
     * @param string $confId
     * @param Profile $profile
     */
    protected function prepareLinks(FormatUtil $formatter, $confId, Profile $profile)
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
}
