<?php

namespace Itsonix\LinkHub\Tests;

use Bitrix\Intranet\Composite\CacheProvider;
use Bitrix\Intranet\Portal\FirstPage;
use Bitrix\Main\Config\Option;
use Itsonix\LinkHub\Config;
use Itsonix\LinkHub\MenuItem;
use PHPUnit\Framework\TestCase;

final class MenuItemTest extends TestCase
{
	private const OPTION_NAME = 'left_menu_items_to_all_s1';

	protected function setUp(): void
	{
		Option::resetForTests();
		CacheProvider::resetForTests();
		FirstPage::resetForTests();
		\CSite::resetForTests();
	}

	private function storedMenuItems(): array
	{
		$raw = Option::get('intranet', self::OPTION_NAME, '', 's1');
		return $raw === '' ? [] : unserialize($raw, ['allowed_classes' => false]);
	}

	public function testGetIdAndGetLinkAreIndexBased(): void
	{
		self::assertSame('menu_itsonix_linkhub_2', MenuItem::getId(2));
		self::assertSame('/local/linkhub/?entry=2', MenuItem::getLink(2));
	}

	public function testSyncAddsOneEntryPerMenuFlaggedConfigRowAtItsFullListIndex(): void
	{
		Config::setEntries([
			['label' => 'Popup only', 'url' => '/a/', 'showInMenu' => false, 'showInPopup' => true],
			['label' => 'Menu entry', 'url' => '/b/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		MenuItem::sync();

		$items = $this->storedMenuItems();
		self::assertCount(1, $items);
		// itsonix: Index 1, weil "Menu entry" die zweite Zeile in der VOLLEN Entries-Liste ist —
		// siehe MenuItem-Klassenkommentar (Index muss mit EventHandler/local/linkhub/index.php
		// uebereinstimmen).
		self::assertSame('menu_itsonix_linkhub_1', $items[0]['ID']);
		self::assertSame('/local/linkhub/?entry=1', $items[0]['LINK']);
		self::assertSame('Menu entry', $items[0]['TEXT']);
		self::assertSame('N', $items[0]['NEW_PAGE']);
	}

	public function testSyncFallsBackToUrlAsTextWhenLabelIsEmpty(): void
	{
		Config::setEntries([
			['label' => '', 'url' => '/no-label/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		MenuItem::sync();

		self::assertSame('/no-label/', $this->storedMenuItems()[0]['TEXT']);
	}

	public function testSyncRemovesOwnEntriesWhenMenuToggleIsDisabled(): void
	{
		Option::set('intranet', self::OPTION_NAME, serialize([
			['ID' => 'menu_itsonix_linkhub_0', 'TEXT' => 'Stale', 'LINK' => '/local/linkhub/?entry=0', 'NEW_PAGE' => 'N'],
		]), 's1');
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'N');

		MenuItem::sync();

		self::assertSame([], $this->storedMenuItems());
		self::assertSame('', Option::get('intranet', self::OPTION_NAME, '', 's1'), 'option must be deleted, not left as an empty array');
	}

	public function testSyncLeavesForeignMenuItemsUntouched(): void
	{
		Option::set('intranet', self::OPTION_NAME, serialize([
			['ID' => 'menu_some_other_app', 'TEXT' => 'Other app', 'LINK' => '/other/', 'NEW_PAGE' => 'N'],
			['ID' => 'menu_itsonix_linkhub_0', 'TEXT' => 'Stale', 'LINK' => '/local/linkhub/?entry=0', 'NEW_PAGE' => 'N'],
		]), 's1');
		Config::setEntries([
			['label' => 'Fresh', 'url' => '/fresh/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		MenuItem::sync();

		$items = $this->storedMenuItems();
		self::assertCount(2, $items);
		self::assertSame('menu_some_other_app', $items[0]['ID'], 'foreign entry must survive sync() untouched and keep its position');
		self::assertSame('menu_itsonix_linkhub_0', $items[1]['ID']);
		self::assertSame('Fresh', $items[1]['TEXT'], 'own entry must be recomputed from current Config, not just left as-is');
	}

	public function testSyncCleansUpLegacyXwikiPrefixedEntriesFromThePredecessorModule(): void
	{
		Option::set('intranet', self::OPTION_NAME, serialize([
			// itsonix: eine Zeile ganz ohne Index (aelteste Vorgaenger-Version), eine mit Index.
			['ID' => 'menu_itsonix_xwiki', 'TEXT' => 'Old XWiki', 'LINK' => '/xwiki/', 'NEW_PAGE' => 'N'],
			['ID' => 'menu_itsonix_xwiki_2', 'TEXT' => 'Old XWiki 2', 'LINK' => '/xwiki/', 'NEW_PAGE' => 'N'],
		]), 's1');
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'N');
		Config::setEntries([]);

		MenuItem::sync();

		self::assertSame('', Option::get('intranet', self::OPTION_NAME, '', 's1'));
	}

	public function testSyncIgnoresAMisleadingAmbientSiteIdConstant(): void
	{
		// itsonix: Regressionstest fuer einen echten Bug (16.09.2026) — im Bitrix-Admin-Kontext
		// (options.php-Speichern) gibt es oft kein aufloesbares Site-Objekt, dann faellt Bitrix'
		// eigener Kernel-Bootstrap auf SITE_ID=LANG zurueck (bitrix/modules/main/include.php),
		// also die Admin-UI-Sprache ("de") statt der echten Portal-Site ("s1"). sync() schrieb
		// dadurch in left_menu_items_to_all_de statt _s1 — die echte Navigation liest aber _s1,
		// Eintrag blieb unsichtbar. getSiteId() darf sich daher NICHT auf die SITE_ID-Konstante
		// verlassen, sondern muss CSite::GetDefSite() fragen.
		if (!defined('SITE_ID'))
		{
			define('SITE_ID', 'de');
		}

		Config::setEntries([
			['label' => 'XWiki', 'url' => '/xwiki/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		MenuItem::sync();

		self::assertCount(1, $this->storedMenuItems(), 'sync() muss trotz falscher SITE_ID-Konstante in left_menu_items_to_all_s1 schreiben');
		self::assertSame('', Option::get('intranet', 'left_menu_items_to_all_de', '', 'de'), 'darf NICHT in die durch die Konstante nahegelegte falsche Site schreiben');
	}

	public function testSyncInvalidatesCompositeAndFirstPageCache(): void
	{
		// itsonix: Regressionstest fuer den Bug "Menuepunkt erscheint beim Test nicht" — sync()
		// schreibt die Option direkt (kein Controller-Aufruf), muss den Composite-/FirstPage-Cache
		// deshalb selbst wegraeumen (siehe lib/MenuItem.php::invalidateMenuCache()).
		MenuItem::sync();

		self::assertSame(1, CacheProvider::$deleteAllCacheCallCount);
		self::assertSame(1, FirstPage::$clearCacheForAllCallCount);
	}

	public function testRemoveAllStripsOwnAndLegacyItemsRegardlessOfMenuToggle(): void
	{
		Option::set('intranet', self::OPTION_NAME, serialize([
			['ID' => 'menu_some_other_app', 'TEXT' => 'Other app', 'LINK' => '/other/', 'NEW_PAGE' => 'N'],
			['ID' => 'menu_itsonix_linkhub_0', 'TEXT' => 'Mine', 'LINK' => '/local/linkhub/?entry=0', 'NEW_PAGE' => 'N'],
			['ID' => 'menu_itsonix_xwiki', 'TEXT' => 'Legacy', 'LINK' => '/xwiki/', 'NEW_PAGE' => 'N'],
		]), 's1');
		Option::set(Config::MODULE_ID, 'MENU_ENABLED', 'Y');

		MenuItem::removeAll();

		$items = $this->storedMenuItems();
		self::assertCount(1, $items);
		self::assertSame('menu_some_other_app', $items[0]['ID']);
	}

	public function testRemoveAllDeletesTheOptionWhenNothingForeignIsLeft(): void
	{
		Option::set('intranet', self::OPTION_NAME, serialize([
			['ID' => 'menu_itsonix_linkhub_0', 'TEXT' => 'Mine', 'LINK' => '/local/linkhub/?entry=0', 'NEW_PAGE' => 'N'],
		]), 's1');

		MenuItem::removeAll();

		self::assertSame('', Option::get('intranet', self::OPTION_NAME, '', 's1'));
	}

	public function testRemoveAllIsANoOpWhenTheOptionWasNeverSet(): void
	{
		MenuItem::removeAll();

		self::assertSame('', Option::get('intranet', self::OPTION_NAME, '', 's1'));
	}
}
