<?php

namespace WPSD\site_health;

$t=(object)[
	'file_count'	=> __('File Count', 'wpsd-site-health'),
	'opcache_size'	=> __('OPcache Size', 'wpsd-site-health'),
	'duration'		=> __('Duration', 'wpsd-site-health'),
	'css'			=> file_get_contents( __DIR__.'/style.css' ),
];

echo <<<HTML
<table id="wpsdsh-table">
	<thead>
		<tr>
			<th></th>
			<th data-sort="file_count">{$t->file_count}<span class="sort-indicator"></span></th>
			<th data-sort="opcache_size">{$t->opcache_size}<span class="sort-indicator"></span></th>
			<th data-sort="duration">{$t->duration}<span class="sort-indicator"></span></th>
		</tr>
	</thead>
	<tbody id="tbody-plugins"></tbody>
	<tbody id="tbody-themes"></tbody>
	<tbody id="tbody-core"></tbody>
</table>

<style>{$t->css}</style>

HTML;
?>