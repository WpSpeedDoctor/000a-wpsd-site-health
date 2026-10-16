(function(){
	'use strict';

	function read_data(id){
		const element = document.getElementById(id);

		return element ? JSON.parse(element.textContent) : null;
	}

	function create_element(tag, text, class_name){
		const element = document.createElement(tag);

		if(text !== undefined) element.textContent = text;
		if(class_name) element.className = class_name;

		return element;
	}

	function format_value(value, key, texts){
		if(value === null || value === undefined || value === false) return texts.na;
		if(key === '2') return String(value);
		if(key === '10') return value + ' ' + texts.ms;
		if(value >= 1048576) return (value / 1048576).toFixed(1) + ' MB';
		if(value >= 1024) return (value / 1024).toFixed(1) + ' KB';

		return value + ' B';
	}

	function metric_cell(before, after, key, limit, texts){
		const value = key === '2' || key === '1' ? format_value(before, key, texts) + ' → ' + format_value(after, key, texts) : format_value(after, key, texts);

		const cell = create_element('td', value);

		if(limit !== undefined && typeof after === 'number'){
			const failed = after > limit;
			cell.append(' ', create_element('span', failed ? '✗' : '✓', 'eval-icon ' + (failed ? 'eval-fail' : 'eval-pass')));
		}

		return cell;
	}

	function file_list(files, texts){
		const container = create_element('div');
		const sorted_files = files.slice().sort((a, b) => {
			const a_folder = a.slice(0, a.lastIndexOf('/'));
			const b_folder = b.slice(0, b.lastIndexOf('/'));

			return a_folder.localeCompare(b_folder) || a.localeCompare(b);
		});
		const list = create_element('ul');
		let remaining_list;

		sorted_files.forEach((file, index) => {
			if(index === 10){
				const details = create_element('details');
				details.append(create_element('summary', texts.more + ' (' + (sorted_files.length - 10) + ')'));
				remaining_list = create_element('ul');
				details.append(remaining_list);
				container.append(details);
			}

			const item = create_element('li');
			item.append(create_element('code', file, 'wpsd-no-bg'));
			(index < 10 ? list : remaining_list).append(item);
		});
		container.prepend(list);

		return container;
	}

	function top_link(texts){
		const link = create_element('a', texts.top, 'wpsd-back-to-top');
		link.href = '#wpsd-bloat-top';

		return link;
	}

	function render_table(initial, final, config, container){
		const texts = config.texts;
		const table = create_element('table');
		table.id = 'wpsdsh-table';
		const head = create_element('thead');
		const header = create_element('tr');
		const columns = [['name', texts.name], ['2', texts.file_count], ['1', texts.opcache_size], ['10', texts.duration], ['resources', texts.css_js]];
		const bodies = [];
		const heading_cells = [];
		let sort_key = '10';
		let descending = true;

		function sort_rows(){
			bodies.forEach(body => {
				const rows = Array.from(body.querySelectorAll('tr[data-name]'));
				rows.sort((a, b) => {
					const order = sort_key === 'name' ? a.dataset.name.localeCompare(b.dataset.name) : Number(a.dataset['value_' + sort_key]) - Number(b.dataset['value_' + sort_key]);

					return (descending ? -order : order) || a.dataset.name.localeCompare(b.dataset.name);
				});
				rows.forEach(row => body.append(row));
			});
			heading_cells.forEach(([cell, key, indicator]) => {
				const active = key === sort_key;
				cell.setAttribute('aria-sort', active ? (descending ? 'descending' : 'ascending') : 'none');
				indicator.textContent = active ? (descending ? '↓' : '↑') : '↕';
			});
		}

		columns.forEach(([key, label]) => {
			const cell = create_element('th');
			cell.scope = 'col';
			const button = create_element('button', label);
			button.type = 'button';
			button.title = texts.sort;
			const indicator = create_element('span', '↕', 'sort-indicator');
			indicator.setAttribute('aria-hidden', 'true');
			button.append(indicator);
			button.addEventListener('click', () => {
				descending = sort_key === key ? !descending : key !== 'name';
				sort_key = key;
				sort_rows();
			});
			cell.append(button);
			heading_cells.push([cell, key, indicator]);
			header.append(cell);
		});
		head.append(header);
		table.append(head);

		const breakdown = create_element('section', undefined, 'wpsd-opcache-breakdown');
		breakdown.append(create_element('h3', texts.breakdown));

		['plugins', 'themes', 'wp-core'].forEach(type => {
			const before_items = initial.groups?.[type] || {};
			const after_items = final.groups?.[type] || {};
			const slugs = Array.from(new Set([...Object.keys(before_items), ...Object.keys(after_items)]));
			if(!slugs.length) return;
			const body = create_element('tbody');
			const category_row = create_element('tr');
			const category_cell = create_element('td', texts[type], 'category-header');
			category_cell.colSpan = columns.length;
			category_row.append(category_cell);
			body.append(category_row);
			bodies.push(body);
			table.append(body);
			const group = create_element('section');
			group.append(create_element('h4', texts[type]));

			slugs.forEach((slug, index) => {
				const before = before_items[slug] || {};
				const after = after_items[slug] || {};
				const name = after['5'] || before['5'] || slug;
				const version = after['3'] || before['3'];
				const anchor = 'wpsd-files-' + type + '-' + index;
				const row = create_element('tr');
				row.dataset.name = name;
				const name_cell = create_element('td');
				const link = create_element('a', name);
				link.href = '#' + anchor;
				name_cell.append(link);
				if(version) name_cell.append(' ', create_element('small', version));
				row.append(name_cell);

				['2', '1', '10'].forEach((key, metric_index) => {
					let before_value = before[key];
					let after_value = after[key];
					if(key !== '10'){
						before_value = key === '1' && !initial.opcache_available ? null : (before_value ?? 0);
						after_value = key === '1' && !final.opcache_available ? null : (after_value ?? 0);
					}
					row.dataset['value_' + key] = typeof after_value === 'number' ? after_value : -1;
					row.append(metric_cell(before_value, after_value, key, config.limits[type]?.[metric_index], texts));
				});
				const resource_name = slug.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
				const resources = type === 'wp-core' ? {} : final.resources?.[resource_name] || {};
				const resource_cell = create_element('td');
				let resource_size = 0;
				['styles', 'scripts'].forEach(resource_type => {
					const count = resources[resource_type]?.length || 0;
					if(!count) return;
					const sizes = resources[resource_type + '_sizes'] || [];
					const size = sizes.length === count && sizes.every(value => typeof value === 'number') ? sizes.reduce((total, value) => total + value, 0) : null;
					resource_size += size || 0;
					resource_cell.append(create_element('div', texts[resource_type] + ': ' + count + ' (' + format_value(size, 'size', texts) + ')'));
				});
				row.dataset.value_resources = resource_size;
				row.append(resource_cell);
				body.append(row);

				const section = create_element('section');
				section.id = anchor;
				section.append(create_element('h5', name));
				const initial_files = before.files || [];
				const initial_set = new Set(initial_files);
				const additional_files = (after.files || []).filter(file => !initial_set.has(file));
				const initial_hook = type === 'themes' ? texts.after_setup_theme : texts.plugins_loaded;

				section.append(create_element('h6', initial_hook), file_list(initial_files, texts));
				if(additional_files.length){
					const shutdown_field = create_element('div', undefined, 'wpsd-shutdown-files');
					shutdown_field.append(create_element('h6', texts.shutdown), file_list(additional_files, texts));
					section.append(shutdown_field);
				}
				section.append(top_link(texts));
				group.append(section);
			});
			breakdown.append(group);
		});
		sort_rows();
		container.append(table, breakdown);
	}

	function render_resources(resources, texts){
		const container = document.getElementById('wpsd-enqueued-resources');
		const groups = Object.entries(resources || {});
		if(!groups.length) container.append(create_element('p', texts.no_resources));

		groups.forEach(([name, data]) => {
			const section = create_element('section', undefined, 'wpsd-resource-group');
			section.append(create_element('h4', name));
			['scripts', 'styles'].forEach(type => {
				if(!data[type]?.length) return;
				section.append(create_element('h5', texts[type]), file_list(data[type], texts));
			});
			container.append(section);
		});
		container.append(top_link(texts));
	}

	function submission_metrics(item, opcache_available, include_duration = true){
		const metrics = {
			file_count: item['2'] ?? 0,
			opcache_size: opcache_available ? (item['1'] ?? 0) : null
		};
		if(include_duration) metrics.load_duration = typeof item['10'] === 'number' ? item['10'] : null;

		return metrics;
	}

	function submission_data(initial, final){
		const data = {ttfb: final.ttfb, groups: {}};

		['plugins', 'themes', 'wp-core'].forEach(type => {
			const before_items = initial.groups?.[type] || {};
			const after_items = final.groups?.[type] || {};
			const slugs = new Set([...Object.keys(before_items), ...Object.keys(after_items)]);
			data.groups[type] = [];

			slugs.forEach(slug => {
				const before = before_items[slug] || {};
				const after = after_items[slug] || {};
				const name = after['5'] || before['5'] || slug;
				const resource_name = slug.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
				const resources = final.resources?.[resource_name] || {};

				data.groups[type].push({
					slug: type === 'plugins' ? (after['4'] || before['4'] || slug) : slug,
					name: name,
					version: after['3'] || before['3'] || '',
					initial: submission_metrics(before, initial.opcache_available),
					shutdown: submission_metrics(after, final.opcache_available, false),
					scripts: resources.scripts?.length || 0,
					styles: resources.styles?.length || 0
				});
			});
		});

		return data;
	}

	function setup_modal(button_id, dialog_id){
		const button = document.getElementById(button_id);
		const dialog = document.getElementById(dialog_id);
		if(!button || !dialog) return false;

		button.addEventListener('click', () => dialog.showModal());
		dialog.addEventListener('close', () => button.focus());
	}

	function render_submission_items(data, texts){
		const container = document.getElementById('wpsd-submission-items');
		const selections = {};

		Object.entries(data.groups).forEach(([type, items]) => {
			selections[type] = [];
			if(!items.length) return;
			const group = create_element('fieldset');
			const legend = create_element('legend', texts[type]);
			const unselect = create_element('button', texts.unselect, 'button-link');
			unselect.type = 'button';
			unselect.addEventListener('click', () => {
				selections[type].forEach(checkbox => { checkbox.checked = false; });
				group.dispatchEvent(new Event('change', {bubbles: true}));
			});
			legend.append(' ', unselect);
			group.append(legend);
			items.forEach(item => {
				const label = create_element('label');
				const checkbox = create_element('input');
				checkbox.type = 'checkbox';
				checkbox.checked = true;
				label.append(checkbox, ' ', item.name);
				if(item.version) label.append(' ', create_element('small', item.version));
				group.append(label);
				selections[type].push(checkbox);
			});
			container.append(group);
		});

		return selections;
	}

	function selected_submission_data(data, selections){
		const groups = {};
		Object.entries(data.groups).forEach(([type, items]) => {
			groups[type] = items.filter((item, index) => selections[type][index].checked);
		});

		return {ttfb: data.ttfb, groups: groups};
	}

	function setup_submission(initial, final, config){
		const form = document.getElementById('wpsd-send-data');
		if(!form) return;
		const consent = document.getElementById('wpsd-data-consent');
		const button = form.querySelector('button[type="submit"]');
		const status = document.getElementById('wpsd-send-status');
		const data = submission_data(initial, final);
		const selections = render_submission_items(data, config.texts);
		const checkboxes = Object.values(selections).flat();
		let sending = false;
		let sent = false;

		function update_button(){
			button.disabled = sending || sent || !consent.checked || !checkboxes.some(checkbox => checkbox.checked);
		}

		form.addEventListener('change', update_button);
		update_button();
		form.addEventListener('submit', async event => {
			event.preventDefault();
			if(sending || sent || !consent.checked || !checkboxes.some(checkbox => checkbox.checked)) return;
			sending = true;
			button.disabled = true;
			status.textContent = config.texts.sending;

			try{
				const body = new URLSearchParams({
					action: 'wpsd_send_bloat_data',
					nonce: config.nonce,
					consent: '1',
					data: JSON.stringify(selected_submission_data(data, selections))
				});
				const response = await fetch(config.ajax_url, {method: 'POST', credentials: 'same-origin', body: body});
				const result = await response.json().catch(() => null);
				if(!response.ok || !result?.success){
					const message = response.status === 429 ? config.texts.send_rate_limit : config.texts.send_http_error.replace('%s', response.status);
					throw new Error(result?.data?.message || (response.ok ? config.texts.send_error : message));
				}
				sent = true;
				status.textContent = config.texts.sent;
				const message = create_element('p');
				message.append(status);
				form.replaceChildren(message);
			}catch(error){
				status.textContent = error instanceof TypeError ? config.texts.send_error : error.message || config.texts.send_error;
			}finally{
				sending = false;
				update_button();
			}
		});
	}

	function render_data(){
		const container = document.getElementById('wpsd-bloat-data');
		if(!container) return;
		setup_modal('wpsd-send-main', 'wpsd-contribute-modal');
		setup_modal('wpsd-why-send', 'wpsd-send-explanation');
		setup_modal('wpsd-privacy-policy', 'wpsd-privacy-modal');
		const initial = read_data('wpsd-plugins-loaded-data');
		const final = read_data('wpsd-shutdown-data');
		const config = read_data('wpsd-bloat-config');
		if(!initial || !final || !config) return;
		if(!final.opcache_available) container.append(create_element('p', config.texts.warning, 'notice notice-warning'));
		setup_submission(initial, final, config);
		render_table(initial, final, config, container);
		render_resources(final.resources, config.texts);
		document.getElementById('wpsd-ttfb-value').textContent = final.ttfb + ' ' + config.texts.seconds;
	}

	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', render_data);
	else render_data();
})();
