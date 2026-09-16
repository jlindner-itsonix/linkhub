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

// itsonix: Stand-in fuer Bitrix' globale bitrix_sessid_post() — rendert normalerweise ein
// verstecktes CSRF-Token-Feld ins Formular. ui/options_view.php ruft sie auf.
if (!function_exists('bitrix_sessid_post'))
{
	function bitrix_sessid_post()
	{
		return '<input type="hidden" name="sessid" value="test-sessid">';
	}
}
