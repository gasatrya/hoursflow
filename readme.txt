=== OpenNow CTA ===
Contributors: gasatrya
Tags: call to action, business hours, dynamic content, gutenberg, shortcode
Requires at least: 6.6
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically show the right call to action based on the current business state.

== Description ==

OpenNow CTA displays one global open CTA during configured business hours and a
closed CTA at other times. It provides a shortcode and a dynamic OpenNow CTA
block. Both use the same saved settings and server-side renderer.

This is a focused, single-site MVP. It does not become a booking system,
complete business-hours manager, CRM, marketing automation service, or SaaS
integration.

== Installation ==

1. Upload the `opennow` folder to `/wp-content/plugins/`, or upload the
   production `opennow-0.1.0.zip` in **Plugins > Add New > Upload Plugin**.
2. Activate **OpenNow CTA** from the Plugins screen.
3. Go to **Settings > OpenNow**.
4. Select a named IANA business timezone, configure Monday through Sunday, and
   enter both open and closed CTA label/action values.
5. Save the settings and add `[opennow_cta]` or the OpenNow CTA block to a page.

A new activation is unconfigured and renders nothing until a complete valid
configuration is saved. Settings are validated atomically; an invalid save
retains the last known-good configuration and reports field-specific errors.

== Usage ==

Add either shortcode:

`[opennow_cta]`

`[opennow_cta hide_status="1"]`

Only the exact string `hide_status="1"` is recognized. It hides the status for
the state selected at render time. Other values or malformed attribute
containers are ignored, as are all other attributes and enclosed content. The
shortcode without the attribute keeps its existing behavior.

Alternatively insert the **OpenNow CTA** block (`opennow/cta`). The block is
dynamic and its editor preview uses the current server-rendered output. It may
store sparse per-state content overrides for the label, action, and status,
plus a nested per-state `hideStatus: true` flag. Only strict boolean `true` is
valid for that flag. Each missing or invalid override field independently falls
back to the matching field in the selected global open or closed CTA; values
from the other state have no effect. `hideStatus: true` takes priority over a
nonblank global or status override. An enabled blank status still explicitly
suppresses the global status for backward compatibility. Removing every
override restores the legacy empty block delimiter. Schedules, colors, and
styles remain global and shared by every integration.

Actions may be root-relative URLs such as `/booking/`, complete `https://`
URLs, or supported `tel:` actions. Labels and status text are plain text. HTML,
unsafe schemes, credentials, protocol-relative URLs, control characters, and
bare relative paths are rejected.

== Schedule and timezone behavior ==

There is one weekly schedule and one period or closed entry per day. Periods
are half-open `[opening, closing)`: the opening minute is open and the exact
closing minute is closed. A closing time earlier than the opening time is an
overnight period belonging to its opening day and carrying into the next day.
A closed next day can still be open during a valid previous-day overnight carry.
Equal opening and closing times are invalid and do not mean 24-hour opening.

The saved named IANA timezone controls evaluation. `UTC` is valid, while raw
UTC offsets are not. Visitor, browser, server, and WordPress site timezones do
not replace the saved business timezone. PHP's timezone database supplies DST
rules: nonexistent spring-forward minutes are not evaluated, and both
occurrences of a fall-back repeated local time receive the same result. The
MVP has no DST override, holidays, exceptions, multiple periods, split shifts,
or date ranges.

== Appearance and accessibility ==

Only two global appearance controls exist: CTA link background and text color.
Content overrides do not affect appearance. Blank values use the defaults
`#166534` and `#FFFFFF`. Nonblank values must be six-digit hexadecimal colors
and the effective pair must meet WCAG 2.2 AA contrast for normal text. Invalid
colors or low contrast use the complete plugin default pair. There are no
per-block or per-state style controls.

Plugin styling uses the theme's inherited typography and a compact, button-like
baseline: the link has a minimum 44x44 CSS-pixel target, modest rounding, and
responsive wrapping; status text has a small separation gap and also wraps
safely. It does not set fixed dimensions or load fonts.

The rendered CTA is a native keyboard-operable link with visible focus styling.
State is not communicated by color alone, and no fake button role or `aria-live`
region is added. Stable styling hooks are `.opennow-cta`,
`.opennow-cta--open`, `.opennow-cta--closed`, `.opennow-cta__link`, and
`.opennow-cta__status`.

== Caching and limitations ==

OpenNow evaluates state server-side when output is rendered. It does not use
browser timers, AJAX, REST polling, cache variation, scheduled purges, or a
computed-state cache. Cached HTML can remain stale until the site's page-cache
TTL. For a maximum staleness of N minutes, configure a TTL of no more than N
minutes or arrange boundary purges in the site's own caching system; OpenNow
does neither. Administrators are responsible for the accuracy of status copy.

== Privacy and external services ==

OpenNow makes no external requests and loads no remote scripts, fonts, images,
stylesheets, or other assets. It collects no visitor data, sets no cookies, and
performs no tracking, analytics, telemetry, scheduled network activity, or
remote license checks. A visitor following an administrator-configured CTA is
ordinary user-initiated navigation and the sole outbound behavior.

== Deactivation and uninstall ==

Deactivation stops plugin behavior but retains settings for reactivation.
Uninstall permanently deletes the OpenNow configuration and schema marker. No
custom tables, posts, pages, user metadata, or transient business data are
created. Reinstalling after uninstall starts unconfigured.

== Support ==

The required release matrix verifies WordPress 6.6.7 with PHP 7.4 and 8.3,
and WordPress 7.0.4 with PHP 7.4, 8.3, and 8.5. A package is release-ready
only after every required CI lane passes. See the repository support contract
for the complete behavior and compatibility details.

== Changelog ==

= 0.1.0 =
* Initial OpenNow CTA release.
