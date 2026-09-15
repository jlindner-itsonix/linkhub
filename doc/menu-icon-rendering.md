# Menu-icon rendering

## What / why

Entries flagged "show in menu" get a real left-navigation item (written by
[menu-sync.md](menu-sync.md)), but Bitrix renders custom items with a
generic look (a fallback-letter icon). This feature overlays the module's
own icon on that specific item via CSS, so it looks like a proper
navigation entry rather than a plain text link.

The icon can't be targeted by the item's configured ID — Bitrix assigns its
own internal numeric ID to custom left-menu items at render time, so
ID-based CSS targeting silently fails (verified in Browser DevTools). The
one thing that *is* reliably present and unchanged is the link's `data-link`
attribute, which Bitrix's `template.php` passes straight through — so that's
the anchor this feature uses instead.

## How it works

- `EventHandler::onProlog()` — if `Config::isMenuEnabled()`, calls
  `renderMenuItemIcon($index, $url)` for every entry with
  `Config::appearsInMenu()` true, at the entry's full-list index (same index
  [menu-sync.md](menu-sync.md) used to build that entry's `data-link`).
- `EventHandler::renderMenuItemIcon()` picks an icon (XWiki-branded if the
  URL contains "xwiki", generic otherwise), builds
  `MenuItem::getLink($index)` as the selector target, and loads
  `templates/menu-item-icon.css` with `__LINK__`/`__ICON_DATA_URI__`
  placeholders substituted. The CSS makes the fallback letter transparent
  and sets the real icon as a `background-image` on
  `li[data-link="..."] .menu-item-icon`.

## Tests

`tests/EventHandlerTest.php::testOnPrologRendersMenuIconCssTargetingTheEntrysDataLinkSelector`
— for a menu-flagged entry at a non-zero index, the emitted CSS selector
targets exactly `MenuItem::getLink($index)` (proving the index used here
and in `MenuItem::sync()` stay in lockstep), and no unsubstituted
`__LINK__`/`__ICON_DATA_URI__` placeholder text leaks into the output.

## Related

- [menu-sync.md](menu-sync.md) — writes the actual navigation item this
  feature only decorates; the `$index` (and therefore `data-link`) must
  match exactly.
- [popup-rendering.md](popup-rendering.md) — the other `onProlog()` branch.
