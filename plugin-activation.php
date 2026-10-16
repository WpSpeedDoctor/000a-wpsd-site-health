<?php

namespace WPSD\site_health;

add_action('activated_plugin', __NAMESPACE__.'\move_plugin_first', PHP_INT_MAX);

function move_plugin_first(){

	$active_plugins = get_option('active_plugins', []);

	if(!is_array($active_plugins)){

		return false;

	}

	$plugin_file = plugin_basename(WPSD_SITE_HEALTH_FILE);

	$plugin_index = array_search($plugin_file, $active_plugins, true);

	if($plugin_index === false || $plugin_index === 0){

		return false;

	}

	unset($active_plugins[$plugin_index]);

	array_unshift($active_plugins, $plugin_file);

	return update_option('active_plugins', array_values($active_plugins));

}