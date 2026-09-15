<?php

namespace Bitrix\Intranet\Composite;

// itsonix: Stand-in fuer \Bitrix\Intranet\Composite\CacheProvider — zaehlt nur Aufrufe, damit
// MenuItem::invalidateMenuCache() (siehe lib/MenuItem.php) darauf getestet werden kann, dass es
// den Composite-Cache nach jeder Menue-Aenderung tatsaechlich wegraeumt.
class CacheProvider
{
	public static int $deleteAllCacheCallCount = 0;

	public static function deleteAllCache(): void
	{
		self::$deleteAllCacheCallCount++;
	}

	public static function resetForTests(): void
	{
		self::$deleteAllCacheCallCount = 0;
	}
}
