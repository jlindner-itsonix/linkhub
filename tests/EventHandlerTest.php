<?php

namespace Itsonix\LinkHub\Tests;

use Bitrix\Main\Config\Option;
use Itsonix\LinkHub\Config;
use Itsonix\LinkHub\EventHandler;
use Itsonix\LinkHub\MenuItem;
use Itsonix\LinkHub\Tests\Stubs\FakeApplication;
use PHPUnit\Framework\TestCase;

final class EventHandlerTest extends TestCase
{
	protected function setUp(): void
	{
		Option::resetForTests();
	}

	private function runOnProlog(): FakeApplication
	{
		global $APPLICATION;
		$APPLICATION = new FakeApplication();

		EventHandler::onProlog();

		return $APPLICATION;
	}

	public function testOnPrologAddsNothingWhenBothTogglesAreOff(): void
	{
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'N');
		Option::set(Config::MODULE_ID, 'POPUP_ENABLED', 'N');

		$app = $this->runOnProlog();

		self::assertSame([], $app->headStrings);
	}

	public function testOnPrologRendersPopupTilesForPopupFlaggedEntriesOnly(): void
	{
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'N');
		Option::set(Config::MODULE_ID, 'POPUP_ENABLED', 'Y');
		Config::setEntries([
			['label' => 'Menu Only', 'url' => '/menu-only/', 'showInMenu' => true, 'showInPopup' => false],
			['label' => 'AWU', 'url' => 'https://awu.example.com/', 'showInMenu' => false, 'showInPopup' => true],
		]);

		$html = implode('', $this->runOnProlog()->headStrings);

		self::assertStringContainsString('#itsonix-app-switcher-menu', $html, 'popup CSS must be present');
		self::assertStringContainsString('AWU', $html);
		self::assertStringContainsString('https://awu.example.com/', $html);
		self::assertStringNotContainsString('Menu Only', $html, 'menu-only entry must not show up as a popup tile');
		self::assertStringNotContainsString('__TILES__', $html, 'template placeholder must be substituted, not printed literally');
		self::assertStringNotContainsString('__SWITCHER_ICON__', $html);
	}

	public function testOnPrologOpensExternalPopupTargetsInNewTab(): void
	{
		Option::set(Config::MODULE_ID, 'POPUP_ENABLED', 'Y');
		Config::setEntries([
			['label' => 'External', 'url' => 'https://example.com/', 'showInMenu' => false, 'showInPopup' => true],
			['label' => 'Internal', 'url' => '/intern/', 'showInMenu' => false, 'showInPopup' => true],
		]);

		$html = implode('', $this->runOnProlog()->headStrings);

		self::assertMatchesRegularExpression('~<a href="https://example\.com/" target="_blank" rel="noopener">~', $html);
		self::assertDoesNotMatchRegularExpression('~<a href="/intern/"[^>]*target="_blank"~', $html);
	}

	public function testOnPrologSkipsPopupBlockWhenNoEntryIsFlaggedForIt(): void
	{
		Option::set(Config::MODULE_ID, 'POPUP_ENABLED', 'Y');
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'N');
		Config::setEntries([
			['label' => 'Menu Only', 'url' => '/menu-only/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		$app = $this->runOnProlog();

		self::assertSame([], $app->headStrings, 'no popup entries and menu disabled -> nothing to render');
	}

	public function testOnPrologRendersMenuIconCssTargetingTheEntrysDataLinkSelector(): void
	{
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'Y');
		Option::set(Config::MODULE_ID, 'POPUP_ENABLED', 'N');
		Config::setEntries([
			['label' => 'Not in menu', 'url' => '/skip/', 'showInMenu' => false, 'showInPopup' => false],
			['label' => 'In menu', 'url' => '/xwiki/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		$html = implode('', $this->runOnProlog()->headStrings);
		$expectedLink = MenuItem::getLink(1);

		self::assertStringContainsString('li[data-link="' . $expectedLink . '"] .menu-item-icon', $html);
		self::assertStringNotContainsString('__LINK__', $html);
		self::assertStringNotContainsString('__ICON_DATA_URI__', $html);
	}
}
