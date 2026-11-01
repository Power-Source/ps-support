<div align="center">

# PS Support System

[Deutsch](README.md) | **English**

**Ticket support, knowledge base and helpdesk for WordPress and ClassicPress networks.**

[![Version](https://img.shields.io/badge/Version-1.0.3-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-up%20to%207.1.4-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

</div>

---

## What is PS Support?

PS Support System adds a complete helpdesk directly to WordPress or ClassicPress.
Tickets, replies, FAQ content and permissions are managed in the familiar admin
area, while suitable frontend pages are available to users.

The plugin works on single sites and is especially designed for Multisite
networks: network support remains centrally available while enabled subsites can
operate their own, fully separated ticket and FAQ areas.

## Features

### Ticket system

- Tickets with status, priority, categories and a complete reply history
- File attachments on tickets and replies
- Automatic assignee selection through ticket categories
- Categories can be assigned to an individual agent or a team
- Email notifications for ticket activity
- Internal notes visible to staff only
- Coloured labels for flexible organisation
- Reusable reply templates for common requests
- SLA status indicator and "waiting since" information for overdue items
- Personal dashboard queue for assigned tickets awaiting an admin reply

### FAQ and self-service

- Custom FAQ categories and convenient FAQ management
- Helpful/not-helpful feedback by users
- Searchable frontend FAQ output
- Central, freely named network FAQ collection
- Optional dashboard widget for recent or selected network FAQs
- Direct dashboard link to the main site's FAQ page

### Permissions and privacy

- Separate role enablement for tickets and FAQs
- Separate permissions for replying, assigning, closing, labelling and deleting
- Ticket visibility for all authorised users or only the requester
- Role-based staff and administration permissions

### Integrations

- **MarketPress:** local shop tickets, product FAQs, categories and assignees
- **SmartCRM:** optional synchronisation with a selectable CRM site
- **PS Bloghosting / Pro Sites:** feature enablement appropriate to the booked plan
- **PS Chat:** private guest support chat with category teams and exclusive claiming

### Private support chat

**Support → Settings → Support chat** contains activation, chat name, placement,
floating chat-button position, a visitor notice, required fields and retention
periods. Chat windows are initially closed. The shortcode
`[ps_chat_support position="bottom-left"]` and the widget can override the
button position for an individual embedding (`bottom-right`, `bottom-left`,
`top-right`, `top-left`).

The inbox is located at **Support → Support chats**, not in a separate top-level
menu. Configuration remains visible if PS Chat is inactive, but PS Chat must be
active on the relevant site for the chat to operate.

Missing fallback agents and invalid retention periods are displayed directly in
the form before saving. AJAX server failures retain every input; without
JavaScript, a validation error returns to the settings page with the draft rather
than displaying a `wp_die` error page.

In a network, central chat is enabled under **Network Admin → Support →
Settings → Support chat** and subsites are enabled separately. The central chat
appears automatically on the support site selected in PS Support frontend
settings as soon as PS Chat is active there. If no support site is configured,
the network main site is used. A subsite additionally requires network approval
and local activation; it can use its own or network support. Ticket support does
not need to be enabled for this. On single-site installations, chat is enabled
directly under Support.

Only the first guest message sends an email notification to the category agent
or team. The link requires a regular login; only **Claim chat** exclusively
assigns the session to a staff member. Other visitors cannot see its content.
The visitor notice is empty by default and can be written by the administrator.

The fallback team for requests without a responsible category is selected from
existing support-staff roles, administrators and explicitly assigned category
teams at the relevant level. With CRM integration, only the CRM agent pool is
available. Regular site users are not automatically offered as staff. Autocomplete
starts after two characters and returns at most 20 results. Clicking a result
adds it to the visible team; **Remove** removes it again.

There is no additional chat-staff role. Legacy selections without support
membership are marked and rejected when saved; they do not receive new chats.
Existing conversations retain their assignment.

### Support bot

The support bot is a freely configurable help flow before a ticket form or in
private support chat. Operators define their own topics and build them from
neutral steps: notices, choice and yes/no questions, free-text inputs, links,
endings and handoff to support. Every answer can branch to another step. On
handoff, the topic and all answers are transferred as an editable summary to the
chat or ticket; a ticket category can be preselected per topic.

Existing topics with a single answer remain compatible and continue as a simple
notice step.

## Multisite

PS Support consistently separates local content by site while retaining central
network content:

| Area | Behaviour |
| --- | --- |
| Network support | Central administration in Network Admin |
| Network FAQ | Central main-site collection, readable on subsites |
| Subsite support | Globally enabled or disabled by the network administrator |
| Local tickets | Fully separated from other sites |
| Local FAQs and categories | Independently managed for each subsite |
| Enforced features | Integrations such as MarketPress can additively enable required shop features |

Existing network FAQs remain unchanged when local support areas are enabled.
They are neither copied nor converted to subsite data.

### Category teams

Under **Ticket categories**, multiple available assignees can be added through
autocomplete when creating or editing a category and can be removed separately.
Only selected people are visible in the team. Search results are loaded from the
server after two characters and are limited to 20 results.

For central network categories without CRM synchronisation, registered users of
sites in the current network can be selected without requiring super-admin
rights. Local categories and CRM synchronisation retain their existing assignee
pool. Other existing individual-assignment fields continue to use the established
CRM/administrator list:

- **No selection:** no automatic category assignment.
- **One user:** unchanged automatic assignment through `category->user_id`.
- **Multiple users:** new tickets remain unassigned; every still-available team
  member receives an individual notification. Replies by the requester notify
  the team; after an explicit assignment, only the assignee is notified.
  Internal team notes are not sent to the requester and replies are not returned
  to their author.

Explicitly named category agents can view and handle their category tickets even
when the "own tickets only" setting is active. Membership grants no global staff,
administration or deletion rights. Public ticket visibility remains unchanged.
Network and subsite categories retain their existing data boundaries.

No table migration is needed. Teams are stored per category under
`psource_support_category_team_{blog_id}_{cat_id}` in an option of the relevant
network. `blog_id = 0` denotes the central collection. For a single selection or
an empty team, the option is removed; legacy categories keep working. Unavailable
members are excluded while reading without automatically converting a team to an
individual assignment.

Integrations can use these helpers:

```php
psource_support_get_category_assignee_ids( $category ); // int[]
psource_support_user_can_handle_category( $user_id, $category ); // bool
```

`$category` must be a `PSource_Support_Ticket_Category` from the current
network. The ID list is unique and contains only existing users; for teams, only
currently available category agents are included. Without a team, the existing
`user_id` is used. The membership helper checks the supplied category, not global
permissions or access to foreign subsites. For ticket-specific permissions use
`psource_support_user_can( $user_id, 'update_ticket', $ticket_id )`. Before
loading a subsite category, an integration must establish the available subsite
data context.

Programmatically, `psource_support_insert_ticket_category( $name,
$user_id = false, $assignee_ids = null )` optionally accepts a validated ID
list. `psource_support_update_ticket_category( $cat_id, array(
'assignee_ids' => $ids ) )` changes this selection; an explicit legacy
`user_id` update switches back to individual assignment. Changes without an
assignment argument retain the team.
`psource_support_get_category_available_assignees( $blog_id = null )` returns
the category-specific selection pool (arrays with `user_id` and `label`);
`psource_support_validate_category_assignee_ids( $ids, $blog_id = null )`
validates against this pool. Without an explicit blog ID, the current data
context is used.

Focused integration test on a development installation (with temporary database
fixtures removed at the end and intercepted emails):

```sh
wp --skip-plugins --skip-themes eval-file wp-content/plugins/ps-support/tests/category-teams.php
```

## Support bot

Under **Support → Settings → Support bot**, up to 50 topics are maintained as
configurable help flows. A flow consists of notices, choice or yes/no questions,
free-text inputs, links, endings and optional support handoff. Every choice can
branch to a different step. **Support chat** (requires PS Chat) and **ticket
forms** are enabled separately and are disabled by default. Existing reply
templates remain unchanged.

Visitors select a topic and follow the configured help flow. The existing form
remains reachable at all times, including through **Go directly to ticket form**,
without JavaScript or when the bot is disabled. Only an explicit handoff adds an
editable log of the topic, category and all flow answers to the frontend or
admin ticket form. Existing messages and user-entered titles are retained. The
bot never automatically submits a ticket, creates a ticket or sends email;
login, permissions and existing form nonces remain unchanged. The user must
submit the ticket themselves afterwards.

Invalid or incomplete topics are never saved. Errors are displayed directly
above the form and inputs are retained after server or network failures. Without
JavaScript, invalid input is displayed once after redirection for the same
administrator (draft retained for up to 10 minutes).

### Integration contract

```php
$bot = PSource_Support_Bot::config( $scope = 0 );
// Exact public keys:
// enabled_chat (bool), enabled_tickets (bool), name (string), greeting (string),
// topics: array of {id: string, title: string, answer: string, category_id: int,
//   steps: array of flow steps}
```

`0` means central support: Multisite uses the `psource_support_bot` network
option, while single-site installations use the local option of the same name.
A positive blog ID reads only the local blog option; there is no inheritance.
Network Admin configures scope `0`; local Multisite settings configure the
current blog ID. Forms use `psource_support_get_data_blog_id()`; chat callers
must pass the existing actual support scope (central/network `0`, own site
support as blog ID), not the site being visited. Invalid or foreign network sites
return a disabled default configuration.

Categories are always limited to the current network and supplied scope; invalid
categories are rejected on save. Categories deleted or made stale later become
`0` (no preselection) in the public configuration. All text is sanitised
plain text and integrations must render it as text, not HTML. The API call
changes no data and requires neither PS Chat nor external services.

Focused tests (development database only; options, categories and users are
restored even after failures):

```sh
wp --skip-plugins --skip-themes eval-file wp-content/plugins/ps-support/tests/support-bot.php
node wp-content/plugins/ps-support/tests/support-bot-ui.cjs
```

The JavaScript test uses an existing `jsdom` development installation; the
plugin itself has no new dependency.

## Frontend

The plugin provides ticket and FAQ features through shortcodes. Support pages
can therefore be freely composed with the block editor, Classic Editor or a
page builder.

| Shortcode | Output |
| --- | --- |
| `[support-system-tickets-index]` | Ticket list and individual ticket views |
| `[support-system-submit-ticket-form]` | Form for creating a ticket |
| `[support-system-faqs]` | FAQ list for the current site |
| `[support-system-faqs scope="network"]` | Central network FAQ collection |

Output can use the plugin styles or be completely adapted to the active theme.

## Installation

1. Upload the `ps-support` directory to `wp-content/plugins/`.
2. Activate **PS Support System** in WordPress or ClassicPress.
3. In Multisite installations, activate the plugin network-wide.
4. Configure roles, privacy and integrations under **Support → Settings**.
5. Create the required frontend pages and insert the appropriate shortcodes.

## Configuration

### General

- Name of the Support menu and the network FAQ area
- Sender name and sender address for notifications
- Primary contact for unassigned tickets
- Roles and action permissions
- Privacy mode for tickets
- Enablement of independent support areas on subsites
- CRM synchronisation and CRM site
- Global network FAQ dashboard widget

### Frontend

- Enablement of frontend functionality
- Support, ticket-form and FAQ pages
- Plugin styling or theme presentation
- Optional restrictions through PS Bloghosting / Pro Sites

## Requirements

| Component | Requirement |
| --- | --- |
| PHP | 8.0 or later |
| WordPress | 4.9 or later, tested up to 7.1.0 |
| ClassicPress | tested with 2.7.1 |
| Multisite | optional; required for network features |

## Documentation and support

Detailed documentation is available in the
[PSOURCE wiki](https://psource.eimen.net/wiki/ps-support-system-dokumentation/).
Changes in the current version are listed in the [changelog](readme.txt#L117).

## License

PS Support System is free software released under the
[GNU General Public License v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

<div align="center">

Developed by [PSOURCE](https://psource.eimen.net/)

</div>
