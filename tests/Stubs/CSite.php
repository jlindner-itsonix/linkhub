<?php

// itsonix: Stand-in fuer Bitrix' globale CSite — MenuItem::getSiteId() ruft nur GetDefSite() auf.
class CSite
{
	public static string $defaultSiteId = 's1';

	public static function GetDefSite($LID = false)
	{
		return self::$defaultSiteId;
	}

	public static function resetForTests(): void
	{
		self::$defaultSiteId = 's1';
	}
}
