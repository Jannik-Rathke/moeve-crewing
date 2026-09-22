# Möwe Crewing

Möwe Crewing ist eine CiviCRM-Erweiterung für die Besatzungsplanung der
**Thor Heyerdahl**. Sie verbindet den Crewbedarf von Veranstaltungen und
Törns mit Bewerbungen, Crew-Zuweisungen und konfigurierbaren
Teilnahmestatus.

## Status

Die Version `0.2.0-alpha.1` ist eine Alpha-Version für die Erprobung in einer
Testumgebung. Vor einer produktiven Aktualisierung müssen Dateien und
Datenbank gesichert und die neue Version in einer Kopie der Produktivumgebung
getestet werden.

Die Erweiterung ist noch nicht im offiziellen CiviCRM-Erweiterungsverzeichnis
veröffentlicht. Installation und Aktualisierung erfolgen in dieser Phase
manuell.

## Funktionsumfang

- Cockpit mit kommenden Törns, offenem Crewbedarf und neuen Bewerbungen
- Törnplanung mit Soll-Besetzung, bestätigter Crew und offenen Funktionen
- Bewerbungsübersicht mit Filterung nach Jahr, Törn, Person und Status
- einzelne und gebündelte Zuweisung von Crewfunktionen
- gebündelte Änderung von Teilnahmestatus für zugewiesene oder abgesagte
  Bewerbungen
- Jahresübersicht nach Bordfunktion und Person
- farbige Darstellung konfigurierbarer Teilnahmestatus
- Zuordnung vorhandener CiviCRM-Felder, Rollen und Statuswerte
- empfohlene, wiederholbar ausführbare Ersteinrichtung
- getrennte Berechtigungen für Anzeige und Bearbeitung

## Datenbasis

Möwe Crewing verwendet die bestehenden CiviCRM-Entitäten für Veranstaltungen
und Teilnahmen. Crewfunktionen werden über Teilnehmerrollen abgebildet.
Gewünschte Funktionen, Besetzungsbedarf und Veranstaltungsinformationen werden
über zugeordnete benutzerdefinierte Felder ausgewertet.

Vorhandene Strukturen können in der Einrichtungsseite zugeordnet werden. Die
Erweiterung muss deshalb keine bereits gepflegten Rollen, Statuswerte oder
Felder ersetzen.

## Voraussetzungen und getestete Umgebung

- CiviCRM `6.17.x` mit aktivierter Komponente CiviEvent
- Smarty 5
- getestet mit Drupal `10.6.x`
- getestet mit PHP `8.3`

Das Manifest deklariert PHP 8.0 bis 8.4. In der Alpha-Phase ist jedoch nur die
oben genannte Kombination praktisch getestet.

## Installation

Das Repository muss als Verzeichnis `moeve_crewing` in einem von CiviCRM
erkannten Erweiterungsverzeichnis liegen. Anschließend kann die Erweiterung
über die CiviCRM-Erweiterungsverwaltung oder mit `cv` aktiviert werden:

```bash
cv ext:enable moeve_crewing
cv upgrade:db --mode=ext --no-interaction
cv flush
```

Danach unter **Administration → Möwe Crewing einrichten** die vorhandenen
Feldgruppen, Felder, Teilnehmerrollen und Teilnahmestatus zuordnen. Die
empfohlene Konfiguration ergänzt fehlende Bestandteile, ohne kompatible
vorhandene Einträge zu ersetzen.

## Berechtigungen

Die Erweiterung registriert zwei eigene CiviCRM-Berechtigungen:

- `access Moeve Crewing`: Cockpit, Törnplanung, Bewerbungen und
  Jahresübersicht ansehen
- `manage Moeve Crewing`: Crewfunktionen zuweisen und Teilnahmestatus ändern;
  schließt die Anzeigeberechtigung ein

Die Einrichtungsseite erfordert weiterhin `administer CiviCRM`. Nach der
Installation müssen die beiden Crewing-Berechtigungen gezielt den vorgesehenen
CMS-Rollen erteilt werden. Normale Vereinsmitglieder erhalten standardmäßig
keinen Zugriff.

## Aktualisierung

Vor jeder Aktualisierung Dateien und Datenbank sichern. Danach den neuen
Release-Stand in das Erweiterungsverzeichnis übernehmen und die
Datenbank-Upgrades ausführen:

```bash
cv upgrade:db --mode=ext --no-interaction
cv flush
```

Nummerierte Upgrade-Schritte werden über den CiviCRM-Upgrader verwaltet. Die
initiale Schema-Basis dieser Erweiterung ist `1000`.

## Noch nicht enthalten

Die automatische Prüfung von Qualifikationen und deren Gültigkeit ist noch
nicht Bestandteil dieser Alpha-Version. Auch ein Update über das offizielle
CiviCRM-Erweiterungsverzeichnis steht erst nach einer stabilen Veröffentlichung
zur Verfügung.

## Support

Fehler und Funktionswünsche können im
[GitHub-Issue-Tracker](https://github.com/Jannik-Rathke/moeve-crewing/issues)
gemeldet werden.

## Lizenz

Möwe Crewing ist unter der [AGPL-3.0](LICENSE.txt) lizenziert.
