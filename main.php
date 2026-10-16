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

