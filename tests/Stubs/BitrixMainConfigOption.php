<?php

namespace Bitrix\Main\Config;

// itsonix: In-Memory-Stand-in fuer Bitrix\Main\Config\Option — nur die drei Methoden, die
// Config/MenuItem tatsaechlich aufrufen, Signaturen 1:1 aus bitrix/modules/main/lib/config/
// option.php abgeschrieben. Kein DB-Zugriff, kein Bitrix-Kernel noetig, damit die Modul-Logik
// isoliert per PHPUnit getestet werden kann.
class Option
{
	private static array $store = [];

	public static function get($moduleId, $name, $default = '', $siteId = false)
	{
		$key = self::key($moduleId, $name, $siteId);
		return array_key_exists($key, self::$store) ? self::$store[$key] : $default;
	}

	public static function set($moduleId, $name, $value = '', $siteId = '')
	{
		self::$store[self::key($moduleId, $name, $siteId)] = $value;
	}

	public static function delete($moduleId, array $filter = [])
	{
		$name = $filter['name'] ?? '';
		$siteId = $filter['site_id'] ?? '';
		unset(self::$store[self::key($moduleId, $name, $siteId)]);
	}

	// itsonix: Test-Helfer — kein Pendant im echten Bitrix, nur um zwischen Tests sauber
	// zurueckzusetzen.
	public static function resetForTests(): void
	{
		self::$store = [];
	}

	private static function key($moduleId, $name, $siteId): string
	{
		return mb_strtolower((string)$moduleId) . '|' . mb_strtolower((string)$name) . '|' . ($siteId === false ? '' : $siteId);
	}
}
