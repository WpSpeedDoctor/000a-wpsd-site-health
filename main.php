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
	
	$performance_stats= <<<HTML
	<div id="wpsd-ttfb-wrap">
		{$t->ttfb}<span class="wpsd-ttfb-value" id="wpsd-ttfb-value">–</span>
	</div>

	HTML;

	echo <<<HTML
	<div class="health-check-body health-check-status-tab">
		<p>{$t->php_bloat_title}</p>
		{$performance_stats}
		{$content}
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



function get_texts(){

	return (object)[
		'php_bloat_title'		=> __('Identify what plugins and themes are making your site slow', 'wpsd-site-health'),
		'ttfb'					=> __('Current page TTFB: ', 'wpsd-site-health'),
		'resources_bloat_title'	=> __('List of enqueued resources that should not be present on this page:', 'wpsd-site-health'),
		'no_resources'			=> __('All good here, no bloat.', 'wpsd-site-health'),
	];
}


purge_opcache();

function purge_opcache(){

	if(!function_exists('opcache_reset')){

		return false;

	}

	$status = opcache_get_status(false);

	if(empty($status) || empty($status['opcache_enabled'])){

		return false;

	}

	opcache_reset();

	return true;

}


add_action('admin_enqueue_scripts', __NAMESPACE__ . '\pass_data_to_js', PHP_INT_MAX);

function pass_data_to_js(){

	$consts = (new \ReflectionClass( __NAMESPACE__ . '\Consts'))->getConstants();

	foreach(array_keys($consts) as $key){
		//remove EVAL constants
		if( $key[0] !== 'E' && $key[0] !== 'T' ) continue;

		unset($consts[$key]);
	}

	require __DIR__.'/class-folder.php';

	$folder = new Folder();

	$display_data = [

		'consts' => $consts,
		'data' => $folder->get_files(),
		'texts' => [
			'core'		=> __('WordPress Core', 'wpsd-site-health'),
			'plugins'	=> __('Plugins', 'wpsd-site-health'),
			'themes'	=> __('Themes', 'wpsd-site-health')
			]
	];
	
	wp_register_script(Consts::THIS_PLUGIN_SLUG, plugins_url('script.js', __FILE__), [], false, true);

	wp_enqueue_script(Consts::THIS_PLUGIN_SLUG);

	wp_localize_script(Consts::THIS_PLUGIN_SLUG, Consts::THIS_PLUGIN_JS_SLUG, $display_data);
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

