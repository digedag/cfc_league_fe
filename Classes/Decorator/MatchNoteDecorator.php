<?php

namespace System25\T3sports\Decorator;

use Sys25\RnBase\Frontend\Marker\FormatUtil;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\MatchNote;
use System25\T3sports\Model\Profile;
use System25\T3sports\Utility\MatchProfileProvider;
use tx_rnbase;

class MatchNoteDecorator
{
    private $profileDecorator;
    private $profileProvider;

    public function __construct(?ProfileDecorator $profileDecorator = null, MatchProfileProvider $profileProvider)
    {
        $this->profileDecorator = $profileDecorator ?: new ProfileDecorator($this);
        $this->profileProvider = $profileProvider ?: new MatchProfileProvider();
    }

    /**
     * Formatiert die Ausgabe der Note über TS.
     * Die Note besteht aus insgesamt
     * fünf Teilen:
     * <ul><li>Die Spielminute TS: ticker.minute
     * <li>der Typ TS: ticker.type
     * <li>der Spieler TS: ticker.profile und ticker.profile2
     * <li>der Kommentar TS: ticker.comment
     * <li>der Spielstand zum Zeitpunkt der Note TS: ticker.score
     * </ul>
     * Für jedes Element kann ein "weight" gesetzt werden, womit die Reihenfolge bestimmt wird.
     * Das Element mit dem höchsten Gewicht wird zuletzt dargestellt.
     *
     * @param FormatUtil $formatter
     * @param string $confId
     * @param MatchNote $ticker
     */
    public function wrap(FormatUtil $formatter, $confId, MatchNote $ticker)
    {
        if ('1' == $formatter->getConfigurations()->get($confId.'hide')) {
            // Die Meldung soll nicht gezeigt werden
            return '';
        }

        // Wenn der Ticker für den eigene Vereins ist, einen Eintrag im Register setzen
        $GLOBALS['TSFE']->register['T3SPORTS_NOTE_FAVCLUB'] = $this->isFavClub($ticker); // XXX: Access to image size by TS

        $arr = [];
        $conf = $formatter->getConfigurations()->get($confId.'profile.');
        // Angezeigt wird ein Spieler, sobald etwas im TS steht
        if ($conf && is_object($ticker)) {
            // Bei einem Wechsel ist profile für den ausgewechselten Spieler
            if ($ticker->isChange()) {
                $playerUid = $ticker->getPlayerUidChangeOut();
                $player = $this->getPlayerInstance($ticker, $playerUid);
            } else {
                $player = $this->getPlayerInstance($ticker);
            }
            $value = $this->profileDecorator->wrap($formatter, $confId.'profile.', $player);

            if (strlen($value) > 0) {
                $arr[] = [
                    $value,
                    $weights[] = $conf['s_weight'] ? intval($conf['s_weight']) : 0,
                ];
                $value = '';
            }
        }

        // Bei Spielerwechseln gibt es noch ein zweites Profil
        $conf = $formatter->configurations->get($confId.'profile2.');
        if ($conf && is_object($ticker) && $ticker->isChange()) {
            $playerUid2 = $ticker->getPlayerUidChangeIn();
            $player2 = $this->getPlayerInstance($ticker, $playerUid2);
            if (!is_object($player2)) {
                // Hier liegt vermutlich ein Fehler bei der Dateneingabe vor
                // Es wird ein Hinweistext gezeigt
                $value = 'ERROR!';
            } else {
                $value = $this->profileDecorator->wrap($formatter, $confId.'profile2.', $player2);
            }
            // $value = $player2->wrap($formatter, $conf['profile2.']);
            if (strlen($value) > 0) {
                $arr[] = [
                    $value,
                    $weights[] = $conf['s_weight'] ? intval($conf['s_weight']) : 0,
                ];
                $value = '';
            }
        }
        $cObj = $formatter->getConfigurations()->getCObj(1);
        $cObj->data = $ticker->getProperty();
        foreach ($ticker->getProperty() as $key => $val) {
            $conf = $formatter->getConfigurations()->get($confId.$key.'.');
            if ($conf) {
                $cObj->setCurrentVal($ticker->getProperty($key));
                $value = $cObj->stdWrap($ticker->getProperty($key), $conf);
                if (strlen($value) > 0) {
                    $arr[] = [
                        $value,
                        (int) ($conf['s_weight'] ?? 0),
                    ];
                    $value = '';
                }
            }
        }

        // Jetzt die Teile sortieren
        usort($arr, function ($a, $b) {
            if ($a[1] == $b[1]) {
                return 0;
            }

            return ($a[1] < $b[1]) ? -1 : 1;
        });
        $ret = [];
        // Jetzt die Strings extrahieren
        foreach ($arr as $val) {
            $ret[] = $val[0];
        }

        // Der Seperator sollte mit zwei Pipes eingeschlossen sein
        $sep = $formatter->getConfigurations()->get($confId.'seperator');
        $sep = (strlen($sep) > 2) ? substr($sep, 1, strlen($sep) - 2) : $sep;
        $ret = implode($sep, $ret);

        // Abschließend nochmal den Ergebnisstring wrappen
        return $formatter->wrap($ret, $confId, $ticker->getProperty());
    }

    /**
     * Entscheidet, ob die Note angezeigt werden soll.
     * Dies wird über die Config
     * entschieden. Derzeit wird die Spielminute (noteMinimumMinute) und der
     * Typ der Meldung (noteType und noteIgnoreType) überprüft.
     *
     * @param array $conf
     *
     * @return bool
     */
    public function isVisible(MatchNote $note, array $conf)
    {
        $minMinute = (int) ($conf['noteMinimumMinute'] ?? 0);

        return $minMinute <= $note->getProperty('minute') && $this->isType($note, $conf);
    }

    /**
     * Liefert true, wenn die Meldung dem Typ entspricht
     * Parameter ist entweder die Typnummer oder ein Array mit den Keys
     * noteType und noteTeam.
     * Bei noteType kann eine Liste von Typnummern
     * angegeben werden. NoteTeam ist entweder home oder guest.
     *
     * @param array $typeNumberOrArray
     */
    public function isType(MatchNote $note, $typeNumberOrArray)
    {
        $ret = false;
        if (is_array($typeNumberOrArray)) {
            $typeArr = $typeNumberOrArray;
            // Wenn es ein Array ist, dann zunächst die Typen ermitteln
            // Keine Typen bedeutet, daß alle verwendet werden
            $types = [];
            if (isset($typeArr['noteType'])) {
                $types = Strings::intExplode(',', $typeArr['noteType']);
            }
            $ignoreTypes = [];
            if (isset($typeArr['noteIgnoreType'])) {
                $ignoreTypes = Strings::intExplode(',', $typeArr['noteIgnoreType']);
            }

            // Wenn Typen definiert sind, dann wird ignoreType nicht betrachtet
            if (in_array($note->getType(), $types) || (!count($types) && !count($ignoreTypes)) || (!in_array($note->getType(), $ignoreTypes) && count($ignoreTypes))) {
                // Wird nach Home/Guest unterschieden?
                if (array_key_exists('noteTeam', $typeArr) && strlen($typeArr['noteTeam'])) {
                    // Eigentore beim jeweils anderen Team zeigen
                    if ('HOME' == strtoupper($typeArr['noteTeam']) && ($note->isGoalOwn() ? $note->isGuest() : $note->isHome())) {
                        $ret = true;
                    } elseif ('GUEST' == strtoupper($typeArr['noteTeam']) && ($note->isGoalOwn() ? $note->isHome() : $note->isGuest())) {
                        $ret = true;
                    }
                } else {
                    $ret = true;
                }
            }
        } else {
            $type = $note->getType();
            $ret = ($type == intval($typeNumberOrArray));
        }

        return $ret;
    }

    /**
     * Whether or not the match note is for favorite club.
     *
     * @return int 0/1
     */
    private function isFavClub(MatchNote $note)
    {
        // Zuerst das Team holen
        $team = null;
        $match = $note->getMatch();
        if ($note->isHome()) {
            $team = $match->getHome();
        } elseif ($note->isGuest()) {
            $team = $match->getGuest();
        }
        if (!is_object($team)) {
            return 0;
        }
        $club = $team->getClub();
        if (!is_object($club)) {
            return 0;
        }

        return $club->isFavorite() ? 1 : 0;
    }

    /**
     * Liefert den Spieler dem diese Meldung zugeordnet ist.
     *
     * @return Profile ein Profil oder 0
     */
    public function getPlayerInstance(MatchNote $note, $playerUid = null)
    {
        if ($note->isHome()) {
            return $this->getInstancePlayerHome($note, $playerUid);
        }
        if ($note->isGuest()) {
            return $this->getInstancePlayerGuest($note, $playerUid);
        }

        return null;
    }

    /**
     * Liefert das Profil des an der Aktion beteiligten Spielers der Heimmannschaft.
     * Wenn nicht vorhanden wird der Spieler "Unbekannt" geliefert.
     */
    protected function getInstancePlayerHome(MatchNote $note, $playerUid = null)
    {
        if (null !== $playerUid) {
            return $this->findPlayerInstance($playerUid, $note, MatchProfileProvider::PLAYERS_HOME);
        }
        // Innerhalb der Matchnote gibt es das Konstrukt des unbekannten Spielers. Dieser
        // Wird verwendet, wenn der eigentliche Spieler nicht mehr in der Datenbank gefunden
        // wird, oder wenn die ID des Spielers -1 ist.
        if (0 == intval($note->getProperty('player_home'))) { // ID 0 ist nicht vergeben
            $player = null;
        } elseif (-1 == intval($note->getProperty('player_home'))) {
            $player = $this->getUnknownPlayer();
        } else {
            $player = $this->findPlayerInstance($note->getProperty('player_home'), $note, MatchProfileProvider::PLAYERS_HOME);
        }

        return $player;
    }

    /**
     * Liefert das Profil, des an der Aktion beteiligten Spielers der Gastmannschaft.
     */
    protected function getInstancePlayerGuest(MatchNote $note, $playerUid = null)
    {
        if (null !== $playerUid) {
            return $this->findPlayerInstance($playerUid, $note, MatchProfileProvider::PLAYERS_GUEST);
        }

        if (0 == intval($note->getProperty('player_guest'))) { // ID 0 ist nicht vergeben
            $player = null;
        } elseif (-1 == intval($note->getProperty('player_guest'))) {
            $player = $this->getUnknownPlayer();
        } else {
            $player = $this->findPlayerInstance($note->getProperty('player_guest'), $note, MatchProfileProvider::PLAYERS_GUEST);
        }

        return $player;
    }

    protected function findPlayerInstance(int $playerUid, MatchNote $note, string $methodName)
    {
        $players = $this->profileProvider->getPlayers($note->getMatch(), $methodName, true);
        $player = isset($players[$playerUid]) ? $players[$playerUid] : null;
        if (!is_object($player)) {
            $player = $this->getNotFoundProfile();
        }

        return $player;
    }

    /**
     * Liefert die Singleton-Instanz des unbekannten Spielers.
     * Dieser hat die ID -1 und
     * wird für MatchNotes verwendet, wenn der Spieler nicht bekannt ist.
     */
    public function getUnknownPlayer()
    {
        if (!is_object(self::$unknownPlayer)) {
            self::$unknownPlayer = tx_rnbase::makeInstance(Profile::class, '-1');
        }

        return self::$unknownPlayer;
    }

    private static $unknownPlayer;
    private static $notFoundProfile;

    /**
     * Liefert die Singleton-Instanz eines nicht gefundenen Profils.
     * Dieses hat die ID -2 und
     * wird für MatchNotes verwendet, wenn das Profil nicht mehr in der Datenbank gefunden wurde.
     * FIXME: Vermutlich ist diese Funktionalität in der Matchklasse besser aufgehoben.
     */
    public function getNotFoundProfile()
    {
        if (!is_object(self::$notFoundProfile)) {
            self::$notFoundProfile = tx_rnbase::makeInstance(Profile::class, '-2');
        }

        return self::$notFoundProfile;
    }
}
