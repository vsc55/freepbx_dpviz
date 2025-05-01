<?php 
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

function options_gets() {
	$row = \FreePBX::Dpviz()->getOptions();
	$i = 0;
	if(!empty($row) && is_array($row)) {
		foreach ($row as $item) {
			$row[$i] = $item;
			$i++;
		}
		return $row;
	} else {
		return [];
	}
}

?>
