# Admin options form

## What / why

The settings page an admin uses to configure everything else in this repo
(Settings → Modules → "Link Hub"): the two global toggles, the entry list
editor (add/remove rows via JS, label/URL/show-in-menu/show-in-popup per
row), and the iframe height setting.

## How it works

- `options.php` — logic only: admin guard, POST handling (parses the
  `LABEL[]`/`URL[]`/`SHOW_MENU[]`/`SHOW_POPUP[]` associative arrays keyed by
  row ID, builds the entries array, calls `Config::setEntries()` +
  `Option::set()` for the toggles/height fields, then `MenuItem::sync()` so
  changes apply immediately — see [menu-sync.md](menu-sync.md)), then
  `require`s the view.
- `ui/options_view.php` — pure rendering: the form markup, looping
  `Config::getEntries()` for the existing rows, no business logic.
- `ui/options.js` — plain JS (no PHP), loaded by the view via
  `file_get_contents()`: adds/removes entry rows client-side before submit.

## Tests

**None, deliberately.** Rendering and POST handling depend on the real
Bitrix admin framework — `CAdminTabControl`, `check_bitrix_sessid()`,
`LocalRedirect()`, `$APPLICATION->GetCurPage()`, `bitrix_sessid_post()` —
none of which are meaningfully stubbable without either reimplementing a
chunk of Bitrix's admin kernel or testing the stub instead of real
behavior. What *is* covered is everything this page delegates to:
`Config::setEntries()`/toggle options (see
[entry-storage.md](entry-storage.md), [global-toggles.md](global-toggles.md),
[iframe-height.md](iframe-height.md)) and `MenuItem::sync()` (see
[menu-sync.md](menu-sync.md)) — the actual persistence and side effects,
just not this file's HTML/POST plumbing.

**Manual verification instead**, after any change to this page: save the
form on a real Bitrix24 instance and confirm — toggles persist, an added
row survives a reload, a removed row's URL disappears from the option, and
the corresponding menu item appears/disappears per
[menu-sync.md](menu-sync.md).

## Related

- [entry-storage.md](entry-storage.md), [global-toggles.md](global-toggles.md),
  [iframe-height.md](iframe-height.md) — what this form actually persists.
- [menu-sync.md](menu-sync.md) — triggered on every save.
