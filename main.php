<<<<<<< HEAD
<?php

namespace WPSD\site_health;

defined('ABSPATH') || die;

require __DIR__.'/class-consts.php';

add_filter('site_health_navigation_tabs', __NAMESPACE__.'\add_site_health_tab');

function add_site_health_tab($tabs){

	$tabs[Consts::THIS_PLUGIN_SLUG] = __('WP Bloat', 'wpsd-site-health');

	return $tabs;

}

add_action('site_health_tab_content', __NAMESPACE__.'\the_site_health_tab', 10, 1);

function the_site_health_tab($tab){

	if($tab !== Consts::THIS_PLUGIN_SLUG){

		return false;

	}

	$GLOBALS['wpsd_bloat_displayed'] = true;

	require __DIR__.'/display-data.php';

}

add_action('admin_enqueue_scripts', __NAMESPACE__.'\enqueue_site_health_script', PHP_INT_MAX);

function enqueue_site_health_script(){

	$is_development = defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development';

	$style_version = $is_development ? filemtime(__DIR__.'/style.css') : Consts::PLUGIN_VER;

	$script_version = $is_development ? filemtime(__DIR__.'/script.js') : Consts::PLUGIN_VER;

	wp_enqueue_style(Consts::THIS_PLUGIN_SLUG, plugins_url('style.css', __FILE__), [], $style_version);

	wp_enqueue_script(Consts::THIS_PLUGIN_SLUG, plugins_url('script.js', __FILE__), [], $script_version, true);

}

function get_display_texts(){

	return [
		'title' => __('Identify which plugins and themes are loading unnecesary code.', 'wpsd-site-health'),
		'send' => __('Contribute to our research', 'wpsd-site-health'),
		'contribute_data' => __('Contribute this data', 'wpsd-site-health'),
		'unselect' => __('Unselect', 'wpsd-site-health'),
		'why_send' => __('Why send the data?', 'wpsd-site-health'),
		'send_explanation' => __('WordPress is bloated. Across plugins, themes, and core, developers keep adding code without enough consideration for whether it is really necessary or what it costs in performance.', 'wpsd-site-health'),
		'send_reform' => __('By sending your data, you help build the case for reform in how WordPress is developed and encourage developers to learn more efficient ways to build within its ecosystem. We plan to use this data in graphs that track how plugins grow across versions, making the problem visible and measurable.', 'wpsd-site-health'),
		'send_mission' => __('Our mission is to make WordPress the most efficient CMS ecosystem—or, at the very least, give you the tools to identify plugins and themes built with efficiency in mind.', 'wpsd-site-health'),
		'send_contents' => __('The submission includes plugin and theme names, slugs and versions, PHP file counts, OPcache sizes, load durations, CSS and JavaScript counts, and this page’s TTFB. Sending is optional and requires your agreement to the privacy policy.', 'wpsd-site-health'),
		'send_visualization' => __('Visualizations of the collected data are currently under construction and will be available for everyone to browse for free.', 'wpsd-site-health'),
		'close' => __('Close', 'wpsd-site-health'),
		'consent' => __('I agree with', 'wpsd-site-health'),
		'privacy' => __('privacy policy', 'wpsd-site-health'),
		'privacy_title' => __('Privacy policy', 'wpsd-site-health'),
		'privacy_data' => __('Only technical performance data is included: plugin, theme and core names, slugs and versions, file counts, OPcache sizes, load times, resource counts, locale and TTFB. The submission does not record IP addresses, website URLs, names, email addresses or personal identifiers.', 'wpsd-site-health'),
		'privacy_purpose' => __('Sharing is optional. The data is intended for performance research and graphs comparing versions, to help improve efficiency across the WordPress ecosystem.', 'wpsd-site-health'),
		'privacy_local' => __('Your WordPress login is used locally to authorize the request. A local, account-specific flag prevents repeat submissions for one hour; it is separate from the performance data.', 'wpsd-site-health'),
		'privacy_gdpr' => __('Under the GDPR, genuinely anonymous data that cannot identify a person falls outside personal data rules. Please do not submit if custom plugin or theme names contain personal information.', 'wpsd-site-health'),
		'sending' => __('Sending…', 'wpsd-site-health'),
		'sent' => __('Data sent successfully.', 'wpsd-site-health'),
		'send_error' => __('Unable to send data. Please try again.', 'wpsd-site-health'),
		'send_http_error' => __('Unable to send data (HTTP %s). Please try again later.', 'wpsd-site-health'),
		'send_rate_limit' => __('Too many requests (HTTP 429). Wait at least one minute before trying again.', 'wpsd-site-health'),
		'ttfb' => __('Current page TTFB:', 'wpsd-site-health'),
		'comparison' => __('File counts and OPcache sizes show plugins_loaded → shutdown for plugins and core, and after_setup_theme → shutdown for themes.', 'wpsd-site-health'),
		'name' => __('Name', 'wpsd-site-health'),
		'file_count' => __('File Count', 'wpsd-site-health'),
		'opcache_size' => __('OPcache Size', 'wpsd-site-health'),
		'duration' => __('Load Duration', 'wpsd-site-health'),
		'css_js' => __('CSS/JS', 'wpsd-site-health'),
		'plugins' => __('Plugins', 'wpsd-site-health'),
		'themes' => __('Themes', 'wpsd-site-health'),
		'wp-core' => __('WordPress Core', 'wpsd-site-health'),
		'breakdown' => __('PHP files', 'wpsd-site-health'),
		'plugins_loaded' => __('plugins_loaded', 'wpsd-site-health'),
		'after_setup_theme' => __('after_setup_theme', 'wpsd-site-health'),
		'shutdown' => __('Additional files loaded by shutdown', 'wpsd-site-health'),
		'more' => __('Show remaining files', 'wpsd-site-health'),
		'top' => __('Back to top', 'wpsd-site-health'),
		'resources' => __('CSS/JS Bloat', 'wpsd-site-health'),
		'resources_description' => __('Enqueued resources on this page:', 'wpsd-site-health'),
		'no_resources' => __('All good here, no bloat.', 'wpsd-site-health'),
		'scripts' => __('JS', 'wpsd-site-health'),
		'styles' => __('CSS', 'wpsd-site-health'),
		'na' => __('N/A', 'wpsd-site-health'),
		'ms' => __('ms', 'wpsd-site-health'),
		'seconds' => __('s', 'wpsd-site-health'),
		'sort' => __('Sort by this column', 'wpsd-site-health'),
		'warning' => __('OPcache data is unavailable or restricted. Check your PHP OPcache settings.', 'wpsd-site-health'),
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

require_once __DIR__.'/class-folder.php';

add_action('plugins_loaded', __NAMESPACE__.'\collect_plugins_loaded_data', PHP_INT_MAX);

function collect_plugins_loaded_data(){

	$GLOBALS['wpsd_bloat_plugins_loaded'] = collect_bloat_data();

}

add_action('after_setup_theme', __NAMESPACE__.'\collect_theme_loaded_data', PHP_INT_MAX);

function collect_theme_loaded_data(){

	$data = collect_bloat_data();

	$GLOBALS['wpsd_bloat_plugins_loaded']['groups'][Consts::WP_THEMES] = $data['groups'][Consts::WP_THEMES];

}

function collect_bloat_data(){

	$folder = new Folder();

	return [
		'groups' => $folder->get_files(),
		'opcache_available' => function_exists('opcache_get_status') && !ini_get('opcache.restrict_api') && (bool)opcache_get_status(false),
	];

}

function print_json_data($id, $data){

	$id = esc_attr($id);

	$json = wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

	echo <<<HTML
	<script id="{$id}" type="application/json">{$json}</script>
	HTML;

}

add_action('admin_print_footer_scripts', __NAMESPACE__.'\print_plugins_loaded_data', PHP_INT_MAX);

function print_plugins_loaded_data(){

	if(empty($GLOBALS['wpsd_bloat_displayed'])){

		return false;

	}

	print_json_data('wpsd-plugins-loaded-data', $GLOBALS['wpsd_bloat_plugins_loaded'] ?? []);

	print_json_data('wpsd-bloat-config', [
		'texts' => get_display_texts(),
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('wpsd_send_bloat_data'),
		'limits' => [
			'plugins' => [Consts::EVAL_PLUGIN_MAX_COUNT, Consts::EVAL_PLUGIN_MAX_OPCACHE, Consts::EVAL_PLUGIN_MAX_TIME],
			'themes' => [Consts::EVAL_THEME_MAX_COUNT, Consts::EVAL_THEME_MAX_OPCACHE, Consts::EVAL_THEME_MAX_TIME],
		],
	]);

}

add_action('shutdown', __NAMESPACE__.'\collect_shutdown_data', PHP_INT_MAX);

function collect_shutdown_data(){

	if(empty($GLOBALS['wpsd_bloat_displayed'])){

		return false;

	}

	$data = collect_bloat_data();

	$data['resources'] = collect_enqueued_resources();

	$data['ttfb'] = round(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)), 3);

	if(defined('WP_START_TIMESTAMP')){

		$data['ttfb'] = round(microtime(true) - WP_START_TIMESTAMP, 3);

	}

	print_json_data('wpsd-shutdown-data', $data);

}

function collect_enqueued_resources(){

	global $wp_scripts, $wp_styles;

	$groups = [];

	foreach(['scripts' => $wp_scripts, 'styles' => $wp_styles] as $type => $registry){

		foreach($registry->queue ?? [] as $handle){

			$src = $registry->registered[$handle]->src ?? '';

			if(!$src){

				continue;

			}

			$group = get_resource_group($src);

			if(!$group){

				continue;

			}

			$groups[$group][$type][] = esc_url_raw($src);

			$size = get_resource_filesize($src);

			$groups[$group][$type.'_sizes'][] = $size;

		}

	}

	return $groups;

}

function get_resource_filesize($src){

	$path = rawurldecode(wp_parse_url($src, PHP_URL_PATH) ?: '');

	$content_path = wp_parse_url(content_url(), PHP_URL_PATH);

	if(!$content_path || !str_starts_with($path, $content_path.'/')){

		return false;

	}

	$file = realpath(WP_CONTENT_DIR.substr($path, strlen($content_path)));

	$root = realpath(WP_CONTENT_DIR);

	if(!$file || !$root || !str_starts_with($file, $root.DIRECTORY_SEPARATOR) || !is_file($file) || !is_readable($file)){

		return false;

	}

	return filesize($file);

}

function get_resource_group($src){

	if(str_contains($src, '/'.Consts::get_self_dir_slug().'/')){

		return false;

	}

	foreach(['/wp-content/plugins/', '/wp-content/themes/'] as $root){

		if(!str_contains($src, $root)){

			continue;

		}

		$parts = explode($root, $src, 2);

		$slug = explode('/', $parts[1])[0];

		return ucwords(str_replace('-', ' ', $slug));

	}

	return false;

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

=======
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

>>>>>>> origin/main
