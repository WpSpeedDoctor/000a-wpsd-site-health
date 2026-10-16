<?php


add_action('wpsd-debug-fetch', __NAMESPACE__.'\\test_code');

function test_code($value){
	echo 'yes';
	$r=wp_get_environment_type();
	vd($r);
}



// add_action('wpsd-debug-fetch', __NAMESPACE__.'\\receiver_test');

// wySWmnDJ7AgegnqmcUaJ
function receiver_test($value){

	$response = wp_remote_post('https://data.wpspeeddoctor.com/receiver.php', [

		'headers' => [

			'Accept' => 'application/json',

			'Content-Type' => 'application/json; charset=utf-8',

		],

		'user-agent' => 'WPSD-Site-Health/1.0',

		'body' => wp_json_encode(['abc' => 'def']),

		'timeout' => 10,

		'redirection' => 0,

	]);

	// $url = "https://data-input.22web.org/";

	// $user_agent="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36";

	// $response = wp_remote_get(
	// 	$url,
	// 	array(
	// 		'user-agent' => $user_agent
	// 	)
	// );

	// $response_code = wp_remote_retrieve_response_code( $response );

	$response_body = wp_remote_retrieve_body( $response );
	
	echo substr(__FILE__,defined('ABSPATH') ? strlen(ABSPATH)-1 : 0).':'.__LINE__.'<br><div style="white-space: break-spaces;line-height: 2;font-family: monospace;">';
	ob_start();var_dump( $response_body );echo htmlentities(str_replace("]=>\n ",'] =>',ob_get_clean()) );
	echo'</div><br>';
	
}
