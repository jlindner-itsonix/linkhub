# doc/ — feature docs

One file per feature. Each one states what the feature does and why, where
it lives in `itsonix.linkhub/`, and which tests prove it — so context and
verification stay next to each other instead of drifting apart.

This is the CDD (context-driven development) contract for this repo: **a
feature without a doc file here, or a doc file whose tests don't exist,
is incomplete.** See `../agent.md` for the loop to follow when you add or
change one.

| Doc | Feature | Source |
|---|---|---|
| [entry-storage.md](entry-storage.md) | Link entries: storage, defaults, legacy migration, `external` derivation | `lib/Config.php` |
| [global-toggles.md](global-toggles.md) | Menu/popup on/off switches | `lib/Config.php` |
| [iframe-height.md](iframe-height.md) | Iframe landing-page sizing mode | `lib/Config.php` |
| [menu-sync.md](menu-sync.md) | Left-menu item sync (add/remove/toggle, legacy cleanup) | `lib/MenuItem.php` |
| [menu-cache-invalidation.md](menu-cache-invalidation.md) | Composite-/FirstPage-cache busting after a menu write | `lib/MenuItem.php` |
| [popup-rendering.md](popup-rendering.md) | Sidebar-logo popup (tiles) | `lib/EventHandler.php`, `templates/popup.*` |
| [menu-icon-rendering.md](menu-icon-rendering.md) | Left-menu icon overlay per entry | `lib/EventHandler.php`, `templates/menu-item-icon.css` |
| [install-uninstall.md](install-uninstall.md) | Module registration lifecycle | `install/index.php` |
| [admin-options-form.md](admin-options-form.md) | Admin settings page (view smoke-tested; POST-handling untested — documented why) | `options.php`, `ui/` |
