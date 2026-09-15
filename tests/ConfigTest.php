<?php

namespace Itsonix\LinkHub\Tests;

use Bitrix\Main\Config\Option;
use Itsonix\LinkHub\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
	protected function setUp(): void
	{
		Option::resetForTests();
	}

	public function testFreshInstallDefaultsMirrorThePredecessorModules(): void
	{
		$entries = Config::getEntries();

		self::assertCount(3, $entries);

		self::assertSame('XWiki', $entries[0]['label']);
		self::assertTrue($entries[0]['showInMenu']);
		self::assertFalse($entries[0]['showInPopup']);
		self::assertFalse($entries[0]['external'], 'internal /xwiki/ path must not be treated as external');

		self::assertSame('AWU', $entries[1]['label']);
		self::assertFalse($entries[1]['showInMenu']);
		self::assertTrue($entries[1]['showInPopup']);
		self::assertTrue($entries[1]['external']);

		self::assertSame('SkillDB', $entries[2]['label']);
		self::assertTrue($entries[2]['external']);
	}

	public function testGetEntriesDropsRowsWithEmptyUrl(): void
	{
		Config::setEntries([
			['label' => 'Keep', 'url' => '/keep/', 'showInMenu' => true, 'showInPopup' => false],
			['label' => 'Drop me', 'url' => '   ', 'showInMenu' => true, 'showInPopup' => true],
		]);

		$entries = Config::getEntries();

		self::assertCount(1, $entries);
		self::assertSame('Keep', $entries[0]['label']);
	}

	public function testSetEntriesRoundTripsShowFlagsAndDerivesExternal(): void
	{
		Config::setEntries([
			['label' => 'Internal', 'url' => '/tool/', 'showInMenu' => true, 'showInPopup' => true],
			['label' => 'External', 'url' => 'https://example.com/', 'showInMenu' => false, 'showInPopup' => true],
		]);

		$entries = Config::getEntries();

		self::assertFalse($entries[0]['external']);
		self::assertTrue($entries[0]['showInMenu']);
		self::assertTrue($entries[0]['showInPopup']);

		self::assertTrue($entries[1]['external']);
		self::assertFalse($entries[1]['showInMenu']);
	}

	public function testLegacyModeFieldIsMigratedToShowInMenuAndShowInPopup(): void
	{
		// itsonix: Format der allerersten Modul-Version, vor der showInMenu/showInPopup-Umstellung
		// (siehe Config::getEntries()-Kommentar zur Migration).
		Option::set(Config::MODULE_ID, 'ENTRIES', serialize([
			['label' => 'MenuOnly', 'url' => '/a/', 'mode' => Config::MODE_MENU],
			['label' => 'PopupOnly', 'url' => '/b/', 'mode' => Config::MODE_POPUP],
			['label' => 'Both', 'url' => '/c/', 'mode' => Config::MODE_BOTH],
		]));

		$entries = Config::getEntries();

		self::assertTrue($entries[0]['showInMenu']);
		self::assertFalse($entries[0]['showInPopup']);

		self::assertFalse($entries[1]['showInMenu']);
		self::assertTrue($entries[1]['showInPopup']);

		self::assertTrue($entries[2]['showInMenu']);
		self::assertTrue($entries[2]['showInPopup']);
	}

	public function testMenuEnabledDefaultsTrueAndPopupEnabledDefaultsFalse(): void
	{
		self::assertTrue(Config::isMenuEnabled());
		self::assertFalse(Config::isPopupEnabled());
	}

	public function testTogglesReflectStoredOption(): void
	{
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'N');
		Option::set(Config::MODULE_ID, 'POPUP_ENABLED', 'Y');

		self::assertFalse(Config::isMenuEnabled());
		self::assertTrue(Config::isPopupEnabled());
	}

	public function testIframeHeightModeFallsBackToViewportOnInvalidStoredValue(): void
	{
		Option::set(Config::MODULE_ID, 'IFRAME_HEIGHT_MODE', 'not-a-real-mode');

		self::assertSame(Config::HEIGHT_MODE_VIEWPORT, Config::getIframeHeightMode());
	}

	public function testIframeHeightModeAcceptsFixed(): void
	{
		Option::set(Config::MODULE_ID, 'IFRAME_HEIGHT_MODE', Config::HEIGHT_MODE_FIXED);

		self::assertSame(Config::HEIGHT_MODE_FIXED, Config::getIframeHeightMode());
	}

	public function testIframeHeightPxDefaultsTo800AndIsCastToInt(): void
	{
		self::assertSame(800, Config::getIframeHeightPx());

		Option::set(Config::MODULE_ID, 'IFRAME_HEIGHT_PX', '450');
		self::assertSame(450, Config::getIframeHeightPx());
	}

	public function testAppearsInMenuAndAppearsInPopupReadTheFlagsVerbatim(): void
	{
		$entry = ['showInMenu' => true, 'showInPopup' => false];

		self::assertTrue(Config::appearsInMenu($entry));
		self::assertFalse(Config::appearsInPopup($entry));
	}
}
