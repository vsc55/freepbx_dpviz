<?php

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2014 Schmooze Com Inc.
//
global $db;
global $amp_conf;

$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT":"AUTO_INCREMENT";
// Check if table exists first
$sql = "SHOW TABLES LIKE 'dpviz'";
$exists = $db->getOne($sql);
$table_created = false;

if (empty($exists)) {
    // Table doesn't exist, so create it
    $sql = "CREATE TABLE dpviz (
        id INTEGER NOT NULL PRIMARY KEY $autoincrement,
        datetime TINYINT(1) NOT NULL DEFAULT 1,
        horizontal TINYINT(1) NOT NULL DEFAULT 0,
        panzoom TINYINT(1) NOT NULL DEFAULT 1,
        dynmembers TINYINT(1) NOT NULL DEFAULT 0,
        combineQueueRing TINYINT(1) NOT NULL DEFAULT 0,
				extOptional TINYINT(1) NOT NULL DEFAULT 0,
        fmfm TINYINT(1) NOT NULL DEFAULT 0
    )";
    $check = $db->query($sql);
    if (DB::IsError($check)) {
        die_freepbx("Can not create dpviz table");
    }

    $table_created = true;
}

// Insert default row if the table was just created
if ($table_created) {
    $sql = "INSERT INTO dpviz (datetime, horizontal, panzoom, dynmembers, combineQueueRing, extOptional, fmfm) VALUES (1, 0, 1, 0, 0, 0, 0)";
    $check = $db->query($sql);
    if (DB::IsError($check)) {
        die_freepbx("Failed to insert initial row");
    }
}


// Version 0.22 adds minimal view
$sql = "SELECT minimal FROM dpviz";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE dpviz ADD minimal TINYINT(1) NOT NULL DEFAULT 0;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
		
		// Only update row if the column was just added
    $sql = "UPDATE dpviz SET minimal = 0 WHERE id = 1;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}

// Version 0.23 adds queue_member_display
$sql = "SELECT queue_member_display FROM dpviz";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE dpviz ADD queue_member_display TINYINT(1) NOT NULL DEFAULT 0;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
		
		// Only update row if the column was just added
    $sql = "UPDATE dpviz SET queue_member_display = 1 WHERE id = 1;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}

// Version 0.23 adds ring_member_display
$sql = "SELECT ring_member_display FROM dpviz";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE dpviz ADD ring_member_display TINYINT(1) NOT NULL DEFAULT 0;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
		
		// Only update row if the column was just added
    $sql = "UPDATE dpviz SET ring_member_display = 1 WHERE id = 1;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}