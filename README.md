# itsonix.linkhub — Bitrix24 App Switcher & Menu Links

Bitrix24 local module that lets an admin register any number of external links
(intranet tools, wikis, dashboards, ...) and expose each one as a sidebar-logo
popup tile, a left-menu entry with an in-page iframe, or both. Successor/merge
of the earlier `itsonix.xwiki` (menu + iframe) and `itsonix.appswitcher`
(popup tiles) modules.

## Functional overview

**Problem it solves:** Employees need one-click access to tools that live
outside Bitrix24 (XWiki, AWU, SkillDB, ...) without leaving the Bitrix24 shell
or hunting for bookmarks.

**What the admin configures** (Bitrix24 admin panel → Settings → Modules →
"Link Hub" options page): an arbitrary list of entries, each with

- **Label** — display name.
- **URL** — internal path (e.g. `/xwiki/`) or full external URL.
- **Show in menu** — adds an icon-only item to the left navigation; clicking
  it opens the target inside Bitrix24, embedded in an iframe on a dedicated
  page (so the Bitrix24 frame/navigation stays visible).
- **Show in popup** — adds a tile to a popup menu that opens when clicking the
  sidebar logo (top of the left navigation), next to the burger icon.
  External targets (different domain) open in a new tab instead, so the
  Bitrix24 session isn't affected.

Both placements are independent per entry — a link can appear in neither, one,
or both. There are two global toggles to turn the menu feature and the popup
feature on/off entirely, plus a setting for how the iframe page sizes itself
(fill the viewport, or a fixed pixel height).

**Defaults on a fresh install:** XWiki (menu), AWU (popup), SkillDB (popup) —
these mirror what the two predecessor modules shipped before the merge.

## Technical overview

### Structure

```
itsonix.linkhub/
  install/index.php     CModule: register/unregister module + OnProlog event, menu sync
  install/version.php   Module version
  options.php            Admin settings page (entry list editor, add/remove rows via JS)
  lib/Config.php          Reads/writes module options (Bitrix\Main\Config\Option)
  lib/EventHandler.php    OnProlog handler: injects popup HTML/CSS/JS and per-entry menu icon CSS
  lib/MenuItem.php        Syncs left_menu_items_to_all_<SITE_ID> option with configured entries
  lang/{de,en}/...        Admin UI translations
  icons/xwiki-light.svg   Icon used for XWiki entries (URL contains "xwiki")
```

### How it works

- **No core patches.** Everything is injected via the standard `main`/`OnProlog`
  event (`EventHandler::onProlog`), which adds `<style>`/`<script>` to
  `<head>` on every page. The popup is attached client-side to the existing
  `.menu-items-header__logo` element; per-entry menu icons are targeted via a
  CSS selector on `data-link` (see the code comment in `MenuItem::getLink` for
  why `data-link` was chosen over the item's admin-configured ID — Bitrix
  assigns its own internal numeric ID to custom left-menu items, so ID-based
  targeting doesn't work).
- **Storage:** entries are one serialized array in module option `ENTRIES`
  (`Config::getEntries()`/`setEntries()`); `unserialize()` is called with
  `allowed_classes => false`. Global toggles and iframe height are separate
  scalar options.
- **Left-menu integration:** menu entries are written directly into Bitrix's
  own `left_menu_items_to_all_<SITE_ID>` option (`MenuItem::sync()`), tagged
  with an ID prefix (`menu_itsonix_linkhub_<index>`) so the module can find
  and remove exactly its own rows on save/uninstall without disturbing other
  custom menu items. `sync()` also cleans up legacy `menu_itsonix_xwiki*`
  entries left over from the pre-merge module.
- **Iframe landing page:** menu entries link to `/local/linkhub/?entry=N`
  (`N` = index into the full `Config::getEntries()` list), which renders the
  target URL in an iframe and hides Bitrix24's page toolbar so the embedded
  tool fills the content area.
- **Migration:** `Config::getEntries()` still understands the old single
  `mode` field (`menu`/`popup`/`both`) from the module's first version and
  converts it to the current `showInMenu`/`showInPopup` booleans on read.

### Known limitation

`/local/linkhub/index.php` (the iframe landing page that menu entries link
to) lives outside `itsonix.linkhub/` and is **not** included in the packaged
`.tar.gz`. On a fresh install elsewhere, menu entries will render but the
"no entry" message rather than an iframe until that page is deployed too.
Ask before relying on this in a new environment.

### Build

```bash
cd bitrix24-module-linkhub
./build.sh          # produces itsonix.linkhub.tar.gz
```

Produces a `.tar.gz` with `itsonix.linkhub/` at the archive root — the format
Bitrix24 expects for "Install module from file" (Marketplace → Local modules).

### Install / uninstall

Admin panel → Marketplace → Local modules → upload `itsonix.linkhub.tar.gz` →
Install. `DoInstall()` registers the `OnProlog` event handler and syncs the
default menu entries. `DoUninstall()` removes the event handler and all of
the module's own left-menu entries.

### Local dev setup

The Docker test instance's module directory is a symlink to this project
(host-visible only):

```
bitrix24-docker/test/bitrixdock/www/local/modules/itsonix.linkhub
  -> bitrix24-module-linkhub/itsonix.linkhub
```

That symlink alone does **not** work inside the `php` container — Docker only
bind-mounts `SITE_PATH` (`./www`), so a symlink target outside that tree is
unreachable from inside the container's mount namespace. `docker-compose.yml`
therefore has an extra bind mount for the `php` service pointing straight at
this project's `itsonix.linkhub/` folder, overriding the host symlink path
inside the container. If you re-run `bitrixdock`'s installer or otherwise
regenerate `docker-compose.yml`, that extra mount line needs to be re-added
and the `php` container recreated (`docker compose up -d php`).

Edit the files here; changes apply to the running test instance immediately
(no rebuild/reinstall needed for PHP changes — only rebuild the `.tar.gz` when
you need a distributable package).
