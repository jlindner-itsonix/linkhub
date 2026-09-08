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

	private static function getSiteId(): string
	{
		return defined('SITE_ID') ? SITE_ID : 's1';
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
	}
}
