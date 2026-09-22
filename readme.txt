=== OpenNow CTA ===
Contributors: gasatrya
Tags: call to action, business hours, dynamic content, gutenberg, shortcode
Requires at least: 6.6
Requires PHP: 7.4
Tested up to: 7.1
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
The weekly-hours editor uses one keyboard-operable fieldset per weekday with
visible Open/Closed state text; Closed disables its time controls, and the time
rows stack on narrow screens. A live preview beside the form (stacked below it
on narrow screens) lets administrators inspect either CTA state using current
unsaved label, optional status, and color values without evaluating the
schedule. Beneath the preview, a page-scoped developer card offers explicit
links to hire Gasatrya or support OpenNow; it loads no remote content or makes a
request until an administrator follows a link.

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
override restores the legacy empty block delimiter. Schedules remain global
and shared by every integration. Blocks additionally expose WordPress's
built-in text/background color and typography controls. A block's colors
override the global CTA link colors for that instance, while its typography can
set font size, family, weight, style, line height, letter spacing, and text
transform. These choices do not change shortcode output.

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

The OpenNow settings provide global CTA link background and text colors for the
shortcode and as the default for every block. Individual blocks can override
both through WordPress's built-in color panel; content and state overrides do
not affect appearance. The global color pickers display
`#166534` and `#FFFFFF` when their corresponding saved values are blank.
Legacy blank values remain valid and use those defaults at runtime; saving the
displayed picker value stores an explicit color. Nonblank values must be
six-digit hexadecimal colors and the effective pair must meet WCAG 2.2 AA
contrast for normal text. Invalid global colors or low global contrast use the
complete plugin default pair. WordPress's editor contrast checker warns about
potentially inaccessible per-block choices, but editors remain responsible for
the contrast of those overrides. There are no per-state color controls.

Plugin styling uses the theme's inherited typography by default. Each block
can override that inheritance through WordPress's built-in typography panel;
the shortcode continues to inherit theme typography. The compact, button-like
baseline gives the link a minimum 44x44 CSS-pixel target, modest rounding, and
responsive wrapping; status text has a small separation gap and also wraps
safely. It does not set fixed dimensions or load fonts.

The rendered CTA is a native keyboard-operable link with visible focus styling.
State is not communicated by color alone, and no fake button role or `aria-live`
region is added. Stable styling hooks are `.opennow-cta`,
`.opennow-cta--open`, `.opennow-cta--closed`, `.opennow-cta__link`, and
`.opennow-cta__status`.

The settings preview has keyboard-operable Open and Closed controls with an
announced selected state. It immediately reflects unsaved label, optional
status, and global color edits, uses `#166534` and `#FFFFFF` for blank colors,
and is visual only: it never previews or activates the CTA action. Preview state
selection neither predicts nor changes the schedule.

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
remote license checks. Ordinary user-initiated navigation is the only outbound
behavior: a visitor may follow an administrator-configured CTA, and an
administrator may follow the clearly labeled Gasatrya hire or donation links on
Settings > OpenNow. The settings card loads no remote content before activation.

== Deactivation and uninstall ==

Deactivation stops plugin behavior but retains settings for reactivation.
Uninstall permanently deletes the OpenNow configuration and schema marker. No
custom tables, posts, pages, user metadata, or transient business data are
created. Reinstalling after uninstall starts unconfigured.

== Support ==

The required release gate verifies WordPress 7.1 (pinned to 7.1.1) with PHP
8.5. This is the only required integration lane; it does not replace the
WordPress 6.6 and PHP 7.4 minimum targets. A package is release-ready only
after the required CI gate passes. See the repository support contract for the
complete behavior and compatibility details.

== Changelog ==

= 0.1.0 =
* Initial OpenNow CTA release.
