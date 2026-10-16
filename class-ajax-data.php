<?php

namespace WPSD\site_health;

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

	static public function receive_bloat_data(){

		if(!current_user_can('view_site_health_checks')){

			wp_send_json_error(['message' => __('You do not have permission to send data.', 'wpsd-site-health')], 403);

			return false;

		}

		if(!check_ajax_referer('wpsd_send_bloat_data', 'nonce', false)){

			wp_send_json_error(['message' => __('Your session has expired. Refresh the page and try again.', 'wpsd-site-health')], 403);

			return false;

		}

		if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['consent'] ?? '') !== '1' || !is_string($_POST['data'] ?? null)){

			wp_send_json_error(['message' => __('The submission is invalid or privacy consent is missing.', 'wpsd-site-health')], 400);

			return false;

		}

		if(get_transient(Consts::KEY_TRANSIENT_SENT)){

			wp_send_json_success();

			return false;

		}

		$data = self::sanitize_bloat_data(json_decode(wp_unslash($_POST['data']), true));

		if($data === false){

			wp_send_json_error(['message' => __('The performance data is invalid. Refresh the page and try again.', 'wpsd-site-health')], 400);

			return false;

		}

		$response = wp_remote_post('https://data.wpspeeddoctor.com/receiver.php', [

			'headers' => ['Content-Type' => 'application/json'],

			'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 ',

			'body' => wp_json_encode($data),

			'timeout' => 10,

			'redirection' => 0,

		]);

		if(is_wp_error($response)){

			wp_send_json_error(['message' => sprintf(__('Unable to connect to the data receiver: %s', 'wpsd-site-health'), $response->get_error_message())], 502);

			return false;

		}

		$response_code = wp_remote_retrieve_response_code($response);

		if($response_code !== 200){

			$message = sprintf(__('The data receiver returned HTTP %d. Please try again later.', 'wpsd-site-health'), $response_code);

			if($response_code === 429){

				$message = __('Too many requests (HTTP 429). Wait at least one minute before trying again.', 'wpsd-site-health');

			}

			wp_send_json_error(['message' => $message], $response_code === 429 ? 429 : 502);

			return false;

		}

		$result = json_decode(wp_remote_retrieve_body($response), true);

		if(($result['success'] ?? false) !== true){

			wp_send_json_error(['message' => __('The data receiver returned an invalid response or did not accept the data. Please try again later.', 'wpsd-site-health')], 502);

			return false;

		}

		if(wp_get_environment_type() !== 'development'){

			set_transient(Consts::KEY_TRANSIENT_SENT, true, HOUR_IN_SECONDS);
		}		

		wp_send_json_success();

	}

	static public function sanitize_bloat_number($value){

		if(!is_int($value) && !is_float($value)){

			return null;

		}

		return is_finite((float)$value) && $value >= 0 ? $value : null;

	}

	static public function sanitize_bloat_data($data){

		if(!is_array($data) || !is_array($data['groups'] ?? null)){

			return false;

		}

		$clean = [

			self::KEY_TTFB => self::sanitize_bloat_number($data['ttfb'] ?? null),

			self::KEY_GROUPS => [],

			self::KEY_LOCALE => get_locale(),

		];

		foreach([self::KEY_PLUGINS => 'plugins', self::KEY_THEMES => 'themes', self::KEY_WP_CORE => 'wp-core'] as $type_key => $type){

			if(!is_array($data['groups'][$type] ?? null)){

				return false;

			}

			$clean[self::KEY_GROUPS][$type_key] = [];

			foreach($data['groups'][$type] as $item){

				if(!is_array($item)){

					return false;

				}

				$entry = [];

				foreach([self::KEY_SLUG => 'slug', self::KEY_NAME => 'name', self::KEY_VERSION => 'version'] as $entry_key => $key){

					if(!is_string($item[$key] ?? null)){

						return false;

					}

					$entry[$entry_key] = $key === 'slug' && $type !== 'plugins' ? sanitize_title($item[$key]) : sanitize_text_field($item[$key]);

				}

				foreach([self::KEY_INITIAL => 'initial', self::KEY_SHUTDOWN => 'shutdown'] as $stage_key => $stage){

					if(!is_array($item[$stage] ?? null)){

						return false;

					}

					$metrics = $stage === 'initial' ? [self::KEY_FILE_COUNT => 'file_count', self::KEY_OPCACHE_SIZE => 'opcache_size', self::KEY_LOAD_DURATION => 'load_duration'] : [self::KEY_FILE_COUNT => 'file_count', self::KEY_OPCACHE_SIZE => 'opcache_size'];

					foreach($metrics as $metric_key => $metric){

						$entry[$stage_key][$metric_key] = self::sanitize_bloat_number($item[$stage][$metric] ?? null);

					}

				}

				foreach([self::KEY_SCRIPTS => 'scripts', self::KEY_STYLES => 'styles'] as $resource_key => $resource){

					$entry[$resource_key] = self::sanitize_bloat_number($item[$resource] ?? null);

				}

				$clean[self::KEY_GROUPS][$type_key][] = $entry;

			}

		}

		return $clean;

	}

}
