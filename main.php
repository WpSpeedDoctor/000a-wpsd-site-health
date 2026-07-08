<?php

namespace WPSD\site_health;

defined('ABSPATH') || die;

require __DIR__.'/class-consts.php';

add_filter('site_health_navigation_tabs', __NAMESPACE__.'\add_site_health_tab');

function add_site_health_tab($tabs){
	$tabs[Consts::TAB_SLUG_PHP] = __('PHP Bloat', 'wpsd-site-health');
	$tabs[Consts::TAB_SLUG_RESOURCES] = __('CSS/JS Bloat', 'wpsd-site-health');
	return $tabs;
}

add_action('site_health_tab_content', __NAMESPACE__.'\the_site_health_tab', 10, 1);

function the_site_health_tab($tab){
	
	switch($tab){
		case Consts::TAB_SLUG_PHP:
			the_php_tab();
			break;
		case Consts::TAB_SLUG_RESOURCES:
			the_resources_tab();
			break;
	}

}

function the_php_tab(){
	
	$t = get_texts();

	$content = get_required_files_markup();

	$breakdown = get_opcache_breakdown_markup();
	
	$performance_stats= <<<HTML
	<div id="wpsd-ttfb-wrap">
		{$t->ttfb}<span class="wpsd-ttfb-value" id="wpsd-ttfb-value">–</span>
	</div>

	HTML;

	echo <<<HTML
	<div class="health-check-body health-check-status-tab">
		{$t->opcache_warning}
		<p>{$t->php_bloat_title}</p>
		{$performance_stats}
		{$content}
		{$breakdown}
	</div>
	HTML;
}

function the_resources_tab(){
	
	$t = get_texts();

	echo <<<HTML
	<div class="health-check-body health-check-status-tab">
		<p>{$t->resources_bloat_title}</p>
		<div id="wpsd-enqueued-resources"></div>
	</div>
	HTML;
}

add_action( 'plugin_loaded', __NAMESPACE__.'\measure_plugin_time', PHP_INT_MAX );

function measure_plugin_time($plugin_path, $retrieve_data = false ){
	
	static $data;

	if( $retrieve_data ){
		return (array) $data;
	}

	$plugin_slug = basename( dirname( $plugin_path ) );

	if($plugin_slug === '.'){

		$plugin_slug = basename($plugin_path, '.php');

	}

	static $start_time;

	$time_us = $plugin_slug === Consts::get_self_dir_slug() ? false : get_hrtime_ms( $start_time, hrtime(true) ); 

	$data[$plugin_slug] = $time_us;

	$start_time = hrtime(true);
	
}

function get_hrtime_ms( $start_time, $end_time ){
	
	return (int) round( ( $end_time - $start_time ) / 1e6 );
}


/**
 * Measure theme time
 */
add_action('setup_theme', __NAMESPACE__.'\measure_theme_start' );

function measure_theme_start(){

	static $start_time;

	$start_time || $start_time = hrtime(true);

	return $start_time;
}

add_action('after_setup_theme', __NAMESPACE__.'\measure_theme_end' );

function measure_theme_end(){

	static $end_time;

	$end_time || $end_time = hrtime(true);

	return $end_time;
}

/**
 * required files section
 */

function get_required_files_markup(){

	ob_start();

	require __DIR__.'/display-data.php';

	return ob_get_clean();

}

function get_required_files_table_markup($data){

	$texts = [
		'file_count' => __('File Count', 'wpsd-site-health'),
		'opcache_size' => __('OPcache Size', 'wpsd-site-health'),
		'duration' => __('Duration', 'wpsd-site-health'),
		'plugins' => __('Plugins', 'wpsd-site-health'),
		'themes' => __('Themes', 'wpsd-site-health'),
		'core' => __('WordPress Core', 'wpsd-site-health'),
	];

	$anchors = get_opcache_breakdown_anchor_ids($data);

	$plugins_html = get_required_files_table_rows($data[Consts::WP_PLUGINS] ?? [], Consts::WP_PLUGINS, $texts['plugins'], $anchors);

	$themes_html = get_required_files_table_rows($data[Consts::WP_THEMES] ?? [], Consts::WP_THEMES, $texts['themes'], $anchors);

	$core_html = get_required_files_table_rows($data[Consts::WP_CORE] ?? [], Consts::WP_CORE, $texts['core'], $anchors);

	$file_count = $texts['file_count'];

	$opcache_size = $texts['opcache_size'];

	$duration = $texts['duration'];

	return <<<HTML
	<table id="wpsdsh-table">
		<thead>
			<tr>
				<th></th>
				<th>{$file_count}</th>
				<th>{$opcache_size}</th>
				<th>{$duration}</th>
			</tr>
		</thead>
		<tbody>{$plugins_html}</tbody>
		<tbody>{$themes_html}</tbody>
		<tbody>{$core_html}</tbody>
	</table>
	HTML;

}

function get_required_files_table_rows($items, $type, $heading, $anchors){

	if(empty($items)){

		return '';

	}

	if($type === Consts::WP_PLUGINS){

		uasort($items, __NAMESPACE__ . '\sort_items_by_duration');

	}

	$heading = esc_html($heading);

	$rows = <<<HTML
	<tr>
		<td colspan="4" class="category-header">{$heading}</td>
	</tr>
	HTML;

	foreach($items as $slug => $item){

		$rows .= get_required_files_table_row($slug, $item, $type, $anchors);

	}

	return $rows;

}

function get_required_files_table_row($slug, $item, $type, $anchors){

	$name = $type === Consts::WP_CORE ? $slug : ($item[Consts::NAME] ?? $slug);

	$version = $item[Consts::VERSION] ?? '';

	$name = esc_html($name);

	$version = esc_html($version);

	$name_version = $version ? $name . ' <small>' . $version . '</small>' : $name;

	$anchor = $anchors[$type][$slug] ?? '';

	if($anchor){

		$anchor = esc_attr($anchor);

		$name_version = '<a href="#' . $anchor . '">' . $name_version . '</a>';

	}

	$file_count = $item[Consts::RESULT_COUNT] ?? $item[Consts::FILE_COUNT] ?? '';

	$opcache_size = $item[Consts::RESULT_OPCACHE] ?? $item[Consts::FILE_OPCACHE_SIZE] ?? '';

	$duration = $item[Consts::RESULT_TIME] ?? $item[Consts::DURATION] ?? '';

	return <<<HTML
	<tr>
		<td>{$name_version}</td>
		<td>{$file_count}</td>
		<td>{$opcache_size}</td>
		<td>{$duration}</td>
	</tr>
	HTML;

}

function sort_items_by_duration($a, $b){

	$a_duration = (int)($a[Consts::DURATION] ?? 0);

	$b_duration = (int)($b[Consts::DURATION] ?? 0);

	return $b_duration <=> $a_duration;

}



function get_texts(){

	$warning_text =  __('Warning: OPcache is restricted so memory usage is not displayed.remove from php.ini settings `opcache.restrict_api=1` to get full details.', 'wpsd-site-health');

	$warning_markup = <<<HTML
	<div class="notice notice-warning is-dismissible">
		<p>{$warning_text}</p>
	</div>
	HTML;

	$opcache_warning = is_opcache_api_restricted() ? $warning_markup : '';

	return (object)[
		'opcache_warning'		=> $opcache_warning,
		'php_bloat_title'		=> __('Identify what plugins and themes are making your site slow', 'wpsd-site-health'),
		'ttfb'					=> __('Current page TTFB: ', 'wpsd-site-health'),
		'resources_bloat_title'	=> __('List of enqueued resources that should not be present on this page:', 'wpsd-site-health'),
		'no_resources'			=> __('All good here, no bloat.', 'wpsd-site-health'),
		'breakdown'				=> __('Breakdown', 'wpsd-site-health'),
		'core'					=> __('WordPress Core', 'wpsd-site-health'),
		'plugins'				=> __('Plugins', 'wpsd-site-health'),
		'themes'				=> __('Themes', 'wpsd-site-health'),
		'no_opcache_files'		=> __('No OPcache files found.', 'wpsd-site-health'),
	];
}


purge_opcache();

function purge_opcache(){

	if(!function_exists('opcache_reset') || is_opcache_api_restricted()){

		return false;

	}

	$status = opcache_get_status(false);

	if(empty($status) || empty($status['opcache_enabled'])){

		return false;

	}

	opcache_reset();

	return true;

}

function is_opcache_api_restricted(){

	return (bool)ini_get('opcache.restrict_api');
}

function get_opcache_breakdown_markup(){

	$t = get_texts();

	$filepaths = get_required_files();

	if(empty($filepaths)){

		return '';

	}

	$abs_path = wp_normalize_path(ABSPATH);

	$abs_len = strlen($abs_path) - 1;

	$plugin_names = get_plugin_names_by_slug();

	$theme_names = get_theme_names_by_slug();

	$roots = [
		[
			'type' => $t->themes,
			'root' => '/wp-content/themes/',
			'group' => '',
		],
		[
			'type' => $t->plugins,
			'root' => '/wp-content/plugins/',
			'group' => '',
		],
		[
			'type' => $t->core,
			'root' => '/wp-admin/',
			'group' => 'wp-admin',
		],
		[
			'type' => $t->core,
			'root' => '/wp-includes/',
			'group' => 'wp-includes',
		],
	];

	$groups = [];

	foreach($filepaths as $file){

		if(empty($file)){

			continue;

		}

		$path = wp_normalize_path($file);

		if(!str_starts_with($path, $abs_path)){

			continue;

		}

		$relative_filepath = substr($path, $abs_len);

		foreach($roots as $root_data){

			if(!str_starts_with($relative_filepath, $root_data['root'])){

				continue;

			}

			$type = $root_data['type'];

			$group = $root_data['group'];

			if(empty($group)){

				$pieces = explode('/', $relative_filepath);

				$group = $pieces[3] ?? '';

			}

			if(empty($group)){

				continue;

			}

			if($type === $t->plugins){

				if(str_ends_with($group, '.php')){

					$group = basename($group, '.php');

				}

				$group = $plugin_names[$group] ?? $group;

			}

			if($type === $t->themes){

				$group = $theme_names[$group] ?? $group;

			}

			$groups[$type][$group][] = $relative_filepath;

			break;

		}

	}

	$groups_html = '';

	$group_order = [
		$t->themes,
		$t->plugins,
		$t->core,
	];

	foreach($group_order as $type){

		if(empty($groups[$type])){

			continue;

		}

		$items = $groups[$type];

		$items_html = '';

		foreach($items as $group => $files){

			usort($files, __NAMESPACE__ . '\sort_filepaths_by_folder');

			$files_html = '';

			foreach($files as $file){

				$file = esc_html($file);

				$files_html .= '<li><code class="wpsd-no-bg">' . $file . '</code></li>';

			}

			$group_name = esc_html($group);

			$anchor_type = '';

			switch($type){
				case $t->plugins:
					$anchor_type = Consts::WP_PLUGINS;
					break;
				case $t->themes:
					$anchor_type = Consts::WP_THEMES;
					break;
				case $t->core:
					$anchor_type = Consts::WP_CORE;
					break;
			}

			$section_id = $anchor_type ? get_opcache_breakdown_anchor_id($anchor_type, $group) : '';

			$id_attr = $section_id ? ' id="' . esc_attr($section_id) . '"' : '';

			$items_html .= <<<HTML
			<section{$id_attr}>
				<h5>{$group_name}</h5>
				<ul>{$files_html}</ul>
			</section>
			HTML;

		}

		$groups_html .= <<<HTML
		<section>
			<h4>{$type}</h4>
			{$items_html}
		</section>
		HTML;

	}

	if(empty($groups_html)){

		$groups_html = $t->no_opcache_files;

	}

	$heading = $t->breakdown;

	return <<<HTML
	<section class="wpsd-opcache-breakdown">
		<h3>{$heading}</h3>
		{$groups_html}
	</section>
	HTML;

}

function get_opcache_breakdown_anchor_id($type, $name){

	return 'wpsd-opcache-' . $type . '-' . sanitize_title($name);

}

function sort_filepaths_by_folder($a, $b){

	$a_folder = dirname($a);

	$b_folder = dirname($b);

	$folder_sort = strcmp($a_folder, $b_folder);

	if($folder_sort !== 0){

		return $folder_sort;

	}

	return strcmp($a, $b);

}

function get_plugin_names_by_slug(){

	if(!function_exists('get_plugins')){

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

	}

	$plugin_names = [];

	foreach(get_plugins() as $plugin_file => $plugin_data){

		$slug = dirname($plugin_file);

		if($slug === '.'){

			$slug = basename($plugin_file, '.php');

		}

		$plugin_names[$slug] = $plugin_data['Name'] ?? $slug;

	}

	return $plugin_names;

}

function get_theme_names_by_slug(){

	$theme_names = [];

	foreach(wp_get_themes() as $slug => $theme){

		$theme_names[$slug] = $theme->get('Name') ?: $slug;

	}

	return $theme_names;

}


add_action('admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_site_health_script', PHP_INT_MAX);

function enqueue_site_health_script(){

	wp_register_script(Consts::THIS_PLUGIN_SLUG, plugins_url('script.js', __FILE__), [], Consts::PLUGIN_VER, true);

	wp_enqueue_script(Consts::THIS_PLUGIN_SLUG);

}

function get_opcache_breakdown_anchor_ids($data){

	$anchors = [
		Consts::WP_PLUGINS => [],
		Consts::WP_THEMES => [],
		Consts::WP_CORE => [],
	];

	foreach($anchors as $type => $items){

		foreach($data[$type] ?? [] as $slug => $item){

			$name = $item[Consts::NAME] ?? $slug;

			$anchors[$type][$slug] = get_opcache_breakdown_anchor_id($type, $name);

		}

	}

	return $anchors;

}

add_action('shutdown', __NAMESPACE__.'\print_performance_data', PHP_INT_MAX);

function print_performance_data(){


	$ttfb = round((microtime(true) - WP_START_TIMESTAMP), 3);

	$json_ttfb = json_encode(['ttfb' => $ttfb]);

	echo <<<HTML
	<script id="wpsd-ttfb-data" type="application/json">
		{$json_ttfb}
	</script>
	HTML;

}

add_action('shutdown', __NAMESPACE__ . '\collect_enqueued_resources', PHP_INT_MAX);

function collect_enqueued_resources(){

	global $wp_scripts;

	global $wp_styles;

	$texts = get_texts();

	$theme = wp_get_theme();

	$theme_name = $theme->get('Name');

	$groups = [];

	foreach($wp_scripts->queue as $handle){

		$src = $wp_scripts->registered[$handle]->src ?? '';

		if(!$src)continue;

		$group = get_resource_group($src, $theme_name);
		
		if(empty($group)){
			continue;
		}

		$groups[$group]['scripts'][] = esc_url($src);

	}

	foreach($wp_styles->queue as $handle){

		$src = $wp_styles->registered[$handle]->src ?? '';

		if(!$src)continue;

		$group = get_resource_group($src, $theme_name);

		if(empty($group)){
			continue;
		}

		$groups[$group]['styles'][] = esc_url($src);

	}

	$groups_html = '';

	foreach($groups as $group_name => $data){

		$scripts_html = '';

		$styles_html = '';

		foreach($data['scripts'] ?? [] as $src){

			$scripts_html .= '<li>' . $src . '</li>';

		}

		foreach($data['styles'] ?? [] as $src){

			$styles_html .= '<li>' . $src . '</li>';

		}

		$groups_html .= <<<HTML
		<section class="wpsd-resource-group">
			<h4>{$group_name}</h4>
			<ul>{$scripts_html}{$styles_html}</ul>
		</section>
		HTML;

	}

	if(empty($groups_html)){
	
		$groups_html = $texts->no_resources;
	}
		
	$markup = <<<HTML
	<section class="wpsd-enqueued">
		{$groups_html}
	</section>
	HTML;
		
	$json_markup = json_encode($markup);

	echo <<<HTML
	<script>
	window.wpsd_enqueued_markup = $json_markup;
	window.dispatchEvent(new Event("wpsd_enqueued_ready"));
	</script>
	HTML;

}

function get_resource_group($src, $theme_name){

	switch(true) {
		case str_contains($src, 'wpsd-site-health'):
			return '';

		case str_contains($src, '/wp-content/plugins/'):
			$parts = explode('/wp-content/plugins/', $src);
			$plugin = explode('/', $parts[1])[0];
			return ucwords(str_replace('-', ' ', $plugin));

		case str_contains($src, '/wp-content/themes/'):
			return $theme_name;

		default:
			return '';
	};

}

