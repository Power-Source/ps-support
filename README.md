<div align="center">

# PS Support System

**Ticket-Support, Wissensdatenbank und Helpdesk für WordPress- und ClassicPress-Netzwerke.**

[![Version](https://img.shields.io/badge/Version-1.0.2-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
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
- Kategorien wahlweise einem Bearbeiter oder einem Team zuordnen
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
- **PS Chat:** privater Gast-Supportchat mit Kategorie-Teams und exklusiver Übernahme

### Privater Supportchat

**Support → Einstellungen → Supportchat** enthält Aktivierung, Chatname,
Platzierung, eigenen Besucher-Hinweis, Pflichtangaben und Aufbewahrungsfristen.
Der Posteingang liegt unter **Support → Supportchats**, nicht in einem eigenen
Hauptmenü. Die Konfiguration ist auch bei inaktivem PS Chat sichtbar;
für den Chatbetrieb muss PS Chat auf der jeweiligen Site aktiv sein.

Fehlende Fallback-Mitarbeiter und ungültige Aufbewahrungsfristen werden vor dem
Speichern direkt im Formular angezeigt. AJAX-Serverfehler erhalten alle Eingaben;
ohne JavaScript führt ein Validierungsfehler mit dem Entwurf zurück zu den
Einstellungen statt auf eine `wp_die`-Fehlerseite.

Im Netzwerk werden unter **Netzwerkverwaltung → Support →
Einstellungen → Supportchat** der zentrale Chat aktiviert und Subsites gesondert
freigegeben. Der zentrale Chat erscheint auf der in den PS-Support-
Frontend-Einstellungen gewählten Support-Site automatisch, sobald PS Chat dort
aktiv ist (ohne konfigurierte Support-Site gilt die Netzwerk-Hauptseite).
Eine Subsite benötigt zusätzlich Netzwerkfreigabe und lokale Aktivierung;
sie kann eigenen oder Netzwerk-Support nutzen. Die Ticketfunktion muss dafür
nicht aktiviert sein. Auf Einzelinstallationen wird der Chat direkt unter
Support aktiviert.

Erst die erste Gastnachricht benachrichtigt den Kategorie-Bearbeiter bzw.
jedes Teammitglied per E-Mail. Der Link erfordert eine reguläre Anmeldung;
erst „Chat übernehmen“ weist die Sitzung exklusiv einem Mitarbeiter zu.
Andere Besucher sehen keine Inhalte. Der Besucher-Hinweis ist standardmäßig
leer und kann vom Administrator selbst verfasst werden.

Das Standardteam für Anfragen ohne Kategorie-Zuständigkeit wird aus den
vorhandenen Support-Mitarbeiter-Rollen, Administratoren und ausdrücklich
zugewiesenen Kategorie-Teams der jeweiligen Ebene gewählt. Bei CRM-Anbindung
gilt ausschließlich der CRM-Agentenpool. Normale Website-Benutzer erscheinen
nicht automatisch als Mitarbeiter. Die Autovervollständigung sucht ab zwei
Zeichen und liefert höchstens 20 Treffer. Ein Klick fügt die Person dem
sichtbaren Team hinzu; „Entfernen“ nimmt sie wieder heraus.
Es gibt keine zusätzliche Chat-Mitarbeiterrolle. Veraltete Auswahlen ohne
Support-Zugehörigkeit werden markiert und beim Speichern zurückgewiesen;
sie erhalten keine neuen Chats. Bestehende Gespräche behalten ihre Zuordnung.

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

### Kategorie-Teams

Unter **Ticketkategorien** können beim Anlegen und Bearbeiten mehrere verfügbare
Bearbeiter über die Autovervollständigung hinzugefügt und einzeln wieder
entfernt werden. Es erscheinen nur ausgewählte Personen im Team; Suchtreffer
werden erst ab zwei Zeichen serverseitig geladen und auf 20 begrenzt.
Bei zentralen Netzwerkkategorien ohne
CRM-Sync sind registrierte Mitglieder der Sites dieses Netzwerks auswählbar,
ohne Superadminrechte zu benötigen. Lokale Kategorien und CRM-Sync behalten
ihren bisherigen Bearbeiterpool. Andere bestehende Einzelzuweisungsfelder
verwenden unverändert die bisherige CRM-/Administratorliste:

- **Keine Auswahl:** keine automatische Kategoriezuweisung.
- **Ein Benutzer:** unveränderte automatische Zuweisung über `category->user_id`.
- **Mehrere Benutzer:** neue Tickets bleiben unzugewiesen; alle weiterhin
  verfügbaren Teammitglieder erhalten jeweils eine eigene Benachrichtigung.
  Antworten des Erstellers benachrichtigen das Team, nach einer ausdrücklichen
  Zuweisung nur noch den Bearbeiter. Interne Teamnotizen werden nicht an den
  Ersteller und Antworten nicht an ihren Verfasser zurückgesendet.

Explizit benannte Kategorie-Bearbeiter können ihre Kategorietickets auch bei der Einstellung „nur eigene Tickets“
sehen und ticketbezogen bearbeiten, beantworten, zuweisen, markieren und schließen.
Die Mitgliedschaft verleiht keine globalen Mitarbeiter-, Verwaltungs- oder
Löschrechte. Die Sichtbarkeit öffentlicher Tickets bleibt unverändert.
Netzwerk- und Subsite-Kategorien behalten ihre bestehenden Datengrenzen.

Es ist keine Tabellenmigration nötig: Teams werden je Kategorie unter
`psource_support_category_team_{blog_id}_{cat_id}` in einer Option des jeweiligen
Netzwerks gespeichert. `blog_id = 0` bezeichnet den zentralen Bestand. Bei einer
Einzelauswahl oder beim Leeren wird diese Option entfernt; alte Kategorien
funktionieren unverändert. Nicht mehr verfügbare Mitglieder werden beim Lesen
ausgeschlossen, ohne ein Team automatisch in eine Einzelzuweisung umzuwandeln.

Integrationen können diese Helfer verwenden:

```php
psource_support_get_category_assignee_ids( $category ); // int[]
psource_support_user_can_handle_category( $user_id, $category ); // bool
```

`$category` muss ein `PSource_Support_Ticket_Category` des aktuellen Netzwerks
sein. Die ID-Liste ist eindeutig und enthält nur existierende Benutzer; bei
Teams zusätzlich nur aktuell verfügbare Kategorie-Bearbeiter. Ohne Team wird die bisherige
`user_id` verwendet. Der Mitgliedschaftshelfer prüft die übergebene Kategorie,
nicht globale Berechtigungen oder den Zugriff auf fremde Subsites. Für
ticketbezogene Rechte `psource_support_user_can( $user_id, 'update_ticket', $ticket_id )`
verwenden. Vor dem Laden einer Subsite-Kategorie muss die Integration den
vorhandenen Subsite-Datenkontext herstellen.

Programmgesteuert akzeptiert `psource_support_insert_ticket_category( $name,
$user_id = false, $assignee_ids = null )` optional eine validierte ID-Liste.
`psource_support_update_ticket_category( $cat_id, array( 'assignee_ids' => $ids ) )`
ändert diese Auswahl; ein ausdrückliches altes `user_id`-Update wechselt zurück
zur Einzelzuweisung. Änderungen ohne Zuweisungsargument erhalten das Team.
`psource_support_get_category_available_assignees( $blog_id = null )` liefert den
kategoriespezifischen Auswahlpool (Arrays mit `user_id` und `label`);
`psource_support_validate_category_assignee_ids( $ids, $blog_id = null )` prüft
gegen diesen Pool. Ohne explizite Blog-ID wird der aktuelle Datenkontext verwendet.

Gezielter Integrationstest auf einer Entwicklungsinstallation (mit temporären,
abschließend entfernten Datenbank-Fixtures und abgefangenen E-Mails):

```sh
wp --skip-plugins --skip-themes eval-file wp-content/plugins/ps-support/tests/category-teams.php
```

## Support-Bot

Unter **Support → Einstellungen → Support-Bot** gibt es einfache Themenkarten:
Name und Begrüßung festlegen, pro Thema Frage/Titel, Klartext-Antwort und optional
eine Kategorie wählen. Über **Thema hinzufügen/entfernen** werden maximal 50
Themen gepflegt; kein JSON und kein Ablaufeditor sind erforderlich. Aktivierung
für **Supportchat** (benötigt PS Chat) und **Ticketformulare** erfolgt getrennt;
beides ist standardmäßig ausgeschaltet. Bestehende Antwort-Templates bleiben
unverändert.

Besucher wählen ein Thema, lesen die Antwort und wählen **Gelöst**, **Neu starten**
oder **Support kontaktieren / Ticket vorbereiten**. Das bisherige Formular ist
immer erreichbar, auch per **Direkt zum Ticketformular**, ohne JavaScript oder
bei deaktiviertem Bot. Erst eine ausdrückliche Übergabe ergänzt Titel,
Kategorie und ein bearbeitbares Bot-Protokoll im Frontend- bzw. Admin-Ticketformular.
Vorhandene Nachrichten und eigene Titel bleiben erhalten. Es erfolgen weder
automatische Übermittlung noch Ticketanlage oder E-Mail; Login, Berechtigungen
und bestehende Formular-Nonces bleiben unverändert. Anschließend muss der Benutzer
das Ticket selbst absenden.

Ungültige bzw. unvollständige Themen werden nicht gespeichert. Fehler erscheinen
direkt über dem Formular; die Eingaben bleiben erhalten, auch bei Server- oder
Netzwerkfehlern. Ohne JavaScript werden fehlerhafte Eingaben nach der Rückleitung
für denselben Administrator einmalig wieder angezeigt (Entwurf maximal 10 Minuten).

### Integrationsvertrag

```php
$bot = PSource_Support_Bot::config( $scope = 0 );
// Exakte öffentliche Schlüssel:
// enabled_chat (bool), enabled_tickets (bool), name (string), greeting (string),
// topics: array of {id: string, title: string, answer: string, category_id: int}
```

`0` bezeichnet den zentralen Support: Multisite verwendet die Netzwerkoption
`psource_support_bot`, Single-Site die gleichnamige lokale Option. Eine positive
Blog-ID liest ausschließlich die lokale Blog-Option; es gibt keine Vererbung.
Netzwerk-Admin konfiguriert Scope `0`, lokale Multisite-Einstellungen die aktuelle
Blog-ID. Für Formulare wird `psource_support_get_data_blog_id()` verwendet; für
den Chat muss der Aufrufer den bestehenden tatsächlichen Support-Scope übergeben
(zentral/Netzwerk `0`, eigener Site-Support Blog-ID), nicht die besuchte Seite.
Ungültige/fremde Netzwerksites liefern eine deaktivierte Standardkonfiguration.
Kategorien werden immer auf das aktuelle Netzwerk und den übergebenen Scope
begrenzt; ungültige Kategorien werden beim Speichern abgewiesen. Nachträglich
gelöschte oder veraltete Kategorien werden in der öffentlichen Konfiguration
zu `0` (keine Vorauswahl). Alle Texte sind bereinigter Klartext und müssen von
Integrationen als Text, nicht als HTML, gerendert werden. Der API-Aufruf verändert
keine Daten und benötigt weder PS Chat noch externe Dienste.

Gezielte Tests (nur Entwicklungsdatenbank; Optionen, Kategorien und Benutzer
werden auch bei Fehlern wiederhergestellt):

```sh
wp --skip-plugins --skip-themes eval-file wp-content/plugins/ps-support/tests/support-bot.php
node wp-content/plugins/ps-support/tests/support-bot-ui.cjs
```

Der JavaScript-Test verwendet eine bereits vorhandene `jsdom`-Entwicklungsinstallation;
das Plugin selbst hat keine neue Abhängigkeit.

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
