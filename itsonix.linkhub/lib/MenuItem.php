<?php

namespace Itsonix\LinkHub;

use Bitrix\Main\Config\Option;

// itsonix: Menüeintrag-Verwaltung ausgelagert (statt nur in install/index.php), damit sowohl
// DoInstall() als auch die Options-Seite (Toggle + Entry-Änderungen) denselben Code nutzen
// können. Ein Menüeintrag pro Config::getEntries()-Zeile mit mode 'menu'/'both'
// (appearsInMenu()) — Index ist die 0-basierte Position in der VOLLEN Entries-Liste, damit er
// mit EventHandler::onProlog() (Icon-CSS) und local/linkhub/index.php (?entry=N) übereinstimmt.
class MenuItem
{
	// itsonix: ID-Präfix je Eintrag, gleichzeitig ID im left_menu_items_to_all_<SITE_ID>-Array
	// (Bitrix\Intranet\UI\LeftMenu\MenuItem\ItemAdminShared) — siehe
	// xwiki/integrations_plan.md Punkt 16. Match-Kriterium beim Entfernen/Aktualisieren.
	const ID_PREFIX = 'menu_itsonix_linkhub_';

	// itsonix: Präfixe der Vorgänger-Module (itsonix.xwiki, vor dessen eigenem Multi-Entry-
	// Umbau sogar ohne Index) — sync() räumt sie beim ersten Lauf nach der Modul-Fusion mit weg,
	// sonst bleiben verwaiste Einträge stehen.
	const LEGACY_ID_PREFIXES = ['menu_itsonix_xwiki_', 'menu_itsonix_xwiki'];

	const LINK_BASE = '/local/linkhub/';

	public static function getId(int $index): string
	{
		return self::ID_PREFIX . $index;
	}

	// itsonix: auch von EventHandler::onProlog() genutzt (data-link-Selektor pro Eintrag).
	public static function getLink(int $index): string
	{
		return self::LINK_BASE . '?entry=' . $index;
	}

	// itsonix: NICHT auf die SITE_ID-Konstante verlassen — im Admin-Kontext (options.php,
	// install/index.php) gibt es oft kein aufloesbares Site-Objekt, dann faellt Bitrix' eigener
	// Kernel-Bootstrap auf SITE_ID=LANG zurueck (bitrix/modules/main/include.php), also die
	// Admin-UI-Sprache (z.B. "de") statt der echten Portal-Site ("s1"). Führte dazu, dass
	// MenuItem::sync() beim Speichern im Options-Formular in left_menu_items_to_all_de statt
	// _s1 schrieb — die echte Navigation liest aber _s1, Eintrag blieb unsichtbar (Bug
	// 16.09.2026). CSite::GetDefSite() fragt stattdessen direkt die Site-Verwaltung ab, unabhaengig
	// vom Request-Kontext.
	private static function getSiteId(): string
	{
		return \CSite::GetDefSite() ?: 's1';
	}

	private static function getOptionName(): string
	{
		return 'left_menu_items_to_all_' . self::getSiteId();
	}

	private static function isOwnItem(array $item): bool
	{
		$id = (string)($item['ID'] ?? '');
		if (strpos($id, self::ID_PREFIX) === 0)
		{
			return true;
		}
		foreach (self::LEGACY_ID_PREFIXES as $prefix)
		{
			if ($id === $prefix || strpos($id, $prefix) === 0)
			{
				return true;
			}
		}
		return false;
	}

	// itsonix: kompletter Abgleich in einem Rutsch — eigene (+ Vorgänger-)Einträge erst raus,
	// dann bei aktiviertem Menü-Toggle für jede passende Config-Zeile neu rein. Deckt
	// Hinzufügen/Entfernen/Umbenennen/Umsortieren/Deaktivieren einheitlich ab, kein Diffing nötig.
	public static function sync(): void
	{
		$siteId = self::getSiteId();
		$optionName = self::getOptionName();
		$adminOption = Option::get('intranet', $optionName, '', $siteId);
		$items = $adminOption !== '' ? unserialize($adminOption, ['allowed_classes' => false]) : [];

		$items = array_values(array_filter($items, function ($item)
		{
			return !self::isOwnItem($item);
		}));

		if (Config::isMenuEnabled())
		{
			foreach (Config::getEntries() as $index => $entry)
			{
				if (!Config::appearsInMenu($entry))
				{
					continue;
				}
				$items[] = [
					'TEXT' => $entry['label'] !== '' ? $entry['label'] : $entry['url'],
					'LINK' => self::getLink($index),
					'ID' => self::getId($index),
					'NEW_PAGE' => 'N',
				];
			}
		}

		if (empty($items))
		{
			Option::delete('intranet', ['name' => $optionName, 'site_id' => $siteId]);
		}
		else
		{
			Option::set('intranet', $optionName, serialize($items), $siteId);
		}

		self::invalidateMenuCache();
	}

	// itsonix: für DoUninstall() — entfernt alle eigenen (+ Vorgänger-)Einträge unabhängig vom
	// MENU_ENABLED-Stand (Modul wird komplett deinstalliert, nicht nur die Menüeinträge
	// deaktiviert).
	public static function removeAll(): void
	{
		$siteId = self::getSiteId();
		$optionName = self::getOptionName();
		$adminOption = Option::get('intranet', $optionName, '', $siteId);

		if ($adminOption === '')
		{
			return;
		}

		$items = array_values(array_filter(
			unserialize($adminOption, ['allowed_classes' => false]),
			function ($item)
			{
				return !self::isOwnItem($item);
			}
		));

		if (empty($items))
		{
			Option::delete('intranet', ['name' => $optionName, 'site_id' => $siteId]);
		}
		else
		{
			Option::set('intranet', $optionName, serialize($items), $siteId);
		}

		self::invalidateMenuCache();
	}

	// itsonix: Bitrix24 rendert die linke Navigation i.d.R. über den Composite-/Turbo-Cache. Der
	// Core-eigene Controller (\Bitrix\Intranet\Controller\LeftMenu::processBeforeAction()) räumt
	// diesen VOR jeder Menü-Änderung weg — wir schreiben die Option aber direkt (kein Controller-
	// Aufruf), daher hier explizit nachgezogen. Ohne das bleibt ein neuer/geänderter Menüpunkt
	// unsichtbar, bis der Seiten-Cache anderweitig abläuft/geleert wird (siehe 15.09.2026:
	// Menüeintrag erschien beim Test nicht, obwohl Option korrekt gespeichert war).
	private static function invalidateMenuCache(): void
	{
		if (class_exists('\Bitrix\Intranet\Composite\CacheProvider'))
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteAllCache();
		}
		if (class_exists('\Bitrix\Intranet\Portal\FirstPage'))
		{
			\Bitrix\Intranet\Portal\FirstPage::getInstance()->clearCacheForAll();
		}
	}
}
