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
- Fixed the editor preview transport so nested boolean status-hiding flags
  remain booleans during server-side rendering.
- Added a real server-rendered block-editor preview while keeping schedules and
  shortcode behavior unchanged.
- Added WordPress's built-in per-block typography controls and applied their
  generated classes and styles to the dynamic CTA wrapper and editor preview.
- Added built-in per-block text and background color controls that override the
  global defaults on that CTA link without changing shortcode output.
- Added reproducible quality checks, WordPress integration coverage, and a
  deterministic production package.
