<?php

namespace Bitrix\Intranet\Portal;

// itsonix: Stand-in fuer \Bitrix\Intranet\Portal\FirstPage — zaehlt nur Aufrufe, Pendant zu
// BitrixIntranetCompositeCacheProvider.php (siehe dort).
class FirstPage
{
	private static ?self $instance = null;

	public static int $clearCacheForAllCallCount = 0;

	public static function getInstance(): self
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function clearCacheForAll(): void
	{
		self::$clearCacheForAllCallCount++;
	}

	public static function resetForTests(): void
	{
		self::$instance = null;
		self::$clearCacheForAllCallCount = 0;
	}
}
