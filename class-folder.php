<?php

namespace WPSD\site_health;

class Folder{
	
	const FAIL = '<span class="eval-icon eval-fail">✗</span>';

	const PASS = '<span class="eval-icon eval-pass">✓</span>';

	private object $root;

	private string $abs_path;

	private int $abs_len;

	private array $data = [ Consts::WP_CORE => [] ];

	private array $opcache_scripts = [];

	private string $na; //not available

	private string $ms;//milliseconds

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

		$this->na = __('N/A', 'wpsd-site-health');

		$this->ms = __('ms', 'wpsd-site-health');
	}

	private function set_opcache_data(){
	
			switch(true){
			case !function_exists('opcache_get_status'):
			case ini_get('opcache.restrict_api'):
			case empty( @opcache_get_status()['scripts'] ):
				$opcache_raw = [];
				break;

			default:
				$opcache_raw = opcache_get_status()['scripts'] ?? [];
				break;
		}
		
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

		$this->add_results();

		return $this->data;	
	}

	private function add_results(){

		foreach( $this->data[Consts::WP_PLUGINS] 
			as $slug => &$plugin ){

			$plugin[Consts::RESULT_COUNT] = 
				$this->get_result_markup(
					Consts::EVAL_PLUGIN_MAX_COUNT,
					$plugin[Consts::FILE_COUNT],
					Consts::FILE_COUNT
				);

			$plugin[Consts::RESULT_OPCACHE] = 
				$this->get_result_markup(
					Consts::EVAL_PLUGIN_MAX_OPCACHE,
					$plugin[Consts::FILE_OPCACHE_SIZE],
					Consts::FILE_OPCACHE_SIZE
				);

			$plugin[Consts::RESULT_TIME] = 
				$this->get_result_markup(
					Consts::EVAL_PLUGIN_MAX_TIME,
					$plugin[Consts::DURATION]??$this->na,
					Consts::DURATION
				);

			//passes 0 for sorting into JS when data not available
			if( !is_numeric($plugin[Consts::DURATION]??false) ){
				$plugin[Consts::DURATION] = 0;
			}
		}

		foreach( $this->data[Consts::WP_THEMES] 
			as $slug => &$theme ){

			$theme[Consts::RESULT_COUNT] = 
				$this->get_result_markup(
					Consts::EVAL_THEME_MAX_COUNT,
					$theme[Consts::FILE_COUNT],
					Consts::FILE_COUNT
				);

			$theme[Consts::RESULT_OPCACHE] = 
				$this->get_result_markup(
					Consts::EVAL_THEME_MAX_OPCACHE,
					$theme[Consts::FILE_OPCACHE_SIZE],
					Consts::FILE_OPCACHE_SIZE
				);

			$theme[Consts::RESULT_TIME] = 
				$this->get_result_markup(
					Consts::EVAL_THEME_MAX_TIME,
					$theme[Consts::DURATION],
					Consts::DURATION
				);
		}

		foreach( $this->data[Consts::WP_CORE] 
			as $slug => &$core ){

			$core[Consts::RESULT_COUNT] = $core[Consts::FILE_COUNT];

			$core[Consts::RESULT_OPCACHE] = empty( $core[Consts::FILE_OPCACHE_SIZE] ) ? $this->na : $this->convert_to_kb_mb($core[Consts::FILE_OPCACHE_SIZE]);

			$core[Consts::RESULT_TIME] = $this->na;
		}
	}

	private function get_result_markup($max_value,$current_value,$context=0){

		switch($context){
		
			case Consts::FILE_COUNT:
				$eval = $current_value > $max_value ? self::FAIL : self::PASS;
				$percentile = $this->get_percentile_markup($current_value, $max_value );
				$result = "{$current_value} / {$max_value} ( {$percentile} ) {$eval}";
				break;

			case Consts::FILE_OPCACHE_SIZE:
				if($current_value){
					$eval = $current_value > $max_value ? self::FAIL : self::PASS;
					$percentile = $this->get_percentile_markup($current_value, $max_value );
					$current_size = $this->convert_to_kb_mb($current_value);
					$max_size = $this->convert_to_kb_mb($max_value);
					$result = "{$current_size} / {$max_size} ( {$percentile} ) {$eval}";
				} else {
					$result = $this->na;
				}
				break;

			case Consts::DURATION:
				
				if(!is_numeric($current_value)){
					return $this->na;
				} 
					
				$eval = $current_value > $max_value ? self::FAIL : self::PASS;
				$current_markup = $current_value === 0 ? ">0 {$this->ms}" : "{$current_value} ms";
				$result = "{$current_markup} / {$max_value} {$this->ms} {$eval}";
				break;

			default:
				$result = $current_value > $max_value ? self::FAIL : self::PASS;
				break;
		}
		
		return $result;
		
	}

	private function convert_to_kb_mb($int_value){
		
		switch(true){
			case $int_value > 1024 * 1024:
				return number_format($int_value / 1024 / 1024, 0).' MB';
			case $int_value > 1024:
				return number_format($int_value / 1024, 0).' KB';
			default:
				return $int_value.' B';
		}
	}

	private function get_percentile_markup($current_value, $max_value ){
	
		return number_format($current_value/$max_value*100).'%';
		
	}
	private function add_durations(){
	
		$plugins_time = measure_plugin_time('',true);

		foreach( $plugins_time as $plugin_slug => $duration ){

			$this->data['plugins'][$plugin_slug][Consts::DURATION] = $duration;
		}
		
		foreach( $this->data[Consts::WP_THEMES] as $theme_slug => $theme_data ){
			
			$this->data[Consts::WP_THEMES][$theme_slug][Consts::DURATION] = get_hrtime_ms( measure_theme_start(), measure_theme_end());
			break;
		}
	}

	private function move_core_into_cat(){
		
		$this->data[Consts::WP_CORE] = [

			Consts::WP_INCLUDES => $this->data[Consts::WP_INCLUDES][Consts::WP_INCLUDES],
			Consts::WP_ADMIN => $this->data[Consts::WP_ADMIN][Consts::WP_ADMIN]
		];
		
		unset($this->data[Consts::WP_INCLUDES], $this->data[Consts::WP_ADMIN]);
	}

	private function add_versions(){

		// Single site OR site-specific plugins
		$active_plugins = get_option('active_plugins', []);
		
		// Multisite network-activated plugins
		if(is_multisite()){
			$network_plugins = get_site_option('active_sitewide_plugins', []);
			$active_plugins = array_merge($active_plugins, array_keys($network_plugins));
		}
		
		foreach($active_plugins as $plugin_file){
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
			$plugin_dir = dirname($plugin_file);
			$version = $plugin_data['Version'] ?? 'unknown';
			$name = $plugin_data['Name'] ?? 'unknown';
			
			foreach($this->data[Consts::WP_PLUGINS] as $dir => &$plugin_stats){
				if($dir === $plugin_dir){
					$plugin_stats[Consts::VERSION] = $version;
					$plugin_stats[Consts::SLUG] = $plugin_file;
					$plugin_stats[Consts::NAME] = $name;
					$plugin_stats[Consts::HASH] = hash('fnv164', $plugin_file);
					break;
				}
			}
		}
		
		// Rest of theme/core code stays the same...
		$theme = wp_get_theme();
		$current_theme = $theme->get_stylesheet();
		$version = $theme->get('Version') ?? 'unknown';
		$name = $theme->get('Name') ?? 'unknown';
		
		foreach($this->data[Consts::WP_THEMES] as $dir => &$theme_stats){
			if($dir === $current_theme){
				$theme_stats[Consts::VERSION] = $version;
				$theme_stats[Consts::SLUG] = $current_theme;
				$theme_stats[Consts::NAME] = $name;
				$theme_stats[Consts::HASH] = hash('fnv164', $current_theme);
				break;
			}
		}

		$this->data[Consts::WP_CORE][Consts::WP_ADMIN][Consts::VERSION] = get_bloginfo('version');
		$this->data[Consts::WP_CORE][Consts::WP_ADMIN][Consts::HASH] = hash('fnv164', Consts::WP_ADMIN);

		$this->data[Consts::WP_CORE][Consts::WP_INCLUDES][Consts::VERSION] = get_bloginfo('version');
		$this->data[Consts::WP_CORE][Consts::WP_INCLUDES][Consts::HASH] = hash('fnv164', Consts::WP_INCLUDES);
	}

	private function set_path_data($file,$path){

		$relative_filepath = substr( $path, $this->abs_len );
	
		foreach( $this->root as $name => $cat_root ){

			if( !str_starts_with($relative_filepath, $cat_root) ){
				continue;
			}

			$memory_size = $this->opcache_scripts[$relative_filepath] ?? 0;
	
			$disk_size = file_exists($file) ? filesize($file) : 0;

			$root_folder = $this->get_root_folder( $relative_filepath );

			if( !isset($this->data[$name][$root_folder]) ){

				$this->data[$name][$root_folder] = [
					Consts::FILE_OPCACHE_SIZE => 0,
					Consts::FILE_COUNT => 0
				];
			}

			$this->data[$name][$root_folder][Consts::FILE_OPCACHE_SIZE] += $memory_size;

			$this->data[$name][$root_folder][Consts::FILE_COUNT] += 1;

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
