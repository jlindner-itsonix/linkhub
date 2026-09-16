# itsonix.linkhub — Bitrix24 App Switcher & Menü-Links

Bitrix24-Lokalmodul, mit dem ein Admin beliebig viele externe Links (Intranet-
Tools, Wikis, Dashboards, ...) hinterlegen und jeden davon als Sidebar-Logo-
Popup-Kachel, als Menüeintrag mit In-Page-iFrame, oder beides anzeigen kann.

> Für Entwicklungskontext (Konventionen, wie Features hier verändert werden)
> siehe [`agent.md`](agent.md); für Feature-für-Feature-Doku mit den jeweils
> zugehörigen Tests siehe [`doc/`](doc/README.md).

## Funktionaler Überblick

**Gelöstes Problem:** Mitarbeiter brauchen Ein-Klick-Zugriff auf Tools
außerhalb von Bitrix24 (XWiki, AWU, SkillDB, ...), ohne die Bitrix24-Oberfläche
zu verlassen oder Lesezeichen suchen zu müssen.

**Was der Admin konfiguriert** (Bitrix24-Adminbereich → Einstellungen →
Module → Options-Seite "Link Hub"): eine beliebige Liste von Einträgen, je
mit

- **Label** — Anzeigename.
- **URL** — interner Pfad (z. B. `/xwiki/`) oder vollständige externe URL.
- **Im Menü anzeigen** — fügt einen reinen Icon-Eintrag in die linke
  Navigation ein; ein Klick öffnet das Ziel innerhalb von Bitrix24, eingebettet
  per iFrame auf einer eigenen Seite (damit Bitrix24-Rahmen/-Navigation
  sichtbar bleiben).
- **Im Popup anzeigen** — fügt eine Kachel zu einem Popup-Menü hinzu, das sich
  beim Klick auf das Sidebar-Logo öffnet (oben in der linken Navigation, neben
  dem Burger-Icon). Externe Ziele (andere Domain) öffnen stattdessen in einem
  neuen Tab, damit die Bitrix24-Session nicht beeinträchtigt wird.

Beide Darstellungen sind pro Eintrag unabhängig voneinander — ein Link kann in
keiner, einer oder beiden auftauchen. Es gibt zwei globale Schalter, um die
Menü-Funktion und die Popup-Funktion jeweils komplett ein-/auszuschalten, plus
eine Einstellung dafür, wie sich die iFrame-Seite dimensioniert (Viewport
ausfüllen, oder feste Pixel-Höhe).

**Defaults bei einer frischen Installation:** XWiki (Menü), AWU (Popup),
SkillDB (Popup).

## Technischer Überblick

### Struktur

```
itsonix.linkhub/
  install/index.php       CModule: Modul registrieren/deregistrieren + OnProlog-Event, Menü-Sync
  install/version.php     Modul-Version
  options.php             Admin-Einstellungsseite: nur POST-Handling/Logik, rendert via ui/options_view.php
  ui/options_view.php     Formular-Markup (reines HTML/PHP-Ausgabe, keine Logik)
  ui/options.js           Zeilen per Klick hinzufügen/entfernen (reines JS, von options_view.php geladen)
  lib/Config.php          Liest/schreibt Modul-Options (Bitrix\Main\Config\Option)
  lib/EventHandler.php    OnProlog-Handler: laedt templates/* und injiziert Popup-/Menü-Icon-CSS+JS
  lib/MenuItem.php        Synchronisiert Option left_menu_items_to_all_<SITE_ID> mit den konfigurierten Einträgen
  templates/popup.css     Popup-Styling (reines CSS, keine PHP-Tags)
  templates/popup.js      Popup-Verhalten (reines JS, Platzhalter __SWITCHER_ICON__/__TILES__)
  templates/menu-item-icon.css  Menü-Icon-CSS (Platzhalter __LINK__/__ICON_DATA_URI__)
  lang/{de,en}/...        Übersetzungen der Admin-UI
  icons/xwiki-light.svg   Icon für XWiki-Einträge (URL enthält "xwiki")
  icons/app-switcher.svg  Icon für den Popup-Trigger-Button
```

### Funktionsweise

- **Keine Core-Patches.** Alles wird über das Standard-`main`/`OnProlog`-Event
  injiziert (`EventHandler::onProlog`), das bei jedem Seitenaufruf
  `<style>`/`<script>` ins `<head>` einfügt. Das Popup wird clientseitig an
  das bestehende `.menu-items-header__logo`-Element angehängt; Menü-Icons je
  Eintrag werden über einen CSS-Selektor auf `data-link` angesprochen (siehe
  Code-Kommentar in `MenuItem::getLink`, warum `data-link` statt der
  admin-konfigurierten ID des Eintrags gewählt wurde — Bitrix vergibt eigene
  interne numerische IDs für Custom-Left-Menu-Items, ID-basiertes Targeting
  funktioniert daher nicht).
- **Speicherung:** Einträge liegen als ein serialisiertes Array in der
  Modul-Option `ENTRIES` (`Config::getEntries()`/`setEntries()`);
  `unserialize()` wird mit `allowed_classes => false` aufgerufen. Globale
  Schalter und iFrame-Höhe sind eigene skalare Options.
- **Linke-Menü-Integration:** Menüeinträge werden direkt in Bitrix' eigene
  Option `left_menu_items_to_all_<SITE_ID>` geschrieben (`MenuItem::sync()`),
  markiert mit einem ID-Präfix (`menu_itsonix_linkhub_<index>`), damit das
  Modul beim Speichern/Deinstallieren genau seine eigenen Zeilen findet und
  entfernt, ohne andere Custom-Menüeinträge zu stören. `sync()` räumt
  außerdem verwaiste `menu_itsonix_xwiki*`-Einträge vom Vorgänger-Modul mit
  weg.
- **Cache-Invalidierung:** Bitrix24 rendert die linke Navigation meist über
  den Composite-/Turbo-Cache. Weil `MenuItem::sync()`/`removeAll()` die
  Option direkt schreiben (kein Controller-Aufruf wie bei Bitrix' eigenem
  Menü-Editor), räumen sie danach explizit
  `\Bitrix\Intranet\Composite\CacheProvider::deleteAllCache()` und
  `\Bitrix\Intranet\Portal\FirstPage::clearCacheForAll()` weg — sonst bleibt
  ein neuer/geänderter Menüpunkt unsichtbar, bis der Cache anderweitig
  abläuft (siehe [`doc/menu-cache-invalidation.md`](doc/menu-cache-invalidation.md),
  ein echter Bug, der genau daran lag).
- **iFrame-Landingpage:** Menüeinträge verlinken auf
  `/local/linkhub/?entry=N` (`N` = Index in der vollständigen
  `Config::getEntries()`-Liste), die die Ziel-URL in einem iFrame rendert und
  Bitrix24s Seiten-Toolbar ausblendet, damit das eingebettete Tool den
  Content-Bereich ausfüllt.
- **Migration:** `Config::getEntries()` versteht weiterhin das alte einzelne
  `mode`-Feld (`menu`/`popup`/`both`) aus der ersten Modul-Version und
  konvertiert es beim Lesen in die aktuellen `showInMenu`/`showInPopup`-
  Booleans.

### Bekannte Einschränkung

`/local/linkhub/index.php` (die iFrame-Landingpage, auf die Menüeinträge
verlinken) liegt außerhalb von `itsonix.linkhub/` und ist **nicht** im
gepackten `.tar.gz` enthalten. Bei einer frischen Installation anderswo
rendern Menüeinträge zwar, zeigen aber die "kein Eintrag"-Meldung statt eines
iFrames, bis diese Seite ebenfalls deployt wird. Vor Verlassen darauf in einer
neuen Umgebung nachfragen.

### Build

```bash
cd bitrix24-module-linkhub
./build.sh          # erzeugt itsonix.linkhub.tar.gz
```

Erzeugt ein `.tar.gz` mit `itsonix.linkhub/` im Archiv-Root — die Struktur,
die ein lokaler Modul-Ordner unter `local/modules/` braucht (siehe
"Installieren / Deinstallieren" unten; im Standard-Bitrix gibt es keinen
Browser-Upload für beliebige lokale Module — verifiziert anhand von
`bitrix/modules/main/admin/module_admin.php` und `partner_modules.php`,
Letzteres ist Bitrix' eigener kostenpflichtiger/offizieller Marketplace-
Katalog, kein generischer Datei-Upload-Installer).

### Installieren / Deinstallieren

Der Modul-Ordner muss physisch unter `local/modules/itsonix.linkhub/` auf dem
Server existieren, bevor Bitrix die Installation anbietet — es gibt keinen
Admin-UI-Upload für eigene/lokale Module (nur Bitrix' eigener
kostenpflichtiger Marketplace-Katalog hat einen upload-ähnlichen Ablauf, der
damit aber nichts zu tun hat). Zwei Wege, den Ordner dorthin zu bekommen:

- **Datei-Zugriff** (SSH/SCP/Docker exec/etc.): `itsonix.linkhub.tar.gz`
  direkt nach `local/modules/` entpacken.
- **Nur Admin-UI, ohne SSH** (funktioniert auch mit Demo-Lizenz — verifiziert:
  weder `module_admin.php` noch `partner_modules.php` haben im Code
  irgendeine Lizenz-/Edition-Prüfung für lokale Module):
  1. Inhalte → Dateien und Ordner
     (`/bitrix/admin/fileman_admin.php?lang=de&site=s1&path=/local/modules/`).
  2. Dort `itsonix.linkhub.tar.gz` per Upload-Button in den Ordner
     `/local/modules/` hochladen.
  3. Datei anklicken/markieren → Werkzeug **"Packen/Entpacken"** →
     Zielordner `/local/modules/` → **Entpacken** klicken (ruft intern
     `CArchive->Unpack()` auf, entpackt den `itsonix.linkhub/`-Ordner aus
     dem Archiv-Root — siehe `fileman/classes/general/fileman_utils.php`).
  4. Entpacken allein installiert das Modul noch **nicht** — nur die Dateien
     liegen jetzt da. Danach: Einstellungen → Systemeinstellungen → Module
     (`http://localhost/bitrix/admin/partner_modules.php?lang=de`) →
     `itsonix.linkhub` suchen → **Installieren** klicken.

So oder so, sobald der Ordner existiert: Einstellungen → Systemeinstellungen
→ Module (`module_admin.php` bzw. `partner_modules.php`) → `itsonix.linkhub`
→ **Installieren**.
`DoInstall()` registriert den `OnProlog`-Event-Handler und synchronisiert die
Default-Menüeinträge. `DoUninstall()` entfernt den Event-Handler und alle
eigenen Menüeinträge des Moduls (und, je nach "Auch Dateien löschen"-Checkbox
der Admin-UI, den Modul-Ordner selbst).

### Lokales Dev-Setup

Die Docker-Testinstanz (`bitrix24-docker/test/bitrixdock`) läuft mit einer
**klassischen, nicht-live Installation** — das Modul ist eine echte, statische
Kopie des tar.gz-Inhalts unter `www/local/modules/itsonix.linkhub` im
`php`-Container, installiert auf dem normalen Weg (siehe "Installieren /
Deinstallieren" oben), genau wie auf einer echten Bitrix24-Box. Es gibt
absichtlich keinen Bind-Mount oder Symlink mehr von diesem Projekt-Ordner
`itsonix.linkhub/` in den Container hinein (eine frühere Fassung dieses
Setups hatte einen — der wurde bewusst entfernt, um den echten
Packaging-/Install-Weg zu testen statt ihn zu verdecken).

Konsequenz: Dateien hier zu editieren ändert **nicht** die laufende
Testinstanz. Um eine Änderung anzuwenden: `./build.sh`, das resultierende
`itsonix.linkhub.tar.gz` auf den Container bringen (z. B. `docker cp`, oder
der oben beschriebene Admin-UI-Upload+Entpacken-Weg), über
`local/modules/itsonix.linkhub` entpacken, danach `DoInstall()` erneut
auslösen, falls die Änderung install-zeitliches Verhalten betrifft
(Event-Registrierung, Default-Menü-Sync) — sonst reicht ein einfacher
Seiten-Reload, um das neue PHP zu übernehmen.
