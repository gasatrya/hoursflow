# Changelog

All notable OpenNow CTA changes are documented here.

## 0.1.0 - 2026-09-13

- Initial release of the schedule-aware OpenNow CTA shortcode and dynamic block.
- Added timezone-safe weekly and overnight schedule evaluation.
- Added atomic settings validation, global accessible colors, and lifecycle data
  retention/deletion behavior.
- Added backward-compatible per-block, per-state CTA content overrides with
  independent global fallback and explicit blank-status suppression.
- Added explicit per-state status hiding for blocks and the exact
  `[opennow_cta hide_status="1"]` shortcode attribute, with selected-state
  semantics and legacy blank-status compatibility.
- Added a real server-rendered block-editor preview while keeping schedules,
  global colors, styles, and shortcode behavior unchanged.
- Added reproducible quality checks, WordPress integration coverage, and a
  deterministic production package.
