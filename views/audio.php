<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

//getrecording
if (isset($fpbxResults) && !empty($fpbxResults['playbacklist'])){
	$audiolist='';
	foreach ($fpbxResults['playbacklist'] as $f){
		if (!empty($fpbxResults['soundlist'][$f]['filenames'][$lang])){
			$audiolist.=$lang.'/'.$f.'&';
		}
	}
	$audiolist = rtrim($audiolist, '&');
	
	$results['displayname']=$fpbxResults['displayname'];
	$results['filename']=$audiolist;
}

//getfile
if (isset($_POST['file'])){
	$filename= $_POST['file'];
	error_log($filename);
	
	$path = "/var/lib/asterisk/sounds/$filename.wav";

	if (file_exists($path) && is_readable($path)) {
			header('Content-Type: audio/wav');
			header('Content-Length: ' . filesize($path));
			header('X-Filename: ' . "$filename");
			readfile($path);
			exit;
	} else {
			http_response_code(404);
			echo "File not found.";
			exit;
	}

}