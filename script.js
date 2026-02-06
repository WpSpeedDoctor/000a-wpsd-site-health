(function() {
	const c = WPSDsH.consts;
	const data = WPSDsH.data;
	const texts = WPSDsH.texts;

	let sortState = { column: 'duration', direction: 'desc' };

	function getValue(item, key) {
		return item[key] ?? '';
	}

	function getSortValue(item, sortType) {
		const keyMap = {
			file_count: c.FILE_COUNT,
			opcache_size: c.FILE_OPCACHE_SIZE,
			duration: c.DURATION
		};
		const val = getValue(item, keyMap[sortType]);
		return parseInt(val) || 0;
	}

	function getDisplayValue(item, resultKey, fallbackKey) {
		return getValue(item, resultKey) || getValue(item, fallbackKey) || '';
	}

	function createCategoryHeader(tbody, displayName) {
		const headerRow = document.createElement('tr');
		const headerCell = document.createElement('td');
		headerCell.colSpan = 4;
		headerCell.className = 'category-header';
		headerCell.textContent = displayName;
		headerRow.appendChild(headerCell);
		tbody.appendChild(headerRow);
	}

	function populateStatic(categoryDataKey, tbodyId, useDirAsName, displayName) {
		const tbody = document.getElementById(tbodyId);
		if(!tbody) return;
		tbody.innerHTML = '';
		createCategoryHeader(tbody, displayName);
		const catData = data[categoryDataKey] || {};
		Object.entries(catData).forEach(([key, item]) => {
			const name = useDirAsName ? key : getValue(item, c.NAME);
			const version = getValue(item, c.VERSION);
			const nameVersion = version ? `${name} <small>${version}</small>` : name;
			const fileCountDisplay = getDisplayValue(item, c.RESULT_COUNT, c.FILE_COUNT);
			const opcacheDisplay = getDisplayValue(item, c.RESULT_OPCACHE, c.FILE_OPCACHE_SIZE);
			const durationDisplay = getDisplayValue(item, c.RESULT_TIME, c.DURATION);
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${nameVersion}</td>
				<td>${fileCountDisplay}</td>
				<td>${opcacheDisplay}</td>
				<td>${durationDisplay}</td>
			`;
			tbody.appendChild(row);
		});
	}

	function populatePlugins() {
		const tbody = document.getElementById('tbody-plugins');
		if (!tbody) return;
		tbody.innerHTML = '';
		createCategoryHeader(tbody, texts.plugins);
		const catData = data.plugins || {};
		let items = Object.entries(catData).map(([slug, item]) => ({ item }));
		items.sort((a, b) => {
			const aVal = getSortValue(a.item, sortState.column);
			const bVal = getSortValue(b.item, sortState.column);
			return sortState.direction === 'desc' ? bVal - aVal : aVal - bVal;
		});
		items.forEach(({ item }) => {
			const name = getValue(item, c.NAME);
			const version = getValue(item, c.VERSION);
			const nameVersion = version ? `${name} <small>${version}</small>` : name;
			const fileCountDisplay = getDisplayValue(item, c.RESULT_COUNT, c.FILE_COUNT);
			const opcacheDisplay = getDisplayValue(item, c.RESULT_OPCACHE, c.FILE_OPCACHE_SIZE);
			const durationDisplay = getDisplayValue(item, c.RESULT_TIME, c.DURATION);
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${nameVersion}</td>
				<td>${fileCountDisplay}</td>
				<td>${opcacheDisplay}</td>
				<td>${durationDisplay}</td>
			`;
			tbody.appendChild(row);
		});
	}

	function updateSortIndicators() {
		document.querySelectorAll('#wpsdsh-table th[data-sort]').forEach(th => {
			th.classList.remove('sorted-asc', 'sorted-desc');
		});
		const activeTh = document.querySelector(`#wpsdsh-table th[data-sort="${sortState.column}"]`);
		if (activeTh) {
			activeTh.classList.add(`sorted-${sortState.direction}`);
		}
	}

	function handleSort(column) {
		if (sortState.column === column) {
			sortState.direction = sortState.direction === 'desc' ? 'asc' : 'desc';
		} else {
			sortState.column = column;
			sortState.direction = 'desc';
		}
		updateSortIndicators();
		populatePlugins();
	}

	function init() {
		populatePlugins();
		populateStatic('themes', 'tbody-themes', false, texts.themes);
		populateStatic('wp-core', 'tbody-core', true, texts.core);
		updateSortIndicators();
		document.querySelectorAll('#wpsdsh-table th[data-sort]').forEach(th => {
			th.addEventListener('click', () => handleSort(th.dataset.sort));
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

document.addEventListener('DOMContentLoaded', () => {

	const dataEl = document.getElementById('wpsd-ttfb-data');

	if(!dataEl) return;

	try {

		const data = JSON.parse(dataEl.textContent);

		const el = document.getElementById('wpsd-ttfb-value');

		if(el && data.ttfb){

			el.textContent = data.ttfb + ' s';

		}

	} catch(e){}

});

(function(){

	window.addEventListener('wpsd_enqueued_ready', function(){

		var container = document.getElementById('wpsd-enqueued-resources');

		if(!container)return;

		container.innerHTML = window.wpsd_enqueued_markup;

	});

})();
