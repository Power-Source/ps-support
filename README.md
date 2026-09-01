<div align="center">

# PS Support System

**Ticket-Support, Wissensdatenbank und Helpdesk für WordPress- und ClassicPress-Netzwerke.**

[![Version](https://img.shields.io/badge/Version-1.0.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.1-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

[Features](#features) · [Multisite](#multisite) · [Shortcodes](#shortcodes) · [Installation](#installation) · [Dokumentation](https://psource.eimen.net/wiki/ps-support-system-dokumentation/)

</div>

---

## Was ist PS Support?

PS Support System bringt einen vollständigen Helpdesk direkt in WordPress oder ClassicPress. Tickets, Antworten, FAQ-Inhalte und Berechtigungen werden im vertrauten Administrationsbereich verwaltet; für Benutzer stehen passende Frontend-Seiten bereit.

Das Plugin funktioniert auf einzelnen Websites und ist besonders auf Multisite-Netzwerke zugeschnitten: Der Netzwerk-Support bleibt zentral verfügbar, während freigegebene Subsites eigene, vollständig getrennte Ticket- und FAQ-Bereiche betreiben können.

## Features

### Ticket-System

- Tickets mit Status, Priorität, Kategorien und vollständigem Antwortverlauf
- Dateianhänge bei Tickets und Antworten
- Automatische Bearbeiterzuweisung über Ticketkategorien
- E-Mail-Benachrichtigungen bei Ticketaktivitäten
- Interne Notizen, die ausschließlich Mitarbeiter sehen
- Farbige Labels zur flexiblen Organisation
- Wiederverwendbare Antwortvorlagen für häufige Anfragen
- SLA-Ampel und „Warte seit“-Anzeige für überfällige Vorgänge
- Persönliche Dashboard-Queue für zugewiesene Tickets mit ausstehender Admin-Antwort

### FAQ und Self-Service

- Eigene FAQ-Kategorien und komfortable FAQ-Verwaltung
- Hilfreich-/Nicht-hilfreich-Bewertung durch Benutzer
- Durchsuchbare FAQ-Ausgabe im Frontend
- Zentraler, frei benennbarer Netzwerk-FAQ-Bestand
- Optionales Dashboard-Widget mit neuesten oder ausgewählten Netzwerk-FAQs
- Direkter Link vom Dashboard zur FAQ-Seite der Main-Site

### Rechte und Datenschutz

- Getrennte Rollenfreigaben für Tickets und FAQs
- Separate Berechtigungen für Antworten, Zuweisen, Schließen, Labels und Löschen
- Ticket-Sichtbarkeit wahlweise für alle berechtigten Benutzer oder nur für den Ersteller
- Rollenbasierte Mitarbeiter- und Verwaltungsrechte

### Integrationen

- **MarketPress:** lokale Shop-Tickets, Produkt-FAQs, Kategorien und Bearbeiter
- **SmartCRM:** optionale Synchronisation mit auswählbarer CRM-Site
- **PS Bloghosting / Pro Sites:** Funktionsfreigaben passend zum gebuchten Tarif

## Multisite

PS Support trennt lokale Inhalte konsequent nach Website und bewahrt zugleich den zentralen Netzwerkbestand:

| Bereich | Verhalten |
| --- | --- |
| Netzwerk-Support | Zentrale Verwaltung im Netzwerk-Admin |
| Netzwerk-FAQ | Zentraler Bestand der Main-Site, auf Subsites lesbar |
| Subsite-Support | Vom Netzwerkadmin global erlaubbar oder sperrbar |
| Lokale Tickets | Vollständig von anderen Sites getrennt |
| Lokale FAQs und Kategorien | Eigene Verwaltung je Subsite |
| Erzwungene Funktionen | Integrationen wie MarketPress können benötigte Shop-Funktionen additiv aktivieren |

Bestehende Netzwerk-FAQs bleiben bei der Aktivierung lokaler Supportbereiche unverändert. Sie werden weder kopiert noch in Subsite-Daten umgewandelt.

## Frontend

Das Plugin stellt Ticket- und FAQ-Funktionen über Shortcodes bereit. Dadurch können die Supportseiten mit dem Block-Editor, Classic Editor oder einem Page Builder frei aufgebaut werden.

| Shortcode | Ausgabe |
| --- | --- |
| `[support-system-tickets-index]` | Ticketliste und einzelne Ticketansichten |
| `[support-system-submit-ticket-form]` | Formular zum Erstellen eines Tickets |
| `[support-system-faqs]` | FAQ-Liste der aktuellen Site |
| `[support-system-faqs scope="network"]` | Zentraler Netzwerk-FAQ-Bestand |

Die Ausgabe kann mit den Plugin-Styles verwendet oder vollständig an das aktive Theme angepasst werden.

## Installation

1. Den Ordner `ps-support` nach `wp-content/plugins/` hochladen.
2. **PS Support System** in WordPress oder ClassicPress aktivieren.
3. In Multisite-Installationen das Plugin netzwerkweit aktivieren.
4. Unter **Support → Einstellungen** Rollen, Datenschutz und Integrationen konfigurieren.
5. Die benötigten Frontend-Seiten anlegen und die passenden Shortcodes einfügen.

## Konfiguration

### Allgemein

- Name des Support-Menüs und des Netzwerk-FAQ-Bereichs
- Absendername und Absenderadresse für Benachrichtigungen
- Hauptansprechpartner für nicht zugewiesene Tickets
- Rollen und Aktionsberechtigungen
- Datenschutzmodus für Tickets
- Freigabe eigenständiger Supportbereiche auf Subsites
- CRM-Synchronisation und CRM-Site
- Globales Netzwerk-FAQ-Dashboard-Widget

### Frontend

- Aktivierung der Frontend-Funktionen
- Support-, Ticketformular- und FAQ-Seite
- Plugin-eigenes Styling oder Theme-Darstellung
- Optionale Einschränkungen über PS Bloghosting / Pro Sites

## Anforderungen

| Komponente | Voraussetzung |
| --- | --- |
| PHP | 8.0 oder neuer |
| WordPress | 4.9 oder neuer, getestet bis 7.1.0 |
| ClassicPress | getestet mit 2.7.1 |
| Multisite | optional, für Netzwerkfunktionen erforderlich |

## Dokumentation und Support

Die ausführliche Dokumentation findest Du im [PSOURCE-Wiki](https://psource.eimen.net/wiki/ps-support-system-dokumentation/). Änderungen der aktuellen Version stehen im [Changelog](readme.txt#L117).

## Lizenz

PS Support System ist freie Software und wird unter der [GNU General Public License v2 oder neuer](https://www.gnu.org/licenses/gpl-2.0.html) veröffentlicht.

---

<div align="center">

Entwickelt von [PSOURCE](https://psource.eimen.net/)

</div>
