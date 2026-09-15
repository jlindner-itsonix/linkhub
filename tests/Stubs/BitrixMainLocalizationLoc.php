<?php

namespace Bitrix\Main\Localization;

// itsonix: Stand-in fuer Bitrix\Main\Localization\Loc — gibt den Message-Code selbst zurueck
// statt einer echten Uebersetzung, reicht fuer Tests, die nur pruefen DASS/WIE etwas gerendert
// wird, nicht den genauen Uebersetzungstext.
class Loc
{
	public static function loadMessages($file): bool
	{
		return true;
	}

	public static function getMessage($code, $replace = null): ?string
	{
		return $code;
	}
}
