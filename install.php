<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
global $db;

$sql = "INSERT INTO dpviz (`panzoom`,`horizontal`,`datetime`,`dynmembers`,`combineQueueRing`,`extOptional`,`fmfm`) VALUES (1,0,1,0,0,0,0)";
$result = $db->query($sql);

if (DB::isError($result)) {
    die("Error inserting data: " . $result->getMessage());
}
