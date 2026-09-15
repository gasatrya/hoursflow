# OpenNow release checklist

Use this checklist from a clean checkout before publishing a package. It does
not authorize a WordPress.org submission, Git tag, or hosted release.

## Automated gates

1. Install locked dependencies and run the repository checks:

   ```bash
   composer install --no-interaction --prefer-dist
   npm ci --ignore-scripts
   composer check:php
   npm run check
   ```

2. Confirm CI passes without allowed failures on every declared lane:

   - WordPress 6.6.7 / PHP 7.4
   - WordPress 6.6.7 / PHP 8.3
   - WordPress 7.0.4 / PHP 7.4
   - WordPress 7.0.4 / PHP 8.3
   - WordPress 7.0.4 / PHP 8.5

3. Confirm CI tests the extracted production package, records the exact
   WordPress/PHP/PHPUnit versions, and publishes
   `opennow-0.1.0.zip` only after every required job passes.

4. Confirm `npm run build:check`, `npm run i18n:pot:check`, and
   `npm run package:check` report reproducible output. Inspect the ZIP manifest:
   it must have one `opennow/` root, contain runtime PHP, local assets, generated
   block files (including editor CSS and its RTL companion), `languages/opennow.pot`,
   and release documents, and omit tests,
   development source, dependency directories, repository tooling, and lock
   files.

## Manual installation and runtime checks

Use a clean single-site WordPress installation with `WP_DEBUG` enabled.

1. Upload and activate the generated ZIP without Composer or npm dependencies.
   Confirm there are no PHP warnings, missing-asset requests, or debug-log
   entries.
2. Confirm a fresh activation renders no CTA before settings are saved.
3. Save a valid timezone, all seven weekdays, both CTA states, and blank default
   colors. Confirm `[opennow_cta]` and an override-free block show equivalent
   markup and state. Confirm `[opennow_cta hide_status="1"]` hides the selected
   status, while non-exact values are no-ops and all other shortcode attributes
   and content remain ignored. In the block editor, verify the real
   server-rendered preview, independent open/closed field overrides, the
   per-state `hideStatus: true` toggle, global fallback for omitted or invalid
   fields, hide priority over nonblank global/status overrides, explicit
   blank-status suppression, state isolation, and pruning back to the legacy
   empty delimiter.
4. Check an exact opening instant, an exact closing instant, a closed weekday,
   an overnight carry into the next day, Sunday-to-Monday rollover, a different
   site/business timezone pair, and representative spring-forward and fall-back
   instants. The automated evaluator suite is authoritative for these results.
5. Confirm invalid configuration is rejected atomically and the previous valid
   settings remain active.
6. View pages without a CTA and confirm OpenNow frontend CSS is absent. View a
   page with a valid shortcode or block and confirm only the unchanged local
   frontend CTA stylesheet is loaded. In the editor, confirm the generated
   editor stylesheet disables CTA navigation without changing public CSS.
   Confirm the settings script appears only on **Settings → OpenNow** for a user
   with `manage_options`.
7. Inspect browser network/storage panels and confirm no plugin-originated remote
   requests, cookies, local storage, tracking, polling, or telemetry.

## Manual accessibility checks

Test the settings screen and frontend with keyboard-only navigation at minimum;
a screen reader check is recommended for the release environment.

1. Reach every settings control in a logical keyboard order. Confirm each
   control has a programmatic label and its instructions are announced.
2. Submit representative errors. Confirm focus remains usable, errors are
   visible and announced as an alert, invalid controls expose `aria-invalid`,
   and descriptions/errors are associated with those controls.
3. Toggle each **Closed all day** checkbox by keyboard. Confirm its opening and
   closing controls become disabled or enabled without trapping focus.
4. Tab to the frontend CTA. Confirm it is a native link with a visible,
   non-empty accessible name and an obvious focus indicator in default, hover,
   and focused states. Confirm the link target is at least 44x44 CSS pixels and
   the focus indicator remains visible and unclipped. Status text must remain
   visible text, have a clear separation gap, not be an `aria-live`
   announcement, and state must not be communicated by color alone.
5. Confirm default colors and a representative accepted custom pair meet WCAG
   2.2 AA contrast for normal text (4.5:1). Check the focus indicator against
   surrounding light and dark content. Themes/custom CSS must be checked again
   because plugin validation cannot govern later overrides.
6. At a 320px viewport and with text enlarged, confirm labels, errors, CTA
   text, and status text wrap within the viewport without horizontal overflow.
   Focus indicators must remain perceivable and no control may require pointer
   input.

## Metadata and lifecycle

1. Confirm version `0.1.0`, WordPress minimum `6.6`, PHP minimum `7.4`, tested-up-to
   `7.0`, text domain `opennow`, and GPL-2.0-or-later metadata agree across the
   plugin header, readmes, changelog, package metadata, and POT file.
2. Deactivate and reactivate; configuration must remain. Uninstall; both
   `opennow_config` and `opennow_schema_version` must be deleted. Reinstalling
   must start unconfigured.
3. Re-read the public caching, limitations, privacy, external-service, and data
   retention disclosures before release. Do not raise compatibility claims or
   add submission/update-service claims without a separate authorized change.
