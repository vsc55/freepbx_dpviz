<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$filename = isset($_POST['file']) ? $_POST['file'] : '';
$lang = isset($_POST['lang']) ? preg_replace('/[^a-z_]/', '', $_POST['lang']) : 'en';

$path = "/var/lib/asterisk/sounds/$lang/$filename.wav";

if (file_exists($path) && is_readable($path)) {
    header('Content-Type: audio/wav');
    header('Content-Length: ' . filesize($path));
    header('X-Filename: ' . "$lang/$filename");
    readfile($path);
    exit;
} else {
    http_response_code(404);
    echo "File not found.";
    exit;
}