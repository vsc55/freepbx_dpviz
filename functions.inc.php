<?php 
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

function options_gets() {
    $rows = \FreePBX::Dpviz()->getOptions();
    return (!empty($rows[0]) && is_array($rows[0])) ? $rows[0] : [];
}

?>
