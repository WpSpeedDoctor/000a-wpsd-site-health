<?php

/*
* Plugin Name: Site Health addon by WP Speed Doctor
* Description: Adds into Site Health menu tabs with bloated PHP plugins and CSS/JS files. 
* Version: 1.3
* Updated: 2025-02-06
* Author: WP Speed Doctor
* Author URI: https://wpspeeddoctor.com/
* Text Domain: wpsd-site-health
* Domain Path: /languages/
* License: GPLv3
* Requires at least: 5.9
* Requires PHP: 7.4.0
*/

add_action('plugins_loaded', __NAMESPACE__.'\load_language_domain');

switch( true ){

	case wp_doing_ajax(): 
	case !is_admin():
		return;

	case ($pagenow??basename($_SERVER['SCRIPT_NAME'])) === 'site-health.php':
		require __DIR__.'/main.php';
		break;
	
}

function load_language_domain(){

	load_plugin_textdomain('wpsd-site-health', false, basename(__DIR__) . '/languages/');
}