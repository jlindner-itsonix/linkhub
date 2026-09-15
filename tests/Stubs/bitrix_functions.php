<?php

// itsonix: Stand-in fuer Bitrix' globale htmlspecialcharsbx() (BX_UTF-bewusstes
// htmlspecialchars) — EventHandler.php ruft sie fuer jede Ausgabe von Nutzereingaben
// (Label/URL/Link) auf. Fuer Tests reicht die Standard-htmlspecialchars()-Semantik.
if (!function_exists('htmlspecialcharsbx'))
{
	function htmlspecialcharsbx($string)
	{
		return htmlspecialchars((string)$string, ENT_QUOTES);
	}
}
