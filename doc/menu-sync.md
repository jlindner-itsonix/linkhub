# Left-menu sync

## What / why

Bitrix24's left navigation isn't extended through a public API for this use
case — custom "for all users" menu items are stored as one serialized array
in the admin option `left_menu_items_to_all_<SITE_ID>` (module `intranet`),
the same option Bitrix's own admin left-menu editor writes to
(`Bitrix\Intranet\Controller\LeftMenu::addItemToAllAction()` in Bitrix core).
This module writes into that same option directly, so a configured
"show in menu" entry needs no core patch to appear as a real navigation item.

Because it's a *shared* option (other custom items, from other modules or
the admin's own manual additions, can live in the same array), every write
has to touch only this module's own rows and leave everything else alone —
that's what the ID-prefix scheme below is for.

## How it works

- `MenuItem::getId($index)` → `menu_itsonix_linkhub_<index>`,
  `MenuItem::getLink($index)` → `/local/linkhub/?entry=<index>`. `$index` is
  the entry's 0-based position in the *full* `Config::getEntries()` list —
  this has to stay consistent with
  [menu-icon-rendering.md](menu-icon-rendering.md) (`data-link` selector) and
  the `/local/linkhub/index.php` landing page (`?entry=N`).
- `MenuItem::isOwnItem($item)` matches the current ID prefix
  (`menu_itsonix_linkhub_`) or either legacy prefix
  (`menu_itsonix_xwiki_`/`menu_itsonix_xwiki`) left over from the
  pre-merge `itsonix.xwiki` module.
- `MenuItem::sync()`: reads the current option, strips every row this module
  owns (current or legacy prefix), then — only if
  `Config::isMenuEnabled()` — re-adds one row per `Config::getEntries()` row
  flagged `showInMenu`, at its full-list index. Writes the result back (or
  deletes the option if the result is empty), then busts the menu cache (see
  [menu-cache-invalidation.md](menu-cache-invalidation.md)). This one
  strip-then-rebuild pass handles add/remove/rename/reorder/toggle
  uniformly — no diffing against the previous state needed.
- `MenuItem::removeAll()`: same strip, but unconditional (ignores
  `MENU_ENABLED`) — used by `DoUninstall()` (see
  [install-uninstall.md](install-uninstall.md)) since the whole module is
  being removed, not just its menu items disabled.
- `MenuItem::getSiteId()` resolves the target site via `\CSite::GetDefSite()`
  — **not** the `SITE_ID` constant. Real incident (16.09.2026): saving the
  admin options form runs in Bitrix's admin context, where there's often no
  resolvable site object; Bitrix's own kernel bootstrap then falls back to
  `SITE_ID = LANG` (`bitrix/modules/main/include.php`) — the admin UI's
  *language* (e.g. `de`), not the actual portal site (`s1`). Trusting
  `SITE_ID` made `sync()` write into `left_menu_items_to_all_de` while the
  real navigation reads `left_menu_items_to_all_s1` — entries saved via the
  admin form silently never appeared, even though the option write itself
  "succeeded". `CSite::GetDefSite()` queries the actual site table instead
  and is unaffected by which context (admin/frontend/CLI) the code runs in.

## Tests

`tests/MenuItemTest.php`:
- `testGetIdAndGetLinkAreIndexBased` — ID/link format.
- `testSyncAddsOneEntryPerMenuFlaggedConfigRowAtItsFullListIndex` — the index
  used is the row's position in the *full* entries list, not among only the
  menu-flagged ones.
- `testSyncFallsBackToUrlAsTextWhenLabelIsEmpty` — an entry with no label
  still gets a usable menu label (falls back to the URL).
- `testSyncRemovesOwnEntriesWhenMenuToggleIsDisabled` — disabling the menu
  toggle removes previously-synced rows, and deletes the option entirely
  rather than leaving an empty array behind.
- `testSyncLeavesForeignMenuItemsUntouched` — a row belonging to another
  module/manual admin item survives `sync()` unchanged and keeps its
  position; this module's own row is recomputed fresh, not left stale.
- `testSyncCleansUpLegacyXwikiPrefixedEntriesFromThePredecessorModule` — both
  legacy ID shapes (with and without a numeric suffix) get cleaned up.
- `testSyncInvalidatesCompositeAndFirstPageCache` — see
  [menu-cache-invalidation.md](menu-cache-invalidation.md).
- `testSyncIgnoresAMisleadingAmbientSiteIdConstant` — regression test for the
  `SITE_ID`-vs-`CSite::GetDefSite()` bug above: defines `SITE_ID` to a wrong
  value and asserts `sync()` still writes to the real default site's option,
  not the one the constant would suggest.
- `testRemoveAllStripsOwnAndLegacyItemsRegardlessOfMenuToggle` — `removeAll()`
  ignores `MENU_ENABLED` entirely.
- `testRemoveAllDeletesTheOptionWhenNothingForeignIsLeft` /
  `testRemoveAllIsANoOpWhenTheOptionWasNeverSet` — the two empty-result edge
  cases.

## Related

- [entry-storage.md](entry-storage.md) — the source data being synced.
- [menu-icon-rendering.md](menu-icon-rendering.md) — targets the exact same
  `data-link` this feature writes.
- [install-uninstall.md](install-uninstall.md) — calls `sync()`/`removeAll()`
  at the right lifecycle points.
