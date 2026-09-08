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
	// Zeilen-ID als Schlüssel (siehe Options-Formular unten) — nicht positionsbasiert, weil
	// ein nicht angehaktes Checkbox-Feld vom Browser gar nicht mitgeschickt wird und eine rein
	// sequenzielle []-Zuordnung sonst die Zeilen verschieben würde. Leere URL-Zeilen verworfen.
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
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
	<?php $tabControl->BeginNextTab(); ?>
	<tr>
		<td width="40%"><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_POPUP_ENABLED") ?>:</td>
		<td width="60%"><input type="checkbox" name="POPUP_ENABLED" value="Y" <?= Config::isPopupEnabled() ? 'checked' : '' ?>></td>
	</tr>
	<tr>
		<td width="40%"><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_MENU_ENABLED") ?>:</td>
		<td width="60%"><input type="checkbox" name="MENU_ENABLED" value="Y" <?= Config::isMenuEnabled() ? 'checked' : '' ?>></td>
	</tr>
	<tr>
		<td colspan="2">
			<?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRIES_TITLE") ?>
			<table id="itsonix-linkhub-entries" style="width:100%;margin-top:6px;table-layout:fixed;">
				<tr>
					<td style="width:22%;padding:0 8px 4px 0;"><b><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRY_LABEL") ?></b></td>
					<td style="width:38%;padding:0 8px 4px;"><b><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRY_URL") ?></b></td>
					<td style="width:90px;padding:0 8px 4px;text-align:center;"><b><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_SHOW_MENU") ?></b></td>
					<td style="width:90px;padding:0 8px 4px;text-align:center;"><b><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_SHOW_POPUP") ?></b></td>
					<td style="width:40px;"></td>
				</tr>
				<?php $i = 0; foreach (Config::getEntries() as $entry): ?>
				<tr class="itsonix-entry-row">
					<td style="width:22%;padding:8px 8px 8px 0;">
						<input type="text" name="LABEL[<?= $i ?>]" placeholder="<?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRY_LABEL") ?>" value="<?= htmlspecialcharsbx($entry['label']) ?>" style="width:100%;box-sizing:border-box;">
					</td>
					<td style="width:38%;padding:8px 8px;">
						<input type="text" name="URL[<?= $i ?>]" placeholder="<?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRY_URL") ?>" value="<?= htmlspecialcharsbx($entry['url']) ?>" style="width:100%;box-sizing:border-box;">
					</td>
					<td style="width:90px;padding:8px;text-align:center;">
						<input type="checkbox" name="SHOW_MENU[<?= $i ?>]" value="Y" <?= $entry['showInMenu'] ? 'checked' : '' ?>>
					</td>
					<td style="width:90px;padding:8px;text-align:center;">
						<input type="checkbox" name="SHOW_POPUP[<?= $i ?>]" value="Y" <?= $entry['showInPopup'] ? 'checked' : '' ?>>
					</td>
					<td style="width:40px;padding:8px 0;">
						<button type="button" class="itsonix-entry-remove" title="<?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRY_REMOVE") ?>">&times;</button>
					</td>
				</tr>
				<?php $i++; endforeach; ?>
			</table>
			<button type="button" id="itsonix-linkhub-entry-add" style="margin-top:6px;">+ <?= Loc::getMessage("ITSONIX_LINKHUB_OPT_ENTRY_ADD") ?></button>
		</td>
	</tr>
	<tr>
		<td width="40%"><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_HEIGHT_MODE") ?>:</td>
		<td width="60%">
			<select name="IFRAME_HEIGHT_MODE">
				<option value="<?= Config::HEIGHT_MODE_VIEWPORT ?>" <?= Config::getIframeHeightMode() === Config::HEIGHT_MODE_VIEWPORT ? 'selected' : '' ?>>
					<?= Loc::getMessage("ITSONIX_LINKHUB_OPT_HEIGHT_MODE_VIEWPORT") ?>
				</option>
				<option value="<?= Config::HEIGHT_MODE_FIXED ?>" <?= Config::getIframeHeightMode() === Config::HEIGHT_MODE_FIXED ? 'selected' : '' ?>>
					<?= Loc::getMessage("ITSONIX_LINKHUB_OPT_HEIGHT_MODE_FIXED") ?>
				</option>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%"><?= Loc::getMessage("ITSONIX_LINKHUB_OPT_HEIGHT_PX") ?>:</td>
		<td width="60%"><input type="number" min="200" step="50" name="IFRAME_HEIGHT_PX" value="<?= (int)Config::getIframeHeightPx() ?>"> px</td>
	</tr>
	<?php
	$tabControl->Buttons();
	?>
	<input type="submit" name="Update" value="<?= Loc::getMessage("MAIN_SAVE") ?>" class="adm-btn-save">
	<?= bitrix_sessid_post(); ?>
	<?php $tabControl->End(); ?>
</form>
<script>
(function () {
	var table = document.getElementById('itsonix-linkhub-entries');
	var addBtn = document.getElementById('itsonix-linkhub-entry-add');
	// itsonix: nächster freier Zeilen-Index für neu hinzugefügte Zeilen — muss über die
	// bestehenden (PHP-gerenderten) Zeilen hinaus weiterzählen, sonst kollidieren Feldnamen.
	var nextIndex = table.querySelectorAll('.itsonix-entry-row').length;

	function bindRemove(row) {
		row.querySelector('.itsonix-entry-remove').addEventListener('click', function () {
			row.remove();
		});
	}

	Array.prototype.forEach.call(table.querySelectorAll('.itsonix-entry-row'), bindRemove);

	addBtn.addEventListener('click', function () {
		var index = nextIndex++;
		var row = document.createElement('tr');
		row.className = 'itsonix-entry-row';

		var tdLabel = document.createElement('td');
		tdLabel.style.cssText = 'width:22%;padding:8px 8px 8px 0;';
		var inputLabel = document.createElement('input');
		inputLabel.type = 'text';
		inputLabel.name = 'LABEL[' + index + ']';
		inputLabel.style.cssText = 'width:100%;box-sizing:border-box;';
		tdLabel.appendChild(inputLabel);

		var tdUrl = document.createElement('td');
		tdUrl.style.cssText = 'width:38%;padding:8px 8px;';
		var inputUrl = document.createElement('input');
		inputUrl.type = 'text';
		inputUrl.name = 'URL[' + index + ']';
		inputUrl.style.cssText = 'width:100%;box-sizing:border-box;';
		tdUrl.appendChild(inputUrl);

		var tdShowMenu = document.createElement('td');
		tdShowMenu.style.cssText = 'width:90px;padding:8px;text-align:center;';
		var cbMenu = document.createElement('input');
		cbMenu.type = 'checkbox';
		cbMenu.name = 'SHOW_MENU[' + index + ']';
		cbMenu.value = 'Y';
		tdShowMenu.appendChild(cbMenu);

		var tdShowPopup = document.createElement('td');
		tdShowPopup.style.cssText = 'width:90px;padding:8px;text-align:center;';
		var cbPopup = document.createElement('input');
		cbPopup.type = 'checkbox';
		cbPopup.name = 'SHOW_POPUP[' + index + ']';
		cbPopup.value = 'Y';
		tdShowPopup.appendChild(cbPopup);

		var tdRemove = document.createElement('td');
		tdRemove.style.cssText = 'width:40px;padding:8px 0;';
		var removeBtn = document.createElement('button');
		removeBtn.type = 'button';
		removeBtn.className = 'itsonix-entry-remove';
		removeBtn.innerHTML = '&times;';
		tdRemove.appendChild(removeBtn);

		row.appendChild(tdLabel);
		row.appendChild(tdUrl);
		row.appendChild(tdShowMenu);
		row.appendChild(tdShowPopup);
		row.appendChild(tdRemove);
		table.appendChild(row);
		bindRemove(row);
	});
})();
</script>
