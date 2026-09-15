# Install / uninstall lifecycle

## What / why

Standard Bitrix module registration: hook into the platform (register the
module + the rendering event) on install, and leave no trace on uninstall
— including menu items this module created, but *not* items belonging to
other modules or the admin's own manual customization.

## How it works

`itsonix_linkhub extends CModule`, in `install/index.php`:
- `DoInstall()` — admin-only guard, then `ModuleManager::registerModule()`,
  registers the `main`/`OnProlog` event handler
  (`\Itsonix\LinkHub\EventHandler::onProlog`), and calls
  `MenuItem::sync()` so the default entries' menu items exist immediately
  (no second admin action needed after install).
- `DoUninstall()` — admin-only guard, unregisters the event handler, calls
  `MenuItem::removeAll()` (own items only — see
  [menu-sync.md](menu-sync.md)), then `ModuleManager::unRegisterModule()`.

Both guard on `$USER->IsAdmin()` — Bitrix calls these through the module
admin UI, which should already restrict this, but the check is defense in
depth.

## Tests

`tests/InstallTest.php` (against stubbed
`Bitrix\Main\EventManager`/`ModuleManager` — see `tests/Stubs/`, no real
Bitrix kernel involved):
- `testDoInstallRegistersModuleAndOnPrologEventAndSyncsDefaultMenu` — module
  registered, exactly one `OnProlog` handler registered with the right
  class/method, and the default entries' menu sync already happened.
- `testDoInstallIsRefusedForNonAdminUsers` — a non-admin `$USER` → nothing
  happens (no registration, no menu items written).
- `testDoUninstallUnregistersEventAndRemovesOwnMenuItemsOnly` — event
  unregistered, module unregistered, this module's own menu item removed,
  a foreign menu item (simulating another module/manual admin item) left
  untouched.
- `testDoUninstallIsRefusedForNonAdminUsers` — a non-admin `$USER` → nothing
  is torn down, the previously-synced menu item survives.

## Related

- [menu-sync.md](menu-sync.md) — what `DoInstall()`/`DoUninstall()` delegate
  the actual menu-item bookkeeping to.
