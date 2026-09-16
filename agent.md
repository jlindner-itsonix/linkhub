# agent.md — context for anyone (human or AI) working in this repo

**This repo follows CDD (context-driven development):** context lives in
written docs next to the code it describes, not just in people's/agents'
heads or past chat history — don't re-derive it from scratch each session,
and every feature's doc stays paired with the tests that prove it (see
`doc/`). Keep it written down, and keep it current as code changes.

This file is the entry point. Read it before touching code. It doesn't repeat
what's already documented elsewhere — it says where to look and what rules
hold across the whole repo.

- **Functional/technical deep-dive:** `README.md` — what the module does, how
  it's built, storage format, known limitations, local dev/Docker setup.
- **Per-feature docs:** `doc/` — one file per feature: what it does, why,
  where it lives, and exactly which tests prove it. Start at
  [doc/README.md](doc/README.md).
- **This file:** conventions and the loop to follow when you change or add a
  feature.

## Repository layout

```
bitrix24-module-linkhub/
  itsonix.linkhub/     the actual Bitrix24 module — this and ONLY this
                       directory is packaged by build.sh / shipped as
                       itsonix.linkhub.tar.gz. Nothing outside it may be
                       required by module code.
  tests/               PHPUnit tests + Bitrix stub classes (dev-only)
  doc/                 one .md per feature, each naming its tests (dev-only)
  composer.json        dev tooling (phpunit) — NOT shipped
  phpunit.xml.dist
  agent.md             this file
  README.md            functional + technical documentation
```

Rule: if you add a dependency or a file that the module needs at runtime, it
must live under `itsonix.linkhub/`. If you add dev/test tooling, it must live
outside `itsonix.linkhub/` (repo root, `tests/`) — `build.sh` only tars
`itsonix.linkhub/`, so anything else never ships, and nothing shipped may
require anything else.

## Running the tests

No PHP/Composer on the host in this project's normal dev setup — run them
inside the Bitrix Docker PHP container (see README.md's "Local dev setup" for
how that container is wired to this repo), or any PHP 8.2+ with Composer:

```bash
composer install
composer test        # = vendor/bin/phpunit
```

The test suite stubs every Bitrix core class it touches (`tests/Stubs/`) —
it does **not** need a running Bitrix instance, a database, or the Docker
setup. It tests this module's own logic in isolation.

If `composer install` can't reach GitHub (some sandboxed/firewalled networks
502/504 on `api.github.com` specifically), skip Composer entirely and run
against the standalone PHPUnit PHAR instead — `tests/bootstrap.php` doesn't
require `vendor/autoload.php` to exist, it registers its own tiny PSR-4
autoloader for `Itsonix\LinkHub\*`:

```bash
curl -sSL -o /tmp/phpunit.phar https://phar.phpunit.de/phpunit-10.5.phar
php /tmp/phpunit.phar --bootstrap tests/bootstrap.php tests/
```

## Feature docs (source ↔ tests)

Every feature described in README.md has its own doc file under `doc/`
(index: [doc/README.md](doc/README.md)), each naming exactly which tests
prove it. That table is the contract — if you add or change a feature, add
or update its doc file. Don't duplicate that map here; it would just drift
out of sync with a second copy.

One area is only partially covered on purpose: see
[doc/admin-options-form.md](doc/admin-options-form.md) for why
`options.php`'s POST-handling has no automated test (its view template does,
and so does the persistence both delegate to).

## Conventions

- **Comments explain WHY, never WHAT.** Existing German inline comments
  (`// itsonix: ...`) capture non-obvious constraints, prior bugs, or
  rejected alternatives. Match that style; don't add comments that restate
  the code.
- **Ownership by ID prefix.** Anything this module writes into a shared
  Bitrix option (`left_menu_items_to_all_<SITE_ID>`) is tagged with
  `MenuItem::ID_PREFIX`. Never touch entries without that prefix (or the
  legacy `menu_itsonix_xwiki*` prefixes) — they belong to other
  modules/admin-configured items.
- **Direct option writes need explicit cache invalidation.** Bitrix's own
  left-menu controller (`Bitrix\Intranet\Controller\LeftMenu`) busts the
  Composite/FirstPage cache before every menu mutation. Because
  `MenuItem::sync()`/`removeAll()` write the option directly (no controller
  round-trip), they must keep calling `invalidateMenuCache()` — this was a
  real bug (menu item silently not appearing after save) before it was added.
  Don't remove it, and don't add another direct `Option::set()` on a
  Bitrix-owned option without checking whether Bitrix itself invalidates a
  cache when it makes the same change.
- **Never trust the `SITE_ID` constant for resolving "which site" this
  module targets — use `\CSite::GetDefSite()`.** `MenuItem::getSiteId()`
  does this on purpose. Real incident (16.09.2026): in Bitrix's admin
  context (saving the options form) there's often no resolvable site
  object, so Bitrix's own kernel bootstrap falls back to `SITE_ID = LANG`
  (the admin UI's *language*, e.g. `de`) instead of the real portal site
  (`s1`) — trusting it made menu entries get written to
  `left_menu_items_to_all_de` while the real navigation reads `_s1`, so
  admin-saved entries silently never appeared. `SITE_ID` is reliable on the
  frontend but not in admin/CLI contexts on this class of install; don't
  reintroduce a `defined('SITE_ID') ? SITE_ID : ...` fallback here or
  elsewhere in this module.
- **HTML/CSS/JS stay out of PHP control-flow files where practical.**
  `lib/EventHandler.php` loads static CSS/JS from `templates/` (placeholder
  substitution via `str_replace`, no PHP in those files); `options.php` is
  logic-only and `require`s `ui/options_view.php` for markup. Keep new
  UI/rendering code following that split rather than growing another heredoc.

## Adding or changing a feature — the loop

1. Change the behavior in `lib/` (and `ui/`/`templates/` if it's user-facing).
2. Update or add a test in `tests/` for it (see `doc/README.md` for where
   similar features live) — run `composer test`.
3. Update `README.md`'s functional or technical overview if what the admin
   sees or how storage works changed.
4. Update the feature's file under `doc/` (or add a new one + its row in
   `doc/README.md`, if it's a new feature area rather than a tweak to an
   existing one).
5. If you touched anything Bitrix-storage-related
   (`left_menu_items_to_all_*`, module options), sanity-check the "Direct
   option writes need explicit cache invalidation" rule above still holds.
