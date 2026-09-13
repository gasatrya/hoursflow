# OpenNow foundation and configuration

This document describes the installable foundation shipped in issue #3. It does
not add a settings screen or a frontend renderer.

## Bootstrap

`opennow.php` is the plugin entry point. It defines the global constants
`OPENNOW_VERSION` (`0.1.0`), `OPENNOW_PLUGIN_FILE`, and `OPENNOW_PLUGIN_DIR`,
loads the namespaced autoloader, registers activation/deactivation callbacks,
and schedules `OpenNow\Plugin::boot()` on `plugins_loaded`.

The plugin has no public layer yet. On the `plugins_loaded` callback it creates
and registers `OpenNow\Admin\Settings` only when WordPress reports an admin
request. The settings registration runs on `admin_init` and registers the
`opennow_config` option in the `opennow` group. It is an array setting, has a
strict validation callback, and is not exposed in REST responses. No settings
UI is created by this foundation.

## Stored shape

There is one atomic configuration option, `opennow_config`. A valid stored
value has exactly this shape (the values below are illustrative):

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
        'open' => array(
            'label' => 'Call Now',
            'action' => 'tel:+123456789',
            'status' => 'We are open.',
        ),
        'closed' => array(
            'label' => 'Book an Appointment',
            'action' => '/booking/',
            'status' => '',
        ),
    ),
    'appearance' => array(
        'background_color' => '',
        'text_color' => '',
    ),
)
```

The top-level keys, all seven weekday keys, both CTA state keys, and every
nested key shown above are required. A closed day has only `type`; a period has
only `type`, `opens`, and `closes`. Openings and closings are local `HH:MM`
values and may describe an overnight period, but they may not be equal. CTA
status is optional in meaning but remains present as a blank string in the
canonical shape. Blank colors select their defaults and remain blank in
storage; surrounding whitespace is trimmed, but disallowed data is not
rewritten into an allowed value.

The default effective colors are background `#166534` and text `#FFFFFF`.
They meet the WCAG AA normal-text contrast requirement of 4.5:1. A submitted
nonblank color must be an exact six-digit hexadecimal value, and the effective
pair must meet that same threshold.

## Runtime shape and revalidation

`OpenNow\Config\Repository` reads the option without writing to it. It always
returns this shape, even when the option is absent, legacy, or manually
corrupted:

```php
array(
    'timezone' => 'America/New_York', // or null when invalid/missing
    'schedule' => array(/* exactly monday through sunday */),
    'cta' => array(
        'open' => array(/* canonical label/action/status */), // or null
        'closed' => null,                                    // or canonical array
    ),
    'appearance' => array(
        'background_color' => '#166534',
        'text_color' => '#FFFFFF',
    ),
)
```

Each invalid or missing weekday is independently coerced to
`array('type' => 'closed')`. Each CTA state is independently revalidated and
becomes `null` when invalid. Runtime appearance values are always effective
colors: a blank color uses its individual default; a malformed value, or a
pair with insufficient contrast, uses the complete default pair. This salvage
is in memory only and never repairs the WordPress option.

Named IANA timezone identifiers are required; `UTC` is valid, while raw UTC
offsets are not. Supported actions are exactly root-relative URLs, complete
`https://` URLs with a valid hostname and no credentials, or the supported
`tel:` form. Labels and status text remain plain text and reject angle brackets.

## Atomicity and lifecycle

The Settings API callback validates the complete submission before WordPress
persists it. Any error adds field-specific settings errors and returns the
exact existing option, or `false` when the option does not exist. A valid
submission returns one canonical array. No partial update or read-time repair
is performed.

Activation is repeat-safe. It adds only the
`opennow_schema_version` marker with value `1` when that marker does not
already exist, using non-autoloaded marker storage. Activation never creates a
configuration, posts, pages, or external requests. Deactivation is a no-op and
retains the configuration. Uninstall removes `opennow_config` and
`opennow_schema_version`; reinstalling then starts unconfigured.

This MVP does not evaluate schedules, render frontend output, make remote
requests, or vary/cache page output. When a later renderer is added, cached
HTML may remain stale until the site's normal page-cache TTL or an externally
managed boundary purge.
