<?php

namespace WPSD\site_health;

/*
* Plugin Name: WPSD Site Health
* Description: Adds into Site Health menu tabs with bloated PHP plugins and CSS/JS files. 
* Version: 1.6
* Updated: 2026-10-16
* Author: WP Speed Doctor
* Author URI: https://wpspeeddoctor.com/
* Text Domain: wpsd-site-health
* Domain Path: /languages/
* License: GPLv3
* Requires at least: 5.9
* Requires PHP: 7.4.0
*/

defined('ABSPATH') || die;

switch(true){

	case wp_doing_ajax():
		add_action('wp_ajax_wpsd_send_bloat_data', __NAMESPACE__.'\\ajax_loader');
		break;
		
	case is_admin():
		
		add_action('plugins_loaded', __NAMESPACE__.'\load_language_domain');

		if(($pagenow??'') === 'site-health.php'){
			require __DIR__.'/main.php';
		}
		break;

}

/**
 * Plugin activation
 */
register_activation_hook(__FILE__, __NAMESPACE__.'\activate_plugin');



/**
 * Helper functions
*/
function load_language_domain(){

	load_plugin_textdomain('wpsd-site-health', false, basename(__DIR__) . '/languages/');
}


function ajax_loader(){

	require __DIR__.'/class-consts.php';

	require __DIR__.'/class-ajax-data.php';
	
	Ajax_Data::receive_bloat_data();
}

function activate_plugin(){

	define('WPSD_SITE_HEALTH_FILE', __FILE__);

	require __DIR__.'/plugin-activation.php';	

}