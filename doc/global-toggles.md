# Global menu/popup toggles

## What / why

Two portal-wide switches let the admin turn the entire menu feature or the
entire popup feature off without deleting any entries — e.g. to temporarily
disable the popup while keeping the configured entries for later.

They default differently on purpose: **menu defaults ON**, **popup defaults
OFF** — the menu feature is the module's primary/original purpose (it's what
`itsonix.xwiki` shipped), the popup was the later addition
(`itsonix.appswitcher`) and stays opt-in.

## How it works

- `Config::isMenuEnabled()` → `Option::get(MODULE_ID, 'MENU_ENABLED', 'Y') === 'Y'`
- `Config::isPopupEnabled()` → `Option::get(MODULE_ID, 'POPUP_ENABLED', 'N') === 'Y'`

Both gate the corresponding block in `EventHandler::onProlog()` (see
[popup-rendering.md](popup-rendering.md) /
[menu-icon-rendering.md](menu-icon-rendering.md)) and `Config::isMenuEnabled()`
additionally gates whether `MenuItem::sync()` writes anything into Bitrix's
own left-menu option at all (see [menu-sync.md](menu-sync.md)).

## Tests

`tests/ConfigTest.php`:
- `testMenuEnabledDefaultsTrueAndPopupEnabledDefaultsFalse` — the asymmetric
  defaults above, on a fresh option store.
- `testTogglesReflectStoredOption` — once set, both read back whatever was
  stored, independent of each other.
- `testAppearsInMenuAndAppearsInPopupReadTheFlagsVerbatim` — the per-entry
  counterparts (`Config::appearsInMenu`/`appearsInPopup`) are plain
  pass-throughs of the entry's own flags, not re-derived from anything else.

## Related

- [entry-storage.md](entry-storage.md) — the per-entry `showInMenu`/
  `showInPopup` flags these toggles sit on top of.
