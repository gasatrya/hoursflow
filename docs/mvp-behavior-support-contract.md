# OpenNow CTA MVP behavior and support contract

Status: normative implementation target for the MVP

Scope authority: [`plugin-concept.md`](../plugin-concept.md) defines the product scope; this document resolves behavior within that scope.

`MUST`, `MUST NOT`, and `SHOULD` are requirements for implementation and tests.

## 1. Platform target

The MVP targets:

- WordPress 6.6 or newer.
- PHP 7.4 or newer.

These are target minimums, not release claims, until CI demonstrates them. The initial matrix, selected on 2026-09-13 from the official WordPress [requirements](https://wordpress.org/about/requirements/), [PHP compatibility table](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/), and [release archive](https://wordpress.org/download/releases/), is:

| WordPress patch | PHP release line | Purpose |
| --- | --- | --- |
| 6.6.7 | 7.4.x | Minimum supported combination |
| 6.6.7 | 8.3.x | Minimum WordPress on a modern PHP version supported by that branch |
| 7.0.4 | 7.4.x | Current WordPress release line with the PHP minimum |
| 7.0.4 | 8.3.x | Current WordPress with the recommended PHP baseline |
| 7.0.4 | 8.5.x | Current WordPress with the newest PHP release line it supports |

CI MUST record the exact patch versions used and use the newest available patch in each listed release line. Before release, metadata and documentation MUST claim only the minimums and combinations that pass required automated checks. An allowed-failure or experimental job proves no compatibility. Updating current-version lanes does not lower the minimums, but raising either minimum requires an explicit contract change.

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

## 5. Rendering and invalid configuration

- The current schedule state selects exactly the matching open or closed CTA. The plugin MUST NOT substitute the other state's CTA.
- A missing or invalid business timezone deterministically selects the closed state. If the closed CTA is valid, it renders; otherwise nothing renders. Invalid timezone configuration never falls back to another timezone.
- A missing, closed, or invalid weekday entry starts no period for that weekday. A valid previous-day overnight period may still apply.
- If required content for the selected state is missing or invalid, the shortcode and block render no frontend markup. They MUST fail safely without warnings or fatal errors.
- Invalid saved style values fall back to plugin defaults and do not suppress otherwise valid CTA content.
- Saved values MUST be revalidated at runtime so manually corrupted or legacy option data cannot bypass these rules.

A settings submission is atomic. If any submitted timezone, schedule entry, required CTA field, action, nonblank color, or effective color pair is invalid, none of the submitted OpenNow settings is persisted, and the administrator receives a field-specific error. Trimming surrounding whitespace is the only normalization that may affect validation; disallowed content is not silently rewritten.

A fresh activation is unconfigured and renders nothing until valid settings are saved. The settings UI may preselect the current WordPress site timezone for convenience, but that value does not become configuration until the administrator saves it.

## 6. Styling boundary

The MVP provides only two optional global appearance controls, shared by the shortcode and every block instance:

- CTA link background color.
- CTA link text color.

A blank control selects its plugin default; a nonblank value must match `#[0-9A-Fa-f]{6}` exactly after surrounding whitespace is trimmed. The settings UI MUST reject an effective text/background pair that does not meet WCAG 2.2 AA contrast for normal text. The default pair MUST meet the same threshold. During runtime revalidation, if either stored value is malformed or the effective pair has insufficient contrast, the complete default pair is used.

The plugin supplies minimal layout, hover, and visible keyboard-focus styling. It MUST NOT expose typography, font loading, dimensions, spacing, borders, shadows, animation, responsive layout, per-state styles, or per-block style overrides.

Themes may customize stable public hooks without editing plugin files:

- `.opennow-cta`
- `.opennow-cta--open` or `.opennow-cta--closed`
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
- Status text, when present, is visibly grouped with the CTA and rendered as text. The plugin does not add a hidden open/closed announcement; administrators are responsible for labels and status copy that communicate the intended action. Plugin styling MUST NOT use color as the only distinction between otherwise identical state content.
- Every plugin-authored user-facing PHP or JavaScript string MUST be translatable with the `opennow` text domain, including block-editor and validation messages. Weekday labels in administration MUST use WordPress locale data; stored weekday keys and `HH:MM` values remain locale-independent. Administrator-authored CTA copy is displayed as entered and is not automatically translated.
- All output MUST be escaped for its context. Administrative writes require capability checks, nonce verification, sanitization, and validation.
- No lifecycle hook, administration screen, scheduled task, or frontend execution makes an external request or loads a remote asset. The plugin collects no visitor data, sets no cookies, and performs no tracking, analytics, or telemetry. A visitor following an administrator-configured HTTPS CTA is ordinary user-initiated navigation and the sole exception.

Anything not defined here remains outside the MVP unless this contract and the product concept are deliberately revised.
