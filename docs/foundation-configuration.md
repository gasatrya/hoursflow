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
back to the complete default pair.

`OpenNow\Config\Repository` revalidates every stored section in memory and
never repairs the option. Missing or invalid days become closed, invalid CTA
states become unavailable, invalid timezones make evaluation closed, and
invalid appearance data uses the default color pair.

## Frontend services

The shortcode is:

```text
[opennow_cta]
```

The dynamic block is `opennow/cta`. It stores no user attributes and uses the
same `OpenNow\Frontend\Renderer` as the shortcode, so saved settings produce
the same server-rendered output for both integrations. The renderer evaluates
the current absolute instant in the saved named IANA timezone, selects only
the matching open or closed CTA, escapes output, and conditionally enqueues
the local shared stylesheet after valid markup is built.

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
