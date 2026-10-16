<<<<<<< HEAD
<?php

namespace WPSD\site_health;

$texts = array_map('esc_html', get_display_texts());

$submission = <<<HTML
<p role="status">{$texts['sent']}</p>
HTML;

if(!get_transient('wpsd_bloat_sent_'.get_current_user_id())){

	$submission = <<<HTML
	<div id="wpsd-send-actions">
		<button id="wpsd-send-main" type="button" class="button button-primary" aria-haspopup="dialog" aria-controls="wpsd-contribute-modal">{$texts['send']}</button>
	</div>
	<dialog id="wpsd-contribute-modal" class="wpsd-data-modal" aria-label="{$texts['send']}">
		<form id="wpsd-send-data">
			<div class="wpsd-contribute-actions">
				<button id="wpsd-contribute" type="submit" class="button button-primary" disabled>{$texts['contribute_data']}</button>
				<button id="wpsd-why-send" type="button" class="button-link" aria-haspopup="dialog" aria-controls="wpsd-send-explanation">{$texts['why_send']}</button>
			</div>
			<div id="wpsd-submission-items"></div>
			<div>
				<label><input id="wpsd-data-consent" type="checkbox" required> {$texts['consent']}</label>
				<button id="wpsd-privacy-policy" type="button" class="button-link" aria-haspopup="dialog" aria-controls="wpsd-privacy-modal">{$texts['privacy']}</button>
			</div>
			<span id="wpsd-send-status" role="status"></span>
		</form>
		<form method="dialog">
			<button class="button" autofocus>{$texts['close']}</button>
		</form>
	</dialog>
	<dialog id="wpsd-send-explanation" class="wpsd-data-modal" aria-labelledby="wpsd-send-explanation-title" aria-describedby="wpsd-send-explanation-text">
		<h2 id="wpsd-send-explanation-title">{$texts['why_send']}</h2>
		<p id="wpsd-send-explanation-text">{$texts['send_explanation']}</p>
		<p>{$texts['send_reform']}</p>
		<p>{$texts['send_mission']}</p>
		<p>{$texts['send_contents']}</p>
		<p>{$texts['send_visualization']}</p>
		<form method="dialog">
			<button class="button" autofocus>{$texts['close']}</button>
		</form>
	</dialog>
	<dialog id="wpsd-privacy-modal" class="wpsd-data-modal" aria-labelledby="wpsd-privacy-title">
		<h2 id="wpsd-privacy-title">{$texts['privacy_title']}</h2>
		<p>{$texts['privacy_data']}</p>
		<p>{$texts['privacy_purpose']}</p>
		<p>{$texts['privacy_local']}</p>
		<p>{$texts['privacy_gdpr']}</p>
		<form method="dialog">
			<button class="button" autofocus>{$texts['close']}</button>
		</form>
	</dialog>
	HTML;

}

echo <<<HTML
<div id="wpsd-bloat-top" class="health-check-body health-check-status-tab">
	<p>{$texts['title']}</p>
	<p>{$texts['ttfb']} <span id="wpsd-ttfb-value">–</span></p>
	{$submission}
	<p>{$texts['comparison']}</p>
	<div id="wpsd-bloat-data"></div>
	<h3 id="wpsd-resources">{$texts['resources']}</h3>
	<p>{$texts['resources_description']}</p>
	<div id="wpsd-enqueued-resources"></div>
</div>
HTML;
=======
<?php

namespace WPSD\site_health;

$css = file_get_contents( __DIR__.'/style.css' );

require_once __DIR__.'/class-folder.php';

$folder = new Folder();

$data = $folder->get_files();

$table = get_required_files_table_markup($data);

echo <<<HTML
<style>{$css}</style>
{$table}
HTML;
?>
>>>>>>> origin/main
