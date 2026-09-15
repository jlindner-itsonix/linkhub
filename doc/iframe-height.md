# Iframe height mode

## What / why

Menu entries open their target URL in an iframe on `/local/linkhub/`
(outside this repo — see [install-uninstall.md](install-uninstall.md)'s
"known limitation" note in the main README). That landing page needs to know
how tall to make the iframe: fill the viewport, or a fixed pixel height —
some embedded tools render badly at 100% height. This is a per-portal
setting, not per-entry.

## How it works

- `Config::getIframeHeightMode()` reads `IFRAME_HEIGHT_MODE`, defaulting to
  and falling back to `Config::HEIGHT_MODE_VIEWPORT` if the stored value
  isn't one of the two known constants (`viewport`/`fixed`) — protects the
  landing page from an unrecognized mode string (e.g. from a stale/corrupted
  option) silently breaking rendering.
- `Config::getIframeHeightPx()` reads `IFRAME_HEIGHT_PX`, cast to `int`,
  default `800`.

## Tests

`tests/ConfigTest.php`:
- `testIframeHeightModeFallsBackToViewportOnInvalidStoredValue` — garbage
  stored value → `viewport`, never an unrecognized string.
- `testIframeHeightModeAcceptsFixed` — the one other valid value round-trips.
- `testIframeHeightPxDefaultsTo800AndIsCastToInt` — default value, and that a
  stored string gets cast to `int`.

## Related

- [entry-storage.md](entry-storage.md) — sibling module options read the
  same way.
