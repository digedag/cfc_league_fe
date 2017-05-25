# Anleitung

Die Extension stellt zwei Plugins mit zahlreichen integrierten Views zur Verfügung. Diese Views 
kann man prinzipiell frei kombinieren. Sie werden in der Reihenfolge ihrer Auswahl angezeigt.

Für jeden View gibt es ein gesondertes HTML-Template. Dieses basiert auf dem Marker-System von TYPO3. 
Wenn man ein eigenes Template erstellen möchte, dann erfolgt die Pfadangabe 
relativ vom TYPO3-Rootverzeichnis.

## View Wettbewerbsauswahl
Dieser View ist sowohl im Plugin Ligaverwaltung als auch Berichte vorhanden. Seine Einstellungen 
erfolgen jeweils im ersten Tab des Flexform. Die Hauptaufgabe dieser Einstellungen bestehen darin 
festzulegen, welche Datensätze genau auf der Seite dargestellt werden sollen.

Am besten läßt sich dies an einem Beispiel verdeutlichen. Angenommen man möchte auf der Seite eine 
Teamdarstellung realisieren. Mit Hilfe der Wettbewerbsauswahl legt man fest, welches Team gezeigt 
werden soll. Diese Angabe erfolgt aber nicht direkt über die Auswahl eines Teamdatensatzes, sondern 
indem man einen Scope angibt. Dieser Scope besteht im Plugin Berichte aus der Saison des Teams und 
der Altersgruppe. Da je Saison und Altersgruppe genau ein Team spielen sollte, kann man das Team so 
genau definieren. Man kann aber auch mehrere Saisondatensätze integrieren und dann im Frontend dem 
Benutzer die Auswahl überlassen. Dadurch hat man dann automatisch eine Teamhistorie.

Im Screenshot kann der Nutzer im Frontend zwischen zwei Spieljahren wählen und bekommt jeweils das 
Team der 2. Mannschaft angezeigt.

Man kann die Auswahlboxen auch leer lassen und nur jeweils die Select-Boxen aktivieren. Das bewirkt, 
daß automatisch alle vorhandenen Datensätze zur Auswahl stehen. 

Die Selectboxen im Frontend erscheinen nur, wenn auch der View Wettbewerbsauswahl mit ausgewählt wurde.
Bei der Verwendung von HTML-Templates werden die Selectboxen durch normale Links ersetzt. Damit können 
die Cachingmechanismen von TYPO3 genutzt werden. 

## View Spielplan

Um den Spielplan anzuzeigen, muß man zunächst im Tab **Allgemein** den Scope festlegen. Wenn man den Spielplan 
für ein bestimmtes Team anzeigen möchte, muß man zusätzlich den entsprechenden Verein auswählen. (Achtung: 
Die Auswahl mehrerer Vereine wurde noch nicht getestet und die Selectbox nicht unterstützt.)

Im Tab **Spielplan** können nun die weiteren Angaben gemacht werden. Ein Link zum Spielbericht wird nur 
dann angelegt, wenn dieser im Spiel vorhanden ist und wenn im Flexform eine Seite für dessen Darstellung 
angegeben wurde.

Wenn der Spielplan nicht für einen bestimmten Verein gezeigt wird, dann wird über den View Wettbewerbsauswahl 
automatisch eine Selectbox zur Auswahl des Spieltages eingefügt.

**Zeitliche Eingrenzung:** Man kann den Spielplan auf Tagesbasis eingrenzen. Ausgangspunkt ist natürlich immer 
das aktuelle Datum. 

Per Typoscript ist es zusätzlich möglich auch die Anzahl der Spiele zu begrenzen. Damit kann beispielsweise 
immer das nächste Spiel eines Teams gezeigt werden.

## View Ligatabelle

Erzeugt die Darstellung einer Ligatabelle. Auch hier muß vorher der gewünschte Scope eingestellt werden. Die 
einzelnen Einstellungen haben folgende Bedeutung:

**Tabellenscope:** Man kann zwischen Saison, Hin- und Rückrunde wählen. Es wird jeweils die passende Tabelle 
berechnet. Optional kann man die Auswahl aber auch dem Nutzer überlassen (Selectbox anzeigen).

**Tabellentyp:** Auswahl zwischen Normal, Heim- und Auswärtstabelle. Auch hier ist eine Selectbox für den FE-Nutzer möglich.

**Selectbox für Punktesystem:** Die Tabelle wird normalerweise entsprechend der Punktezählung im entsprechenden 
Wettbewerb angezeigt. Mit diesem Punkt kann man dem Nutzer in Frontend die Tabelle aber nach Wahl im 2-Punkte 
oder 3-Punkte-Modus anzeigen lassen.

**Livetabelle:** Zeigt die Tabelle inklusive aktuell laufender Spiele.

**Vereine hervorheben:** Hiermit können in der Tabelle die Teams bestimmter Vereine hervorgehoben werden. 

## View Tabellenfahrt

Erzeugt die Daten für eine Tabellenfahrt als JSON-String. Die Daten können dann mit einer passenden JS-Bibliothek 
für Charts gerendert werden. Eine Beispiel-Implementierung auf Basis **Flot** wird mitgeliefert.

## View Statistiken (veraltet)

**WICHTIG:** Für Statistiken sollte die Extension [t3sportstats](https://github.com/digedag/t3sportstats) verwendet werden. Diese bietet 
zusätzliche Möglichkeiten und ist deutlich performanter.

Dieser View wurde mit Version 0.2.0 komplett umgestellt. Alle Statistiken werden jetzt als Service dynamisch integriert, 
so daß die bisherigen Spielerstatistiken nun in vier verschiedene Einzelstatistiken aufgeteilt wurden:

* Spieler (der Arbeitsnachweis der Spieler)
* Torschützenliste
* Beste Vorlagengeber
* Spielerzusammenfassung

Die letzte Statistik ist die kürzeste. Hier wird nur ausgegeben, welche Spiele in der Spielerstatistik Verwendung fanden.
Das Grundprinzip der Statistiken bleibt wie bisher erhalten. Es wird zunächst wieder der Scope ausgewählt, um festzulegen, 
über welche Spiele und ggf. für welche Teams eine Statistik erstellt werden soll.

Die Reihenfolge der Spieler im Arbeitsnachweis kann nicht mehr über das Flexform festgelegt werden. Das geschieht jetzt mit 
folgender TypoScript-Anweisung:

```
# Sortierung der Spieler: 0-alphabetisch, 1- wie im Team
plugin.tx_cfcleaguefe_competition.statistics.player.profileSortOrder = 0
```

**Cache-Timeout:*  Es ist dringend zu empfehlen, im Livebetrieb das Plugin für die Statistik, im Modus **USER** laufen zu lassen. 
Nur so ist der Seitencache von TYPO3 wirksam! Bei Standardinstallation ist das Plugin automatisch als USER in TYPO3 registriert.

## View Spielbericht

Für diesen View ist die Angabe eines Scopes nicht notwendig. Man hat die Möglichkeit einzelne Ticker-Typen im Spielstenogramm 
nicht anzuzeigen. Dies ist dann sinnvoll, wenn diese Informationen nur der Statistik dienen, wie z.B. die Torvorlagen.

## View Teamdarstellung

Nach Eingabe des Scopes mit Saison und Altersgruppe muß man hier zusätzlich noch den gewünschten Verein angeben. Wenn man von 
der Liste der Spieler, Trainer und Betreuer auf die Einzeldarstellung der Personen verlinken möchte, dann müssen noch die 
entsprechenden Seiten festgelegt werden. Für Trainer und Betreuer wird die selbe Seite verwendet.
In diesem View hat man bei der Ausgabe die Möglichkeit auch Teamnotizen anzuzeigen. 

## View Personenprofil

Der Scope ist hier nicht notwendig. Man hat die Möglichkeit ein bestimmtes Profil darzustellen. Üblicherweise wird man diese 
Angabe aber nicht machen und stattdessen des gewünschte Profil per Parameter von einer anderen Seite (Teamview oder Personenliste) 
erhalten.

In jedem Fall muß aber der SysFolder angegeben werden, der die Profile enthält.
Weiterhin besteht die Möglichkeit ein zufällig ausgewählte Person anzeigen zu lassen. Dies ist nützlich, wenn man z.B. einige 
Spieler in einem Teaser anzeigen möchte.

## View Personenliste

Auch hier ist der Scope nicht von Bedeutung, es muß lediglich der gewünschte SysFolder mit den Profilen angegeben werden. Bei 
einer Verlinkung zur Einzeldarstellung muß die entsprechende Seite festgelegt werden (Constant-Editor).

Optional kann die Personenliste mit einem Buchstaben-Menu angezeigt werden. Des weiteren besteht die Möglichkeit eine 
Geburtstagsliste zu erstellen. Es können alle Personen angezeigt, die entweder im aktuellen Monat oder Tag Geburtstag haben.

## FAQ

**Warum werden keine Links für die Teamdarstellung im Spielplan/Ligatabelle erzeugt?**

Damit Links erzeugt werden sind grundsätzlich zwei Bedingungen notwendig. Erstens muss eine Seite konfiguriert werden, auf die 
der Link verweisen soll. Dies geschieht entweder im Flexform des Plugins oder per Typoscript-Konstante über den TYPO3 
Constant-Editor. Zweitens muss das Team auch ein Häkchen bei Link auf Teamseite möglich im Datensatz gesetzt haben. Diese 
Vorbedingungen werden deshalb verwendet, weil man dadurch recht gezielt einstellen kann, wo Links erscheinen sollen.
