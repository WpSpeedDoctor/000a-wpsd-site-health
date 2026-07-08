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
