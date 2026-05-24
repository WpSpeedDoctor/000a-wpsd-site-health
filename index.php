<?php

namespace WPSD\site_health;

/*
* Plugin Name: Site Health addon by WP Speed Doctor
* Description: Adds into Site Health menu tabs with bloated PHP plugins and CSS/JS files. 
* Version: 1.4
* Updated: 2026-05-24
* Author: WP Speed Doctor
* Author URI: https://wpspeeddoctor.com/
* Text Domain: wpsd-site-health
* Domain Path: /languages/
* License: GPLv3
* Requires at least: 5.9
* Requires PHP: 7.4.0
*/


if( ($pagenow??basename($_SERVER['SCRIPT_NAME']??'')) === 'site-health.php'){
	
	add_action('plugins_loaded', __NAMESPACE__.'\load_language_domain');

	require __DIR__.'/main.php';
	
}

function load_language_domain(){

	load_plugin_textdomain('wpsd-site-health', false, basename(__DIR__) . '/languages/');

}
