<?php

$module_id = "itsonix.linkhub";

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Itsonix\LinkHub\Config;
use Itsonix\LinkHub\MenuItem;

if (!$USER->IsAdmin())
{
	return;
}

Loader::includeModule($module_id);
IncludeModuleLangFile(__FILE__);

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_REQUEST["Update"] ?? "") !== "" && check_bitrix_sessid())
{
	// itsonix: LABEL/URL/SHOW_MENU/SHOW_POPUP sind assoziative Arrays, alle mit derselben
	// Zeilen-ID als Schlüssel (siehe Options-Formular in ui/options_view.php) — nicht
	// positionsbasiert, weil ein nicht angehaktes Checkbox-Feld vom Browser gar nicht mitgeschickt
	// wird und eine rein sequenzielle []-Zuordnung sonst die Zeilen verschieben würde. Leere
	// URL-Zeilen verworfen.
	$labels = $_POST['LABEL'] ?? [];
	$urls = $_POST['URL'] ?? [];
	$showMenu = $_POST['SHOW_MENU'] ?? [];
	$showPopup = $_POST['SHOW_POPUP'] ?? [];
	$entries = [];
	foreach ($urls as $key => $url)
	{
		$url = trim((string)$url);
		if ($url === '')
		{
			continue;
		}
		$entries[] = [
			'label' => trim((string)($labels[$key] ?? '')),
			'url' => $url,
			'showInMenu' => ($showMenu[$key] ?? '') === 'Y',
			'showInPopup' => ($showPopup[$key] ?? '') === 'Y',
		];
	}
	Config::setEntries($entries);

	Option::set($module_id, 'POPUP_ENABLED', ($_POST['POPUP_ENABLED'] ?? '') === 'Y' ? 'Y' : 'N');
	Option::set($module_id, 'MENU_ENABLED', ($_POST['MENU_ENABLED'] ?? '') === 'Y' ? 'Y' : 'N');

	$heightMode = ($_POST['IFRAME_HEIGHT_MODE'] ?? '') === Config::HEIGHT_MODE_FIXED
		? Config::HEIGHT_MODE_FIXED
		: Config::HEIGHT_MODE_VIEWPORT;
	Option::set($module_id, 'IFRAME_HEIGHT_MODE', $heightMode);
	Option::set($module_id, 'IFRAME_HEIGHT_PX', (int)($_POST['IFRAME_HEIGHT_PX'] ?? Config::getIframeHeightPx()));

	// itsonix: Menüeinträge reaktiv synchronisieren — Toggle UND ggf. geänderte/neue/entfernte/
	// umgeschaltete Einträge müssen sofort sichtbar werden, nicht erst bei erneuter
	// Modul-Installation.
	MenuItem::sync();

	LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($module_id) . "&lang=" . urlencode(LANGUAGE_ID));
}

$aTabs = [
	["DIV" => "edit1", "TAB" => Loc::getMessage("ITSONIX_LINKHUB_OPT_TAB"), "TITLE" => Loc::getMessage("ITSONIX_LINKHUB_OPT_TAB")],
];
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$tabControl->Begin();
require __DIR__ . '/ui/options_view.php';
