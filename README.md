# OpenNow CTA

OpenNow CTA automatically shows the appropriate call to action for a business's
current weekly schedule. It is a small, server-rendered WordPress plugin: no
account, SaaS service, booking system, tracking, or remote service is required.

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
known-good option and displays field-specific errors.

## Add a CTA

Use the shortcode anywhere WordPress processes shortcodes:

```text
[opennow_cta]
```

Or insert the **OpenNow CTA** block (`opennow/cta`) in the block editor. The
block is dynamic and uses the same server renderer as the shortcode. Its editor
preview shows the current server-rendered output, including the current
business state.

Each block may optionally override the label, action, and/or status separately
for its open and closed state. Overrides are sparse: every missing or invalid
field independently falls back to that field in the global state CTA. Enabling
a status override and leaving it blank explicitly suppresses the global status.
Removing all overrides restores the legacy empty block delimiter. The
shortcode accepts no content overrides, and schedules, global colors, and
styles remain global for every integration.

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

Appearance controls are global only and are shared by the shortcode and all
OpenNow blocks. The MVP exposes only CTA link background and text colors;
content overrides do not change appearance. Leave either field blank to use
the plugin defaults (`#166534` and `#FFFFFF`). Nonblank values must be
six-digit hexadecimal colors and the effective pair must meet WCAG 2.2 AA
contrast for normal text. Invalid colors or an invalid pair use the complete
default pair at runtime; there are no per-block or per-state style overrides.

Plugin styling uses the theme's inherited typography and a compact, button-like
baseline: the link has a minimum 44x44 CSS-pixel target, modest rounding, and
responsive wrapping; status text has a small separation gap and also wraps
safely. It does not set fixed dimensions or load fonts.

The output uses a native, keyboard-operable link with a visible focus style.
Open/closed state is not conveyed by color alone, and the plugin does not add a
fake button role or an `aria-live` announcement. Themes may style the stable
hooks `.opennow-cta`, `.opennow-cta--open`, `.opennow-cta--closed`,
`.opennow-cta__link`, and `.opennow-cta__status`.

## Caching and limitations

State is evaluated on the server when the shortcode or dynamic block renders.
OpenNow does not use browser timers, AJAX, REST polling, cache variation,
scheduled purges, or a computed-state cache. Cached page HTML can therefore
remain stale for up to the site's page-cache TTL. If a site needs a maximum
staleness of **N** minutes, configure a cache TTL of no more than **N** minutes
or arrange boundary purges in its own caching system; OpenNow performs neither.

The administrator is responsible for keeping status copy such as “reopen at
9 AM” accurate. OpenNow does not calculate or translate that copy, provide
holiday hours, or integrate with booking, CRM, analytics, CDNs, or page-cache
systems.

## Privacy and external services

OpenNow makes no external requests and loads no remote scripts, fonts, images,
stylesheets, or other assets. It collects no visitor data, sets no cookies, and
performs no analytics, tracking, telemetry, scheduled network activity, or
remote license checks. A visitor following an administrator-configured HTTPS
or telephone action is ordinary user-initiated navigation and is the only
outbound behavior.

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

A local real-WordPress run additionally needs MySQL and the exact test version:

```bash
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 6.6.7
WP_VERSION=6.6.7 composer test:integration
```

The production package is generated as `dist/opennow-0.1.0.zip` and is
intentionally ignored by Git. It contains only runtime PHP, local assets,
generated block files, the translation template, and release documentation.
See [`docs/release-checklist.md`](docs/release-checklist.md) for matrix,
accessibility, package, and release verification.
