<?php

abstract class Ajax_Data{

	const KEY_TTFB = 0;

	const KEY_GROUPS = 1;

	const KEY_LOCALE = 2;

	const KEY_PLUGINS = 0;

	const KEY_THEMES = 1;

	const KEY_WP_CORE = 2;

	const KEY_SLUG = 0;

	const KEY_NAME = 1;

	const KEY_VERSION = 2;

	const KEY_INITIAL = 3;

	const KEY_SHUTDOWN = 4;

	const KEY_SCRIPTS = 5;

	const KEY_STYLES = 6;

	const KEY_FILE_COUNT = 0;

	const KEY_OPCACHE_SIZE = 1;

	const KEY_LOAD_DURATION = 2;

}

if(function_exists('apcu_add') && apcu_enabled() && !apcu_add('wp_bloat_receiver:'.($_SERVER['REMOTE_ADDR'] ?? ''), 1, 60)){

	header('Content-Type: application/json; charset=utf-8');

	header('Cache-Control: no-store');

	header('Retry-After: 60');

	http_response_code(429);

	echo json_encode(['success' => false]);

	die;

}

header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: no-store');

header('X-Content-Type-Options: nosniff');

$success = wp_bloat_receive_data();

echo json_encode(['success' => $success]);

die;

function wp_bloat_has_keys($value, $keys){

	if(!is_array($value)){

		return false;

	}

	$actual_keys = array_keys($value);

	return $actual_keys === $keys;

}

function wp_bloat_valid_number($value){

	if($value === null){

		return true;

	}

	return (is_int($value) || is_float($value)) && is_finite((float)$value) && $value >= 0;

}

function wp_bloat_valid_data($data){

	if(!wp_bloat_has_keys($data, [Ajax_Data::KEY_TTFB, Ajax_Data::KEY_GROUPS, Ajax_Data::KEY_LOCALE]) || !wp_bloat_valid_number($data[Ajax_Data::KEY_TTFB])){

		return false;

	}

	if(!is_string($data[Ajax_Data::KEY_LOCALE]) || $data[Ajax_Data::KEY_LOCALE] === '' || strlen($data[Ajax_Data::KEY_LOCALE]) > 1024 || strpos($data[Ajax_Data::KEY_LOCALE], "\0") !== false){

		return false;

	}

	if(!wp_bloat_has_keys($data[Ajax_Data::KEY_GROUPS], [Ajax_Data::KEY_PLUGINS, Ajax_Data::KEY_THEMES, Ajax_Data::KEY_WP_CORE])){

		return false;

	}

	foreach([Ajax_Data::KEY_PLUGINS, Ajax_Data::KEY_THEMES, Ajax_Data::KEY_WP_CORE] as $type){

		$items = $data[Ajax_Data::KEY_GROUPS][$type];

		if(!is_array($items) || count($items) > 1000){

			return false;

		}

		foreach($items as $item){

			if(!wp_bloat_has_keys($item, [Ajax_Data::KEY_SLUG, Ajax_Data::KEY_NAME, Ajax_Data::KEY_VERSION, Ajax_Data::KEY_INITIAL, Ajax_Data::KEY_SHUTDOWN, Ajax_Data::KEY_SCRIPTS, Ajax_Data::KEY_STYLES])){

				return false;

			}

			foreach([Ajax_Data::KEY_SLUG, Ajax_Data::KEY_NAME, Ajax_Data::KEY_VERSION] as $key){

				if(!is_string($item[$key]) || strlen($item[$key]) > 1024 || strpos($item[$key], "\0") !== false){

					return false;

				}

			}

			foreach([Ajax_Data::KEY_INITIAL, Ajax_Data::KEY_SHUTDOWN] as $stage){

				$metrics = $stage === Ajax_Data::KEY_INITIAL ? [Ajax_Data::KEY_FILE_COUNT, Ajax_Data::KEY_OPCACHE_SIZE, Ajax_Data::KEY_LOAD_DURATION] : [Ajax_Data::KEY_FILE_COUNT, Ajax_Data::KEY_OPCACHE_SIZE];

				if(!wp_bloat_has_keys($item[$stage], $metrics)){

					return false;

				}

				foreach($metrics as $metric){

					if(!wp_bloat_valid_number($item[$stage][$metric])){

						return false;

					}

				}

			}

			foreach([Ajax_Data::KEY_SCRIPTS, Ajax_Data::KEY_STYLES] as $resource){

				if(!wp_bloat_valid_number($item[$resource])){

					return false;

				}

			}

		}

	}

	return true;

}

function wp_bloat_append_data($data){

	$storage_file = __DIR__.'/wp-bloat-data.json';

	if(is_link($storage_file)){

		return false;

	}

	$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	if($json === false){

		return false;

	}

	$previous_umask = umask(0077);

	$line = $json.PHP_EOL;

	$written = @file_put_contents($storage_file, $line, FILE_APPEND | LOCK_EX);

	umask($previous_umask);

	return $written === strlen($line);

}

function wp_bloat_receive_data(){

	if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'){

		header('Allowed: POST');

		http_response_code(405);

		return false;

	}

	if(!empty($_SERVER['QUERY_STRING'])){

		http_response_code(400);

		return false;

	}

	$content_type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));

	if(!in_array($content_type, ['application/json', 'application/x-www-form-urlencoded'], true)){

		http_response_code(415);

		return false;

	}

	$body = file_get_contents('php://input', false, null, 0, 1048577);

	if($body === false || strlen($body) > 1048576){

		http_response_code(413);

		return false;

	}

	if($content_type === 'application/x-www-form-urlencoded'){

		if(strncmp($body, 'data=', 5) !== 0 || strpos($body, '&') !== false){

			http_response_code(400);

			return false;

		}

		$body = urldecode(substr($body, 5));

	}

	$data = json_decode($body, false, 16);

	if(json_last_error() !== JSON_ERROR_NONE || !wp_bloat_valid_data($data)){

		http_response_code(400);

		return false;

	}

	if(!wp_bloat_append_data($data)){

		http_response_code(500);

		return false;

	}

	return true;

}
