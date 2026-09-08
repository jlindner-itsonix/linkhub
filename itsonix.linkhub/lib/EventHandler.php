<?php

namespace Itsonix\LinkHub;

// itsonix: kein Direkt-Patch von template.php (nicht update-sicher, siehe
// xwiki/integrations_plan.md Punkt 14) — beide Darstellungen (Popup am Sidebar-Logo,
// Menü-Icons) werden per registriertem "main"/"OnProlog"-Event rein per CSS/JS draufgesetzt.
// Vereint die vormals getrennten Module itsonix.appswitcher (Popup) und itsonix.xwiki
// (Menü-Icon) — ein Eintrag kann jetzt in beiden gleichzeitig auftauchen (Config::MODE_BOTH).
class EventHandler
{
	public static function onProlog(): void
	{
		global $APPLICATION;

		$entries = Config::getEntries();
		$html = '';

		if (Config::isPopupEnabled())
		{
			$popupEntries = array_values(array_filter($entries, [Config::class, 'appearsInPopup']));
			if (!empty($popupEntries))
			{
				$html .= self::renderPopup($popupEntries);
			}
		}

		if (Config::isMenuEnabled())
		{
			foreach ($entries as $index => $entry)
			{
				if (Config::appearsInMenu($entry))
				{
					$html .= self::renderMenuItemIcon($index, $entry['url']);
				}
			}
		}

		if ($html !== '')
		{
			$APPLICATION->AddHeadString($html, true);
		}
	}

	// itsonix: kein Direkt-Patch — Klick-Handler wird per JS auf den bestehenden Sidebar-Logo-
	// Link draufgesetzt: .menu-items-header__logo (Kopfzeile der linken Navigation neben dem
	// Burger-Icon, .../menu/left_vertical/template.php:45). Bewusst NICHT am oberen
	// Header-Logo (.air-header__logo .logo-link, zeigt den konfigurierten Firmennamen) — dort
	// ausdrücklich nicht gewünscht. Popup wird bei Klick per getBoundingClientRect() am Element
	// positioniert (position:fixed, Anhang an document.body) — so ist es unabhängig von evtl.
	// overflow:hidden/auto der Sidebar, die ein absolut positioniertes Kind-Popup abschneiden
	// könnte.
	private static function renderPopup(array $entries): string
	{
		$xwikiIcon = self::getIconDataUri('xwiki-light.svg');
		$genericIcon = self::getGenericAppIconDataUri();
		// itsonix: als Inline-SVG statt data-URI-<img> eingebunden, damit fill="currentColor"
		// im SVG die Button-Textfarbe (.itsonix-app-switcher-btn, an --ui-color-base-1 gekoppelt
		// wie der "Bitrix24"-Schriftzug) übernimmt und in hellem wie dunklem Theme lesbar bleibt —
		// ein data-URI-<img> könnte das nicht per CSS einfärben.
		$switcherIcon = self::getInlineSvg('app-switcher.svg');

		$tiles = '';
		foreach ($entries as $entry)
		{
			$url = htmlspecialcharsbx($entry['url']);
			$label = htmlspecialcharsbx($entry['label']);
			// itsonix: Icon-Wahl per URL-Inhalt statt Array-Position — Einträge sind frei
			// sortier-/löschbar, eine positionsbasierte Zuordnung wäre nach dem Umsortieren falsch.
			$icon = stripos($entry['url'], 'xwiki') !== false ? $xwikiIcon : $genericIcon;
			$target = $entry['external'] ? ' target="_blank" rel="noopener"' : '';
			$tiles .= "<a href=\"{$url}\"{$target}><img src=\"{$icon}\" alt=\"\"><span>{$label}</span></a>";
		}

		return <<<HTML
<style>
/* itsonix: Blauton per Pixel-Sample aus Screenshot der linken Navigation gezogen
(RGB~28,37,140 / #1c258e). Layout: 3 Kacheln pro Zeile (Container-Breite passend für
3x64px+Gaps berechnet, box-sizing:border-box auf Container UND Kacheln), vierte rutscht
per flex-wrap automatisch in die nächste Zeile. */
#itsonix-app-switcher-menu{display:none;flex-wrap:wrap;position:fixed;box-sizing:border-box;width:220px;padding:8px;gap:4px;background:linear-gradient(180deg,#232f9e,#141c72);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.35);z-index:9999;}
#itsonix-app-switcher-menu a{box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;width:64px;padding:8px 4px;border-radius:6px;color:#dfe3f5;text-decoration:none;font-size:11px;text-align:center;}
#itsonix-app-switcher-menu a:hover{background:rgba(255,255,255,.1);}
#itsonix-app-switcher-menu img{width:20px;height:20px;}
/* itsonix: Trigger-Button rechts neben dem "Bitrix24"-Schriftzug im Sidebar-Header
(.menu-items-header__logo) — gleiche Farbvariable wie der Schriftzug selbst
(--ui-color-base-1, siehe .menu-items-header__logo in menu-items-header.css), damit er in
hellem wie dunklem Theme dazu passt. */
.itsonix-app-switcher-btn{display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:24px;height:24px;margin-left:6px;border-radius:6px;color:var(--ui-color-base-1);cursor:pointer;text-decoration:none;}
.itsonix-app-switcher-btn:hover{background:rgba(0,0,0,.06);}
.itsonix-app-switcher-btn svg{width:18px;height:18px;display:block;}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
	// itsonix: einziger Trigger-Punkt ist dieser eigene Button, als Geschwister-Element direkt
	// NACH .menu-items-header__logo (dem "Bitrix24"-Schriftzug) eingefügt — nicht mehr der ganze
	// Schriftzug-Link selbst (der navigiert wieder normal zu $siteUrl, kein preventDefault mehr
	// darauf). Davor sitzt bereits der Sidebar-Ein-/Ausklapp-Button
	// (.menu-items-header__menu-swticher), daher rechts danach statt davor platziert.
	var logo = document.querySelector('.menu-items-header__logo');
	if (!logo) return;

	var switcherBtn = document.createElement('a');
	switcherBtn.href = '#';
	switcherBtn.className = 'itsonix-app-switcher-btn';
	switcherBtn.title = 'Apps wechseln';
	switcherBtn.innerHTML = `{$switcherIcon}`;
	logo.insertAdjacentElement('afterend', switcherBtn);

	var menu = document.createElement('div');
	menu.id = 'itsonix-app-switcher-menu';
	menu.innerHTML = `{$tiles}`;
	document.body.appendChild(menu);

	function closeMenu() {
		menu.style.display = 'none';
	}

	function toggleMenu() {
		if (menu.style.display === 'flex') {
			closeMenu();
			return;
		}
		var rect = switcherBtn.getBoundingClientRect();
		menu.style.top = (rect.bottom + 6) + 'px';
		menu.style.left = rect.left + 'px';
		menu.style.display = 'flex';
	}

	switcherBtn.addEventListener('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		toggleMenu();
	});

	document.addEventListener('click', function (e) {
		if (menu.style.display === 'flex' && !menu.contains(e.target) && e.target !== switcherBtn && !switcherBtn.contains(e.target)) {
			closeMenu();
		}
	});
});
</script>
HTML;
	}

	private static function renderMenuItemIcon(int $index, string $url): string
	{
		// itsonix: Annahme "Item-ID wird 1:1 als data-id/CSS-Klasse übernommen" war FALSCH
		// (per Browser-DevTools verifiziert 01.09.2026) — Custom-Items aus
		// left_menu_items_to_all_<SITE_ID> bekommen von Bitrix intern eine eigene numerische
		// ID, .menu-item-icon enthält dann literalen Fallback-Text (Erstbuchstabe). Verlässlicher
		// Ankerpunkt ist stattdessen data-link — das setzen wir selbst über
		// MenuItem::getLink($index), pro Eintrag eindeutig (?entry=N), und wird von template.php
		// unverändert durchgereicht. Fallback-Text per color:transparent unsichtbar machen
		// (Layout bleibt erhalten), Icon per background-image einsetzen. "light"-Variante (weiße
		// Füllung) wie beim Messenger-Vorbild — Sidebar-Icons liegen auf dunklem Grund.
		$iconDataUri = stripos($url, 'xwiki') !== false
			? self::getIconDataUri('xwiki-light.svg')
			: self::getGenericAppIconDataUri();
		$link = htmlspecialcharsbx(MenuItem::getLink($index));

		return <<<HTML
<style>
li[data-link="{$link}"] .menu-item-icon{
	color: transparent;
	background-color: transparent !important;
	background-image: url("{$iconDataUri}");
	background-repeat: no-repeat;
	background-position: center;
	background-size: 70%;
	opacity: 0.8;
}
</style>
HTML;
	}

	private static function getIconDataUri(string $file): string
	{
		$svg = @file_get_contents(__DIR__ . '/../icons/' . $file);
		return $svg !== false ? 'data:image/svg+xml;base64,' . base64_encode($svg) : '';
	}

	// itsonix: rohes <svg>...</svg>-Markup statt data-URI — wird per JS ins DOM eingefügt
	// (nicht als <img src="data:...">), damit fill="currentColor" im SVG per CSS einfärbbar
	// bleibt. XML-Prolog/Kommentar-Zeilen vor dem öffnenden <svg>-Tag entfernt, da sie in einem
	// HTML- (nicht XHTML-)Dokument beim Einfügen per innerHTML zu Parse-Artefakten führen können.
	private static function getInlineSvg(string $file): string
	{
		$svg = @file_get_contents(__DIR__ . '/../icons/' . $file);
		if ($svg === false)
		{
			return '';
		}
		$start = strpos($svg, '<svg');
		return $start !== false ? substr($svg, $start) : $svg;
	}

	// itsonix: kein Marken-Icon für Nicht-XWiki-Einträge vorhanden — generisches "Apps"-Symbol
	// (3x3-Punktraster) in Weiß.
	private static function getGenericAppIconDataUri(): string
	{
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#fff">'
			. '<path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/>'
			. '</svg>';
		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}
}
