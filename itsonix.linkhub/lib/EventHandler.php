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

		$css = self::loadTemplate('popup.css');
		$js = str_replace(
			['__SWITCHER_ICON__', '__TILES__'],
			[$switcherIcon, $tiles],
			self::loadTemplate('popup.js')
		);

		return "<style>{$css}</style>\n<script>{$js}</script>";
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

		$css = str_replace(
			['__LINK__', '__ICON_DATA_URI__'],
			[$link, $iconDataUri],
			self::loadTemplate('menu-item-icon.css')
		);

		return "<style>{$css}</style>";
	}

	private static function loadTemplate(string $file): string
	{
		$content = @file_get_contents(__DIR__ . '/../templates/' . $file);
		return $content !== false ? $content : '';
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
