<?php

namespace Itsonix\LinkHub\Tests;

use Bitrix\Intranet\Composite\CacheProvider;
use Bitrix\Intranet\Portal\FirstPage;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Itsonix\LinkHub\Tests\Stubs\FakeUser;
use PHPUnit\Framework\TestCase;

final class InstallTest extends TestCase
{
	private const OPTION_NAME = 'left_menu_items_to_all_s1';

	protected function setUp(): void
	{
		Option::resetForTests();
		EventManager::resetForTests();
		ModuleManager::resetForTests();
		CacheProvider::resetForTests();
		FirstPage::resetForTests();
	}

	private function newModule(bool $isAdmin): object
	{
		global $USER;
		$USER = new FakeUser($isAdmin);

		// itsonix: install/index.php guardet Klassen-/Require-Redeklaration schon selbst
		// (class_exists-Checks) — require_once reicht, auch ueber mehrere Tests hinweg.
		require_once __DIR__ . '/../itsonix.linkhub/install/index.php';

		return new \itsonix_linkhub();
	}

	public function testDoInstallRegistersModuleAndOnPrologEventAndSyncsDefaultMenu(): void
	{
		$module = $this->newModule(true);

		$module->DoInstall();

		self::assertSame(['itsonix.linkhub'], ModuleManager::$registered);

		$registeredEvents = EventManager::getInstance()->registered;
		self::assertCount(1, $registeredEvents);
		self::assertSame(['main', 'OnProlog', 'itsonix.linkhub', '\Itsonix\LinkHub\EventHandler', 'onProlog'], $registeredEvents[0]);

		// itsonix: Default-Entries (siehe Config::getEntries()) haben genau einen
		// showInMenu=true-Eintrag (XWiki) -> genau ein Menuepunkt wird angelegt.
		$stored = unserialize(Option::get('intranet', self::OPTION_NAME, '', 's1'), ['allowed_classes' => false]);
		self::assertCount(1, $stored);
		self::assertSame('menu_itsonix_linkhub_0', $stored[0]['ID']);
	}

	public function testDoInstallIsRefusedForNonAdminUsers(): void
	{
		$module = $this->newModule(false);

		$module->DoInstall();

		self::assertSame([], ModuleManager::$registered);
		self::assertSame([], EventManager::getInstance()->registered);
		self::assertSame('', Option::get('intranet', self::OPTION_NAME, '', 's1'));
	}

	public function testDoUninstallUnregistersEventAndRemovesOwnMenuItemsOnly(): void
	{
		$module = $this->newModule(true);
		$module->DoInstall();

		// itsonix: ein fremder Menuepunkt (anderes Modul) darf DoUninstall() nicht zum Opfer
		// fallen — MenuItem::removeAll() filtert per ID-Praefix.
		$foreign = unserialize(Option::get('intranet', self::OPTION_NAME, '', 's1'), ['allowed_classes' => false]);
		$foreign[] = ['ID' => 'menu_some_other_app', 'TEXT' => 'Other', 'LINK' => '/other/', 'NEW_PAGE' => 'N'];
		Option::set('intranet', self::OPTION_NAME, serialize($foreign), 's1');

		$module->DoUninstall();

		self::assertSame(['itsonix.linkhub'], ModuleManager::$unregistered);
		self::assertCount(1, EventManager::getInstance()->unregistered);

		$remaining = unserialize(Option::get('intranet', self::OPTION_NAME, '', 's1'), ['allowed_classes' => false]);
		self::assertSame([['ID' => 'menu_some_other_app', 'TEXT' => 'Other', 'LINK' => '/other/', 'NEW_PAGE' => 'N']], $remaining);
	}

	public function testDoUninstallIsRefusedForNonAdminUsers(): void
	{
		$module = $this->newModule(true);
		$module->DoInstall();

		global $USER;
		$USER = new FakeUser(false);
		$module->DoUninstall();

		self::assertSame([], ModuleManager::$unregistered);
		self::assertNotSame('', Option::get('intranet', self::OPTION_NAME, '', 's1'), 'menu item must survive a refused uninstall attempt');
	}
}
