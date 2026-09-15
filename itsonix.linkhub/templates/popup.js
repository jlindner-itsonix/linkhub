document.addEventListener('DOMContentLoaded', function () {
	// itsonix: einziger Trigger-Punkt ist dieser eigene Button, als Geschwister-Element direkt
	// NACH .menu-items-header__logo (dem "Bitrix24"-Schriftzug) eingefuegt - nicht mehr der ganze
	// Schriftzug-Link selbst (der navigiert wieder normal zu $siteUrl, kein preventDefault mehr
	// darauf). Davor sitzt bereits der Sidebar-Ein-/Ausklapp-Button
	// (.menu-items-header__menu-swticher), daher rechts danach statt davor platziert.
	var logo = document.querySelector('.menu-items-header__logo');
	if (!logo) return;

	var switcherBtn = document.createElement('a');
	switcherBtn.href = '#';
	switcherBtn.className = 'itsonix-app-switcher-btn';
	switcherBtn.title = 'Apps wechseln';
	switcherBtn.innerHTML = `__SWITCHER_ICON__`;
	logo.insertAdjacentElement('afterend', switcherBtn);

	var menu = document.createElement('div');
	menu.id = 'itsonix-app-switcher-menu';
	menu.innerHTML = `__TILES__`;
	document.body.appendChild(menu);

	function closeMenu() {
		menu.style.display = 'none';
	}

	function toggleMenu() {
		if (menu.style.display === 'flex') {
			closeMenu();
			return;
		}
		var rect = switcherBtn.getBoundingClientRect();
		menu.style.top = (rect.bottom + 6) + 'px';
		menu.style.left = rect.left + 'px';
		menu.style.display = 'flex';
	}

	switcherBtn.addEventListener('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		toggleMenu();
	});

	document.addEventListener('click', function (e) {
		if (menu.style.display === 'flex' && !menu.contains(e.target) && e.target !== switcherBtn && !switcherBtn.contains(e.target)) {
			closeMenu();
		}
	});
});
