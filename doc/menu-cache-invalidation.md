# Menu cache invalidation

## What / why

This was a real bug: after saving a new/changed menu entry in the admin
options page, the menu item didn't appear on the actual portal. The option
was written correctly — the problem was that Bitrix24 typically renders the
left navigation through its Composite/Turbo page cache. Bitrix's own
left-menu controller
(`Bitrix\Intranet\Controller\LeftMenu::processBeforeAction()`) busts that
cache (`\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache()` +
`FirstPage::getInstance()->clearCache()`) before *every* menu-editing AJAX
action. This module writes the option directly (see
[menu-sync.md](menu-sync.md)) — no controller round-trip — so nothing was
invalidating that cache, and the stale cached page kept being served.

## How it works

`MenuItem::invalidateMenuCache()` (private, called at the end of both
`sync()` and `removeAll()`) calls:
- `\Bitrix\Intranet\Composite\CacheProvider::deleteAllCache()` — chosen over
  `deleteUserCache()` (current user only) because this is an admin-wide
  change affecting every user who sees the shared menu, not just the admin
  who saved the form.
- `\Bitrix\Intranet\Portal\FirstPage::getInstance()->clearCacheForAll()` —
  same reasoning, portal-wide variant.

Both calls are guarded by `class_exists(...)` so this never fatals on a
Bitrix setup where Composite mode (or these specific classes) isn't present.

## Tests

`tests/MenuItemTest.php::testSyncInvalidatesCompositeAndFirstPageCache` —
regression test: calls `MenuItem::sync()` against
`tests/Stubs/BitrixIntranetCompositeCacheProvider.php` and
`tests/Stubs/BitrixIntranetPortalFirstPage.php` (call counters instead of
real cache backends) and asserts both were invoked exactly once. If this
call is ever removed or accidentally skipped, this test fails.

## Related

- [menu-sync.md](menu-sync.md) — the two call sites.
