<?php

namespace WPSD\site_health;

class Folder{
	
	const FAIL = '<span class="eval-icon eval-fail">✗</span>';

	const PASS = '<span class="eval-icon eval-pass">✓</span>';

	private object $root;

	private string $abs_path;

	private int $abs_len;

	private array $data = [
		Consts::WP_CORE => [],
		Consts::WP_PLUGINS => [],
		Consts::WP_THEMES => [],
	];

	private array $opcache_scripts = [];

	public function __construct(){

		$this->abs_path = wp_normalize_path(ABSPATH);

		$this->abs_len = strlen(ABSPATH)-1;

		$this->root = (object)[
			Consts::WP_ADMIN => '/wp-admin/',
			Consts::WP_INCLUDES => '/wp-includes/',
			Consts::WP_PLUGINS => '/wp-content/plugins/',
			Consts::WP_THEMES => '/wp-content/themes/'
		];

		$this->set_opcache_data();

	}

	private function set_opcache_data(){
	
		if(!function_exists('opcache_get_status') || ini_get('opcache.restrict_api')){

			return false;

		}

		$status = opcache_get_status();

		$opcache_raw = $status['scripts'] ?? [];

		$this->opcache_scripts = [];

		foreach($opcache_raw as $data){

			$path = wp_normalize_path($data['full_path']);
			
			if(!str_starts_with($path, $this->abs_path)) continue;
			
			$relative_filepath = substr($path, $this->abs_len);
			
			$this->opcache_scripts[$relative_filepath] = $data['memory_consumption'];
		}
		
	}
	public function get_files(){
	
		foreach(get_included_files() as $file){

			$path = wp_normalize_path($file);

			if( !str_starts_with($path,$this->abs_path) ) continue;

			$this->set_path_data($file,$path);

		}

		$this->move_core_into_cat();

		$this->add_versions();

		$this->add_durations();

		return $this->data;	
	}

	private function add_durations(){
	
		$plugins_time = measure_plugin_time('',true);

		foreach( $plugins_time as $plugin_slug => $duration ){

			$this->data[Consts::WP_PLUGINS][$plugin_slug][Consts::DURATION] = $duration;
		}
		
		foreach( $this->data[Consts::WP_THEMES] as $theme_slug => $theme_data ){
			
			$this->data[Consts::WP_THEMES][$theme_slug][Consts::DURATION] = did_action('after_setup_theme') ? get_hrtime_ms(measure_theme_start(), measure_theme_end()) : null;
			break;
		}
	}

	private function move_core_into_cat(){
		
		$this->data[Consts::WP_CORE] = [

			Consts::WP_INCLUDES => $this->data[Consts::WP_INCLUDES][Consts::WP_INCLUDES] ?? [Consts::KEY_FILE_COUNT => 0, Consts::KEY_OPCACHE_SIZE => 0, 'files' => []],
			Consts::WP_ADMIN => $this->data[Consts::WP_ADMIN][Consts::WP_ADMIN] ?? [Consts::KEY_FILE_COUNT => 0, Consts::KEY_OPCACHE_SIZE => 0, 'files' => []]
		];
		
		unset($this->data[Consts::WP_INCLUDES], $this->data[Consts::WP_ADMIN]);
	}

	private function add_versions(){

		if(!function_exists('get_plugin_data')){

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		// Single site OR site-specific plugins
		$active_plugins = get_option('active_plugins', []);
		
		// Multisite network-activated plugins
		if(is_multisite()){
			$network_plugins = get_site_option('active_sitewide_plugins', []);
			$active_plugins = array_merge($active_plugins, array_keys($network_plugins));
		}
		
		foreach($active_plugins as $plugin_file){
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false);
			$plugin_dir = dirname($plugin_file);

			if($plugin_dir === '.'){

				$plugin_dir = basename($plugin_file, '.php');

			}

			$version = $plugin_data['Version'] ?? 'unknown';
			$name = $plugin_data['Name'] ?? 'unknown';
			
			foreach($this->data[Consts::WP_PLUGINS] as $dir => &$plugin_stats){
				if($dir === $plugin_dir){
					$plugin_stats[Consts::KEY_VERSION] = $version;
					$plugin_stats[Consts::KEY_SLUG] = $plugin_file;
					$plugin_stats[Consts::KEY_NAME] = $name;
					break;
				}
			}
		}
		
		// Rest of theme/core code stays the same...
		$theme = wp_get_theme();
		$current_theme = $theme->get_stylesheet();
		$version = $theme->get('Version') ?? 'unknown';
		$name = $theme->get('Name') ?? 'unknown';
		
		//When the theme is disabled, like when using Oxygen builder
		if(empty($this->data[Consts::WP_THEMES])){
			$this->data[Consts::WP_THEMES] = [
				$current_theme => [
					Consts::KEY_VERSION => $version,
					Consts::KEY_SLUG => $current_theme,
					Consts::KEY_NAME => $name,
					Consts::DURATION => 0,
					Consts::KEY_FILE_COUNT => 0,
					Consts::KEY_OPCACHE_SIZE => 0,
				],
			];
		}
		
		foreach($this->data[Consts::WP_THEMES] as $dir => &$theme_stats){

			$theme = wp_get_theme($dir);

			$theme_stats[Consts::KEY_VERSION] = $theme->get('Version');

			$theme_stats[Consts::KEY_SLUG] = $dir;

			$theme_stats[Consts::KEY_NAME] = $theme->get('Name');


		}

		$this->data[Consts::WP_CORE][Consts::WP_ADMIN][Consts::KEY_VERSION] = get_bloginfo('version');

		$this->data[Consts::WP_CORE][Consts::WP_INCLUDES][Consts::KEY_VERSION] = get_bloginfo('version');
	}

	private function set_path_data($file,$path){

		$relative_filepath = substr( $path, $this->abs_len );
	
		foreach( $this->root as $name => $cat_root ){

			if( !str_starts_with($relative_filepath, $cat_root) ){
				continue;
			}

			$memory_size = $this->opcache_scripts[$relative_filepath] ?? 0;
	
			$root_folder = $this->get_root_folder( $relative_filepath );

			if($name === Consts::WP_PLUGINS && str_ends_with($root_folder, '.php')){

				$root_folder = basename($root_folder, '.php');

			}

			if( !isset($this->data[$name][$root_folder]) ){

				$this->data[$name][$root_folder] = [
					Consts::KEY_OPCACHE_SIZE => 0,
					Consts::KEY_FILE_COUNT => 0
				];
			}

			$this->data[$name][$root_folder][Consts::KEY_OPCACHE_SIZE] += $memory_size;

			$this->data[$name][$root_folder][Consts::KEY_FILE_COUNT] += 1;

			$this->data[$name][$root_folder]['files'][] = $relative_filepath;

		}
		
	}

	private function get_root_folder($relative_filepath){

		$pieces = explode('/', $relative_filepath);

		switch($pieces[1]){

			case Consts::WP_ADMIN:
			case Consts::WP_INCLUDES:
				return $pieces[1];

			default:
				return $pieces[3];
		}

	}

}

