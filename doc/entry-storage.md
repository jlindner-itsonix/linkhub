# Entry storage

## What / why

The admin configures an arbitrary list of link entries (label, URL, "show in
menu", "show in popup"). This is the single source of truth every other
feature (menu sync, popup rendering, menu-icon rendering) reads from.

Two extra rules exist because of the module's history:
- **Legacy migration** — the first version of this module (`itsonix.xwiki` /
  `itsonix.appswitcher`, before they were merged) stored a single `mode`
  field (`menu`/`popup`/`both`) per entry instead of two booleans. Old stored
  data still has to load correctly.
- **`external` is derived, not stored** — whether a URL is "external"
  (different domain → open in a new tab) is computed from the URL itself
  every time, so it can never drift out of sync with the URL.

## How it works

- `Config::getEntries()` reads the `ENTRIES` module option (one serialized
  array), applies the legacy-`mode` migration if a row still has it, derives
  `external` via `preg_match('~^https?://~i', $url)`, and drops rows with an
  empty URL.
- `Config::setEntries(array $entries)` writes the array back
  (`array_values()`'d, to keep it a plain indexed list).
- Fresh installs with no stored option get a hardcoded default list (XWiki as
  a menu entry, AWU + SkillDB as popup entries) — this mirrors what the two
  predecessor modules shipped before the merge.

## Tests

`tests/ConfigTest.php`:
- `testFreshInstallDefaultsMirrorThePredecessorModules` — no stored option →
  the three documented defaults, correct flags and `external` per entry.
- `testGetEntriesDropsRowsWithEmptyUrl` — a row with a blank/whitespace URL
  never comes back out.
- `testSetEntriesRoundTripsShowFlagsAndDerivesExternal` — write → read gives
  back the same flags, and `external` matches the URL's scheme/host shape.
- `testLegacyModeFieldIsMigratedToShowInMenuAndShowInPopup` — old-format rows
  (`mode` = `menu`/`popup`/`both`) migrate to the correct
  `showInMenu`/`showInPopup` combination.

## Related

- [global-toggles.md](global-toggles.md) — the module-wide switches that gate
  whether these entries render at all.
- [menu-sync.md](menu-sync.md) and [popup-rendering.md](popup-rendering.md) —
  consumers of `Config::getEntries()`.
