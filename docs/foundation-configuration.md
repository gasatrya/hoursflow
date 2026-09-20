# OpenNow configuration and runtime foundation

OpenNow is an installable WordPress plugin. Its entry point is `opennow.php`.
The entry point defines the `OPENNOW_*` constants, loads the namespaced
autoloading layer, registers activation/deactivation callbacks, and boots the
plugin after `plugins_loaded`. Frontend services are always available; the
Settings API screen is registered only during an admin request.

The user-facing installation and support disclosures are in [`README.md`](../README.md)
and [`readme.txt`](../readme.txt). The normative behavior contract is in
[`mvp-behavior-support-contract.md`](mvp-behavior-support-contract.md).

## Configuration

The Settings API registers one atomic, non-REST option: `opennow_config` in
the `opennow` group. A successful submission is validated completely before
WordPress persists it. Invalid submissions return the exact existing option
(or `false` when it does not exist), add field-specific settings errors, and
never partially update the option.

A valid value has exactly this shape:

```php
array(
    'timezone' => 'America/New_York',
    'schedule' => array(
        'monday' => array('type' => 'period', 'opens' => '09:00', 'closes' => '17:00'),
        'tuesday' => array('type' => 'closed'),
        'wednesday' => array('type' => 'closed'),
        'thursday' => array('type' => 'closed'),
        'friday' => array('type' => 'closed'),
        'saturday' => array('type' => 'closed'),
        'sunday' => array('type' => 'closed'),
    ),
    'cta' => array(
        'open' => array('label' => 'Call Now', 'action' => 'tel:+123456789', 'status' => ''),
        'closed' => array('label' => 'Book an Appointment', 'action' => '/booking/', 'status' => ''),
    ),
    'appearance' => array(
        'background_color' => '',
        'text_color' => '',
    ),
)
```

There must be exactly seven weekday entries, exactly one period or a closed
entry per day, and both CTA states. Times are local `HH:MM` values. A closing
time earlier than its opening time is overnight; equal times are invalid.
Actions are root-relative URLs, HTTPS URLs, or supported `tel:` values. Labels
and status are plain text. Optional colors are six-digit hex values or blank;
blank values use the accessible plugin defaults and a low-contrast pair falls
back to the complete default pair. The settings screen uses native color
pickers: a picker displays its corresponding plugin default when a persisted
value is blank, while the legacy blank remains valid and is preserved by the
validator until the administrator saves a color.

The settings screen also renders a page-scoped live preview beside the form and
stacks it below the form at narrow admin widths. Its keyboard-operable Open and
Closed controls select a preview state independently of the weekly schedule.
The preview reads current unsaved label, optional status, and global color
fields, applies the default color pair when a color is blank, and inserts copy
as text. It deliberately omits the action and uses non-link markup, so it cannot
navigate or perform the configured action. The same page-scoped sidebar places
a distinct developer promotion after the preview in DOM and visual order. Its
translated, escaped Hire Me and donation links open only after administrator
activation, use protected new browsing contexts, and load no remote assets or
embedded content. A WordPress.org review link remains absent until the OpenNow
listing and canonical review destination are confirmed.

`OpenNow\Config\Repository` revalidates every stored section in memory and
never repairs the option. Missing or invalid days become closed, invalid CTA
states become unavailable, invalid timezones make evaluation closed, and
invalid appearance data uses the default color pair.

Block content overrides are not part of the global option. The dynamic block
may store a sparse `overrides` attribute with optional `open` and `closed`
objects and optional `label`, `action`, `status`, and `hideStatus` fields.
`hideStatus` is retained only when it is the strict boolean `true`; false,
strings, numbers, arrays, and other invalid values are ignored independently.
The runtime canonicalizes each content field using the same plain-text and
action rules as global CTA values. A valid field replaces only the selected
state's matching global field; a missing or invalid field falls back
independently. An explicitly present blank status is valid and suppresses the
global status. `hideStatus: true` suppresses the selected status even when the
global or overridden status is nonblank; the other state's overrides never
apply.

## Frontend services

The shortcode is:

```text
[opennow_cta]
[opennow_cta hide_status="1"]
```

Only the exact string `hide_status="1"` is recognized. It forwards a
`hideStatus: true` override for both states, so the shared renderer hides only
the state selected at runtime. Other values or malformed attribute containers
are a no-op, and all other attributes and content remain ignored.

The dynamic block is `opennow/cta`. It uses the same
`OpenNow\Frontend\Renderer` as the shortcode, so saved global settings produce
the same server-rendered output when no block overrides are present. Its editor
uses a server-side-rendered preview of the current output. The renderer first
requires a valid selected global CTA, then applies only valid selected-state
content and status-visibility overrides. `hideStatus: true` wins over a
nonblank global or status override, while an explicitly blank status override
retains its legacy suppression behavior. Schedules, appearance colors, and
frontend styles remain global for both integrations.

The renderer evaluates the current absolute instant in the saved named IANA
timezone, selects only the matching open or closed CTA, applies only valid
selected-state block fields after validating the selected global CTA, escapes
output, and conditionally enqueues the local shared stylesheet after valid
markup is built.

The weekly-hours settings UI renders one semantic fieldset per weekday with
visible translated Open/Closed state text, explicit opening and closing labels,
and keyboard-orderable controls. Closed days disable and remove the required
state from their time inputs; each day has its own bordered group and the time
rows stack at narrow admin widths. Schedule and preview initializers are
independent: either interface remains functional when the other interface's
markup is absent, and preview state changes never mutate schedule controls.

The plugin has no browser polling, AJAX, REST polling, cache variation, or
scheduled purge. Cached HTML can therefore be stale until the site's normal
page-cache TTL; configure that TTL to the maximum staleness the site requires
or arrange a boundary purge outside OpenNow.

## Lifecycle

Activation is repeat-safe and creates only the non-autoloaded schema marker.
It does not create content or make requests. Deactivation is a no-op that
retains settings for reactivation. Uninstall deletes the configuration and
schema marker. Direct PHP execution is guarded and no custom tables, posts,
visitor data, cookies, tracking, telemetry, or external assets are used.
