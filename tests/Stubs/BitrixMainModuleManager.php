<?php

namespace Bitrix\Main;

// itsonix: Stand-in fuer Bitrix\Main\ModuleManager — DoInstall()/DoUninstall() rufen nur
// registerModule()/unRegisterModule() auf, hier als reine Aufzeichnung ohne DB-Registrierung.
class ModuleManager
{
	public static array $registered = [];
	public static array $unregistered = [];

	public static function registerModule($moduleId): void
	{
		self::$registered[] = $moduleId;
	}

	public static function unRegisterModule($moduleId): void
	{
		self::$unregistered[] = $moduleId;
	}

	public static function resetForTests(): void
	{
		self::$registered = [];
		self::$unregistered = [];
	}
}
