=== PS Support System ===
Contributors: DerN3rd (PSOURCE)
Tags: multisite, support, helpdesk, faq, classicpress-plugin
Requires at least: 4.9
Tested up to: 6.8.1
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Support-Tickets, FAQ-Verwaltung und Frontend-Supportseiten fuer ClassicPress und WordPress, inklusive Multisite-Unterstuetzung.

== Description ==

PS Support System ist ein Helpdesk-Plugin fuer ClassicPress oder WordPress mit Schwerpunkt auf Multisite-Umgebungen. Das Plugin kombiniert ein Ticket-System, einen FAQ-Bereich und eine rollenbasierte Rechtevergabe in einer gemeinsamen Loesung.

Es eignet sich fuer einzelne Webseiten ebenso wie fuer Netzwerke, in denen Support zentral im Netzwerk-Admin bearbeitet, aber von Benutzern einzelner Sites aus angefragt wird.

== Features ==

* Ticket-System mit Status, Prioritaet, Kategorien und Antwortverlauf
* FAQ-Management mit eigenen FAQ-Kategorien und Hilfreich/Nicht-hilfreich-Feedback
* Frontend-Ausgabe ueber Shortcodes fuer Ticketliste, Ticketformular und FAQ-Seite
* Admin- und Netzwerk-Menues fuer Single-Site und Multisite
* Rollenbasierte Freigabe fuer Tickets und FAQs
* Datenschutzoption: alle Tickets sichtbar oder nur eigene Tickets
* Dateianhaenge bei Tickets und Antworten
* Automatische Zuweisung von Bearbeitern ueber Ticket-Kategorien
* E-Mail-Benachrichtigungen bei Ticket-Aktivitaeten
* Integration mit PS Bloghosting / Pro Sites
* CRM-Agenten-Auswahl ueber SmartCRM-Tabellen, wenn vorhanden
* SLA-Ampel: farbliche Hervorhebung ueberfaelliger Tickets in der Admin-Liste
* "Warte seit"-Spalte zeigt Wartezeit pro Ticket
* Interne Notizen: Antworten nur fuer Mitarbeiter sichtbar markieren
* Eigene Queue: Dashboard-Karte fuer zugewiesene Tickets die auf Admin-Antwort warten
* Feinere Berechtigungen: separate Caps fuer Antworten, Zuweisen, Schliessen, Labels, Loeschen
* Ticket-Labels: farbige Tags zur freien Kategorisierung
* Antwort-Templates: vordefinierte Textbausteine fuer haeufige Antworten

== Settings ==

Das Plugin verwaltet seine Optionen zentral als Site-Option und stellt zwei Einstellungsbereiche bereit.

Allgemein:

* Bezeichnung des Support-Menues
* Absendername und Absender-E-Mail fuer Support-Nachrichten
* Hauptadministrator bzw. Hauptansprechpartner
* Erlaubte Rollen fuer Tickets
* Erlaubte Rollen fuer FAQs
* Datenschutz fuer Tickets
* Optionale CRM-Sync-Site-ID fuer die Bearbeiterliste in Multisite
* Optionale Einschraenkung ueber PS Bloghosting / Pro Sites

Frontend:

* Frontend-Unterstuetzung aktivieren
* Ziel-Site fuer die Frontend-Ausgabe in Multisite waehlen
* Support-Seite, Neue-Ticket-Seite und FAQ-Seite auswaehlen
* Plugin-Styles im Frontend aktivieren oder Theme-Styling verwenden
* Optionale Einschraenkung fuer Pro-Benutzer im Frontend

== Shortcodes ==

Fuer das Frontend stehen drei Shortcodes bereit:

* `[support-system-tickets-index]` zeigt die Ticketliste und Einzelansichten
* `[support-system-submit-ticket-form]` zeigt das Formular fuer neue Tickets
* `[support-system-faqs]` zeigt die FAQ-Liste

Diese Shortcodes werden nur auf der konfigurierten Frontend-Site initialisiert.

== Typical Use Cases ==

* Zentraler Netzwerk-Helpdesk fuer Multisite-Kundenprojekte
* Internes Support-System fuer Mitgliederbereiche oder Redaktionen
* FAQ plus Ticketformular fuer Hosting-, Shop- oder Agentur-Support
* Kombination aus Self-Service durch FAQs und nachgelagertem Ticket-Support

== Installation ==

1. Plugin in das Verzeichnis `wp-content/plugins/` kopieren.
2. In ClassicPress oder WordPress aktivieren.
3. Bei Multisite das Plugin netzwerkweit aktivieren.
4. Unter `Support > Einstellungen` die allgemeinen Rechte und die Frontend-Seiten konfigurieren.
5. Bei Frontend-Nutzung die passenden Shortcodes in die ausgewaehlten Seiten einfuegen.

== Frequently Asked Questions ==

= Kann das Plugin nur im Backend genutzt werden? =

Nein. Tickets und FAQs koennen komplett im Backend verwaltet werden, optional aber auch im Frontend ueber Shortcodes bereitgestellt werden.

= Funktioniert das Plugin in Multisite? =

Ja. Das Plugin ist fuer Multisite vorbereitet und kann Tickets zentral im Netzwerk verwalten, waehrend Benutzer von einzelnen Sites aus arbeiten.

= Koennen Dateien an Tickets angehaengt werden? =

Ja. Standardmaessig sind unter anderem JPG, PNG, GIF, ZIP, GZ, RAR, PDF und TXT erlaubt.

= Kann ich festlegen, wer Tickets oder FAQs sehen darf? =

Ja. Tickets und FAQs koennen getrennt nach Rollen freigeschaltet werden. Zusaetzlich gibt es eine Datenschutzoption fuer Tickets.

= Gibt es eine Integration mit CRM oder Pro Sites? =

Ja. Wenn passende SmartCRM-Tabellen vorhanden sind, kann die Bearbeiterliste aus CRM-Agenten kommen. Fuer PS Bloghosting / Pro Sites sind Zugriffsbeschraenkungen integriert.

== Screenshots ==

1. Ticketliste im Admin oder Netzwerk-Admin
2. Frontend-Formular fuer neue Tickets
3. FAQ-Liste mit Hilfreich-Feedback
4. Einstellungen fuer Rollen, Datenschutz und Frontend-Seiten

== Changelog ==

= 1.0.0 =

* Initiale Release-Version