<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

// itsonix: PSR-4-Autoload für dieses Modul greift erst ab dem nächsten Request (Namespace-Map
// wird beim Kernel-Init aus registrierten Modulen aufgebaut) — im selben Request, in dem
// DoInstall() das Modul registriert, ist \Itsonix\LinkHub\... noch nicht autoloadbar.
// class_exists(..., false)-Guard nötig, weil Bitrix' Admin-Modulliste install/index.php pro
// Zeile per include() (nicht include_once) erneut einbindet — ohne Guard "Cannot declare
// class", wenn die Klasse bereits per Autoload (aus einem früheren Aufruf in diesem Request)
// geladen wurde.
if (!class_exists('\Itsonix\LinkHub\Config', false))
{
	require_once __DIR__ . '/../lib/Config.php';
}
if (!class_exists('\Itsonix\LinkHub\MenuItem', false))
{
	require_once __DIR__ . '/../lib/MenuItem.php';
}

if (class_exists("itsonix_linkhub"))
	return;

class itsonix_linkhub extends CModule
{
	var $MODULE_ID = "itsonix.linkhub";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("ITSONIX_LINKHUB_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("ITSONIX_LINKHUB_MODULE_DESCRIPTION");
	}

	private function registerEvent(): void
	{
		// itsonix: registerEventHandler() dedupliziert selbst per INSERT IGNORE auf einer
		// aus allen Parametern gebildeten UNIQUE_ID (eventmanager.php) — kein Vorab-Check nötig.
		EventManager::getInstance()->registerEventHandler('main', 'OnProlog', $this->MODULE_ID, '\Itsonix\LinkHub\EventHandler', 'onProlog');
	}

	private function unregisterEvent(): void
	{
		EventManager::getInstance()->unRegisterEventHandler('main', 'OnProlog', $this->MODULE_ID, '\Itsonix\LinkHub\EventHandler', 'onProlog');
	}

	public function DoInstall()
	{
		global $USER;

		if (!$USER->IsAdmin())
		{
			return;
		}

		ModuleManager::registerModule($this->MODULE_ID);
		$this->registerEvent();
		\Itsonix\LinkHub\MenuItem::sync();
	}

	public function DoUninstall()
	{
		global $USER;

		if (!$USER->IsAdmin())
		{
			return;
		}

		$this->unregisterEvent();
		\Itsonix\LinkHub\MenuItem::removeAll();
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}
}
