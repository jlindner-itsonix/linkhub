<?php

namespace Itsonix\LinkHub\Tests;

use Bitrix\Main\Config\Option;
use Itsonix\LinkHub\Config;
use Itsonix\LinkHub\Tests\Stubs\FakeApplication;
use Itsonix\LinkHub\Tests\Stubs\FakeTabControl;
use PHPUnit\Framework\TestCase;

// itsonix: Regressionstest fuer einen echten Bug (16.09.2026) — ui/options_view.php nutzt
// Loc::getMessage()/Config::* unqualifiziert, aber als per require() eingebundene EIGENSTAENDIGE
// Datei ohne eigene use-Statements gilt der Import aus options.php NICHT (PHP-Imports sind pro
// Datei, nicht pro Request) -> "Class Loc not found" beim echten Aufruf der Options-Seite. Kein
// Smoke-Test fuer options.php als Ganzes (siehe doc/admin-options-form.md, POST-Handling braucht
// echtes Bitrix-Admin-Framework) — aber DAS hier, das reine Rendern des View-Templates mit ein
// paar Stubs, ist isolierbar und haette diesen Bug gefangen.
final class OptionsViewTest extends TestCase
{
	protected function setUp(): void
	{
		Option::resetForTests();
		if (!defined('LANGUAGE_ID'))
		{
			define('LANGUAGE_ID', 'de');
		}
	}

	public function testViewRendersWithoutThrowing(): void
	{
		$html = $this->renderView();

		self::assertStringContainsString('<form', $html);
		self::assertStringContainsString('itsonix-linkhub-entries', $html);
	}

	public function testViewRendersConfiguredEntries(): void
	{
		Config::setEntries([
			['label' => 'Mein Tool', 'url' => '/mein-tool/', 'showInMenu' => true, 'showInPopup' => false],
		]);

		$html = $this->renderView();

		self::assertStringContainsString('Mein Tool', $html);
		self::assertStringContainsString('/mein-tool/', $html);
	}

	private function renderView(): string
	{
		global $APPLICATION;
		$APPLICATION = new FakeApplication();

		$module_id = 'itsonix.linkhub';
		$tabControl = new FakeTabControl();

		ob_start();
		try
		{
			require __DIR__ . '/../itsonix.linkhub/ui/options_view.php';
			return ob_get_clean();
		}
		catch (\Throwable $e)
		{
			ob_end_clean();
			throw $e;
		}
	}
}
