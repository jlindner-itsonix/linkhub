<?php

namespace Bitrix\Main;

// itsonix: Stand-in fuer Bitrix\Main\EventManager — zeichnet register/unregister-Aufrufe auf,
// damit install/index.php (DoInstall/DoUninstall) getestet werden kann, ohne den echten
// Event-Kernel zu brauchen.
class EventManager
{
	private static ?self $instance = null;

	public array $registered = [];
	public array $unregistered = [];

	public static function getInstance(): self
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function registerEventHandler($module, $event, $fromModule, $class, $method): void
	{
		$this->registered[] = [$module, $event, $fromModule, $class, $method];
	}

	public function unRegisterEventHandler($module, $event, $fromModule, $class, $method): void
	{
		$this->unregistered[] = [$module, $event, $fromModule, $class, $method];
	}

	public static function resetForTests(): void
	{
		self::$instance = null;
	}
}
