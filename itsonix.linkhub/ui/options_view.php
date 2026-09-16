<?php
// itsonix: reines Ansichts-Template — nur Ausgabe, keine Business-Logik. Wird von options.php
// nach dem POST-Handling per require eingebunden; erwartet $module_id, $aTabs, $tabControl als
// vorbereitete Variablen aus dem Aufrufer. Eigene use-Imports noetig — als separate Datei per
// require() eingebunden erbt sie NICHT die use-Statements von options.php (PHP-Imports gelten
// pro Datei, nicht pro Ausfuehrungskontext); ohne das hier: "Class Loc not found".
use Bitrix\Main\Localization\Loc;
use Itsonix\LinkHub\Config;
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
<script><?= file_get_contents(__DIR__ . '/options.js') ?></script>
