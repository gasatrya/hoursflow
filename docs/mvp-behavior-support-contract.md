# OpenNow CTA MVP behavior and support contract

Status: normative implementation target for the MVP

Scope authority: [`plugin-concept.md`](../plugin-concept.md) defines the product scope; this document resolves behavior within that scope.

`MUST`, `MUST NOT`, and `SHOULD` are requirements for implementation and tests.

## 1. Platform target

The MVP targets:

- WordPress 6.6 or newer.
- PHP 7.4 or newer.

These remain broad target minimums rather than the tested release gate. The required integration gate is intentionally one lane, selected from the official WordPress [requirements](https://wordpress.org/about/requirements/), [PHP compatibility table](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/), and [release archive](https://wordpress.org/download/releases/):

| WordPress release line | PHP release line | Purpose |
| --- | --- | --- |
| 7.1 (pinned to 7.1.1) | 8.5.x | Only required integration release gate |

CI MUST record the exact WordPress patch and PHP versions used by this gate. This single lane is not exhaustive compatibility coverage and does not prove every combination within the WordPress 6.6+ and PHP 7.4+ target ranges. Before release, metadata and documentation MUST claim only the minimums and combinations that pass required automated checks. Raising either minimum requires an explicit contract change.

## 2. Weekly schedule

- There is one business profile and one weekly schedule.
- Each Monday-through-Sunday entry is either **closed** or has exactly one opening time and one closing time, stored at minute precision as local `HH:MM` values.
- A valid period is half-open: `[opening, closing)`. The business is open at the exact opening instant and closed at the exact closing instant.
- Equal opening and closing times are invalid; they do not mean 24-hour opening. The MVP has no 24-hour-day setting.
- When closing is later than opening, the period ends on the same weekday.
- When closing is earlier than opening, the period is overnight and ends on the following weekday. The period belongs to the weekday on which it opens.
- A closed weekday starts no period of its own. It may still be open after midnight because of the previous weekday's valid overnight period.
- At any instant, the business is open if any applicable valid period contains that instant. Adjacent or overlapping periods therefore form an open union.
- No exceptions, holidays, date ranges, split shifts, or multiple daily periods exist in the MVP.

Examples:

| Configuration | Instant in business timezone | Result |
| --- | --- | --- |
| Monday `09:00–17:00` | Monday `09:00` | Open |
| Monday `09:00–17:00` | Monday `16:59:59` | Open |
| Monday `09:00–17:00` | Monday `17:00` | Closed |
| Wednesday closed, with no Tuesday carry-over | Wednesday noon | Closed |
| Sunday `22:00–02:00`, Monday closed | Sunday `22:00` | Open |
| Sunday `22:00–02:00`, Monday closed | Monday `01:59:59` | Open from Sunday's period |
| Sunday `22:00–02:00`, Monday closed | Monday `02:00` | Closed; this also defines week rollover |
| Tuesday `22:00–02:00`; Wednesday `01:00–03:00` | Wednesday `02:00` | Open from Wednesday's period after Tuesday's closes |
| Tuesday `22:00–02:00`; Wednesday `01:00–03:00` | Wednesday `03:00` | Closed |

## 3. Timezone and daylight saving time

- The administrator MUST select one named IANA timezone (for example, `America/New_York` or `Asia/Jakarta`). `UTC` is valid. Raw UTC offsets are not valid business timezones because they cannot represent daylight-saving rules.
- Evaluation uses the current absolute instant converted to the saved business timezone. It MUST NOT use the visitor, browser, web-server, or WordPress site timezone as an implicit fallback.
- Changing the WordPress site timezone does not change a previously saved business timezone.
- Weekly times are recurring local wall-clock times. Schedule membership is determined from the localized weekday and wall-clock `HH:MM` at each absolute instant. The installed PHP timezone database supplies offset and daylight-saving transitions.
- During a spring-forward gap, nonexistent wall-clock minutes are never evaluated. A period whose opening falls in the gap first becomes open at the first existing local minute inside its configured half-open interval. A period whose closing falls in the gap is closed at the first existing local minute at or after that boundary.
- During a fall-back overlap, both occurrences of the same weekday and `HH:MM` receive the same open/closed result.
- The MVP provides no DST override or ambiguity control. Tests MUST cover opening and closing boundaries in a spring gap and a repeated time in a named DST-observing zone such as `America/New_York`.

## 4. CTA content and actions

Open and closed states each have:

1. a required plain-text label containing at least one non-whitespace character after trimming;
2. a required action; and
3. optional plain-text status text.

Labels and status text MUST be stored and rendered without HTML. A submitted label or status containing `<` or `>` is invalid rather than rendered or silently converted to text. The status text is literal administrator-authored copy for that state. It is not generated from the schedule and has no placeholders. A blank value is omitted without an empty element. Copy such as “We reopen tomorrow at 9 AM” is allowed but is not verified or updated by the plugin; the administrator is responsible for ensuring it is true whenever that state is shown.

Only these action forms are valid:

- A root-relative same-site URL beginning with exactly one slash, such as `/booking/` or `/contact/?from=cta#form`.
- An absolute `https://` URL with a non-empty host, such as `https://example.com/book` or an HTTPS WhatsApp link.
- A `tel:` URI whose value starts with an optional `+` followed by a digit and then contains only digits, spaces, `.`, `-`, `(`, or `)`, such as `tel:+123456789` or `tel:+1 (234) 567-8900`.

Actions MUST be trimmed, validated as a complete value, and escaped as a URL when rendered. Validation occurs before output escaping; sanitization MUST NOT transform a disallowed action into an allowed one. Control characters, backslashes, protocol-relative URLs (`//example.com`), credentials in absolute URLs, and empty values are invalid. Whitespace is invalid in root-relative and HTTPS actions; ASCII spaces are allowed only inside a valid `tel:` value. An HTTPS action requires a standards-based parser to identify the `https` scheme, a non-empty ASCII or punycode hostname, no username or password, and a valid optional port. Unicode hostnames must be supplied in punycode form.

Rejected schemes include `javascript:`, `data:`, `file:`, `http:`, `mailto:`, and `sms:`. Bare relative forms such as `booking/` are also rejected. No action opens a new window by default.

The dynamic `opennow/cta` block MAY store a sparse `overrides` object with optional `open` and `closed` state objects. Each state MAY contain only `label`, `action`, `status`, and `hideStatus` fields. Runtime canonicalization MUST ignore malformed containers, unknown states or fields, wrong types, and invalid field values independently. `hideStatus` MUST be retained only when it is the strict boolean `true`; false, strings, numbers, arrays, and other values are invalid and have no effect. A valid content field is trimmed and replaces only that field for the selected state; every missing or invalid content field falls back to the matching global field. A valid explicitly blank `status` override MUST suppress the global status. A selected-state `hideStatus: true` MUST suppress a nonblank global or overridden status and MUST NOT affect the other state. The block editor MUST add enabled fields sparsely and prune empty states and the empty overrides object when fields are disabled.

The shortcode APIs are `[opennow_cta]` and `[opennow_cta hide_status="1"]`. Only the exact string value `hide_status="1"` MUST enable hiding. Other values, malformed attribute containers, all other attributes, and enclosed content MUST be ignored. When enabled, the shortcode forwards `hideStatus: true` for both states; the renderer still applies only the selected state. The shortcode without the attribute MUST remain equivalent to the legacy renderer call.

## 5. Rendering and invalid configuration

- The current schedule state selects exactly the matching open or closed CTA. The plugin MUST NOT substitute the other state's CTA.
- For a block, valid content overrides replace only fields in the selected state. The renderer MUST first revalidate and require the selected global CTA to be complete; overrides MUST NOT rescue an invalid selected global CTA. Missing or invalid override fields fall back independently, while a valid blank status suppresses the global status. A canonical selected-state `hideStatus: true` suppresses status output regardless of the global or overridden status value and has no effect on the unselected state. The hide control MUST NOT change the CTA action, label, markup, classes, CSS, escaping, or accessibility behavior.
- With no overrides, the block MUST retain the legacy serialized delimiter and exact global-rendered markup. The editor preview MUST use the server-rendered block output for the current state; it may be empty when the selected global CTA is invalid.
- A missing or invalid business timezone deterministically selects the closed state. If the closed CTA is valid, it renders; otherwise nothing renders. Invalid timezone configuration never falls back to another timezone.
- A missing, closed, or invalid weekday entry starts no period for that weekday. A valid previous-day overnight period may still apply.
- If required content for the selected state is missing or invalid, the shortcode and block render no frontend markup. They MUST fail safely without warnings or fatal errors.
- Invalid saved style values fall back to plugin defaults and do not suppress otherwise valid CTA content.
- Saved values MUST be revalidated at runtime so manually corrupted or legacy option data cannot bypass these rules.

A settings submission is atomic. If any submitted timezone, schedule entry, required CTA field, action, nonblank color, or effective color pair is invalid, none of the submitted OpenNow settings is persisted, and the administrator receives a field-specific error. Trimming surrounding whitespace is the only normalization that may affect validation; disallowed content is not silently rewritten.

A fresh activation is unconfigured and renders nothing until valid settings are saved. The settings UI may preselect the current WordPress site timezone for convenience, but that value does not become configuration until the administrator saves it.

## 6. Styling boundary

The MVP provides two optional global appearance controls. They apply to the shortcode and provide the default for every block instance:

- CTA link background color.
- CTA link text color.

Block content and state overrides MUST NOT change these global defaults or any
frontend style, class, or stylesheet behavior. Independently of content
overrides, block instances MAY use WordPress's built-in color supports to
override the CTA link text and background colors for that instance. The color
support classes and custom styles MUST be applied to the CTA link rather than
the surrounding status wrapper. A missing individual block color MUST retain
its corresponding global color. Block instances MAY also use
WordPress's built-in typography supports for font size, font family, font
weight, font style, line height, letter spacing, and text transform. Typography
attributes MUST apply to the rendered CTA wrapper. Color and typography
attributes MUST appear in the server-rendered editor preview, MUST remain scoped
to that block instance, and MUST NOT change shortcode output.

A blank globally persisted value selects its plugin default; a nonblank global value must match `#[0-9A-Fa-f]{6}` exactly after surrounding whitespace is trimmed. The native settings color picker MUST display the corresponding plugin default when a persisted value is blank. Legacy blank values MUST remain accepted by validation and use that default at runtime until an administrator saves an explicit picker value. The settings UI MUST reject an effective global text/background pair that does not meet WCAG 2.2 AA contrast for normal text. The default pair MUST meet the same threshold. During runtime revalidation, if either stored global value is malformed or the effective global pair has insufficient contrast, the complete default pair is used. Per-block colors use WordPress palette presets or custom-color serialization and its native contrast warning; editors remain responsible for ensuring the selected per-block pair has sufficient contrast.

The plugin supplies a tightly bounded layout baseline, hover, and visible keyboard-focus styling. The link MUST use compact button-like internal spacing, a minimum 44x44 CSS-pixel target, modest corner rounding, centered text, and wrapping safeguards. Status text MUST remain block-level, have a modest separation gap, and wrap safely. The baseline inherits theme typography unless a block instance uses the supported WordPress typography controls. The plugin does not load fonts; font-family choices are limited to fonts supplied by WordPress, the active theme, or the site. The plugin MUST NOT expose controls for fixed dimensions, spacing, borders, shadows, animation, responsive layout, or per-state styles.

The admin weekly-hours editor MUST keep each weekday in a bordered semantic fieldset with a localized weekday legend, explicit opening and closing labels, visible translated Open/Closed text, and aligned time rows. Its Closed control MUST accurately reference both time input IDs with `aria-controls`; closed inputs MUST be disabled and not required, while open inputs MUST be required and not disabled. The local admin stylesheet MUST keep these groups within the viewport and stack each time row at widths of 782 CSS pixels or less. The settings page MUST provide a labeled live CTA preview beside the form at wider widths and in a stacked position at narrow widths. Native Open and Closed controls MUST expose their selected state, select a variant independently of the schedule, and have at least 44x44 CSS-pixel targets with a visible focus indicator. The preview MUST reflect current unsaved labels, optional status, and global colors, use the documented defaults for blank colors, preserve safe wrapping, and remain visual-only non-link markup that cannot activate an action. A visually distinct developer promotion MUST follow the preview within the same sidebar in DOM and visual order, and the complete sidebar MUST stack below the form at widths of 782 CSS pixels or less without horizontal overflow. Promotion links MUST use translated, escaped text and URLs, protected new browsing contexts, understandable accessible names, and visible focus indicators. The card MUST load no remote assets or embedded content; its Gasatrya hire and donation destinations are contacted only after administrator activation. A review link MUST remain absent unless the canonical OpenNow WordPress.org review page is confirmed. Sidebar assets and markup MUST remain scoped to the authorized OpenNow settings page. Themes may customize stable public hooks without editing plugin files:

- `.opennow-cta`
- `.opennow-cta--open`
- `.opennow-cta--closed`
- `.opennow-cta__link`
- `.opennow-cta__status`

The plugin is not responsible for contrast failures introduced later by theme or custom CSS. Focus indication MUST remain visible, and open/closed state MUST NOT be communicated by color alone.

## 7. Caching

State is evaluated server-side when the shortcode or dynamic block renders. The MVP does not use browser timers, AJAX, REST polling, cache variation, scheduled cache purges, or integrations with page caches/CDNs.

Consequently, cached HTML may remain stale for up to the page-cache TTL. Documentation MUST disclose this limitation. A site requiring a maximum staleness of N minutes must configure a TTL of no more than N minutes or arrange boundary purges in its caching system; OpenNow performs neither operation. The plugin itself MUST NOT cache the computed open/closed result across requests.

## 8. Lifecycle and data retention

- Activation MUST be safe to repeat and MUST NOT create posts/pages or make external requests. A fresh activation with no retained settings starts unconfigured and may store only a schema/version marker needed for migration; reactivation MUST preserve retained configuration.
- Deactivation stops plugin behavior but retains settings so reactivation restores them.
- Uninstall permanently removes every OpenNow option and schema/version value. The MVP creates no custom tables, content, user metadata, or transient business data.
- Reinstalling after uninstall starts unconfigured. There is no “retain on uninstall” setting in the MVP.

The MVP support contract is single-site WordPress only; multisite and network activation are not claimed. Lifecycle and settings mutations require the appropriate WordPress capability and nonce protection when initiated by an administrator. Direct execution of PHP files MUST be blocked.

## 9. Accessibility, internationalization, and privacy

- The CTA uses a native link with a visible, non-empty label. It MUST remain keyboard operable and have a visible focus indicator. It MUST NOT use a fake button role or an `aria-live` region for server-rendered status.
- The block editor MUST show the actual server-rendered preview and provide translated open/closed per-field controls, including a per-state `Hide status` toggle. Enabling a content field stores an explicit value, including `status: ''`; enabling the hide toggle stores `hideStatus: true`; disabling either removes only that field and prunes empty containers. The status override and hide toggle remain independent and may coexist.
- The settings weekly-hours controls MUST be reachable in keyboard order. Each day MUST expose its translated Open/Closed state as visible text in addition to any visual modifier, and toggling Closed MUST update only that day's inputs, required/disabled state, class, data state, and state text.
- The settings preview Open and Closed buttons MUST be keyboard operable and announce selection through their pressed state. Switching or editing the preview MUST NOT mutate weekly-hours controls. Administrator-entered preview label and status copy MUST be inserted as text, never HTML, and missing preview elements MUST NOT prevent weekly-hours initialization.
- Status text, when present, is visibly grouped with the CTA and rendered as text. The plugin does not add a hidden open/closed announcement; administrators are responsible for labels and status copy that communicate the intended action. Plugin styling MUST NOT use color as the only distinction between otherwise identical state content.
- Every plugin-authored user-facing PHP or JavaScript string MUST be translatable with the `opennow` text domain, including block-editor and validation messages. Weekday labels in administration MUST use WordPress locale data; stored weekday keys and `HH:MM` values remain locale-independent. Administrator-authored CTA copy is displayed as entered and is not automatically translated.
- All output MUST be escaped for its context. Administrative writes require capability checks, nonce verification, sanitization, and validation.
- No lifecycle hook, administration screen, scheduled task, or frontend execution makes a background external request or loads a remote asset. The plugin collects no visitor data, sets no cookies, and performs no tracking, analytics, or telemetry. Ordinary user-initiated navigation is the only exception: a visitor may follow an administrator-configured HTTPS CTA, and an administrator may follow the clearly labeled Gasatrya hire or donation links on the OpenNow settings page.

Anything not defined here remains outside the MVP unless this contract and the product concept are deliberately revised.
