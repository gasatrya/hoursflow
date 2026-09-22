# OpenNow CTA

OpenNow CTA helps visitors find the right next action for a business's current
weekly schedule. Configure one open CTA and one closed CTA, then present them
through a shortcode or dynamic block. It is a focused, server-rendered plugin:
there is no account or remote service to configure.

## Who is this for?

OpenNow CTA is for small businesses, studios, practices, shops, and service
teams that want a clear schedule-aware action while keeping their labels,
destinations, and status copy in WordPress.

## Core features

- One shared open/closed schedule for the shortcode and dynamic block.
- Named IANA timezone support, including overnight periods.
- Per-state block overrides for label, action, status, and status visibility.
- Global colors plus WordPress's built-in block color and typography controls.
- A keyboard-operable, responsive CTA with visible focus styling.
- Server-side rendering without browser timers, polling, remote assets, or
  background requests.

## Requirements

- WordPress 6.6 or newer.
- PHP 7.4 or newer.
- A single-site WordPress installation. Multisite and network activation are
  not claimed by this MVP.

## Install and configure

1. Download the production `opennow-0.1.0.zip`, or copy this plugin directory to
   `wp-content/plugins/opennow/`.
2. In **Plugins**, activate **OpenNow CTA**.
3. Open **Settings → OpenNow** and save one complete configuration. Choose a
   named IANA business timezone, enter the Monday–Sunday schedule, and provide
   both the open and closed CTA label/action pairs. Status text is optional.
4. Add the shortcode or block described below to a page.

A fresh activation is intentionally unconfigured and renders nothing until a
valid configuration is saved. Settings are one atomic WordPress option and are
validated before they are persisted. An invalid submission keeps the last
known-good option and displays field-specific errors. The weekly-hours editor
uses one keyboard-operable fieldset per weekday with visible Open/Closed state
text; Closed disables its time controls, and the time rows stack on narrow
screens. A live preview beside the form (stacked below it on narrow screens)
lets administrators inspect either CTA state using current unsaved label,
optional status, and color values without evaluating the schedule. Beneath the
preview, a page-scoped developer card offers explicit links to hire Gasatrya or
support OpenNow; it does not load remote content or make a request until an
administrator follows a link.

## Add a CTA

Use the shortcode anywhere WordPress processes shortcodes:

```text
[opennow_cta]
[opennow_cta hide_status="1"]
```

`hide_status="1"` is the only recognized shortcode attribute spelling and
value. It hides the status for whichever state is selected at render time; all
other attribute values, attribute containers, attributes, and enclosed content
are ignored. The default `[opennow_cta]` behavior is unchanged.

Or insert the **OpenNow CTA** block (`opennow/cta`) in the block editor. The
block is dynamic and uses the same server renderer as the shortcode. Its editor
preview shows the current server-rendered output, including the current
business state.

Each block may optionally override the label, action, and/or status separately
for its open and closed state. Each state may also store the nested boolean
`hideStatus` flag; only strict `true` is valid. Overrides are sparse: every
missing or invalid content field or flag independently falls back to the
matching global state CTA. The selected state's overrides alone are applied;
the other state's values have no effect. `hideStatus: true` hides the selected
status even when the global or overridden status is nonblank, while a valid
blank status override continues to suppress the global status for backward
compatibility. Removing all overrides restores the legacy empty block
delimiter. Schedules remain global for every integration. Blocks additionally
expose WordPress's built-in text/background color and typography controls. A
block's colors override the global CTA link colors for that instance, while its
typography can set font size, family, weight, style, line height, letter
spacing, and text transform. These choices do not change shortcode output.

Allowed actions are root-relative URLs (for example `/booking/`), complete
`https://` URLs, and supported `tel:` actions. Labels and status values are
plain text. Unsafe schemes, credentials, control characters, protocol-relative
URLs, HTML, and bare relative paths are rejected.

## Schedule behavior

There is one weekly profile with one period or a closed entry per day.

- Periods are minute-precision local wall-clock intervals `[opening, closing)`:
  opening is included and the exact closing minute is closed.
- A closing time earlier than the opening time is an overnight period. It
  belongs to the day on which it opens and carries into the following day.
- A closed following day can still be open after midnight because of the
  previous day's overnight period.
- Equal opening and closing times are invalid; they do not mean all-day open.
- Holidays, exceptions, split shifts, multiple periods, date ranges, and
  24-hour-day settings are outside this MVP.

The saved named timezone, not the visitor, browser, server, or WordPress site
timezone, controls evaluation. `UTC` and named IANA regions such as
`America/New_York` are valid; raw UTC offsets are not. Recurring wall-clock
rules use PHP's timezone database. During a spring-forward gap, nonexistent
minutes are never evaluated and the period begins or ends at the first
existing minute at or after its configured boundary. During a fall-back
repeated hour, both occurrences of a local time receive the same schedule
result. There is no DST override or ambiguity setting.

## Appearance and accessibility

The OpenNow settings expose global CTA link background and text colors shared
by the shortcode and used as the default for every block. Individual blocks
can override both colors through WordPress's built-in color panel; content and
state overrides do not change appearance. The global color pickers display
`#166534` and `#FFFFFF` when their corresponding saved values are blank.
Legacy blank values remain valid and use those defaults at runtime; saving the
displayed picker value stores an explicit color. Nonblank values must be
six-digit hexadecimal colors and the effective pair must meet WCAG 2.2 AA
contrast for normal text. Invalid global colors or an invalid global pair use
the complete default pair at runtime. WordPress's editor contrast checker warns
about potentially inaccessible per-block choices, but editors remain
responsible for the contrast of those overrides. There are no per-state color
overrides.

Plugin styling uses the theme's inherited typography by default. Each block
can override that inheritance through WordPress's built-in typography panel;
the shortcode continues to inherit theme typography. The compact, button-like
baseline gives the link a minimum 44x44 CSS-pixel target, modest rounding, and
responsive wrapping; status text has a small separation gap and also wraps
safely. It does not set fixed dimensions or load fonts.

The output uses a native, keyboard-operable link with a visible focus style.
Open/closed state is not conveyed by color alone, and the plugin does not add a
fake button role or an `aria-live` announcement. Themes may style the stable
hooks `.opennow-cta`, `.opennow-cta--open`, `.opennow-cta--closed`,
`.opennow-cta__link`, and `.opennow-cta__status`.

The settings preview has keyboard-operable Open and Closed controls whose
selected state is announced. It reflects unsaved label, optional status, and
global color edits immediately, uses `#166534` and `#FFFFFF` when color values
are blank, and is visual only: it never previews, follows, or activates the CTA
action. Preview state selection neither predicts nor changes the schedule.

## Caching and limitations

State is evaluated on the server when the shortcode or dynamic block renders.
OpenNow does not use browser timers, AJAX, REST polling, cache variation,
scheduled purges, or a computed-state cache. Cached page HTML can therefore
remain stale for up to the site's page-cache TTL. If a site needs a maximum
staleness of **N** minutes, configure a cache TTL of no more than **N** minutes
or arrange boundary purges in its own caching system; OpenNow performs neither.

The administrator is responsible for keeping status copy such as “reopen at
9 AM” accurate. OpenNow does not calculate or translate that copy, provide
holiday hours, or integrate with booking, CRM, CDNs, or page-cache systems.

## Privacy and external services

OpenNow makes no external requests and loads no remote scripts, fonts, images,
stylesheets, or other assets. It collects no visitor data, sets no cookies, and
performs no tracking, telemetry, scheduled network activity, or remote license
checks. Ordinary user-initiated navigation is the only outbound
behavior: a visitor may follow an administrator-configured HTTPS or telephone
CTA, and an administrator may follow the clearly labeled Gasatrya hire or
donation links on **Settings → OpenNow**. The settings card loads no remote
content before a link is activated.

## Deactivation and uninstall

Deactivation stops OpenNow behavior but retains the saved settings so a later
reactivation restores them. Uninstall permanently deletes the OpenNow
configuration and schema marker. The plugin creates no custom tables, posts,
pages, user metadata, or transient business data. Reinstalling after uninstall
starts unconfigured.

## Development

The repository pins its Composer and npm dependencies. Useful checks are:

From a clean checkout, install the locked dependencies and run every local gate:

```bash
composer install --no-interaction --prefer-dist
npm ci --ignore-scripts
composer check:php
npm run check
```

`composer check:php` validates Composer metadata, audits locked Composer
packages, checks PHP syntax and WordPress coding standards, audits static
strings, and runs PHPUnit. `npm run check` audits production npm packages, runs
JavaScript tests and linting, checks formatting and CSS, reproduces block/POT
assets, and verifies deterministic packaging.

A local real-WordPress run additionally needs MySQL and the exact boundary
lane you want to exercise. The required integration matrix uses WordPress
6.6.7 with PHP 7.4 and WordPress 7.1.1 with PHP 8.5:

```bash
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 6.6.7
WP_VERSION=6.6.7 composer test:integration

bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 7.1.1
WP_VERSION=7.1.1 composer test:integration
```

The production package is generated as `dist/opennow-0.1.0.zip` and is
intentionally ignored by Git. It contains runtime PHP, local assets, generated
block files, the translation template, and the three readable block source
files `src/blocks/cta/index.js`, `src/blocks/cta/editor.scss`, and
`src/blocks/cta/block.json`; other development files are excluded. The source
file `src/blocks/cta/index.js` is the human-readable source for the compiled
`build/blocks/cta/index.js`.

See the [release checklist on GitHub](https://github.com/gasatrya/opennow/blob/main/docs/release-checklist.md)
for integration, accessibility, package, and release verification.
