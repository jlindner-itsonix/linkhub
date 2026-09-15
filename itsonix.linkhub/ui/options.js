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
