<?php

namespace WPSD\site_health;

abstract class Consts{

	const THIS_PLUGIN_SLUG = 'wpsd-bloat';

	const THIS_PLUGIN_JS_SLUG = 'WPSDsH';

	const TAB_SLUG_PHP = 'wpsd-php-bloat';

	const TAB_SLUG_RESOURCES = 'wpsd-resources-bloat';

	const WP_INCLUDES = 'wp-includes';

	const WP_ADMIN = 'wp-admin';

	const WP_CORE = 'wp-core';

	const WP_CONTENT = 'wp-content';

	const WP_PLUGINS = 'plugins';

	const WP_THEMES = 'themes';

	const FILE_OPCACHE_SIZE = 1;

	const FILE_COUNT = 2;

	const VERSION = 3;

	const SLUG = 4;

	const NAME = 5;

	const HASH = 6;

	//constants related to measuring time

	const START_TIME = 8;

	const END_TIME = 9;

	const DURATION = 10;

	//constants related to evaluation, true if passed the test

	const RESULT_COUNT = 11;

	const RESULT_OPCACHE = 12;

	const RESULT_TIME = 13;

	const EVAL_PLUGIN_MAX_COUNT = 10;

	const EVAL_PLUGIN_MAX_OPCACHE = 50*1024;

	const EVAL_PLUGIN_MAX_TIME = 2; //2 ms

	const EVAL_THEME_MAX_COUNT = 10;

	const EVAL_THEME_MAX_OPCACHE = 50*1024;

	const EVAL_THEME_MAX_TIME = 2; //2 ms


	static public function get_self_dir_slug(){

		static $slug;

		$slug || $slug = basename( __DIR__ );

		return $slug;

	}

}