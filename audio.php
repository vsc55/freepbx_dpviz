<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$recording= $results[0];
/*
if (empty($recording['fcode_lang'])){
	$lang='en';
}else{
	$lang= $recording['fcode_lang'];
}
*/

$filename= $recording['filename'];
$displayname= $recording['displayname'];

$path = "/var/lib/asterisk/sounds/$lang/$filename.wav";

if (file_exists($path) && is_readable($path)) {
    header('Content-Type: audio/wav');
    header('Content-Length: ' . filesize($path));
		header('X-Displayname: ' . $displayname);
		header('X-Filename: ' . $lang.'/'.$filename);
    //header('Content-Disposition: inline; filename="' . basename($path) . '"');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($path);
    exit;
} else {
    http_response_code(404);
    echo "File not found.";
    exit;
}