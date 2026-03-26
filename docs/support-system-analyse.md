---
layout: psource-theme
title: "PS Support System Analyse"
---

# PS Support System Analyse

Diese Seite fasst die aktuelle Funktionsweise des Plugins zusammen, beschreibt die vorhandenen Einstellungen und zeigt typische Einsatzmoeglichkeiten. Die Aussagen basieren auf dem aktuellen Code-Stand des Plugins.

## Kurzueberblick

PS Support System ist ein Support- und FAQ-Plugin fuer ClassicPress beziehungsweise WordPress. Es bringt zwei zentrale Funktionsbereiche zusammen:

1. Ein Ticket-System fuer Support-Anfragen mit Antworten, Prioritaeten, Kategorien und Anhaengen.
2. Ein FAQ-System mit Kategorien, Voting und Frontend-Ausgabe.

Der Schwerpunkt liegt auf Multisite. Das Plugin funktioniert aber auch auf Einzelinstallationen.

## Funktionsweise

## 1. Ticket-System

Das Plugin legt eigene Datenbanktabellen fuer Tickets, Ticket-Nachrichten, Ticket-Meta und Ticket-Kategorien an. Ein Ticket besteht im Kern aus:

* Kategorie
* Ersteller
* optional zugewiesenem Bearbeiter
* Prioritaet
* Status
* Titel
* erster Nachricht und weiteren Antworten
* optionalen Dateianhaengen

Unterstuetzte Status im Code:

* Neu
* In Bearbeitung
* Warten auf die Antwort des Benutzers
* Warten auf Admin, um zu antworten
* Eingestellt
* Geschlossen

Unterstuetzte Prioritaeten:

* Niedrig
* Normal
* Hoch
* Eilt
* Dringend

Wird ein Ticket erstellt, wird automatisch auch der erste Nachrichteneintrag gespeichert. Antworten auf ein Ticket landen in einer separaten Nachrichten-Tabelle und koennen ebenfalls Anhaenge enthalten.

## 2. FAQ-System

Das FAQ-System nutzt eigene Tabellen fuer FAQ-Eintraege und FAQ-Kategorien. Jeder FAQ-Eintrag besitzt:

* eine Kategorie
* eine Frage
* eine Antwort
* Zaehler fuer Aufrufe und Feedback

Im Frontend koennen Benutzer markieren, ob ein FAQ hilfreich war oder nicht. Dadurch entsteht ein einfaches Feedback-System fuer die Qualitaet der Inhalte.

## 3. Admin- und Netzwerk-Logik

Das Plugin trennt zwischen:

* Netzwerk-Admin in Multisite
* Site-Admin in Multisite
* normaler Single-Site-Installation

In Multisite wird die zentrale Verwaltung ueber den Netzwerk-Admin bereitgestellt. Site-Admins oder andere berechtigte Rollen koennen je nach Konfiguration Tickets und FAQs in ihrer eigenen Site sehen. In Single-Site werden Netzwerkfunktionen auf normale Admin-Menues abgebildet.

## 4. Frontend-Logik

Die Frontend-Ausgabe wird nur aktiviert, wenn sie in den Einstellungen eingeschaltet ist. In Multisite ist sie an eine definierte Ziel-Site gebunden. Dort registriert das Plugin drei Shortcodes:

* `[support-system-tickets-index]`
* `[support-system-submit-ticket-form]`
* `[support-system-faqs]`

Darueber koennen Benutzer:

* Tickets auflisten
* einzelne Tickets ansehen
* auf Tickets antworten
* neue Tickets einreichen
* FAQ-Inhalte durchsuchen und bewerten

## Einstellungen

Die Konfiguration ist in zwei Registerkarten aufgeteilt.

## Allgemein

Vorhandene Optionen:

* Support-Menuname
* Absendername fuer E-Mails
* Absender-E-Mail
* Hauptadministrator / Hauptansprechpartner
* Datenschutz fuer Tickets
* erlaubte Rollen fuer Tickets
* erlaubte Rollen fuer FAQs
* optionale CRM-Sync-Site-ID in Multisite
* optionale PS Bloghosting / Pro Sites Einschraenkungen

Die Rechtevergabe ist rollenbasiert. Dabei werden Tickets und FAQs getrennt behandelt. Das ist praktisch, wenn zum Beispiel alle Mitglieder FAQs sehen duerfen, aber nur Kunden oder Redakteure Tickets anlegen sollen.

Die Datenschutzoption fuer Tickets kennt derzeit zwei Betriebsarten:

* alle berechtigten Benutzer duerfen alle Tickets sehen
* nur der Ticketersteller sieht seine eigenen Tickets

## Frontend

Vorhandene Optionen:

* Frontend aktivieren
* in Multisite die Frontend-Site-ID waehlen
* Support-Seite auswaehlen
* Seite fuer neue Tickets auswaehlen
* FAQ-Seite auswaehlen
* Standard-Styles des Plugins aktivieren oder deaktivieren
* optionale Pro-Benutzer-Einschraenkung im Frontend

Das Plugin fuegt in den Einstellungen direkt Hinweise auf die benoetigten Shortcodes ein. Dadurch ist die Konfiguration fuer Redakteure relativ nachvollziehbar.

## Berechtigungen und Rollen

Super-Admins in Multisite beziehungsweise Administratoren in Single-Site haben Vollzugriff. Alle anderen Rechte werden ueber Rollenlisten bestimmt.

Standardmaessig sind fuer Tickets und FAQs diese Rollen hinterlegt:

* administrator
* editor
* author
* contributor
* subscriber

Zusetzlich existiert intern auch die Rolle `support-guest`, die fuer Besucher vorgesehen ist. In der aktuellen Umsetzung ist der praktische Einsatz aber stark von Filtern oder individuellen Erweiterungen abhaengig.

## Dateianhaenge

Tickets und Antworten unterstuetzen Dateiuploads. Standardmaessig sind folgende MIME-Typen vorgesehen:

* jpg
* jpeg
* gif
* png
* zip
* gz und gzip
* rar
* pdf
* txt

Die erlaubten Typen koennen ueber einen Filter erweitert werden.

## Bearbeiter-Zuweisung und CRM

Bearbeiter koennen ueber Ticket-Kategorien automatisch zugeordnet werden. Darueber hinaus besitzt das Plugin eine Integration fuer SmartCRM-Tabellen.

Wenn auf der konfigurierten Sync-Site die Tabellen `smartcrm_agents` und `smartcrm_agent_roles` existieren, wird die Bearbeiterliste aus aktiven CRM-Agenten aufgebaut. Andernfalls verwendet das Plugin Super-Admins beziehungsweise Administratoren als verfuegbare Bearbeiter.

Das ist fuer Agentur- oder Hosting-Setups interessant, in denen Support nicht nur technisch, sondern auch organisatorisch ueber Rollen im CRM abgebildet wird.

## Benachrichtigungen

Das Plugin erzeugt HTML-E-Mails fuer Ticket-bezogene Ereignisse. Im Code sind ausserdem Hinweise fuer ein IMAP-/Mail-Reply-Szenario vorbereitet. Der sichtbare Kernnutzen im aktuellen Stand liegt aber klar bei den Benachrichtigungs-E-Mails, nicht bei einer vollstaendigen eingehenden E-Mail-Verarbeitung.

## Typische Einsatzmoeglichkeiten

## 1. Zentraler Kundensupport in Multisite

Ein Netzwerkbetreiber kann alle Tickets zentral im Netzwerk-Admin bearbeiten, waehrend Kunden ihre Anfragen im Frontend oder Site-Admin ihrer eigenen Site stellen.

## 2. Interner Support fuer Redaktionen oder Teams

Auch auf einer einzelnen Installation kann das Plugin genutzt werden, um interne Anfragen strukturiert ueber Kategorien, Prioritaeten und Ticket-Verlaeufe abzubilden.

## 3. Self-Service plus Eskalation

Die Kombination aus FAQ und Tickets ist vor allem fuer Support-Teams sinnvoll, die wiederkehrende Fragen zunaechst ueber Self-Service abfangen und erst danach echte Support-Faelle bearbeiten wollen.

## 4. Premium-Support mit Pro Sites

Durch die vorhandene Pro-Sites-Integration laesst sich der Zugriff auf Tickets oder FAQs an bezahlte Tarife koppeln. Das ist fuer Hosting, Membership oder Agenturangebote relevant.

## Staerken des Plugins

* gutes Multisite-Fundament
* klare Trennung zwischen Ticket- und FAQ-Bereich
* Frontend und Backend parallel nutzbar
* Rollenkonzept ist einfach nachvollziehbar
* Dateianhaenge und Antwortverlauf sind bereits integriert
* CRM-Agenten koennen als Bearbeiterquelle dienen

## Grenzen im aktuellen Stand

* die UI und Formulierungen wirken an mehreren Stellen veraltet
* einige Funktionen sind vorbereitet, aber nicht durchgaengig ausgebaut, zum Beispiel IMAP
* die Dokumentation war bisher deutlich duenner als der reale Funktionsumfang
* Datenschutz und Rechte sind eher grobgranular als fein abgestuft
* die Frontend-Ausgabe ist shortcode-basiert und nicht blockorientiert

## Moegliche Verbesserungen

## Produkt und UX

* moderneres Frontend mit klarerem Ticket-Status, Filtern und Suchfunktion
* bessere Status-Kommunikation fuer Benutzer, zum Beispiel SLA, letzte Antwort, zustaendiger Bearbeiter
* komfortablere Ticket-Erstellung mit Inline-Validierung und Upload-Fortschritt
* echte Dashboard-Uebersicht fuer Support-Mitarbeiter mit offenen, ueberfaelligen und unbeantworteten Tickets

## Rechte und Workflows

* feinere Berechtigungen statt nur Rollenlisten, zum Beispiel getrennt fuer lesen, beantworten, zuweisen und schliessen
* Team-Queues, Labels und interne Notizen fuer Support-Mitarbeiter
* automatische Regeln fuer Kategorie, Prioritaet, Zuweisung und Eskalation

## Technik

* REST-API oder moderne AJAX-Endpunkte fuer Frontend und Integrationen
* Block-Editor-Unterstuetzung statt reiner Shortcodes
* saubere Abdeckung der Datenbank- und Business-Logik mit automatisierten Tests
* konsistente Nutzung von `wp_safe_redirect`, Nonces und strikterer Input-Validierung in allen Pfaden

## Integrationen

* echte bidirektionale CRM-Synchronisation statt nur Bearbeiterquelle
* verlässliche E-Mail-Reply-Verarbeitung fuer Antworten per Mail
* Webhook- oder API-Anbindung an Slack, Matrix, Discord oder Helpdesk-Systeme

## Reporting

* Kennzahlen fuer Antwortzeit, Loesungszeit, Ticketvolumen und FAQ-Wirksamkeit
* Exportfunktionen fuer Tickets, Antworten und FAQ-Feedback

## Empfehlung

Wenn Ihr das Plugin weiterentwickeln wollt, wuerde ich in dieser Reihenfolge vorgehen:

1. Dokumentation und Begriffe bereinigen
2. moderne Support-Mitarbeiter-Uebersicht bauen
3. Rechte- und Workflow-Modell verfeinern
4. Block-Editor und REST-Schnittstellen einfuehren
5. CRM- und Mail-Integrationen vervollstaendigen