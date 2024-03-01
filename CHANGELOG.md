
Changes
-------

v1.11.3 (01.03.2024)
 * Add video assists to fixtures
 * Add option for sort order in match table view
 * Fix broken backend in non-composer-mode

v1.11.2 (09.12.2023)
 * Fix statistics view
 * Fix rendering of empty profile lists

v1.11.1 (23.07.2023)
 * Fix php warning in profile view

v1.11.0 (21.07.2023)
 * #84 Support for TYPO3 12.4 LTS
 * Convert language files to xlf
 * Many, many warnings for PHP 8 fixed

v1.10.0 (05.02.2023)
 * #79 Support for PHP 8

v1.9.0 (27.01.2023)
 * Allow install in TYPO3 11.5
 * Some bugfixes

v1.8.2 (26.06.2022)
 * Fixed dummy teams visible in league table
 * Enable automatic TER release

v1.8.1 (26.06.2022)
 * removed all anient model classes
 * Fix filter classes
 * Fix fatal error in stadium list
 * MatchNoteMarker renders match data again

v1.8.0 (19.06.2022)
 * all classes beside from models are moved to PSR-4 namespace
 * action `tx_cfcleaguefe_actions_LeagueTableShow` removed
 * all html templates moved to `Resources/Private/Templates/Html/`
 * league table alltime migrated to new league table calculation

v1.7.2 (19.05.2021)
 * Fix matchtable shows home matches only

v1.7.1 (13.05.2021)
 * #72 fix ambiguous search classes
 * Fix table aliases for teams in matchtable queries

v1.7.0 (11.04.2021)
 * BREAKING CHANGE: all SearchClasses support new querybuilder API. Update hooks on search classes.

v1.6.1 (25.11.2020)
 * fix TravisCI release stage

v1.6.0 (23.11.2020)
 * #59 Moving table classes to PSR-4
 * #70 allow to configure league table strategy in competition
 * #71 ignore matches of teams out of competitions in table calculation
 * fix some PHP issues
 * TS config `leaguetable.comparatorClass` for comparator replaced by table strategy value in competition

v1.5.2 (14.06.2020)
 * labels in plugin flexforms fixed

v1.5.2 (14.06.2020)
 * labels in plugin flexforms fixed

v1.5.1 (06.06.2020)
 * Support for TYPO3 10.4 LTS added
 * Show selected actions in plugin overview
 * Some small bugfixes

v1.4.1 (04.06.2020)
 * fixed plugin wizard icon
 * unused code removed
 * #52 remove occurancies of cObj::fileResource
 * show stadium details even if no map is possible

v1.4.0 (02.05.2020)
 * Support for TYPO3 9.5 LTS added
 * Support for TYPO3 6.2 LTS dropped
 * fix rendering of assigned team logo
 * fix plugin wizards
 * some refactorings

v1.3.0 (05.10.2019)
 * always prefer static over dynamic lineup in match reports
 * respect GDPR for personal information of profiles
 * new field for date of death for profiles
 * many references to DAM removed
 * many fixes to avoid warnings in PHP 7.x

v1.2.0 (11.07.2018)

 * #14 refactoring of model classes
 * #15 enable match marker in media templates
 * #47 support for TWIG templating added
 * fixes for table charts
 * MatchMarker derives from rn_base SimpleMarker. This offers more features for rendering.
 * fixed some issues for PHP7 support (Thanks to Mario Näther)
 * MatchDetail view send 404 header if match is not found
 * #27 **Breaking change:** support for fixed positions in league table. Behavior of static_position in competition penalty changed.
 * Setting livetable option will convert plugin to **USER_INT**

v1.1.1 (04.01.2017)

 * composer.json added
 * #12 flexform for competition plugin with better selection of clubs, age groups and saisons (Thanks to Mario Näther!) 

v1.1.0 (23.12.2016)

 * Modification for PHP7
 * API changes of rn_base
 * Some bug fixes (Thanks to Mario Näther!)
 * Some refactorings

v1.0.1 (07.05.2016)
 * Prev and next links in profile view if linked from teamview.
 * ScopeSelection: Marker for current item changed to ###SAISON_CURRENT_NAME###
 * TableChart based on JS-Libraries possible. Pre-Build examples for Flot and jqPlot.
 * TableChart: TS-Path for club selection changed from chartClubs to tablechart.chartClubs

v1.0.0 (26.04.2014)
 * Support for TYPO3 6.2
 * #74: New 3 point system for volleyball
 * Changes to apply code conventions

v0.9.1 (01.06.2013)
 * Compat-Version for TYPO3 6.0
 * Dependency to rn_memento removed
 * FAL support added

v0.9.0 (12.01.2012)
 * Bugfix for cal extension
 * Support for match sets in matchtable
 * #8 Support for volleyball in leaguetable
 * #8 League table for icehockey: count wins and looses after penalty and extratime

v0.8.5 (08.12.2012)
 * New filter options for cal extension

v0.8.4 (10.03.2012)
 * #64 League table is empty on start of saison

v0.8.3 (21.12.2011)
 * Pointsystem selection in leaguetable is now dynamic for selected sports

v0.8.2 (21.12.2011) (not released)
 * Match date: show no kickoff time in FE if not set. Thanks to saerdna78 from typo3.org forum!
 * #52: Exception in league table alltime fixed.
 * ROLL-Markers added and configured in TeamView for players
 * ScopeSelection configured for report plugin
 * New Leaguetable: Set H2H-mode by TS: leaguetable.comparatorClass = System25\T3sports\Table\Football\ComparatorH2H
 * Match report: display match notes with listbuilder.
 * Bugfix: Don't cache scope if no cObj UID is available
 * New marker ###MATCH_DCRESULTSUFFIX### to indicate extra time and penalty
 * Leaguetable works for icehockey in 2 and 3 point system
 * NPE in distance calculation fixed if wec_map is not installed
 * Substitution cache disabled for matchlist to save memory

v0.8.1 (17.10.2010)
 * Leaguetable: new head to head mode. That means the direct match decides the position if points are equals. Set this by TS: leaguetable.compareMethod = compareTeamsH2H. Thanks to Christophe Denis!
 * #49: number of matches is used to calculate league table
 * #48: Template for match status -1 included 
 * #47: Result calculation changed

v0.8.0 (21.10.2010)
 * MatchTable: Output markers for profile data in filter items changed
 * Leaguetable in match report: TS config path fixed
 * ProfileView: Example subparts for player and referee statistics with t3sportstats

v0.7.8 (05.10.2010)
 * It is possible to show team notes in profile view. Link is build in team view.
 * Filter class for matchtable
 * Display filter data for players and referees in matchtable
 * #40: New constants for first picture in team and profile details view

v0.7.7 (26.09.2010)
 * Character browser in stadium list
 * Profile list: TS-path for character browser changed to profilelist.profile.charbrowser
 * Profile list: HTML-part for charbrowser must be within subpart ###PROFILES###
 * It is possible to show player stats in ProfileView

v0.7.6 (16.09.2010)
 * Two new markers for profiles: ###PROFILE_DBNAME### and ###PROFILE_DBNAMEREV### with support for stage name
 * #42: remove marker ###MATCH_LEAGUETABLE###
 * Bugfixes for alltime table

v0.7.5 (03.09.2010)
 * All references to extensions lib/div removed.
 * New chart builder class from rn_base used

v0.7.4 (17.07.2010)
 * Show goals own for opponent team
 * Draw map icons with GIFBuilder
 * Distance calculation for clubs and stadiums

v0.7.3 (04.07.2010)
 * Country integration for stadiums and clubs.

v0.7.2 (03.07.2010)
 * It is possible to output all teams of a club.
 * T3-Register T3SPORTS_GROUP in match is filled preferred with teams agegroup
 * Bugfix: club logos without DAM
 * New view stadium list with GoogleMaps integration
 * Stadium details with GoogleMaps integration
 * Team list with GoogleMaps integration
 * MatchReport: New marker ###MATCH_MTCURRENTROUND### to show other matches of current round.

v0.7.1 (18.05.2010)
 * DAM includes removed in team list and profile list

v0.7.0 (17.05.2010)
 * Saison-Selection: Sorting fixed
 * PlayerStatistics: Missing include fixed
 * LeagueTableAllTime: some deprecated calls removed
 * Pictures without DAM: zoom activated

v0.6.5 (11.05.2010) (not released)
 * #23: New marker ###MATCH_LEAGUETABLE### to show a leaguetable within a matchreport
 * All references to extension div removed
 * LeagueTable: support for php view removed
 * Compatibility for TYPO3 4.3 and PHP 5.3 improved
 * New static TS template to use T3sports without DAM
 * Dummy logo fixed in team

v0.6.2 (22.11.2009)
 * #15: League table fixed
 * #14: Limit in matchtable works now
 * #16: Fatal error in match crosstable
 * #17: New relation from stadium to tt_address
 * #18: Fix logos in club list

v0.6.1 (08.11.2009)
 * #11: tx_cfcleaguefe_models_competitions::getGroup() fixed

v0.6.0 (07.11.2009)
 * Bugfix for Task #3
 * View club list: New marker ###CLUBMAP### to show clubs in a GoogleMap. Extension wec_map required!

v0.5.6 (06.11.2009) (not released)
 * Player statistics supports agegroup in team
v0.5.5 (06.11.2009) (not released)
 * New view for club list
 * Markers changed for profile images from ###PROFILE_FIRST_PICTURE_IMGTAG### to ###PROFILE_FIRSTPICTURE###
 * To show all pictures of a profile use ###PROFILE_PICTURES###
 * Markers changed for match pictures. You have to use ###MATCH_PICTURES### and ###MATCH_FIRSTPICTURE### now.
 * Examples for image output with Lightbox 2 and Galleriffic
 * Competitions: It is now possible to add more then one age group to a competition
 * Teams: It is now possible to set an age group for a team

v0.5.4 (18.10.2009) (not released)
 * Some issues with pointsystem control fixed in league table
 * There is a new HTML-Template example in leaguetable.html to show TableScope-Controls as Select-Boxes.
 * Output of profiles in Team-View changed. This is now full configureable. This is how to change sorting:
{{{
teamview.team.player.options.orderby.PROFILE.LAST_NAME = asc
}}}
 ** Notice: the Subpart-Marker for coach-list changed from ###TEAM_COACHES### to ###TEAM_COACHS###
 * Output of competition logo
 * Matchreport: Output of player names with support of stage_name
 * Matchreport: New marker ###MATCH_DAM_IMAGES### for picture output.
 * Scope will be cached in plugin. New register-value T3SPORTS_SCOPEPARAMS with current Scopevalues. Set scope.noCache=1 to disable this caching.
 * Rendering of team logos changed. Have a look at lib.t3sports.teamLogo
 * Bug 2840858: Agegroup was wrong
 * Some performance improvements

v0.5.3 (06.12.2008)
 * Due to an error the modulmarker ###MARKERMODULE__MATCHHISTORY### was switched back to a simple marker. The subpart is now outside of ###MATCHREPORT###. Have a look at matchreport.html
 * New link ###PROFILE_REFEREEMATCHESLINK### for profile.
 * With new config matchtable.fixedOpponentClub it is possible to matches of clubs against each other
 * PageBrowser added to matchtable. Default pagesize is 50 entries.

v0.5.2 (28.11.2008)
 * Modulmarker ###MARKERMODULE__MATCHHISTORY### changed to use subparts. HTML template changed.
 * Matchtable-View can be used to search for matches of a referee
 * In MatchReport there is a new register T3SPORTS_PARAMS_REFEREE_MATCHES. It contains necessary url params to link to a matchtable view with all matches of the referee.

v0.5.1 (25.11.2008) (not released)
 * Bugfix for t3sportsbet: Methods getGoalsHome() and getGoalsGuest() fixed for extratime and penalty
 * Wizard for html template in flexforms
 * Bug 2220269: Avoid warnings in player statistics
 * New Modul-Marker ###MARKERMODULE__MATCHHISTORY### for matchreport to show other matches of opponents. Have a look at matchreport.html.

v0.5.0 (12.09.2008)
 * Wizicon for plugins
 * Bugfix: League table for competitions with dummy teams failed. This is fixed and also a new unit test was written.
 * Bugfix: Set match table parameters from TS in Calendar Base integration

v0.4.11 (24.08.2008)
 * Support for new fields in club record

v0.4.10 (16.08.2008)
 * Debug output in matchtable removed
 * Link to team now with option removeIfDisabled

v0.4.9 (15.08.2008)
 * Cal integration uses now sv1_Matches. It is possible to debug sql statement with view.cfc_league_events.debug = 1 in cal plugin.
 * Creation of league table changed. It is now possible to sync league table with selected round.

v0.4.8 (07.08.2008)
 * News typoscript fields in flexform
 * Some modifications for cal integration

v0.4.7 (31.07.2008)
 * It is possible to output the agegroup for a match again: ###MATCH_COMPETITION_GROUP_NAME###
 * The register T3SPORTS_GROUP is available in matchtable. So you can configure the target page for matchreport by agegroup with typoscript.

v0.4.6 (11.07.2008)
 * Statistics: avoid php errors if profiles were deleted from database.
 * getTeams() failed with some mysql versions
 * Show addinfo in matchtable template

v0.4.5 (06.07.2008)
 * Support for plain text lineups, substitutes and scorers

v0.4.4 (30.06.2008)
 * Bugfix: leaguetable used goals from second matchpart only
 * Bugs 2000153 and 2000137
 * Support for team notes implemented
 * tt_news: register:T3SPORTS_GROUP with current age group is available for matchreport link

v0.4.3 (16.06.2008)
 * Bugfix: Logos set in team record were ignored in matchtable

v0.4.2 (16.06.2008)
 * key fixed for league table template in flexform

v0.4.1 (13.06.2008)
 * small changes in TS-Config
 * missing include in search_match fixed
 * Sort order of match notes changed (is now always as same as in backend)

v0.4.0 (08.06.2008)
 * Logo size for teamSmall fixed

v0.3.8 (07.06.2008) (not released)
 * Some small fixed for new release
 * Documentation updated

v0.3.7 (06.06.2008) (not released)
 * TS-Options for profile view changed. Reset your defined storage folder in plugins.
 * Randomizes profiles are possible with profile view.
 * Birthday list is ordered now by month-day
 * Bugfix: Sorting of players with german umlauts was wrong in statistics

v0.3.6 (31.05.2008) (not released)
 * Redesign of match ticker. Better support of TS-References.
 * New TS-Register register:T3SPORTS_NOTE_FAVCLUB for match notes. It can be used to make special outputs for favorite team. Example for goals is included.
 * New link option removeIfDisabled for match and ticker link
 * Configuration of charts was changed. You have to set colors in flexform again. TS key changed from plugin.tx_cfcleaguefe_competition.chartColors to plugin.tx_cfcleaguefe_competition.chart.defaults.colors
 * Chart in matchreport is now really configureable. See plugin.tx_cfcleaguefe_report.matchreport.svChartMatch
 * Some constants for chart size and colors

v0.3.5 (25.05.2008) (not released)
 * A complete refactoring of util_LeagueTable that makes it much more flexible.
 * New markers for leaguetable template:
  * ###ROW_OLDPOSITION### - position on previous round
  * ###ROW_POSITIONCHANGE### - returns either UP, DOWN or EQ. Useable for CSS classes.
 * HTML-Markers for match media changed to common structure: Subpart ###MATCH_MEDIAS_2### is now ###MATCH_MEDIA###. ###MATCH_MEDIA### is now ###MATCH_MEDIA_PLAYER###
 * HTML-Markers for match pictures changed to common structure: Subpart ###MATCH_PICTURES_2### is now ###MATCH_PICTURE###. ###MATCH_PICTURE### is now ###MATCH_PICTURE_IMGTAG###. ###MATCH_FIRST_PICTURE### is now ###MATCH_FIRST_PICTURE_IMGTAG###
 * New view for a special all-time-leaguetable. It creates a table from all found matches in scope. So you can join competitions.
 * New subpart-marker ###ROWS### necessary for leaguetable output


v0.3.5 (22.05.2008) (not released)
 * Profilelist with new option to show a list of profiles with birthday in current day or month

v0.3.4 (17.05.2008) (not released)
 * Some internal changes for better integration of other extensions

v0.3.3 (07.05.2008) (not released)
 * Integration into tt_news. You can set links to T3sports views from within news records. For instance you can set a link to a match report.

v0.3.2 (05.05.2008) (not released)
 * Improved performance in matchmarker and teammarker.
 * Performance trackings included

v0.3.1 (04.05.2008) (not released)
 * TS for match date fixed
 * Many new TS contants for HTML templates
 * Some keys in flexform changed. So most probably your page plugins will fail after update. Simply resave your plugins.
 * Match cross table supports more then match against one opponent. HTML-Template changed!
 * League chart can now be displayed in match report. First working marker service!

v0.3.0 (02.05.2008) (not released)
Änderungen bei der Verlinkung von Informationen
 * Neue TS-Config für Links in Anlehnung an die typolink-Konfigration
Unterhalb einer Datensatz-Konfiguration gibt es den neuen reservierten Typ links. Diesem folgt der Name des Links und die Zielseite. Beispiel:
{{{
lib.t3sports.team {
  links {
    showteam.pid = 123
    showmatchtable.pid = 321
  }
}
}}}
Aus dieser Konfiguration ergeben sich für ein Team zwei Link-Marker:
###TEAM_SHOWTEAMLINK### und ###TEAM_SHOWMATCHTABLELINK###
Dieses Schema ist (noch) nicht voll dynamisch. Es können also noch keine eigenen Links erstellt werden.
 * View Teamlist: Neuer Subpartmarker ###TEAMS###
 * View Matchtable: der Subpart ###MATCH_FREE### steht jetzt innerhalb des Subparts ###MATCH###
 * Statistiken werden im Flexform dynamisch ermittelt. Es stehen alle Services mit dem Key cfcleague_statistics zur Auswahl.
 * Link-Marker für Teamansicht hat sich geändert. Alt: ###TEAM_LINK### Neu: ###TEAM_SHOWTEAMLINK###.
 * Neue Linkmarker ###TEAM_SHOWMATCHTABLELINK### mit dem man direkt auf einen Teamspielplan verlinken kann.
 * Link-Marker für Profilansicht hat sich geändert. Alt: ###TEAM_PLAYER_LINK### Neu: ###TEAM_PLAYER_SHOWPROFILELINK###. Analog bei Trainern und Betreuern.
 * Link-Marker für Spielbericht hat sich geändert. Alt: ###MATCH_LINK### Neu: ###MATCH_REPORTLINK###.
 * Personenliste überarbeitet. CharBrowser ist jetzt optional. Einige Marker und TS-Optionen haben sich geändert.
 * Neue Optionen bei der Wettbewerbsauswahl im Scope
 * Wettbewerbe im Scope werden wie im Backend sortiert
 * Es neue Marker per Service eingebunden werden. Genauere Beschreibung später in der Dokumentation
 * Neue Sprach-Marker können dynamisch angelegt werden. ###LABEL_TEST### entspricht dem Sprachwert plugin.tx_cfcleaguefe_competition.default.label_test

v0.2.3 (29.01.2008)
 * Neuer View: Kreuztabelle
 * Bug 1864071 - Gelbe Karten nur zählen, wenn nicht Gelbrot erhalten
 * Integration tt_address für Vereine
 * Bug 1864066: Rote Karte wird auch für Auswechselspieler in Statistik gewertet
 * Für die Tabellenfahrt wird ein Font mitgeliefert
 * Bug 1880237: Chart zeigt falschen Verlauf
 * Liste der Liveticker um weitere TS-Config erweitert (limit, orderby und timeRange)
 * Neuer View: Teamliste
 * Anpassungen für Cal Version 0.16
 * Templatevariable des ScopeView von competitionSelectionTemplate auf scopeTemplate geändert. Dadurch funktioniert jetzt die Zuordnung im Flexform.
 * Der Spielplan akzeptiert jetzt auch UIDs von Teams aus dem Request. Dies kann man mit der Einstellung matchtable.acceptTeamIdFromRequest wieder deaktivieren.

v0.2.2 (12.11.2007)
 * Bugfix: Bei Wettbewerbsstrafen wurden Niederlagen nicht korrigiert
 * Nicht verwendete Kontrollelemente der Ligatabelle werden jetzt automatisch ausgeblendet. Vorher waren immer noch die Marker sichtbar.
 * Bugfix: alle unnötigen Leerzeichen außerhalb von <?php ?> entfernt
 * Bugfix: In der PlayerSummary-Statistik konnte es zu DB-Fehlern kommen, wenn keine Teams gefunden wurden.
 * Bugfix: Bei den Auswechslungen wurden die Ticker in einem Fall nicht zusammengefasst.
 * Bugfix: CalendarService liefert bei der Suche jetzt keine Warnungen mehr. Aber auch noch keine Ergebnisse!
 * Spielbericht jetzt auf Basis von HTML-Template
 * Bei den Ticker- und Profile-Wraps wird ist jetzt das Datenarray gesetzt und kann verwendet werden. Damit ergeben sich erheblich mehr Möglichkeiten bei der TS-Konfiguration.
 * Bugfix: im Profile-Wrap wurde der Vorname nicht ausgeben.
 * Bugfix ID-0001: Ligatabelle wurde im 2-Punktemodus falsch berechnet
 * Zuschauer-Statistik: Sortierung nach Durchschnittswerten ist jetzt möglich
 * Scopeauswahl: Es kann jetzt auch nach Vereinen eingeschränkt werden.
 * Spielbericht: Ein eingewechselter Spieler, der wieder ausgewechselt wird, wird nun korrekt dargestellt.
 * Ein erster Unittest wurde erstellt. Hier gibt es aber noch viel Arbeit...

Die Veränderungen im Spielbericht sind diesmal recht massiv. Vor allem weil sich auch viele
Einstellungen im TS-Setup geändert haben. Diese Umstellungen habe ich durchgeführt, um bei der
Erstellung des Layouts maximale Freiheiten zu gewähren. Ein Blick in das TS-Setup der Extension 
ist bei Anpassungswünschen sehr zu empfehlen. Das sind auch einige zusätzliche Hinweise drin.

 * Version rn_base 0.0.8 notwendig

v0.2.1 (29.09.2007)
 * Bugfix: Parameterübergabe im Scope funktionierte nicht mehr
 * Bessere Formatierung der Control-Elemente der Ligatabelle und des Scopes möglich. Hier sind jetzt Wraps um die erzeugten Links möglich, wobei zwischen dem aktiven und den nicht aktiven Elementen unterschieden werden kann. Außerdem gibt es einen neuen Marker der nur die URL ausgibt.
 * Spieltagsanzeige erfolgt nicht mehr automatisch, sondern über Einstellung im Flexform.
 * Neu: Zuschauer-Statistik
 * Performance bei Spielerstatistik um Faktor 3 verbessert

v0.2.0 (27.09.2007)
Man beachte den Sprung auf Version 0.2.0. Grund ist eine größere Umstellung der Statistiken.

Die Statistikfunktion wurde komplett überarbeitet und als Service gestaltet. Somit sollte es 
recht einfach möglich sein, weitere Statistiken, auch aus anderen Extensions, hinzuzufügen. 
Nachteil: Die bisherigen Plugineinstellungen funktionieren nicht mehr. (Daher der Versionssprung)
Nach der Installation ist es empfehlenswert, das alte Plugin zur Anzeige der Statistik zu löschen
und ein neues Plugin anzulegen.
Die Statistiken verwenden jetzt alle HTML-Templates. Die Einstellung zur Sortierung der Spieler
ist aus dem Flexform ins TS-Setup gewandert:

  # Sortierung der Spieler: 0-alphabetisch, 1-wie im Team
  plugin.tx_cfcleaguefe_competition.statistics.player.profileSortOrder = 0

  Die Cache-Funktionalität der Statistikergebnisse wurde vorerst deaktivert. Daher ist es
  dringend zu empfehlen im Livebetrieb das Plugin für die Statistik als USER laufen zu
  lassen. (Bei der Standardinstallation ist dies der Fall.) 

 * Bei Berechnung der Tabelle werden die erweiterten Korrekturmöglichkeiten der Wettbewerbsstrafen berücksichtigt.
 * Spielfreie Mannschaften werden im Spielplan und in der Ligatabelle korrekt behandelt. Im Spielplan ist man in der Art der Darstellung recht frei. Hierzu bitte einen Blick ins HTML-Template und in die setup.txt der Extension werfen.
 * Die Dokumentation wurde aktualisiert

v0.1.8 (18.09.2007)
 * Bugfix Teaser Ligatabelle: Der Tabellenstand sollte jetzt immer stimmen.
 * Für die Darstellung der Spiele wurde eine eigene Markerklasse erstellt. Diese wird im Spielplan und in der Liste der Liveticker verwendet. Dadurch ist jetzt in beiden Views die volle Funktionalität verfügbar.
 * Für die Liste der Liveticker kann das Template jetzt im Flexform des Plugins gesetzt werden
 * In der Liste der Liveticker hat sich der Marker für den Link auf die Liveticker-Seite geändert: ###MATCH_TICKER_LINK###
 * Bugfix: der rowroll des Spielplans verwendete bisher die TS-Config der Ligatabelle
 * Änderung im Spielplan. Bei zeitlicher Eingrenzung wird jetzt für die nicht gesetzte "Gegenrichtung" immer das aktuelle Datum verwendet. Bei der Anweisung 10 Tage im voraus lautet, dann bedeutet das 10 Tage inklusive dem aktuellen Tag. Bisher wurde in diesem Fall die Vergangenheit nicht begrenzt.
 * Bugfix Spielerstatistik: Die Sortierung der Assists-Tabelle stimmt jetzt
 * In der Ligatabelle ist jetzt die volle Funktionalität verfügbar. Es können also auch Logos angezeigt werden. Allerdings haben sich die Marker nochmal geändert: aus ###ROW_TEAM_NAME### wird jetzt ###ROW_NAME###. Das "TEAM_" fällt also weg. Der Marker des Logos lautet entsprechend ###ROW_LOGO###.

v0.1.7 (08.09.2007)
 * Beim Spielplan kann die Anzahl Spiele per TS begrenzt werden. Beispiel für Teaser:
{{{
temp.nextMatch < plugin.tx_cfcleaguefe_competition
temp.nextMatch {
  action = tx_cfcleaguefe_actions_MatchTable
  # Hier UID der Saison angeben
  saisonSelection = 4
  # UID der Altersklasse
  groupSelection = 1
  # UID des Vereins für Teamspielplan
  clubSelection = 1

  matchTableTemplate = fileadmin/nextmatch_teaser.html
  # Spiele der nächsten 20 Tage einbeziehen
  matchTableTimeRangeFuture = 20
  # inklusive der gestrigen Spiele
  matchTableTimeRangePast = 1
  matchtable {
    # Nur ein Spiel anzeigen
    limit = 1
    # nur angesetzte und laufende Spiele anzeigen
    status = 0,1
  }
}
}}}
 * Änderung der Marker und TS-Config im TeamView!
  Bei allen Markern ist jetzt ein TEAM_ vorangestellt.
  Vorher: ###PLAYERS### Neu: ###TEAM_PLAYERS###
  Gleiches Prinzip in der TS-Config
  Vorher: plugin.tx_cfcleaguefe_report.teamview.player
  Neu: plugin.tx_cfcleaguefe_report.teamview.team.player

  Vorteil dieser Änderung ist der einheitliche Zugriff in allen Views

 * Im Spielplan ist der Zugriff auf alle Daten der Teams möglich also auch Logo, Bilder, Spieler usw. (Wer's brauch... ;)
 * Bugfix: Bei Eigentoren wird der Spielstand im Stenogramm jetzt aktualisiert
 * Die Eigentore werden jetzt extra in der Spielerstatistik gezählt
 * In der Spielerstatistik kann man jetzt einer Extra-Tabelle die besten Vorlagengeber anzeigen
 * Die Plugins wurden von USER_INT auf USER umgestellt. Dadurch werden die Ausgaben von TYPO3 im Cache abgelegt. Wer noch Selectboxen verwendet, muss das wieder umstellen.
 * NEU: Die Spiele können jetzt als Termine in die Kalender der Extension cal aufgenommen werden.
  Dafür muss das Static-Template "League Management cal-events (cfc_league_fe)" in die TS-Config 
  eingebunden werden. Dieses unbedingt NACH dem normalen Static-Template von cfc_league_fe, da es
  sich auf deren Angaben bezieht.
  Im Kalender-Plugin muss die Seite mit den Spielen zusätzlich als Quelle für Events mit angegeben werden.
  Die Gestaltung des Layout geschieht über HTML-Templates. Eine rudimentäre Vorlage liegt 
  unter views/templates/match_event.html.
  Damit nicht alle Spiele gezeigt werden, kann wie üblich die Auswahl vorher eingeschränkt werden.
  In diesem Fall aber nur per TS:
{{{
  plugin.tx_cal_controller {
    view.cfc_league_events {
      # Eigenes Template einbinden
      template = fileadmin/match_events.html
      # Es kann auf die gleichen Werte wie in den normalen Views eingegrenzt werden
      # Andernfalls werden alle Spiele in den Kalender eingetragen!
#      saisonSelection = 4
      groupSelection = 1
      clubSelection = 1
#      competitionSelection = 1
    }
  }
}}}
  In diesem Beispiel wird auf die Altersklasse mit der UID 1 und den Verein mit der UID 1 eingeschränkt.
  Mehrere Angaben werden durch Komma getrennt. Also z.B. groupSelection = 1,3
  Außerdem wird ein eigenes Template integriert. Zur Formatierung der einzelnen Werte bitte in 
  das Static-Template schauen: ext:cfc_league_fe/static/cal/setup.txt
  

  Ich kenne mich mit cal leider noch nicht so gut aus. Da kann in Zukunft sicher noch einiges 
  verbessert werden, aber es funktioniert schon mal ganz gut. Sogar die Team-Logos können im Kalender 
  gezeigt werden! :-)
  

v0.1.6 (28.08.2007)
 * fehlenden include für tx_cfcleaguefe_models_club in ScopeController ergänzt

v0.1.5 (28.08.2007)
 * In der Scopeauswahl kann man mit TS einen String zwischen den einzelnen Links setzen
 * Ligatabelle und Spielplan auf HTML-Views umgestellt. 
Die PHP-Views können aber per TS wieder aktiviert werden:
  plugin.tx_cfcleaguefe_competition.leaguetable.viewType = PHP
bzw.
  plugin.tx_cfcleaguefe_competition.matchtable.viewType = PHP
 * Der Teamview kann jetzt auch per Parameter initialisiert werden. Damit sind Links auf Teams von anderen Views möglich (Spielplan oder Ligatabelle). Um nicht auf alle Teams zu verlinken, gibt es im Teamdatensatz das neue Feld "Link auf Teamseite möglich".
  Im Spielplan und der Ligatabelle muss die PID der Zielseite per TS definiert werden:
  plugin.tx_cfcleaguefe_competition.matchtable.teamPage = 123
  plugin.tx_cfcleaguefe_competition.leaguetable.teamPage = 123
  Alternativ existiert auch eine Konstante dafür, die über den Constant-Editor gesetzt werden kann.


v0.1.4 (23.08.2007)
 * Im Spielstenogramm werden jetzt auch Nachspielminuten angezeigt
 * Zugeordnete Team-Logos werden nun auch im Spielbericht verwendet
 * Bilder auf Klick vergrößern ist jetzt für so ziemlich alle Bilder verfügbar (siehe TS-Setup)
 * Die Scopeauswahl ist auf HTML-Template umgestellt. Alternativ kann aber immer noch das alte PHP-Template verwendet werden. Umstellung mit folgender TS-Anweisung:
{{{
  plugin.tx_cfcleaguefe_competition.scopeSelection.viewType = PHP
bzw.
  plugin.tx_cfcleaguefe_report.scopeSelection.viewType = PHP
}}}

v0.1.3 (15.8.2007)
 * einige Bugfixes in util_statistics
 * Prüfung auf Variable $mark in template/leaguetable.php um NPE zu vermeiden
 * In der Liste der Liveticker kann jetzt die Seite mit dem Ticker konfiguriert werden
 * Im Flexform des Spielbericht-View kann jetzt optional auch die UID eines Spiels direkt angegeben werden.

v0.1.2 (9.8.2007)
 * Auswahl Person im Flexform für Profileview korrigiert
 * Marker und TS für Bilder im Teamview geändert
 * Im Flexform des Teamviews kann jetzt ein Team auch direkt ausgewählt werden

Vielen Dank an Daniel Mross für die gemeldeten Fehler!

v0.1.1 (8.8.2007)
 * Max Anzahl Zeichen für Pfadangaben der Templates hochgesetzt
 * NPE im Teamview bei fehlendem Spieler entfernt (alternativ wird neuer Language-Marker angezeigt)
 * Im View der Liga-Tabelle kann jetzt auch ein Template angegeben werden
 * Umstellung der Profil-Anzeige
  * Templates für Profile sind jetzt einheitlich im Teamview, ProfileList und ProfileView
  * Marker für FIRST_PICTURE geändert in PROFILE_FIRST_PICTURE
  * TS für Bilder der Personen geändert (sind jetzt "profile" zugeordnet)
{{{
plugin.tx_cfcleaguefe_report {
  profilelist {
    profile < temp.profile
    profile.firstImage {
      file.maxW = 40
      file.maxH = 40
      wrap = <br />|
    }
  }
  profileview {
    profile < temp.profile
    profile.firstImage {
      file.maxW = 150
      file.maxH = 200
    }
  }
}
}}}
 * Umstellungen im Matchview
  * Es lassen sich mehr Werte per Typoscript formatieren
  * TS für Spieldatum und Spielbericht wurde matchreport.match zugeordnet
  * Neue Methoden für die formatierte Ausgabe im Template vorhanden
