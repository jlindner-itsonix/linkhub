# Popup rendering (sidebar-logo app switcher)

## What / why

Entries flagged "show in popup" appear as tiles in a small popup menu that
opens when clicking the sidebar logo (top of the left navigation, next to
the burger icon) — a quick app-switcher for tools that don't need a
permanent menu slot. External targets (different domain) open in a new tab
so the Bitrix24 session/tab isn't affected; internal targets navigate
in-place.

No core template patch is used (patching `template.php` isn't update-safe)
— the whole thing is injected as `<style>`/`<script>` via the standard
`main`/`OnProlog` event and attached client-side to the existing
`.menu-items-header__logo` element.

## How it works

- `EventHandler::onProlog()` — if `Config::isPopupEnabled()` and at least one
  entry has `Config::appearsInPopup()` true, calls `renderPopup()` and adds
  its output to `<head>` via `$APPLICATION->AddHeadString()`.
- `EventHandler::renderPopup(array $entries)` builds one `<a>` tile per
  entry (label, icon — XWiki-branded if the URL contains "xwiki", generic
  otherwise — and `target="_blank" rel="noopener"` for external URLs), then
  loads `templates/popup.css` (static, no substitution) and
  `templates/popup.js` (placeholder substitution: `__SWITCHER_ICON__` for
  the trigger button's inline SVG, `__TILES__` for the built tile HTML).
- The JS inserts a trigger button right after `.menu-items-header__logo` and
  positions the popup with `getBoundingClientRect()` at click time
  (`position:fixed`, appended to `document.body` — independent of any
  `overflow:hidden` on the sidebar).

## Tests

`tests/EventHandlerTest.php`:
- `testOnPrologAddsNothingWhenBothTogglesAreOff` — no popup toggle, no menu
  toggle → nothing added to `<head>` at all.
- `testOnPrologRendersPopupTilesForPopupFlaggedEntriesOnly` — popup CSS is
  present, a popup-flagged entry's label/URL show up as a tile, a
  menu-only entry does not; and — importantly — no unsubstituted
  `__TILES__`/`__SWITCHER_ICON__` placeholder text leaks into the output
  (would indicate a broken template substitution).
- `testOnPrologOpensExternalPopupTargetsInNewTab` — `target="_blank"
  rel="noopener"` only on the external tile, never the internal one.
- `testOnPrologSkipsPopupBlockWhenNoEntryIsFlaggedForIt` — popup toggle on
  but zero entries flagged for it → no popup block rendered at all (not an
  empty one).

## Related

- [entry-storage.md](entry-storage.md) — source of the entries being
  rendered.
- [menu-icon-rendering.md](menu-icon-rendering.md) — the other `onProlog()`
  branch, same event, independent toggle.
