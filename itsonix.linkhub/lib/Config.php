<?php

namespace Itsonix\LinkHub;

use Bitrix\Main\Config\Option;

// itsonix: Namespace-Basis für dotted Modul-IDs ist NICHT "Bitrix\<Modul>", sondern
// str_replace(".", "\\", ucwords($moduleId, ".")) — siehe modules/main/lib/loader.php:121-131.
// "itsonix.linkhub" -> "Itsonix\Linkhub" (PHP-Klassenname case-insensitiv, "LinkHub" hier nur
// für Lesbarkeit).
//
// itsonix: Vereinigung der vormals getrennten Module itsonix.xwiki (Menüeintrag + iFrame) und
// itsonix.appswitcher (Popup am Sidebar-Logo) — jeder Eintrag hat zwei unabhängige Checkboxen
// ("Menüeintrag" / "Popup") statt eines einzelnen Modus-Felds, damit beide, eine oder keine der
// beiden Darstellungen frei kombinierbar sind (auch "keine von beiden" = Eintrag vorübergehend
// stillgelegt, ohne ihn zu löschen).
class Config
{
	const MODULE_ID = 'itsonix.linkhub';

	// itsonix: nur noch für die Migration alter 'mode'-Werte (menu/popup/both) aus der ersten
	// Version dieses Moduls gebraucht — siehe getEntries().
	const MODE_MENU = 'menu';
	const MODE_POPUP = 'popup';
	const MODE_BOTH = 'both';

	const HEIGHT_MODE_VIEWPORT = 'viewport';
	const HEIGHT_MODE_FIXED = 'fixed';

	public static function isPopupEnabled(): bool
	{
		return Option::get(self::MODULE_ID, 'POPUP_ENABLED', 'N') === 'Y';
	}

	public static function isMenuEnabled(): bool
	{
		return Option::get(self::MODULE_ID, 'MENU_ENABLED', 'Y') === 'Y';
	}

	public static function getIframeHeightMode(): string
	{
		$mode = Option::get(self::MODULE_ID, 'IFRAME_HEIGHT_MODE', self::HEIGHT_MODE_VIEWPORT);
		return in_array($mode, [self::HEIGHT_MODE_VIEWPORT, self::HEIGHT_MODE_FIXED], true)
			? $mode
			: self::HEIGHT_MODE_VIEWPORT;
	}

	public static function getIframeHeightPx(): int
	{
		return (int)Option::get(self::MODULE_ID, 'IFRAME_HEIGHT_PX', 800);
	}

	// itsonix: beliebig viele Einträge, dynamisch per +/× in den Options gepflegt — als ein
	// serialisiertes Array in der Option ENTRIES gespeichert
	// (Format: [['label'=>..,'url'=>..,'showInMenu'=>bool,'showInPopup'=>bool], ...]).
	// 'external' wird aus der URL abgeleitet, nicht gespeichert.
	public static function getEntries(): array
	{
		$raw = Option::get(self::MODULE_ID, 'ENTRIES', '');
		if ($raw !== '')
		{
			$stored = @unserialize($raw, ['allowed_classes' => false]);
			$stored = is_array($stored) ? array_values($stored) : [];
		}
		else
		{
			// itsonix: Default für komplett frische Installationen — vereint die bisherigen
			// Defaults beider Vorgänger-Module (XWiki als Menüeintrag+iFrame, AWU/SkillDB nur
			// als Popup-Kacheln).
			$stored = [
				['label' => 'XWiki', 'url' => '/xwiki/', 'showInMenu' => true, 'showInPopup' => false],
				['label' => 'AWU', 'url' => 'https://awu.itsonix.eu/', 'showInMenu' => false, 'showInPopup' => true],
				['label' => 'SkillDB', 'url' => 'https://skilldb.stage.itsinternal.de/', 'showInMenu' => false, 'showInPopup' => true],
			];
		}

		$entries = [];
		foreach ($stored as $entry)
		{
			$url = trim((string)($entry['url'] ?? ''));
			if ($url === '')
			{
				continue;
			}

			if (array_key_exists('showInMenu', $entry) || array_key_exists('showInPopup', $entry))
			{
				$showInMenu = (bool)($entry['showInMenu'] ?? false);
				$showInPopup = (bool)($entry['showInPopup'] ?? false);
			}
			else
			{
				// itsonix: Migration von der Vorgänger-Struktur (einzelnes 'mode'-Feld
				// menu/popup/both) — nur relevant für vor diesem Umbau gespeicherte Einträge.
				$mode = (string)($entry['mode'] ?? self::MODE_POPUP);
				$showInMenu = in_array($mode, [self::MODE_MENU, self::MODE_BOTH], true);
				$showInPopup = in_array($mode, [self::MODE_POPUP, self::MODE_BOTH], true);
			}

			$entries[] = [
				'label' => (string)($entry['label'] ?? ''),
				'url' => $url,
				'showInMenu' => $showInMenu,
				'showInPopup' => $showInPopup,
				// itsonix: externe Ziele (andere Domain) im neuen Tab öffnen (Popup-Kacheln),
				// damit die Bitrix24-Session/-Tab nicht verloren geht.
				'external' => (bool)preg_match('~^https?://~i', $url),
			];
		}
		return $entries;
	}

	public static function setEntries(array $entries): void
	{
		Option::set(self::MODULE_ID, 'ENTRIES', serialize(array_values($entries)));
	}

	public static function appearsInMenu(array $entry): bool
	{
		return $entry['showInMenu'];
	}

	public static function appearsInPopup(array $entry): bool
	{
		return $entry['showInPopup'];
	}
}
