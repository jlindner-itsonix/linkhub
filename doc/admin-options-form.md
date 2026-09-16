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

`tests/OptionsViewTest.php` — smoke-tests `ui/options_view.php` in isolation
(stubbed `$APPLICATION`/`$tabControl`, real `Config`): asserts it renders
without throwing and reflects configured entries in the output. This is a
regression test for a real bug (16.09.2026): the view uses
`Loc::getMessage()`/`Config::*` unqualified, and as a separately-`require`d
file it does **not** inherit `options.php`'s `use` imports (PHP imports are
per-file, not per request) — without its own `use Bitrix\Main\Localization\Loc;`
/ `use Itsonix\LinkHub\Config;` at the top, this throws `Class "Loc" not
found` the moment the real admin page is opened. Any new class reference
added to `ui/options_view.php` needs its own `use` there too — this test
would catch a missing one, but only if the class is actually referenced in
a code path the test exercises.

**`options.php`'s own POST handling stays untested**, deliberately. It
depends on the real Bitrix admin framework — `CAdminTabControl`,
`check_bitrix_sessid()`, `LocalRedirect()` — not meaningfully stubbable
without either reimplementing a chunk of Bitrix's admin kernel or testing
the stub instead of real behavior. What *is* covered is everything it
delegates to: `Config::setEntries()`/toggle options (see
[entry-storage.md](entry-storage.md), [global-toggles.md](global-toggles.md),
[iframe-height.md](iframe-height.md)) and `MenuItem::sync()` (see
[menu-sync.md](menu-sync.md)).

**Manual verification** after any change to the POST-handling side: save the
form on a real Bitrix24 instance and confirm — toggles persist, an added
row survives a reload, a removed row's URL disappears from the option, and
the corresponding menu item appears/disappears per
[menu-sync.md](menu-sync.md).

## Related

- [entry-storage.md](entry-storage.md), [global-toggles.md](global-toggles.md),
  [iframe-height.md](iframe-height.md) — what this form actually persists.
- [menu-sync.md](menu-sync.md) — triggered on every save.
