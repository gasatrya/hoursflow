=== OpenNow CTA ===
Contributors: gasatrya
Tags: call to action, business hours, dynamic content, gutenberg, shortcode
Requires at least: 6.6
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://gasatrya.com/donate/

Show an open or closed call to action based on your weekly business hours.

== Description ==

Visitors should not have to guess what to do when your business is open or
closed. OpenNow CTA lets you present one clear action for each state, such as
calling while you are open and booking or sending a message after hours.

Configure it once in **Settings > OpenNow**, then use the same schedule-aware
CTA through a shortcode or a dynamic block. OpenNow renders the selected state
on the server, without an account or a remote service.

= Who is this for? =

OpenNow CTA is for small businesses, studios, practices, shops, and service
teams that want a simple open-hours action on their WordPress site. It works
well when the action changes with the weekly schedule and the site owner wants
to keep the copy and destination under their control.

= Core Features =

* One global open CTA and one global closed CTA shared by the shortcode and
  the OpenNow CTA block.
* A Monday-to-Sunday schedule using a named IANA business timezone, including
  overnight periods such as 22:00 to 02:00.
* Sparse per-state block overrides for label, action, status, and hiding the
  status without changing the global settings.
* Root-relative URLs, HTTPS URLs, and supported `tel:` actions, with plain-text
  labels and status values.
* Global background and text colors, plus WordPress's built-in block color and
  typography controls.
* A compact, responsive link with visible keyboard focus and a minimum 44 CSS
  pixel target. Open and closed state is not communicated by color alone.
* No browser timers, polling, AJAX, remote assets, cookies, or background
  requests.

= Focused by design =

OpenNow CTA is a focused weekly-hours switch, not a booking system or a full
business-hours manager. Each weekday has one period or is closed. Holiday
exceptions, multiple daily periods, split shifts, date ranges, and automatic
schedule-generated status copy are outside this release. The status message is
written by the administrator and stays exactly as configured.

OpenNow is intended for a single-site installation. Multisite and network
activation are not claimed by this release.

== Installation ==

1. Upload the `opennow` folder to `/wp-content/plugins/`, or upload the
   production ZIP in **Plugins > Add New > Upload Plugin**.
2. Activate **OpenNow CTA**.
3. Go to **Settings > OpenNow** and choose a named IANA timezone.
4. Set each weekday to closed or enter one opening and closing time. Add the
   open and closed labels and actions, then save.
5. Add `[opennow_cta]` or the **OpenNow CTA** block to a page.

A fresh activation is unconfigured and renders no CTA until a complete valid
configuration is saved. Invalid settings keep the last valid configuration and
show field-specific errors.

== Usage ==

Use the shortcode anywhere WordPress processes shortcodes:

`[opennow_cta]`

`[opennow_cta hide_status="1"]`

Only the exact `hide_status="1"` value hides the selected state's status. Other
attributes and enclosed content are ignored.

The dynamic `opennow/cta` block uses the same server renderer. It can override
the label, action, or status independently for its open and closed states. A
block can also hide a state's status; invalid or missing values fall back to
the corresponding global value. Removing all overrides restores the standard
block output.

== Schedule and timezone ==

Times use the saved business timezone, not the visitor, browser, server, or
WordPress site timezone. `UTC` and named IANA regions such as
`America/New_York` are valid; raw UTC offsets are not.

A period is half-open: the opening minute is open and the exact closing minute
is closed. A closing time earlier than the opening time continues overnight
into the next day and belongs to the day on which it starts. Equal opening and
closing times are invalid and do not mean 24-hour opening. PHP's timezone data
handles daylight-saving transitions; there is no separate DST setting.

== Appearance and accessibility ==

Global background and text colors apply to the shortcode and provide the
block defaults. Blank saved colors use `#166534` and `#FFFFFF`; nonblank colors
must be six-digit hexadecimal values and the pair must meet WCAG 2.2 AA
contrast for normal text. Blocks may use WordPress's built-in color and
typography controls for instance-specific styling.

The frontend CTA is a native keyboard-operable link with visible focus styling,
readable status text, safe wrapping, and no fake button role or live region.
The settings screen includes a keyboard-operable preview that never follows an
action.

== Caching ==

OpenNow checks the schedule when the shortcode or dynamic block renders.
Cached page HTML can therefore remain stale until the site's page-cache TTL.
If a site needs a maximum staleness of **N** minutes, set its cache TTL to no
more than **N** minutes or arrange boundary purges in the site's own caching
system. OpenNow does not manage cache variation or purges.

== Privacy ==

OpenNow makes no external requests and loads no remote scripts, fonts, images,
or stylesheets. It collects no visitor data, sets no cookies, and performs no
tracking or telemetry. A visitor can follow the configured CTA, and an
administrator can follow the clearly labeled Gasatrya hire or donation link on
**Settings > OpenNow**; those are ordinary user-initiated navigations.

== Deactivation and uninstall ==

Deactivation stops OpenNow behavior but keeps the saved settings for
reactivation. Uninstall deletes the OpenNow configuration and schema marker.
The plugin creates no custom tables, posts, pages, user metadata, or transient
business data. Reinstalling after uninstall starts unconfigured.

== Frequently Asked Questions ==

= Is OpenNow CTA a booking system? =

No. It displays a link that you configure. Point the open or closed action at
your existing booking, contact, phone, or other destination.

= Can open and closed states use different actions? =

Yes. Configure a separate label, action, and optional status for each state.

= Do overnight hours work? =

Yes. Enter a closing time earlier than the opening time, such as 22:00 to
02:00. The period carries into the following day.

= Can OpenNow write the next opening time for me? =

No. Status text is administrator-authored. Enter wording that is accurate for
your schedule and update it when your message changes.

= Can I configure holidays or multiple shifts? =

Not in this release. OpenNow has one weekly period per day and no holiday or
exception calendar.

= Why does a cached page show the previous state? =

The state is calculated when the page is rendered. A page cache can keep that
HTML until its TTL expires, so set an appropriate TTL or configure boundary
purges in the site's caching system.

= Does OpenNow support multisite? =

This release is designed for a single site. Multisite and network activation
are not claimed.

== Support ==

For questions, use the plugin page's Support tab once the plugin is published on WordPress.org.

For reproducible bugs and feature discussion, use [GitHub issues](https://github.com/gasatrya/opennow/issues).

== Source and build ==

The public source repository is [github.com/gasatrya/opennow](https://github.com/gasatrya/opennow).
The human-readable block source in `src/blocks/cta/index.js` corresponds to the
compiled `build/blocks/cta/index.js`; `src/blocks/cta/editor.scss` and
`src/blocks/cta/block.json` are included alongside it for reference.

From a clean checkout, reproduce the build and package with:

```
npm ci --ignore-scripts
npm run build
npm run build:check
npm run package:check
npm run package
```

The production package is `dist/opennow-0.1.0.zip`. Development dependencies,
tests, and build tooling remain in the public repository rather than the
production ZIP.

== Changelog ==

= 0.1.0 =
* Initial OpenNow CTA release.
